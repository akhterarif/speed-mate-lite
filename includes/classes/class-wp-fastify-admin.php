<?php

namespace WP_Fastify;

class WP_Fastify_Admin {

    public function __construct() {
        $this->load_dependencies();
        $this->call_hooks();
    }

    public function call_hooks() {
        add_action('admin_menu', [ $this, 'add_settings_page' ]);
        add_action('admin_init', [ $this, 'register_settings' ]);
        add_action('admin_init', [ $this, 'update_htaccess_based_on_setting' ]);
        // Loading the minified files in the site 
        add_filter('script_loader_src', [ 'WP_Fastify\WP_Fastify_Minifier', 'minify_assets' ], 10, 2);
        add_filter('style_loader_src', [ 'WP_Fastify\WP_Fastify_Minifier', 'minify_assets' ], 10, 2);
    }

    // Load dependencies (e.g., the minifier class)
    public function load_dependencies() {
        require_once plugin_dir_path( __FILE__ ) . '../classes/class-wp-fastify-minifier.php';
    }

    public function add_settings_page() {
        add_options_page(
            'WP Fastify Settings',
            'WP Fastify',
            'manage_options',
            'wp-fastify',
            [ $this, 'render_settings_page' ]
        );
    }

    public function register_settings() {
        // Caching settings
        register_setting('wp_fastify_caching_options', 'wp_fastify_caching_enable_cache');
        register_setting('wp_fastify_caching_options', 'wp_fastify_caching_cache_duration', [
            'default' => 31536000, // Default to 1 year
            'sanitize_callback' => 'absint',
        ]);
        register_setting('wp_fastify_caching_options', 'wp_fastify_caching_enable_static_caching');
        register_setting('wp_fastify_caching_options', 'wp_fastify_caching_enable_header_caching');

        // Asset Optimization settings
        register_setting('wp_fastify_asset_optimization_options', 'wp_fastify_asset_optimization_enable_minification');
        register_setting('wp_fastify_asset_optimization_options', 'wp_fastify_asset_optimization_enable_html_minification');
    }

    public function render_settings_page() {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'caching';
        ?>
        <div class="wrap">
            <h1>WP Fastify Settings</h1>
            <h2 class="nav-tab-wrapper">
                <a href="?page=wp-fastify&tab=caching" class="nav-tab <?php echo $active_tab === 'caching' ? 'nav-tab-active' : ''; ?>">
                    Caching
                </a>
                <a href="?page=wp-fastify&tab=asset_optimization" class="nav-tab <?php echo $active_tab === 'asset_optimization' ? 'nav-tab-active' : ''; ?>">
                    Asset Optimization
                </a>
            </h2>

            <form method="post" action="options.php">
                <?php
                if ($active_tab === 'caching') {
                    settings_fields('wp_fastify_caching_options');
                    $this->render_caching_section();
                } elseif ($active_tab === 'asset_optimization') {
                    settings_fields('wp_fastify_asset_optimization_options');
                    $this->render_asset_optimization_section();
                }
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    private function render_caching_section() {
        ?>
        <h2>Caching</h2>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Enable Page Caching</th>
                <td>
                    <input type="checkbox" name="wp_fastify_caching_enable_cache" value="1" 
                    <?php checked(1, get_option('wp_fastify_caching_enable_cache', 0)); ?> />
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Cache Duration (Seconds)</th>
                <td>
                    <input type="number" name="wp_fastify_caching_cache_duration" 
                           value="<?php echo esc_attr(get_option('wp_fastify_caching_cache_duration', 31536000)); ?>" 
                           min="0" step="1" />
                    <p class="description">Specify the cache duration in seconds (e.g., 31536000 for 1 year).</p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Enable Static Asset Caching (.htaccess)</th>
                <td>
                    <input type="checkbox" name="wp_fastify_caching_enable_static_caching" value="1" 
                    <?php checked(1, get_option('wp_fastify_caching_enable_static_caching', 0)); ?> />
                    <label for="wp_fastify_caching_enable_static_caching">Insert static asset caching rules in .htaccess</label>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Enable Header Caching</th>
                <td>
                    <input type="checkbox" name="wp_fastify_caching_enable_header_caching" value="1" 
                    <?php checked(1, get_option('wp_fastify_caching_enable_header_caching', 0)); ?> />
                    <label for="wp_fastify_caching_enable_header_caching">Add cache headers dynamically for served assets.</label>
                </td>
            </tr>
        </table>
        <h3>Nginx Configuration</h3>
        <p>
            If you are using Nginx, add the following code to your server block to enable static asset caching:
        </p>
        <pre style="background: #f1f1f1; padding: 10px; border: 1px solid #ddd;">
# WPFastify Static Asset Caching
location ~* \.(css|js|jpg|jpeg|png|gif|webp|svg|ico|woff|woff2|ttf|otf|eot|mp4)$ {
    expires 1y;
    add_header Cache-Control "public";
}
# End WPFastify Static Asset Caching
        </pre>
        <?php
    }

    private function render_asset_optimization_section() {
        ?>
        <h2>Asset Optimization</h2>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Enable CSS/JS Minification</th>
                <td>
                    <input type="checkbox" name="wp_fastify_asset_optimization_enable_minification" value="1" 
                    <?php checked(1, get_option('wp_fastify_asset_optimization_enable_minification', 0)); ?> />
                    <label for="wp_fastify_asset_optimization_enable_minification">Minify CSS and JS files to reduce file sizes by removing unnecessary spaces and comments.</label>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Enable HTML Minification</th>
                <td>
                    <input type="checkbox" name="wp_fastify_asset_optimization_enable_html_minification" value="1" 
                    <?php checked(1, get_option('wp_fastify_asset_optimization_enable_html_minification', 0)); ?> />
                    <label for="wp_fastify_asset_optimization_enable_html_minification">Minify HTML files to reduce file sizes and Simplifies HTML files for faster loading.</label>
                </td>
            </tr>
            </table>
        <?php
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

    /**
     * Set Cache Headers for Static Files
     *
     * Adds cache control headers for dynamically served static assets if enabled.
     */
    public function set_cache_headers() {
        $enable_header_caching = get_option('wp_fastify_caching_enable_header_caching', 0);
        if ($enable_header_caching) {
            $cache_duration = absint(get_option('wp_fastify_caching_cache_duration', 31536000)); // Default to 1 year
            header('Cache-Control: public, max-age=' . $cache_duration);
            header('Pragma: public');
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cache_duration) . ' GMT');
        }
    }

    /**
     * Generate Nginx cache config file for user download
     */
    public function generate_nginx_cache_config() {
        $nginx_config = <<<NGINX
# WPFastify Static Asset Caching
location ~* \.(css|js|jpg|jpeg|png|gif|webp|svg|ico|woff|woff2|ttf|otf|eot|mp4)$ {
    expires 1y;
    add_header Cache-Control "public";
}
# End WPFastify Static Asset Caching
NGINX;

        // Create a custom Nginx config file
        $config_file = WP_CONTENT_DIR . '/wp-fastify-nginx-cache.conf';

        // Write the config to the file (user can copy it into their Nginx config)
        file_put_contents($config_file, $nginx_config);

        echo '<p>Download the Nginx cache configuration file: <a href="' . content_url('wp-fastify-nginx-cache.conf') . '" download>Download Nginx Config</a></p>';
    }
}
