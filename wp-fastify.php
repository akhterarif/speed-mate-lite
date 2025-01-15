<?php
/**
 * Plugin Name: WP Fastify
 * Description: A WordPress performance optimization plugin.
 * Version: 1.0.0
 * Author: Arif
 * License: GPLv2 or later
 */

defined( 'ABSPATH' ) || exit;

require_once plugin_dir_path(__FILE__) . 'includes/classes/class-wp-fastify-admin.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/classes/class-wp-fastify.php';


if (is_admin()) {
    $admin = new WP_Fastify\WP_Fastify_Admin();
}


$plugin = new WP_Fastify\WP_Fastify();
$plugin->run();


