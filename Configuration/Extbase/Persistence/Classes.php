<?php

defined('TYPO3') or die();

return [
    \Pagemachine\AItools\Domain\Model\Server::class => [
        'subclasses' => [
            'custom' => \Pagemachine\AItools\Domain\Model\ServerCustom::class,
            'aigude' => \Pagemachine\AItools\Domain\Model\ServerAigude::class,
            'openai' => \Pagemachine\AItools\Domain\Model\ServerOpenai::class,
            'deepl' => \Pagemachine\AItools\Domain\Model\ServerDeepl::class,
        ],
    ],
    \Pagemachine\AItools\Domain\Model\ServerCustom::class => [
        'tableName' => 'tx_aitools_domain_model_server',
        'recordType' => 'custom',
    ],
    \Pagemachine\AItools\Domain\Model\ServerAigude::class => [
        'tableName' => 'tx_aitools_domain_model_server',
        'recordType' => 'aigude',
    ],
    \Pagemachine\AItools\Domain\Model\ServerOpenai::class => [
        'tableName' => 'tx_aitools_domain_model_server',
        'recordType' => 'openai',
    ],
    \Pagemachine\AItools\Domain\Model\ServerDeepl::class => [
        'tableName' => 'tx_aitools_domain_model_server',
        'recordType' => 'deepl',
    ],
];
