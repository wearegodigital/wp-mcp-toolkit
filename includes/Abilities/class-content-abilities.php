<?php
/**
 * WP MCP Toolkit — Content Management Abilities.
 *
 * CRUD operations for posts, pages, and custom post types.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();

class WP_MCP_Toolkit_Content_Abilities extends WP_MCP_Toolkit_Abstract_Abilities {

	protected function get_abilities(): array {
		return array(
			'wpmcp/list-post-types' => array(
				'label'         => __( 'List Post Types', 'wp-mcp-toolkit' ),
				'description'   => __( 'Lists all registered public post types (post, page, and custom types) with their labels, supported features, and REST API configuration. Call this first to discover what content types exist on the site before using list-posts. Returns: name (slug for use in other tools), label, singular name, whether hierarchical, archive support, REST base, and supported features (title, editor, thumbnail, etc.).', 'wp-mcp-toolkit' ),
				'category'      => 'wpmcp-content',
				'input_schema'  => self::empty_input_schema(),
				'output_schema' => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'name'         => array( 'type' => 'string' ),
							'label'        => array( 'type' => 'string' ),
							'singular'     => array( 'type' => 'string' ),
							'hierarchical' => array( 'type' => 'boolean' ),
							'has_archive'  => array( 'type' => 'boolean' ),
							'rest_base'    => array( 'type' => 'string' ),
							'supports'     => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
						),
					),
				),
				'callback'   => 'execute_list_post_types',
				'permission' => 'read',
			),
			'wpmcp/list-posts' => array(
				'label'         => __( 'List Posts', 'wp-mcp-toolkit' ),
				'description'   => __( 'Lists posts of any content type with pagination, filtering by status, and text search. Use post_type slug from list-post-types (e.g. "page", "post", "case-study"). Returns summary data (id, title, slug, status, date, url, excerpt, author) — for full content use get-post with a specific post_id. Supports ordering by date, title, modified, menu_order, or ID.', 'wp-mcp-toolkit' ),
				'category'      => 'wpmcp-content',
				'input_schema'  => array(
					'type'       => 'object',
					'required'   => array( 'post_type' ),
					'properties' => array(
						'post_type' => array(
							'type'        => 'string',
							'description' => __( 'Post type slug (e.g. post, page, case-study).', 'wp-mcp-toolkit' ),
						),
						'per_page'  => array( 'type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 100 ),
						'page'      => array( 'type' => 'integer', 'default' => 1, 'minimum' => 1 ),
						'search'    => array( 'type' => 'string' ),
						'status'    => array( 'type' => 'string', 'default' => 'publish' ),
						'orderby'   => array( 'type' => 'string', 'default' => 'date', 'enum' => array( 'date', 'title', 'modified', 'menu_order', 'ID' ) ),
						'order'     => array( 'type' => 'string', 'default' => 'DESC', 'enum' => array( 'ASC', 'DESC' ) ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'posts'       => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'id'       => array( 'type' => 'integer' ),
									'title'    => array( 'type' => 'string' ),
									'slug'     => array( 'type' => 'string' ),
									'status'   => array( 'type' => 'string' ),
									'date'     => array( 'type' => 'string' ),
									'modified' => array( 'type' => 'string' ),
									'url'      => array( 'type' => 'string' ),
									'excerpt'  => array( 'type' => 'string' ),
									'author'   => array( 'type' => 'string' ),
								),
							),
						),
						'total'       => array( 'type' => 'integer' ),
						'total_pages' => array( 'type' => 'integer' ),
					),
				),
				'callback'   => 'execute_list_posts',
				'permission' => 'read',
			),
			'wpmcp/get-post' => array(
				'label'         => __( 'Get Post', 'wp-mcp-toolkit' ),
				'description'   => __( 'Gets a single post by ID with full details: raw block content (content_raw — the Gutenberg block markup), rendered HTML (content_rendered), excerpt, all public post meta, featured image URL, page template, and taxonomy terms. Use content_raw to understand block structure before editing. For block-level editing, use parse-blocks instead which returns structured block data with indices.', 'wp-mcp-toolkit' ),
				'category'      => 'wpmcp-content',
				'input_schema'  => array(
					'type'       => 'object',
					'required'   => array( 'post_id' ),
					'properties' => array(
						'post_id' => array(
							'type'        => 'integer',
							'description' => __( 'The post ID to retrieve.', 'wp-mcp-toolkit' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id'                 => array( 'type' => 'integer' ),
						'title'              => array( 'type' => 'string' ),
						'slug'               => array( 'type' => 'string' ),
						'content_raw'        => array( 'type' => 'string' ),
						'content_rendered'   => array( 'type' => 'string' ),
						'excerpt'            => array( 'type' => 'string' ),
						'status'             => array( 'type' => 'string' ),
						'post_type'          => array( 'type' => 'string' ),
						'date'               => array( 'type' => 'string' ),
						'modified'           => array( 'type' => 'string' ),
						'url'                => array( 'type' => 'string' ),
						'author'             => array( 'type' => 'string' ),
						'featured_image_url' => array( 'type' => 'string' ),
						'template'           => array( 'type' => 'string' ),
						'meta'               => array( 'type' => 'object' ),
						'taxonomy_terms'     => array( 'type' => 'object' ),
					),
				),
				'callback'   => 'execute_get_post',
				'permission' => static function ( $input ): bool {
					$input   = is_array( $input ) ? $input : (array) $input;
					$post_id = absint( $input['post_id'] ?? 0 );
					return current_user_can( 'read_post', $post_id );
				},
			),
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
				'permission'  => static function ( $input ): bool {
					$input     = is_array( $input ) ? $input : (array) $input;
					$post_type = sanitize_key( $input['post_type'] ?? 'post' );
					$pt_obj    = get_post_type_object( $post_type );
					return $pt_obj && current_user_can( $pt_obj->cap->publish_posts );
				},
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
				'permission' => static function ( $input ): bool {
					$input   = is_array( $input ) ? $input : (array) $input;
					$post_id = absint( $input['post_id'] ?? 0 );
					return current_user_can( 'edit_post', $post_id );
				},
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
				'permission'  => static function ( $input ): bool {
					$input   = is_array( $input ) ? $input : (array) $input;
					$post_id = absint( $input['post_id'] ?? 0 );
					return current_user_can( 'delete_post', $post_id );
				},
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
						'success'       => array( 'type' => 'boolean' ),
						'replacements'  => array( 'type' => 'integer' ),
					),
				),
				'callback'   => 'execute_replace_content',
				'readonly'   => false,
				'permission' => static function ( $input ): bool {
					$input   = is_array( $input ) ? $input : (array) $input;
					$post_id = absint( $input['post_id'] ?? 0 );
					return current_user_can( 'edit_post', $post_id );
				},
			),
		);
	}

	public function execute_list_post_types( $input = array() ): array {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$result     = array();

		foreach ( $post_types as $pt ) {
			$supports = get_all_post_type_supports( $pt->name );
			$result[] = array(
				'name'         => $pt->name,
				'label'        => $pt->label,
				'singular'     => $pt->labels->singular_name,
				'hierarchical' => $pt->hierarchical,
				'has_archive'  => (bool) $pt->has_archive,
				'rest_base'    => $pt->rest_base ?: $pt->name,
				'supports'     => array_keys( array_filter( $supports ) ),
			);
		}

		return $result;
	}

	public function execute_list_posts( $input = array() ): array {
		$input = self::normalize_input( $input );

		$query_args = array(
			'post_type'      => sanitize_key( $input['post_type'] ?? 'post' ),
			'posts_per_page' => absint( $input['per_page'] ?? 20 ),
			'paged'          => absint( $input['page'] ?? 1 ),
			'post_status'    => sanitize_key( $input['status'] ?? 'publish' ),
			'orderby'        => sanitize_key( $input['orderby'] ?? 'date' ),
			'order'          => strtoupper( sanitize_key( $input['order'] ?? 'DESC' ) ),
		);

		if ( ! empty( $input['search'] ) ) {
			$query_args['s'] = sanitize_text_field( $input['search'] );
		}

		$query = new \WP_Query( $query_args );
		$posts = array();

		foreach ( $query->posts as $post ) {
			$author  = get_userdata( $post->post_author );
			$posts[] = array(
				'id'       => $post->ID,
				'title'    => $post->post_title,
				'slug'     => $post->post_name,
				'status'   => $post->post_status,
				'date'     => $post->post_date,
				'modified' => $post->post_modified,
				'url'      => get_permalink( $post->ID ),
				'excerpt'  => wp_trim_words( $post->post_excerpt ?: $post->post_content, 30 ),
				'author'   => $author ? $author->display_name : '',
			);
		}

		return array(
			'posts'       => $posts,
			'total'       => (int) $query->found_posts,
			'total_pages' => (int) $query->max_num_pages,
		);
	}

	public function execute_get_post( $input = array() ): array|\WP_Error {
		$input   = self::normalize_input( $input );
		$post_id = absint( $input['post_id'] ?? 0 );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error( 'not_found', __( 'Post not found.', 'wp-mcp-toolkit' ) );
		}

		$author = get_userdata( $post->post_author );

		// Featured image.
		$thumb_id  = get_post_thumbnail_id( $post_id );
		$thumb_url = $thumb_id ? wp_get_attachment_url( $thumb_id ) : '';

		// Taxonomy terms.
		$taxonomies    = get_object_taxonomies( $post->post_type );
		$taxonomy_terms = array();
		foreach ( $taxonomies as $tax ) {
			$terms = wp_get_post_terms( $post_id, $tax, array( 'fields' => 'names' ) );
			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				$taxonomy_terms[ $tax ] = $terms;
			}
		}

		// Post meta (public only, skip internal _prefixed keys).
		$all_meta = get_post_meta( $post_id );
		$meta     = array();
		foreach ( $all_meta as $key => $values ) {
			if ( str_starts_with( $key, '_' ) ) {
				continue;
			}
			$meta[ $key ] = count( $values ) === 1 ? $values[0] : $values;
		}

		return array(
			'id'                 => $post->ID,
			'title'              => $post->post_title,
			'slug'               => $post->post_name,
			'content_raw'        => $post->post_content,
			'content_rendered'   => apply_filters( 'the_content', $post->post_content ),
			'excerpt'            => $post->post_excerpt,
			'status'             => $post->post_status,
			'post_type'          => $post->post_type,
			'date'               => $post->post_date,
			'modified'           => $post->post_modified,
			'url'                => get_permalink( $post_id ),
			'author'             => $author ? $author->display_name : '',
			'featured_image_url' => $thumb_url,
			'template'           => get_page_template_slug( $post_id ) ?: '',
			'meta'               => $meta,
			'taxonomy_terms'     => $taxonomy_terms,
		);
	}

	public function execute_create_post( $input = array() ): array|\WP_Error {
		$input = self::normalize_input( $input );

		$post_data = array(
			'post_type'    => sanitize_key( $input['post_type'] ),
			'post_title'   => sanitize_text_field( $input['title'] ),
			'post_content' => wp_kses_post( $input['content'] ?? '' ),
			'post_excerpt' => sanitize_textarea_field( $input['excerpt'] ?? '' ),
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
			return new \WP_Error( 'not_found', __( 'Post not found.', 'wp-mcp-toolkit' ) );
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
				$sanitizer = $sanitizers[ $input_key ] ?? 'sanitize_text_field';
				$post_data[ $wp_key ] = $sanitizer( $input[ $input_key ] );
				$updated_fields[] = $input_key;
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
			return new \WP_Error( 'not_found', __( 'Post not found.', 'wp-mcp-toolkit' ) );
		}

		$previous_status = $post->post_status;
		$result = $force ? wp_delete_post( $post_id, true ) : wp_trash_post( $post_id );

		if ( ! $result ) {
			return new \WP_Error( 'delete_failed', __( 'Failed to delete post.', 'wp-mcp-toolkit' ) );
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
		$replace_text = $input['replace_text'] ?? '';
		$replace_all  = ! empty( $input['replace_all'] );
		$post         = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error( 'not_found', __( 'Post not found.', 'wp-mcp-toolkit' ) );
		}

		if ( empty( $search_text ) ) {
			return new \WP_Error( 'invalid_input', __( 'search_text cannot be empty.', 'wp-mcp-toolkit' ) );
		}

		$content = $post->post_content;

		if ( false === strpos( $content, $search_text ) ) {
			return new \WP_Error( 'not_found', __( 'search_text not found in post content.', 'wp-mcp-toolkit' ) );
		}

		if ( $replace_all ) {
			$count   = substr_count( $content, $search_text );
			$content = str_replace( $search_text, $replace_text, $content );
		} else {
			$pos = strpos( $content, $search_text );
			$content = substr_replace( $content, $replace_text, $pos, strlen( $search_text ) );
			$count = 1;
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
	 * Sets post meta from input if present. Returns true if any meta was set.
	 */
	private function set_post_meta( int $post_id, array $input ): bool {
		if ( empty( $input['meta'] ) || ! is_array( $input['meta'] ) ) {
			return false;
		}
		foreach ( $input['meta'] as $key => $value ) {
			update_post_meta( $post_id, sanitize_key( $key ), $value );
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
