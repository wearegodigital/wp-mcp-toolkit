<?php
/**
 * WP MCP Toolkit — Gravity Forms Module bootstrap.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();

class WP_MCP_Toolkit_GF_Module {

	public static function is_active(): bool {
		return class_exists( 'GFAPI' );
	}

	public static function init( array $disabled = array() ): void {
		if ( ! self::is_active() ) {
			return;
		}

		require_once __DIR__ . '/class-gf-abilities.php';
		( new WP_MCP_Toolkit_GF_Abilities() )->register( $disabled );
	}
}
