<?php

declare(strict_types=1);

defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['BE']['customPermOptions']['tx_aitools_permissions'] = [
    'header' => 'AI Tools permissions',
    'items' => [
        'prompt_management' => [ // key
            'Prompt management',
            // Icon has been registered above
            'tcarecords-tx_styleguide_forms-default',
            'Allows User to manage prompts',
        ],
    ],
];
