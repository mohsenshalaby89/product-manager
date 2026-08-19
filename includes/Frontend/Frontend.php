<?php
declare(strict_types=1);

namespace ProductManager\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ProductManager\Products\ProductQueryService;

final class Frontend
{
    private ProductSingle $productSingle;
    private CatalogShortcode $catalogShortcode;

    public function __construct( ProductQueryService $productQueryService )
    {
        $productCatalog = new ProductCatalog( $productQueryService );

        $this->productSingle = new ProductSingle( $productQueryService );
        $this->catalogShortcode = new CatalogShortcode( $productCatalog );
    }

    public function register(): void
    {
        $this->catalogShortcode->register();
        $this->productSingle->register_rewrite_rule();

        add_filter( 'query_vars', array( $this->productSingle, 'register_query_var' ) );
        add_filter( 'template_include', array( $this->productSingle, 'filter_template' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'conditionally_enqueue_assets' ) );
    }

    public function conditionally_enqueue_assets(): void
    {
        wp_register_style(
            'product-manager-frontend',
            plugins_url( 'assets/css/frontend.css', PRODUCT_MANAGER_PLUGIN_FILE ),
            array(),
            PRODUCT_MANAGER_VERSION
        );

        if ( $this->current_request_uses_catalog_shortcode() || $this->productSingle->is_product_request() ) {
            wp_enqueue_style( 'product-manager-frontend' );
        }
    }

    private function current_request_uses_catalog_shortcode(): bool
    {
        if ( is_admin() || ! is_singular() ) {
            return false;
        }

        $post = get_queried_object();

        if ( ! $post instanceof \WP_Post ) {
            return false;
        }

        return has_shortcode( (string) $post->post_content, 'product_manager_catalog' );
    }
}
