<?php
namespace Lukasbableck\ContaoInternalLinksBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ContaoInternalLinksBundle extends Bundle {
	public function getPath(): string {
		return \dirname(__DIR__);
	}
}
