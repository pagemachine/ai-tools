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
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileType;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;

class ImageRecognizeController extends ActionController
{
    protected ?ImageMetaDataService $imageMetaDataService;
    protected ?TranslationService $translationService;
    protected SiteFinder $siteFinder;
    protected SettingsService $settingsService;
    protected PromptRepository $promptRepository;

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

    protected function getLanguageFlagHtml($identifier, $title = '', $size = IconSize::LARGE, $overlay = '')
    {
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $icon = $iconFactory->getIcon($identifier, $size, $overlay);

        if ($title ?? false) {
            $icon->setTitle($title);
        }
        return $icon->render();
    }

    /**
     * Gets the file object from the request target value (which is the file combined identifier)
     * @throws ResourceDoesNotExistException
     */
    private function getFileObjectFromRequestTarget(ServerRequestInterface $request): ?array
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();
        $target = $parsedBody['target'] ?? $queryParams['target'] ?? '';
        $target_language = $parsedBody['target-language'] ?? $queryParams['target-language'] ?? '';

        if ($target) {
            $fileObject = $this->resourceFactory->retrieveFileOrFolderObject($target);
            if ($fileObject instanceof FileInterface) {
                if ($fileObject->getType() !== FileType::IMAGE->value) {
                    return null;
                }

                return [$this->addMetaToFile($fileObject, [$target_language])];
            }
            if ($fileObject instanceof FolderInterface) {
                $files = $fileObject->getFiles();
                $files = array_filter($files, fn($file) => $file->getType() === FileType::IMAGE->value);
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

        $defaultPrompt = $this->promptRepository->getDefaultPrompt();

        $siteLanguages = $this->getAllSiteLanguages();

        $modal = $parsedBody['modal'] ?? $queryParams['modal'] ?? false;

        $target_language = $parsedBody['target-language'] ?? $queryParams['target-language'] ?? null;
        if (is_null($target_language)) {
            throw new Exception("No target language", 1727169730);
        }
        $targetLanguage = $this->getLanguageById((int) $target_language);
        $targetTwoLetterIsoCode = $this->getLocaleLanguageCode($targetLanguage);

        $action = $parsedBody['action'] ?? $queryParams['action'] ?? '';
        $target = $parsedBody['target'] ?? $queryParams['target'] ?? '';
        switch ($action) {
            case 'saveMetaData':
                $altText = $parsedBody['altText'] ?? $queryParams['altText'] ?? '';
                $doTranslate = $parsedBody['translate'] ?? $queryParams['translate'] ?? false;
                $parentUid = $this->imageMetaDataService->saveMetaData($target, $altText, (int) $target_language);

                $translations = [];
                if ($doTranslate) {
                    foreach ($siteLanguages as $siteLanguage) {
                        if ($siteLanguage->getLanguageId() !== (int) $target_language) {
                            $translationProvider = $this->settingsService->getTranslationProviderForLanguage($siteLanguage->getLanguageId());
                            if (is_null($translationProvider)) {
                                continue;
                            }

                            $altTextTranslated = $this->translationService->translateText($altText, $targetTwoLetterIsoCode, $this->getLocaleLanguageCode($siteLanguage), $translationProvider);
                            $metaDataUid = $this->imageMetaDataService->saveMetaData($target, $altTextTranslated, $siteLanguage->getLanguageId(), $parentUid);
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
                    'saved' => true,
                ];

                return $this->responseFactory->createResponse()
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($this->streamFactory->createStream(json_encode($returnArray)));
            case 'generateMetaData':
                $textPrompt = $parsedBody['textPrompt'] ?? $queryParams['textPrompt'] ?: ($defaultPrompt->getPrompt() != null ? $defaultPrompt->getPrompt() : '');
                $translationProvider = $parsedBody['translationProvider'] ?? $queryParams['translationProvider'] ?? null;
                if ($this->imageMetaDataService->supportsTranslation()) {
                    $altTextFromImageTranslated = $this->imageMetaDataService->generateImageDescription(
                        $fileObjects[0]['file'],
                        $textPrompt,
                        $targetTwoLetterIsoCode,
                        (int) $target_language,
                        $translationProvider
                    );
                    $data = ['alternative' => $altTextFromImageTranslated, 'baseAlternative' => $altTextFromImageTranslated];
                } else {
                    $altTextFromImage = $this->imageMetaDataService->generateImageDescription(
                        $fileObjects[0]['file'],
                        $textPrompt,
                        'en',
                        (int) $target_language,
                        $translationProvider,
                    );
                    $altText = $this->translationService->translateText($altTextFromImage, 'en', $targetTwoLetterIsoCode, $this->settingsService->getTranslationProviderForLanguage((int) $target_language));
                    $data = ['alternative' => $altText, 'baseAlternative' => $altTextFromImage];
                }

                return $this->responseFactory->createResponse()
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($this->streamFactory->createStream(json_encode($data)));

            default:
                $attribute = new ExtbaseRequestParameters(ImageRecognizeController::class);
                $request = $request->withAttribute('extbase', $attribute);
                $extbaseRequest = GeneralUtility::makeInstance(Request::class, $request);
                $moduleTemplate = $this->moduleTemplateFactory->create($extbaseRequest);

                $moduleTemplate->getDocHeaderComponent()->disable();

                $template_variables = [
                    'siteLanguages' => $siteLanguages,
                    'action' => $action,
                    'target' => $target,
                    'fileObjects' => $fileObjects ?? null,
                    'targetLanguage' => (int) $target_language,
                    'modal' => $modal,
                    'textPrompt' => $defaultPrompt->getPrompt(),
                    'textPromptValue' => json_encode(['prompt' => $defaultPrompt->getPrompt(), 'language' => $defaultPrompt->getLanguage()]),
                    'allTextPrompts' => array_map(fn($prompt) => [
                        'description' => ($prompt->isDefault() ? "\u{2605} " : '') . $prompt->getDescription(),
                        'prompt' => json_encode(['prompt' => $prompt->getPrompt(), 'language' => $prompt->getLanguage()]),
                    ], $allPrompts->toArray()),
                ];

                try {
                    $template_variables['translationProvider'] = $this->settingsService->getTranslationProviderForLanguage((int) $target_language);
                    $template_variables['translationProviders'] = $this->settingsService->getTranslationProviders();
                } catch (\Exception $e) {
                    $template_variables['translationProviderError'] = $e->getMessage();
                }

                $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
                $pageRenderer->loadJavaScriptModule(
                    '@pagemachine/ai-tools/AjaxMetaGenerate.js',
                );

                $moduleTemplate->assignMultiple($template_variables);
                return $moduleTemplate->renderResponse('Backend/ImageRecognize/AjaxMetaGenerate');
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
        return $siteLanguage->getLocale()->getLanguageCode();
    }
}
