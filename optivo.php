<?php
/**
 * Plugin Name:       Optivo – Product Options for WooCommerce
 * Plugin URI:        https://wpspeedpress.com
 * Description:       Extra fields for WooCommerce products: text, number, email, dropdown, radio, checkbox, and headings, with optional prices.
 * Version:           1.7.4
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Author:            Md Laju Miah
 * Author URI:        https://wpspeedpress.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       optivo
 * Domain Path:       /languages
 * WC requires at least: 7.0
 * WC tested up to:   10.2
 *
 * @package AdvanceProductAddons
 */

defined( 'ABSPATH' ) || exit;

define( 'SPPA_VERSION', '1.7.4' );
define( 'SPPA_FILE', __FILE__ );
define( 'SPPA_PATH', plugin_dir_path( __FILE__ ) );
define( 'SPPA_URL', plugin_dir_url( __FILE__ ) );
define( 'SPPA_BASENAME', plugin_basename( __FILE__ ) );
define( 'SPPA_META_PRODUCT', '_sppa_addon_groups' );
define( 'SPPA_META_EXCLUDE_GLOBAL', '_sppa_exclude_global_addons' );

if ( ! function_exists( 'sppa_autoload' ) ) {
	/**
	 * Simple class map autoloader.
	 *
	 * @param string $class Class name.
	 */
	function sppa_autoload( $class ) {
		$map = array(
			'SPPA_Plugin'           => 'includes/class-plugin.php',
			'SPPA_Helpers'          => 'includes/class-helpers.php',
			'SPPA_Field_Types'      => 'includes/class-field-types.php',
			'SPPA_Pricing'          => 'includes/class-pricing.php',
			'SPPA_Conditions'       => 'includes/class-conditions.php',
			'SPPA_Repository'       => 'includes/class-repository.php',
			'SPPA_Resolver'         => 'includes/class-resolver.php',
			'SPPA_Validation'       => 'includes/class-validation.php',
			'SPPA_Cart'             => 'includes/class-cart.php',
			'SPPA_Order'            => 'includes/class-order.php',
			'SPPA_Ajax'             => 'includes/class-ajax.php',
			'SPPA_REST_API'         => 'includes/class-rest-api.php',
			'SPPA_Import_Export'    => 'includes/class-import-export.php',
			'SPPA_Admin'            => 'admin/class-admin.php',
			'SPPA_Product_Metabox'  => 'admin/class-product-metabox.php',
			'SPPA_Global_Addons'    => 'admin/class-global-addons.php',
			'SPPA_Renderer'         => 'frontend/class-renderer.php',
			'SPPA_Frontend_Assets'  => 'frontend/class-assets.php',
			'SPPA_Blocks'           => 'integrations/class-blocks.php',
			'SPPA_Elementor'        => 'integrations/class-elementor.php',
			'SPPA_Subscriptions'    => 'integrations/class-subscriptions.php',
		);

		if ( isset( $map[ $class ] ) ) {
			$file = SPPA_PATH . $map[ $class ];
			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
	}
	spl_autoload_register( 'sppa_autoload' );
}

/**
 * Declare WooCommerce feature compatibility.
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', SPPA_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', SPPA_FILE, true );
		}
	}
);

/**
 * Boot the plugin after WooCommerce is loaded.
 */
add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error"><p>';
					echo esc_html__( 'Optivo – Product Options for WooCommerce requires WooCommerce to be installed and active.', 'optivo' );
					echo '</p></div>';
				}
			);
			return;
		}

		SPPA_Plugin::instance()->init();
	}
);

register_deactivation_hook(
	__FILE__,
	function () {
		flush_rewrite_rules();
	}
);

register_activation_hook(
	__FILE__,
	function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( esc_html__( 'Optivo – Product Options for WooCommerce requires WooCommerce.', 'optivo' ) );
		}
		if ( ! class_exists( 'SPPA_Global_Addons' ) ) {
			require_once SPPA_PATH . 'admin/class-global-addons.php';
		}
		SPPA_Global_Addons::register_post_type();
		flush_rewrite_rules();
		if ( false === get_option( 'sppa_settings', false ) ) {
			update_option(
				'sppa_settings',
				array(
					'show_in_cart'           => 'yes',
					'show_in_checkout'       => 'yes',
					'show_in_order'          => 'yes',
					'show_in_emails'         => 'yes',
					'show_prices_in_cart'    => 'yes',
					'show_summary'           => 'yes',
					'live_price'             => 'yes',
					'upload_max_mb'          => 10,
					'allowed_mime_types'     => 'jpg,jpeg,png,gif,webp,pdf',
				)
			);
		}
	}
);
