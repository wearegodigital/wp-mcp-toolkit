<?php
/**
 * WP MCP Toolkit — ACF Field Abilities.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();

class WP_MCP_Toolkit_ACF_Field_Abilities {

	public function register(): void {
		$this->register_list_field_groups();
		$this->register_get_field_group();
		$this->register_get_post_fields();
		$this->register_update_post_fields();
	}

	private function register_list_field_groups(): void {
		wp_register_ability(
			'wpmcp-acf/list-field-groups',
			array(
				'label'               => __( 'List ACF Field Groups', 'wp-mcp-toolkit' ),
				'description'         => __( 'Lists all ACF field groups with their titles, keys, and location rules.', 'wp-mcp-toolkit' ),
				'category'            => 'wpmcp-acf-fields',
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
							'key'            => array( 'type' => 'string' ),
							'title'          => array( 'type' => 'string' ),
							'active'         => array( 'type' => 'boolean' ),
							'fields_count'   => array( 'type' => 'integer' ),
							'location_rules' => array( 'type' => 'array' ),
						),
					),
				),
				'execute_callback'    => array( $this, 'execute_list_field_groups' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'meta'                => array(
					'annotations'  => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
					'show_in_rest' => true,
				),
			)
		);
	}

	public function execute_list_field_groups( $input = array() ): array {
		$groups = acf_get_field_groups();
		$result = array();

		foreach ( $groups as $group ) {
			$fields = acf_get_fields( $group['key'] );
			$result[] = array(
				'key'            => $group['key'],
				'title'          => $group['title'],
				'active'         => (bool) $group['active'],
				'fields_count'   => is_array( $fields ) ? count( $fields ) : 0,
				'location_rules' => $group['location'] ?? array(),
			);
		}

		return $result;
	}

	private function register_get_field_group(): void {
		wp_register_ability(
			'wpmcp-acf/get-field-group',
			array(
				'label'               => __( 'Get ACF Field Group', 'wp-mcp-toolkit' ),
				'description'         => __( 'Gets a field group\'s full configuration including all fields with types, choices, and defaults.', 'wp-mcp-toolkit' ),
				'category'            => 'wpmcp-acf-fields',
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'group_key' ),
					'properties' => array(
						'group_key' => array(
							'type'        => 'string',
							'description' => __( 'The field group key (e.g. group_5fb55103c1d32).', 'wp-mcp-toolkit' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'key'    => array( 'type' => 'string' ),
						'title'  => array( 'type' => 'string' ),
						'fields' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'key'           => array( 'type' => 'string' ),
									'name'          => array( 'type' => 'string' ),
									'label'         => array( 'type' => 'string' ),
									'type'          => array( 'type' => 'string' ),
									'required'      => array( 'type' => 'boolean' ),
									'default_value' => array(),
									'choices'       => array( 'type' => 'object' ),
									'sub_fields'    => array( 'type' => 'array' ),
								),
							),
						),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_field_group' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'meta'                => array(
					'annotations'  => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
					'show_in_rest' => true,
				),
			)
		);
	}

	public function execute_get_field_group( $input = array() ): array|\WP_Error {
		$input     = is_array( $input ) ? $input : (array) $input;
		$group_key = sanitize_text_field( $input['group_key'] ?? '' );

		$group = acf_get_field_group( $group_key );
		if ( ! $group ) {
			return new \WP_Error( 'not_found', __( 'Field group not found.', 'wp-mcp-toolkit' ) );
		}

		$fields     = acf_get_fields( $group_key );
		$field_data = $this->format_fields( is_array( $fields ) ? $fields : array() );

		return array(
			'key'    => $group['key'],
			'title'  => $group['title'],
			'fields' => $field_data,
		);
	}

	private function format_fields( array $fields ): array {
		$result = array();
		foreach ( $fields as $field ) {
			$item = array(
				'key'           => $field['key'],
				'name'          => $field['name'],
				'label'         => $field['label'],
				'type'          => $field['type'],
				'required'      => (bool) ( $field['required'] ?? false ),
				'default_value' => $field['default_value'] ?? '',
			);

			if ( ! empty( $field['choices'] ) ) {
				$item['choices'] = $field['choices'];
			}

			// Recursively handle sub_fields for repeaters and groups.
			if ( ! empty( $field['sub_fields'] ) ) {
				$item['sub_fields'] = $this->format_fields( $field['sub_fields'] );
			}

			// Handle layouts for flexible content.
			if ( ! empty( $field['layouts'] ) ) {
				$layouts = array();
				foreach ( $field['layouts'] as $layout ) {
					$layouts[] = array(
						'key'        => $layout['key'],
						'name'       => $layout['name'],
						'label'      => $layout['label'],
						'sub_fields' => $this->format_fields( $layout['sub_fields'] ?? array() ),
					);
				}
				$item['layouts'] = $layouts;
			}

			$result[] = $item;
		}
		return $result;
	}

	private function register_get_post_fields(): void {
		wp_register_ability(
			'wpmcp-acf/get-post-fields',
			array(
				'label'               => __( 'Get ACF Post Fields', 'wp-mcp-toolkit' ),
				'description'         => __( 'Gets all ACF field values for a specific post, including repeaters and groups.', 'wp-mcp-toolkit' ),
				'category'            => 'wpmcp-acf-fields',
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'post_id' ),
					'properties' => array(
						'post_id' => array( 'type' => 'integer' ),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type' => 'object',
				),
				'execute_callback'    => array( $this, 'execute_get_post_fields' ),
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

	public function execute_get_post_fields( $input = array() ): array|\WP_Error {
		$input   = is_array( $input ) ? $input : (array) $input;
		$post_id = absint( $input['post_id'] ?? 0 );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error( 'not_found', __( 'Post not found.', 'wp-mcp-toolkit' ) );
		}

		$fields = get_fields( $post_id );

		if ( ! is_array( $fields ) ) {
			return array();
		}

		return $fields;
	}

	private function register_update_post_fields(): void {
		wp_register_ability(
			'wpmcp-acf/update-post-fields',
			array(
				'label'               => __( 'Update ACF Post Fields', 'wp-mcp-toolkit' ),
				'description'         => __( 'Updates ACF field values on a post. Handles text, repeaters, groups, and other field types.', 'wp-mcp-toolkit' ),
				'category'            => 'wpmcp-acf-fields',
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'post_id', 'fields' ),
					'properties' => array(
						'post_id' => array( 'type' => 'integer' ),
						'fields'  => array(
							'type'        => 'object',
							'description' => __( 'Object of field_name => value pairs.', 'wp-mcp-toolkit' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'updated_fields' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
						'errors'         => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
					),
				),
				'execute_callback'    => array( $this, 'execute_update_post_fields' ),
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

	public function execute_update_post_fields( $input = array() ): array|\WP_Error {
		$input   = is_array( $input ) ? $input : (array) $input;
		$post_id = absint( $input['post_id'] ?? 0 );
		$fields  = $input['fields'] ?? array();
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error( 'not_found', __( 'Post not found.', 'wp-mcp-toolkit' ) );
		}

		if ( ! is_array( $fields ) && ! is_object( $fields ) ) {
			return new \WP_Error( 'invalid_fields', __( 'Fields must be an object.', 'wp-mcp-toolkit' ) );
		}

		$fields  = (array) $fields;
		$updated = array();
		$errors  = array();

		foreach ( $fields as $field_name => $value ) {
			$field_name = sanitize_text_field( $field_name );
			$result = update_field( $field_name, $value, $post_id );

			if ( false === $result ) {
				$errors[] = sprintf( 'Failed to update field: %s', $field_name );
			} else {
				$updated[] = $field_name;
			}
		}

		return array(
			'updated_fields' => $updated,
			'errors'         => $errors,
		);
	}
}
