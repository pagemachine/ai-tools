<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Controller\Backend;

use Pagemachine\AItools\Compatibility\Typo3VersionGate;
use Pagemachine\AItools\Domain\Repository\PromptRepository;
use Pagemachine\AItools\Service\SettingsService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class PromptsController extends ActionController
{

    public function __construct(
        private readonly PromptRepository $promptRepository,
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly IconFactory $iconFactory,
        private readonly SettingsService $settingsService,
    ) {
    }

    private function setDocHeader(ModuleTemplate $moduleTemplate, $requestUri): void
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();

        $newRecordButton = $buttonBar->makeLinkButton()
            ->setHref((string)$uriBuilder->buildUriFromRoute(
                'record_edit',
                [
                    'edit' => [
                        'tx_aitools_domain_model_prompt' => ['new'],
                    ],
                    'returnUrl' => (string)$requestUri,
                ]
            ))
            ->setTitle('Add')
            ->setIcon($this->iconFactory->getIcon('actions-add', Typo3VersionGate::iconSizeSmall()));

        $buttonBar->addButton($newRecordButton, ButtonBar::BUTTON_POSITION_LEFT, 10);
    }

    public function listAction(): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);

        $requestUri = $this->request->getAttribute('normalizedParams')->getRequestUri();

        $baseLangCode = $this->promptRepository->getBaseLanguageCode();
        $isBaseLanguageSupported = $this->promptRepository->isAigudeVisionSupportedLanguage($baseLangCode);

        $baseLangTitle = strtoupper($baseLangCode);
        try {
            $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
            $sites = $siteFinder->getAllSites();
            if (!empty($sites)) {
                $baseLangTitle = (reset($sites))->getLanguageById(0)->getTitle();
            }
        } catch (\Throwable) {
        }

        $baseProvider = null;
        if (!$isBaseLanguageSupported) {
            try {
                $baseProvider = $this->settingsService->getTranslationProviderForLanguage(0);
            } catch (\Exception) {
            }
        }

        $template_variables = [
            'prompts' => $this->promptRepository->listAllPrompts(),
            'returnUrl' => $requestUri,
            'isBaseLanguageSupported' => $isBaseLanguageSupported,
            'baseLangTitle' => $baseLangTitle,
            'baseProvider' => $baseProvider,
        ];

        $this->setDocHeader($moduleTemplate, $requestUri);

        $moduleTemplate->assignMultiple($template_variables);
        return $moduleTemplate->renderResponse('Backend/Prompts/List');
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
