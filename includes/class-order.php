<?php
/**
 * Order, email, and admin display.
 *
 * @package AdvanceProductAddons
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class SPPA_Order
 */
class SPPA_Order {

	/**
	 * Instance.
	 *
	 * @var SPPA_Order|null
	 */
	private static $instance = null;

	/**
	 * Singleton.
	 *
	 * @return SPPA_Order
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
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'save_item_meta' ), 20, 4 );
		add_filter( 'woocommerce_order_item_display_meta_key', array( $this, 'pretty_meta_key' ), 10, 3 );
		add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'hide_internal_meta' ) );
		add_filter( 'woocommerce_order_item_get_formatted_meta_data', array( $this, 'filter_display_meta' ), 20, 2 );
		add_action( 'woocommerce_order_status_changed', array( $this, 'maybe_adjust_stock' ), 20, 4 );
	}

	/**
	 * Persist add-ons onto the order line item.
	 *
	 * @param WC_Order_Item_Product $item          Item.
	 * @param string                $cart_item_key Key.
	 * @param array                 $values        Cart values.
	 * @param WC_Order              $order         Order.
	 */
	public function save_item_meta( $item, $cart_item_key, $values, $order ) {
		if ( empty( $values['sppa']['lines'] ) ) {
			return;
		}

		$settings = SPPA_Helpers::settings();

		foreach ( $values['sppa']['lines'] as $line ) {
			$display = $line['value'];
			if ( 'yes' === $settings['show_prices_in_cart'] && ! empty( $line['amount'] ) && empty( $line['hide_price'] ) ) {
				$display .= ' (' . wp_strip_all_tags( wc_price( $line['amount'] ) ) . ')';
			}
			$item->add_meta_data( $line['label'], $display, false );
		}

		$item->add_meta_data( '_sppa_payload', $values['sppa'], true );

		if ( ! empty( $values['sppa']['total'] ) ) {
			$item->add_meta_data( '_sppa_addon_total', $values['sppa']['total'], true );
		}
	}

	/**
	 * Hide internal keys from the customer-facing meta list.
	 *
	 * @param array $hidden Hidden keys.
	 * @return array
	 */
	public function hide_internal_meta( $hidden ) {
		$hidden[] = '_sppa_payload';
		$hidden[] = '_sppa_addon_total';
		return $hidden;
	}

	/**
	 * Leave display keys as-is (already human labels).
	 *
	 * @param string $display_key Key.
	 * @return string
	 */
	public function pretty_meta_key( $display_key ) {
		return $display_key;
	}

	/**
	 * Show options on the admin order, thank-you page, emails, and packing slips.
	 *
	 * @param array                    $formatted_meta Meta.
	 * @param WC_Order_Item_Product    $item           Item.
	 * @return array
	 */
	public function filter_display_meta( $formatted_meta, $item ) {
		$settings = SPPA_Helpers::settings();
		$is_email = did_action( 'woocommerce_email_header' ) || did_action( 'woocommerce_email_before_order_table' );
		if ( is_admin() && ! $is_email ) {
			return $formatted_meta;
		}
		$allow = $is_email ? ( 'yes' === $settings['show_in_emails'] ) : ( 'yes' === $settings['show_in_order'] );
		if ( $allow ) {
			return $formatted_meta;
		}

		$payload = $item->get_meta( '_sppa_payload', true );
		$labels  = array();
		if ( is_array( $payload ) && ! empty( $payload['lines'] ) ) {
			foreach ( $payload['lines'] as $line ) {
				$labels[] = (string) ( $line['label'] ?? '' );
			}
		}
		foreach ( $formatted_meta as $id => $meta ) {
			if ( in_array( (string) $meta->key, $labels, true ) ) {
				unset( $formatted_meta[ $id ] );
			}
		}
		return $formatted_meta;
	}

	/**
	 * Reduce option stock when the order is paid-ish, restore on cancel or refund.
	 *
	 * @param int      $order_id Order ID.
	 * @param string   $from     Old status.
	 * @param string   $to       New status.
	 * @param WC_Order $order    Order.
	 */
	public function maybe_adjust_stock( $order_id, $from, $to, $order ) {
		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order_id );
		}
		if ( ! $order ) {
			return;
		}

		$reduce  = array( 'processing', 'completed', 'on-hold' );
		$restore = array( 'cancelled', 'refunded', 'failed' );
		$flag    = $order->get_meta( '_sppa_stock_reduced' );

		if ( in_array( $to, $reduce, true ) && 'yes' !== $flag ) {
			$this->adjust_stock( $order, -1 );
			$order->update_meta_data( '_sppa_stock_reduced', 'yes' );
			$order->save();
		} elseif ( in_array( $to, $restore, true ) && 'yes' === $flag ) {
			$this->adjust_stock( $order, 1 );
			$order->update_meta_data( '_sppa_stock_reduced', 'no' );
			$order->save();
		}
	}

	/**
	 * Apply stock delta for every chosen option on the order.
	 *
	 * @param WC_Order $order Order.
	 * @param int      $dir   -1 sell, +1 restore.
	 */
	protected function adjust_stock( $order, $dir ) {
		foreach ( $order->get_items() as $item ) {
			$payload = $item->get_meta( '_sppa_payload', true );
			if ( ! is_array( $payload ) || empty( $payload['lines'] ) ) {
				continue;
			}
			$product_id = $item->get_product_id();
			$qty        = max( 1, (int) $item->get_quantity() );
			foreach ( $payload['lines'] as $line ) {
				if ( empty( $line['option_id'] ) || empty( $line['field_id'] ) ) {
					continue;
				}
				SPPA_Repository::change_option_stock( $product_id, $line['field_id'], $line['option_id'], $dir * $qty );
			}
		}
	}
}
