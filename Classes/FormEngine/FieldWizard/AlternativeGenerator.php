<?php

declare(strict_types=1);

namespace Pagemachine\AItools\FormEngine\FieldWizard;

use Pagemachine\AItools\Domain\Repository\PromptRepository;
use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

class AlternativeGenerator extends AbstractNode
{

    protected StandaloneView $templateView;

    protected PromptRepository $promptRepository;

    public function __construct(NodeFactory $nodeFactory, array $data)
    {
        parent::__construct($nodeFactory, $data);

        $this->promptRepository = GeneralUtility::makeInstance(PromptRepository::class);

        $this->templateView = GeneralUtility::makeInstance(StandaloneView::class);
        $this->templateView->setLayoutRootPaths([GeneralUtility::getFileAbsFileName('EXT:ai_tools/Resources/Private/Layouts/')]);
        $this->templateView->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:ai_tools/Resources/Private/Partials/FieldWizard/')]);
        $this->templateView->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:ai_tools/Resources/Private/Templates/FieldWizard/AlternativeGenerator.html'));
    }

    public function render()
    {
        $result = $this->initializeResultArray();

        $prompt = $this->promptRepository->getDefaultPromptText();

        $arguments = [
            'target' => $this->data['databaseRow']['file'][0],
            'target-language' => $this-> data['databaseRow']['sys_language_uid'],
            'title' => $this->data['recordTitle'],
            'input-field-selector' => '[data-formengine-input-name="' . $this->data["parameterArray"]["itemFormElName"] . '"]',
            'prompt' => $prompt,
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
