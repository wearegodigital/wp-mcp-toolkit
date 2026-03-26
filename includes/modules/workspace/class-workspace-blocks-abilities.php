<?php
/**
 * Workspace Block abilities — scaffold and manage Gutenberg blocks in the workspace.
 *
 * @package WP_MCP_Toolkit
 * @since   2.1.0
 */

defined( 'ABSPATH' ) || exit;

class WP_MCP_Toolkit_Workspace_Blocks_Abilities extends WP_MCP_Toolkit_Abstract_Abilities {

	use WP_MCP_Toolkit_Workspace_Helpers;

	protected function get_abilities(): array {
		$c = 'wpmcp-workspace-blocks';
		$p = 'manage_options';
		$s = fn( $r, $pr ) => [ 'type' => 'object', 'required' => $r, 'properties' => $pr, 'additionalProperties' => false ];
		$o = fn( $pr ) => [ 'type' => 'object', 'properties' => $pr ];
		$w = fn( $cb, $ro, $dest, $idemp ) => [ 'callback' => $cb, 'permission' => $p, 'readonly' => $ro, 'destructive' => $dest, 'idempotent' => $idemp ];

		return [
			'wpmcp-workspace/scaffold-block' => [
				'label'         => __( 'Scaffold Block', 'wp-mcp-toolkit' ),
				'description'   => __( 'Scaffolds a Gutenberg block in the workspace with block.json, render.php, and style.css.', 'wp-mcp-toolkit' ),
				'category'      => $c,
				'input_schema'  => $s( [ 'block_name', 'title' ], [
					'block_name'  => [ 'type' => 'string' ],
					'title'       => [ 'type' => 'string' ],
					'description' => [ 'type' => 'string', 'default' => '' ],
					'category'    => [ 'type' => 'string', 'default' => 'widgets' ],
					'icon'        => [ 'type' => 'string', 'default' => 'block-default' ],
					'attributes'  => [ 'type' => 'object', 'default' => [] ],
					'render_php'  => [ 'type' => 'string', 'default' => '' ],
					'css'         => [ 'type' => 'string', 'default' => '' ],
				] ),
				'output_schema' => $o( [
					'block_name'   => [ 'type' => 'string' ],
					'files'        => [ 'type' => 'array', 'items' => [ 'type' => 'string' ] ],
					'registration' => [ 'type' => 'string' ],
				] ),
			] + $w( 'execute_scaffold_block', false, false, true ),

			'wpmcp-workspace/update-block' => [
				'label'         => __( 'Update Block', 'wp-mcp-toolkit' ),
				'description'   => __( 'Updates one or more files of an existing workspace block.', 'wp-mcp-toolkit' ),
				'category'      => $c,
				'input_schema'  => $s( [ 'block_name' ], [
					'block_name'  => [ 'type' => 'string' ],
					'render_php'  => [ 'type' => 'string', 'default' => '' ],
					'css'         => [ 'type' => 'string', 'default' => '' ],
					'title'       => [ 'type' => 'string', 'default' => '' ],
					'description' => [ 'type' => 'string', 'default' => '' ],
					'attributes'  => [ 'type' => 'object', 'default' => [] ],
				] ),
				'output_schema' => $o( [
					'block_name'    => [ 'type' => 'string' ],
					'updated_files' => [ 'type' => 'array', 'items' => [ 'type' => 'string' ] ],
				] ),
			] + $w( 'execute_update_block', false, false, true ),

			'wpmcp-workspace/list-workspace-blocks' => [
				'label'         => __( 'List Workspace Blocks', 'wp-mcp-toolkit' ),
				'description'   => __( 'Lists all Gutenberg blocks registered in the workspace.', 'wp-mcp-toolkit' ),
				'category'      => $c,
				'input_schema'  => [ 'type' => 'object', 'properties' => [], 'additionalProperties' => false ],
				'output_schema' => [ 'type' => 'array', 'items' => [ 'type' => 'object' ] ],
			] + $w( 'execute_list_workspace_blocks', true, false, true ),
		];
	}

	// -- Helpers --------------------------------------------------------------

