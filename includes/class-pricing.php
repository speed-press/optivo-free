<?php
/**
 * Pricing engine.
 *
 * @package AdvanceProductAddons
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class SPPA_Pricing
 */
class SPPA_Pricing {

	/**
	 * Calculate total add-on price for a set of submitted values.
	 *
	 * @param array $groups   Resolved groups.
	 * @param array $values   Submitted values keyed by field id.
	 * @param float $base     Product base price.
	 * @param int   $qty      Product quantity.
	 * @return array { total, lines }
	 */
	public static function calculate( $groups, $values, $base = 0, $qty = 1 ) {
		$lines = array();
		$total = 0.0;
		$qty   = max( 1, (int) $qty );

		foreach ( SPPA_Helpers::flatten_fields( $groups ) as $field ) {
			if ( ! isset( SPPA_Field_Types::all()[ $field['type'] ] ) ) {
				continue;
			}
			if ( ! SPPA_Field_Types::is_input( $field['type'] ) ) {
				continue;
			}
			if ( ! SPPA_Conditions::field_is_visible( $field, $values, $groups ) ) {
				continue;
			}

			$raw = isset( $values[ $field['id'] ] ) ? $values[ $field['id'] ] : null;
			if ( self::is_empty_value( $raw, $field['type'] ) ) {
				continue;
			}

			$field_lines = self::price_field( $field, $raw, $base, $qty );
			foreach ( $field_lines as $line ) {
				$total  += (float) $line['amount'];
				$lines[] = $line;
			}
		}

		return array(
			'total' => round( $total, wc_get_price_decimals() ),
			'lines' => $lines,
		);
	}

	/**
	 * Price a single field.
	 *
	 * @param array $field Field.
	 * @param mixed $raw   Value.
	 * @param float $base  Base price.
	 * @param int   $qty   Qty.
	 * @return array
	 */
	public static function price_field( $field, $raw, $base, $qty ) {
		$type = $field['type'];

		if ( 'customer_price' === $type ) {
			$amount = (float) $raw;
			return array(
				array(
					'field_id' => $field['id'],
					'label'    => $field['label'],
					'value'    => self::format_value_label( $field, $raw ),
					'amount'   => $amount,
					'hide_price' => ! empty( $field['hide_price'] ),
				),
			);
		}

		if ( SPPA_Field_Types::has_options( $type ) ) {
			return self::price_options( $field, $raw, $base, $qty );
		}

		if ( 'checkbox' === $type ) {
			$checked = in_array( $raw, array( '1', 1, 'yes', true, 'on' ), true ) || ( is_string( $raw ) && $raw === $field['id'] );
			if ( ! $checked ) {
				return array();
			}
			$amount = self::compute_amount( $field['price_type'], $field['price'], $base, $qty, $raw, $field );
			$amount = self::apply_role_price( $field, $amount );
			return array(
				array(
					'field_id' => $field['id'],
					'label'    => $field['label'],
					'value'    => __( 'Yes', 'optivo' ),
					'amount'   => $amount,
					'hide_price' => ! empty( $field['hide_price'] ),
				),
			);
		}

		$amount = self::compute_amount( $field['price_type'], $field['price'], $base, $qty, $raw, $field );
		$amount = self::apply_role_price( $field, $amount );

		return array(
			array(
				'field_id' => $field['id'],
				'label'    => $field['label'],
				'value'    => self::format_value_label( $field, $raw ),
				'amount'   => $amount,
				'hide_price' => ! empty( $field['hide_price'] ),
			),
		);
	}

	/**
	 * Price choice options.
	 *
	 * @param array $field Field.
	 * @param mixed $raw   Value.
	 * @param float $base  Base.
	 * @param int   $qty   Qty.
	 * @return array
	 */
	protected static function price_options( $field, $raw, $base, $qty ) {
		$selected = is_array( $raw ) ? $raw : array( $raw );
		$lines    = array();
		$options  = isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : array();

		foreach ( $options as $option ) {
			if ( ! in_array( (string) $option['id'], array_map( 'strval', $selected ), true ) && ! in_array( (string) $option['label'], array_map( 'strval', $selected ), true ) ) {
				continue;
			}
			$amount = self::compute_amount( $option['price_type'], $option['price'], $base, $qty, $raw, $field );
			$amount = self::apply_role_price( $field, $amount );
			$lines[] = array(
				'field_id'  => $field['id'],
				'option_id' => $option['id'],
				'label'     => $field['label'],
				'value'     => $option['label'],
				'amount'    => $amount,
				'hide_price'=> ! empty( $field['hide_price'] ),
			);
		}

		return $lines;
	}

