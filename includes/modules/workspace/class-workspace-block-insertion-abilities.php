<?php
/**
 * Workspace Block Insertion abilities — insert Gutenberg blocks into post content.
 *
 * @package WP_MCP_Toolkit
 * @since   2.2.0
 */

defined( 'ABSPATH' ) || exit;

class WP_MCP_Toolkit_Workspace_Block_Insertion_Abilities extends WP_MCP_Toolkit_Abstract_Abilities {

	protected function get_abilities(): array {
		return [
			'wpmcp-workspace/insert-block' => [
				'label'         => __( 'Insert Block', 'wp-mcp-toolkit' ),
				'description'   => __( 'Inserts a Gutenberg block into a post\'s content at a specified position. Supports vanilla blocks (via attributes) and ACF blocks (via data field values). Position can be "append", "prepend", or a numeric block index.', 'wp-mcp-toolkit' ),
				'category'      => 'wpmcp-workspace-blocks',
				'input_schema'  => [
					'type'       => 'object',
					'required'   => [ 'post_id', 'block_name' ],
					'properties' => [
						'post_id'    => [ 'type' => 'integer', 'description' => 'Post ID to insert the block into.' ],
						'block_name' => [ 'type' => 'string',  'description' => 'Full block name (e.g. "wpmcp-workspace/icon-grid" or "acf/wpmcp-workspace-feature-grid").' ],
						'attributes' => [ 'type' => 'object',  'default' => [], 'description' => 'Block attributes (for vanilla blocks).' ],
						'data'       => [ 'type' => 'object',  'default' => [], 'description' => 'Field data (for ACF blocks). Keys are field names, values are field values.' ],
						'position'   => [ 'type' => 'string',  'default' => 'append', 'description' => 'Where to insert: "append", "prepend", or integer block index.' ],
					],
					'additionalProperties' => false,
				],
				'output_schema' => [
					'type'       => 'object',
					'properties' => [
						'success'     => [ 'type' => 'boolean' ],
						'post_id'     => [ 'type' => 'integer' ],
						'block_name'  => [ 'type' => 'string' ],
						'block_index' => [ 'type' => 'integer' ],
						'block_count' => [ 'type' => 'integer' ],
					],
				],
				'callback'   => 'execute_insert_block',
				'permission' => self::permission_for_post( 'edit_post' ),
				'readonly'   => false,
				'destructive' => false,
				'idempotent'  => false,
			],
		];
	}

	/**
	 * Insert a block into post content at the specified position.
	 *
	 * @since 2.2.0
	 * @param mixed $input Ability input.
	 * @return array|\WP_Error
	 */
	public function execute_insert_block( $input = [] ): array|\WP_Error {
		if ( 'disabled' === get_option( 'wpmcp_workspace_mode', 'auto' ) ) {
			return new \WP_Error( 'wpmcp_workspace_disabled', 'Workspace is disabled.' );
		}

		$input      = self::normalize_input( $input );
		$post_id    = absint( $input['post_id'] ?? 0 );
		$block_name = sanitize_text_field( $input['block_name'] ?? '' );
		$attributes = self::sanitize_recursive( (array) ( $input['attributes'] ?? [] ) );
		$data       = self::sanitize_recursive( (array) ( $input['data'] ?? [] ) );
		$position   = sanitize_text_field( $input['position'] ?? 'append' );

		if ( ! $post_id ) {
			return new \WP_Error( 'wpmcp_missing_fields', 'post_id is required.' );
		}

		if ( '' === $block_name ) {
			return new \WP_Error( 'wpmcp_missing_fields', 'block_name is required.' );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'wpmcp_not_found', "Post not found: {$post_id}" );
		}

		// Parse existing blocks, filtering empty filler blocks WordPress inserts.
		$blocks = parse_blocks( $post->post_content );
		$blocks = array_values( array_filter( $blocks, function( $b ) {
			return ! empty( $b['blockName'] );
		} ) );

		// Build the new block structure.
		if ( 0 === strpos( $block_name, 'acf/' ) ) {
			$new_block = [
				'blockName'    => $block_name,
				'attrs'        => [
					'name' => $block_name,
					'data' => $data,
					'mode' => 'preview',
				],
				'innerBlocks'  => [],
				'innerHTML'    => '',
				'innerContent' => [],
			];
		} else {
			$new_block = [
				'blockName'    => $block_name,
				'attrs'        => $attributes,
				'innerBlocks'  => [],
				'innerHTML'    => '',
				'innerContent' => [],
			];
		}

		// Insert at position.
		if ( 'prepend' === $position ) {
			array_unshift( $blocks, $new_block );
			$block_index = 0;
		} elseif ( 'append' === $position ) {
			$blocks[]    = $new_block;
			$block_index = count( $blocks ) - 1;
		} elseif ( is_numeric( $position ) ) {
			$idx = absint( $position );
			array_splice( $blocks, $idx, 0, [ $new_block ] );
			$block_index = $idx;
		} else {
			return new \WP_Error( 'wpmcp_invalid_position', 'position must be "append", "prepend", or a numeric block index.' );
		}

		$content = serialize_blocks( $blocks );
		$content = self::fix_serialized_block_html( $content );

		$result = wp_update_post(
			[
				'ID'           => $post_id,
				'post_content' => $content,
			],
			true
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return [
			'success'     => true,
			'post_id'     => $post_id,
			'block_name'  => $block_name,
			'block_index' => $block_index,
			'block_count' => count( $blocks ),
		];
	}
}
