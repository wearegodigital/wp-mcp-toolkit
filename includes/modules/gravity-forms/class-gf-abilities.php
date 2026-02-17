<?php
/**
 * WP MCP Toolkit — Gravity Forms Abilities.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();

class WP_MCP_Toolkit_GF_Abilities extends WP_MCP_Toolkit_Abstract_Abilities {

	protected function get_abilities(): array {
		return array(
			'wpmcp-gf/list-forms' => array(
				'label'         => __( 'List Gravity Forms', 'wp-mcp-toolkit' ),
				'description'   => __( 'Lists all Gravity Forms on the site. Returns id (form ID for use in other tools), title, field_count (number of fields in the form), entry_count (total submissions), is_active (whether the form accepts submissions), and date_created. Call this first to discover what forms exist before retrieving form details or entries.', 'wp-mcp-toolkit' ),
				'category'      => 'wpmcp-gf',
				'input_schema'  => self::empty_input_schema(),
				'output_schema' => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'id'          => array( 'type' => 'integer' ),
							'title'       => array( 'type' => 'string' ),
							'field_count' => array( 'type' => 'integer' ),
							'entry_count' => array( 'type' => 'integer' ),
							'is_active'   => array( 'type' => 'boolean' ),
							'date_created' => array( 'type' => 'string' ),
						),
					),
				),
				'callback'   => 'execute_list_forms',
				'permission' => 'gravityforms_view_entries',
			),
			'wpmcp-gf/get-form' => array(
				'label'         => __( 'Get Gravity Form', 'wp-mcp-toolkit' ),
				'description'   => __( 'Gets the complete field definitions for a Gravity Form. Returns id, title, description, fields array (each with id, type, label, isRequired, choices for select/radio/checkbox fields, inputType), confirmations, and notifications count. Use form_id from list-forms. Essential for understanding what fields and values are expected before creating entries.', 'wp-mcp-toolkit' ),
				'category'      => 'wpmcp-gf',
				'input_schema'  => array(
					'type'       => 'object',
					'required'   => array( 'form_id' ),
					'properties' => array(
						'form_id' => array(
							'type'        => 'integer',
							'description' => __( 'The form ID.', 'wp-mcp-toolkit' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id'           => array( 'type' => 'integer' ),
						'title'        => array( 'type' => 'string' ),
						'description'  => array( 'type' => 'string' ),
						'fields'       => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'id'         => array( 'type' => 'integer' ),
									'type'       => array( 'type' => 'string' ),
									'label'      => array( 'type' => 'string' ),
									'isRequired' => array( 'type' => 'boolean' ),
									'choices'    => array( 'type' => 'array' ),
									'inputType'  => array( 'type' => 'string' ),
								),
							),
						),
						'confirmations' => array( 'type' => 'object' ),
						'notifications_count' => array( 'type' => 'integer' ),
					),
				),
				'callback'   => 'execute_get_form',
				'permission' => 'gravityforms_view_entries',
			),
			'wpmcp-gf/list-entries' => array(
				'label'         => __( 'List Gravity Form Entries', 'wp-mcp-toolkit' ),
				'description'   => __( 'Lists entries (submissions) for a Gravity Form with pagination, filtering by status, date range, and field values. Returns entries array (each with id, date_created, status, and field values as field_{id} => value), total entries, and total_pages. Use form_id from list-forms. Supports field_filters array for searching by specific field values with operators (is, isnot, contains, starts_with, ends_with, >, <, etc.).', 'wp-mcp-toolkit' ),
				'category'      => 'wpmcp-gf',
				'input_schema'  => array(
					'type'       => 'object',
					'required'   => array( 'form_id' ),
					'properties' => array(
						'form_id' => array(
							'type'        => 'integer',
							'description' => __( 'The form ID.', 'wp-mcp-toolkit' ),
						),
						'per_page' => array(
							'type'    => 'integer',
							'default' => 20,
							'minimum' => 1,
							'maximum' => 100,
						),
						'page' => array(
							'type'    => 'integer',
							'default' => 1,
							'minimum' => 1,
						),
						'status' => array(
							'type'        => 'string',
							'default'     => 'active',
							'description' => __( 'Entry status: active, trash, spam.', 'wp-mcp-toolkit' ),
						),
						'start_date' => array(
							'type'        => 'string',
							'description' => __( 'Start date in Y-m-d format.', 'wp-mcp-toolkit' ),
						),
						'end_date' => array(
							'type'        => 'string',
							'description' => __( 'End date in Y-m-d format.', 'wp-mcp-toolkit' ),
						),
						'field_filters' => array(
							'type'        => 'array',
							'description' => __( 'Array of {key: field_id, value: search_value, operator: "is"}.', 'wp-mcp-toolkit' ),
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'key'      => array( 'type' => 'string' ),
									'value'    => array( 'type' => 'string' ),
									'operator' => array( 'type' => 'string' ),
								),
							),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'entries'     => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'id'           => array( 'type' => 'integer' ),
									'date_created' => array( 'type' => 'string' ),
									'status'       => array( 'type' => 'string' ),
								),
							),
						),
						'total'       => array( 'type' => 'integer' ),
						'total_pages' => array( 'type' => 'integer' ),
					),
				),
				'callback'   => 'execute_list_entries',
				'permission' => 'gravityforms_view_entries',
			),
			'wpmcp-gf/get-entry' => array(
				'label'         => __( 'Get Gravity Form Entry', 'wp-mcp-toolkit' ),
				'description'   => __( 'Gets a single Gravity Form entry (submission) by ID with full details. Returns id, form_id, date_created, status, ip, source_url, user_agent, and all field values as field_{id} => value pairs. Use entry_id from list-entries. Essential for viewing complete submission data including all custom fields.', 'wp-mcp-toolkit' ),
				'category'      => 'wpmcp-gf',
				'input_schema'  => array(
					'type'       => 'object',
					'required'   => array( 'entry_id' ),
					'properties' => array(
						'entry_id' => array(
							'type'        => 'integer',
							'description' => __( 'The entry ID.', 'wp-mcp-toolkit' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id'           => array( 'type' => 'integer' ),
						'form_id'      => array( 'type' => 'integer' ),
						'date_created' => array( 'type' => 'string' ),
						'status'       => array( 'type' => 'string' ),
					),
				),
				'callback'   => 'execute_get_entry',
				'permission' => 'gravityforms_view_entries',
			),
			'wpmcp-gf/create-entry' => array(
				'label'         => __( 'Create Gravity Form Entry', 'wp-mcp-toolkit' ),
				'description'   => __( 'Creates a new Gravity Form entry (submission) programmatically. Requires form_id and field_values object mapping field IDs to values (e.g. {"1": "John Doe", "2": "john@example.com"}). Use get-form to discover field IDs and types. Returns the new entry_id. Useful for importing data, creating test submissions, or programmatic form submissions. Does NOT trigger form notifications by default.', 'wp-mcp-toolkit' ),
				'category'      => 'wpmcp-gf',
				'input_schema'  => array(
					'type'       => 'object',
					'required'   => array( 'form_id', 'field_values' ),
					'properties' => array(
						'form_id' => array(
							'type'        => 'integer',
							'description' => __( 'The form ID.', 'wp-mcp-toolkit' ),
						),
						'field_values' => array(
							'type'        => 'object',
							'description' => __( 'Object of field_id => value pairs (e.g. {"1": "John", "2": "john@example.com"}).', 'wp-mcp-toolkit' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'entry_id' => array( 'type' => 'integer' ),
					),
				),
				'callback'   => 'execute_create_entry',
				'readonly'   => false,
				'idempotent' => false,
				'permission' => 'gravityforms_edit_entries',
			),
		);
	}

	public function execute_list_forms( $input = array() ): array {
		$forms  = \GFAPI::get_forms();
		$result = array();

		foreach ( $forms as $form ) {
			$entry_count = \GFAPI::count_entries( $form['id'] );
			$result[]    = array(
				'id'           => absint( $form['id'] ),
				'title'        => sanitize_text_field( $form['title'] ),
				'field_count'  => is_array( $form['fields'] ) ? count( $form['fields'] ) : 0,
				'entry_count'  => absint( $entry_count ),
				'is_active'    => (bool) ( $form['is_active'] ?? true ),
				'date_created' => sanitize_text_field( $form['date_created'] ?? '' ),
			);
		}

		return $result;
	}

	public function execute_get_form( $input = array() ): array|\WP_Error {
		$input   = self::normalize_input( $input );
		$form_id = absint( $input['form_id'] ?? 0 );

		$form = \GFAPI::get_form( $form_id );
		if ( ! $form ) {
			return new \WP_Error( 'wpmcp_not_found', __( 'Form not found.', 'wp-mcp-toolkit' ) );
		}

		$fields = array();
		if ( ! empty( $form['fields'] ) && is_array( $form['fields'] ) ) {
			foreach ( $form['fields'] as $field ) {
				$field_data = array(
					'id'         => absint( $field->id ),
					'type'       => sanitize_text_field( $field->type ),
					'label'      => sanitize_text_field( $field->label ),
					'isRequired' => (bool) ( $field->isRequired ?? false ),
					'inputType'  => sanitize_text_field( $field->inputType ?? '' ),
				);

				if ( ! empty( $field->choices ) && is_array( $field->choices ) ) {
					$field_data['choices'] = $field->choices;
				}

				$fields[] = $field_data;
			}
		}

		return array(
			'id'                  => absint( $form['id'] ),
			'title'               => sanitize_text_field( $form['title'] ),
			'description'         => sanitize_textarea_field( $form['description'] ?? '' ),
			'fields'              => $fields,
			'confirmations'       => $form['confirmations'] ?? array(),
			'notifications_count' => is_array( $form['notifications'] ?? array() ) ? count( $form['notifications'] ) : 0,
		);
	}

	public function execute_list_entries( $input = array() ): array|\WP_Error {
		$input   = self::normalize_input( $input );
		$form_id = absint( $input['form_id'] ?? 0 );

		$form = \GFAPI::get_form( $form_id );
		if ( ! $form ) {
			return new \WP_Error( 'wpmcp_not_found', __( 'Form not found.', 'wp-mcp-toolkit' ) );
		}

		$per_page = absint( $input['per_page'] ?? 20 );
		$per_page = min( max( $per_page, 1 ), 100 );
		$page     = absint( $input['page'] ?? 1 );
		$page     = max( $page, 1 );
		$status   = sanitize_key( $input['status'] ?? 'active' );

		$search_criteria = array(
			'status' => $status,
		);

		if ( ! empty( $input['start_date'] ) ) {
			$search_criteria['start_date'] = sanitize_text_field( $input['start_date'] );
		}

		if ( ! empty( $input['end_date'] ) ) {
			$search_criteria['end_date'] = sanitize_text_field( $input['end_date'] );
		}

		if ( ! empty( $input['field_filters'] ) && is_array( $input['field_filters'] ) ) {
			$search_criteria['field_filters'] = array();
			foreach ( $input['field_filters'] as $filter ) {
				if ( ! is_array( $filter ) && ! is_object( $filter ) ) {
					continue;
				}
				$filter = (array) $filter;
				$search_criteria['field_filters'][] = array(
					'key'      => sanitize_text_field( $filter['key'] ?? '' ),
					'value'    => sanitize_text_field( $filter['value'] ?? '' ),
					'operator' => sanitize_text_field( $filter['operator'] ?? 'is' ),
				);
			}
		}

		$sorting = array(
			'key'        => 'date_created',
			'direction'  => 'DESC',
		);

		$paging = array(
			'offset'    => ( $page - 1 ) * $per_page,
			'page_size' => $per_page,
		);

		$entries = \GFAPI::get_entries( $form_id, $search_criteria, $sorting, $paging );

		if ( is_wp_error( $entries ) ) {
			return $entries;
		}

		$result = array();
		foreach ( $entries as $entry ) {
			unset( $entry['ip'], $entry['user_agent'], $entry['source_url'] );
			$result[] = $entry;
		}

		$total_count = \GFAPI::count_entries( $form_id, $search_criteria );
		$total_pages = ceil( $total_count / $per_page );

		return array(
			'entries'     => $result,
			'total'       => absint( $total_count ),
			'total_pages' => absint( $total_pages ),
		);
	}

	public function execute_get_entry( $input = array() ): array|\WP_Error {
		$input    = self::normalize_input( $input );
		$entry_id = absint( $input['entry_id'] ?? 0 );

		$entry = \GFAPI::get_entry( $entry_id );

		if ( is_wp_error( $entry ) ) {
			return $entry;
		}

		if ( ! $entry ) {
			return new \WP_Error( 'wpmcp_not_found', __( 'Entry not found.', 'wp-mcp-toolkit' ) );
		}

		$sensitive_fields = array( 'ip', 'user_agent', 'source_url' );
		foreach ( $sensitive_fields as $field ) {
			unset( $entry[ $field ] );
		}

		return $entry;
	}

	public function execute_create_entry( $input = array() ): array|\WP_Error {
		$input        = self::normalize_input( $input );
		$form_id      = absint( $input['form_id'] ?? 0 );
		$field_values = $input['field_values'] ?? array();

		$form = \GFAPI::get_form( $form_id );
		if ( ! $form ) {
			return new \WP_Error( 'wpmcp_not_found', __( 'Form not found.', 'wp-mcp-toolkit' ) );
		}

		if ( ! is_array( $field_values ) && ! is_object( $field_values ) ) {
			return new \WP_Error( 'wpmcp_invalid_fields', __( 'field_values must be an object.', 'wp-mcp-toolkit' ) );
		}

		$field_values = (array) $field_values;

		$entry = array(
			'form_id' => $form_id,
		);

		foreach ( $field_values as $field_id => $value ) {
			$field_id           = sanitize_text_field( $field_id );
			$entry[ $field_id ] = is_string( $value ) ? sanitize_text_field( $value ) : $value;
		}

		$entry_id = \GFAPI::add_entry( $entry );

		if ( is_wp_error( $entry_id ) ) {
			return $entry_id;
		}

		return array(
			'entry_id' => absint( $entry_id ),
		);
	}
}
