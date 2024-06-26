<?php

declare(strict_types=1);
use Pagemachine\AItools\ContextMenu\AiToolItemProvider;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

defined('TYPO3') or die();

$version = GeneralUtility::makeInstance(VersionNumberUtility::class)->getNumericTypo3Version();

if (version_compare($version, '11.0', '>=') && version_compare($version, '12.0', '<')) {
    // for TYPO3 v11
    $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1693997897] = AiToolItemProvider::class;
}
