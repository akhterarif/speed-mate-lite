<?php

namespace Speed_Mate\Admin;

use Speed_Mate\Includes\Speed_Mate_Asset_Optimizer;
use Speed_Mate\Includes\Speed_Mate_Caching;
use Speed_Mate\Includes\Speed_Mate_DB_Optimizer;
use Speed_Mate\Includes\Speed_Mate_Page_Speed;

class Speed_Mate_Admin {
    private $settings;
    private $caching;
    private $asset_optimizer;
    private $db_optimizer;
    private $page_speed;
    
    public function __construct() {
        $this->load_dependencies();
        $this->init_components();
        $this->register_hooks();
        $this->register_ajax_handlers();
    }

    private function load_dependencies() {
        require_once plugin_dir_path(__FILE__) . 'class-speed-mate-settings.php';
        require_once plugin_dir_path(__FILE__) . '../includes/classes/class-speed-mate-caching.php';
        require_once plugin_dir_path(__FILE__) . '../includes/classes/class-speed-mate-asset-optimizer.php';
        require_once plugin_dir_path(__FILE__) . '../includes/classes/class-speed-mate-db-optimizer.php';
        require_once plugin_dir_path(__FILE__) . '../includes/classes/class-speed-mate-page-speed.php';
    }

    private function init_components() {
        $this->settings = new Speed_Mate_Settings();
        $this->caching = new Speed_Mate_Caching();
        $this->asset_optimizer = new Speed_Mate_Asset_Optimizer();
        $this->db_optimizer = new Speed_Mate_DB_Optimizer();
        $this->page_speed = new Speed_Mate_Page_Speed();
    }

    public function register_hooks() {
        add_action('admin_menu', [$this->settings, 'add_settings_page']);
        add_action('admin_init', [$this->settings, 'register_settings']);
        
    }

    public function register_ajax_handlers() {
        add_action('wp_ajax_speed_mate_save_settings', [$this, 'handle_save_settings']);
        add_action('wp_ajax_speed_mate_get_speed_metrics', [$this->page_speed, 'get_speed_metrics']);
    }
    
