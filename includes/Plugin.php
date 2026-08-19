<?php
declare(strict_types=1);

namespace ProductManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ProductManager\Admin\AdminMenu;
use ProductManager\Admin\CompanySettingsScreen;
use ProductManager\Admin\DashboardScreen;
use ProductManager\Admin\ProductActions;
use ProductManager\Admin\ProductFormScreen;
use ProductManager\Admin\ProductListScreen;
use ProductManager\Admin\SettingsScreen;
use ProductManager\Company\CompanySettingsService;
use ProductManager\Core\CapabilityService;
use ProductManager\Core\PostTypeService;
use ProductManager\Core\TaxonomyService;
use ProductManager\Frontend\Frontend;
use ProductManager\Multilingual\PolylangBridge;
use ProductManager\Products\ProductMetadataService;
use ProductManager\Products\ProductQueryService;
use ProductManager\Products\ProductService;

final class Plugin
{
    private const REWRITE_RULES_VERSION = 2;

    private PostTypeService $postTypeService;
    private TaxonomyService $taxonomyService;
    private CapabilityService $capabilityService;
    private ProductService $productService;
    private ProductQueryService $productQueryService;
    private ProductMetadataService $productMetadataService;
    private CompanySettingsService $companySettingsService;
    private DashboardScreen $dashboardScreen;
    private SettingsScreen $settingsScreen;
    private CompanySettingsScreen $companySettingsScreen;
    private Frontend $frontend;
    private bool $adminBooted = false;

    public function __construct()
    {
        $this->postTypeService = new PostTypeService();
        $this->taxonomyService = new TaxonomyService();
        $this->capabilityService = new CapabilityService();
        $this->productService = new ProductService();
        $this->productQueryService = new ProductQueryService();
        $this->productMetadataService = new ProductMetadataService();
        $this->companySettingsService = new CompanySettingsService();
        $this->dashboardScreen = new DashboardScreen( $this->productQueryService );
        $this->settingsScreen = new SettingsScreen();
        $this->companySettingsScreen = new CompanySettingsScreen( $this->companySettingsService );
        $this->frontend = new Frontend( $this->productQueryService );

        add_action( 'admin_init', array( $this, 'register_admin_actions' ) );
        add_action( 'admin_init', array( $this->settingsScreen, 'register' ) );
        add_filter( 'pll_get_post_types', array( PolylangBridge::class, 'register_content_type_support' ), 10, 2 );
        add_filter( 'pll_get_taxonomies', array( PolylangBridge::class, 'register_taxonomy_support' ), 10, 2 );
    }

    public function register_admin_actions(): void
    {
        static $registered = false;

        if ( $registered ) {
            return;
        }

        $registered = true;

        $productActions = new ProductActions( $this->productService, $this->productMetadataService, $this->companySettingsService );
        $productActions->register();
    }

    public function boot(): void
    {
        $this->boot_frontend();

        if ( ! is_admin() ) {
            return;
        }

        if ( did_action( 'admin_menu' ) ) {
            $this->boot_admin();
            return;
        }

        add_action( 'admin_menu', array( $this, 'boot_admin' ), 0 );
    }

    public function boot_frontend(): void
    {
        $this->postTypeService->register();
        $this->taxonomyService->register();
        $this->capabilityService->ensure_capabilities();

        $this->frontend->register();

        if ( (int) get_option( 'product_manager_rewrite_rules_version', 0 ) < self::REWRITE_RULES_VERSION ) {
            flush_rewrite_rules();
            update_option( 'product_manager_rewrite_rules_version', self::REWRITE_RULES_VERSION );
        }
    }

    public function boot_admin(): void
    {
        $this->capabilityService->ensure_capabilities();

        if ( $this->adminBooted ) {
            return;
        }

        $this->adminBooted = true;

        $this->register_admin_actions();

        $productListScreen = new ProductListScreen( $this->productService, $this->productQueryService );
        $productFormScreen = new ProductFormScreen( $this->productService, $this->productMetadataService, $this->companySettingsService );

        $adminMenu = new AdminMenu( $this->capabilityService, $this->dashboardScreen, $productListScreen, $productFormScreen, $this->settingsScreen, $this->companySettingsScreen );
        $adminMenu->register();
    }
}
