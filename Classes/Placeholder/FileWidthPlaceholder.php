<?php

namespace Pagemachine\AItools\Placeholder;

class FileWidthPlaceholder extends PlaceholderAbstract
{
    public function getValue(): string
    {
        if (!$this->file || !$this->file->hasProperty('width')) {
            return '';
        }

        return $this->file->getProperty('width');
    }

    public function getExampleValue(): string
    {
        return '1024';
    }
}
