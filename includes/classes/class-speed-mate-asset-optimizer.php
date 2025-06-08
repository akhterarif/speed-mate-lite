<?php
namespace Speed_Mate\Includes;

class Speed_Mate_Asset_Optimizer {
    private $exclusions;

    public function __construct() {
        $this->exclusions = get_option('speed_mate_asset_optimization_exclusions', '');
        $this->register_hooks();
    }

    public function register_hooks() {
        add_filter('style_loader_src', [$this, 'minify_assets'], 10, 2);
        add_filter('script_loader_src', [$this, 'minify_assets'], 10, 2);
        
    }

    /**
     * Minify CSS and JS assets.
     *
     * @param string $src The asset URL.
     * @param string $handle The handle of the asset.
     * @return string Minified asset URL or original URL if minification isn't applied.
     */
    public static function minify_assets($src, $handle) {
        $enable_minification = get_option('speed_mate_asset_optimization_enable_minification', 0);
        $exclusions = array_filter(array_map('trim', explode("\n", get_option('speed_mate_asset_optimization_exclusions', ''))));

        // Skip minification for admin pages or excluded handles
        if (boolval(is_admin()) || in_array($handle, ['wp-edit-post', 'wp-block-editor', 'wp-blocks', 'wp-components'])) {
            return $src;
        }

        // Skip minification for excluded files
        foreach ($exclusions as $exclusion) {
            // Escape special characters and replace wildcards with regex patterns
            $exclusion_pattern = str_replace('*', '.*', preg_quote($exclusion, '/'));
            // Allow optional query strings at the end
            $exclusion_pattern .= '(\\?.*)?$';
            if (preg_match('/^' . $exclusion_pattern . '/', $src)) {
                return $src;
            }
        }

        if ($enable_minification && strpos($src, '.min.') === false) {
            $parsed_url = wp_parse_url($src); // Replaced parse_url with wp_parse_url
            $file_path = isset($parsed_url['path']) ? ABSPATH . ltrim($parsed_url['path'], '/') : '';
            $file_path = preg_replace('#/+#', '/', $file_path);
            
            
            if (file_exists($file_path)) {
                // Use WP_Filesystem to get the file content
                global $wp_filesystem;


                if (empty($wp_filesystem)) {
                    require_once ABSPATH . 'wp-admin/includes/file.php';

                    $creds = request_filesystem_credentials('', '', false, false, null);
                    
                    if (!$creds) {
                        error_log('Speed Mate: Filesystem credentials are not available');
                        return $src; // Exit if credentials are not available
                    }

                    if (!WP_Filesystem($creds)) {
                        return $src; // Exit if WP_Filesystem fails
                    }
                }

                $content = $wp_filesystem->get_contents($file_path); // Use WP_Filesystem
                $ext = pathinfo($file_path, PATHINFO_EXTENSION);
                
                if (in_array($ext, ['css', 'js'])) {
                    
                    $minified_content = Speed_Mate_Asset_Optimizer::minify_content($content, $ext);
                    $minified_path = preg_replace('/\.' . $ext . '$/', '.min.' . $ext, $file_path);

                    // Use WP_Filesystem to save the minified content
                    $wp_filesystem->put_contents($minified_path, $minified_content); // WP_Filesystem

                    return str_replace(ABSPATH, site_url('/'), $minified_path);
                }

                
            }
        }
        return $src;
    }

    /**
     * Minify the content of CSS or JS files.
     *
     * @param string $content The content of the file.
     * @param string $type The file type (css or js).
     * @return string Minified content.
     */
    public static function minify_content($content, $type) {
        if ($type === 'css') {
            // Remove comments, whitespace, and unnecessary characters in CSS
            return preg_replace(['/\s+/', '/\/\*.*?\*\//', '/;}/'], [' ', '', '}'], $content);
        } elseif ($type === 'js') {
            // Use a more robust minification approach for JavaScript
            // For example, using a library like JShrink or a similar tool
            return \JShrink\Minifier::minify($content, ['flaggedComments' => false]);
        }
        return $content;
    }
}