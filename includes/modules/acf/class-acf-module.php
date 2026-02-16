<?php
/**
 * WP MCP Toolkit — ACF Module bootstrap.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();

class WP_MCP_Toolkit_ACF_Module {

	public static function is_active(): bool {
		return class_exists( 'ACF' );
	}

	public static function init(): void {
		if ( ! self::is_active() ) {
			return;
		}

		$disabled = get_option( 'wpmcp_disabled_abilities', array() );

		require_once __DIR__ . '/class-acf-field-abilities.php';
		( new WP_MCP_Toolkit_ACF_Field_Abilities() )->register( $disabled );

		require_once __DIR__ . '/class-acf-block-abilities.php';
		( new WP_MCP_Toolkit_ACF_Block_Abilities() )->register( $disabled );
	}
}
