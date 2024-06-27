<?php
/**
 * Woocommerce-direct-checkout Plugin
 *
 * @package  WooCommerce Direct Checkout
 * @since    1.0.0
 */

namespace QuadLayers\WCDC\Controller;

use QuadLayers\WCDC\View\Frontend\Checkout as Frontend_Checkout;

/**
 * Controller Checkout
 *
 * @class Checkout
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
		new Frontend_Checkout();

		add_action( 'qlwcdc_sections_header', array( __CLASS__, 'add_header' ) );
		add_action( 'woocommerce_sections_' . QLWCDC_PREFIX, array( $this, 'add_section' ), 99 );
		add_action( 'woocommerce_settings_save_' . QLWCDC_PREFIX, array( $this, 'save_settings' ) );
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
	 * Add header
	 */
	public static function add_header() {
		global $current_section;
		?>
	<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=' . QLWCDC_PREFIX . '&section=checkout' ) ); ?>" class="<?php echo ( 'checkout' == $current_section ? 'current' : '' ); ?>"><?php esc_html_e( 'Checkout', 'woocommerce-direct-checkout' ); ?></a> | </li>
		<?php
	}

	/**
	 * Get settings
	 */
	public function get_settings() {

		return array(
			array(
				'name' => esc_html__( 'Checkout', 'woocommerce-direct-checkout' ),
				'type' => 'title',
				'id'   => 'section_title',
			),
			array(
				'name'     => esc_html__( 'Add cart to checkout', 'woocommerce-direct-checkout' ),
				'desc_tip' => esc_html__( 'Simplifies the checkout process by including the shopping cart page in the checkout.	', 'woocommerce-direct-checkout' ),
				'id'       => 'qlwcdc_add_checkout_cart',
				'type'     => 'select',
				'class'    => 'chosen_select',
				'options'  => array(
					'yes' => esc_html__( 'Yes', 'woocommerce-direct-checkout' ),
					'no'  => esc_html__( 'No', 'woocommerce-direct-checkout' ),
				),
				'default'  => 'no',
			),
			array(
				'name'     => esc_html__( 'Add cart to checkout via ajax', 'woocommerce-direct-checkout' ),
				'desc_tip' => esc_html__( 'Prevent page reload when users change the product quantities.', 'woocommerce-direct-checkout' ),
				'id'       => 'qlwcdc_add_checkout_cart_ajax',
				'type'     => 'select',
				'class'    => 'chosen_select',
				'options'  => array(
					'yes' => esc_html__( 'Yes', 'woocommerce-direct-checkout' ),
					'no'  => esc_html__( 'No', 'woocommerce-direct-checkout' ),
				),
				'default'  => 'yes',
			),
			array(
				'name'     => esc_html__( 'Add cart to checkout fields', 'woocommerce-direct-checkout' ),
				'desc_tip' => esc_html__( 'Include these fields inside the checkout cart.', 'woocommerce-direct-checkout' ),
				'id'       => 'qlwcdc_add_checkout_cart_fields',
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'options'  => array(
					'remove'    => esc_html__( 'Remove', 'woocommerce-direct-checkout' ),
					'thumbnail' => esc_html__( 'Thumbnail', 'woocommerce-direct-checkout' ),
					'name'      => esc_html__( 'Name', 'woocommerce-direct-checkout' ),
					'price'     => esc_html__( 'Price', 'woocommerce-direct-checkout' ),
					'qty'       => esc_html__( 'Quantity', 'woocommerce-direct-checkout' ),
				),
				'default'  => array(
					0 => 'remove',
					1 => 'thumbnail',
					2 => 'price',
					3 => 'qty',
				),
			),
			array(
				'name'     => esc_html__( 'Remove checkout coupon form', 'woocommerce-direct-checkout' ),
				'desc_tip' => esc_html__( 'Simplifies the checkout process removing the coupon form.', 'woocommerce-direct-checkout' ),
				'id'       => 'qlwcdc_remove_checkout_coupon_form',
				'type'     => 'select',
				'class'    => 'chosen_select',
				'options'  => array(
					'no'       => esc_html__( 'Leave coupon form', 'woocommerce-direct-checkout' ),
					'remove'   => esc_html__( 'Remove coupon form', 'woocommerce-direct-checkout' ),
					'toggle'   => esc_html__( 'Remove coupon toggle', 'woocommerce-direct-checkout' ),
					'checkout' => esc_html__( 'Move to checkout order', 'woocommerce-direct-checkout' ),
				),
				'default'  => 'no',
			),
			array(
				'name'     => esc_html__( 'Add custom class to cart table', 'woocommerce-direct-checkout' ),
				'desc_tip' => esc_html__( 'Add a custom class to the cart table form in the checkout.', 'woocommerce-direct-checkout' ),
				'id'       => 'qlwcdc_add_checkout_cart_class',
				'type'     => 'text',
				'default'  => '',
			),
			array(
				'name'     => esc_html__( 'Remove checkout fields', 'woocommerce-direct-checkout' ),
				'desc_tip' => esc_html__( 'Simplifies the checkout process removing the unnecessary checkout fields.', 'woocommerce-direct-checkout' ),
				'id'       => 'qlwcdc_remove_checkout_fields',
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'options'  => array(
					'first_name' => esc_html__( 'First Name', 'woocommerce-direct-checkout' ),
					'last_name'  => esc_html__( 'Last Name', 'woocommerce-direct-checkout' ),
					'country'    => esc_html__( 'Country', 'woocommerce-direct-checkout' ),
					'state'      => esc_html__( 'State', 'woocommerce-direct-checkout' ),
					'city'       => esc_html__( 'City', 'woocommerce-direct-checkout' ),
					'postcode'   => esc_html__( 'Postcode', 'woocommerce-direct-checkout' ),
					'address_1'  => esc_html__( 'Address 1', 'woocommerce-direct-checkout' ),
					'address_2'  => esc_html__( 'Address 2', 'woocommerce-direct-checkout' ),
					'company'    => esc_html__( 'Company', 'woocommerce-direct-checkout' ),
					'phone'      => esc_html__( 'Phone', 'woocommerce-direct-checkout' ),
				),
				'default'  => array(
					0 => 'phone',
					1 => 'company',
					2 => 'address_2',
				),
			),
			array(
				'name'     => esc_html__( 'Remove checkout shipping address', 'woocommerce-direct-checkout' ),
				'desc_tip' => esc_html__( 'Simplifies the checkout process removing the shipping address.', 'woocommerce-direct-checkout' ),
				'id'       => 'qlwcdc_remove_checkout_shipping_address',
				'type'     => 'select',
				'class'    => 'chosen_select',
				'options'  => array(
					'yes' => esc_html__( 'Yes', 'woocommerce-direct-checkout' ),
					'no'  => esc_html__( 'No', 'woocommerce-direct-checkout' ),
				),
				'default'  => 'no',
			),
			array(
				'name'     => esc_html__( 'Remove checkout order comments', 'woocommerce-direct-checkout' ),
				'desc_tip' => esc_html__( 'Simplifies the checkout process removing the order notes.', 'woocommerce-direct-checkout' ),
				'id'       => 'qlwcdc_remove_checkout_order_comments',
				'type'     => 'select',
				'class'    => 'chosen_select',
				'options'  => array(
					'yes' => esc_html__( 'Yes', 'woocommerce-direct-checkout' ),
					'no'  => esc_html__( 'No', 'woocommerce-direct-checkout' ),
				),
				'default'  => 'no',
			),
			array(
				'name'     => esc_html__( 'Remove checkout policy text', 'woocommerce-direct-checkout' ),
				'desc_tip' => esc_html__( 'Simplifies the checkout process removing the policy text.', 'woocommerce-direct-checkout' ),
				'id'       => 'qlwcdc_remove_checkout_privacy_policy_text',
				'type'     => 'select',
				'class'    => 'chosen_select qlwcdc-premium-field',
				'options'  => array(
					'yes' => esc_html__( 'Yes', 'woocommerce-direct-checkout' ),
					'no'  => esc_html__( 'No', 'woocommerce-direct-checkout' ),
				),
				'default'  => 'no',
			),
			array(
				'name'     => esc_html__( 'Remove checkout terms and conditions', 'woocommerce-direct-checkout' ),
				'desc_tip' => esc_html__( 'Simplifies the checkout process removing the terms and conditions.', 'woocommerce-direct-checkout' ),
				'id'       => 'qlwcdc_remove_checkout_terms_and_conditions',
				'type'     => 'select',
				'class'    => 'chosen_select',
				'options'  => array(
					'yes' => esc_html__( 'Yes', 'woocommerce-direct-checkout' ),
					'no'  => esc_html__( 'No', 'woocommerce-direct-checkout' ),
				),
				'default'  => 'no',
			),
			array(
				'name'     => esc_html__( 'Remove checkout gateway icons', 'woocommerce-direct-checkout' ),
				'desc_tip' => esc_html__( 'Simplifies the checkout view by removing the payment gateway icons.', 'woocommerce-direct-checkout' ),
				'id'       => 'qlwcdc_remove_checkout_gateway_icon',
				'type'     => 'select',
				'class'    => 'chosen_select qlwcdc-premium-field',
				'options'  => array(
					'yes' => esc_html__( 'Yes', 'woocommerce-direct-checkout' ),
					'no'  => esc_html__( 'No', 'woocommerce-direct-checkout' ),
				),
				'default'  => 'no',
			),
			array(
				'name'     => esc_html__( 'Remove checkout columns', 'woocommerce-direct-checkout' ),
				'desc_tip' => esc_html__( 'Attempt to display the checkout form and order review in a single column by removing the columns.', 'woocommerce-direct-checkout' ),
				'id'       => 'qlwcdc_remove_checkout_columns',
				'type'     => 'select',
				'class'    => 'chosen_select',
				'options'  => array(
					'yes' => esc_html__( 'Yes', 'woocommerce-direct-checkout' ),
					'no'  => esc_html__( 'No', 'woocommerce-direct-checkout' ),
				),
				'default'  => 'no',
			),
			array(
				'name'     => esc_html__( 'Remove order details address', 'woocommerce-direct-checkout' ),
				'desc_tip' => esc_html__( 'Remove the customer\'s billing address on the order received page.', 'woocommerce-direct-checkout' ),
				'id'       => 'qlwcdc_remove_order_details_address',
				'type'     => 'select',
				'class'    => 'chosen_select',
				'options'  => array(
					'yes' => esc_html__( 'Yes', 'woocommerce-direct-checkout' ),
					'no'  => esc_html__( 'No', 'woocommerce-direct-checkout' ),
				),
				'default'  => 'no',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'wc_settings_tab_demo_section_end',
			),
		);
	}

	/**
	 * Add section
	 */
	public function add_section() {

		global $current_section;

		if ( 'checkout' == $current_section ) {

			$settings = $this->get_settings();

			include_once QLWCDC_PLUGIN_DIR . 'lib/view/backend/pages/checkout.php';
		}
	}

	/**
	 * Save settings
	 */
	public function save_settings() {

		global $current_section;

		if ( 'checkout' == $current_section ) {

			woocommerce_update_options( $this->get_settings() );
		}
	}

}
