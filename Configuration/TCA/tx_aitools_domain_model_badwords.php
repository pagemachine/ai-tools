<?php

defined('TYPO3_MODE') or die();

return [
    'ctrl' => [
        'title' => 'Badwords',
        'label' => 'badword',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'default_sortby' => 'badword',
        'searchFields' => 'badword',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:ai_tools/Resources/Public/Icons/ext_icon.png',
        'hideAtCopy' => true,
        'rootLevel' => -1,
        'security' => [
            'ignoreWebMountRestriction' => true,
            'ignoreRootLevelRestriction' => true,
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                --palette--;;paletteHidden, badword,
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
    'columns' => [
        'imagelabelid' => [
            'exclude' => false,
            'label' => 'LLL:EXT:ai_tools/Resources/Private/Language/locallang_db.xlf:tx_aitools_domain_model_badwords.imagelabelid',
            'config' => [
                'type' => 'input',
                'eval' => 'int',
                'placeholder' => 'Enter image label ID',
                'default' => 0,
            ],
        ],
        'badword' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ai_tools/Resources/Private/Language/locallang_db.xlf:tx_aitools_domain_model_badwords.badword',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'max' => 255,
            ],
        ],
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'tstamp' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.tstamp',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime',
                'default' => 0,
            ],
        ],
        'crdate' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.crdate',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime',
                'default' => 0,
            ],
        ],
    ],
];
