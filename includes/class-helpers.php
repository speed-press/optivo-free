<?php
/**
 * Shared helpers.
 *
 * @package AdvanceProductAddons
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class SPPA_Helpers
 */
class SPPA_Helpers {

	/**
	 * Generate a unique field/option id.
	 *
	 * @param string $prefix Prefix.
	 * @return string
	 */
	public static function uid( $prefix = 'fld' ) {
		return $prefix . '_' . wp_generate_password( 8, false, false );
	}

	/**
	 * Sanitize a group/field tree coming from admin.
	 *
	 * @param array $groups Raw groups.
	 * @return array
	 */
	public static function sanitize_groups( $groups ) {
		if ( ! is_array( $groups ) ) {
			return array();
		}

		$clean = array();
		foreach ( $groups as $group ) {
			if ( ! is_array( $group ) ) {
				continue;
			}
			$clean[] = self::sanitize_group( $group );
		}
		return $clean;
	}

	/**
	 * Sanitize one group.
	 *
	 * @param array $group Group.
	 * @return array
	 */
	public static function sanitize_group( $group ) {
		$fields = array();
		if ( ! empty( $group['fields'] ) && is_array( $group['fields'] ) ) {
			foreach ( $group['fields'] as $field ) {
				if ( is_array( $field ) ) {
					$fields[] = self::sanitize_field( $field );
				}
			}
		}

		return array(
			'id'          => sanitize_key( $group['id'] ?? self::uid( 'grp' ) ),
			'name'        => sanitize_text_field( $group['name'] ?? '' ),
			'description' => wp_kses_post( $group['description'] ?? '' ),
			'status'      => in_array( $group['status'] ?? 'active', array( 'active', 'inactive' ), true ) ? $group['status'] : 'active',
			'priority'    => absint( $group['priority'] ?? 10 ),
			'layout'      => self::normalize_layout( $group['layout'] ?? 'vertical' ),
			'style'         => self::normalize_style( $group['style'] ?? 'default' ),
			'variation_ids' => self::id_list( $group['variation_ids'] ?? array() ),
			'fields'        => $fields,
		);
	}

	/**
	 * Comma list or array of IDs.
	 *
	 * @param mixed $raw Raw.
	 * @return array
	 */
	public static function id_list( $raw ) {
		if ( is_array( $raw ) ) {
			$raw = implode( ',', $raw );
		}
		$parts = array_filter( array_map( 'absint', explode( ',', (string) $raw ) ) );
		return array_values( $parts );
	}

	/**
	 * Give copied groups fresh field and option IDs.
	 *
	 * @param array $groups Groups.
	 * @return array
	 */
	public static function regenerate_ids( $groups ) {
		$groups = self::sanitize_groups( $groups );
		foreach ( $groups as &$group ) {
			$map         = array();
			$group['id'] = self::uid( 'grp' );
			if ( empty( $group['fields'] ) ) {
				continue;
			}
			foreach ( $group['fields'] as &$field ) {
				$old            = $field['id'];
				$field['id']    = self::uid( 'fld' );
				$map[ $old ]    = $field['id'];
				if ( ! empty( $field['options'] ) ) {
					foreach ( $field['options'] as &$option ) {
						$option['id'] = self::uid( 'opt' );
					}
					unset( $option );
				}
			}
			unset( $field );
			foreach ( $group['fields'] as &$field ) {
				if ( empty( $field['conditions']['rules'] ) ) {
					continue;
				}
				foreach ( $field['conditions']['rules'] as &$rule ) {
					if ( isset( $map[ $rule['field'] ] ) ) {
						$rule['field'] = $map[ $rule['field'] ];
					}
				}
				unset( $rule );
			}
			unset( $field );
		}
		unset( $group );
		return $groups;
	}

