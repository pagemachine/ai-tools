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
    ) {}

    /**
     * Check if the current user has the permission to manage prompts
     *
     * @param string $itemKey
     * @return bool
     */
    private function checkPermission(string $itemKey): bool
    {
        if (!isset($GLOBALS['BE_USER'])) {
            return false;
        }
        return $GLOBALS['BE_USER']->check('custom_options', 'tx_aitools_permissions' . ':' . $itemKey);
    }

    /**
     * Show settings form
     */
    public function settingsAction(): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);

        foreach ($this->settingOptions as $option) {
            $this->view->assign($option, $this->settingsService->getSetting($option));
        }

        // get all prompts
        $prompts = $this->promptRepository->findAll();
        $defaultPrompt = $this->promptRepository->findOneByDefault(true);

        $this->view->assign('prompts', $prompts);
        $this->view->assign('defaultPrompt', $defaultPrompt);

        $this->view->assign('permissions', [
            'admin' => $GLOBALS['BE_USER']->isAdmin(),
            'promptManagement' => $this->checkPermission('prompt_management'),
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
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uri = (string)$uriBuilder->buildUriFromRoute('aitools_AItoolsSettings', ['tx_aitools_settings' => ['controller' => 'Settings', 'action' => 'settings']]);

        if (!$this->checkPermission('prompt_management')) {
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
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uri = (string)$uriBuilder->buildUriFromRoute('aitools_AItoolsSettings', ['tx_aitools_settings' => ['controller' => 'Settings', 'action' => 'settings']]);

        if (!$this->checkPermission('prompt_management')) {
            return GeneralUtility::makeInstance(RedirectResponse::class, $uri);
        }

        // set old default prompt to false
        $oldDefaultPrompt = $this->promptRepository->findOneByDefault(true);
        if ($oldDefaultPrompt) {
            $oldDefaultPrompt->setDefault(false);
            $this->promptRepository->update($oldDefaultPrompt);
        }

        $promptUid = $this->request->getArgument('defaultPrompt');
        if (is_array($promptUid) && isset($promptUid['__identity'])) {
            // in case argument is an identity array
            $promptUid = $promptUid['__identity'];
        }
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