	/**
	 * Build InspectorControls JS fragment from block attributes schema.
	 *
	 * Skips array/object types (like 'items') that can't be represented with simple controls.
	 *
	 * @since 2.2.0
	 * @param array $attributes Block attributes schema.
	 * @return string JS snippet for {{INSPECTOR_CONTROLS}} placeholder.
	 */
	private static function build_inspector_controls_js( array $attributes ): string {
		if ( empty( $attributes ) ) {
			return 'null,';
		}

		$controls = [];
		foreach ( $attributes as $name => $config ) {
			$type    = $config['type'] ?? 'string';
			$label   = ucwords( str_replace( [ '-', '_' ], ' ', $name ) );
			$default = isset( $config['default'] ) ? wp_json_encode( $config['default'] ) : "''";

			// Escape values before JS string interpolation.
			$label = addslashes( $label );
			$name  = addslashes( $name );

			switch ( $type ) {
				case 'string':
					$controls[] = "el( components.TextControl, { label: '{$label}', value: props.attributes['{$name}'] || {$default}, onChange: function( val ) { setAttributes( { '{$name}': val } ); } } )";
					break;
				case 'number':
				case 'integer':
					$min = isset( $config['minimum'] ) ? ", min: {$config['minimum']}" : '';
					$max = isset( $config['maximum'] ) ? ", max: {$config['maximum']}" : '';
					$controls[] = "el( components.RangeControl, { label: '{$label}', value: props.attributes['{$name}'] || {$default}, onChange: function( val ) { setAttributes( { '{$name}': val } ); }{$min}{$max} } )";
					break;
				case 'boolean':
					$controls[] = "el( components.ToggleControl, { label: '{$label}', checked: !! props.attributes['{$name}'], onChange: function( val ) { setAttributes( { '{$name}': val } ); } } )";
					break;
				// Skip array/object types — can't be represented with simple controls.
			}
		}

		if ( empty( $controls ) ) {
			return 'null,';
		}

		$controls_js = implode( ",\n\t\t\t\t\t", $controls );

		return "el( InspectorControls, null,\n\t\t\t\tel( PanelBody, { title: 'Settings', initialOpen: true },\n\t\t\t\t\t{$controls_js}\n\t\t\t\t)\n\t\t\t),";
	}

	// -- Execute methods ------------------------------------------------------

