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
		$page = $GLOBALS['objPage'];
		$rootPage = PageModel::findByPk($page->rootId);
		$index = InternalLinkIndexModel::findBy(['rootPageID=?'], [$rootPage->id]);
		if($page->disableInternalLinks || !$index) {
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

		$ignoreElements = Config::get('internalLinkIgnoreElements');
		$ignoreElements = explode('><', trim($ignoreElements, '<>'));

		libxml_use_internal_errors(true);
		$dom = new \DOMDocument();
		$dom->loadHTML(mb_encode_numericentity($buffer, [0x80, 0x10FFFF, 0, ~0], 'UTF-8'));
		$xpath = new \DOMXPath($dom);
		$nodes = $xpath->query('body//text()');
		foreach ($nodes as $node) {
			$parents = [];
			$parentNode = $node->parentNode;
			while ('#document' !== $parentNode->nodeName) {
				$parents[] = $parentNode->nodeName;
				$parentNode = $parentNode->parentNode;
			}
			foreach ($parents as $key => $parent) {
				if (\in_array($parent, $ignoreElements)) {
					continue 2;
				}
			}

			$flag = '';
			if (!Config::get('internalLinkCaseSensitive')) {
				$flag = 'i';
			}

			$element = preg_replace_callback('/\b('.implode('|', array_keys($keywords)).')\b/'.$flag, function ($matches) use ($keywords) {
				$keyword = $matches[1];

				if (!Config::get('internalLinkCaseSensitive')) {
					$keywords = array_change_key_case($keywords, \CASE_LOWER);
					$keyword = strtolower($keyword);
				}
				if (isset($keywords[$keyword])) {
					$newElement = new \DOMDocument();
					$link = $newElement->createElement('a', $matches[1]);
					$link->setAttribute('class', 'internal-link');
					$link->setAttribute('href', $keywords[$keyword]['url']);
					if ($keywords[$keyword]['nofollow']) {
						$link->setAttribute('rel', 'nofollow');
					}
					if ($keywords[$keyword]['blank']) {
						$link->setAttribute('target', '_blank');
						$link->setAttribute('rel', 'noopener');
					}
					if ($keywords[$keyword]['nofollow'] && $keywords[$keyword]['blank']) {
						$link->setAttribute('rel', 'nofollow noopener');
					}

					return $newElement->saveHTML($link);
				}

				return $matches[1];
			}, $node->textContent);

			$newElement = $dom->createDocumentFragment();
			$newElement->appendXML($element);
			$node->parentNode->replaceChild($newElement, $node);

			// really not sure if this is the best solution, but it works ¯\_(ツ)_/¯
		}

		return $dom->saveHTML();
	}
}
