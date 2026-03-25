<?php
/**
 * WP MCP Toolkit — Yoast SEO Add-on.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();

class WP_MCP_Toolkit_Yoast_Addon implements WP_MCP_Toolkit_Addon {

	public function get_slug(): string {
		return 'yoast';
	}

	public function get_name(): string {
		return 'Yoast SEO';
	}

	public function get_description(): string {
		return 'Read and update SEO metadata, titles, descriptions, and overview.';
	}

	public function get_icon(): string {
		return 'dashicons-chart-area';
	}

	public function is_available(): bool {
		return defined( 'WPSEO_VERSION' );
	}

	public function is_premium(): bool {
		return true;
	}

	public function is_licensed(): bool {
		$licenses = get_option( 'wpmcp_licenses', array() );
		$key = $licenses[ $this->get_slug() ] ?? '';
		// Basic format check — must be at least 8 chars, alphanumeric + hyphens only.
		// Full LemonSqueezy API validation to be added before public release.
		return (bool) preg_match( '/^[a-zA-Z0-9\-]{8,}$/', $key );
	}

	public function get_version(): string {
		return defined( 'WPSEO_VERSION' ) ? WPSEO_VERSION : '';
	}

	public function get_ability_count(): int {
		return 3; // get-post-seo, update-post-seo, get-seo-overview.
	}

	public function register_categories(): void {
		wp_register_ability_category( 'wpmcp-yoast', array(
			'label'       => __( 'Yoast SEO', 'wp-mcp-toolkit' ),
			'description' => __( 'Abilities for managing Yoast SEO metadata.', 'wp-mcp-toolkit' ),
		) );
	}

	public function register_abilities( array $disabled ): void {
		require_once __DIR__ . '/class-yoast-module.php';
		WP_MCP_Toolkit_Yoast_Module::init( $disabled );
	}
}
