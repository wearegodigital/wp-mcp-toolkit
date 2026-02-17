<?php
/**
 * WP MCP Toolkit — ACF Block Abilities.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();

class WP_MCP_Toolkit_ACF_Block_Abilities extends WP_MCP_Toolkit_Abstract_Abilities {

	protected function get_abilities(): array {
		return array(
			'wpmcp-acf/list-acf-blocks' => array(
				'label'         => __( 'List ACF Blocks', 'wp-mcp-toolkit' ),
				'description'   => __( 'Lists all ACF block types registered on the site. ACF blocks are custom Gutenberg blocks powered by ACF fields — they store field data in the block attributes (attrs.data) within post_content, not in wp_postmeta. Returns name (e.g. "acf/hero"), title, description, category, and icon. Use the name value with get-block-fields and update-block-fields to read/write field values within specific block instances.', 'wp-mcp-toolkit' ),
				'category'      => 'wpmcp-acf-fields',
				'input_schema'  => self::empty_input_schema(),
				'output_schema' => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'name'        => array( 'type' => 'string' ),
							'title'       => array( 'type' => 'string' ),
							'description' => array( 'type' => 'string' ),
							'category'    => array( 'type' => 'string' ),
							'icon'        => array( 'type' => 'string' ),
						),
					),
				),
				'callback'   => 'execute_list_acf_blocks',
				'permission' => 'edit_posts',
			),
			'wpmcp-acf/get-block-fields' => array(
				'label'         => __( 'Get ACF Block Fields', 'wp-mcp-toolkit' ),
				'description'   => __( 'Gets ACF field values from a specific ACF block instance within a post\'s content. Find the block by block_name (e.g. "acf/hero" — returns the first match) or block_index (0-based position among ACF blocks only). Returns block_name, block_index, and a fields object with field_name => value pairs (internal ACF keys prefixed with _ are filtered out). Searches recursively through nested/inner blocks. Use this instead of parse-blocks when you need ACF field data specifically.', 'wp-mcp-toolkit' ),
				'category'      => 'wpmcp-acf-fields',
				'input_schema'  => array(
					'type'       => 'object',
					'required'   => array( 'post_id' ),
					'properties' => array(
						'post_id'     => array( 'type' => 'integer' ),
						'block_index' => array(
							'type'        => 'integer',
							'description' => __( 'The index of the ACF block in post content.', 'wp-mcp-toolkit' ),
						),
						'block_name'  => array(
							'type'        => 'string',
							'description' => __( 'Alternative: find ACF block by name (e.g. acf/hero).', 'wp-mcp-toolkit' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'block_name'  => array( 'type' => 'string' ),
						'block_index' => array( 'type' => 'integer' ),
						'fields'      => array( 'type' => 'object' ),
					),
				),
				'callback'   => 'execute_get_block_fields',
				'permission' => static function ( $input ): bool {
					$input   = is_array( $input ) ? $input : (array) $input;
					$post_id = absint( $input['post_id'] ?? 0 );
					return current_user_can( 'read_post', $post_id );
				},
			),
			'wpmcp-acf/update-block-fields' => array(
				'label'         => __( 'Update ACF Block Fields', 'wp-mcp-toolkit' ),
				'description'   => __( 'Updates ACF field values within a specific ACF block in post content. Find the block by block_name (e.g. "acf/hero") or block_index. Pass fields as {field_name: value} pairs — only specified fields are changed. HTML in values is preserved correctly (the plugin handles WordPress block serialization encoding). Modifies the block\'s attrs.data in post_content and saves via wp_update_post. Use get-block-fields first to see current values and available field names.', 'wp-mcp-toolkit' ),
				'category'      => 'wpmcp-acf-fields',
				'input_schema'  => array(
					'type'       => 'object',
					'required'   => array( 'post_id', 'fields' ),
					'properties' => array(
						'post_id'     => array( 'type' => 'integer' ),
						'block_index' => array( 'type' => 'integer' ),
						'block_name'  => array( 'type' => 'string' ),
						'fields'      => array(
							'type'        => 'object',
							'description' => __( 'Object of field_name => new_value pairs.', 'wp-mcp-toolkit' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'success'        => array( 'type' => 'boolean' ),
						'block_name'     => array( 'type' => 'string' ),
						'updated_fields' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
					),
				),
				'callback'   => 'execute_update_block_fields',
				'readonly'   => false,
				'permission' => static function ( $input ): bool {
					$input   = is_array( $input ) ? $input : (array) $input;
					$post_id = absint( $input['post_id'] ?? 0 );
					return current_user_can( 'edit_post', $post_id );
				},
			),
		);
	}

	public function execute_list_acf_blocks( $input = array() ): array {
		if ( ! function_exists( 'acf_get_block_types' ) ) {
			return array();
		}

		$block_types = acf_get_block_types();
		$result      = array();

		foreach ( $block_types as $block ) {
			$result[] = array(
				'name'        => $block['name'] ?? '',
				'title'       => $block['title'] ?? '',
				'description' => $block['description'] ?? '',
				'category'    => $block['category'] ?? '',
				'icon'        => is_string( $block['icon'] ?? '' ) ? $block['icon'] : '',
			);
		}

		return $result;
	}

	public function execute_get_block_fields( $input = array() ): array|\WP_Error {
		$input   = self::normalize_input( $input );
		$post_id = absint( $input['post_id'] ?? 0 );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error( 'wpmcp_not_found', __( 'Post not found.', 'wp-mcp-toolkit' ) );
		}

		$blocks = parse_blocks( $post->post_content );
		$target = $this->find_acf_block( $blocks, $input );

		if ( null === $target ) {
			return new \WP_Error( 'wpmcp_block_not_found', __( 'ACF block not found.', 'wp-mcp-toolkit' ) );
		}

		$data = $target['block']['attrs']['data'] ?? array();

		// Filter out ACF internal keys (prefixed with _).
		$fields = array();
		foreach ( $data as $key => $value ) {
			if ( ! str_starts_with( $key, '_' ) ) {
				$fields[ $key ] = $value;
			}
		}

		return array(
			'block_name'  => $target['block']['blockName'],
			'block_index' => $target['index'],
			'fields'      => $fields,
		);
	}

	public function execute_update_block_fields( $input = array() ): array|\WP_Error {
		$input       = self::normalize_input( $input );
		$post_id     = absint( $input['post_id'] ?? 0 );
		$new_fields  = (array) ( $input['fields'] ?? array() );
		$post        = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error( 'wpmcp_not_found', __( 'Post not found.', 'wp-mcp-toolkit' ) );
		}

		$blocks = parse_blocks( $post->post_content );
		$target = $this->find_acf_block( $blocks, $input );

		if ( null === $target ) {
			return new \WP_Error( 'wpmcp_block_not_found', __( 'ACF block not found.', 'wp-mcp-toolkit' ) );
		}

		$block_name = $target['block']['blockName'];
		$block_path = $target['path'];

		// Navigate to the nested block using the path.
		$ref = &$blocks;
		foreach ( $block_path as $i => $segment ) {
			if ( $i === count( $block_path ) - 1 ) {
				if ( ! isset( $ref[ $segment ]['attrs']['data'] ) ) {
					$ref[ $segment ]['attrs']['data'] = array();
				}
				$updated = array();
				foreach ( $new_fields as $key => $value ) {
					$key = sanitize_text_field( $key );
					if ( is_string( $value ) ) {
						$value = wp_kses_post( $value );
					}
					$ref[ $segment ]['attrs']['data'][ $key ] = $value;
					$updated[] = $key;
				}
				break;
			} else {
				$ref = &$ref[ $segment ];
			}
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
			'success'        => true,
			'block_name'     => $block_name,
			'updated_fields' => $updated,
		);
	}

	/**
	 * Finds an ACF block recursively in parsed blocks by index or name.
	 */
	private function find_acf_block( array $blocks, array $input ): ?array {
		$target_index = isset( $input['block_index'] ) ? absint( $input['block_index'] ) : null;
		$target_name  = ! empty( $input['block_name'] ) ? sanitize_text_field( $input['block_name'] ) : null;

		$counter = array( 'idx' => 0 );
		return $this->search_acf_blocks_recursive( $blocks, $target_index, $target_name, $counter, array() );
	}

	/**
	 * Recursively searches for ACF blocks through inner blocks.
	 */
	private function search_acf_blocks_recursive( array $blocks, ?int $target_index, ?string $target_name, array &$counter, array $path ): ?array {
		foreach ( $blocks as $real_idx => $block ) {
			$name = $block['blockName'] ?? '';

			if ( 0 === strpos( $name, 'acf/' ) ) {
				$current_path = array_merge( $path, array( $real_idx ) );

				if ( null !== $target_index && $counter['idx'] === $target_index ) {
					return array( 'block' => $block, 'index' => $counter['idx'], 'real_index' => $real_idx, 'path' => $current_path );
				}

				if ( null !== $target_name && $name === $target_name ) {
					return array( 'block' => $block, 'index' => $counter['idx'], 'real_index' => $real_idx, 'path' => $current_path );
				}

				if ( null === $target_index && null === $target_name ) {
					return array( 'block' => $block, 'index' => $counter['idx'], 'real_index' => $real_idx, 'path' => $current_path );
				}

				$counter['idx']++;
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$inner_path = array_merge( $path, array( $real_idx, 'innerBlocks' ) );
				$found = $this->search_acf_blocks_recursive( $block['innerBlocks'], $target_index, $target_name, $counter, $inner_path );
				if ( null !== $found ) {
					return $found;
				}
			}
		}

		return null;
	}
}
