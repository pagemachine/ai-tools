<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Controller\Backend;

use Exception;
use Pagemachine\AItools\Domain\Repository\PromptRepository;
use Pagemachine\AItools\Service\ImageMetaDataService;
use Pagemachine\AItools\Service\SettingsService;
use Pagemachine\AItools\Service\TranslationService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\Icon\IconState;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Fluid\View\TemplatePaths;
use TYPO3\CMS\Core\Information\Typo3Version;

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
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly UriBuilder $backendUriBuilder,
    ) {
        $this->imageMetaDataService = GeneralUtility::makeInstance(ImageMetaDataService::class);
        $this->translationService = GeneralUtility::makeInstance(TranslationService::class);
        $this->responseFactory = GeneralUtility::makeInstance(ResponseFactoryInterface::class);
        $this->settingsService = GeneralUtility::makeInstance(SettingsService::class);
        $this->siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        $this->promptRepository = GeneralUtility::makeInstance(PromptRepository::class);
    }

    protected function getFileMetaDataEditLink(int $uid, string $returnUrl = null): UriInterface
    {
        $uriParameters = [
            'edit' =>
                [
                    'sys_file_metadata' => [$uid => 'edit'],
                ],
        ];
        if (!empty($returnUrl)) {
            $uriParameters['returnUrl'] = $returnUrl;
        }
        return $this->backendUriBuilder
            ->buildUriFromRoute('record_edit', $uriParameters);
    }

    protected function getLanguageFlagHtml($identifier, $title = '', $size = Icon::SIZE_LARGE, $overlay = '', $state = IconState::STATE_DEFAULT)
    {
        $version = GeneralUtility::makeInstance(VersionNumberUtility::class)->getNumericTypo3Version();
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $icon = $iconFactory->getIcon($identifier, $size, $overlay, IconState::cast($state));

        if (version_compare($version, '12.0', '>=')) {
            if ($title ?? false) {
                // @phpstan-ignore-next-line
                $icon->setTitle($title);
            }
        }
        return $icon->render();
    }

    /**
     * Return custom Standalone View
     * @internal
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
     * @throws ResourceDoesNotExistException
     */
    private function getFileObjectFromRequestTarget(ServerRequestInterface $request): ?array
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();
        // Setting target, which must be a file reference to a file within the mounts.
        $target = $parsedBody['target'] ?? $queryParams['target'] ?? '';
        $target_language = $parsedBody['target-language'] ?? $queryParams['target-language'] ?? '';

        // create the file object
        if ($target) {
            $fileObject = $this->resourceFactory->retrieveFileOrFolderObject($target);
            if ($fileObject instanceof FileInterface) {
                if ($fileObject->getType() !== AbstractFile::FILETYPE_IMAGE) {
                    return null;
                }

                return [$this->addMetaToFile($fileObject, [$target_language])];
            }
            if ($fileObject instanceof FolderInterface) {
                $files = $fileObject->getFiles();
                $files = array_filter($files, fn($file) => $file->getType() === AbstractFile::FILETYPE_IMAGE);
                $files = array_map(fn($file) => $this->addMetaToFile($file, [$target_language]), $files);

                return $files;
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
            foreach ($site->getAllLanguages() as $language) {
                $languages[$language->getLanguageId()] = $language;
            }
        }
        return array_values($languages);
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
     * @return ResponseInterface
     * @throws \JsonException
     */
    public function ajaxMetaGenerateAction(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->settingsService->checkPermission('generate_metadata')) {
            return $this->responseFactory->createResponse(500, 'Insufficient permissions');
        }

        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();
        $action = $parsedBody['action'] ?? $queryParams['action'] ?? null;

        if (!is_null($action)) {
            try {
                return $this->ajaxData($request);
            } catch (Exception $e) {
                return $this->responseFactory->createResponse()
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($this->streamFactory->createStream(json_encode(['error' => $e->getMessage()], JSON_THROW_ON_ERROR)));
            }
        }

        return $this->ajaxData($request);
    }

    protected function ajaxData(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $fileObjects = $this->getFileObjectFromRequestTarget($request);

        $allPrompts = $this->promptRepository->findAll();

        $defaultPrompt = $this->promptRepository->getDefaultPromptText();

        $siteLanguages = $this->getAllSiteLanguages();

        $modal = $parsedBody['modal'] ?? $queryParams['modal'] ?? false;

        $target_language = $parsedBody['target-language'] ?? $queryParams['target-language'] ?? null;
        if (is_null($target_language)) {
            throw new Exception("No target language", 1727169730);
        }
        $targetLanguage = $this->getLanguageById((int) $target_language);
        $targetTwoLetterIsoCode = $this->getLocaleLanguageCode($targetLanguage);

        // Setting target, which must be a file reference to a file within the mounts.
        $action = $parsedBody['action'] ?? $queryParams['action'] ?? '';
        $target = $parsedBody['target'] ?? $queryParams['target'] ?? '';
        switch ($action) {
            case 'saveMetaData':
                $altText = $parsedBody['altText'] ?? $queryParams['altText'] ?? '';
                $doTranslate = $parsedBody['translate'] ?? $queryParams['translate'] ?? false;
                $saved = $this->imageMetaDataService->saveMetaData($target, $altText, (int) $target_language);

                $translations = [];
                if ($doTranslate) {
                    // fetch all site languages and translate the altText
                    foreach ($siteLanguages as $siteLanguage) {
                        // only translate additional languages (skip current language)
                        if ($siteLanguage->getLanguageId() !== (int) $target_language) {
                            $altTextTranslated = $this->translationService->translateText($altText, $targetTwoLetterIsoCode, $this->getLocaleLanguageCode($siteLanguage));
                            $metaDataUid = $this->imageMetaDataService->saveMetaData($target, $altTextTranslated, $siteLanguage->getLanguageId());
                            $translations[] = [
                                'languageId' => $siteLanguage->getLanguageId(),
                                'title' => $siteLanguage->getTitle(),
                                'flagHtml' => $this->getLanguageFlagHtml($siteLanguage->getFlagIdentifier(), $siteLanguage->getTitle()),
                                'altTextTranslated' => $altTextTranslated,
                                'editLink' => (string)$this->getFileMetaDataEditLink($metaDataUid),
                            ];
                        }
                    }
                }

                $returnArray = [
                    'translations' => $translations,
                    'saved' => (bool)$saved,
                ];

                return $this->responseFactory->createResponse()
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($this->streamFactory->createStream(json_encode($returnArray)));
            case 'generateMetaData':
                $textPrompt = $parsedBody['textPrompt'] ?? $queryParams['textPrompt'] ?: ($defaultPrompt != null ? $defaultPrompt : '');
                $supportsTranslation = false; //d asd sad sadsa das dasd sad asd
                if ($this->imageMetaDataService->supportsTranslation()) {
                    $altTextFromImageTranslated = $this->imageMetaDataService->generateImageDescription(
                        $fileObjects[0]['file'],
                        $textPrompt,
                        $targetTwoLetterIsoCode
                    );
                    $data = ['alternative' => $altTextFromImageTranslated, 'baseAlternative' => $altTextFromImageTranslated];
                } else {
                    $altTextFromImage = $this->imageMetaDataService->generateImageDescription(
                        $fileObjects[0]['file'],
                        $textPrompt,
                        'en'
                    );
                    $altText = $this->translationService->translateText($altTextFromImage, 'en', $targetTwoLetterIsoCode);
                    $data = ['alternative' => $altText, 'baseAlternative' => $altTextFromImage];
                }

                return $this->responseFactory->createResponse()
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($this->streamFactory->createStream(json_encode($data)));

            default:
                if (version_compare(GeneralUtility::makeInstance(VersionNumberUtility::class)->getNumericTypo3Version(), '13.0', '<')) {
                    $moduleTemplate = $this->moduleTemplateFactory->create($request);
                    $view = $this->getView('AjaxMetaGenerate', $request);
                } else {
                    $attribute = new ExtbaseRequestParameters(ImageRecognizeController::class);
                    $request = $request->withAttribute('extbase', $attribute);
                    $extbaseRequest = GeneralUtility::makeInstance(Request::class, $request);
                    $moduleTemplate = $this->moduleTemplateFactory->create($extbaseRequest);
                }

                $moduleTemplate->getDocHeaderComponent()->disable();

                $template_variables = [
                    'siteLanguages' => $siteLanguages,
                    'action' => $action,
                    'target' => $target,
                    'fileObjects' => $fileObjects ?? null,
                    'targetLanguage' => (int) $target_language,
                    'modal' => $modal,
                    'textPrompt' => $defaultPrompt,
                    'allTextPrompts' => $allPrompts,
                ];

                $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
                $typo3Version = new Typo3Version();
                if ($typo3Version->getMajorVersion() > 11) {
                    $pageRenderer->loadJavaScriptModule(
                        '@pagemachine/ai-tools/AjaxMetaGenerate.js',
                    );
                } else {
                    $pageRenderer->loadRequireJsModule(
                        'TYPO3/CMS/AiTools/Amd/AjaxMetaGenerate'
                    );
                }


                if (version_compare(GeneralUtility::makeInstance(VersionNumberUtility::class)->getNumericTypo3Version(), '13.0', '<')) {
                    $view = $this->getView('AjaxMetaGenerate', $request);
                    $view->assignMultiple($template_variables);
                    $moduleTemplate->setContent($view->render());
                    return $this->htmlResponse($moduleTemplate->renderContent());
                } else {
                    $moduleTemplate->assignMultiple($template_variables);
                    return $moduleTemplate->renderResponse('ImageRecognize/AjaxMetaGenerate');
                }
        }
    }

    protected function addMetaToFile($fileObject, $languages): array
    {
        $meta = $this->imageMetaDataService->getMetaDataLanguages($fileObject, $languages);

        return [
            'file' => $fileObject,
            'meta' => $meta[0],
        ];
    }

    public function getLocaleLanguageCode(SiteLanguage $siteLanguage): string
    {
        $version = GeneralUtility::makeInstance(VersionNumberUtility::class)->getNumericTypo3Version();
        if (version_compare($version, '12.0', '>=')) {
            // @phpstan-ignore-next-line Stop PHPStan about complaining this line for TYPO3 v11
            return $siteLanguage->getLocale()->getLanguageCode();
        }
        return $siteLanguage->getTwoLetterIsoCode();
    }
}
