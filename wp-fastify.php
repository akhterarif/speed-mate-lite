<?php
/**
 * Plugin Name: WP Fastify
 * Description: A WordPress performance optimization plugin.
 * Version: 1.0.0
 * Author: Arif
 * License: GPLv2 or later
 */

use WP_Fastify\Admin\WP_Fastify_Admin;

defined( 'ABSPATH' ) || exit;

require_once plugin_dir_path( __FILE__ ) . 'includes/classes/class-wp-fastify.php';
require_once plugin_dir_path(__FILE__) . 'includes/classes/class-wp-fastify-assets.php';
require_once plugin_dir_path(__FILE__) . 'admin/class-wp-fastify-admin.php';

// Hook to enqueue scripts and styles
add_action('wp_enqueue_scripts', ['WP_Fastify\Includes\WP_Fastify_Assets', 'enqueue_scripts']);
add_action('admin_enqueue_scripts', ['WP_Fastify\Includes\WP_Fastify_Assets', 'enqueue_admin_scripts']);


if (is_admin()) {
    $admin = new WP_Fastify_Admin;
}


$plugin = new WP_Fastify\Includes\WP_Fastify();
$plugin->run();


