<?php
/**
 * Persistence for product and global add-on groups.
 *
 * @package AdvanceProductAddons
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class SPPA_Repository
 */
class SPPA_Repository {

	/**
	 * Product-level groups.
	 *
	 * @param int $product_id Product ID.
	 * @return array
	 */
	public static function get_product_groups( $product_id ) {
		$groups = get_post_meta( $product_id, SPPA_META_PRODUCT, true );
		if ( ! is_array( $groups ) ) {
			return array();
		}
		return $groups;
	}

	/**
	 * Save product-level groups.
	 *
	 * @param int   $product_id Product ID.
	 * @param array $groups     Groups.
	 */
	public static function save_product_groups( $product_id, $groups ) {
		update_post_meta( $product_id, SPPA_META_PRODUCT, SPPA_Helpers::sanitize_groups( $groups ) );
	}

	/**
	 * Whether a product excludes global add-ons.
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	public static function excludes_global( $product_id ) {
		return 'yes' === get_post_meta( $product_id, SPPA_META_EXCLUDE_GLOBAL, true );
	}

	/**
	 * All published global groups.
	 *
	 * @return array
	 */
	public static function get_global_groups() {
		$posts = get_posts(
			array(
				'post_type'      => 'sppa_addon_group',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'menu_order title',
				'order'          => 'ASC',
			)
		);

		$groups = array();
		foreach ( $posts as $post ) {
			$base = self::hydrate_global( $post );
			if ( ! empty( $base['_all_groups'] ) && is_array( $base['_all_groups'] ) ) {
				foreach ( $base['_all_groups'] as $i => $g ) {
					$g['id']           = 'global_' . $post->ID . '_' . $i;
					$g['global_id']    = $post->ID;
					$g['source']       = 'global';
					$g['apply']        = $base['apply'];
					$g['include_ids']  = $base['include_ids'];
					$g['exclude_ids']  = $base['exclude_ids'];
					$g['include_cats'] = $base['include_cats'];
					$g['include_tags'] = $base['include_tags'];
					$g['include_types']= $base['include_types'];
					$g['exclude_cats'] = $base['exclude_cats'];
					unset( $g['_all_groups'] );
					$groups[] = $g;
				}
			} else {
				unset( $base['_all_groups'] );
				$groups[] = $base;
			}
		}
		return $groups;
	}

	/**
	 * Single global group.
	 *
	 * @param int $id Post ID.
	 * @return array|null
	 */
	public static function get_global_group( $id ) {
		$post = get_post( $id );
		if ( ! $post || 'sppa_addon_group' !== $post->post_type ) {
			return null;
		}
		return self::hydrate_global( $post );
	}

	/**
	 * Hydrate a CPT into the group schema used everywhere else.
	 *
	 * @param WP_Post $post Post.
	 * @return array
	 */
	public static function hydrate_global( $post ) {
		$multi = get_post_meta( $post->ID, '_sppa_groups', true );
		if ( is_array( $multi ) && ! empty( $multi ) ) {
			$first = $multi[0];
			$first['id']        = 'global_' . $post->ID;
			$first['global_id'] = $post->ID;
			$first['source']    = 'global';
			$first['apply']     = get_post_meta( $post->ID, '_sppa_apply', true ) ?: 'all';
			$first['include_ids']  = array_map( 'absint', (array) get_post_meta( $post->ID, '_sppa_include_ids', true ) );
			$first['exclude_ids']  = array_map( 'absint', (array) get_post_meta( $post->ID, '_sppa_exclude_ids', true ) );
			$first['include_cats'] = array_map( 'absint', (array) get_post_meta( $post->ID, '_sppa_include_cats', true ) );
			$first['include_tags'] = array_map( 'absint', (array) get_post_meta( $post->ID, '_sppa_include_tags', true ) );
			$first['include_types']= (array) get_post_meta( $post->ID, '_sppa_include_types', true );
			$first['exclude_cats'] = array_map( 'absint', (array) get_post_meta( $post->ID, '_sppa_exclude_cats', true ) );
			$first['_all_groups']  = $multi;
			return $first;
		}
		$fields  = get_post_meta( $post->ID, '_sppa_fields', true );
		$apply   = get_post_meta( $post->ID, '_sppa_apply', true );
		$include = get_post_meta( $post->ID, '_sppa_include_ids', true );
		$exclude = get_post_meta( $post->ID, '_sppa_exclude_ids', true );
		$cats    = get_post_meta( $post->ID, '_sppa_include_cats', true );
		$tags    = get_post_meta( $post->ID, '_sppa_include_tags', true );
		$types   = get_post_meta( $post->ID, '_sppa_include_types', true );
		$excats  = get_post_meta( $post->ID, '_sppa_exclude_cats', true );

		$title  = get_post_meta( $post->ID, '_sppa_group_title', true );
		$desc   = get_post_meta( $post->ID, '_sppa_group_desc', true );
		$status = get_post_meta( $post->ID, '_sppa_status', true );

		return array(
			'id'           => 'global_' . $post->ID,
			'global_id'    => $post->ID,
			'name'         => is_string( $title ) ? $title : '',
			'description'  => is_string( $desc ) ? $desc : '',
			'status'       => ( 'inactive' === $status ) ? 'inactive' : 'active',
			'priority'     => absint( get_post_meta( $post->ID, '_sppa_priority', true ) ?: $post->menu_order ),
			'layout'       => get_post_meta( $post->ID, '_sppa_layout', true ) ?: 'vertical',
			'style'        => get_post_meta( $post->ID, '_sppa_style', true ) ?: 'default',
			'apply'        => $apply ?: 'all',
			'include_ids'  => is_array( $include ) ? array_map( 'absint', $include ) : array(),
			'exclude_ids'  => is_array( $exclude ) ? array_map( 'absint', $exclude ) : array(),
			'include_cats' => is_array( $cats ) ? array_map( 'absint', $cats ) : array(),
			'include_tags' => is_array( $tags ) ? array_map( 'absint', $tags ) : array(),
			'include_types'=> is_array( $types ) ? $types : array(),
			'exclude_cats' => is_array( $excats ) ? array_map( 'absint', $excats ) : array(),
			'fields'       => is_array( $fields ) ? $fields : array(),
			'source'       => 'global',
		);
	}

