<?php

namespace Pagemachine\AItools\Placeholder;

class FileWidthPlaceholder extends PlaceholderAbstract
{
    protected bool $shouldBeQuoted = false;

    public function getValue(): string
    {
        if (!$this->file || !$this->hasFileProperty('width')) {
            return '';
        }

        return $this->getFileProperty('width');
    }

    public function getExampleValue(): string
    {
        return '1024';
    }
}
