<?php

use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_internal_link_index'] = [
	'config' => [
		'dataContainer' => DC_Table::class,
		'sql' => [
			'keys' => [
				'id' => 'primary',
				'rootPageID' => 'index'
			],
		],
	],
	'palette' => [
		'default' => '{title_legend},rootPageID',
	],
	'fields' => [
		'id' => [
			'sql' => 'int(10) unsigned NOT NULL auto_increment',
		],
		'tstamp' => [
			'sql' => "int(10) unsigned NOT NULL default '0'",
		],
		'rootPageID' => [
			'sql' => "int(10) unsigned NOT NULL default '0'",
		],
		'url' => [
			'sql' => "text NULL",
		],
		'keywords' => [
			'sql' => "text NULL",
		],
	]
];