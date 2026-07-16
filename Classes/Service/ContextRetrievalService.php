<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service;

use PAGEmachine\Searchable\Connection;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ContextRetrievalService
{
    /**
     * Total character cap for the merged proximity context chunk.
     */
    private const CONTEXT_MAX_LENGTH = 800;

    private function getLogger(): LoggerInterface
    {
        return GeneralUtility::makeInstance(LogManager::class)->getLogger(self::class);
    }

    /**
     * Retrieve relevant text chunks from the searchable Elasticsearch index.
     *
     * Primary path: proximity — find the records the image is actually placed on
     * (via sys_file_reference) and use their indexed text. Fallback: filename search.
     *
     * @param FileInterface $fileObject The image file
     * @param int $limit Max chunks to return
     * @return string[] Array of text chunks
     */
    public function retrieveContextChunks(FileInterface $fileObject, int $limit = 1): array
    {
        $settingsService = GeneralUtility::makeInstance(SettingsService::class);
        if (!$settingsService->getRagEnabled()) {
            return [];
        }

        // pagemachine/searchable is an optional dependency; without it RAG is inert.
        if (!class_exists(Connection::class)) {
            return [];
        }

        $chunks = $this->retrieveByUsage($fileObject);
        if (!empty($chunks)) {
            $this->getLogger()->debug('RAG context via page placement (proximity)', [
                'file' => $fileObject->getName(),
                'chunk' => $chunks[0],
            ]);
            return $chunks;
        }

        $chunks = $this->retrieveByFilename($fileObject, $limit);
        $this->getLogger()->debug('RAG context via filename search (fallback)', [
            'file' => $fileObject->getName(),
            'chunks' => $chunks,
        ]);
        return $chunks;
    }

    /**
     * Proximity context: the text of the pages/records the image is placed on.
     *
     * @return string[]
     */
    private function retrieveByUsage(FileInterface $fileObject): array
    {
        $usages = $this->locateUsages($fileObject);
        $this->getLogger()->debug('RAG usage lookup (sys_file_reference)', [
            'file' => $fileObject->getName(),
            'usages' => $usages,
        ]);
        if ($usages === []) {
            return [];
        }

        // Build one query matching the searchable documents of the located records.
        // tt_content usages: the page document embeds per-CE content[] sub-documents,
        // so content.uid == CE uid pinpoints the exact page (CE uids are instance-unique).
        $ceUids = [];
        $should = [];
        foreach ($usages as $usage) {
            $recordUid = (int) $usage['uid_foreign'];
            switch ($usage['tablenames']) {
                case 'tt_content':
                    $ceUids[] = $recordUid;
                    break;
                case 'pages':
                    $should[] = ['bool' => ['must' => [
                        ['term' => ['uid' => $recordUid]],
                        ['exists' => ['field' => 'content']],
                    ]]];
                    break;
                default:
                    // News and other records: match by uid, require a text-bearing field
                    // to avoid uid collisions with unrelated indices.
                    $should[] = ['bool' => [
                        'must' => [['term' => ['uid' => $recordUid]]],
                        'should' => [
                            ['exists' => ['field' => 'teaser']],
                            ['exists' => ['field' => 'bodytext']],
                        ],
                        'minimum_should_match' => 1,
                    ]];
            }
        }
        if ($ceUids !== []) {
            $should[] = ['terms' => ['content.uid' => $ceUids]];
        }

        try {
            $result = Connection::getClient()->search([
                'index' => '_all',
                'body' => [
                    'query' => ['bool' => ['should' => $should, 'minimum_should_match' => 1]],
                    'size' => 5,
                ],
            ]);
        } catch (\Exception $exception) {
            $this->getLogger()->debug('RAG usage search failed', ['exception' => $exception->getMessage()]);
            return [];
        }

        $this->getLogger()->debug('RAG usage search hits', [
            'total' => $result['hits']['total']['value'] ?? 0,
            'ids' => array_map(
                fn(array $hit): string => ($hit['_index'] ?? '?') . '/' . ($hit['_id'] ?? '?'),
                $result['hits']['hits'] ?? []
            ),
        ]);

        // The filename often disambiguates which of several referenced images is shown
        // (e.g. three speaker portraits on one news record) - pass it along as a hint.
        return $this->extractUsageChunks(
            $result,
            $ceUids,
            $this->tokenizeFilename($fileObject->getNameWithoutExtension())
        );
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
            return $queryBuilder
                ->select('tablenames', 'uid_foreign')
                ->from('sys_file_reference')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid_local',
                        $queryBuilder->createNamedParameter($fileObject->getUid(), \TYPO3\CMS\Core\Database\Connection::PARAM_INT)
                    )
                )
                ->executeQuery()
                ->fetchAllAssociative();
        } catch (\Exception) {
            return [];
        }
    }

    /**
     * Build the context from located documents: all titles (cheap, high-signal)
     * plus the single richest body text, capped, to keep the prompt focused.
     *
     * @param array<mixed> $esResponse
     * @param int[] $ceUids CE uids the image is attached to (for narrowing)
     * @param string $filenameTerms Tokenized filename, prepended as a disambiguation hint
     * @return string[]
     */
    private function extractUsageChunks(array $esResponse, array $ceUids, string $filenameTerms = ''): array
    {
        $titles = [];
        $bodies = [];

        foreach (($esResponse['hits']['hits'] ?? []) as $hit) {
            $source = $hit['_source'] ?? [];
            if (!empty($source['title'])) {
                $titles[] = trim((string) $source['title']);
            }

            $parts = [];
            $content = is_array($source['content'] ?? null) ? array_values($source['content']) : [];
            if ($content !== []) {
                // Narrow to the CE the image sits in (± direct neighbors); the
                // content[] array is in page sorting order.
                $matchedIndexes = [];
                foreach ($content as $index => $element) {
                    if (in_array((int) ($element['uid'] ?? 0), $ceUids, true)) {
                        $matchedIndexes[] = $index;
                    }
                }
                // Image placed on the page itself (not a CE): use the leading elements.
                if ($matchedIndexes === []) {
                    $matchedIndexes = [0];
                }
                $indexes = [];
                foreach ($matchedIndexes as $index) {
                    $indexes[] = $index - 1;
                    $indexes[] = $index;
                    $indexes[] = $index + 1;
                }
                foreach (array_unique($indexes) as $index) {
                    foreach (['header', 'subheader', 'bodytext'] as $field) {
                        if (!empty($content[$index][$field])) {
                            $parts[] = (string) $content[$index][$field];
                        }
                    }
                }
            } else {
                // News and other flat documents.
                foreach (['teaser', 'bodytext', 'abstract'] as $field) {
                    if (!empty($source[$field])) {
                        $parts[] = (string) $source[$field];
                    }
                }
            }

            $body = trim((string) preg_replace('/\s+/', ' ', strip_tags(implode('. ', $parts))));
            if ($body !== '') {
                $bodies[] = $body;
            }
        }

        if ($titles === [] && $bodies === []) {
            return [];
        }

        // Richest single body wins; titles from every usage are merged in front.
        usort($bodies, fn(string $a, string $b): int => strlen($b) <=> strlen($a));
        $hint = $filenameTerms !== '' ? 'Image filename: ' . $filenameTerms : '';
        $chunk = implode('. ', array_unique(array_filter([$hint, ...$titles, $bodies[0] ?? ''])));

        return [mb_substr($chunk, 0, self::CONTEXT_MAX_LENGTH)];
    }

    /**
     * Fallback: filename-token search across all indices.
     *
     * @return string[]
     */
    private function retrieveByFilename(FileInterface $fileObject, int $limit): array
    {
        $filename = $fileObject->getNameWithoutExtension();
        $searchTerms = $this->tokenizeFilename($filename);

        if (empty($searchTerms)) {
            return [];
        }

        try {
            $client = Connection::getClient();
            $result = $client->search([
                // Search every searchable index; index names are project-specific
                // (typo3_pages/typo3_news on some, german_website_publications on others).
                'index' => '_all',
                'body' => [
                    'query' => [
                        'multi_match' => [
                            'fields' => ['*'],
                            'query' => $searchTerms,
                        ],
                    ],
                    // Highlighting returns the matched snippet from whatever fields matched,
                    // so context extraction does not depend on a specific document schema.
                    'highlight' => [
                        'fields' => ['*' => (object) []],
                        'fragment_size' => 200,
                        'number_of_fragments' => 3,
                    ],
                    'size' => $limit,
                ],
            ]);
        } catch (\Exception) {
            return [];
        }

        return $this->extractChunks($result);
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

    /**
     * Extract text chunks from Elasticsearch response.
     *
     * Uses highlight fragments (schema-agnostic) with a fallback to the document
     * title, so it works regardless of how a project's searchable index is shaped.
     *
     * @param array<mixed> $esResponse
     * @return string[]
     */
    private function extractChunks(array $esResponse): array
    {
        $chunks = [];
        $hits = $esResponse['hits']['hits'] ?? [];

        foreach ($hits as $hit) {
            $parts = [];

            // Highlight fragments: the matched snippet from whichever fields matched.
            foreach (($hit['highlight'] ?? []) as $fieldFragments) {
                foreach ($fieldFragments as $fragment) {
                    // Highlighting wraps matches in <em>; strip all tags to keep plain text.
                    $text = trim((string) preg_replace('/\s+/', ' ', strip_tags((string) $fragment)));
                    if ($text !== '') {
                        $parts[] = $text;
                    }
                }
            }

            // Fallback: document title when nothing was highlighted.
            if (empty($parts) && !empty($hit['_source']['title'])) {
                $parts[] = (string) $hit['_source']['title'];
            }

            if (!empty($parts)) {
                $chunks[] = implode('. ', array_values(array_unique($parts)));
            }
        }

        return $chunks;
    }
}
