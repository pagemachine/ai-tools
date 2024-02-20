<?php

declare(strict_types = 1);

namespace Pagemachine\AItools\Service;

use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CustomImageRecognitionService
{
    protected $requestFactory;
    protected $authToken;

    public function __construct()
    {
        $this->settingsService = GeneralUtility::makeInstance(SettingsService::class);
        $this->authToken = $this->settingsService->getSetting('custom_auth_token');
        $this->requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
    }

    public function sendFileToApi(FileInterface $fileObject, string $textPrompt = ''): string
    {
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
                'headers'  => ['Content-Type' => $fileType]
            ]
        ];

        $response = $this->requestFactory->request($url, 'POST', [
            'headers' => ['X-Auth-Token' => $this->authToken],
            'multipart' => $multipartBody
        ]);

        if ($response->getStatusCode() === 200) {
            return $response->getBody()->getContents();
        }

        throw new \Exception('API request failed');
    }

    public function sendTranslationRequestToApi(string $text, string $sourceLang = 'eng_Latn', string $targetLang = 'eng_Latn'): string
    {
        $url = $this->settingsService->getSetting('custom_translation_api_uri');

        $url .= '?text=' . urlencode($text) . '&source_lang=' . $sourceLang . '&target_lang=' . $targetLang;

        $response = $this->requestFactory->request($url, 'POST', [
            'headers' => ['X-Auth-Token' => $this->authToken]
        ]);

        if ($response->getStatusCode() === 200) {
            return $response->getBody()->getContents();
        }

        throw new \Exception('API request failed');
    }
}
