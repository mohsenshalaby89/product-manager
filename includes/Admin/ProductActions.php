<?php
declare(strict_types=1);

namespace ProductManager\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ProductManager\Company\CompanySettingsService;
use ProductManager\Multilingual\PolylangBridge;
use ProductManager\Products\ProductMetadataService;
use ProductManager\Products\ProductService;

final class ProductActions
{
    private ProductService $productService;
    private ProductMetadataService $productMetadataService;
    private CompanySettingsService $companySettingsService;

    public function __construct( ProductService $productService, ProductMetadataService $productMetadataService, CompanySettingsService $companySettingsService )
    {
        $this->productService = $productService;
        $this->productMetadataService = $productMetadataService;
        $this->companySettingsService = $companySettingsService;
    }

    public function register(): void
    {
        add_action( 'admin_post_product_manager_save_product', array( $this, 'handleSaveProduct' ) );
        add_action( 'admin_post_product_manager_delete_product', array( $this, 'handleDeleteProduct' ) );
        add_action( 'admin_post_product_manager_save_company_settings', array( $this, 'handleSaveCompanySettings' ) );
    }

    public function handleSaveProduct(): void
    {
        if ( 'POST' !== strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) ) {
            wp_die( esc_html__( 'Invalid request method.', 'product-manager' ) );
        }

        if ( ! current_user_can( 'pm_manage_products' ) && ! current_user_can( 'pm_edit_products' ) && ! current_user_can( 'manage_options' ) && ! current_user_can( 'edit_posts' ) ) {
            wp_die( esc_html__( 'You do not have permission to manage products.', 'product-manager' ) );
        }

        check_admin_referer( 'product_manager_save_product', 'product_manager_product_nonce' );

