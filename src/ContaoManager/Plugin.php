<?php
namespace Lukasbableck\ContaoInternalLinksBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Lukasbableck\ContaoInternalLinksBundle\ContaoInternalLinksBundle;

class Plugin implements BundlePluginInterface {
	public function getBundles(ParserInterface $parser): array {
		return [BundleConfig::create(ContaoInternalLinksBundle::class)->setLoadAfter([ContaoCoreBundle::class])];
	}
}
