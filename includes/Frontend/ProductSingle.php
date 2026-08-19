<?php

declare(strict_types=1);

namespace ProductManager\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ProductManager\Multilingual\PolylangBridge;
use ProductManager\Products\ProductQueryService;
final class ProductSingle
{
    private const QUERY_VAR = 'product_manager_product_slug';

    private ProductQueryService $productQueryService;

    public function __construct( ProductQueryService $productQueryService )
    {
        $this->productQueryService = $productQueryService;
    }

    public function register_rewrite_rule(): void
    {
        add_rewrite_rule(
            '^' . preg_quote( PRODUCT_MANAGER_PRODUCT_BASE_SLUG, '/' ) . '/([^/]+)/?$',
            'index.php?post_type=pm_product&name=$matches[1]&' . self::QUERY_VAR . '=$matches[1]',
            'top'
        );
    }

    public function register_query_var( array $query_vars ): array
    {
        $query_vars[] = self::QUERY_VAR;

        return $query_vars;
    }

    public function is_product_request(): bool
    {
        return '' !== $this->get_requested_slug();
    }

    public function filter_template( string $template ): string
    {
        $requested_slug = $this->get_requested_slug();

        if ( '' === $requested_slug ) {
            return $template;
        }

        $product = $this->productQueryService->get_public_product_by_slug( $requested_slug );

        if ( null === $product ) {
            return $this->resolve_not_found_template( $template );
        }

        set_query_var( 'product_manager_single_product', $this->prepare_product_data( $product ) );

        $single_template = PRODUCT_MANAGER_PLUGIN_DIR . 'templates/single-product.php';

        return file_exists( $single_template ) ? $single_template : $template;
    }

    private function get_requested_slug(): string
    {
        $slug = get_query_var( self::QUERY_VAR );

        if ( ! is_string( $slug ) ) {
            return '';
        }

        return sanitize_title( wp_unslash( $slug ) );
    }

    private function prepare_product_data( \WP_Post $product ): array
    {
        $translated_post_id = PolylangBridge::get_translated_post_id( (int) $product->ID );
        $translated_product = get_post( $translated_post_id );

        $terms = wp_get_post_terms( $translated_post_id, 'pm_product_cat' );
        $category_name = '';

        if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
            $category_name = $terms[0]->name;
        }

        $gallery = array();
        if ( $translated_product instanceof \WP_Post ) {
            $gallery_meta = get_post_meta( $translated_product->ID, 'pm_product_gallery', true );
            if ( is_array( $gallery_meta ) ) {
                $gallery = array_values( array_filter( array_map( 'absint', $gallery_meta ) ) );
            } elseif ( is_string( $gallery_meta ) ) {
                $gallery = array_values( array_filter( array_map( 'absint', explode( ',', $gallery_meta ) ) ) );
            }
        }

        $company_settings = get_option( 'product_manager_company_settings', array() );

        if ( ! is_array( $company_settings ) ) {
            $company_settings = array();
        }

        return array(
            'id' => (int) $translated_post_id,
            'title' => $translated_product instanceof \WP_Post ? $translated_product->post_title : $product->post_title,
            'content' => apply_filters( 'the_content', $translated_product instanceof \WP_Post ? $translated_product->post_content : $product->post_content ),
            'category' => $category_name,
            'season' => isset( $product->season ) ? (string) $product->season : '',
            'availability' => isset( $product->availability ) ? (string) $product->availability : 'in_stock',
            'price' => isset( $product->price ) ? (string) $product->price : '',
            'sku' => isset( $product->sku ) ? (string) $product->sku : '',
            'details' => isset( $product->details ) ? (string) $product->details : '',
            'gallery' => $gallery,
            'url' => get_permalink( $translated_post_id ),
            'thumbnail_id' => get_post_thumbnail_id( $translated_post_id ),
            'company_name' => sanitize_text_field( (string) ( $company_settings['company_name'] ?? '' ) ),
            'company_email' => sanitize_email( (string) ( $company_settings['company_email'] ?? '' ) ),
            'company_phone' => sanitize_text_field( (string) ( $company_settings['company_phone'] ?? '' ) ),
            'company_whatsapp' => sanitize_text_field( (string) ( $company_settings['company_whatsapp'] ?? '' ) ),
            'company_website' => esc_url_raw( (string) ( $company_settings['company_website'] ?? '' ) ),
            'company_description' => sanitize_textarea_field( (string) ( $company_settings['company_description'] ?? '' ) ),
        );
    }

    private function resolve_not_found_template( string $template ): string
    {
        global $wp_query;

        if ( $wp_query instanceof \WP_Query ) {
            $wp_query->set_404();
        }

        status_header( 404 );
        nocache_headers();

        $not_found_template = get_404_template();

        if ( is_string( $not_found_template ) && '' !== $not_found_template ) {
            return $not_found_template;
        }

        return $template;
    }
}
