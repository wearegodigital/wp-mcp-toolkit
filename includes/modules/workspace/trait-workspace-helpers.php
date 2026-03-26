<?php
/**
 * Shared helpers for workspace ability classes.
 *
 * @package WP_MCP_Toolkit
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

trait WP_MCP_Toolkit_Workspace_Helpers {

	/**
	 * Resolve a workspace template path by filename.
	 */
	protected static function tpl( string $name ): string {
		return __DIR__ . '/templates/' . $name;
	}

	protected function ensure_workspace(): true|\WP_Error {
		if ( WP_MCP_Toolkit_Workspace_Container::is_initialized() ) {
			return true;
		}
		return WP_MCP_Toolkit_Workspace_Container::initialize_workspace();
	}

	protected function save_artifact( string $name, string $type, string $file, string $ability ): true|\WP_Error {
		$result = WP_MCP_Toolkit_Workspace_Manifest::add_artifact( [
			'name' => $name, 'type' => $type, 'file' => $file, 'source_ability' => $ability,
		] );
		if ( is_wp_error( $result ) ) {
			if ( 'wpmcp_duplicate_artifact' === $result->get_error_code() ) {
				$update = WP_MCP_Toolkit_Workspace_Manifest::update_artifact( $name, [ 'file' => $file ] );
				if ( is_wp_error( $update ) ) {
					return $update;
				}
			} else {
				return $result;
			}
		}
		WP_MCP_Toolkit_Workspace_Container::reinitialize();
		return true;
	}
}
