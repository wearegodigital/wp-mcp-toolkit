<?php
/**
 * WP MCP Toolkit — Media Library Abilities.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();

class WP_MCP_Toolkit_Media_Abilities extends WP_MCP_Toolkit_Abstract_Abilities {

	protected function get_abilities(): array {
		return array(
			'wpmcp/list-media' => array(
				'label'         => __( 'List Media', 'wp-mcp-toolkit' ),
				'description'   => __( 'Lists media library items (images, documents, videos, etc.) with optional filtering. Use mime_type to filter by type: "image" (all images), "image/jpeg" (specific format), "application/pdf" (PDFs), "video" (all video). Use search to find by filename or title. Returns id, title, url, mime_type, and date. Use get-media with a specific media_id for full details including dimensions and alt text.', 'wp-mcp-toolkit' ),
				'category'      => 'wpmcp-media',
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'mime_type' => array(
							'type'        => 'string',
							'description' => __( 'Filter by MIME type (e.g. image, image/jpeg, application/pdf).', 'wp-mcp-toolkit' ),
						),
						'search'   => array( 'type' => 'string' ),
						'per_page' => array( 'type' => 'integer', 'default' => 20 ),
						'page'     => array( 'type' => 'integer', 'default' => 1 ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'media' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'id'        => array( 'type' => 'integer' ),
									'title'     => array( 'type' => 'string' ),
									'url'       => array( 'type' => 'string' ),
									'mime_type' => array( 'type' => 'string' ),
									'date'      => array( 'type' => 'string' ),
								),
							),
						),
						'total' => array( 'type' => 'integer' ),
					),
				),
				'callback'   => 'execute_list_media',
				'permission' => 'upload_files',
			),
			'wpmcp/get-media' => array(
				'label'         => __( 'Get Media', 'wp-mcp-toolkit' ),
				'description'   => __( 'Gets full details for a single media item by ID. Returns: url (full-size), mime_type, title, caption, alt_text (for accessibility), description, width/height (for images), file_size in bytes, and date uploaded. Use the media ID from list-media results. The url returned is the direct file URL suitable for use in img tags or downloads.', 'wp-mcp-toolkit' ),
				'category'      => 'wpmcp-media',
				'input_schema'  => array(
					'type'       => 'object',
					'required'   => array( 'media_id' ),
					'properties' => array(
						'media_id' => array( 'type' => 'integer' ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id'          => array( 'type' => 'integer' ),
						'title'       => array( 'type' => 'string' ),
						'url'         => array( 'type' => 'string' ),
						'mime_type'   => array( 'type' => 'string' ),
						'alt_text'    => array( 'type' => 'string' ),
						'caption'     => array( 'type' => 'string' ),
						'description' => array( 'type' => 'string' ),
						'width'       => array( 'type' => 'integer' ),
						'height'      => array( 'type' => 'integer' ),
						'file_size'   => array( 'type' => 'integer' ),
						'sizes'       => array( 'type' => 'object' ),
					),
				),
				'callback'   => 'execute_get_media',
				'permission' => 'upload_files',
			),
			'wpmcp/upload-media' => array(
				'label'         => __( 'Upload Media', 'wp-mcp-toolkit' ),
				'description'   => __( 'Downloads an image from a URL and adds it to the WordPress media library (sideload). Provide the source url of the image to download. Optionally set title, alt_text (for accessibility/SEO), caption, and description. Optionally attach to a post_id to associate the media with a specific post. Returns the new attachment id, url, and all available sizes. Supports common image formats (JPEG, PNG, GIF, WebP, SVG) and other file types WordPress allows. The file is downloaded server-side — the URL must be publicly accessible.', 'wp-mcp-toolkit' ),
				'category'      => 'wpmcp-media',
				'input_schema'  => array(
					'type'       => 'object',
					'required'   => array( 'url' ),
					'properties' => array(
						'url'         => array(
							'type'        => 'string',
							'description' => __( 'Public URL of the file to download and add to the media library.', 'wp-mcp-toolkit' ),
						),
						'title'       => array(
							'type'        => 'string',
							'description' => __( 'Title for the media item. Defaults to the filename.', 'wp-mcp-toolkit' ),
						),
						'alt_text'    => array(
							'type'        => 'string',
							'description' => __( 'Alt text for accessibility and SEO.', 'wp-mcp-toolkit' ),
						),
						'caption'     => array( 'type' => 'string' ),
						'description' => array( 'type' => 'string' ),
						'post_id'     => array(
							'type'        => 'integer',
							'description' => __( 'Optional post ID to attach this media to.', 'wp-mcp-toolkit' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id'       => array( 'type' => 'integer' ),
						'title'    => array( 'type' => 'string' ),
						'url'      => array( 'type' => 'string' ),
						'mime_type' => array( 'type' => 'string' ),
						'width'    => array( 'type' => 'integer' ),
						'height'   => array( 'type' => 'integer' ),
						'sizes'    => array( 'type' => 'object' ),
					),
				),
				'callback'    => 'execute_upload_media',
				'permission'  => 'upload_files',
				'readonly'    => false,
				'idempotent'  => false,
			),
		);
	}

	public function execute_list_media( $input = array() ): array {
		$input = self::normalize_input( $input );

		$args = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => absint( $input['per_page'] ?? 20 ),
			'paged'          => absint( $input['page'] ?? 1 ),
		);

		if ( ! empty( $input['mime_type'] ) ) {
			$args['post_mime_type'] = sanitize_mime_type( $input['mime_type'] );
		}

		if ( ! empty( $input['search'] ) ) {
			$args['s'] = sanitize_text_field( $input['search'] );
		}

		$query = new \WP_Query( $args );
		$items = array();

		foreach ( $query->posts as $attachment ) {
			$items[] = array(
				'id'        => $attachment->ID,
				'title'     => $attachment->post_title,
				'url'       => wp_get_attachment_url( $attachment->ID ),
				'mime_type' => $attachment->post_mime_type,
				'date'      => $attachment->post_date,
			);
		}

		return array(
			'media' => $items,
			'total' => (int) $query->found_posts,
		);
	}

	public function execute_get_media( $input = array() ): array|\WP_Error {
		$input    = self::normalize_input( $input );
		$media_id = absint( $input['media_id'] ?? 0 );
		$post     = get_post( $media_id );

		if ( ! $post || 'attachment' !== $post->post_type ) {
			return new \WP_Error( 'wpmcp_not_found', __( 'Media item not found.', 'wp-mcp-toolkit' ) );
		}

		$metadata = wp_get_attachment_metadata( $media_id );
		$sizes    = array();

		if ( ! empty( $metadata['sizes'] ) ) {
			foreach ( $metadata['sizes'] as $size_name => $size_data ) {
				$sizes[ $size_name ] = array(
					'width'  => $size_data['width'],
					'height' => $size_data['height'],
					'url'    => wp_get_attachment_image_url( $media_id, $size_name ),
				);
			}
		}

		return array(
			'id'          => $post->ID,
			'title'       => $post->post_title,
			'url'         => wp_get_attachment_url( $media_id ),
			'mime_type'   => $post->post_mime_type,
			'alt_text'    => get_post_meta( $media_id, '_wp_attachment_image_alt', true ) ?: '',
			'caption'     => $post->post_excerpt,
			'description' => $post->post_content,
			'width'       => (int) ( $metadata['width'] ?? 0 ),
			'height'      => (int) ( $metadata['height'] ?? 0 ),
			'file_size'   => (int) ( $metadata['filesize'] ?? 0 ),
			'sizes'       => $sizes,
		);
	}

	public function execute_upload_media( $input = array() ): array|\WP_Error {
		$input = self::normalize_input( $input );
		$url   = esc_url_raw( $input['url'] ?? '' );

		if ( empty( $url ) || ! wp_http_validate_url( $url ) ) {
			return new \WP_Error( 'wpmcp_invalid_url', __( 'A valid, publicly accessible URL is required.', 'wp-mcp-toolkit' ) );
		}

		// Load required WordPress media functions.
		if ( ! function_exists( 'media_sideload_image' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		$parent_post_id = absint( $input['post_id'] ?? 0 );

		// Sideload the file — returns the attachment ID.
		$attachment_id = media_sideload_image( $url, $parent_post_id, $input['title'] ?? null, 'id' );

		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		// Set optional metadata.
		if ( ! empty( $input['alt_text'] ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $input['alt_text'] ) );
		}

		$update_data = array( 'ID' => $attachment_id );
		if ( ! empty( $input['title'] ) ) {
			$update_data['post_title'] = sanitize_text_field( $input['title'] );
		}
		if ( ! empty( $input['caption'] ) ) {
			$update_data['post_excerpt'] = sanitize_textarea_field( $input['caption'] );
		}
		if ( ! empty( $input['description'] ) ) {
			$update_data['post_content'] = sanitize_textarea_field( $input['description'] );
		}
		if ( count( $update_data ) > 1 ) {
			wp_update_post( $update_data );
		}

		// Build response.
		$metadata = wp_get_attachment_metadata( $attachment_id );
		$sizes    = array();

		if ( ! empty( $metadata['sizes'] ) ) {
			foreach ( $metadata['sizes'] as $size_name => $size_data ) {
				$sizes[ $size_name ] = array(
					'width'  => $size_data['width'],
					'height' => $size_data['height'],
					'url'    => wp_get_attachment_image_url( $attachment_id, $size_name ),
				);
			}
		}

		$post = get_post( $attachment_id );

		return array(
			'id'        => $attachment_id,
			'title'     => $post->post_title,
			'url'       => wp_get_attachment_url( $attachment_id ),
			'mime_type' => $post->post_mime_type,
			'width'     => (int) ( $metadata['width'] ?? 0 ),
			'height'    => (int) ( $metadata['height'] ?? 0 ),
			'sizes'     => $sizes,
		);
	}
}
