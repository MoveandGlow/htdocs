<?php
/**
 * Woocommerce-direct-checkout Plugin
 *
 * @package  WooCommerce Direct Checkout
 * @since    1.0.0
 */

namespace QuadLayers\WCDC\Controller;

use QuadLayers\WCDC\View\Frontend\General as Frontend_General;

/**
 * Controller General
 *
 * @class General
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
		new Frontend_General();

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
	<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=' . QLWCDC_PREFIX . '&section' ) ); ?>" class="<?php echo ( '' == $current_section ? 'current' : '' ); ?>"><?php esc_html_e( 'General', 'woocommerce-direct-checkout' ); ?></a> | </li>
		<?php
	}

	/**
	 * Get settings
	 */
	public function get_settings() {
		return array(
			array(
				'name' => esc_html__( 'General', 'woocommerce-direct-checkout' ),
				'type' => 'title',
				'desc' => esc_html__( 'Simplifies the checkout process.', 'woocommerce-direct-checkout' ),
				'id'   => 'qlwcdc_section_title',
			),
			array(
				'name'     => esc_html__( 'Added to cart alert', 'woocommerce-direct-checkout' ),
				'desc_tip' => esc_html__( 'Replace the "View Cart" alert with a direct checkout option.', 'woocommerce-direct-checkout' ),
				'id'       => 'qlwcdc_add_to_cart_message',
				'type'     => 'select',
				'class'    => 'chosen_select',
				'options'  => array(
					'yes' => esc_html__( 'Yes', 'woocommerce-direct-checkout' ),
					'no'  => esc_html__( 'No', 'woocommerce-direct-checkout' ),
				),
				'default'  => 'no',
			),
			array(
				'name'     => esc_html__( 'Added to cart link in shop', 'woocommerce-direct-checkout' ),
				'desc_tip' => esc_html__( 'Replace the "View Cart" link with a "Checkout" link on the shop page.', 'woocommerce-direct-checkout' ),
				'id'       => 'qlwcdc_add_to_cart_link',
				'type'     => 'select',
				'class'    => 'chosen_select',
				'options'  => array(
					'yes' => esc_html__( 'Yes', 'woocommerce-direct-checkout' ),
					'no'  => esc_html__( 'No', 'woocommerce-direct-checkout' ),
				),
				'default'  => 'no',
			),
			array(
				'name'     => esc_html__( 'Added to cart redirect', 'woocommerce-direct-checkout' ),
				'desc_tip' => esc_html__( 'Add to cart button behavior.', 'woocommerce-direct-checkout' ),
				'id'       => 'qlwcdc_add_to_cart',
				'type'     => 'select',
				'class'    => 'chosen_select',
				'options'  => array(
					'no'       => esc_html__( 'No', 'woocommerce-direct-checkout' ),
					// 'ajax' => esc_html__('Ajax', 'woocommerce-direct-checkout'),
					'redirect' => esc_html__( 'Yes', 'woocommerce-direct-checkout' ),
				),
				'default'  => 'no',
			),
			array(
				'name'     => esc_html__( 'Added to cart redirect to', 'woocommerce-direct-checkout' ),
				'desc_tip' => esc_html__( 'Redirect to the cart or checkout page after successful addition of the product.', 'woocommerce-direct-checkout' ),
				'id'       => 'qlwcdc_add_to_cart_redirect_page',
				'type'     => 'select',
				'class'    => 'chosen_select',
				'options'  => array(
					'cart'     => esc_html__( 'Cart', 'woocommerce-direct-checkout' ),
					'checkout' => esc_html__( 'Checkout', 'woocommerce-direct-checkout' ),
					'url'      => esc_html__( 'Custom URL', 'woocommerce-direct-checkout' ),
				),
				'default'  => 'cart',
			),
			array(
				'name'        => esc_html__( 'Added to cart redirect to custom url', 'woocommerce-direct-checkout' ),
				'desc_tip'    => esc_html__( 'Redirect to the cart or checkout page after successful addition of the product.', 'woocommerce-direct-checkout' ),
				'id'          => 'qlwcdc_add_to_cart_redirect_url',
				'type'        => 'text',
				'placeholder' => home_url(),
			),
			array(
				'name'     => esc_html__( 'Replace cart url', 'woocommerce-direct-checkout' ),
				'desc_tip' => esc_html__( 'Replace cart url', 'woocommerce-direct-checkout' ),
				'id'       => 'qlwcdc_replace_cart_url',
				'type'     => 'select',
				'class'    => 'chosen_select',
				'options'  => array(
					'no'       => esc_html__( 'No', 'woocommerce-direct-checkout' ),
					'checkout' => esc_html__( 'Checkout', 'woocommerce-direct-checkout' ),
					'custom'   => esc_html__( 'Custom URL', 'woocommerce-direct-checkout' ),
				),
				'default'  => 'no',
			),
			array(
				'name'        => esc_html__( 'Replace the cart URL with a custom URL.', 'woocommerce-direct-checkout' ),
				'desc_tip'    => esc_html__( 'Replace the cart URL with a custom URL.', 'woocommerce-direct-checkout' ),
				'id'          => 'qlwcdc_replace_cart_url_custom',
				'type'        => 'text',
				'placeholder' => wc_get_checkout_url(),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'qlwcdc_section_end',
			),
		);
	}

	/**
	 * Add section
	 */
	public function add_section() {
		global $current_section;

		if ( '' == $current_section ) {

			$settings = $this->get_settings();

			include_once QLWCDC_PLUGIN_DIR . 'lib/view/backend/pages/general.php';
		}
	}

	/**
	 * Save settings
	 */
	public function save_settings() {
		global $current_section;

		if ( '' == $current_section ) {

			woocommerce_update_options( $this->get_settings() );
		}
	}
}
