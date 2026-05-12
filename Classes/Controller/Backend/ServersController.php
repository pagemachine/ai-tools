<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Controller\Backend;

use Pagemachine\AItools\Compatibility\Typo3VersionGate;
use Pagemachine\AItools\Domain\Repository\PromptRepository;
use Pagemachine\AItools\Domain\Repository\ServerRepository;
use Pagemachine\AItools\Service\ServerService;
use Pagemachine\AItools\Service\SettingsService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class ServersController extends ActionController
{

    public function __construct(
        private readonly ServerRepository $serverRepository,
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly IconFactory $iconFactory,
        private readonly ServerService $serverService,
        private readonly SettingsService $settingsService,
        private readonly PromptRepository $promptRepository,
    ) {
    }

    private function setDocHeader(ModuleTemplate $moduleTemplate, $requestUri): void
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();

        $group = 0;

        foreach ($this->serverService->getServers() as $key => $value) {
            $newRecordButton = $buttonBar->makeLinkButton()
                ->setHref((string)$uriBuilder->buildUriFromRoute(
                    'record_edit',
                    [
                        'edit' => [
                            'tx_aitools_domain_model_server' => ['new'],
                        ],
                        'defVals' => [
                            'tx_aitools_domain_model_server' => [
                                'type' => $key,
                            ],
                        ],
                        'returnUrl' => (string)$requestUri,
                    ]
                ))
                ->setTitle('Default server')
                ->setShowLabelText(true)
                ->setIcon($this->iconFactory->getIcon('actions-add', Typo3VersionGate::iconSizeSmall()));

            $group++;
            $buttonBar->addButton($newRecordButton, ButtonBar::BUTTON_POSITION_LEFT, $group);
        }
    }

    public function listAction(): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);

        $requestUri = $this->request->getAttribute('normalizedParams')->getRequestUri();

        $template_variables = [
            'servers' => $this->serverRepository->listAllServers(),
            'returnUrl' => $requestUri,
            'gdprCompliant' => $this->settingsService->getGdprCompliant(),
        ];

        try {
            $providers = $this->settingsService->getTranslationProviders();
            foreach ($providers as $languageId => &$entry) {
                $siteLanguage = $entry['siteLanguage'] ?? null;
                $langCode = '';
                if ($siteLanguage !== null) {
                    $langCode = $siteLanguage->getLocale()->getLanguageCode();
                }
                $entry['isAigudeVisionSupported'] = $this->promptRepository->isAigudeVisionSupportedLanguage($langCode);
                $entry['isBase'] = ($languageId === 0);
            }
            unset($entry);
            $template_variables['translationProviderPerLanguage'] = $providers;
        } catch (\Exception $e) {
            $template_variables['translationProviderError'] = $e->getMessage();
        }

        $this->setDocHeader($moduleTemplate, $requestUri);

        $moduleTemplate->assignMultiple($template_variables);
        return $moduleTemplate->renderResponse('Backend/Servers/List');
    }

    public function saveSettingsAction(bool $gdprCompliant, array $providers): ResponseInterface
    {
        $this->settingsService->setGdprCompliant($gdprCompliant);
        $this->settingsService->setTranslationProviders($providers);

        $this->addFlashMessage('Settings have been saved.', 'Settings saved', ContextualFeedbackSeverity::OK);

        return $this->redirect('list');
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
