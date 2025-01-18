<?php

namespace WP_Fastify;

class WP_Fastify_Admin {

    public function __construct() {
        $this->load_dependencies();
        $this->register_hooks();
    }

    public function register_hooks() {
        // Loading the minified files in the site 
        add_filter('script_loader_src', [ 'WP_Fastify\WP_Fastify_Minifier', 'minify_assets' ], 10, 2);
        add_filter('style_loader_src', [ 'WP_Fastify\WP_Fastify_Minifier', 'minify_assets' ], 10, 2);

        
        add_action('admin_menu', [ $this, 'add_settings_page' ]);
        add_action('admin_init', [ $this, 'register_settings' ]);
        add_action('admin_init', [ $this, 'update_htaccess_based_on_setting' ]);
        add_action('admin_post_wp_fastify_run_cleanup', [ $this, 'handle_manual_cleanup' ]); // Add this line

        

        // Schedule cleanup task
        add_action('wp', function () {
            if (!wp_next_scheduled('wp_fastify_revisions_cleanup_cron') && get_option('wp_fastify_revisions_cleanup_enable', 0)) {
                $schedule = get_option('wp_fastify_revisions_cleanup_schedule', 'weekly');
                wp_schedule_event(time(), $schedule, 'wp_fastify_revisions_cleanup_cron');
            }
        });

        // Perform the cleanup
        add_action('wp_fastify_revisions_cleanup_cron', [$this, 'wp_fastify_cleanup_revisions']);

        // Clear scheduled task when disabling
        add_action('update_option_wp_fastify_revisions_cleanup_enable', function ($old_value, $value) {
            if (!$value) {
                wp_clear_scheduled_hook('wp_fastify_revisions_cleanup_cron');
            }
        }, 10, 2);

        add_action('admin_footer', function () {
            if (isset($_POST['wp_fastify_revisions_cleanup_manual'])) {
                check_admin_referer('wp_fastify_revisions_cleanup_manual_action');
                $this->wp_fastify_cleanup_revisions();
                add_settings_error('wp_fastify', 'manual_cleanup', 'Revisions cleanup executed successfully.', 'updated');
            }
        });
        
        add_action('wp_fastify_settings_page', function () {
            echo '<form method="post" action="">';
            wp_nonce_field('wp_fastify_revisions_cleanup_manual_action');
            echo '<input type="submit" name="wp_fastify_revisions_cleanup_manual" class="button button-primary" value="Run Cleanup Now">';
            echo '</form>';
        });

        // Perform trash and spam cleanup
        add_action('wp_fastify_trash_spam_cleanup_cron', [$this, 'wp_fastify_cleanup_trash_and_spam']);

        // Clear scheduled task when disabling
        add_action('update_option_wp_fastify_trash_spam_cleanup_enable', function ($old_value, $value) {
            if (!$value) {
                wp_clear_scheduled_hook('wp_fastify_trash_spam_cleanup_cron');
            }
        }, 10, 2);

        // Manual cleanup handler
        // add_action('admin_post_wp_fastify_run_trash_spam_cleanup', [$this, 'handle_manual_trash_spam_cleanup']);
    

        // AJAX hooks for manual trash and spam cleanup
        add_action('wp_ajax_wp_fastify_trash_spam_cleanup', [ $this, 'ajax_trash_spam_cleanup' ]);
    }

