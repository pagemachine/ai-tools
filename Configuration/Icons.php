<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'tx-aitools-svgicon' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:ai_tools/Resources/Public/Icons/ext_icon.svg',
    ],
    'tx-aitools-bitmapicon' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:ai_tools/Resources/Public/Icons/ext_icon.png',
    ],
    // Own icon: core's actions-star is filled on v12/v13 but an outline on v14.
    'tx-aitools-star' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:ai_tools/Resources/Public/Icons/star.svg',
    ],
    'tx-aitools-module' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:ai_tools/Resources/Public/Icons/ext_module.svg',
    ],
    'tx-aitools-module-templates' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:ai_tools/Resources/Public/Icons/ext_module_templates.svg',
    ],
    'tx-aitools-module-settings' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:ai_tools/Resources/Public/Icons/ext_module_settings.svg',
    ],
];
