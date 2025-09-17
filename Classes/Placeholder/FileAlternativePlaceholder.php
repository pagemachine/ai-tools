<?php

namespace Pagemachine\AItools\Placeholder;

class FileAlternativePlaceholder extends PlaceholderAbstract
{
    public function getValue(): string
    {
        if (!$this->file || !$this->hasFileProperty('alternative')) {
            return '';
        }

        return $this->getFileProperty('alternative');
    }

    public function getExampleValue(): string
    {
        return 'A red car parked on the street';
    }
}
