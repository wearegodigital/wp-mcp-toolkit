<?php
/**
 * WP MCP Toolkit — Taxonomy & Term Abilities.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();

class WP_MCP_Toolkit_Taxonomy_Abilities {

	public function register( array $disabled = array() ): void {
		if ( ! in_array( 'wpmcp/list-taxonomies', $disabled, true ) ) {
			$this->register_list_taxonomies();
		}
		if ( ! in_array( 'wpmcp/list-terms', $disabled, true ) ) {
			$this->register_list_terms();
		}
		if ( ! in_array( 'wpmcp/create-term', $disabled, true ) ) {
			$this->register_create_term();
		}
	}

	private function register_list_taxonomies(): void {
		wp_register_ability(
			'wpmcp/list-taxonomies',
			array(
				'label'               => __( 'List Taxonomies', 'wp-mcp-toolkit' ),
				'description'         => __( 'Lists all registered public taxonomies with their associated post types.', 'wp-mcp-toolkit' ),
				'category'            => 'wpmcp-taxonomy',
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
							'name'         => array( 'type' => 'string' ),
							'label'        => array( 'type' => 'string' ),
							'hierarchical' => array( 'type' => 'boolean' ),
							'post_types'   => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
							'rest_base'    => array( 'type' => 'string' ),
						),
					),
				),
				'execute_callback'    => array( $this, 'execute_list_taxonomies' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'read' );
				},
				'meta'                => array(
					'annotations'  => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
					'show_in_rest' => true,
				),
			)
		);
	}

	public function execute_list_taxonomies( $input = array() ): array {
		$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
		$result     = array();

		foreach ( $taxonomies as $tax ) {
			$result[] = array(
				'name'         => $tax->name,
				'label'        => $tax->label,
				'hierarchical' => $tax->hierarchical,
				'post_types'   => $tax->object_type,
				'rest_base'    => $tax->rest_base ?: $tax->name,
			);
		}

		return $result;
	}

	private function register_list_terms(): void {
		wp_register_ability(
			'wpmcp/list-terms',
			array(
				'label'               => __( 'List Terms', 'wp-mcp-toolkit' ),
				'description'         => __( 'Lists terms in a taxonomy with count, parent, and description.', 'wp-mcp-toolkit' ),
				'category'            => 'wpmcp-taxonomy',
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'taxonomy' ),
					'properties' => array(
						'taxonomy'   => array( 'type' => 'string' ),
						'hide_empty' => array( 'type' => 'boolean', 'default' => false ),
						'search'     => array( 'type' => 'string' ),
						'per_page'   => array( 'type' => 'integer', 'default' => 100 ),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'id'          => array( 'type' => 'integer' ),
							'name'        => array( 'type' => 'string' ),
							'slug'        => array( 'type' => 'string' ),
							'description' => array( 'type' => 'string' ),
							'parent_id'   => array( 'type' => 'integer' ),
							'count'       => array( 'type' => 'integer' ),
						),
					),
				),
				'execute_callback'    => array( $this, 'execute_list_terms' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'read' );
				},
				'meta'                => array(
					'annotations'  => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
					'show_in_rest' => true,
				),
			)
		);
	}

	public function execute_list_terms( $input = array() ): array|\WP_Error {
		$input    = is_array( $input ) ? $input : (array) $input;
		$taxonomy = sanitize_key( $input['taxonomy'] ?? '' );

		if ( ! taxonomy_exists( $taxonomy ) ) {
			return new \WP_Error( 'invalid_taxonomy', __( 'Taxonomy does not exist.', 'wp-mcp-toolkit' ) );
		}

		$args = array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => ! empty( $input['hide_empty'] ),
			'number'     => absint( $input['per_page'] ?? 100 ),
		);

		if ( ! empty( $input['search'] ) ) {
			$args['search'] = sanitize_text_field( $input['search'] );
		}

		$terms = get_terms( $args );

		if ( is_wp_error( $terms ) ) {
			return $terms;
		}

		$result = array();
		foreach ( $terms as $term ) {
			$result[] = array(
				'id'          => $term->term_id,
				'name'        => $term->name,
				'slug'        => $term->slug,
				'description' => $term->description,
				'parent_id'   => (int) $term->parent,
				'count'       => (int) $term->count,
			);
		}

		return $result;
	}

	private function register_create_term(): void {
		wp_register_ability(
			'wpmcp/create-term',
			array(
				'label'               => __( 'Create Term', 'wp-mcp-toolkit' ),
				'description'         => __( 'Creates a new term in a taxonomy.', 'wp-mcp-toolkit' ),
				'category'            => 'wpmcp-taxonomy',
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'taxonomy', 'name' ),
					'properties' => array(
						'taxonomy'    => array( 'type' => 'string' ),
						'name'        => array( 'type' => 'string' ),
						'slug'        => array( 'type' => 'string' ),
						'description' => array( 'type' => 'string', 'default' => '' ),
						'parent'      => array( 'type' => 'integer', 'default' => 0 ),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'term_id' => array( 'type' => 'integer' ),
						'name'    => array( 'type' => 'string' ),
						'slug'    => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_create_term' ),
				'permission_callback' => static function ( $input ): bool {
					$input    = is_array( $input ) ? $input : (array) $input;
					$taxonomy = sanitize_key( $input['taxonomy'] ?? '' );
					$tax_obj  = get_taxonomy( $taxonomy );
					return $tax_obj && current_user_can( $tax_obj->cap->manage_terms );
				},
				'meta'                => array(
					'annotations'  => array( 'readonly' => false, 'destructive' => false, 'idempotent' => false ),
					'show_in_rest' => true,
				),
			)
		);
	}

	public function execute_create_term( $input = array() ): array|\WP_Error {
		$input    = is_array( $input ) ? $input : (array) $input;
		$taxonomy = sanitize_key( $input['taxonomy'] ?? '' );
		$name     = sanitize_text_field( $input['name'] ?? '' );

		$args = array();
		if ( ! empty( $input['slug'] ) ) {
			$args['slug'] = sanitize_title( $input['slug'] );
		}
		if ( ! empty( $input['description'] ) ) {
			$args['description'] = sanitize_textarea_field( $input['description'] );
		}
		if ( ! empty( $input['parent'] ) ) {
			$args['parent'] = absint( $input['parent'] );
		}

		$result = wp_insert_term( $name, $taxonomy, $args );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$term = get_term( $result['term_id'], $taxonomy );

		return array(
			'term_id' => $term->term_id,
			'name'    => $term->name,
			'slug'    => $term->slug,
		);
	}
}
