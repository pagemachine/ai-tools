<?php

declare(strict_types=1);

defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1693997897] = \Pagemachine\AItools\Controller\ContextMenu\AiToolItemProvider::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/backend.php']['constructPostProcess'][] = \Pagemachine\AItools\Hooks\BackendControllerHook::class . '->addJavaScript';
