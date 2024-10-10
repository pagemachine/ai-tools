<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Controller\Backend;

use Pagemachine\AItools\Domain\Repository\ServerRepository;
use Pagemachine\AItools\Service\ServerService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class ServersController extends ActionController
{

    public function __construct(
        private readonly ServerRepository $serverRepository,
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly IconFactory $iconFactory,
        private readonly ServerService $serverService,
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
                ->setTitle($value['name'])
                ->setShowLabelText(true)
                ->setIcon($this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL));

            $group++;
            $buttonBar->addButton($newRecordButton, ButtonBar::BUTTON_POSITION_LEFT, $group);
        }
    }

    public function listAction(): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);

        $requestUri = $this->request->getAttribute('normalizedParams')->getRequestUri();

        $this->view->assignMultiple([
            'servers' => $this->serverRepository->listAllServers(),
            'returnUrl' => $requestUri,
        ]);


        $moduleTemplate->setContent($this->view->render());
        $this->setDocHeader($moduleTemplate, $requestUri);
        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
