<?php

namespace WP_Fastify;

class WP_Fastify {

    public function __construct() {
        $this->load_dependencies();
        $this->call_hooks();
    }

    public function call_hooks() {
        // Minify CSS and JS files
        add_filter('script_loader_src', [ 'WP_Fastify\WP_Fastify_Minifier', 'minify_assets' ], 10, 2);
        add_filter('style_loader_src', [ 'WP_Fastify\WP_Fastify_Minifier', 'minify_assets' ], 10, 2);

        // Add the HTML Minification functionality
        add_action('template_redirect', [ $this, 'start_html_minification' ]);
        add_action('shutdown', [ $this, 'end_html_minification' ], 0);
    }

    // Load dependencies (e.g., Cache and Minifier classes)
    public function load_dependencies() {
        require_once plugin_dir_path(__FILE__) . '../classes/class-wp-fastify-cache.php';
        require_once plugin_dir_path(__FILE__) . '../classes/class-wp-fastify-minifier.php';
    }

    // Initialize the caching functionality
    public function run() {
        $enable_cache = get_option('wp_fastify_caching_enable_cache', 0);

        if ($enable_cache) {
            add_action('template_redirect', [ $this, 'start_caching' ]);
            add_filter('shutdown', [ $this, 'end_caching' ]);
        }

        add_action('save_post', [ WP_Fastify_Cache::class, 'clear_cache' ]);
    }

    // Serve the cached page
    public function serve_cache() {
        WP_Fastify_Cache::serve_cache();
    }

    // Start caching the output
    public function start_caching() {
        ob_start([ WP_Fastify_Cache::class, 'save_cache' ]);
        WP_Fastify_Cache::serve_cache();
    }

    // End caching and flush output
    public function end_caching() {
        if (ob_get_length()) {
            ob_end_flush();
        }
    }

    // Start HTML Minification
    public function start_html_minification() {
        $enable_html_minification = get_option('wp_fastify_asset_optimization_enable_html_minification', 0);
        error_log(wp_json_encode("{$enable_html_minification}"));


        // Apply HTML minification only if enabled and not on admin pages
        if ($enable_html_minification && !is_admin() && $_SERVER['REQUEST_METHOD'] === 'GET') {
            ob_start(function ($buffer) {
                return WP_Fastify_Minifier::minify_html($buffer);
            });
        }
    }

    // End HTML Minification and flush the output
    public function end_html_minification() {
        if (ob_get_length()) {
            ob_end_flush();
        }
    }
}
