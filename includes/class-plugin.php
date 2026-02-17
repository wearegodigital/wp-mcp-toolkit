<?php
/**
 * WP MCP Toolkit — Main plugin class.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();

final class WP_MCP_Toolkit {

	private static ?self $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->init();
		}
		return self::$instance;
	}

	private function init(): void {
		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_categories' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
		add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
	}

	public function register_categories(): void {
		$categories = array(
			'wpmcp-content'  => array(
				'label'       => __( 'Content Management', 'wp-mcp-toolkit' ),
				'description' => __( 'Abilities for managing posts, pages, and custom post types.', 'wp-mcp-toolkit' ),
			),
			'wpmcp-blocks'   => array(
				'label'       => __( 'Block Content', 'wp-mcp-toolkit' ),
				'description' => __( 'Abilities for parsing and editing block content.', 'wp-mcp-toolkit' ),
			),
			'wpmcp-taxonomy' => array(
				'label'       => __( 'Taxonomies & Terms', 'wp-mcp-toolkit' ),
				'description' => __( 'Abilities for managing taxonomies and terms.', 'wp-mcp-toolkit' ),
			),
			'wpmcp-media'    => array(
				'label'       => __( 'Media Library', 'wp-mcp-toolkit' ),
				'description' => __( 'Abilities for managing media attachments.', 'wp-mcp-toolkit' ),
			),
			'wpmcp-schema'   => array(
				'label'       => __( 'Site Discovery', 'wp-mcp-toolkit' ),
				'description' => __( 'Abilities for discovering site structure.', 'wp-mcp-toolkit' ),
			),
		);

		if ( class_exists( 'ACF' ) ) {
			$categories['wpmcp-acf-fields'] = array(
				'label'       => __( 'ACF Fields', 'wp-mcp-toolkit' ),
				'description' => __( 'Abilities for managing Advanced Custom Fields data.', 'wp-mcp-toolkit' ),
			);
		}

		if ( class_exists( 'GFAPI' ) ) {
			$categories['wpmcp-gf'] = array(
				'label'       => __( 'Gravity Forms', 'wp-mcp-toolkit' ),
				'description' => __( 'Abilities for managing Gravity Forms data and entries.', 'wp-mcp-toolkit' ),
			);
		}

		if ( defined( 'WPSEO_VERSION' ) ) {
			$categories['wpmcp-yoast'] = array(
				'label'       => __( 'Yoast SEO', 'wp-mcp-toolkit' ),
				'description' => __( 'Abilities for managing Yoast SEO metadata.', 'wp-mcp-toolkit' ),
			);
		}

		$categories['wpmcp-templates'] = array(
			'label'       => __( 'Content Templates', 'wp-mcp-toolkit' ),
			'description' => __( 'Abilities for managing content templates and creating posts from templates.', 'wp-mcp-toolkit' ),
		);

		foreach ( $categories as $slug => $args ) {
			wp_register_ability_category( $slug, $args );
		}
	}

	public function register_abilities(): void {
		require_once __DIR__ . '/class-ability-registrar.php';
		( new WP_MCP_Toolkit_Ability_Registrar() )->register();

		// ACF module.
		if ( class_exists( 'ACF' ) ) {
			require_once __DIR__ . '/modules/acf/class-acf-module.php';
			WP_MCP_Toolkit_ACF_Module::init();
		}

		// Gravity Forms module.
		if ( class_exists( 'GFAPI' ) ) {
			require_once __DIR__ . '/modules/gravity-forms/class-gf-module.php';
			WP_MCP_Toolkit_GF_Module::init();
		}

		// Yoast SEO module.
		if ( defined( 'WPSEO_VERSION' ) ) {
			require_once __DIR__ . '/modules/yoast/class-yoast-module.php';
			WP_MCP_Toolkit_Yoast_Module::init();
		}

		// Content Templates (always available).
		require_once __DIR__ . '/Abilities/class-template-abilities.php';
		$disabled = get_option( 'wpmcp_disabled_abilities', array() );
		( new WP_MCP_Toolkit_Template_Abilities() )->register( $disabled );
	}

	public function register_admin_page(): void {
		require_once __DIR__ . '/../admin/class-admin-page.php';
		( new WP_MCP_Toolkit_Admin_Page() )->register();
	}
}

WP_MCP_Toolkit::instance();
