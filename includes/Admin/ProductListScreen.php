<?php
declare(strict_types=1);

namespace ProductManager\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ProductManager\Products\ProductQueryService;
use ProductManager\Products\ProductService;
use WP_List_Table;

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

final class ProductListScreen extends WP_List_Table
{
    private ProductService $productService;
    private ProductQueryService $productQueryService;

    public function __construct( ProductService $productService, ProductQueryService $productQueryService )
    {
        $this->productService = $productService;
        $this->productQueryService = $productQueryService;

        parent::__construct(array(
            'singular' => 'product',
            'plural' => 'products',
            'ajax' => false,
        ));
    }

    public function render(): void
    {
        if ( ! current_user_can( 'pm_manage_products' ) ) {
            wp_die( esc_html__( 'You do not have permission to manage products.', 'product-manager' ) );
        }

        $this->prepare_items();

        echo '<div class="wrap">';
        echo '<h1 class="wp-heading-inline">' . esc_html__( 'Products', 'product-manager' ) . '</h1>';
        echo ' <a href="' . esc_url( admin_url( 'admin.php?page=product-manager-add-product' ) ) . '" class="page-title-action">' . esc_html__( 'Add New Product', 'product-manager' ) . '</a>';

        $notice = isset( $_GET['pm_notice'] ) ? sanitize_key( wp_unslash( $_GET['pm_notice'] ) ) : '';
        if ( 'saved' === $notice ) {
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Product saved successfully.', 'product-manager' ) . '</p></div>';
        } elseif ( 'deleted' === $notice ) {
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Product deleted successfully.', 'product-manager' ) . '</p></div>';
        } elseif ( 'error' === $notice ) {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'The product could not be saved or deleted. Please try again.', 'product-manager' ) . '</p></div>';
        }

