<?php
/**
 * Templates tab partial — AJAX post search + delete.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();

// Display redirect notices from template extraction.
$wpmcp_notice = isset( $_GET['wpmcp_notice'] ) ? sanitize_key( $_GET['wpmcp_notice'] ) : '';
if ( 'success' === $wpmcp_notice ) {
	$sections      = absint( $_GET['wpmcp_sections'] ?? 0 );
	$placeholders  = absint( $_GET['wpmcp_placeholders'] ?? 0 );
	echo '<div class="notice notice-success"><p>';
	printf(
		esc_html__( 'Template extracted: %d sections, %d placeholders.', 'wp-mcp-toolkit' ),
		$sections,
		$placeholders
	);
	echo '</p></div>';
} elseif ( 'error' === $wpmcp_notice && isset( $_GET['wpmcp_message'] ) ) {
	echo '<div class="notice notice-error"><p>' . esc_html( sanitize_text_field( wp_unslash( $_GET['wpmcp_message'] ) ) ) . '</p></div>';
}

$post_types = get_post_types( array( 'public' => true ), 'objects' );
?>

<p><?php esc_html_e( 'Select a reference post for each post type. The plugin will extract the block structure as a reusable template that AI agents can use to create new content.', 'wp-mcp-toolkit' ); ?></p>

<div class="wpmcp-template-form">
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
					<label for="wpmcp_template_reference_post"><?php esc_html_e( 'Reference Post', 'wp-mcp-toolkit' ); ?></label>
				</th>
				<td>
					<select name="wpmcp_template_reference_post" id="wpmcp_template_reference_post" class="wpmcp-post-search" style="width: 100%; max-width: 400px;">
						<option value=""><?php esc_html_e( 'Search for a post…', 'wp-mcp-toolkit' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Search and select a post to use as the template reference. Its block structure will be extracted.', 'wp-mcp-toolkit' ); ?></p>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Extract Template', 'wp-mcp-toolkit' ), 'primary', 'wpmcp_extract_template' ); ?>
	</form>
</div>

<?php
require_once dirname( __DIR__, 2 ) . '/includes/class-template-engine.php';
$templates = WP_MCP_Toolkit_Template_Engine::list_templates();
if ( ! empty( $templates ) ) :
?>
	<h3><?php esc_html_e( 'Saved Templates', 'wp-mcp-toolkit' ); ?></h3>
	<table class="widefat wpmcp-templates-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Post Type', 'wp-mcp-toolkit' ); ?></th>
				<th><?php esc_html_e( 'Reference Post', 'wp-mcp-toolkit' ); ?></th>
				<th><?php esc_html_e( 'Sections', 'wp-mcp-toolkit' ); ?></th>
				<th><?php esc_html_e( 'Placeholders', 'wp-mcp-toolkit' ); ?></th>
				<th><?php esc_html_e( 'ACF Fields', 'wp-mcp-toolkit' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'wp-mcp-toolkit' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $templates as $tpl ) : ?>
				<tr data-post-type="<?php echo esc_attr( $tpl['post_type'] ); ?>">
					<td><code><?php echo esc_html( $tpl['post_type'] ); ?></code></td>
					<td>
						<?php
						$ref_post = get_post( $tpl['reference_post_id'] );
						echo $ref_post
							? esc_html( $ref_post->post_title ) . ' <span style="color: var(--wpmcp-text-muted);">#' . esc_html( $tpl['reference_post_id'] ) . '</span>'
							: '#' . esc_html( $tpl['reference_post_id'] );
						?>
					</td>
					<td><?php echo esc_html( $tpl['section_count'] ); ?></td>
					<td><?php echo esc_html( $tpl['placeholder_count'] ); ?></td>
					<td><?php echo $tpl['has_acf_fields'] ? esc_html__( 'Yes', 'wp-mcp-toolkit' ) : esc_html__( 'No', 'wp-mcp-toolkit' ); ?></td>
					<td>
						<button type="button" class="button wpmcp-btn-danger wpmcp-delete-template" data-post-type="<?php echo esc_attr( $tpl['post_type'] ); ?>">
							<?php esc_html_e( 'Delete', 'wp-mcp-toolkit' ); ?>
						</button>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>
