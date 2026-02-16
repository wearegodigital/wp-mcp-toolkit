<?php
/**
 * WP MCP Toolkit — Ability Registrar.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();

class WP_MCP_Toolkit_Ability_Registrar {

	public function register(): void {
		$ability_classes = array(
			'schema'   => __DIR__ . '/abilities/class-schema-abilities.php',
			'content'  => __DIR__ . '/abilities/class-content-abilities.php',
			'block'    => __DIR__ . '/abilities/class-block-abilities.php',
			'taxonomy' => __DIR__ . '/abilities/class-taxonomy-abilities.php',
			'media'    => __DIR__ . '/abilities/class-media-abilities.php',
		);

		foreach ( $ability_classes as $file ) {
			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}

		$classes = array(
			'WP_MCP_Toolkit_Schema_Abilities',
			'WP_MCP_Toolkit_Content_Abilities',
			'WP_MCP_Toolkit_Block_Abilities',
			'WP_MCP_Toolkit_Taxonomy_Abilities',
			'WP_MCP_Toolkit_Media_Abilities',
		);

		$disabled = get_option( 'wpmcp_disabled_abilities', array() );

		foreach ( $classes as $class ) {
			if ( class_exists( $class ) ) {
				$instance = new $class();
				$instance->register( $disabled );
			}
		}
	}
}