	/**
	 * Persist a global group from admin form.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $data    Data.
	 */
	public static function save_global_meta( $post_id, $data ) {
		if ( isset( $data['groups'] ) && is_array( $data['groups'] ) ) {
			update_post_meta( $post_id, '_sppa_groups', SPPA_Helpers::sanitize_groups( $data['groups'] ) );
		}
		if ( isset( $data['fields'] ) ) {
			$sanitized = SPPA_Helpers::sanitize_groups( array( array( 'fields' => $data['fields'] ) ) );
			$fields    = ! empty( $sanitized[0]['fields'] ) ? $sanitized[0]['fields'] : array();
			update_post_meta( $post_id, '_sppa_fields', $fields );
		}
		if ( isset( $data['apply'] ) ) {
			update_post_meta( $post_id, '_sppa_apply', sanitize_key( $data['apply'] ) );
		}
		foreach ( array( 'include_ids', 'exclude_ids', 'include_cats', 'include_tags', 'exclude_cats' ) as $key ) {
			$raw = $data[ $key ] ?? array();
			if ( is_string( $raw ) ) {
				$raw = array_filter( array_map( 'absint', explode( ',', $raw ) ) );
			}
			update_post_meta( $post_id, '_sppa_' . $key, array_map( 'absint', (array) $raw ) );
		}
		$types = $data['include_types'] ?? array();
		update_post_meta( $post_id, '_sppa_include_types', array_map( 'sanitize_key', (array) $types ) );
		if ( isset( $data['layout'] ) ) {
			update_post_meta( $post_id, '_sppa_layout', sanitize_key( $data['layout'] ) );
		}
		if ( isset( $data['style'] ) ) {
			update_post_meta( $post_id, '_sppa_style', sanitize_key( $data['style'] ) );
		}
		if ( isset( $data['name'] ) ) {
			update_post_meta( $post_id, '_sppa_group_title', sanitize_text_field( $data['name'] ) );
		}
		if ( isset( $data['description'] ) ) {
			update_post_meta( $post_id, '_sppa_group_desc', wp_kses_post( $data['description'] ) );
		}
		if ( isset( $data['status'] ) ) {
			$status = in_array( $data['status'], array( 'active', 'inactive' ), true ) ? $data['status'] : 'active';
			update_post_meta( $post_id, '_sppa_status', $status );
		}
		if ( isset( $data['priority'] ) ) {
			update_post_meta( $post_id, '_sppa_priority', absint( $data['priority'] ) );
		}
	}

	/**
	 * Change one option's stock on a product group or any global group.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $field_id   Field ID.
	 * @param string $option_id  Option ID.
	 * @param int    $delta      Positive restores, negative sells.
	 */
	public static function change_option_stock( $product_id, $field_id, $option_id, $delta ) {
		$delta = (int) $delta;
		if ( 0 === $delta || '' === $option_id ) {
			return;
		}

		$groups  = self::get_product_groups( $product_id );
		$changed = self::walk_stock( $groups, $field_id, $option_id, $delta );
		if ( $changed ) {
			update_post_meta( $product_id, SPPA_META_PRODUCT, $groups );
			return;
		}

		$posts = get_posts(
			array(
				'post_type'      => 'sppa_addon_group',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);
		foreach ( $posts as $post_id ) {
			$stored = get_post_meta( $post_id, '_sppa_groups', true );
			if ( ! is_array( $stored ) || empty( $stored ) ) {
				continue;
			}
			if ( self::walk_stock( $stored, $field_id, $option_id, $delta ) ) {
				update_post_meta( $post_id, '_sppa_groups', $stored );
				return;
			}
		}
	}

	/**
	 * Apply a stock delta inside a group tree.
	 *
	 * @param array  $groups    Groups (by reference).
	 * @param string $field_id  Field.
	 * @param string $option_id Option.
	 * @param int    $delta     Delta.
	 * @return bool
	 */
	protected static function walk_stock( &$groups, $field_id, $option_id, $delta ) {
		foreach ( $groups as &$group ) {
			if ( empty( $group['fields'] ) || ! is_array( $group['fields'] ) ) {
				continue;
			}
			foreach ( $group['fields'] as &$field ) {
				if ( (string) ( $field['id'] ?? '' ) !== (string) $field_id || empty( $field['options'] ) ) {
					continue;
				}
				foreach ( $field['options'] as &$option ) {
					if ( (string) ( $option['id'] ?? '' ) !== (string) $option_id ) {
						continue;
					}
					if ( ! isset( $option['stock'] ) || '' === $option['stock'] ) {
						return false;
					}
					$option['stock'] = max( 0, (int) $option['stock'] + (int) $delta );
					return true;
				}
			}
		}
		return false;
	}
}
