<?php
/**
 * Workspace Bricks Insertion abilities — insert Bricks elements into page content.
 *
 * @package WP_MCP_Toolkit
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

class WP_MCP_Toolkit_Workspace_Bricks_Insertion_Abilities extends WP_MCP_Toolkit_Abstract_Abilities {

	use WP_MCP_Toolkit_Workspace_Helpers;

	protected function get_abilities(): array {
		$c = 'wpmcp-bricks';
		$s = fn( $r, $pr ) => [ 'type' => 'object', 'required' => $r, 'properties' => $pr, 'additionalProperties' => false ];
		$o = fn( $pr ) => [ 'type' => 'object', 'properties' => $pr ];
		$w = fn( $cb, $ro, $dest, $idemp ) => [ 'callback' => $cb, 'permission' => self::permission_for_post( 'edit_post' ), 'readonly' => $ro, 'destructive' => $dest, 'idempotent' => $idemp ];

		return [
			'wpmcp-bricks/insert-bricks-element' => [
				'label'         => __( 'Insert Bricks Element', 'wp-mcp-toolkit' ),
				'description'   => __( 'Inserts a workspace Bricks element into a post or page\'s Bricks content.', 'wp-mcp-toolkit' ),
				'category'      => $c,
				'input_schema'  => $s( [ 'post_id', 'element_name' ], [
					'post_id'          => [ 'type' => 'integer', 'description' => 'Post/page ID to insert the element into.' ],
					'element_name'     => [ 'type' => 'string', 'description' => 'Workspace element name (e.g. "icon-grid").' ],
					'parent_id'        => [ 'type' => 'string', 'default' => '', 'description' => 'Parent element ID. Empty = root level.' ],
					'after_element_id' => [ 'type' => 'string', 'default' => '', 'description' => 'Insert after this element ID. Empty = append.' ],
					'settings'         => [ 'type' => 'object', 'default' => [], 'description' => 'Element settings/attribute values.' ],
				] ),
				'output_schema' => $o( [
					'success'        => [ 'type' => 'boolean' ],
					'post_id'        => [ 'type' => 'integer' ],
					'element_id'     => [ 'type' => 'string' ],
					'element_name'   => [ 'type' => 'string' ],
					'total_elements' => [ 'type' => 'integer' ],
				] ),
			] + $w( 'execute_insert_bricks_element', false, false, false ),
		];
	}

	// -- Execute methods ------------------------------------------------------

	/**
	 * Insert a workspace Bricks element into a post's Bricks content.
	 *
	 * @since 2.0.0
	 * @param array|object $input Ability input.
	 * @return array|\WP_Error
	 */
	public function execute_insert_bricks_element( $input = [] ): array|\WP_Error {
		if ( 'disabled' === get_option( 'wpmcp_workspace_mode', 'auto' ) ) {
			return new \WP_Error( 'wpmcp_workspace_disabled', 'Workspace is disabled.' );
		}

		$input = self::normalize_input( $input );

		$post_id      = absint( $input['post_id'] ?? 0 );
		$element_name = sanitize_key( $input['element_name'] ?? '' );
		$parent_id    = sanitize_text_field( $input['parent_id'] ?? '' );
		$after_id     = sanitize_text_field( $input['after_element_id'] ?? '' );
		$settings     = self::sanitize_recursive( (array) ( $input['settings'] ?? [] ) );

		// Validate post_id.
		if ( $post_id < 1 ) {
			return new \WP_Error( 'wpmcp_invalid_input', 'post_id is required and must be a positive integer.' );
		}

		// Validate element_name.
		if ( '' === $element_name ) {
			return new \WP_Error( 'wpmcp_invalid_input', 'element_name is required.' );
		}

		// Validate post exists.
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'wpmcp_not_found', 'Post not found: ' . $post_id );
		}

		// Validate element_name exists in workspace manifest as a bricks-element.
		$artifact = WP_MCP_Toolkit_Workspace_Manifest::get_artifact( $element_name );
		if ( ! $artifact ) {
			return new \WP_Error( 'wpmcp_not_found', 'Workspace element not found: ' . $element_name . '. Use scaffold-bricks-element first.' );
		}
		if ( ( $artifact['type'] ?? '' ) !== 'bricks-element' ) {
			return new \WP_Error( 'wpmcp_invalid_input', 'Artifact "' . $element_name . '" is not a bricks-element (type: ' . ( $artifact['type'] ?? 'unknown' ) . ').' );
		}

		// Read existing Bricks content.
		$elements = get_post_meta( $post_id, '_bricks_page_content_2', true );
		if ( ! is_array( $elements ) ) {
			$elements = [];
		}

		// Generate unique element ID.
		if ( class_exists( '\Bricks\Helpers' ) ) {
			$new_id = \Bricks\Helpers::generate_random_id( false );
		} else {
			$new_id = substr( md5( uniqid( '', true ) ), 0, 6 );
		}

		// Build new element.
		$new_element = [
			'id'       => $new_id,
			'name'     => sanitize_key( $element_name ),
			'parent'   => $parent_id,
			'children' => [],
			'settings' => $settings,
		];

		// Determine insertion position.
		if ( '' !== $after_id ) {
			// Find after_element_id index in flat array and insert after it.
			$insert_at = count( $elements ); // default: append if not found.
			foreach ( $elements as $idx => $el ) {
				if ( ( $el['id'] ?? '' ) === $after_id ) {
					$insert_at = $idx + 1;
					break;
				}
			}
			array_splice( $elements, $insert_at, 0, [ $new_element ] );
		} else {
			// Append to end (root or under parent).
			$elements[] = $new_element;
		}

		// If parent_id is set, add new_id to parent's children array.
		if ( '' !== $parent_id ) {
			foreach ( $elements as &$el ) {
				if ( ( $el['id'] ?? '' ) === $parent_id ) {
					if ( ! isset( $el['children'] ) ) {
						$el['children'] = [];
					}
					$el['children'][] = $new_id;
					break;
				}
			}
			unset( $el );
		}

		// Save updated content.
		update_post_meta( $post_id, '_bricks_page_content_2', $elements );

		return [
			'success'        => true,
			'post_id'        => $post_id,
			'element_id'     => $new_id,
			'element_name'   => $element_name,
			'total_elements' => count( $elements ),
		];
	}
}
