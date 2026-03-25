<?php
/**
 * WP MCP Toolkit — Admin Settings Page.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();

class WP_MCP_Toolkit_Admin_Page {

	public function register(): void {
		add_submenu_page(
			'tools.php',
			__( 'MCP Toolkit', 'wp-mcp-toolkit' ),
			__( 'MCP Toolkit', 'wp-mcp-toolkit' ),
			'manage_options',
			'wp-mcp-toolkit',
			array( $this, 'render' )
		);

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'handle_template_extraction' ) );
	}

	public function register_ajax_handlers(): void {
		add_action( 'wp_ajax_wpmcp_search_posts', array( $this, 'ajax_search_posts' ) );
		add_action( 'wp_ajax_wpmcp_delete_template', array( $this, 'ajax_delete_template' ) );
		add_action( 'wp_ajax_wpmcp_save_license', array( $this, 'ajax_save_license' ) );
		add_action( 'wp_ajax_wpmcp_toggle_addon', array( $this, 'ajax_toggle_addon' ) );
		add_action( 'wp_ajax_wpmcp_workspace_recover', array( $this, 'ajax_workspace_recover' ) );
	}

	public function enqueue_assets( string $hook ): void {
		if ( 'tools_page_wp-mcp-toolkit' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'wpmcp-admin',
			plugins_url( 'admin/css/admin.css', dirname( __FILE__ ) . '/../wp-mcp-toolkit.php' ),
			array(),
			WP_MCP_VERSION
		);

		// Select2 for post search on Templates tab (bundled locally — no CDN).
		wp_enqueue_style(
			'select2',
			plugins_url( 'admin/vendor/select2/select2.min.css', dirname( __FILE__ ) . '/../wp-mcp-toolkit.php' ),
			array(),
			'4.1.0-rc.0'
		);

		wp_enqueue_script(
			'select2',
			plugins_url( 'admin/vendor/select2/select2.min.js', dirname( __FILE__ ) . '/../wp-mcp-toolkit.php' ),
			array( 'jquery' ),
			'4.1.0-rc.0',
			true
		);

		wp_enqueue_script(
			'wpmcp-admin',
			plugins_url( 'admin/js/admin.js', dirname( __FILE__ ) . '/../wp-mcp-toolkit.php' ),
			array( 'jquery', 'select2' ),
			WP_MCP_VERSION,
			true
		);

		wp_localize_script( 'wpmcp-admin', 'wmcpAdmin', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'wpmcp_admin' ),
			'strings' => array(
				'copied'             => __( 'Copied!', 'wp-mcp-toolkit' ),
				'copy'               => __( 'Copy', 'wp-mcp-toolkit' ),
				'deleting'           => __( 'Deleting…', 'wp-mcp-toolkit' ),
				'delete'             => __( 'Delete', 'wp-mcp-toolkit' ),
				'saving'             => __( 'Saving…', 'wp-mcp-toolkit' ),
				'update'             => __( 'Update', 'wp-mcp-toolkit' ),
				'activate'           => __( 'Activate', 'wp-mcp-toolkit' ),
				'enabled'            => __( 'Enabled', 'wp-mcp-toolkit' ),
				'disabled'           => __( 'Disabled', 'wp-mcp-toolkit' ),
				'searchPlaceholder'  => __( 'Search for a post…', 'wp-mcp-toolkit' ),
				'errorDeleting'      => __( 'Error deleting template.', 'wp-mcp-toolkit' ),
				'errorSavingLicense' => __( 'Error saving license.', 'wp-mcp-toolkit' ),
				'errorTogglingAddon' => __( 'Error toggling add-on.', 'wp-mcp-toolkit' ),
				'requestFailed'      => __( 'Request failed. Please try again.', 'wp-mcp-toolkit' ),
				// translators: %s: post type slug.
				'confirmDeleteTpl'   => __( 'Delete the template for "%s"? This cannot be undone.', 'wp-mcp-toolkit' ),
			),
		) );
	}

	public function register_settings(): void {
		register_setting( 'wpmcp_settings', 'wpmcp_disabled_abilities', array(
			'type'              => 'array',
			'sanitize_callback' => array( $this, 'sanitize_abilities_setting' ),
			'default'           => array(),
		) );

		register_setting( 'wpmcp_settings', 'wpmcp_workspace_mode', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_workspace_mode_setting' ),
			'default'           => 'auto',
		) );

		register_setting( 'wpmcp_settings', 'wpmcp_workspace_allowlist', array(
			'type'              => 'array',
			'sanitize_callback' => array( $this, 'sanitize_workspace_allowlist_setting' ),
			'default'           => array(),
		) );
	}

	public function sanitize_abilities_setting( $value ): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$all_raw     = isset( $_POST['wpmcp_all_abilities'] ) ? sanitize_text_field( wp_unslash( $_POST['wpmcp_all_abilities'] ) ) : '';
		$all         = array_filter( explode( ',', $all_raw ) );
		$enabled_raw = isset( $_POST['wpmcp_enabled_abilities'] ) && is_array( $_POST['wpmcp_enabled_abilities'] )
			? array_map( 'sanitize_text_field', wp_unslash( $_POST['wpmcp_enabled_abilities'] ) )
			: array();

		return array_values( array_diff( $all, $enabled_raw ) );
	}

	public function handle_template_extraction(): void {
		if ( ! isset( $_POST['wpmcp_extract_template'] ) ) {
			return;
		}

		check_admin_referer( 'wpmcp_extract_template' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'wp-mcp-toolkit' ) );
		}

		$ref_post_type = sanitize_key( $_POST['wpmcp_template_post_type'] ?? '' );
		$ref_post_id   = absint( $_POST['wpmcp_template_reference_post'] ?? 0 );

		$redirect_args = array(
			'page' => 'wp-mcp-toolkit',
			'tab'  => 'templates',
		);

		if ( $ref_post_type && $ref_post_id ) {
			require_once dirname( __DIR__ ) . '/includes/class-template-engine.php';
			$template = WP_MCP_Toolkit_Template_Engine::extract_template( $ref_post_id );

			if ( is_wp_error( $template ) ) {
				$redirect_args['wpmcp_notice']  = 'error';
				$redirect_args['wpmcp_message'] = $template->get_error_message();
			} else {
				WP_MCP_Toolkit_Template_Engine::save_template( $ref_post_type, $template );
				$redirect_args['wpmcp_notice']       = 'success';
				$redirect_args['wpmcp_sections']     = count( $template['sections'] );
				$redirect_args['wpmcp_placeholders'] = count( $template['placeholders'] );
			}
		}

		wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'tools.php' ) ) );
		exit;
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'connection';

		include __DIR__ . '/views/settings-page.php';
	}

	// ── AJAX Handlers ──────────────────────────────────────────────

	public function ajax_search_posts(): void {
		check_ajax_referer( 'wpmcp_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized', 403 );
		}

		$search    = sanitize_text_field( wp_unslash( $_GET['q'] ?? '' ) );
		$post_type = sanitize_key( $_GET['post_type'] ?? 'post' );

		$posts = get_posts( array(
			's'              => $search,
			'post_type'      => $post_type,
			'posts_per_page' => 20,
			'post_status'    => 'publish',
		) );

		wp_send_json_success( array_map( function ( $p ) {
			return array(
				'id'    => $p->ID,
				'title' => $p->post_title ?: '(no title)',
				'date'  => get_the_date( 'M j, Y', $p ),
			);
		}, $posts ) );
	}

	public function ajax_delete_template(): void {
		check_ajax_referer( 'wpmcp_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized', 403 );
		}

		$post_type = sanitize_key( $_POST['post_type'] ?? '' );
		if ( empty( $post_type ) ) {
			wp_send_json_error( 'Missing post_type' );
		}

		require_once dirname( __DIR__ ) . '/includes/class-template-engine.php';
		WP_MCP_Toolkit_Template_Engine::delete_template( $post_type );
		wp_send_json_success();
	}

	public function ajax_save_license(): void {
		check_ajax_referer( 'wpmcp_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized', 403 );
		}

		$addon_slug  = sanitize_key( $_POST['addon_slug'] ?? '' );
		$license_key = sanitize_text_field( wp_unslash( $_POST['license_key'] ?? '' ) );

		if ( empty( $addon_slug ) ) {
			wp_send_json_error( 'Missing addon_slug' );
		}

		$licenses = get_option( 'wpmcp_licenses', array() );

		if ( empty( $license_key ) ) {
			unset( $licenses[ $addon_slug ] );
		} else {
			$licenses[ $addon_slug ] = $license_key;
		}

		update_option( 'wpmcp_licenses', $licenses );
		wp_send_json_success();
	}

	public function ajax_toggle_addon(): void {
		check_ajax_referer( 'wpmcp_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized', 403 );
		}

		$addon_slug = sanitize_key( $_POST['addon_slug'] ?? '' );
		$enabled    = ! empty( $_POST['enabled'] );

		if ( empty( $addon_slug ) ) {
			wp_send_json_error( 'Missing addon_slug' );
		}

		$disabled = get_option( 'wpmcp_disabled_addons', array() );

		if ( $enabled ) {
			$disabled = array_values( array_diff( $disabled, array( $addon_slug ) ) );
		} else {
			if ( ! in_array( $addon_slug, $disabled, true ) ) {
				$disabled[] = $addon_slug;
			}
		}

		update_option( 'wpmcp_disabled_addons', $disabled );
		wp_send_json_success( array( 'enabled' => $enabled ) );
	}

	public function sanitize_workspace_mode_setting( $value ): string {
		$valid = array( 'auto', 'staging', 'production', 'disabled' );
		$value = sanitize_key( (string) $value );
		return in_array( $value, $valid, true ) ? $value : 'auto';
	}

	public function sanitize_workspace_allowlist_setting( $value ): array {
		// Input arrives as a newline-delimited string from the textarea.
		if ( is_string( $value ) ) {
			$lines = explode( "\n", wp_unslash( $value ) );
		} elseif ( is_array( $value ) ) {
			$lines = $value;
		} else {
			return array();
		}

		$sanitized = array();
		foreach ( $lines as $line ) {
			$fn = sanitize_text_field( trim( $line ) );
			// Only accept valid PHP identifiers — same rule as the validator class.
			if ( '' !== $fn && preg_match( '/^[a-zA-Z_][a-zA-Z0-9_]*$/', $fn ) ) {
				$sanitized[] = $fn;
			}
		}

		return array_values( array_unique( $sanitized ) );
	}

	public function ajax_workspace_recover(): void {
		check_ajax_referer( 'wpmcp_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized', 403 );
		}

		require_once dirname( __DIR__ ) . '/includes/modules/workspace/class-workspace-container.php';
		require_once dirname( __DIR__ ) . '/includes/modules/workspace/class-workspace-mu-loader.php';

		$result = WP_MCP_Toolkit_Workspace_MU_Loader::recover_from_crash();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success();
	}

	// ── Config Helpers ─────────────────────────────────────────────

	public function get_stdio_config(): array {
		$current_user = wp_get_current_user();

		return array(
			'command' => 'wp',
			'args'    => array(
				'--path=' . ABSPATH,
				'--user=' . $current_user->user_login,
				'mcp-adapter',
				'serve',
			),
		);
	}

	public function get_http_config(): array {
		$current_user = wp_get_current_user();

		return array(
			'command' => 'npx',
			'args'    => array( '@anthropic/mcp-wordpress-remote' ),
			'env'     => array(
				'WP_API_URL'      => rest_url(),
				'WP_API_USERNAME' => $current_user->user_login,
				'WP_API_PASSWORD' => '(application-password)',
			),
		);
	}
}
