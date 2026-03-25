<?php
/**
 * Workspace container — path management and template rendering.
 *
 * @package WP_MCP_Toolkit
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Static utility class for workspace path management and template rendering.
 *
 * Resolves the writable workspace root once per request and exposes helpers
 * for subdirectory creation, path normalisation, and mustache-style template
 * rendering.
 */
class WP_MCP_Toolkit_Workspace_Container {

	/**
	 * Cached active directory so we only pay the mkdir/writable check once.
	 *
	 * @var string|null
	 */
	private static ?string $active_dir = null;

	/**
	 * Returns the plugin-owned workspace directory path.
	 *
	 * Not guaranteed to exist or be writable — use get_active_dir() for that.
	 *
	 * @return string
	 */
	public static function get_plugin_dir(): string {
		return WP_CONTENT_DIR . '/plugins/wpmcp-workspace/';
	}

	/**
	 * Returns the uploads-based fallback workspace directory path.
	 *
	 * Uploads is almost always writable even when the plugin dir is not,
	 * so this serves as the safety net for restricted server configurations.
	 *
	 * @return string
	 */
	public static function get_uploads_dir(): string {
		return wp_upload_dir()['basedir'] . '/wpmcp-workspace/';
	}

	/**
	 * Returns the first writable workspace root, creating it if necessary.
	 *
	 * Prefers the plugin directory so workspace files stay co-located with
	 * plugin assets; falls back to uploads when permissions prevent that.
	 * The result is cached for the lifetime of the request.
	 *
	 * @return string
	 */
	public static function get_active_dir(): string {
		if ( null !== self::$active_dir ) {
			return self::$active_dir;
		}

		$plugin_dir = self::get_plugin_dir();

		if ( wp_mkdir_p( $plugin_dir ) && wp_is_writable( $plugin_dir ) ) {
			self::$active_dir = $plugin_dir;
		} else {
			// Plugin directory isn't usable — uploads is the universal fallback.
			self::$active_dir = self::get_uploads_dir();
		}

		return self::$active_dir;
	}

	/**
	 * Returns true when the active workspace directory exists and is writable.
	 *
	 * A false return means workspace operations will silently fail, so callers
	 * should gate any write operations on this check.
	 *
	 * @return bool
	 */
	public static function is_writable(): bool {
		$dir = self::get_active_dir();

		if ( ! wp_mkdir_p( $dir ) ) {
			return false;
		}

		return wp_is_writable( $dir );
	}

	/**
	 * Creates a subdirectory within the workspace and returns its absolute path.
	 *
	 * @param string $subdir Relative subdirectory name (e.g. 'exports/2024').
	 * @return string|\WP_Error Absolute path on success, WP_Error when mkdir fails.
	 */
	public static function ensure_dir( string $subdir ): string|\WP_Error {
		$path = self::get_active_dir() . $subdir;

		if ( ! wp_mkdir_p( $path ) ) {
			return new \WP_Error( 'wpmcp_mkdir_failed', 'Could not create directory: ' . $subdir );
		}

		return $path;
	}

	/**
	 * Strips the workspace root from an absolute path for portable storage.
	 *
	 * Storing relative paths in manifests means the workspace can be relocated
	 * (e.g. plugin → uploads fallback) without invalidating stored references.
	 *
	 * @param string $absolute Absolute filesystem path inside the workspace.
	 * @return string Relative portion of the path.
	 */
	public static function get_relative_path( string $absolute ): string {
		return str_replace( self::get_active_dir(), '', $absolute );
	}

	/**
	 * Renders a template file by replacing {{KEY}} placeholders with values.
	 *
	 * Deliberately uses str_replace rather than eval/extract so the rendering
	 * surface is predictable and auditable. Keys are compared case-sensitively
	 * after uppercasing, matching the {{UPPERCASE}} convention used in templates.
	 *
	 * @param string        $template_path Absolute path to the template file.
	 * @param array<string,string> $vars   Map of placeholder name → replacement value.
	 * @return string Rendered output, or empty string when the file is missing.
	 */
	public static function render_template( string $template_path, array $vars ): string {
		if ( ! file_exists( $template_path ) ) {
			return '';
		}

		$content = file_get_contents( $template_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		$search  = [];
		$replace = [];

		foreach ( $vars as $key => $value ) {
			$search[]  = '{{' . strtoupper( $key ) . '}}';
			$replace[] = $value;
		}

		return str_replace( $search, $replace, $content );
	}

	/**
	 * Sets up the full workspace structure on first use.
	 *
	 * Creates the standard subdirectories, writes the bootstrap plugin file,
	 * initialises an empty manifest, and installs the MU-plugin loader so
	 * WordPress auto-loads the workspace on every request.
	 *
	 * @since 2.0.0
	 * @return true|\WP_Error True on success, WP_Error on first failure.
	 */
	public static function initialize_workspace(): true|\WP_Error {
		self::get_active_dir();

		foreach ( [ 'functions', 'classes', 'hooks', 'blocks', 'bricks' ] as $subdir ) {
			$result = self::ensure_dir( $subdir );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		$write_result = self::write_bootstrap();
		if ( is_wp_error( $write_result ) ) {
			return $write_result;
		}

		$manifest_result = WP_MCP_Toolkit_Workspace_Manifest::write(
			[
				'version'   => 1,
				'artifacts' => [],
			]
		);
		if ( is_wp_error( $manifest_result ) ) {
			return $manifest_result;
		}

		$mu_result = WP_MCP_Toolkit_Workspace_MU_Loader::install();
		if ( is_wp_error( $mu_result ) ) {
			return $mu_result;
		}

		// Signal that initialization is complete.
		file_put_contents( self::get_active_dir() . '.ready', gmdate( 'c' ), LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		return true;
	}

	/**
	 * Returns whether the workspace has been fully initialised.
	 *
	 * Presence of the .ready marker is the canonical signal — it is written
	 * as the very last step of initialize_workspace(), so its absence means
	 * setup either never ran or was interrupted mid-flight.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public static function is_initialized(): bool {
		return file_exists( self::get_active_dir() . '.ready' );
	}

	/**
	 * Regenerates the bootstrap file without touching existing artifacts.
	 *
	 * Useful after a plugin version bump or template change where the
	 * bootstrap needs refreshing but workspace content must be preserved.
	 * Removes .ready before work and re-writes it on success so the MU-loader
	 * never sees a half-regenerated workspace as fully ready.
	 *
	 * @since 2.0.0
	 * @return true|\WP_Error True on success, WP_Error on failure.
	 */
	public static function reinitialize(): true|\WP_Error {
		$ready_file = self::get_active_dir() . '.ready';

		// Clear the ready marker so the MU-loader gates out during regeneration.
		@unlink( $ready_file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		$result = self::write_bootstrap();
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Restore the ready marker now that regeneration is complete.
		file_put_contents( $ready_file, gmdate( 'c' ), LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		return true;
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Render the bootstrap template and write it to the workspace root.
	 *
	 * Both initialize_workspace() and reinitialize() need exactly this — extracted
	 * to avoid duplicating the template path and variable map.
	 *
	 * @return true|\WP_Error
	 */
	private static function write_bootstrap(): true|\WP_Error {
		$content = self::render_template(
			__DIR__ . '/templates/workspace-plugin.php.tpl',
			[
				'WORKSPACE_DIR'     => "__DIR__ . '/'",
				'WORKSPACE_VERSION' => '1.0.0',
			]
		);

		return WP_MCP_Toolkit_Workspace_File_Writer::write_bootstrap(
			self::get_active_dir() . 'wpmcp-workspace.php',
			$content
		);
	}
}
