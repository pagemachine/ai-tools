<?php

declare(strict_types = 1);

namespace Pagemachine\AItools\Hooks;

use TYPO3\CMS\Backend\Controller\BackendController;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class adds Filelist related JavaScript to the backend
 */
class BackendControllerHook
{
    /**
     * Adds Filelist JavaScript used e.g. by context menu
     *
     * @param array $configuration
     * @param BackendController $backendController
     * @throws RouteNotFoundException
     */
    public function addJavaScript(array $configuration, BackendController $backendController)
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);

        //$moduleUrl = $uriBuilder->buildUriFromRoute('aitools_ai_tools_images');
        //$pageRenderer->addInlineSetting('AItoolsImageDescribe', 'moduleUrl', $moduleUrl);
    }
}
