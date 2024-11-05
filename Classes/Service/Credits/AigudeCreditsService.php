<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service\Credits;

use Pagemachine\AItools\Service\Abstract\AigudeAbstract;
use Pagemachine\AItools\Service\Credits\CreditsServiceInterface;

class AigudeCreditsService extends AigudeAbstract implements CreditsServiceInterface
{

    public function sendCreditsRequestToApi(): string
    {
        $url = $this->domain . '/remaining_credits';

        $json = $this->request($url, 'GET', [
            'timeout' => 1,
            'headers' => [
                'apikey' => $this->authToken,
                'Content-Type' => 'application/json',
            ],
        ]);

        return (string) $json['remaining_credits'];
    }
}
