<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_settings']['fields']['internalLinkIgnoreElements'] = [
	'inputType' => 'text',
	'eval' => ['tl_class' => 'long clr', 'maxlength' => 255],
	'sql' => "text NOT NULL default '<h1><h2><h3><h4><h5><h6><a><button>'",
];
$GLOBALS['TL_DCA']['tl_settings']['fields']['internalLinkCaseSensitive'] = [
	'inputType' => 'checkbox',
	'eval' => ['tl_class' => 'w50 clr'],
	'sql' => "char(1) NOT NULL default ''",
];

PaletteManipulator::create()
	->addLegend('internal_links_legend', 'chmod_legend', PaletteManipulator::POSITION_AFTER)
	->addField('internalLinkIgnoreElements', 'internal_links_legend', PaletteManipulator::POSITION_APPEND)
	->addField('internalLinkCaseSensitive', 'internal_links_legend', PaletteManipulator::POSITION_APPEND)
	->applyToPalette('default', 'tl_settings')
;
