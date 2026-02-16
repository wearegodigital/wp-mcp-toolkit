<?php
/**
 * WP MCP Toolkit — Schema / Site Discovery Abilities.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();

class WP_MCP_Toolkit_Schema_Abilities {

	public function register( array $disabled = array() ): void {
		if ( ! in_array( 'wpmcp/get-site-structure', $disabled, true ) ) {
			$this->register_get_site_structure();
		}
		if ( ! in_array( 'wpmcp/get-page-tree', $disabled, true ) ) {
			$this->register_get_page_tree();
		}
	}

	private function register_get_site_structure(): void {
		wp_register_ability(
			'wpmcp/get-site-structure',
			array(
				'label'               => __( 'Get Site Structure', 'wp-mcp-toolkit' ),
				'description'         => __( 'Returns a comprehensive overview of the site: post types, taxonomies, active plugins, theme info, and content counts.', 'wp-mcp-toolkit' ),
				'category'            => 'wpmcp-schema',
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => new \stdClass(),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'post_types' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'name'         => array( 'type' => 'string' ),
									'label'        => array( 'type' => 'string' ),
									'hierarchical' => array( 'type' => 'boolean' ),
									'has_archive'  => array( 'type' => 'boolean' ),
									'rest_base'    => array( 'type' => 'string' ),
									'count'        => array( 'type' => 'integer' ),
								),
							),
						),
						'taxonomies' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'name'         => array( 'type' => 'string' ),
									'label'        => array( 'type' => 'string' ),
									'post_types'   => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
									'hierarchical' => array( 'type' => 'boolean' ),
								),
							),
						),
						'theme'   => array(
							'type'       => 'object',
							'properties' => array(
								'name'    => array( 'type' => 'string' ),
								'version' => array( 'type' => 'string' ),
								'parent'  => array( 'type' => 'string' ),
							),
						),
						'plugins' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'name'    => array( 'type' => 'string' ),
									'version' => array( 'type' => 'string' ),
								),
							),
						),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_site_structure' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'read' );
				},
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
				),
			)
		);
	}

	public function execute_get_site_structure( $input = array() ): array {
		$post_types_raw = get_post_types( array( 'public' => true ), 'objects' );
		$post_types     = array();
		foreach ( $post_types_raw as $pt ) {
			$counts       = wp_count_posts( $pt->name );
			$post_types[] = array(
				'name'         => $pt->name,
				'label'        => $pt->label,
				'hierarchical' => $pt->hierarchical,
				'has_archive'  => (bool) $pt->has_archive,
				'rest_base'    => $pt->rest_base ?: $pt->name,
				'count'        => isset( $counts->publish ) ? (int) $counts->publish : 0,
			);
		}

		$taxonomies_raw = get_taxonomies( array( 'public' => true ), 'objects' );
		$taxonomies     = array();
		foreach ( $taxonomies_raw as $tax ) {
			$taxonomies[] = array(
				'name'         => $tax->name,
				'label'        => $tax->label,
				'post_types'   => $tax->object_type,
				'hierarchical' => $tax->hierarchical,
			);
		}

		$theme_obj = wp_get_theme();
		$theme     = array(
			'name'    => $theme_obj->get( 'Name' ),
			'version' => $theme_obj->get( 'Version' ),
			'parent'  => $theme_obj->parent() ? $theme_obj->parent()->get( 'Name' ) : '',
		);

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$active_plugins = get_option( 'active_plugins', array() );
		$plugins        = array();
		foreach ( $active_plugins as $plugin_file ) {
			$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file, false, false );
			$plugins[]   = array(
				'name'    => $plugin_data['Name'] ?? $plugin_file,
				'version' => $plugin_data['Version'] ?? '',
			);
		}

		return array(
			'post_types' => $post_types,
			'taxonomies' => $taxonomies,
			'theme'      => $theme,
			'plugins'    => $plugins,
		);
	}

	private function register_get_page_tree(): void {
		wp_register_ability(
			'wpmcp/get-page-tree',
			array(
				'label'               => __( 'Get Page Tree', 'wp-mcp-toolkit' ),
				'description'         => __( 'Returns a hierarchical tree of pages with IDs, titles, slugs, URLs, and parent relationships.', 'wp-mcp-toolkit' ),
				'category'            => 'wpmcp-schema',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_type' => array(
							'type'    => 'string',
							'default' => 'page',
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'id'        => array( 'type' => 'integer' ),
							'title'     => array( 'type' => 'string' ),
							'slug'      => array( 'type' => 'string' ),
							'url'       => array( 'type' => 'string' ),
							'parent_id' => array( 'type' => 'integer' ),
							'children'  => array( 'type' => 'array' ),
						),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_page_tree' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'read' );
				},
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
				),
			)
		);
	}

	public function execute_get_page_tree( $input = array() ): array {
		$input     = is_array( $input ) ? $input : array();
		$post_type = ! empty( $input['post_type'] ) ? sanitize_key( $input['post_type'] ) : 'page';

		$pages = get_pages( array(
			'post_type'   => $post_type,
			'sort_column' => 'menu_order,post_title',
		) );

		if ( empty( $pages ) ) {
			return array();
		}

		$flat = array();
		foreach ( $pages as $page ) {
			$flat[ $page->ID ] = array(
				'id'        => $page->ID,
				'title'     => $page->post_title,
				'slug'      => $page->post_name,
				'url'       => get_permalink( $page->ID ),
				'parent_id' => (int) $page->post_parent,
				'children'  => array(),
			);
		}

		$tree = array();
		foreach ( $flat as $id => &$node ) {
			if ( $node['parent_id'] && isset( $flat[ $node['parent_id'] ] ) ) {
				$flat[ $node['parent_id'] ]['children'][] = &$node;
			} else {
				$tree[] = &$node;
			}
		}
		unset( $node );

		return $tree;
	}
}
