<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service\Credits;

use Pagemachine\AItools\Service\Credits\CreditsServiceInterface;
use Pagemachine\AItools\Service\Translation\AigudeTranslationService;

class AigudeCreditsService extends AigudeTranslationService implements CreditsServiceInterface
{

    public function sendCreditsRequestToApi(): string
    {
        $url = 'https://credits.kong.pagemachine.de/remaining_credits';

        $response = $this->requestFactory->request($url, 'GET', [
            'headers' => [
                'apikey' => $this->authToken,
                'Content-Type' => 'application/json',
            ],
        ]);

        if ($response->getStatusCode() === 200) {
            $result = $response->getBody()->getContents();
            $json = json_decode((string)$result, true);
            return (string) $json['remaining_credits'].' Credits';
        }

        throw new \Exception('API request failed');
    }
}
