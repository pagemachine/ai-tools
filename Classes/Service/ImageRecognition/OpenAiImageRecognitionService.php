<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service\ImageRecognition;

use Pagemachine\AItools\Service\SettingsService;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class OpenAiImageRecognitionService implements ImageRecognitionServiceInterface
{
    protected $requestFactory;
    protected string $apikey;
    protected $settingsService;

    private $apiUri = 'https://api.openai.com/v1/chat/completions';

    public function __construct()
    {
        $this->settingsService = GeneralUtility::makeInstance(SettingsService::class);
        $this->requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
        $this->apikey = $this->settingsService->getSetting('openai_apikey');
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
            'model' => 'gpt-4-vision-preview',
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

        $response = $this->requestFactory->request($this->apiUri, 'POST', [
            'headers' => $headers,
            'body' => $jsonPayload,
        ]);

        if ($response->getStatusCode() === 200) {
            $responseBody = $response->getBody()->getContents();
            $responseArray = json_decode((string) $responseBody, true);
            return $responseArray['choices'][0]['message']['content'] ?? '';
        }

        throw new \Exception('API request failed');
    }
}
