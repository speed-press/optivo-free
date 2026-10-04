<?php
/**
 * Shared field-group builder.
 *
 * Expects $groups and $name.
 *
 * @package AdvanceProductAddons
 */

defined( 'ABSPATH' ) || exit;

// Template variables are local to the including method, not globals.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$types      = SPPA_Field_Types::all();
$operators  = SPPA_Conditions::operators();
$price_types = array(
	'none'       => __( 'No extra price', 'optivo' ),
	'flat'       => __( 'Fixed', 'optivo' ),
	'percentage' => __( 'Percentage of product', 'optivo' ),
	'quantity'   => __( 'Quantity × price', 'optivo' ),
);
?>
<div class="sppa-builder" data-name="<?php echo esc_attr( $name ); ?>">
	<div class="sppa-builder-toolbar">
		<div class="sppa-builder-toolbar-copy">
			<h3><?php esc_html_e( 'Product options', 'optivo' ); ?></h3>
			<p><?php esc_html_e( 'Build groups, add fields, then set a price if you need one. Drag to reorder.', 'optivo' ); ?></p>
		</div>
		<button type="button" class="button button-primary sppa-add-group"><span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add option group', 'optivo' ); ?></button>
	</div>
	<div class="sppa-modal" hidden>
		<div class="sppa-modal-backdrop"></div>
		<div class="sppa-modal-card" role="dialog" aria-modal="true">
			<div class="sppa-modal-icon"><span class="dashicons dashicons-trash"></span></div>
			<h3 class="sppa-modal-title"><?php esc_html_e( 'Remove this field?', 'optivo' ); ?></h3>
			<p class="sppa-modal-text"><?php esc_html_e( 'The field and its options will be deleted from this group after you save.', 'optivo' ); ?></p>
			<div class="sppa-modal-actions">
				<button type="button" class="sppa-btn sppa-btn-ghost sppa-modal-cancel"><?php esc_html_e( 'Keep it', 'optivo' ); ?></button>
				<button type="button" class="sppa-btn sppa-btn-primary sppa-modal-ok"><?php esc_html_e( 'Remove', 'optivo' ); ?></button>
			</div>
		</div>
	</div>
	<div class="sppa-groups">
		<?php
		if ( ! empty( $groups ) ) {
			foreach ( $groups as $gi => $group ) {
				if ( empty( $group['fields'] ) && empty( $group['name'] ) && empty( $group['description'] ) ) {
					continue;
				}
				include SPPA_PATH . 'admin/views/group.php';
			}
		}
		?>
	</div>
</div>
<?php
// Templates used by admin.js.
?>
<template id="tmpl-sppa-group">
<?php
$gi    = '__GI__';
$group = array(
	'id'          => '__GID__',
	'name'        => '',
	'description' => '',
	'status'      => 'active',
	'priority'    => 10,
	'layout'      => 'vertical',
	'style'       => 'default',
	'fields'      => array(),
);
include SPPA_PATH . 'admin/views/group.php';
?>
</template>
<template id="tmpl-sppa-field">
<?php
$fi    = '__FI__';
$field = array(
	'id'               => '__FID__',
	'type'             => 'text',
	'label'            => '',
	'description'      => '',
	'placeholder'      => '',
	'required'         => false,
	'required_message' => '',
	'price_type'       => 'none',
	'price'            => '',
	'min'              => '',
	'max'              => '',
	'options'          => array(),
	'conditions'       => array(
		'enabled' => false,
		'match'   => 'all',
		'action'  => 'show',
		'rules'   => array(),
	),
);
include SPPA_PATH . 'admin/views/field.php';
?>
</template>
<template id="tmpl-sppa-option">
<?php
$oi     = '__OI__';
$option = array(
	'id'         => '__OID__',
	'label'      => '',
	'price_type' => 'flat',
	'price'      => '',
	'image'      => 0,
	'color'      => '',
	'default'    => false,
	'stock'      => '',
	'tooltip'    => '',
);
include SPPA_PATH . 'admin/views/option.php';
?>
</template>
<template id="tmpl-sppa-rule">
<?php
$ri   = '__RI__';
$rule = array(
	'field'    => '',
	'operator' => 'is',
	'value'    => '',
);
include SPPA_PATH . 'admin/views/rule.php';
?>
</template>
