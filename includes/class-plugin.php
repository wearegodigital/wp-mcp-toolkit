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

		foreach ( $categories as $slug => $args ) {
			wp_register_ability_category( $slug, $args );
		}

		// ACF categories if available.
		if ( class_exists( 'ACF' ) ) {
			wp_register_ability_category(
				'wpmcp-acf-fields',
				array(
					'label'       => __( 'ACF Fields', 'wp-mcp-toolkit' ),
					'description' => __( 'Abilities for managing Advanced Custom Fields data.', 'wp-mcp-toolkit' ),
				)
			);
		}
	}

	public function register_abilities(): void {
		require_once __DIR__ . '/class-ability-registrar.php';
		$registrar = new WP_MCP_Toolkit_Ability_Registrar();
		$registrar->register();

		// ACF module.
		if ( class_exists( 'ACF' ) && file_exists( __DIR__ . '/modules/acf/class-acf-module.php' ) ) {
			require_once __DIR__ . '/modules/acf/class-acf-module.php';
			WP_MCP_Toolkit_ACF_Module::init();
		}
	}

	public function register_admin_page(): void {
		if ( file_exists( __DIR__ . '/../admin/class-admin-page.php' ) ) {
			require_once __DIR__ . '/../admin/class-admin-page.php';
			$admin = new WP_MCP_Toolkit_Admin_Page();
			$admin->register();
		}
	}
}

WP_MCP_Toolkit::instance();
