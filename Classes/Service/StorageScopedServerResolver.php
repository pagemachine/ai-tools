<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service;

use Pagemachine\AItools\Domain\Model\Server;
use Pagemachine\AItools\Domain\Repository\ServerRepository;
use Pagemachine\AItools\Exception\StorageDisabledException;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceStorage;

class StorageScopedServerResolver
{
    public function __construct(private readonly ServerRepository $serverRepository)
    {
    }

    public function isEnabledForStorage(ResourceStorage $storage): bool
    {
        $record = $storage->getStorageRecord();
        return (int) ($record['tx_aitools_enabled'] ?? 1) === 1;
    }

    public function isEnabledForFile(FileInterface $file): bool
    {
        return $this->isEnabledForStorage($file->getStorage());
    }

    public function resolveForFile(FileInterface $file): Server
    {
        $record = $file->getStorage()->getStorageRecord();

        if ((int) ($record['tx_aitools_enabled'] ?? 1) !== 1) {
            throw new StorageDisabledException(
                sprintf(
                    'AI Tools disabled for storage "%s" (uid %d)',
                    $record['name'] ?? '?',
                    (int) ($record['uid'] ?? 0)
                )
            );
        }

        $overrideUid = (int) ($record['tx_aitools_server'] ?? 0);
        if ($overrideUid > 0) {
            $server = $this->serverRepository->findServerByUid($overrideUid);
            if ($server instanceof Server) {
                return $server;
            }
        }

        $default = $this->serverRepository->getDefault();
        if (!$default instanceof Server) {
            throw new \RuntimeException('No default AI server configured.');
        }
        return $default;
    }
}
