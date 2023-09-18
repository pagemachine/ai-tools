<?php

declare(strict_types = 1);

namespace Pagemachine\AItools\Controller\Backend;


use Pagemachine\AItools\Domain\Model\Aiimage;
use Pagemachine\AItools\Service\AwsImageRecognizeService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Resource\Event\AfterFileAddedEvent;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\MetaDataAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class ImageRecognizeController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    private Registry $registry;

    private ?AwsImageRecognizeService $awsImageRecognizeService;

    public function __construct() {
        $this->awsImageRecognizeService = GeneralUtility::makeInstance(AwsImageRecognizeService::class);
        $this->registry = GeneralUtility::makeInstance(Registry::class);
    }

    /**
     * @throws \TYPO3\CMS\Core\Exception
     */
    public function setMetadataAction($file): void
    {
        if (!$this->hasAllAwsSettings()) {
            return;
        }
        $filePath = Environment::getPublicPath() . $file->getPublicUrl();

        $extension = strtolower($file->getExtension());
        $imageExtensions = ['jpg', 'png'];
        if (in_array($extension, $imageExtensions, true) && !empty($file->getPublicUrl())) {
            /** @var MetaDataAspect $metaData */
            $metaData = $file->getMetaData();

            $keywords = $this->awsImageRecognizeService->detectLabels($filePath);
            if (!empty($keywords)) {
                $metaData->offsetSet('aws_labels', $keywords);
            }
            $detectedText = $this->awsImageRecognizeService->detectText($filePath);
            if (!empty($detectedText)) {
                $metaData->offsetSet('aws_text', $detectedText);
            }

            //Send only the first $maxWords to OpenAI
            $strArrayLabels = explode (", ", $keywords);
            $strArrayText = explode (", ", $detectedText);
            $maxWords = 2;
            if(count($strArrayLabels) >= $maxWords){
                $maxLabels = $maxWords;
            }
            else {
                $maxLabels = count($strArrayLabels);
            }
            if(count($strArrayText) >= $maxWords){
                $maxTexts = $maxWords;
            }
            else {
                $maxTexts = count($strArrayText);
            }

            //We put frist the recognised objects and then the recognised text for the OpenAI input
            //$strArrayInputOpenAI = [];
            //for ($x = 0; $x < $maxLabels; $x++) {
            //    array_push($strArrayInputOpenAI,$strArrayLabels[$x]);
            //}
            //for ($x = 0; $x < $maxTexts; $x++) {
            //    array_push($strArrayInputOpenAI,$strArrayText[$x]);
            //}
            //$strInputOpenAI = implode (", ", $strArrayInputOpenAI);
//
            ////We do the OpenAI request
            //$openaiText = $this->openAIrequest($strInputOpenAI);
            //if (!empty($openaiText)) {
            //    $metaData->offsetSet('openai_text', $openaiText);
            //}

            $this->addMessageToFlashMessageQueue(
                'Metadata updated via AWS Rekognition and OpenAI',
                FlashMessage::INFO
            );

            $metaData->save();
        }
    }

    /**
     * @todo
     * @return ResponseInterface
     * @throws \JsonException
     */
    public function ajaxMetaGenerateAction(): ResponseInterface
    {
        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
        $response->getBody()->write(json_encode(['result' => []], JSON_THROW_ON_ERROR));
        return $response;
    }

    /**
     * describe an image
     * @param Aiimage $aiimage
     * @return void
     */
    public function describeAction(Aiimage $aiimage): void
    {
        $file = $aiimage->getFile();

        if (!$this->hasAllAwsSettings()) {
            $this->addMessageToFlashMessageQueue(
                "AWS settings are not configured",
                FlashMessage::ERROR
            );
            return;
        }
        if ($file['tmp_name']) {
            $filePath = $file['tmp_name'];

            $text = $this->awsImageRecognizeService->detectLabels($filePath);

            $this->view->assign('text', $text);

        }
    }

    /**
     * @param string $message
     * @param int $severity
     *
     * @return void
     * @throws \TYPO3\CMS\Core\Exception
     */
    protected function addMessageToFlashMessageQueue(string $message, int $severity = FlashMessage::ERROR): void
    {
        if (Environment::isCli()) {
            return;
        }

        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            $message,
            'AI-Metadata Status',
            $severity,
            true
        );

        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }

    private function hasAllAwsSettings(): bool
    {
        $aws_region = $this->registry->get('ai_tools', "aws_region");
        $aws_access_key_id = $this->registry->get('ai_tools', "aws_access_key_id");
        $aws_secret_access_key = $this->registry->get('ai_tools', "aws_secret_access_key");
        return !empty($aws_region) && !empty($aws_access_key_id) && !empty($aws_secret_access_key);
    }
}
