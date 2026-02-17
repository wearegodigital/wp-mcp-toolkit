<?php
/**
 * WP MCP Toolkit — ACF Add-on.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();

class WP_MCP_Toolkit_ACF_Addon implements WP_MCP_Toolkit_Addon {

	public function get_slug(): string {
		return 'acf';
	}

	public function get_name(): string {
		return 'Advanced Custom Fields';
	}

	public function get_description(): string {
		return 'Manage ACF field groups, post fields, and block-level fields.';
	}

	public function get_icon(): string {
		return 'dashicons-admin-generic';
	}

	public function is_available(): bool {
		return class_exists( 'ACF' );
	}

	public function is_premium(): bool {
		return false;
	}

	public function is_licensed(): bool {
		return true;
	}

	public function get_version(): string {
		return defined( 'ACF_VERSION' ) ? ACF_VERSION : '';
	}

	public function get_ability_count(): int {
		return 7;
	}

	public function register_categories(): void {
		wp_register_ability_category( 'wpmcp-acf-fields', array(
			'label'       => __( 'ACF Fields', 'wp-mcp-toolkit' ),
			'description' => __( 'Abilities for managing Advanced Custom Fields data.', 'wp-mcp-toolkit' ),
		) );
	}

	public function register_abilities( array $disabled ): void {
		require_once __DIR__ . '/class-acf-module.php';
		WP_MCP_Toolkit_ACF_Module::init();
	}
}
