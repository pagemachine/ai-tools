<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;
use Pagemachine\AItools\Service\ServerService;

defined('TYPO3') or die();

$serverService = GeneralUtility::makeInstance(ServerService::class);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:ai_tools/Resources/Private/Language/locallang_db.xlf:tx_aitools_domain_model_server',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'title',
        'default_sortby' => 'title',
        'searchFields' => 'title',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:ai_tools/Resources/Public/Icons/ext_icon.png',
        'hideAtCopy' => true,
        'thumbnail' => 'logo',
        'rootLevel' => -1,
        'security' => [
            'ignoreWebMountRestriction' => true,
            'ignoreRootLevelRestriction' => true,
        ],
        'type' => 'type',
    ],
    'columns' => [
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.enabled',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
                'items' => [
                    [
                        0 => '',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
        'title' => [
            'exclude' => true,
            'label' => 'LLL:EXT:ai_tools/Resources/Private/Language/locallang_db.xlf:tx_aitools_domain_model_server.title',
            'config' => [
                'type' => 'input',
                'eval' => 'trim,required',
                'required' => true,
                'size' => 20,
                'max' => 50,
            ],
        ],
        'type' => [
            'exclude' => true,
            'label' => 'LLL:EXT:ai_tools/Resources/Private/Language/locallang_db.xlf:tx_aitools_domain_model_server.type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'default' => 'aigude',
                'items' => $serverService->getTcaOptions(),
            ],
        ],
        'apikey' => [
            'exclude' => true,
            'label' => 'LLL:EXT:ai_tools/Resources/Private/Language/locallang_db.xlf:tx_aitools_domain_model_server.apikey',
            'config' => [
                'type' => 'input',
                'eval' => 'trim,password',
                'size' => 20,
                'max' => 120,
            ],
        ],
        'endpoint' => [
            'exclude' => true,
            'label' => 'LLL:EXT:ai_tools/Resources/Private/Language/locallang_db.xlf:tx_aitools_domain_model_server.endpoint',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'Free',
                        'free',
                    ],
                    [
                        'Pro',
                        'pro',
                    ],
                ],
            ],
        ],
        'formality' => [
            'exclude' => true,
            'label' => 'LLL:EXT:ai_tools/Resources/Private/Language/locallang_db.xlf:tx_aitools_domain_model_server.formality',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'Default',
                        'default',
                    ],
                    [
                        'More',
                        'more',
                    ],
                    [
                        'Less',
                        'less',
                    ],
                ],
            ],
        ],
        'username' => [
            'exclude' => true,
            'label' => 'LLL:EXT:ai_tools/Resources/Private/Language/locallang_db.xlf:tx_aitools_domain_model_server.username',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'size' => 20,
                'max' => 120,
            ],
        ],
        'password' => [
            'exclude' => true,
            'label' => 'LLL:EXT:ai_tools/Resources/Private/Language/locallang_db.xlf:tx_aitools_domain_model_server.password',
            'config' => [
                'type' => 'input',
                'eval' => 'trim,password',
                'size' => 20,
                'max' => 120,
            ],
        ],
        'imageUrl' => [
            'exclude' => true,
            'label' => 'LLL:EXT:ai_tools/Resources/Private/Language/locallang_db.xlf:tx_aitools_domain_model_server.imageUrl',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'size' => 50,
                'max' => 120,
            ],
        ],
        'translationUrl' => [
            'exclude' => true,
            'label' => 'LLL:EXT:ai_tools/Resources/Private/Language/locallang_db.xlf:tx_aitools_domain_model_server.translationUrl',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'size' => 50,
                'max' => 120,
            ],
        ],
    ],
    'types' => [
        'aigude' => [
            'showitem' => '
                --palette--;;paletteGeneral, apikey
            ',
        ],
        'openai' => [
            'showitem' => '
                --palette--;;paletteGeneral, apikey
            ',
        ],
        'deepl' => [
            'showitem' => '
                --palette--;;paletteGeneral, endpoint, apikey, formality
            ',
        ],
        'custom' => [
            'showitem' => '
                --palette--;;paletteGeneral, apikey, --palette--;;paletteKongAuth, --palette--;;paletteKongUrls,
            ',
        ],
    ],
    'palettes' => [
        'paletteGeneral' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                type, hidden, --linebreak--,
                title,
            ',
        ],
        'paletteKongAuth' => [
            'showitem' => '
                username, password,
            ',
        ],
        'paletteKongUrls' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                imageUrl, --linebreak--,
                translationUrl,
            ',
        ],
    ],
];
