<?php
/**
 * Plugin Name: WP MCP Workspace
 * Description: AI-generated workspace plugin managed by WP MCP Toolkit. Contains functions, classes, hooks, blocks, and Bricks elements created via MCP abilities.
 * Version:     1.0.0
 * Requires PHP: 8.0
 *
 * This file is auto-generated. Do not edit manually — changes will be overwritten.
 *
 * @package WP_MCP_Workspace
 */

defined( 'ABSPATH' ) || exit;

define( 'WPMCP_WORKSPACE_DIR', {{WORKSPACE_DIR}} );
define( 'WPMCP_WORKSPACE_VERSION', '{{WORKSPACE_VERSION}}' );

// Load generated functions.
foreach ( glob( WPMCP_WORKSPACE_DIR . 'functions/*.php' ) as $file ) {
	require_once $file;
}

// Load generated classes.
foreach ( glob( WPMCP_WORKSPACE_DIR . 'classes/*.php' ) as $file ) {
	require_once $file;
}

// Load generated hook registrations.
foreach ( glob( WPMCP_WORKSPACE_DIR . 'hooks/*.php' ) as $file ) {
	require_once $file;
}

// Register generated Gutenberg blocks.
add_action( 'init', function () {
	foreach ( glob( WPMCP_WORKSPACE_DIR . 'blocks/*/block.json' ) as $block_json ) {
		register_block_type( dirname( $block_json ) );
	}
} );

// Register generated Bricks elements — guarded to prevent fatal if Bricks is deactivated.
if ( class_exists( '\Bricks\Elements' ) ) {
	add_action( 'init', function () {
		foreach ( glob( WPMCP_WORKSPACE_DIR . 'bricks/*/element.php' ) as $element_file ) {
			require_once $element_file;
		}
	}, 11 );
}
