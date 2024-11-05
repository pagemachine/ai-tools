<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service\Translation;

use Pagemachine\AItools\Domain\Model\Server;
use Pagemachine\AItools\Domain\Model\ServerDeepl;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DeepLTranslationService implements TranslationServiceInterface
{
    protected ServerDeepl $server;
    protected $requestFactory;
    protected string $authKey;

    protected string $apiEndpointUri;
    private $endpoints = [
        'free' => 'https://api-free.deepl.com/v2/translate',
        'pro' => 'https://api.deepl.com/v2/translate',
    ];

    private array $languages = [
        'bg' => 'BG',
        'cs' => 'CS',
        'da' => 'DA',
        'de' => 'DE',
        'el' => 'EL',
        'en' => 'EN',
        'es' => 'ES',
        'et' => 'ET',
        'fi' => 'FI',
        'fr' => 'FR',
        'hu' => 'HU',
        'id' => 'ID',
        'it' => 'IT',
        'ja' => 'JA',
        'ko' => 'KO',
        'lt' => 'LT',
        'lv' => 'LV',
        'nb' => 'NB',
        'nl' => 'NL',
        'pl' => 'PL',
        'pt' => 'PT',
        'ro' => 'RO',
        'ru' => 'RU',
        'sk' => 'SK',
        'sl' => 'SL',
        'sv' => 'SV',
        'tr' => 'TR',
        'uk' => 'UK',
        'zh' => 'ZH',
    ];

    public function __construct(Server $server)
    {
        if ($server instanceof ServerDeepl) {
            $this->server = $server;
        } else {
            throw new \InvalidArgumentException('Expected instance of ServerDeepl');
        }

        $this->requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
        $this->authKey = $this->server->getApikey();
        $this->apiEndpointUri = $this->endpoints[(string)$this->server->getEndpoint()];
    }

    /**
     * Retrieves the script code for a given language code.
     * Used for mapping AI language codes to TYPO3 Language codes.
     * Where the array key is the TYPO3 lang. code and the value the AI language code.
     *
     * @param string $code The language code.
     * @return string|null The script code for the given language code, or null if the language code is not found.
     */
    private function getLanguageScript(string $code): ?string
    {
        return $this->languages[$code] ?? null;
    }

    public function sendTranslationRequestToApi(string $text, string $sourceLang = 'en', string $targetLang = 'en'): string
    {
        $sourceLang = $this->getLanguageScript($sourceLang);
        $targetLang = $this->getLanguageScript($targetLang);

        $formality = $this->server->getFormality();

        $authKey = $this->authKey; // Assuming the DeepL auth key is stored in settings

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
            $responseBody = json_decode((string)$response->getBody()->getContents(), true);
            return $responseBody['translations'][0]['text']; // Extracting the translated text
        }

        throw new \Exception('API request failed');
    }
}
