<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Hooks;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DataHandlerHooks
{
    public function processDatamap_postProcessFieldArray(
        string $status,
        string $table,
        $id,
        array &$fieldArray,
        DataHandler $dataHandler
    ): void {
        if ($table === 'tx_aitools_domain_model_prompt'
            && array_key_exists('default', $fieldArray)
        ) {
            if ($fieldArray['default'] == 1) {
                $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
                $queryBuilder = $connection->createQueryBuilder();

                $queryBuilder
                    ->update($table)
                    ->set('default', 0)
                    ->executeStatement();
            }
        }
    }
}
