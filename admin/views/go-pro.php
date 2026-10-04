<?php
/**
 * Free vs Premium comparison.
 *
 * @package AdvanceProductAddons
 */

defined( 'ABSPATH' ) || exit;

// Template variables are local to this view.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$sppa_upgrade = 'https://wpspeedpress.com';

$sppa_rows = array(
	array(
		'group' => __( 'Fields included now', 'optivo' ),
		'items' => array(
			array( __( 'Text, textarea, number, and email', 'optivo' ), true, true ),
			array( __( 'Dropdown, radio, and checkbox', 'optivo' ), true, true ),
			array( __( 'Heading and paragraph', 'optivo' ), true, true ),
			array( __( 'Required fields and custom messages', 'optivo' ), true, true ),
		),
	),
	array(
		'group' => __( 'Pricing and storefront', 'optivo' ),
		'items' => array(
			array( __( 'Fixed, percentage, and quantity prices', 'optivo' ), true, true ),
			array( __( 'Live price and configuration summary', 'optivo' ), true, true ),
			array( __( 'Cart, checkout, order, and email display', 'optivo' ), true, true ),
			array( __( 'Per-product and global option groups', 'optivo' ), true, true ),
			array( __( 'Import and export', 'optivo' ), true, true ),
		),
	),
	array(
		'group' => __( 'Premium plugin only', 'optivo' ),
		'items' => array(
			array( __( 'Phone, URL, hidden, quantity, and name-your-price', 'optivo' ), false, true ),
			array( __( 'Checkbox group and multi-select', 'optivo' ), false, true ),
			array( __( 'Image radio and image checkbox', 'optivo' ), false, true ),
			array( __( 'Color picker and color swatches', 'optivo' ), false, true ),
			array( __( 'File upload', 'optivo' ), false, true ),
			array( __( 'Date picker and time picker', 'optivo' ), false, true ),
			array( __( 'Conditional logic (show or hide, AND / OR)', 'optivo' ), false, true ),
			array( __( 'Extra layouts and styles', 'optivo' ), false, true ),
			array( __( 'Character, length, area, and weight pricing', 'optivo' ), false, true ),
		),
	),
);
?>
<div class="wrap sppa-gopro">
	<div class="sppa-gopro-hero">
		<div>
			<p class="sppa-gopro-kicker"><?php esc_html_e( 'Free vs Premium', 'optivo' ); ?></p>
			<h1><?php esc_html_e( 'Everything in this plugin is free. Premium is a separate plugin.', 'optivo' ); ?></h1>
			<p><?php esc_html_e( 'The fields you have now stay on, with no key and no limit. Upgrade only if you want the extra field types, conditional logic, and pricing rules.', 'optivo' ); ?></p>
		</div>
		<a class="sppa-gopro-btn" href="<?php echo esc_url( $sppa_upgrade ); ?>" target="_blank" rel="noopener noreferrer">
			<span class="dashicons dashicons-star-filled" aria-hidden="true"></span>
			<?php esc_html_e( 'Get Premium', 'optivo' ); ?>
		</a>
	</div>

	<div class="sppa-gopro-plans">
		<article class="sppa-gopro-plan">
			<p class="sppa-gopro-plan-name"><?php esc_html_e( 'Free', 'optivo' ); ?></p>
			<p class="sppa-gopro-price"><span>$0</span></p>
			<p class="sppa-gopro-plan-note"><?php esc_html_e( 'Installed on this site', 'optivo' ); ?></p>
			<ul>
				<li><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span><?php esc_html_e( 'Core fields and choices', 'optivo' ); ?></li>
				<li><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span><?php esc_html_e( 'Basic extra prices', 'optivo' ); ?></li>
				<li><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span><?php esc_html_e( 'Global and product groups', 'optivo' ); ?></li>
			</ul>
			<span class="sppa-gopro-current"><?php esc_html_e( 'Current plan', 'optivo' ); ?></span>
		</article>
		<article class="sppa-gopro-plan is-pro">
			<p class="sppa-gopro-badge"><?php esc_html_e( 'Recommended', 'optivo' ); ?></p>
			<p class="sppa-gopro-plan-name"><?php esc_html_e( 'Premium', 'optivo' ); ?></p>
			<p class="sppa-gopro-price"><span>$29</span><small><?php esc_html_e( '/ year · 1 site', 'optivo' ); ?></small></p>
			<p class="sppa-gopro-plan-note"><?php esc_html_e( 'Or $100 once, for the life of one site', 'optivo' ); ?></p>
			<ul>
				<li><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span><?php esc_html_e( 'Every free feature', 'optivo' ); ?></li>
				<li><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span><?php esc_html_e( 'Images, swatches, files, dates', 'optivo' ); ?></li>
				<li><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span><?php esc_html_e( 'Conditional logic and advanced prices', 'optivo' ); ?></li>
			</ul>
			<a class="sppa-gopro-btn" href="<?php echo esc_url( $sppa_upgrade ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Get Premium', 'optivo' ); ?></a>
		</article>
		<article class="sppa-gopro-plan">
			<p class="sppa-gopro-plan-name"><?php esc_html_e( 'Agency', 'optivo' ); ?></p>
			<p class="sppa-gopro-price"><span><?php esc_html_e( 'Custom', 'optivo' ); ?></span></p>
			<p class="sppa-gopro-plan-note"><?php esc_html_e( 'Priced from the yearly plan, per site', 'optivo' ); ?></p>
			<ul>
				<li><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span><?php esc_html_e( 'Same features as Premium', 'optivo' ); ?></li>
				<li><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span><?php esc_html_e( 'Several client sites on one order', 'optivo' ); ?></li>
			</ul>
			<a class="sppa-gopro-btn sppa-gopro-btn-ghost" href="<?php echo esc_url( $sppa_upgrade ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Ask about agency', 'optivo' ); ?></a>
		</article>
	</div>

	<div class="sppa-gopro-table-wrap">
		<table class="sppa-gopro-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Feature', 'optivo' ); ?></th>
					<th><?php esc_html_e( 'Free', 'optivo' ); ?></th>
					<th><?php esc_html_e( 'Premium', 'optivo' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $sppa_rows as $sppa_group ) : ?>
					<tr class="is-group">
						<td colspan="3"><?php echo esc_html( $sppa_group['group'] ); ?></td>
					</tr>
					<?php foreach ( $sppa_group['items'] as $sppa_item ) : ?>
						<tr>
							<td><?php echo esc_html( $sppa_item[0] ); ?></td>
							<td><?php echo $sppa_item[1] ? '<span class="sppa-gopro-yes" aria-label="' . esc_attr__( 'Included', 'optivo' ) . '"><span class="dashicons dashicons-yes"></span></span>' : '<span class="sppa-gopro-no" aria-label="' . esc_attr__( 'Not included', 'optivo' ) . '"><span class="dashicons dashicons-minus"></span></span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup is static and the label is escaped. ?></td>
							<td><?php echo $sppa_item[2] ? '<span class="sppa-gopro-yes is-pro" aria-label="' . esc_attr__( 'Included', 'optivo' ) . '"><span class="dashicons dashicons-yes"></span></span>' : '<span class="sppa-gopro-no"><span class="dashicons dashicons-minus"></span></span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup is static and the label is escaped. ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
