<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Domain\Repository;

use Pagemachine\AItools\Domain\Model\Prompt;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class PromptRepository extends Repository
{
    public const SYSTEM_PROMPT_TEXT = 'Describe the essential content of the picture briefly and concisely. Limit the text to a very short sentence. Avoid elements such as "The picture shows" and descriptive adjectives.';

    public function listAllPrompts(): QueryResultInterface
    {
        $query = $this->createQuery();

        $query->getQuerySettings()
            ->setIgnoreEnableFields(true);

        return $query->execute();
    }

    public function getDefaultPrompt(): Prompt
    {
        $this->ensureSystemPromptExists();

        $version = GeneralUtility::makeInstance(VersionNumberUtility::class)->getNumericTypo3Version();
        if (version_compare($version, '11.0', '>=') && version_compare($version, '12.0', '<')) {
            // @phpstan-ignore-next-line
            $defaultPrompt = $this->findOneByDefault(true);
        } else {
            /**
             * @var Prompt $defaultPrompt
             * @phpstan-ignore-next-line
             */
            $defaultPrompt = $this->findOneBy(['default' => true]);
        }

        if (!$defaultPrompt) {
            $defaultPrompt = $this->findSystemPrompt();
        }

        return $defaultPrompt;
    }

    public function getDefaultPromptText(): string
    {
        return $this->getDefaultPrompt()->getPrompt();
    }

    public function ensureSystemPromptExists(): void
    {
        $systemPrompt = $this->findSystemPrompt();

        if ($systemPrompt !== null) {
            return;
        }

        $version = GeneralUtility::makeInstance(VersionNumberUtility::class)->getNumericTypo3Version();
        if (version_compare($version, '11.0', '>=') && version_compare($version, '12.0', '<')) {
            // @phpstan-ignore-next-line
            $existingDefault = $this->findOneByDefault(true);
        } else {
            /** @phpstan-ignore-next-line */
            $existingDefault = $this->findOneBy(['default' => true]);
        }

        $prompt = new Prompt();
        $prompt->setPrompt(self::SYSTEM_PROMPT_TEXT);
        $prompt->setDescription('Default Prompt');
        $prompt->setType('img2txt');
        $prompt->setSystem(true);
        $prompt->setDefault($existingDefault === null);
        $prompt->setLanguage('en_US');
        $prompt->setPid(0);
        $this->add($prompt);
        GeneralUtility::makeInstance(PersistenceManager::class)->persistAll();
    }

    private function findSystemPrompt(): ?Prompt
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setIgnoreEnableFields(true);
        $query->matching($query->equals('system', true));

        /** @var Prompt|null $result */
        $result = $query->execute()->getFirst();
        return $result;
    }
}
