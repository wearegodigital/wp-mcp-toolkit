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
		$this->load_addon_registry();
		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_categories' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
		add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
		add_action( 'admin_init', array( $this, 'register_ajax_handlers' ) );
	}

	private function load_addon_registry(): void {
		require_once __DIR__ . '/interface-addon.php';
		require_once __DIR__ . '/class-addon-registry.php';

		$registry = WP_MCP_Toolkit_Addon_Registry::instance();

		// Register built-in add-ons.
		require_once __DIR__ . '/modules/acf/class-acf-addon.php';
		$registry->register( new WP_MCP_Toolkit_ACF_Addon() );

		require_once __DIR__ . '/modules/gravity-forms/class-gf-addon.php';
		$registry->register( new WP_MCP_Toolkit_GF_Addon() );

		require_once __DIR__ . '/modules/yoast/class-yoast-addon.php';
		$registry->register( new WP_MCP_Toolkit_Yoast_Addon() );

		/**
		 * Allow third-party plugins to register their own add-ons.
		 *
		 * @param WP_MCP_Toolkit_Addon_Registry $registry The add-on registry instance.
		 */
		do_action( 'wpmcp_register_addons', $registry );
	}

	public function register_categories(): void {
		// Core categories (always available).
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

		// Add-on categories.
		$registry = WP_MCP_Toolkit_Addon_Registry::instance();
		foreach ( $registry->get_active() as $addon ) {
			$addon->register_categories();
		}

		// Templates category (always available).
		wp_register_ability_category( 'wpmcp-templates', array(
			'label'       => __( 'Content Templates', 'wp-mcp-toolkit' ),
			'description' => __( 'Abilities for managing content templates and creating posts from templates.', 'wp-mcp-toolkit' ),
		) );
	}

	public function register_abilities(): void {
		// Core abilities.
		require_once __DIR__ . '/class-ability-registrar.php';
		( new WP_MCP_Toolkit_Ability_Registrar() )->register();

		// Add-on abilities.
		$disabled = get_option( 'wpmcp_disabled_abilities', array() );
		$registry = WP_MCP_Toolkit_Addon_Registry::instance();
		foreach ( $registry->get_active() as $addon ) {
			$addon->register_abilities( $disabled );
		}

		// Content Templates (always available).
		require_once __DIR__ . '/Abilities/class-template-abilities.php';
		( new WP_MCP_Toolkit_Template_Abilities() )->register( $disabled );
	}

	public function register_admin_page(): void {
		require_once __DIR__ . '/../admin/class-admin-page.php';
		( new WP_MCP_Toolkit_Admin_Page() )->register();
	}

	public function register_ajax_handlers(): void {
		if ( ! wp_doing_ajax() ) {
			return;
		}
		require_once __DIR__ . '/../admin/class-admin-page.php';
		( new WP_MCP_Toolkit_Admin_Page() )->register_ajax_handlers();
	}
}

WP_MCP_Toolkit::instance();
