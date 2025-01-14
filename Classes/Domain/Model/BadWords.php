<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class BadWords extends AbstractEntity
{
    /**
     * @var string
     */
    protected string $badword = '';

    protected int $imagelabelid = 0;

    /**
     * @var bool
     */
    protected bool $hidden = false;

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
     * @return int
     */
    public function getImagelabelid(): int
    {
        return $this->imagelabelid;
    }

    /**
     * @param int $imagelabelid
     */
    public function setImagelabelid(int $imagelabelid): void
    {
        $this->imagelabelid = $imagelabelid;
    }

    /**
     * @return string
     */
    public function getBadword(): string
    {
        return $this->badword;
    }

    /**
     * @param string $badword
     */
    public function setBadword(string $badword): void
    {
        $this->badword = $badword;
    }
}
