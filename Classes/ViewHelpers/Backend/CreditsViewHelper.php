<?php

declare(strict_types=1);

namespace Pagemachine\AItools\ViewHelpers\Backend;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

class CreditsViewHelper extends AbstractTagBasedViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('type', 'string', 'The type of action to calculate the price to', false, '');
        $this->registerArgument('file-identifier', 'string', 'The file to calculate the price for', false, '');
        $this->registerArgument('text-prompt', 'string', 'The text prompt to send to the API', false, '');
    }

    public function render(): string
    {
        $ajaxUri = $this->getAjaxUri();
        GeneralUtility::makeInstance(PageRenderer::class)->loadRequireJsModule(
            'TYPO3/CMS/AiTools/CreditsViewHelper',
            'function (CreditsViewHelper) { CreditsViewHelper("'. htmlspecialchars($ajaxUri, ENT_QUOTES, 'UTF-8').'"); }'
        );

        $this->tag->addAttribute(
            'class',
            'label label-default t3js-ai-tools-credits-view-helper'
        );

        $this->tag->addAttribute(
            'data-type',
            $this->arguments['type']
        );

        $this->tag->addAttribute(
            'data-file-identifier',
            $this->arguments['file-identifier']
        );

        $this->tag->addAttribute(
            'data-text-prompt',
            $this->arguments['text-prompt']
        );

        $this->tag->addAttribute(
            'style',
            'display: none;'
        );

        return $this->tag->render();
    }

    protected function getAjaxUri(): string
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uri = $uriBuilder->buildUriFromRoute('ajax_aitools_ai_tools_credits', [], UriBuilder::ABSOLUTE_PATH);
        return (string)$uri;
    }
}
