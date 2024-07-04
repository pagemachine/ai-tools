<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Controller\Backend;

use OpenAI\Client;
use Pagemachine\AItools\Domain\Model\Aiimage;
use Pagemachine\AItools\Service\SettingsService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class ImageCreationController extends ActionController
{
    public function __construct(private readonly ?SettingsService $settingsService)
    {
    }

    private function getOpenAIClient(): Client
    {
        $openaiKey = (string)$this->settingsService->getSetting('openai_apikey');
        return \OpenAI::client($openaiKey);
    }

    public function showAction(): ResponseInterface
    {
        //This is the default action because is the first listed in packages/ai_image/ext_tables.php
        //show form to generate image packages/ai_image/Resources/Private/Templates/Backend/Show.html
        //echo "<h2>showAction - BackendController.php</h2>";
        return $this->htmlResponse();
    }

    public function generateAction(Aiimage $aiimage): ResponseInterface
    {
        //Do the openai request to generate image
        //echo "<h2>generateAction - BackendController.php</h2>";

        //The object Aiimage and the name should be called the same in the form from Show.html
        //to be passed to this function

        // Define paths and set template
        //setTemplateRootPaths not necessary you define the template paths in a Typoscript:
        //ai_image/ext_typoscript_setup.typoscript, then you dont need to add it to every function.
        //setTemplate should not be necessary as per default it uses the function name without action
        //therefore the default template is Generate.html for generateAction
        //$this->view->setTemplateRootPaths(['EXT:ai_image/Resources/Private/Templates']);
        //$this->view->setTemplate('Generate');

        //We get the object $aiimage from the Show.html form
        $description = $aiimage->getDescription();
        $imagesnumber = $aiimage->getImagesnumber();
        $imagesnumber = (int)$imagesnumber;
        $resolution = $aiimage->getResolution();
        $altText = str_replace(' ', '-', $description);

        //Get the property description from the form
        //$description = $generatedImageName['description']; //no Aiimage object
        //We generate an image using the description
        if (!empty($description)) {
            $imageDescriptionUrlArray = $this->generateImage($description, $imagesnumber, $resolution);

            $this->view->assignMultiple([
                'descriptionImageLinksFluid' => $imageDescriptionUrlArray,
                'imageDescription' => $altText,
            ]);
        }
        return $this->htmlResponse();
    }

    public function variateAction(Aiimage $aiimage): ResponseInterface
    {
        $file = $aiimage->getFile();
        $imagesnumber = $aiimage->getImagesnumber();
        $imagesnumber = (int)$imagesnumber;
        $resolution = $aiimage->getResolution();

        //We pass the temp path of the file to create a variation
        if ($file['tmp_name']) {
            $filePath = $file['tmp_name'];
            $fileName = $file['name'];
            $fileName = str_replace('.png', '', (string)$fileName);

            $imageVariationUrlArray = $this->variationImage($filePath, $imagesnumber, $resolution);

            $this->view->assignMultiple([
                'variationImageLinksFluid' => $imageVariationUrlArray,
                'variationImageFileFluid' => $fileName,
            ]);
        }
        return $this->htmlResponse();
    }

    /**
     * @throws Exception
     */
    public function saveAction(array $result_aiimage): Response
    {
        // get filestorage
        $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
        $defaultStorage = $storageRepository->getDefaultStorage();
        /** @var Folder $folder **/
        $folder = $defaultStorage->getRootLevelFolder();

        //Save the file in fileadmin
        $fileUrl = $result_aiimage['fileurl'];
        $filename = $result_aiimage['filename'];
        $filename = $filename . '-' . uniqid() . '.png';

        // download generated file to temporary file
        $fileContent = GeneralUtility::getUrl($fileUrl);
        $temporaryFile = GeneralUtility::tempnam('temp/' . $filename);
        GeneralUtility::writeFileToTypo3tempDir(
            $temporaryFile,
            $fileContent
        );

        // add file to filestorage folder
        $savedFile = $folder->addFile($temporaryFile, $filename);

        // delete temporary file
        GeneralUtility::unlink_tempfile($temporaryFile);

        // check if $savedFile is an instance of \TYPO3\CMS\Core\Resource\File
        if ($savedFile instanceof File) {
            $saveTarget = $savedFile->getIdentifier();

            $this->addFlashMessage(
                'The image has been saved in ' . $saveTarget,
                'Image saved',
                ContextualFeedbackSeverity::INFO,
                true
            );
            /** @phpstan-ignore-next-line Else branch is unreachable because previous condition is always true. */
        } else {
            $this->addFlashMessage(
                'The image could not be saved',
                'Image not saved',
                ContextualFeedbackSeverity::ERROR,
                true
            );
        }

        return GeneralUtility::makeInstance(ForwardResponse::class, 'show');
    }

    private function generateImage($text, $imagesnumber, $resolution): array
    {
        $client = $this->getOpenAIClient();

        $returnUrlArray = [];

        try {
            $response = $client->images()->create([
                'prompt' => $text,
                'n' => $imagesnumber,
                'size' => $resolution,
                'response_format' => 'url',
            ]);

            $imageUrlArray = $response->toArray();

            for ($i = 0; $i < count($imageUrlArray['data']); $i++) {
                $imageUrl = $imageUrlArray['data'][$i]['url'];
                array_push($returnUrlArray, $imageUrl);
            }
        } catch (\Exception $e) {
            $exceptionMsg = $e->getMessage();

            $this->addFlashMessage(
                $exceptionMsg,
                'Warning',
                ContextualFeedbackSeverity::WARNING,
                true
            );
        }

        return $returnUrlArray;
    }

    private function variationImage($image, $imagesnumber, $resolution): array
    {
        $client = $this->getOpenAIClient();
        $returnUrlArray = [];

        try {
            $response = $client->images()->variation([
                'image' => fopen($image, 'r'),
                'n' => $imagesnumber,
                'size' => $resolution,
                'response_format' => 'url',
            ]);

            $imageUrlArray = $response->toArray();

            for ($i = 0; $i < count($imageUrlArray['data']); $i++) {
                $imageUrl = $imageUrlArray['data'][$i]['url'];
                array_push($returnUrlArray, $imageUrl);
            }
        } catch (\Exception $e) {
            $exceptionMsg = $e->getMessage();

            $this->addFlashMessage(
                $exceptionMsg,
                'Warning',
                ContextualFeedbackSeverity::WARNING,
                true
            );
        }

        return $returnUrlArray;
    }
}
