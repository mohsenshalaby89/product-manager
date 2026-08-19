<?php
/**
 * Plugin Name: Product Manager
 * Description: A standalone WordPress product catalog and product management foundation for company product listings and catalog pages.
 * Version: 1.0.3
 * Author: Mohsen Shalaby | <a target="_blank" href="https://gdh-eg.com">Graphic Design House</a>
 * Author URI: https://gdh-eg.com
 * Plugin URI: https://github.com/mohsenshalaby89/product-manager
 * Text Domain: product-manager
 */
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
	return;
}

if ( ! function_exists( 'add_action' ) ) {
	return;
}

if ( ! defined( 'PRODUCT_MANAGER_VERSION' ) ) {
	define( 'PRODUCT_MANAGER_VERSION', '1.0.3' );
}

define( 'PRODUCT_MANAGER_PLUGIN_FILE', __FILE__ );
define( 'PRODUCT_MANAGER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PRODUCT_MANAGER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PRODUCT_MANAGER_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'PRODUCT_MANAGER_TEXT_DOMAIN', 'product-manager' );
define( 'PRODUCT_MANAGER_PRODUCT_BASE_SLUG', 'products' );

require_once PRODUCT_MANAGER_PLUGIN_DIR . 'includes/Autoloader.php';

ProductManager\Autoloader::register();

register_activation_hook( __FILE__, array( 'ProductManager\\Core\\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ProductManager\\Core\\Deactivator', 'deactivate' ) );

$plugin = new ProductManager\Plugin();
add_action( 'init', array( $plugin, 'boot' ) );