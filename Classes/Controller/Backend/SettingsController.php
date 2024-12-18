<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Controller\Backend;

use Pagemachine\AItools\Domain\Repository\ServerRepository;
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
        // used service selections
        'image_recognition_service', 'translation_service',
    ];

    public function __construct(
        private readonly SettingsService $settingsService,
        private readonly ServerRepository $serverRepository,
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
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);

        $template_variables = [
            'imageOptions' => $this->serverRepository->getByFunctionality('image_recognition'),
            'translationOptions' => $this->serverRepository->getByFunctionality('translation'),
        ];

        foreach ($this->settingOptions as $option) {
            $template_variables[$option] = $this->settingsService->getSetting($option);
        }

        $this->setDocHeader($moduleTemplate);

        if (version_compare(GeneralUtility::makeInstance(VersionNumberUtility::class)->getNumericTypo3Version(), '13.0', '<')) {
            $this->view->assignMultiple($template_variables);
            $moduleTemplate->setContent($this->view->render()); // @phpstan-ignore-line
            return $this->htmlResponse($moduleTemplate->renderContent()); // @phpstan-ignore-line
        } else {
            $moduleTemplate->assignMultiple($template_variables); // @phpstan-ignore-line
            return $moduleTemplate->renderResponse('Backend/Settings/Settings'); // @phpstan-ignore-line
        }
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
