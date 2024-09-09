<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Domain\Model;

use Pagemachine\AItools\Service\ServerService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Server extends AbstractEntity
{
    /**
     * @var bool
     */
    protected bool $hidden;

    /**
     * @var string
     */
    protected string $title;

    /**
     * @var string
     */
    protected string $type;

    public function getHidden(): bool
    {
        return $this->hidden;
    }

    public function setHidden(bool $hidden): void
    {
        $this->hidden = $hidden;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getServer(): string
    {
        return GeneralUtility::makeInstance(ServerService::class)->getNameOfServerType($this->getType());
    }

    public function getFunctionality(): array
    {
        return GeneralUtility::makeInstance(ServerService::class)->getFunctionalityOfServerType($this->getType());
    }
}
