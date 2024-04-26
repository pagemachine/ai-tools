<?php

declare(strict_types = 1);

namespace Pagemachine\AItools\Controller\Backend;

use Pagemachine\AItools\Service\ImageMetaDataService;
use Pagemachine\AItools\Service\SettingsService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Fluid\View\TemplatePaths;

class ImageRecognizeController extends ActionController
{
    private ?ImageMetaDataService $imageMetaDataService;
    protected ResourceFactory $resourceFactory;
    protected SiteFinder $siteFinder;
    protected SettingsService $settingsService;

    /**
     * @var string
     */
    protected $templateRootPath = 'EXT:ai_tools/Resources/Private/Templates/Backend/ImageRecognize/';

    /**
     * @var string
     */
    protected $layoutRootPath = 'EXT:ai_tools/Resources/Private/Layouts/';


    public function __construct(ResourceFactory $resourceFactory) {
        $this->imageMetaDataService = GeneralUtility::makeInstance(ImageMetaDataService::class);
        $this->responseFactory = GeneralUtility::makeInstance(ResponseFactoryInterface::class);
        $this->settingsService = GeneralUtility::makeInstance(SettingsService::class);
        $this->siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
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
        $view->setLayoutRootPaths([$this->layoutRootPath]);
        $view->assign('settings', $this->settings);
        return $view;
    }

    /**
     * Gets the file object from the request target value (which is the file combined identifier)
     * @param ServerRequestInterface $request
     * @return FileInterface[]|null
     * @throws ResourceDoesNotExistException
     */
    private function getFileObjectFromRequestTarget(ServerRequestInterface $request)
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();
        // Setting target, which must be a file reference to a file within the mounts.
        $target = $parsedBody['target'] ?? $queryParams['target'] ?? '';
        // create the file object
        if ($target) {
            $fileObject = $this->resourceFactory->retrieveFileOrFolderObject($target);
            if ($fileObject instanceof FileInterface) {
                return [$fileObject];
            } elseif ($fileObject instanceof FolderInterface) {
                return $fileObject->getFiles();
            }
        }
        return null;
    }

    /**
     * return all SiteLanguages
     * @return SiteLanguage[]
     */
    private function getAllSiteLanguages() {
        $sites = $this->siteFinder->getAllSites();
        $languages = [];
        foreach ($sites as $site) {
            $languages = array_merge($languages, $site->getAllLanguages());
        }
        return $languages;
    }
    private function getLanguageById(int $languageId) {
        $sites = $this->siteFinder->getAllSites();
        foreach ($sites as $site) {
            try {
                return $site->getLanguageById($languageId);
            } catch (\Exception $e) {
                continue;
            }
        }
        return Null;
    }

    /**
     * Process the Image recognition request from Filelist (accessed by right click on file)
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \JsonException
     */
    public function ajaxMetaGenerateAction(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $fileObjects = $this->getFileObjectFromRequestTarget($request);

        $fileMetaData = $this->imageMetaDataService->getMetaData($fileObjects[0]);

        $imageRecognitionDefaultValue = LocalizationUtility::translate('LLL:EXT:ai_tools/Resources/Private/Language/BackendModules/locallang_be_settings.xlf:image_recognition_prompt_default');

        $siteLanguages = $this->getAllSiteLanguages();

        // Setting target, which must be a file reference to a file within the mounts.
        $action = $parsedBody['action'] ?? $queryParams['action'] ?? '';
        switch ($action) {
            case 'saveMetaData':
                $altText = $parsedBody['altText'] ?? $queryParams['altText'] ?? '';
                $saved = $this->imageMetaDataService->saveMetaData($parsedBody['target'], $altText);
                return $this->responseFactory->createResponse()
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($this->streamFactory->createStream(json_encode($saved)));
            case 'saveMetaDataAndTranslate':
                $altText = $parsedBody['altText'] ?? $queryParams['altText'] ?? '';
                $saved = $this->imageMetaDataService->saveMetaData($parsedBody['target'], $altText);
                $siteLanguages = $this->getAllSiteLanguages();
                foreach ($siteLanguages as $siteLanguage) {
                    $translationLanguage = match ($siteLanguage->getTwoLetterIsoCode()) {
                        'de' => 'deu_Latn',
                        'en' => 'eng_Latn',
                        default => 'eng_Latn',
                    };
                    $this->imageMetaDataService->saveMetaData($parsedBody['target'], $altText, $siteLanguage->getLanguageId());
                }

            case 'generateMetaData':
                $textPrompt = $parsedBody['textPrompt'] ?? $queryParams['textPrompt'] ?: ($this->settingsService->getSetting('image_recognition_prompt') ?: $imageRecognitionDefaultValue);
                $language = $this->getLanguageById((int)($parsedBody['language'] ?? $queryParams['language']));
                $twoLetterIsoCode = $language->getTwoLetterIsoCode();
                //$translationLanguage = match ($language->getTwoLetterIsoCode()) {
                //    'de' => 'deu_Latn',
                //    'en' => 'eng_Latn',
                //    default => 'eng_Latn',
                //};
                $altText = $this->imageMetaDataService->generateImageDescription(
                    fileObject: $fileObjects[0], language: $twoLetterIsoCode, textPrompt: $textPrompt
                );
                $data = ['alternative' => $altText];
                return $this->responseFactory->createResponse()
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($this->streamFactory->createStream(json_encode($data)));

            default:
                // create custom fluid template html view
                $view = $this->getView('AjaxMetaGenerate');

                $view->assign('siteLanguages', $siteLanguages ?? null);
                $view->assign('action', $action);
                $view->assign('fileObjects', $fileObjects ?? null);

                DebuggerUtility::var_dump($fileMetaData['alternative'] ?? null);

                $view->assign(
                    'textPrompt',
                    $this->settingsService->getSetting('image_recognition_prompt') ?: $imageRecognitionDefaultValue
                );

                return $this->responseFactory->createResponse()
                    ->withHeader('Content-Type', 'text/html; charset=utf-8')
                    ->withBody($this->streamFactory->createStream((string)$view->render()));
        }
    }
}