	/**
	 * Sanitize one field.
	 *
	 * @param array $field Field.
	 * @return array
	 */
	public static function sanitize_field( $field ) {
		$types   = array_keys( SPPA_Field_Types::all() );
		$type    = sanitize_key( $field['type'] ?? 'text' );
		if ( ! in_array( $type, $types, true ) ) {
			$type = 'text';
		}

		$options = array();
		if ( ! empty( $field['options'] ) && is_array( $field['options'] ) ) {
			foreach ( $field['options'] as $option ) {
				if ( is_array( $option ) ) {
					$options[] = self::sanitize_option( $option );
				}
			}
		}

		$conditions = array(
			'enabled' => ! empty( $field['conditions']['enabled'] ),
			'match'   => ( isset( $field['conditions']['match'] ) && 'any' === $field['conditions']['match'] ) ? 'any' : 'all',
			'action'  => ( isset( $field['conditions']['action'] ) && 'hide' === $field['conditions']['action'] ) ? 'hide' : 'show',
			'rules'   => array(),
		);
		if ( ! empty( $field['conditions']['rules'] ) && is_array( $field['conditions']['rules'] ) ) {
			foreach ( $field['conditions']['rules'] as $rule ) {
				if ( empty( $rule['field'] ) ) {
					continue;
				}
				$conditions['rules'][] = array(
					'field'    => sanitize_text_field( $rule['field'] ),
					'operator' => sanitize_key( $rule['operator'] ?? 'is' ),
					'value'    => sanitize_text_field( $rule['value'] ?? '' ),
				);
			}
		}

		$price_types = array( 'none', 'flat', 'percentage', 'quantity', 'character', 'length', 'area', 'weight', 'negative' );
		$price_type  = sanitize_key( $field['price_type'] ?? 'none' );
		if ( ! in_array( $price_type, $price_types, true ) ) {
			$price_type = 'none';
		}

		return array(
			'id'                 => sanitize_key( $field['id'] ?? self::uid( 'fld' ) ),
			'type'               => $type,
			'label'              => sanitize_text_field( $field['label'] ?? '' ),
			'description'        => wp_kses_post( $field['description'] ?? '' ),
			'placeholder'        => sanitize_text_field( $field['placeholder'] ?? '' ),
			'required'           => ! empty( $field['required'] ),
			'required_message'   => sanitize_text_field( $field['required_message'] ?? '' ),
			'price_type'         => $price_type,
			'price'              => self::sanitize_price( $field['price'] ?? '' ),
			'price_tiers'        => self::sanitize_tiers( $field['price_tiers'] ?? array() ),
			'char_base_count'    => absint( $field['char_base_count'] ?? 0 ),
			'char_base_price'    => self::sanitize_price( $field['char_base_price'] ?? '' ),
			'char_extra_price'   => self::sanitize_price( $field['char_extra_price'] ?? '' ),
			'min'                => sanitize_text_field( $field['min'] ?? '' ),
			'max'                => sanitize_text_field( $field['max'] ?? '' ),
			'step'               => sanitize_text_field( $field['step'] ?? '' ),
			'default'            => is_array( $field['default'] ?? '' ) ? array_map( 'sanitize_text_field', $field['default'] ) : sanitize_text_field( $field['default'] ?? '' ),
			'allowed_chars'      => sanitize_key( $field['allowed_chars'] ?? '' ),
			'regex'              => sanitize_text_field( $field['regex'] ?? '' ),
			'display_style'      => sanitize_key( $field['display_style'] ?? 'default' ),
			'swatch_shape'       => sanitize_key( $field['swatch_shape'] ?? 'circle' ),
			'hide_price'         => ! empty( $field['hide_price'] ),
			'hide_in_cart'       => ! empty( $field['hide_in_cart'] ),
			'multiple'           => ! empty( $field['multiple'] ),
			'max_files'          => absint( $field['max_files'] ?? 1 ),
			'max_file_mb'        => absint( $field['max_file_mb'] ?? 10 ),
			'allowed_types'      => sanitize_text_field( $field['allowed_types'] ?? 'jpg,jpeg,png,pdf' ),
			'min_days'           => absint( $field['min_days'] ?? 0 ),
			'max_days'           => absint( $field['max_days'] ?? 0 ),
			'blocked_dates'      => sanitize_textarea_field( $field['blocked_dates'] ?? '' ),
			'weekdays'           => is_array( $field['weekdays'] ?? null ) ? array_map( 'absint', $field['weekdays'] ) : array(),
			'stock_enabled'      => ! empty( $field['stock_enabled'] ),
			'stock_qty'          => $field['stock_qty'] === '' || ! isset( $field['stock_qty'] ) ? '' : absint( $field['stock_qty'] ),
			'hide_out_of_stock'  => ! empty( $field['hide_out_of_stock'] ),
			'html'               => wp_kses_post( $field['html'] ?? '' ),
			'options'            => $options,
			'conditions'         => $conditions,
			'role_prices'        => self::sanitize_role_prices( $field['role_prices'] ?? array() ),
		);
	}

