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

		$ignoreElements = explode('><', trim(Config::get('internalLinkIgnoreElements'), '<>'));

		libxml_use_internal_errors(true);
		$dom = new \DOMDocument();
		$dom->loadHTML($buffer);
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
					$link->setAttribute('href', $keywords[$keyword]['url']);
					if ($keywords[$keyword]['nofollow']) {
						$link->setAttribute('rel', 'nofollow');
					}
					if ($keywords[$keyword]['blank']) {
						$link->setAttribute('target', '_blank');
						$link->setAttribute('rel', 'noopener');
					}

					return $newElement->saveHTML($link);
				}

				return $matches[1];
			}, $node->data);

			$newElement = $dom->createDocumentFragment();
			$newElement->appendXML($element);
			$node->parentNode->replaceChild($newElement, $node);

			// really not sure if this is the best solution, but it works ¯\_(ツ)_/¯
		}

		return $dom->saveHTML();
	}
}
