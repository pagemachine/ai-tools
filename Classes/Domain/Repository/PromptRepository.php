<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Domain\Repository;

use Pagemachine\AItools\Domain\Model\Prompt;
use Pagemachine\AItools\Service\NativeLanguageService;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
        /** @var Prompt|null $prompt */
        $prompt = $this->findOneBy(['default' => true]);
        return $prompt;
    }

    public function isAigudeVisionSupportedLanguage(string $langCode): bool
    {
        $service = GeneralUtility::makeInstance(NativeLanguageService::class);
        foreach ($service->get() as $lang) {
            if (strtolower($lang['code']) === strtolower($langCode)) {
                return true;
            }
        }
        return false;
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
