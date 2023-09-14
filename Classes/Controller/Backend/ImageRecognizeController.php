<?php

declare(strict_types = 1);

namespace Pagemachine\AItools\Controller\Backend;


use Pagemachine\AItools\Domain\Model\Aiimage;
use Pagemachine\AItools\Service\AwsImageRecognizeService;
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

    public function __construct(
        AwsImageRecognizeService $awsImageRecognizeService,
    ) {
        $this->awsImageRecognizeService = $awsImageRecognizeService;
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

    /*private function openAIrequest($input): string
    {
        $describeInput = "Describe a ".$input;

        $openaiKey = getenv('OPENAI_API_KEY');
        $client = OpenAI::client($openaiKey);

        //What sampling temperature to use, between 0 and 2.
        //Higher values like 0.8 will make the output more random,
        //while lower values like 0.2 will make it more focused and deterministic.

        try {
            $result = $client->completions()->create([
                'model' => 'text-davinci-003',
                'prompt' => $describeInput,
                'temperature' => 0,
                'max_tokens' => 50
            ]);
        }
            //Handle request errors like wrong API key
        catch (\Exception $e) {
            $exceptionMsg = $e->getMessage();
        }

        //Handle undefined array keys in $result
        if(isset($result['choices'][0]['text'])){
            $completedText = $result['choices'][0]['text'];
        }
        else{
            $completedText = '';
        }

        return $completedText;
    }*/

    /**
     * describe an image
     * @param Aiimage $aiimage
     * @return void
     */
    public function describeAction(Aiimage $aiimage): void
    {
        $file = $aiimage->getFile();
        DebuggerUtility::var_dump($aiimage);
        DebuggerUtility::var_dump($file);

        if (!$this->hasAllAwsSettings()) {
            return;
        }
        if ($file['tmp_name']) {
            $filePath = $file['tmp_name'];
            $fileName = $file['name'];
            $fileName = str_replace('.png', '', $fileName);
            //$filePath = Environment::getPublicPath(). $file->getPublicUrl();

            $extension = strtolower($file->getExtension());
            $imageExtensions = ['jpg', 'png'];

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
