<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Prompt extends AbstractEntity
{
    /**
     * @var bool
     */
    protected bool $hidden;

    /**
     * @var string
     */
    protected string $prompt;

    /**
     * @var string
     */
    protected string $description;

    /**
     * @var string
     */
    protected string $type;

    /**
     * @var bool
     */
    protected bool $default = false;

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
}
