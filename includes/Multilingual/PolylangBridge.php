<?php
declare(strict_types=1);

namespace ProductManager\Multilingual;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PolylangBridge
{
    public static function is_active(): bool
    {
        return function_exists( 'pll_current_language' ) || function_exists( 'pll_languages_list' );
    }

    public static function get_translated_post_id( int $post_id ): int
    {
        if ( $post_id <= 0 || ! self::is_active() || ! function_exists( 'pll_get_post' ) ) {
            return $post_id;
        }

        $translated_id = pll_get_post( $post_id );

        return is_int( $translated_id ) && $translated_id > 0 ? $translated_id : $post_id;
    }

    public static function get_translated_term_id( int $term_id ): int
    {
        if ( $term_id <= 0 || ! self::is_active() || ! function_exists( 'pll_get_term' ) ) {
            return $term_id;
        }

        $translated_id = pll_get_term( $term_id );

        return is_int( $translated_id ) && $translated_id > 0 ? $translated_id : $term_id;
    }

    public static function get_post_meta_value( int $post_id, string $meta_key, $default = '' )
    {
        $resolved_post_id = self::get_translated_post_id( $post_id );

        $value = get_post_meta( $resolved_post_id, $meta_key, true );

        return '' !== $value ? $value : $default;
    }
}
