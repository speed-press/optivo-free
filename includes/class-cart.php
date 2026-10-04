<?php
/**
 * Cart integration.
 *
 * @package AdvanceProductAddons
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class SPPA_Cart
 */
class SPPA_Cart {

	/**
	 * Instance.
	 *
	 * @var SPPA_Cart|null
	 */
	private static $instance = null;

	/**
	 * Singleton.
	 *
	 * @return SPPA_Cart
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
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate' ), 20, 3 );
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_cart_item_data' ), 20, 3 );
		add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'from_session' ), 20, 2 );
		add_filter( 'woocommerce_get_item_data', array( $this, 'item_data' ), 20, 2 );
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'add_price_to_item' ), 20, 1 );
		add_filter( 'woocommerce_add_cart_item', array( $this, 'add_cart_item' ), 20, 1 );
	}

	/**
	 * Validate.
	 *
	 * @param bool $passed Passed.
	 * @param int  $product_id Product.
	 * @param int  $qty Qty.
	 * @return bool
	 */
	public function validate( $passed, $product_id, $qty ) {
		return SPPA_Validation::validate_add_to_cart( $passed, $product_id, $qty );
	}

	/**
	 * Attach add-on payload to the cart item.
	 *
	 * @param array $cart_item_data Data.
	 * @param int   $product_id     Product.
	 * @param int   $variation_id   Variation.
	 * @return array
	 */
	public function add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
		$groups = SPPA_Resolver::filter_variations( SPPA_Resolver::for_product( $product_id ), $variation_id );
		if ( empty( $groups ) ) {
			return $cart_item_data;
		}

		$values = SPPA_Validation::collect_posted_values( $groups );
		$values = $this->handle_uploads( $groups, $values );

		$visible = array();
		foreach ( $values as $fid => $val ) {
			$field = SPPA_Helpers::find_field( $groups, $fid );
			if ( ! $field ) {
				continue;
			}
			if ( ! SPPA_Conditions::field_is_visible( $field, $values, $groups ) ) {
				continue;
			}
			$visible[ $fid ] = $val;
		}

		$product = wc_get_product( $variation_id ? $variation_id : $product_id );
		$base    = $product ? (float) $product->get_price() : 0;
		$priced  = SPPA_Pricing::calculate( $groups, $visible, $base, 1 );

		$cart_item_data['sppa'] = array(
			'values' => $visible,
			'lines'  => $priced['lines'],
			'total'  => $priced['total'],
		);
		$cart_item_data['unique_key'] = md5( wp_json_encode( $visible ) . microtime( true ) );

		return $cart_item_data;
	}

	/**
	 * Handle file uploads into randomized storage.
	 *
	 * @param array $groups Groups.
	 * @param array $values Values.
	 * @return array
	 */
	protected function handle_uploads( $groups, $values ) {
		foreach ( SPPA_Helpers::flatten_fields( $groups ) as $field ) {
			if ( 'file' !== $field['type'] ) {
				continue;
			}
			$key = 'sppa_' . $field['id'];
			if ( empty( $_FILES[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce checks the add-to-cart nonce before this runs.
				continue;
			}
			$files = SPPA_Validation::normalize_files( $_FILES[ $key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput
			$saved = array();

			foreach ( $files as $file ) {
				if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
					continue;
				}
				require_once ABSPATH . 'wp-admin/includes/file.php';
				$handled = wp_handle_upload(
					$file,
					array(
						'test_form' => false,
					)
				);
				if ( empty( $handled['file'] ) || ! empty( $handled['error'] ) ) {
					continue;
				}
				$saved[] = array(
					'name' => sanitize_file_name( $file['name'] ),
					'file' => wp_basename( $handled['file'] ),
					'path' => $handled['file'],
					'url'  => $handled['url'],
				);
			}

			if ( $saved ) {
				$values[ $field['id'] ] = $saved;
			}
		}
		return $values;
	}

	/**
	 * Restore from session.
	 *
	 * @param array $cart_item Cart item.
	 * @param array $values    Session values.
	 * @return array
	 */
	public function from_session( $cart_item, $values ) {
		if ( isset( $values['sppa'] ) ) {
			$cart_item['sppa'] = $values['sppa'];
		}
		return $cart_item;
	}

	/**
	 * Display in cart / checkout.
	 *
	 * @param array $item_data Item data.
	 * @param array $cart_item Cart item.
	 * @return array
	 */
	public function item_data( $item_data, $cart_item ) {
		if ( empty( $cart_item['sppa']['lines'] ) ) {
			return $item_data;
		}

		$settings = SPPA_Helpers::settings();
		if ( is_checkout() && 'yes' !== $settings['show_in_checkout'] ) {
			return $item_data;
		}
		if ( ! is_checkout() && 'yes' !== $settings['show_in_cart'] ) {
			return $item_data;
		}

		$show_price = 'yes' === $settings['show_prices_in_cart'];

		$groups = SPPA_Resolver::for_product( $cart_item['product_id'] ?? 0 );

		foreach ( $cart_item['sppa']['lines'] as $line ) {
			$field = ! empty( $line['field_id'] ) ? SPPA_Helpers::find_field( $groups, $line['field_id'] ) : null;
			if ( $field && ! empty( $field['hide_in_cart'] ) ) {
				continue;
			}
			$value = $line['value'];
			if ( $show_price && ! empty( $line['amount'] ) ) {
				$value .= ' (' . wp_strip_all_tags( wc_price( $line['amount'] ) ) . ')';
			}
			$item_data[] = array(
				'key'   => $line['label'],
				'value' => $value,
			);
		}

		return $item_data;
	}

	/**
	 * Adjust cart item price.
	 *
	 * @param WC_Cart $cart Cart.
	 */
	public function add_price_to_item( $cart ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}
		if ( did_action( 'woocommerce_before_calculate_totals' ) > 1 ) {
			// Allow re-calc after qty change; WC fires this more than once sometimes.
		}

		foreach ( $cart->get_cart() as $cart_item ) {
			if ( empty( $cart_item['sppa'] ) || empty( $cart_item['data'] ) ) {
				continue;
			}
			$product = $cart_item['data'];
			$base    = (float) $product->get_sale_price() !== (float) '' && $product->get_sale_price() !== ''
				? (float) $product->get_sale_price()
				: (float) $product->get_regular_price();
			if ( ! $base ) {
				$base = (float) $product->get_price();
			}

			$groups = SPPA_Resolver::filter_variations( SPPA_Resolver::for_product( $cart_item['product_id'] ), $cart_item['variation_id'] ?? 0 );
			$qty    = isset( $cart_item['quantity'] ) ? (int) $cart_item['quantity'] : 1;
			$priced = SPPA_Pricing::calculate( $groups, $cart_item['sppa']['values'], $base, $qty );

			// Store per-unit add-on total. Quantity-based field prices already include their own multiplier.
			$unit_addon = (float) $priced['total'];
			$product->set_price( $base + $unit_addon );
		}
	}

	/**
	 * Ensure price is applied as soon as the item is added.
	 *
	 * @param array $cart_item Cart item.
	 * @return array
	 */
	public function add_cart_item( $cart_item ) {
		if ( empty( $cart_item['sppa']['total'] ) || empty( $cart_item['data'] ) ) {
			return $cart_item;
		}
		$product = $cart_item['data'];
		$base    = (float) $product->get_price();
		$product->set_price( $base + (float) $cart_item['sppa']['total'] );
		return $cart_item;
	}
}
