<?php

if ( class_exists( 'QuadLayers\\WP_Plugin_Table_Links\\Load' ) ) {
	new \QuadLayers\WP_Plugin_Table_Links\Load(
		QLWCDC_PLUGIN_FILE,
		array(
			array(
				'text' => esc_html__( 'Settings', 'woocommerce-direct-checkout' ),
				'url'  => admin_url( 'admin.php?page=wc-settings&tab=' . sanitize_title( QLWCDC_PREFIX ) ),
				'target' => '_self',
			),
			array(
				'text' => esc_html__( 'Premium', 'woocommerce-direct-checkout' ),
				'url'  => QLWCDC_PREMIUM_SELL_URL,
			),
			array(
				'place' => 'row_meta',
				'text'  => esc_html__( 'Support', 'woocommerce-direct-checkout' ),
				'url'   => QLWCDC_SUPPORT_URL,
			),
			array(
				'place' => 'row_meta',
				'text'  => esc_html__( 'Documentation', 'woocommerce-direct-checkout' ),
				'url'   => QLWCDC_DOCUMENTATION_URL,
			),
		)
	);
}
