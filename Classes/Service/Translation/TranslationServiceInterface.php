<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service\Translation;

use Pagemachine\AItools\Domain\Model\Server;

interface TranslationServiceInterface
{
    public function __construct(Server $server);

    /**
     * Sends a translation request to the translation API and returns the translated text.
     *
     * @return string
     */
    public function sendTranslationRequestToApi(string $text, string $sourceLang = 'en', string $targetLang = 'en'): string;
}
