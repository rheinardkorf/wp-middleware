<?php
/**
 * Instantiates the WP Middleware plugin
 *
 * @package WPMiddleware
 */

namespace WPMiddleware;

global $wp_middleware_plugin;

require_once __DIR__ . '/php/class-plugin-base.php';
require_once __DIR__ . '/php/class-plugin.php';

$wp_middleware_plugin = new Plugin();

add_action( 'after_setup_theme', array( $wp_middleware_plugin, 'init' ), 9 );

/**
 * WP Middleware Plugin Instance
 *
 * @return Plugin
 */
function get_plugin_instance() {
	global $wp_middleware_plugin;
	return $wp_middleware_plugin;
}
