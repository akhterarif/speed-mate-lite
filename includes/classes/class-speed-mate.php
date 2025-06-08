<?php

namespace Speed_Mate\Includes;

use Speed_Mate\Includes\Speed_Mate_Asset_Optimizer;
use Speed_Mate\Includes\Speed_Mate_Caching;

class Speed_Mate {

    public function __construct() {
        $this->load_dependencies();
        $this->register_hooks();
    }

    // Load dependencies (e.g., Cache and Minifier classes)
    public function load_dependencies() {
        require_once plugin_dir_path(__FILE__) . '../classes/class-speed-mate-caching.php';
        require_once plugin_dir_path(__FILE__) . '../classes/class-speed-mate-asset-optimizer.php';
    }

    public function register_hooks() {
        // Minify CSS and JS files
        add_filter('script_loader_src', [ 'Speed_Mate\Includes\Speed_Mate_Asset_Optimizer', 'minify_assets' ], 10, 2);
        add_filter('style_loader_src', [ 'Speed_Mate\Includes\Speed_Mate_Asset_Optimizer', 'minify_assets' ], 10, 2);

        // Add Lazy Loading for Images
        add_filter('the_content', [ $this, 'apply_lazy_loading_to_images' ]);
    }

    

    // Initialize the caching functionality
    public function run() {
        $enable_cache = get_option('speed_mate_caching_enable_cache', 0);

        if ($enable_cache) {
            add_action('template_redirect', [ $this, 'start_caching' ]);
            add_filter('shutdown', [ $this, 'end_caching' ]);
        }

        add_action('save_post', [ Speed_Mate_Caching::class, 'clear_cache' ]);
    }

    // Serve the cached page
    public function serve_cache() {
        Speed_Mate_Caching::serve_cache();
    }

    // Start caching the output
    public function start_caching() {
        ob_start([ Speed_Mate_Caching::class, 'save_cache' ]);
        Speed_Mate_Caching::serve_cache();
    }

    // End caching and flush output
    public function end_caching() {
        if (ob_get_length()) {
            ob_end_flush();
        }
    }

    // Enables lazy loading for images 
    public function apply_lazy_loading_to_images($content) {
        $enable_lazy_loading = get_option('speed_mate_asset_optimization_enable_html_minification', 0);

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
