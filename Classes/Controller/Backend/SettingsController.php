<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Controller\Backend;

use Pagemachine\AItools\Domain\Model\Prompt;
use Pagemachine\AItools\Domain\Repository\PromptRepository;
use Pagemachine\AItools\Service\SettingsService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException;

class SettingsController extends ActionController
{
    /**
     * List of settings that are saved in the registry
     * @var array
     */
    private array $settingOptions = [
        // openAI settings
        'openai_apikey',
        // custom API settings
        'custom_auth_token', 'custom_api_username', 'custom_api_password', 'custom_image_recognition_api_uri', 'custom_translation_api_uri',
        // deepl settings
        'deepl_auth_key', 'deepl_endpoint', 'deepl_formality',
        // used service selections
        'image_recognition_service', 'translation_service',
    ];

    public function __construct(
        private readonly SettingsService $settingsService,
        private readonly PromptRepository $promptRepository,
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly IconFactory $iconFactory,
    ) {
    }

    private function setDocHeader(ModuleTemplate $moduleTemplate): void
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $list = $buttonBar->makeInputButton()
            ->setForm('EditSettingsController')
            ->setIcon($this->iconFactory->getIcon('actions-document-save', Icon::SIZE_SMALL))
            ->setName('_savedok')
            ->setShowLabelText(true)
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.saveDoc'))
            ->setValue('1');
        $buttonBar->addButton($list, ButtonBar::BUTTON_POSITION_LEFT, 1);
    }

    /**
     * Show settings form
     */
    public function settingsAction(): ResponseInterface
    {
        $version = GeneralUtility::makeInstance(VersionNumberUtility::class)->getNumericTypo3Version();
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);

        foreach ($this->settingOptions as $option) {
            $this->view->assign($option, $this->settingsService->getSetting($option));
        }

        // get all prompts
        $prompts = $this->promptRepository->findAll();
        if (version_compare($version, '11.0', '>=') && version_compare($version, '12.0', '<')) {
            // for TYPO3 v11
            // @phpstan-ignore-next-line
            $defaultPrompt = $this->promptRepository->findOneByDefault(true);
        } else {
            /**
             * @var Prompt $defaultPrompt
             * @phpstan-ignore-next-line
             */
            $defaultPrompt = $this->promptRepository->findOneBy(['default' => true]);
        }

        $this->view->assign('prompts', $prompts);
        $this->view->assign('defaultPrompt', $defaultPrompt);

        $moduleTemplate->setContent($this->view->render());
        $this->setDocHeader($moduleTemplate);
        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    /**
     * Save settings
     *
     * @return ResponseInterface
     * @throws NoSuchArgumentException
     */
    public function saveAction(): ResponseInterface
    {
        foreach ($this->settingOptions as $option) {
            if ($this->request->hasArgument($option)) {
                $this->settingsService->setSetting($option, $this->request->getArgument($option));
            }
        }

        return GeneralUtility::makeInstance(ForwardResponse::class, 'settings');
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
