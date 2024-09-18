<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Domain\Repository;

use Pagemachine\AItools\Domain\Model\Prompt;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
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

    public function getDefaultPromptText(): string
    {
        $version = GeneralUtility::makeInstance(VersionNumberUtility::class)->getNumericTypo3Version();
        if (version_compare($version, '11.0', '>=') && version_compare($version, '12.0', '<')) {
            // for TYPO3 v11
            // @phpstan-ignore-next-line
            $defaultPrompt = $this->findOneByDefault(true);
        } else {
            /**
             * @var Prompt $defaultPrompt
             * @phpstan-ignore-next-line
             */
            $defaultPrompt = $this->findOneBy(['default' => true]);
        }

        if ($defaultPrompt) {
            $prompt = $defaultPrompt->getPrompt();
        } else {
            $prompt = 'Describe the essential content of the picture briefly and concisely. Limit the text to a very short sentence. Avoid elements such as "The picture shows" and descriptive adjectives.';
        }

        return $prompt;
    }
}
