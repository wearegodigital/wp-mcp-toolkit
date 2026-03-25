<?php
/**
 * Workspace Bricks abilities — Bricks Builder element scaffolding.
 *
 * @package WP_MCP_Toolkit
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

class WP_MCP_Toolkit_Workspace_Bricks_Abilities extends WP_MCP_Toolkit_Abstract_Abilities {

	use WP_MCP_Toolkit_Workspace_Helpers;

	protected function get_abilities(): array {
		$c = 'wpmcp-bricks';
		$p = 'manage_options';
		$s = fn( $r, $pr ) => [ 'type' => 'object', 'required' => $r, 'properties' => $pr, 'additionalProperties' => false ];
		$o = fn( $pr ) => [ 'type' => 'object', 'properties' => $pr ];
		$w = fn( $cb, $ro, $dest, $idemp ) => [ 'callback' => $cb, 'permission' => $p, 'readonly' => $ro, 'destructive' => $dest, 'idempotent' => $idemp ];

		return [
			'wpmcp-bricks/scaffold-bricks-element' => [
				'label'         => __( 'Scaffold Bricks Element', 'wp-mcp-toolkit' ),
				'description'   => __( 'Scaffolds a Bricks Builder element in the workspace with PHP class and optional CSS.', 'wp-mcp-toolkit' ),
				'category'      => $c,
				'input_schema'  => $s( [ 'element_name', 'label' ], [
					'element_name' => [ 'type' => 'string' ],
					'label'        => [ 'type' => 'string' ],
					'description'  => [ 'type' => 'string', 'default' => '' ],
					'category'     => [ 'type' => 'string', 'default' => 'wpmcp-workspace' ],
					'icon'         => [ 'type' => 'string', 'default' => 'ti-layout-cta-right' ],
					'controls'     => [ 'type' => 'array', 'default' => [] ],
					'render_php'   => [ 'type' => 'string', 'default' => '' ],
					'css'          => [ 'type' => 'string', 'default' => '' ],
				] ),
				'output_schema' => $o( [
					'element_name'  => [ 'type' => 'string' ],
					'class_name'    => [ 'type' => 'string' ],
					'files'         => [ 'type' => 'array' ],
					'registration'  => [ 'type' => 'string' ],
				] ),
			] + $w( 'execute_scaffold_bricks_element', false, false, true ),
			'wpmcp-bricks/update-bricks-element' => [
				'label'         => __( 'Update Bricks Element', 'wp-mcp-toolkit' ),
				'description'   => __( 'Updates an existing Bricks Builder element in the workspace.', 'wp-mcp-toolkit' ),
				'category'      => $c,
				'input_schema'  => $s( [ 'element_name' ], [
					'element_name' => [ 'type' => 'string' ],
					'controls'     => [ 'type' => 'array', 'default' => [] ],
					'render_php'   => [ 'type' => 'string', 'default' => '' ],
					'css'          => [ 'type' => 'string', 'default' => '' ],
				] ),
				'output_schema' => $o( [
					'element_name'  => [ 'type' => 'string' ],
					'updated_files' => [ 'type' => 'array' ],
				] ),
			] + $w( 'execute_update_bricks_element', false, false, true ),
			'wpmcp-bricks/list-bricks-elements' => [
				'label'         => __( 'List Bricks Elements', 'wp-mcp-toolkit' ),
				'description'   => __( 'Lists all Bricks Builder elements in the workspace.', 'wp-mcp-toolkit' ),
				'category'      => $c,
				'input_schema'  => [ 'type' => 'object', 'properties' => [], 'additionalProperties' => false ],
				'output_schema' => [ 'type' => 'array', 'items' => [ 'type' => 'object' ] ],
			] + $w( 'execute_list_bricks_elements', true, false, true ),
		];
	}

	// -- Helpers --------------------------------------------------------------

	private static function tpl( string $name ): string {
		return __DIR__ . '/templates/' . $name;
	}

	private static function kebab_to_class_name( string $kebab ): string {
		return 'WPMCP_Workspace_Bricks_' . str_replace( ' ', '_', ucwords( str_replace( '-', ' ', $kebab ) ) );
	}

	private static function build_controls_php( array $controls ): string {
		if ( empty( $controls ) ) {
			return '// No controls defined.';
		}

		$lines = [];
		foreach ( $controls as $control ) {
			$control = (array) $control;
			$name    = sanitize_key( $control['name'] ?? '' );
			$type    = sanitize_text_field( $control['type'] ?? 'text' );
			$label   = sanitize_text_field( $control['label'] ?? ucwords( str_replace( '-', ' ', $name ) ) );

			if ( '' === $name ) {
				continue;
			}

			$parts = [
				"'type' => " . var_export( $type, true ),
				"'label' => " . var_export( $label, true ),
			];

			if ( array_key_exists( 'default', $control ) ) {
				$parts[] = "'default' => " . var_export( $control['default'], true );
			}

			if ( ! empty( $control['options'] ) && is_array( $control['options'] ) ) {
				$opts = [];
				foreach ( $control['options'] as $k => $v ) {
					$opts[] = var_export( (string) $k, true ) . ' => ' . var_export( (string) $v, true );
				}
				$parts[] = "'options' => [ " . implode( ', ', $opts ) . ' ]';
			}

			$lines[] = "\t\t\$this->controls[" . var_export( $name, true ) . '] = [ ' . implode( ', ', $parts ) . ' ];';
		}

		return implode( "\n", $lines );
	}

	private static function validate_element_name( string $name ): true|\WP_Error {
		if ( '' === $name ) {
			return new \WP_Error( 'wpmcp_invalid_input', 'element_name is required.' );
		}
		if ( ! preg_match( '/^[a-z0-9]+(-[a-z0-9]+)*$/', $name ) ) {
			return new \WP_Error( 'wpmcp_invalid_input', 'element_name must be kebab-case (lowercase alphanumeric with hyphens): ' . $name );
		}
		return true;
	}

	// -- Execute methods ------------------------------------------------------

	/**
	 * Scaffold a new Bricks Builder element.
	 *
	 * @since 2.0.0
	 * @param array|object $input Ability input.
	 * @return array|\WP_Error
	 */
	public function execute_scaffold_bricks_element( $input = [] ): array|\WP_Error {
		$input = self::normalize_input( $input );
		$init  = $this->ensure_workspace();
		if ( is_wp_error( $init ) ) {
			return $init;
		}

		$element_name = sanitize_key( $input['element_name'] ?? '' );
		$valid        = self::validate_element_name( $element_name );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		// Guard against duplicate — update-bricks-element handles existing elements.
		$existing = WP_MCP_Toolkit_Workspace_Manifest::get_artifact( $element_name );
		if ( $existing ) {
			return new \WP_Error( 'wpmcp_duplicate_artifact', 'Bricks element already exists: ' . $element_name . '. Use update-bricks-element instead.' );
		}

		$label      = sanitize_text_field( $input['label'] ?? '' );
		if ( '' === $label ) {
			return new \WP_Error( 'wpmcp_invalid_input', 'label is required.' );
		}

		$category   = sanitize_text_field( $input['category'] ?? 'wpmcp-workspace' );
		$icon       = sanitize_text_field( $input['icon'] ?? 'ti-layout-cta-right' );
		$controls   = (array) ( $input['controls'] ?? [] );
		$render_php = $input['render_php'] ?? '';
		$css        = $input['css'] ?? '';
		$class_name = self::kebab_to_class_name( $element_name );

		$element_php = WP_MCP_Toolkit_Workspace_Container::render_template(
			self::tpl( 'bricks-element.php.tpl' ),
			[
				'ELEMENT_CLASS'        => $class_name,
				'ELEMENT_NAME'         => $element_name,
				'ELEMENT_LABEL'        => $label,
				'ELEMENT_CATEGORY'     => $category,
				'ELEMENT_ICON'         => $icon,
				'ELEMENT_CSS_SELECTOR' => 'brxe-' . $element_name,
				'ELEMENT_CONTROLS'     => self::build_controls_php( $controls ),
				'ELEMENT_RENDER'       => '' !== $render_php ? $render_php : "echo '<p>' . esc_html( \$this->get_label() ) . '</p>';",
			]
		);

		if ( '' === $element_php ) {
			return new \WP_Error( 'wpmcp_template_error', 'Failed to render bricks-element.php.tpl template.' );
		}

		$syntax = WP_MCP_Toolkit_Workspace_Validator::validate_php_syntax( $element_php );
		if ( is_wp_error( $syntax ) ) {
			return $syntax;
		}

		$php_path = "bricks/{$element_name}/element.php";
		$written  = WP_MCP_Toolkit_Workspace_File_Writer::write_file( $php_path, $element_php );
		if ( is_wp_error( $written ) ) {
			return $written;
		}

		$style_css = WP_MCP_Toolkit_Workspace_Container::render_template(
			self::tpl( 'bricks-element-style.css.tpl' ),
			[
				'ELEMENT_NAME' => $element_name,
				'CUSTOM_CSS'   => '' !== $css ? $css : '/* Add custom styles here. */',
			]
		);

		$css_path = "bricks/{$element_name}/style.css";
		$written  = WP_MCP_Toolkit_Workspace_File_Writer::write_file( $css_path, $style_css );
		if ( is_wp_error( $written ) ) {
			return $written;
		}

		$saved = $this->save_artifact( $element_name, 'bricks-element', $php_path, 'wpmcp-bricks/scaffold-bricks-element' );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		return [
			'element_name' => $element_name,
			'class_name'   => $class_name,
			'files'        => [ $php_path, $css_path ],
			'registration' => $element_name,
		];
	}

	/**
	 * Update an existing Bricks Builder element.
	 *
	 * @since 2.0.0
	 * @param array|object $input Ability input.
	 * @return array|\WP_Error
	 */
	public function execute_update_bricks_element( $input = [] ): array|\WP_Error {
		$input = self::normalize_input( $input );

		$element_name = sanitize_key( $input['element_name'] ?? '' );
		$valid        = self::validate_element_name( $element_name );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		$artifact = WP_MCP_Toolkit_Workspace_Manifest::get_artifact( $element_name );
		if ( ! $artifact ) {
			return new \WP_Error( 'wpmcp_not_found', 'Bricks element not found: ' . $element_name . '. Use scaffold-bricks-element first.' );
		}

		$controls   = $input['controls'] ?? [];
		$render_php = $input['render_php'] ?? '';
		$css        = $input['css'] ?? '';

		$updated_files = [];
		$php_path      = "bricks/{$element_name}/element.php";

		$has_controls = is_array( $controls ) && ! empty( $controls );
		$has_render   = is_string( $render_php ) && '' !== $render_php;

		if ( $has_controls || $has_render ) {
			$existing_php = WP_MCP_Toolkit_Workspace_File_Writer::read_file( $php_path );
			if ( is_wp_error( $existing_php ) ) {
				return $existing_php;
			}

			if ( $has_controls ) {
				$existing_php = preg_replace(
					'/(public function set_controls\(\): void \{)\n.*?(\n\t\})/s',
					'$1' . "\n" . self::build_controls_php( (array) $controls ) . '$2',
					$existing_php
				);
			}

			if ( $has_render ) {
				$existing_php = preg_replace(
					'/(echo "<div \{\$root_attributes\}>";)\n.*?(\n\t\techo \'<\/div>\';)/s',
					'$1' . "\n\t\t" . $render_php . '$2',
					$existing_php
				);
			}

			$syntax = WP_MCP_Toolkit_Workspace_Validator::validate_php_syntax( $existing_php );
			if ( is_wp_error( $syntax ) ) {
				return $syntax;
			}

			$written = WP_MCP_Toolkit_Workspace_File_Writer::write_file( $php_path, $existing_php );
			if ( is_wp_error( $written ) ) {
				return $written;
			}
			$updated_files[] = $php_path;
		}

		if ( is_string( $css ) && '' !== $css ) {
			$css_path  = "bricks/{$element_name}/style.css";
			$style_css = WP_MCP_Toolkit_Workspace_Container::render_template(
				self::tpl( 'bricks-element-style.css.tpl' ),
				[
					'ELEMENT_NAME' => $element_name,
					'CUSTOM_CSS'   => $css,
				]
			);

			$written = WP_MCP_Toolkit_Workspace_File_Writer::write_file( $css_path, $style_css );
			if ( is_wp_error( $written ) ) {
				return $written;
			}
			$updated_files[] = $css_path;
		}

		if ( empty( $updated_files ) ) {
			return new \WP_Error( 'wpmcp_invalid_input', 'No updates provided. Supply controls, render_php, or css.' );
		}

		WP_MCP_Toolkit_Workspace_Manifest::update_artifact( $element_name, [ 'file' => $php_path ] );
		WP_MCP_Toolkit_Workspace_Container::reinitialize();

		return [
			'element_name'  => $element_name,
			'updated_files' => $updated_files,
		];
	}

	/**
	 * List all Bricks Builder elements in the workspace.
	 *
	 * @since 2.0.0
	 * @param array|object $input Ability input (unused).
	 * @return array
	 */
	public function execute_list_bricks_elements( $input = [] ): array {
		$artifacts = WP_MCP_Toolkit_Workspace_Manifest::list_artifacts( 'bricks-element' );

		foreach ( $artifacts as &$a ) {
			$abs = WP_MCP_Toolkit_Workspace_Container::get_active_dir() . ( $a['file'] ?? '' );
			$a['file_size'] = file_exists( $abs ) ? filesize( $abs ) : 0;
		}

		return $artifacts;
	}
}
