<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service\ImageRecognition;

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
    public function sendFileToApi(FileInterface $fileObject, string $textPrompt = ''): string;
}
