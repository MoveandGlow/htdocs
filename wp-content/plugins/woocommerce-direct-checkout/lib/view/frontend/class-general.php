<?php
/**
 * Woocommerce-direct-checkout Plugin
 *
 * @package  WooCommerce Direct Checkout
 * @since    1.0.0
 */

namespace QuadLayers\WCDC\View\Frontend;

/**
 * General
 *
 * @class Plugin
 * @version 1.0.0
 */
class General {

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
		 add_filter( 'woocommerce_get_script_data', array( $this, 'add_to_cart_params' ) );
		add_filter( 'wc_add_to_cart_message_html', array( $this, 'add_to_cart_message' ) );
		add_filter( 'woocommerce_add_to_cart_redirect', array( $this, 'add_to_cart_redirect' ) );
		add_filter( 'woocommerce_get_cart_url', array( $this, 'replace_cart_url' ) );

		if ( 'redirect' === get_option( 'qlwcdc_add_to_cart' ) ) {
			add_filter( 'option_woocommerce_enable_ajax_add_to_cart', '__return_false' );
			add_filter( 'option_woocommerce_cart_redirect_after_add', '__return_false' );
		}
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
	 * Add to cart params
	 *
	 * @param array $params Parameters.
	 */
	public function add_to_cart_params( $params ) {
		if ( 'yes' === get_option( 'qlwcdc_add_to_cart_link' ) ) {
			if ( isset( $params['cart_url'] ) ) {
				$params['cart_url'] = wc_get_checkout_url();
			}
			if ( isset( $params['i18n_view_cart'] ) ) {
				$params['i18n_view_cart'] = esc_html__( 'Checkout', 'woocommerce-direct-checkout' );
			}
		}

		return $params;
	}

	/**
	 * Add to cart message
	 *
	 * @param string $message Message.
	 */
	public function add_to_cart_message( $message ) {
		if ( 'yes' === get_option( 'qlwcdc_add_to_cart_message' ) ) {

			$message = str_replace( wc_get_page_permalink( 'cart' ), wc_get_page_permalink( 'checkout' ), $message );

			$message = str_replace( esc_html__( 'View cart', 'woocommerce' ), esc_html__( 'Checkout', 'woocommerce' ), $message );
		}

		return $message;
	}

	/**
	 * Add to cart redirect
	 *
	 * @param string $url URL.
	 */
	public function add_to_cart_redirect( $url ) {
		if ( 'redirect' === get_option( 'qlwcdc_add_to_cart' ) ) {
			if ( 'cart' === get_option( 'qlwcdc_add_to_cart_redirect_page' ) ) {
				$url = wc_get_cart_url();
			} elseif ( 'url' === get_option( 'qlwcdc_add_to_cart_redirect_page' ) ) {
				$url = get_option( 'qlwcdc_add_to_cart_redirect_url' );
			} else {
				$url = wc_get_checkout_url();
			}
		}

		return $url;
	}

	/**
	 * Replace cart url
	 *
	 * @param string $url URL.
	 */
	public function replace_cart_url( $url ) {
		if ( wp_doing_ajax() || ( ! is_admin() && ! is_checkout() && 'no' !== get_option( 'qlwcdc_replace_cart_url', 'no' ) ) ) {

			// Empty checkout redirect to custom/cart/shop.
			if ( method_exists( WC(), 'cart' ) && method_exists( WC()->cart, 'is_empty' ) && WC()->cart->is_empty() ) {
				return get_permalink( wc_get_page_id( 'shop' ) );
			}

			if ( 'checkout' === get_option( 'qlwcdc_replace_cart_url' ) ) {
				return wc_get_checkout_url();
			}

			if ( 'custom' === get_option( 'qlwcdc_replace_cart_url' ) && get_option( 'qlwcdc_replace_cart_url_custom' ) ) {
				return get_option( 'qlwcdc_replace_cart_url_custom' );
			}
		}

		return $url;
	}
}
