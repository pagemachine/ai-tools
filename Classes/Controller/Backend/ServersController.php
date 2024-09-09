<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Controller\Backend;

use Pagemachine\AItools\Domain\Repository\ServerRepository;
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
                        'tx_aitools_domain_model_server' => ['new'],
                    ],
                    'returnUrl' => (string)$requestUri,
                ]
            ))
            ->setTitle('Add')
            ->setIcon($this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL));

        $buttonBar->addButton($newRecordButton, ButtonBar::BUTTON_POSITION_LEFT, 10);
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
