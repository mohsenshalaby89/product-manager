<?php

declare(strict_types=1);

namespace ProductManager\Products;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ProductManager\Multilingual\PolylangBridge;

final class ProductQueryService{
    private const PUBLIC_ORDERBY_MAP = array(
        'date' => 'date',
        'title' => 'title',
        'modified' => 'modified',
        'menu_order' => 'menu_order',
    );

    public function get_products( array $args = array() ): array
    {
        $defaults = array(
            'search' => '',
            'category_id' => 0,
            'status' => '',
            'paged' => 1,
            'per_page' => 20,
        );

        $args = wp_parse_args( $args, $defaults );

        $query_args = array(
            'post_type' => 'pm_product',
            'post_status' => array( 'draft', 'publish' ),
            'posts_per_page' => max( 1, absint( $args['per_page'] ) ),
            'paged' => max( 1, absint( $args['paged'] ) ),
            'orderby' => 'date',
            'order' => 'DESC',
            'suppress_filters' => false,
        );

        if ( '' !== trim( (string) $args['search'] ) ) {
            $query_args['s'] = sanitize_text_field( wp_unslash( (string) $args['search'] ) );
        }

        if ( '' !== $args['status'] ) {
            $status = sanitize_key( wp_unslash( (string) $args['status'] ) );
            if ( in_array( $status, array( 'draft', 'publish' ), true ) ) {
                $query_args['post_status'] = array( $status );
            }
        }

        if ( $args['category_id'] > 0 ) {
            $category_id = absint( $args['category_id'] );
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'pm_product_cat',
                    'field' => 'term_id',
                    'terms' => array( $category_id ),
                ),
            );
        }

        $posts = get_posts( $query_args );

        $items = array();
        foreach ( $posts as $post ) {
            $terms = wp_get_post_terms( $post->ID, 'pm_product_cat' );
            $category_name = '';
            if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                $category_name = $terms[0]->name;
            }

            $items[] = array(
                'ID' => $post->ID,
                'title' => $post->post_title,
                'excerpt' => $post->post_excerpt,
                'status' => $post->post_status,
                'date' => $post->post_date,
                'category' => $category_name,
                'thumbnail_id' => get_post_thumbnail_id( $post->ID ),
            );
        }

        $count_query_args = $query_args;
        $count_query_args['posts_per_page'] = -1;
        $count_query_args['fields'] = 'ids';
        $count_query_args['no_found_rows'] = true;
        $count_query_args['ignore_sticky_posts'] = true;

        $total_items = count( get_posts( $count_query_args ) );

        return array(
            'items' => $items,
            'total_items' => $total_items,
            'per_page' => max( 1, absint( $args['per_page'] ) ),
            'paged' => max( 1, absint( $args['paged'] ) ),
        );
    }

    public function get_public_products( array $args = array() ): array
    {
        $defaults = array(
            'search' => '',
            'category_id' => 0,
            'paged' => 1,
            'per_page' => 12,
            'orderby' => 'date',
            'order' => 'DESC',
        );

        $args = wp_parse_args( $args, $defaults );

        $query = new \WP_Query( $this->build_public_query_args( $args ) );
        $items = array();

        foreach ( $query->posts as $post ) {
            $validated_post = $this->validate_public_product_post( $post );

            if ( null !== $validated_post ) {
                $items[] = $this->attach_public_product_meta( $validated_post );
            }
        }

        return array(
            'items' => $items,
            'total_items' => (int) $query->found_posts,
            'total_pages' => max( 1, (int) $query->max_num_pages ),
            'per_page' => max( 1, min( 48, absint( $args['per_page'] ) ) ),
            'paged' => max( 1, absint( $args['paged'] ) ),
        );
    }

    public function get_public_product_by_slug( string $slug )
    {
        $normalized_slug = sanitize_title( wp_unslash( $slug ) );

        if ( '' === $normalized_slug ) {
            return null;
        }

        $query = new \WP_Query(
            array(
                'post_type' => 'pm_product',
                'post_status' => array( 'publish' ),
                'name' => $normalized_slug,
                'posts_per_page' => 1,
                'ignore_sticky_posts' => true,
                'no_found_rows' => true,
                'suppress_filters' => false,
            )
        );

        if ( empty( $query->posts ) ) {
            return null;
        }

        $product = $this->validate_public_product_post( $query->posts[0] );

        return $product instanceof \WP_Post ? $this->attach_public_product_meta( $product ) : null;
    }

    public function get_public_product_by_id( int $product_id )
    {
        if ( $product_id <= 0 ) {
            return null;
        }

        $product = $this->validate_public_product_post( get_post( $product_id ) );

        return $product instanceof \WP_Post ? $this->attach_public_product_meta( $product ) : null;
    }

    public function get_public_categories(): array
    {
        $terms = get_terms(
            array(
                'taxonomy' => 'pm_product_cat',
                'hide_empty' => true,
                'orderby' => 'name',
                'order' => 'ASC',
            )
        );

        if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
            return array();
        }

        return array_values(
            array_filter(
                $terms,
                static function ( $term ): bool {
                    return $term instanceof \WP_Term;
                }
            )
        );
    }

    public function get_public_category( $value )
    {
        if ( is_int( $value ) ) {
            $term_id = $value;
        } else {
            $value = sanitize_text_field( wp_unslash( (string) $value ) );

            if ( '' === $value ) {
                return null;
            }

            if ( ctype_digit( $value ) ) {
                $term_id = absint( $value );
            } else {
                $term = get_term_by( 'slug', sanitize_title( $value ), 'pm_product_cat' );

                return $term instanceof \WP_Term ? $term : null;
            }
        }

        if ( $term_id <= 0 ) {
            return null;
        }

        $term = get_term( $term_id, 'pm_product_cat' );

        return $term instanceof \WP_Term ? $term : null;
    }

    private function build_public_query_args( array $args ): array
    {
        $query_args = array(
            'post_type' => 'pm_product',
            'post_status' => array( 'publish' ),
            'posts_per_page' => max( 1, min( 48, absint( $args['per_page'] ) ) ),
            'paged' => max( 1, absint( $args['paged'] ) ),
            'orderby' => $this->get_public_orderby( (string) $args['orderby'] ),
            'order' => $this->get_public_order( (string) $args['order'] ),
            'ignore_sticky_posts' => true,
            'suppress_filters' => false,
        );

        if ( '' !== trim( (string) $args['search'] ) ) {
            $query_args['s'] = sanitize_text_field( wp_unslash( (string) $args['search'] ) );
        }

        if ( ! empty( $args['category_id'] ) ) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'pm_product_cat',
                    'field' => 'term_id',
                    'terms' => array( absint( $args['category_id'] ) ),
                ),
            );
        }

        return $query_args;
    }

    private function get_public_orderby( string $orderby ): string
    {
        $normalized_orderby = sanitize_key( $orderby );

        if ( isset( self::PUBLIC_ORDERBY_MAP[ $normalized_orderby ] ) ) {
            return self::PUBLIC_ORDERBY_MAP[ $normalized_orderby ];
        }

        return self::PUBLIC_ORDERBY_MAP['date'];
    }

    private function get_public_order( string $order ): string
    {
        $normalized_order = strtoupper( sanitize_key( $order ) );

        return in_array( $normalized_order, array( 'ASC', 'DESC' ), true ) ? $normalized_order : 'DESC';
    }

    private function validate_public_product_post( $post )
    {
        if ( ! $post instanceof \WP_Post ) {
            return null;
        }

        if ( 'pm_product' !== $post->post_type ) {
            return null;
        }

        if ( 'publish' !== $post->post_status ) {
            return null;
        }

        return $post;
    }

    private function attach_public_product_meta( \WP_Post $post ): \WP_Post
    {
        $translated_post_id = PolylangBridge::get_translated_post_id( (int) $post->ID );
        $gallery = $this->normalize_gallery_meta( get_post_meta( $translated_post_id, 'pm_product_gallery', true ) );

        $meta = array(
            'season' => PolylangBridge::get_post_meta_value( (int) $post->ID, 'pm_product_season', '' ),
            'availability' => PolylangBridge::get_post_meta_value( (int) $post->ID, 'pm_product_availability', 'in_stock' ),
            'price' => PolylangBridge::get_post_meta_value( (int) $post->ID, 'pm_product_price', '' ),
            'sku' => PolylangBridge::get_post_meta_value( (int) $post->ID, 'pm_product_sku', '' ),
            'details' => PolylangBridge::get_post_meta_value( (int) $post->ID, 'pm_product_details', '' ),
            'gallery' => $gallery,
        );

        foreach ( $meta as $key => $value ) {
            $post->{$key} = $value;
        }

        $post->thumbnail_id = get_post_thumbnail_id( $translated_post_id );
        $post->url = get_permalink( $translated_post_id );

        return $post;
    }

    private function normalize_gallery_meta( $value ): array
    {
        if ( is_array( $value ) ) {
            return array_values( array_filter( array_map( 'absint', $value ) ) );
        }

        if ( is_string( $value ) ) {
            $ids = array_filter( array_map( 'absint', explode( ',', $value ) ) );
            return array_values( $ids );
        }

        return array();
    }
}
