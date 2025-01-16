<?php

namespace WP_Fastify;

class WP_Fastify_Minifier {

    /**
     * Minify CSS and JS assets.
     *
     * @param string $src The asset URL.
     * @param string $handle The handle of the asset.
     * @return string Minified asset URL or original URL if minification isn't applied.
     */
    public static function minify_assets($src, $handle) {
        $enable_minification = get_option('wp_fastify_asset_optimization_enable_minification', 0);

        // Proceed only if minification is enabled and the file is not already minified
        if ($enable_minification && strpos($src, '.min.') === false) {
            // Parse the file URL to remove query strings and get the path
            $parsed_url = parse_url($src);
            $file_path = isset($parsed_url['path']) ? ABSPATH . ltrim($parsed_url['path'], '/') : '';
            
            // Normalize the file path to avoid extra slashes
            $file_path = preg_replace('#/+#', '/', $file_path);

            // Log for debugging purposes
            error_log(wp_json_encode("Processing file path: {$file_path}"));

            // Check if the file exists
            if (file_exists($file_path)) {
                $content = file_get_contents($file_path);
                $ext = pathinfo($file_path, PATHINFO_EXTENSION);

                // Only process CSS and JS files
                if (in_array($ext, ['css', 'js'])) {
                    // Minify the content
                    $minified_content = self::minify_content($content, $ext);

                    // Generate the path for the minified file
                    $minified_path = preg_replace('/\.' . $ext . '$/', '.min.' . $ext, $file_path);

                    // Save the minified content to the new file
                    file_put_contents($minified_path, $minified_content);

                    // Log the creation of the minified file
                    error_log(wp_json_encode("Minified file created: {$minified_path}"));

                    // Return the URL of the minified file
                    return str_replace(ABSPATH, site_url('/'), $minified_path);
                }
            } else {
                error_log(wp_json_encode("File does not exist: {$file_path}"));
            }
        }

        // Return the original source URL if minification is not applicable
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
}
