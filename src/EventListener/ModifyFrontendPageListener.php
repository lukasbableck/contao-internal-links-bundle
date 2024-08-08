<?php
namespace Lukasbableck\ContaoInternalLinksBundle\EventListener;

use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\PageModel;
use Contao\StringUtil;
use DOMDocument;
use Lukasbableck\ContaoInternalLinksBundle\Models\InternalLinkIndexModel;
use Symfony\Component\DomCrawler\Crawler;

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

		$crawler = new Crawler($buffer);

		$crawler->filter('body')->each(function (Crawler $node) use ($keywords) {
			$node->html($this->replaceKeywords($node->html(), $keywords));
		});

		$buffer = $crawler->html();

		return $buffer;
	}

	private function replaceKeywords(string $html, array $keywords): string {
		$forbidden_elements = Config::get('internalLinkIgnoreElements');
		$forbidden_elements .= '<script><style><link>';

		$case_sensitive = Config::get('internalLinkCaseSensitive');
		$mod = '';
		if (!$case_sensitive) {
			$mod = 'i';
		}

		$keywords = array_change_key_case($keywords, \CASE_LOWER);

		$dom = new DOMDocument();
		$dom->loadHTML($html);

		$xpath = new \DOMXPath($dom);
		$nodes = $xpath->query('//text()');

		foreach ($nodes as $node) {
			$node->nodeValue = preg_replace_callback('/\b('.implode('|', array_map('preg_quote', array_keys($keywords))).')\b/'.$mod, function ($matches) use ($keywords, $forbidden_elements) {
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
			}, $node->nodeValue);
		}

		return $dom->saveHTML();
	}
}
