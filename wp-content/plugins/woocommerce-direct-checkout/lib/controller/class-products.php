<?php
/**
 * Woocommerce-direct-checkout Plugin
 *
 * @package  WooCommerce Direct Checkout
 * @since    1.0.0
 */

namespace QuadLayers\WCDC\Controller;

use QuadLayers\WCDC\Plugin;
use QuadLayers\WCDC\View\Frontend\Products as Frontend_Products;

/**
 * Controller Products
 *
 * @class Products
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
	 * Product fields
	 *
	 * @var WCDC
	 */
	public $product_fields;

	/**
	 * Construct
	 */
	public function __construct() {

		new Frontend_Products();

		add_action( 'qlwcdc_sections_header', array( __CLASS__, 'add_header' ) );
		add_action( 'woocommerce_sections_' . QLWCDC_PREFIX, array( $this, 'add_section' ), 99 );
		add_action( 'woocommerce_settings_save_' . QLWCDC_PREFIX, array( $this, 'save_settings' ) );
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_product_tabs' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'add_product_tab_content' ) );
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
	<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=' . QLWCDC_PREFIX . '&section=products' ) ); ?>" class="<?php echo ( 'products' == $current_section ? 'current' : '' ); ?>"><?php esc_html_e( 'Products', 'woocommerce-direct-checkout' ); ?></a> | </li>
		<?php
	}

	/**
	 * Get settings
	 */
	public function get_settings() {

		return array(
			array(
				'name' => esc_html__( 'Products', 'woocommerce-direct-checkout' ),
				'type' => 'title',
				'id'   => 'qlwcdc_products_section_title',
			),
			array(
				'name'     => esc_html__( 'Add ajax add to cart', 'woocommerce-direct-checkout' ),
				'desc_tip' => esc_html__( 'Add products to cart via ajax.', 'woocommerce-direct-checkout' ),
				'id'       => 'qlwcdc_add_product_ajax',
				'type'     => 'select',
				'class'    => 'chosen_select qlwcdc-premium-field',
				'options'  => array(
					'yes' => esc_html__( 'Yes', 'woocommerce-direct-checkout' ),
					'no'  => esc_html__( 'No', 'woocommerce-direct-checkout' ),
				),
				'default'  => 'no',
			),
			array(
				'name'     => esc_html__( 'Add ajax add to cart alert', 'woocommerce-direct-checkout' ),
				'desc_tip' => esc_html__( 'Display alert when product is added to the cart.', 'woocommerce-direct-checkout' ),
				'id'       => 'qlwcdc_add_product_ajax_alert',
				'type'     => 'select',
				'class'    => 'chosen_select qlwcdc-premium-field',
				'options'  => array(
					'yes' => esc_html__( 'Yes', 'woocommerce-direct-checkout' ),
					'no'  => esc_html__( 'No', 'woocommerce-direct-checkout' ),
				),
				'default'  => 'yes',
			),
			array(
				'name'     => esc_html__( 'Replace Add to cart text', 'woocommerce-direct-checkout' ),
				'desc_tip' => esc_html__( 'Replace Add to cart text', 'woocommerce-direct-checkout' ),
				'id'       => 'qlwcdc_add_product_text',
				'type'     => 'select',
				'class'    => 'chosen_select',
				'options'  => array(
					'yes' => esc_html__( 'Yes', 'woocommerce-direct-checkout' ),
					'no'  => esc_html__( 'No', 'woocommerce-direct-checkout' ),
				),
				'default'  => 'no',
			),
			array(
				'name'     => esc_html__( 'Replace Add to cart text content', 'woocommerce-direct-checkout' ),
				'desc_tip' => esc_html__( 'Replace "Add to cart" text with this text.', 'woocommerce-direct-checkout' ),
				'id'       => 'qlwcdc_add_product_text_content',
				'type'     => 'text',
				'default'  => esc_html__( 'Purchase', 'woocommerce-direct-checkout' ),
			),
			array(
				'name'     => esc_html__( 'Add quick purchase button', 'woocommerce-direct-checkout' ),
				'desc_tip' => esc_html__( 'Add a quick purchase button to the products pages.', 'woocommerce-direct-checkout' ),
				'id'       => 'qlwcdc_add_product_quick_purchase',
				'type'     => 'select',
				'class'    => 'chosen_select',
				'options'  => array(
					'yes' => esc_html__( 'Yes', 'woocommerce-direct-checkout' ),
					'no'  => esc_html__( 'No', 'woocommerce-direct-checkout' ),
				),
				'default'  => 'no',
			),
			array(
				'name'     => esc_html__( 'Add quick purchase button type', 'woocommerce-direct-checkout' ),
				'desc_tip' => esc_html__( 'Select the WooCommerce button type.', 'woocommerce-direct-checkout' ),
				'id'       => 'qlwcdc_add_product_quick_purchase_type',
				'type'     => 'select',
				'class'    => 'chosen_select',
				'options'  => array(
					''    => esc_html__( 'Default', 'woocommerce-direct-checkout' ),
					'alt' => esc_html__( 'Alternative', 'woocommerce-direct-checkout' ),
				),
				'default'  => '',
			),
			array(
				'name'     => esc_html__( 'Add quick purchase class', 'woocommerce-direct-checkout' ),
				'desc_tip' => esc_html__( 'Add a custom class to the quick purchase button.', 'woocommerce-direct-checkout' ),
				'id'       => 'qlwcdc_add_product_quick_purchase_class',
				'type'     => 'text',
				'default'  => '',
			),
			array(
				'name'     => esc_html__( 'Redirect quick purchase to', 'woocommerce-direct-checkout' ),
				'desc_tip' => esc_html__( 'Redirect the quick purchase button to the cart or checkout page.', 'woocommerce-direct-checkout' ),
				'id'       => 'qlwcdc_add_product_quick_purchase_to',
				'type'     => 'select',
				'class'    => 'chosen_select',
				'options'  => array(
					'cart'     => esc_html__( 'Cart', 'woocommerce-direct-checkout' ),
					'checkout' => esc_html__( 'Checkout', 'woocommerce-direct-checkout' ),
				),
				'default'  => 'checkout',
			),
			array(
				'name'     => esc_html__( 'Add quick purchase text', 'woocommerce-direct-checkout' ),
				'desc_tip' => esc_html__( 'Add a custom text to the quick purchase button.', 'woocommerce-direct-checkout' ),
				'id'       => 'qlwcdc_add_product_quick_purchase_text',
				'type'     => 'text',
				'default'  => esc_html__( 'Purchase Now', 'woocommerce-direct-checkout' ),
			),
			array(
				'name'     => esc_html__( 'Add default attributes in variable products', 'woocommerce-direct-checkout' ),
				'desc_tip' => esc_html__( 'Add default attributes in all variable products to avoid disabled Add to cart button.', 'woocommerce-direct-checkout' ),
				'id'       => 'qlwcdc_add_product_default_attributes',
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
				'id'   => 'qlwcdc_products_section_end',
			),
		);
	}

	/**
	 * Add section
	 */
	public function add_section() {

		global $current_section;

		if ( 'products' == $current_section ) {

			$settings = $this->get_settings();

			include_once QLWCDC_PLUGIN_DIR . 'lib/view/backend/pages/products.php';
		}
	}

	/**
	 * Save settings
	 */
	public function save_settings() {

		global $current_section;

		if ( 'products' == $current_section ) {

			woocommerce_update_options( $this->get_settings() );
		}
	}

	/**
	 * Add product fields
	 */
	public function add_product_fields() {

		global $thepostid;

		if ( $this->product_fields ) {
			return;
		}

		// Fields.
		$this->product_fields = array(
			'start_group',
			array(
				'label'       => esc_html__( 'Add ajax add to cart', 'woocommerce-direct-checkout' ),
				'description' => esc_html__( 'Add products to cart via ajax.', 'woocommerce-direct-checkout' ),
				'desc_tip'    => true,
				'id'          => 'qlwcdc_add_product_ajax',
				'type'        => 'select',
				'options'     => array(
					'yes' => esc_html__( 'Yes', 'woocommerce-direct-checkout' ),
					'no'  => esc_html__( 'No', 'woocommerce-direct-checkout' ),
				),
				'value'       => Plugin::instance()->get_product_option( $thepostid, 'qlwcdc_add_product_ajax', 'no' ),
			),
			array(
				'label'       => esc_html__( 'Add ajax add to cart alert', 'woocommerce-direct-checkout' ),
				'description' => esc_html__( 'Display alert when product is added to the cart.', 'woocommerce-direct-checkout' ),
				'desc_tip'    => true,
				'id'          => 'qlwcdc_add_product_ajax_alert',
				'type'        => 'select',
				'options'     => array(
					'yes' => esc_html__( 'Yes', 'woocommerce-direct-checkout' ),
					'no'  => esc_html__( 'No', 'woocommerce-direct-checkout' ),
				),
				'value'       => Plugin::instance()->get_product_option( $thepostid, 'qlwcdc_add_product_ajax_alert', 'yes' ),
			),
			'start_group',
			'end_group',
			array(
				'label'       => esc_html__( 'Replace Add to cart text', 'woocommerce-direct-checkout' ),
				'desc_tip'    => true,
				'description' => esc_html__( 'Replace Add to cart text', 'woocommerce-direct-checkout' ),
				'id'          => 'qlwcdc_add_product_text',
				'type'        => 'select',
				'options'     => array(
					'yes' => esc_html__( 'Yes', 'woocommerce-direct-checkout' ),
					'no'  => esc_html__( 'No', 'woocommerce-direct-checkout' ),
				),
				'value'       => Plugin::instance()->get_product_option( $thepostid, 'qlwcdc_add_product_text', 'no' ),
			),
			array(
				'label'       => esc_html__( 'Replace Add to cart text content', 'woocommerce-direct-checkout' ),
				'desc_tip'    => true,
				'description' => esc_html__( 'Replace "Add to cart" text with this text.', 'woocommerce-direct-checkout' ),
				'id'          => 'qlwcdc_add_product_text_content',
				'type'        => 'text',
				'placeholder' => get_option( 'qlwcdc_add_product_text_content' ),
				'value'       => Plugin::instance()->get_product_option( $thepostid, 'qlwcdc_add_product_text_content' ),
			),
			'start_group',
			'end_group',
			array(
				'label'       => esc_html__( 'Add quick purchase button', 'woocommerce-direct-checkout' ),
				'desc_tip'    => true,
				'description' => esc_html__( 'Add quick purchase button to single product page.', 'woocommerce-direct-checkout' ),
				'id'          => 'qlwcdc_add_product_quick_purchase',
				'class'       => 'short',
				'type'        => 'select',
				'options'     => array(
					'yes' => esc_html__( 'Yes', 'woocommerce-direct-checkout' ),
					'no'  => esc_html__( 'No', 'woocommerce-direct-checkout' ),
				),
				'value'       => Plugin::instance()->get_product_option( $thepostid, 'qlwcdc_add_product_quick_purchase', 'no' ),
			),
			array(
				'label'       => esc_html__( 'Add quick purchase class', 'woocommerce-direct-checkout' ),
				'desc_tip'    => true,
				'description' => esc_html__( 'Add quick purchase custom class.', 'woocommerce-direct-checkout' ),
				'id'          => 'qlwcdc_add_product_quick_purchase_class',
				'type'        => 'text',
				'placeholder' => get_option( 'qlwcdc_add_product_quick_purchase_class' ),
				'value'       => Plugin::instance()->get_product_option( $thepostid, 'qlwcdc_add_product_quick_purchase_class' ),
			),
			array(
				'label'       => esc_html__( 'Add quick purchase text', 'woocommerce-direct-checkout' ),
				'desc_tip'    => true,
				'description' => esc_html__( 'Add quick purchase custom text.', 'woocommerce-direct-checkout' ),
				'id'          => 'qlwcdc_add_product_quick_purchase_text',
				'type'        => 'text',
				'placeholder' => get_option( 'qlwcdc_add_product_quick_purchase_text' ),
				'value'       => Plugin::instance()->get_product_option( $thepostid, 'qlwcdc_add_product_quick_purchase_text' ),
			),
			array(
				'label'       => esc_html__( 'Redirect quick purchase to', 'woocommerce-direct-checkout' ),
				'desc_tip'    => true,
				'description' => esc_html__( 'Redirect quick purchase to the cart or checkout page.', 'woocommerce-direct-checkout' ),
				'id'          => 'qlwcdc_add_product_quick_purchase_to',
				'type'        => 'select',
				'options'     => array(
					'cart'     => esc_html__( 'Cart', 'woocommerce-direct-checkout' ),
					'checkout' => esc_html__( 'Checkout', 'woocommerce-direct-checkout' ),
				),
				'value'       => Plugin::instance()->get_product_option( $thepostid, 'qlwcdc_add_product_quick_purchase_to', 'checkout' ),
			),
			'end_group',
		);
	}

	/**
	 * Add product tabs
	 *
	 * @param array $tabs Tabs.
	 */
	public function add_product_tabs( $tabs ) {

		$tabs[ QLWCDC_PREFIX ] = array(
			'label'  => esc_html__( 'Direct Checkout', 'woocommerce-direct-checkout' ),
			'target' => 'qlwcdc_options',
		);

		return $tabs;
	}

	/**
	 * Add setting field
	 *
	 * @param array $field Field.
	 */
	public function add_setting_field( $field ) {

		if ( ! isset( $field['id'] ) ) {
			if ( 'start_group' == $field ) {
				echo '<div class="options_group">';
			} elseif ( 'end_group' == $field ) {
				echo '</div>';
			}
		} else {
			$function  = 'woocommerce_wp_' . $field['type'];
			$function2 = 'woocommerce_wp_' . $field['type'] . '_input';
			if ( function_exists( $function ) ) {
				$function( $field );
			} elseif ( function_exists( $function2 ) ) {
				$function2( $field );
			} else {
				woocommerce_wp_text_input( $field );
			}
		}
	}

	/**
	 * Add product tab content
	 */
	public function add_product_tab_content() {

		$this->add_product_fields();
		?>
	<div id="qlwcdc_options" class="panel woocommerce_options_panel" style="display: none;">
		<?php
		foreach ( $this->product_fields as $field ) {
			$this->add_setting_field( $field );
		}
		?>
	  <div style="font-size: 1.1em;" class="marketplace-suggestions-container">
		<div style="padding: 1em 1.5em;overflow: hidden;" class="marketplace-suggestion-container">
		  <div style="float:right;"><a target="blank" class="marketplace-suggestion-manage-link linkout" href="<?php echo esc_url( QLWCDC_DOCUMENTATION_URL ); ?>"><?php esc_html_e( 'Documentation', 'woocommerce-direct-checkout' ); ?></a></div>
		  <div style="float:left;"><a style="text-decoration:none" target="blank" href="<?php echo esc_url( QLWCDC_PREMIUM_SELL_URL ); ?>" class="linkout"><?php esc_html_e( 'Purchase Now', 'woocommerce-direct-checkout' ); ?><span style="margin-left: 4px;bottom: 2px;position: relative;" class="dashicons dashicons-external"></span></a></div>
		</div>
	  </div>
	</div>
		<?php
	}

}
