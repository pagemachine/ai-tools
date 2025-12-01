<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service\TranslationProvider;

use Pagemachine\AItools\Domain\Model\Server;
use Pagemachine\AItools\Service\Abstract\AigudeAbstract;
use Pagemachine\AItools\Service\TranslationProvider\TranslationProviderServiceInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AigudeTranslationProviderService extends AigudeAbstract implements TranslationProviderServiceInterface
{
    private FrontendInterface $cache;

    public function __construct(Server $server)
    {
        parent::__construct($server);
        $this->cache = GeneralUtility::makeInstance(CacheManager::class)
            ->getCache('ai_tools');
    }

    public function sendTranslationProviderRequestToApi(): array
    {
        $cacheIdentifier = 'providers_list';

        if ($this->cache->has($cacheIdentifier)) {
            return $this->cache->get($cacheIdentifier);
        }

        $url = $this->domain . '/translate/providers';

        $json = $this->request($url, 'GET', [
            'timeout' => 5,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

        $providers = $json['providers'];

        $this->cache->set($cacheIdentifier, $providers, [], 86400);

        return $providers;
    }

    public function providerSupportedForLanguage(string $languageCode, ?string $countryCode = null, $regionFilter = null): array
    {
        $providers = $this->sendTranslationProviderRequestToApi();

        $supportedProviders = [];
        foreach ($providers as $key => $provider) {
            $supportLanguageCode = null;
            if (!is_null($countryCode)) {
                $code = strtoupper($languageCode).'-'.strtoupper($countryCode);
                if (in_array($code, $provider['canonical_codes'] ?? [])) {
                    $supportLanguageCode = $code;
                }
            }
            if (is_null($supportLanguageCode) && in_array(strtoupper($languageCode), $provider['canonical_codes'] ?? [])) {
                $supportLanguageCode = strtoupper($languageCode);
            }
            if ($supportLanguageCode) {
                if (!is_null($regionFilter)) {
                    if ($provider['region'] !== $regionFilter) {
                        continue;
                    }
                }
                $supportedProviders[] = [
                    'provider' => $key,
                    'languageCode' => $supportLanguageCode,
                    'languageName' => $provider['display_names'][$supportLanguageCode] ?? strtolower($supportLanguageCode),
                ];
            }
        }

        return $supportedProviders;
    }
}
