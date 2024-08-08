<?php
namespace Lukasbableck\ContaoInternalLinksBundle\Classes;

use Composer\InstalledVersions;
use Contao\CalendarEventsModel;
use Contao\FaqModel;
use Contao\NewsModel;
use Contao\PageModel;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;

class InternalLinks {
	public function __construct(private Connection $connection) {
	}

	public function buildIndex(): void {
		$index = [];
		$this->indexPages($index);

		if (InstalledVersions::isInstalled('contao/calendar-bundle')) {
			$this->indexCalendarEvents($index);
		}
		if (InstalledVersions::isInstalled('contao/faq-bundle')) {
			$this->indexFAQ($index);
		}
		if (InstalledVersions::isInstalled('contao/news-bundle')) {
			$this->indexNews($index);
		}

		$this->saveIndex($index);
	}

	private function indexCalendarEvents(array &$index): void {
		$calendarEvents = CalendarEventsModel::findAll();
		foreach ($calendarEvents as $calendarEvent) {
			$calendar = $calendarEvent->getRelated('pid');
			if (!$calendarEvent->published || ($calendarEvent->start && $calendarEvent->start > time() || ($calendarEvent->stop && $calendarEvent->stop < time()))) {
				continue;
			}

			$page = $calendar->getRelated('jumpTo');
			$this->addToIndex($page, $page->getAbsoluteUrl('/'.$calendarEvent->alias), $calendarEvent->internalLinkKeywords, $calendarEvent->internalLinkNoFollow, $calendarEvent->internalLinkBlank, $index);
		}
	}

	private function indexFAQ(array &$index): void {
		$faq = FaqModel::findAll();
		foreach ($faq as $faqItem) {
			$faqArchive = $faqItem->getRelated('pid');
			if (!$faqArchive->jumpTo) {
				continue;
			}
			if (!$faqItem->published || ($faqItem->start && $faqItem->start > time() || ($faqItem->stop && $faqItem->stop < time()))) {
				continue;
			}
			$page = $faqArchive->getRelated('jumpTo');
			$this->addToIndex($page, $page->getAbsoluteUrl('/'.$faqItem->alias), $faqItem->internalLinkKeywords, $faqItem->internalLinkNoFollow, $faqItem->internalLinkBlank, $index);
		}
	}

	private function indexNews(array &$index): void {
		$news = NewsModel::findAll();
		foreach ($news as $newsItem) {
			$newsArchive = $newsItem->getRelated('pid');
			if (!$newsItem->published || ($newsItem->start && $newsItem->start > time() || ($newsItem->stop && $newsItem->stop < time()))) {
				continue;
			}

			$page = $newsArchive->getRelated('jumpTo');
			$this->addToIndex($page, $page->getAbsoluteUrl('/'.$newsItem->alias), $newsItem->internalLinkKeywords, $newsItem->internalLinkNoFollow, $newsItem->internalLinkBlank, $index);
		}
	}

	private function indexPages(array &$index): void {
		$pages = PageModel::findAll();
		foreach ($pages as $page) {
			$this->addToIndex($page, $page->getAbsoluteUrl(), $page->internalLinkKeywords, $page->internalLinkNoFollow, $page->internalLinkBlank, $index);
		}
	}

	private function addToIndex(PageModel $page, string $url, ?string $keywords, string $nofollow, string $blank, array &$index): void {
		$page->loadDetails();
		if (!$page->published || ($page->start && $page->start > time() || ($page->stop && $page->stop < time()))) {
			return;
		}
		$keywords = array_filter(StringUtil::deserialize($page->internalLinkKeywords) ?? []);
		if ($page->internalLinkKeywords && \count($keywords) > 0) {
			$index[] = [
				'rootPageID' => $page->rootId,
				'url' => $url,
				'keywords' => serialize($keywords),
				'nofollow' => $nofollow,
				'blank' => $blank,
			];
		}
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
