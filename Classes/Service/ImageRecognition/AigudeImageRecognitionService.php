<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service\ImageRecognition;

use Pagemachine\AItools\Domain\Model\PlaceholderResult;
use Pagemachine\AItools\Service\Abstract\AigudeAbstract;
use Pagemachine\AItools\Service\ContextRetrievalService;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AigudeImageRecognitionService extends AigudeAbstract implements ImageRecognitionServiceInterface
{
    private static string $cleanUpRegex = '/^(?:Certainly!\s*)?(?:The\s*|This\s*)?(?:main subject of the\s*)?(?:image\s)?(?:is\s*|prominently\s*|primarily\s*|predominantly\s*)?(?:shows|showing|displays|depicts|showcases|features|features)?\s*/';

    public function sendFileToApi(FileInterface $fileObject, PlaceholderResult $placeholderResult, string $targetLanguage = 'en', ?string $translationProvider = null, string $promptLang = 'auto'): string
    {
        $urlParts = ['api_version=2', 'model=aigude-vision-v1'];

        if (!empty($targetLanguage)) {
            $urlParts[] = 'target_lang=' . urlencode((string) $targetLanguage);
        }

        if (!empty($translationProvider)) {
            $urlParts[] = 'translation_provider=' . urlencode((string) $translationProvider);
        }

        $url = $this->domain . '/img2desc_file/' . '?' . implode('&', $urlParts);

        $filePath = $fileObject->getForLocalProcessing(false);
        $fileName = $fileObject->getName();
        $fileType = $fileObject->getMimeType();

        $tokens = [];
        foreach ($placeholderResult->getPlaceholders() as $placeholder) {
            $lang = 'auto';
            $langScript = $placeholder->getLanguage();
            if ($langScript) {
                $lang = $langScript;
            }

            $tokens[$placeholder->getIdentifier()] = [
                "value" => $placeholder->getValue(),
                "lang" => $lang,
                "translatable" => (bool) $langScript,
            ];
        }

        $prompt_spec = [
            "prompt_template" => $placeholderResult->getText(),
            "prompt_lang" => $promptLang,
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

        $contextService = GeneralUtility::makeInstance(ContextRetrievalService::class);
        $contextChunks = $contextService->retrieveContextChunks($fileObject);
        if (!empty($contextChunks)) {
            $multipartBody[] = [
                'name'     => 'context_chunks',
                'contents' => json_encode($contextChunks),
            ];
        }

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

    /**
     * Send multiple images in a single batch request to the CreditsAPI.
     *
     * @param array<int, array{file: FileInterface, placeholderResult: PlaceholderResult}> $items
     * @return array<int, string> Generated texts keyed by index
     */
    public function sendBatchToApi(array $items, string $targetLanguage = 'en', ?string $translationProvider = null): array
    {
        $urlParts = ['api_version=2', 'model=aigude-vision-v1'];
        if (!empty($targetLanguage)) {
            $urlParts[] = 'target_lang=' . urlencode($targetLanguage);
        }
        if (!empty($translationProvider)) {
            $urlParts[] = 'translation_provider=' . urlencode($translationProvider);
        }
        $url = $this->domain . '/img2desc_file_batch/?' . implode('&', $urlParts);

        $multipartBody = [];
        $contextChunksByFile = [];
        $tokensByFile = [];
        $promptTemplate = '';
        $promptLang = 'auto';

        $contextService = GeneralUtility::makeInstance(ContextRetrievalService::class);

        foreach ($items as $idx => $item) {
            $fileObject = $item['file'];
            $placeholderResult = $item['placeholderResult'];

            $filePath = $fileObject->getForLocalProcessing(false);
            $fileName = $fileObject->getName();
            $fileType = $fileObject->getMimeType();

            $multipartBody[] = [
                'name'     => 'image_files',
                'contents' => fopen($filePath, 'r'),
                'filename' => $fileName,
                'headers'  => ['Content-Type' => $fileType],
            ];

            $chunks = $contextService->retrieveContextChunks($fileObject);
            if (!empty($chunks)) {
                $contextChunksByFile[$fileName] = $chunks;
            }

            $tokens = [];
            foreach ($placeholderResult->getPlaceholders() as $placeholder) {
                $lang = 'auto';
                $langScript = $placeholder->getLanguage();
                if ($langScript) {
                    $lang = $langScript;
                }
                $tokens[$placeholder->getIdentifier()] = [
                    'value' => $placeholder->getValue(),
                    'lang' => $lang,
                    'translatable' => (bool) $langScript,
                ];
            }
            $tokensByFile[$fileName] = $tokens;

            if ($idx === 0) {
                $promptTemplate = $placeholderResult->getText();
            }
        }

        $promptSpec = [
            'prompt_template' => $promptTemplate,
            'prompt_lang' => $promptLang,
            'tokens' => $tokensByFile,
        ];

        $multipartBody[] = [
            'name'     => 'prompt_spec',
            'contents' => json_encode($promptSpec),
        ];

        if (!empty($contextChunksByFile)) {
            $multipartBody[] = [
                'name'     => 'context_chunks',
                'contents' => json_encode($contextChunksByFile),
            ];
        }

        $json = $this->request($url, 'POST', [
            'headers' => [
                'apikey' => $this->authToken,
            ],
            'multipart' => $multipartBody,
            'timeout' => 300,
        ]);

        $resultsByFilename = [];
        foreach ($json['items'] ?? [] as $item) {
            $text = preg_replace(self::$cleanUpRegex, '', (string)($item['generated_text'] ?? ''));
            $text = trim((string) $text);
            if (!empty($text)) {
                $text[0] = strtoupper($text[0]);
            }
            $resultsByFilename[$item['filename']] = $text;
        }

        $results = [];
        foreach ($items as $idx => $item) {
            $fileName = $item['file']->getName();
            $results[$idx] = $resultsByFilename[$fileName] ?? '';
        }

        return $results;
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
