<?php
/**
 * Admin settings page template — shell with tab navigation.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();

$active_tab = $active_tab ?? 'connection';
$valid_tabs = array( 'connection', 'addons', 'abilities', 'templates', 'workspace', 'blocks' );
if ( ! in_array( $active_tab, $valid_tabs, true ) ) {
	$active_tab = 'connection';
}
?>
<div class="wrap wpmcp-admin">
	<h1>
		<span class="dashicons dashicons-rest-api" style="font-size: 24px; width: 24px; height: 24px; margin-right: 4px;"></span>
		<?php esc_html_e( 'WP MCP Toolkit', 'wp-mcp-toolkit' ); ?>
		<span class="wpmcp-version-badge">v<?php echo esc_html( WP_MCP_VERSION ); ?></span>
	</h1>

	<nav class="nav-tab-wrapper">
		<a href="?page=wp-mcp-toolkit&tab=connection" class="nav-tab <?php echo 'connection' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Connection', 'wp-mcp-toolkit' ); ?>
		</a>
		<a href="?page=wp-mcp-toolkit&tab=addons" class="nav-tab <?php echo 'addons' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Add-ons', 'wp-mcp-toolkit' ); ?>
		</a>
		<a href="?page=wp-mcp-toolkit&tab=abilities" class="nav-tab <?php echo 'abilities' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Abilities', 'wp-mcp-toolkit' ); ?>
		</a>
		<a href="?page=wp-mcp-toolkit&tab=templates" class="nav-tab <?php echo 'templates' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Templates', 'wp-mcp-toolkit' ); ?>
		</a>
		<a href="?page=wp-mcp-toolkit&tab=workspace" class="nav-tab <?php echo 'workspace' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Workspace', 'wp-mcp-toolkit' ); ?>
		</a>
		<a href="?page=wp-mcp-toolkit&tab=blocks" class="nav-tab <?php echo 'blocks' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Blocks', 'wp-mcp-toolkit' ); ?>
		</a>
	</nav>

	<div class="wpmcp-tab-content">
		<?php include __DIR__ . '/tab-' . $active_tab . '.php'; ?>
	</div>
</div>
