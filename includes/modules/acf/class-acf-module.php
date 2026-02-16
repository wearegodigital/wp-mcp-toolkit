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

	public static function get_version(): string {
		return defined( 'ACF_VERSION' ) ? ACF_VERSION : '';
	}

	public static function init(): void {
		if ( ! self::is_active() ) {
			return;
		}

		require_once __DIR__ . '/class-acf-field-abilities.php';
		$fields = new WP_MCP_Toolkit_ACF_Field_Abilities();
		$fields->register();

		require_once __DIR__ . '/class-acf-block-abilities.php';
		$blocks = new WP_MCP_Toolkit_ACF_Block_Abilities();
		$blocks->register();
	}
}
