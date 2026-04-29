<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Domain\Repository;

use Pagemachine\AItools\Domain\Model\Prompt;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class PromptRepository extends Repository
{
    public const AIGUDE_VISION_LANGS = [
        'en', 'de', 'es', 'fr', 'it', 'pt', 'nl',
        'ja', 'ko', 'ar', 'zh', 'ru', 'hi', 'tr', 'he',
    ];

    public function listAllPrompts(): QueryResultInterface
    {
        $query = $this->createQuery();

        $query->getQuerySettings()
            ->setIgnoreEnableFields(true);

        return $query->execute();
    }

    public function getDefaultPrompt(): ?Prompt
    {
        /** @var Prompt|null $prompt */
        $prompt = $this->findOneBy(['default' => true]);
        return $prompt;
    }

    public function isAigudeVisionSupportedLanguage(string $langCode): bool
    {
        return in_array(strtolower($langCode), self::AIGUDE_VISION_LANGS, true);
    }

    public function getBaseLanguageCode(): string
    {
        return $this->detectBaseLanguageCode();
    }

    private function detectBaseLanguageCode(): string
    {
        try {
            $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
            $sites = $siteFinder->getAllSites();
            if (empty($sites)) {
                return 'en';
            }
            $firstSite = reset($sites);
            $baseLang = $firstSite->getLanguageById(0);
            return $baseLang->getLocale()->getLanguageCode();
        } catch (\Throwable) {
            return 'en';
        }
    }
}
