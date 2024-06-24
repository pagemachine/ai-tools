<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Controller\Backend;

use Pagemachine\AItools\Domain\Repository\PromptRepository;
use Pagemachine\AItools\Service\ImageMetaDataService;
use Pagemachine\AItools\Service\SettingsService;
use Pagemachine\AItools\Service\TranslationService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Fluid\View\TemplatePaths;

class ImageRecognizeController extends ActionController
{
    protected ?ImageMetaDataService $imageMetaDataService;
    protected ?TranslationService $translationService;
    protected SiteFinder $siteFinder;
    protected SettingsService $settingsService;
    protected PromptRepository $promptRepository;

    /**
     * @var string
     */
    protected $templateRootPath = 'EXT:ai_tools/Resources/Private/Templates/Backend/ImageRecognize/';

    /**
     * @var string
     */
    protected $layoutRootPath = 'EXT:ai_tools/Resources/Private/Layouts/';

    public function __construct(
        private readonly ResourceFactory $resourceFactory,
        private readonly ModuleTemplateFactory $moduleTemplateFactory
    ) {
        $this->imageMetaDataService = GeneralUtility::makeInstance(ImageMetaDataService::class);
        $this->translationService = GeneralUtility::makeInstance(TranslationService::class);
        $this->responseFactory = GeneralUtility::makeInstance(ResponseFactoryInterface::class);
        $this->settingsService = GeneralUtility::makeInstance(SettingsService::class);
        $this->siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        $this->promptRepository = GeneralUtility::makeInstance(PromptRepository::class);
    }

    /**
     * Return custom Standalone View
     * @internal
     * @param string $templateName
     * @return StandaloneView
     */
    protected function getView(string $templateName = 'Default', $request = null): StandaloneView
    {
        $templatePaths = new TemplatePaths($this->templateRootPath);
        $view = GeneralUtility::makeInstance(StandaloneView::class);

        $version = GeneralUtility::makeInstance(VersionNumberUtility::class)->getNumericTypo3Version();
        if ($request !== null && version_compare($version, '12.0', '>=')) {
            // needed in TYPO3 v12 see https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Breaking-98377-FluidStandaloneViewDoesNotCreateAnExtbaseRequestAnymore.html
            $attribute = new ExtbaseRequestParameters(ImageRecognizeController::class);
            $request = $request->withAttribute('extbase', $attribute);
            $extbaseRequest = GeneralUtility::makeInstance(Request::class, $request);
            $view->setRequest($extbaseRequest);
        }

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
            }
            if ($fileObject instanceof FolderInterface) {
                return $fileObject->getFiles();
            }
        }
        return null;
    }

    /**
     * return all SiteLanguages
     * @return SiteLanguage[]
     */
    private function getAllSiteLanguages(): array
    {
        $sites = $this->siteFinder->getAllSites();
        $languages = [];
        foreach ($sites as $site) {
            $languages = array_merge($languages, $site->getAllLanguages());
        }
        return $languages;
    }
    private function getLanguageById(int $languageId)
    {
        $sites = $this->siteFinder->getAllSites();
        foreach ($sites as $site) {
            try {
                return $site->getLanguageById($languageId);
            } catch (\Exception) {
                continue;
            }
        }
        return null;
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

        $allPrompts = $this->promptRepository->findAll();
        $defaultPrompt = $this->promptRepository->findOneByDefault(true);

        $siteLanguages = $this->getAllSiteLanguages();

        // get default language
        $defaultLanguage = $this->getLanguageById(0);
        $defaultTwoLetterIsoCode = $this->getLocale($defaultLanguage)->getLanguageCode();

        // Setting target, which must be a file reference to a file within the mounts.
        $action = $parsedBody['action'] ?? $queryParams['action'] ?? '';
        switch ($action) {
            case 'saveMetaData':
                $altText = $parsedBody['altText'] ?? $queryParams['altText'] ?? '';
                $doTranslate = $parsedBody['translate'] ?? $queryParams['translate'] ?? false;
                $saved = $this->imageMetaDataService->saveMetaData($parsedBody['target'], $altText);

                if ($doTranslate) {
                    // fetch all site languages and translate the altText
                    foreach ($siteLanguages as $siteLanguage) {
                        $altTextTranslated = $this->translationService->translateText($altText, $defaultTwoLetterIsoCode, $this->getLocale($siteLanguage)->getLanguageCode());
                        $this->imageMetaDataService->saveMetaData($parsedBody['target'], $altTextTranslated, $siteLanguage->getLanguageId());
                    }
                }

                return $this->responseFactory->createResponse()
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($this->streamFactory->createStream(json_encode($saved)));
            case 'generateMetaData':
                $textPrompt = $parsedBody['textPrompt'] ?? $queryParams['textPrompt'] ?: ($defaultPrompt ? $defaultPrompt->getPrompt() : '');
                $altText = $this->imageMetaDataService->generateImageDescription(
                    fileObject: $fileObjects[0],
                    textPrompt: $textPrompt,
                );
                $altText = $this->translationService->translateText($altText, 'en', $defaultTwoLetterIsoCode);
                $data = ['alternative' => $altText];
                return $this->responseFactory->createResponse()
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($this->streamFactory->createStream(json_encode($data)));

            default:
                $moduleTemplate = $this->moduleTemplateFactory->create($request);
                // create custom fluid template html view
                $view = $this->getView('AjaxMetaGenerate', $request);

                $view->assign('siteLanguages', $siteLanguages);
                $view->assign('action', $action);
                $view->assign('fileObjects', $fileObjects ?? null);

                $view->assign(
                    'textPrompt',
                    $defaultPrompt
                );
                $view->assign(
                    'allTextPrompts',
                    $allPrompts
                );

                $moduleTemplate->setContent($view->render());

                return $this->responseFactory->createResponse()
                    ->withHeader('Content-Type', 'text/html; charset=utf-8')
                    ->withBody($this->streamFactory->createStream($moduleTemplate->renderContent()));
        }
    }

    public function getLocale(SiteLanguage $siteLanguage): Locale
    {
        $version = GeneralUtility::makeInstance(VersionNumberUtility::class)->getNumericTypo3Version();
        if (version_compare($version, '12.0', '>=')) {
            return $siteLanguage()->getLocale();
        }
        return new Locale($siteLanguage()->getLocale());
    }
}
