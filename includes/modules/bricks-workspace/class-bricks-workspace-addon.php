<?php
/**
 * Bricks Workspace addon — Bricks Builder element scaffolding for the Workspace module.
 *
 * @package WP_MCP_Toolkit
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

class WP_MCP_Toolkit_Bricks_Workspace_Addon implements WP_MCP_Toolkit_Addon {

	public function get_slug(): string {
		return 'bricks-workspace';
	}

	public function get_name(): string {
		return 'Bricks Workspace';
	}

	public function get_description(): string {
		return 'Bricks Builder element scaffolding for the Workspace module.';
	}

	public function get_icon(): string {
		return 'dashicons-layout';
	}

	public function is_available(): bool {
		return version_compare( PHP_VERSION, '8.0', '>=' ) && defined( 'BRICKS_VERSION' );
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
		return 3; // scaffold-bricks-element, update-bricks-element, list-bricks-elements.
	}

	public function register_categories(): void {
		wp_register_ability_category( 'wpmcp-bricks', array(
			'label' => __( 'Bricks Workspace', 'wp-mcp-toolkit' ),
		) );
	}

	public function register_abilities( array $disabled ): void {
		require_once __DIR__ . '/../workspace/class-workspace-bricks-abilities.php';
		( new WP_MCP_Toolkit_Workspace_Bricks_Abilities() )->register( $disabled );
	}
}
