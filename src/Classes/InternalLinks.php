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

		$index = array_merge($index, $this->indexPages());

		if (InstalledVersions::isInstalled('contao/calendar-bundle')) {
			$index = array_merge($index, $this->indexCalendarEvents());
		}
		if (InstalledVersions::isInstalled('contao/faq-bundle')) {
			$index = array_merge($index, $this->indexFAQ());
		}
		if (InstalledVersions::isInstalled('contao/news-bundle')) {
			$index = array_merge($index, $this->indexNews());
		}

		$this->saveIndex($index);
	}

	private function indexCalendarEvents(): array {
		$index = [];
		$calendarEvents = CalendarEventsModel::findAll();
		foreach ($calendarEvents as $calendarEvent) {
			$calendarEvent->loadDetails();
			$calendar = $calendarEvent->getRelated('pid');
			$page = $calendar->getRelated('jumpTo');
			$page->loadDetails();
			if (!$calendarEvent->published || ($calendarEvent->start && $calendarEvent->start > time() || ($calendarEvent->stop && $calendarEvent->stop < time()))) {
				continue;
			}
			if (!$page->published || ($page->start && $page->start > time() || ($page->stop && $page->stop < time()))) {
				continue;
			}
			$keywords = array_filter(StringUtil::deserialize($calendarEvent->internalLinkKeywords) ?? []);
			if ($calendarEvent->internalLinkKeywords && \count($keywords) > 0) {
				$index[] = [
					'rootPageID' => $page->rootId,
					'url' => $page->getAbsoluteUrl($calendarEvent->alias),
					'nofollow' => $calendarEvent->internalLinkNoFollow,
					'keywords' => serialize($keywords),
					'blank' => $calendarEvent->internalLinkBlank,
				];
			}
		}

		return $index;
	}

	private function indexFAQ(): array {
		$index = [];
		$faq = FaqModel::findAll();
		foreach ($faq as $faqItem) {
			$faqItem->loadDetails();
			if (!$faqItem->published || ($faqItem->start && $faqItem->start > time() || ($faqItem->stop && $faqItem->stop < time()))) {
				continue;
			}
			$keywords = array_filter(StringUtil::deserialize($faqItem->internalLinkKeywords) ?? []);
			if ($faqItem->internalLinkKeywords && \count($keywords) > 0) {
				$index[] = [
					'rootPageID' => $faqItem->rootId, // TODO: this doesnt work like that here
					'url' => $faqItem->getAbsoluteUrl(), // TODO: this doesnt work like that here
					'nofollow' => $faqItem->internalLinkNoFollow,
					'keywords' => serialize($keywords),
					'blank' => $faqItem->internalLinkBlank,
				];
			}
		}

		return $index;
	}

	private function indexNews(): array {
		$index = [];
		$news = NewsModel::findAll();
		foreach ($news as $newsItem) {
			$newsItem->loadDetails();
			$newsArchive = $newsItem->getRelated('pid');
			$page = $newsArchive->getRelated('jumpTo');
			$page->loadDetails();
			if (!$newsItem->published || ($newsItem->start && $newsItem->start > time() || ($newsItem->stop && $newsItem->stop < time()))) {
				continue;
			}
			if (!$page->published || ($page->start && $page->start > time() || ($page->stop && $page->stop < time()))) {
				continue;
			}
			$keywords = array_filter(StringUtil::deserialize($newsItem->internalLinkKeywords) ?? []);
			if ($newsItem->internalLinkKeywords && \count($keywords) > 0) {
				$index[] = [
					'rootPageID' => $page->rootId,
					'url' => $page->getAbsoluteUrl($newsItem->alias),
					'nofollow' => $newsItem->internalLinkNoFollow,
					'keywords' => serialize($keywords),
					'blank' => $newsItem->internalLinkBlank,
				];
			}
		}

		return $index;
	}

	private function indexPages(): array {
		$index = [];
		$pages = PageModel::findAll();
		foreach ($pages as $page) {
			$page->loadDetails();
			if (!$page->published || ($page->start && $page->start > time() || ($page->stop && $page->stop < time()))) {
				continue;
			}
			$keywords = array_filter(StringUtil::deserialize($page->internalLinkKeywords) ?? []);
			if ($page->internalLinkKeywords && \count($keywords) > 0) {
				$index[] = [
					'rootPageID' => $page->rootId,
					'url' => $page->getAbsoluteUrl(),
					'nofollow' => $page->internalLinkNoFollow,
					'keywords' => serialize($keywords),
					'blank' => $page->internalLinkBlank,
				];
			}
		}

		return $index;
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
