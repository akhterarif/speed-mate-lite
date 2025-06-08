<?php
namespace Speed_Mate\Includes;

/**
 * Class Speed_Mate_Caching
 *
 * Handles caching functionality for Speed Mate plugin.
 */
class Speed_Mate_Caching {

    /**
     * Constructor to initialize caching functionality.
     */
    public function __construct() {
        $this->register_hooks();
    }

    /**
     * Register necessary hooks for caching.
     */
    public function register_hooks() {
        add_action('admin_init', [$this, 'update_htaccess_based_on_setting']);
        add_action('template_redirect', [$this, 'start_buffering'], 1);
        add_action('shutdown', [$this, 'save_cache'], 9999);
        add_action('template_redirect', [$this, 'serve_cache'], 0);
    }

    public function start_buffering() {
        $enable_cache = get_option('speed_mate_caching_enable_cache', 0);
        if ($enable_cache && !is_user_logged_in() && !is_admin() && $_SERVER['REQUEST_METHOD'] === 'GET') {
            ob_start();
        }
    }

    /**
     * Update the .htaccess file based on plugin settings.
     */
    public function update_htaccess_based_on_setting() {
        global $wp_filesystem;


        if (empty($wp_filesystem)) {
            require_once ABSPATH . 'wp-admin/includes/file.php';

            $creds = request_filesystem_credentials('', '', false, false, null);
            
            if (!$creds) {
                return $src; // Exit if credentials are not available
            }

            if (!WP_Filesystem($creds)) {
                return $src; // Exit if WP_Filesystem fails
            }
        }

        $enable_static_caching = get_option('speed_mate_caching_enable_static_caching');
        $cache_duration = absint(get_option('speed_mate_caching_cache_duration', 31536000)); // Default to 1 year
        $htaccess_file = ABSPATH . '.htaccess';

        // Check if the .htaccess file is writable
        if (!$wp_filesystem->is_writable($htaccess_file)) {
            return;
        }

        $custom_rules = "# SpeedMate Static Asset Caching\n";
        $custom_rules .= "<IfModule mod_headers.c>\n";
        $custom_rules .= "<FilesMatch \"\\.(css|js|jpg|jpeg|png|gif|webp|svg|ico|woff|woff2|ttf|otf|eot|mp4)$\">\n";
        $custom_rules .= "    Header set Cache-Control \"max-age={$cache_duration}, public\"\n";
        $custom_rules .= "</FilesMatch>\n";
        $custom_rules .= "</IfModule>\n";
        $custom_rules .= "# End SpeedMate Static Asset Caching\n";


        // Read current .htaccess content
        $htaccess_content = $wp_filesystem->get_contents($htaccess_file);

        if ($enable_static_caching) {
            if (strpos($htaccess_content, '# SpeedMate Static Asset Caching') === false) {
                // Add rules if not already present
                $htaccess_content .= "\n" . $custom_rules . "\n";
                $wp_filesystem->put_contents($htaccess_file, $htaccess_content);
            }
        } else {
            // Remove rules if present
            $htaccess_content = preg_replace('/# SpeedMate Static Asset Caching.*?# End SpeedMate Static Asset Caching/s', '', $htaccess_content);
            $wp_filesystem->put_contents($htaccess_file, $htaccess_content);
        }
    }

