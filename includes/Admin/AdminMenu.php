<?php
declare(strict_types=1);

namespace ProductManager\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ProductManager\Core\CapabilityService;

final class AdminMenu
{
    private CapabilityService $capabilityService;
    private DashboardScreen $dashboardScreen;
    private ProductListScreen $productListScreen;
    private ProductFormScreen $productFormScreen;
    private SettingsScreen $settingsScreen;
    private CompanySettingsScreen $companySettingsScreen;

    public function __construct( CapabilityService $capabilityService, DashboardScreen $dashboardScreen, ProductListScreen $productListScreen, ProductFormScreen $productFormScreen, SettingsScreen $settingsScreen, CompanySettingsScreen $companySettingsScreen )
    {
        $this->capabilityService = $capabilityService;
        $this->dashboardScreen = $dashboardScreen;
        $this->productListScreen = $productListScreen;
        $this->productFormScreen = $productFormScreen;
        $this->settingsScreen = $settingsScreen;
        $this->companySettingsScreen = $companySettingsScreen;
    }

    public function register(): void
    {
        add_action( 'admin_menu', array( $this, 'registerMenus' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAssets' ) );
    }

    public function registerMenus(): void
    {
        add_menu_page(
            __( 'Product Manager', 'product-manager' ),
            __( 'Product Manager', 'product-manager' ),
            'pm_manage_products',
            'product-manager',
            array( $this->dashboardScreen, 'render' ),
            'dashicons-archive',
            25
        );

        add_submenu_page(
            'product-manager',
            __( 'Dashboard', 'product-manager' ),
            __( 'Dashboard', 'product-manager' ),
            'pm_manage_products',
            'product-manager',
            array( $this->dashboardScreen, 'render' )
        );

        add_submenu_page(
            'product-manager',
            __( 'Products', 'product-manager' ),
            __( 'Products', 'product-manager' ),
            'pm_manage_products',
            'product-manager-products',
            array( $this->productListScreen, 'render' )
        );

        add_submenu_page(
            'product-manager',
            __( 'Categories', 'product-manager' ),
            __( 'Categories', 'product-manager' ),
            'pm_manage_categories',
            'product-manager-categories',
            array( $this, 'redirectToCategories' )
        );

        add_submenu_page(
            'product-manager',
            __( 'Add New Product', 'product-manager' ),
            __( 'Add New Product', 'product-manager' ),
            'pm_manage_products',
            'product-manager-add-product',
            array( $this->productFormScreen, 'renderAdd' )
        );

        add_submenu_page(
            'product-manager',
            __( 'Edit Product', 'product-manager' ),
            __( 'Edit Product', 'product-manager' ),
            'pm_manage_products',
            'product-manager-edit-product',
            array( $this->productFormScreen, 'renderEdit' )
        );

        add_submenu_page(
            'product-manager',
            __( 'Company Settings', 'product-manager' ),
            __( 'Company Settings', 'product-manager' ),
            'pm_manage_products',
            'product-manager-company-settings',
            array( $this->companySettingsScreen, 'render' )
        );

        add_submenu_page(
            'product-manager',
            __( 'Settings', 'product-manager' ),
            __( 'Settings', 'product-manager' ),
            'pm_manage_products',
            'product-manager-settings',
            array( $this, 'renderSettingsPage' )
        );
    }

    public function redirectToCategories(): void
    {
        wp_safe_redirect( admin_url( 'edit-tags.php?taxonomy=pm_product_cat&post_type=pm_product' ) );
        exit;
    }

    public function renderSettingsPage(): void
    {
        $this->settingsScreen->render();
    }

    public function enqueueAssets( string $hook_suffix ): void
    {
        $allowed_hooks = array(
            'toplevel_page_product-manager',
            'product-manager_page_product-manager-products',
            'product-manager_page_product-manager-add-product',
            'product-manager_page_product-manager-edit-product',
            'product-manager_page_product-manager-company-settings',
            'product-manager_page_product-manager-settings',
        );

        if ( ! in_array( $hook_suffix, $allowed_hooks, true ) ) {
            return;
        }

        wp_enqueue_style(
            'product-manager-admin-css',
            plugins_url( 'assets/css/admin.css', PRODUCT_MANAGER_PLUGIN_FILE ),
            array(),
            PRODUCT_MANAGER_VERSION
        );

        wp_enqueue_script(
            'product-manager-admin-js',
            plugins_url( 'assets/js/admin.js', PRODUCT_MANAGER_PLUGIN_FILE ),
            array( 'jquery', 'wp-mediaelement' ),
            PRODUCT_MANAGER_VERSION,
            true
        );

        wp_enqueue_media();
    }
}
