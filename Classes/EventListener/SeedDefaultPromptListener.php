<?php

declare(strict_types=1);

namespace Pagemachine\AItools\EventListener;

use Pagemachine\AItools\Domain\Repository\PromptRepository;
use Pagemachine\AItools\Service\NativeLanguageService;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Package\Event\PackagesMayHaveChangedEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Creates the built-in prompt on installations that have none.
 *
 * Without a prompt flagged as default, PromptRepository::getDefaultPrompt()
 * returns null: the generate button does not render, and the AJAX endpoints
 * send an empty prompt to the API, spending credits on a blank instruction.
 *
 * PackagesMayHaveChangedEvent is used because it is the only package event
 * dispatched by extension:setup on TYPO3 12.4, 13.4 and 14.x alike. It fires
 * more than once over an installation's life, which is harmless: seeding is
 * skipped as soon as any prompt exists.
 */
final class SeedDefaultPromptListener
{
    private const TABLE = 'tx_aitools_domain_model_prompt';
    private const FALLBACK_DESCRIPTION = 'Default prompt';
    private const FALLBACK_PROMPT = 'Describe the essential content of the picture briefly and concisely. Limit the text to a very short sentence. Avoid elements such as "The picture shows" and descriptive adjectives.';
    private const FALLBACK_LOCALE = 'en_US';

    public function __invoke(PackagesMayHaveChangedEvent $event): void
    {
        try {
            if (!$this->promptTableIsEmpty()) {
                return;
            }

            $promptData = $this->loadDefaultPromptForBaseLanguage();

            GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable(self::TABLE)
                ->insert(self::TABLE, [
                    'pid' => 0,
                    'description' => $promptData['description'],
                    'prompt' => $promptData['prompt'],
                    'type' => 'img2txt',
                    'default' => 1,
                    'language' => $promptData['locale'],
                    'hidden' => 0,
                    'system' => 1,
                ]);
        } catch (\Throwable) {
            // The event also fires before the schema exists on a fresh install.
            // Seeding then simply happens on a later dispatch.
        }
    }

    /**
     * Seeding only ever happens into an empty table, so an installation that
     * already has prompts keeps both its records and its chosen default.
     */
    private function promptTableIsEmpty(): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll();

        $count = $queryBuilder
            ->count('uid')
            ->from(self::TABLE)
            ->executeQuery()
            ->fetchOne();

        return (int)$count === 0;
    }

    /**
     * @return array{description: string, prompt: string, locale: string}
     */
    private function loadDefaultPromptForBaseLanguage(): array
    {
        $promptRepository = GeneralUtility::makeInstance(PromptRepository::class);
        $baseLang = strtolower($promptRepository->getBaseLanguageCode());

        try {
            $service = GeneralUtility::makeInstance(NativeLanguageService::class);
            $langs = $service->get();
        } catch (\Exception) {
            $langs = [];
        }

        // Match site base language
        foreach ($langs as $lang) {
            if (strtolower($lang['code']) === $baseLang) {
                return [
                    'description' => $lang['description'],
                    'prompt' => $lang['default_prompt'],
                    'locale' => $lang['locale'],
                ];
            }
        }

        // Fall back to English from API
        foreach ($langs as $lang) {
            if (strtolower($lang['code']) === 'en') {
                return [
                    'description' => $lang['description'],
                    'prompt' => $lang['default_prompt'],
                    'locale' => $lang['locale'],
                ];
            }
        }

        // Hard fallback if API is unreachable
        return [
            'description' => self::FALLBACK_DESCRIPTION,
            'prompt' => self::FALLBACK_PROMPT,
            'locale' => self::FALLBACK_LOCALE,
        ];
    }
}
