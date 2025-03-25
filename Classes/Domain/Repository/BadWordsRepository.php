<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\RootLevelRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class BadWordsRepository extends Repository
{
    public function listAllBadWords(): QueryResultInterface
    {
        $query = $this->createQuery();

        $query->getQuerySettings()
            ->setIgnoreEnableFields(true);

        return $query->execute();
    }

    public function handleBadword(int $imagelabelid, string $badword, int $badwordid, string $action): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable("tx_aitools_domain_model_badwords");

        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(RootLevelRestriction::class));

        if ($action == "cut") {
            return $queryBuilder
                ->delete("tx_aitools_domain_model_badwords")
                ->where(
                    $queryBuilder->expr()->eq('uid', $badwordid)
                )
                ->executeStatement();
        }

        $existingLabel = $queryBuilder
            ->select('uid')
            ->from('tx_aitools_domain_model_badwords')
            ->where(
                $queryBuilder->expr()->eq('badword', $queryBuilder->createNamedParameter($badword)),
                $queryBuilder->expr()->eq('imagelabelid', $queryBuilder->createNamedParameter($imagelabelid))
            )
            ->executeQuery()
            ->fetchOne();

        if (!$existingLabel) {
            if ($action == "add") {
                return $queryBuilder
                    ->insert("tx_aitools_domain_model_badwords")
                    ->values([
                        'imagelabelid' => $imagelabelid,
                        'badword' => $badword,
                        'tstamp' => time(),
                        'crdate' => time(),
                    ])
                    ->executeStatement();
            } elseif ($action == "set") {
                return $queryBuilder
                    ->update("tx_aitools_domain_model_badwords")
                    ->set('imagelabelid', $imagelabelid)
                    ->set('badword', $badword)
                    ->set('tstamp', time())
                    ->set('crdate', time())
                    ->where(
                        $queryBuilder->expr()->eq('uid', $badwordid)
                    )
                    ->executeStatement();
            }
        }

        return 0;
    }
}
