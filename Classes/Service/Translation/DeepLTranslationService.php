<?php

declare(strict_types = 1);

namespace Pagemachine\AItools\Service\Translation;

use Pagemachine\AItools\Service\SettingsService;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DeepLTranslationService
{
    protected $requestFactory;
    protected string $authKey;
    protected $settingsService;

    protected string $apiEndpointUri;
    private $endpoints = [
        'free' => 'https://api-free.deepl.com/v2/translate',
        'pro' => 'https://api.deepl.com/v2/translate',
    ];

    public function __construct()
    {
        $this->settingsService = GeneralUtility::makeInstance(SettingsService::class);
        $this->requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
        $this->authKey = $this->settingsService->getSetting('deepl_auth_key');
        $this->apiEndpointUri = $this->endpoints[(string)$this->settingsService->getSetting('deepl_endpoint')];
    }

    public function sendTranslationRequestToApi(string $text, string $sourceLang = 'EN', string $targetLang = 'EN-GB'): string
    {
        $formality = $this->settingsService->getSetting('deepl_formality');

        $authKey = $this->settingsService->getSetting('deepl_auth_key'); // Assuming the DeepL auth key is stored in settings

        $data = [
            'text' => $text,
            'source_lang' => $sourceLang,
            'target_lang' => $targetLang,
            'auth_key' => $authKey,
        ];
        if ($formality !== 'default' && $formality !== '') {
            $data['formality'] = $formality;
        }

        $response = $this->requestFactory->request($this->apiEndpointUri, 'POST', [
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'body' => http_build_query($data),
        ]);

        if ($response->getStatusCode() === 200) {
            $responseBody = json_decode($response->getBody()->getContents(), true);
            return $responseBody['translations'][0]['text']; // Extracting the translated text
        }

        throw new \Exception('API request failed');
    }
}