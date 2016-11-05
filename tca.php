<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$TCA['tx_tagpackprovider_selections'] = [
    'ctrl' => $TCA['tx_tagpackprovider_selections']['ctrl'],
    'interface' => [
        'showRecordFieldList' => 'name,tables,tags',
    ],
    'feInterface' => $TCA['tx_tagpackprovider_selections']['feInterface'],
    'columns' => [
        'name' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:tagpackprovider/locallang_db.xml:tx_tagpackprovider_selections.name',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'required,trim',
            ],
        ],
        'tables' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:tagpackprovider/locallang_db.xml:tx_tagpackprovider_selections.tables',
            'config' => [
                'type' => 'select',
                'itemsProcFunc' => 'tx_tagpackprovider_tca->getListOfTables',
                'size' => 5,
                'maxitems' => 10,
            ],
        ],
        'tags' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:tagpackprovider/locallang_db.xml:tx_tagpackprovider_selections.tags',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_tagpack_tags',
                'size' => 5,
                'minitems' => 0,
                'maxitems' => 1000,
            ],
        ],
        'tag_expressions' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:tagpackprovider/locallang_db.xml:tx_tagpackprovider_selections.tag_expressions',
            'config' => [
                'type' => 'text',
                'cols' => 50,
                'rows' => 5,
            ],
        ],
        'logical_operator' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:tagpackprovider/locallang_db.xml:tx_tagpackprovider_selections.logical_operator',
            'config' => [
                'type' => 'radio',
                'default' => 'OR',
                'items' => [
                        ['LLL:EXT:tagpackprovider/locallang_db.xml:tx_tagpackprovider_selections.logical_operator.I.0', 'AND'],
                        ['LLL:EXT:tagpackprovider/locallang_db.xml:tx_tagpackprovider_selections.logical_operator.I.1', 'OR'],
                ],
            ],
        ],
        'tags_override' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:tagpackprovider/locallang_db.xml:tx_tagpackprovider_selections.tags_override',
            'config' => [
                'type' => 'check',
                'default' => 1,
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'name;;;;1-1-1, tables;;;;2-2-2, tags;;;;3-3-3, tag_expressions;;1, logical_operator'],
    ],
    'palettes' => [
        '1' => ['showitem' => 'tags_override'],
    ],
];
