<?php

declare(strict_types=1);

use Pagemachine\AItools\ContextMenu\ItemProviders\AiToolItemProvider;
use Pagemachine\AItools\FormEngine\FieldWizard\AlternativeGenerator;
use Pagemachine\AItools\Hooks\DataHandlerHooks;
use Pagemachine\AItools\Service\Credits\AigudeCreditsService;
use Pagemachine\AItools\Service\ImageRecognition\AigudeImageRecognitionService;
use Pagemachine\AItools\Service\ImageRecognition\CustomImageRecognitionService;
use Pagemachine\AItools\Service\ImageRecognition\OpenAiImageRecognitionService;
use Pagemachine\AItools\Service\Translation\AigudeTranslationService;
use Pagemachine\AItools\Service\Translation\CustomTranslationService;
use Pagemachine\AItools\Service\Translation\DeepLTranslationService;
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
        ],
    ],
    'openai' => [
        'name' => 'Open AI',
        'functionality' => [
            'image_recognition' => OpenAiImageRecognitionService::class,
        ],
    ],
    'deepl' => [
        'name' => 'DeepL',
        'functionality' => [
            'translation' => DeepLTranslationService::class,
        ],
    ],
    'custom' => [
        'name' => 'Custom',
        'functionality' => [
            'image_recognition' => CustomImageRecognitionService::class,
            'translation' => CustomTranslationService::class,
        ],
    ],
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
