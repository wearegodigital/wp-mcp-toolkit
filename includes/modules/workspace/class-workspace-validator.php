<?php
/**
 * Workspace validator — path, syntax, and name validation.
 *
 * @package WP_MCP_Toolkit
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/** @since 2.0.0 */
class WP_MCP_Toolkit_Workspace_Validator {

	/** @since 2.0.0 @var string[] */
	private static array $reserved_words = [
		'__halt_compiler', 'abstract', 'and', 'array', 'as', 'break',
		'callable', 'case', 'catch', 'class', 'clone', 'const', 'continue',
		'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty',
		'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile',
		'eval', 'exit', 'extends', 'final', 'finally', 'fn', 'for', 'foreach',
		'function', 'global', 'goto', 'if', 'implements', 'include',
		'include_once', 'instanceof', 'insteadof', 'interface', 'isset', 'list',
		'match', 'namespace', 'new', 'or', 'print', 'private', 'protected',
		'public', 'readonly', 'require', 'require_once', 'return', 'static',
		'switch', 'throw', 'trait', 'try', 'unset', 'use', 'var', 'while',
		'xor', 'yield',
	];

	/**
	 * Validates a path against traversal, extension allowlist, and workspace boundary.
	 *
	 * @since 2.0.0
	 * @param string $path Path to validate (relative or absolute).
	 * @return true|\WP_Error
	 */
	public static function validate_path( string $path ): true|\WP_Error {
		// Block traversal before any filesystem access to fail fast.
		if ( str_contains( $path, '..' ) ) {
			return new \WP_Error( 'wpmcp_path_traversal', 'Path cannot contain directory traversal (..).' );
		}

		$filename = basename( $path );
		if ( ! preg_match( '/^[a-zA-Z0-9_-]+\.(php|css|json|js|txt|tpl)$/', $filename ) ) {
			return new \WP_Error( 'wpmcp_invalid_filename', 'Invalid filename: ' . $filename );
		}

		// realpath() only works on existing files, so skip the symlink check
		// for paths that don't exist yet — the directory must exist for us to check.
		$dir = dirname( $path );
		if ( is_dir( $dir ) ) {
			$workspace_dir = WP_MCP_Toolkit_Workspace_Container::get_active_dir();
			$real_path     = realpath( $path );

			// Only enforce boundary when realpath resolves — catches symlink escapes.
			if ( false !== $real_path && ! str_starts_with( $real_path, rtrim( $workspace_dir, '/' ) . '/' ) ) {
				return new \WP_Error( 'wpmcp_path_escape', 'Path resolves outside workspace directory.' );
			}
		}

		return true;
	}

	/**
	 * Validates PHP syntax by shelling out to PHP_BINARY.
	 *
	 * Degrades gracefully when exec() is disabled — better to skip than to block.
	 *
	 * @since 2.0.0
	 * @param string $code PHP code to lint.
	 * @return true|\WP_Error
	 */
	public static function validate_php_syntax( string $code ): true|\WP_Error {
		// Can't lint without exec() or a known binary path.
		if ( ! function_exists( 'exec' ) || empty( PHP_BINARY ) ) {
			return true;
		}

		$tmp = tempnam( sys_get_temp_dir(), 'wpmcp_lint_' );
		file_put_contents( $tmp, $code, LOCK_EX );

		$output     = [];
		$return_var = 0;
		exec( escapeshellarg( PHP_BINARY ) . ' -l ' . escapeshellarg( $tmp ) . ' 2>&1', $output, $return_var );

		unlink( $tmp );

		if ( 0 !== $return_var ) {
			// Replace the temp path so error messages are readable in API responses.
			$error_text = str_replace( $tmp, 'file.php', implode( "\n", $output ) );
			return new \WP_Error( 'wpmcp_syntax_error', 'PHP syntax error: ' . $error_text );
		}

		return true;
	}

	/** @return true|\WP_Error */
	public static function validate_function_name( string $name ): true|\WP_Error {
		return self::validate_php_identifier( $name, 'function name' );
	}

	/** @return true|\WP_Error */
	public static function validate_class_name( string $name ): true|\WP_Error {
		return self::validate_php_identifier( $name, 'class name' );
	}

	/**
	 * Validates a WordPress hook name.
	 *
	 * Hooks allow forward slashes (e.g. `rest_api/init`), unlike PHP identifiers.
	 *
	 * @param string $name Proposed hook name.
	 * @return true|\WP_Error
	 */
	public static function validate_hook_name( string $name ): true|\WP_Error {
		if ( ! preg_match( '/^[a-zA-Z_][a-zA-Z0-9_\/]*$/', $name ) ) {
			return new \WP_Error( 'wpmcp_invalid_hook_name', 'Invalid hook name: ' . $name );
		}

		return true;
	}

