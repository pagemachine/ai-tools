<?php

namespace Pagemachine\AItools\Service;

use Pagemachine\AItools\Domain\Model\Placeholder;
use Pagemachine\AItools\Domain\Model\PlaceholderResult;
use Pagemachine\AItools\Placeholder\PlaceholderInterface;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PlaceholderService
{
    public function getAllPlaceholders(): array
    {
        return $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['ai_tools']['placeholder'] ?? [];
    }

    public function resolvePlaceholders(string $text, ?array $config = null): PlaceholderResult
    {
        $allPlaceholders = $this->getAllPlaceholders();

        $matchedPlaceholders = [];
        preg_match_all('/%(([a-zA-Z0-9_]+)(\|([a-zA-Z0-9_|]+))?)%/', $text, $matchedPlaceholders);

        $placeholderValues = [];

        if (!empty($matchedPlaceholders[0])) {
            foreach ($matchedPlaceholders[0] as $index => $placeholderFull) {
                $placeholderText = $matchedPlaceholders[1][$index];
                $identifier = $matchedPlaceholders[2][$index];
                $modifier = $matchedPlaceholders[4][$index] ?? null;

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
                    if (!empty($value)) {
                        $value = $this->applyModifiers($value, $placeholderInstance, $modifiers);
                    }

                    $placeholderValues[$placeholderText] = new Placeholder(
                        $value,
                        $identifier,
                        $modifiers,
                        $placeholderText,
                        $placeholderInstance->getLanguage()
                    );
                }
            }
        }

        return new PlaceholderResult($text, $placeholderValues);
    }

    public function applyPlaceholders(string $text, ?array $config = null): string
    {
        $resolved = $this->resolvePlaceholders($text, $config);

        $text = $resolved->getText();
        foreach ($resolved->getPlaceholders() as $placeholderText => $placeholder) {
            $text = str_replace('%' . $placeholderText . '%', $placeholder->getValue(), $text);
        }

        return $text;
    }

    public function applyPlaceholdersWithoutTranslation(string $text, ?array $config = null): PlaceholderResult
    {
        $resolved = $this->resolvePlaceholders($text, $config);
        $translatablePlaceholders = [];

        $text = $resolved->getText();
        foreach ($resolved->getPlaceholders() as $placeholderText => $placeholder) {
            if (!$placeholder->getLanguage()) {
                $text = str_replace($placeholderText, $placeholder->getValue(), $text);
            } else {
                $translatablePlaceholders[$placeholderText] = $placeholder;
            }
        }

        return new PlaceholderResult($text, $translatablePlaceholders);
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
