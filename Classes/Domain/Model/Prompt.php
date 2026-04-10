<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Prompt extends AbstractEntity
{
    protected bool $hidden = false;

    protected string $prompt;

    protected string $description;

    protected string $type;

    protected bool $default = false;

    protected bool $system = false;

    protected string $language = 'en_US';

    public function getHidden(): bool
    {
        return $this->hidden;
    }

    public function setHidden(bool $hidden): void
    {
        $this->hidden = $hidden;
    }

    public function getPrompt(): string
    {
        return $this->prompt;
    }

    public function setPrompt(string $prompt): void
    {
        $this->prompt = $prompt;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getDescriptionPrompt(): string
    {
        return $this->description . ' (' . $this->prompt . ')';
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function isDefault(): bool
    {
        return $this->default;
    }

    public function setDefault(bool $default): void
    {
        $this->default = $default;
    }

    public function isSystem(): bool
    {
        return $this->system;
    }

    public function setSystem(bool $system): void
    {
        $this->system = $system;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }
}