	/**
	 * Scaffold a new Gutenberg block in the workspace.
	 *
	 * @since 2.1.0
	 * @param mixed $input Ability input.
	 * @return array|\WP_Error
	 */
	public function execute_scaffold_block( $input = [] ): array|\WP_Error {
		$input = self::normalize_input( $input );
		$init  = $this->ensure_workspace();
		if ( is_wp_error( $init ) ) {
			return $init;
		}

		$block_name = sanitize_text_field( $input['block_name'] ?? '' );
		if ( ! preg_match( '/^[a-z0-9]+(-[a-z0-9]+)*$/', $block_name ) ) {
			return new \WP_Error( 'wpmcp_invalid_block_name', 'block_name must be kebab-case (e.g. "my-block").' );
		}

		// Guard against duplicate blocks in the manifest.
		$existing = WP_MCP_Toolkit_Workspace_Manifest::get_artifact( $block_name );
		if ( null !== $existing && 'block' === ( $existing['type'] ?? '' ) ) {
			return new \WP_Error( 'wpmcp_duplicate_artifact', 'Block already exists: ' . $block_name );
		}

		$title       = sanitize_text_field( $input['title'] ?? $block_name );
		$description = sanitize_text_field( $input['description'] ?? '' );
		$category    = sanitize_text_field( $input['category'] ?? 'widgets' );
		$icon        = sanitize_text_field( $input['icon'] ?? 'block-default' );
		$attributes  = $input['attributes'] ?? [];
		$render_php  = $input['render_php'] ?? '';
		$css         = $input['css'] ?? '';

		// Scan code inputs for blocked content.
		if ( '' !== $render_php ) {
			$scan = WP_MCP_Toolkit_Workspace_Validator::scan_code_for_blocked_content( $render_php, 'render_php' );
			if ( is_wp_error( $scan ) ) {
				return $scan;
			}
		}
		if ( '' !== $css ) {
			$scan = WP_MCP_Toolkit_Workspace_Validator::scan_css_for_blocked_content( $css );
			if ( is_wp_error( $scan ) ) {
				return $scan;
			}
		}

		// Encode attributes — use {} for empty, otherwise pretty-print.
		$attributes_json = empty( $attributes )
			? '{}'
			: wp_json_encode( $attributes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

		// Default render body.
		$php_open  = '<' . '?php';
		$php_close = '?' . '>';
		if ( '' === $render_php ) {
			$render_php = '<div ' . $php_open . ' echo get_block_wrapper_attributes(); ' . $php_close . ">\n\t<p>" . $php_open . ' echo esc_html( $attributes[\'content\'] ?? \'\' ); ' . $php_close . "</p>\n</div>";
		}

		// If render_php is pure PHP (no HTML tags), wrap in php tags and block wrapper.
		if ( false === strpos( $render_php, '<' ) ) {
			$render_php = $php_open . "\n" . $render_php . "\n" . $php_close;
		}

		// Ensure block wrapper is present.
		if ( false === strpos( $render_php, 'get_block_wrapper_attributes' ) ) {
			$render_php = '<div ' . $php_open . ' echo get_block_wrapper_attributes(); ' . $php_close . ">\n\t" . $render_php . "\n</div>";
		}

		// Default CSS.
		if ( '' === $css ) {
			$css = 'padding: 1rem;';
		}

		// Render block.json.
		$block_json = WP_MCP_Toolkit_Workspace_Container::render_template(
			self::tpl( 'block-json.tpl' ),
			[
				'BLOCK_NAME'        => $block_name,
				'BLOCK_TITLE'       => $title,
				'BLOCK_DESCRIPTION' => $description,
				'BLOCK_CATEGORY'    => $category,
				'BLOCK_ICON'        => $icon,
				'BLOCK_ATTRIBUTES'  => $attributes_json,
			]
		);

		// Render render.php.
		$render_content = WP_MCP_Toolkit_Workspace_Container::render_template(
			self::tpl( 'block-render.php.tpl' ),
			[
				'BLOCK_NAME'  => $block_name,
				'RENDER_BODY' => $render_php,
			]
		);

		// Render style.css.
		$style_content = WP_MCP_Toolkit_Workspace_Container::render_template(
			self::tpl( 'block-style.css.tpl' ),
			[
				'BLOCK_NAME' => $block_name,
				'CUSTOM_CSS' => $css,
			]
		);

		// Render editor.js.
		$editor_js = WP_MCP_Toolkit_Workspace_Container::render_template(
			self::tpl( 'block-editor.js.tpl' ),
			[
				'BLOCK_NAME'         => $block_name,
				'INSPECTOR_CONTROLS' => self::build_inspector_controls_js( $attributes ),
			]
		);

		// editor.asset.php — declares wp-blocks, wp-element, etc. for enqueueing.
		$asset_php  = $php_open . " return ['dependencies' => ['wp-blocks', 'wp-element', 'wp-server-side-render', 'wp-block-editor', 'wp-components'], 'version' => '1.0.0'];";

		// Write all 5 files.
		$json_path   = "blocks/{$block_name}/block.json";
		$render_path = "blocks/{$block_name}/render.php";
		$style_path  = "blocks/{$block_name}/style.css";
		$editor_path = "blocks/{$block_name}/editor.js";
		$asset_path  = "blocks/{$block_name}/editor.asset.php";

		$written = WP_MCP_Toolkit_Workspace_File_Writer::write_file( $json_path, $block_json );
		if ( is_wp_error( $written ) ) {
			return $written;
		}

		$written = WP_MCP_Toolkit_Workspace_File_Writer::write_file( $render_path, $render_content );
		if ( is_wp_error( $written ) ) {
			return $written;
		}

		$written = WP_MCP_Toolkit_Workspace_File_Writer::write_file( $style_path, $style_content );
		if ( is_wp_error( $written ) ) {
			return $written;
		}

		$written = WP_MCP_Toolkit_Workspace_File_Writer::write_file( $editor_path, $editor_js );
		if ( is_wp_error( $written ) ) {
			return $written;
		}

		$written = WP_MCP_Toolkit_Workspace_File_Writer::write_file( $asset_path, $asset_php );
		if ( is_wp_error( $written ) ) {
			return $written;
		}

		// Register in manifest.
		$saved = $this->save_artifact( $block_name, 'block', $json_path, 'wpmcp-workspace/scaffold-block' );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		return [
			'block_name'   => $block_name,
			'files'        => [ $json_path, $render_path, $style_path, $editor_path, $asset_path ],
			'registration' => "wpmcp-workspace/{$block_name}",
		];
	}

	/**
	 * Update one or more files of an existing workspace block.
	 *
	 * @since 2.1.0
	 * @param mixed $input Ability input.
	 * @return array|\WP_Error
	 */
	public function execute_update_block( $input = [] ): array|\WP_Error {
		$input      = self::normalize_input( $input );
		$block_name = sanitize_text_field( $input['block_name'] ?? '' );

		$artifact = WP_MCP_Toolkit_Workspace_Manifest::get_artifact( $block_name );
		if ( null === $artifact || 'block' !== ( $artifact['type'] ?? '' ) ) {
			return new \WP_Error( 'wpmcp_not_found', "Block not found in workspace: {$block_name}" );
		}

		$updated_files = [];

		// Scan code inputs for blocked content.
		if ( ! empty( $input['render_php'] ) ) {
			$scan = WP_MCP_Toolkit_Workspace_Validator::scan_code_for_blocked_content( $input['render_php'], 'render_php' );
			if ( is_wp_error( $scan ) ) {
				return $scan;
			}
		}
		if ( ! empty( $input['css'] ) ) {
			$scan = WP_MCP_Toolkit_Workspace_Validator::scan_css_for_blocked_content( $input['css'] );
			if ( is_wp_error( $scan ) ) {
				return $scan;
			}
		}

		// Update render.php if provided.
		if ( ! empty( $input['render_php'] ) ) {
			$render_php  = $input['render_php'];
			$render_path = "blocks/{$block_name}/render.php";
			$php_open    = '<' . '?php';
			$php_close   = '?' . '>';

			// If render_php is pure PHP (no HTML tags), wrap in php tags.
			if ( false === strpos( $render_php, '<' ) ) {
				$render_php = $php_open . "\n" . $render_php . "\n" . $php_close;
			}

			// Ensure block wrapper is present.
			if ( false === strpos( $render_php, 'get_block_wrapper_attributes' ) ) {
				$render_php = '<div ' . $php_open . ' echo get_block_wrapper_attributes(); ' . $php_close . ">\n\t" . $render_php . "\n</div>";
			}

			$render_content = WP_MCP_Toolkit_Workspace_Container::render_template(
				self::tpl( 'block-render.php.tpl' ),
				[
					'BLOCK_NAME'  => $block_name,
					'RENDER_BODY' => $render_php,
				]
			);
			$written = WP_MCP_Toolkit_Workspace_File_Writer::write_file( $render_path, $render_content );
			if ( is_wp_error( $written ) ) {
				return $written;
			}
			$updated_files[] = $render_path;
		}

		// Update style.css if provided.
		if ( ! empty( $input['css'] ) ) {
			$style_path = "blocks/{$block_name}/style.css";
			$style_content = WP_MCP_Toolkit_Workspace_Container::render_template(
				self::tpl( 'block-style.css.tpl' ),
				[
					'BLOCK_NAME' => $block_name,
					'CUSTOM_CSS' => $input['css'],
				]
			);
			$written = WP_MCP_Toolkit_Workspace_File_Writer::write_file( $style_path, $style_content );
			if ( is_wp_error( $written ) ) {
				return $written;
			}
			$updated_files[] = $style_path;
		}

		// Update block.json if title, description, or attributes provided.
		$json_fields = array_filter( [
			'title'       => $input['title'] ?? '',
			'description' => $input['description'] ?? '',
			'attributes'  => $input['attributes'] ?? null,
		], fn( $v ) => '' !== $v && null !== $v );

		if ( ! empty( $json_fields ) ) {
			$json_path = "blocks/{$block_name}/block.json";
			$abs_path  = WP_MCP_Toolkit_Workspace_Container::get_active_dir() . $json_path;

			$existing_json = [];
			if ( file_exists( $abs_path ) ) {
				$raw = file_get_contents( $abs_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				$existing_json = json_decode( $raw, true ) ?? [];
			}

			if ( isset( $json_fields['title'] ) ) {
				$existing_json['title'] = sanitize_text_field( $json_fields['title'] );
			}
			if ( isset( $json_fields['description'] ) ) {
				$existing_json['description'] = sanitize_text_field( $json_fields['description'] );
			}
			if ( isset( $json_fields['attributes'] ) ) {
				$existing_json['attributes'] = $json_fields['attributes'];
			}

			$new_json = wp_json_encode( $existing_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
			$written  = WP_MCP_Toolkit_Workspace_File_Writer::write_file( $json_path, $new_json );
			if ( is_wp_error( $written ) ) {
				return $written;
			}
			$updated_files[] = $json_path;
		}

		// Refresh manifest checksum.
		WP_MCP_Toolkit_Workspace_Manifest::update_artifact( $block_name, [ 'file' => $artifact['file'] ] );
		WP_MCP_Toolkit_Workspace_Container::reinitialize();

		return [
			'block_name'    => $block_name,
			'updated_files' => $updated_files,
		];
	}

	/**
	 * List all Gutenberg blocks in the workspace.
	 *
	 * @since 2.1.0
	 * @param mixed $input Ability input.
	 * @return array
	 */
	public function execute_list_workspace_blocks( $input = [] ): array {
		$blocks    = WP_MCP_Toolkit_Workspace_Manifest::list_artifacts( 'block' );
		$summaries = [];

		foreach ( $blocks as $block ) {
			$block_name = $block['name'] ?? '';
			$json_path  = WP_MCP_Toolkit_Workspace_Container::get_active_dir() . "blocks/{$block_name}/block.json";

			$title       = '';
			$description = '';

			if ( file_exists( $json_path ) ) {
				$raw  = file_get_contents( $json_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				$data = json_decode( $raw, true );
				if ( is_array( $data ) ) {
					$title       = $data['title'] ?? '';
					$description = $data['description'] ?? '';
				}
			}

			$summaries[] = [
				'block_name'   => $block_name,
				'title'        => $title,
				'description'  => $description,
				'file'         => $block['file'] ?? '',
				'created_at'   => $block['created_at'] ?? '',
				'updated_at'   => $block['updated_at'] ?? '',
			];
		}

		return $summaries;
	}
}
