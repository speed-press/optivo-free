<?php
/**
 * One field editor.
 *
 * @package AdvanceProductAddons
 */

defined( 'ABSPATH' ) || exit;

// Template variables are local to the including method, not globals.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$types       = isset( $types ) ? $types : SPPA_Field_Types::all();
$price_types = isset( $price_types ) ? $price_types : array();
$fid         = $field['id'] ?? ( 'fld_' . $fi );
$prefix      = $name . '[' . $gi . '][fields][' . $fi . ']';
$has_opts    = SPPA_Field_Types::has_options( $field['type'] ?? 'text' );
$cond        = $field['conditions'] ?? array(
	'enabled' => false,
	'match'   => 'all',
	'action'  => 'show',
	'rules'   => array(),
);
?>
<div class="sppa-field" data-index="<?php echo esc_attr( $fi ); ?>" data-type="<?php echo esc_attr( $field['type'] ?? 'text' ); ?>">
	<div class="sppa-field-header">
		<span class="sppa-handle dashicons dashicons-menu"></span>
		<input type="hidden" name="<?php echo esc_attr( $prefix ); ?>[id]" value="<?php echo esc_attr( $fid ); ?>" class="sppa-field-id" />
		<div class="sppa-type-picker">
			<select name="<?php echo esc_attr( $prefix ); ?>[type]" class="sppa-field-type">
				<?php foreach ( $types as $type_key => $type_def ) : ?>
					<option value="<?php echo esc_attr( $type_key ); ?>" <?php selected( ( $field['type'] ?? 'text' ), $type_key ); ?>><?php echo esc_html( $type_def['label'] ); ?></option>
				<?php endforeach; ?>
			</select>
			<button type="button" class="sppa-type-trigger">
				<span class="dashicons dashicons-plus-alt"></span>
				<span class="sppa-type-trigger-label"><?php echo esc_html( $types[ $field['type'] ?? 'text' ]['label'] ?? __( 'Text', 'optivo' ) ); ?></span>
				<span class="dashicons dashicons-arrow-down-alt2"></span>
			</button>
			<div class="sppa-type-panel" hidden>
				<?php foreach ( $types as $type_key => $type_def ) : ?>
					<button type="button" class="sppa-type-card<?php echo ( $field['type'] ?? 'text' ) === $type_key ? ' is-active' : ''; ?>" data-value="<?php echo esc_attr( $type_key ); ?>">
						<span class="sppa-type-card-icon dashicons dashicons-<?php echo esc_attr( 'heading' === $type_key ? 'heading' : ( 'checkbox' === $type_key ? 'yes' : ( 'radio' === $type_key ? 'marker' : ( 'select' === $type_key ? 'arrow-down-alt2' : ( 'email' === $type_key ? 'email' : ( 'number' === $type_key ? 'calculator' : ( 'textarea' === $type_key || 'paragraph' === $type_key ? 'editor-paragraph' : 'editor-textcolor' ) ) ) ) ) ) ); ?>"></span>
						<span><?php echo esc_html( $type_def['label'] ); ?></span>
					</button>
				<?php endforeach; ?>
			</div>
		</div>
		<input type="text" class="sppa-field-label" name="<?php echo esc_attr( $prefix ); ?>[label]" value="<?php echo esc_attr( $field['label'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Field label', 'optivo' ); ?>" />
		<label class="sppa-switch" title="<?php esc_attr_e( 'Required field', 'optivo' ); ?>">
			<input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[required]" value="1" <?php checked( ! empty( $field['required'] ) ); ?> />
			<span class="sppa-switch-ui"></span>
			<span class="sppa-switch-label"><?php esc_html_e( 'Required', 'optivo' ); ?></span>
		</label>
		<code class="sppa-field-id-display"><?php echo esc_html( $fid ); ?></code>
		<button type="button" class="button-link sppa-toggle-field"><span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e( 'Settings', 'optivo' ); ?></button>
		<button type="button" class="sppa-icon-btn sppa-remove-field" title="<?php esc_attr_e( 'Remove field', 'optivo' ); ?>"><span class="dashicons dashicons-trash"></span></button>
	</div>
	<div class="sppa-field-settings" hidden>
		<div class="sppa-grid">
			<p class="sppa-set" data-for="text,textarea,number,email,phone,url,select,radio,checkbox,checkbox_group,multiselect,image_radio,image_checkbox,color,color_swatch,file,date,time,quantity,customer_price,heading,paragraph,hidden">
				<label><?php esc_html_e( 'Description', 'optivo' ); ?></label>
				<textarea class="widefat" rows="2" name="<?php echo esc_attr( $prefix ); ?>[description]"><?php echo esc_textarea( $field['description'] ?? '' ); ?></textarea>
			</p>
			<p class="sppa-set" data-for="text,textarea,number,email,phone,url,quantity,customer_price,hidden,color">
				<label><?php esc_html_e( 'Placeholder / default', 'optivo' ); ?></label>
				<input type="text" class="widefat" name="<?php echo esc_attr( $prefix ); ?>[placeholder]" value="<?php echo esc_attr( $field['placeholder'] ?? '' ); ?>" />
				<input type="text" class="widefat" name="<?php echo esc_attr( $prefix ); ?>[default]" value="<?php echo esc_attr( is_array( $field['default'] ?? '' ) ? implode( ',', $field['default'] ) : ( $field['default'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'Default value', 'optivo' ); ?>" />
			</p>
			<p class="sppa-set" data-for="text,textarea,number,email,phone,url,select,radio,checkbox,checkbox_group,multiselect,image_radio,image_checkbox,color,color_swatch,file,date,time,quantity,customer_price,hidden">
				<label><?php esc_html_e( 'Price type', 'optivo' ); ?></label>
				<select name="<?php echo esc_attr( $prefix ); ?>[price_type]" class="widefat">
					<?php foreach ( $price_types as $pk => $pl ) : ?>
						<option value="<?php echo esc_attr( $pk ); ?>" <?php selected( ( $field['price_type'] ?? 'none' ), $pk ); ?>><?php echo esc_html( $pl ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<p class="sppa-set" data-for="text,textarea,number,email,phone,url,select,radio,checkbox,checkbox_group,multiselect,image_radio,image_checkbox,color,color_swatch,file,date,time,quantity,customer_price,hidden">
				<label><?php esc_html_e( 'Price', 'optivo' ); ?></label>
				<span class="sppa-money">
					<span class="dashicons dashicons-money-alt" aria-hidden="true"></span>
					<input type="text" class="widefat" name="<?php echo esc_attr( $prefix ); ?>[price]" value="<?php echo esc_attr( $field['price'] ?? '' ); ?>" placeholder="0.00" />
				</span>
			</p>
			<p class="sppa-set" data-for="text,textarea,number,quantity,customer_price">
				<label><?php esc_html_e( 'Min / Max', 'optivo' ); ?></label>
				<input type="text" name="<?php echo esc_attr( $prefix ); ?>[min]" value="<?php echo esc_attr( $field['min'] ?? '' ); ?>" placeholder="min" style="width:48%" />
				<input type="text" name="<?php echo esc_attr( $prefix ); ?>[max]" value="<?php echo esc_attr( $field['max'] ?? '' ); ?>" placeholder="max" style="width:48%" />
			</p>
			<p class="sppa-set" data-for="text,textarea">
				<label><?php esc_html_e( 'Character pricing (optional)', 'optivo' ); ?></label>
				<input type="number" name="<?php echo esc_attr( $prefix ); ?>[char_base_count]" value="<?php echo esc_attr( $field['char_base_count'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'First N chars', 'optivo' ); ?>" style="width:32%" />
				<input type="text" name="<?php echo esc_attr( $prefix ); ?>[char_base_price]" value="<?php echo esc_attr( $field['char_base_price'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Base price', 'optivo' ); ?>" style="width:32%" />
				<input type="text" name="<?php echo esc_attr( $prefix ); ?>[char_extra_price]" value="<?php echo esc_attr( $field['char_extra_price'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Each extra', 'optivo' ); ?>" style="width:32%" />
			</p>
			<p class="sppa-set" data-for="text,textarea">
				<label><?php esc_html_e( 'Allowed characters / regex', 'optivo' ); ?></label>
				<select name="<?php echo esc_attr( $prefix ); ?>[allowed_chars]" class="widefat">
					<option value=""><?php esc_html_e( 'Any', 'optivo' ); ?></option>
					<option value="letters" <?php selected( ( $field['allowed_chars'] ?? '' ), 'letters' ); ?>><?php esc_html_e( 'Letters only', 'optivo' ); ?></option>
					<option value="numbers" <?php selected( ( $field['allowed_chars'] ?? '' ), 'numbers' ); ?>><?php esc_html_e( 'Numbers only', 'optivo' ); ?></option>
					<option value="alphanumeric" <?php selected( ( $field['allowed_chars'] ?? '' ), 'alphanumeric' ); ?>><?php esc_html_e( 'Letters + numbers', 'optivo' ); ?></option>
				</select>
				<input type="text" class="widefat" name="<?php echo esc_attr( $prefix ); ?>[regex]" value="<?php echo esc_attr( $field['regex'] ?? '' ); ?>" placeholder="^[A-Z]{3}-[0-9]{4}$" />
			</p>
			<p class="sppa-set" data-for="select,radio,checkbox_group,multiselect,image_radio,image_checkbox,color_swatch">
				<label><?php esc_html_e( 'Display style / swatch shape', 'optivo' ); ?></label>
				<select name="<?php echo esc_attr( $prefix ); ?>[display_style]">
					<option value="default" <?php selected( ( $field['display_style'] ?? '' ), 'default' ); ?>><?php esc_html_e( 'Default', 'optivo' ); ?></option>
					<option value="cards" <?php selected( ( $field['display_style'] ?? '' ), 'cards' ); ?>><?php esc_html_e( 'Cards', 'optivo' ); ?></option>
					<option value="pills" <?php selected( ( $field['display_style'] ?? '' ), 'pills' ); ?>><?php esc_html_e( 'Pills', 'optivo' ); ?></option>
					<option value="swatches" <?php selected( ( $field['display_style'] ?? '' ), 'swatches' ); ?>><?php esc_html_e( 'Swatches', 'optivo' ); ?></option>
				</select>
				<select name="<?php echo esc_attr( $prefix ); ?>[swatch_shape]">
					<option value="circle" <?php selected( ( $field['swatch_shape'] ?? '' ), 'circle' ); ?>><?php esc_html_e( 'Circle', 'optivo' ); ?></option>
					<option value="square" <?php selected( ( $field['swatch_shape'] ?? '' ), 'square' ); ?>><?php esc_html_e( 'Square', 'optivo' ); ?></option>
					<option value="rounded" <?php selected( ( $field['swatch_shape'] ?? '' ), 'rounded' ); ?>><?php esc_html_e( 'Rounded', 'optivo' ); ?></option>
				</select>
			</p>
			<p class="sppa-set" data-for="file">
				<label><?php esc_html_e( 'File upload', 'optivo' ); ?></label>
				<input type="number" name="<?php echo esc_attr( $prefix ); ?>[max_files]" value="<?php echo esc_attr( $field['max_files'] ?? 1 ); ?>" min="1" style="width:30%" /> <?php esc_html_e( 'max files', 'optivo' ); ?>
				<input type="number" name="<?php echo esc_attr( $prefix ); ?>[max_file_mb]" value="<?php echo esc_attr( $field['max_file_mb'] ?? 10 ); ?>" min="1" style="width:30%" /> MB
				<input type="text" class="widefat" name="<?php echo esc_attr( $prefix ); ?>[allowed_types]" value="<?php echo esc_attr( $field['allowed_types'] ?? 'jpg,jpeg,png,pdf' ); ?>" />
			</p>
			<p class="sppa-set" data-for="date,time">
				<label><?php esc_html_e( 'Date restrictions', 'optivo' ); ?></label>
				<input type="number" name="<?php echo esc_attr( $prefix ); ?>[min_days]" value="<?php echo esc_attr( $field['min_days'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Min days ahead', 'optivo' ); ?>" style="width:48%" />
				<input type="number" name="<?php echo esc_attr( $prefix ); ?>[max_days]" value="<?php echo esc_attr( $field['max_days'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Max days ahead', 'optivo' ); ?>" style="width:48%" />
				<textarea class="widefat" rows="2" name="<?php echo esc_attr( $prefix ); ?>[blocked_dates]" placeholder="<?php esc_attr_e( 'Blocked dates YYYY-MM-DD, one per line', 'optivo' ); ?>"><?php echo esc_textarea( $field['blocked_dates'] ?? '' ); ?></textarea>
			</p>
			<p class="sppa-set" data-for="paragraph">
				<label><?php esc_html_e( 'HTML (paragraph field)', 'optivo' ); ?></label>
				<textarea class="widefat" rows="3" name="<?php echo esc_attr( $prefix ); ?>[html]"><?php echo esc_textarea( $field['html'] ?? '' ); ?></textarea>
			</p>
			<p class="sppa-set" data-for="text,textarea,number,email,phone,url,select,radio,checkbox,checkbox_group,multiselect,image_radio,image_checkbox,color,color_swatch,file,date,time,quantity,customer_price">
				<label><?php esc_html_e( 'Custom required message', 'optivo' ); ?></label>
				<input type="text" class="widefat" name="<?php echo esc_attr( $prefix ); ?>[required_message]" value="<?php echo esc_attr( $field['required_message'] ?? '' ); ?>" />
			</p>
			<p class="sppa-set" data-for="text,textarea,number,email,phone,url,select,radio,checkbox,checkbox_group,multiselect,image_radio,image_checkbox,color,color_swatch,file,date,time,quantity,customer_price">
				<label><input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[hide_price]" value="1" <?php checked( ! empty( $field['hide_price'] ) ); ?> /> <?php esc_html_e( 'Hide extra price on the product page (label, options, and summary). Cart still charges it.', 'optivo' ); ?></label><br />
				<label><input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[hide_in_cart]" value="1" <?php checked( ! empty( $field['hide_in_cart'] ) ); ?> /> <?php esc_html_e( 'Hide this field in cart', 'optivo' ); ?></label>
			</p>
			<p class="sppa-set" data-for="select,radio,checkbox_group,multiselect,image_radio,image_checkbox,color_swatch">
				<label><input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[stock_enabled]" value="1" <?php checked( ! empty( $field['stock_enabled'] ) ); ?> /> <?php esc_html_e( 'Track option stock', 'optivo' ); ?></label>
				<label><input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[hide_out_of_stock]" value="1" <?php checked( ! empty( $field['hide_out_of_stock'] ) ); ?> /> <?php esc_html_e( 'Hide out-of-stock options', 'optivo' ); ?></label>
			</p>
		</div>

		<div class="sppa-options-wrap" <?php echo $has_opts ? '' : 'hidden'; ?>>
			<h4><?php esc_html_e( 'Choices', 'optivo' ); ?></h4>
			<div class="sppa-options">
				<?php
				if ( ! empty( $field['options'] ) ) {
					foreach ( $field['options'] as $oi => $option ) {
						include SPPA_PATH . 'admin/views/option.php';
					}
				}
				?>
			</div>
			<p><button type="button" class="button sppa-add-option"><?php esc_html_e( '+ Add choice', 'optivo' ); ?></button></p>
		</div>
	</div>
</div>
