<?php

use Pagemachine\AItools\Controller\Backend\PromptsController;
use Pagemachine\AItools\Controller\Backend\ServersController;
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
        'labels' => 'LLL:EXT:ai_tools/Resources/Private/Language/locallang_db.xlf:tx_aitools_domain_model_prompt.templates',
        'extensionName' => 'AItools',
        'controllerActions' => [
            PromptsController::class => [
                'list',
            ],
        ],
    ],
    'AItoolsAitools_AItoolsSettings' => [
        'parent' => 'AItoolsAitools',
        'position' => ['before' => '*'],
        'access' => 'admin',
        'workspaces' => 'live',
        'iconIdentifier' => 'module-install-settings',
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
    'AItoolsAitools_AItoolsServers' => [
        'parent' => 'AItoolsAitools',
        'position' => ['before' => '*'],
        'access' => 'admin',
        'workspaces' => 'live',
        'iconIdentifier' => 'apps-filetree-mount',
        'path' => '/module/aitools/AItoolsServers',
        'labels' => 'LLL:EXT:ai_tools/Resources/Private/Language/locallang_db.xlf:tx_aitools_domain_model_server.servers',
        'extensionName' => 'AItools',
        'controllerActions' => [
            ServersController::class => [
                'list',
            ],
        ],
    ],
];
