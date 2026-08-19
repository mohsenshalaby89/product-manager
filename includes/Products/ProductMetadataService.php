<?php
declare(strict_types=1);

namespace ProductManager\Products;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ProductMetadataService
{
    private const META_PREFIX = 'pm_product_';

    public function get_product_meta( int $product_id, string $key, $default = '' )
    {
        if ( ! $this->is_valid_product( $product_id ) ) {
            return $default;
        }

        return get_post_meta( $product_id, self::META_PREFIX . $key, true );
    }

    public function update_product_meta( int $product_id, string $key, $value ): bool
    {
        if ( ! $this->is_valid_product( $product_id ) ) {
            return false;
        }

        return (bool) update_post_meta( $product_id, self::META_PREFIX . $key, $value );
    }

    public function delete_product_meta( int $product_id, string $key ): bool
    {
        if ( ! $this->is_valid_product( $product_id ) ) {
            return false;
        }

        return delete_post_meta( $product_id, self::META_PREFIX . $key );
    }

    public function is_valid_product( int $product_id ): bool
    {
        $post = get_post( $product_id );

        return $post instanceof \WP_Post && 'pm_product' === $post->post_type;
    }
}
