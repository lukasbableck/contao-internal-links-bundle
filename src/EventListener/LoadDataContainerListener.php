<?php
namespace Lukasbableck\ContaoInternalLinksBundle\EventListener;

use Composer\InstalledVersions;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;

#[AsHook('loadDataContainer')]
class LoadDataContainerListener {
	public function __invoke(string $table): void {
		if (!\in_array($table, ['tl_calendar_events', 'tl_faq', 'tl_news', 'tl_page'])) {
			return;
		}
		$GLOBALS['TL_DCA'][$table]['fields']['internalLinkKeywords'] = [
			'label' => &$GLOBALS['TL_LANG']['internal_links']['internalLinkKeywords'],
			'exclude' => true,
			'inputType' => 'listWizard',
			'eval' => ['tl_class' => 'w50 clr'],
			'sql' => 'blob NULL',
		];
		$GLOBALS['TL_DCA'][$table]['fields']['internalLinkNoFollow'] = [
			'label' => &$GLOBALS['TL_LANG']['internal_links']['internalLinkNoFollow'],
			'exclude' => true,
			'inputType' => 'checkbox',
			'eval' => ['tl_class' => 'w50 clr'],
			'sql' => "char(1) NOT NULL default ''",
		];
		$GLOBALS['TL_DCA'][$table]['fields']['internalLinkBlank'] = [
			'label' => &$GLOBALS['TL_LANG']['internal_links']['internalLinkBlank'],
			'exclude' => true,
			'inputType' => 'checkbox',
			'eval' => ['tl_class' => 'w50'],
			'sql' => "char(1) NOT NULL default ''",
		];

		$legend = 'protected_legend';
		if ('tl_calendar_events' == $table) {
			$legend = 'details_legend';
		} elseif ('tl_faq' == $table) {
			$legend = 'answer_legend';
		} elseif ('tl_news' == $table) {
			$legend = 'teaser_legend';
		}

		$paletteManipulator = PaletteManipulator::create()
			->addLegend('internal_links_legend', $legend, PaletteManipulator::POSITION_BEFORE)
			->addField('internalLinkKeywords', 'internal_links_legend', PaletteManipulator::POSITION_APPEND)
			->addField('internalLinkNoFollow', 'internal_links_legend', PaletteManipulator::POSITION_APPEND)
			->addField('internalLinkBlank', 'internal_links_legend', PaletteManipulator::POSITION_APPEND)
		;
		switch ($table) {
			case 'tl_calendar_events':
				if (InstalledVersions::isInstalled('contao/calendar-bundle')) {
					$paletteManipulator
						->applyToPalette('default', 'tl_calendar_events')
						->applyToPalette('internal', 'tl_calendar_events')
						->applyToPalette('external', 'tl_calendar_events')
						->applyToPalette('article', 'tl_calendar_events')
					;
				}
				break;
			case 'tl_faq':
				if (InstalledVersions::isInstalled('contao/faq-bundle')) {
					$paletteManipulator
						->applyToPalette('default', 'tl_faq')
					;
				}
				break;
			case 'tl_news':
				if (InstalledVersions::isInstalled('contao/news-bundle')) {
					$paletteManipulator
						->applyToPalette('default', 'tl_news')
						->applyToPalette('internal', 'tl_news')
						->applyToPalette('external', 'tl_news')
						->applyToPalette('article', 'tl_news')
					;
				}
				break;
			case 'tl_page':
				$GLOBALS['TL_DCA'][$table]['fields']['disableInternalLinks'] = [
					'label' => &$GLOBALS['TL_LANG']['internal_links']['disableInternalLinks'],
					'exclude' => true,
					'inputType' => 'checkbox',
					'eval' => ['tl_class' => 'w50 clr'],
					'sql' => "char(1) NOT NULL default ''",
				];

				$paletteManipulator = PaletteManipulator::create()
					->addLegend('internal_links_legend', $legend, PaletteManipulator::POSITION_BEFORE)
					->addField('disableInternalLinks', 'internal_links_legend', PaletteManipulator::POSITION_APPEND)
					->addField('internalLinkKeywords', 'internal_links_legend', PaletteManipulator::POSITION_APPEND)
					->addField('internalLinkNoFollow', 'internal_links_legend', PaletteManipulator::POSITION_APPEND)
					->addField('internalLinkBlank', 'internal_links_legend', PaletteManipulator::POSITION_APPEND)
				;

				$paletteManipulator
					->applyToPalette('regular', 'tl_page')
					->applyToPalette('forward', 'tl_page')
					->applyToPalette('redirect', 'tl_page')
				;
				break;
		}
	}
}