	/**
	 * Sanitize option.
	 *
	 * @param array $option Option.
	 * @return array
	 */
	public static function sanitize_option( $option ) {
		$price_types = array( 'none', 'flat', 'percentage', 'quantity', 'negative' );
		$price_type  = sanitize_key( $option['price_type'] ?? 'flat' );
		if ( ! in_array( $price_type, $price_types, true ) ) {
			$price_type = 'flat';
		}

		return array(
			'id'          => sanitize_key( $option['id'] ?? self::uid( 'opt' ) ),
			'label'       => sanitize_text_field( $option['label'] ?? '' ),
			'description' => sanitize_text_field( $option['description'] ?? '' ),
			'price_type'  => $price_type,
			'price'       => self::sanitize_price( $option['price'] ?? '' ),
			'image'       => absint( $option['image'] ?? 0 ),
			'color'       => sanitize_hex_color( $option['color'] ?? '' ) ?: sanitize_text_field( $option['color'] ?? '' ),
			'default'     => ! empty( $option['default'] ),
			'stock'       => ( isset( $option['stock'] ) && '' !== $option['stock'] ) ? absint( $option['stock'] ) : '',
			'tooltip'     => sanitize_text_field( $option['tooltip'] ?? '' ),
		);
	}

	/**
	 * Sanitize price value (allows negatives).
	 *
	 * @param mixed $price Price.
	 * @return string
	 */
	public static function sanitize_price( $price ) {
		if ( '' === $price || null === $price ) {
			return '';
		}
		$price = str_replace( ',', '.', (string) $price );
		return is_numeric( $price ) ? (string) (float) $price : '';
	}

	/**
	 * Sanitize quantity price tiers.
	 *
	 * @param array $tiers Tiers.
	 * @return array
	 */
	public static function sanitize_tiers( $tiers ) {
		if ( ! is_array( $tiers ) ) {
			return array();
		}
		$out = array();
		foreach ( $tiers as $tier ) {
			if ( ! is_array( $tier ) ) {
				continue;
			}
			$out[] = array(
				'min'   => absint( $tier['min'] ?? 1 ),
				'max'   => absint( $tier['max'] ?? 0 ),
				'price' => self::sanitize_price( $tier['price'] ?? '' ),
			);
		}
		return $out;
	}

	/**
	 * Sanitize role-based prices.
	 *
	 * @param array $roles Roles.
	 * @return array
	 */
	public static function sanitize_role_prices( $roles ) {
		if ( ! is_array( $roles ) ) {
			return array();
		}
		$out = array();
		foreach ( $roles as $role => $price ) {
			$out[ sanitize_key( $role ) ] = self::sanitize_price( $price );
		}
		return $out;
	}

	/**
	 * Settings helper.
	 *
	 * @return array
	 */
	public static function settings() {
		$defaults = array(
			'show_in_cart'        => 'yes',
			'show_in_checkout'    => 'yes',
			'show_in_order'       => 'yes',
			'show_in_emails'      => 'yes',
			'show_prices_in_cart' => 'yes',
			'show_summary'        => 'yes',
			'live_price'          => 'yes',
			'upload_max_mb'       => 10,
			'allowed_mime_types'  => 'jpg,jpeg,png,gif,webp,pdf',
		);
		return wp_parse_args( get_option( 'sppa_settings', array() ), $defaults );
	}

