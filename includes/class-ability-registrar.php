<?php
/**
 * WP MCP Toolkit — Ability Registrar.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();

class WP_MCP_Toolkit_Ability_Registrar {

	public function register(): void {
		require_once __DIR__ . '/class-abstract-abilities.php';

		$classes = array(
			__DIR__ . '/abilities/class-schema-abilities.php'   => 'WP_MCP_Toolkit_Schema_Abilities',
			__DIR__ . '/abilities/class-content-abilities.php'  => 'WP_MCP_Toolkit_Content_Abilities',
			__DIR__ . '/abilities/class-block-abilities.php'    => 'WP_MCP_Toolkit_Block_Abilities',
			__DIR__ . '/abilities/class-taxonomy-abilities.php' => 'WP_MCP_Toolkit_Taxonomy_Abilities',
			__DIR__ . '/abilities/class-media-abilities.php'    => 'WP_MCP_Toolkit_Media_Abilities',
		);

		$disabled = get_option( 'wpmcp_disabled_abilities', array() );

		foreach ( $classes as $file => $class ) {
			require_once $file;
			( new $class() )->register( $disabled );
		}
	}
}
