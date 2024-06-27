<?php

if ( class_exists( 'QuadLayers\\WP_Notice_Plugin_Promote\\Load' ) ) {
	/**
	 *  Promote constants
	 */
	define( 'QLWCDC_PROMOTE_LOGO_SRC', plugins_url( '/assets/backend/img/logo.jpg', QLWCDC_PLUGIN_FILE ) );
	/**
	 * Notice review
	 */
	define( 'QLWCDC_PROMOTE_REVIEW_URL', 'https://wordpress.org/support/plugin/woocommerce-direct-checkout/reviews/?filter=5#new-post' );
	/**
	 * Notice premium sell
	 */
	define( 'QLWCDC_PROMOTE_PREMIUM_SELL_SLUG', 'woocommerce-direct-checkout-pro' );
	define( 'QLWCDC_PROMOTE_PREMIUM_SELL_NAME', 'WooCommerce Direct Checkout PRO' );
	define(
		'QLWCDC_PROMOTE_PREMIUM_SELL_DESCRIPTION',
		sprintf(
			esc_html__(
				'Today we have a special gift for you. Use the coupon code %1$s within the next 48 hours to receive a %2$s discount on the premium version of the %3$s plugin.',
				'woocommerce-direct-checkout'
			),
			'ADMINPANEL20%',
			'20%',
			QLWCDC_PROMOTE_PREMIUM_SELL_NAME
		)
	);
	define( 'QLWCDC_PROMOTE_PREMIUM_SELL_URL', QLWCDC_PREMIUM_SELL_URL );
	define( 'QLWCDC_PROMOTE_PREMIUM_INSTALL_URL', 'https://quadlayers.com/product/woocommerce-direct-checkout/?utm_source=qlwcdc_admin' );
	/**
	 * Notice cross sell 1
	 */
	define( 'QLWCDC_PROMOTE_CROSS_INSTALL_1_SLUG', 'woocommerce-checkout-manager' );
	define( 'QLWCDC_PROMOTE_CROSS_INSTALL_1_NAME', 'WooCommerce Checkout Manager' );
	define( 'QLWCDC_PROMOTE_CROSS_INSTALL_1_DESCRIPTION', esc_html__( 'WooCommerce Checkout Manager allows you to add custom fields to the checkout page, related to billing, Shipping or Additional fields sections.', 'woocommerce-direct-checkout' ) );
	define( 'QLWCDC_PROMOTE_CROSS_INSTALL_1_URL', 'https://quadlayers.com/products/woocommerce-checkout-manager/?utm_source=qlwcdc_admin' );
	/**
	 * Notice cross sell 2
	 */
	define( 'QLWCDC_PROMOTE_CROSS_INSTALL_2_SLUG', 'perfect-woocommerce-brands' );
	define( 'QLWCDC_PROMOTE_CROSS_INSTALL_2_NAME', 'Perfect WooCommerce Brands' );
	define( 'QLWCDC_PROMOTE_CROSS_INSTALL_2_DESCRIPTION', esc_html__( 'Perfect WooCommerce Brands the perfect tool to improve customer experience on your site. It allows you to highlight product brands and organize them in lists, dropdowns, thumbnails, and as a widget.', 'woocommerce-direct-checkout' ) );
	define( 'QLWCDC_PROMOTE_CROSS_INSTALL_2_URL', 'https://quadlayers.com/products/perfect-woocommerce-brands/?utm_source=qlwcdc_admin' );

	new \QuadLayers\WP_Notice_Plugin_Promote\Load(
		QLWCDC_PLUGIN_FILE,
		array(
			array(
				'type'               => 'ranking',
				'notice_delay'       => MONTH_IN_SECONDS,
				'notice_logo'        => QLWCDC_PROMOTE_LOGO_SRC,
				'notice_title'       => sprintf(
					esc_html__(
						'Hello! Thank you for choosing the %s plugin!',
						'woocommerce-direct-checkout'
					),
					QLWCDC_PLUGIN_NAME
				),
				'notice_description' => esc_html__( 'Could you please give it a 5-star rating on WordPress? Your feedback boosts our motivation, helps us promote, and continues to improve this product. Your support matters!', 'woocommerce-direct-checkout' ),
				'notice_link'        => QLWCDC_PROMOTE_REVIEW_URL,
				'notice_link_label'  => esc_html__(
					'Yes, of course!',
					'woocommerce-direct-checkout'
				),
				'notice_more_link'   => QLWCDC_SUPPORT_URL,
				'notice_more_label'  => esc_html__(
					'Report a bug',
					'woocommerce-direct-checkout'
				),
			),
			array(
				'plugin_slug'        => QLWCDC_PROMOTE_PREMIUM_SELL_SLUG,
				'plugin_install_link'   => QLWCDC_PROMOTE_PREMIUM_INSTALL_URL,
				'plugin_install_label'  => esc_html__(
					'Purchase Now',
					'woocommerce-direct-checkout'
				),
				'notice_delay'       => MONTH_IN_SECONDS,
				'notice_logo'        => QLWCDC_PROMOTE_LOGO_SRC,
				'notice_title'       => esc_html__(
					'Hello! We have a special gift!',
					'woocommerce-direct-checkout'
				),
				'notice_description' => QLWCDC_PROMOTE_PREMIUM_SELL_DESCRIPTION,
				'notice_more_link'   => QLWCDC_PROMOTE_PREMIUM_SELL_URL,
				'notice_more_label'  => esc_html__(
					'More info!',
					'woocommerce-direct-checkout'
				),
			),
			array(
				'plugin_slug'        => QLWCDC_PROMOTE_CROSS_INSTALL_1_SLUG,
				'notice_delay'       => MONTH_IN_SECONDS * 4,
				'notice_logo'        => QLWCDC_PROMOTE_LOGO_SRC,
				'notice_title'       => sprintf(
					esc_html__(
						'Hello! We want to invite you to try our %s plugin!',
						'woocommerce-direct-checkout'
					),
					QLWCDC_PROMOTE_CROSS_INSTALL_1_NAME
				),
				'notice_description' => QLWCDC_PROMOTE_CROSS_INSTALL_1_DESCRIPTION,
				'notice_more_link'   => QLWCDC_PROMOTE_CROSS_INSTALL_1_URL,
				'notice_more_label'  => esc_html__(
					'More info!',
					'woocommerce-direct-checkout'
				),
			),
			array(
				'plugin_slug'        => QLWCDC_PROMOTE_CROSS_INSTALL_2_SLUG,
				'notice_delay'       => MONTH_IN_SECONDS * 6,
				'notice_logo'        => QLWCDC_PROMOTE_LOGO_SRC,
				'notice_title'       => sprintf(
					esc_html__(
						'Hello! We want to invite you to try our %s plugin!',
						'woocommerce-direct-checkout'
					),
					QLWCDC_PROMOTE_CROSS_INSTALL_2_NAME
				),
				'notice_description' => QLWCDC_PROMOTE_CROSS_INSTALL_2_DESCRIPTION,
				'notice_more_link'   => QLWCDC_PROMOTE_CROSS_INSTALL_2_URL,
				'notice_more_label'  => esc_html__(
					'More info!',
					'woocommerce-direct-checkout'
				),
			),
		)
	);
}
