<?php
/**
 * WP MCP Toolkit — Add-on Interface.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();

interface WP_MCP_Toolkit_Addon {
	public function get_slug(): string;
	public function get_name(): string;
	public function get_description(): string;
	public function get_icon(): string;
	public function is_available(): bool;
	public function is_premium(): bool;
	public function is_licensed(): bool;
	public function get_version(): string;
	public function get_ability_count(): int;
	public function register_categories(): void;
	public function register_abilities( array $disabled ): void;
}
