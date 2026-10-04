<?php
/**
 * Frontend assets.
 *
 * @package AdvanceProductAddons
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class SPPA_Frontend_Assets
 */
class SPPA_Frontend_Assets {

	/**
	 * Instance.
	 *
	 * @var SPPA_Frontend_Assets|null
	 */
	private static $instance = null;

	/**
	 * Singleton.
	 *
	 * @return SPPA_Frontend_Assets
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Hooks.
	 */
	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue on product pages (and anywhere the shortcode/block is used).
	 */
	public function enqueue() {
		$load = is_product() || is_cart() || has_block( 'sppa/product-addons' );
		if ( ! $load ) {
			return;
		}

		wp_enqueue_style( 'sppa-frontend', SPPA_URL . 'frontend/assets/frontend.css', array(), SPPA_VERSION );
		wp_enqueue_script( 'sppa-frontend', SPPA_URL . 'frontend/assets/frontend.js', array( 'jquery' ), SPPA_VERSION, true );

		$product_id = is_product() ? get_the_ID() : 0;
		wp_localize_script(
			'sppa-frontend',
			'sppaFront',
			array(
				'ajax'      => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'sppa_frontend' ),
				'productId' => $product_id,
				'settings'  => SPPA_Helpers::settings(),
				'currency'  => function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '$',
				'i18n'      => array(
					'required' => __( 'This field is required.', 'optivo' ),
					'yes'      => __( 'Yes', 'optivo' ),
				),
			)
		);
	}
}