        $product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
        $product_name = isset( $_POST['product_name'] ) ? sanitize_text_field( wp_unslash( $_POST['product_name'] ) ) : '';
        $product_excerpt = isset( $_POST['product_excerpt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['product_excerpt'] ) ) : '';
        $product_content = isset( $_POST['product_content'] ) ? wp_kses_post( wp_unslash( $_POST['product_content'] ) ) : '';
        $status = isset( $_POST['product_status'] ) ? sanitize_key( wp_unslash( $_POST['product_status'] ) ) : 'draft';
        $category_id = isset( $_POST['product_category'] ) ? absint( wp_unslash( $_POST['product_category'] ) ) : 0;
        $featured_image_id = isset( $_POST['product_featured_image_id'] ) ? absint( wp_unslash( $_POST['product_featured_image_id'] ) ) : 0;
        $season = isset( $_POST['product_season'] ) ? sanitize_text_field( wp_unslash( $_POST['product_season'] ) ) : '';
        $availability = isset( $_POST['product_availability'] ) ? sanitize_key( wp_unslash( $_POST['product_availability'] ) ) : 'in_stock';
        $price = isset( $_POST['product_price'] ) ? sanitize_text_field( wp_unslash( $_POST['product_price'] ) ) : '';
        $sku = isset( $_POST['product_sku'] ) ? sanitize_text_field( wp_unslash( $_POST['product_sku'] ) ) : '';
        $details = isset( $_POST['product_details'] ) ? sanitize_textarea_field( wp_unslash( $_POST['product_details'] ) ) : '';
        $translation_product_id = isset( $_POST['product_translation_id'] ) ? absint( wp_unslash( $_POST['product_translation_id'] ) ) : 0;
        $translation_language = isset( $_POST['product_translation_language'] ) ? sanitize_key( wp_unslash( $_POST['product_translation_language'] ) ) : 'en';
        $translation_title = isset( $_POST['product_name_translation'] ) ? sanitize_text_field( wp_unslash( $_POST['product_name_translation'] ) ) : '';
        $translation_excerpt = isset( $_POST['product_excerpt_translation'] ) ? sanitize_textarea_field( wp_unslash( $_POST['product_excerpt_translation'] ) ) : '';
        $translation_content = isset( $_POST['product_content_translation'] ) ? wp_kses_post( wp_unslash( $_POST['product_content_translation'] ) ) : '';
        $translation_category_id = isset( $_POST['product_category_translation'] ) ? absint( wp_unslash( $_POST['product_category_translation'] ) ) : 0;
        $translation_season = isset( $_POST['product_season_translation'] ) ? sanitize_text_field( wp_unslash( $_POST['product_season_translation'] ) ) : '';
        $translation_availability = isset( $_POST['product_availability_translation'] ) ? sanitize_key( wp_unslash( $_POST['product_availability_translation'] ) ) : 'in_stock';
        $translation_price = isset( $_POST['product_price_translation'] ) ? sanitize_text_field( wp_unslash( $_POST['product_price_translation'] ) ) : '';
        $translation_sku = isset( $_POST['product_sku_translation'] ) ? sanitize_text_field( wp_unslash( $_POST['product_sku_translation'] ) ) : '';
        $translation_details = isset( $_POST['product_details_translation'] ) ? sanitize_textarea_field( wp_unslash( $_POST['product_details_translation'] ) ) : '';
        $gallery = isset( $_POST['product_gallery'] ) ? $_POST['product_gallery'] : '';
        $gallery_ids = array();
        if ( is_string( $gallery ) && '' !== trim( $gallery ) ) {
            foreach ( explode( ',', $gallery ) as $item ) {
                $attachment_id = absint( $item );
                if ( $attachment_id > 0 ) {
                    $gallery_ids[] = $attachment_id;
                }
            }
        }

        if ( '' === $product_name ) {
            $this->redirectToList( 'error' );
        }

        if ( 'publish' === $status && ! current_user_can( 'pm_publish_products' ) ) {
            wp_die( esc_html__( 'You do not have permission to publish products.', 'product-manager' ) );
        }

        if ( $product_id > 0 ) {
            $existing_product = $this->productService->get_product( $product_id );
            if ( null === $existing_product ) {
                $this->redirectToList( 'error' );
            }
        }

        if ( $category_id > 0 && ! term_exists( $category_id, 'pm_product_cat' ) ) {
            $category_id = 0;
        }

        $data = array(
            'ID' => $product_id,
            'title' => $product_name,
            'excerpt' => $product_excerpt,
            'content' => $product_content,
            'status' => $status,
            'category_id' => $category_id,
            'featured_image_id' => $featured_image_id,
            'meta' => array(
                'season' => $season,
                'availability' => in_array( $availability, array( 'in_stock', 'limited', 'out_of_stock' ), true ) ? $availability : 'in_stock',
                'price' => $price,
                'sku' => $sku,
                'details' => $details,
                'gallery' => $gallery_ids,
            ),
        );

        $saved_id = $this->productService->save_product( $data );
        if ( $saved_id > 0 ) {
            $this->saveTranslationIfProvided(
                $saved_id,
                $translation_product_id,
                $translation_language,
                $translation_title,
                $translation_excerpt,
                $translation_content,
                $translation_category_id,
                $translation_season,
                $translation_availability,
                $translation_price,
                $translation_sku,
                $translation_details,
                $data
            );
            $this->redirectToList( 'saved' );
        }

        $this->redirectToList( 'error' );
    }

    public function handleDeleteProduct(): void
    {
        if ( 'POST' !== strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) ) {
            wp_die( esc_html__( 'Invalid request method.', 'product-manager' ) );
        }

        if ( ! current_user_can( 'pm_delete_products' ) && ! current_user_can( 'delete_posts' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to delete products.', 'product-manager' ) );
        }

        check_admin_referer( 'product_manager_delete_product', 'product_manager_delete_nonce' );

        $product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
        if ( $product_id > 0 ) {
            $existing_product = $this->productService->get_product( $product_id );
            if ( null === $existing_product ) {
                $this->redirectToList( 'error' );
            }

            if ( $this->productService->delete_product( $product_id ) ) {
                $this->redirectToList( 'deleted' );
            }
        }

        $this->redirectToList( 'error' );
    }

    public function handleSaveCompanySettings(): void
    {
        if ( 'POST' !== strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) ) {
            wp_die( esc_html__( 'Invalid request method.', 'product-manager' ) );
        }

        if ( ! current_user_can( 'pm_manage_products' ) ) {
            wp_die( esc_html__( 'You do not have permission to manage company settings.', 'product-manager' ) );
        }

        check_admin_referer( 'product_manager_save_company_settings', 'product_manager_company_settings_nonce' );

        $settings = array(
            'company_name' => isset( $_POST['company_name'] ) ? sanitize_text_field( wp_unslash( $_POST['company_name'] ) ) : '',
            'company_description' => isset( $_POST['company_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['company_description'] ) ) : '',
            'company_email' => isset( $_POST['company_email'] ) ? sanitize_email( wp_unslash( $_POST['company_email'] ) ) : '',
            'company_phone' => isset( $_POST['company_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['company_phone'] ) ) : '',
            'company_whatsapp' => isset( $_POST['company_whatsapp'] ) ? sanitize_text_field( wp_unslash( $_POST['company_whatsapp'] ) ) : '',
            'company_website' => isset( $_POST['company_website'] ) ? esc_url_raw( wp_unslash( $_POST['company_website'] ) ) : '',
            'company_logo_id' => isset( $_POST['company_logo_id'] ) ? absint( wp_unslash( $_POST['company_logo_id'] ) ) : 0,
        );

        $this->companySettingsService->save_settings( $settings );

        wp_safe_redirect( admin_url( 'admin.php?page=product-manager-company-settings' ) );
        exit;
    }

    private function saveTranslationIfProvided(
        int $source_product_id,
        int $translation_product_id,
        string $translation_language,
        string $translation_title,
        string $translation_excerpt,
        string $translation_content,
        int $translation_category_id,
        string $translation_season,
        string $translation_availability,
        string $translation_price,
        string $translation_sku,
        string $translation_details,
        array $source_data
    ): void {
        if ( ! PolylangBridge::is_active() || '' === $translation_title ) {
            return;
        }

        $available_languages = PolylangBridge::get_languages();
        if ( empty( $available_languages ) ) {
            return;
        }

        if ( ! in_array( $translation_language, $available_languages, true ) ) {
            $translation_language = in_array( 'en', $available_languages, true ) ? 'en' : (string) $available_languages[0];
        }

        $translation_data = $source_data;
        $translation_data['ID'] = $translation_product_id;
        $translation_data['title'] = $translation_title;
        $translation_data['excerpt'] = $translation_excerpt;
        $translation_data['content'] = $translation_content;
        $translation_data['category_id'] = $translation_category_id > 0 && term_exists( $translation_category_id, 'pm_product_cat' ) ? $translation_category_id : 0;
        $translation_data['meta'] = array(
            'season' => $translation_season,
            'availability' => in_array( $translation_availability, array( 'in_stock', 'limited', 'out_of_stock' ), true ) ? $translation_availability : 'in_stock',
            'price' => $translation_price,
            'sku' => $translation_sku,
            'details' => $translation_details,
            'gallery' => isset( $source_data['meta']['gallery'] ) && is_array( $source_data['meta']['gallery'] ) ? $source_data['meta']['gallery'] : array(),
        );

        $translated_product_id = $this->productService->save_product( $translation_data );
        if ( $translated_product_id <= 0 ) {
            return;
        }

        $source_language = in_array( 'ar', $available_languages, true ) ? 'ar' : (string) $available_languages[0];
        if ( $source_language === $translation_language ) {
            $source_language = in_array( 'en', $available_languages, true ) ? 'en' : (string) $available_languages[0];
        }

        PolylangBridge::set_post_language( $source_product_id, $source_language );
        PolylangBridge::set_post_language( $translated_product_id, $translation_language );

        PolylangBridge::save_post_translations(
            array(
                $source_language => $source_product_id,
                $translation_language => $translated_product_id,
            )
        );
    }

    private function redirectToList( string $notice ): void
    {
        wp_safe_redirect( admin_url( 'admin.php?page=product-manager-products&pm_notice=' . rawurlencode( $notice ) ) );
        exit;
    }
}
