<?php

namespace Pagemachine\AItools\Placeholder;

use TYPO3\CMS\Core\Resource\FileInterface;

/**
 * Interface for prompt placeholders, such as %filename%.
 */
interface PlaceholderInterface
{
    /**
     * Get the value to replace the placeholder with.
     *
     * @return string The value to replace the placeholder.
     */
    public function getValue(): string;

    /**
     * Get example value for the placeholder.
     * @return string Example value.
     */
    public function getExampleValue(): string;

    /**
     * Get the file associated with the placeholder.
     * @return FileInterface
     */
    public function getFile(): FileInterface;

    /**
     * Set the file associated with the placeholder.
     * @param FileInterface $file
     * @return void
     */
    public function setFile(FileInterface $file): void;
}
