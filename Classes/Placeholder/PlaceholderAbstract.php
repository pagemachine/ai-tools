<?php

namespace Pagemachine\AItools\Placeholder;

use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

/**
 * Abstract class for prompt placeholders
 */
abstract class PlaceholderAbstract implements PlaceholderInterface
{
    /**
     * @var FileInterface|null
     */
    protected $file;

    /**
     * @var Array|null
     */
    protected $fileReference;

    protected bool $shouldBeQuoted = true;

    public function getFile(): FileInterface
    {
        return $this->file;
    }

    public function setFile(FileInterface $file): void
    {
        $this->file = $file;
    }

    public function getFileReference(): ?Array
    {
        return $this->fileReference;
    }

    public function setFileReference(Array $fileReference): void
    {
        $this->fileReference = $fileReference;
    }

    public function shouldBeQuotedByDefault(): bool
    {
        return $this->shouldBeQuoted;
    }

    public function getLanguage(): ?string
    {
        return null;
    }

    protected function getFileProperty(string $propertyName): string
    {
        if (is_array($this->fileReference) && array_key_exists($propertyName, $this->fileReference)) {
            $value = $this->fileReference[$propertyName];
            if (!empty($value)) {
                return $value;
            }
        }

        if ($this->file && $this->file->hasProperty($propertyName)) {
            $value = $this->file->getProperty($propertyName);
            if (!empty($value)) {
                return $value;
            }
        }

        return '';
    }

    protected function hasFileProperty(string $propertyName): bool
    {
        if (is_array($this->fileReference) && array_key_exists($propertyName, $this->fileReference)) {
            $value = $this->fileReference[$propertyName];
            if (!empty($value)) {
                return true;
            }
        }

        if ($this->file && $this->file->hasProperty($propertyName)) {
            $value = $this->file->getProperty($propertyName);
            if (!empty($value)) {
                return true;
            }
        }

        return false;
    }

    protected function getFilePropertyLanguage(string $propertyName): ?string
    {
        if (is_array($this->fileReference) && array_key_exists($propertyName, $this->fileReference)) {
            $value = $this->fileReference[$propertyName];
            if (!empty($value)) {
                return $this->getLanguageCodeById($this->fileReference['sys_language_uid']);
            }
        }

        if ($this->file && $this->file->hasProperty($propertyName)) {
            $value = $this->file->getProperty($propertyName);
            if (!empty($value)) {
                return $this->getLanguageCodeById($this->file->getProperty('sys_language_uid'));
            }
        }

        return null;
    }

    private function getLanguageCodeById(int $languageId)
    {
        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        $sites = $siteFinder->getAllSites();
        foreach ($sites as $site) {
            try {
                $site = $site->getLanguageById($languageId);
                return $this->getLocaleLanguageCode($site);
            } catch (\Exception) {
                continue;
            }
        }
        return null;
    }

    public function getLocaleLanguageCode(SiteLanguage $siteLanguage): string
    {
        $version = GeneralUtility::makeInstance(VersionNumberUtility::class)->getNumericTypo3Version();
        if (version_compare($version, '12.0', '>=')) {
            // @phpstan-ignore-next-line Stop PHPStan about complaining this line for TYPO3 v11
            return $siteLanguage->getLocale()->getLanguageCode();
        }
        return $siteLanguage->getTwoLetterIsoCode(); // @phpstan-ignore-line
    }
}
