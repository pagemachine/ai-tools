<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service\ImageRecognition;

use Pagemachine\AItools\Service\SettingsService;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CustomImageRecognitionService implements ImageRecognitionServiceInterface
{
    protected $requestFactory;
    protected string $authToken = '';
    protected string $basicAuth = '';
    protected $settingsService;

    private static string $cleanUpRegex = '/^The image (shows|displays|depicts|showcases|features)/';

    public function __construct()
    {
        $this->settingsService = GeneralUtility::makeInstance(SettingsService::class);
        $this->requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
        $this->authToken = $this->settingsService->getSetting('custom_auth_token');
        // Retrieve the username and password from settings
        $username = $this->settingsService->getSetting('custom_api_username');
        $password = $this->settingsService->getSetting('custom_api_password');
        if (!empty($username) && !empty($password)) {
            $this->basicAuth = base64_encode($username . ':' . $password);
        }
    }

    public function sendFileToApi(FileInterface $fileObject, string $textPrompt = ''): string
    {
        /** @var string $url */
        $url = $this->settingsService->getSetting('custom_image_recognition_api_uri');

        if (!empty($textPrompt)) {
            $url .= '?prompt=' . urlencode($textPrompt);
        }

        $filePath = $fileObject->getForLocalProcessing(false);
        $fileName = $fileObject->getName();
        $fileType = $fileObject->getMimeType();

        $multipartBody = [
            [
                'name'     => 'image_file',
                'contents' => fopen($filePath, 'r'),
                'filename' => $fileName,
                'headers'  => ['Content-Type' => $fileType],
            ],
        ];

        $response = $this->requestFactory->request($url, 'POST', [
            'headers' => [
                'X-Auth-Token' => $this->authToken,
                'Authorization' => 'Basic ' . $this->basicAuth,
            ],
            'multipart' => $multipartBody,
        ]);

        if ($response->getStatusCode() === 200) {
            $text = $response->getBody()->getContents();

            // cleanup text from "The image depicts" introduction text.
            $text = preg_replace(self::$cleanUpRegex, '', $text);
            $text = trim($text);
            $text[0] = strtoupper($text[0]);
            return $text;
        }

        throw new \Exception('API request failed');
    }
}
