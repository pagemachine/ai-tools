<?php

declare(strict_types=1);

namespace Pagemachine\AItools\ViewHelpers\Backend;

use Pagemachine\AItools\Service\ImageMetaDataService;
use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

class CreditsViewHelper extends AbstractTagBasedViewHelper
{
    protected ImageMetaDataService $imageMetaDataService;
    protected ResourceFactory $resourceFactory;

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('type', 'string', 'The type of action to calculate the price to', false, '');
        $this->registerArgument('file-identifier', 'string', 'The file to calculate the price for', false, '');
        $this->registerArgument('text-prompt', 'string', 'The text prompt to send to the API', false, '');
    }

    protected function imageRecognition(): string
    {
        $fileIdentifer = $this->arguments['file-identifier'];
        if ($fileIdentifer) {
            $fileObject = $this->resourceFactory->retrieveFileOrFolderObject($fileIdentifer);
            if ($fileObject instanceof FileInterface) {
                if ($fileObject->getType() !== AbstractFile::FILETYPE_IMAGE) {
                    return '';
                }

                return $this->imageMetaDataService->priceForImageDescription($fileObject, $this->arguments['text-prompt']);
            }
        }

        return '';
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->imageMetaDataService = GeneralUtility::makeInstance(ImageMetaDataService::class);
        $this->resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
    }

    public function render(): string
    {
        $text = '';

        switch ($this->arguments['type']) {
            case 'imageRecognition':
                $text = $this->imageRecognition();
                break;
        }


        if (empty($text)) {
            return '';
        }

        $this->tag->addAttribute(
            'class',
            'label label-default'
        );

        $this->tag->setContent($text);

        return $this->tag->render();
    }
}
