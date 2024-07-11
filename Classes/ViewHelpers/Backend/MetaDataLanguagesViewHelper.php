<?php

declare(strict_types=1);

namespace Pagemachine\AItools\ViewHelpers\Backend;

use Pagemachine\AItools\Service\ImageMetaDataService;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class MetaDataLanguagesViewHelper extends AbstractViewHelper
{
    /**
     * Initialize arguments.
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('fileObject', FileInterface::class, 'Array of FileInterface objects or UIDs', true);
        $this->registerArgument('languages', 'array', 'Array of language UIDs', true);
    }

    /**
     * Render the view helper.
     *
     * @return array
     * @throws \TYPO3\CMS\Core\Resource\Exception\InvalidUidException
     * @throws \Doctrine\DBAL\Exception
     */
    public function render(): array
    {
        /** @var ImageMetaDataService $imageMetaDataService */
        $imageMetaDataService = GeneralUtility::makeInstance(ImageMetaDataService::class);
        $fileObject = $this->arguments['fileObject'];
        $languages = $this->arguments['languages'];

        return $imageMetaDataService->getMetaDataLanguages($fileObject, $languages);
    }
}
