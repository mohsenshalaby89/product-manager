<?php
declare(strict_types=1);

namespace ProductManager\Products;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ProductService
{
    public function save_product( array $data ): int
    {
        $post_id = isset( $data['ID'] ) ? (int) $data['ID'] : 0;
        $title = isset( $data['title'] ) ? trim( sanitize_text_field( wp_unslash( (string) $data['title'] ) ) ) : '';
        $excerpt = isset( $data['excerpt'] ) ? trim( sanitize_textarea_field( wp_unslash( (string) $data['excerpt'] ) ) ) : '';
        $content = isset( $data['content'] ) ? wp_kses_post( wp_unslash( (string) $data['content'] ) ) : '';
        $status = isset( $data['status'] ) ? sanitize_key( wp_unslash( (string) $data['status'] ) ) : 'draft';
        $featured_image_id = isset( $data['featured_image_id'] ) ? absint( $data['featured_image_id'] ) : 0;
        $category_id = isset( $data['category_id'] ) ? absint( $data['category_id'] ) : 0;
        $meta = isset( $data['meta'] ) && is_array( $data['meta'] ) ? $data['meta'] : array();

        if ( '' === $title ) {
            return 0;
        }

        $post_data = array(
            'post_title' => $title,
            'post_content' => $content,
            'post_excerpt' => $excerpt,
            'post_status' => in_array( $status, array( 'publish', 'draft' ), true ) ? $status : 'draft',
            'post_type' => 'pm_product',
        );

        if ( $post_id > 0 ) {
            $existing_post = get_post( $post_id );
            if ( ! $existing_post instanceof \WP_Post || 'pm_product' !== $existing_post->post_type ) {
                return 0;
            }

            $post_data['ID'] = $post_id;
            $saved_id = wp_update_post( $post_data, true );
        } else {
            $saved_id = wp_insert_post( $post_data, true );
        }

        if ( is_wp_error( $saved_id ) ) {
            return 0;
        }

        $saved_id = (int) $saved_id;

        if ( $featured_image_id > 0 ) {
            set_post_thumbnail( $saved_id, $featured_image_id );
        } else {
            delete_post_thumbnail( $saved_id );
        }

        if ( $category_id > 0 && term_exists( $category_id, 'pm_product_cat' ) ) {
            wp_set_object_terms( $saved_id, array( $category_id ), 'pm_product_cat', false );
        } else {
            wp_set_object_terms( $saved_id, array(), 'pm_product_cat', false );
        }

        foreach ( $meta as $key => $value ) {
            $normalized_key = preg_replace( '/[^a-z0-9_]/', '', strtolower( (string) $key ) );
            if ( '' === $normalized_key ) {
                continue;
            }

            $meta_key = 'pm_product_' . $normalized_key;

            if ( is_array( $value ) ) {
                $sanitized_value = array_map( static function ( $item ) {
                    return sanitize_text_field( wp_unslash( (string) $item ) );
                }, $value );
                update_post_meta( $saved_id, $meta_key, $sanitized_value );
                continue;
            }

            if ( is_bool( $value ) ) {
                update_post_meta( $saved_id, $meta_key, $value ? '1' : '0' );
                continue;
            }

            if ( is_numeric( $value ) ) {
                update_post_meta( $saved_id, $meta_key, (string) $value );
                continue;
            }

            update_post_meta( $saved_id, $meta_key, sanitize_text_field( wp_unslash( (string) $value ) ) );
        }

        return $saved_id;
    }

    public function get_product( int $product_id )
    {
        $post = get_post( $product_id );

        if ( ! $post instanceof \WP_Post ) {
            return null;
        }

        if ( 'pm_product' !== $post->post_type ) {
            return null;
        }

        return $post;
    }

    public function delete_product( int $product_id ): bool
    {
        if ( ! $this->is_valid_product( $product_id ) ) {
            return false;
        }

        $deleted = wp_delete_post( $product_id, true );

        return $deleted instanceof \WP_Post;
    }

    public function is_valid_product( int $product_id ): bool
    {
        return null !== $this->get_product( $product_id );
    }
}
