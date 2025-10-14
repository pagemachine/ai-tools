<?php

namespace Pagemachine\AItools\Placeholder;

class FileTitlePlaceholder extends PlaceholderAbstract
{
    public function getValue(): string
    {
        if (!$this->file || !$this->hasFileProperty('title')) {
            return '';
        }

        return $this->getFileProperty('title');
    }

    public function getExampleValue(): string
    {
        return 'Picture of a car';
    }
}
