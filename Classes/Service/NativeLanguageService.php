<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Fetches native-generation language metadata from the CreditsAPI public endpoint.
 *
 * No Server dependency — the endpoint is public, so this works on fresh
 * installs before any server is configured.
 */
class NativeLanguageService
{
    private readonly FrontendInterface $cache;
    private readonly RequestFactory $requestFactory;

    private const ENDPOINT = 'https://credits.aigude.io/img2desc/native-languages';
    private const CACHE_ID = 'native_languages_v1';
    private const CACHE_LIFETIME = 86400;

    public function __construct()
    {
        $this->cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('ai_tools');
        $this->requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
    }

    /**
     * @return list<array{code: string, name: string, description: string, locale: string, default_prompt: string}>
     */
    public function get(): array
    {
        if ($this->cache->has(self::CACHE_ID)) {
            $languages = $this->cache->get(self::CACHE_ID);
            error_log('NativeLanguageService: cache hit, ' . count($languages) . ' languages');
            return $languages;
        }

        $url = self::ENDPOINT . '?model=aigude-vision-v1';
        error_log('NativeLanguageService: calling ' . $url);

        $response = $this->requestFactory->request(
            $url,
            'GET',
            ['timeout' => 5, 'http_errors' => false]
        );

        if ($response->getStatusCode() !== 200) {
            error_log('NativeLanguageService: API returned HTTP ' . $response->getStatusCode());
            return [];
        }

        $json = json_decode((string) $response->getBody()->getContents(), true);
        $languages = $json['languages'] ?? [];

        error_log('NativeLanguageService: API returned ' . count($languages) . ' languages');
        $this->cache->set(self::CACHE_ID, $languages, [], self::CACHE_LIFETIME);

        return $languages;
    }
}
