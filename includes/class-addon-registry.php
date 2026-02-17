<?php
/**
 * WP MCP Toolkit — Add-on Registry.
 *
 * Singleton registry for add-on modules. Replaces hardcoded class_exists() checks.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();

class WP_MCP_Toolkit_Addon_Registry {

	private static ?self $instance = null;

	/** @var array<string, WP_MCP_Toolkit_Addon> */
	private array $addons = array();

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function register( WP_MCP_Toolkit_Addon $addon ): void {
		$this->addons[ $addon->get_slug() ] = $addon;
	}

	/** @return array<string, WP_MCP_Toolkit_Addon> */
	public function get_all(): array {
		return $this->addons;
	}

	/**
	 * Check if an add-on is enabled (not in the disabled list).
	 */
	public function is_enabled( string $slug ): bool {
		$disabled = get_option( 'wpmcp_disabled_addons', array() );
		return ! in_array( $slug, $disabled, true );
	}

	/** @return array<string, WP_MCP_Toolkit_Addon> */
	public function get_active(): array {
		$disabled_addons = get_option( 'wpmcp_disabled_addons', array() );

		return array_filter(
			$this->addons,
			static function ( WP_MCP_Toolkit_Addon $addon ) use ( $disabled_addons ): bool {
				// Check admin-level enable/disable toggle.
				if ( in_array( $addon->get_slug(), $disabled_addons, true ) ) {
					return false;
				}
				// Check if the required plugin is available.
				if ( ! $addon->is_available() ) {
					return false;
				}
				return true;
			}
		);
	}
}
