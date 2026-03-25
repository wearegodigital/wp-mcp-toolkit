<?php
/**
 * Workspace tab partial.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();

require_once dirname( __DIR__, 2 ) . '/includes/modules/workspace/class-workspace-container.php';
require_once dirname( __DIR__, 2 ) . '/includes/modules/workspace/class-workspace-validator.php';
require_once dirname( __DIR__, 2 ) . '/includes/modules/workspace/class-workspace-manifest.php';
require_once dirname( __DIR__, 2 ) . '/includes/modules/workspace/class-workspace-mu-loader.php';

$current_mode  = WP_MCP_Toolkit_Workspace_Validator::get_mode();
$resolved_mode = WP_MCP_Toolkit_Workspace_Validator::resolve_mode();
$crash_status  = WP_MCP_Toolkit_Workspace_MU_Loader::get_crash_status();
$is_crashed    = $crash_status['crashed'];

$mode_options = array(
	'auto'       => array(
		'label' => __( 'Auto', 'wp-mcp-toolkit' ),
		'desc'  => __( 'Staging on non-production environments, Production on live sites.', 'wp-mcp-toolkit' ),
	),
	'staging'    => array(
		'label' => __( 'Staging', 'wp-mcp-toolkit' ),
		'desc'  => __( 'Permissive — any function not on the blocklist is allowed. Use during active development.', 'wp-mcp-toolkit' ),
	),
	'production' => array(
		'label' => __( 'Production', 'wp-mcp-toolkit' ),
		'desc'  => __( 'Restricted — only allowlisted functions may be called by AI agents.', 'wp-mcp-toolkit' ),
	),
	'disabled'   => array(
		'label' => __( 'Disabled', 'wp-mcp-toolkit' ),
		'desc'  => __( 'All workspace abilities are blocked. The MU-plugin loader will not run.', 'wp-mcp-toolkit' ),
	),
);

$resolved_badge_class = 'staging' === $resolved_mode ? 'wpmcp-badge-active' : ( 'production' === $resolved_mode ? 'wpmcp-badge-premium' : 'wpmcp-badge-inactive' );

$workspace_dir    = WP_MCP_Toolkit_Workspace_Container::get_active_dir();
$dir_is_writable  = is_writable( $workspace_dir );
$default_allowlist = WP_MCP_Toolkit_Workspace_Validator::get_default_allowlist();
$custom_allowlist  = (array) get_option( 'wpmcp_workspace_allowlist', array() );
?>

<?php if ( $is_crashed ) : ?>
<div class="notice notice-error wpmcp-workspace-crash-notice">
	<p>
		<strong><?php esc_html_e( 'Workspace crashed on last load.', 'wp-mcp-toolkit' ); ?></strong>
		<?php esc_html_e( 'The workspace encountered a fatal error during the previous request. Resolve the issue in your workspace files before recovering.', 'wp-mcp-toolkit' ); ?>
	</p>
	<p>
		<button type="button" class="button button-primary wpmcp-workspace-recover">
			<?php esc_html_e( 'Recover Workspace', 'wp-mcp-toolkit' ); ?>
		</button>
		&nbsp;
		<a href="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" class="button">
			<?php esc_html_e( 'Disable Workspace', 'wp-mcp-toolkit' ); ?>
		</a>
	</p>
</div>
<?php endif; ?>

<!-- Section 1: Mode Selector -->
<div class="wpmcp-card">
	<div class="wpmcp-card-header">
		<h2><?php esc_html_e( 'Workspace Mode', 'wp-mcp-toolkit' ); ?></h2>
		<span class="wpmcp-badge <?php echo esc_attr( $resolved_badge_class ); ?>">
			<?php
			/* translators: %s: resolved workspace mode (e.g. Staging, Production, Disabled) */
			printf( esc_html__( 'Currently: %s', 'wp-mcp-toolkit' ), esc_html( ucfirst( $resolved_mode ) ) );
			?>
		</span>
	</div>

	<form method="post" action="options.php">
		<?php settings_fields( 'wpmcp_settings' ); ?>

		<div class="wpmcp-workspace-modes">
			<?php foreach ( $mode_options as $value => $option ) : ?>
				<label class="wpmcp-workspace-mode-option <?php echo esc_attr( $value === $current_mode ? 'wpmcp-workspace-mode-selected' : '' ); ?>">
					<input
						type="radio"
						name="wpmcp_workspace_mode"
						value="<?php echo esc_attr( $value ); ?>"
						<?php checked( $current_mode, $value ); ?>
					>
					<span class="wpmcp-workspace-mode-label"><?php echo esc_html( $option['label'] ); ?></span>
					<span class="wpmcp-workspace-mode-desc"><?php echo esc_html( $option['desc'] ); ?></span>
				</label>
			<?php endforeach; ?>
		</div>

		<?php submit_button( __( 'Save Mode', 'wp-mcp-toolkit' ) ); ?>
	</form>
