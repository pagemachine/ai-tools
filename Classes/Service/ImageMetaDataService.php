<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service;

use Doctrine\DBAL\Driver\Exception;
use Pagemachine\AItools\Domain\Repository\MetaDataRepository;
use Pagemachine\AItools\Service\ImageRecognition\CustomImageRecognitionService;
use Pagemachine\AItools\Service\ImageRecognition\OpenAiImageRecognitionService;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Resource\Exception\InvalidUidException;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

class ImageMetaDataService
{
    protected SettingsService $settingsService;
    private ?CustomImageRecognitionService $customImageRecognitionService;
    private ?OpenAiImageRecognitionService $openAiImageRecognitionService;
    private MetaDataRepository $metaDataRepository;
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
     * generate MetaData for this File and redirect back to ajaxMetaGenerate
     * @param string $target
     * @param string|null $altText
     * @param int $language
     * @return bool true if metadata was saved
     * @throws InvalidUidException
     * @throws ResourceDoesNotExistException
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function saveMetaData(string $target, string $altText = null, int $language = 0): bool
    {
        if (!empty($target)) {
            $fileObject = $this->resourceFactory->retrieveFileOrFolderObject($target);
            if (empty($fileObject)) {
                return false;
            }
        } else {
            return false;
        }

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
                /** @var \T3G\AgencyPack\FileVariants\Service\ResourcesService $resourcesService */
                $resourcesService = GeneralUtility::makeInstance(\T3G\AgencyPack\FileVariants\Service\ResourcesService::class);
                $fileMetadata = $fileObject->getMetaData()->get();
                $fileMetadataUid = $fileMetadata['uid'] ?? null;
                if (empty($fileMetadataUid)) {
                    return false;
                }

                // Try to find already translated file variant
                /** @var QueryBuilder $queryBuilder */
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
                $translatedFileQuery = $queryBuilder->select('uid')->from('sys_file_metadata')->where(
                    $queryBuilder->expr()->eq(
                        'sys_language_uid',
                        $queryBuilder->createNamedParameter($language, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'l10n_parent',
                        $queryBuilder->createNamedParameter($fileMetadataUid, Connection::PARAM_INT)
                    ),
                )->executeQuery();
                $translatedFile = $translatedFileQuery->fetchOne();

                if (!$translatedFile) {
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
                    $folder = $resourcesService->prepareFileStorageEnvironment();
                    $resourcesService->copyOriginalFileAndUpdateAllConsumingReferencesToUseTheCopy(
                        sys_language_uid: $language,
                        metaDataRecord: $translatedMetaDataRecord,
                        folder: $folder,
                    );
                } else {
                    // Otherwise update file variant meta data
                    $this->metaDataRepository->updateMetaDataByFileUidAndLanguageUid(
                        $translatedFile,
                        languageUid: $language,
                        fieldName: 'alternative',
                        fieldValue: $altText
                    );
                }

                return true;
            }

            // check if metadata for language already exists
            $fileLanguageMetaData = $this->metaDataRepository->findWithOverlayByFileUid($fileObjectUid, $language);

            if ($fileLanguageMetaData !== null) {
                // Update the existing metadata language record
                // (This does not return number of updated records. That's why findWithOverlayByFileUid is used to check if record exists)
                $this->metaDataRepository->updateMetaDataByFileUidAndLanguageUid(
                    $fileObjectUid,
                    languageUid: $language,
                    fieldName: 'alternative',
                    fieldValue: $altText
                );
            } else {
                // Create a new record if no record for language exists
                $diffSourceJson = json_encode($fileObject->getProperties());
                $this->metaDataRepository->createMetaDataRecord($fileObjectUid, [
                    'sys_language_uid' => $language,
                    'l10n_parent' => $fileObjectUid,
                    't3_origuid' => $fileObjectUid,
                    'width' => $fileObject->getProperty('width'),
                    'height' => $fileObject->getProperty('height'),
                    'alternative' => $altText,
                    'l10n_diffsource' => $diffSourceJson,
                ]);
            }
        }

        return true;
    }
}
