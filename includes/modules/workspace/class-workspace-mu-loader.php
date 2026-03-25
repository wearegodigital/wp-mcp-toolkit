<?php
/**
 * Workspace MU-plugin loader — install, update, and crash recovery management.
 *
 * @package WP_MCP_Toolkit
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Static utility class for managing the MU-plugin loader installation.
 */
class WP_MCP_Toolkit_Workspace_MU_Loader {

	/**
	 * Path where the MU-plugin loader file lives.
	 *
	 * @since 2.0.0
	 *
	 * @return string Absolute path to the installed loader file.
	 */
	public static function get_loader_path(): string {
		return WPMU_PLUGIN_DIR . '/wpmcp-workspace-loader.php';
	}

	/**
	 * Whether the MU-plugin loader is currently on disk.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public static function is_installed(): bool {
		return file_exists( self::get_loader_path() );
	}

	/**
	 * Whether the on-disk loader is out of sync with the current template.
	 *
	 * Detects drift caused by version upgrades or active-dir changes so the
	 * caller can decide whether to re-install without touching disk unnecessarily.
	 *
	 * @since 2.0.0
	 *
	 * @return bool True when the installed file differs from the rendered template.
	 */
	public static function needs_update(): bool {
		if ( ! self::is_installed() ) {
			return true;
		}

		$rendered = self::render_loader_template();
		if ( is_wp_error( $rendered ) ) {
			// Treat render failure as "needs update" so install() can surface the error.
			return true;
		}

		return md5( $rendered ) !== md5_file( self::get_loader_path() );
	}

	/**
	 * Write the rendered MU-plugin loader to disk, creating the mu-plugins
	 * directory if it does not yet exist.
	 *
	 * @since 2.0.0
	 *
	 * @return true|\WP_Error
	 */
	public static function install(): true|\WP_Error {
		$content = self::render_loader_template();
		if ( is_wp_error( $content ) ) {
			return $content;
		}

		// WordPress may not have created the mu-plugins directory yet on fresh installs.
		wp_mkdir_p( WPMU_PLUGIN_DIR );

		$written = file_put_contents( self::get_loader_path(), $content, LOCK_EX );
		if ( false === $written ) {
			return new \WP_Error( 'wpmcp_mu_install_failed', 'Could not write MU-plugin loader.' );
		}

		return true;
	}

	/**
	 * Remove the MU-plugin loader from disk and clean up any crash markers.
	 *
	 * Idempotent — returns true when the file is already absent.
	 *
	 * @since 2.0.0
	 *
	 * @return true|\WP_Error
	 */
	public static function uninstall(): true|\WP_Error {
		if ( ! self::is_installed() ) {
			// Nothing to remove; still attempt crash cleanup so state is consistent.
			self::recover_from_crash();
			return true;
		}

		// Suppress the warning — we check the return value explicitly.
		if ( ! @unlink( self::get_loader_path() ) ) {
			return new \WP_Error( 'wpmcp_mu_uninstall_failed', 'Could not remove MU-plugin loader.' );
		}

		self::recover_from_crash();

		return true;
	}

	/**
	 * Snapshot of crash / loading marker files so callers can surface health
	 * information without directly touching the filesystem.
	 *
	 * @since 2.0.0
	 *
	 * @return array{crashed: bool, loading: bool, crash_file: string}
	 */
	public static function get_crash_status(): array {
		$dir = WP_MCP_Toolkit_Workspace_Container::get_active_dir();

		return [
			'crashed'    => file_exists( $dir . '.crashed' ),
			'loading'    => file_exists( $dir . '.loading' ),
			'crash_file' => $dir . '.crashed',
		];
	}

	/**
	 * Delete crash and loading marker files so the workspace can boot cleanly
	 * on the next request.
	 *
	 * Markers are written by the MU-loader on boot and cleared on clean shutdown;
	 * their presence after a crash prevents auto-restart loops.
	 *
	 * @since 2.0.0
	 *
	 * @return true|\WP_Error
	 */
	public static function recover_from_crash(): true|\WP_Error {
		$dir = WP_MCP_Toolkit_Workspace_Container::get_active_dir();

		if ( file_exists( $dir . '.crashed' ) ) {
			@unlink( $dir . '.crashed' );
		}

		if ( file_exists( $dir . '.loading' ) ) {
			@unlink( $dir . '.loading' );
		}

		return true;
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Render the mu-loader template with the current active workspace path.
	 *
	 * @since 2.0.0
	 *
	 * @return string|\WP_Error Rendered PHP source on success, WP_Error on failure.
	 */
	private static function render_loader_template(): string|\WP_Error {
		$vars = [
			'WORKSPACE_PLUGIN_PATH' => WP_MCP_Toolkit_Workspace_Container::get_active_dir() . 'wpmcp-workspace.php',
		];

		return WP_MCP_Toolkit_Workspace_Container::render_template(
			__DIR__ . '/templates/mu-loader.php.tpl',
			$vars
		);
	}
}
