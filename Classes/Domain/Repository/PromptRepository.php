<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Domain\Repository;

use Pagemachine\AItools\Domain\Model\Prompt;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class PromptRepository extends Repository
{
    public function listAllPrompts(): QueryResultInterface
    {
        $query = $this->createQuery();

        $query->getQuerySettings()
            ->setIgnoreEnableFields(true);

        return $query->execute();
    }

    public function getDefaultPrompt(): ?Prompt
    {
        return $this->findOneBy(['default' => true]);
    }
}
