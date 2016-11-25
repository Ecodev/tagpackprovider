<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Tag Pack-based Data Provider - Tesseract project',
    'description' => 'This Data Provider relies on tags from extension Tag Pack to provide lists of items. More info on http://www.typo3-tesseract.com/',
    'category' => 'services',
    'version' => '2.0.1',
    'state' => 'stable',
    'author' => 'Ecodev',
    'author_email' => 'contact@ecodev.ch',
    'author_company' => 'Ecodev',
    'constraints' => [
        'depends' => [
            'tagpack' => '',
            'typo3' => '6.2.0-0.0.0',
            'tesseract' => '1.0.0-0.0.0',
            'expressions' => '',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
