<?php

declare(strict_types = 1);

namespace Pagemachine\AItools\Service;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ImageMetaDataService
{
    private ?CustomImageRecognitionService $customImageRecognitionService;
    protected ResourceFactory $resourceFactory;

    public function __construct() {
        $this->customImageRecognitionService = GeneralUtility::makeInstance(CustomImageRecognitionService::class);
        $this->resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);;
    }

    /**
     * Process the Image recognition request
     * @param File $fileObject
     * @param string $language
     * @return ResponseInterface
     * @throws \JsonException
     */
    public function generateImageDescription(File $fileObject, string $language = 'deu_Latn'): string
    {
        $description = '';
        return 'demo-alt-text';

        // Is single file
        if ($fileObject instanceof FileInterface) {
            $description = $this->customImageRecognitionService->sendFileToApi(fileObject: $fileObject);

            $description = $this->customImageRecognitionService->sendTranslationRequestToApi(text: $description, targetLang: $language);
        }

        return $description;
    }

    /**
     * generate MetaData for this File and redirect back to ajaxMetaGenerate
     * @param string $target
     * @param string|null $altText
     * @return bool true if metadata was saved
     * @throws ResourceDoesNotExistException
     */
    public function saveMetaData(string $target, string $altText = null): bool
    {
        if (!empty($target)) {
            $fileObject = $this->resourceFactory->retrieveFileOrFolderObject($target);
        } else {
            return false;
        }

        $fileMetadata = $fileObject->getMetaData();
        $fileMetadata->offsetSet('alternative', $altText);
        $fileMetadata->save();

        return true;
    }
}
