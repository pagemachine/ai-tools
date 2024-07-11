<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Controller\Backend;

use Pagemachine\AItools\Domain\Model\Prompt;
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
use TYPO3\CMS\Core\Resource\AbstractFile;
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

    protected function getFileMetaDatGenerateLink(string $target): UriInterface
    {
        $uriParameters = [
            'target' => $target,
        ];
        return $this->backendUriBuilder
            ->buildUriFromRoute('ajax_aitools_ai_tools_images', $uriParameters);
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
     * @return FileInterface[]|null
     * @throws ResourceDoesNotExistException
     */
    private function getFileObjectFromRequestTarget(ServerRequestInterface $request): ?array
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();
        // Setting target, which must be a file reference to a file within the mounts.
        $target = $parsedBody['target'] ?? $queryParams['target'] ?? '';
        // create the file object
        if ($target) {
            $fileObject = $this->resourceFactory->retrieveFileOrFolderObject($target);
            if ($fileObject instanceof FileInterface) {
                if ($fileObject->getType() !== AbstractFile::FILETYPE_IMAGE) {
                    return null;
                }
                return [$fileObject];
            }
            if ($fileObject instanceof FolderInterface) {
                $files = $fileObject->getFiles();
                $files = array_filter($files, fn($file) => $file->getType() === AbstractFile::FILETYPE_IMAGE);
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
        $version = GeneralUtility::makeInstance(VersionNumberUtility::class)->getNumericTypo3Version();
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $fileObjects = $this->getFileObjectFromRequestTarget($request);

        $allPrompts = $this->promptRepository->findAll();
        if (version_compare($version, '11.0', '>=') && version_compare($version, '12.0', '<')) {
            // for TYPO3 v11
            // @phpstan-ignore-next-line
            $defaultPrompt = $this->promptRepository->findOneByDefault(true);
        } else {
            /**
             * @var Prompt $defaultPrompt
             * @phpstan-ignore-next-line
             */
            $defaultPrompt = $this->promptRepository->findOneBy(['default' => true]);
        }

        $siteLanguages = $this->getAllSiteLanguages();

        // get default language
        $defaultLanguage = $this->getLanguageById(0);
        $defaultTwoLetterIsoCode = $this->getLocaleLanguageCode($defaultLanguage);

        // Setting target, which must be a file reference to a file within the mounts.
        $action = $parsedBody['action'] ?? $queryParams['action'] ?? '';
        $target = $parsedBody['target'] ?? $queryParams['target'] ?? '';
        switch ($action) {
            case 'saveMetaData':
                $altText = $parsedBody['altText'] ?? $queryParams['altText'] ?? '';
                $doTranslate = $parsedBody['translate'] ?? $queryParams['translate'] ?? false;
                $saved = $this->imageMetaDataService->saveMetaData($target, $altText);

                $translations = [];
                if ($doTranslate) {
                    // fetch all site languages and translate the altText
                    foreach ($siteLanguages as $siteLanguage) {
                        // only translate additional languages (skip default language)
                        if ($siteLanguage->getLanguageId() > 0) {
                            $altTextTranslated = $this->translationService->translateText($altText, $defaultTwoLetterIsoCode, $this->getLocaleLanguageCode($siteLanguage));
                            $metaDataUid = $this->imageMetaDataService->saveMetaData($target, $altTextTranslated, $siteLanguage->getLanguageId());
                            $translations[] = [
                                'languageId' => $siteLanguage->getLanguageId(),
                                'title' => $siteLanguage->getTitle(),
                                //'languageCode' => $this->getLocaleLanguageCode($siteLanguage),
                                'languageFlagIdentifier' => str_replace('flags-', '', $siteLanguage->getFlagIdentifier()),
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
                $textPrompt = $parsedBody['textPrompt'] ?? $queryParams['textPrompt'] ?: ($defaultPrompt != null ? $defaultPrompt->getPrompt() : '');
                $altTextFromImage = $this->imageMetaDataService->generateImageDescription(
                    fileObject: $fileObjects[0],
                    textPrompt: $textPrompt,
                );
                $altText = $this->translationService->translateText($altTextFromImage, 'en', $defaultTwoLetterIsoCode);
                $data = ['alternative' => $altText, 'baseAlternative' => $altTextFromImage];
                return $this->responseFactory->createResponse()
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($this->streamFactory->createStream(json_encode($data)));

            default:
                $moduleTemplate = $this->moduleTemplateFactory->create($request);
                // create custom fluid template html view
                $view = $this->getView('AjaxMetaGenerate', $request);

                $view->assign('siteLanguages', $siteLanguages);
                $view->assign('action', $action);
                $view->assign('target', $target);
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