    /**
     * Set cache headers based on plugin settings.
     */
    public function set_cache_headers() {
        $enable_header_caching = get_option('speed_mate_caching_enable_header_caching', 0);
        if ($enable_header_caching) {
            $cache_duration = absint(get_option('speed_mate_caching_cache_duration', 31536000)); // Default to 1 year
            header('Cache-Control: public, max-age=' . $cache_duration);
            header('Pragma: public');
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cache_duration) . ' GMT');
        }
    }

    /**
     * Get the directory for storing cached files.
     *
     * @return string Cache directory path.
     */
    public static function get_cache_dir() {
        $upload_dir = wp_upload_dir();
        $cache_dir = trailingslashit($upload_dir['basedir']) . 'speed-mate-cache/';

        if (!file_exists($cache_dir)) {
            wp_mkdir_p($cache_dir);
        }

        return $cache_dir;
    }

    /**
     * Serve cached content if available.
     */
    public static function serve_cache() {
        $enable_cache = get_option('speed_mate_caching_enable_cache', 0);
        if (!$enable_cache || is_user_logged_in() || is_admin() || $_SERVER['REQUEST_METHOD'] !== 'GET') {
            return;
        }
    
        $request_uri = esc_url_raw($_SERVER['REQUEST_URI']);
        $cache_file = self::get_cache_file($request_uri);
    
        // Initialize WP_Filesystem
        global $wp_filesystem;

        if (!$wp_filesystem || !is_object($wp_filesystem)) {
            return;
        }

        if (empty($wp_filesystem)) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }
    
        if ($wp_filesystem->exists($cache_file)) {
            header('Cache-Control: public, max-age=3600');
            header('Content-Type: text/html; charset=UTF-8');
    
            // Read and output file using WP_Filesystem
            $cache_content = $wp_filesystem->get_contents($cache_file);
            if ($cache_content !== false) {
                echo $cache_content;
            } else {
                error_log("Speed-Mate: Failed to read cache file $cache_file");
            }
            exit;
        }
    }

    /**
     * Get the cache file path for the current page.
     *
     * @return string Cache file path.
     */
    public static function get_cache_file($request_uri) {
        // Get the cache directory path
        $cache_dir = self::get_cache_dir();
    
        // Use WP_Filesystem to ensure the cache directory exists
        global $wp_filesystem;
    
        // Initialize WP_Filesystem if not already initialized
        if (empty($wp_filesystem)) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
    
            // Request filesystem credentials
            $creds = request_filesystem_credentials('', '', false, false, null);
    
            if (!$creds || !WP_Filesystem($creds)) {
                // If credentials are not available or initialization fails, return an empty string
                return '';
            }
        }
    
        // Check if the cache directory exists; create it if necessary
        if (!$wp_filesystem->is_dir($cache_dir)) {
            if (!$wp_filesystem->mkdir($cache_dir, FS_CHMOD_DIR)) {
                // If directory creation fails, return an empty string
                return '';
            }
        }
    
        // Create a sanitized cache key based on the request URI
        $cache_key = md5(wp_unslash($request_uri)); // Unscrub the URL for safety
    
        // Return the full path to the cache file
        return trailingslashit($cache_dir) . $cache_key . '.html';
    }
    

    /**
     * Save the page output to the cache.
     *
     * @param string $output The output content to be cached.
     * @return string The cached content.
     */
    public static function save_cache($output) {
        $enable_cache = get_option('speed_mate_caching_enable_cache', 0);

        // Save to cache only if caching is enabled
        if ($enable_cache && !is_user_logged_in() && !is_admin() && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'GET' && isset($_SERVER['REQUEST_URI'])) {
            // Sanitize the REQUEST_URI to ensure it's safe
            $request_uri = esc_url_raw($_SERVER['REQUEST_URI']);
            $cache_file = self::get_cache_file($request_uri);

            // Use WP_Filesystem to save the cache file
            global $wp_filesystem;


            if (empty($wp_filesystem)) {
                require_once ABSPATH . 'wp-admin/includes/file.php';

                $creds = request_filesystem_credentials('', '', false, false, null);
                
                if (!$creds) {
                    return $src; // Exit if credentials are not available
                }

                if (!WP_Filesystem($creds)) {
                    return $src; // Exit if WP_Filesystem fails
                }
            }

            $wp_filesystem->put_contents($cache_file, $output);
        }

        return $output;
    }

    /**
     * Clear the cached files.
     */
    public static function clear_cache() {
        $cache_dir = self::get_cache_dir();

        // Use WP_Filesystem to remove cache files
        global $wp_filesystem;


        if (empty($wp_filesystem)) {
            require_once ABSPATH . 'wp-admin/includes/file.php';

            $creds = request_filesystem_credentials('', '', false, false, null);
            
            if (!$creds) {
                return $src; // Exit if credentials are not available
            }

            if (!WP_Filesystem($creds)) {
                return $src; // Exit if WP_Filesystem fails
            }
        }

        if ($wp_filesystem->is_dir($cache_dir)) {
            $files = $wp_filesystem->dirlist($cache_dir); // Get all cache files
            foreach ($files as $file => $file_info) {
                if ($file_info['type'] === 'file') {
                    $wp_filesystem->delete($cache_dir . $file); // Use WP_Filesystem to delete each file
                }
            }
        }
    }
}
