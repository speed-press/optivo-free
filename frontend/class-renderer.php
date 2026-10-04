<?php
/**
 * Product page renderer.
 *
 * @package AdvanceProductAddons
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class SPPA_Renderer
 */
class SPPA_Renderer {

	/**
	 * Instance.
	 *
	 * @var SPPA_Renderer|null
	 */
	private static $instance = null;

	/**
	 * Singleton.
	 *
	 * @return SPPA_Renderer
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
		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'render' ), 12 );
		add_filter( 'woocommerce_product_add_to_cart_form_action', array( $this, 'noop_action' ), 1 );
		add_shortcode( 'sppa_product_addons', array( $this, 'shortcode' ) );
	}

	/**
	 * Keep the form action unchanged (hook exists so the class is the single frontend entry).
	 *
	 * @param string $action Action.
	 * @return string
	 */
	public function noop_action( $action ) {
		return $action;
	}

	/**
	 * Shortcode / block callback.
	 *
	 * @param array $atts Atts.
	 * @return string
	 */
	public function shortcode( $atts ) {
		$atts       = shortcode_atts( array( 'product_id' => 0 ), $atts, 'sppa_product_addons' );
		$product_id = absint( $atts['product_id'] );
		if ( ! $product_id ) {
			$product_id = get_the_ID();
		}
		ob_start();
		$this->output( $product_id );
		return ob_get_clean();
	}

	/**
	 * Default product page hook.
	 */
	public function render() {
		global $product;
		if ( ! $product ) {
			return;
		}
		$this->output( $product->get_id() );
	}

