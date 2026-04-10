<?php

declare(strict_types=1);

namespace Pagemachine\AItools\FormEngine\FieldInformation;

use Pagemachine\AItools\Service\PlaceholderService;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PromptInfoElement extends AbstractFormElement
{
    public function render(): array
    {
        $placeholderService = GeneralUtility::makeInstance(PlaceholderService::class);
        $placeholders = $placeholderService->getAllPlaceholders();

        $html = '<div class="form-control-wrap">';

        $html .= '<details style="margin-bottom: 0.5em;">';
        $html .= '<summary style="font-weight: bold; cursor: pointer;">' . $this->getTranslation('placeholder.header') . '</summary>';
        $html .= '<div style="padding: 0.5em 0 0 1em;">';
        foreach ($placeholders as $identifier => $placeholderClass) {
            $placeholder = GeneralUtility::makeInstance($placeholderClass);
            $html .= '<code>%' . htmlspecialchars($identifier) . '%</code>';
            $html .= ' &rarr; ';
            $html .= '<span style="color: #888; font-size: 90%;">' . $placeholderService->applyModifiers(htmlspecialchars((string) $placeholder->getExampleValue()), $placeholder) . '</span>';
            $html .= '<br>';
        }
        $html .= '</div></details>';

        $html .= '<details style="margin-bottom: 0.5em;">';
        $html .= '<summary style="font-weight: bold; cursor: pointer;">' . $this->getTranslation('modifier.header') . '</summary>';
        $html .= '<div style="padding: 0.5em 0 0 1em;">';
        $html .= '<span style="font-size: 90%; color: #666;">' . $this->getTranslation('modifier.info') . '<br>';

        $modifier = [
            'q' => 'Force quoting',
            'raw' => 'No quoting',
            'trim' => 'Trim whitespace',
            'lower' => 'Convert to lowercase',
            'upper' => 'Convert to uppercase',
            'ucfirst' => 'Uppercase the first character',
            'translatable' => 'Force translation',
            'untranslatable' => 'Never translate',
        ];

        foreach ($modifier as $key => $value) {
            $html .= '<code>' . htmlspecialchars($key) . '</code> &rarr; <span style="color: #888; font-size: 90%;">' . htmlspecialchars($value) . '</span><br>';
        }

        $html .= 'Example: <code>%filename|raw|upper%</code></span>';
        $html .= '</div></details>';

        $promptText = $this->data['databaseRow']['prompt'];
        $prompt = $placeholderService->applyPlaceholders($promptText);

        if ($prompt !== $promptText) {
            $html .= '<div style="margin-top: 0.5em;">';
            $html .= '<strong>' . $this->getTranslation('applied.header') . '</strong><br>';
            $html .= '<code style="white-space: pre-wrap;">' . htmlspecialchars($prompt) . '</code>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return [
            'html' => $html,
        ];
    }

    protected function getTranslation($key): string
    {
        return $this->getLanguageService()->sL('LLL:EXT:ai_tools/Resources/Private/Language/locallang_db.xlf:tx_aitools_domain_model_prompt.prompt.' . $key) ?: $key;
    }
}
