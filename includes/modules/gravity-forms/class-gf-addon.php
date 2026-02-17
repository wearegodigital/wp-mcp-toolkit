<?php
/**
 * WP MCP Toolkit — Gravity Forms Add-on.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();

class WP_MCP_Toolkit_GF_Addon implements WP_MCP_Toolkit_Addon {

	public function get_slug(): string {
		return 'gravity-forms';
	}

	public function get_name(): string {
		return 'Gravity Forms';
	}

	public function get_description(): string {
		return 'List forms, query entries, and create new form submissions.';
	}

	public function get_icon(): string {
		return 'dashicons-feedback';
	}

	public function is_available(): bool {
		return class_exists( 'GFAPI' );
	}

	public function is_premium(): bool {
		return true;
	}

	public function is_licensed(): bool {
		$licenses = get_option( 'wpmcp_licenses', array() );
		$key = $licenses[ $this->get_slug() ] ?? '';
		if ( empty( $key ) ) {
			return false;
		}
		// TODO: Validate against LemonSqueezy API.
		// Stub: any non-empty key is treated as valid.
		return true;
	}

	public function get_version(): string {
		return class_exists( 'GFCommon' ) ? \GFCommon::$version : '';
	}

	public function get_ability_count(): int {
		return 5;
	}

	public function register_categories(): void {
		wp_register_ability_category( 'wpmcp-gf', array(
			'label'       => __( 'Gravity Forms', 'wp-mcp-toolkit' ),
			'description' => __( 'Abilities for managing Gravity Forms data and entries.', 'wp-mcp-toolkit' ),
		) );
	}

	public function register_abilities( array $disabled ): void {
		require_once __DIR__ . '/class-gf-module.php';
		WP_MCP_Toolkit_GF_Module::init();
	}
}
