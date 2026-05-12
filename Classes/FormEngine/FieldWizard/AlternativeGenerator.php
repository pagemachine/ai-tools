<?php

declare(strict_types=1);

namespace Pagemachine\AItools\FormEngine\FieldWizard;

use Pagemachine\AItools\Compatibility\Typo3VersionGate;
use Pagemachine\AItools\Domain\Repository\PromptRepository;
use Pagemachine\AItools\Service\SettingsService;
use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

class AlternativeGenerator extends AbstractNode
{

    protected PromptRepository $promptRepository;
    protected SettingsService $settingsService;

    public function __construct(?NodeFactory $nodeFactory = null, ?array $data = null)
    {
        $typo3Version = new Typo3Version();
        if ($typo3Version->getMajorVersion() < 13) {
            parent::__construct($nodeFactory, $data);
        }

        $this->promptRepository = GeneralUtility::makeInstance(PromptRepository::class);
        $this->settingsService = GeneralUtility::makeInstance(SettingsService::class);
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

        if ($file->getType() !== Typo3VersionGate::imageFileType()) {
            return false;
        }

        $storageRecord = $file->getStorage()->getStorageRecord();
        if ((int) ($storageRecord['tx_aitools_enabled'] ?? 1) !== 1) {
            return false;
        }

        return true;
    }

    public function render(): array
    {
        $result = $this->initializeResultArray();

        try {
            return $this->buildWizardResult($result);
        } catch (\Exception $e) {
            $result['html'] = '<div class="alert alert-danger">AI Tools: ' . htmlspecialchars($e->getMessage()) . '</div>';
            return $result;
        }
    }

    protected function buildWizardResult(array $result): array
    {
        $fileData = $this->data['databaseRow']['file'] ?? null;
        $rawFile = is_array($fileData) ? ($fileData[0] ?? null) : $fileData;
        $target = is_array($rawFile) ? ($rawFile['uid'] ?? null) : $rawFile;

        if (empty($target) || !$this->isActive($target)) {
            return $result;
        }

        if (!$this->settingsService->checkPermission('generate_metadata')) {
            return $result;
        }

        $prompt = $this->promptRepository->getDefaultPrompt();
        if ($prompt === null) {
            return $result;
        }

        $arguments = [
            'target' => $target,
            'target-language' => $this->data['databaseRow']['sys_language_uid'],
            'title' => $this->data['recordTitle'],
            'input-field-selector' => '[data-formengine-input-name="' . $this->data["parameterArray"]["itemFormElName"] . '"]',
            'prompt' => $prompt->getPrompt(),
            'prompt-language' => $prompt->getLanguage(),
        ];

        try {
            $arguments['translation-provider'] = $this->settingsService->getTranslationProviderForLanguage((int) $this->data['databaseRow']['sys_language_uid']);
        } catch (\Exception $e) {
            $arguments['translation-provider-error'] = $e->getMessage();
        }

        if (Typo3VersionGate::isV14OrHigher()) {
            // @phpstan-ignore-next-line ViewFactoryInterface only exists in v14+
            $viewFactoryData = new ViewFactoryData(
                templateRootPaths: [GeneralUtility::getFileAbsFileName('EXT:ai_tools/Resources/Private/Templates/')],
                layoutRootPaths: [GeneralUtility::getFileAbsFileName('EXT:ai_tools/Resources/Private/Layouts/')],
                partialRootPaths: [GeneralUtility::getFileAbsFileName('EXT:ai_tools/Resources/Private/Partials/FieldWizard/')],
            );
            // @phpstan-ignore-next-line
            $view = GeneralUtility::makeInstance(ViewFactoryInterface::class)->create($viewFactoryData);
            $view->assignMultiple($arguments);
            $result['html'] = $view->render('FieldWizard/AlternativeGenerator');
        } else {
            // @phpstan-ignore-next-line StandaloneView deprecated in v14, only used on v12/v13
            $templateView = GeneralUtility::makeInstance(StandaloneView::class);
            $templateView->setLayoutRootPaths([GeneralUtility::getFileAbsFileName('EXT:ai_tools/Resources/Private/Layouts/')]);
            $templateView->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:ai_tools/Resources/Private/Partials/FieldWizard/')]);
            $templateView->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:ai_tools/Resources/Private/Templates/FieldWizard/AlternativeGenerator.html'));
            $templateView->assignMultiple($arguments);
            $result['html'] = $templateView->render();
        }

        $result['stylesheetFiles'] = [
            'EXT:ai_tools/Resources/Public/Css/FieldWizard.css',
        ];

        $result['javaScriptModules'][] = JavaScriptModuleInstruction::create('@pagemachine/ai-tools/AlternativeGenerator.js');

        return $result;
    }
}
