<?php

declare(strict_types=1);

use Pagemachine\AItools\ContextMenu\ItemProviders\AiToolItemProvider;
use Pagemachine\AItools\FormEngine\FieldInformation\ApiKeyInfoElement;
use Pagemachine\AItools\FormEngine\FieldInformation\PromptInfoElement;
use Pagemachine\AItools\FormEngine\FieldWizard\AlternativeGenerator;
use Pagemachine\AItools\Hooks\DataHandlerHooks;
use Pagemachine\AItools\Placeholder\CurrentTimePlaceholder;
use Pagemachine\AItools\Placeholder\FileAlternativePlaceholder;
use Pagemachine\AItools\Placeholder\FileCategoriesPlaceholder;
use Pagemachine\AItools\Placeholder\FileDescriptionPlaceholder;
use Pagemachine\AItools\Placeholder\FileHeightPlaceholder;
use Pagemachine\AItools\Placeholder\FileMimePlaceholder;
use Pagemachine\AItools\Placeholder\FilenameNoExtensionPlaceholder;
use Pagemachine\AItools\Placeholder\FilenamePlaceholder;
use Pagemachine\AItools\Placeholder\FileTitlePlaceholder;
use Pagemachine\AItools\Placeholder\FileWidthPlaceholder;
use Pagemachine\AItools\Service\Credits\AigudeCreditsService;
use Pagemachine\AItools\Service\ImageRecognition\AigudeImageRecognitionService;
use Pagemachine\AItools\Service\Translation\AigudeTranslationService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['ai_tools']['servers'] = [
    'aigude' => [
        'name' => 'AI Gude',
        'credits' => AigudeCreditsService::class,
        'functionality' => [
            'translation' => AigudeTranslationService::class,
            'image_recognition' => AigudeImageRecognitionService::class,
            'translation_provider' => \Pagemachine\AItools\Service\TranslationProvider\AigudeTranslationProviderService::class,
        ],
    ],
];

$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['ai_tools']['placeholder'] = [
    'current_time' => CurrentTimePlaceholder::class,
    'filename' => FilenamePlaceholder::class,
    'filename_no_ext' => FilenameNoExtensionPlaceholder::class,
    'title' => FileTitlePlaceholder::class,
    'categories' => FileCategoriesPlaceholder::class,
    'description' => FileDescriptionPlaceholder::class,
    'current_alt' => FileAlternativePlaceholder::class,
    'mime' => FileMimePlaceholder::class,
    'height' => FileHeightPlaceholder::class,
    'width' => FileWidthPlaceholder::class,
];

$version = GeneralUtility::makeInstance(VersionNumberUtility::class)->getNumericTypo3Version();

if (version_compare($version, '11.0', '>=') && version_compare($version, '12.0', '<')) {
    // for TYPO3 v11
    $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1693997897] = AiToolItemProvider::class;
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['aitools'] = DataHandlerHooks::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['aitools'] = DataHandlerHooks::class;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1726477308] = [
    'nodeName' => 'AlternativeGenerator',
    'priority' => 30,
    'class' => AlternativeGenerator::class,
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1731322595] = [
    'nodeName' => 'ApiKeyInfoElement',
    'priority' => 70,
    'class' => ApiKeyInfoElement::class,
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1758028928] = [
    'nodeName' => 'PromptInfoElement',
    'priority' => 70,
    'class' => PromptInfoElement::class,
];
