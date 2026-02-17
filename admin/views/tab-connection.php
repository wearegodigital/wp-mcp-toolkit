<?php
/**
 * Connection tab partial.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();
?>

<div class="wpmcp-card">
	<h2><?php esc_html_e( 'STDIO Configuration (Local / WP-CLI)', 'wp-mcp-toolkit' ); ?></h2>
	<p><?php esc_html_e( 'Add this to your MCP client config (e.g. Claude Desktop, claude_desktop_config.json):', 'wp-mcp-toolkit' ); ?></p>
	<div class="wpmcp-config-block">
		<pre id="wpmcp-stdio-config"><?php echo esc_html( wp_json_encode( $this->get_stdio_config(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></pre>
		<button type="button" class="button wpmcp-copy-btn" data-target="wpmcp-stdio-config">
			<?php esc_html_e( 'Copy', 'wp-mcp-toolkit' ); ?>
		</button>
	</div>
</div>

<div class="wpmcp-card">
	<h2><?php esc_html_e( 'HTTP Configuration (Remote)', 'wp-mcp-toolkit' ); ?></h2>
	<p>
		<?php esc_html_e( 'For remote connections, you need an Application Password.', 'wp-mcp-toolkit' ); ?>
		<a href="<?php echo esc_url( admin_url( 'profile.php#application-passwords-section' ) ); ?>">
			<?php esc_html_e( 'Create one here.', 'wp-mcp-toolkit' ); ?>
		</a>
	</p>
	<div class="wpmcp-config-block">
		<pre id="wpmcp-http-config"><?php echo esc_html( wp_json_encode( $this->get_http_config(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></pre>
		<button type="button" class="button wpmcp-copy-btn" data-target="wpmcp-http-config">
			<?php esc_html_e( 'Copy', 'wp-mcp-toolkit' ); ?>
		</button>
	</div>
</div>
