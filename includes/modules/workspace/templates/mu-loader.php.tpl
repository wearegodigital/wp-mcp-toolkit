<?php
/**
 * Plugin Name: WP MCP Workspace Loader
 * Description: MU-plugin that loads the WP MCP Workspace plugin with crash recovery.
 * Version:     1.0.0
 * Requires PHP: 8.0
 *
 * Auto-installed by WP MCP Toolkit. Do not edit manually.
 *
 * @package WP_MCP_Workspace
 */

defined( 'ABSPATH' ) || exit;

$wpmcp_workspace_plugin = '{{WORKSPACE_PLUGIN_PATH}}';

// Nothing to load if the workspace plugin doesn't exist.
if ( ! file_exists( $wpmcp_workspace_plugin ) ) {
	return;
}

$wpmcp_workspace_dir = dirname( $wpmcp_workspace_plugin ) . '/';
$wpmcp_loading_file  = $wpmcp_workspace_dir . '.loading';
$wpmcp_crashed_file  = $wpmcp_workspace_dir . '.crashed';

// Detect crash: .loading persisting from previous request means a fatal occurred.
if ( file_exists( $wpmcp_loading_file ) ) {
	@rename( $wpmcp_loading_file, $wpmcp_crashed_file );
}

// In crashed state: skip loading, let the admin UI handle recovery.
if ( file_exists( $wpmcp_crashed_file ) ) {
	add_action( 'admin_notices', function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p>';
		echo esc_html__( 'WP MCP Workspace crashed on last load. It has been disabled to prevent further issues. ', 'wp-mcp-toolkit' );
		echo '<a href="' . esc_url( admin_url( 'tools.php?page=wp-mcp-toolkit&tab=workspace' ) ) . '">';
		echo esc_html__( 'Manage Workspace', 'wp-mcp-toolkit' );
		echo '</a></p></div>';
	} );

	do_action( 'wpmcp_workspace_crash_detected' );
	return;
}

// Normal load: create .loading marker, require workspace, remove marker.
file_put_contents( $wpmcp_loading_file, gmdate( 'c' ), LOCK_EX );
require_once $wpmcp_workspace_plugin;
@unlink( $wpmcp_loading_file );
