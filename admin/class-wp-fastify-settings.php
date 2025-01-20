<?php
namespace WP_Fastify\Admin;

class WP_Fastify_Settings {
    public function register_settings() {
        // Move all register_setting() calls here
    }

    public function add_settings_page() {
        // Add WP Fastify to the main menu
        add_menu_page(
            'WP Fastify Settings',          // Page title
            'WP Fastify',                   // Menu title
            'manage_options',               // Capability
            'wp-fastify',                   // Menu slug
            [$this, 'render_settings_page'], // Callback function to display the settings page
            'dashicons-performance',        // Icon for the menu (dashicons-performance fits the optimization theme)
            50                              // Position in the admin menu
        );
    }

    public function render_settings_page() {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'caching';
        require_once plugin_dir_path(__FILE__) . 'views/settings-page.php';
    }
}