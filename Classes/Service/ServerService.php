<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service;

class ServerService
{
    private readonly array $serverConfig;

    public function __construct()
    {
        $this->serverConfig = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['ai_tools']['servers'];
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

    public function getFunctionalityOfServerType(string $type): array
    {
        return array_keys($this->serverConfig[$type]['functionality']);
    }
}
