<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class ImageLabel extends AbstractEntity
{
    /**
     * @var string
     */
    protected string $imagelabel = '';

    /**
     * @var string
     */
    protected string $description = '';

    /**
     * @var bool
     */
    protected bool $hidden = false;

    /**
     * @var bool
     */
    protected bool $default = false;

    /**
     * Getter für die ID (bereits in AbstractEntity enthalten)
     *
     * @return int
     */
    public function getUid(): int
    {
        return parent::getUid();  // Verwendet die Methode aus AbstractEntity
    }

    /**
     * @return string
     */
    public function getImageLabel(): string
    {
        return $this->imagelabel;
    }

    /**
     * @param string $imagelabel
     */
    public function setImageLabel(string $imagelabel): void
    {
        $this->imagelabel = $imagelabel;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * Kombinierter Getter für imageLabel und description
     *
     * @return string
     */
    public function getImageLabelWithDescription(): string
    {
        return $this->imagelabel . ': ' . $this->description;
    }

    /**
     * @return bool
     */
    public function isHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * @param bool $hidden
     */
    public function setHidden(bool $hidden): void
    {
        $this->hidden = $hidden;
    }

    /**
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->default;
    }

    /**
     * @param bool $default
     */
    public function setDefault(bool $default): void
    {
        $this->default = $default;
    }
}
