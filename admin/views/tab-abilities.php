<?php
/**
 * Abilities tab partial — grouped by category with human-readable names.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();

require_once dirname( __DIR__, 2 ) . '/includes/class-abstract-abilities.php';

$disabled   = get_option( 'wpmcp_disabled_abilities', array() );
$abilities  = wp_get_abilities();

// Group abilities by category.
$groups = array();
$wpmcp_abilities = array();

foreach ( $abilities as $ability ) {
	$slug = $ability->get_name();
	if ( 0 !== strpos( $slug, 'wpmcp' ) ) {
		continue;
	}
	$wpmcp_abilities[] = $slug;
	$category = $ability->get_category() ?: 'wpmcp-other';
	if ( ! isset( $groups[ $category ] ) ) {
		$groups[ $category ] = array();
	}
	$groups[ $category ][] = $ability;
}

// Category display labels.
$category_labels = array(
	'wpmcp-content'    => __( 'Content Management', 'wp-mcp-toolkit' ),
	'wpmcp-blocks'     => __( 'Block Content', 'wp-mcp-toolkit' ),
	'wpmcp-taxonomy'   => __( 'Taxonomies & Terms', 'wp-mcp-toolkit' ),
	'wpmcp-media'      => __( 'Media Library', 'wp-mcp-toolkit' ),
	'wpmcp-schema'     => __( 'Site Discovery', 'wp-mcp-toolkit' ),
	'wpmcp-acf-fields' => __( 'ACF Fields', 'wp-mcp-toolkit' ),
	'wpmcp-gf'         => __( 'Gravity Forms', 'wp-mcp-toolkit' ),
	'wpmcp-yoast'      => __( 'Yoast SEO', 'wp-mcp-toolkit' ),
	'wpmcp-templates'  => __( 'Content Templates', 'wp-mcp-toolkit' ),
	'wpmcp-workspace'        => __( 'Workspace', 'wp-mcp-toolkit' ),
	'wpmcp-workspace-blocks' => __( 'Workspace Blocks', 'wp-mcp-toolkit' ),
	'wpmcp-bricks'           => __( 'Bricks Workspace', 'wp-mcp-toolkit' ),
);
?>

<form method="post" action="options.php">
	<?php settings_fields( 'wpmcp_settings' ); ?>
	<input type="hidden" name="wpmcp_all_abilities" value="<?php echo esc_attr( implode( ',', $wpmcp_abilities ) ); ?>">

	<?php foreach ( $groups as $category => $group_abilities ) :
		$group_label = $category_labels[ $category ] ?? ucwords( str_replace( array( 'wpmcp-', '-' ), array( '', ' ' ), $category ) );
		$count       = count( $group_abilities );
	?>
		<div class="wpmcp-ability-group" data-group="<?php echo esc_attr( $category ); ?>">
			<div class="wpmcp-ability-group-header">
				<div class="wpmcp-ability-group-title">
					<span class="dashicons dashicons-arrow-down-alt2"></span>
					<?php echo esc_html( $group_label ); ?>
					<span class="wpmcp-ability-group-count">(<?php echo esc_html( $count ); ?>)</span>
				</div>
			</div>
			<div class="wpmcp-ability-group-body">
				<?php foreach ( $group_abilities as $ability ) :
					$slug       = $ability->get_name();
					$label      = WP_MCP_Toolkit_Abstract_Abilities::get_ability_label( $slug );
					$is_enabled = ! in_array( $slug, $disabled, true );
				?>
					<div class="wpmcp-ability-row">
						<div class="wpmcp-ability-name">
							<?php echo esc_html( $label ); ?>
							<div class="wpmcp-ability-slug"><?php echo esc_html( $slug ); ?></div>
						</div>
						<div class="wpmcp-ability-desc">
							<?php echo esc_html( $ability->get_description() ); ?>
						</div>
						<div class="wpmcp-ability-toggle">
							<label class="wpmcp-toggle">
								<input type="checkbox"
									name="wpmcp_enabled_abilities[]"
									value="<?php echo esc_attr( $slug ); ?>"
									<?php checked( $is_enabled ); ?>
								>
								<span class="wpmcp-toggle-slider"></span>
							</label>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endforeach; ?>

	<?php submit_button(); ?>
</form>
