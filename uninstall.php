<?php
/**
 * Uninstall cleanup.
 *
 * @package AdvanceProductAddons
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'sppa_settings' );
delete_option( 'sppa_license' );
wp_clear_scheduled_hook( 'sppa_license_check' );

delete_metadata( 'post', 0, '_sppa_addon_groups', '', true );
delete_metadata( 'post', 0, '_sppa_exclude_global_addons', '', true );

$posts = get_posts(
	array(
		'post_type'      => 'sppa_addon_group',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);

foreach ( $posts as $id ) {
	wp_delete_post( $id, true );
}
