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

// Module status.
$acf_active    = class_exists( 'ACF' );
$acf_version   = defined( 'ACF_VERSION' ) ? ACF_VERSION : '';
$gf_active     = class_exists( 'GFAPI' );
$gf_version    = class_exists( 'GFCommon' ) ? GFCommon::$version : '';
$yoast_active  = defined( 'WPSEO_VERSION' );
$yoast_version = defined( 'WPSEO_VERSION' ) ? WPSEO_VERSION : '';

// Collect our ability slugs for the form.
$wpmcp_abilities = array();
foreach ( $abilities as $ability ) {
	$name = $ability->get_name();
	if ( 0 === strpos( $name, 'wpmcp' ) ) {
		$wpmcp_abilities[] = $name;
	}
}
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
		<a href="?page=wp-mcp-toolkit&tab=templates" class="nav-tab <?php echo 'templates' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Templates', 'wp-mcp-toolkit' ); ?>
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

			<div class="wpmcp-module-status">
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

				<?php if ( $gf_active ) : ?>
					<div class="wpmcp-acf-status wpmcp-acf-active">
						<?php printf( esc_html__( 'Gravity Forms Detected (v%s)', 'wp-mcp-toolkit' ), esc_html( $gf_version ) ); ?>
					</div>
				<?php else : ?>
					<div class="wpmcp-acf-status wpmcp-acf-inactive">
						<?php esc_html_e( 'Gravity Forms not detected — GF abilities are disabled', 'wp-mcp-toolkit' ); ?>
					</div>
				<?php endif; ?>

				<?php if ( $yoast_active ) : ?>
					<div class="wpmcp-acf-status wpmcp-acf-active">
						<?php printf( esc_html__( 'Yoast SEO Detected (v%s)', 'wp-mcp-toolkit' ), esc_html( $yoast_version ) ); ?>
					</div>
				<?php else : ?>
					<div class="wpmcp-acf-status wpmcp-acf-inactive">
						<?php esc_html_e( 'Yoast SEO not detected — Yoast abilities are disabled', 'wp-mcp-toolkit' ); ?>
					</div>
				<?php endif; ?>
			</div>

			<form method="post" action="options.php">
				<?php settings_fields( 'wpmcp_settings' ); ?>

				<?php
				// Hidden field lists all known ability slugs so the sanitize
				// callback can compute disabled = all - enabled.
				?>
				<input type="hidden" name="wpmcp_all_abilities" value="<?php echo esc_attr( implode( ',', $wpmcp_abilities ) ); ?>">

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
							if ( 0 !== strpos( $name, 'wpmcp' ) ) {
								continue;
							}
							$is_enabled = ! in_array( $name, $disabled, true );
						?>
							<tr>
								<td><code><?php echo esc_html( $name ); ?></code></td>
								<td><?php echo esc_html( $ability->get_description() ); ?></td>
								<td>
									<label class="wpmcp-toggle">
										<input type="checkbox"
											name="wpmcp_enabled_abilities[]"
											value="<?php echo esc_attr( $name ); ?>"
											<?php checked( $is_enabled ); ?>
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

		<?php elseif ( 'templates' === $active_tab ) : ?>

			<h2><?php esc_html_e( 'Content Templates', 'wp-mcp-toolkit' ); ?></h2>
			<p><?php esc_html_e( 'Select a reference post for each post type. The plugin will extract the block structure as a reusable template that AI agents can use to create new content.', 'wp-mcp-toolkit' ); ?></p>

			<?php
			// Handle template extraction form submission.
			if ( isset( $_POST['wpmcp_extract_template'] ) && check_admin_referer( 'wpmcp_extract_template' ) ) {
				$ref_post_type = sanitize_key( $_POST['wpmcp_template_post_type'] ?? '' );
				$ref_post_id   = absint( $_POST['wpmcp_template_reference_post'] ?? 0 );

				if ( $ref_post_type && $ref_post_id ) {
					require_once dirname( __DIR__, 2 ) . '/includes/class-template-engine.php';
					$template = WP_MCP_Toolkit_Template_Engine::extract_template( $ref_post_id );

					if ( is_wp_error( $template ) ) {
						echo '<div class="notice notice-error"><p>' . esc_html( $template->get_error_message() ) . '</p></div>';
					} else {
						WP_MCP_Toolkit_Template_Engine::save_template( $ref_post_type, $template );
						echo '<div class="notice notice-success"><p>';
						printf(
							esc_html__( 'Template extracted: %d sections, %d placeholders.', 'wp-mcp-toolkit' ),
							count( $template['sections'] ),
							count( $template['placeholders'] )
						);
						echo '</p></div>';
					}
				}
			}

			// Get public post types for the dropdown.
			$post_types = get_post_types( array( 'public' => true ), 'objects' );
			?>

			<form method="post">
				<?php wp_nonce_field( 'wpmcp_extract_template' ); ?>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="wpmcp_template_post_type"><?php esc_html_e( 'Post Type', 'wp-mcp-toolkit' ); ?></label>
						</th>
						<td>
							<select name="wpmcp_template_post_type" id="wpmcp_template_post_type">
								<?php foreach ( $post_types as $pt ) : ?>
									<option value="<?php echo esc_attr( $pt->name ); ?>"><?php echo esc_html( $pt->label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wpmcp_template_reference_post"><?php esc_html_e( 'Reference Post ID', 'wp-mcp-toolkit' ); ?></label>
						</th>
						<td>
							<input type="number" name="wpmcp_template_reference_post" id="wpmcp_template_reference_post" min="1" class="small-text">
							<p class="description"><?php esc_html_e( 'Enter the ID of a post to use as the template reference. Its block structure will be extracted.', 'wp-mcp-toolkit' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Extract Template', 'wp-mcp-toolkit' ), 'primary', 'wpmcp_extract_template' ); ?>
			</form>

			<?php
			// Show existing templates.
			require_once dirname( __DIR__, 2 ) . '/includes/class-template-engine.php';
			$templates = WP_MCP_Toolkit_Template_Engine::list_templates();
			if ( ! empty( $templates ) ) :
			?>
				<h3><?php esc_html_e( 'Saved Templates', 'wp-mcp-toolkit' ); ?></h3>
				<table class="widefat">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Post Type', 'wp-mcp-toolkit' ); ?></th>
							<th><?php esc_html_e( 'Reference Post', 'wp-mcp-toolkit' ); ?></th>
							<th><?php esc_html_e( 'Sections', 'wp-mcp-toolkit' ); ?></th>
							<th><?php esc_html_e( 'Placeholders', 'wp-mcp-toolkit' ); ?></th>
							<th><?php esc_html_e( 'ACF Fields', 'wp-mcp-toolkit' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $templates as $tpl ) : ?>
							<tr>
								<td><code><?php echo esc_html( $tpl['post_type'] ); ?></code></td>
								<td>
									<?php
									$ref_post = get_post( $tpl['reference_post_id'] );
									echo $ref_post ? esc_html( $ref_post->post_title ) . ' (#' . esc_html( $tpl['reference_post_id'] ) . ')' : '#' . esc_html( $tpl['reference_post_id'] );
									?>
								</td>
								<td><?php echo esc_html( $tpl['section_count'] ); ?></td>
								<td><?php echo esc_html( $tpl['placeholder_count'] ); ?></td>
								<td><?php echo $tpl['has_acf_fields'] ? esc_html__( 'Yes', 'wp-mcp-toolkit' ) : esc_html__( 'No', 'wp-mcp-toolkit' ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

		<?php endif; ?>
	</div>
</div>
