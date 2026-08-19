<?php
declare(strict_types=1);

namespace ProductManager\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TaxonomyService
{
    public function register(): void
    {
        $labels = array(
            'name' => _x( 'Product Categories', 'Taxonomy general name', 'product-manager' ),
            'singular_name' => _x( 'Product Category', 'Taxonomy singular name', 'product-manager' ),
            'menu_name' => __( 'Product Categories', 'product-manager' ),
            'all_items' => __( 'All Product Categories', 'product-manager' ),
            'edit_item' => __( 'Edit Product Category', 'product-manager' ),
            'view_item' => __( 'View Product Category', 'product-manager' ),
            'update_item' => __( 'Update Product Category', 'product-manager' ),
            'add_new_item' => __( 'Add New Product Category', 'product-manager' ),
            'new_item_name' => __( 'New Product Category Name', 'product-manager' ),
            'parent_item' => __( 'Parent Product Category', 'product-manager' ),
            'parent_item_colon' => __( 'Parent Product Category:', 'product-manager' ),
            'search_items' => __( 'Search Product Categories', 'product-manager' ),
            'popular_items' => __( 'Popular Product Categories', 'product-manager' ),
            'separate_items_with_commas' => __( 'Separate Product Categories with commas', 'product-manager' ),
            'choose_from_most_used' => __( 'Choose from the most used product categories', 'product-manager' ),
            'not_found' => __( 'No product categories found.', 'product-manager' ),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'hierarchical' => true,
            'show_ui' => true,
            'show_in_menu' => 'product-manager',
            'show_admin_column' => true,
            'show_in_rest' => false,
            'rewrite' => array( 'slug' => 'product-category' ),
            'capabilities' => array(
                'manage_terms' => 'pm_manage_categories',
                'edit_terms' => 'pm_manage_categories',
                'delete_terms' => 'pm_manage_categories',
                'assign_terms' => 'pm_edit_products',
            ),
        );

        register_taxonomy( 'pm_product_cat', array( 'pm_product' ), $args );
    }
}
