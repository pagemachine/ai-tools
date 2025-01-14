<?php

use Pagemachine\AItools\Controller\Backend\CreditsController;
use Pagemachine\AItools\Controller\Backend\ImageRecognizeController;
use Pagemachine\AItools\Controller\Backend\ImageLabelController;

/**
 * Definitions for routes provided by EXT:backend
 * Contains all "regular" routes for entry points
 *
 * Please note that this setup is preliminary until all core use-cases are set up here.
 * Especially some more properties regarding modules will be added until TYPO3 CMS 7 LTS, and might change.
 *
 * Currently the "access" property is only used so no token creation + validation is made,
 * but will be extended further.
 */
return [
    //
    'aitools_ai_tools_images' => [
        'path' => '/aitoolsimages/metagen',
        'target' => ImageRecognizeController::class . '::ajaxMetaGenerateAction',
    ],
    'aitools_ai_tools_credits' => [
        'path' => '/aitools/credits',
        'target' => CreditsController::class . '::ajaxCreditsAction',
    ],
    'aitools_ai_tools_badwords' => [
        'path' => '/aitools/badwords',
        'target' => ImageLabelController::class . '::ajaxhandleBadwordAction',
    ],
];
