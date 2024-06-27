<?php
/**
 * Woocommerce-direct-checkout Plugin
 *
 * @package  WooCommerce Direct Checkout
 * @since    1.0.0
 */

namespace QuadLayers\WCDC;

use QuadLayers\WCDC\Controller\Backend;
use QuadLayers\WCDC\Controller\Archives;
use QuadLayers\WCDC\Controller\Checkout;
use QuadLayers\WCDC\Controller\General;
use QuadLayers\WCDC\Controller\Premium;
use QuadLayers\WCDC\Controller\Products;

/**
 * Plugin Main class
 *
 * @class Plugin
 * @version 1.0.0
 */
final class Plugin {

	/**
	 * The single instance of the class.
	 *
	 * @var WCDC
	 */
	protected static $instance;

	/**
	 * Construct
	 */
	private function __construct() {
		new Install();

		/**
		 * Load plugin textdomain.
		 */
		load_plugin_textdomain( 'woocommerce-direct-checkout', false, QLWCDC_PLUGIN_DIR . '/languages/' );
		add_action(
			'woocommerce_init',
			function() {
				new Backend();
				new General();
				new Archives();
				new Products();
				new Checkout();
				new Premium();
				/**
				 * Add premium CSS
				 */
				add_action( 'admin_footer', array( __CLASS__, 'add_premium_css' ) );
				do_action( 'wcdc_init' );
			}
		);
	}

	/**
	 * Register scripts
	 */
	public function register_scripts() {
		wp_register_script( 'qlwcdc-admin', plugins_url( '/assets/backend/qlwcdc-admin' . self::instance()->is_min() . '.js', QLWCDC_PLUGIN_FILE ), array( 'jquery' ), QLWCDC_PLUGIN_VERSION, true );
	}

	/**
	 * Is min
	 */
	public function is_min() {
		if ( ! defined( 'SCRIPT_DEBUG' ) || ! SCRIPT_DEBUG ) {
			return '.min';
		}
	}

	/**
	 * Get product option
	 *
	 * @param integer $product_id Product id.
	 * @param integer $meta_key Meta key.
	 * @param integer $default Default.
	 * @return string
	 */
	public function get_product_option( $product_id = null, $meta_key = null, $default = null ) {
		if ( ! $meta_key ) {
			return null;
		}

		if ( $product_id && metadata_exists( 'post', $product_id, $meta_key ) ) {

			$value = get_post_meta( $product_id, $meta_key, true );
			if ( $value ) {
				return $value;
			}
		}

		return get_option( $meta_key, $default );
	}

	/**
	 * Remove premium
	 */
	public static function add_premium_css() {
		?>
		<script>
		(function ($) {
			'use strict';
			$(window).on('load', function (e) {
			$('#qlwcdc_options .options_group').css({'opacity': '0.5', 'pointer-events': 'none'});
			$('label[for=qlwcdc_add_product_ajax]').closest('tr').addClass('qlwcdc-premium-field').css({'opacity': '0.5', 'pointer-events': 'none'});
			$('label[for=qlwcdc_add_product_ajax_alert]').closest('tr').addClass('qlwcdc-premium-field').css({'opacity': '0.5', 'pointer-events': 'none'});
			$('label[for=qlwcdc_add_checkout_cart]').closest('tr').addClass('qlwcdc-premium-field').css({'opacity': '0.5', 'pointer-events': 'none'});
			$('label[for=qlwcdc_add_checkout_cart_fields]').closest('tr').addClass('qlwcdc-premium-field').css({'opacity': '0.5', 'pointer-events': 'none'});
			$('label[for=qlwcdc_add_checkout_cart_class]').closest('tr').addClass('qlwcdc-premium-field').css({'opacity': '0.5', 'pointer-events': 'none'});
			$('label[for=qlwcdc_remove_checkout_columns]').closest('tr').addClass('qlwcdc-premium-field').css({'opacity': '0.5', 'pointer-events': 'none'});
			$('label[for=qlwcdc_remove_checkout_coupon_form]').closest('tr').addClass('qlwcdc-premium-field').css({'opacity': '0.5', 'pointer-events': 'none'});
			$('label[for=qlwcdc_remove_order_details_address]').closest('tr').addClass('qlwcdc-premium-field').css({'opacity': '0.5', 'pointer-events': 'none'});
			$('label[for=qlwcdc_add_product_quick_purchase]').closest('tr').addClass('qlwcdc-premium-field').css({'opacity': '0.5', 'pointer-events': 'none'});
			$('label[for=qlwcdc_add_product_quick_purchase_to]').closest('tr').addClass('qlwcdc-premium-field').css({'opacity': '0.5', 'pointer-events': 'none'});
			$('label[for=qlwcdc_add_product_quick_purchase_qty]').closest('tr').addClass('qlwcdc-premium-field').css({'opacity': '0.5', 'pointer-events': 'none'});
			$('label[for=qlwcdc_add_product_quick_purchase_type]').closest('tr').addClass('qlwcdc-premium-field').css({'opacity': '0.5', 'pointer-events': 'none'});
			$('label[for=qlwcdc_add_product_quick_purchase_class]').closest('tr').addClass('qlwcdc-premium-field').css({'opacity': '0.5', 'pointer-events': 'none'});
			$('label[for=qlwcdc_add_product_quick_purchase_text]').closest('tr').addClass('qlwcdc-premium-field').css({'opacity': '0.5', 'pointer-events': 'none'});
			$('label[for=qlwcdc_add_product_default_attributes]').closest('tr').addClass('qlwcdc-premium-field').css({'opacity': '0.5', 'pointer-events': 'none'});
			$('label[for=qlwcdc_add_archive_quick_view]').closest('tr').addClass('qlwcdc-premium-field').css({'opacity': '0.5', 'pointer-events': 'none'});
			});
		}(jQuery));
		</script>
		<?php

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
}

Plugin::instance();
