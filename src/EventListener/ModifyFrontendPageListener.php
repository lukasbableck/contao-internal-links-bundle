<?php
namespace Lukasbableck\ContaoInternalLinksBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\PageModel;

#[AsHook('modifyFrontendPage')]
class ModifyFrontendPageListener {
	public function __invoke(string $buffer, string $templateName): string {
		$rootPage = PageModel::findByPk($GLOBALS['objPage']->rootId);
		
		return $buffer;
	}
}
