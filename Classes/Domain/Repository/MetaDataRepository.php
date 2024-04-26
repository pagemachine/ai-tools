<?php

declare(strict_types = 1);

namespace Pagemachine\AItools\Domain\Repository;

use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Extbase\Annotation\Validate;


use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\RootLevelRestriction;
use TYPO3\CMS\Core\Resource\Event\EnrichFileMetaDataEvent;
use TYPO3\CMS\Core\Resource\Exception\InvalidUidException;
use TYPO3\CMS\Core\Utility\GeneralUtility;


class MetaDataRepository extends \TYPO3\CMS\Core\Resource\Index\MetaDataRepository
{
    /**
     * Retrieves metadata for file
     *
     * @param int $uid
     * @return array
     * @throws InvalidUidException
     */
    public function findWithOverlayByFileUid(int $fileUid, $languageUid = 0)
    {
        if ($fileUid <= 0) {
            throw new InvalidUidException('Metadata can only be retrieved for indexed files. UID: "' . $fileUid . '"', 1381590731);
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);

        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(RootLevelRestriction::class));

        $record = $queryBuilder
            ->select('*')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->eq('file', $queryBuilder->createNamedParameter($fileUid, Connection::PARAM_INT)),
                $queryBuilder->expr()->in('sys_language_uid', $queryBuilder->createNamedParameter([$languageUid], Connection::PARAM_INT_ARRAY))
            )
            ->executeQuery()
            ->fetchAssociative();

        if (empty($record)) {
            return [];
        }
        return $record;

        //$overlaidMetaData = $this->findByFileUid($uid);
        //$event = new EnrichFileMetaDataEvent($uid, (int)$overlaidMetaData['uid'], $overlaidMetaData);
//
        //$pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        //$pageRepository->versionOL('sys_file_metadata', $overlaidMetaData);
        //$overlaidMetaData = $pageRepository
        //    ->getLanguageOverlay(
        //        'sys_file_metadata',
        //        $overlaidMetaData
        //    );
        //if ($overlaidMetaData !== null) {
        //    $event->setRecord($overlaidMetaData);
        //}
//
        ////return $this->eventDispatcher->dispatch(new EnrichFileMetaDataEvent($uid, (int)$record['uid'], $record))->getRecord();
        //return $this->eventDispatcher->dispatch($event)->getRecord();
    }

    public function updateMetaDataByFileUidAndLanguageUid(int $fileUid, int $languageUid, $fieldName = "alternative", $fieldValue = ""): string
    {
        if ($fileUid <= 0) {
            throw new InvalidUidException('Metadata can only be updated for indexed files. UID: "' . $fileUid . '"', 1381590731);
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);

        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(RootLevelRestriction::class));

        $queryBuilder
            ->update($this->tableName)
            ->where(
                $queryBuilder->expr()->eq('file', $queryBuilder->createNamedParameter($fileUid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($languageUid, Connection::PARAM_INT))
            )
            ->set($fieldName, $fieldValue)
            ->execute();

        return $fieldValue;

    }
}
