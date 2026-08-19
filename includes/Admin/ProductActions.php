<?php
declare(strict_types=1);

namespace ProductManager\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ProductManager\Company\CompanySettingsService;
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

    private function redirectToList( string $notice ): void
    {
        wp_safe_redirect( admin_url( 'admin.php?page=product-manager-products&pm_notice=' . rawurlencode( $notice ) ) );
        exit;
    }
}
