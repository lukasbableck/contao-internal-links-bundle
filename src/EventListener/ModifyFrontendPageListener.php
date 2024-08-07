<?php
namespace Lukasbableck\ContaoInternalLinksBundle\EventListener;

use Contao\Config;
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
					'blank' => $entry->blank,
				];
			}
		}

		$buffer = preg_replace_callback('/<body[^>]*>(.*?)<\/body>/s', function ($matches) use ($keywords) {
			$forbidden_elements = Config::get('internalLinkIgnoreElements');
			$forbidden_elements .= '<script><style><link>';

			$case_sensitive = Config::get('internalLinkCaseSensitive');
			$mod = '';
			if (!$case_sensitive) {
				$mod = 'i';
			}

			$matches[1] = preg_replace_callback('/\b('.implode('|', array_map('preg_quote', array_keys($keywords))).')\b/'.$mod, function ($matches) use ($keywords, $forbidden_elements) {
				$keywords = array_change_key_case($keywords, CASE_LOWER);
				$keyword = strtolower($matches[0]);
				$link = $keywords[$keyword];
				$attr = '';
				if ($link['nofollow']) {
					$attr = ' rel="nofollow';
					if ($link['blank']) {
						$attr .= ' noopener';
					}
					$attr .= '"';
				}
				if ($link['blank']) {
					$attr .= ' target="_blank"';
				}

				if (preg_match('/<('.$forbidden_elements.')[^>]*>.*'.$keyword.'.*<\/\1>/', $matches[0])) {
					return $matches[0];
				}

				return \sprintf('<a href="%s"%s>%s</a>', $link['url'], $attr, $matches[0]);
			}, $matches[1]);

			return '<body>'.$matches[1].'</body>';
		}, $buffer);

		return $buffer;
	}
}
