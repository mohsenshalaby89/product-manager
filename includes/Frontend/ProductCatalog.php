<?php
declare(strict_types=1);

namespace ProductManager\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ProductManager\Products\ProductQueryService;

final class ProductCatalog
{
    private ProductQueryService $productQueryService;

    public function __construct( ProductQueryService $productQueryService )
    {
        $this->productQueryService = $productQueryService;
    }

    public function render( array $shortcode_atts = array() ): string
    {
        $state = $this->build_state( $shortcode_atts );
        $query_result = $this->productQueryService->get_public_products(
            array(
                'search' => $state['search'],
                'category_id' => $state['category_id'],
                'paged' => $state['paged'],
                'per_page' => $state['per_page'],
                'orderby' => $state['orderby'],
                'order' => $state['order'],
            )
        );

        $products = array_map( array( $this, 'prepare_product_card' ), $query_result['items'] );
        $categories = array_map( array( $this, 'prepare_category_option' ), $this->productQueryService->get_public_categories() );
        $pagination_links = $this->build_pagination_links( $query_result, $state );

        return $this->render_template(
            PRODUCT_MANAGER_PLUGIN_DIR . 'templates/catalog.php',
            array(
                'products' => $products,
                'categories' => $categories,
                'filters' => array(
                    'search' => $state['search'],
                    'category' => $state['category_value'],
                    'has_active_filters' => '' !== $state['search'] || '' !== $state['category_value'],
                ),
                'pagination_links' => $pagination_links,
                'catalog_action_url' => $this->get_catalog_base_url(),
                'product_card_template' => PRODUCT_MANAGER_PLUGIN_DIR . 'templates/product-card.php',
            )
        );
    }

    private function build_state( array $shortcode_atts ): array
    {
        $settings = get_option( 'product_manager_settings', array() );
        if ( ! is_array( $settings ) ) {
            $settings = array();
        }

        $default_per_page = isset( $settings['catalog_per_page'] ) ? max( 1, min( 48, absint( $settings['catalog_per_page'] ) ) ) : 12;
        $default_orderby = isset( $settings['catalog_orderby'] ) ? sanitize_key( (string) $settings['catalog_orderby'] ) : 'date';
        $default_order = isset( $settings['catalog_order'] ) ? strtoupper( sanitize_key( (string) $settings['catalog_order'] ) ) : 'DESC';
        if ( ! in_array( $default_order, array( 'ASC', 'DESC' ), true ) ) {
            $default_order = 'DESC';
        }

        $search = isset( $_GET['pm_search'] )
            ? sanitize_text_field( wp_unslash( (string) $_GET['pm_search'] ) )
            : sanitize_text_field( (string) $shortcode_atts['search'] );

        $category_value = isset( $_GET['pm_category'] )
            ? $this->normalize_category_value( $_GET['pm_category'] )
            : $this->normalize_category_value( $shortcode_atts['category'] );

        $category = $this->productQueryService->get_public_category( $category_value );

        return array(
            'search' => $search,
            'category_id' => $category instanceof \WP_Term ? (int) $category->term_id : 0,
            'category_value' => $category instanceof \WP_Term ? $category->slug : '',
            'paged' => isset( $_GET['pm_page'] ) ? max( 1, absint( $_GET['pm_page'] ) ) : 1,
            'per_page' => max( 1, min( 48, absint( $shortcode_atts['per_page'] ?? $default_per_page ) ) ),
            'orderby' => sanitize_key( (string) ( $shortcode_atts['orderby'] ?? $default_orderby ) ),
            'order' => in_array( strtoupper( sanitize_key( (string) ( $shortcode_atts['order'] ?? $default_order ) ) ), array( 'ASC', 'DESC' ), true )
                ? strtoupper( sanitize_key( (string) ( $shortcode_atts['order'] ?? $default_order ) ) )
                : $default_order,
        );
    }

    private function normalize_category_value( $category ): string
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

    private function prepare_product_card( \WP_Post $post ): array
    {
        $terms = wp_get_post_terms( $post->ID, 'pm_product_cat' );
        $category_name = '';

        if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
            $category_name = $terms[0]->name;
        }

        $excerpt = $post->post_excerpt;

        if ( '' === trim( $excerpt ) ) {
            $excerpt = wp_trim_words( wp_strip_all_tags( $post->post_content ), 24 );
        }

        $season = isset( $post->season ) ? (string) $post->season : '';
        $availability = isset( $post->availability ) ? (string) $post->availability : 'in_stock';
        $price = isset( $post->price ) ? (string) $post->price : '';

        return array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'excerpt' => $excerpt,
            'category' => $category_name,
            'season' => $season,
            'availability' => $availability,
            'price' => $price,
            'url' => get_permalink( $post ),
            'thumbnail_id' => get_post_thumbnail_id( $post->ID ),
        );
    }

    private function prepare_category_option( \WP_Term $term ): array
    {
        return array(
            'id' => (int) $term->term_id,
            'slug' => $term->slug,
            'name' => $term->name,
        );
    }

    private function build_pagination_links( array $query_result, array $state ): array
    {
        if ( empty( $query_result['total_pages'] ) || $query_result['total_pages'] <= 1 ) {
            return array();
        }

        $add_args = array();
        $page_placeholder = 999999999;

        if ( '' !== $state['search'] ) {
            $add_args['pm_search'] = $state['search'];
        }

        if ( '' !== $state['category_value'] ) {
            $add_args['pm_category'] = $state['category_value'];
        }

        $links = paginate_links(
            array(
                'base' => str_replace(
                    (string) $page_placeholder,
                    '%#%',
                    add_query_arg( 'pm_page', $page_placeholder, $this->get_catalog_base_url() )
                ),
                'format' => '',
                'current' => max( 1, (int) $query_result['paged'] ),
                'total' => max( 1, (int) $query_result['total_pages'] ),
                'type' => 'array',
                'prev_text' => __( 'Previous', 'product-manager' ),
                'next_text' => __( 'Next', 'product-manager' ),
                'add_args' => $add_args,
            )
        );

        return is_array( $links ) ? $links : array();
    }

    private function get_catalog_base_url(): string
    {
        $post = get_queried_object();

        if ( $post instanceof \WP_Post ) {
            $permalink = get_permalink( $post );

            if ( is_string( $permalink ) && '' !== $permalink ) {
                return remove_query_arg( 'pm_page', $permalink );
            }
        }

        return remove_query_arg( 'pm_page', home_url( '/' ) );
    }

    private function render_template( string $template_path, array $variables ): string
    {
        if ( ! file_exists( $template_path ) ) {
            return '';
        }

        ob_start();
        extract( $variables, EXTR_SKIP );
        include $template_path;

        return (string) ob_get_clean();
    }
}
