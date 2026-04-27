<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

ExtensionManagementUtility::addTCAcolumns('sys_file_storage', [
    'tx_aitools_enabled' => [
        'exclude' => true,
        'label' => 'LLL:EXT:ai_tools/Resources/Private/Language/locallang_db.xlf:sys_file_storage.tx_aitools_enabled',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
            'default' => 1,
            'items' => [
                [
                    'label' => '',
                    'invertStateDisplay' => false,
                ],
            ],
        ],
    ],
    'tx_aitools_server' => [
        'exclude' => true,
        'label' => 'LLL:EXT:ai_tools/Resources/Private/Language/locallang_db.xlf:sys_file_storage.tx_aitools_server',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'foreign_table' => 'tx_aitools_domain_model_server',
            'foreign_table_where' => 'ORDER BY tx_aitools_domain_model_server.title ASC',
            'items' => [
                [
                    'label' => 'LLL:EXT:ai_tools/Resources/Private/Language/locallang_db.xlf:sys_file_storage.tx_aitools_server.default',
                    'value' => 0,
                ],
            ],
            'default' => 0,
        ],
    ],
]);

ExtensionManagementUtility::addToAllTCAtypes(
    'sys_file_storage',
    '--div--;LLL:EXT:ai_tools/Resources/Private/Language/locallang_db.xlf:sys_file_storage.tab, tx_aitools_enabled, tx_aitools_server'
);
