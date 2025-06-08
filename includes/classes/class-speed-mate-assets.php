<?php

namespace Speed_Mate\Includes;

class Speed_Mate_Assets {

    public static function enqueue_jquery() {
       
    }
    
    public static function enqueue_scripts() {
        wp_enqueue_script('jquery'); // Enqueues the default WordPress jQuery
        // Enqueue CSS
        wp_enqueue_style(
            'speed-mate-style',
            plugin_dir_url(__DIR__) . 'assets/css/style.css',
            [],
            '1.0.0'
        );

        // Enqueue JS
        wp_enqueue_script(
            'speed-mate-script',
            plugin_dir_url(__DIR__) . 'assets/js/script.js',
            ['jquery'],
            '1.0.0',
            true
        );
    }

    public static function enqueue_admin_scripts($hook) {
        // Enqueue CSS
        wp_enqueue_style(
            'speed-mate-admin-style',
            plugin_dir_url(__DIR__) . 'assets/admin/css/style.css',
            [],
            '1.0.0'
        );

        // Enqueue JS
        wp_enqueue_script(
            'speed-mate-admin-script',
            plugin_dir_url(__DIR__) . 'assets/admin/js/script.js',
            ['jquery'],
            '1.0.0',
            true
        );


        // Pass AJAX URL and nonce to JavaScript
        wp_localize_script('speed-mate-admin-script', 'speedMateAjax', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'trashSpamNonce' => wp_create_nonce('speed_mate_trash_spam_cleanup_nonce'),
            'revisionsCleanupNonce' => wp_create_nonce('speed_mate_revisions_cleanup_nonce'),
        ]);
    }
}
