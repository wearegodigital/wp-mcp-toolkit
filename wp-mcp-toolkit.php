<?php
/**
 * WP MCP Toolkit
 *
 * @package     wp-mcp-toolkit
 * @author      Sean Wilkinson
 * @copyright   2026 Sean Wilkinson
 * @license     GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       WP MCP Toolkit
 * Plugin URI:        https://github.com/wearegodigital/wp-mcp-toolkit
 * Description:       Comprehensive MCP toolkit for WordPress — content CRUD, block editing, media, taxonomies, ACF, Gravity Forms, Yoast SEO, and content templates. Built on the official MCP Adapter.
 * Requires at least: 6.9
 * Version:           0.5.0
 * Requires PHP:      8.1
 * Author:            Sean Wilkinson
 * Author URI:        https://seanwilkinson.com
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain:       wp-mcp-toolkit
 */

declare (strict_types = 1);

namespace WP\MCP\Toolkit;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit();

/**
 * Define the plugin constants.
 */
function constants(): void {
	define( 'WP_MCP_DIR', plugin_dir_path( __FILE__ ) );
	define( 'WP_MCP_VERSION', '0.5.0' );
}

constants();

add_action( 'init', static function () {
	load_plugin_textdomain( 'wp-mcp-toolkit', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

// Load Jetpack Autoloader (handles version conflicts across plugins).
$autoloader = __DIR__ . '/vendor/autoload_packages.php';
if ( file_exists( $autoloader ) ) {
	require_once $autoloader;
} elseif ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
} else {
	add_action( 'admin_notices', static function () {
		echo '<div class="notice notice-error"><p>';
		echo esc_html__( 'WP MCP Toolkit: Composer dependencies are missing. Please run "composer install" in the plugin directory.', 'wp-mcp-toolkit' );
		echo '</p></div>';
	} );
	return;
}

// Initialize the upstream MCP Adapter (from Composer package).
if ( class_exists( \WP\MCP\Plugin::class ) ) {
	\WP\MCP\Plugin::instance();
}

// Load the WP MCP Toolkit extensions.
require_once __DIR__ . '/includes/class-plugin.php';
