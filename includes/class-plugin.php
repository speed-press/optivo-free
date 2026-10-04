<?php
/**
 * Main plugin orchestrator.
 *
 * @package AdvanceProductAddons
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class SPPA_Plugin
 */
class SPPA_Plugin {

	/**
	 * Singleton.
	 *
	 * @var SPPA_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return SPPA_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize subsystems.
	 */
	public function init() {
		SPPA_Global_Addons::instance()->init();
		SPPA_Admin::instance()->init();
		SPPA_Product_Metabox::instance()->init();
		SPPA_Frontend_Assets::instance()->init();
		SPPA_Renderer::instance()->init();
		SPPA_Cart::instance()->init();
		SPPA_Order::instance()->init();
		SPPA_Ajax::instance()->init();
		SPPA_REST_API::instance()->init();
		SPPA_Import_Export::instance()->init();
		SPPA_Blocks::instance()->init();
		SPPA_Elementor::instance()->init();
		SPPA_Subscriptions::instance()->init();
	}

	/**
	 * Plugin settings.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Default.
	 * @return mixed
	 */
	public static function setting( $key, $default = '' ) {
		$settings = get_option( 'sppa_settings', array() );
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}
}
