<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service;

use Pagemachine\AItools\Domain\Model\Server;
use Pagemachine\AItools\Domain\Repository\ServerRepository;
use Pagemachine\AItools\Service\SettingsService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ServerService
{
    private readonly array $serverConfig;
    private readonly SettingsService $settingsService;

    public function __construct()
    {
        $this->serverConfig = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['ai_tools']['servers'];

        $this->settingsService = GeneralUtility::makeInstance(SettingsService::class);
    }

    public function getServers(): array
    {
        return $this->serverConfig;
    }

    public function getServerKeysByFunctionality($functionality): array
    {
        $serverKeys = [];

        foreach ($this->serverConfig as $key => $value) {
            if (in_array($functionality, array_keys($value['functionality']))) {
                $serverKeys[] = $key;
            }
        }

        return $serverKeys;
    }

    public function getTcaOptions(): array
    {
        $options = [];

        foreach ($this->serverConfig as $key => $value) {
            $options[] = [
                $value['name'],
                $key,
            ];
        }

        return $options;
    }

    public function getNameOfServerType(string $type): string
    {
        return $this->serverConfig[$type]['name'] ?? 'Unknown';
    }

    public function getCreditsClassOfServerType(string $type)
    {
        return $this->serverConfig[$type]['credits'] ?? null;
    }

    public function getFunctionalityOfServerType(string $type): array
    {
        return array_keys($this->serverConfig[$type]['functionality']);
    }

    public function getActiveServerClassByFunctionality($functionality)
    {
        $serviceUid = (integer) $this->settingsService->getSetting($functionality.'_service');

        if (empty($serviceUid)) {
            throw new \Exception('No default API Connection defined in the settings (' . $functionality . ')');
        }

        $serverRepository = GeneralUtility::makeInstance(ServerRepository::class);
        $serverEntry = $serverRepository->findByUid($serviceUid);

        if (!$serverEntry instanceof Server) {
            throw new \Exception('No valid '.$functionality.' service configured');
        }

        $serverClass = $this->serverConfig[$serverEntry->getType()]['functionality'][$functionality];

        return new $serverClass($serverEntry);
    }
}
