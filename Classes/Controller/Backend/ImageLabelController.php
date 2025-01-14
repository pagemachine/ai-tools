<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Controller\Backend;

use Pagemachine\AItools\Domain\Repository\BadWordsRepository;
use Pagemachine\AItools\Domain\Repository\ImageLabelRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Controller for managing ImageLabels and BadWords in the TYPO3 backend.
 */
class ImageLabelController extends ActionController
{
    // Repository for handling BadWords
    protected BadWordsRepository $badwordsRepository;

    /**
     * Constructor to initialize required dependencies.
     */
    public function __construct(
        private readonly ImageLabelRepository $imagelabelRepository, // Repository for ImageLabels
        private readonly ModuleTemplateFactory $moduleTemplateFactory, // Factory for backend templates
        private readonly IconFactory $iconFactory // Factory for generating icons
    ) {
        // Initialize BadWordsRepository using GeneralUtility
        $this->badwordsRepository = GeneralUtility::makeInstance(BadWordsRepository::class);
    }

    /**
     * Sets the module header with a new button.
     *
     * @param ModuleTemplate $moduleTemplate The module template
     * @param string $requestUri The current request URI
     */
    private function setDocHeader(ModuleTemplate $moduleTemplate, $requestUri): void
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

        // Create a new button in the button bar
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();

        $newRecordButton = $buttonBar->makeLinkButton()
            ->setHref((string)$uriBuilder->buildUriFromRoute(
                'record_edit',
                [
                    'edit' => [
                        'tx_aitools_domain_model_imagelabel' => ['new'],
                    ],
                    'returnUrl' => (string)$requestUri,
                ]
            ))
            ->setTitle('Add') // Set the button title
            ->setIcon($this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL));

        // Add the button to the button bar
        $buttonBar->addButton($newRecordButton, ButtonBar::BUTTON_POSITION_LEFT, 10);
    }

    /**
     * Renders a list of ImageLabels and BadWords in the backend module.
     */
    public function listlabelAction(): ResponseInterface
    {
        // Create a new module template instance
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);

        // Get the current request URI
        $requestUri = $this->request->getAttribute('normalizedParams')->getRequestUri();

        // Pass data to the view
        $this->view->assignMultiple([
            'labels' => $this->imagelabelRepository->listAllLabels(), // List all ImageLabels
            'badwords' => $this->badwordsRepository->listAllBadWords(), // List all BadWords
            'returnUrl' => $requestUri,
        ]);

        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $typo3Version = new Typo3Version();
        if ($typo3Version->getMajorVersion() > 11) {
            $pageRenderer->loadJavaScriptModule( // @phpstan-ignore-line
                '@pagemachine/ai-tools/Listlabel.js',
            );
        } else {
            $pageRenderer->loadRequireJsModule( // @phpstan-ignore-line
                'TYPO3/CMS/AiTools/Amd/Listlabel'
            );
        }

        // Set the content of the module template
        $moduleTemplate->setContent($this->view->render()); // @phpstan-ignore-line

        // Set the header with a custom button
        $this->setDocHeader($moduleTemplate, $requestUri);

        // Return the rendered module content as an HTML response
        return $this->htmlResponse($moduleTemplate->renderContent()); // @phpstan-ignore-line
    }

    /**
     * Handles AJAX requests for managing BadWords or ImageLabels.
     *
     * @param ServerRequestInterface $request The incoming request
     * @return ResponseInterface The JSON response
     */
    public function ajaxhandleBadwordAction(ServerRequestInterface $request): ResponseInterface
    {
        // Parse incoming POST parameters
        $requestbody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $badwords = $requestbody['badword'] ?? $queryParams['badword'] ?? null;
        $imagelabelid = $requestbody['imagelabelid'] ?? $queryParams['badword'] ?? null;
        $badwordid = $requestbody['badwordid'] ?? $queryParams['badword'] ?? null;
        $action = $requestbody['action'] ?? $queryParams['badword'] ?? null;
        $funktion = $requestbody['funktion'] ?? $queryParams['badword'] ?? null;
        $isdefault = $requestbody['default'] ?? $queryParams['badword'] ?? null;

        // Validate required parameters based on the function type
        if (!$funktion ||
            ($funktion == "badword" && (!$badwords || !$imagelabelid || !$badwordid || !$action)) ||
            ($funktion == "label" && (!$imagelabelid || !$action))) {
            // Return a 400 Bad Request response if parameters are missing
            return $this->responseFactory->createResponse()
                ->withHeader('Content-Type', 'application/json; charset=utf-8')
                ->withBody($this->streamFactory->createStream(json_encode([
                    'error' => 'Missing parameters',
                    'badwords' => $badwords,
                    'badwordid' => $badwordid,
                    'imagelabelid' => $imagelabelid,
                    'action' => $action,
                ])))
                ->withStatus(400);
        }

        // Handle BadWord-related actions
        if ($funktion == "badword" || $funktion == "metabadword") {
            $response = $this->badwordsRepository->handleBadword((int)$imagelabelid, $badwords, (int)$badwordid, $action);
            if ($response == 0) {
                // Return a 409 Conflict response if there is a duplicate
                return $this->responseFactory->createResponse()
                    ->withHeader('Content-Type', 'application/json; charset=utf-8')
                    ->withBody($this->streamFactory->createStream(json_encode([
                        'error' => 'Duplicate',
                        'badwords' => $badwords,
                        'badwordid' => $badwordid,
                        'imagelabelid' => $imagelabelid,
                        'action' => $action,
                    ])))
                    ->withStatus(409);
            }
        } elseif ($funktion == "label") {
            // Handle ImageLabel-related actions
            $this->imagelabelRepository->handleLabel((int)$imagelabelid, $action);
        }

        // Return a success response
        return $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withBody($this->streamFactory->createStream(json_encode(
                $funktion == "label" ?
                [
                    'success' => true,
                    'imagelabelid' => $imagelabelid,
                    'action' => $action,
                ] : [
                    'success' => true,
                    'badwords' => $badwords,
                    'imagelabelid' => $imagelabelid,
                    'badwordid' => $badwordid,
                    'action' => $action,
                ]
            )));
    }

    /**
     * Retrieves the language service for translations.
     *
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
