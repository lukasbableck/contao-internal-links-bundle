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
			if ($page->internalLinkKeywords) {
				$index[] = [
					'rootPageID' => $page->rootId,
					'url' => $page->getAbsoluteUrl(),
					'keywords' => $page->internalLinkKeywords,
				];
			}
		}

		$this->saveIndex($index);
	}

	private function saveIndex(array $index): void {
		$connection = $this->connection;
		$connection->executeStatement('TRUNCATE TABLE tl_internal_link_index;');
		$query = 'INSERT INTO tl_internal_link_index (rootPageID, url, keywords) VALUES (?, ?, ?);';
		foreach ($index as $entry) {
			$connection->executeQuery($query, [
				$entry['rootPageID'],
				$entry['url'],
				$entry['keywords']
			]);
		}
	}
}
