<?php
/**
 * Resolve which add-on groups apply to a product.
 *
 * @package AdvanceProductAddons
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class SPPA_Resolver
 */
class SPPA_Resolver {

	/**
	 * Groups that should render on a product.
	 *
	 * @param int $product_id Product ID.
	 * @return array
	 */
	public static function for_product( $product_id ) {
		$product_id = absint( $product_id );
		$product    = wc_get_product( $product_id );
		if ( ! $product ) {
			return array();
		}

		$groups = array();
		foreach ( SPPA_Repository::get_product_groups( $product_id ) as $group ) {
			if ( 'inactive' === ( $group['status'] ?? 'active' ) ) {
				continue;
			}
			$group['source'] = 'product';
			$groups[]        = $group;
		}

		if ( ! SPPA_Repository::excludes_global( $product_id ) ) {
			foreach ( SPPA_Repository::get_global_groups() as $global ) {
				if ( 'inactive' === ( $global['status'] ?? 'active' ) ) {
					continue;
				}
				if ( self::global_matches( $global, $product ) ) {
					$groups[] = $global;
				}
			}
		}

		usort(
			$groups,
			function ( $a, $b ) {
				return ( (int) ( $a['priority'] ?? 10 ) ) <=> ( (int) ( $b['priority'] ?? 10 ) );
			}
		);

		return apply_filters( 'sppa_resolved_groups', $groups, $product_id );
	}

	/**
	 * Keep groups that apply to the chosen variation.
	 *
	 * Empty variation list means the group shows for every variation.
	 *
	 * @param array $groups       Groups.
	 * @param int   $variation_id Variation ID.
	 * @return array
	 */
	public static function filter_variations( $groups, $variation_id ) {
		$variation_id = absint( $variation_id );
		$out          = array();
		foreach ( $groups as $group ) {
			$ids = $group['variation_ids'] ?? array();
			if ( empty( $ids ) ) {
				$out[] = $group;
				continue;
			}
			if ( $variation_id && in_array( $variation_id, array_map( 'absint', $ids ), true ) ) {
				$out[] = $group;
			}
		}
		return $out;
	}

	/**
	 * Does a global group apply to this product?
	 *
	 * @param array      $group   Group.
	 * @param WC_Product $product Product.
	 * @return bool
	 */
	public static function global_matches( $group, $product ) {
		$id = $product->get_id();
		if ( ! empty( $group['exclude_ids'] ) && in_array( $id, $group['exclude_ids'], true ) ) {
			return false;
		}

		$cats = wc_get_product_term_ids( $id, 'product_cat' );
		$tags = wc_get_product_term_ids( $id, 'product_tag' );

		if ( ! empty( $group['exclude_cats'] ) && array_intersect( $cats, $group['exclude_cats'] ) ) {
			return false;
		}

		if ( ! empty( $group['include_types'] ) && is_array( $group['include_types'] ) ) {
			$type = $product->is_type( 'variation' ) ? 'variable' : $product->get_type();
			if ( ! in_array( $type, $group['include_types'], true ) ) {
				return false;
			}
		}

		$apply = $group['apply'] ?? 'all';
		switch ( $apply ) {
			case 'products':
				return ! empty( $group['include_ids'] ) && in_array( $id, $group['include_ids'], true );
			case 'categories':
				return ! empty( $group['include_cats'] ) && (bool) array_intersect( $cats, $group['include_cats'] );
			case 'tags':
				return ! empty( $group['include_tags'] ) && (bool) array_intersect( $tags, $group['include_tags'] );
			case 'types':
				return ! empty( $group['include_types'] );
			case 'all':
			default:
				return true;
		}
	}
}
