<?php

declare(strict_types = 1);

namespace Pagemachine\AItools\Service;

use Pagemachine\AItools\Domain\Repository\MetaDataRepository;
use Pagemachine\AItools\Service\ImageRecognition\CustomImageRecognitionService;
use Pagemachine\AItools\Service\ImageRecognition\OpenAiImageRecognitionService;
use Pagemachine\AItools\Service\Translation\CustomTranslationService;
use Pagemachine\AItools\Service\Translation\DeepLTranslationService;
use Psr\Http\Message\ResponseInterface;
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
    private ?CustomTranslationService $customTranslationService;
    private ?DeepLTranslationService $deeplTranslationService;
    private MetaDataRepository $metaDataRepository;
    protected ResourceFactory $resourceFactory;

    public function __construct() {
        $this->settingsService = GeneralUtility::makeInstance(SettingsService::class);

        $this->customImageRecognitionService = GeneralUtility::makeInstance(CustomImageRecognitionService::class);
        $this->openAiImageRecognitionService = GeneralUtility::makeInstance(OpenAiImageRecognitionService::class);

        $this->customTranslationService = GeneralUtility::makeInstance(CustomTranslationService::class);
        $this->deeplTranslationService = GeneralUtility::makeInstance(DeepLTranslationService::class);

        $this->metaDataRepository = GeneralUtility::makeInstance(MetaDataRepository::class);

        $this->resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);;
    }

    /**
     * Process the Image recognition request
     * @param FileInterface $fileObject
     * @param string $language
     * @return ResponseInterface
     * @throws \JsonException
     * @throws \Exception
     */
    public function generateImageDescription(FileInterface $fileObject, string $language = 'deu_Latn', string $textPrompt = ''): string
    {
        $imageRecognitionService = $this->settingsService->getSetting('image_recognition_service');
        $translationService = $this->settingsService->getSetting('translation_service');
        $description = match ($imageRecognitionService) {
            'openai' => $this->openAiImageRecognitionService->sendFileToApi(fileObject: $fileObject, textPrompt: $textPrompt),
            'custom' => $this->customImageRecognitionService->sendFileToApi(fileObject: $fileObject, textPrompt: $textPrompt),
            default => throw new \Exception('No valid image recognition service configured'),
        };
        return match ($translationService) {
            'deepl' => $this->deeplTranslationService->sendTranslationRequestToApi(text: $description, targetLang: 'DE'),
            'custom' => $this->customTranslationService->sendTranslationRequestToApi(text: $description, targetLang: $language),
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
            $fileMetaData = $this->metaDataRepository->updateMetaDataByFileUidAndLanguageUid(
                $fileObjectUid, languageUid: $language, fieldName: 'alternative', fieldValue: $altText
            );
        }

        return true;
    }
}
