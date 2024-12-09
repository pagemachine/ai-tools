<?php

declare(strict_types=1);

namespace Pagemachine\AItools\FormEngine\FieldWizard;

use Pagemachine\AItools\Domain\Repository\PromptRepository;
use Pagemachine\AItools\Service\SettingsService;
use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

class AlternativeGenerator extends AbstractNode
{

    protected StandaloneView $templateView;

    protected PromptRepository $promptRepository;
    protected SettingsService $settingsService;

    public function __construct(NodeFactory $nodeFactory = null, array $data = null)
    {
        if ($nodeFactory !== null) {
            parent::__construct($nodeFactory, $data);
        }

        $this->promptRepository = GeneralUtility::makeInstance(PromptRepository::class);
        $this->settingsService = GeneralUtility::makeInstance(SettingsService::class);

        $this->templateView = GeneralUtility::makeInstance(StandaloneView::class);
        $this->templateView->setLayoutRootPaths([GeneralUtility::getFileAbsFileName('EXT:ai_tools/Resources/Private/Layouts/')]);
        $this->templateView->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:ai_tools/Resources/Private/Partials/FieldWizard/')]);
        $this->templateView->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:ai_tools/Resources/Private/Templates/FieldWizard/AlternativeGenerator.html'));
    }

    protected function isActive($identifier): bool
    {
        try {
            $file = GeneralUtility::makeInstance(ResourceFactory::class)->retrieveFileOrFolderObject($identifier);
        } catch (ResourceDoesNotExistException) {
            return false;
        }

        if (! $file instanceof File) {
            return false;
        }

        if ($file->getType() !== AbstractFile::FILETYPE_IMAGE) {
            return false;
        }

        return true;
    }

    public function render(): array
    {
        $result = $this->initializeResultArray();
        $target = $this->data['databaseRow']['file'][0];

        if (!$this->isActive($target)) {
            return $result;
        }

        if (!$this->settingsService->checkPermission('generate_metadata')) {
            return $result;
        }

        $prompt = $this->promptRepository->getDefaultPromptText();

        $arguments = [
            'target' => $target,
            'target-language' => $this->data['databaseRow']['sys_language_uid'],
            'title' => $this->data['recordTitle'],
            'input-field-selector' => '[data-formengine-input-name="' . $this->data["parameterArray"]["itemFormElName"] . '"]',
            'prompt' => $prompt,
        ];

        $this->templateView->assignMultiple($arguments);

        $result['html'] = $this->templateView->render();

        $result['stylesheetFiles'] = [
            'EXT:ai_tools/Resources/Public/Css/FieldWizard.css',
        ];

        $typo3Version = new \TYPO3\CMS\Core\Information\Typo3Version();
        if ($typo3Version->getMajorVersion() > 11) {
            $result['javaScriptModules'][] = JavaScriptModuleInstruction::create('@pagemachine/ai-tools/AlternativeGenerator.js');
        } else {
            $result['requireJsModules'][] = JavaScriptModuleInstruction::forRequireJS(
                'TYPO3/CMS/AiTools/Amd/AlternativeGenerator'
            );
        }

        return $result;
    }
}
