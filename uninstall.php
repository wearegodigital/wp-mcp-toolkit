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

// Clean up template options.
global $wpdb;
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		'wpmcp_template_%'
	)
);
