<?php

namespace Pagemachine\AItools\Domain\Model;

class Placeholder
{
    /**
     * @param string $value The resolved value of the placeholder
     * @param string $valueWithModifiers The resolved value after applying modifiers
     * @param string $identifier The placeholder identifier (e.g., 'filename' from %filename%)
     * @param array<string> $modifiers Applied modifiers (e.g., ['trim', 'upper'])
     * @param string $placeholder The full placeholder text (e.g., '%filename|trim%')
     * @param string|null $language The language code if this placeholder has language-specific content
     */
    public function __construct(private readonly string $value, private readonly string $valueWithModifiers, private readonly string $identifier, private readonly array $modifiers, private readonly string $placeholder, private readonly ?string $language = null)
    {
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getValueWithModifiers(): string
    {
        return $this->valueWithModifiers;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getModifiers(): array
    {
        return $this->modifiers;
    }

    public function getPlaceholderText(): string
    {
        return $this->placeholder;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }
}
