<?php

declare(strict_types=1);

namespace Pagemachine\AItools\ViewHelpers\Backend;

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

class CreditsViewHelper extends AbstractTagBasedViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('type', 'string', 'The type of action to calculate the price to', false, '');
        $this->registerArgument('file-identifier', 'string', 'The file to calculate the price for', false, '');
        $this->registerArgument('text-prompt', 'string', 'The text prompt to send to the API', false, '');
        $this->registerArgument('target-language', 'string', 'sys_language_uid', false, '');
    }

    public function render(): string
    {
        $hash = md5(uniqid('credits_', true));

        $this->tag->addAttribute(
            'id',
            'credits-button-' . $hash
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
            'data-target-language',
            $this->arguments['target-language']
        );

        $this->tag->addAttribute(
            'data-text-prompt',
            $this->arguments['text-prompt']
        );

        $this->tag->addAttribute(
            'style',
            'display: none;'
        );

        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);

        $typo3Version = new Typo3Version();
        if ($typo3Version->getMajorVersion() > 11) {
            $pageRenderer->loadJavaScriptModule(
                '@pagemachine/ai-tools/CreditsViewHelper.js',
            );
        } else {
            $pageRenderer->loadRequireJsModule(
                'TYPO3/CMS/AiTools/Amd/CreditsViewHelper'
            );
        }

        return $this->tag->render();
    }
}
