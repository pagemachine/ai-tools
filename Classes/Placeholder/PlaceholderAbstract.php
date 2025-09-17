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

    /**
     * @var Array|null
     */
    protected $fileReference;

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
}