	/**
	 * Print groups.
	 *
	 * @param int $product_id Product ID.
	 */
	public function output( $product_id ) {
		$groups = SPPA_Resolver::for_product( $product_id );
		if ( empty( $groups ) ) {
			return;
		}

		$settings = SPPA_Helpers::settings();
		$product  = wc_get_product( $product_id );
		$base     = $product ? (float) $product->get_price() : 0;

		echo '<div class="sppa-wrap" data-product-id="' . esc_attr( $product_id ) . '" data-base-price="' . esc_attr( $base ) . '">';

		foreach ( $groups as $group ) {
			if ( isset( $group['status'] ) && 'inactive' === $group['status'] ) {
				continue;
			}
			if ( empty( $group['fields'] ) ) {
				continue;
			}
			$layout = SPPA_Helpers::normalize_layout( $group['layout'] ?? 'vertical' );
			$style  = SPPA_Helpers::normalize_style( $group['style'] ?? 'default' );
			$vars   = isset( $group['variation_ids'] ) ? array_filter( array_map( 'absint', (array) $group['variation_ids'] ) ) : array();
			$lock   = $product && $product->is_type( 'variable' ) && $vars;
			echo '<div class="sppa-group sppa-layout-' . esc_attr( $layout ) . ' sppa-style-' . esc_attr( $style ) . '" data-variations="' . esc_attr( implode( ',', $vars ) ) . '"' . ( $lock ? ' hidden' : '' ) . '>';
			if ( ! empty( $group['name'] ) ) {
				echo '<h3 class="sppa-group-title">' . esc_html( $group['name'] ) . '</h3>';
			}
			if ( ! empty( $group['description'] ) ) {
				echo '<div class="sppa-group-desc">' . wp_kses_post( wpautop( $group['description'] ) ) . '</div>';
			}
			echo '<div class="sppa-fields-list">';
			foreach ( $group['fields'] as $field ) {
				$this->render_field( $field, $groups );
			}
			echo '</div>';
			echo '</div>';
		}

		if ( 'yes' === $settings['show_summary'] ) {
			echo '<div class="sppa-summary" hidden>';
			echo '<div class="sppa-summary-lines"></div>';
			echo '<p class="sppa-summary-total"><span>' . esc_html__( 'Subtotal', 'optivo' ) . '</span><strong class="sppa-grand-total"></strong></p>';
			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * Render a single field.
	 *
	 * @param array $field  Field.
	 * @param array $groups Groups.
	 */
	protected function render_field( $field, $groups ) {
		$type = $field['type'] ?? 'text';
		if ( ! isset( SPPA_Field_Types::all()[ $type ] ) ) {
			return;
		}
		$id     = $field['id'];
		$name   = 'sppa[' . $id . ']';
		$req    = ! empty( $field['required'] );
		$desc   = $field['description'] ?? '';
		$label  = $field['label'] ?? '';
		$ph     = $field['placeholder'] ?? '';
		$def    = is_array( $field['default'] ?? '' ) ? '' : ( $field['default'] ?? '' );
		$conds  = array();
		$hidden = false;

		$classes = array( 'sppa-field', 'sppa-field-' . $type );
		if ( $req ) {
			$classes[] = 'sppa-required';
		}
		if ( ! empty( $field['hide_price'] ) ) {
			$classes[] = 'sppa-hide-price';
		}
		if ( 'hidden' === $type ) {
			$classes[] = 'sppa-field-hidden';
		}
		if ( ! empty( $field['display_style'] ) ) {
			$classes[] = 'sppa-display-' . $field['display_style'];
		}

		$conditions_attr = esc_attr( wp_json_encode( $conds ) );
		$hidden_attr     = $hidden ? ' hidden' : '';
		echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" data-field-id="' . esc_attr( $id ) . '" data-type="' . esc_attr( $type ) . '" data-hide-price="' . ( ! empty( $field['hide_price'] ) ? '1' : '0' ) . '" data-conditions="' . $conditions_attr . '"' . $hidden_attr . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $conditions_attr and $hidden_attr are escaped or fixed literals.

		if ( 'heading' === $type ) {
			echo '<h4 class="sppa-heading">' . esc_html( $label ) . '</h4>';
			if ( $desc ) {
				echo '<div class="sppa-desc">' . wp_kses_post( $desc ) . '</div>';
			}
			echo '</div>';
			return;
		}

		if ( 'paragraph' === $type ) {
			echo '<div class="sppa-html">' . wp_kses_post( $field['html'] ? $field['html'] : $desc ) . '</div>';
			echo '</div>';
			return;
		}

		if ( $label && 'hidden' !== $type ) {
			echo '<label class="sppa-label" for="sppa-' . esc_attr( $id ) . '">';
			echo esc_html( $label );
			if ( $req ) {
				echo ' <abbr class="required" title="required">*</abbr>';
			}
			if ( empty( $field['hide_price'] ) && ! SPPA_Field_Types::has_options( $type ) && '' !== (string) ( $field['price'] ?? '' ) && 'none' !== ( $field['price_type'] ?? 'none' ) ) {
				echo ' <span class="sppa-price-hint">' . $this->price_hint( $field['price_type'], $field['price'] ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '</label>';
		}

		if ( $desc && 'paragraph' !== $type ) {
			echo '<div class="sppa-desc">' . wp_kses_post( $desc ) . '</div>';
		}

		switch ( $type ) {
			case 'textarea':
				echo '<textarea class="sppa-input" id="sppa-' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" rows="4" placeholder="' . esc_attr( $ph ) . '"' . ( $req ? ' required' : '' ) . '>' . esc_textarea( $def ) . '</textarea>';
				break;
			case 'number':
			case 'quantity':
			case 'customer_price':
				$min  = ( isset( $field['min'] ) && '' !== $field['min'] ) ? ' min="' . esc_attr( $field['min'] ) . '"' : '';
				$max  = ( isset( $field['max'] ) && '' !== $field['max'] ) ? ' max="' . esc_attr( $field['max'] ) . '"' : '';
				$step = ! empty( $field['step'] ) ? $field['step'] : 'any';
				echo '<input class="sppa-input" type="number" step="' . esc_attr( $step ) . '" id="sppa-' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $def ) . '" placeholder="' . esc_attr( $ph ) . '"' . $min . $max . ( $req ? ' required' : '' ) . ' />'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				break;
			case 'email':
				echo '<input class="sppa-input" type="email" id="sppa-' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $def ) . '" placeholder="' . esc_attr( $ph ) . '"' . ( $req ? ' required' : '' ) . ' />';
				break;
			case 'phone':
				echo '<input class="sppa-input" type="tel" id="sppa-' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $def ) . '" placeholder="' . esc_attr( $ph ) . '"' . ( $req ? ' required' : '' ) . ' />';
				break;
			case 'url':
				echo '<input class="sppa-input" type="url" id="sppa-' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $def ) . '" placeholder="' . esc_attr( $ph ?: 'https://' ) . '"' . ( $req ? ' required' : '' ) . ' />';
				break;
			case 'color':
				echo '<input class="sppa-input" type="color" id="sppa-' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $def ?: '#000000' ) . '" />';
				break;
			case 'date':
				$min_date = '';
				if ( ! empty( $field['min_days'] ) ) {
					$min_date = gmdate( 'Y-m-d', time() + DAY_IN_SECONDS * (int) $field['min_days'] );
				}
				$max_date = '';
				if ( ! empty( $field['max_days'] ) ) {
					$max_date = gmdate( 'Y-m-d', time() + DAY_IN_SECONDS * (int) $field['max_days'] );
				}
				echo '<input class="sppa-input" type="date" id="sppa-' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $def ) . '"' . ( $min_date ? ' min="' . esc_attr( $min_date ) . '"' : '' ) . ( $max_date ? ' max="' . esc_attr( $max_date ) . '"' : '' ) . ( $req ? ' required' : '' ) . ' data-blocked="' . esc_attr( $field['blocked_dates'] ?? '' ) . '" />';
				break;
			case 'time':
				echo '<input class="sppa-input" type="time" id="sppa-' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $def ) . '"' . ( $req ? ' required' : '' ) . ' />';
				break;
			case 'file':
				$multiple = absint( $field['max_files'] ?? 1 ) > 1;
				$exts     = array_filter( array_map( 'trim', explode( ',', strtolower( (string) ( $field['allowed_types'] ?? '' ) ) ) ) );
				$accept   = $exts ? ' accept=".' . esc_attr( implode( ',.', $exts ) ) . '"' : '';
				echo '<input class="sppa-input sppa-file" type="file" id="sppa-' . esc_attr( $id ) . '" name="sppa_' . esc_attr( $id ) . ( $multiple ? '[]' : '' ) . '"' . ( $multiple ? ' multiple' : '' ) . $accept . ( $req ? ' required' : '' ) . ' />'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $accept is built with esc_attr.
				echo '<div class="sppa-file-preview"></div>';
				break;
			case 'hidden':
				echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $def ) . '" />';
				break;
			case 'checkbox':
				echo '<label class="sppa-check"><input type="checkbox" name="' . esc_attr( $name ) . '" value="1" ' . checked( $def, '1', false ) . ( $req ? ' required' : '' ) . ' /> ' . esc_html( $label ? '' : __( 'Yes', 'optivo' ) ) . '</label>';
				break;
			case 'select':
			case 'multiselect':
				$this->render_select( $field, $name, $req );
				break;
			case 'radio':
			case 'image_radio':
			case 'color_swatch':
				$this->render_choices( $field, $name, $req, false );
				break;
			case 'checkbox_group':
			case 'image_checkbox':
				$this->render_choices( $field, $name, $req, true );
				break;
			case 'text':
			default:
				echo '<input class="sppa-input" type="text" id="sppa-' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $def ) . '" placeholder="' . esc_attr( $ph ) . '"' . ( $req ? ' required' : '' ) . ' />';
				break;
		}

		echo '</div>';
	}

	/**
	 * Select / multiselect.
	 *
	 * @param array  $field Field.
	 * @param string $name  Name.
	 * @param bool   $req   Required.
	 */
	protected function render_select( $field, $name, $req ) {
		$options = isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : array();
		$multi = 'multiselect' === $field['type'];
		echo '<select class="sppa-input" id="sppa-' . esc_attr( $field['id'] ) . '" name="' . esc_attr( $name ) . ( $multi ? '[]' : '' ) . '"' . ( $multi ? ' multiple' : '' ) . ( $req ? ' required' : '' ) . '>';
		if ( ! $multi ) {
			echo '<option value="">' . esc_html__( 'Choose an option', 'optivo' ) . '</option>';
		}
		foreach ( $options as $option ) {
			if ( $this->option_hidden( $field, $option ) ) {
				continue;
			}
			$disabled = $this->option_disabled( $field, $option );
			$hint     = $this->option_price_hint( $field, $option );
			echo '<option value="' . esc_attr( $option['id'] ) . '" ' . selected( ! empty( $option['default'] ), true, false ) . ( $disabled ? ' disabled' : '' ) . '>';
			echo esc_html( $option['label'] . $hint );
			echo '</option>';
		}
		echo '</select>';
	}

	/**
	 * Radio / checkbox / swatch / image choices.
	 *
	 * @param array  $field Field.
	 * @param string $name  Name.
	 * @param bool   $req   Required.
	 * @param bool   $multi Multi.
	 */
	protected function render_choices( $field, $name, $req, $multi ) {
		$type  = $field['type'];
		$input = $multi ? 'checkbox' : 'radio';
		$n     = $multi ? $name . '[]' : $name;
		$shape = $field['swatch_shape'] ?? 'circle';
		echo '<div class="sppa-choices sppa-choices-' . esc_attr( $type ) . ' sppa-shape-' . esc_attr( $shape ) . '">';
		$options = isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : array();
		foreach ( $options as $option ) {
			if ( $this->option_hidden( $field, $option ) ) {
				continue;
			}
			$disabled = $this->option_disabled( $field, $option );
			$hint     = $this->option_price_hint( $field, $option );
			$oid      = $option['id'];
			$img      = ! empty( $option['image'] ) ? wp_get_attachment_image_url( $option['image'], 'medium' ) : '';
			echo '<label class="sppa-choice' . ( $disabled ? ' is-disabled' : '' ) . '"' . ( ! empty( $option['tooltip'] ) ? ' title="' . esc_attr( $option['tooltip'] ) . '"' : '' ) . '>';
			echo '<input type="' . esc_attr( $input ) . '" name="' . esc_attr( $n ) . '" value="' . esc_attr( $oid ) . '" ' . checked( ! empty( $option['default'] ), true, false ) . ( $disabled ? ' disabled' : '' ) . ' />';
			if ( in_array( $type, array( 'image_radio', 'image_checkbox' ), true ) && $img ) {
				echo '<span class="sppa-choice-image"><img src="' . esc_url( $img ) . '" alt="' . esc_attr( $option['label'] ) . '" /></span>';
			}
			if ( 'color_swatch' === $type ) {
				$color = $option['color'] ?: '#ccc';
				echo '<span class="sppa-swatch" style="background:' . esc_attr( $color ) . '"></span>';
			}
			echo '<span class="sppa-choice-text">' . esc_html( $option['label'] . $hint ) . '</span>';
			if ( ! empty( $option['description'] ) ) {
				echo '<span class="sppa-choice-desc">' . esc_html( $option['description'] ) . '</span>';
			}
			echo '</label>';
		}
		echo '</div>';
	}

	/**
	 * Hide OOS option.
	 *
	 * @param array $field  Field.
	 * @param array $option Option.
	 * @return bool
	 */
	protected function option_hidden( $field, $option ) {
		if ( empty( $field['hide_out_of_stock'] ) ) {
			return false;
		}
		return isset( $option['stock'] ) && '' !== $option['stock'] && (int) $option['stock'] <= 0;
	}

	/**
	 * Disable OOS option.
	 *
	 * @param array $field  Field.
	 * @param array $option Option.
	 * @return bool
	 */
	protected function option_disabled( $field, $option ) {
		if ( empty( $field['stock_enabled'] ) ) {
			return false;
		}
		return isset( $option['stock'] ) && '' !== $option['stock'] && (int) $option['stock'] <= 0;
	}

	/**
	 * Option price suffix.
	 *
	 * @param array $field  Field.
	 * @param array $option Option.
	 * @return string
	 */
	protected function option_price_hint( $field, $option ) {
		if ( ! empty( $field['hide_price'] ) ) {
			return '';
		}
		if ( empty( $option['price'] ) || 'none' === ( $option['price_type'] ?? 'flat' ) ) {
			return '';
		}
		return ' ' . wp_strip_all_tags( $this->price_hint( $option['price_type'], $option['price'] ) );
	}

	/**
	 * Human price hint.
	 *
	 * @param string $type  Type.
	 * @param mixed  $price Price.
	 * @return string
	 */
	protected function price_hint( $type, $price ) {
		$n      = (float) $price;
		$symbol = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '$';
		$money  = function_exists( 'wc_price' ) ? html_entity_decode( wp_strip_all_tags( wc_price( abs( $n ) ) ), ENT_QUOTES, 'UTF-8' ) : ( $symbol . number_format( abs( $n ), 2 ) );
		if ( false === strpos( $money, $symbol ) && '$' !== $symbol ) {
			$money = $symbol . number_format( abs( $n ), 2 );
		}
		if ( false === strpos( $money, $symbol ) && false === strpos( $money, '$' ) ) {
			$money = '$' . number_format( abs( $n ), 2 );
		}
		switch ( $type ) {
			case 'percentage':
				$sign = $n >= 0 ? '+' : '';
				return $sign . $n . '%';
			case 'quantity':
				return $money . ' ×';
			case 'character':
				return $money . '/' . __( 'char', 'optivo' );
			case 'negative':
				return '−' . $money;
			default:
				return ( $n >= 0 ? '+' : '−' ) . $money;
		}
	}
}
