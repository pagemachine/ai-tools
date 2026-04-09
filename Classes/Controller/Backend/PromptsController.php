<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Controller\Backend;

use Pagemachine\AItools\Domain\Repository\PromptRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

class PromptsController extends ActionController
{

    public function __construct(
        private readonly PromptRepository $promptRepository,
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
                        'tx_aitools_domain_model_prompt' => ['new'],
                    ],
                    'returnUrl' => (string)$requestUri,
                ]
            ))
            ->setTitle('Add')
            ->setIcon($this->iconFactory->getIcon('actions-add', IconSize::SMALL));

        $buttonBar->addButton($newRecordButton, ButtonBar::BUTTON_POSITION_LEFT, 10);
    }

    public function listAction(): ResponseInterface
    {
        $this->promptRepository->ensureSystemPromptExists();

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);

        $requestUri = $this->request->getAttribute('normalizedParams')->getRequestUri();

        $template_variables = [
            'prompts' => $this->promptRepository->listAllPrompts(),
            'returnUrl' => $requestUri,
        ];

        $this->setDocHeader($moduleTemplate, $requestUri);

        $moduleTemplate->assignMultiple($template_variables);
        return $moduleTemplate->renderResponse('Backend/Prompts/List');
    }

    public function restoreDefaultsAction(): ResponseInterface
    {
        $systemPrompt = $this->promptRepository->findOneBy(['system' => true]);

        if ($systemPrompt) {
            $systemPrompt->setPrompt(PromptRepository::SYSTEM_PROMPT_TEXT);
            $systemPrompt->setDescription('Default Prompt');
            $systemPrompt->setType('img2txt');
            $systemPrompt->setLanguage('en_US');
            $this->promptRepository->update($systemPrompt);
            GeneralUtility::makeInstance(PersistenceManager::class)->persistAll();
        }

        return $this->redirect('list');
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
