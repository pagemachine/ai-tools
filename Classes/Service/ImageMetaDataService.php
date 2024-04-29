<?php

declare(strict_types = 1);

namespace Pagemachine\AItools\Service;

use Pagemachine\AItools\Domain\Repository\MetaDataRepository;
use Pagemachine\AItools\Service\ImageRecognition\CustomImageRecognitionService;
use Pagemachine\AItools\Service\ImageRecognition\OpenAiImageRecognitionService;
use TYPO3\CMS\Core\Resource\Exception\InvalidUidException;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ImageMetaDataService
{
    protected SettingsService $settingsService;
    private ?CustomImageRecognitionService $customImageRecognitionService;
    private ?OpenAiImageRecognitionService $openAiImageRecognitionService;
    private MetaDataRepository $metaDataRepository;
    protected ResourceFactory $resourceFactory;

    public function __construct() {
        $this->settingsService = GeneralUtility::makeInstance(SettingsService::class);

        $this->customImageRecognitionService = GeneralUtility::makeInstance(CustomImageRecognitionService::class);
        $this->openAiImageRecognitionService = GeneralUtility::makeInstance(OpenAiImageRecognitionService::class);

        $this->metaDataRepository = GeneralUtility::makeInstance(MetaDataRepository::class);

        $this->resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);;
    }

    /**
     * Process the Image recognition request
     * @param FileInterface $fileObject
     * @param string $textPrompt
     * @return string
     * @throws \Exception
     */
    public function generateImageDescription(FileInterface $fileObject, string $textPrompt = ''): string
    {
        $imageRecognitionService = $this->settingsService->getSetting('image_recognition_service');
        return match ($imageRecognitionService) {
            'openai' => $this->openAiImageRecognitionService->sendFileToApi(fileObject: $fileObject, textPrompt: $textPrompt),
            'custom' => $this->customImageRecognitionService->sendFileToApi(fileObject: $fileObject, textPrompt: $textPrompt),
            default => throw new \Exception('No valid image recognition service configured'),
        };
    }

    /**
     * Retrieve MetaData for this File
     * @param FileInterface $fileObject
     * @param int $language
     * @return array|null
     * @throws InvalidUidException
     */
    public function getMetaData(FileInterface $fileObject, int $language = 0): ?array
    {
        $fileObjectUid = $fileObject->getUid();
        $fileMetaData = $this->metaDataRepository->findWithOverlayByFileUid($fileObjectUid, $language);
        if (empty($fileMetaData)) {
            return null;
        }
        return $fileMetaData;
    }

    /**
     * generate MetaData for this File and redirect back to ajaxMetaGenerate
     * @param string $target
     * @param string|null $altText
     * @param int $language
     * @return bool true if metadata was saved
     * @throws InvalidUidException
     * @throws ResourceDoesNotExistException
     */
    public function saveMetaData(string $target, string $altText = null, int $language = 0): bool
    {
        if (!empty($target)) {
            $fileObject = $this->resourceFactory->retrieveFileOrFolderObject($target);
        } else {
            return false;
        }

        if ($language == 0) {
            $fileMetadata = $fileObject->getMetaData();
            $fileMetadata->offsetSet('alternative', $altText);
            $fileMetadata->save();
        } else {
            $fileObjectUid = $fileObject->getUid();
            $this->metaDataRepository->updateMetaDataByFileUidAndLanguageUid(
                $fileObjectUid, languageUid: $language, fieldName: 'alternative', fieldValue: $altText
            );
        }

        return true;
    }
}
