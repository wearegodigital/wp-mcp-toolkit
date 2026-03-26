<?php
/**
 * Workspace ACF Block abilities — scaffold ACF-powered Gutenberg blocks in the workspace.
 *
 * @package WP_MCP_Toolkit
 * @since   2.2.0
 */

defined( 'ABSPATH' ) || exit;

class WP_MCP_Toolkit_Workspace_ACF_Blocks_Abilities extends WP_MCP_Toolkit_Abstract_Abilities {

	use WP_MCP_Toolkit_Workspace_Helpers;

	/**
	 * Supported ACF field types for validation.
	 *
	 * @var string[]
	 */
	private const SUPPORTED_FIELD_TYPES = [
		'text', 'textarea', 'number', 'image', 'select',
		'true_false', 'repeater', 'wysiwyg', 'url', 'email',
	];

	protected function get_abilities(): array {
		$c = 'wpmcp-workspace-acf-blocks';
		$p = 'manage_options';
		$s = fn( $r, $pr ) => [ 'type' => 'object', 'required' => $r, 'properties' => $pr, 'additionalProperties' => false ];
		$o = fn( $pr ) => [ 'type' => 'object', 'properties' => $pr ];
		$w = fn( $cb, $ro, $dest, $idemp ) => [ 'callback' => $cb, 'permission' => $p, 'readonly' => $ro, 'destructive' => $dest, 'idempotent' => $idemp ];

		return [
			'wpmcp-workspace/scaffold-acf-block' => [
				'label'         => __( 'Scaffold ACF Block', 'wp-mcp-toolkit' ),
				'description'   => __( 'Scaffolds an ACF-powered Gutenberg block in the workspace with register.php, render.php, fields.php (or acf-json), and style.css. Requires the ACF plugin.', 'wp-mcp-toolkit' ),
				'category'      => $c,
				'input_schema'  => $s( [ 'block_name', 'title', 'fields' ], [
					'block_name'    => [ 'type' => 'string', 'description' => 'Kebab-case block name (e.g. "feature-grid"). Auto-prefixed with acf/.' ],
					'title'         => [ 'type' => 'string' ],
					'description'   => [ 'type' => 'string', 'default' => '' ],
					'category'      => [ 'type' => 'string', 'default' => 'common' ],
					'icon'          => [ 'type' => 'string', 'default' => 'block-default' ],
					'fields'        => [ 'type' => 'array', 'items' => [ 'type' => 'object' ], 'description' => 'Array of field definitions: [{type, label, name, default}]' ],
					'field_storage' => [ 'type' => 'string', 'default' => 'php', 'description' => 'How to store the field group: "php" (acf_add_local_field_group) or "json" (acf-json sync)' ],
					'render_php'    => [ 'type' => 'string', 'default' => '' ],
					'css'           => [ 'type' => 'string', 'default' => '' ],
				] ),
				'output_schema' => $o( [
					'block_name'   => [ 'type' => 'string' ],
					'files'        => [ 'type' => 'array', 'items' => [ 'type' => 'string' ] ],
					'registration' => [ 'type' => 'string' ],
					'field_count'  => [ 'type' => 'integer' ],
				] ),
			] + $w( 'execute_scaffold_acf_block', false, false, true ),
		];
	}

	// -- Helpers --------------------------------------------------------------