        $this->display();
        echo '</div>';
    }

    public function prepare_items(): void
    {
        $per_page = 20;
        $paged = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;
        $search = isset( $_GET['pm_search'] ) ? sanitize_text_field( wp_unslash( $_GET['pm_search'] ) ) : '';
        $category_id = isset( $_GET['pm_category'] ) ? absint( wp_unslash( $_GET['pm_category'] ) ) : 0;
        $status = isset( $_GET['pm_status'] ) ? sanitize_key( wp_unslash( $_GET['pm_status'] ) ) : '';

        $result = $this->productQueryService->get_products(array(
            'search' => $search,
            'category_id' => $category_id,
            'status' => $status,
            'paged' => $paged,
            'per_page' => $per_page,
        ));

        $this->items = $result['items'];

        $this->set_pagination_args(array(
            'total_items' => $result['total_items'],
            'per_page' => $result['per_page'],
            'total_pages' => (int) ceil( $result['total_items'] / max( 1, $result['per_page'] ) ),
        ));
    }

    public function get_columns(): array
    {
        return array(
            'title' => __( 'Title', 'product-manager' ),
            'thumbnail' => __( 'Thumbnail', 'product-manager' ),
            'category' => __( 'Category', 'product-manager' ),
            'status' => __( 'Status', 'product-manager' ),
            'date' => __( 'Date', 'product-manager' ),
            'actions' => __( 'Actions', 'product-manager' ),
        );
    }

    public function column_default( $item, $column_name ): string
    {
        return esc_html( $item[ $column_name ] ?? '' );
    }

    public function column_title( $item ): string
    {
        $title = esc_html( $item['title'] );
        $edit_link = esc_url( admin_url( 'admin.php?page=product-manager-edit-product&pm_product_id=' . absint( $item['ID'] ) ) );

        $actions = array(
            'edit' => '<a href="' . $edit_link . '">' . esc_html__( 'Edit', 'product-manager' ) . '</a>',
        );

        $delete_form = '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline;">';
        $delete_form .= '<input type="hidden" name="action" value="product_manager_delete_product" />';
        $delete_form .= wp_nonce_field( 'product_manager_delete_product', 'product_manager_delete_nonce', true, false );
        $delete_form .= '<input type="hidden" name="product_id" value="' . absint( $item['ID'] ) . '" />';
        $delete_form .= '<button type="submit" class="button-link" onclick="return confirm(\'' . esc_js( __( 'Delete this product?', 'product-manager' ) ) . '\');">' . esc_html__( 'Delete', 'product-manager' ) . '</button>';
        $delete_form .= '</form>';
        $actions['delete'] = $delete_form;

        return '<strong>' . $title . '</strong>' . $this->row_actions( $actions );
    }

    public function column_thumbnail( $item ): string
    {
        $thumbnail_id = absint( $item['thumbnail_id'] );

        if ( $thumbnail_id > 0 ) {
            $thumbnail = wp_get_attachment_image( $thumbnail_id, array( 48, 48 ) );
            if ( $thumbnail ) {
                return $thumbnail;
            }
        }

        return '—';
    }

    public function column_category( $item ): string
    {
        return esc_html( (string) ( $item['category'] ?? '' ) );
    }

    public function column_status( $item ): string
    {
        return esc_html( ucfirst( (string) ( $item['status'] ?? '' ) ) );
    }

    public function column_date( $item ): string
    {
        $timestamp = strtotime( (string) ( $item['date'] ?? '' ) );

        if ( false === $timestamp ) {
            return esc_html__( 'Unknown', 'product-manager' );
        }

        return esc_html( date_i18n( get_option( 'date_format' ), $timestamp ) );
    }

    public function column_actions( $item ): string
    {
        $edit_link = esc_url( admin_url( 'admin.php?page=product-manager-edit-product&pm_product_id=' . absint( $item['ID'] ) ) );
        $delete_form = '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline;">';
        $delete_form .= '<input type="hidden" name="action" value="product_manager_delete_product" />';
        $delete_form .= wp_nonce_field( 'product_manager_delete_product', 'product_manager_delete_nonce', true, false );
        $delete_form .= '<input type="hidden" name="product_id" value="' . absint( $item['ID'] ) . '" />';
        $delete_form .= '<button type="submit" class="button-link" onclick="return confirm(\'' . esc_js( __( 'Delete this product?', 'product-manager' ) ) . '\');">' . esc_html__( 'Delete', 'product-manager' ) . '</button>';
        $delete_form .= '</form>';

        return '<a href="' . $edit_link . '">' . esc_html__( 'Edit', 'product-manager' ) . '</a> | ' . $delete_form;
    }

    public function extra_tablenav( $which ): void
    {
        if ( 'top' !== $which ) {
            return;
        }

        $current_search = isset( $_GET['pm_search'] ) ? sanitize_text_field( wp_unslash( $_GET['pm_search'] ) ) : '';
        $current_category_id = isset( $_GET['pm_category'] ) ? absint( wp_unslash( $_GET['pm_category'] ) ) : 0;
        $current_status = isset( $_GET['pm_status'] ) ? sanitize_key( wp_unslash( $_GET['pm_status'] ) ) : '';
        $categories = get_terms(array(
            'taxonomy' => 'pm_product_cat',
            'hide_empty' => false,
        ));

        echo '<div class="alignleft actions">';
        echo '<form method="get" action="' . esc_url( admin_url( 'admin.php' ) ) . '">';
        echo '<input type="hidden" name="page" value="product-manager-products" />';
        echo '<label class="screen-reader-text" for="pm-search-input">' . esc_html__( 'Search products', 'product-manager' ) . '</label>';
        echo '<input type="search" name="pm_search" id="pm-search-input" value="' . esc_attr( $current_search ) . '" placeholder="' . esc_attr__( 'Search products', 'product-manager' ) . '" />';
        echo '<select name="pm_category">';
        echo '<option value="0">' . esc_html__( 'All categories', 'product-manager' ) . '</option>';
        if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
            foreach ( $categories as $category ) {
                echo '<option value="' . esc_attr( (string) $category->term_id ) . '"' . selected( $current_category_id, $category->term_id, false ) . '>' . esc_html( $category->name ) . '</option>';
            }
        }
        echo '</select>';
        echo '<select name="pm_status">';
        echo '<option value="">' . esc_html__( 'All statuses', 'product-manager' ) . '</option>';
        echo '<option value="draft"' . selected( $current_status, 'draft', false ) . '>' . esc_html__( 'Draft', 'product-manager' ) . '</option>';
        echo '<option value="publish"' . selected( $current_status, 'publish', false ) . '>' . esc_html__( 'Published', 'product-manager' ) . '</option>';
        echo '</select>';
        echo '<button type="submit" class="button">' . esc_html__( 'Filter', 'product-manager' ) . '</button>';

        if ( '' !== $current_search || $current_category_id > 0 || '' !== $current_status ) {
            echo ' <a href="' . esc_url( admin_url( 'admin.php?page=product-manager-products' ) ) . '" class="button">' . esc_html__( 'Clear', 'product-manager' ) . '</a>';
        }

        echo '</form>';
        echo '</div>';
    }

    public function no_items(): void
    {
        esc_html_e( 'No products found. Add a new product to get started.', 'product-manager' );
    }
}
