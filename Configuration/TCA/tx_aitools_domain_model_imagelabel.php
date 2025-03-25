<?php

defined('TYPO3') or die();

return [
    'ctrl' => [
        'title' => 'description',
        'label' => 'imagelabel',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'default_sortby' => 'imagelabel',
        'searchFields' => 'imagelabel',
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
        'description' => [
            'exclude' => true,
            'label' => 'description',
            'config' => [
                'type' => 'text',
                'cols' => '40',
                'required' => true,
                'rows' => '5',
            ],
        ],
        'imagelabel' => [
            'exclude' => true,
            'label' => 'imagelabel',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'required' => true,
                'max' => 255,
            ],
        ],
        'default' => [
            'exclude' => true,
            'label' => 'default',
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
                --palette--;;paletteHidden, imagelabel, description,
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
