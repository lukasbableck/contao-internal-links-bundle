<?php
namespace Lukasbableck\ContaoInternalLinksBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Lukasbableck\ContaoInternalLinksBundle\Classes\InternalLinks;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CallbackListener {
	private static bool $updateIndex = false;

	public function __construct(private InternalLinks $internalLinks) {
	}

	#[AsCallback(table: 'tl_calendar_events', target: 'config.ondelete')]
	#[AsCallback(table: 'tl_faq', target: 'config.ondelete')]
	#[AsCallback(table: 'tl_news', target: 'config.ondelete')]
	#[AsCallback(table: 'tl_page', target: 'config.ondelete')]
	public function onDelete(DataContainer $dc, int $undoId): void {
		self::$updateIndex = true;
	}

	#[AsCallback(table: 'tl_calendar_events', target: 'config.oninvalidate_cache_tags')]
	#[AsCallback(table: 'tl_faq', target: 'config.oninvalidate_cache_tags')]
	#[AsCallback(table: 'tl_news', target: 'config.oninvalidate_cache_tags')]
	#[AsCallback(table: 'tl_page', target: 'config.oninvalidate_cache_tags')]
	public function onInvalidateCacheTags(DataContainer $dc, array $tags): array {
		self::$updateIndex = true;

		return $tags;
	}

	#[AsEventListener(KernelEvents::TERMINATE)]
	public function onTerminate(KernelEvent $event): void {
		if (self::$updateIndex) {
			$this->internalLinks->buildIndex();
		}
	}
}
