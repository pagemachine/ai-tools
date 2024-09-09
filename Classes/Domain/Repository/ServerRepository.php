<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Domain\Repository;

use Pagemachine\AItools\Service\ServerService;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class ServerRepository extends Repository
{
    protected ServerService $serverService;

    public function injectServerService(ServerService $serverService)
    {
        $this->serverService = $serverService;
    }

    public function listAllServers(): QueryResultInterface
    {
        $query = $this->createQuery();

        $query->getQuerySettings()
            ->setIgnoreEnableFields(true);

        return $query->execute();
    }

    public function getByFunctionality(string $functionality): QueryResultInterface
    {
        $query = $this->createQuery();

        $servers = $this->serverService->getServerKeysByFunctionality($functionality);

        $query->matching(
            $query->in('type', $servers)
        );

        return $query->execute();
    }
}
