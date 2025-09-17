<?php

namespace Pagemachine\AItools\Placeholder;

use TYPO3\CMS\Core\Resource\FileInterface;

/**
 * Abstract class for prompt placeholders
 */
abstract class PlaceholderAbstract implements PlaceholderInterface
{
    /**
     * @var FileInterface|null
     */
    protected $file;

    public function getFile(): FileInterface
    {
        return $this->file;
    }

    public function setFile(FileInterface $file): void
    {
        $this->file = $file;
    }

    protected function getFileProperty(string $propertyName): string
    {
        if (!$this->file || !$this->file->hasProperty($propertyName)) {
            return '';
        }

        return $this->file->getProperty($propertyName) ?? '';
    }

    protected function hasFileProperty(string $propertyName): bool
    {
        if (!$this->file) {
            return false;
        }

        return $this->file->hasProperty($propertyName);
    }
}
