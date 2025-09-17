<?php

namespace Pagemachine\AItools\Placeholder;

class FileHeightPlaceholder extends PlaceholderAbstract
{
    public function getValue(): string
    {
        if (!$this->file || !$this->hasFileProperty('height')) {
            return '';
        }

        return $this->getFileProperty('height');
    }

    public function getExampleValue(): string
    {
        return '1024';
    }
}
