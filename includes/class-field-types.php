<?php
/**
 * Registered field types.
 *
 * @package AdvanceProductAddons
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class SPPA_Field_Types
 */
class SPPA_Field_Types {

	/**
	 * All field types.
	 *
	 * @return array
	 */
	public static function all() {
		return apply_filters(
			'sppa_field_types',
			array(
				'text'      => array(
					'label'       => __( 'Text', 'optivo' ),
					'has_options' => false,
					'has_price'   => true,
					'input'       => true,
				),
				'textarea'  => array(
					'label'       => __( 'Textarea', 'optivo' ),
					'has_options' => false,
					'has_price'   => true,
					'input'       => true,
				),
				'number'    => array(
					'label'       => __( 'Number', 'optivo' ),
					'has_options' => false,
					'has_price'   => true,
					'input'       => true,
				),
				'email'     => array(
					'label'       => __( 'Email', 'optivo' ),
					'has_options' => false,
					'has_price'   => true,
					'input'       => true,
				),
				'select'    => array(
					'label'       => __( 'Dropdown', 'optivo' ),
					'has_options' => true,
					'has_price'   => true,
					'input'       => true,
				),
				'radio'     => array(
					'label'       => __( 'Radio', 'optivo' ),
					'has_options' => true,
					'has_price'   => true,
					'input'       => true,
				),
				'checkbox'  => array(
					'label'       => __( 'Checkbox', 'optivo' ),
					'has_options' => false,
					'has_price'   => true,
					'input'       => true,
				),
				'heading'   => array(
					'label'       => __( 'Heading', 'optivo' ),
					'has_options' => false,
					'has_price'   => false,
					'input'       => false,
				),
				'paragraph' => array(
					'label'       => __( 'Paragraph', 'optivo' ),
					'has_options' => false,
					'has_price'   => false,
					'input'       => false,
				),
			)
		);
	}

	/**
	 * Types that collect a customer value.
	 *
	 * @param string $type Type.
	 * @return bool
	 */
	public static function is_input( $type ) {
		$all = self::all();
		return ! empty( $all[ $type ]['input'] );
	}

	/**
	 * Types that have option rows.
	 *
	 * @param string $type Type.
	 * @return bool
	 */
	public static function has_options( $type ) {
		$all = self::all();
		return ! empty( $all[ $type ]['has_options'] );
	}

	/**
	 * Multi-value types.
	 *
	 * @param string $type Type.
	 * @return bool
	 */
	public static function is_multi( $type ) {
		return in_array( $type, array( 'checkbox_group', 'multiselect', 'image_checkbox' ), true );
	}
}
