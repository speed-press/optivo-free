<?php
/**
 * Gutenberg block.
 *
 * @package AdvanceProductAddons
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class SPPA_Blocks
 */
class SPPA_Blocks {

	/**
	 * Instance.
	 *
	 * @var SPPA_Blocks|null
	 */
	private static $instance = null;

	/**
	 * Singleton.
	 *
	 * @return SPPA_Blocks
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
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Register a dynamic block that renders current product add-ons.
	 */
	public function register() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			'sppa/product-addons',
			array(
				'api_version'     => 2,
				'title'           => __( 'Product Options', 'optivo' ),
				'description'     => __( 'Renders product options for the current product.', 'optivo' ),
				'category'        => 'woocommerce',
				'icon'            => 'forms',
				'render_callback' => array( $this, 'render' ),
				'attributes'      => array(
					'productId' => array(
						'type'    => 'number',
						'default' => 0,
					),
				),
			)
		);
	}

	/**
	 * Render.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$product_id = absint( $atts['productId'] ?? 0 );
		return SPPA_Renderer::instance()->shortcode( array( 'product_id' => $product_id ) );
	}
}
