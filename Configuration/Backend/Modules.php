<?php

return [
    'aitools' => [
        'position' => ['after' => 'web'],
        'iconIdentifier' => 'EXT:ai_tools/Resources/Public/Icons/ext_icon.svg',
        'labels' => 'LLL:EXT:ai_tools/Resources/Private/Language/BackendModules/locallang_be_mainmodule.xlf',
    ],
    /*'aitools_AItoolsImages' => [
        'parent' => 'aitools',
        'position' => ['before' => '*'],
        'access' => 'user,group',
        'workspaces' => 'live',
        'iconIdentifier' => 'EXT:ai_tools/Resources/Public/Icons/ext_icon.svg',
        'path' => '/module/aitools/AItoolsImages',
        'labels' => 'LLL:EXT:ai_tools/Resources/Private/Language/BackendModules/locallang_be_aiimage.xlf',
        'extensionName' => 'AItools',
        'controllerActions' => [
            \Pagemachine\AItools\Controller\Backend\ImageCreationController::class => [
                'show',
                'generate',
                'variate',
                'save',
            ],
            \Pagemachine\AItools\Controller\Backend\ImageRecognizeController::class => [
                'describe',
            ],
        ],
    ],*/
    'aitools_AItoolsSettings' => [
        'parent' => 'aitools',
        'position' => ['before' => '*'],
        'access' => 'user,group',
        'workspaces' => 'live',
        'iconIdentifier' => 'EXT:ai_tools/Resources/Public/Icons/ext_icon.svg',
        'path' => '/module/aitools/AItoolsSettings',
        'labels' => 'LLL:EXT:ai_tools/Resources/Private/Language/BackendModules/locallang_be_settings.xlf',
        'extensionName' => 'AItools',
        'controllerActions' => [
            \Pagemachine\AItools\Controller\Backend\SettingsController::class => [
                'settings',
                'save',
                'addPrompt',
                'saveDefaultPrompt',
            ],
        ],
    ],
];
