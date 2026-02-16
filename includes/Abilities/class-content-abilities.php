<?php
/**
 * WP MCP Toolkit — Content Management Abilities.
 *
 * CRUD operations for posts, pages, and custom post types.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();

class WP_MCP_Toolkit_Content_Abilities {

	public function register( array $disabled = array() ): void {
		$abilities = array(
			'wpmcp/list-post-types',
			'wpmcp/list-posts',
			'wpmcp/get-post',
			'wpmcp/create-post',
			'wpmcp/update-post',
			'wpmcp/delete-post',
		);

		foreach ( $abilities as $name ) {
			if ( in_array( $name, $disabled, true ) ) {
				continue;
			}
			$method = 'register_' . str_replace( array( 'wpmcp/', '-' ), array( '', '_' ), $name );
			if ( method_exists( $this, $method ) ) {
				$this->$method();
			}
		}
	}

	private function register_list_post_types(): void {
		wp_register_ability(
			'wpmcp/list-post-types',
			array(
				'label'               => __( 'List Post Types', 'wp-mcp-toolkit' ),
				'description'         => __( 'Lists all registered public post types with their labels, supports, and configuration.', 'wp-mcp-toolkit' ),
				'category'            => 'wpmcp-content',
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => new \stdClass(),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
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
				'execute_callback'    => array( $this, 'execute_list_post_types' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'read' );
				},
				'meta'                => array(
					'annotations'  => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
					'show_in_rest' => true,
				),
			)
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

	private function register_list_posts(): void {
		wp_register_ability(
			'wpmcp/list-posts',
			array(
				'label'               => __( 'List Posts', 'wp-mcp-toolkit' ),
				'description'         => __( 'Lists posts of any type with pagination, filtering, and search.', 'wp-mcp-toolkit' ),
				'category'            => 'wpmcp-content',
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'post_type' ),
					'properties' => array(
						'post_type' => array(
							'type'        => 'string',
							'description' => __( 'Post type slug (e.g. post, page, case-study).', 'wp-mcp-toolkit' ),
						),
						'per_page'  => array(
							'type'    => 'integer',
							'default' => 20,
							'minimum' => 1,
							'maximum' => 100,
						),
						'page'      => array(
							'type'    => 'integer',
							'default' => 1,
							'minimum' => 1,
						),
						'search'    => array(
							'type' => 'string',
						),
						'status'    => array(
							'type'    => 'string',
							'default' => 'publish',
						),
						'orderby'   => array(
							'type'    => 'string',
							'default' => 'date',
							'enum'    => array( 'date', 'title', 'modified', 'menu_order', 'ID' ),
						),
						'order'     => array(
							'type'    => 'string',
							'default' => 'DESC',
							'enum'    => array( 'ASC', 'DESC' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
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
				'execute_callback'    => array( $this, 'execute_list_posts' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'read' );
				},
				'meta'                => array(
					'annotations'  => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
					'show_in_rest' => true,
				),
			)
		);
	}

	public function execute_list_posts( $input = array() ): array {
		$input = is_array( $input ) ? $input : (array) $input;

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

	private function register_get_post(): void {
		wp_register_ability(
			'wpmcp/get-post',
			array(
				'label'               => __( 'Get Post', 'wp-mcp-toolkit' ),
				'description'         => __( 'Gets a single post with full content (raw + rendered), excerpt, meta, featured image, status, and taxonomy terms.', 'wp-mcp-toolkit' ),
				'category'            => 'wpmcp-content',
				'input_schema'        => array(
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
				'output_schema'       => array(
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
				'execute_callback'    => array( $this, 'execute_get_post' ),
				'permission_callback' => static function ( $input ): bool {
					$input = is_array( $input ) ? $input : (array) $input;
					$post_id = absint( $input['post_id'] ?? 0 );
					return current_user_can( 'read_post', $post_id );
				},
				'meta'                => array(
					'annotations'  => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
					'show_in_rest' => true,
				),
			)
		);
	}

	public function execute_get_post( $input = array() ): array|\WP_Error {
		$input   = is_array( $input ) ? $input : (array) $input;
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

	private function register_create_post(): void {
		wp_register_ability(
			'wpmcp/create-post',
			array(
				'label'               => __( 'Create Post', 'wp-mcp-toolkit' ),
				'description'         => __( 'Creates a new post, page, or custom post type with title, content, status, meta, and taxonomy terms.', 'wp-mcp-toolkit' ),
				'category'            => 'wpmcp-content',
				'input_schema'        => array(
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
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'post_id' => array( 'type' => 'integer' ),
						'url'     => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_create_post' ),
				'permission_callback' => static function ( $input ): bool {
					$input     = is_array( $input ) ? $input : (array) $input;
					$post_type = sanitize_key( $input['post_type'] ?? 'post' );
					$pt_obj    = get_post_type_object( $post_type );
					return $pt_obj && current_user_can( $pt_obj->cap->publish_posts );
				},
				'meta'                => array(
					'annotations'  => array( 'readonly' => false, 'destructive' => false, 'idempotent' => false ),
					'show_in_rest' => true,
				),
			)
		);
	}

	public function execute_create_post( $input = array() ): array|\WP_Error {
		$input = is_array( $input ) ? $input : (array) $input;

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

		// Set meta.
		if ( ! empty( $input['meta'] ) && is_array( $input['meta'] ) ) {
			foreach ( $input['meta'] as $key => $value ) {
				update_post_meta( $post_id, sanitize_key( $key ), $value );
			}
		}

		// Set taxonomy terms.
		if ( ! empty( $input['taxonomy_terms'] ) && is_array( $input['taxonomy_terms'] ) ) {
			foreach ( $input['taxonomy_terms'] as $taxonomy => $terms ) {
				wp_set_object_terms( $post_id, $terms, sanitize_key( $taxonomy ) );
			}
		}

		return array(
			'post_id' => $post_id,
			'url'     => get_permalink( $post_id ),
		);
	}

	private function register_update_post(): void {
		wp_register_ability(
			'wpmcp/update-post',
			array(
				'label'               => __( 'Update Post', 'wp-mcp-toolkit' ),
				'description'         => __( 'Updates an existing post. Only provided fields are changed.', 'wp-mcp-toolkit' ),
				'category'            => 'wpmcp-content',
				'input_schema'        => array(
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
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'        => array( 'type' => 'integer' ),
						'url'            => array( 'type' => 'string' ),
						'updated_fields' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
					),
				),
				'execute_callback'    => array( $this, 'execute_update_post' ),
				'permission_callback' => static function ( $input ): bool {
					$input   = is_array( $input ) ? $input : (array) $input;
					$post_id = absint( $input['post_id'] ?? 0 );
					return current_user_can( 'edit_post', $post_id );
				},
				'meta'                => array(
					'annotations'  => array( 'readonly' => false, 'destructive' => false, 'idempotent' => true ),
					'show_in_rest' => true,
				),
			)
		);
	}

	public function execute_update_post( $input = array() ): array|\WP_Error {
		$input   = is_array( $input ) ? $input : (array) $input;
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

		foreach ( $field_map as $input_key => $wp_key ) {
			if ( isset( $input[ $input_key ] ) ) {
				if ( 'content' === $input_key ) {
					$post_data[ $wp_key ] = wp_kses_post( $input[ $input_key ] );
				} elseif ( 'excerpt' === $input_key ) {
					$post_data[ $wp_key ] = sanitize_textarea_field( $input[ $input_key ] );
				} else {
					$post_data[ $wp_key ] = sanitize_text_field( $input[ $input_key ] );
				}
				$updated_fields[] = $input_key;
			}
		}

		if ( count( $post_data ) > 1 ) {
			$result = wp_update_post( $post_data, true );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		// Update meta.
		if ( ! empty( $input['meta'] ) && is_array( $input['meta'] ) ) {
			foreach ( $input['meta'] as $key => $value ) {
				update_post_meta( $post_id, sanitize_key( $key ), $value );
			}
			$updated_fields[] = 'meta';
		}

		// Update taxonomy terms.
		if ( ! empty( $input['taxonomy_terms'] ) && is_array( $input['taxonomy_terms'] ) ) {
			foreach ( $input['taxonomy_terms'] as $taxonomy => $terms ) {
				wp_set_object_terms( $post_id, $terms, sanitize_key( $taxonomy ) );
			}
			$updated_fields[] = 'taxonomy_terms';
		}

		return array(
			'post_id'        => $post_id,
			'url'            => get_permalink( $post_id ),
			'updated_fields' => $updated_fields,
		);
	}

	private function register_delete_post(): void {
		wp_register_ability(
			'wpmcp/delete-post',
			array(
				'label'               => __( 'Delete Post', 'wp-mcp-toolkit' ),
				'description'         => __( 'Trashes or permanently deletes a post.', 'wp-mcp-toolkit' ),
				'category'            => 'wpmcp-content',
				'input_schema'        => array(
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
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'deleted'         => array( 'type' => 'boolean' ),
						'previous_status' => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_delete_post' ),
				'permission_callback' => static function ( $input ): bool {
					$input   = is_array( $input ) ? $input : (array) $input;
					$post_id = absint( $input['post_id'] ?? 0 );
					return current_user_can( 'delete_post', $post_id );
				},
				'meta'                => array(
					'annotations'  => array( 'readonly' => false, 'destructive' => true, 'idempotent' => false ),
					'show_in_rest' => true,
				),
			)
		);
	}

	public function execute_delete_post( $input = array() ): array|\WP_Error {
		$input   = is_array( $input ) ? $input : (array) $input;
		$post_id = absint( $input['post_id'] ?? 0 );
		$force   = ! empty( $input['force'] );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error( 'not_found', __( 'Post not found.', 'wp-mcp-toolkit' ) );
		}

		$previous_status = $post->post_status;

		if ( $force ) {
			$result = wp_delete_post( $post_id, true );
		} else {
			$result = wp_trash_post( $post_id );
		}

		if ( ! $result ) {
			return new \WP_Error( 'delete_failed', __( 'Failed to delete post.', 'wp-mcp-toolkit' ) );
		}

		return array(
			'deleted'         => true,
			'previous_status' => $previous_status,
		);
	}
}
