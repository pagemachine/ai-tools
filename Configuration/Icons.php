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
];
