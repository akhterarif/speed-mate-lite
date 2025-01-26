<?php
namespace Site_Fastify\Includes;

class Site_Fastify_Asset_Optimizer {
    private $exclusions;

    public function __construct() {
        $this->exclusions = get_option('wp_fastify_asset_optimization_exclusions', '');
        $this->register_hooks();
    }

    public function register_hooks() {
        add_filter('script_loader_src', [$this, 'minify_assets'], 10, 2);
        add_filter('style_loader_src', [$this, 'minify_assets'], 10, 2);
    }

    /**
     * Minify CSS and JS assets.
     *
     * @param string $src The asset URL.
     * @param string $handle The handle of the asset.
     * @return string Minified asset URL or original URL if minification isn't applied.
     */
    public static function minify_assets($src, $handle) {
        $enable_minification = get_option('wp_fastify_asset_optimization_enable_minification', 0);
        $exclusions = array_filter(array_map('trim', explode("\n", get_option('wp_fastify_asset_optimization_exclusions', ''))));

        // Skip minification for admin pages or excluded handles
        if (is_admin() || in_array($handle, ['wp-edit-post', 'wp-block-editor', 'wp-blocks', 'wp-components'])) {
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
            $parsed_url = parse_url($src);
            $file_path = isset($parsed_url['path']) ? ABSPATH . ltrim($parsed_url['path'], '/') : '';
            $file_path = preg_replace('#/+#', '/', $file_path);

            if (file_exists($file_path)) {
                $content = file_get_contents($file_path);
                $ext = pathinfo($file_path, PATHINFO_EXTENSION);

                if (in_array($ext, ['css', 'js'])) {
                    $minified_content = Site_Fastify_Asset_Optimizer::minify_content($content, $ext);
                    $minified_path = preg_replace('/\.' . $ext . '$/', '.min.' . $ext, $file_path);

                    file_put_contents($minified_path, $minified_content);

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
            // For basic JS minification, remove comments and extra whitespace
            return preg_replace(['/\s+/', '/\/\/[^\n]*\n/', '/\/\*.*?\*\//s'], [' ', '', ''], $content);
        }

        // Return unmodified content if the type is unsupported
        return $content;
    }


    public static function minify_html($html) {
        // Remove comments (excluding conditional comments for IE)
        $html = preg_replace('/<!--(?!\[if).*?-->/', '', $html);
        // Remove whitespace between tags
        $html = preg_replace('/>\s+</', '><', $html);
        // Remove unnecessary whitespace
        $html = preg_replace('/\s+/', ' ', $html);
        return $html;
    }
}