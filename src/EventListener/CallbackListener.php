<?php
namespace Lukasbableck\ContaoInternalLinksBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Lukasbableck\ContaoInternalLinksBundle\Classes\InternalLinks;

class CallbackListener {
	public function __construct(private InternalLinks $internalLinks) {
	}

	#[AsCallback(table: 'tl_page', target: 'config.ondelete')]
	public function onDelete(DataContainer $dc, int $undoId): void {
		if (!$dc->id) {
			return;
		}
		$this->internalLinks->buildIndex(); // TODO: this does not work yet, because the record is not deleted at this point
	}

	#[AsCallback(table: 'tl_page', target: 'config.oninvalidate_cache_tags')]
	public function onInvalidateCacheTags(DataContainer $dc, array $tags): array {
		$this->internalLinks->buildIndex();

		return $tags;
	}
}
