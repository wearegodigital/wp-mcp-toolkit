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
 * Description:       Comprehensive MCP toolkit for WordPress — content CRUD, block editing, media, taxonomies, ACF, Gravity Forms, Yoast SEO, and content templates. Fork of the official MCP Adapter.
 * Requires at least: 6.9
 * Version:           0.4.0
 * Requires PHP:      7.4
 * Author:            Sean Wilkinson
 * Author URI:        https://seanwilkinson.com
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain:       wp-mcp-toolkit
 */

declare (strict_types = 1);

namespace WP\MCP;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit();

/**
 * Define the plugin constants.
 */
function constants(): void {
	define( 'WP_MCP_DIR', plugin_dir_path( __FILE__ ) );
	define( 'WP_MCP_VERSION', '0.4.0' );
}

constants();

add_action( 'init', static function () {
	load_plugin_textdomain( 'wp-mcp-toolkit', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

require_once __DIR__ . '/includes/Autoloader.php';

// If autoloader failed, we cannot proceed.
if ( ! Autoloader::autoload() ) {
	return;
}

// Load the upstream MCP Adapter.
if ( class_exists( Plugin::class ) ) {
	Plugin::instance();
}

// Load the WP MCP Toolkit extensions.
require_once __DIR__ . '/includes/class-plugin.php';
