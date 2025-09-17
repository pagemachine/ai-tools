<?php

namespace Pagemachine\AItools\Placeholder;

class FileDescriptionPlaceholder extends PlaceholderAbstract
{
    public function getValue(): string
    {
        if (!$this->file || !$this->hasFileProperty('description')) {
            return '';
        }

        return $this->getFileProperty('description');
    }

    public function getExampleValue(): string
    {
        return 'A beautiful scenery of mountains during sunset.';
    }
}
