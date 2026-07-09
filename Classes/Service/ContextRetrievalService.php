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
                'index' => 'typo3_pages,typo3_news',
                'body' => [
                    'query' => [
                        'multi_match' => [
                            'fields' => ['*'],
                            'query' => $searchTerms,
                        ],
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
     * @param array<mixed> $esResponse
     * @return string[]
     */
    private function extractChunks(array $esResponse): array
    {
        $chunks = [];
        $hits = $esResponse['hits']['hits'] ?? [];

        foreach ($hits as $hit) {
            $source = $hit['_source'] ?? [];
            $parts = [];

            if (!empty($source['title'])) {
                $parts[] = $source['title'];
            }

            // Content from tt_content sub-documents (pages index)
            if (!empty($source['content']) && is_array($source['content'])) {
                foreach ($source['content'] as $contentElement) {
                    if (!empty($contentElement['bodytext'])) {
                        $text = strip_tags((string) $contentElement['bodytext']);
                        $text = trim((string) preg_replace('/\s+/', ' ', $text));
                        if (strlen($text) > 50) {
                            $parts[] = mb_substr($text, 0, 600);
                        }
                    }
                }
            }

            // News bodytext / teaser
            if (!empty($source['bodytext'])) {
                $text = strip_tags((string) $source['bodytext']);
                $parts[] = mb_substr(trim((string) preg_replace('/\s+/', ' ', $text)), 0, 600);
            }
            if (!empty($source['teaser'])) {
                $parts[] = strip_tags((string) $source['teaser']);
            }

            if (!empty($parts)) {
                $chunks[] = implode('. ', $parts);
            }
        }

        return $chunks;
    }
}
