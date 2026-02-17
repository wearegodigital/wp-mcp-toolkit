<?php
/**
 * WP MCP Toolkit — Block Content Abilities.
 *
 * Parse and edit blocks within post content.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();

class WP_MCP_Toolkit_Block_Abilities extends WP_MCP_Toolkit_Abstract_Abilities {

	protected function get_abilities(): array {
		return array(
			'wpmcp/parse-blocks' => array(
				'label'         => __( 'Parse Blocks', 'wp-mcp-toolkit' ),
				'description'   => __( 'Parses a post\'s Gutenberg block content into a structured list. Each block includes: index (flat position for use with update-block-content), block_name (e.g. "core/paragraph", "acf/hero"), attributes (block settings and data), inner_html (the visible content), and inner_blocks (nested children, recursively). Use this before update-block-content to find the correct block index. For ACF block fields, use wpmcp-acf/get-block-fields instead.', 'wp-mcp-toolkit' ),
				'category'      => 'wpmcp-blocks',
				'input_schema'  => array(
					'type'       => 'object',
					'required'   => array( 'post_id' ),
					'properties' => array(
						'post_id' => array( 'type' => 'integer' ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
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
				'callback'   => 'execute_parse_blocks',
				'permission' => static function ( $input ): bool {
					$input   = is_array( $input ) ? $input : (array) $input;
					$post_id = absint( $input['post_id'] ?? 0 );
					return current_user_can( 'read_post', $post_id );
				},
			),
			'wpmcp/update-block-content' => array(
				'label'         => __( 'Update Block Content', 'wp-mcp-toolkit' ),
				'description'   => __( 'Updates the innerHTML of a specific top-level block, preserving Gutenberg block markers (<!-- wp:... --> comments). Identify the block by block_index (from parse-blocks output) or search_text (finds the first block containing that text). Provide new_content as the full replacement HTML for that block. LIMITATION: Only works on top-level blocks. For nested blocks (e.g. paragraphs inside columns), use get-post to read raw content, modify the text, and update-post to save. For ACF block field values, use wpmcp-acf/update-block-fields instead.', 'wp-mcp-toolkit' ),
				'category'      => 'wpmcp-blocks',
				'input_schema'  => array(
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
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'success'     => array( 'type' => 'boolean' ),
						'block_name'  => array( 'type' => 'string' ),
						'block_index' => array( 'type' => 'integer' ),
					),
				),
				'callback'   => 'execute_update_block_content',
				'readonly'   => false,
				'permission' => static function ( $input ): bool {
					$input   = is_array( $input ) ? $input : (array) $input;
					$post_id = absint( $input['post_id'] ?? 0 );
					return current_user_can( 'edit_post', $post_id );
				},
			),
		);
	}

	public function execute_parse_blocks( $input = array() ): array|\WP_Error {
		$input   = self::normalize_input( $input );
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

	public function execute_update_block_content( $input = array() ): array|\WP_Error {
		$input       = self::normalize_input( $input );
		$post_id     = absint( $input['post_id'] ?? 0 );
		$new_content = wp_kses_post( $input['new_content'] ?? '' );
		$post        = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error( 'not_found', __( 'Post not found.', 'wp-mcp-toolkit' ) );
		}

		$blocks       = parse_blocks( $post->post_content );
		$target_index = $this->resolve_target_index( $blocks, $input );

		if ( null === $target_index ) {
			return new \WP_Error( 'block_not_found', __( 'Could not find the target block.', 'wp-mcp-toolkit' ) );
		}

		// Map flat index back to actual blocks array index (skipping empty blocks).
		$real_idx = $this->flat_to_real_index( $blocks, $target_index );

		if ( null === $real_idx || ! isset( $blocks[ $real_idx ] ) ) {
			return new \WP_Error( 'block_not_found', __( 'Block index out of range.', 'wp-mcp-toolkit' ) );
		}

		$block_name = $blocks[ $real_idx ]['blockName'] ?? '';

		// Update innerHTML and innerContent.
		$blocks[ $real_idx ]['innerHTML'] = $new_content;
		if ( ! empty( $blocks[ $real_idx ]['innerContent'] ) ) {
			foreach ( $blocks[ $real_idx ]['innerContent'] as $ci => $chunk ) {
				if ( null !== $chunk ) {
					$blocks[ $real_idx ]['innerContent'][ $ci ] = $new_content;
					break;
				}
			}
		} else {
			$blocks[ $real_idx ]['innerContent'] = array( $new_content );
		}

		$result = wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => self::fix_serialized_block_html( serialize_blocks( $blocks ) ),
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

	/**
	 * Resolves a target flat-index from either block_index or search_text input.
	 */
	private function resolve_target_index( array $blocks, array $input ): ?int {
		if ( isset( $input['block_index'] ) ) {
			return absint( $input['block_index'] );
		}

		if ( empty( $input['search_text'] ) ) {
			return null;
		}

		$search = $input['search_text'];
		$idx    = 0;
		foreach ( $blocks as $block ) {
			if ( empty( $block['blockName'] ) && empty( trim( $block['innerHTML'] ?? '' ) ) ) {
				continue;
			}
			if ( false !== strpos( $block['innerHTML'] ?? '', $search ) ) {
				return $idx;
			}
			$idx++;
		}

		return null;
	}

	/**
	 * Maps a flat index (skipping empty blocks) to the real array index.
	 */
	private function flat_to_real_index( array $blocks, int $target ): ?int {
		$flat_idx = 0;
		foreach ( $blocks as $i => $block ) {
			if ( empty( $block['blockName'] ) && empty( trim( $block['innerHTML'] ?? '' ) ) ) {
				continue;
			}
			if ( $flat_idx === $target ) {
				return $i;
			}
			$flat_idx++;
		}
		return null;
	}
}
