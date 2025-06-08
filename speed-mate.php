<?php
/**
 * Plugin Name: Speed-Mate
 * Description: A WordPress performance optimization plugin.
 * Version: 1.0.0
 * Author: Arif
 * License: GPLv2 or later
 */




defined( 'ABSPATH' ) || exit;

if ( ! defined( 'SPEED_MATE_PLUGIN_DIR' ) ) {
    define( 'SPEED_MATE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

require_once SPEED_MATE_PLUGIN_DIR . 'vendor/autoload.php';

require_once plugin_dir_path(__FILE__) . 'admin/class-speed-mate-admin.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/classes/class-speed-mate.php';
require_once plugin_dir_path(__FILE__) . 'includes/classes/class-speed-mate-assets.php';



// Hook to enqueue scripts and styles
add_action('wp_enqueue_scripts', ['Speed_Mate\Includes\Speed_Mate_Assets', 'enqueue_scripts']);
add_action('admin_enqueue_scripts', ['Speed_Mate\Includes\Speed_Mate_Assets', 'enqueue_admin_scripts'], 10, 1);


if (is_admin()) {
    $admin = new Speed_Mate\Admin\Speed_Mate_Admin();
}

$plugin = new Speed_Mate\Includes\Speed_Mate();
$plugin->run();


