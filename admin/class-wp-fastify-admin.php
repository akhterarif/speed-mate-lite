<?php

namespace WP_Fastify\Admin;

use WP_Fastify\Includes\WP_Fastify_Asset_Optimizer;
use WP_Fastify\Includes\WP_Fastify_Caching;
use WP_Fastify\Includes\WP_Fastify_DB_Optimizer;

class WP_Fastify_Admin {
    private $settings;
    private $caching;
    private $asset_optimizer;
    private $db_optimizer;
    
    public function __construct() {
        $this->load_dependencies();
        $this->init_components();
        $this->register_hooks();
        $this->register_ajax_handlers();
    }

    private function load_dependencies() {
        require_once plugin_dir_path(__FILE__) . 'class-wp-fastify-settings.php';
        require_once plugin_dir_path(__FILE__) . '../includes/classes/class-wp-fastify-caching.php';
        require_once plugin_dir_path(__FILE__) . '../includes/classes/class-wp-fastify-asset-optimizer.php';
        require_once plugin_dir_path(__FILE__) . '../includes/classes/class-wp-fastify-db-optimizer.php';
    }

    private function init_components() {
        $this->settings = new WP_Fastify_Settings();
        $this->caching = new WP_Fastify_Caching();
        $this->asset_optimizer = new WP_Fastify_Asset_Optimizer();
        $this->db_optimizer = new WP_Fastify_DB_Optimizer();
    }

    public function register_hooks() {
        add_action('admin_menu', [$this->settings, 'add_settings_page']);
        add_action('admin_init', [$this->settings, 'register_settings']);
    }

    public function register_ajax_handlers() {
        add_action('wp_ajax_wp_fastify_save_settings', [$this, 'handle_save_settings']);
    }
    
    public function handle_save_settings() {
        // Verify nonce
        $tab = isset($_POST['tab']) ? sanitize_key($_POST['tab']) : '';
        if (!check_ajax_referer('wp_fastify_' . $tab . '_nonce', 'wp_fastify_nonce', false)) {
            wp_send_json_error(['message' => 'Invalid security token.']);
        }
    
        // Verify user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'You are not allowed to do this action.']);
        }
    
        $response = ['reload' => false];
    
        // Handle settings based on tab
        switch ($tab) {
            case 'caching':
                $response = $this->save_caching_settings($_POST);
                break;
                
            case 'asset_optimization':
                $response = $this->save_asset_optimization_settings($_POST);
                break;
                
            case 'db_optimization':
                $response = $this->save_db_optimization_settings($_POST);
                break;
                
            default:
                wp_send_json_error(['message' => 'Invalid settings section.']);
        }
    
        if (isset($response['error'])) {
            wp_send_json_error(['message' => $response['error']]);
        }
    
        wp_send_json_success($response);
    }
    
    private function save_caching_settings($data) {
        $response = ['htaccess_updated' => false];
        
        // Update cache settings
        update_option('wp_fastify_caching_enable_cache', 
            isset($data['wp_fastify_caching_enable_cache']) ? 1 : 0);
        
        update_option('wp_fastify_caching_cache_duration', 
            absint($data['wp_fastify_caching_cache_duration']));
        
        $static_caching = isset($data['wp_fastify_caching_enable_static_caching']) ? 1 : 0;
        update_option('wp_fastify_caching_enable_static_caching', $static_caching);
        
        // Update htaccess if static caching setting changed
        if ($static_caching != get_option('wp_fastify_caching_enable_static_caching')) {
            $this->caching->update_htaccess_based_on_setting();
            $response['htaccess_updated'] = true;
        }
        
        return $response;
    }

    private function save_asset_optimization_settings($data) {
        $response = ['cache_cleared' => false];
        
        // Update optimization settings
        update_option('wp_fastify_asset_optimization_enable_minification', 
            isset($data['wp_fastify_asset_optimization_enable_minification']) ? 1 : 0);
        
        update_option('wp_fastify_asset_optimization_enable_html_minification', 
            isset($data['wp_fastify_asset_optimization_enable_html_minification']) ? 1 : 0);
        
        update_option('wp_fastify_asset_optimization_enable_image_lazy_loading', 
            isset($data['wp_fastify_asset_optimization_enable_image_lazy_loading']) ? 1 : 0);

        update_option('wp_fastify_asset_optimization_exclusions', 
            sanitize_textarea_field($data['wp_fastify_asset_optimization_exclusions']));
        
        // Clear asset cache if settings changed
        $this->caching::clear_cache();
        $response['cache_cleared'] = true;
        
        return $response;
    }
    
    private function save_db_optimization_settings($data) {
        // Update database optimization settings
        update_option('wp_fastify_db_optimization_revisions_cleanup_enable', 
            isset($data['wp_fastify_db_optimization_revisions_cleanup_enable']) ? 1 : 0);
        
        update_option('wp_fastify_db_optimization_revisions_cleanup_schedule', 
            sanitize_key($data['wp_fastify_db_optimization_revisions_cleanup_schedule']));
        
        update_option('wp_fastify_db_optimization_trash_spam_cleanup_enable', 
            isset($data['wp_fastify_db_optimization_trash_spam_cleanup_enable']) ? 1 : 0);
        
        update_option('wp_fastify_db_optimization_trash_spam_cleanup_schedule', 
            sanitize_key($data['wp_fastify_db_optimization_trash_spam_cleanup_schedule']));
        
        return ['schedule_updated' => true];
    }
}