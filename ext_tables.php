<?php

declare(strict_types=1);
use Pagemachine\AItools\Controller\Backend\SettingsController;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die();

$version = GeneralUtility::makeInstance(VersionNumberUtility::class)->getNumericTypo3Version();
if (version_compare($version, '11.0', '>=') && version_compare($version, '12.0', '<')) {
    // for TYPO3 v11

    ExtensionUtility::registerModule(
        'AItools',
        'aitools',
        '',
        'after:web',
        [],
        [
            'access' => '',
            'icon' => 'EXT:ai_tools/Resources/Public/Icons/ext_icon.svg',
            'labels' => 'LLL:EXT:ai_tools/Resources/Private/Language/BackendModules/locallang_be_mainmodule.xlf',
        ]
    );

    ExtensionUtility::registerModule(
        'AItools',
        'aitools',
        'prompts',
        '',
        [
            SettingsController::class => 'promptList',
        ],
        [
            'access' => 'user, group',
            'iconIdentifier' => 'actions-notebook',
            'labels' => 'LLL:EXT:ai_tools/Resources/Private/Language/locallang_db.xlf:tx_aitools_domain_model_prompt.templates',
        ]
    );

    ExtensionUtility::registerModule(
        'AItools',
        'aitools',
        'settings',
        '',
        [
            SettingsController::class => 'settings, save',
        ],
        [
            'access' => 'admin',
            'iconIdentifier' => 'module-install-settings',
            'labels' => 'LLL:EXT:ai_tools/Resources/Private/Language/BackendModules/locallang_be_settings.xlf',
        ]
    );
}

$GLOBALS['TYPO3_CONF_VARS']['BE']['customPermOptions']['tx_aitools_permissions'] = [
    'header' => 'AI Tools permissions',
    'items' => [
        'generate_metadata' => [ // key
            'Generate metadata',
            // Icon has been registered above
            'tcarecords-tx_styleguide_forms-default',
            'Allows User to use the generate metadata context menu',
        ],
    ],
];
