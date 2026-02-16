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

		wp_enqueue_script(
			'wpmcp-admin',
			plugins_url( 'admin/js/admin.js', dirname( __FILE__ ) . '/../wp-mcp-toolkit.php' ),
			array(),
			WP_MCP_VERSION,
			true
		);
	}

	public function register_settings(): void {
		// The form submits enabled abilities; we compute and store disabled ones.
		register_setting( 'wpmcp_settings', 'wpmcp_disabled_abilities', array(
			'type'              => 'array',
			'sanitize_callback' => array( $this, 'sanitize_abilities_setting' ),
			'default'           => array(),
		) );
	}

	/**
	 * Converts the submitted enabled-abilities list into a disabled-abilities list.
	 *
	 * The form posts wpmcp_enabled_abilities[] (checked toggles) and
	 * wpmcp_all_abilities (comma-separated list of every slug). Disabled
	 * abilities = all - enabled. This approach means "checked = enabled"
	 * in the UI, which is the intuitive behavior.
	 */
	public function sanitize_abilities_setting( $value ): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified by settings_fields().
		$all_raw     = isset( $_POST['wpmcp_all_abilities'] ) ? sanitize_text_field( wp_unslash( $_POST['wpmcp_all_abilities'] ) ) : '';
		$all         = array_filter( explode( ',', $all_raw ) );
		$enabled_raw = isset( $_POST['wpmcp_enabled_abilities'] ) && is_array( $_POST['wpmcp_enabled_abilities'] )
			? array_map( 'sanitize_text_field', wp_unslash( $_POST['wpmcp_enabled_abilities'] ) )
			: array();

		return array_values( array_diff( $all, $enabled_raw ) );
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'connection';

		include __DIR__ . '/views/settings-page.php';
	}

	public function get_stdio_config(): array {
		return array(
			'command' => 'wp',
			'args'    => array(
				'--path=' . ABSPATH,
				'mcp-adapter',
				'serve',
			),
		);
	}

	public function get_http_config(): array {
		return array(
			'command' => 'npx',
			'args'    => array( '@anthropic/mcp-wordpress-remote' ),
			'env'     => array(
				'WP_API_URL'      => rest_url(),
				'WP_API_USERNAME' => '(your-username)',
				'WP_API_PASSWORD' => '(application-password)',
			),
		);
	}
}
