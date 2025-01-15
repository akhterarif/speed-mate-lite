<?php

namespace WP_Fastify;

class WP_Fastify {
    public function __construct() {
        $this->load_dependencies();
    }

    // Load dependencies (e.g., the cache class)
    public function load_dependencies() {
        require_once plugin_dir_path( __FILE__ ) . '../classes/class-wp-fastify-cache.php';
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
}
