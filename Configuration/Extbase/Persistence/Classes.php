<?php

defined('TYPO3') or die();

return [
    \Pagemachine\AItools\Domain\Model\Server::class => [
        'subclasses' => [
            'aigude' => \Pagemachine\AItools\Domain\Model\ServerAigude::class,
        ],
    ],
    \Pagemachine\AItools\Domain\Model\ServerAigude::class => [
        'tableName' => 'tx_aitools_domain_model_server',
        'recordType' => 'aigude',
    ],
];
