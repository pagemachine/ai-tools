<?php

namespace Pagemachine\AItools\Service;

use Pagemachine\AItools\Placeholder\PlaceholderInterface;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PlaceholderService
{
    public function getAllPlaceholders(): array
    {
        return $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['ai_tools']['placeholder'] ?? [];
    }

    public function applyPlaceholders(string $text, ?array $config = null): string
    {
        $allPlaceholders = $this->getAllPlaceholders();

        $matchedPlaceholders = [];
        preg_match_all('/%([a-zA-Z0-9_]+)(\|([a-zA-Z0-9_]+))?%/', $text, $matchedPlaceholders);

        if (!empty($matchedPlaceholders[0])) {
            foreach ($matchedPlaceholders[0] as $index => $placeholderText) {
                $identifier = $matchedPlaceholders[1][$index];
                $modifier = $matchedPlaceholders[3][$index] ?? null;

                if (isset($allPlaceholders[$identifier])) {
                    $placeholderClass = $allPlaceholders[$identifier];
                    /** @var PlaceholderInterface $placeholderInstance */
                    $placeholderInstance = GeneralUtility::makeInstance($placeholderClass);

                    if (!$config) {
                        $value = $placeholderInstance->getExampleValue();
                    } else {
                        if (isset($config['file']) && $config['file'] instanceof FileInterface) {
                            $placeholderInstance->setFile($config['file']);
                        }
                        $value = $placeholderInstance->getValue();
                    }

                    $value = trim($value, '"\'');

                    if ($modifier !== 'raw') {
                        $value = '"' . $value . '"';
                    }

                    $text = str_replace($placeholderText, $value, $text);
                }
            }
        }

        return $text;
    }
}
