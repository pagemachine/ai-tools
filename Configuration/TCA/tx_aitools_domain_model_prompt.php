<?php

defined('TYPO3') or die();

return [
    'ctrl' => [
        'title' => 'LLL:EXT:ai_tools/Resources/Private/Language/locallang_db.xlf:tx_aitools_domain_model_prompt',
        'label' => 'description',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'default_sortby' => 'description',
        'searchFields' => 'description',
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
        'prompt' => [
            'exclude' => true,
            'label' => 'LLL:EXT:ai_tools/Resources/Private/Language/locallang_db.xlf:tx_aitools_domain_model_prompt.prompt',
            'config' => [
                'type' => 'text',
                'cols' => '40',
                'required' => true,
                'rows' => '15',
                'fieldInformation' => [
                    'PromptInfo' => [
                        'renderType' => 'PromptInfoElement',
                    ]
                ],
            ],
        ],
        'description' => [
            'exclude' => true,
            'label' => 'LLL:EXT:ai_tools/Resources/Private/Language/locallang_db.xlf:tx_aitools_domain_model_prompt.description',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'required' => true,
                'max' => 255,
            ],
        ],
        'type' => [
            'exclude' => true,
            'label' => 'LLL:EXT:ai_tools/Resources/Private/Language/locallang_db.xlf:tx_aitools_domain_model_prompt.type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:ai_tools/Resources/Private/Language/locallang_db.xlf:tx_aitools_domain_model_prompt.img2txt', 'img2txt'],
                ],
            ],
        ],
        'default' => [
            'exclude' => true,
            'label' => 'LLL:EXT:ai_tools/Resources/Private/Language/locallang_db.xlf:tx_aitools_domain_model_prompt.default',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                --palette--;;paletteHidden, default, type, description, prompt,
            ',
        ],
    ],
    'palettes' => [
        'paletteHidden' => [
            'showitem' => '
                hidden
            ',
        ],
    ],
];
