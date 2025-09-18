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

        $html = '<div class="form-control-wrap" style="font-weight: bold;">';

        $html .= 'Available placeholders: <br>';
        foreach ($placeholders as $identifier => $placeholderClass) {
            $placeholder = GeneralUtility::makeInstance($placeholderClass);
            $html .= '<code>%' . htmlspecialchars($identifier) . '%</code>';
            $html .= ' → ';
            $html .= '<span style="color: #888; font-size: 90%;">' . $placeholderService->applyModifiers(htmlspecialchars($placeholder->getExampleValue()), $placeholder) . '</span>';
            $html .= '<br>';
        }
        $html .= '<br>';

        $html .= 'Available modifiers:<br>';
        $html .= '<span style="font-size: 90%; color: #666;">You can apply one or more modifiers to a placeholder by using the | character. Modifiers can be chained in any order.<br>';


        $modifier = [
            'q' => 'Force quoting',
            'raw' => 'No quoting',
            'trim' => 'Trim whitespace',
            'lower' => 'Convert to lowercase',
            'upper' => 'Convert to uppercase',
            'ucfirst' => 'Uppercase the first character',
        ];

        foreach ($modifier as $key => $value) {
            $html .= '<code>' . htmlspecialchars($key) . '</code> → <span style="color: #888; font-size: 90%;">' . htmlspecialchars($value) . '</span><br>';
        }

        $html .= 'Example: <code>%filename|raw|upper%</code></span><br><br>';

        $promptText = $this->data['databaseRow']['prompt'];
        $prompt = $placeholderService->applyPlaceholders($promptText);

        if ($prompt !== $promptText) {
            $html .= 'Prompt after applying placeholders:<br>';
            $html .= '<code style="white-space: pre;">' . htmlspecialchars($prompt) . '</code><br><br>';
        }

        $html .= '</div>';

        return [
            'html' => $html,
        ];
    }
}
