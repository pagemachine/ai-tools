<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service;

use Pagemachine\AItools\Domain\Repository\ServerRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SettingsService
{

    /**
     * get setting
     *
     * @return mixed|null
     */
    public function getSetting(string $key)
    {
        switch ($key) {
            case 'translation_service':
            case 'image_recognition_service':
                $serverRepository = GeneralUtility::makeInstance(ServerRepository::class);
                $server = $serverRepository->getDefault();
                if ($server === null) {
                    return null;
                }
                return $server->getUid();
            default:
                throw new \InvalidArgumentException('Unknown settings key: ' . $key, 1759757766);
        }
    }

    /**
     * Check if the current user has the permission to manage prompts
     *
     * @return bool
     */
    public function checkPermission(string $itemKey): bool
    {
        if (!isset($GLOBALS['BE_USER'])) {
            return false;
        }
        return $GLOBALS['BE_USER']->check('custom_options', 'tx_aitools_permissions' . ':' . $itemKey);
    }
}
