<?php
/**
 * WooCommerce Subscriptions compatibility notes.
 *
 * Add-on prices are applied to the product price via cart totals, so they
 * already flow into subscription line items created from the cart. This
 * class exists so the integration point is explicit and filterable.
 *
 * @package AdvanceProductAddons
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class SPPA_Subscriptions
 */
class SPPA_Subscriptions {

	/**
	 * Instance.
	 *
	 * @var SPPA_Subscriptions|null
	 */
	private static $instance = null;

	/**
	 * Singleton.
	 *
	 * @return SPPA_Subscriptions
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
		if ( ! class_exists( 'WC_Subscriptions' ) && ! function_exists( 'wcs_get_subscription' ) ) {
			return;
		}

		add_filter( 'sppa_resolved_groups', array( $this, 'maybe_mark_recurring' ), 10, 2 );
	}

	/**
	 * Pass-through filter for future recurring-only field flags.
	 *
	 * @param array $groups     Groups.
	 * @param int   $product_id Product ID.
	 * @return array
	 */
	public function maybe_mark_recurring( $groups, $product_id ) {
		return apply_filters( 'sppa_subscription_groups', $groups, $product_id );
	}
}
