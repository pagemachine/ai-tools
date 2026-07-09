<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service\ImageRecognition;

use Pagemachine\AItools\Domain\Model\PlaceholderResult;
use Pagemachine\AItools\Domain\Model\Server;
use TYPO3\CMS\Core\Resource\FileInterface;

interface ImageRecognitionServiceInterface
{
    public function __construct(Server $server);

    /**
     * Sends the file to the image recognition API
     *
     * @return string
     */
    public function sendFileToApi(FileInterface $fileObject, PlaceholderResult $placeholderResult, string $targetLanguage = 'en', ?string $translationProvider = null, string $promptLang = 'auto'): string;

    /**
     * Sends multiple files to the image recognition API in a single batch request.
     *
     * @param array<int, array{file: FileInterface, placeholderResult: PlaceholderResult}> $items
     * @return array<int, string> Generated texts keyed by the input index
     */
    public function sendBatchToApi(array $items, string $targetLanguage = 'en', ?string $translationProvider = null): array;

    /**
     * Returns the price for the action
     *
     * @return string
     */
    public function sendCreditsRequestToApi(FileInterface $fileObject, string $textPrompt = '', string $targetLanguage = 'en'): string;

    public function supportsTranslation(): bool;
}
