<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Domain\Repository;

use Pagemachine\AItools\Domain\Model\Server;
use Pagemachine\AItools\Service\ServerService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
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

    public function getDefault()
    {
        $version = GeneralUtility::makeInstance(VersionNumberUtility::class)->getNumericTypo3Version();
        if (version_compare($version, '11.0', '>=') && version_compare($version, '12.0', '<')) {
            // for TYPO3 v11
            // @phpstan-ignore-next-line
            $default = $this->findOneByDefault(true);
        } else {
            /**
             * @var Server $default
             * @phpstan-ignore-next-line
             */
            $default = $this->findOneBy(['default' => true]);
        }

        return $default;
    }
}
