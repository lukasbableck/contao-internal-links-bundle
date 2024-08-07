<?php

use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_internal_link_index'] = [
	'config' => [
		'dataContainer' => DC_Table::class,
		'sql' => [
			'keys' => [
				'url' => 'primary',
				'rootPageID' => 'index',
			],
		],
	],
	'fields' => [
		'url' => [
			'sql' => 'text NULL',
		],
		'rootPageID' => [
			'sql' => "int(10) unsigned NOT NULL default '0'",
		],
		'keywords' => [
			'sql' => 'text NULL',
		],
		'nofollow' => [
			'sql' => "CHAR(1) DEFAULT '' NOT NULL",
		],
		'blank' => [
			'sql' => "CHAR(1) DEFAULT '' NOT NULL",
		],
	],
];
