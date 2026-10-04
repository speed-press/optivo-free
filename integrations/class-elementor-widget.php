<?php
/**
 * Elementor Product Add-Ons widget.
 *
 * @package AdvanceProductAddons
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

/**
 * Class SPPA_Elementor_Widget
 */
class SPPA_Elementor_Widget extends \Elementor\Widget_Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'sppa_product_addons';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Product Options', 'optivo' );
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	/**
	 * Categories.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'woocommerce-elements' );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'content',
			array(
				'label' => __( 'Add-Ons', 'optivo' ),
			)
		);

		$this->add_control(
			'product_id',
			array(
				'label'       => __( 'Product ID', 'optivo' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'description' => __( 'Leave empty to use the current product.', 'optivo' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render.
	 */
	protected function render() {
		$settings   = $this->get_settings_for_display();
		$product_id = absint( $settings['product_id'] ?? 0 );
		echo SPPA_Renderer::instance()->shortcode( array( 'product_id' => $product_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
