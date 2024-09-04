<?php

use Pagemachine\AItools\Controller\Backend\SettingsController;

return [
    'AItoolsAitools' => [
        'position' => ['after' => 'web'],
        'iconIdentifier' => 'tx-aitools-svgicon',
        'labels' => 'LLL:EXT:ai_tools/Resources/Private/Language/BackendModules/locallang_be_mainmodule.xlf',
    ],
    'AItoolsAitools_AItoolsPrompts' => [
        'parent' => 'AItoolsAitools',
        'position' => ['before' => '*'],
        'access' => 'user',
        'workspaces' => 'live',
        'iconIdentifier' => 'actions-notebook',
        'path' => '/module/aitools/AItoolsPrompts',
        'labels' => 'LLL:EXT:ai_tools/Resources/Private/Language/BackendModules/locallang_be_settings.xlf',
        'extensionName' => 'AItools',
        'controllerActions' => [
            SettingsController::class => [
                'promptList',
            ],
        ],
    ],
    'AItoolsAitools_AItoolsSettings' => [
        'parent' => 'AItoolsAitools',
        'position' => ['before' => '*'],
        'access' => 'admin',
        'workspaces' => 'live',
        'iconIdentifier' => 'module-install-setting',
        'path' => '/module/aitools/AItoolsSettings',
        'labels' => 'LLL:EXT:ai_tools/Resources/Private/Language/BackendModules/locallang_be_settings.xlf',
        'extensionName' => 'AItools',
        'controllerActions' => [
            SettingsController::class => [
                'settings',
                'save',
            ],
        ],
    ],
];
