<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service;

use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SettingsService
{
    private readonly Registry $registry;

    private string $namespace = 'ai_tools';

    public function __construct()
    {
        $this->registry = GeneralUtility::makeInstance(Registry::class);
    }

    /**
     * get setting
     *
     * @return mixed|null
     */
    public function getSetting(string $key): mixed
    {
        return $this->registry->get($this->namespace, $key);
    }

    /**
     * set setting
     */
    public function setSetting(string $key, mixed $value): void
    {
        $this->registry->set($this->namespace, $key, $value);
    }
}
