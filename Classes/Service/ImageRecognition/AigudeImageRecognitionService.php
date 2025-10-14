<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service\ImageRecognition;

use Pagemachine\AItools\Domain\Model\PlaceholderResult;
use Pagemachine\AItools\Service\Abstract\AigudeAbstract;
use TYPO3\CMS\Core\Resource\FileInterface;

class AigudeImageRecognitionService extends AigudeAbstract implements ImageRecognitionServiceInterface
{
    private static string $cleanUpRegex = '/^(?:Certainly!\s*)?(?:The\s*|This\s*)?(?:main subject of the\s*)?(?:image\s)?(?:is\s*|prominently\s*|primarily\s*|predominantly\s*)?(?:shows|showing|displays|depicts|showcases|features|features)?\s*/';

    public function sendFileToApi(FileInterface $fileObject, PlaceholderResult $placeholderResult, string $targetLanguage = 'en', string $promptLanguage = 'auto'): string
    {
        $urlParts = ['api_version=2'];

        if (!empty($targetLanguage)) {
            $urlParts[] = '&target_lang=' . urlencode((string) $targetLanguage);
        }

        $url = $this->domain . '/img2desc_file/' . '?' . implode('&', $urlParts);

        $filePath = $fileObject->getForLocalProcessing(false);
        $fileName = $fileObject->getName();
        $fileType = $fileObject->getMimeType();

        $tokens = [];
        foreach ($placeholderResult->getPlaceholders() as $placeholder) {
            $lang = 'auto';
            $langScript = $placeholder->getLanguage();
            if ($placeholder->getLanguage() && $langScript) {
                $lang = $langScript;
            }

            $tokens[$placeholder->getIdentifier()] = [
                "value" => $placeholder->getValue(),
                "lang" => $lang,
                "translatable" => (boolean) $placeholder->getLanguage(),
            ];
        }

        $prompt_spec = [
            "prompt_template" => $placeholderResult->getText(),
            "prompt_lang" => $promptLanguage,
            "tokens" => $tokens,
        ];

        $multipartBody = [
            [
                'name'     => 'image_file',
                'contents' => fopen($filePath, 'r'),
                'filename' => $fileName,
                'headers'  => ['Content-Type' => $fileType],
            ],
            [
                'name'     => 'prompt_spec',
                'contents' => json_encode($prompt_spec),
            ],
        ];

        $json = $this->request($url, 'POST', [
            'headers' => [
                'apikey' => $this->authToken,
            ],
            'multipart' => $multipartBody,
        ]);

        $text = preg_replace(self::$cleanUpRegex, '', (string)$json['generated_text']);
        $text = trim((string)$text);
        $text[0] = strtoupper($text[0]);
        return $text;
    }

    public function sendCreditsRequestToApi(FileInterface $fileObject, string $textPrompt = '', string $targetLanguage = 'en'): string
    {
        $url = $this->domain . '/img2desc/calculate';

        $formData = [
            'width' => $fileObject->getProperty('width'),
            'height' => $fileObject->getProperty('height'),
            'target_lang' => $targetLanguage,
        ];

        $json = $this->request($url, 'POST', [
            'headers' => [
                'apikey' => $this->authToken,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($formData),
        ]);

        return (string) $json['credits_needed'];
    }

    public function supportsTranslation(): bool
    {
        return true;
    }
}
