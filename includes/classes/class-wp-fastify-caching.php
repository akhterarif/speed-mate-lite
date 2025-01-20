<?php
namespace WP_Fastify\Includes;

class WP_Fastify_Caching {
    public function __construct() {
        $this->register_hooks();
    }

    public function register_hooks() {
        add_action('admin_init', [$this, 'update_htaccess_based_on_setting']);
    }

    public function update_htaccess_based_on_setting() {
        $enable_static_caching = get_option('wp_fastify_caching_enable_static_caching');
        $cache_duration = absint(get_option('wp_fastify_caching_cache_duration', 31536000)); // Default to 1 year
        $htaccess_file = ABSPATH . '.htaccess';

        if (!is_writable($htaccess_file)) {
            return; // Skip if not writable
        }

        $custom_rules = <<<HTACCESS
# WPFastify Static Asset Caching
<IfModule mod_headers.c>
<FilesMatch "\.(css|js|jpg|jpeg|png|gif|webp|svg|ico|woff|woff2|ttf|otf|eot|mp4)$">
    Header set Cache-Control "max-age={$cache_duration}, public"
</FilesMatch>
</IfModule>
# End WPFastify Static Asset Caching
HTACCESS;

        $htaccess_content = file_get_contents($htaccess_file);

        if ($enable_static_caching) {
            if (strpos($htaccess_content, '# WPFastify Static Asset Caching') === false) {
                // Add rules if not present
                $htaccess_content .= "\n" . $custom_rules . "\n";
                file_put_contents($htaccess_file, $htaccess_content);
            }
        } else {
            // Remove rules if present
            $htaccess_content = preg_replace('/# WPFastify Static Asset Caching.*?# End WPFastify Static Asset Caching/s', '', $htaccess_content);
            file_put_contents($htaccess_file, $htaccess_content);
        }
    }

    public function set_cache_headers() {
        $enable_header_caching = get_option('wp_fastify_caching_enable_header_caching', 0);
        if ($enable_header_caching) {
            $cache_duration = absint(get_option('wp_fastify_caching_cache_duration', 31536000)); // Default to 1 year
            header('Cache-Control: public, max-age=' . $cache_duration);
            header('Pragma: public');
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cache_duration) . ' GMT');
        }
    }

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