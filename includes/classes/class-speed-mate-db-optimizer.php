<?php 
namespace Speed_Mate\Includes;

class Speed_Mate_DB_Optimizer {
    public function __construct() {
        $this->register_hooks();
    }

    public function register_hooks() {
        add_action('wp', [$this, 'schedule_cleanup_tasks']);
        add_action('speed_mate_revisions_cleanup_cron', [$this, 'cleanup_revisions']);
        add_action('speed_mate_trash_spam_cleanup_cron', [$this, 'cleanup_trash_and_spam']);

        add_action('update_option_speed_mate_db_optimization_revisions_cleanup_enable', function ($old_value, $value) {
            if (!$value) {
                wp_clear_scheduled_hook('speed_mate_revisions_cleanup_cron');
            }
        }, 10, 2);

        add_action('update_option_speed_mate_db_optimization_trash_spam_cleanup_enable', function ($old_value, $value) {
            if (!$value) {
                wp_clear_scheduled_hook('speed_mate_trash_spam_cleanup_cron');
            }
        }, 10, 2);

        add_action('wp_ajax_speed_mate_revisions_cleanup', [$this, 'ajax_revisions_cleanup']);
        add_action('wp_ajax_speed_mate_trash_spam_cleanup', [$this, 'ajax_trash_spam_cleanup']);
    }

    public function ajax_revisions_cleanup() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_key($_POST['nonce']), 'speed_mate_revisions_cleanup_nonce')) {
            wp_send_json_error(['message' => 'Nonce verification failed.']);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'You do not have sufficient permissions to perform this action.']);
        }

        $this->cleanup_revisions();
        wp_send_json_success(['message' => 'Revisions cleanup executed successfully.']);
    }

    public function ajax_trash_spam_cleanup() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_key($_POST['nonce']), 'speed_mate_trash_spam_cleanup_nonce')) {
            wp_send_json_error(['message' => 'Nonce verification failed.']);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'You do not have sufficient permissions to perform this action.']);
        }

        $this->cleanup_trash_and_spam();
        wp_send_json_success(['message' => 'Trash and spam cleanup executed successfully.']);
    }

    public function cleanup_revisions() {
        global $wpdb;
    
        $enable_revisions_cleanup = get_option('speed_mate_db_optimization_revisions_cleanup_enable', 0);
        $keep_count = (int) get_option('speed_mate_db_optimization_revisions_cleanup_keep_count', 5);
    
        if (!$enable_revisions_cleanup) {
            error_log('Revisions cleanup is disabled.');
            return;
        }
    
        // Get all post IDs that have more revisions than the allowed limit
        $posts_with_excess_revisions = $wpdb->get_col($wpdb->prepare("
            SELECT post_parent 
            FROM {$wpdb->posts}
            WHERE post_type = %s 
            GROUP BY post_parent
            HAVING COUNT(ID) > %d
        ", 'revision', $keep_count));
    
        if (empty($posts_with_excess_revisions)) {
            return;
        }
    
        // Loop through each post that has excess revisions
        foreach ($posts_with_excess_revisions as $post_id) {
            // Get the IDs of the oldest revisions beyond the keep count
            $revisions_to_delete = $wpdb->get_col($wpdb->prepare("
                SELECT ID FROM {$wpdb->posts}
                WHERE post_type = %s 
                AND post_parent = %d
                ORDER BY post_date ASC
                LIMIT %d
            ", 'revision', $post_id, $keep_count));
    
            if (!empty($revisions_to_delete)) {
                // Delete old revisions
                $placeholders = implode(',', array_fill(0, count($revisions_to_delete), '%d'));
                $query = $wpdb->prepare("
                    DELETE FROM {$wpdb->posts} 
                    WHERE ID IN ($placeholders)
                ", ...$revisions_to_delete);
    
                $wpdb->query($query);
            }
        }
    }
    

    public function cleanup_trash_and_spam() {
        $enable_trash_spam_cleanup = get_option('speed_mate_db_optimization_trash_spam_cleanup_enable', 0);

        if ($enable_trash_spam_cleanup) {
            global $wpdb;

            // Delete spam comments
            $wpdb->query($wpdb->prepare("
                DELETE FROM {$wpdb->comments}
                WHERE comment_approved = %s
            ", 'spam'));

            // Delete trash comments
            $wpdb->query($wpdb->prepare("
                DELETE FROM {$wpdb->comments}
                WHERE comment_approved = %s
            ", 'trash'));

            // Delete trashed posts
            $wpdb->query($wpdb->prepare("
                DELETE FROM {$wpdb->posts}
                WHERE post_status = %s
            ", 'trash'));

            // Delete orphaned metadata
            $wpdb->query("
                DELETE FROM {$wpdb->commentmeta}
                WHERE comment_id NOT IN (
                    SELECT comment_ID FROM {$wpdb->comments}
                )
            ");

            $wpdb->query("
                DELETE FROM {$wpdb->postmeta}
                WHERE post_id NOT IN (
                    SELECT ID FROM {$wpdb->posts}
                )
            ");
        }
    }

    public function schedule_cleanup_tasks() {
        if (!wp_next_scheduled('speed_mate_revisions_cleanup_cron') && get_option('speed_mate_db_optimization_revisions_cleanup_enable', 0)) {
            $schedule = get_option('speed_mate_db_optimization_revisions_cleanup_schedule', 'weekly');
            wp_schedule_event(time(), $schedule, 'speed_mate_revisions_cleanup_cron');
        }

        if (!wp_next_scheduled('speed_mate_trash_spam_cleanup_cron') && get_option('speed_mate_db_optimization_trash_spam_cleanup_enable', 0)) {
            $schedule = get_option('speed_mate_db_optimization_trash_spam_cleanup_schedule', 'weekly');
            wp_schedule_event(time(), $schedule, 'speed_mate_trash_spam_cleanup_cron');
        }
    }
}
