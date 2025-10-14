<?php

namespace Pagemachine\AItools\Placeholder;

class CurrentTimePlaceholder extends PlaceholderAbstract
{
    public function getValue(): string
    {
        return date('Y-m-d H:i:s');
    }

    public function getExampleValue(): string
    {
        return '2025-09-16 14:30:00';
    }
}
