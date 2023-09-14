<?php

declare(strict_types = 1);

namespace Pagemachine\AItools\Controller\Backend;

use Pagemachine\AItools\Service\SettingsService;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class SettingsController extends ActionController
{
    private ?SettingsService $settingsService;

    private array $settingOptions = ['openai_apikey', 'aws_region', 'aws_access_key_id', 'aws_secret_access_key', 'aws_confidence'];

    public function __construct(
        SettingsService $settingsService,
    )
    {
        $this->settingsService = $settingsService;
    }

    public function settingsAction(): void
    {
        foreach ($this->settingOptions as $option) {
            $this->view->assign($option, $this->settingsService->getSetting($option));
        }
    }

    public function saveAction(): Response
    {
        foreach ($this->settingOptions as $option) {
            if ($this->request->hasArgument($option)) {
                $this->settingsService->setSetting($option, $this->request->getArgument($option));
            }
        }

        return GeneralUtility::makeInstance(ForwardResponse::class, 'settings');
    }
}
