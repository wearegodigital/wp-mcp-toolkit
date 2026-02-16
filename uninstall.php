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
