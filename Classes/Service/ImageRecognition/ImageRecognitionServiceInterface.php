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
    public function sendFileToApi(FileInterface $fileObject, PlaceholderResult $placeholderResult, string $targetLanguage = 'en', string $promptLanguage = 'auto'): string;

    /**
     * Returns the price for the action
     *
     * @return string
     */
    public function sendCreditsRequestToApi(FileInterface $fileObject, string $textPrompt = '', string $targetLanguage = 'en'): string;

    public function supportsTranslation(): bool;
}
