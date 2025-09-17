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
}
