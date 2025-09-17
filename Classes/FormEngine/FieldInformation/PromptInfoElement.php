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
            $html .= '<span style="color: #888; font-size: 90%;">"' . htmlspecialchars($placeholder->getExampleValue()) . '"</span>';
            $html .= '<br>';
        }

        $promptText = $this->data['databaseRow']['prompt'];
        $prompt = $placeholderService->applyPlaceholders($promptText);

        if ($prompt !== $promptText) {
            $html .= '<br>Prompt after applying placeholders:<br>';
            $html .= '<code style="white-space: pre;">' . htmlspecialchars($prompt) . '</code>';
        }

        $html .= '</div>';

        return [
            'html' => $html,
        ];
    }
}
