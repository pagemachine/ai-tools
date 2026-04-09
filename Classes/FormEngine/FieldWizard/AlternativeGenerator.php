<?php

declare(strict_types=1);

namespace Pagemachine\AItools\FormEngine\FieldWizard;

use Pagemachine\AItools\Domain\Repository\PromptRepository;
use Pagemachine\AItools\Service\SettingsService;
use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileType;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;

class AlternativeGenerator extends AbstractNode
{

    protected PromptRepository $promptRepository;
    protected SettingsService $settingsService;
    protected ViewFactoryInterface $viewFactory;

    public function __construct()
    {
        $this->promptRepository = GeneralUtility::makeInstance(PromptRepository::class);
        $this->settingsService = GeneralUtility::makeInstance(SettingsService::class);
        $this->viewFactory = GeneralUtility::makeInstance(ViewFactoryInterface::class);
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

        if ($file->getType() !== FileType::IMAGE->value) {
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
        $target = $this->data['databaseRow']['file'][0];

        if (!$this->isActive($target)) {
            return $result;
        }

        if (!$this->settingsService->checkPermission('generate_metadata')) {
            return $result;
        }

        $prompt = $this->promptRepository->getDefaultPrompt();

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

        $viewFactoryData = new ViewFactoryData(
            templateRootPaths: [GeneralUtility::getFileAbsFileName('EXT:ai_tools/Resources/Private/Templates/')],
            layoutRootPaths: [GeneralUtility::getFileAbsFileName('EXT:ai_tools/Resources/Private/Layouts/')],
            partialRootPaths: [GeneralUtility::getFileAbsFileName('EXT:ai_tools/Resources/Private/Partials/FieldWizard/')],
        );
        $view = $this->viewFactory->create($viewFactoryData);
        $view->assignMultiple($arguments);

        $result['html'] = $view->render('FieldWizard/AlternativeGenerator');

        $result['stylesheetFiles'] = [
            'EXT:ai_tools/Resources/Public/Css/FieldWizard.css',
        ];

        $result['javaScriptModules'][] = JavaScriptModuleInstruction::create('@pagemachine/ai-tools/AlternativeGenerator.js');

        return $result;
    }
}
