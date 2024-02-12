<?php

declare(strict_types = 1);

namespace Pagemachine\AItools\Controller\Backend;

use Pagemachine\AItools\Service\ImageMetaDataService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Fluid\View\TemplatePaths;

class ImageRecognizeController extends ActionController
{
    private ?ImageMetaDataService $imageMetaDataService;

    protected ResourceFactory $resourceFactory;

    /**
     * @var string
     */
    protected $templateRootPath = 'EXT:ai_tools/Resources/Private/Templates/Backend/ImageRecognize/';


    public function __construct(ResourceFactory $resourceFactory) {
        $this->imageMetaDataService = GeneralUtility::makeInstance(ImageMetaDataService::class);
        $this->responseFactory = GeneralUtility::makeInstance(ResponseFactoryInterface::class);
        $this->resourceFactory = $resourceFactory;
    }

    /**
     * Return custom Standalone View
     * @internal
     * @param string $templateName
     * @return StandaloneView
     */
    protected function getView(string $templateName = 'Default'): StandaloneView
    {
        $templatePaths = new TemplatePaths($this->templateRootPath);
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->getRenderingContext()->setTemplatePaths($templatePaths);
        $view->setTemplate($templateName);
        $view->setFormat('html');
        $view->setTemplatePathAndFilename($this->templateRootPath . $templateName . '.html');
        $view->assign('settings', $this->settings);
        return $view;
    }

    /**
     * Gets the file object from the request target value (which is the file combined identifier)
     * @param ServerRequestInterface $request
     * @return FileInterface|null
     * @throws ResourceDoesNotExistException
     */
    private function getFileObjectFromRequestTarget(ServerRequestInterface $request): ?FileInterface
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();
        // Setting target, which must be a file reference to a file within the mounts.
        $target = $parsedBody['target'] ?? $queryParams['target'] ?? '';
        // create the file object
        $fileObject = null;
        if ($target) {
            $fileObject = $this->resourceFactory->retrieveFileOrFolderObject($target);
        }
        return $fileObject;
    }

    /**
     * Process the Image recognition request from Filelist (accessed by right click on file)
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \JsonException
     */
    public function ajaxMetaGenerateAction(ServerRequestInterface $request, $altText = null): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $fileObject = $this->getFileObjectFromRequestTarget($request);

        // Setting target, which must be a file reference to a file within the mounts.
        $action = $parsedBody['action'] ?? $queryParams['action'] ?? '';
        switch ($action) {
            case 'saveMetaData':
                $altText = $parsedBody['altText'] ?? $queryParams['altText'] ?? '';
                $saved = $this->imageMetaDataService->saveMetaData($parsedBody['target'], $altText);
                if ($saved) {
                    $this->addMessageToFlashMessageQueue('Metadata saved', FlashMessage::OK);
                } else {
                    $this->addMessageToFlashMessageQueue('Metadata could not be saved', FlashMessage::ERROR);
                }
                break;
            case 'generateMetaData':
                $altText = $this->imageMetaDataService->generateImageDescription(fileObject: $fileObject, language: 'deu_Latn');
                if (!empty($altText)) {
                    $this->addMessageToFlashMessageQueue('Metadata generated. Check the description, make any necessary change and Press "Save".', FlashMessage::OK);
                } else {
                    $this->addMessageToFlashMessageQueue('failed to generate metadata', FlashMessage::ERROR);
                }
                break;
        }

        // @todo fetch all site languages to generate altText for all languages
        // fetch languages
        /** @var NullSite $site */
        $site = $request->getAttribute('site');
        /** @var SiteLanguage[] $siteLanguages */
        $siteLanguages = $site->getLanguages();

        // create custom fluid template html view
        $view = $this->getView('AjaxMetaGenerate');

        $view->assign('action', $action);
        $view->assign('fileObject', $fileObject);

        // fetch metadata from file if no new metaDataText is given
        if (empty($altText) && $fileObject instanceof FileInterface) {
            $altText = $fileObject->getMetaData()->offsetGet('alternative');
        }
        $view->assign('altText', $altText);

        return $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withBody($this->streamFactory->createStream((string)$view->render()));
    }


    /**
     * @param string $message
     * @param int $severity
     *
     * @return void
     * @throws \TYPO3\CMS\Core\Exception
     */
    protected function addMessageToFlashMessageQueue(string $message, int $severity = FlashMessage::ERROR): void
    {
        if (Environment::isCli()) {
            return;
        }

        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            $message,
            'AI-Metadata Status',
            $severity,
            true
        );

        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier('ai-tools.template.flashMessages');
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }

}
