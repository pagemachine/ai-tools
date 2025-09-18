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
        preg_match_all('/%([a-zA-Z0-9_]+)(\|([a-zA-Z0-9_|]+))?%/', $text, $matchedPlaceholders);

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
                        if (isset($config['fileReference'])) {
                            $placeholderInstance->setFileReference($config['fileReference']);
                        }
                        $value = $placeholderInstance->getValue();
                    }

                    $modifiers = $modifier ? explode('|', $modifier) : [];

                    $value = $this->applyModifiers($value, $placeholderInstance, $modifiers);

                    $text = str_replace($placeholderText, $value, $text);
                }
            }
        }

        return $text;
    }

    public function applyModifiers(string $value, PlaceholderInterface $placeholder, array $modifiers = []): string
    {
        $value = trim($value, '"\'');

        $force_raw = false;
        $force_q = false;
        foreach ($modifiers as $mod) {
            switch ($mod) {
                case 'raw':
                    $force_raw = true;
                    break;
                case 'q':
                    $force_q = true;
                    break;
                case 'trim':
                    $value = trim($value);
                    break;
                case 'lower':
                    $value = strtolower($value);
                    break;
                case 'upper':
                    $value = strtoupper($value);
                    break;
                case 'ucfirst':
                    $value = ucfirst($value);
                    break;
            }
        }

        $addQuotes = $placeholder->shouldBeQuotedByDefault();
        if ($force_raw) {
            $addQuotes = false;
        }
        if ($force_q) {
            $addQuotes = true;
        }

        if ($addQuotes) {
            $value = '"' . $value . '"';
        }

        return $value;
    }
}
