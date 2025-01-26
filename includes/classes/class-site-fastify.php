<?php

namespace Site_Fastify\Includes;

use Site_Fastify\Includes\Site_Fastify_Asset_Optimizer;
use Site_Fastify\Includes\Site_Fastify_Caching;

class Site_Fastify {

    public function __construct() {
        $this->load_dependencies();
        $this->register_hooks();
    }

    public function register_hooks() {
        
        // Minify CSS and JS files
        add_filter('script_loader_src', [ 'Site_Fastify\Includes\Site_Fastify_Asset_Optimizer', 'minify_assets' ], 10, 2);
        add_filter('style_loader_src', [ 'Site_Fastify\Includes\Site_Fastify_Asset_Optimizer', 'minify_assets' ], 10, 2);

        // Add the HTML Minification functionality
        add_action('template_redirect', [ $this, 'start_html_minification' ]);
        add_action('shutdown', [ $this, 'end_html_minification' ], 0);

        // Add Lazy Loading for Images
        add_filter('the_content', [ $this, 'apply_lazy_loading_to_images' ]);
    }

    // Load dependencies (e.g., Cache and Minifier classes)
    public function load_dependencies() {
        require_once plugin_dir_path(__FILE__) . '../classes/class-site-fastify-caching.php';
        require_once plugin_dir_path(__FILE__) . '../classes/class-site-fastify-asset-optimizer.php';
    }

    // Initialize the caching functionality
    public function run() {
        $enable_cache = get_option('wp_fastify_caching_enable_cache', 0);

        if ($enable_cache) {
            add_action('template_redirect', [ $this, 'start_caching' ]);
            add_filter('shutdown', [ $this, 'end_caching' ]);
        }

        add_action('save_post', [ Site_Fastify_Caching::class, 'clear_cache' ]);
    }

    // Serve the cached page
    public function serve_cache() {
        Site_Fastify_Caching::serve_cache();
    }

    // Start caching the output
    public function start_caching() {
        ob_start([ Site_Fastify_Caching::class, 'save_cache' ]);
        Site_Fastify_Caching::serve_cache();
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

        // Apply HTML minification only if enabled and not on admin pages
        if ($enable_html_minification && !is_admin() && $_SERVER['REQUEST_METHOD'] === 'GET') {
            ob_start(function ($buffer) {
                return Site_Fastify_Asset_Optimizer::minify_html($buffer);
            });
        }
    }

    // End HTML Minification and flush the output
    public function end_html_minification() {
        if (ob_get_length()) {
            ob_end_flush();
        }
    }


    // Enables lazy loading for images 
    public function apply_lazy_loading_to_images($content) {
        $enable_lazy_loading = get_option('wp_fastify_asset_optimization_enable_html_minification', 0);

        // Apply lazy loading only if the setting is enabled
        if ($enable_lazy_loading) {
            $content = preg_replace_callback(
                '/<img\s+[^>]*>/i',
                function ($matches) {
                    $img = $matches[0];

                    // Skip if the image already has a loading attribute
                    if (strpos($img, 'loading=') !== false) {
                        return $img;
                    }

                    // Add the lazy loading attribute
                    $img = preg_replace('/<img\s+/i', '<img loading="lazy" ', $img);
                    return $img;
                },
                $content
            );
        }

        return $content;
    }
}
