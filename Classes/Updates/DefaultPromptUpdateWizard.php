<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Updates;

use Doctrine\DBAL\ParameterType;
use Pagemachine\AItools\Domain\Repository\PromptRepository;
use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Upgrades\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Core\Upgrades\UpgradeWizardInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[UpgradeWizard('aiToolsDefaultPrompt')]
class DefaultPromptUpdateWizard implements UpgradeWizardInterface
{
    private const TABLE = 'tx_aitools_domain_model_prompt';
    private const DEFAULT_PROMPTS_DIR = 'EXT:ai_tools/Resources/Private/DefaultPrompts/';
    private const FALLBACK_DESCRIPTION = 'Default prompt';
    private const FALLBACK_PROMPT = 'Describe the essential content of the picture briefly and concisely. Limit the text to a very short sentence. Avoid elements such as "The picture shows" and descriptive adjectives.';
    private const FALLBACK_LOCALE = 'en_US';

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

        $promptData = $this->loadDefaultPromptForBaseLanguage();

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::TABLE);

        $connection->insert(self::TABLE, [
            'pid' => 0,
            'description' => $promptData['description'],
            'prompt' => $promptData['prompt'],
            'type' => 'img2txt',
            'default' => 0,
            'language' => $promptData['locale'],
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
                $queryBuilder->expr()->eq('system', $queryBuilder->createNamedParameter(1, ParameterType::INTEGER))
            )
            ->executeQuery()
            ->fetchOne();

        return (int)$count > 0;
    }

    /**
     * @return array{description: string, prompt: string, locale: string}
     */
    private function loadDefaultPromptForBaseLanguage(): array
    {
        $promptRepository = GeneralUtility::makeInstance(PromptRepository::class);
        $baseLang = strtolower($promptRepository->getBaseLanguageCode());

        $absDir = GeneralUtility::getFileAbsFileName(self::DEFAULT_PROMPTS_DIR);
        $candidate = $absDir . $baseLang . '.json';

        if (!file_exists($candidate)) {
            $candidate = $absDir . 'en.json';
        }

        if (!file_exists($candidate)) {
            return [
                'description' => self::FALLBACK_DESCRIPTION,
                'prompt' => self::FALLBACK_PROMPT,
                'locale' => self::FALLBACK_LOCALE,
            ];
        }

        $raw = file_get_contents($candidate);
        if ($raw === false) {
            return [
                'description' => self::FALLBACK_DESCRIPTION,
                'prompt' => self::FALLBACK_PROMPT,
                'locale' => self::FALLBACK_LOCALE,
            ];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || !isset($decoded['description'], $decoded['prompt'], $decoded['locale'])) {
            throw new \RuntimeException('Invalid default prompt JSON: ' . $candidate);
        }

        return [
            'description' => (string)$decoded['description'],
            'prompt' => (string)$decoded['prompt'],
            'locale' => (string)$decoded['locale'],
        ];
    }
}
