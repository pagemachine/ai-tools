<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service\Translation;

use Pagemachine\AItools\Service\Abstract\AigudeAbstract;
use Pagemachine\AItools\Utility\LanguageScriptUtility;

class AigudeTranslationService extends AigudeAbstract implements TranslationServiceInterface
{
    public function sendTranslationRequestToApi(string $text, string $sourceLang = 'en', string $targetLang = 'en'): string
    {
        $sourceLang = LanguageScriptUtility::getLanguageScript($sourceLang);
        $targetLang = LanguageScriptUtility::getLanguageScript($targetLang);

        $url = $this->domain.'/translate';

        // Prepare the form data
        $formData = [
            'text' => $text,
            'source_lang' => $sourceLang,
            'target_lang' => $targetLang,
        ];

        return $this->request($url, 'POST', [
            'headers' => [
                'apikey' => $this->authToken,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($formData),
        ]);
    }
}
