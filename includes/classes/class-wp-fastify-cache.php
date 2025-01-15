<?php 

namespace WP_Fastify;

class WP_Fastify_Cache {

    // Directory to store cached files
    public static function get_cache_dir() {
        $upload_dir = wp_upload_dir();
        return trailingslashit($upload_dir['basedir']) . 'wp-fastify-cache/';
    }

    public static function serve_cache() {
        $enable_cache = get_option('wp_fastify_caching_enable_cache', 0);

        // Serve cache only if caching is enabled
        if ($enable_cache && !is_user_logged_in() && !is_admin() && $_SERVER['REQUEST_METHOD'] === 'GET') {
            $cache_file = self::get_cache_file();
            if (file_exists($cache_file)) {
                error_log("Cache hit: Serving cached file from $cache_file");
                readfile($cache_file);
                exit;
            } else {
                error_log("Cache miss: No cache found for " . $_SERVER['REQUEST_URI']);
            }
        }
    }

    // Get the cache file path for the current page
    public static function get_cache_file() {
        $cache_dir = self::get_cache_dir();
        if (!file_exists($cache_dir)) {
            mkdir($cache_dir, 0777, true); // Create directory if it doesn't exist
        }

        $cache_key = md5($_SERVER['REQUEST_URI']);
        return $cache_dir . $cache_key . '.html';
    }

    // Save the output to the cache
    public static function save_cache($output) {
        $enable_cache = get_option('wp_fastify_caching_enable_cache', 0);

        // Save to cache only if caching is enabled
        if ($enable_cache && !is_user_logged_in() && !is_admin() && $_SERVER['REQUEST_METHOD'] === 'GET') {
            $cache_file = self::get_cache_file();
            file_put_contents($cache_file, $output);
        }

        return $output;
    }

    // Clear cache
    public static function clear_cache() {
        $cache_dir = self::get_cache_dir();
        if (file_exists($cache_dir)) {
            $files = glob($cache_dir . '*'); // Get all cache files
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file); // Delete each file
                }
            }
        }
    }
}
