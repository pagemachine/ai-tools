<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service\ImageRecognition;

use Pagemachine\AItools\Service\Abstract\AigudeAbstract;
use TYPO3\CMS\Core\Resource\FileInterface;

class AigudeImageRecognitionService extends AigudeAbstract implements ImageRecognitionServiceInterface
{
    private static string $cleanUpRegex = '/^(?:Certainly!\s*)?(?:The\s*|This\s*)?(?:main subject of the\s*)?(?:image\s)?(?:is\s*|prominently\s*|primarily\s*|predominantly\s*)?(?:shows|showing|displays|depicts|showcases|features|features)?\s*/';

    public function sendFileToApi(FileInterface $fileObject, string $textPrompt = ''): string
    {
        $url = $this->domain . '/img2desc_file/';

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

        $json = $this->request($url, 'POST', [
            'headers' => [
                'apikey' => $this->authToken,
            ],
            'multipart' => $multipartBody,
        ]);

        $text = preg_replace(self::$cleanUpRegex, '', (string)$json);
        $text = trim((string)$text);
        $text[0] = strtoupper($text[0]);
        return $text;
    }

    public function sendCreditsRequestToApi(FileInterface $fileObject, string $textPrompt = ''): string
    {
        // Use img2desc_translate until img2desc_translate is a separate service
        // $url = $this->domain . '/img2desc/calculate';
        $url = $this->domain . '/img2desc_translate/calculate';

        $formData = [
            'width' => $fileObject->getProperty('width'),
            'height' => $fileObject->getProperty('height'),
        ];

        $json = $this->request($url, 'POST', [
            'headers' => [
                'apikey' => $this->authToken,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($formData),
        ]);

        return (string) $json['credits_needed'] . ' Credits';
    }
}
