<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service\Translation;

interface TranslationServiceInterface
{
    /**
     * Sends a translation request to the translation API and returns the translated text.
     *
     * @param string $text
     * @param string $sourceLang
     * @param string $targetLang
     * @return string
     */
    public function sendTranslationRequestToApi(string $text, string $sourceLang = 'en', string $targetLang = 'en'): string;
}
