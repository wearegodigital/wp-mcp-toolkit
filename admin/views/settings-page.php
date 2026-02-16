<?php
/**
 * Admin settings page template.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();

$active_tab = $active_tab ?? 'connection';
$disabled   = get_option( 'wpmcp_disabled_abilities', array() );
$abilities  = wp_get_abilities();

// ACF status.
$acf_active  = class_exists( 'ACF' );
$acf_version = defined( 'ACF_VERSION' ) ? ACF_VERSION : '';
?>
<div class="wrap wpmcp-admin">
	<h1><?php esc_html_e( 'WP MCP Toolkit', 'wp-mcp-toolkit' ); ?></h1>

	<nav class="nav-tab-wrapper">
		<a href="?page=wp-mcp-toolkit&tab=connection" class="nav-tab <?php echo 'connection' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Connection Info', 'wp-mcp-toolkit' ); ?>
		</a>
		<a href="?page=wp-mcp-toolkit&tab=abilities" class="nav-tab <?php echo 'abilities' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Abilities', 'wp-mcp-toolkit' ); ?>
		</a>
	</nav>

	<div class="wpmcp-tab-content">
		<?php if ( 'connection' === $active_tab ) : ?>

			<h2><?php esc_html_e( 'STDIO Configuration (Local / WP-CLI)', 'wp-mcp-toolkit' ); ?></h2>
			<p><?php esc_html_e( 'Add this to your MCP client config (e.g. Claude Desktop, claude_desktop_config.json):', 'wp-mcp-toolkit' ); ?></p>
			<div class="wpmcp-config-block">
				<pre id="wpmcp-stdio-config"><?php echo esc_html( wp_json_encode( $this->get_stdio_config(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></pre>
				<button type="button" class="button wpmcp-copy-btn" data-target="wpmcp-stdio-config">
					<?php esc_html_e( 'Copy', 'wp-mcp-toolkit' ); ?>
				</button>
			</div>

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

		<?php elseif ( 'abilities' === $active_tab ) : ?>

			<h2><?php esc_html_e( 'Registered Abilities', 'wp-mcp-toolkit' ); ?></h2>

			<?php if ( $acf_active ) : ?>
				<div class="wpmcp-acf-status wpmcp-acf-active">
					<?php printf( esc_html__( 'ACF Detected (v%s)', 'wp-mcp-toolkit' ), esc_html( $acf_version ) ); ?>
					<?php if ( version_compare( $acf_version, '6.8', '<' ) ) : ?>
						<span class="wpmcp-acf-warning"> — <?php esc_html_e( 'Update to 6.8+ recommended for native Abilities API support', 'wp-mcp-toolkit' ); ?></span>
					<?php endif; ?>
				</div>
			<?php else : ?>
				<div class="wpmcp-acf-status wpmcp-acf-inactive">
					<?php esc_html_e( 'ACF not detected — ACF abilities are disabled', 'wp-mcp-toolkit' ); ?>
				</div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'wpmcp_settings' ); ?>

				<table class="widefat wpmcp-abilities-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Ability', 'wp-mcp-toolkit' ); ?></th>
							<th><?php esc_html_e( 'Description', 'wp-mcp-toolkit' ); ?></th>
							<th><?php esc_html_e( 'Enabled', 'wp-mcp-toolkit' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $abilities as $ability ) :
							$name = $ability->get_name();
							// Only show wpmcp abilities.
							if ( 0 !== strpos( $name, 'wpmcp' ) ) {
								continue;
							}
							$is_disabled = in_array( $name, $disabled, true );
						?>
							<tr>
								<td><code><?php echo esc_html( $name ); ?></code></td>
								<td><?php echo esc_html( $ability->get_description() ); ?></td>
								<td>
									<label class="wpmcp-toggle">
										<input type="checkbox"
											name="wpmcp_disabled_abilities[]"
											value="<?php echo esc_attr( $name ); ?>"
											<?php checked( ! $is_disabled ); ?>
										>
										<span class="wpmcp-toggle-slider"></span>
									</label>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<?php submit_button(); ?>
			</form>

		<?php endif; ?>
	</div>
</div>
