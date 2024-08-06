<?php
namespace Lukasbableck\ContaoInternalLinksBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Lukasbableck\ContaoInternalLinksBundle\Classes\InternalLinks;

class CallbackListener {
	public function __construct(private InternalLinks $internalLinks) {
	}

	#[AsCallback(table: 'tl_page', target: 'config.onsubmit')]
	public function onSubmit(DataContainer $dc): void {
		$this->internalLinks->buildIndex();
	}
}
