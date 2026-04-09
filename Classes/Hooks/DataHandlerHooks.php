<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Hooks;

use Pagemachine\AItools\Domain\Repository\ServerRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DataHandlerHooks
{
    public function processCmdmap(
        string $command,
        string $table,
        $id,
        $value,
        bool &$commandIsProcessed,
        DataHandler $dataHandler
    ): void {
        if ($table !== 'tx_aitools_domain_model_prompt' || $command !== 'delete') {
            return;
        }
        $record = BackendUtility::getRecord($table, (int)$id, 'system');
        if ($record && $record['system']) {
            $dataHandler->log($table, (int)$id, 0, null, 1, 'Cannot delete a system prompt');
            $commandIsProcessed = true;
        }
    }

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
        } elseif ($table === 'tx_aitools_domain_model_server'
            && array_key_exists('default', $fieldArray)
        ) {
            if ($fieldArray['default'] == 1) {
                $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
                $queryBuilder = $connection->createQueryBuilder();

                $queryBuilder
                    ->update($table)
                    ->set('default', 0)
                    ->executeStatement();
            } elseif ($fieldArray['default'] == 0) {
                $serverRepository = GeneralUtility::makeInstance(ServerRepository::class);
                $defaultServer = $serverRepository->getDefault();
                if ($defaultServer === null) {
                    $fieldArray['default'] = 1;
                }
            }
        }
    }
}
