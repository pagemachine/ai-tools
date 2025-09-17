<?php

namespace Pagemachine\AItools\Placeholder;

class FileTitlePlaceholder extends PlaceholderAbstract
{
    public function getValue(): string
    {
        if (!$this->file || !$this->file->hasProperty('title')) {
            return '';
        }

        return $this->file->getProperty('title');
    }

    public function getExampleValue(): string
    {
        return 'Picture of a car';
    }
}