    public function ajax_trash_spam_cleanup() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_fastify_trash_spam_cleanup_nonce')) {
            wp_send_json_error(['message' => 'Nonce verification failed.']);
        }
    
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'You do not have sufficient permissions to perform this action.']);
        }
    
        // Run the trash and spam cleanup
        $this->wp_fastify_cleanup_trash_and_spam();
    
        // Send a success response
        wp_send_json_success(['message' => 'Trash and spam cleanup executed successfully.']);
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
        register_setting('wp_fastify_asset_optimization_options', 'wp_fastify_asset_optimization_enable_image_lazy_loading');

        // Database Optimization
        register_setting('wp_fastify_db_optimization_options', 'wp_fastify_db_optimization_revisions_cleanup_enable');
        register_setting('wp_fastify_db_optimization_options', 'wp_fastify_db_optimization_revisions_cleanup_schedule');
        register_setting('wp_fastify_db_optimization_options', 'wp_fastify_db_optimization_revisions_cleanup_keep_count');
    }

    public function render_settings_page() {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'caching';
        if (isset($_GET['cleanup']) && $_GET['cleanup'] === 'success') {
            echo '<div class="notice notice-success is-dismissible"><p>Revisions cleanup executed successfully.</p></div>';
        }
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
                <a href="?page=wp-fastify&tab=db_optimization" class="nav-tab <?php echo $active_tab === 'db_optimization' ? 'nav-tab-active' : ''; ?>">
                    Database Optimization
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
                } elseif ($active_tab === 'db_optimization') {
                    settings_fields('wp_fastify_db_optimization_options');
                    $this->render_db_optimization_section();
                }
                submit_button();
                ?>
            </form>
    
            <?php if ($active_tab === 'db_optimization') : ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('wp_fastify_revisions_cleanup_manual_action', '_wpnonce'); ?>
                    <input type="hidden" name="action" value="wp_fastify_run_cleanup">
                    <input type="submit" class="button button-primary" value="Run Cleanup Now">
                </form>
            <?php endif; ?>
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
            <tr valign="top">
                <th scope="row">Enable Lazy Loading for Images</th>
                <td>
                    <input type="checkbox" name="wp_fastify_asset_optimization_enable_image_lazy_loading" value="1" 
                    <?php checked(1, get_option('wp_fastify_asset_optimization_enable_image_lazy_loading', 0)); ?> />
                    <label for="wp_fastify_asset_optimization_enable_image_lazy_loading">Loads images as users scroll.</label>
                </td>
            </tr>
        </table>
        <?php
    }

    private function render_db_optimization_section() {
        ?>
        <h2>Database Optimization</h2>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Enable Revisions Cleanup</th>
                <td>
                    <input type="checkbox" name="wp_fastify_db_optimization_revisions_cleanup_enable" value="1" 
                    <?php checked(1, get_option('wp_fastify_db_optimization_revisions_cleanup_enable', 0)); ?> />
                    <label for="wp_fastify_db_optimization_revisions_cleanup_enable">Enable/Disable revisions cleanup in database.</label>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Cleanup Schedule</th>
                <td>
                    <?php
                    $value = get_option('wp_fastify_db_optimization_revisions_cleanup_schedule', 'weekly');
                    $options = ['daily', 'weekly', 'monthly'];
                    ?>
                    <select name="wp_fastify_db_optimization_revisions_cleanup_schedule">
                        <?php foreach ($options as $option) { ?>
                        <option value="<?php echo $option; ?>" <?php selected($value, $option, true); ?> >
                            <?php echo ucfirst($option); ?>
                        </option>
                        <?php } ?>
                    </select>
                    <label for="wp_fastify_db_optimization_revisions_cleanup_schedule">Schedule the time when you want to delete the revisions.</label>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Revisions to Keep per Post</th>
                <td>
                    <?php $value = get_option('wp_fastify_db_optimization_revisions_cleanup_keep_count', 5); ?>
                    <input type="number" name="wp_fastify_db_optimization_revisions_cleanup_keep_count" value="<?php echo esc_attr($value); ?>" />
                    <label for="wp_fastify_db_optimization_revisions_cleanup_keep_count">Enter the number of revisions to keep per post.</label>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Enable Trash and Spam Cleanup</th>
                <td>
                    <input type="checkbox" name="wp_fastify_trash_spam_cleanup_enable" value="1" 
                    <?php checked(1, get_option('wp_fastify_trash_spam_cleanup_enable', 0)); ?> />
                    <label for="wp_fastify_trash_spam_cleanup_enable">Automatically clean up trash and spam from the database.</label>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Trash and Spam Cleanup Schedule</th>
                <td>
                    <?php
                    $value = get_option('wp_fastify_trash_spam_cleanup_schedule', 'weekly');
                    $options = ['daily', 'weekly', 'monthly'];
                    ?>
                    <select name="wp_fastify_trash_spam_cleanup_schedule">
                        <?php foreach ($options as $option) { ?>
                        <option value="<?php echo $option; ?>" <?php selected($value, $option, true); ?>>
                            <?php echo ucfirst($option); ?>
                        </option>
                        <?php } ?>
                    </select>
                    <label for="wp_fastify_trash_spam_cleanup_schedule">Set the schedule for trash and spam cleanup.</label>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Trash and Spam Cleanup Manually</th>
                <td>
                    <button id="wp-fastify-trash-spam-cleanup-btn" class="button button-primary">Run Cleanup Now</button>
                    <div id="wp-fastify-success-message" class="hidden" style="margin-top: 10px;"></div>
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

    /**
     * Cleans up the revisions of a post 
    */
    public function wp_fastify_cleanup_revisions() {
        $keep_count = (int) get_option('wp_fastify_db_optimization_revisions_cleanup_keep_count', 5);
    
        global $wpdb;
    
        // Get the IDs of revisions to keep
        $revisions_to_keep_query = "
            SELECT rr.ID
            FROM {$wpdb->posts} AS rr
            WHERE rr.post_type = 'revision'
            AND (
                SELECT COUNT(*)
                FROM {$wpdb->posts} AS rr_inner
                WHERE rr_inner.post_parent = rr.post_parent
                AND rr_inner.post_type = 'revision'
                AND rr_inner.ID >= rr.ID
            ) <= %d
        ";
    
        $revisions_to_keep_ids = $wpdb->get_col($wpdb->prepare($revisions_to_keep_query, $keep_count));
    
        if (!empty($revisions_to_keep_ids)) {
            $placeholders = implode(',', array_fill(0, count($revisions_to_keep_ids), '%d'));
    
            // Delete revisions not in the keep list
            $cleanup_query = "
                DELETE FROM {$wpdb->posts}
                WHERE post_type = 'revision'
                AND ID NOT IN ($placeholders)
            ";
    
            $wpdb->query($wpdb->prepare($cleanup_query, ...$revisions_to_keep_ids));
        } else {
            // No revisions to keep, clean up all revisions
            $cleanup_query = "
                DELETE FROM {$wpdb->posts}
                WHERE post_type = 'revision'
            ";
    
            $wpdb->query($cleanup_query);
        }
    
        error_log('Revisions cleanup executed.');
    }

    public function handle_manual_cleanup() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
    
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wp_fastify_revisions_cleanup_manual_action')) {
            wp_die(__('Nonce verification failed.'));
        }
    
        $this->wp_fastify_cleanup_revisions();
    
        // Redirect back to the settings page with a success message
        wp_redirect(add_query_arg(['page' => 'wp-fastify', 'tab' => 'db_optimization', 'cleanup' => 'success'], admin_url('options-general.php')));
        exit;
    }

    public function wp_fastify_cleanup_trash_and_spam() {
        global $wpdb;
    
        // Delete spam comments
        $spam_comments_deleted = $wpdb->query("
            DELETE FROM $wpdb->comments
            WHERE comment_approved = 'spam'
        ");
    
        // Delete trash comments
        $trash_comments_deleted = $wpdb->query("
            DELETE FROM $wpdb->comments
            WHERE comment_approved = 'trash'
        ");
    
        // Delete trashed posts
        $trashed_posts_deleted = $wpdb->query("
            DELETE FROM $wpdb->posts
            WHERE post_status = 'trash'
        ");
    
        // Optionally, delete related metadata for comments and posts
        $wpdb->query("
            DELETE FROM $wpdb->commentmeta
            WHERE comment_id NOT IN (
                SELECT comment_id FROM $wpdb->comments
            )
        ");
        $wpdb->query("
            DELETE FROM $wpdb->postmeta
            WHERE post_id NOT IN (
                SELECT ID FROM $wpdb->posts
            )
        ");
    
        // Log results for debugging
        error_log("Trash and spam cleanup executed: 
            Spam comments deleted: $spam_comments_deleted, 
            Trash comments deleted: $trash_comments_deleted, 
            Trashed posts deleted: $trashed_posts_deleted.");
    }

}
