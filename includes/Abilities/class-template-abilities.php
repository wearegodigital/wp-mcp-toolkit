<?php
/**
 * WP MCP Toolkit — Content Template Abilities.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();

class WP_MCP_Toolkit_Template_Abilities extends WP_MCP_Toolkit_Abstract_Abilities {

	protected function get_abilities(): array {
		return array(
			'wpmcp/list-content-templates' => array(
				'label'         => __( 'List Content Templates', 'wp-mcp-toolkit' ),
				'description'   => __( 'Lists all saved content templates. Each template is linked to a post type and was extracted from a reference post. Returns post_type, reference_post_id, placeholder_count, section_count, and whether ACF fields are included. Use this to discover which post types have templates before calling get-content-template.', 'wp-mcp-toolkit' ),
				'category'      => 'wpmcp-templates',
				'input_schema'  => self::empty_input_schema(),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'templates' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'post_type'         => array( 'type' => 'string' ),
									'reference_post_id' => array( 'type' => 'integer' ),
									'placeholder_count' => array( 'type' => 'integer' ),
									'section_count'     => array( 'type' => 'integer' ),
									'has_acf_fields'    => array( 'type' => 'boolean' ),
								),
							),
						),
					),
				),
				'callback'   => 'execute_list_templates',
				'permission' => 'read',
			),
			'wpmcp/get-content-template' => array(
				'label'         => __( 'Get Content Template', 'wp-mcp-toolkit' ),
				'description'   => __( 'Gets the full content template for a post type. Returns sections (with names and placeholder lists), placeholders (with types, sample values from the reference post, and which section they belong to), raw_template (block HTML with {{placeholder}} markers), and acf_fields (field definitions with types and choices). Use the placeholder names as keys in template_data when calling create-from-template.', 'wp-mcp-toolkit' ),
				'category'      => 'wpmcp-templates',
				'input_schema'  => array(
					'type'       => 'object',
					'required'   => array( 'post_type' ),
					'properties' => array(
						'post_type' => array(
							'type'        => 'string',
							'description' => __( 'Post type slug (e.g. case-study, post, page).', 'wp-mcp-toolkit' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'post_type'         => array( 'type' => 'string' ),
						'reference_post_id' => array( 'type' => 'integer' ),
						'sections'          => array( 'type' => 'array' ),
						'placeholders'      => array( 'type' => 'object' ),
						'raw_template'      => array( 'type' => 'string' ),
						'acf_fields'        => array( 'type' => 'object' ),
					),
				),
				'callback'   => 'execute_get_template',
				'permission' => 'read',
			),
			'wpmcp/create-from-template' => array(
				'label'         => __( 'Create From Template', 'wp-mcp-toolkit' ),
				'description'   => __( 'Creates a new post from a saved content template. Provide post_type and title (required), plus template_data (placeholder name => value pairs) and acf_fields (field name => value pairs). Unfilled placeholders are removed. Creates as "draft" by default. Returns post_id and url. Call get-content-template first to see available placeholders and their expected types.', 'wp-mcp-toolkit' ),
				'category'      => 'wpmcp-templates',
				'input_schema'  => array(
					'type'       => 'object',
					'required'   => array( 'post_type', 'title' ),
					'properties' => array(
						'post_type'     => array( 'type' => 'string', 'description' => __( 'Post type slug.', 'wp-mcp-toolkit' ) ),
						'title'         => array( 'type' => 'string', 'description' => __( 'Title for the new post.', 'wp-mcp-toolkit' ) ),
						'status'        => array( 'type' => 'string', 'default' => 'draft', 'enum' => array( 'draft', 'publish', 'pending', 'private' ) ),
						'template_data' => array( 'type' => 'object', 'description' => __( 'Placeholder name => value pairs.', 'wp-mcp-toolkit' ) ),
						'acf_fields'    => array( 'type' => 'object', 'description' => __( 'ACF field name => value pairs.', 'wp-mcp-toolkit' ) ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'post_id' => array( 'type' => 'integer' ),
						'url'     => array( 'type' => 'string' ),
					),
				),
				'callback'   => 'execute_create_from_template',
				'readonly'   => false,
				'idempotent' => false,
				'permission' => self::permission_for_post_type(),
			),
		);
	}

	public function execute_list_templates( $input = array() ): array {
		require_once dirname( __DIR__ ) . '/class-template-engine.php';
		return array(
			'templates' => WP_MCP_Toolkit_Template_Engine::list_templates(),
		);
	}

	public function execute_get_template( $input = array() ): array|\WP_Error {
		$input     = self::normalize_input( $input );
		$post_type = sanitize_key( $input['post_type'] ?? '' );

		if ( empty( $post_type ) ) {
			return new \WP_Error( 'wpmcp_missing_post_type', __( 'post_type is required.', 'wp-mcp-toolkit' ) );
		}

		require_once dirname( __DIR__ ) . '/class-template-engine.php';
		$template = WP_MCP_Toolkit_Template_Engine::get_template( $post_type );

		// If template exists but needs re-extraction.
		if ( $template && ! empty( $template['reference_post_id'] ) && empty( $template['raw_template'] ) ) {
			$extracted = WP_MCP_Toolkit_Template_Engine::extract_template( absint( $template['reference_post_id'] ) );
			if ( is_wp_error( $extracted ) ) {
				return $extracted;
			}
			WP_MCP_Toolkit_Template_Engine::save_template( $post_type, $extracted );
			$template = $extracted;
		}

		if ( ! $template ) {
			return new \WP_Error( 'wpmcp_not_found', __( 'No template found for post type: ', 'wp-mcp-toolkit' ) . $post_type );
		}

		return $template;
	}

	public function execute_create_from_template( $input = array() ): array|\WP_Error {
		$input         = self::normalize_input( $input );
		$post_type     = sanitize_key( $input['post_type'] ?? '' );
		$title         = sanitize_text_field( $input['title'] ?? '' );
		$status        = sanitize_key( $input['status'] ?? 'draft' );
		$template_data = is_array( $input['template_data'] ?? null ) ? $input['template_data'] : array();
		$acf_fields    = is_array( $input['acf_fields'] ?? null ) ? $input['acf_fields'] : array();

		if ( empty( $post_type ) || empty( $title ) ) {
			return new \WP_Error( 'wpmcp_missing_fields', __( 'post_type and title are required.', 'wp-mcp-toolkit' ) );
		}

		require_once dirname( __DIR__ ) . '/class-template-engine.php';

		return WP_MCP_Toolkit_Template_Engine::create_from_template(
			$post_type,
			$title,
			$template_data,
			$acf_fields,
			$status
		);
	}
}
