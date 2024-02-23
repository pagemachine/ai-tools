<?php

declare(strict_types = 1);

namespace Pagemachine\AItools\Service;

use Pagemachine\AItools\Service\ImageRecognition\CustomImageRecognitionService;
use Pagemachine\AItools\Service\ImageRecognition\OpenAiImageRecognitionService;
use Pagemachine\AItools\Service\Translation\CustomTranslationService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ImageMetaDataService
{
    protected $settingsService;
    private ?CustomImageRecognitionService $customImageRecognitionService;
    private ?OpenAiImageRecognitionService $openAiImageRecognitionService;
    private ?CustomTranslationService $customTranslationService;
    protected ResourceFactory $resourceFactory;

    public function __construct() {
        $this->settingsService = GeneralUtility::makeInstance(SettingsService::class);
        $this->customImageRecognitionService = GeneralUtility::makeInstance(CustomImageRecognitionService::class);
        $this->openAiImageRecognitionService = GeneralUtility::makeInstance(OpenAiImageRecognitionService::class);
        $this->customTranslationService = GeneralUtility::makeInstance(CustomTranslationService::class);
        $this->resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);;
    }

    /**
     * Process the Image recognition request
     * @param FileInterface $fileObject
     * @param string $language
     * @return ResponseInterface
     * @throws \JsonException
     */
    public function generateImageDescription(FileInterface $fileObject, string $language = 'deu_Latn', string $textPrompt = ''): string
    {
        $description = '';
        $imageRecognitionService = $this->settingsService->getSetting('image_recognition_service');
        switch ($imageRecognitionService) {
            case 'openai':
                $description = $this->openAiImageRecognitionService->sendFileToApi(fileObject: $fileObject, textPrompt: $textPrompt);
                break;
            case 'custom':
                $description = $this->customImageRecognitionService->sendFileToApi(fileObject: $fileObject, textPrompt: $textPrompt);
                break;
            default:
                throw new \Exception('No valid image recognition service configured');
        }

        $description = $this->customTranslationService->sendTranslationRequestToApi(text: $description, targetLang: $language);

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
