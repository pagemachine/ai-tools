<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Updates;

use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Upgrades\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Core\Upgrades\UpgradeWizardInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[UpgradeWizard('aiToolsDefaultPrompt')]
class DefaultPromptUpdateWizard implements UpgradeWizardInterface
{
    private const TABLE = 'tx_aitools_domain_model_prompt';
    private const DEFAULT_DESCRIPTION = 'Default prompt';
    private const DEFAULT_PROMPT = 'Describe the essential content of the picture briefly and concisely. Limit the text to a very short sentence. Avoid elements such as "The picture shows" and descriptive adjectives.';

    public function getTitle(): string
    {
        return 'AI Tools: Create default prompt';
    }

    public function getDescription(): string
    {
        return 'Creates the built-in default prompt if it does not exist yet.';
    }

    public function updateNecessary(): bool
    {
        return !$this->defaultPromptExists();
    }

    public function executeUpdate(): bool
    {
        if ($this->defaultPromptExists()) {
            return true;
        }

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::TABLE);

        $connection->insert(self::TABLE, [
            'pid' => 0,
            'description' => self::DEFAULT_DESCRIPTION,
            'prompt' => self::DEFAULT_PROMPT,
            'type' => 'img2txt',
            'default' => 0,
            'language' => 'en_US',
            'hidden' => 0,
            'system' => 1,
        ]);

        return true;
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    private function defaultPromptExists(): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);

        $count = $queryBuilder
            ->count('uid')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq('system', $queryBuilder->createNamedParameter(1, \Doctrine\DBAL\ParameterType::INTEGER))
            )
            ->executeQuery()
            ->fetchOne();

        return (int)$count > 0;
    }
}
