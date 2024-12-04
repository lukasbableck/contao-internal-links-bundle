<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_settings']['fields']['internalLinkIgnoreElements'] = [
	'inputType' => 'text',
	'eval' => ['tl_class' => 'long clr', 'useRawRequestData' => true],
];
$GLOBALS['TL_DCA']['tl_settings']['fields']['internalLinkOccurrence'] = [
	'inputType' => 'select',
	'options' => ['first', 'last', 'all'],
	'eval' => ['tl_class' => 'w50'],
];
$GLOBALS['TL_DCA']['tl_settings']['fields']['internalLinkCaseSensitive'] = [
	'inputType' => 'checkbox',
	'eval' => ['tl_class' => 'w50 clr'],
];

PaletteManipulator::create()
	->addLegend('internal_links_legend', 'chmod_legend', PaletteManipulator::POSITION_AFTER)
	->addField('internalLinkIgnoreElements', 'internal_links_legend', PaletteManipulator::POSITION_APPEND)
	->addField('internalLinkOccurrence', 'internal_links_legend', PaletteManipulator::POSITION_APPEND)
	->addField('internalLinkCaseSensitive', 'internal_links_legend', PaletteManipulator::POSITION_APPEND)
	->applyToPalette('default', 'tl_settings')
;
