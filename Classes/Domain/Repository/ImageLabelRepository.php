<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\RootLevelRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class ImageLabelRepository extends Repository
{
    public function listAllLabels(): QueryResultInterface
    {
        $query = $this->createQuery();

        $query->getQuerySettings()
            ->setIgnoreEnableFields(true);

        return $query->execute();
    }

    public function getDefaultImageLabelid(): int
    {
        $version = GeneralUtility::makeInstance(VersionNumberUtility::class)->getNumericTypo3Version();
        if (version_compare($version, '11.0', '>=') && version_compare($version, '12.0', '<')) {
            $defaultLabels = $this->findOneByDefault(true);
        } else {
            /**
             * @var ImageLabel $defaultLabel
             * @phpstan-ignore-next-line
             */
            $defaultLabels = $this->findOneBy(['default' => true]);
        }
        if ($defaultLabels) {
            return $defaultLabels->getUid();
        } else {
            return -1;
        }
    }

    public function handleLabel(int $imagelabelid, string $action): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable("tx_aitools_domain_model_badwords");

        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(RootLevelRestriction::class));

        if ($action == "default") {
            $queryBuilder->update("tx_aitools_domain_model_imagelabel")
                ->set('default', 0)
                ->where(
                    $queryBuilder->expr()->eq("default", 1)
                )
                ->executeStatement();
            if ($imagelabelid == 0) {
                return 0;
            }
            return $queryBuilder->update("tx_aitools_domain_model_imagelabel")
                ->set('default', 1)
                ->where(
                    $queryBuilder->expr()->eq('uid', $imagelabelid)
                )
                ->executeStatement();
        }

        return 1;
    }
}