	// -- Mode system ----------------------------------------------------------

	/** Returns the raw configured mode, which may be 'auto'. @since 2.0.0 */
	public static function get_mode(): string {
		return (string) get_option( 'wpmcp_workspace_mode', 'auto' );
	}

	/**
	 * Resolves the effective runtime mode — never returns 'auto'.
	 *
	 * 'auto' maps to 'staging' unless on a confirmed production environment.
	 * WPMCP_DEV_MODE constant lets developers force staging on live sites.
	 *
	 * @since 2.0.0
	 * @return string 'staging', 'production', or 'disabled'
	 */
	public static function resolve_mode(): string {
		$mode = self::get_mode();

		if ( in_array( $mode, [ 'staging', 'production', 'disabled' ], true ) ) {
			return $mode;
		}

		// 'auto' and any unknown stored value resolve here.
		if ( defined( 'WPMCP_DEV_MODE' ) && WPMCP_DEV_MODE ) {
			return 'staging';
		}
		if ( function_exists( 'wp_get_environment_type' ) && 'production' !== wp_get_environment_type() ) {
			return 'staging';
		}
		return 'production';
	}

	/** @since 2.0.0 */
	public static function is_staging(): bool {
		return 'staging' === self::resolve_mode();
	}

	/** @since 2.0.0 */
	public static function is_production(): bool {
		return 'production' === self::resolve_mode();
	}

	/**
	 * Persists the workspace mode setting.
	 *
	 * @since 2.0.0
	 * @param string $mode One of: auto, staging, production, disabled.
	 * @return true|\WP_Error
	 */
	public static function set_mode( string $mode ): true|\WP_Error {
		if ( ! in_array( $mode, [ 'auto', 'staging', 'production', 'disabled' ], true ) ) {
			return new \WP_Error( 'wpmcp_invalid_mode', 'Invalid workspace mode.' );
		}
		update_option( 'wpmcp_workspace_mode', $mode );
		return true;
	}

	// -- Allowlist / blocklist ------------------------------------------------

	/**
	 * WordPress registration APIs always safe to call — they register intent
	 * without mutating content or executing arbitrary logic.
	 *
	 * @since 2.0.0
	 * @return string[]
	 */
	public static function get_default_allowlist(): array {
		return [
			'register_post_type', 'register_taxonomy', 'register_meta',
			'add_action', 'add_filter', 'remove_action', 'remove_filter',
			'register_block_type', 'register_block_style', 'add_theme_support',
			'register_nav_menus', 'register_sidebar', 'register_widget',
			'wp_register_style', 'wp_register_script', 'wp_enqueue_style',
			'wp_enqueue_script', 'add_shortcode', 'add_rewrite_rule',
			'add_rewrite_tag', 'flush_rewrite_rules', 'register_rest_route',
		];
	}

	/**
	 * Functions permanently blocked in all modes — arbitrary execution, filesystem
	 * access, data exfiltration, or credential modification.
	 *
	 * @since 2.0.0
	 * @return string[]
	 */
	public static function get_blocklist(): array {
		return [
			'eval', 'assert', 'create_function', 'exec', 'system', 'passthru', 'popen',
			'proc_open', 'pcntl_exec', 'shell_exec', 'dl', 'putenv', 'ini_set', 'extract',
			'parse_str', 'include', 'require', 'include_once', 'require_once',
			'call_user_func', 'call_user_func_array', 'unlink', 'rmdir', 'rename', 'copy',
			'mkdir', 'chmod', 'chown', 'chgrp', 'file_put_contents', 'file_get_contents',
			'fopen', 'fwrite', 'fputs', 'fclose', 'readfile', 'move_uploaded_file',
			'curl_exec', 'curl_multi_exec', 'mail', 'header', 'setcookie',
			// Obfuscation decoders commonly chained with code execution primitives.
			'base64_decode', 'str_rot13', 'gzinflate', 'gzuncompress', 'hex2bin',
			'wp_delete_user', 'wp_set_password', 'wp_insert_user', 'update_option',
			'delete_option', 'delete_site_option',
		];
	}

	/** Defaults merged with site-specific additions from options. @since 2.0.0 @return string[] */
	public static function get_allowlist(): array {
		$custom = (array) get_option( 'wpmcp_workspace_allowlist', [] );
		return array_unique( array_merge( self::get_default_allowlist(), $custom ) );
	}

	/** @since 2.0.0 @param string[] $functions @return true|\WP_Error */
	public static function set_custom_allowlist( array $functions ): true|\WP_Error {
		foreach ( $functions as $fn ) {
			if ( ! preg_match( '/^[a-zA-Z_][a-zA-Z0-9_]*$/', $fn ) ) {
				return new \WP_Error( 'wpmcp_invalid_function', 'Invalid function name: ' . $fn );
			}
		}
		update_option( 'wpmcp_workspace_allowlist', $functions );
		return true;
	}

