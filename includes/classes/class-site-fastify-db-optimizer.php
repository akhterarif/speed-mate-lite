<?php 
namespace Site_Fastify\Includes;

class Site_Fastify_DB_Optimizer {
    public function __construct() {
        $this->register_hooks();
    }

    public function register_hooks() {
        add_action('wp', [$this, 'schedule_cleanup_tasks']);
        add_action('site_fastify_revisions_cleanup_cron', [$this, 'cleanup_revisions']);
        add_action('site_fastify_trash_spam_cleanup_cron', [$this, 'cleanup_trash_and_spam']);

        add_action('update_option_site_fastify_db_optimization_revisions_cleanup_enable', function ($old_value, $value) {
            if (!$value) {
                wp_clear_scheduled_hook('site_fastify_revisions_cleanup_cron');
            }
        }, 10, 2);

        add_action('update_option_site_fastify_db_optimization_trash_spam_cleanup_enable', function ($old_value, $value) {
            if (!$value) {
                wp_clear_scheduled_hook('site_fastify_trash_spam_cleanup_cron');
            }
        }, 10, 2);

        add_action('wp_ajax_site_fastify_revisions_cleanup', [$this, 'ajax_revisions_cleanup']);
        add_action('wp_ajax_site_fastify_trash_spam_cleanup', [$this, 'ajax_trash_spam_cleanup']);
    }

    public function ajax_revisions_cleanup() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_key($_POST['nonce']), 'site_fastify_revisions_cleanup_nonce')) {
            wp_send_json_error(['message' => 'Nonce verification failed.']);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'You do not have sufficient permissions to perform this action.']);
        }

        $this->cleanup_revisions();
        wp_send_json_success(['message' => 'Revisions cleanup executed successfully.']);
    }

    public function ajax_trash_spam_cleanup() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_key($_POST['nonce']), 'site_fastify_trash_spam_cleanup_nonce')) {
            wp_send_json_error(['message' => 'Nonce verification failed.']);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'You do not have sufficient permissions to perform this action.']);
        }

        $this->cleanup_trash_and_spam();
        wp_send_json_success(['message' => 'Trash and spam cleanup executed successfully.']);
    }

    public function cleanup_revisions() {
        $enable_revisions_cleanup = get_option('site_fastify_db_optimization_revisions_cleanup_enable', 0);
        $keep_count = (int) get_option('site_fastify_db_optimization_revisions_cleanup_keep_count', 5);

        if ($enable_revisions_cleanup) {
            global $wpdb;

            // Get IDs of revisions to keep
            $revisions_to_keep_query = $wpdb->prepare("
                SELECT ID 
                FROM {$wpdb->posts}
                WHERE post_type = %s
                AND post_parent IN (
                    SELECT post_parent 
                    FROM {$wpdb->posts}
                    WHERE post_type = %s
                    GROUP BY post_parent
                    HAVING COUNT(ID) > %d
                )
            ", 'revision', 'revision', $keep_count);

            $revisions_to_keep_ids = $wpdb->get_col($revisions_to_keep_query);

            if (!empty($revisions_to_keep_ids)) {
                $placeholders = implode(',', array_fill(0, count($revisions_to_keep_ids), '%d'));
                $sql = $wpdb->prepare("
                    DELETE FROM {$wpdb->posts}
                    WHERE post_type = %s
                    AND ID NOT IN ($placeholders)
                ", array_merge(['revision'], $revisions_to_keep_ids));

                $wpdb->query($sql);
            } else {
                // If no revisions to keep, delete all revisions
                $wpdb->query($wpdb->prepare("
                    DELETE FROM {$wpdb->posts}
                    WHERE post_type = %s
                ", 'revision'));
            }
        }
    }

    public function cleanup_trash_and_spam() {
        $enable_trash_spam_cleanup = get_option('site_fastify_db_optimization_trash_spam_cleanup_enable', 0);

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
        if (!wp_next_scheduled('site_fastify_revisions_cleanup_cron') && get_option('site_fastify_db_optimization_revisions_cleanup_enable', 0)) {
            $schedule = get_option('site_fastify_db_optimization_revisions_cleanup_schedule', 'weekly');
            wp_schedule_event(time(), $schedule, 'site_fastify_revisions_cleanup_cron');
        }

        if (!wp_next_scheduled('site_fastify_trash_spam_cleanup_cron') && get_option('site_fastify_db_optimization_trash_spam_cleanup_enable', 0)) {
            $schedule = get_option('site_fastify_db_optimization_trash_spam_cleanup_schedule', 'weekly');
            wp_schedule_event(time(), $schedule, 'site_fastify_trash_spam_cleanup_cron');
        }
    }
}
