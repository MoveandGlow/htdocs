<?php
/**
 * Woocommerce-direct-checkout Plugin
 *
 * @package  WooCommerce Direct Checkout
 * @since    1.0.0
 */

namespace QuadLayers\WCDC\Controller;

/**
 * Controller Premium
 *
 * @class Premium
 * @version 1.0.0
 */
class Premium {

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
		add_action( 'qlwcdc_sections_header', array( __CLASS__, 'add_header' ) );
		add_action( 'woocommerce_sections_' . QLWCDC_PREFIX, array( $this, 'add_section' ), 99 );
	}

	/**
	 * Add header
	 */
	public static function add_header() {
		?>
			<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=' . QLWCDC_PREFIX . '&section=premium' ) ); ?>"><?php echo esc_html__( 'Premium', 'woocommerce-direct-checkout' ); ?></a></li> |
		<?php
	}

	/**
	 * Add section
	 */
	public function add_section() {

		global $current_section;

		if ( 'premium' == $current_section ) {

			include_once QLWCDC_PLUGIN_DIR . 'lib/view/backend/pages/premium.php';
		}
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
