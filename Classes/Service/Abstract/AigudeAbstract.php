<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service\Abstract;

use Pagemachine\AItools\Domain\Model\Server;
use Pagemachine\AItools\Domain\Model\ServerAigude;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AigudeAbstract
{
    protected ServerAigude $server;
    private $requestFactory;
    protected string $authToken = '';
    protected string $domain = 'https://credits.aigude.io';

    public function __construct(Server $server)
    {
        if ($server instanceof ServerAigude) {
            $this->server = $server;
        } else {
            throw new \InvalidArgumentException('Expected instance of ServerAigude');
        }

        $this->requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
        $this->authToken = $this->server->getApikey();
    }

    protected function request($url, $method, $options = [])
    {
        $options['http_errors'] = false;
        $response = $this->requestFactory->request($url, $method, $options);

        if ($response->getStatusCode() === 200) {
            $result = $response->getBody()->getContents();
            $json = json_decode((string)$result, true);

            return $json;
        }

        $error = null;
        try {
            $result = $response->getBody()->getContents();
            $json = json_decode((string)$result, true);
            if (isset($json['detail'])) {
                $error = $json['detail'];
            }
        } catch (\Exception) {
        }

        if (!is_null($error)) {
            throw new \Exception($error);
        }

        throw new \Exception('API request failed (code '.$response->getStatusCode().')');
    }
}
