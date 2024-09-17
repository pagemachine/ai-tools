<?php

declare(strict_types=1);

namespace Pagemachine\AItools\FormEngine\FieldWizard;

use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AlternativeGenerator extends AbstractNode
{

    protected StandaloneView $templateView;

    public function __construct(NodeFactory $nodeFactory, array $data)
    {
        parent::__construct($nodeFactory, $data);
        $this->templateView = GeneralUtility::makeInstance(StandaloneView::class);
        $this->templateView->setLayoutRootPaths([GeneralUtility::getFileAbsFileName('EXT:ai_tools/Resources/Private/Layouts/')]);
        $this->templateView->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:ai_tools/Resources/Private/Partials/FieldWizard/')]);
        $this->templateView->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:ai_tools/Resources/Private/Templates/FieldWizard/AlternativeGenerator.html'));
    }

    public function render()
    {
        $result = $this->initializeResultArray();

        $arguments = [
            'target' => $this->data['databaseRow']['file'][0],
            'title' => $this->data['recordTitle'],
        ];

        $this->templateView->assignMultiple($arguments);

        $result['html'] = $this->templateView->render();

        $result['stylesheetFiles'] = [
            'EXT:ai_tools/Resources/Public/Css/FieldWizard.css',
        ];

        $result['requireJsModules'] = ['TYPO3/CMS/AiTools/AlternativeGenerator'];
        $resultArray['requireJsModules'][] = 'TYPO3/CMS/AiTools/AlternativeGenerator';

        return $result;
    }
}
