<?php
defined('TYPO3_MODE') || die('Access denied.');

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'AItools',
    'aitools',
    '',
    'after:web',
    [],
    [
        'access' => 'user,group',
        'iconIdentifier' => 'EXT:ai_tools/Resources/Public/Icons/ext_icon.svg',
        'labels' => 'LLL:EXT:ai_tools/Resources/Private/Language/BackendModules/locallang_be_mainmodule.xlf',
    ]
);

/*
Temporary disabled
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'AItools',
    'aitools',
    'images',
    '',
    array(
        \Pagemachine\AItools\Controller\Backend\ImageCreationController::class => 'show, generate, variate, save',
        \Pagemachine\AItools\Controller\Backend\ImageRecognizeController::class => 'describe',
    ),
    array(
        'access' => 'user,group',
        'icon' => 'EXT:ai_tools/Resources/Public/Icons/ext_icon.svg',
        'labels' => 'LLL:EXT:ai_tools/Resources/Private/Language/BackendModules/locallang_be_aiimage.xlf',
    )
);
*/

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'AItools',
    'aitools',
    'settings',
    '',
    array(
        \Pagemachine\AItools\Controller\Backend\SettingsController::class => 'settings, save, addPrompt, saveDefaultPrompt',
    ),
    array(
        'access' => 'user, group',
        'icon' => 'EXT:ai_tools/Resources/Public/Icons/ext_icon.svg',
        'labels' => 'LLL:EXT:ai_tools/Resources/Private/Language/BackendModules/locallang_be_settings.xlf',
    )
);

$GLOBALS['TYPO3_CONF_VARS']['BE']['customPermOptions']['tx_aitools_permissions'] = [
    'header' => 'AI Tools permissions',
    'items' => [
        'prompt_management' => [ // key
            'Prompt management',
            // Icon has been registered above
            'tcarecords-tx_styleguide_forms-default',
            'Allows User to manage prompts',
        ],
    ],
];
