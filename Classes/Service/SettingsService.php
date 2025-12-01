<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service;

use Pagemachine\AItools\Domain\Repository\ServerRepository;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SettingsService
{
    protected ExtensionConfiguration $extensionConfiguration;
    protected LanguageService $languageService;

    public function __construct() {
        $this->extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        $this->languageService = GeneralUtility::makeInstance(LanguageService::class);
    }

    /**
     * get setting
     *
     * @return mixed|null
     */
    public function getSetting(string $key)
    {
        switch ($key) {
            case 'translation_service':
            case 'image_recognition_service':
            case 'translation_provider_service':
                $serverRepository = GeneralUtility::makeInstance(ServerRepository::class);
                $server = $serverRepository->getDefault();
                if ($server === null) {
                    return null;
                }
                return $server->getUid();
            default:
                throw new \InvalidArgumentException('Unknown settings key: ' . $key, 1759757766);
        }
    }

    /**
     * Get GDPR compliance setting from extension configuration
     */
    public function getGdprCompliant(): bool
    {
        return (bool) $this->extensionConfiguration->get('ai_tools', 'gdprCompliant');
    }

    public function setGdprCompliant(bool $gdprCompliant): void
    {
        $this->setExtConfigValue('gdprCompliant', $gdprCompliant);
    }

    public function getTranslationProviderForLanguage(int $languageId): ?string
    {
        $translationProviders = $this->getTranslationProviders();
        return $translationProviders[$languageId]['active'] ?? null;
    }

    public function getTranslationProviders(): array
    {
        try {
            $providersJson = $this->extensionConfiguration->get('ai_tools', 'translationProviders');
            $providers = json_decode($providersJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception $e) {
            $providers = [];
        }
        $translationProviderOptions = $this->languageService->getTranslationProviderPerLanguage($this->getGdprCompliant() ? 'eu' : null);

        foreach ($translationProviderOptions as $key => $option) {
            $active = $option['providers'][0]['provider'] ?? null;


            if (isset($providers[$option['siteLanguage']->getLanguageId()]) ) {
                $providerKeys = array_column($option['providers'], 'provider');
                if (in_array($providers[$option['siteLanguage']->getLanguageId()], $providerKeys, true)) {
                    $active = $providers[$option['siteLanguage']->getLanguageId()];
                }
            }
            $translationProviderOptions[$key]['active'] = $active;
        }

        return $translationProviderOptions;
    }

    public function setTranslationProviders(array $providers): void
    {
        $this->setExtConfigValue('translationProviders', json_encode($providers, JSON_THROW_ON_ERROR));
    }

    /**
     * Check if the current user has the permission to manage prompts
     *
     * @return bool
     */
    public function checkPermission(string $itemKey): bool
    {
        if (!isset($GLOBALS['BE_USER'])) {
            return false;
        }
        return $GLOBALS['BE_USER']->check('custom_options', 'tx_aitools_permissions' . ':' . $itemKey);
    }

    protected function setExtConfigValue(string $key, mixed $value): void
    {
        $config = $this->extensionConfiguration->get('ai_tools');
        $config[$key] = $value;
        $config = array_intersect_key($config, array_flip(['gdprCompliant', 'translationProviders']));
        $this->extensionConfiguration->set('ai_tools', $config);
    }
}
