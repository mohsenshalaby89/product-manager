<?php
declare(strict_types=1);

namespace ProductManager\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CatalogShortcode
{
    private ProductCatalog $productCatalog;

    public function __construct( ProductCatalog $productCatalog )
    {
        $this->productCatalog = $productCatalog;
    }

    public function register(): void
    {
        add_shortcode( 'product_manager_catalog', array( $this, 'render' ) );
    }

    public function render( array $atts = array() ): string
    {
        $defaults = $this->get_default_attributes();
        $atts = shortcode_atts(
            $defaults,
            $atts,
            'product_manager_catalog'
        );

        return $this->productCatalog->render(
            array(
                'category' => $this->sanitize_category( $atts['category'] ),
                'search' => sanitize_text_field( wp_unslash( (string) $atts['search'] ) ),
                'per_page' => max( 1, min( 48, absint( $atts['per_page'] ) ) ),
                'orderby' => $this->sanitize_orderby( (string) $atts['orderby'] ),
                'order' => $this->sanitize_order( (string) $atts['order'] ),
            )
        );
    }

    private function get_default_attributes(): array
    {
        $settings = get_option( 'product_manager_settings', array() );

        if ( ! is_array( $settings ) ) {
            $settings = array();
        }

        return array(
            'category' => '',
            'search' => '',
            'per_page' => isset( $settings['catalog_per_page'] ) ? absint( $settings['catalog_per_page'] ) : 12,
            'orderby' => isset( $settings['catalog_orderby'] ) ? sanitize_key( (string) $settings['catalog_orderby'] ) : 'date',
            'order' => isset( $settings['catalog_order'] ) ? strtoupper( sanitize_key( (string) $settings['catalog_order'] ) ) : 'DESC',
        );
    }

    private function sanitize_category( $category ): string
    {
        $normalized_category = trim( sanitize_text_field( wp_unslash( (string) $category ) ) );

        if ( '' === $normalized_category ) {
            return '';
        }

        if ( ctype_digit( $normalized_category ) ) {
            return (string) absint( $normalized_category );
        }

        return sanitize_title( $normalized_category );
    }

    private function sanitize_orderby( string $orderby ): string
    {
        $allowed_orderby = array( 'date', 'title', 'modified', 'menu_order' );
        $normalized_orderby = sanitize_key( $orderby );

        return in_array( $normalized_orderby, $allowed_orderby, true ) ? $normalized_orderby : 'date';
    }

    private function sanitize_order( string $order ): string
    {
        $normalized_order = strtoupper( sanitize_key( $order ) );

        return in_array( $normalized_order, array( 'ASC', 'DESC' ), true ) ? $normalized_order : 'DESC';
    }
}
