<?php
/**
 * Workspace addon — registers workspace abilities with the toolkit.
 *
 * @package WP_MCP_Toolkit
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

class WP_MCP_Toolkit_Workspace_Addon implements WP_MCP_Toolkit_Addon {

	public function get_slug(): string {
		return 'workspace';
	}

	public function get_name(): string {
		return 'Workspace';
	}

	public function get_description(): string {
		return 'Dev-mode extension that lets AI agents write structured PHP code and Gutenberg blocks inside WordPress.';
	}

	public function get_icon(): string {
		return 'dashicons-editor-code';
	}

	public function is_available(): bool {
		return version_compare( PHP_VERSION, '8.0', '>=' );
	}

	public function is_premium(): bool {
		return false;
	}

	public function is_licensed(): bool {
		return true;
	}

	public function get_version(): string {
		return WP_MCP_VERSION;
	}

	public function get_ability_count(): int {
		return 10; // 7 workspace abilities (generate-function, generate-class, register-hook, call-wp-api, list-workspace, read-workspace-file, delete-workspace-artifact) + 3 block abilities (scaffold-block, update-block, list-workspace-blocks).
	}

	public function register_categories(): void {
		wp_register_ability_category( 'wpmcp-workspace', array(
			'label' => __( 'Workspace', 'wp-mcp-toolkit' ),
		) );
		wp_register_ability_category( 'wpmcp-workspace-blocks', array(
			'label' => __( 'Workspace Blocks', 'wp-mcp-toolkit' ),
		) );
	}

	public function register_abilities( array $disabled ): void {
		require_once __DIR__ . '/class-workspace-module.php';
		WP_MCP_Toolkit_Workspace_Module::init( $disabled );
	}
}
