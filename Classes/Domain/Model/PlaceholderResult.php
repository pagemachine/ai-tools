<?php

namespace Pagemachine\AItools\Domain\Model;

class PlaceholderResult
{
    /**
     * @param string $text The processed text
     * @param array<string, Placeholder> $placeholders
     * @param string|null $language
     */
    public function __construct(private readonly string $text, private array $placeholders, private readonly ?string $language = null)
    {
    }

    public function getText(): string
    {
        return $this->text;
    }

    /**
     *
     * @return array<string, Placeholder>
     */
    public function getPlaceholders(): array
    {
        return $this->placeholders;
    }

    public function getPlaceholder(string $key): ?Placeholder
    {
        return $this->placeholders[$key] ?? null;
    }

    public function hasPlaceholders(): bool
    {
        return !empty($this->placeholders);
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }
}
