<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Controller\Backend;

use Pagemachine\AItools\Domain\Model\Prompt;
use Pagemachine\AItools\Domain\Repository\PromptRepository;
use Pagemachine\AItools\Service\SettingsService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

class SettingsController extends ActionController
{
    /**
     * List of settings that are saved in the registry
     * @var array
     */
    private array $settingOptions = [
        // openAI settings
        'openai_apikey',
        // custom API settings
        'custom_auth_token', 'custom_api_username', 'custom_api_password', 'custom_image_recognition_api_uri', 'custom_translation_api_uri',
        // deepl settings
        'deepl_auth_key', 'deepl_endpoint', 'deepl_formality',
        // used service selections
        'image_recognition_service', 'translation_service',
    ];

    public function __construct(
        private readonly SettingsService $settingsService,
        private readonly PromptRepository $promptRepository,
        private readonly PersistenceManagerInterface $persistenceManager,
        private readonly ModuleTemplateFactory $moduleTemplateFactory
    ) {
    }

    /**
     * Show settings form
     */
    public function settingsAction(): ResponseInterface
    {
        $version = GeneralUtility::makeInstance(VersionNumberUtility::class)->getNumericTypo3Version();
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);

        foreach ($this->settingOptions as $option) {
            $this->view->assign($option, $this->settingsService->getSetting($option));
        }

        // get all prompts
        $prompts = $this->promptRepository->findAll();
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

        $this->view->assign('prompts', $prompts);
        $this->view->assign('defaultPrompt', $defaultPrompt);

        $this->view->assign('permissions', [
            'admin' => $GLOBALS['BE_USER']->isAdmin(),
            'promptManagement' => $this->settingsService->checkPermission('prompt_management'),
        ]);
        $moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    /**
     * Save settings
     *
     * @return ResponseInterface
     * @throws NoSuchArgumentException
     */
    public function saveAction(): ResponseInterface
    {
        foreach ($this->settingOptions as $option) {
            if ($this->request->hasArgument($option)) {
                $this->settingsService->setSetting($option, $this->request->getArgument($option));
            }
        }

        return GeneralUtility::makeInstance(ForwardResponse::class, 'settings');
    }

    /**
     * Add a new prompt
     *
     * @return ResponseInterface
     * @throws RouteNotFoundException
     * @throws NoSuchArgumentException
     * @throws IllegalObjectTypeException
     */
    public function addPromptAction(): ResponseInterface
    {
        $version = GeneralUtility::makeInstance(VersionNumberUtility::class)->getNumericTypo3Version();
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

        $uri = (string)$uriBuilder->buildUriFromRoute('AItoolsAitools_AItoolsSettings', ['tx_aitools_settings' => ['controller' => 'Settings', 'action' => 'settings']]);

        if (!$this->settingsService->checkPermission('prompt_management')) {
            return GeneralUtility::makeInstance(RedirectResponse::class, $uri);
        }

        $prompt = GeneralUtility::makeInstance(Prompt::class);
        $prompt->setPrompt($this->request->getArgument('prompt'));
        $prompt->setDescription($this->request->getArgument('description'));
        $prompt->setType($this->request->getArgument('type'));

        $this->promptRepository->add($prompt);

        return GeneralUtility::makeInstance(RedirectResponse::class, $uri);
    }

    /**
     * Save default prompt
     *
     * @return ResponseInterface
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     * @throws RouteNotFoundException
     * @throws NoSuchArgumentException
     */
    public function saveDefaultPromptAction(): ResponseInterface
    {
        $version = GeneralUtility::makeInstance(VersionNumberUtility::class)->getNumericTypo3Version();

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uri = (string)$uriBuilder->buildUriFromRoute('AItoolsAitools_AItoolsSettings', ['tx_aitools_settings' => ['controller' => 'Settings', 'action' => 'settings']]);

        if (!$this->settingsService->checkPermission('prompt_management')) {
            return GeneralUtility::makeInstance(RedirectResponse::class, $uri);
        }

        // set old default prompt to false
        if (version_compare($version, '11.0', '>=') && version_compare($version, '12.0', '<')) {
            // for TYPO3 v11
            // @phpstan-ignore-next-line
            $oldDefaultPrompt = $this->promptRepository->findOneByDefault(true);
        } else {
            /**
             * @var Prompt $oldDefaultPrompt
             * @phpstan-ignore-next-line
             */
            $oldDefaultPrompt = $this->promptRepository->findOneBy(['default' => true]);
        }
        if ($oldDefaultPrompt != null) {
            $oldDefaultPrompt->setDefault(false);
            $this->promptRepository->update($oldDefaultPrompt);
        }

        $promptUid = $this->request->getArgument('defaultPrompt');
        if (is_array($promptUid) && isset($promptUid['__identity'])) {
            // in case argument is an identity array
            $promptUid = $promptUid['__identity'];
        }
        /**
         * @var Prompt $defaultPrompt
         */
        $defaultPrompt = $this->promptRepository->findByUid($promptUid);

        // check if deletePrompt argument is set
        if ($this->request->hasArgument('deletePrompt') && $this->request->getArgument('deletePrompt') == '1') {
            $this->promptRepository->remove($defaultPrompt);
        } else {
            // set new default prompt to true
            $defaultPrompt->setDefault(true);
            $this->promptRepository->update($defaultPrompt);
        }

        // persist all changes
        $this->persistenceManager->persistAll();

        return GeneralUtility::makeInstance(RedirectResponse::class, $uri);
    }
}
