<?php
/**
 * One choice option.
 *
 * @package AdvanceProductAddons
 */

defined( 'ABSPATH' ) || exit;

// Template variables are local to the including method, not globals.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$oid     = $option['id'] ?? ( 'opt_' . $oi );
$oprefix = $name . '[' . $gi . '][fields][' . $fi . '][options][' . $oi . ']';
$img_id  = absint( $option['image'] ?? 0 );
$thumb   = $img_id ? wp_get_attachment_image_url( $img_id, 'thumbnail' ) : '';
?>
<div class="sppa-option" data-index="<?php echo esc_attr( $oi ); ?>">
	<span class="sppa-handle dashicons dashicons-menu"></span>
	<input type="hidden" name="<?php echo esc_attr( $oprefix ); ?>[id]" value="<?php echo esc_attr( $oid ); ?>" />
	<input type="text" name="<?php echo esc_attr( $oprefix ); ?>[label]" value="<?php echo esc_attr( $option['label'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Label', 'optivo' ); ?>" />
	<select name="<?php echo esc_attr( $oprefix ); ?>[price_type]">
		<option value="flat" <?php selected( ( $option['price_type'] ?? 'flat' ), 'flat' ); ?>><?php esc_html_e( 'Fixed', 'optivo' ); ?></option>
		<option value="percentage" <?php selected( ( $option['price_type'] ?? '' ), 'percentage' ); ?>><?php esc_html_e( '%', 'optivo' ); ?></option>
		<option value="quantity" <?php selected( ( $option['price_type'] ?? '' ), 'quantity' ); ?>><?php esc_html_e( '× qty', 'optivo' ); ?></option>
		<option value="none" <?php selected( ( $option['price_type'] ?? '' ), 'none' ); ?>><?php esc_html_e( 'Free', 'optivo' ); ?></option>
		<option value="negative" <?php selected( ( $option['price_type'] ?? '' ), 'negative' ); ?>><?php esc_html_e( 'Discount', 'optivo' ); ?></option>
	</select>
	<span class="sppa-money">
		<span class="dashicons dashicons-money-alt" aria-hidden="true"></span>
		<input type="text" name="<?php echo esc_attr( $oprefix ); ?>[price]" value="<?php echo esc_attr( $option['price'] ?? '' ); ?>" placeholder="0.00" />
	</span>
	<input type="text" name="<?php echo esc_attr( $oprefix ); ?>[color]" value="<?php echo esc_attr( $option['color'] ?? '' ); ?>" placeholder="#000000" style="width:90px" />
	<input type="number" name="<?php echo esc_attr( $oprefix ); ?>[stock]" value="<?php echo esc_attr( $option['stock'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Stock', 'optivo' ); ?>" style="width:70px" />
	<input type="hidden" class="sppa-option-image" name="<?php echo esc_attr( $oprefix ); ?>[image]" value="<?php echo esc_attr( $img_id ); ?>" />
	<button type="button" class="button sppa-pick-image"><?php echo $thumb ? esc_html__( 'Change image', 'optivo' ) : esc_html__( 'Image', 'optivo' ); ?></button>
	<?php if ( $thumb ) : ?>
		<img src="<?php echo esc_url( $thumb ); ?>" class="sppa-opt-thumb" alt="" />
	<?php endif; ?>
	<label><input type="checkbox" name="<?php echo esc_attr( $oprefix ); ?>[default]" value="1" <?php checked( ! empty( $option['default'] ) ); ?> /> <?php esc_html_e( 'Default', 'optivo' ); ?></label>
	<button type="button" class="button-link-delete sppa-remove-option">&times;</button>
</div>
