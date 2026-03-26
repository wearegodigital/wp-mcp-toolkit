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

// Load ACF block registrations and field groups.
foreach ( glob( WPMCP_WORKSPACE_DIR . 'blocks/*/register.php' ) as $file ) {
	require_once $file;
}
foreach ( glob( WPMCP_WORKSPACE_DIR . 'blocks/*/fields.php' ) as $file ) {
	require_once $file;
}

// Register generated Gutenberg blocks.
add_action( 'init', function () {
	foreach ( glob( WPMCP_WORKSPACE_DIR . 'blocks/*/block.json' ) as $block_json ) {
		register_block_type( dirname( $block_json ) );
	}
} );

// Register generated Bricks elements — deferred to after_setup_theme so Bricks is loaded.
add_action( 'after_setup_theme', function () {
	if ( ! class_exists( '\Bricks\Elements' ) || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
		return;
	}
	add_action( 'init', function () {
		foreach ( glob( WPMCP_WORKSPACE_DIR . 'bricks/*/element.php' ) as $element_file ) {
			try {
				\Bricks\Elements::register_element( $element_file );
			} catch ( \Error $e ) {
				// Bricks internal error (e.g. WooCommerce element incompatibility) — skip gracefully.
			}
		}
	}, 11 );
}, 20 );
