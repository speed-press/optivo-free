<?php
/**
 * Live price AJAX.
 *
 * @package AdvanceProductAddons
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class SPPA_Ajax
 */
class SPPA_Ajax {

	/**
	 * Instance.
	 *
	 * @var SPPA_Ajax|null
	 */
	private static $instance = null;

	/**
	 * Singleton.
	 *
	 * @return SPPA_Ajax
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
		add_action( 'wp_ajax_sppa_live_price', array( $this, 'live_price' ) );
		add_action( 'wp_ajax_nopriv_sppa_live_price', array( $this, 'live_price' ) );
	}

	/**
	 * Recalculate price from posted values.
	 */
	public function live_price() {
		check_ajax_referer( 'sppa_frontend', 'nonce' );

		$product_id   = absint( $_POST['product_id'] ?? 0 );
		$variation_id = absint( $_POST['variation_id'] ?? 0 );
		$qty          = max( 1, absint( $_POST['quantity'] ?? 1 ) );

		$product = wc_get_product( $variation_id ? $variation_id : $product_id );
		if ( ! $product ) {
			wp_send_json_error( array( 'message' => 'invalid_product' ) );
		}

		$groups = SPPA_Resolver::filter_variations( SPPA_Resolver::for_product( $product_id ), $variation_id );
		$posted = isset( $_POST['sppa'] ) ? wp_unslash( $_POST['sppa'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! is_array( $posted ) ) {
			$posted = array();
		}

		$values = array();
		foreach ( $posted as $key => $val ) {
			$values[ sanitize_key( $key ) ] = is_array( $val ) ? array_map( 'sanitize_text_field', $val ) : sanitize_text_field( $val );
		}

		$base   = (float) $product->get_price();
		$priced = SPPA_Pricing::calculate( $groups, $values, $base, $qty );
		$total  = $base + (float) $priced['total'];

		$pretty = array();
		foreach ( $priced['lines'] as $line ) {
			$amt = (float) ( $line['amount'] ?? 0 );
			$line['hide_price']  = ! empty( $line['hide_price'] );
			$line['amount_html'] = $line['hide_price'] ? '' : wc_price( $amt );
			$pretty[]            = $line;
		}

		wp_send_json_success(
			array(
				'base'         => $base,
				'addons'       => (float) $priced['total'],
				'total'        => $total,
				'base_html'    => wc_price( $base ),
				'addons_html'  => wc_price( $priced['total'] ),
				'total_html'   => wc_price( $total ),
				'lines'        => $pretty,
				'product_name' => $product->get_name(),
				'qty'          => $qty,
				'line_html'    => wc_price( $base * $qty ),
				'display_html'=> wc_price( $total ),
			)
		);
	}
}
