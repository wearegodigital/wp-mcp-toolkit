<?php
/**
 * Blocks tab partial.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();

$acf_active = class_exists( 'ACF' );
$method     = get_option( 'wpmcp_block_method', 'recommended' );
?>

<div class="wpmcp-card">
	<div class="wpmcp-card-header">
		<h2><?php esc_html_e( 'Block Build Method', 'wp-mcp-toolkit' ); ?></h2>
	</div>

	<p class="description">
		<?php esc_html_e( 'Choose how AI agents should build Gutenberg blocks when asked.', 'wp-mcp-toolkit' ); ?>
	</p>

	<?php if ( ! $acf_active ) : ?>
		<div class="notice notice-info inline">
			<p><?php esc_html_e( 'ACF is not installed. Only vanilla Gutenberg blocks are available.', 'wp-mcp-toolkit' ); ?></p>
		</div>
	<?php endif; ?>

	<form method="post" action="options.php">
		<?php settings_fields( 'wpmcp_settings' ); ?>
		<?php do_settings_sections( 'wpmcp_settings' ); ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Method', 'wp-mcp-toolkit' ); ?></th>
				<td>
					<fieldset>
						<legend class="screen-reader-text">
							<?php esc_html_e( 'Block Build Method', 'wp-mcp-toolkit' ); ?>
						</legend>

						<label style="display:block; margin-bottom:12px;">
							<input type="radio" name="wpmcp_block_method" value="recommended" <?php checked( $method, 'recommended' ); ?> />
							<strong><?php esc_html_e( 'Recommended', 'wp-mcp-toolkit' ); ?></strong>
							<p class="description"><?php esc_html_e( 'Agent recommends the best approach and confirms with you before building.', 'wp-mcp-toolkit' ); ?></p>
						</label>

						<label style="display:block; margin-bottom:12px;">
							<input type="radio" name="wpmcp_block_method" value="vanilla" <?php checked( $method, 'vanilla' ); ?> />
							<strong><?php esc_html_e( 'Vanilla Gutenberg', 'wp-mcp-toolkit' ); ?></strong>
							<p class="description"><?php esc_html_e( 'Always build standard Gutenberg blocks with PHP render and editor controls.', 'wp-mcp-toolkit' ); ?></p>
						</label>

						<?php if ( $acf_active ) : ?>
							<label style="display:block; margin-bottom:12px;">
								<input type="radio" name="wpmcp_block_method" value="acf" <?php checked( $method, 'acf' ); ?> />
								<strong><?php esc_html_e( 'ACF Blocks', 'wp-mcp-toolkit' ); ?></strong>
								<p class="description"><?php esc_html_e( 'Always build ACF-powered blocks with field groups managed by Advanced Custom Fields.', 'wp-mcp-toolkit' ); ?></p>
							</label>

							<label style="display:block; margin-bottom:12px;">
								<input type="radio" name="wpmcp_block_method" value="agent-decides" <?php checked( $method, 'agent-decides' ); ?> />
								<strong><?php esc_html_e( 'Agent Decides', 'wp-mcp-toolkit' ); ?></strong>
								<p class="description"><?php esc_html_e( 'Let the AI agent automatically choose the best approach based on block complexity.', 'wp-mcp-toolkit' ); ?></p>
							</label>
						<?php endif; ?>

					</fieldset>
				</td>
			</tr>
		</table>

		<?php submit_button(); ?>
	</form>
</div>
