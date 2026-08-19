<?php
declare(strict_types=1);

namespace ProductManager\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ProductManager\Frontend\ProductSingle;
use ProductManager\Products\ProductQueryService;

final class Activator
{
    public static function activate(): void
    {
        $postTypeService = new PostTypeService();
        $postTypeService->register();

        $taxonomyService = new TaxonomyService();
        $taxonomyService->register();

        $capabilityService = new CapabilityService();
        $capabilityService->ensure_capabilities();

        $productSingle = new ProductSingle( new ProductQueryService() );
        $productSingle->register_rewrite_rule();

        flush_rewrite_rules();
    }
}
