<?php
namespace Lukasbableck\ContaoInternalLinksBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\PageModel;
use Contao\StringUtil;
use Lukasbableck\ContaoInternalLinksBundle\Models\InternalLinkIndexModel;

#[AsHook('modifyFrontendPage')]
class ModifyFrontendPageListener {
	public function __invoke(string $buffer, string $templateName): string {
		$rootPage = PageModel::findByPk($GLOBALS['objPage']->rootId);
		$index = InternalLinkIndexModel::findBy(['rootPageID=?'], [$rootPage->id]);
		if (!$index) {
			return $buffer;
		}

		$keywords = [];
		foreach ($index as $entry) {
			$kw = StringUtil::deserialize($entry->keywords);
			foreach ($kw as $keyword) {
				$keywords[$keyword] = $entry->url;
			}
		}

		$buffer = preg_replace_callback('/\b(' . implode('|', array_map('preg_quote', array_keys($keywords))) . ')\b/', function($matches) use ($keywords) {
			return sprintf('<a href="%s">%s</a>', $keywords[$matches[0]], $matches[0]);
		}, $buffer);

		return $buffer;
	}
}