    public function handle_save_settings() {
        // Verify nonce
        $tab = isset($_POST['tab']) ? sanitize_key($_POST['tab']) : '';
        if (!check_ajax_referer('speed_mate_' . $tab . '_nonce', 'speed_mate_nonce', false)) {
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

            case 'performance_analysis':
                $response = $this->show_performance_analysis($_POST);
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
        update_option('speed_mate_caching_enable_cache', 
            isset($data['speed_mate_caching_enable_cache']) ? 1 : 0);
        
        update_option('speed_mate_caching_cache_duration', 
            absint($data['speed_mate_caching_cache_duration']));
        
        $static_caching = isset($data['speed_mate_caching_enable_static_caching']) ? 1 : 0;
        update_option('speed_mate_caching_enable_static_caching', $static_caching);
        
        // Update htaccess if static caching setting changed
        if ($static_caching != get_option('speed_mate_caching_enable_static_caching')) {
            $this->caching->update_htaccess_based_on_setting();
            $response['htaccess_updated'] = true;
        }
        
        return $response;
    }

    private function save_asset_optimization_settings($data) {
        $response = ['cache_cleared' => false];
        
        // Update optimization settings
        update_option('speed_mate_asset_optimization_enable_minification', 
            isset($data['speed_mate_asset_optimization_enable_minification']) ? 1 : 0);
        
        update_option('speed_mate_asset_optimization_enable_html_minification', 
            isset($data['speed_mate_asset_optimization_enable_html_minification']) ? 1 : 0);
        
        update_option('speed_mate_asset_optimization_enable_image_lazy_loading', 
            isset($data['speed_mate_asset_optimization_enable_image_lazy_loading']) ? 1 : 0);

        update_option('speed_mate_asset_optimization_exclusions', 
            sanitize_textarea_field($data['speed_mate_asset_optimization_exclusions']));

        update_option('speed_mate_asset_optimization_combine_css', 
            isset($data['speed_mate_asset_optimization_combine_css']) ? 1 : 0);
        
        // Clear asset cache if settings changed
        $this->caching::clear_cache();
        $response['cache_cleared'] = true;
        
        return $response;
    }
    
    private function save_db_optimization_settings($data) {
        // Update database optimization settings
        update_option('speed_mate_db_optimization_revisions_cleanup_enable', 
            isset($data['speed_mate_db_optimization_revisions_cleanup_enable']) ? 1 : 0);
        
        update_option('speed_mate_db_optimization_revisions_cleanup_schedule', 
            sanitize_key($data['speed_mate_db_optimization_revisions_cleanup_schedule']));

        update_option('speed_mate_db_optimization_revisions_cleanup_keep_count', isset($data['speed_mate_db_optimization_revisions_cleanup_keep_count']) ? $data['speed_mate_db_optimization_revisions_cleanup_keep_count'] : 5);
        
        
        update_option('speed_mate_db_optimization_trash_spam_cleanup_enable', 
            isset($data['speed_mate_db_optimization_trash_spam_cleanup_enable']) ? 1 : 0);
        
        update_option('speed_mate_db_optimization_trash_spam_cleanup_schedule', 
            sanitize_key($data['speed_mate_db_optimization_trash_spam_cleanup_schedule']));
        
        
        
            return ['schedule_updated' => true];
    }


    private function show_performance_analysis($data) {
        update_option('speed_mate_pa_google_api_key', 
            sanitize_text_field($data['speed_mate_pa_google_api_key']));
       
        // Update database optimization settings
        $url = get_home_url(); // Use site's home URL
        $api_key = get_option('speed_mate_pa_google_api_key', ''); // Replace with your actual API key
        $api_url = "https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=$url&key=$api_key";


        if (empty($api_key)) {
            wp_send_json_error(['message' => 'Please enter a valid Google API key']);
        }

        $response = wp_remote_get($api_url);
        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Failed to fetch data from PageSpeed API']);
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($data['lighthouseResult'])) {
            wp_send_json_error(['message' => 'Invalid response from API']);
        }

        $lighthouse = $data['lighthouseResult'];

        // Extract metrics and calculate statuses
        $metrics = [
            [
                'name' => 'First Contentful Paint (FCP)',
                'value' => $lighthouse['audits']['first-contentful-paint']['displayValue'],
                'status' => $lighthouse['audits']['first-contentful-paint']['score'] >= 0.9 ? 'Good' : 'Needs Improvement',
            ],
            [
                'name' => 'Largest Contentful Paint (LCP)',
                'value' => $lighthouse['audits']['largest-contentful-paint']['displayValue'],
                'status' => $lighthouse['audits']['largest-contentful-paint']['score'] >= 0.9 ? 'Good' : 'Needs Improvement',
            ],
            [
                'name' => 'Cumulative Layout Shift (CLS)',
                'value' => $lighthouse['audits']['cumulative-layout-shift']['displayValue'],
                'status' => $lighthouse['audits']['cumulative-layout-shift']['score'] >= 0.9 ? 'Good' : 'Needs Improvement',
            ],
            [
                'name' => 'Total Blocking Time (TBT)',
                'value' => $lighthouse['audits']['total-blocking-time']['displayValue'],
                'status' => $lighthouse['audits']['total-blocking-time']['score'] >= 0.9 ? 'Good' : 'Needs Improvement',
            ],
            [
                'name' => 'Time to Interactive (TTI)',
                'value' => $lighthouse['audits']['interactive']['displayValue'],
                'status' => $lighthouse['audits']['interactive']['score'] >= 0.9 ? 'Good' : 'Needs Improvement',
            ],
            [
                'name' => 'Speed Index',
                'value' => $lighthouse['audits']['speed-index']['displayValue'],
                'status' => $lighthouse['audits']['speed-index']['score'] >= 0.9 ? 'Good' : 'Needs Improvement',
            ],
            [
                'name' => 'Server Response Time (TTFB)',
                'value' => $lighthouse['audits']['server-response-time']['displayValue'],
                'status' => $lighthouse['audits']['server-response-time']['score'] >= 0.9 ? 'Good' : 'Needs Improvement',
            ],
        ];

        // Recommendations based on audits
        $recommendations = [];
        if ($lighthouse['audits']['uses-optimized-images']['score'] < 0.9) {
            $recommendations[] = 'Optimize your images to improve loading speed.';
        }
        if ($lighthouse['audits']['unused-css-rules']['score'] < 0.9) {
            $recommendations[] = 'Remove unused CSS to reduce page size.';
        }
        if ($lighthouse['audits']['render-blocking-resources']['score'] < 0.9) {
            $recommendations[] = 'Eliminate render-blocking resources to improve page load time.';
        }
        if ($lighthouse['audits']['uses-text-compression']['score'] < 0.9) {
            $recommendations[] = 'Enable text compression (e.g., Gzip or Brotli) to reduce data transfer.';
        }
        if ($lighthouse['audits']['efficient-animated-content']['score'] < 0.9) {
            $recommendations[] = 'Optimize animations or large visual elements.';
        }

         // Send the response back to the frontend
        return [
            'score' => $lighthouse['categories']['performance']['score'] * 100, // Overall score
            'metrics' => $metrics,
            'recommendations' => $recommendations,
        ];

        
        
    }

}