<?php
declare(strict_types=1);

namespace ProductManager\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ProductManager\Products\ProductQueryService;

final class DashboardScreen
{
    private ProductQueryService $productQueryService;

    public function __construct( ProductQueryService $productQueryService )
    {
        $this->productQueryService = $productQueryService;
    }

    public function render(): void
    {
        if ( ! current_user_can( 'pm_manage_products' ) ) {
            wp_die( esc_html__( 'You do not have permission to view the dashboard.', 'product-manager' ) );
        }

        $stats = $this->get_stats();
        $recent_products = $this->get_recent_products();

        echo '<div class="wrap product-manager-dashboard">';
        echo '<h1 class="wp-heading-inline">' . esc_html__( 'Dashboard', 'product-manager' ) . '</h1>';
        echo '<div class="product-manager-dashboard__actions">';
        echo '<a href="' . esc_url( admin_url( 'admin.php?page=product-manager-add-product' ) ) . '" class="button button-primary">' . esc_html__( 'Add New Product', 'product-manager' ) . '</a>';
        echo '<a href="' . esc_url( admin_url( 'admin.php?page=product-manager-company-settings' ) ) . '" class="button">' . esc_html__( 'Company Settings', 'product-manager' ) . '</a>';
        echo '</div>';

        echo '<div class="product-manager-dashboard__grid">';
        foreach ( $stats as $stat ) {
            echo '<div class="product-manager-dashboard__card">';
            echo '<div class="product-manager-dashboard__label">' . esc_html( $stat['label'] ) . '</div>';
            echo '<div class="product-manager-dashboard__value">' . esc_html( (string) $stat['value'] ) . '</div>';
            echo '</div>';
        }
        echo '</div>';

        echo '<div class="product-manager-dashboard__panel">';
        echo '<h2>' . esc_html__( 'Recent Products', 'product-manager' ) . '</h2>';

        if ( empty( $recent_products ) ) {
            echo '<p>' . esc_html__( 'No products yet. Create your first product to get started.', 'product-manager' ) . '</p>';
        } else {
            echo '<table class="wp-list-table widefat striped">';
            echo '<thead><tr><th>' . esc_html__( 'Product', 'product-manager' ) . '</th><th>' . esc_html__( 'Status', 'product-manager' ) . '</th><th>' . esc_html__( 'Category', 'product-manager' ) . '</th></tr></thead><tbody>';

            foreach ( $recent_products as $product ) {
                $edit_link = admin_url( 'admin.php?page=product-manager-edit-product&pm_product_id=' . absint( $product['ID'] ) );
                echo '<tr>';
                echo '<td><a href="' . esc_url( $edit_link ) . '">' . esc_html( $product['title'] ) . '</a></td>';
                echo '<td>' . esc_html( ucfirst( (string) $product['status'] ) ) . '</td>';
                echo '<td>' . esc_html( (string) ( $product['category'] ?? '' ) ) . '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        }

        echo '</div>';
        echo '</div>';
    }

    private function get_stats(): array
    {
        $counts = wp_count_posts( 'pm_product' );
        $terms = get_terms(
            array(
                'taxonomy' => 'pm_product_cat',
                'hide_empty' => true,
            )
        );

        $published = isset( $counts->publish ) ? (int) $counts->publish : 0;
        $draft = isset( $counts->draft ) ? (int) $counts->draft : 0;
        $total = $published + $draft;
        $category_count = is_wp_error( $terms ) ? 0 : count( $terms );
        $available = $this->count_products_by_availability();

        return array(
            array(
                'label' => __( 'Total Products', 'product-manager' ),
                'value' => $total,
            ),
            array(
                'label' => __( 'Published', 'product-manager' ),
                'value' => $published,
            ),
            array(
                'label' => __( 'Drafts', 'product-manager' ),
                'value' => $draft,
            ),
            array(
                'label' => __( 'Categories', 'product-manager' ),
                'value' => $category_count,
            ),
            array(
                'label' => __( 'Available', 'product-manager' ),
                'value' => $available,
            ),
        );
    }

    private function get_recent_products(): array
    {
        $result = $this->productQueryService->get_products(
            array(
                'paged' => 1,
                'per_page' => 5,
            )
        );

        return is_array( $result['items'] ) ? $result['items'] : array();
    }

    private function count_products_by_availability(): int
    {
        $query = new \WP_Query(
            array(
                'post_type' => 'pm_product',
                'post_status' => array( 'publish', 'draft' ),
                'posts_per_page' => -1,
                'meta_key' => 'pm_product_availability',
                'meta_value' => 'in_stock',
                'fields' => 'ids',
                'no_found_rows' => true,
            )
        );

        return (int) $query->post_count;
    }
}
