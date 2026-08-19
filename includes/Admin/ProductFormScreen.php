<?php
declare(strict_types=1);

namespace ProductManager\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ProductManager\Company\CompanySettingsService;
use ProductManager\Products\ProductMetadataService;
use ProductManager\Products\ProductService;

final class ProductFormScreen
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

    public function renderAdd(): void
    {
        $this->renderForm( 0 );
    }

    public function renderEdit(): void
    {
        $product_id = isset( $_GET['pm_product_id'] ) ? absint( wp_unslash( $_GET['pm_product_id'] ) ) : 0;
        $this->renderForm( $product_id );
    }

    private function renderForm( int $product_id ): void
    {
        if ( ! current_user_can( 'pm_manage_products' ) ) {
            wp_die( esc_html__( 'You do not have permission to manage products.', 'product-manager' ) );
        }

        $product = null;
        if ( $product_id > 0 ) {
            $product = $this->productService->get_product( $product_id );
        }

        if ( $product_id > 0 && null === $product ) {
            wp_die( esc_html__( 'The selected product could not be found.', 'product-manager' ) );
        }

        $title = $product instanceof \WP_Post ? $product->post_title : '';
        $excerpt = $product instanceof \WP_Post ? $product->post_excerpt : '';
        $content = $product instanceof \WP_Post ? $product->post_content : '';
        $status = $product instanceof \WP_Post ? $product->post_status : 'draft';
        $featured_image_id = $product instanceof \WP_Post ? (int) get_post_thumbnail_id( $product->ID ) : 0;
        $season = $product instanceof \WP_Post ? (string) $this->productMetadataService->get_product_meta( $product->ID, 'season', '' ) : '';
        $availability = $product instanceof \WP_Post ? (string) $this->productMetadataService->get_product_meta( $product->ID, 'availability', 'in_stock' ) : 'in_stock';
        $price = $product instanceof \WP_Post ? (string) $this->productMetadataService->get_product_meta( $product->ID, 'price', '' ) : '';
        $sku = $product instanceof \WP_Post ? (string) $this->productMetadataService->get_product_meta( $product->ID, 'sku', '' ) : '';
        $details = $product instanceof \WP_Post ? (string) $this->productMetadataService->get_product_meta( $product->ID, 'details', '' ) : '';
        $gallery = $product instanceof \WP_Post ? (array) $this->productMetadataService->get_product_meta( $product->ID, 'gallery', array() ) : array();
        $gallery_value = implode( ',', array_map( 'strval', array_filter( array_map( 'absint', $gallery ) ) ) );
        $selected_category_id = 0;
        $categories = get_terms(array(
            'taxonomy' => 'pm_product_cat',
            'hide_empty' => false,
        ));

        if ( $product instanceof \WP_Post ) {
            $selected_terms = wp_get_post_terms( $product->ID, 'pm_product_cat', array( 'fields' => 'ids' ) );
            if ( ! is_wp_error( $selected_terms ) && ! empty( $selected_terms ) ) {
                $selected_category_id = (int) $selected_terms[0];
            }
        }

        echo '<div class="wrap">';
        echo '<h1 class="wp-heading-inline">' . esc_html( $product instanceof \WP_Post ? __( 'Edit Product', 'product-manager' ) : __( 'Add New Product', 'product-manager' ) ) . '</h1>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="product-manager-form">';

        wp_nonce_field( 'product_manager_save_product', 'product_manager_product_nonce' );
        echo '<input type="hidden" name="action" value="product_manager_save_product" />';
        echo '<input type="hidden" name="product_id" value="' . esc_attr( $product instanceof \WP_Post ? (string) $product->ID : '0' ) . '" />';

        echo '<table class="form-table product-manager-form__table" role="presentation">';
        echo '<tbody>';
        echo '<tr class="product-manager-form__section-row"><th colspan="2"><div class="product-manager-form__section-title">' . esc_html__( 'General Information', 'product-manager' ) . '</div></th></tr>';
        echo '<tr><th scope="row"><label for="product_name">' . esc_html__( 'Product Name', 'product-manager' ) . '</label></th><td><input type="text" id="product_name" name="product_name" value="' . esc_attr( $title ) . '" class="regular-text" required /></td></tr>';
        echo '<tr><th scope="row"><label for="product_category">' . esc_html__( 'Category', 'product-manager' ) . '</label></th><td><select id="product_category" name="product_category" class="regular-text">';
        echo '<option value="0">' . esc_html__( 'Select a category', 'product-manager' ) . '</option>';
        if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
            foreach ( $categories as $category ) {
                echo '<option value="' . esc_attr( (string) $category->term_id ) . '"' . selected( $selected_category_id, $category->term_id, false ) . '>' . esc_html( $category->name ) . '</option>';
            }
        }
        echo '</select></td></tr>';
        echo '<tr><th scope="row"><label for="product_excerpt">' . esc_html__( 'Short Description', 'product-manager' ) . '</label></th><td><textarea id="product_excerpt" name="product_excerpt" rows="4" class="large-text">' . esc_textarea( $excerpt ) . '</textarea></td></tr>';
        echo '<tr><th scope="row"><label for="product_content">' . esc_html__( 'Description', 'product-manager' ) . '</label></th><td>';
        wp_editor( $content, 'product_content', array(
            'textarea_name' => 'product_content',
            'textarea_rows' => 12,
            'media_buttons' => false,
            'teeny' => true,
            'quicktags' => true,
        ) );
        echo '</td></tr>';

        echo '<tr class="product-manager-form__section-row"><th colspan="2"><div class="product-manager-form__section-title">' . esc_html__( 'Product Details', 'product-manager' ) . '</div></th></tr>';
        echo '<tr><th scope="row"><label for="product_season">' . esc_html__( 'Season / Collection', 'product-manager' ) . '</label></th><td><input type="text" id="product_season" name="product_season" value="' . esc_attr( $season ) . '" class="regular-text" /></td></tr>';
        echo '<tr><th scope="row"><label for="product_availability">' . esc_html__( 'Availability', 'product-manager' ) . '</label></th><td><select id="product_availability" name="product_availability" class="regular-text"><option value="in_stock"' . selected( $availability, 'in_stock', false ) . '>' . esc_html__( 'In Stock', 'product-manager' ) . '</option><option value="limited"' . selected( $availability, 'limited', false ) . '>' . esc_html__( 'Limited', 'product-manager' ) . '</option><option value="out_of_stock"' . selected( $availability, 'out_of_stock', false ) . '>' . esc_html__( 'Out of Stock', 'product-manager' ) . '</option></select></td></tr>';
        echo '<tr><th scope="row"><label for="product_price">' . esc_html__( 'Price', 'product-manager' ) . '</label></th><td><input type="text" id="product_price" name="product_price" value="' . esc_attr( $price ) . '" class="regular-text" placeholder="e.g. 4500" /></td></tr>';
        echo '<tr><th scope="row"><label for="product_sku">' . esc_html__( 'SKU', 'product-manager' ) . '</label></th><td><input type="text" id="product_sku" name="product_sku" value="' . esc_attr( $sku ) . '" class="regular-text" /></td></tr>';
        echo '<tr><th scope="row"><label for="product_details">' . esc_html__( 'Technical Details', 'product-manager' ) . '</label></th><td><textarea id="product_details" name="product_details" rows="6" class="large-text">' . esc_textarea( $details ) . '</textarea></td></tr>';
        echo '<tr><th scope="row"><label>' . esc_html__( 'Product Gallery', 'product-manager' ) . '</label></th><td>';
        echo '<input type="hidden" id="product_gallery" name="product_gallery" value="' . esc_attr( $gallery_value ) . '" />';
        echo '<button type="button" class="button" data-product-manager-gallery>' . esc_html__( 'Select images', 'product-manager' ) . '</button>';
        echo '<div id="product-gallery-preview" class="product-manager-gallery-preview">';
        if ( ! empty( $gallery ) ) {
            foreach ( $gallery as $gallery_image_id ) {
                $attachment_id = absint( $gallery_image_id );
                if ( $attachment_id <= 0 ) {
                    continue;
                }
                $gallery_image = wp_get_attachment_image( $attachment_id, array( 80, 80 ) );
                if ( $gallery_image ) {
                    echo '<span class="product-manager-gallery-item">' . $gallery_image . '</span>';
                }
            }
        }
        echo '</div>';
        echo '</td></tr>';

        echo '<tr class="product-manager-form__section-row"><th colspan="2"><div class="product-manager-form__section-title">' . esc_html__( 'Media & Publishing', 'product-manager' ) . '</div></th></tr>';
        echo '<tr><th scope="row"><label for="product_status">' . esc_html__( 'Status', 'product-manager' ) . '</label></th><td><select id="product_status" name="product_status" class="regular-text"><option value="draft"' . selected( $status, 'draft', false ) . '>' . esc_html__( 'Draft', 'product-manager' ) . '</option><option value="publish"' . selected( $status, 'publish', false ) . '>' . esc_html__( 'Publish', 'product-manager' ) . '</option></select></td></tr>';
        echo '<tr><th scope="row"><label>' . esc_html__( 'Featured Image', 'product-manager' ) . '</label></th><td>';
        echo '<input type="hidden" id="product_featured_image_id" name="product_featured_image_id" value="' . esc_attr( (string) $featured_image_id ) . '" />';
        echo '<div class="product-manager-featured-image-actions">';
        echo '<button type="button" class="button" id="select-featured-image">' . esc_html__( 'Set featured image', 'product-manager' ) . '</button>';
        echo '<button type="button" class="button" id="remove-featured-image"' . ( $featured_image_id > 0 ? '' : ' disabled="disabled"' ) . '>' . esc_html__( 'Remove featured image', 'product-manager' ) . '</button>';
        echo '</div>';
        echo '<div id="featured-image-preview" class="product-manager-featured-image-preview">';
        if ( $featured_image_id > 0 ) {
            $image = wp_get_attachment_image( $featured_image_id, array( 96, 96 ) );
            if ( $image ) {
                echo '<div class="product-manager-featured-image">' . $image . '</div>';
            }
        } else {
            echo '<p class="description">' . esc_html__( 'No featured image selected.', 'product-manager' ) . '</p>';
        }
        echo '</div>';
        echo '</td></tr>';
        echo '</tbody>';
        echo '</table>';

        submit_button( $product instanceof \WP_Post ? __( 'Update Product', 'product-manager' ) : __( 'Save Product', 'product-manager' ) );
        echo '</form>';
        echo '</div>';
    }
}
