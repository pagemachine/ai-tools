<?php

namespace Pagemachine\AItools\Placeholder;

class FilenameNoExtensionPlaceholder extends PlaceholderAbstract
{
    public function getValue(): string
    {
        if (!$this->file) {
            return '';
        }

        return $this->file->getNameWithoutExtension();
    }

    public function getExampleValue(): string
    {
        return 'car-photo-123';
    }
}
