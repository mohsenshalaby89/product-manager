<?php
declare(strict_types=1);

namespace ProductManager\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PostTypeService
{
    public function register(): void
    {
        $labels = array(
            'name' => _x( 'Products', 'Post type general name', 'product-manager' ),
            'singular_name' => _x( 'Product', 'Post type singular name', 'product-manager' ),
            'menu_name' => __( 'Products', 'product-manager' ),
            'add_new' => __( 'Add New', 'product-manager' ),
            'add_new_item' => __( 'Add New Product', 'product-manager' ),
            'edit_item' => __( 'Edit Product', 'product-manager' ),
            'new_item' => __( 'New Product', 'product-manager' ),
            'view_item' => __( 'View Product', 'product-manager' ),
            'view_items' => __( 'View Products', 'product-manager' ),
            'search_items' => __( 'Search Products', 'product-manager' ),
            'not_found' => __( 'No products found.', 'product-manager' ),
            'not_found_in_trash' => __( 'No products found in Trash.', 'product-manager' ),
            'all_items' => __( 'All Products', 'product-manager' ),
        );

        $args = array(
            'labels' => $labels,
            'description' => __( 'A reusable product catalog and product management entity.', 'product-manager' ),
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => false,
            'menu_icon' => 'dashicons-archive',
            'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
            'has_archive' => false,
            'rewrite' => array( 'slug' => PRODUCT_MANAGER_PRODUCT_BASE_SLUG, 'with_front' => false ),
            'show_in_rest' => false,
            'publicly_queryable' => true,
            'exclude_from_search' => false,
            'query_var' => true,
            'map_meta_cap' => true,
            'capabilities' => array(
                'edit_post' => 'pm_edit_products',
                'read_post' => 'pm_edit_products',
                'delete_post' => 'pm_delete_products',
                'edit_posts' => 'pm_edit_products',
                'edit_others_posts' => 'pm_edit_others_products',
                'publish_posts' => 'pm_publish_products',
                'read_private_posts' => 'pm_manage_products',
                'delete_posts' => 'pm_delete_products',
                'delete_private_posts' => 'pm_delete_products',
                'delete_published_posts' => 'pm_delete_products',
                'delete_others_posts' => 'pm_delete_products',
                'edit_private_posts' => 'pm_edit_products',
                'edit_published_posts' => 'pm_edit_products',
                'create_posts' => 'pm_edit_products',
            ),
            'taxonomies' => array( 'pm_product_cat' ),
        );

        register_post_type( 'pm_product', $args );
    }
}
