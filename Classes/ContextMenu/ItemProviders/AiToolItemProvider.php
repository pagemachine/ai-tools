<?php

declare(strict_types=1);

namespace Pagemachine\AItools\ContextMenu\ItemProviders;

use Pagemachine\AItools\Service\SettingsService;
use TYPO3\CMS\Backend\ContextMenu\ItemProviders\AbstractProvider;
use TYPO3\CMS\Core\Resource\FileType;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
            'label' => 'Generate A.I. Metadata',
            'iconIdentifier' => 'actions-document-info',
            'callbackAction' => 'generateAIMetadata',
        ],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->settingsService = GeneralUtility::makeInstance(SettingsService::class);
    }

    public function canHandle(): bool
    {
        return $this->table === 'sys_file';
    }

    protected function initialize()
    {
        parent::initialize();
        try {
            $this->record = GeneralUtility::makeInstance(ResourceFactory::class)->retrieveFileOrFolderObject($this->identifier);
        } catch (ResourceDoesNotExistException) {
            $this->record = null;
        }
    }

    public function getPriority(): int
    {
        return 55;
    }

    protected function getAdditionalAttributes(string $itemName): array
    {
        $attributes = [
            'data-callback-module' => '@pagemachine/ai-tools/ContextMenuActions',
        ];

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

    protected function canRender(string $itemName, string $type): bool
    {
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
        return $this->isFile() && $this->record->getType() == FileType::IMAGE->value;
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
