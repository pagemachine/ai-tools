<?php
defined('TYPO3') or die();

return [
    'ctrl' => [
        'title' => 'LLL:EXT:aitools/Resources/Private/Language/locallang_db.xlf:tx_aitools_domain_model_prompt',
        'label' => 'description',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'default_sortby' => 'description',
        'searchFields' => 'description',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:aitools/Resources/Public/Icons/ext_icon.png',
        'hideAtCopy' => true,
        'thumbnail' => 'logo',
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
            'label' => 'LLL:EXT:aitools/Resources/Private/Language/locallang_db.xlf:tx_aitools_domain_model_prompt.prompt',
            'config' => [
                'type' => 'text',
                'cols' => '40',
                'rows' => '15',
            ],
        ],
        'description' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aitools/Resources/Private/Language/locallang_db.xlf:tx_aitools_domain_model_prompt.description',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'required' => true,
                'max' => 255,
            ],
        ],
        'type' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aitools/Resources/Private/Language/locallang_db.xlf:tx_aitools_domain_model_prompt.type',
            'config' => [
                'type' => 'input',
                'maxlength' => 255,
                'size' => '20',
                'eval' => 'trim',
            ],
        ],
        'default' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aitools/Resources/Private/Language/locallang_db.xlf:tx_aitools_domain_model_prompt.default',
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
                    prompt, description, type,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;paletteHidden,
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
