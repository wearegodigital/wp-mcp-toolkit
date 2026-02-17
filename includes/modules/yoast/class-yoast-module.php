<?php
/**
 * Yoast SEO Module
 *
 * @package WP_MCP_Toolkit
 */

defined( 'ABSPATH' ) || exit();

/**
 * Class WP_MCP_Toolkit_Yoast_Module
 *
 * Handles Yoast SEO integration for WP MCP Toolkit.
 */
class WP_MCP_Toolkit_Yoast_Module {
	/**
	 * Check if Yoast SEO is active
	 *
	 * @return bool
	 */
	public static function is_active(): bool {
		return defined( 'WPSEO_VERSION' );
	}

	/**
	 * Initialize the Yoast module
	 *
	 * @return void
	 */
	public static function init(): void {
		if ( ! self::is_active() ) {
			return;
		}

		$disabled = get_option( 'wpmcp_disabled_abilities', array() );

		require_once __DIR__ . '/class-yoast-abilities.php';
		( new WP_MCP_Toolkit_Yoast_Abilities() )->register( $disabled );
	}
}
