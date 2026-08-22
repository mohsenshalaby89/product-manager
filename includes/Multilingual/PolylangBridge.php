<?php
declare(strict_types=1);

namespace ProductManager\Multilingual;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PolylangBridge
{
    public static function register_content_type_support( array $post_types, bool $is_settings ): array
    {
        if ( $is_settings ) {
            $post_types['pm_product'] = 'pm_product';
        } else {
            $post_types[] = 'pm_product';
        }

        return $post_types;
    }

    public static function register_taxonomy_support( array $taxonomies, bool $is_settings ): array
    {
        if ( $is_settings ) {
            $taxonomies['pm_product_cat'] = 'pm_product_cat';
        } else {
            $taxonomies[] = 'pm_product_cat';
        }

        return $taxonomies;
    }

    public static function is_active(): bool
    {
        return function_exists( 'pll_current_language' ) || function_exists( 'pll_languages_list' );
    }

    public static function get_languages(): array
    {
        if ( ! self::is_active() || ! function_exists( 'pll_languages_list' ) ) {
            return array();
        }

        $languages = pll_languages_list( array( 'fields' => 'slug' ) );

        if ( ! is_array( $languages ) ) {
            return array();
        }

        return array_values(
            array_filter(
                array_map(
                    static function ( $language ) {
                        return is_string( $language ) ? trim( $language ) : '';
                    },
                    $languages
                )
            )
        );
    }

    public static function set_post_language( int $post_id, string $language ): void
    {
        if ( $post_id <= 0 || '' === $language || ! self::is_active() || ! function_exists( 'pll_set_post_language' ) ) {
            return;
        }

        pll_set_post_language( $post_id, $language );
    }

    public static function get_translation_post_id( int $post_id, string $language ): int
    {
        if ( $post_id <= 0 || '' === $language || ! self::is_active() || ! function_exists( 'pll_get_post' ) ) {
            return 0;
        }

        $translation_id = pll_get_post( $post_id, $language );

        return is_int( $translation_id ) && $translation_id > 0 ? $translation_id : 0;
    }

    public static function get_post_translation_ids( int $post_id ): array
    {
        if ( $post_id <= 0 ) {
            return array();
        }

        if ( ! self::is_active() || ! function_exists( 'pll_get_post_translations' ) ) {
            return array( $post_id );
        }

        $translations = pll_get_post_translations( $post_id );
        if ( ! is_array( $translations ) ) {
            return array( $post_id );
        }

        $ids = array_values(
            array_unique(
                array_filter(
                    array_map( 'absint', $translations )
                )
            )
        );

        if ( empty( $ids ) ) {
            return array( $post_id );
        }

        return $ids;
    }

    public static function save_post_translations( array $translations ): void
    {
        if ( ! self::is_active() || ! function_exists( 'pll_save_post_translations' ) ) {
            return;
        }

        $sanitized = array();
        foreach ( $translations as $language => $post_id ) {
            $language_key = is_string( $language ) ? trim( $language ) : '';
            $resolved_post_id = absint( $post_id );

            if ( '' === $language_key || $resolved_post_id <= 0 ) {
                continue;
            }

            $sanitized[ $language_key ] = $resolved_post_id;
        }

        if ( ! empty( $sanitized ) ) {
            pll_save_post_translations( $sanitized );
        }
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

    public static function get_translation_term_id( int $term_id, string $language ): int
    {
        if ( $term_id <= 0 || '' === $language || ! self::is_active() || ! function_exists( 'pll_get_term' ) ) {
            return 0;
        }

        $translated_id = pll_get_term( $term_id, $language );

        return is_int( $translated_id ) && $translated_id > 0 ? $translated_id : 0;
    }

    public static function get_post_meta_value( int $post_id, string $meta_key, $default = '' )
    {
        $resolved_post_id = self::get_translated_post_id( $post_id );

        $value = get_post_meta( $resolved_post_id, $meta_key, true );

        return '' !== $value ? $value : $default;
    }
}