	/**
	 * Map a simplified field definition to a full ACF field config array.
	 *
	 * @since 2.2.0
	 * @param array  $field      Simplified field: {type, label, name, default, choices, sub_fields}.
	 * @param string $block_name Kebab-case block name, used to generate unique field keys.
	 * @return array ACF field config.
	 */
	private function build_acf_field_config( array $field, string $block_name ): array {
		$name       = sanitize_key( $field['name'] ?? '' );
		$type       = sanitize_text_field( $field['type'] ?? 'text' );
		$label      = sanitize_text_field( $field['label'] ?? ucwords( str_replace( [ '-', '_' ], ' ', $name ) ) );
		$block_slug = str_replace( '-', '_', $block_name );

		if ( ! in_array( $type, self::SUPPORTED_FIELD_TYPES, true ) ) {
			$type = 'text';
		}

		$acf_field = [
			'key'   => 'field_' . $block_slug . '_' . $name,
			'label' => $label,
			'name'  => $name,
			'type'  => $type,
		];

		// Default value.
		if ( array_key_exists( 'default', $field ) ) {
			$acf_field['default_value'] = is_string( $field['default'] )
				? sanitize_text_field( $field['default'] )
				: $field['default'];
		}

		// Select choices.
		if ( 'select' === $type && ! empty( $field['choices'] ) && is_array( $field['choices'] ) ) {
			$acf_field['choices'] = array_map( 'sanitize_text_field', $field['choices'] );
		}

		// Repeater sub_fields.
		if ( 'repeater' === $type && ! empty( $field['sub_fields'] ) && is_array( $field['sub_fields'] ) ) {
			$sub_fields = [];
			foreach ( $field['sub_fields'] as $sub ) {
				$sub_fields[] = $this->build_acf_field_config( (array) $sub, $block_name );
			}
			$acf_field['sub_fields'] = $sub_fields;
		}

		// Image return format.
		if ( 'image' === $type ) {
			$acf_field['return_format'] = 'id';
		}

		return $acf_field;
	}

	/**
	 * Auto-generate render PHP from field definitions.
	 *
	 * @since 2.2.0
	 */
	private function generate_render_from_fields( array $fields, string $block_name ): string {
		$o     = '<' . '?php';
		$c     = '?' . '>';
		$lines = [];

		foreach ( $fields as $field ) {
			$field = (array) $field;
			$name  = sanitize_key( $field['name'] ?? '' );
			$type  = sanitize_text_field( $field['type'] ?? 'text' );
			$label = sanitize_text_field( $field['label'] ?? ucwords( str_replace( [ '-', '_' ], ' ', $name ) ) );
			$q     = var_export( $name, true );
			if ( '' === $name ) {
				continue;
			}

			switch ( $type ) {
				case 'image':
					$lines[] = "\t{$o} \$img = get_field( {$q} ); if ( \$img ) { echo wp_get_attachment_image( \$img, 'large' ); } {$c}";
					break;
				case 'wysiwyg':
					$lines[] = "\t<div class=\"wpmcp-field-{$name}\">{$o} echo wp_kses_post( get_field( {$q} ) ); {$c}</div>";
					break;
				case 'url':
					$lines[] = "\t{$o} \$v = get_field( {$q} ); if ( \$v ) : {$c}<a href=\"{$o} echo esc_url( \$v ); {$c}\">" . esc_html( $label ) . "</a>{$o} endif; {$c}";
					break;
				case 'true_false':
					$lines[] = "\t{$o} if ( get_field( {$q} ) ) : {$c}<span class=\"wpmcp-flag-{$name}\">" . esc_html( $label ) . "</span>{$o} endif; {$c}";
					break;
				case 'repeater':
					$lines[] = "\t{$o} if ( have_rows( {$q} ) ) : {$c}";
					$lines[] = "\t\t<ul class=\"wpmcp-repeater-{$name}\">";
					$lines[] = "\t\t{$o} while ( have_rows( {$q} ) ) : the_row(); {$c}";
					$lines[] = "\t\t\t<li>{$o} the_sub_field( 'text' ); {$c}</li>";
					$lines[] = "\t\t{$o} endwhile; {$c}";
					$lines[] = "\t\t</ul>";
					$lines[] = "\t{$o} endif; {$c}";
					break;
				case 'email':
					$lines[] = "\t{$o} \$v = get_field( {$q} ); if ( \$v ) : {$c}<a href=\"mailto:{$o} echo esc_attr( \$v ); {$c}\">{$o} echo esc_html( \$v ); {$c}</a>{$o} endif; {$c}";
					break;
				default:
					$lines[] = "\t{$o} \$v = get_field( {$q} ); if ( \$v ) : {$c}";
					$lines[] = "\t\t<p class=\"wpmcp-field-{$name}\">{$o} echo esc_html( \$v ); {$c}</p>";
					$lines[] = "\t{$o} endif; {$c}";
					break;
			}
		}

		return implode( "\n", $lines );
	}

