<?php
/**
 * Woocommerce-direct-checkout Plugin
 *
 * @package  WooCommerce Direct Checkout
 * @since    1.0.0
 */

namespace QuadLayers\WCDC\View\Frontend;

use QuadLayers\WCDC\Plugin;

/**
 * Products
 *
 * @class Plugin
 * @version 1.0.0
 */
class Products {

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
		add_filter( 'woocommerce_product_single_add_to_cart_text', array( $this, 'add_product_text' ), 10, 2 );
		// WooCommerce Product Addon Compatibility.
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_add_cart_item' ), -10, 4 );
	}

	/**
	 * Instance
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Add product text
	 *
	 * @param string  $text Text.
	 * @param Product $product Product.
	 */
	public function add_product_text( $text, $product ) {

		if ( 'yes' === Plugin::instance()->get_product_option( $product->get_id(), 'qlwcdc_add_product_text' ) ) {
			// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
			$text = esc_html__( Plugin::instance()->get_product_option( $product->get_id(), 'qlwcdc_add_product_text_content' ), 'woocommerce-direct-checkout' );
		}

		return $text;
	}

	/**
	 * Validate add cart item
	 *
	 * @param array $passed Passed.
	 * @param int   $product_id Product id.
	 * @param int   $qty Quantity.
	 * @param array $post_data Post data.
	 */
	public function validate_add_cart_item( $passed, $product_id, $qty, $post_data = null ) {

		if ( class_exists( 'WC_Product_Addons_Helper' ) ) {

			if ( isset( $_REQUEST['add-to-cart'] ) && absint( $_REQUEST['add-to-cart'] ) > 0 ) {

				$product_addons = \WC_Product_Addons_Helper::get_product_addons( $product_id );

				if ( is_array( $product_addons ) && ! empty( $product_addons ) ) {

					foreach ( $product_addons as $addon ) {

						if ( isset( $_REQUEST[ 'addon-' . $addon['field_name'] ] ) ) {
							// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
							$_POST[ 'addon-' . $addon['field_name'] ] = wc_clean( $_REQUEST[ 'addon-' . $addon['field_name'] ] );

						}
					}
				}
			}
		}

		return $passed;
	}
}
