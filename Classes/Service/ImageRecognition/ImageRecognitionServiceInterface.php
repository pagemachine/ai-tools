<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service\ImageRecognition;

use TYPO3\CMS\Core\Resource\FileInterface;

interface ImageRecognitionServiceInterface
{
    /**
     * Sends the file to the image recognition API
     *
     * @param FileInterface $fileObject
     * @param string $textPrompt
     * @return string
     */
    public function sendFileToApi(FileInterface $fileObject, string $textPrompt = ''): string;
}
