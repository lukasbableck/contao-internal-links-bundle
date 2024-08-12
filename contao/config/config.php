<?php

use Lukasbableck\ContaoInternalLinksBundle\Models\InternalLinkIndexModel;

$GLOBALS['TL_MODELS']['tl_internal_link_index'] = InternalLinkIndexModel::class;

$GLOBALS['TL_CONFIG']['internalLinkIgnoreElements'] = '<h1><h2><h3><h4><h5><h6><a><button><header><footer><script><style>';