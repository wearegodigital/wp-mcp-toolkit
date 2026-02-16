<?php
/**
 * WP MCP Toolkit — Schema / Site Discovery Abilities.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();

class WP_MCP_Toolkit_Schema_Abilities extends WP_MCP_Toolkit_Abstract_Abilities {

	protected function get_abilities(): array {
		return array(
			'wpmcp/get-site-structure' => array(
				'label'         => __( 'Get Site Structure', 'wp-mcp-toolkit' ),
				'description'   => __( 'Start here. Returns a comprehensive overview of the entire WordPress site: all post types with content counts, all taxonomies, active theme, active plugins, and site URL. Use this as your first call to understand what content exists and how the site is organized before making any changes. No input required.', 'wp-mcp-toolkit' ),
				'category'      => 'wpmcp-schema',
				'input_schema'  => self::empty_input_schema(),
				'output_schema' => array(
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
				'callback'   => 'execute_get_site_structure',
				'permission' => 'read',
			),
			'wpmcp/get-content-guide' => array(
				'label'         => __( 'Get Content Workflow Guide', 'wp-mcp-toolkit' ),
				'description'   => __( 'Returns a reference guide for AI agents on how to effectively use WP MCP Toolkit tools. Covers recommended tool call sequences, content update workflows, ACF field patterns, block editing best practices, and common pitfalls. Call this when you are unsure how to approach a content management task. No input required.', 'wp-mcp-toolkit' ),
				'category'      => 'wpmcp-schema',
				'input_schema'  => self::empty_input_schema(),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'guide' => array( 'type' => 'string' ),
					),
				),
				'callback'   => 'execute_get_content_guide',
				'permission' => 'read',
			),
			'wpmcp/get-page-tree' => array(
				'label'         => __( 'Get Page Tree', 'wp-mcp-toolkit' ),
				'description'   => __( 'Returns a hierarchical tree of pages (or any hierarchical post type) showing parent-child relationships. Each node includes id, title, slug, url, parent_id, and children array. Useful for understanding site navigation structure and finding specific pages by their position in the hierarchy. Defaults to "page" post type but accepts any hierarchical type.', 'wp-mcp-toolkit' ),
				'category'      => 'wpmcp-schema',
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'post_type' => array(
							'type'    => 'string',
							'default' => 'page',
						),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
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
				'callback'   => 'execute_get_page_tree',
				'permission' => 'read',
			),
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

	public function execute_get_content_guide( $input = array() ): array {
		$guide = <<<'GUIDE'
# WP MCP Toolkit — Content Workflow Guide

## Getting Started
1. Call `get-site-structure` to understand the site (post types, taxonomies, plugins, theme).
2. Call `get-page-tree` to see the page hierarchy with IDs and URLs.
3. If ACF is active, call `list-field-groups` to discover custom field configurations.

## Reading Content

### Page/Post Content
- `list-posts` → find posts by type, search, or status (returns summaries with IDs)
- `get-post` → get full content for a specific post (raw blocks + rendered HTML + meta + taxonomy terms)
- `parse-blocks` → get structured block data with indices (needed before `update-block-content`)

### ACF Fields
ACF stores data in TWO places — understand the difference:
- **Post-level fields** (stored in wp_postmeta): Use `get-post-fields` / `update-post-fields`
  - Example: A "hero_title" field on a page template
- **Block-level fields** (stored in block attrs within post_content): Use `get-block-fields` / `update-block-fields`
  - Example: An "acf/testimonial" block's "quote" field embedded in page content

To determine which: Call `list-field-groups` and check location_rules. If the group targets a post type, it's post-level. If it targets a block type, it's block-level.

## Updating Content

### Simple Field Updates (ACF post-level)
```
1. get-post-fields (post_id) → see current values
2. update-post-fields (post_id, {field_name: new_value}) → update
```

### ACF Block Field Updates
```
1. list-acf-blocks → discover block types
2. get-block-fields (post_id, block_name: "acf/hero") → see current values
3. update-block-fields (post_id, block_name: "acf/hero", fields: {...}) → update
```
HTML in field values is preserved correctly.

### Block Content Updates (non-ACF blocks like paragraphs, headings)
```
1. parse-blocks (post_id) → get block list with indices
2. update-block-content (post_id, block_index: N, new_content: "<p>New text</p>") → update
```
LIMITATION: `update-block-content` only works on top-level blocks. For blocks nested inside columns or groups, use `get-post` to read raw content, modify the specific text with string replacement, then `update-post` to save.

### Creating New Content
```
1. list-post-types → confirm the post type exists
2. create-post (post_type, title, content, status: "draft") → create
3. update-post-fields (new_post_id, fields: {...}) → set ACF fields if needed
```
Content should be valid Gutenberg block markup. Use status "draft" first, verify, then update to "publish".

## Common Pitfalls

1. **ACF post-level vs block-level**: Don't confuse them. `update-post-fields` changes wp_postmeta; `update-block-fields` changes data inside block comments in post_content.

2. **Nested blocks**: Most real pages use columns/groups. `update-block-content` only targets top-level blocks. For nested content, use `get-post` + string replacement + `update-post`.

3. **Block indices**: The index from `parse-blocks` is a flat index across all blocks (including nested). Always call `parse-blocks` fresh before using an index — indices change when blocks are added/removed.

4. **Content sanitization**: `create-post` and `update-post` run content through `wp_kses_post`, which strips unsafe HTML. Block markup (<!-- wp:... -->) is preserved.

5. **ACF block field names**: Use the field name (e.g. "hero_title"), not the field key (e.g. "field_abc123"). Get field names from `get-block-fields` or `get-field-group`.

6. **Taxonomy terms**: When using `update-post` with taxonomy_terms, pass term names or IDs as arrays: {"category": ["News", "Featured"]}.

## Recommended Workflow for Site-Wide Content Updates
1. `get-site-structure` → understand the site
2. `get-page-tree` → map all pages
3. For each page: `get-post` → review content → plan changes
4. Apply changes using the appropriate tool for each content type
5. Verify by calling `get-post` again to confirm changes took effect
GUIDE;

		return array( 'guide' => $guide );
	}

	public function execute_get_page_tree( $input = array() ): array {
		$input     = self::normalize_input( $input );
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
