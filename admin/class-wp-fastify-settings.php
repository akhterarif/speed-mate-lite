<?php
namespace WP_Fastify\Admin;

class WP_Fastify_Settings {
    public function register_settings() {
        // Move all register_setting() calls here
    }

    public function add_settings_page() {
        add_options_page(
            'WP Fastify Settings',
            'WP Fastify',
            'manage_options',
            'wp-fastify',
            [$this, 'render_settings_page']
        );
    }

    public function render_settings_page() {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'caching';
        require_once plugin_dir_path(__FILE__) . 'views/settings-page.php';
    }

    

}