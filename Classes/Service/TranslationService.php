<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service;

use Pagemachine\AItools\Service\ServerService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TranslationService
{
    protected ServerService $serverService;

    public function __construct()
    {
        $this->serverService = GeneralUtility::makeInstance(ServerService::class);
    }

    public function translateText(string $text, string $sourceLanguage = 'en', string $targetLanguage = 'en', ?string $translationProvider = null): string
    {
        if ($text == '') {
            return '';
        }

        if ($sourceLanguage == $targetLanguage) {
            return $text;
        }

        $serverClass = $this->serverService->getActiveServerClassByFunctionality('translation');
        return $serverClass->sendTranslationRequestToApi(text: $text, sourceLang: $sourceLanguage, targetLang: $targetLanguage, translationProvider: $translationProvider);
    }
}
