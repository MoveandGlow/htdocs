<?php

/**
 * Plugin Name:             WooCommerce Direct Checkout
 * Plugin URI:              https://quadlayers.com/products/woocommerce-direct-checkout/
 * Description:             Simplifies the checkout process to improve your sales rate.
 * Version:                 3.3.4
 * Text Domain:             woocommerce-direct-checkout
 * Author:                  QuadLayers
 * Author URI:              https://quadlayers.com
 * License:                 GPLv3
 * Domain Path:             /languages
 * Request at least:        4.7.0
 * Tested up to:            6.5
 * Requires PHP:            5.6
 * WC requires at least:    4.0
 * WC tested up to:         8.9
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Definition globals varibles
 */
define( 'QLWCDC_PLUGIN_NAME', 'WooCommerce Direct Checkout' );
define( 'QLWCDC_PLUGIN_VERSION', '3.3.4' );
define( 'QLWCDC_PLUGIN_FILE', __FILE__ );
define( 'QLWCDC_PLUGIN_DIR', __DIR__ . DIRECTORY_SEPARATOR );
define( 'QLWCDC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'QLWCDC_PREFIX', 'qlwcdc' );
define( 'QLWCDC_WORDPRESS_URL', 'https://wordpress.org/plugins/woocommerce-direct-checkout/' );
define( 'QLWCDC_DOCUMENTATION_URL', 'https://quadlayers.com/documentation/woocommerce-direct-checkout/?utm_source=qlwcdc_admin' );
define( 'QLWCDC_SUPPORT_URL', 'https://quadlayers.com/account/support/?utm_source=qlwcdc_admin' );
define( 'QLWCDC_DEMO_URL', 'https://quadlayers.com/woocommerce-direct-checkout/?utm_source=qlwcdc_admin' );
define( 'QLWCDC_PREMIUM_SELL_URL', 'https://quadlayers.com/products/woocommerce-direct-checkout/?utm_source=qlwcdc_admin' );

/**
 * Load composer autoload
 */
require_once __DIR__ . '/vendor/autoload.php';
/**
 * Load vendor_packages packages
 */
require_once __DIR__ . '/vendor_packages/wp-i18n-map.php';
require_once __DIR__ . '/vendor_packages/wp-dashboard-widget-news.php';
require_once __DIR__ . '/vendor_packages/wp-plugin-table-links.php';
require_once __DIR__ . '/vendor_packages/wp-notice-plugin-required.php';
require_once __DIR__ . '/vendor_packages/wp-notice-plugin-promote.php';
require_once __DIR__ . '/vendor_packages/wp-plugin-suggestions.php';
require_once __DIR__ . '/vendor_packages/wp-plugin-install-tab.php';
/**
 * Load plugin classes
 */
require_once __DIR__ . '/lib/class-plugin.php';
/**
 * Plugin activation hook
 */
register_activation_hook(
	__FILE__,
	function() {
		do_action( 'wcdc_activation' );
	}
);

/**
 * Plugin activation hook
 */
register_deactivation_hook(
	__FILE__,
	function() {
		do_action( 'wcdc_deactivation' );
	}
);

/**
 * Declare compatibility with WooCommerce Custom Order Tables.
 */
add_action(
	'before_woocommerce_init',
	function() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

/**
 * Declare incompatibility with WooCommerce Cart & Checkout Blocks.
 */
add_action(
	'before_woocommerce_init',
	function() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, false );
		}
	}
);
