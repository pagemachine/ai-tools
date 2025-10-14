<?php

namespace Pagemachine\AItools\Placeholder;

class FilenamePlaceholder extends PlaceholderAbstract
{
    public function getValue(): string
    {
        if (!$this->file) {
            return '';
        }

        return $this->file->getName();
    }

    public function getExampleValue(): string
    {
        return 'car-photo-123.png';
    }
}
