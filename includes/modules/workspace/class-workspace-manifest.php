<?php
/**
 * Workspace manifest — artifact tracking via manifest.json.
 *
 * @package WP_MCP_Toolkit
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Static utility class for reading, writing, and mutating the workspace manifest.
 *
 * The manifest is a single JSON file (manifest.json) that tracks every artifact
 * generated inside the active workspace directory.
 */
class WP_MCP_Toolkit_Workspace_Manifest {

	/**
	 * Absolute path to the manifest file in the active workspace.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public static function get_path(): string {
		return WP_MCP_Toolkit_Workspace_Container::get_active_dir() . 'manifest.json';
	}

	/**
	 * Read and decode the manifest, falling back to a blank slate when absent or corrupt.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public static function read(): array {
		$path = self::get_path();

		if ( ! file_exists( $path ) ) {
			return self::default_manifest();
		}

		$contents = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$decoded  = json_decode( $contents, true );

		// Treat any JSON error (null result) as a corrupted file and start fresh.
		if ( ! is_array( $decoded ) ) {
			return self::default_manifest();
		}

		return $decoded;
	}

	/**
	 * Encode and persist the manifest atomically.
	 *
	 * @since 2.0.0
	 *
	 * @param array $manifest Full manifest array to write.
	 * @return true|\WP_Error
	 */
	public static function write( array $manifest ): true|\WP_Error {
		$path   = self::get_path();
		$json   = json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
		$result = file_put_contents( $path, $json, LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		if ( false === $result ) {
			return new \WP_Error( 'wpmcp_manifest_write_failed', 'Failed to write manifest.json' );
		}

		return true;
	}

	/**
	 * Append a new artifact entry, refusing duplicates by name.
	 *
	 * @since 2.0.0
	 *
	 * @param array $entry Artifact data; must include name, type, and file.
	 * @return true|\WP_Error
	 */
	public static function add_artifact( array $entry ): true|\WP_Error {
		$required = [ 'name', 'type', 'file' ];
		foreach ( $required as $key ) {
			if ( empty( $entry[ $key ] ) ) {
				return new \WP_Error( 'wpmcp_missing_fields', 'Artifact entry requires name, type, and file.' );
			}
		}

		$manifest = self::read();

		// Guard against accidental overwrites — callers should use update_artifact() instead.
		if ( null !== self::find_artifact_index( $manifest['artifacts'], $entry['name'] ) ) {
			return new \WP_Error( 'wpmcp_duplicate_artifact', 'Artifact already exists: ' . $entry['name'] );
		}

		$now = gmdate( 'c' );

		$record = [
			'name'           => $entry['name'],
			'type'           => $entry['type'],
			'file'           => $entry['file'],
			'source_ability' => $entry['source_ability'] ?? '',
			'checksum'       => WP_MCP_Toolkit_Workspace_File_Writer::file_checksum( $entry['file'] ),
			'created_at'     => $now,
			'updated_at'     => $now,
		];

		$manifest['artifacts'][] = $record;

		return self::write( $manifest );
	}

	/**
	 * Merge updates into an existing artifact, refreshing the checksum when the file changes.
	 *
	 * @since 2.0.0
	 *
	 * @param string $name    Artifact name to locate.
	 * @param array  $updates Key/value pairs to merge into the existing entry.
	 * @return true|\WP_Error
	 */
	public static function update_artifact( string $name, array $updates ): true|\WP_Error {
		$manifest = self::read();
		$index    = self::find_artifact_index( $manifest['artifacts'], $name );

		if ( null === $index ) {
			return new \WP_Error( 'wpmcp_not_found', 'Artifact not found: ' . $name );
		}

		$existing = $manifest['artifacts'][ $index ];
		$merged   = array_merge( $existing, $updates );

		// Keep the timestamp honest regardless of what the caller passed.
		$merged['updated_at'] = gmdate( 'c' );

		// Recalculate checksum whenever the file path is part of the update.
		if ( isset( $updates['file'] ) ) {
			$merged['checksum'] = WP_MCP_Toolkit_Workspace_File_Writer::file_checksum( $merged['file'] );
		}

		$manifest['artifacts'][ $index ] = $merged;

		return self::write( $manifest );
	}

	/**
	 * Delete an artifact from the manifest (does not delete the file on disk).
	 *
	 * @since 2.0.0
	 *
	 * @param string $name Artifact name to remove.
	 * @return true|\WP_Error
	 */
	public static function remove_artifact( string $name ): true|\WP_Error {
		$manifest = self::read();
		$index    = self::find_artifact_index( $manifest['artifacts'], $name );

		if ( null === $index ) {
			return new \WP_Error( 'wpmcp_not_found', 'Artifact not found: ' . $name );
		}

		// array_splice re-indexes numerically in place — no array_values needed.
		array_splice( $manifest['artifacts'], $index, 1 );

		return self::write( $manifest );
	}

	/**
	 * Retrieve a single artifact by name, or null when not present.
	 *
	 * @since 2.0.0
	 *
	 * @param string $name Artifact name.
	 * @return array|null
	 */
	public static function get_artifact( string $name ): ?array {
		$manifest = self::read();
		$index    = self::find_artifact_index( $manifest['artifacts'], $name );

		return null !== $index ? $manifest['artifacts'][ $index ] : null;
	}

	/**
	 * Return all artifacts, optionally filtered to a specific type.
	 *
	 * @since 2.0.0
	 *
	 * @param string $type When non-empty, only artifacts of this type are returned.
	 * @return array
	 */
	public static function list_artifacts( string $type = '' ): array {
		$manifest  = self::read();
		$artifacts = $manifest['artifacts'];

		if ( '' === $type ) {
			return $artifacts;
		}

		return array_values(
			array_filter( $artifacts, fn( $a ) => $a['type'] === $type )
		);
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Blank manifest structure used when none exists or the file is unreadable.
	 *
	 * @return array
	 */
	private static function default_manifest(): array {
		return [
			'version'   => 1,
			'artifacts' => [],
		];
	}

	/**
	 * Linear search for an artifact by name; returns its index or null.
	 *
	 * A linear scan is acceptable here because workspaces are not expected to
	 * contain thousands of artifacts.
	 *
	 * @param array  $artifacts Flat artifacts array from the manifest.
	 * @param string $name      Name to find.
	 * @return int|null
	 */
	private static function find_artifact_index( array $artifacts, string $name ): ?int {
		foreach ( $artifacts as $i => $artifact ) {
			if ( isset( $artifact['name'] ) && $artifact['name'] === $name ) {
				return $i;
			}
		}

		return null;
	}
}
