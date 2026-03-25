<?php
/**
 * Workspace abilities — code generation, hook registration, and WP API calls.
 *
 * @package WP_MCP_Toolkit
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

class WP_MCP_Toolkit_Workspace_Abilities extends WP_MCP_Toolkit_Abstract_Abilities {

	use WP_MCP_Toolkit_Workspace_Helpers;

	protected function get_abilities(): array {
		$c = 'wpmcp-workspace';
		$p = 'manage_options';
		$s = fn( $r, $pr ) => [ 'type' => 'object', 'required' => $r, 'properties' => $pr, 'additionalProperties' => false ];
		$o = fn( $pr ) => [ 'type' => 'object', 'properties' => $pr ];
		$w = fn( $cb, $ro, $dest, $idemp ) => [ 'callback' => $cb, 'permission' => $p, 'readonly' => $ro, 'destructive' => $dest, 'idempotent' => $idemp ];

		return [
			'wpmcp-workspace/generate-function' => [
				'label'         => __( 'Generate Function', 'wp-mcp-toolkit' ),
				'description'   => __( 'Generates a PHP function file in the workspace. Functions are automatically prefixed with wpmcp_workspace_ to avoid collisions. Provide the function body without opening PHP tag or function declaration.', 'wp-mcp-toolkit' ),
				'category'      => $c,
				'input_schema'  => $s( [ 'function_name', 'body' ], [
					'function_name' => [ 'type' => 'string' ],
					'parameters'    => [ 'type' => 'string', 'default' => '' ],
					'body'          => [ 'type' => 'string' ],
					'description'   => [ 'type' => 'string', 'default' => '' ],
				] ),
				'output_schema' => $o( [ 'name' => [ 'type' => 'string' ], 'prefixed_name' => [ 'type' => 'string' ], 'file' => [ 'type' => 'string' ], 'checksum' => [ 'type' => 'string' ] ] ),
			] + $w( 'execute_generate_function', false, false, true ),
			'wpmcp-workspace/generate-class' => [
				'label'         => __( 'Generate Class', 'wp-mcp-toolkit' ),
				'description'   => __( 'Generates a PHP class file in the workspace. Classes are automatically prefixed with WPMCP_Workspace_. Provide methods and properties as structured arrays.', 'wp-mcp-toolkit' ),
				'category'      => $c,
				'input_schema'  => $s( [ 'class_name' ], [
					'class_name'  => [ 'type' => 'string' ],
					'extends'     => [ 'type' => 'string', 'default' => '' ],
					'implements'  => [ 'type' => 'string', 'default' => '' ],
					'methods'     => [ 'type' => 'array', 'default' => [] ],
					'properties'  => [ 'type' => 'array', 'default' => [] ],
					'description' => [ 'type' => 'string', 'default' => '' ],
				] ),
				'output_schema' => $o( [ 'class_name' => [ 'type' => 'string' ], 'prefixed_name' => [ 'type' => 'string' ], 'file' => [ 'type' => 'string' ], 'checksum' => [ 'type' => 'string' ] ] ),
			] + $w( 'execute_generate_class', false, false, true ),
			'wpmcp-workspace/register-hook' => [
				'label'         => __( 'Register Hook', 'wp-mcp-toolkit' ),
				'description'   => __( 'Registers a WordPress action or filter hook in the workspace. The callback must be a function already in the workspace or a built-in WordPress function.', 'wp-mcp-toolkit' ),
				'category'      => $c,
				'input_schema'  => $s( [ 'hook_type', 'hook_name', 'callback_function' ], [
					'hook_type'         => [ 'type' => 'string', 'enum' => [ 'action', 'filter' ] ],
					'hook_name'         => [ 'type' => 'string' ],
					'callback_function' => [ 'type' => 'string' ],
					'priority'          => [ 'type' => 'integer', 'default' => 10 ],
					'accepted_args'     => [ 'type' => 'integer', 'default' => 1 ],
				] ),
				'output_schema' => $o( [ 'hook_type' => [ 'type' => 'string' ], 'hook_name' => [ 'type' => 'string' ], 'callback' => [ 'type' => 'string' ], 'file' => [ 'type' => 'string' ] ] ),
			] + $w( 'execute_register_hook', false, false, true ),
			'wpmcp-workspace/call-wp-api' => [
				'label'         => __( 'Call WP API', 'wp-mcp-toolkit' ),
				'description'   => __( 'Calls any WordPress global function by name. Only global functions accepted — for class methods, use generate-function to write a wrapper. In production mode, only allowlisted functions are permitted. Dangerous functions (filesystem, process, network) are blocked in all modes.', 'wp-mcp-toolkit' ),
				'category'      => $c,
				'input_schema'  => $s( [ 'function_name' ], [
					'function_name' => [ 'type' => 'string' ],
					'arguments'     => [ 'type' => 'array', 'default' => [] ],
				] ),
				'output_schema' => $o( [ 'function' => [ 'type' => 'string' ], 'result' => [] ] ),
			] + $w( 'execute_call_wp_api', false, false, false ),
			'wpmcp-workspace/list-workspace' => [
				'label'         => __( 'List Workspace', 'wp-mcp-toolkit' ),
				'description'   => __( 'Call this first to see what artifacts exist before creating or modifying. Returns name, type, file path, created date, and file size for each artifact.', 'wp-mcp-toolkit' ),
				'category'      => $c,
				'input_schema'  => [ 'type' => 'object', 'properties' => [ 'type' => [ 'type' => 'string', 'default' => '' ] ], 'additionalProperties' => false ],
				'output_schema' => [ 'type' => 'array', 'items' => [ 'type' => 'object' ] ],
			] + $w( 'execute_list_workspace', true, false, true ),
			'wpmcp-workspace/read-workspace-file' => [
				'label'         => __( 'Read Workspace File', 'wp-mcp-toolkit' ),
				'description'   => __( 'Reads the content of a workspace artifact by name. Use list-workspace first to discover available artifacts.', 'wp-mcp-toolkit' ),
				'category'      => $c,
				'input_schema'  => $s( [ 'name' ], [ 'name' => [ 'type' => 'string' ] ] ),
				'output_schema' => $o( [ 'name' => [ 'type' => 'string' ], 'type' => [ 'type' => 'string' ], 'content' => [ 'type' => 'string' ], 'checksum' => [ 'type' => 'string' ] ] ),
			] + $w( 'execute_read_workspace_file', true, false, true ),
			'wpmcp-workspace/delete-workspace-artifact' => [
				'label'         => __( 'Delete Artifact', 'wp-mcp-toolkit' ),
				'description'   => __( 'Permanently deletes an artifact from the workspace. Requires confirm: true. The file is removed and the workspace bootstrap is regenerated.', 'wp-mcp-toolkit' ),
				'category'      => $c,
				'input_schema'  => $s( [ 'name', 'confirm' ], [ 'name' => [ 'type' => 'string' ], 'confirm' => [ 'type' => 'boolean' ] ] ),
				'output_schema' => $o( [ 'deleted' => [ 'type' => 'string' ], 'remaining' => [ 'type' => 'integer' ] ] ),
			] + $w( 'execute_delete_workspace_artifact', false, true, true ),
		];
	}

	// -- Execute methods ------------------------------------------------------

	public function execute_generate_function( $input = [] ): array|\WP_Error {
		$input = self::normalize_input( $input );
		$init  = $this->ensure_workspace();
		if ( is_wp_error( $init ) ) { return $init; }

		$name = sanitize_text_field( $input['function_name'] ?? '' );
		$valid = WP_MCP_Toolkit_Workspace_Validator::validate_function_name( $name );
		if ( is_wp_error( $valid ) ) { return $valid; }

		$prefixed = 'wpmcp_workspace_' . $name;
		$params   = sanitize_text_field( $input['parameters'] ?? '' );
		$body     = $input['body'] ?? '';
		$desc     = sanitize_text_field( $input['description'] ?? '' );

		$php = "<?php\n";
		if ( $desc ) { $php .= "/**\n * {$desc}\n */\n"; }
		$php .= "function {$prefixed}( {$params} ) {\n{$body}\n}";

		$syntax = WP_MCP_Toolkit_Workspace_Validator::validate_php_syntax( $php );
		if ( is_wp_error( $syntax ) ) { return $syntax; }

		$rel_path = "functions/{$name}.php";
		$written  = WP_MCP_Toolkit_Workspace_File_Writer::write_file( $rel_path, $php );
		if ( is_wp_error( $written ) ) { return $written; }

		$saved = $this->save_artifact( $name, 'function', $rel_path, 'wpmcp-workspace/generate-function' );
		if ( is_wp_error( $saved ) ) { return $saved; }

		return [ 'name' => $name, 'prefixed_name' => $prefixed, 'file' => $rel_path, 'checksum' => WP_MCP_Toolkit_Workspace_File_Writer::file_checksum( $rel_path ) ];
	}

	public function execute_generate_class( $input = [] ): array|\WP_Error {
		$input = self::normalize_input( $input );
		$init  = $this->ensure_workspace();
		if ( is_wp_error( $init ) ) { return $init; }

		$name = sanitize_text_field( $input['class_name'] ?? '' );
		$valid = WP_MCP_Toolkit_Workspace_Validator::validate_class_name( $name );
		if ( is_wp_error( $valid ) ) { return $valid; }

		$prefixed   = 'WPMCP_Workspace_' . $name;
		$extends    = sanitize_text_field( $input['extends'] ?? '' );
		$implements = sanitize_text_field( $input['implements'] ?? '' );
		$desc       = sanitize_text_field( $input['description'] ?? '' );
		$properties = (array) ( $input['properties'] ?? [] );
		$methods    = (array) ( $input['methods'] ?? [] );

		$php = "<?php\n";
		if ( $desc ) { $php .= "/**\n * {$desc}\n */\n"; }
		$decl = "class {$prefixed}";
		if ( $extends )    { $decl .= " extends {$extends}"; }
		if ( $implements ) { $decl .= " implements {$implements}"; }
		$php .= "{$decl} {\n\n";

		foreach ( $properties as $prop ) {
			$prop = (array) $prop;
			$vis  = sanitize_text_field( $prop['visibility'] ?? 'public' );
			$type = isset( $prop['type'] ) ? sanitize_text_field( $prop['type'] ) . ' ' : '';
			$pn   = sanitize_text_field( $prop['name'] ?? '' );
			$line = "\t{$vis} {$type}\${$pn}";
			if ( array_key_exists( 'default', $prop ) ) { $line .= ' = ' . var_export( $prop['default'], true ); }
			$php .= "{$line};\n\n";
		}
		foreach ( $methods as $method ) {
			$method = (array) $method;
			$vis    = sanitize_text_field( $method['visibility'] ?? 'public' );
			$static = ! empty( $method['static'] ) ? 'static ' : '';
			$mn     = sanitize_text_field( $method['name'] ?? '' );
			$mp     = sanitize_text_field( $method['parameters'] ?? '' );
			$mr     = isset( $method['return_type'] ) ? ': ' . sanitize_text_field( $method['return_type'] ) : '';
			$mb     = $method['body'] ?? '';
			$php   .= "\t{$vis} {$static}function {$mn}( {$mp} ){$mr} {\n{$mb}\n\t}\n\n";
		}
		$php .= "}\n";

		$syntax = WP_MCP_Toolkit_Workspace_Validator::validate_php_syntax( $php );
		if ( is_wp_error( $syntax ) ) { return $syntax; }

		$rel_path = "classes/{$name}.php";
		$written  = WP_MCP_Toolkit_Workspace_File_Writer::write_file( $rel_path, $php );
		if ( is_wp_error( $written ) ) { return $written; }

		$saved = $this->save_artifact( $name, 'class', $rel_path, 'wpmcp-workspace/generate-class' );
		if ( is_wp_error( $saved ) ) { return $saved; }

		return [ 'class_name' => $name, 'prefixed_name' => $prefixed, 'file' => $rel_path, 'checksum' => WP_MCP_Toolkit_Workspace_File_Writer::file_checksum( $rel_path ) ];
	}

	public function execute_register_hook( $input = [] ): array|\WP_Error {
		$input = self::normalize_input( $input );
		$init  = $this->ensure_workspace();
		if ( is_wp_error( $init ) ) { return $init; }

		$hook_type = sanitize_key( $input['hook_type'] ?? '' );
		if ( ! in_array( $hook_type, [ 'action', 'filter' ], true ) ) {
			return new \WP_Error( 'wpmcp_invalid_input', 'hook_type must be "action" or "filter".' );
		}
		$hook_name = sanitize_text_field( $input['hook_name'] ?? '' );
		$valid = WP_MCP_Toolkit_Workspace_Validator::validate_hook_name( $hook_name );
		if ( is_wp_error( $valid ) ) { return $valid; }

		$callback      = sanitize_text_field( $input['callback_function'] ?? '' );
		$priority      = absint( $input['priority'] ?? 10 );
		$accepted_args = absint( $input['accepted_args'] ?? 1 );

		// Validate callback is not a blocked function.
		if ( in_array( $callback, WP_MCP_Toolkit_Workspace_Validator::get_blocklist(), true ) ) {
			return new \WP_Error( 'wpmcp_blocked_function', "Function '{$callback}' is blocked for security reasons." );
		}

		$php = "<?php\nadd_{$hook_type}( '{$hook_name}', '{$callback}', {$priority}, {$accepted_args} );\n";
		$syntax = WP_MCP_Toolkit_Workspace_Validator::validate_php_syntax( $php );
		if ( is_wp_error( $syntax ) ) { return $syntax; }

		$safe_hook = sanitize_file_name( $hook_name );
		$safe_cb   = sanitize_file_name( $callback );
		$rel_path  = "hooks/{$safe_hook}--{$safe_cb}.php";
		$art_name  = "{$hook_name}--{$callback}";

		$written = WP_MCP_Toolkit_Workspace_File_Writer::write_file( $rel_path, $php );
		if ( is_wp_error( $written ) ) { return $written; }

		$saved = $this->save_artifact( $art_name, 'hook', $rel_path, 'wpmcp-workspace/register-hook' );
		if ( is_wp_error( $saved ) ) { return $saved; }

		return [ 'hook_type' => $hook_type, 'hook_name' => $hook_name, 'callback' => $callback, 'file' => $rel_path ];
	}

	public function execute_call_wp_api( $input = [] ): array|\WP_Error {
		$input   = self::normalize_input( $input );
		$fn      = sanitize_text_field( $input['function_name'] ?? '' );
		$allowed = WP_MCP_Toolkit_Workspace_Validator::is_function_allowed( $fn );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		if ( ! function_exists( $fn ) ) {
			return new \WP_Error( 'wpmcp_function_not_found', "Function '{$fn}' does not exist." );
		}

		try {
			$result = call_user_func_array( $fn, (array) ( $input['arguments'] ?? [] ) );
		} catch ( \Throwable $e ) {
			return new \WP_Error( 'wpmcp_call_failed', $e->getMessage() );
		}
		if ( is_object( $result ) ) {
			$result = json_decode( wp_json_encode( $result ), true );
		}
		return [ 'function' => $fn, 'result' => $result ];
	}

	public function execute_list_workspace( $input = [] ): array {
		$input     = self::normalize_input( $input );
		$artifacts = WP_MCP_Toolkit_Workspace_Manifest::list_artifacts( sanitize_text_field( $input['type'] ?? '' ) );
		foreach ( $artifacts as &$a ) {
			$abs = WP_MCP_Toolkit_Workspace_Container::get_active_dir() . ( $a['file'] ?? '' );
			$a['file_size'] = file_exists( $abs ) ? filesize( $abs ) : 0;
		}
		unset( $a );
		return $artifacts;
	}

	public function execute_read_workspace_file( $input = [] ): array|\WP_Error {
		$input    = self::normalize_input( $input );
		$name     = sanitize_text_field( $input['name'] ?? '' );
		$artifact = WP_MCP_Toolkit_Workspace_Manifest::get_artifact( $name );
		if ( ! $artifact ) {
			return new \WP_Error( 'wpmcp_not_found', "Artifact not found: {$name}" );
		}
		$content = WP_MCP_Toolkit_Workspace_File_Writer::read_file( $artifact['file'] );
		if ( is_wp_error( $content ) ) { return $content; }
		return [ 'name' => $artifact['name'], 'type' => $artifact['type'], 'content' => $content, 'checksum' => WP_MCP_Toolkit_Workspace_File_Writer::file_checksum( $artifact['file'] ) ];
	}

	public function execute_delete_workspace_artifact( $input = [] ): array|\WP_Error {
		$input = self::normalize_input( $input );
		$name  = sanitize_text_field( $input['name'] ?? '' );
		if ( empty( $input['confirm'] ) ) {
			return new \WP_Error( 'wpmcp_confirmation_required', 'Set confirm=true to delete.' );
		}
		$artifact = WP_MCP_Toolkit_Workspace_Manifest::get_artifact( $name );
		if ( ! $artifact ) {
			return new \WP_Error( 'wpmcp_not_found', "Artifact not found: {$name}" );
		}
		$delete = WP_MCP_Toolkit_Workspace_File_Writer::delete_file( $artifact['file'] );
		if ( is_wp_error( $delete ) ) { return $delete; }
		$remove = WP_MCP_Toolkit_Workspace_Manifest::remove_artifact( $name );
		if ( is_wp_error( $remove ) ) { return $remove; }
		WP_MCP_Toolkit_Workspace_Container::reinitialize();
		return [ 'deleted' => $name, 'remaining' => count( WP_MCP_Toolkit_Workspace_Manifest::list_artifacts() ) ];
	}
}
