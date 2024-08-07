<?php

use Pagemachine\AItools\Controller\Backend\SettingsController;

return [
    'AItoolsAitools' => [
        'position' => ['after' => 'web'],
        'iconIdentifier' => 'tx-aitools-svgicon',
        'labels' => 'LLL:EXT:ai_tools/Resources/Private/Language/BackendModules/locallang_be_mainmodule.xlf',
    ],
    /*'AItoolsAitools_AItoolsImages' => [
        'parent' => 'AItoolsAitools',
        'position' => ['before' => '*'],
        'access' => 'user',
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
    'AItoolsAitools_AItoolsSettings' => [
        'parent' => 'AItoolsAitools',
        'position' => ['before' => '*'],
        'access' => 'user',
        'workspaces' => 'live',
        'iconIdentifier' => 'tx-aitools-svgicon',
        'path' => '/module/aitools/AItoolsSettings',
        'labels' => 'LLL:EXT:ai_tools/Resources/Private/Language/BackendModules/locallang_be_settings.xlf',
        'extensionName' => 'AItools',
        'controllerActions' => [
            SettingsController::class => [
                'settings',
                'save',
                'addPrompt',
                'saveDefaultPrompt',
            ],
        ],
    ],
];
