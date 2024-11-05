<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service\Credits;

use Pagemachine\AItools\Domain\Model\Server;

interface CreditsServiceInterface
{
    public function __construct(Server $server);

    /**
     * Sends a Credits request to the Credits API and returns the translated text.
     *
     */
    public function sendCreditsRequestToApi(): string;
}
