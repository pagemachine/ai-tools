<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service;

use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ContextRetrievalService
{
    /**
     * Total character cap for the merged context chunk.
     */
    private const CONTEXT_MAX_LENGTH = 800;

    /**
     * Max content elements contributing text when the image is placed on a page
     * rather than on a specific content element.
     */
    private const MAX_PAGE_ELEMENTS = 3;

    /**
     * Text-bearing fields to read from foreign records (news and project-specific
     * tables), in priority order. Only fields present in the table's TCA are used.
     */
    private const FOREIGN_TEXT_FIELDS = ['teaser', 'abstract', 'bodytext', 'description'];

    private function getLogger(): LoggerInterface
    {
        return GeneralUtility::makeInstance(LogManager::class)->getLogger(self::class);
    }

    /**
     * Retrieve context for an image from the records it is placed on.
     *
     * Reads the placement from sys_file_reference and the surrounding text
     * directly from the database, so context is always current without an
     * index rebuild step.
     *
     * @param FileInterface $fileObject The image file
     * @return string[] Zero or one merged context chunk
     */
    public function retrieveContextChunks(FileInterface $fileObject): array
    {
        if (!GeneralUtility::makeInstance(SettingsService::class)->getRagEnabled()) {
            return [];
        }

        $usages = $this->locateUsages($fileObject);
        $this->getLogger()->debug('RAG usage lookup (sys_file_reference)', [
            'file' => $fileObject->getName(),
            'usages' => $usages,
        ]);
        if ($usages === []) {
            return [];
        }

        [$titles, $bodies] = $this->collectText($usages);
        if ($titles === [] && $bodies === []) {
            return [];
        }

        // The filename often disambiguates which of several referenced images is
        // shown (e.g. three speaker portraits on one news record).
        $chunks = $this->mergeChunk(
            $titles,
            $bodies,
            $this->tokenizeFilename($fileObject->getNameWithoutExtension())
        );
        $this->getLogger()->debug('RAG context via page placement', [
            'file' => $fileObject->getName(),
            'chunk' => $chunks[0] ?? '',
        ]);

        return $chunks;
    }

    /**
     * Find where the image is used: sys_file_reference maps the file to the exact
     * records (tt_content / pages / news / ...) referencing it.
     *
     * @return array<int, array{tablenames: string, uid_foreign: int|string}>
     */
    private function locateUsages(FileInterface $fileObject): array
    {
        if ($fileObject instanceof ProcessedFile) {
            $fileObject = $fileObject->getOriginalFile();
        }
        if (!$fileObject instanceof File) {
            return [];
        }

        try {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('sys_file_reference');
            // Hidden and time-restricted records are still valid context for a
            // backend authoring tool, so only deleted rows are excluded.
            $queryBuilder->getRestrictions()->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $rows = $queryBuilder
                ->select('tablenames', 'uid_foreign')
                ->from('sys_file_reference')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid_local',
                        $queryBuilder->createNamedParameter($fileObject->getUid(), Connection::PARAM_INT)
                    )
                )
                ->executeQuery()
                ->fetchAllAssociative();
        } catch (\Exception) {
            return [];
        }

        return array_values(array_filter(
            $rows,
            fn(array $row): bool => !empty($row['tablenames']) && (int) $row['uid_foreign'] > 0
        ));
    }

    /**
     * Gather titles and body texts from every record the image is placed on.
     *
     * @param array<int, array{tablenames: string, uid_foreign: int|string}> $usages
     * @return array{0: string[], 1: string[]} Titles and body texts
     */
    private function collectText(array $usages): array
    {
        $ceUids = [];
        $pageUids = [];
        $foreign = [];

        foreach ($usages as $usage) {
            $uid = (int) $usage['uid_foreign'];
            switch ($usage['tablenames']) {
                case 'tt_content':
                    $ceUids[] = $uid;
                    break;
                case 'pages':
                    $pageUids[] = $uid;
                    break;
                default:
                    $foreign[(string) $usage['tablenames']][] = $uid;
            }
        }

        // Content elements live on a page; resolve which one so the surrounding
        // elements can be read in sorting order.
        $cePlacements = $ceUids !== [] ? $this->resolveContentPlacements($ceUids) : [];
        foreach ($cePlacements as $placement) {
            $pageUids[] = $placement['pid'];
        }
        $pageUids = array_values(array_unique($pageUids));

        $titles = $pageUids !== [] ? $this->fetchPageTitles($pageUids) : [];
        $bodies = $pageUids !== [] ? $this->fetchPageBodies($pageUids, $cePlacements) : [];

        foreach ($foreign as $table => $uids) {
            [$foreignTitles, $foreignBodies] = $this->fetchForeignText($table, array_values(array_unique($uids)));
            $titles = [...$titles, ...$foreignTitles];
            $bodies = [...$bodies, ...$foreignBodies];
        }

        return [$titles, $bodies];
    }

    /**
     * Map content element uids to the page and column they sit in.
     *
     * @param int[] $ceUids
     * @return array<int, array{uid: int, pid: int, colPos: int}> Keyed by content element uid
     */
    private function resolveContentPlacements(array $ceUids): array
    {
        $placements = [];
        foreach ($this->select('tt_content', ['uid', 'pid', 'colPos'], 'uid', $ceUids) as $row) {
            $placements[(int) $row['uid']] = [
                'uid' => (int) $row['uid'],
                'pid' => (int) $row['pid'],
                'colPos' => (int) $row['colPos'],
            ];
        }

        return $placements;
    }

    /**
     * @param int[] $pageUids
     * @return string[]
     */
    private function fetchPageTitles(array $pageUids): array
    {
        $titles = [];
        foreach ($this->select('pages', ['title'], 'uid', $pageUids) as $row) {
            if (!empty($row['title'])) {
                $titles[] = trim((string) $row['title']);
            }
        }

        return $titles;
    }

    /**
     * Body text from the content elements of the pages the image is placed on.
     *
     * When the image sits on a specific content element, that element and its
     * direct neighbours in the same column are used. When it is attached to the
     * page itself, the leading elements are used.
     *
     * @param int[] $pageUids
     * @param array<int, array{uid: int, pid: int, colPos: int}> $cePlacements
     * @return string[]
     */
    private function fetchPageBodies(array $pageUids, array $cePlacements): array
    {
        $rows = $this->select(
            'tt_content',
            ['uid', 'pid', 'colPos', 'header', 'subheader', 'bodytext'],
            'pid',
            $pageUids,
            'sorting'
        );

        $byPage = [];
        foreach ($rows as $row) {
            $byPage[(int) $row['pid']][] = $row;
        }

        $bodies = [];
        foreach ($byPage as $pid => $pageRows) {
            $targetUids = [];
            $targetColPos = [];
            foreach ($cePlacements as $placement) {
                if ($placement['pid'] === $pid) {
                    $targetUids[] = $placement['uid'];
                    $targetColPos[$placement['colPos']] = true;
                }
            }

            if ($targetUids === []) {
                // Attached to the page itself: use the leading elements that carry text.
                $textRows = array_values(array_filter($pageRows, $this->hasText(...)));
                $selected = array_slice($textRows, 0, self::MAX_PAGE_ELEMENTS);
            } else {
                // Restrict to the columns holding the image, then take each matched
                // element plus its direct neighbours in sorting order. Matched
                // elements stay in the list even without text, since they anchor the
                // window; contentless neighbours are dropped so the window lands on
                // elements that actually contribute.
                $columnRows = array_values(array_filter(
                    $pageRows,
                    fn(array $row): bool => isset($targetColPos[(int) $row['colPos']])
                        && (in_array((int) $row['uid'], $targetUids, true) || $this->hasText($row))
                ));
                $indexes = [];
                foreach ($columnRows as $index => $row) {
                    if (in_array((int) $row['uid'], $targetUids, true)) {
                        $indexes[] = $index - 1;
                        $indexes[] = $index;
                        $indexes[] = $index + 1;
                    }
                }
                $selected = [];
                foreach (array_unique($indexes) as $index) {
                    if (isset($columnRows[$index])) {
                        $selected[] = $columnRows[$index];
                    }
                }
            }

            $parts = [];
            foreach ($selected as $row) {
                foreach (['header', 'subheader', 'bodytext'] as $field) {
                    if (!empty($row[$field])) {
                        $parts[] = (string) $row[$field];
                    }
                }
            }

            $body = $this->normalize(implode('. ', $parts));
            if ($body !== '') {
                $bodies[] = $body;
            }
        }

        return $bodies;
    }

    /**
     * Whether a content element row contributes any text.
     *
     * Elements without text still occupy a slot in the selection budget, so
     * pages leading with purely structural elements would otherwise yield
     * title-only context.
     *
     * @param array<string, mixed> $row
     */
    private function hasText(array $row): bool
    {
        return !empty($row['header']) || !empty($row['subheader']) || !empty($row['bodytext']);
    }

    /**
     * Text from news and project-specific records.
     *
     * Field names vary per table, so the table's TCA decides which of the
     * candidate fields exist. Selecting an absent column would be a SQL error,
     * and TCA is stable across TYPO3 12.4-14.0 where Doctrine's schema APIs are
     * not. A table without TCA is skipped entirely.
     *
     * @param int[] $uids
     * @return array{0: string[], 1: string[]} Titles and body texts
     */
    private function fetchForeignText(string $table, array $uids): array
    {
        $columns = $GLOBALS['TCA'][$table]['columns'] ?? null;
        if (!is_array($columns)) {
            return [[], []];
        }

        $labelField = (string) ($GLOBALS['TCA'][$table]['ctrl']['label'] ?? '');
        if ($labelField !== '' && !isset($columns[$labelField])) {
            $labelField = '';
        }

        $textFields = array_values(array_filter(
            self::FOREIGN_TEXT_FIELDS,
            fn(string $field): bool => isset($columns[$field])
        ));

        $fields = array_values(array_unique(array_filter([$labelField, ...$textFields])));
        if ($fields === []) {
            return [[], []];
        }

        $titles = [];
        $bodies = [];
        foreach ($this->select($table, $fields, 'uid', $uids) as $row) {
            if ($labelField !== '' && !empty($row[$labelField])) {
                $titles[] = trim((string) $row[$labelField]);
            }

            $parts = [];
            foreach ($textFields as $field) {
                if (!empty($row[$field])) {
                    $parts[] = (string) $row[$field];
                }
            }
            $body = $this->normalize(implode('. ', $parts));
            if ($body !== '') {
                $bodies[] = $body;
            }
        }

        return [$titles, $bodies];
    }

    /**
     * Fetch rows by an integer column, excluding deleted records only.
     *
     * @param string[] $fields
     * @param int[] $values
     * @return array<int, array<string, mixed>>
     */
    private function select(string $table, array $fields, string $column, array $values, string $orderBy = ''): array
    {
        if ($values === []) {
            return [];
        }

        try {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $queryBuilder
                ->select(...$fields)
                ->from($table)
                ->where(
                    $queryBuilder->expr()->in(
                        $column,
                        $queryBuilder->createNamedParameter($values, Connection::PARAM_INT_ARRAY)
                    )
                );
            if ($orderBy !== '') {
                $queryBuilder->orderBy($orderBy);
            }

            return $queryBuilder->executeQuery()->fetchAllAssociative();
        } catch (\Exception $exception) {
            $this->getLogger()->debug('RAG context query failed', [
                'table' => $table,
                'exception' => $exception->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Convert filename to search terms.
     * "thilo-hartmann-vorsitzender.jpg" -> "thilo hartmann vorsitzender"
     */
    private function tokenizeFilename(string $filename): string
    {
        // Remove common non-descriptive prefixes
        $filename = preg_replace('/^(IMG|DSC|DSCN|DSCF|P|SAM|MOV|VID)[-_]?\d+/i', '', $filename);
        // Remove TYPO3 processed image prefixes (csm_ prefix and hash suffixes)
        $filename = preg_replace('/^csm_/', '', (string) $filename);
        $filename = preg_replace('/_[a-f0-9]{10}$/', '', (string) $filename);
        // Replace separators with spaces
        $terms = preg_replace('/[-_]+/', ' ', (string) $filename);
        // Remove numbers-only tokens (dates, counters)
        $terms = preg_replace('/\b\d+\b/', '', (string) $terms);
        return trim((string) preg_replace('/\s+/', ' ', (string) $terms));
    }

    private function normalize(string $text): string
    {
        // Record text is raw RTE HTML, so entities survive strip_tags and would
        // otherwise reach the prompt as literal "&nbsp;".
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Decoded non-breaking spaces are not matched by \s.
        $text = str_replace("\xC2\xA0", ' ', $text);

        return trim((string) preg_replace('/\s+/', ' ', $text));
    }

    /**
     * Titles from every placement plus the single richest body, capped.
     *
     * @param string[] $titles
     * @param string[] $bodies
     * @return string[]
     */
    private function mergeChunk(array $titles, array $bodies, string $filenameTerms): array
    {
        // Richest single body wins; titles from every usage are merged in front.
        usort($bodies, fn(string $a, string $b): int => strlen($b) <=> strlen($a));
        $hint = $filenameTerms !== '' ? 'Image filename: ' . $filenameTerms : '';
        $chunk = implode('. ', array_unique(array_filter([$hint, ...$titles, $bodies[0] ?? ''])));

        return [mb_substr($chunk, 0, self::CONTEXT_MAX_LENGTH)];
    }
}
