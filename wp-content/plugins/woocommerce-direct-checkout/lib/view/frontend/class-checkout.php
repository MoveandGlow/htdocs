<?php
/**
 * Woocommerce-direct-checkout Plugin
 *
 * @package  WooCommerce Direct Checkout
 * @since    1.0.0
 */

namespace QuadLayers\WCDC\View\Frontend;

/**
 * Checkout
 *
 * @class Plugin
 * @version 1.0.0
 */
class Checkout {

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
		add_filter( 'woocommerce_checkout_fields', array( $this, 'remove_checkout_fields' ) );

		// add_filter('woocommerce_form_field_args', array($this, 'country_hidden_field_args'), 10, 4);.

		// add_filter('woocommerce_form_field_country_hidden', array($this, 'country_hidden_field'), 10, 4);.

		add_filter( 'woocommerce_countries_allowed_countries', array( $this, 'remove_allowed_countries' ) );

		add_action( 'woocommerce_before_checkout_form', array( $this, 'remove_country_css' ) );

		add_filter( 'woocommerce_enable_order_notes_field', array( $this, 'remove_checkout_order_commens' ) );
		add_filter( 'option_woocommerce_ship_to_destination', array( $this, 'remove_checkout_shipping_address' ), 10, 3 );

		if ( 'yes' === get_option( 'qlwcdc_remove_checkout_privacy_policy_text' ) ) {
			remove_action( 'woocommerce_checkout_terms_and_conditions', 'wc_checkout_privacy_policy_text', 20 );
		}

		if ( 'yes' === get_option( 'qlwcdc_remove_checkout_terms_and_conditions' ) ) {
			add_filter( 'woocommerce_checkout_show_terms', '__return_false' );
			remove_action( 'woocommerce_checkout_terms_and_conditions', 'wc_terms_and_conditions_page_content', 30 );
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
	 * Remove checkout fields
	 *
	 * @param array $fields Fields.
	 */
	public function remove_checkout_fields( $fields ) {
		$remove = get_option( 'qlwcdc_remove_checkout_fields', array() );
		if ( $remove ) {

			foreach ( $remove as $id => $key ) {
				// We need to remove both fields otherwise will be required.
				if ( 'country' == $key ) {
					continue;
				}
				unset( $fields['billing'][ 'billing_' . $key ] );
				unset( $fields['shipping'][ 'shipping_' . $key ] );
			}
		}

		return $fields;
	}

	/**
	 * Remove allowed countries
	 *
	 * @param array $countries Countries.
	 */
	public function remove_allowed_countries( $countries ) {
		$remove = get_option( 'qlwcdc_remove_checkout_fields', array() );

		if ( in_array( 'country', (array) $remove ) ) {

			if ( isset( WC()->countries ) && method_exists( WC()->countries, 'get_base_country' ) ) {

				$base = WC()->countries->get_base_country();

				if ( isset( $countries[ $base ] ) ) {

					$countries = array(
						$base => $countries[ $base ],
					);
				}
			}
		}

		return $countries;
	}

	/**
	 * Remove country css
	 */
	public function remove_country_css() {
		$remove = get_option( 'qlwcdc_remove_checkout_fields', array() );
		if ( in_array( 'country', (array) $remove ) ) {
			?>
			<style>
				#billing_country_field {
					display: none !important;
				}
			</style>
			<?php
		}
	}

	/**
	 * Remove checkout order commens
	 *
	 * @param bool $return Return.
	 */
	public function remove_checkout_order_commens( $return ) {
		if ( 'yes' === get_option( 'qlwcdc_remove_checkout_order_comments' ) ) {
			$return = false;
		}

		return $return;
	}

	/**
	 * Remove checkout shipping address
	 *
	 * @param string $val Value.
	 */
	public function remove_checkout_shipping_address( $val ) {
		if ( 'yes' === get_option( 'qlwcdc_remove_checkout_shipping_address' ) ) {
			$val = 'billing_only';
		}

		return $val;
	}
}