	/**
	 * Format money using WC.
	 *
	 * @param float $amount Amount.
	 * @return string
	 */
	public static function format_price( $amount ) {
		if ( function_exists( 'wc_price' ) ) {
			return wc_price( $amount );
		}
		return number_format( (float) $amount, 2 );
	}

	/**
	 * Current customer role.
	 *
	 * @return string
	 */
	public static function current_role() {
		if ( ! is_user_logged_in() ) {
			return 'guest';
		}
		$user = wp_get_current_user();
		return ! empty( $user->roles[0] ) ? $user->roles[0] : 'customer';
	}

	/**
	 * Get upload dir for add-on files.
	 *
	 * @return array
	 */
	public static function upload_dir() {
		$uploads = wp_upload_dir();
		$dir     = trailingslashit( $uploads['basedir'] ) . 'sppa_uploads/' . wp_hash( wp_salt() . gmdate( 'Y-m' ) );
		$url     = trailingslashit( $uploads['baseurl'] ) . 'sppa_uploads/' . wp_hash( wp_salt() . gmdate( 'Y-m' ) );
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
			$fs = new WP_Filesystem_Direct( false );
			$fs->put_contents( $dir . '/index.html', '' );
			$fs->put_contents( trailingslashit( $uploads['basedir'] ) . 'sppa_uploads/.htaccess', "Options -Indexes\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n" );
		}
		return array(
			'path' => $dir,
			'url'  => $url,
		);
	}

	/**
	 * Recursively find a field by id in groups.
	 *
	 * @param array  $groups Groups.
	 * @param string $field_id Field id.
	 * @return array|null
	 */
	public static function find_field( $groups, $field_id ) {
		foreach ( $groups as $group ) {
			if ( empty( $group['fields'] ) ) {
				continue;
			}
			foreach ( $group['fields'] as $field ) {
				if ( isset( $field['id'] ) && (string) $field['id'] === (string) $field_id ) {
					return $field;
				}
			}
		}
		return null;
	}

	/**
	 * Flatten fields from groups.
	 *
	 * @param array $groups Groups.
	 * @return array
	 */
	public static function flatten_fields( $groups ) {
		$out = array();
		foreach ( $groups as $group ) {
			if ( empty( $group['fields'] ) || ( isset( $group['status'] ) && 'inactive' === $group['status'] ) ) {
				continue;
			}
			foreach ( $group['fields'] as $field ) {
				$out[] = $field;
			}
		}
		return $out;
	}

	/**
	 * JSON encode for HTML attribute.
	 *
	 * @param mixed $data Data.
	 * @return string
	 */
	public static function json_attr( $data ) {
		return esc_attr( wp_json_encode( $data ) );
	}

	/**
	 * Allowed layouts.
	 *
	 * @param string $layout Layout.
	 * @return string
	 */
	public static function normalize_layout( $layout ) {
		$layout = sanitize_key( $layout );
		$map    = array(
			'compact' => 'vertical',
			'stacked' => 'vertical',
		);
		if ( isset( $map[ $layout ] ) ) {
			$layout = $map[ $layout ];
		}
		$ok = array( 'vertical', 'horizontal', 'grid', 'two-columns' );
		return in_array( $layout, $ok, true ) ? $layout : 'vertical';
	}

	/**
	 * Allowed styles.
	 *
	 * @param string $style Style.
	 * @return string
	 */
	public static function normalize_style( $style ) {
		$style = sanitize_key( $style );
		$map   = array(
			'minimal'  => 'default',
			'bordered' => 'default',
			'soft'     => 'cards',
		);
		if ( isset( $map[ $style ] ) ) {
			$style = $map[ $style ];
		}
		$ok = array( 'default', 'cards', 'pills', 'swatches' );
		return in_array( $style, $ok, true ) ? $style : 'default';
	}
}
