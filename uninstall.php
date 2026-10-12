<?php
/**
 * Remove saved settings only when the site administrator opted in.
 *
 * @package Multi_Agent_Live_Chat
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$option_name = 'wla_settings';

$delete_for_current_site = static function () use ( $option_name ) {
	$settings = get_option( $option_name, array() );
	if ( is_array( $settings ) && ! empty( $settings['delete_on_uninstall'] ) ) {
		delete_option( $option_name );
	}
};

if ( is_multisite() ) {
	$site_ids = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );
	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );
		$delete_for_current_site();
		restore_current_blog();
	}
} else {
	$delete_for_current_site();
}
