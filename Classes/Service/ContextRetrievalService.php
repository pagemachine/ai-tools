<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service;

use PAGEmachine\Searchable\Connection;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ContextRetrievalService
{
    /**
     * Retrieve relevant text chunks from the searchable Elasticsearch index.
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
