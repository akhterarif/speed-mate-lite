<?php

namespace Site_Fastify\Includes;

class Site_Fastify_Assets {

    public static function enqueue_scripts() {
        // Enqueue CSS
        wp_enqueue_style(
            'site-fastify-style',
            plugin_dir_url(__DIR__) . 'assets/css/site-fastify.css',
            [],
            '1.0.0'
        );

        // Enqueue JS
        wp_enqueue_script(
            'site-fastify-script',
            plugin_dir_url(__DIR__) . 'assets/js/site-fastify.js',
            ['jquery'],
            '1.0.0',
            true
        );
    }

    public static function enqueue_admin_scripts($hook) {
        // Enqueue CSS
        wp_enqueue_style(
            'site-fastify-admin-style',
            plugin_dir_url(__DIR__) . 'assets/admin/css/site-fastify.css',
            [],
            '1.0.0'
        );

        // Enqueue JS
        wp_enqueue_script(
            'site-fastify-admin-script',
            plugin_dir_url(__DIR__) . 'assets/admin/js/site-fastify.js',
            ['jquery'],
            '1.0.0',
            true
        );

    }
}
