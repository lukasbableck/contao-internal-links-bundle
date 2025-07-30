<?php
namespace Lukasbableck\ContaoInternalLinksBundle\EventListener;

use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\StringUtil;
use Lukasbableck\ContaoInternalLinksBundle\Models\InternalLinkIndexModel;

#[AsHook('modifyFrontendPage')]
class ModifyFrontendPageListener {
	public function __invoke(string $buffer, string $templateName): string {
		$page = $GLOBALS['objPage'];
		$layout = LayoutModel::findByPk($page->layout);
		$pageTemplate = $layout->template;
		if ($templateName !== $pageTemplate) {
			return $buffer;
		}

		$rootPage = PageModel::findByPk($page->rootId);
		$index = InternalLinkIndexModel::findBy(['rootPageID=?'], [$rootPage->id]);
		if ($page->disableInternalLinks || !$index) {
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

		$occurrence = Config::get('internalLinkOccurrence');

		$ignoreElements = Config::get('internalLinkIgnoreElements');
		$ignoreElements = explode('><', trim($ignoreElements, '<>'));

		libxml_use_internal_errors(true);
		$dom = new \DOMDocument();
		$dom->loadHTML(mb_encode_numericentity($buffer, [0x80, 0x10FFFF, 0, ~0], 'UTF-8'));
		$xpath = new \DOMXPath($dom);
		$nodes = $xpath->query('body//text()');
		$replaced = [];
		if ('occurrence_last' === $occurrence) {
			$nodes = array_reverse(iterator_to_array($nodes));
		}
		foreach ($nodes as $node) {
			$parents = [];
			$parentNode = $node->parentNode;
			while ('#document' !== $parentNode->nodeName) {
				$parents[] = $parentNode->nodeName;
				$parentNode = $parentNode->parentNode;
			}
			foreach ($parents as $parent) {
				if (\in_array($parent, $ignoreElements)) {
					continue 2;
				}
			}

			$flag = '';
			if (!Config::get('internalLinkCaseSensitive')) {
				$flag = 'i';
			}

			$element = null;

			switch ($occurrence) {
				case 'occurrence_first':
					foreach ($keywords as $keyword => $value) {
						if (\in_array($keyword, $replaced)) {
							continue;
						}
						if (!Config::get('internalLinkCaseSensitive')) {
							$pos = stripos($node->textContent, $keyword);
						} else {
							$pos = strpos($node->textContent, $keyword);
						}
						if (false !== $pos) {
							$nextChar = $node->textContent[$pos + \strlen($keyword)] ?? '';
							if (preg_match('/\w/', $nextChar)) {
								continue;
							}

							$word = substr($node->textContent, $pos, \strlen($keyword));
							$link = $this->buildLink($word, $value);
							$element = substr_replace($node->textContent, $link, $pos, \strlen($keyword));
							$replaced[] = $keyword;
						}
					}
					break;
				case 'occurrence_last':
					foreach ($keywords as $keyword => $value) {
						if (\in_array($keyword, $replaced)) {
							continue;
						}
						if (!Config::get('internalLinkCaseSensitive')) {
							$pos = strripos($node->textContent, $keyword);
						} else {
							$pos = strrpos($node->textContent, $keyword);
						}
						if (false !== $pos) {
							$nextChar = $node->textContent[$pos + \strlen($keyword)] ?? '';
							if (preg_match('/\w/', $nextChar)) {
								continue;
							}

							$word = substr($node->textContent, $pos, \strlen($keyword));
							$link = $this->buildLink($word, $value);
							$element = substr_replace($node->textContent, $link, $pos, \strlen($keyword));
							$replaced[] = $keyword;
						}
					}
					break;
				case 'occurrence_all':
					$element = preg_replace_callback('/\b('.implode('|', array_keys($keywords)).')\b/'.$flag, function ($matches) use ($keywords) {
						$keyword = $matches[1];

						if (!Config::get('internalLinkCaseSensitive')) {
							$keywords = array_change_key_case($keywords, \CASE_LOWER);
							$keyword = strtolower($keyword);
						}
						if (isset($keywords[$keyword])) {
							return $this->buildLink($keyword, $keywords[$keyword]);
						}

						return $keyword;
					}, $node->textContent);
			}

			if (!$element) {
				continue;
			}

			$newElement = $dom->createDocumentFragment();
			$newElement->appendXML($element);
			$node->parentNode->replaceChild($newElement, $node);
		}

		return $dom->saveHTML();
	}

	private function buildLink($keyword, $value) {
		$newElement = new \DOMDocument();
		$link = $newElement->createElement('a', $keyword);
		$link->setAttribute('class', 'internal-link');
		$link->setAttribute('href', $value['url']);
		if ($value['nofollow']) {
			$link->setAttribute('rel', 'nofollow');
		}
		if ($value['blank']) {
			$link->setAttribute('target', '_blank');
			$link->setAttribute('rel', 'noopener');
		}
		if ($value['nofollow'] && $value['blank']) {
			$link->setAttribute('rel', 'nofollow noopener');
		}

		return $newElement->saveHTML($link);
	}
}
