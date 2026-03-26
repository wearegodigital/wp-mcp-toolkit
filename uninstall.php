<?php
/**
 * WP MCP Toolkit — Uninstall.
 *
 * Removes plugin options on deletion.
 *
 * @package wp-mcp-toolkit
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit();

delete_option( 'wpmcp_disabled_abilities' );
delete_option( 'wpmcp_licenses' );
delete_option( 'wpmcp_disabled_addons' );

// Clean up workspace options.
delete_option( 'wpmcp_workspace_mode' );
delete_option( 'wpmcp_workspace_allowlist' );

// Clean up block options.
delete_option( 'wpmcp_block_method' );

// Remove MU-plugin loader (but leave workspace artifacts for safety).
if ( defined( 'WPMU_PLUGIN_DIR' ) ) {
	$mu_loader = WPMU_PLUGIN_DIR . '/wpmcp-workspace-loader.php';
	if ( file_exists( $mu_loader ) ) {
		@unlink( $mu_loader ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}
}

// Clean up template options.
global $wpdb;
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		'wpmcp_template_%'
	)
);
