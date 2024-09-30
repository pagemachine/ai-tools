<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service\ImageRecognition;

use Pagemachine\AItools\Domain\Model\Server;
use Pagemachine\AItools\Domain\Model\ServerCustom;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CustomImageRecognitionService implements ImageRecognitionServiceInterface
{
    protected ServerCustom $server;
    protected $requestFactory;
    protected string $authToken = '';
    protected string $basicAuth = '';

    private static string $cleanUpRegex = '/^(?:Certainly!\s*)?(?:The\s*|This\s*)?(?:main subject of the\s*)?(?:image\s)?(?:is\s*|prominently\s*|primarily\s*|predominantly\s*)?(?:shows|showing|displays|depicts|showcases|features|features)?\s*/';

    public function __construct(Server $server)
    {
        if ($server instanceof ServerCustom) {
            $this->server = $server;
        } else {
            throw new \InvalidArgumentException('Expected instance of ServerCustom');
        }

        $this->requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
        $this->authToken = $this->server->getApikey();
        // Retrieve the username and password from settings
        $username = $this->server->getUsername();
        $password = $this->server->getPassword();
        if (!empty($username) && !empty($password)) {
            $this->basicAuth = base64_encode($username . ':' . $password);
        }
    }

    public function sendFileToApi(FileInterface $fileObject, string $textPrompt = ''): string
    {
        /** @var string $url */
        $url = $this->server->getImageUrl();

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
            $text = preg_replace(self::$cleanUpRegex, '', (string)$text);
            $text = trim((string)$text);
            $text[0] = strtoupper($text[0]);
            return $text;
        }

        throw new \Exception('API request failed');
    }

    public function sendCreditsRequestToApi(FileInterface $fileObject, string $textPrompt = ''): string
    {
        return '';
    }
}
