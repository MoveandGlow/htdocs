<?php
/**
 * Woocommerce-direct-checkout Plugin
 *
 * @package  WooCommerce Direct Checkout
 * @since    1.0.0
 */

namespace QuadLayers\WCDC\View\Frontend;

/**
 * Archives
 *
 * @class Plugin
 * @version 1.0.0
 */
class Archives {

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
		add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'add_archive_text' ), 10, 2 );
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
	<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=' . QLWCDC_PREFIX . '&section=archives' ) ); ?>" class="<?php echo ( 'archives' == $current_section ? 'current' : '' ); ?>"><?php esc_html_e( 'Archives', 'woocommerce-direct-checkout' ); ?></a> | </li>
		<?php
	}

	/**
	 * Add archive text
	 *
	 * @param string $text Product id.
	 * @param string $product Meta key.
	 */
	public function add_archive_text( $text, $product ) {

		if ( 'yes' === get_option( 'qlwcdc_add_archive_text' ) ) {
			if ( $product->is_type( get_option( 'qlwcdc_add_archive_text_in', array() ) ) ) {
				// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
				$text = esc_html__( get_option( 'qlwcdc_add_archive_text_content' ), 'woocommerce-direct-checkout' );
			}
		}

		return $text;
	}

}
