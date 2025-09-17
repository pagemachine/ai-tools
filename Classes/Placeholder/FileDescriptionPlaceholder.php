<?php

namespace Pagemachine\AItools\Placeholder;

class FileDescriptionPlaceholder extends PlaceholderAbstract
{
    public function getValue(): string
    {
        if (!$this->file || !$this->file->hasProperty('description')) {
            return '';
        }

        return $this->file->getProperty('description');
    }

    public function getExampleValue(): string
    {
        return 'A beautiful scenery of mountains during sunset.';
    }
}
