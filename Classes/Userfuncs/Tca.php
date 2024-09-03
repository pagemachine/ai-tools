<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Userfuncs;

use TYPO3\CMS\Backend\Utility\BackendUtility;

class Tca
{
    public function promptTitle(&$parameters)
    {
        $record = BackendUtility::getRecord($parameters['table'], $parameters['row']['uid']);
        $newTitle = $record['description'];
        if ($record['default']) {
            $newTitle = '🏠 '.$newTitle;
        }
        $parameters['title'] = $newTitle;
    }
}