	/**
	 * Checks whether a global function may be called via call-wp-api.
	 * Blocklist enforced in all modes; production additionally requires allowlist membership.
	 *
	 * @since 2.0.0
	 * @param string $function_name Global function name to check.
	 * @return true|\WP_Error
	 */
	public static function is_function_allowed( string $function_name ): true|\WP_Error {
		// Blocklist wins in every mode — no exceptions.
		if ( in_array( $function_name, self::get_blocklist(), true ) ) {
			return new \WP_Error( 'wpmcp_blocked_function', "Function '{$function_name}' is blocked for security." );
		}

		// Method syntax implies an object or class context — not a global function call.
		if ( str_contains( $function_name, '::' ) || str_contains( $function_name, '->' ) ) {
			return new \WP_Error( 'wpmcp_invalid_function', 'Only global functions accepted.' );
		}

		$mode = self::resolve_mode();

		// Staging allows anything not on the blocklist to support rapid development.
		if ( 'staging' === $mode ) {
			return true;
		}

		// Production restricts to the explicit allowlist to limit blast radius.
		if ( 'production' === $mode ) {
			if ( ! in_array( $function_name, self::get_allowlist(), true ) ) {
				return new \WP_Error( 'wpmcp_not_allowed', "Function '{$function_name}' is not in the production allowlist." );
			}
			return true;
		}

		// 'disabled' mode or unknown — deny everything.
		return new \WP_Error( 'wpmcp_blocked_function', "Function '{$function_name}' is blocked for security." );
	}

	// -- Code scanning --------------------------------------------------------

	/**
	 * Scan code content for blocked functions and dangerous patterns.
	 *
	 * @since 2.3.0
	 * @param string $code    The code to scan.
	 * @param string $context Context label for error messages (e.g. 'render_php', 'body').
	 * @return true|\WP_Error True if safe, WP_Error if dangerous content detected.
	 */
	public static function scan_code_for_blocked_content( string $code, string $context = 'code' ): true|\WP_Error {
		// Check against function blocklist.
		$blocklist = self::get_blocklist();
		foreach ( $blocklist as $fn ) {
			// Match function calls: fn_name( or fn_name (
			if ( preg_match( '/\b' . preg_quote( $fn, '/' ) . '\s*\(/i', $code ) ) {
				return new \WP_Error(
					'wpmcp_blocked_code',
					sprintf( 'Blocked function "%s" detected in %s.', $fn, $context )
				);
			}
		}

		// Check for superglobals.
		$superglobals = [ '$_GET', '$_POST', '$_REQUEST', '$_SERVER', '$_COOKIE', '$_FILES', '$_SESSION', '$GLOBALS' ];
		foreach ( $superglobals as $sg ) {
			if ( str_contains( $code, $sg ) ) {
				return new \WP_Error(
					'wpmcp_blocked_code',
					sprintf( 'Superglobal "%s" detected in %s. Direct superglobal access is not permitted.', $sg, $context )
				);
			}
		}

		// Check for backtick operator.
		if ( str_contains( $code, '`' ) ) {
			return new \WP_Error(
				'wpmcp_blocked_code',
				sprintf( 'Backtick operator detected in %s. Shell execution is not permitted.', $context )
			);
		}

		return true;
	}

	/**
	 * Scan CSS content for embedded PHP or script tags.
	 *
	 * @since 2.3.0
	 * @param string $css The CSS to scan.
	 * @return true|\WP_Error True if safe, WP_Error if dangerous content detected.
	 */
	public static function scan_css_for_blocked_content( string $css ): true|\WP_Error {
		$php_open = '<' . '?php';
		if ( stripos( $css, $php_open ) !== false || stripos( $css, '<script' ) !== false ) {
			return new \WP_Error(
				'wpmcp_blocked_code',
				'PHP or script tags detected in CSS content.'
			);
		}
		return true;
	}

	// -- Private helpers ------------------------------------------------------

	/**
	 * Shared validation for PHP identifiers — same rules for functions and classes.
	 *
	 * @param string $name  Proposed identifier.
	 * @param string $label Human-readable label for error messages.
	 * @return true|\WP_Error
	 */
	private static function validate_php_identifier( string $name, string $label ): true|\WP_Error {
		if ( ! preg_match( '/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name ) ) {
			return new \WP_Error( 'wpmcp_invalid_name', 'Invalid ' . $label . ': ' . $name );
		}

		if ( in_array( strtolower( $name ), self::$reserved_words, true ) ) {
			return new \WP_Error( 'wpmcp_invalid_name', '"' . $name . '" is a reserved PHP word and cannot be used as a ' . $label . '.' );
		}

		return true;
	}
}
