<?php
/**
 * Plugin Name: Site-Fastify
 * Description: A WordPress performance optimization plugin.
 * Version: 1.0.0
 * Author: Arif
 * License: GPLv2 or later
 */

use Site_Fastify\Admin\Site_Fastify_Admin;

defined( 'ABSPATH' ) || exit;

require_once plugin_dir_path( __FILE__ ) . 'includes/classes/class-site-fastify.php';
require_once plugin_dir_path(__FILE__) . 'includes/classes/class-site-fastify-assets.php';
require_once plugin_dir_path(__FILE__) . 'admin/class-site-fastify-admin.php';

// Hook to enqueue scripts and styles
add_action('wp_enqueue_scripts', ['Site_Fastify\Includes\Site_Fastify_Assets', 'enqueue_scripts']);
add_action('admin_enqueue_scripts', ['Site_Fastify\Includes\Site_Fastify_Assets', 'enqueue_admin_scripts'], 10, 1);


if (is_admin()) {
    $admin = new Site_Fastify_Admin;
}


$plugin = new Site_Fastify\Includes\Site_Fastify();
$plugin->run();


