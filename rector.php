<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php74\Rector\LNumber\AddLiteralSeparatorToNumberRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Renaming\Rector\ClassConstFetch\RenameClassConstFetchRector;
use Ssch\TYPO3Rector\Set\Typo3SetList;
use Ssch\TYPO3Rector\TYPO312\v3\MigrateMagicRepositoryMethodsRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/Classes',
        __DIR__ . '/Tests',
    ])
    ->withRootFiles()
    ->withImportNames(
        importShortClasses: false,
        removeUnusedImports: true,
    )
    ->withPhpSets()
    ->withSets([
        PHPUnitSetList::PHPUNIT_100,
        Typo3SetList::TYPO3_11,
        Typo3SetList::TYPO3_12,
    ])
    ->withSkip([
        AddLiteralSeparatorToNumberRector::class,
        __DIR__ . '/Classes/Controller/Backend/ImageCreationController.php', // currently disabled
        RenameClassConstFetchRector::class => [
            __DIR__ . '/packages/**/ExternalImport/*', // Skip invalid AbstractMessage::* migration
        ],
        MigrateMagicRepositoryMethodsRector::class,// ignore for now to support TYPO3 v11
    ]);
