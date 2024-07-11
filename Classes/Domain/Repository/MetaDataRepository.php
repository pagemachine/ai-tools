<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Domain\Repository;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Driver\Exception;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\RootLevelRestriction;
use TYPO3\CMS\Core\Resource\Exception\InvalidUidException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

class MetaDataRepository extends \TYPO3\CMS\Core\Resource\Index\MetaDataRepository
{
    /**
     * Retrieves metadata for file with multiple language overlays
     *
     * @return array
     * @throws InvalidUidException
     * @throws \Doctrine\DBAL\Exception
     */
    public function findWithOverlayByFileUid(int $fileUid, array $languageUids = [0]): array
    {
        if ($fileUid <= 0) {
            throw new InvalidUidException('Metadata can only be retrieved for indexed files. UID: "' . $fileUid . '"', 1381590731);
        }
        $version = GeneralUtility::makeInstance(VersionNumberUtility::class)->getNumericTypo3Version();
        if (version_compare($version, '12.0', '>=')) {
            // @phpstan-ignore-next-line Stop PHPStan about complaining this line for TYPO3 v11
            $arrayIntegerType = ArrayParameterType::INTEGER;
        } else {
            $arrayIntegerType = Connection::PARAM_INT_ARRAY;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);

        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(RootLevelRestriction::class));

        $records = $queryBuilder
            ->select('*')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->eq('file', $queryBuilder->createNamedParameter($fileUid, Connection::PARAM_INT)),
                $queryBuilder->expr()->in('sys_language_uid', $queryBuilder->createNamedParameter($languageUids, $arrayIntegerType))
            )
            ->executeQuery()
            ->fetchAllAssociative();

        return $records ?: [];
    }

    /**
     * Update metadata for file
     *
     * @return int
     * @throws Exception
     * @throws InvalidUidException
     */
    public function updateMetaDataByFileUidAndLanguageUid(int $fileUid, int $languageUid, string $fieldName = 'alternative', string $fieldValue = ''): int
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

    /**
     * find all translated file variants for a fileMetaDataUid and list of languageUids
     *
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function findAllFileVariantsByLanguageUid(int $fileMetaDataUid, array $languageUids): array
    {
        $version = GeneralUtility::makeInstance(VersionNumberUtility::class)->getNumericTypo3Version();
        if (version_compare($version, '12.0', '>=')) {
            // @phpstan-ignore-next-line Stop PHPStan about complaining this line for TYPO3 v11
            $arrayIntegerType = ArrayParameterType::INTEGER;
        } else {
            $arrayIntegerType = Connection::PARAM_INT_ARRAY;
        }

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
        $translatedFileQuery = $queryBuilder->select('*')->from('sys_file_metadata')->where(
            $queryBuilder->expr()->in(
                'sys_language_uid',
                $queryBuilder->createNamedParameter($languageUids, $arrayIntegerType)
            ),
            $queryBuilder->expr()->eq(
                'l10n_parent',
                $queryBuilder->createNamedParameter($fileMetaDataUid, Connection::PARAM_INT)
            ),
        )->executeQuery();
        return $translatedFileQuery->fetchAllAssociative() ?: [];
    }
}
