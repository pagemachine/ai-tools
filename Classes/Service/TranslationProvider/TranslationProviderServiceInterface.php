<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service\TranslationProvider;

use Pagemachine\AItools\Domain\Model\Server;

interface TranslationProviderServiceInterface
{
    public function __construct(Server $server);

    /**
     * Sends a request to the translation provider API to retrieve available providers.
     *
     */
    public function sendTranslationProviderRequestToApi(): array;

    /**
     * Checks if the translation provider supports the given language (and optional country). Returns an array of provider keys.
     */
    public function providerSupportedForLanguage(string $languageCode, ?string $countryCode = null): array;
}
