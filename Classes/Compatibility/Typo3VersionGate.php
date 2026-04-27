<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Compatibility;

use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Core\Resource\FileType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

/**
 * Cross-version helpers bridging TYPO3 v12, v13, v14 API differences.
 */
final class Typo3VersionGate
{
    public static function major(): int
    {
        $version = GeneralUtility::makeInstance(VersionNumberUtility::class)->getNumericTypo3Version();
        return (int) explode('.', $version)[0];
    }

    public static function isV14OrHigher(): bool
    {
        return self::major() >= 14;
    }

    /**
     * Image file type constant: FileType enum value on v14, AbstractFile constant on v12/v13.
     */
    public static function imageFileType(): int
    {
        if (self::isV14OrHigher()) {
            // @phpstan-ignore-next-line FileType enum only exists in v13+; reachable only on v14
            return FileType::IMAGE->value;
        }
        // @phpstan-ignore-next-line FILETYPE_IMAGE removed in v14; reachable only on v12/v13
        return AbstractFile::FILETYPE_IMAGE;
    }

    /**
     * Icon size for IconFactory::getIcon: IconSize enum on v14, string literal on v12/v13.
     */
    public static function iconSizeSmall(): mixed
    {
        if (self::isV14OrHigher()) {
            // @phpstan-ignore-next-line IconSize enum only exists in v13+
            return IconSize::SMALL;
        }
        return 'small';
    }
}
