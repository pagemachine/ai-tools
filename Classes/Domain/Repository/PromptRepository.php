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

    public function getDefaultPrompt(): Prompt
    {
        /** @var Prompt|null $defaultPrompt */
        $defaultPrompt = $this->findOneBy(['default' => true]);

        if (!$defaultPrompt) {
            $tempPrompt = new Prompt();
            $tempPrompt->setPrompt('Describe the essential content of the picture briefly and concisely. Limit the text to a very short sentence. Avoid elements such as "The picture shows" and descriptive adjectives.');
            $tempPrompt->setDescription('Fallback prompt');
            $tempPrompt->setType('alternative');
            $tempPrompt->setDefault(true);
            $tempPrompt->setLanguage('en_US');
            $defaultPrompt = $tempPrompt;
        }

        return $defaultPrompt;
    }

    public function getDefaultPromptText(): string
    {
        return $this->getDefaultPrompt()->getPrompt();
    }
}