	/**
	 * Compute a numeric amount from a price type.
	 *
	 * @param string $price_type Type.
	 * @param mixed  $price      Price.
	 * @param float  $base       Base product price.
	 * @param int    $qty        Product qty.
	 * @param mixed  $raw        Raw value.
	 * @param array  $field      Field.
	 * @return float
	 */
	public static function compute_amount( $price_type, $price, $base, $qty, $raw, $field = array() ) {
		$price = (float) $price;

		switch ( $price_type ) {
			case 'none':
				return 0.0;
			case 'percentage':
				return ( $base * $price ) / 100;
			case 'quantity':
				$unit = self::tier_unit_price( $field, $raw, $price );
				$mult = is_numeric( $raw ) ? (float) $raw : (float) $qty;
				return $unit * $mult;
			case 'character':
				$text  = is_string( $raw ) ? $raw : '';
				$count = self::char_count( $text );
				$base_n = absint( $field['char_base_count'] ?? 0 );
				if ( $base_n > 0 ) {
					$extra = max( 0, $count - $base_n );
					return (float) ( $field['char_base_price'] ?? 0 ) + ( $extra * (float) ( $field['char_extra_price'] ?? $price ) );
				}
				return $count * $price;
			case 'length':
			case 'area':
			case 'weight':
				return is_numeric( $raw ) ? (float) $raw * $price : 0.0;
			case 'negative':
				return -abs( $price );
			case 'flat':
			default:
				return $price;
		}
	}

	/**
	 * Quantity tier unit price.
	 *
	 * @param array $field Field.
	 * @param mixed $raw   Value.
	 * @param float $fallback Fallback.
	 * @return float
	 */
	protected static function tier_unit_price( $field, $raw, $fallback ) {
		$n     = is_numeric( $raw ) ? (int) $raw : 1;
		$tiers = $field['price_tiers'] ?? array();
		if ( empty( $tiers ) || ! is_array( $tiers ) ) {
			return (float) $fallback;
		}
		foreach ( $tiers as $tier ) {
			$min = (int) ( $tier['min'] ?? 0 );
			$max = (int) ( $tier['max'] ?? 0 );
			if ( $n >= $min && ( 0 === $max || $n <= $max ) ) {
				return (float) $tier['price'];
			}
		}
		return (float) $fallback;
	}

	/**
	 * Count billable characters (trim, count all remaining).
	 *
	 * @param string $text Text.
	 * @return int
	 */
	public static function char_count( $text ) {
		$text = trim( (string) $text );
		if ( function_exists( 'mb_strlen' ) ) {
			return mb_strlen( $text );
		}
		return strlen( $text );
	}

	/**
	 * Apply role override if present.
	 *
	 * @param array $field  Field.
	 * @param float $amount Amount.
	 * @return float
	 */
	protected static function apply_role_price( $field, $amount ) {
		$roles = $field['role_prices'] ?? array();
		if ( empty( $roles ) || ! is_array( $roles ) ) {
			return $amount;
		}
		$role = SPPA_Helpers::current_role();
		if ( isset( $roles[ $role ] ) && '' !== $roles[ $role ] ) {
			return (float) $roles[ $role ];
		}
		return $amount;
	}

	/**
	 * Human label for a stored value.
	 *
	 * @param array $field Field.
	 * @param mixed $raw   Raw.
	 * @return string
	 */
	public static function format_value_label( $field, $raw ) {
		if ( is_array( $raw ) ) {
			if ( isset( $raw['name'] ) ) {
				return (string) $raw['name'];
			}
			$labels = array();
			foreach ( $raw as $item ) {
				$labels[] = is_scalar( $item ) ? (string) $item : wp_json_encode( $item );
			}
			return implode( ', ', $labels );
		}
		if ( 'checkbox' === $field['type'] ) {
			return __( 'Yes', 'optivo' );
		}
		if ( 'file' === $field['type'] && is_string( $raw ) ) {
			return basename( $raw );
		}
		return (string) $raw;
	}

	/**
	 * Empty check.
	 *
	 * @param mixed  $raw  Value.
	 * @param string $type Type.
	 * @return bool
	 */
	public static function is_empty_value( $raw, $type ) {
		if ( null === $raw ) {
			return true;
		}
		if ( is_array( $raw ) ) {
			return empty( $raw );
		}
		if ( 'checkbox' === $type ) {
			return in_array( $raw, array( '', '0', 0, 'no', false ), true );
		}
		return '' === $raw;
	}
}
