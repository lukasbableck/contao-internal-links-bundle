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
				$keywords[$keyword] = [
					'url' => $entry->url,
					'nofollow' => $entry->nofollow,
					'blank' => $entry->blank
				];
			}
		}

		$buffer = preg_replace_callback('/<body[^>]*>(.*?)<\/body>/s', function($matches) use ($keywords) {
			$matches[1] = preg_replace_callback('/\b(' . implode('|', array_map('preg_quote', array_keys($keywords))) . ')\b/', function($matches) use ($keywords) {
				$link = $keywords[$matches[0]];
				$attr = '';
				if ($link['nofollow']) {
					$attr = ' rel="nofollow"';
				}
				if ($link['blank']) {
					$attr .= ' target="_blank"';
				}

				return sprintf('<a href="%s"%s>%s</a>', $link['url'], $attr, $matches[0]);
			}, $matches[1]);
			return '<body>' . $matches[1] . '</body>';
		}, $buffer);

		return $buffer;
	}
}
