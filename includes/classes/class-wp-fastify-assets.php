<?php

namespace WP_Fastify;

class WP_Fastify_Assets {

    public static function enqueue_scripts() {
        // Enqueue CSS
        wp_enqueue_style(
            'wp-fastify-style',
            plugin_dir_url(__DIR__) . 'assets/css/wp-fastify.css',
            [],
            '1.0.0'
        );

        // Enqueue JS
        wp_enqueue_script(
            'wp-fastify-script',
            plugin_dir_url(__DIR__) . 'assets/js/wp-fastify.js',
            ['jquery'],
            '1.0.0',
            true
        );
    }

    public static function enqueue_admin_scripts() {
        // Enqueue CSS
        wp_enqueue_style(
            'wp-fastify-admin-style',
            plugin_dir_url(__DIR__) . 'assets/admin/css/wp-fastify.css',
            [],
            '1.0.0'
        );

        // Enqueue JS
        wp_enqueue_script(
            'wp-fastify-admin-script',
            plugin_dir_url(__DIR__) . 'assets/admin/js/wp-fastify.js',
            ['jquery'],
            '1.0.0',
            true
        );


        // Pass AJAX URL and nonce to JavaScript
        wp_localize_script('wp-fastify-admin-script', 'wpFastifyAjax', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('wp_fastify_trash_spam_cleanup_nonce'),
        ]);
    }
}
