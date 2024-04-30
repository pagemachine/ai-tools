<?php

declare(strict_types = 1);

namespace Pagemachine\AItools\Domain\Repository;

use Doctrine\DBAL\Driver\Exception;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\RootLevelRestriction;
use TYPO3\CMS\Core\Resource\Exception\InvalidUidException;
use TYPO3\CMS\Core\Utility\GeneralUtility;


class MetaDataRepository extends \TYPO3\CMS\Core\Resource\Index\MetaDataRepository
{
    /**
     * Retrieves metadata for file
     *
     * @param int $fileUid
     * @param int $languageUid
     * @return array|null
     * @throws Exception
     * @throws InvalidUidException
     */
    public function findWithOverlayByFileUid(int $fileUid, int $languageUid = 0): ?array
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

        if ($record === false) {
            return null;
        }

        if (empty($record)) {
            return [];
        }
        return $record;
    }

    /**
     * Update metadata for file
     *
     * @param int $fileUid
     * @param int $languageUid
     * @param string $fieldName
     * @param string $fieldValue
     * @return int
     * @throws Exception
     * @throws InvalidUidException
     */
    public function updateMetaDataByFileUidAndLanguageUid(int $fileUid, int $languageUid, string $fieldName = "alternative", string $fieldValue = ""): int
    {
        if ($fileUid <= 0) {
            throw new InvalidUidException('Metadata can only be updated for indexed files. UID: "' . $fileUid . '"', 1381590731);
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);

        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(RootLevelRestriction::class));

        return $queryBuilder
            ->update($this->tableName)
            ->where(
                $queryBuilder->expr()->eq('file', $queryBuilder->createNamedParameter($fileUid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($languageUid, Connection::PARAM_INT))
            )
            ->set($fieldName, $fieldValue)
            ->executeStatement();
    }
}
