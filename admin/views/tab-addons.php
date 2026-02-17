<?php
/**
 * Add-ons tab partial.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();

require_once dirname( __DIR__, 2 ) . '/includes/class-addon-registry.php';
$registry        = WP_MCP_Toolkit_Addon_Registry::instance();
$addons          = $registry->get_all();
$licenses        = get_option( 'wpmcp_licenses', array() );
$disabled_addons = get_option( 'wpmcp_disabled_addons', array() );
?>

<p><?php esc_html_e( 'Extend WP MCP Toolkit with add-ons for popular WordPress plugins. Toggle add-ons on or off to control which abilities are available.', 'wp-mcp-toolkit' ); ?></p>

<?php if ( empty( $addons ) ) : ?>
	<div class="wpmcp-empty-state">
		<span class="dashicons dashicons-admin-plugins"></span>
		<p><?php esc_html_e( 'No add-ons registered.', 'wp-mcp-toolkit' ); ?></p>
	</div>
<?php else : ?>
	<div class="wpmcp-addons-grid">
		<?php foreach ( $addons as $addon ) :
			$slug         = $addon->get_slug();
			$is_available = $addon->is_available();
			$is_premium   = $addon->is_premium();
			$is_licensed  = $addon->is_licensed();
			$is_enabled   = ! in_array( $slug, $disabled_addons, true );
			$license_key  = $licenses[ $slug ] ?? '';
		?>
			<div class="wpmcp-addon-card <?php echo ! $is_enabled ? 'wpmcp-addon-disabled' : ''; ?>" data-addon-slug="<?php echo esc_attr( $slug ); ?>">
				<div class="wpmcp-addon-header">
					<div class="wpmcp-addon-icon">
						<span class="dashicons <?php echo esc_attr( $addon->get_icon() ); ?>"></span>
					</div>
					<div style="flex: 1;">
						<div class="wpmcp-addon-title"><?php echo esc_html( $addon->get_name() ); ?></div>
						<div class="wpmcp-addon-meta">
							<?php echo esc_html( $addon->get_ability_count() ); ?> <?php esc_html_e( 'abilities', 'wp-mcp-toolkit' ); ?>
							<?php if ( $is_available && $addon->get_version() ) : ?>
								&middot; v<?php echo esc_html( $addon->get_version() ); ?>
							<?php endif; ?>
						</div>
					</div>
					<div class="wpmcp-addon-toggle">
						<label class="wpmcp-toggle">
							<input type="checkbox"
								class="wpmcp-addon-enabled"
								data-addon="<?php echo esc_attr( $slug ); ?>"
								<?php checked( $is_enabled ); ?>
								<?php disabled( ! $is_available ); ?>
							>
							<span class="wpmcp-toggle-slider"></span>
						</label>
					</div>
				</div>

				<p class="wpmcp-addon-description"><?php echo esc_html( $addon->get_description() ); ?></p>

				<div class="wpmcp-addon-footer">
					<div>
						<?php if ( ! $is_available ) : ?>
							<span class="wpmcp-badge wpmcp-badge-inactive"><?php esc_html_e( 'Not Detected', 'wp-mcp-toolkit' ); ?></span>
						<?php elseif ( $is_enabled ) : ?>
							<span class="wpmcp-badge wpmcp-badge-active"><?php esc_html_e( 'Enabled', 'wp-mcp-toolkit' ); ?></span>
						<?php else : ?>
							<span class="wpmcp-badge wpmcp-badge-inactive"><?php esc_html_e( 'Disabled', 'wp-mcp-toolkit' ); ?></span>
						<?php endif; ?>

						<?php if ( $is_premium ) : ?>
							<span class="wpmcp-badge wpmcp-badge-premium"><?php esc_html_e( 'Premium', 'wp-mcp-toolkit' ); ?></span>
						<?php else : ?>
							<span class="wpmcp-badge wpmcp-badge-free"><?php esc_html_e( 'Free', 'wp-mcp-toolkit' ); ?></span>
						<?php endif; ?>
					</div>

					<?php if ( $is_premium && $is_available ) : ?>
						<?php if ( $is_licensed ) : ?>
							<span class="wpmcp-badge wpmcp-badge-licensed"><?php esc_html_e( 'Licensed', 'wp-mcp-toolkit' ); ?></span>
						<?php else : ?>
							<span class="wpmcp-badge wpmcp-badge-unlicensed"><?php esc_html_e( 'No License', 'wp-mcp-toolkit' ); ?></span>
						<?php endif; ?>
					<?php endif; ?>
				</div>

				<?php if ( $is_premium && $is_available ) : ?>
					<div class="wpmcp-license-input" data-addon="<?php echo esc_attr( $slug ); ?>">
						<input type="text"
							value="<?php echo esc_attr( $license_key ); ?>"
							placeholder="<?php esc_attr_e( 'Enter license key…', 'wp-mcp-toolkit' ); ?>"
							class="wpmcp-license-key"
						>
						<button type="button" class="button wpmcp-save-license">
							<?php echo $license_key ? esc_html__( 'Update', 'wp-mcp-toolkit' ) : esc_html__( 'Activate', 'wp-mcp-toolkit' ); ?>
						</button>
					</div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
<?php endif; ?>
