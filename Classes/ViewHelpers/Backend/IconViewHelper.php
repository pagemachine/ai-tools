<?php

declare(strict_types=1);

namespace Pagemachine\AItools\ViewHelpers\Backend;

use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Imaging\IconState;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * @deprecated Use <core:icon> ViewHelper instead.
 */
class IconViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('identifier', 'string', 'Identifier of the icon as registered in the Icon Registry.', true);
        $this->registerArgument('size', 'string', 'Desired size of the icon.', false, IconSize::SMALL->value);
        $this->registerArgument('overlay', 'string', 'Identifier of an overlay icon.', false, null);
        $this->registerArgument('state', 'string', 'Sets the state of the icon.', false, IconState::STATE_DEFAULT->value);
        $this->registerArgument('alternativeMarkupIdentifier', 'string', 'Alternative icon identifier.', false, null);
        $this->registerArgument('title', 'string', 'Title for the icon');
    }

    public function render(): string
    {
        $identifier = $this->arguments['identifier'];
        $size = IconSize::from($this->arguments['size']);
        $overlay = $this->arguments['overlay'];
        $state = IconState::from($this->arguments['state']);
        $alternativeMarkupIdentifier = $this->arguments['alternativeMarkupIdentifier'];
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $icon = $iconFactory->getIcon($identifier, $size, $overlay, $state);
        $iconHtml = $icon->render($alternativeMarkupIdentifier);
        if ($this->arguments['title'] ?? false) {
            $iconHtml = str_replace('<span', '<span title="' . htmlspecialchars((string) $this->arguments['title']) . '"', $iconHtml);
        }

        return $iconHtml;
    }
}
