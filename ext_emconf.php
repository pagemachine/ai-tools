<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'AI Tools',
    'description' => 'Use A.I. Tools in the TYPO3 backend. Generate Images, Image Variants and Describe Images.',
    'category' => 'module',
    'author' => 'Sascha Löffler',
    'author_email' => 'sloeffler@pagemachine.de',
    'author_company' => 'Pagemachine AG',
    'state' => 'alpha',
    'version' => '0.0.9',
    'constraints' => [
        'depends' => [
            'typo3' => '11.0.0-12.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
