<?php
/**
 * WP MCP Toolkit — Yoast SEO Abilities.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();

class WP_MCP_Toolkit_Yoast_Abilities extends WP_MCP_Toolkit_Abstract_Abilities {

	protected function get_abilities(): array {
		return array(
			'wpmcp-yoast/get-post-seo'    => array(
				'label'         => __( 'Get Post SEO Data', 'wp-mcp-toolkit' ),
				'description'   => __( 'Retrieves Yoast SEO metadata for a specific post. Returns seo_title, meta_description, focus_keyword, canonical_url, Open Graph fields (og_title, og_description, og_image), schema_type, and analysis scores (seo_score 0-100, readability_score 0-100). Also includes post_title and post_url for context. Use this to audit SEO status before making changes with update-post-seo.', 'wp-mcp-toolkit' ),
				'category'      => 'wpmcp-yoast',
				'input_schema'  => array(
					'type'       => 'object',
					'required'   => array( 'post_id' ),
					'properties' => array(
						'post_id' => array(
							'type'        => 'integer',
							'description' => __( 'The post ID.', 'wp-mcp-toolkit' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'           => array( 'type' => 'integer' ),
						'post_title'        => array( 'type' => 'string' ),
						'post_url'          => array( 'type' => 'string' ),
						'seo_title'         => array( 'type' => 'string' ),
						'meta_description'  => array( 'type' => 'string' ),
						'focus_keyword'     => array( 'type' => 'string' ),
						'canonical_url'     => array( 'type' => 'string' ),
						'og_title'          => array( 'type' => 'string' ),
						'og_description'    => array( 'type' => 'string' ),
						'og_image'          => array( 'type' => 'string' ),
						'schema_type'       => array( 'type' => 'string' ),
						'seo_score'         => array( 'type' => 'integer' ),
						'readability_score' => array( 'type' => 'integer' ),
					),
				),
				'callback'   => 'execute_get_post_seo',
				'permission' => 'read',
			),
			'wpmcp-yoast/update-post-seo' => array(
				'label'         => __( 'Update Post SEO Data', 'wp-mcp-toolkit' ),
				'description'   => __( 'Updates Yoast SEO metadata on a post. Only fields you provide are changed — others are left untouched. Supports: seo_title, meta_description, focus_keyword, canonical_url, og_title, og_description, og_image, schema_type. Use get-post-seo first to see current values. Returns the list of updated fields.', 'wp-mcp-toolkit' ),
				'category'      => 'wpmcp-yoast',
				'input_schema'  => array(
					'type'       => 'object',
					'required'   => array( 'post_id' ),
					'properties' => array(
						'post_id'          => array( 'type' => 'integer', 'description' => __( 'The post ID.', 'wp-mcp-toolkit' ) ),
						'seo_title'        => array( 'type' => 'string', 'description' => __( 'SEO title (appears in search results).', 'wp-mcp-toolkit' ) ),
						'meta_description' => array( 'type' => 'string', 'description' => __( 'Meta description (appears in search results).', 'wp-mcp-toolkit' ) ),
						'focus_keyword'    => array( 'type' => 'string', 'description' => __( 'Focus keyword for SEO analysis.', 'wp-mcp-toolkit' ) ),
						'canonical_url'    => array( 'type' => 'string', 'description' => __( 'Canonical URL.', 'wp-mcp-toolkit' ) ),
						'og_title'         => array( 'type' => 'string', 'description' => __( 'Open Graph title (social sharing).', 'wp-mcp-toolkit' ) ),
						'og_description'   => array( 'type' => 'string', 'description' => __( 'Open Graph description (social sharing).', 'wp-mcp-toolkit' ) ),
						'og_image'         => array( 'type' => 'string', 'description' => __( 'Open Graph image URL (social sharing).', 'wp-mcp-toolkit' ) ),
						'schema_type'      => array( 'type' => 'string', 'description' => __( 'Schema.org page type.', 'wp-mcp-toolkit' ) ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'        => array( 'type' => 'integer' ),
						'updated_fields' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
					),
				),
				'callback'   => 'execute_update_post_seo',
				'readonly'   => false,
				'permission' => self::permission_for_post( 'edit_post' ),
			),
			'wpmcp-yoast/get-seo-overview' => array(
				'label'         => __( 'Get SEO Overview', 'wp-mcp-toolkit' ),
				'description'   => __( 'Site-wide SEO summary for a post type. Shows total_posts, how many have seo_title, meta_description, and focus_keyword set, plus a missing_seo array listing posts that are missing any of these fields (with their scores). Use this to identify SEO gaps across the site. Defaults to "post" type, up to 200 posts.', 'wp-mcp-toolkit' ),
				'category'      => 'wpmcp-yoast',
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'post_type' => array( 'type' => 'string', 'default' => 'post', 'description' => __( 'Post type slug.', 'wp-mcp-toolkit' ) ),
						'per_page'  => array( 'type' => 'integer', 'default' => 50, 'minimum' => 1, 'maximum' => 200 ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'total_posts'              => array( 'type' => 'integer' ),
						'posts_with_seo_title'     => array( 'type' => 'integer' ),
						'posts_with_meta_desc'     => array( 'type' => 'integer' ),
						'posts_with_focus_keyword' => array( 'type' => 'integer' ),
						'missing_seo'              => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'id'                => array( 'type' => 'integer' ),
									'title'             => array( 'type' => 'string' ),
									'url'               => array( 'type' => 'string' ),
									'has_seo_title'     => array( 'type' => 'boolean' ),
									'has_meta_desc'     => array( 'type' => 'boolean' ),
									'has_focus_keyword' => array( 'type' => 'boolean' ),
									'seo_score'         => array( 'type' => 'integer' ),
									'readability_score' => array( 'type' => 'integer' ),
								),
							),
						),
					),
				),
				'callback'   => 'execute_get_seo_overview',
				'permission' => 'read',
			),
		);
	}

	public function execute_get_post_seo( $input = array() ): array|\WP_Error {
		$input   = self::normalize_input( $input );
		$post_id = absint( $input['post_id'] ?? 0 );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error( 'wpmcp_not_found', __( 'Post not found.', 'wp-mcp-toolkit' ) );
		}

		return array(
			'post_id'           => $post_id,
			'post_title'        => $post->post_title,
			'post_url'          => get_permalink( $post_id ),
			'seo_title'         => (string) get_post_meta( $post_id, '_yoast_wpseo_title', true ),
			'meta_description'  => (string) get_post_meta( $post_id, '_yoast_wpseo_metadesc', true ),
			'focus_keyword'     => (string) get_post_meta( $post_id, '_yoast_wpseo_focuskw', true ),
			'canonical_url'     => (string) get_post_meta( $post_id, '_yoast_wpseo_canonical', true ),
			'og_title'          => (string) get_post_meta( $post_id, '_yoast_wpseo_opengraph-title', true ),
			'og_description'    => (string) get_post_meta( $post_id, '_yoast_wpseo_opengraph-description', true ),
			'og_image'          => (string) get_post_meta( $post_id, '_yoast_wpseo_opengraph-image', true ),
			'schema_type'       => (string) get_post_meta( $post_id, '_yoast_wpseo_schema_page_type', true ),
			'seo_score'         => (int) get_post_meta( $post_id, '_yoast_wpseo_linkdex', true ),
			'readability_score' => (int) get_post_meta( $post_id, '_yoast_wpseo_content_score', true ),
		);
	}

	public function execute_update_post_seo( $input = array() ): array|\WP_Error {
		$input   = self::normalize_input( $input );
		$post_id = absint( $input['post_id'] ?? 0 );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error( 'wpmcp_not_found', __( 'Post not found.', 'wp-mcp-toolkit' ) );
		}

		$field_map = array(
			'seo_title'        => '_yoast_wpseo_title',
			'meta_description' => '_yoast_wpseo_metadesc',
			'focus_keyword'    => '_yoast_wpseo_focuskw',
			'canonical_url'    => '_yoast_wpseo_canonical',
			'og_title'         => '_yoast_wpseo_opengraph-title',
			'og_description'   => '_yoast_wpseo_opengraph-description',
			'og_image'         => '_yoast_wpseo_opengraph-image',
			'schema_type'      => '_yoast_wpseo_schema_page_type',
		);

		$updated_fields = array();
		$url_fields     = array( 'canonical_url', 'og_image' );

		foreach ( $field_map as $input_key => $meta_key ) {
			if ( isset( $input[ $input_key ] ) ) {
				$value = in_array( $input_key, $url_fields, true ) ? esc_url_raw( $input[ $input_key ] ) : sanitize_text_field( $input[ $input_key ] );
				update_post_meta( $post_id, $meta_key, $value );
				$updated_fields[] = $input_key;
			}
		}

		return array(
			'post_id'        => $post_id,
			'updated_fields' => $updated_fields,
		);
	}

	public function execute_get_seo_overview( $input = array() ): array {
		$input     = self::normalize_input( $input );
		$post_type = sanitize_key( $input['post_type'] ?? 'post' );
		$per_page  = min( absint( $input['per_page'] ?? 50 ), 200 );

		$query = new \WP_Query( array(
			'post_type'      => $post_type,
			'posts_per_page' => $per_page,
			'post_status'    => 'publish',
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );

		// Prime post meta cache to avoid N+1 get_post_meta() calls.
		$post_ids = wp_list_pluck( $query->posts, 'ID' );
		if ( ! empty( $post_ids ) ) {
			update_postmeta_cache( $post_ids );
		}

		$posts_with_seo_title    = 0;
		$posts_with_meta_desc    = 0;
		$posts_with_focus_keyword = 0;
		$missing_seo             = array();

		foreach ( $query->posts as $post ) {
			$seo_title = get_post_meta( $post->ID, '_yoast_wpseo_title', true );
			$meta_desc = get_post_meta( $post->ID, '_yoast_wpseo_metadesc', true );
			$focus_kw  = get_post_meta( $post->ID, '_yoast_wpseo_focuskw', true );

			$has_title = ! empty( $seo_title );
			$has_desc  = ! empty( $meta_desc );
			$has_kw    = ! empty( $focus_kw );

			if ( $has_title ) { $posts_with_seo_title++; }
			if ( $has_desc )  { $posts_with_meta_desc++; }
			if ( $has_kw )    { $posts_with_focus_keyword++; }

			if ( ! $has_title || ! $has_desc || ! $has_kw ) {
				$missing_seo[] = array(
					'id'                => $post->ID,
					'title'             => $post->post_title,
					'url'               => get_permalink( $post->ID ),
					'has_seo_title'     => $has_title,
					'has_meta_desc'     => $has_desc,
					'has_focus_keyword' => $has_kw,
					'seo_score'         => (int) get_post_meta( $post->ID, '_yoast_wpseo_linkdex', true ),
					'readability_score' => (int) get_post_meta( $post->ID, '_yoast_wpseo_content_score', true ),
				);
			}
		}

		return array(
			'total_posts'              => (int) $query->found_posts,
			'posts_with_seo_title'     => $posts_with_seo_title,
			'posts_with_meta_desc'     => $posts_with_meta_desc,
			'posts_with_focus_keyword' => $posts_with_focus_keyword,
			'missing_seo'              => $missing_seo,
		);
	}
}
