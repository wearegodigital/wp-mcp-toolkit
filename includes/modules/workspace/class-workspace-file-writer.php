<?php
/**
 * Workspace file writer — safe file operations with locking and validation.
 *
 * @package WP_MCP_Toolkit
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Static utility class for safe file operations within the workspace.
 */
class WP_MCP_Toolkit_Workspace_File_Writer {

	/**
	 * Write a file to the workspace, creating parent directories as needed.
	 *
	 * @since 2.0.0
	 *
	 * @param string $relative_path Path relative to the workspace root.
	 * @param string $content       File content to write.
	 * @return string|\WP_Error Absolute path on success, WP_Error on failure.
	 */
	public static function write_file( string $relative_path, string $content ): string|\WP_Error {
		$validated = WP_MCP_Toolkit_Workspace_Validator::validate_path( $relative_path );
		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		$absolute_path = WP_MCP_Toolkit_Workspace_Container::get_active_dir() . $relative_path;

		// Ensure the parent directory exists before attempting the write.
		$relative_parent = dirname( $relative_path );
		if ( '.' !== $relative_parent ) {
			$dir_result = WP_MCP_Toolkit_Workspace_Container::ensure_dir( $relative_parent );
			if ( is_wp_error( $dir_result ) ) {
				return $dir_result;
			}
		}

		$bytes = file_put_contents( $absolute_path, $content, LOCK_EX );
		if ( false === $bytes ) {
			return new \WP_Error( 'wpmcp_write_failed', 'Failed to write file: ' . $relative_path );
		}

		chmod( $absolute_path, 0644 );

		return $absolute_path;
	}

	/**
	 * Atomically write the bootstrap file using a temp-then-rename pattern.
	 *
	 * Concurrent MCP requests may race to regenerate the bootstrap; writing to
	 * a temp file and renaming is the only way to guarantee readers never see a
	 * partial file on POSIX systems.
	 *
	 * @since 2.0.0
	 *
	 * @param string $absolute_path Absolute path to the bootstrap file.
	 * @param string $content       File content to write.
	 * @return true|\WP_Error True on success, WP_Error on failure.
	 */
	public static function write_bootstrap( string $absolute_path, string $content ): true|\WP_Error {
		$tmp = $absolute_path . '.tmp.' . getmypid();

		$bytes = file_put_contents( $tmp, $content, LOCK_EX );
		if ( false === $bytes ) {
			return new \WP_Error( 'wpmcp_write_failed', 'Failed to write bootstrap temp file.' );
		}

		// Open the target with 'c' so it is created if absent but not truncated.
		$handle = fopen( $absolute_path, 'c' );
		if ( false === $handle ) {
			@unlink( $tmp );
			return new \WP_Error( 'wpmcp_write_failed', 'Failed to open bootstrap file for locking.' );
		}

		flock( $handle, LOCK_EX );

		if ( ! rename( $tmp, $absolute_path ) ) {
			@unlink( $tmp );
			flock( $handle, LOCK_UN );
			fclose( $handle );
			return new \WP_Error( 'wpmcp_write_failed', 'Failed to atomically replace bootstrap file.' );
		}

		flock( $handle, LOCK_UN );
		fclose( $handle );

		// Invalidate OPcache so PHP picks up the new bootstrap immediately.
		if ( function_exists( 'wp_opcache_invalidate' ) ) {
			wp_opcache_invalidate( $absolute_path );
		}

		return true;
	}

	/**
	 * Read a file from the workspace.
	 *
	 * @since 2.0.0
	 *
	 * @param string $relative_path Path relative to the workspace root.
	 * @return string|\WP_Error File contents on success, WP_Error on failure.
	 */
	public static function read_file( string $relative_path ): string|\WP_Error {
		$validated = WP_MCP_Toolkit_Workspace_Validator::validate_path( $relative_path );
		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		$absolute_path = WP_MCP_Toolkit_Workspace_Container::get_active_dir() . $relative_path;

		if ( ! file_exists( $absolute_path ) ) {
			return new \WP_Error( 'wpmcp_not_found', 'File not found: ' . $relative_path );
		}

		return file_get_contents( $absolute_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	}

	/**
	 * Delete a file from the workspace.
	 *
	 * @since 2.0.0
	 *
	 * @param string $relative_path Path relative to the workspace root.
	 * @return true|\WP_Error True on success, WP_Error on failure.
	 */
	public static function delete_file( string $relative_path ): true|\WP_Error {
		$validated = WP_MCP_Toolkit_Workspace_Validator::validate_path( $relative_path );
		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		$absolute_path = WP_MCP_Toolkit_Workspace_Container::get_active_dir() . $relative_path;

		if ( ! file_exists( $absolute_path ) ) {
			return new \WP_Error( 'wpmcp_not_found', 'File not found: ' . $relative_path );
		}

		if ( ! unlink( $absolute_path ) ) {
			return new \WP_Error( 'wpmcp_delete_failed', 'Failed to delete file: ' . $relative_path );
		}

		return true;
	}

	/**
	 * Return an MD5 checksum for a workspace file, or an empty string if absent.
	 *
	 * Callers use this to detect whether the file changed between requests,
	 * so a missing file is treated as a known-empty state rather than an error.
	 *
	 * @since 2.0.0
	 *
	 * @param string $relative_path Path relative to the workspace root.
	 * @return string MD5 hex digest, or empty string if the file does not exist.
	 */
	public static function file_checksum( string $relative_path ): string {
		$absolute_path = WP_MCP_Toolkit_Workspace_Container::get_active_dir() . $relative_path;

		if ( ! file_exists( $absolute_path ) ) {
			return '';
		}

		return md5_file( $absolute_path ) ?: '';
	}
}
