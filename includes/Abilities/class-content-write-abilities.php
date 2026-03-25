<?php
/**
 * WP MCP Toolkit — Content Write Abilities.
 *
 * Create, update, delete, and replace-content operations for posts, pages, and custom post types.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();

class WP_MCP_Toolkit_Content_Write_Abilities extends WP_MCP_Toolkit_Abstract_Abilities {

	protected function get_abilities(): array {
		return array(
			'wpmcp/create-post' => array(
				'label'         => __( 'Create Post', 'wp-mcp-toolkit' ),
				'description'   => __( 'Creates a new post, page, or custom post type. Requires post_type slug and title. Content should be valid Gutenberg block markup (e.g. "<!-- wp:paragraph --><p>Text</p><!-- /wp:paragraph -->") or plain HTML. Defaults to "draft" status — set status to "publish" to make immediately visible. Use taxonomy_terms to assign categories/tags: {"category": ["News"], "post_tag": ["featured"]}. Use meta for custom fields: {"key": "value"}.', 'wp-mcp-toolkit' ),
				'category'      => 'wpmcp-content',
				'input_schema'  => array(
					'type'       => 'object',
					'required'   => array( 'post_type', 'title' ),
					'properties' => array(
						'post_type'      => array( 'type' => 'string' ),
						'title'          => array( 'type' => 'string' ),
						'content'        => array( 'type' => 'string', 'default' => '' ),
						'excerpt'        => array( 'type' => 'string', 'default' => '' ),
						'status'         => array( 'type' => 'string', 'default' => 'draft', 'enum' => array( 'draft', 'publish', 'pending', 'private' ) ),
						'meta'           => array( 'type' => 'object' ),
						'taxonomy_terms' => array( 'type' => 'object', 'description' => 'Object of taxonomy_slug => [term names or IDs]' ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'post_id' => array( 'type' => 'integer' ),
						'url'     => array( 'type' => 'string' ),
					),
				),
				'callback'    => 'execute_create_post',
				'readonly'    => false,
				'idempotent'  => false,
				'permission'  => self::permission_for_post_type(),
			),
			'wpmcp/update-post' => array(
				'label'         => __( 'Update Post', 'wp-mcp-toolkit' ),
				'description'   => __( 'Updates an existing post — only fields you provide are changed, others are left untouched. Can update title, content, excerpt, status, meta, and taxonomy_terms. For surgical content edits (changing one block), prefer parse-blocks + update-block-content instead of replacing the entire content field. For ACF fields, use wpmcp-acf/update-post-fields. WARNING: Setting content replaces ALL post content — get the current content with get-post first.', 'wp-mcp-toolkit' ),
				'category'      => 'wpmcp-content',
				'input_schema'  => array(
					'type'       => 'object',
					'required'   => array( 'post_id' ),
					'properties' => array(
						'post_id'        => array( 'type' => 'integer' ),
						'title'          => array( 'type' => 'string' ),
						'content'        => array( 'type' => 'string' ),
						'excerpt'        => array( 'type' => 'string' ),
						'status'         => array( 'type' => 'string', 'enum' => array( 'draft', 'publish', 'pending', 'private', 'trash' ) ),
						'meta'           => array( 'type' => 'object' ),
						'taxonomy_terms' => array( 'type' => 'object' ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'        => array( 'type' => 'integer' ),
						'url'            => array( 'type' => 'string' ),
						'updated_fields' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
					),
				),
				'callback'   => 'execute_update_post',
				'readonly'   => false,
				'permission' => self::permission_for_post( 'edit_post' ),
			),
			'wpmcp/delete-post' => array(
				'label'         => __( 'Delete Post', 'wp-mcp-toolkit' ),
				'description'   => __( 'Moves a post to trash (recoverable) or permanently deletes it. Default behavior is trash — set force=true for permanent deletion. DESTRUCTIVE: permanent deletion cannot be undone. Returns the previous status so you can restore if needed.', 'wp-mcp-toolkit' ),
				'category'      => 'wpmcp-content',
				'input_schema'  => array(
					'type'       => 'object',
					'required'   => array( 'post_id' ),
					'properties' => array(
						'post_id' => array( 'type' => 'integer' ),
						'force'   => array(
							'type'        => 'boolean',
							'default'     => false,
							'description' => __( 'If true, permanently deletes. If false (default), moves to trash.', 'wp-mcp-toolkit' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'deleted'         => array( 'type' => 'boolean' ),
						'previous_status' => array( 'type' => 'string' ),
					),
				),
				'callback'    => 'execute_delete_post',
				'readonly'    => false,
				'destructive' => true,
				'idempotent'  => false,
				'permission'  => self::permission_for_post( 'delete_post' ),
			),
			'wpmcp/replace-content' => array(
				'label'         => __( 'Replace Content', 'wp-mcp-toolkit' ),
				'description'   => __( 'Finds and replaces text within a post\'s raw content (post_content), working at any block nesting depth. Use this for surgical text edits within nested blocks (e.g. paragraphs inside columns) where update-block-content cannot reach. Provide search_text to find and replace_text to substitute. By default replaces the first occurrence only — set replace_all=true to replace all occurrences. Works on raw block markup, so you can include HTML tags in search/replace values. Call get-post first to see the current content_raw.', 'wp-mcp-toolkit' ),
				'category'      => 'wpmcp-content',
				'input_schema'  => array(
					'type'       => 'object',
					'required'   => array( 'post_id', 'search_text', 'replace_text' ),
					'properties' => array(
						'post_id'      => array( 'type' => 'integer' ),
						'search_text'  => array(
							'type'        => 'string',
							'description' => __( 'The text to find in post content (exact match, case-sensitive).', 'wp-mcp-toolkit' ),
						),
						'replace_text' => array(
							'type'        => 'string',
							'description' => __( 'The replacement text.', 'wp-mcp-toolkit' ),
						),
						'replace_all'  => array(
							'type'        => 'boolean',
							'default'     => false,
							'description' => __( 'If true, replaces all occurrences. Default: first occurrence only.', 'wp-mcp-toolkit' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'success'      => array( 'type' => 'boolean' ),
						'replacements' => array( 'type' => 'integer' ),
					),
				),
				'callback'   => 'execute_replace_content',
				'readonly'   => false,
				'permission' => self::permission_for_post( 'edit_post' ),
			),
		);
	}

	public function execute_create_post( $input = array() ): array|\WP_Error {
		$input = self::normalize_input( $input );

		$post_data = array(
			'post_type'    => sanitize_key( $input['post_type'] ),
			'post_title'   => sanitize_text_field( self::decode_unicode_escapes( $input['title'] ) ),
			'post_content' => wp_kses_post( self::decode_unicode_escapes( $input['content'] ?? '' ) ),
			'post_excerpt' => sanitize_textarea_field( self::decode_unicode_escapes( $input['excerpt'] ?? '' ) ),
			'post_status'  => sanitize_key( $input['status'] ?? 'draft' ),
		);

		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$this->set_post_meta( $post_id, $input );
		$this->set_taxonomy_terms( $post_id, $input );

		return array(
			'post_id' => $post_id,
			'url'     => get_permalink( $post_id ),
		);
	}

	public function execute_update_post( $input = array() ): array|\WP_Error {
		$input   = self::normalize_input( $input );
		$post_id = absint( $input['post_id'] ?? 0 );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error( 'wpmcp_not_found', __( 'Post not found.', 'wp-mcp-toolkit' ) );
		}

		$updated_fields = array();
		$post_data      = array( 'ID' => $post_id );

		$field_map = array(
			'title'   => 'post_title',
			'content' => 'post_content',
			'excerpt' => 'post_excerpt',
			'status'  => 'post_status',
		);

		$sanitizers = array(
			'content' => 'wp_kses_post',
			'excerpt' => 'sanitize_textarea_field',
		);

		foreach ( $field_map as $input_key => $wp_key ) {
			if ( isset( $input[ $input_key ] ) ) {
				$value                = self::decode_unicode_escapes( $input[ $input_key ] );
				$sanitizer            = $sanitizers[ $input_key ] ?? 'sanitize_text_field';
				$post_data[ $wp_key ] = $sanitizer( $value );
				$updated_fields[]     = $input_key;
			}
		}

		if ( count( $post_data ) > 1 ) {
			$result = wp_update_post( $post_data, true );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		if ( $this->set_post_meta( $post_id, $input ) ) {
			$updated_fields[] = 'meta';
		}
		if ( $this->set_taxonomy_terms( $post_id, $input ) ) {
			$updated_fields[] = 'taxonomy_terms';
		}

		return array(
			'post_id'        => $post_id,
			'url'            => get_permalink( $post_id ),
			'updated_fields' => $updated_fields,
		);
	}

	public function execute_delete_post( $input = array() ): array|\WP_Error {
		$input   = self::normalize_input( $input );
		$post_id = absint( $input['post_id'] ?? 0 );
		$force   = ! empty( $input['force'] );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error( 'wpmcp_not_found', __( 'Post not found.', 'wp-mcp-toolkit' ) );
		}

		$previous_status = $post->post_status;
		$result          = $force ? wp_delete_post( $post_id, true ) : wp_trash_post( $post_id );

		if ( ! $result ) {
			return new \WP_Error( 'wpmcp_delete_failed', __( 'Failed to delete post.', 'wp-mcp-toolkit' ) );
		}

		return array(
			'deleted'         => true,
			'previous_status' => $previous_status,
		);
	}

	public function execute_replace_content( $input = array() ): array|\WP_Error {
		$input        = self::normalize_input( $input );
		$post_id      = absint( $input['post_id'] ?? 0 );
		$search_text  = $input['search_text'] ?? '';
		$replace_text = wp_kses_post( $input['replace_text'] ?? '' );
		$replace_all  = ! empty( $input['replace_all'] );
		$post         = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error( 'wpmcp_not_found', __( 'Post not found.', 'wp-mcp-toolkit' ) );
		}

		if ( empty( $search_text ) ) {
			return new \WP_Error( 'wpmcp_invalid_input', __( 'search_text cannot be empty.', 'wp-mcp-toolkit' ) );
		}

		$replace_text = self::decode_unicode_escapes( $replace_text );
		$content      = $post->post_content;

		if ( false === strpos( $content, $search_text ) ) {
			return new \WP_Error( 'wpmcp_not_found', __( 'search_text not found in post content.', 'wp-mcp-toolkit' ) );
		}

		if ( $replace_all ) {
			$count   = substr_count( $content, $search_text );
			$content = str_replace( $search_text, $replace_text, $content );
		} else {
			$pos     = strpos( $content, $search_text );
			$content = substr_replace( $content, $replace_text, $pos, strlen( $search_text ) );
			$count   = 1;
		}

		$result = wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $content,
			),
			true
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'success'      => true,
			'replacements' => $count,
		);
	}

	/**
	 * Recursively sanitizes a meta value, handling nested arrays.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return mixed The sanitized value.
	 */
	private static function sanitize_meta_value( $value ) {
		if ( is_string( $value ) ) {
			return sanitize_text_field( $value );
		}
		if ( is_array( $value ) ) {
			return array_map( array( __CLASS__, 'sanitize_meta_value' ), $value );
		}
		// Scalars (int, float, bool) are safe as-is.
		return $value;
	}

	/**
	 * Sets post meta from input if present. Returns true if any meta was set.
	 */
	private function set_post_meta( int $post_id, array $input ): bool {
		if ( empty( $input['meta'] ) || ! is_array( $input['meta'] ) ) {
			return false;
		}
		foreach ( $input['meta'] as $key => $value ) {
			$key   = sanitize_key( $key );
			$value = self::sanitize_meta_value( $value );
			update_post_meta( $post_id, $key, $value );
		}
		return true;
	}

	/**
	 * Sets taxonomy terms from input if present. Returns true if any terms were set.
	 */
	private function set_taxonomy_terms( int $post_id, array $input ): bool {
		if ( empty( $input['taxonomy_terms'] ) || ! is_array( $input['taxonomy_terms'] ) ) {
			return false;
		}
		foreach ( $input['taxonomy_terms'] as $taxonomy => $terms ) {
			wp_set_object_terms( $post_id, $terms, sanitize_key( $taxonomy ) );
		}
		return true;
	}
}
