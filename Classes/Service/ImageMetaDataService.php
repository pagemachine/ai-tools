<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service;

use Doctrine\DBAL\Driver\Exception;
use Pagemachine\AItools\Domain\Repository\MetaDataRepository;
use Pagemachine\AItools\Service\ImageRecognition\CustomImageRecognitionService;
use Pagemachine\AItools\Service\ImageRecognition\OpenAiImageRecognitionService;
use T3G\AgencyPack\FileVariants\Service\ResourcesService;
use TYPO3\CMS\Core\Resource\Exception\InvalidUidException;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

class ImageMetaDataService
{
    protected SettingsService $settingsService;
    private readonly ?CustomImageRecognitionService $customImageRecognitionService;
    private readonly ?OpenAiImageRecognitionService $openAiImageRecognitionService;
    private readonly MetaDataRepository $metaDataRepository;
    protected ResourceFactory $resourceFactory;
    protected PersistenceManagerInterface $persistenceManager;

    public function __construct()
    {
        $this->settingsService = GeneralUtility::makeInstance(SettingsService::class);

        $this->customImageRecognitionService = GeneralUtility::makeInstance(CustomImageRecognitionService::class);
        $this->openAiImageRecognitionService = GeneralUtility::makeInstance(OpenAiImageRecognitionService::class);

        $this->metaDataRepository = GeneralUtility::makeInstance(MetaDataRepository::class);

        $this->resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        $this->persistenceManager = GeneralUtility::makeInstance(PersistenceManagerInterface::class);
    }

    /**
     * Process the Image recognition request
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
     * generate MetaData for this File and redirect back to ajaxMetaGenerate
     * @param string|null $altText
     * @return int true if metadata was saved
     * @throws InvalidUidException
     * @throws ResourceDoesNotExistException
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function saveMetaData(string $target, string $altText = null, int $language = 0): int
    {
        if (!empty($target)) {
            $fileObject = $this->resourceFactory->retrieveFileOrFolderObject($target);
            if (empty($fileObject)) {
                return -1;
            }
        } else {
            return -1;
        }

        $metaDataUid = -1;

        if ($language == 0) {
            $fileMetadata = $fileObject->getMetaData();
            $fileMetadata->offsetSet('alternative', $altText);
            $fileMetadata->save();
        } else {
            $fileObjectUid = $fileObject->getUid();

            /**
             * Special file_variants handling
             */
            if (ExtensionManagementUtility::isLoaded('file_variants')) {
                /** @var ResourcesService $resourcesService */
                $resourcesService = GeneralUtility::makeInstance(ResourcesService::class);
                $fileMetadata = $fileObject->getMetaData()->get();
                $fileMetadataUid = $fileMetadata['uid'] ?? null;
                if (empty($fileMetadataUid)) {
                    return -1;
                }

                // Try to find already translated file variant
                $translatedFiles = $this->metaDataRepository->findAllFileVariantsByLanguageUid($fileMetadataUid, [$language]);

                if (!empty($translatedFiles)) {
                    $metaDataUid = (int)$translatedFiles[0]['uid'];
                    // Otherwise update file variant meta data
                    $this->metaDataRepository->updateMetaDataByUidAndLanguageUid(
                        $metaDataUid,
                        languageUid: $language,
                        fieldName: 'alternative',
                        fieldValue: $altText
                    );
                } else {
                    // Create new file variant record if none exists for language
                    $diffSourceJson = json_encode($fileObject->getProperties());
                    $translatedMetaDataRecord = $this->metaDataRepository->createMetaDataRecord($fileObjectUid, [
                        'sys_language_uid' => $language,
                        'l10n_parent' => $fileMetadataUid,
                        't3_origuid' => $fileMetadataUid,
                        'width' => $fileObject->getProperty('width'),
                        'height' => $fileObject->getProperty('height'),
                        'alternative' => $altText,
                        'l10n_diffsource' => $diffSourceJson,
                    ]);
                    $metaDataUid = (int)$translatedMetaDataRecord['uid'];
                }

                return $metaDataUid;
            }

            // check if metadata for language already exists
            $fileLanguageMetaData = $this->metaDataRepository->findWithOverlayByFileUid($fileObjectUid, [$language]);

            if (!empty($fileLanguageMetaData)) {
                // Update the existing metadata language record
                // (This does not return number of updated records. That's why findWithOverlayByFileUid is used to check if record exists)
                $this->metaDataRepository->updateMetaDataByFileUidAndLanguageUid(
                    $fileObjectUid,
                    languageUid: $language,
                    fieldName: 'alternative',
                    fieldValue: $altText
                );
                $metaDataUid = $fileLanguageMetaData[0]['uid'];
            } else {
                // Create a new record if no record for language exists
                $diffSourceJson = json_encode($fileObject->getProperties());
                $translatedMetaDataRecord = $this->metaDataRepository->createMetaDataRecord($fileObjectUid, [
                    'sys_language_uid' => $language,
                    'l10n_parent' => $fileObjectUid,
                    't3_origuid' => $fileObjectUid,
                    'width' => $fileObject->getProperty('width'),
                    'height' => $fileObject->getProperty('height'),
                    'alternative' => $altText,
                    'l10n_diffsource' => $diffSourceJson,
                ]);

                $metaDataUid = (int)$translatedMetaDataRecord['uid'];
            }
        }

        return $metaDataUid;
    }

    /**
     * Retrieve all language overlays for a file
     *
     * @param int[]|SiteLanguage[] $languages
     * @return array
     * @throws InvalidUidException
     * @throws \Doctrine\DBAL\Exception
     */
    public function getMetaDataLanguages(File $fileObject, array $languages): array
    {
        $siteLanguages = [];

        foreach ($languages as $language) {
            if ($language instanceof SiteLanguage) {
                $siteLanguages[] = $language->getLanguageId();
            } elseif (is_numeric($language)) {
                $siteLanguages[] = $language;
            }
        }
        $fileMetadata = $fileObject->getMetaData()->get();
        $fileMetadataUid = $fileMetadata['uid'];

        return $this->metaDataRepository->findAllFileVariantsByLanguageUid($fileMetadataUid, $siteLanguages);
    }
}
