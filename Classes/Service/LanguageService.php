<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service;

use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

class LanguageService
{
    /**
     * Get language options for TCA select field
     * Provides default options (auto, en_US, en_GB) plus all languages from TYPO3 site configuration
     *
     * @param array $config TCA configuration array
     * @return void
     */
    public function getLanguageOptions(array &$config): void
    {
        $items = [];

        $items[] = ['LLL:EXT:ai_tools/Resources/Private/Language/locallang_db.xlf:tx_aitools_domain_model_prompt.language.auto', 'auto'];
        $items[] = ['English (US)', 'en_US'];
        $items[] = ['English (GB)', 'en_GB'];

        $siteLanguages = $this->getAllSiteLanguages();

        foreach ($siteLanguages as $siteLanguage) {
            $languageCode = $this->getLocaleLanguageCode($siteLanguage);

            if (in_array($languageCode, ['en_US', 'en_GB', 'en'])) {
                continue;
            }

            $label = $siteLanguage->getTitle() . ' (' . $languageCode . ')';

            $exists = false;
            foreach ($items as $item) {
                if ($item[1] === $languageCode) {
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                $items[] = [$label, $languageCode];
            }
        }

        $config['items'] = array_merge($config['items'] ?? [], $items);
    }

    public function getTranslationProviderPerLanguage($regionFilter = null): array
    {
        $serverService = GeneralUtility::makeInstance(ServerService::class);
        $providerService = $serverService->getActiveServerClassByFunctionality('translation_provider');

        $siteLanguages = $this->getAllSiteLanguages();

        $languageProviders = [];
        foreach ($siteLanguages as $key => $siteLanguage) {
            $languageCode = $this->getLocaleLanguageCode($siteLanguage);
            $countryCode = $this->getLocaleCountryCode($siteLanguage);
            $providers = $providerService->providerSupportedForLanguage($languageCode, $countryCode, $regionFilter);
            $languageProviders[$siteLanguage->getLanguageId()] = [
                'siteLanguage' => $siteLanguage,
                'providers' => $providers,
            ];
        }

        return $languageProviders;
    }

    /**
     * Get all site languages from all TYPO3 sites
     *
     * @return array
     */
    private function getAllSiteLanguages(): array
    {
        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        $sites = $siteFinder->getAllSites();
        $languages = [];

        foreach ($sites as $site) {
            foreach ($site->getAllLanguages() as $language) {
                $languages[$language->getLanguageId()] = $language;
            }
        }

        return array_values($languages);
    }

    /**
     * Get locale language code from site language
     * Compatible with TYPO3 v11-13
     *
     * @param SiteLanguage $siteLanguage
     * @return string
     */
    private function getLocaleLanguageCode($siteLanguage): string
    {
        $version = GeneralUtility::makeInstance(VersionNumberUtility::class)->getNumericTypo3Version();
        if (version_compare($version, '12.0', '>=')) {
            // @phpstan-ignore-next-line Stop PHPStan about complaining this line for TYPO3 v11
            return $siteLanguage->getLocale()->getLanguageCode();
        }
        return $siteLanguage->getTwoLetterIsoCode(); // @phpstan-ignore-line
    }

    private function getLocaleCountryCode($siteLanguage): ?string
    {
        $version = GeneralUtility::makeInstance(VersionNumberUtility::class)->getNumericTypo3Version();
        if (version_compare($version, '12.0', '>=')) {
            // @phpstan-ignore-next-line Stop PHPStan about complaining this line for TYPO3 v11
            return $siteLanguage->getLocale()->getCountryCode();
        }
        return null;
    }
}
