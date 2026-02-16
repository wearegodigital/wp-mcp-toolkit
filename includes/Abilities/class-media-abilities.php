<?php
/**
 * WP MCP Toolkit — Media Library Abilities.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();

class WP_MCP_Toolkit_Media_Abilities {

	public function register( array $disabled = array() ): void {
		if ( ! in_array( 'wpmcp/list-media', $disabled, true ) ) {
			$this->register_list_media();
		}
		if ( ! in_array( 'wpmcp/get-media', $disabled, true ) ) {
			$this->register_get_media();
		}
	}

	private function register_list_media(): void {
		wp_register_ability(
			'wpmcp/list-media',
			array(
				'label'               => __( 'List Media', 'wp-mcp-toolkit' ),
				'description'         => __( 'Lists media items with filtering by MIME type and search.', 'wp-mcp-toolkit' ),
				'category'            => 'wpmcp-media',
				'input_schema'        => array(
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
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'items' => array(
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
				'execute_callback'    => array( $this, 'execute_list_media' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'upload_files' );
				},
				'meta'                => array(
					'annotations'  => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
					'show_in_rest' => true,
				),
			)
		);
	}

	public function execute_list_media( $input = array() ): array {
		$input = is_array( $input ) ? $input : (array) $input;

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
			'items' => $items,
			'total' => (int) $query->found_posts,
		);
	}

	private function register_get_media(): void {
		wp_register_ability(
			'wpmcp/get-media',
			array(
				'label'               => __( 'Get Media', 'wp-mcp-toolkit' ),
				'description'         => __( 'Gets detailed info for a media item including URL, dimensions, alt text, and caption.', 'wp-mcp-toolkit' ),
				'category'            => 'wpmcp-media',
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'media_id' ),
					'properties' => array(
						'media_id' => array( 'type' => 'integer' ),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
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
				'execute_callback'    => array( $this, 'execute_get_media' ),
				'permission_callback' => static function ( $input ): bool {
					return current_user_can( 'upload_files' );
				},
				'meta'                => array(
					'annotations'  => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
					'show_in_rest' => true,
				),
			)
		);
	}

	public function execute_get_media( $input = array() ): array|\WP_Error {
		$input    = is_array( $input ) ? $input : (array) $input;
		$media_id = absint( $input['media_id'] ?? 0 );
		$post     = get_post( $media_id );

		if ( ! $post || 'attachment' !== $post->post_type ) {
			return new \WP_Error( 'not_found', __( 'Media item not found.', 'wp-mcp-toolkit' ) );
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
}
