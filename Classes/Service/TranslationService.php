<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service;

use Pagemachine\AItools\Service\Translation\CustomTranslationService;
use Pagemachine\AItools\Service\Translation\DeepLTranslationService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TranslationService
{
    protected SettingsService $settingsService;
    private readonly ?CustomTranslationService $customTranslationService;
    private readonly ?DeepLTranslationService $deeplTranslationService;

    public function __construct()
    {
        $this->settingsService = GeneralUtility::makeInstance(SettingsService::class);

        $this->customTranslationService = GeneralUtility::makeInstance(CustomTranslationService::class);
        $this->deeplTranslationService = GeneralUtility::makeInstance(DeepLTranslationService::class);
    }

    public function translateText(string $text, string $sourceLanguage = 'en', string $targetLanguage = 'en'): string
    {
        $translationService = $this->settingsService->getSetting('translation_service');
        return match ($translationService) {
            'deepl' => $this->deeplTranslationService->sendTranslationRequestToApi(text: $text, sourceLang: $sourceLanguage, targetLang: $targetLanguage),
            'custom' => $this->customTranslationService->sendTranslationRequestToApi(text: $text, sourceLang: $sourceLanguage, targetLang: $targetLanguage),
            default => throw new \Exception('No valid translation service configured'),
        };
    }
}
