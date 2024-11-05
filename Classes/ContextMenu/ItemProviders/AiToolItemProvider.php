<?php

declare(strict_types=1);

namespace Pagemachine\AItools\ContextMenu\ItemProviders;

use Pagemachine\AItools\Service\SettingsService;
use TYPO3\CMS\Backend\ContextMenu\ItemProviders\AbstractProvider;
use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

class AiToolItemProvider extends AbstractProvider
{
    /**
     * @var File|Folder|null
     */
    protected mixed $record = null;

    /**
     * @var SettingsService
     */
    protected SettingsService $settingsService;

    /**
     * This array contains configuration for items you want to add
     * @var array
     */
    protected $itemsConfiguration = [
        'generateAIMetadata' => [
            'type' => 'item',
            'label' => 'Generate A.I. Metadata', // you can use "LLL:" syntax here
            'iconIdentifier' => 'actions-document-info',
            'callbackAction' => 'generateAIMetadata', //name of the function in the JS file
        ],
    ];

    public function __construct(string $table = '', string $identifier = '', string $context = '')
    {
        $version = GeneralUtility::makeInstance(VersionNumberUtility::class)->getNumericTypo3Version();
        if (version_compare($version, '12.0', '>=')) {
            // TYPO3 v12 or later
            // @phpstan-ignore-next-line
            parent::__construct();
        } else {
            // TYPO3 v11 or earlier
            // @phpstan-ignore-next-line
            parent::__construct($table, $identifier, $context);
        }

        $this->settingsService = GeneralUtility::makeInstance(SettingsService::class);
    }

    /**
     * @return bool
     */
    public function canHandle(): bool
    {
        return $this->table === 'sys_file';
    }

    /**
     * Initialize file object
     */
    protected function initialize()
    {
        parent::initialize();
        try {
            $this->record = GeneralUtility::makeInstance(ResourceFactory::class)->retrieveFileOrFolderObject($this->identifier);
        } catch (ResourceDoesNotExistException) {
            $this->record = null;
        }
    }

    /**
     * Returns the provider priority which is used for determining the order in which providers are processing items
     * to the result array. Highest priority means provider is evaluated first.
     *
     * This item provider should be called after PageProvider which has priority 100.
     *
     * BEWARE: Returned priority should logically not clash with another provider.
     *         Please check @see \TYPO3\CMS\Backend\ContextMenu\ContextMenu::getAvailableProviders() if needed.
     *
     * @return int
     */
    public function getPriority(): int
    {
        return 55;
    }

    /**
     * Registers the additional JavaScript RequireJS callback-module which will allow to display a notification
     * whenever the user tries to click on the "Hello World" item.
     * The method is called from AbstractProvider::prepareItems() for each context menu item.
     *
     * @param string $itemName
     * @return array
     */
    protected function getAdditionalAttributes(string $itemName): array
    {
        // TYPO3 version check
        $version = GeneralUtility::makeInstance(VersionNumberUtility::class)->getNumericTypo3Version();

        $attributes = [
            'data-callback-module' => '@pagemachine/aitools/context-menu-actions',
        ];
        if (version_compare($version, '11.0', '>=') && version_compare($version, '12.0', '<')) {
            // for TYPO3 v11
            $attributes = [
                'data-callback-module' => 'TYPO3/CMS/AiTools/ContextMenuActions',
            ];
        }

        if ($itemName === 'generateAIMetadata') {
            $attributes += [
                'data-identifier' => htmlspecialchars($this->identifier),
                'data-type' => $this->record instanceof File ? 'file' : 'folder',
                'data-filecontext-identifier' => htmlspecialchars($this->identifier),
                'data-filecontext-type' => $this->record instanceof File ? 'file' : 'folder',
            ];
        }

        return $attributes;
    }

    /**
     * This method is called for each item this provider adds and checks if given item can be added
     *
     * @param string $itemName
     * @param string $type
     * @return bool
     */
    protected function canRender(string $itemName, string $type): bool
    {
        // checking if item is disabled through TSConfig
        if (in_array($itemName, $this->disabledItems, true)) {
            return false;
        }
        $canRender = false;
        switch ($itemName) {
            case 'generateAIMetadata':
                $canRender = $this->canEditMetadataOfFile() || $this->canEditMetadataOfFolder();
                break;
        }
        return $canRender;
    }

    /**
     * @return bool
     */
    protected function isFile(): bool
    {
        return $this->record instanceof File;
    }

    protected function isFolder(): bool
    {
        return $this->record instanceof Folder;
    }

    protected function isImage(): bool
    {
        return $this->isFile() && $this->record->getType() == AbstractFile::FILETYPE_IMAGE;
    }

    protected function isUserAllowed(): bool
    {
        return $this->settingsService->checkPermission('generate_metadata');
    }

    protected function canEditMetadataOfFile(): bool
    {
        return $this->isImage()
            && $this->isUserAllowed()
            && $this->record->isIndexed()
            && $this->record->checkActionPermission('editMeta')
            && $this->record->getMetaData()->offsetExists('uid')
            && $this->backendUser->check('tables_modify', 'sys_file_metadata')
            && $this->backendUser->checkLanguageAccess(0);
    }

    protected function canEditMetadataOfFolder(): bool
    {
        return $this->isFolder()
            && $this->isUserAllowed()
            && $this->backendUser->check('tables_modify', 'sys_file_metadata')
            && $this->backendUser->checkLanguageAccess(0);
    }

    protected function getIdentifier(): string
    {
        if ($this->record == null) {
            return '';
        }
        return $this->record->getCombinedIdentifier();
    }
}