</div>

<?php if ( 'disabled' !== $current_mode ) : ?>
<!-- Section 2: Production Allowlist -->
<div class="wpmcp-card">
	<div class="wpmcp-card-header">
		<h2><?php esc_html_e( 'Production Allowlist', 'wp-mcp-toolkit' ); ?></h2>
	</div>

	<p class="description">
		<?php esc_html_e( 'These functions are allowed in production mode. The default list is always included.', 'wp-mcp-toolkit' ); ?>
	</p>

	<div class="wpmcp-workspace-allowlist-defaults">
		<label class="wpmcp-field-label"><?php esc_html_e( 'Default allowlist (always active):', 'wp-mcp-toolkit' ); ?></label>
		<div class="wpmcp-workspace-tags">
			<?php foreach ( $default_allowlist as $fn ) : ?>
				<span class="wpmcp-workspace-tag"><?php echo esc_html( $fn ); ?></span>
			<?php endforeach; ?>
		</div>
	</div>

	<form method="post" action="options.php">
		<?php settings_fields( 'wpmcp_settings' ); ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="wpmcp_workspace_allowlist"><?php esc_html_e( 'Custom additions', 'wp-mcp-toolkit' ); ?></label>
				</th>
				<td>
					<textarea
						id="wpmcp_workspace_allowlist"
						name="wpmcp_workspace_allowlist"
						rows="6"
						class="large-text code"
						placeholder="<?php esc_attr_e( 'one_function_per_line', 'wp-mcp-toolkit' ); ?>"
					><?php echo esc_textarea( implode( "\n", $custom_allowlist ) ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'One function name per line. Only valid PHP identifiers are accepted.', 'wp-mcp-toolkit' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Save Allowlist', 'wp-mcp-toolkit' ) ); ?>
	</form>
</div>
<?php endif; ?>

<!-- Section 3: Artifact Browser -->
<div class="wpmcp-card">
	<div class="wpmcp-card-header">
		<h2><?php esc_html_e( 'Workspace Artifacts', 'wp-mcp-toolkit' ); ?></h2>
	</div>

	<p class="description">
		<strong><?php esc_html_e( 'Directory:', 'wp-mcp-toolkit' ); ?></strong>
		<code><?php echo esc_html( $workspace_dir ); ?></code>
		<?php if ( $dir_is_writable ) : ?>
			<span class="wpmcp-badge wpmcp-badge-active"><?php esc_html_e( 'Writable', 'wp-mcp-toolkit' ); ?></span>
		<?php else : ?>
			<span class="wpmcp-badge wpmcp-badge-inactive"><?php esc_html_e( 'Not Writable', 'wp-mcp-toolkit' ); ?></span>
		<?php endif; ?>
	</p>

	<?php
	$artifacts = WP_MCP_Toolkit_Workspace_Manifest::list_artifacts();
	if ( empty( $artifacts ) ) :
	?>
		<div class="wpmcp-empty-state">
			<span class="dashicons dashicons-portfolio"></span>
			<p><?php esc_html_e( 'No artifacts yet. AI agents can create workspace artifacts using MCP abilities.', 'wp-mcp-toolkit' ); ?></p>
		</div>
	<?php else : ?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'wp-mcp-toolkit' ); ?></th>
					<th><?php esc_html_e( 'Type', 'wp-mcp-toolkit' ); ?></th>
					<th><?php esc_html_e( 'File', 'wp-mcp-toolkit' ); ?></th>
					<th><?php esc_html_e( 'Created', 'wp-mcp-toolkit' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $artifacts as $artifact ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $artifact['name'] ?? '' ); ?></strong></td>
						<td><code><?php echo esc_html( $artifact['type'] ?? '' ); ?></code></td>
						<td><code><?php echo esc_html( basename( $artifact['file'] ?? '' ) ); ?></code></td>
						<td><?php echo esc_html( $artifact['created_at'] ?? '' ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
