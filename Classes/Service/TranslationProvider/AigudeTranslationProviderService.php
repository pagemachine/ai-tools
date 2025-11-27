<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service\TranslationProvider;

use Pagemachine\AItools\Service\Abstract\AigudeAbstract;
use Pagemachine\AItools\Service\TranslationProvider\TranslationProviderServiceInterface;

class AigudeTranslationProviderService extends AigudeAbstract implements TranslationProviderServiceInterface
{

    private $resultCache = null;

    public function sendTranslationProviderRequestToApi(): array
    {
        if (!is_null($this->resultCache)) {
            return $this->resultCache;
        }

        $url = $this->domain . '/translate/providers';

        $json = $this->request($url, 'GET', [
            'timeout' => 1,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->resultCache = $json['providers'];

        return $this->resultCache;
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
