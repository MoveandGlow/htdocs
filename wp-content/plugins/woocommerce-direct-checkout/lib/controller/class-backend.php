<?php
/**
 * Woocommerce-direct-checkout Plugin
 *
 * @package  WooCommerce Direct Checkout
 * @since    1.0.0
 */

namespace QuadLayers\WCDC\Controller;

use QuadLayers\WCDC\Plugin;

/**
 * Backend
 *
 * @class Plugin
 * @version 1.0.0
 */
class Backend {

	/**
	 * The single instance of the class.
	 *
	 * @var WCDC
	 */
	protected static $instance;

	/**
	 * Construct
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 99 );
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_tab' ), 50 );
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
	}

	/**
	 * Instance
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Enqueue scripts
	 */
	public function enqueue_scripts() {

		// 1326// filter by page stttings

		Plugin::instance()->register_scripts();

		wp_enqueue_script( 'qlwcdc-admin' );
	}

	/**
	 * Add tab
	 *
	 * @param array $settings_tabs Settings tabs.
	 */
	public function add_tab( $settings_tabs ) {
		$settings_tabs[ QLWCDC_PREFIX ] = esc_html__( 'Direct Checkout', 'woocommerce-direct-checkout' );
		return $settings_tabs;
	}

	/**
	 * Add menu
	 */
	public function add_menu() {
		add_submenu_page( 'woocommerce', esc_html__( 'Direct Checkout', 'woocommerce-direct-checkout' ), esc_html__( 'Direct Checkout', 'woocommerce-direct-checkout' ), 'manage_woocommerce', admin_url( 'admin.php?page=wc-settings&tab=' . sanitize_title( QLWCDC_PREFIX ) ) );
	}

}
