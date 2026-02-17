<?php
/**
 * WP MCP Toolkit — ACF Field Abilities.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();

class WP_MCP_Toolkit_ACF_Field_Abilities extends WP_MCP_Toolkit_Abstract_Abilities {

	protected function get_abilities(): array {
		return array(
			'wpmcp-acf/list-field-groups' => array(
				'label'         => __( 'List ACF Field Groups', 'wp-mcp-toolkit' ),
				'description'   => __( 'Lists all ACF (Advanced Custom Fields) field groups registered on the site. Each group contains a set of custom fields that appear on specific post types, pages, or options pages based on location_rules. Returns group key (needed for get-field-group), title, active status, field count, and location rules. Call this first to discover what custom fields exist, then use get-field-group for full field definitions.', 'wp-mcp-toolkit' ),
				'category'      => 'wpmcp-acf-fields',
				'input_schema'  => self::empty_input_schema(),
				'output_schema' => array(
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
				'callback'   => 'execute_list_field_groups',
				'permission' => 'manage_options',
			),
			'wpmcp-acf/get-field-group' => array(
				'label'         => __( 'Get ACF Field Group', 'wp-mcp-toolkit' ),
				'description'   => __( 'Gets the complete field definitions for an ACF field group. Returns every field with its name (use in get/update-post-fields), label, type (text, textarea, image, repeater, group, select, etc.), required status, default_value, choices (for select/radio/checkbox fields), and sub_fields (for repeater/group types). Use the group_key from list-field-groups. Essential for understanding what values are expected before calling update-post-fields.', 'wp-mcp-toolkit' ),
				'category'      => 'wpmcp-acf-fields',
				'input_schema'  => array(
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
				'output_schema' => array(
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
				'callback'   => 'execute_get_field_group',
				'permission' => 'manage_options',
			),
			'wpmcp-acf/get-post-fields' => array(
				'label'         => __( 'Get ACF Post Fields', 'wp-mcp-toolkit' ),
				'description'   => __( 'Gets all ACF custom field values for a specific post. Returns a field_name => value object. Handles all ACF field types including repeaters (returned as arrays of rows), groups (returned as nested objects), images (returned as arrays with url, alt, title), and simple fields (returned as strings/numbers). These are post-level fields stored in wp_postmeta, NOT block-level fields — for ACF block field values within post content, use get-block-fields instead.', 'wp-mcp-toolkit' ),
				'category'      => 'wpmcp-acf-fields',
				'input_schema'  => array(
					'type'       => 'object',
					'required'   => array( 'post_id' ),
					'properties' => array(
						'post_id' => array( 'type' => 'integer' ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type' => 'object',
				),
				'callback'   => 'execute_get_post_fields',
				'permission' => static function ( $input ): bool {
					$input   = is_array( $input ) ? $input : (array) $input;
					$post_id = absint( $input['post_id'] ?? 0 );
					return current_user_can( 'read_post', $post_id );
				},
			),
			'wpmcp-acf/update-post-fields' => array(
				'label'         => __( 'Update ACF Post Fields', 'wp-mcp-toolkit' ),
				'description'   => __( 'Updates ACF custom field values on a post. Pass fields as {field_name: value} pairs — only fields you include are updated, others are untouched. For repeaters: pass an array of row objects. For groups: pass a nested object. For images: pass the attachment ID (integer). For select/checkbox: pass the choice value(s). HTML in text/textarea values is preserved. These are post-level fields in wp_postmeta — for ACF block fields within post content, use update-block-fields instead.', 'wp-mcp-toolkit' ),
				'category'      => 'wpmcp-acf-fields',
				'input_schema'  => array(
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
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'updated_fields' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
						'errors'         => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
					),
				),
				'callback'   => 'execute_update_post_fields',
				'readonly'   => false,
				'permission' => static function ( $input ): bool {
					$input   = is_array( $input ) ? $input : (array) $input;
					$post_id = absint( $input['post_id'] ?? 0 );
					return current_user_can( 'edit_post', $post_id );
				},
			),
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

	public function execute_get_field_group( $input = array() ): array|\WP_Error {
		$input     = self::normalize_input( $input );
		$group_key = sanitize_text_field( $input['group_key'] ?? '' );

		$group = acf_get_field_group( $group_key );
		if ( ! $group ) {
			return new \WP_Error( 'wpmcp_not_found', __( 'Field group not found.', 'wp-mcp-toolkit' ) );
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

			if ( ! empty( $field['sub_fields'] ) ) {
				$item['sub_fields'] = $this->format_fields( $field['sub_fields'] );
			}

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

	public function execute_get_post_fields( $input = array() ): array|\WP_Error {
		$input   = self::normalize_input( $input );
		$post_id = absint( $input['post_id'] ?? 0 );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error( 'wpmcp_not_found', __( 'Post not found.', 'wp-mcp-toolkit' ) );
		}

		$fields = get_fields( $post_id );

		return is_array( $fields ) ? $fields : array();
	}

	public function execute_update_post_fields( $input = array() ): array|\WP_Error {
		$input   = self::normalize_input( $input );
		$post_id = absint( $input['post_id'] ?? 0 );
		$fields  = $input['fields'] ?? array();
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error( 'wpmcp_not_found', __( 'Post not found.', 'wp-mcp-toolkit' ) );
		}

		if ( ! is_array( $fields ) && ! is_object( $fields ) ) {
			return new \WP_Error( 'wpmcp_invalid_fields', __( 'Fields must be an object.', 'wp-mcp-toolkit' ) );
		}

		$fields  = (array) $fields;
		$updated = array();
		$errors  = array();

		foreach ( $fields as $field_name => $value ) {
			$field_name = sanitize_text_field( $field_name );
			if ( is_string( $value ) ) {
				$value = wp_kses_post( $value );
			}
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