	/**
	 * Format a PHP array for clean var_export output.
	 */
	private static function format_php_array( array $array, int $depth = 1 ): string {
		$indent  = str_repeat( "\t", $depth );
		$lines   = [ 'array(' ];

		foreach ( $array as $key => $value ) {
			$key_str = is_int( $key ) ? $key . ' => ' : var_export( $key, true ) . ' => ';

			if ( is_array( $value ) ) {
				$lines[] = $indent . $key_str . self::format_php_array( $value, $depth + 1 ) . ',';
			} elseif ( is_bool( $value ) ) {
				$lines[] = $indent . $key_str . ( $value ? 'true' : 'false' ) . ',';
			} elseif ( is_null( $value ) ) {
				$lines[] = $indent . $key_str . 'null,';
			} elseif ( is_int( $value ) || is_float( $value ) ) {
				$lines[] = $indent . $key_str . $value . ',';
			} else {
				$lines[] = $indent . $key_str . var_export( (string) $value, true ) . ',';
			}
		}

		$close_indent = str_repeat( "\t", $depth - 1 );
		$lines[]      = $close_indent . ')';

		return implode( "\n", $lines );
	}

	// -- Execute methods ------------------------------------------------------

	/**
	 * Scaffold a new ACF-powered Gutenberg block in the workspace.
	 *
	 * @since 2.2.0
	 * @param mixed $input Ability input.
	 * @return array|\WP_Error
	 */
	public function execute_scaffold_acf_block( $input = [] ): array|\WP_Error {
		$input = self::normalize_input( $input );
		$init  = $this->ensure_workspace();
		if ( is_wp_error( $init ) ) {
			return $init;
		}

		$block_name = sanitize_text_field( $input['block_name'] ?? '' );
		if ( ! preg_match( '/^[a-z0-9]+(-[a-z0-9]+)*$/', $block_name ) ) {
			return new \WP_Error( 'wpmcp_invalid_block_name', 'block_name must be kebab-case (e.g. "feature-grid").' );
		}

		// Guard against duplicates.
		$existing = WP_MCP_Toolkit_Workspace_Manifest::get_artifact( $block_name );
		if ( null !== $existing && 'acf-block' === ( $existing['type'] ?? '' ) ) {
			return new \WP_Error( 'wpmcp_duplicate_artifact', 'ACF block already exists: ' . $block_name );
		}

		$title         = sanitize_text_field( $input['title'] ?? $block_name );
		$description   = sanitize_text_field( $input['description'] ?? '' );
		$category      = sanitize_text_field( $input['category'] ?? 'common' );
		$icon          = sanitize_text_field( $input['icon'] ?? 'block-default' );
		$fields        = (array) ( $input['fields'] ?? [] );
		$field_storage = sanitize_text_field( $input['field_storage'] ?? 'php' );
		$render_php    = $input['render_php'] ?? '';
		$css           = $input['css'] ?? '';

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

		if ( empty( $fields ) ) {
			return new \WP_Error( 'wpmcp_invalid_input', 'At least one field is required.' );
		}

		if ( ! in_array( $field_storage, [ 'php', 'json' ], true ) ) {
			$field_storage = 'php';
		}

		// Build ACF field configs.
		$acf_fields = [];
		foreach ( $fields as $field ) {
			$field = (array) $field;
			if ( empty( $field['name'] ) ) {
				continue;
			}
			$acf_fields[] = $this->build_acf_field_config( $field, $block_name );
		}

		if ( empty( $acf_fields ) ) {
			return new \WP_Error( 'wpmcp_invalid_input', 'No valid fields provided.' );
		}

		$block_slug = str_replace( '-', '_', $block_name );

		// Build field group array.
		$field_group = [
			'key'      => 'group_wpmcp_' . $block_slug,
			'title'    => $title . ' Fields',
			'fields'   => $acf_fields,
			'location' => [
				[
					[
						'param'    => 'block',
						'operator' => '==',
						'value'    => 'acf/wpmcp-workspace-' . $block_name,
					],
				],
			],
			'position' => 'normal',
			'style'    => 'default',
		];

		$written_files = [];

		// 1. Field storage — PHP or JSON.
		if ( 'json' === $field_storage ) {
			$json_content = wp_json_encode( $field_group, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
			$json_path    = "blocks/{$block_name}/acf-json/{$field_group['key']}.json";
			$written      = WP_MCP_Toolkit_Workspace_File_Writer::write_file( $json_path, $json_content );
			if ( is_wp_error( $written ) ) {
				return $written;
			}
			$written_files[] = $json_path;
		} else {
			$fields_php = WP_MCP_Toolkit_Workspace_Container::render_template(
				self::tpl( 'acf-block-fields.php.tpl' ),
				[
					'BLOCK_NAME'      => $block_name,
					'FIELD_GROUP_PHP' => self::format_php_array( $field_group ),
				]
			);
			$fields_path = "blocks/{$block_name}/fields.php";
			$written     = WP_MCP_Toolkit_Workspace_File_Writer::write_file( $fields_path, $fields_php );
			if ( is_wp_error( $written ) ) {
				return $written;
			}
			$written_files[] = $fields_path;
		}

		// 2. register.php — block type registration.
		$register_php = $this->build_register_php( $block_name, $title, $description, $category, $icon );
		$register_path = "blocks/{$block_name}/register.php";
		$written       = WP_MCP_Toolkit_Workspace_File_Writer::write_file( $register_path, $register_php );
		if ( is_wp_error( $written ) ) {
			return $written;
		}
		$written_files[] = $register_path;

		// 3. render.php — block render template.
		$render_body = '' !== $render_php
			? $render_php
			: $this->generate_render_from_fields( $fields, $block_name );

		$render_content = WP_MCP_Toolkit_Workspace_Container::render_template(
			self::tpl( 'acf-block-render.php.tpl' ),
			[
				'BLOCK_NAME'  => $block_name,
				'RENDER_BODY' => $render_body,
			]
		);
		$render_path = "blocks/{$block_name}/render.php";
		$written     = WP_MCP_Toolkit_Workspace_File_Writer::write_file( $render_path, $render_content );
		if ( is_wp_error( $written ) ) {
			return $written;
		}
		$written_files[] = $render_path;

		// 4. style.css.
		$style_css = "/**\n * ACF Block: {$block_name}\n */\n.wpmcp-acf-block-{$block_name} {\n\t"
			. ( '' !== $css ? $css : 'padding: 1rem;' )
			. "\n}\n";
		$style_path = "blocks/{$block_name}/style.css";
		$written    = WP_MCP_Toolkit_Workspace_File_Writer::write_file( $style_path, $style_css );
		if ( is_wp_error( $written ) ) {
			return $written;
		}
		$written_files[] = $style_path;

		// 5. Register in manifest.
		$saved = $this->save_artifact( $block_name, 'acf-block', $register_path, 'wpmcp-workspace/scaffold-acf-block' );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		return [
			'block_name'   => $block_name,
			'files'        => $written_files,
			'registration' => 'acf/wpmcp-workspace-' . $block_name,
			'field_count'  => count( $acf_fields ),
		];
	}

	/**
	 * Build the register.php content for an ACF block.
	 */
	private function build_register_php( string $block_name, string $title, string $description, string $category, string $icon ): string {
		$php_open = '<' . '?php';
		$escaped  = [
			'name'  => addslashes( 'wpmcp-workspace-' . $block_name ),
			'title' => addslashes( $title ),
			'desc'  => addslashes( $description ),
			'cat'   => addslashes( $category ),
			'icon'  => addslashes( $icon ),
		];

		return <<<PHP
{$php_open}
/**
 * ACF Block registration: {$block_name}
 *
 * Auto-generated by WP MCP Toolkit. Do not edit manually.
 *
 * @package WP_MCP_Workspace
 */

defined( 'ABSPATH' ) || exit;

add_action( 'acf/init', function() {
	acf_register_block_type( array(
		'name'            => '{$escaped['name']}',
		'title'           => '{$escaped['title']}',
		'description'     => '{$escaped['desc']}',
		'category'        => '{$escaped['cat']}',
		'icon'            => '{$escaped['icon']}',
		'mode'            => 'preview',
		'render_template' => __DIR__ . '/render.php',
		'enqueue_style'   => plugin_dir_url( __FILE__ ) . 'style.css',
		'supports'        => array( 'align' => true, 'html' => false, 'mode' => true, 'jsx' => true ),
	) );
} );
PHP;
	}
}
