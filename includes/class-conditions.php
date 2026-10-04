<?php
/**
 * Conditional logic evaluator.
 *
 * @package AdvanceProductAddons
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class SPPA_Conditions
 */
class SPPA_Conditions {

	/**
	 * Whether a field should be visible given current values.
	 *
	 * @param array $field  Field.
	 * @param array $values Values keyed by field id.
	 * @param array $groups Groups (unused except for future product-type rules).
	 * @return bool
	 */
	public static function field_is_visible( $field, $values, $groups = array() ) {
		unset( $field, $values, $groups );
		return true;
	}

	/**
	 * Evaluate a single rule.
	 *
	 * @param array $rule   Rule.
	 * @param array $values Values.
	 * @return bool
	 */
	public static function rule_matches( $rule, $values ) {
		$field_id = $rule['field'] ?? '';
		$op       = $rule['operator'] ?? 'is';
		$expected = isset( $rule['value'] ) ? (string) $rule['value'] : '';
		$actual   = isset( $values[ $field_id ] ) ? $values[ $field_id ] : '';

		if ( is_array( $actual ) ) {
			$haystack = array_map( 'strval', $actual );
			switch ( $op ) {
				case 'is':
				case 'equals':
					return in_array( $expected, $haystack, true );
				case 'is_not':
				case 'not_equals':
					return ! in_array( $expected, $haystack, true );
				case 'is_empty':
					return empty( $haystack );
				case 'is_not_empty':
					return ! empty( $haystack );
				case 'contains':
					foreach ( $haystack as $item ) {
						if ( false !== stripos( $item, $expected ) ) {
							return true;
						}
					}
					return false;
				default:
					return in_array( $expected, $haystack, true );
			}
		}

		$actual = (string) $actual;

		switch ( $op ) {
			case 'is':
			case 'equals':
				return $actual === $expected;
			case 'is_not':
			case 'not_equals':
				return $actual !== $expected;
			case 'contains':
				return $expected !== '' && false !== stripos( $actual, $expected );
			case 'not_contains':
				return $expected === '' || false === stripos( $actual, $expected );
			case 'greater_than':
				return is_numeric( $actual ) && is_numeric( $expected ) && (float) $actual > (float) $expected;
			case 'less_than':
				return is_numeric( $actual ) && is_numeric( $expected ) && (float) $actual < (float) $expected;
			case 'greater_or_equal':
				return is_numeric( $actual ) && is_numeric( $expected ) && (float) $actual >= (float) $expected;
			case 'less_or_equal':
				return is_numeric( $actual ) && is_numeric( $expected ) && (float) $actual <= (float) $expected;
			case 'is_empty':
				return '' === $actual;
			case 'is_not_empty':
				return '' !== $actual;
			default:
				return $actual === $expected;
		}
	}

	/**
	 * Operators for the admin builder.
	 *
	 * @return array
	 */
	public static function operators() {
		return array(
			'is'               => __( 'is', 'optivo' ),
			'is_not'           => __( 'is not', 'optivo' ),
			'contains'         => __( 'contains', 'optivo' ),
			'not_contains'     => __( 'does not contain', 'optivo' ),
			'greater_than'     => __( 'greater than', 'optivo' ),
			'less_than'        => __( 'less than', 'optivo' ),
			'greater_or_equal' => __( 'greater or equal', 'optivo' ),
			'less_or_equal'    => __( 'less or equal', 'optivo' ),
			'is_empty'         => __( 'is empty', 'optivo' ),
			'is_not_empty'     => __( 'is not empty', 'optivo' ),
		);
	}
}
