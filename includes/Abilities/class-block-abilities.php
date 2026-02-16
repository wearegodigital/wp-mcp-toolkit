<?php
/**
 * WP MCP Toolkit — Block Content Abilities.
 *
 * Parse and edit blocks within post content.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();

class WP_MCP_Toolkit_Block_Abilities {

	public function register( array $disabled = array() ): void {
		if ( ! in_array( 'wpmcp/parse-blocks', $disabled, true ) ) {
			$this->register_parse_blocks();
		}
		if ( ! in_array( 'wpmcp/update-block-content', $disabled, true ) ) {
			$this->register_update_block_content();
		}
	}

	private function register_parse_blocks(): void {
		wp_register_ability(
			'wpmcp/parse-blocks',
			array(
				'label'               => __( 'Parse Blocks', 'wp-mcp-toolkit' ),
				'description'         => __( 'Parses a post\'s content into a structured list of blocks with names, attributes, and inner content.', 'wp-mcp-toolkit' ),
				'category'            => 'wpmcp-blocks',
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'post_id' ),
					'properties' => array(
						'post_id' => array( 'type' => 'integer' ),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'index'        => array( 'type' => 'integer' ),
							'block_name'   => array( 'type' => 'string' ),
							'attributes'   => array( 'type' => 'object' ),
							'inner_html'   => array( 'type' => 'string' ),
							'inner_blocks' => array( 'type' => 'array' ),
						),
					),
				),
				'execute_callback'    => array( $this, 'execute_parse_blocks' ),
				'permission_callback' => static function ( $input ): bool {
					$input   = is_array( $input ) ? $input : (array) $input;
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

	public function execute_parse_blocks( $input = array() ): array|\WP_Error {
		$input   = is_array( $input ) ? $input : (array) $input;
		$post_id = absint( $input['post_id'] ?? 0 );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error( 'not_found', __( 'Post not found.', 'wp-mcp-toolkit' ) );
		}

		$blocks = parse_blocks( $post->post_content );
		return $this->flatten_blocks( $blocks );
	}

	/**
	 * Flattens parsed blocks into a structured array with global indices.
	 */
	private function flatten_blocks( array $blocks, int &$index = 0 ): array {
		$result = array();

		foreach ( $blocks as $block ) {
			// Skip empty/whitespace-only blocks.
			if ( empty( $block['blockName'] ) && empty( trim( $block['innerHTML'] ?? '' ) ) ) {
				continue;
			}

			$flat = array(
				'index'        => $index++,
				'block_name'   => $block['blockName'] ?? '',
				'attributes'   => $block['attrs'] ?? array(),
				'inner_html'   => trim( $block['innerHTML'] ?? '' ),
				'inner_blocks' => array(),
			);

			if ( ! empty( $block['innerBlocks'] ) ) {
				$flat['inner_blocks'] = $this->flatten_blocks( $block['innerBlocks'], $index );
			}

			$result[] = $flat;
		}

		return $result;
	}

	private function register_update_block_content(): void {
		wp_register_ability(
			'wpmcp/update-block-content',
			array(
				'label'               => __( 'Update Block Content', 'wp-mcp-toolkit' ),
				'description'         => __( 'Updates the HTML content of a specific block within a post, identified by block index or by searching for text. Preserves block markers.', 'wp-mcp-toolkit' ),
				'category'            => 'wpmcp-blocks',
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'post_id', 'new_content' ),
					'properties' => array(
						'post_id'     => array( 'type' => 'integer' ),
						'block_index' => array(
							'type'        => 'integer',
							'description' => __( 'The index of the block to update (from parse-blocks output).', 'wp-mcp-toolkit' ),
						),
						'search_text' => array(
							'type'        => 'string',
							'description' => __( 'Alternative: find the block containing this text.', 'wp-mcp-toolkit' ),
						),
						'new_content' => array(
							'type'        => 'string',
							'description' => __( 'The new innerHTML for the block.', 'wp-mcp-toolkit' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'     => array( 'type' => 'boolean' ),
						'block_name'  => array( 'type' => 'string' ),
						'block_index' => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_update_block_content' ),
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

	public function execute_update_block_content( $input = array() ): array|\WP_Error {
		$input       = is_array( $input ) ? $input : (array) $input;
		$post_id     = absint( $input['post_id'] ?? 0 );
		$new_content = $input['new_content'] ?? '';
		$post        = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error( 'not_found', __( 'Post not found.', 'wp-mcp-toolkit' ) );
		}

		$blocks = parse_blocks( $post->post_content );

		// Find the target block.
		$target_index = null;
		$block_name   = '';

		if ( isset( $input['block_index'] ) ) {
			$target_index = absint( $input['block_index'] );
		} elseif ( ! empty( $input['search_text'] ) ) {
			$search = $input['search_text'];
			$idx    = 0;
			foreach ( $blocks as $block ) {
				if ( empty( $block['blockName'] ) && empty( trim( $block['innerHTML'] ?? '' ) ) ) {
					continue;
				}
				if ( false !== strpos( $block['innerHTML'] ?? '', $search ) ) {
					$target_index = $idx;
					break;
				}
				$idx++;
			}
		}

		if ( null === $target_index ) {
			return new \WP_Error( 'block_not_found', __( 'Could not find the target block.', 'wp-mcp-toolkit' ) );
		}

		// Map flat index back to actual blocks array index (skipping empty blocks).
		$flat_idx    = 0;
		$real_idx    = null;
		foreach ( $blocks as $i => $block ) {
			if ( empty( $block['blockName'] ) && empty( trim( $block['innerHTML'] ?? '' ) ) ) {
				continue;
			}
			if ( $flat_idx === $target_index ) {
				$real_idx = $i;
				break;
			}
			$flat_idx++;
		}

		if ( null === $real_idx || ! isset( $blocks[ $real_idx ] ) ) {
			return new \WP_Error( 'block_not_found', __( 'Block index out of range.', 'wp-mcp-toolkit' ) );
		}

		$block_name = $blocks[ $real_idx ]['blockName'] ?? '';

		// Update innerHTML and innerContent.
		$blocks[ $real_idx ]['innerHTML'] = $new_content;
		if ( ! empty( $blocks[ $real_idx ]['innerContent'] ) ) {
			// Replace the first non-null entry in innerContent.
			foreach ( $blocks[ $real_idx ]['innerContent'] as $ci => $chunk ) {
				if ( null !== $chunk ) {
					$blocks[ $real_idx ]['innerContent'][ $ci ] = $new_content;
					break;
				}
			}
		} else {
			$blocks[ $real_idx ]['innerContent'] = array( $new_content );
		}

		// Serialize and save.
		$new_post_content = serialize_blocks( $blocks );
		$result = wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $new_post_content,
			),
			true
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'success'     => true,
			'block_name'  => $block_name,
			'block_index' => $target_index,
		);
	}
}
