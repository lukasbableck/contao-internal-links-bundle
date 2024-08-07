<?php
namespace Lukasbableck\ContaoInternalLinksBundle\Classes;

use Contao\PageModel;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;

class InternalLinks {
	public function __construct(private Connection $connection) {
	}

	public function buildIndex(): void {
		$pages = PageModel::findAll();
		$index = [];
		foreach ($pages as $page) {
			$page->loadDetails();
			if(!$page->published || ($page->start && $page->start > time() || ($page->stop && $page->stop < time()))) {
				continue;
			}
			$keywords = array_filter(StringUtil::deserialize($page->internalLinkKeywords));
			if ($page->internalLinkKeywords && sizeof($keywords) > 0) {
				$index[] = [
					'rootPageID' => $page->rootId,
					'url' => $page->getAbsoluteUrl(),
					'nofollow' => $page->internalLinkNoFollow,
					'keywords' => serialize($keywords),
					'blank' => $page->internalLinkBlank,
				];
			}
		}

		$this->saveIndex($index);
	}

	private function saveIndex(array $index): void {
		$connection = $this->connection;
		$connection->executeStatement('TRUNCATE TABLE tl_internal_link_index;');
		$query = 'INSERT INTO tl_internal_link_index (rootPageID, url, keywords, nofollow, blank) VALUES (?, ?, ?, ?, ?);';
		foreach ($index as $entry) {
			$connection->executeQuery($query, [
				$entry['rootPageID'],
				$entry['url'],
				$entry['keywords'],
				$entry['nofollow'],
				$entry['blank'],
			]);
		}
	}
}
