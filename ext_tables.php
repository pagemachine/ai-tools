<?php

declare(strict_types=1);

defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['BE']['customPermOptions']['tx_aitools_permissions'] = [
    'header' => 'AI Tools permissions',
    'items' => [
        'generate_metadata' => [
            'Generate metadata',
            'tcarecords-tx_styleguide_forms-default',
            'Allows User to use the generate metadata context menu',
        ],
    ],
];
