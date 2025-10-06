<?php

declare(strict_types=1);

namespace Pagemachine\AItools\FormEngine\FieldInformation;

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;

class ApiKeyInfoElement extends AbstractFormElement
{

    private $urls = [
        'aigude' => 'https://aigude.io/',
    ];

    public function render(): array
    {
        $type = $this->data['recordTypeValue'];
        $href = $this->urls[$type] ?? null;

        if (!$href) {
            return [
                'html' => '',
            ];
        }

        $linkText = $this->getLanguageService()->sL('LLL:EXT:ai_tools/Resources/Private/Language/locallang_db.xlf:tx_aitools_domain_model_server.apikey.linktext');

        return [
            'html' => '<div class="form-control-wrap" style="font-weight: bold;">[<a href="' . htmlspecialchars((string) $href) . '" style="color: blue;" target="_blank">'. htmlspecialchars($linkText) . '</a>]</div>',
        ];
    }
}
