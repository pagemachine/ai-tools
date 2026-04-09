<?php

use Pagemachine\AItools\Controller\Backend\PromptsController;
use Pagemachine\AItools\Controller\Backend\ServersController;

return [
    'AItoolsAitools' => [
        'position' => ['after' => 'content'],
        'iconIdentifier' => 'tx-aitools-module',
        'labels' => 'LLL:EXT:ai_tools/Resources/Private/Language/BackendModules/locallang_be_mainmodule.xlf',
    ],
    'AItoolsAitools_AItoolsPrompts' => [
        'parent' => 'AItoolsAitools',
        'position' => ['before' => '*'],
        'access' => 'user',
        'workspaces' => 'live',
        'iconIdentifier' => 'tx-aitools-module-templates',
        'path' => '/module/aitools/AItoolsPrompts',
        'labels' => 'LLL:EXT:ai_tools/Resources/Private/Language/BackendModules/locallang_be_prompts.xlf',
        'extensionName' => 'AItools',
        'controllerActions' => [
            PromptsController::class => [
                'list',
                'restoreDefaults',
            ],
        ],
    ],
    'AItoolsAitools_AItoolsServers' => [
        'parent' => 'AItoolsAitools',
        'position' => ['before' => '*'],
        'access' => 'admin',
        'workspaces' => 'live',
        'iconIdentifier' => 'tx-aitools-module-settings',
        'path' => '/module/aitools/AItoolsSettings',
        'labels' => 'LLL:EXT:ai_tools/Resources/Private/Language/BackendModules/locallang_be_settings.xlf',
        'extensionName' => 'AItools',
        'controllerActions' => [
            ServersController::class => [
                'list',
                'saveSettings',
            ],
        ],
    ],
];
