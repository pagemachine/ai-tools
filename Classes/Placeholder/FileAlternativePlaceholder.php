<?php

namespace Pagemachine\AItools\Placeholder;

class FileAlternativePlaceholder extends PlaceholderAbstract
{
    public function getValue(): string
    {
        if (!$this->file || !$this->file->hasProperty('alternative')) {
            return '';
        }

        return $this->file->getProperty('alternative');
    }

    public function getExampleValue(): string
    {
        return 'A red car parked on the street';
    }
}
