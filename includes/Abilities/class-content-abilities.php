<?php
/**
 * WP MCP Toolkit — Content Read Abilities.
 *
 * Read-only operations for posts, pages, and custom post types.
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
				'permission' => self::permission_for_post( 'read_post' ),
			),
			'wpmcp/get-page-tree' => array(
				'label'         => __( 'Get Page Tree', 'wp-mcp-toolkit' ),
				'description'   => __( 'Returns a hierarchical tree of all pages (or any hierarchical post type), showing parent-child relationships. Useful for understanding site structure before creating or updating pages. Each node includes id, title, slug, status, and url.', 'wp-mcp-toolkit' ),
				'category'      => 'wpmcp-content',
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'post_type' => array(
							'type'        => 'string',
							'default'     => 'page',
							'description' => __( 'Post type slug. Must be hierarchical (e.g. page).', 'wp-mcp-toolkit' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'id'       => array( 'type' => 'integer' ),
							'title'    => array( 'type' => 'string' ),
							'slug'     => array( 'type' => 'string' ),
							'status'   => array( 'type' => 'string' ),
							'url'      => array( 'type' => 'string' ),
							'children' => array( 'type' => 'array' ),
						),
					),
				),
				'callback'   => 'execute_get_page_tree',
				'permission' => 'read',
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

		// Prime user cache to avoid N+1 get_userdata() calls.
		$author_ids = wp_list_pluck( $query->posts, 'post_author' );
		if ( ! empty( $author_ids ) ) {
			cache_users( array_unique( array_map( 'absint', $author_ids ) ) );
		}

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
			return new \WP_Error( 'wpmcp_not_found', __( 'Post not found.', 'wp-mcp-toolkit' ) );
		}

		$author = get_userdata( $post->post_author );

		// Featured image.
		$thumb_id  = get_post_thumbnail_id( $post_id );
		$thumb_url = $thumb_id ? wp_get_attachment_url( $thumb_id ) : '';

		// Taxonomy terms.
		$taxonomies     = get_object_taxonomies( $post->post_type );
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
			// Note: apply_filters('the_content') runs ALL registered content filters including
			// shortcodes, oEmbed, and third-party plugin filters. In an API/MCP context this
			// may produce large output with embedded iframes/scripts or trigger side effects.
			// The raw post_content is always available as 'content_raw' for safer processing.
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

	public function execute_get_page_tree( $input = array() ): array|\WP_Error {
		$input     = self::normalize_input( $input );
		$post_type = sanitize_key( $input['post_type'] ?? 'page' );

		$pt = get_post_type_object( $post_type );
		if ( ! $pt ) {
			return new \WP_Error( 'wpmcp_not_found', __( 'Post type not found.', 'wp-mcp-toolkit' ) );
		}

		$posts = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => array( 'publish', 'draft', 'private' ),
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			)
		);

		$nodes = array();
		foreach ( $posts as $post ) {
			$nodes[ $post->ID ] = array(
				'id'       => $post->ID,
				'title'    => $post->post_title,
				'slug'     => $post->post_name,
				'status'   => $post->post_status,
				'url'      => get_permalink( $post->ID ),
				'parent'   => $post->post_parent,
				'children' => array(),
			);
		}

		$tree = array();
		foreach ( $nodes as $id => &$node ) {
			$parent_id = $node['parent'];
			unset( $node['parent'] );
			if ( $parent_id && isset( $nodes[ $parent_id ] ) ) {
				$nodes[ $parent_id ]['children'][] = &$node;
			} else {
				$tree[] = &$node;
			}
		}
		unset( $node );

		return $tree;
	}
}
