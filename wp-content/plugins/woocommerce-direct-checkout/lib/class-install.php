<?php
/**
 * Woocommerce-direct-checkout Install
 *
 * @package  WooCommerce Direct Checkout
 * @since    1.0.0
 */

namespace QuadLayers\WCDC;

/**
 * Install
 *
 * @class Install
 * @version 1.0.0
 */
class Install {

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
		add_action( 'wcdc_activation', array( __CLASS__, 'activation' ) );
		add_action( 'wcdc_deactivation', array( __CLASS__, 'deactivation' ) );
		self::import_old_settings();
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
	 * Activation
	 */
	public static function activation() {
		self::add_settings();
	}

	/**
	 * Aeactivation
	 */
	public static function deactivation() {

	}

	/**
	 * Add settings
	 */
	private static function add_settings() {
		if ( 'yes' === get_option( 'woocommerce_cart_redirect_after_add' ) ) {
			add_option( 'qlwcdc_add_to_cart', 'redirect' );
			add_option( 'qlwcdc_add_to_cart_redirect_page', 'cart' );
		}
	}

	/**
	 * Import old settings
	 */
	public static function import_old_settings() {

		global $wpdb;

		if ( ! get_option( 'qlwcdc_wcd_imported2' ) ) {

			if ( get_option( 'direct_checkout_pro_enabled', get_option( 'direct_checkout_enabled' ) ) ) {

				$url = get_option( 'direct_checkout_pro_cart_redirect_url', get_option( 'direct_checkout_cart_redirect_url' ) );

				if ( wc_get_cart_url() === $url ) {
					$val = 'cart';
				} elseif ( filter_var( $url, FILTER_VALIDATE_URL ) !== false && wc_get_checkout_url() != $url ) {
					$val = 'url';
				} else {
					$val = 'checkout';
				}

				/*
				Old
				add_option('qlwcdc_add_product_cart', 'redirect');
				add_option('qlwcdc_add_product_cart_redirect_page', $val);
				add_option('qlwcdc_add_product_cart_redirect_url', $url);

				add_option('qlwcdc_add_archive_cart', 'redirect');
				add_option('qlwcdc_add_archive_cart_redirect_page', $val);
				add_option('qlwcdc_add_archive_cart_redirect_url', $url);
				*/

				add_option( 'qlwcdc_add_to_cart', 'redirect' );
				add_option( 'qlwcdc_add_to_cart_redirect_page', $val );
				add_option( 'qlwcdc_add_to_cart_redirect_url', $url );
			}
			$text = get_option( 'direct_checkout_cart_button_text', get_option( 'direct_checkout_cart_button_text' ) );
			if ( $text ) {
				add_option( 'qlwcdc_add_product_text', 'yes' );
				add_option( 'qlwcdc_add_product_text_content', $text );
				add_option( 'qlwcdc_add_archive_text', 'yes' );
				add_option( 'qlwcdc_add_archive_text_content', $text );
				add_option(
					'qlwcdc_add_archive_text_in',
					array(
						'simple',
						'grouped',
						'virtual',
						'variable',
						'downloadable',
					)
				);
			}

			$keys = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->postmeta} WHERE meta_key = %s", '_direct_checkout_pro_enabled' ) );

			if ( count( $keys ) ) {
				foreach ( $keys as $key ) {
					if ( 'yes' == $key->meta_value ) {
						$text = get_post_meta( $key->post_id, '_direct_checkout_pro_cart_button_text', true );
						if ( $text ) {
							add_post_meta( $key->post_id, 'qlwcdc_add_product_text', 'yes', true );
							add_post_meta( $key->post_id, 'qlwcdc_add_product_text_content', $text, true );
						}
					}
				}
			}

			delete_option( 'qlwcdc_wcd_imported' );
			update_option( 'qlwcdc_wcd_imported2', true );
		}
	}

}
