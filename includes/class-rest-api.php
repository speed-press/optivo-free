<?php
/**
 * REST API.
 *
 * @package AdvanceProductAddons
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class SPPA_REST_API
 */
class SPPA_REST_API {

	/**
	 * Instance.
	 *
	 * @var SPPA_REST_API|null
	 */
	private static $instance = null;

	/**
	 * Singleton.
	 *
	 * @return SPPA_REST_API
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
		add_action( 'rest_api_init', array( $this, 'register' ) );
	}

	/**
	 * Register routes.
	 */
	public function register() {
		register_rest_route(
			'sppa/v1',
			'/products/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_product' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'save_product' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
			)
		);

		register_rest_route(
			'sppa/v1',
			'/groups',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'list_groups' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
			)
		);

		register_rest_route(
			'sppa/v1',
			'/groups/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_group' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
			)
		);
	}

	/**
	 * Manage permission.
	 *
	 * @return bool
	 */
	public function can_manage() {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Product add-ons.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_product( $request ) {
		$id = absint( $request['id'] );
		return rest_ensure_response(
			array(
				'product_id'     => $id,
				'exclude_global' => SPPA_Repository::excludes_global( $id ),
				'groups'         => SPPA_Resolver::for_product( $id ),
			)
		);
	}

	/**
	 * Save product groups.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function save_product( $request ) {
		$id     = absint( $request['id'] );
		$groups = $request->get_param( 'groups' );
		if ( is_array( $groups ) ) {
			SPPA_Repository::save_product_groups( $id, $groups );
		}
		if ( null !== $request->get_param( 'exclude_global' ) ) {
			update_post_meta( $id, SPPA_META_EXCLUDE_GLOBAL, $request->get_param( 'exclude_global' ) ? 'yes' : 'no' );
		}
		return $this->get_product( $request );
	}

	/**
	 * List global groups.
	 *
	 * @return WP_REST_Response
	 */
	public function list_groups() {
		return rest_ensure_response( SPPA_Repository::get_global_groups() );
	}

	/**
	 * Single group.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_group( $request ) {
		$group = SPPA_Repository::get_global_group( absint( $request['id'] ) );
		if ( ! $group ) {
			return new WP_Error( 'not_found', __( 'Group not found.', 'optivo' ), array( 'status' => 404 ) );
		}
		return rest_ensure_response( $group );
	}
}
