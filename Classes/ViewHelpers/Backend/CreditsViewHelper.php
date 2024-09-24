<?php

declare(strict_types=1);

namespace Pagemachine\AItools\ViewHelpers\Backend;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

class CreditsViewHelper extends AbstractTagBasedViewHelper
{

    public function initializeArguments(): void
    {
        parent::initializeArguments();
    }

    public function render(): string
    {
        $this->tag->addAttribute(
            'class',
            'label label-default'
        );

        $this->tag->setContent('? Credits');

        return $this->tag->render();
    }
}
