<?php

namespace Pagemachine\AItools\Placeholder;

class FileMimePlaceholder extends PlaceholderAbstract
{
    public function getValue(): string
    {
        if (!$this->file) {
            return '';
        }

        return $this->file->getMimeType();
    }

    public function getExampleValue(): string
    {
        return 'image/png';
    }
}
