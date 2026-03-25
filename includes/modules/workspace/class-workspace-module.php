<?php
/**
 * Workspace module bootstrap — loads ability classes when active.
 *
 * @package WP_MCP_Toolkit
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

class WP_MCP_Toolkit_Workspace_Module {

	public static function is_active(): bool {
		return 'disabled' !== get_option( 'wpmcp_workspace_mode', 'auto' );
	}

	public static function init( array $disabled = array() ): void {
		if ( ! self::is_active() ) {
			return;
		}

		// Load all workspace infrastructure classes.
		foreach ( glob( __DIR__ . '/class-workspace-*.php' ) as $file ) {
			require_once $file;
		}

		( new WP_MCP_Toolkit_Workspace_Abilities() )->register( $disabled );
		( new WP_MCP_Toolkit_Workspace_Blocks_Abilities() )->register( $disabled );
	}

	public static function activate(): true|\WP_Error {
		$result = WP_MCP_Toolkit_Workspace_Container::initialize_workspace();
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		update_option( 'wpmcp_workspace_mode', 'staging' );
		return true;
	}

	public static function deactivate(): void {
		WP_MCP_Toolkit_Workspace_MU_Loader::uninstall();
	}
}
