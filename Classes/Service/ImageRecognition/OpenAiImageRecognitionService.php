<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service\ImageRecognition;

use GuzzleHttp\Exception\BadResponseException;
use Pagemachine\AItools\Domain\Model\Server;
use Pagemachine\AItools\Domain\Model\ServerOpenai;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class OpenAiImageRecognitionService implements ImageRecognitionServiceInterface
{
    protected ServerOpenai $server;
    protected $requestFactory;
    protected string $apikey;

    private $apiUri = 'https://api.openai.com/v1/chat/completions';

    public function __construct(Server $server)
    {
        if ($server instanceof ServerOpenai) {
            $this->server = $server;
        } else {
            throw new \InvalidArgumentException('Expected instance of ServerOpenai');
        }

        $this->requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
        $this->apikey = $this->server->getApikey();
    }

    /**
     * Send file to OpenAI API
     *
     * @param FileInterface $fileObject
     * @param string $textPrompt
     * @return string
     * @throws \Exception
     */
    public function sendFileToApi(FileInterface $fileObject, string $textPrompt = ''): string
    {
        $fileType = $fileObject->getMimeType();
        $fileContentBase64 = base64_encode($fileObject->getContents());

        $payload = [
            'model' => 'gpt-4o',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $textPrompt,
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => 'data:' . $fileType . ';base64,' . $fileContentBase64,
                            ],
                        ],
                    ],
                ],
            ],
            'max_tokens' => 300,
        ];

        $jsonPayload = json_encode($payload);

        $headers = [
            'Authorization' => 'Bearer ' . $this->apikey,
            'Content-Type' => 'application/json',
        ];
        try {
            $response = $this->requestFactory->request($this->apiUri, 'POST', [
                'headers' => $headers,
                'body' => $jsonPayload,
            ]);

            if ($response->getStatusCode() === 200) {
                $responseBody = $response->getBody()->getContents();
                $responseArray = json_decode((string)$responseBody, true);
                return $responseArray['choices'][0]['message']['content'] ?? '';
            }
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            throw new \Exception($responseBodyAsString);
        }

        throw new \Exception('API request failed');
    }

    public function sendCreditsRequestToApi(FileInterface $fileObject, string $textPrompt = ''): string
    {
        return '';
    }
}
