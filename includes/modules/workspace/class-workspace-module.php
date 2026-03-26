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

	/**
	 * Load trait and all workspace infrastructure classes.
	 *
	 * Centralised so that init(), activate(), deactivate(), and the
	 * Bricks addon can all bootstrap from one place.
	 */
	public static function load_classes(): void {
		require_once __DIR__ . '/trait-workspace-helpers.php';
		foreach ( glob( __DIR__ . '/class-workspace-*.php' ) as $file ) {
			require_once $file;
		}
	}

	public static function init( array $disabled = array() ): void {
		if ( ! self::is_active() ) {
			return;
		}

		self::load_classes();

		( new WP_MCP_Toolkit_Workspace_Abilities() )->register( $disabled );
		( new WP_MCP_Toolkit_Workspace_Blocks_Abilities() )->register( $disabled );
		( new WP_MCP_Toolkit_Workspace_Block_Insertion_Abilities() )->register( $disabled );

		if ( class_exists( 'ACF' ) ) {
			( new WP_MCP_Toolkit_Workspace_ACF_Blocks_Abilities() )->register( $disabled );
		}

		( new WP_MCP_Toolkit_Workspace_Smart_Block_Ability() )->register( $disabled );
	}

	public static function activate(): true|\WP_Error {
		self::load_classes();
		$result = WP_MCP_Toolkit_Workspace_Container::initialize_workspace();
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		update_option( 'wpmcp_workspace_mode', 'staging' );
		return true;
	}

	public static function deactivate(): void {
		self::load_classes();
		WP_MCP_Toolkit_Workspace_MU_Loader::uninstall();
	}
}
