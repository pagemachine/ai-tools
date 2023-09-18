<?php

declare(strict_types = 1);

namespace Pagemachine\AItools\Service;

use Aws\Exception\AwsException;
use Aws\Rekognition\RekognitionClient;
use Exception;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class AwsImageRecognizeService
{
    private ?SettingsService $settingsService;
    private ?RekognitionClient $rekognitionClient;

    private LoggerInterface $logger;

    public function __construct()
    {
        $this->settingsService = GeneralUtility::makeInstance(SettingsService::class);

        $options = [
            'region' => $this->settingsService->getSetting('aws_region'),
            'credentials' => [
                'key' => $this->settingsService->getSetting('aws_access_key_id'),
                'secret' => $this->settingsService->getSetting('aws_secret_access_key'),
            ],
            'version' => '2016-06-27',
        ];
        $this->rekognitionClient = new RekognitionClient($options);
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }

    /**
     * @param string $imagePath
     * @return string
     */
    public function detectLabels(string $imagePath): string
    {
        $result = $this->recognizeImage($imagePath, 'detectLabels');

        $confidenceSetting = $this->settingsService->getSetting('aws_confidence');

        $keywords = [];
        foreach ($result as $labels) {
            if (is_array($labels)) {
                foreach ($labels ?? [] as $label) {
                    $confidence = (float)($label['Confidence'] ?? 0.00);
                    $name = $label['Name'] ?? '';
                    if (($confidence > $confidenceSetting) && !empty($name)) {
                        $keywords[] = $name;
                    }
                }
            }
        }

        return implode(', ', array_unique($keywords));
    }

    /**
     * @param string $imagePath
     * @return string
     */
    public function detectText(string $imagePath): string
    {
        $result = $this->recognizeImage($imagePath, 'detectText');

        $confidenceSetting = $this->settingsService->getSetting('aws_confidence');

        $detectedTextItems = [];
        foreach ($result as $labels) {
            if (is_array($labels)) {
                foreach ($labels ?? [] as $label) {
                    $confidence = (float)($label['Confidence'] ?? 0.00);
                    $detectedText = $label['DetectedText'] ?? '';
                    if (($confidence > $confidenceSetting) && !empty($detectedText)) {
                        $detectedTextItems[] = $detectedText;
                    }
                }
            }
        }

        return implode(', ', array_unique($detectedTextItems));
    }

    /**
     * @param string $imagePath
     * @param string $function
     */
    protected function recognizeImage(string $imagePath, string $function)
    {
        $fpImage = fopen($imagePath, 'rb');
        $image = fread($fpImage, filesize($imagePath));
        fclose($fpImage);

        try {
            return $this->rekognitionClient->$function([
                'Image' => [
                    'Bytes' => $image,
                ],
                'Attributes' => ['ALL'],
            ]);
        } catch (AwsException $e) {
            $this->logger->error('AWS Exception', [$e->getAwsRequestId(), $e->getAwsErrorType(), $e->getAwsErrorCode()]);

            return [];
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());

            return [];
        }
    }
}
