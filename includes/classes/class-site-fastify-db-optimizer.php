<?php
namespace Site_Fastify\Includes;

class Site_Fastify_DB_Optimizer {
    public function __construct() {
        $this->register_hooks();
    }

    public function register_hooks() {
        // Move all database-related hooks here
        add_action('wp', [$this, 'schedule_cleanup_tasks']);
        add_action('wp_fastify_revisions_cleanup_cron', [$this, 'cleanup_revisions']);
        add_action('wp_fastify_trash_spam_cleanup_cron', [$this, 'cleanup_trash_and_spam']);

        // Clear scheduled task when disabling
        add_action('update_option_wp_fastify_db_optimization_revisions_cleanup_enable', function ($old_value, $value) {
            if (!$value) {
                wp_clear_scheduled_hook('wp_fastify_revisions_cleanup_cron');
            }
        }, 10, 2);

        // Clear scheduled task when disabling
        add_action('update_option_wp_fastify_db_optimization_trash_spam_cleanup_enable', function ($old_value, $value) {
            if (!$value) {
                wp_clear_scheduled_hook('wp_fastify_trash_spam_cleanup_cron');
            }
        }, 10, 2);

        // AJAX hooks for manual revisions cleanup
        add_action('wp_ajax_wp_fastify_revisions_cleanup', [ $this, 'ajax_revisions_cleanup' ]);

        // AJAX hooks for manual trash and spam cleanup
        add_action('wp_ajax_wp_fastify_trash_spam_cleanup', [ $this, 'ajax_trash_spam_cleanup' ]);
    }

    public function ajax_revisions_cleanup() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce(wp_unslash($_POST['nonce']), 'wp_fastify_revisions_cleanup_nonce')) { // Use wp_unslash here
            wp_send_json_error(['message' => 'Nonce verification failed.']);
        }
    
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'You do not have sufficient permissions to perform this action.']);
        }
    
        // Run the revisions cleanup
        $this->cleanup_revisions();
    
        // Send a success response
        wp_send_json_success(['message' => 'Revisions cleanup executed successfully.']);
    }

    public function ajax_trash_spam_cleanup() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce(wp_unslash($_POST['nonce']), 'wp_fastify_trash_spam_cleanup_nonce')) { // Use wp_unslash here
            wp_send_json_error(['message' => 'Nonce verification failed.']);
        }
    
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'You do not have sufficient permissions to perform this action.']);
        }
    
        // Run the trash and spam cleanup
        $this->cleanup_trash_and_spam();
    
        // Send a success response
        wp_send_json_success(['message' => 'Trash and spam cleanup executed successfully.']);
    }

    /**
     * Cleans up the revisions of a post 
     */
    public function cleanup_revisions() {
        $enable_revisions_cleanup = get_option('wp_fastify_db_optimization_revisions_cleanup_enable', 0);
        $keep_count = (int) get_option('wp_fastify_db_optimization_revisions_cleanup_keep_count', 5);

        if ($enable_revisions_cleanup) {
            global $wpdb;
        
            // Get the IDs of revisions to keep with proper placeholders
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
        
            $revisions_to_keep_ids = $wpdb->get_col($wpdb->prepare($revisions_to_keep_query, $keep_count)); // Using prepare()

            if (!empty($revisions_to_keep_ids)) {
                $placeholders = implode(',', array_fill(0, count($revisions_to_keep_ids), '%d'));
        
                // Delete revisions not in the keep list using placeholders
                $cleanup_query = "
                    DELETE FROM {$wpdb->posts}
                    WHERE post_type = 'revision'
                    AND ID NOT IN ($placeholders)
                ";
        
                $wpdb->query($wpdb->prepare($cleanup_query, ...$revisions_to_keep_ids)); // Using prepare()
            } else {
                // No revisions to keep, clean up all revisions
                $cleanup_query = "
                    DELETE FROM {$wpdb->posts}
                    WHERE post_type = 'revision'
                ";
        
                $wpdb->query($cleanup_query); // No prepare needed since no placeholders
            }
        }
    }

    public function cleanup_trash_and_spam() {
        $enable_trash_spam_cleanup = get_option('wp_fastify_db_optimization_trash_spam_cleanup_enable', 0);

        if ($enable_trash_spam_cleanup) {
            global $wpdb;
        
            // Delete spam comments with placeholders
            $spam_comments_deleted = $wpdb->query($wpdb->prepare("
                DELETE FROM $wpdb->comments
                WHERE comment_approved = %s
            ", 'spam'));
        
            // Delete trash comments with placeholders
            $trash_comments_deleted = $wpdb->query($wpdb->prepare("
                DELETE FROM $wpdb->comments
                WHERE comment_approved = %s
            ", 'trash'));
        
            // Delete trashed posts with placeholders
            $trashed_posts_deleted = $wpdb->query($wpdb->prepare("
                DELETE FROM $wpdb->posts
                WHERE post_status = %s
            ", 'trash'));
        
            // Optionally, delete related metadata for comments and posts
            $wpdb->query($wpdb->prepare("
                DELETE FROM $wpdb->commentmeta
                WHERE comment_id NOT IN (
                    SELECT comment_id FROM $wpdb->comments
                )
            "));
            $wpdb->query($wpdb->prepare("
                DELETE FROM $wpdb->postmeta
                WHERE post_id NOT IN (
                    SELECT ID FROM $wpdb->posts
                )
            "));
        
            // Check if WP_DEBUG_LOG is enabled and log the results conditionally
            if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log("Trash and spam cleanup executed: 
                    Spam comments deleted: $spam_comments_deleted, 
                    Trash comments deleted: $trash_comments_deleted, 
                    Trashed posts deleted: $trashed_posts_deleted.");
            }
        }
    }

    public function schedule_cleanup_tasks() {
        if (!wp_next_scheduled('wp_fastify_revisions_cleanup_cron') && get_option('wp_fastify_db_optimization_revisions_cleanup_enable', 0)) {
            $schedule = get_option('wp_fastify_db_optimization_revisions_cleanup_schedule', 'weekly');
            wp_schedule_event(time(), $schedule, 'wp_fastify_revisions_cleanup_cron');
        }

        if (!wp_next_scheduled('wp_fastify_trash_spam_cleanup_cron') && get_option('wp_fastify_db_optimization_trash_spam_cleanup_enable', 0)) {
            $schedule = get_option('wp_fastify_db_optimization_trash_spam_cleanup_schedule', 'weekly');
            wp_schedule_event(time(), $schedule, 'wp_fastify_trash_spam_cleanup_cron');
        }
    }
}
