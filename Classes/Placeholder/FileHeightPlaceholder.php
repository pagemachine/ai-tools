<?php

namespace Pagemachine\AItools\Placeholder;

class FileHeightPlaceholder extends PlaceholderAbstract
{
    public function getValue(): string
    {
        if (!$this->file || !$this->file->hasProperty('height')) {
            return '';
        }

        return $this->file->getProperty('height');
    }

    public function getExampleValue(): string
    {
        return '1024';
    }
}
