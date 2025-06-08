<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>

<h2><?php esc_html_e('Database Optimization Settings', 'speed-mate'); ?></h2>
<div class="notice notice-info">
    <p><?php esc_html_e('Optimize your WordPress database by cleaning up unnecessary data.', 'speed-mate'); ?></p>
</div>

<!-- Revisions Cleanup Section -->
<h3><?php esc_html_e('Post Revisions Cleanup', 'speed-mate'); ?></h3>
<table class="form-table">
    <tr valign="top">
        <th scope="row"><?php esc_html_e('Enable Post/Page Revisions Cleanup', 'speed-mate'); ?></th>
        <td>
            <input type="checkbox" 
                   name="speed_mate_db_optimization_revisions_cleanup_enable" 
                   value="1" 
                   <?php checked(1, get_option('speed_mate_db_optimization_revisions_cleanup_enable', 0)); ?> />
            <p class="description">
                <?php esc_html_e('Automatically clean up old post/page revisions from the database.', 'speed-mate'); ?>
            </p>
        </td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php esc_html_e('Cleanup Schedule', 'speed-mate'); ?></th>
        <td>
            <?php
            $schedule = get_option('speed_mate_db_optimization_revisions_cleanup_schedule', 'weekly');
            ?>
            <select name="speed_mate_db_optimization_revisions_cleanup_schedule">
                <option value="daily" <?php selected($schedule, 'daily'); ?>><?php esc_html_e('Daily', 'speed-mate'); ?></option>
                <option value="weekly" <?php selected($schedule, 'weekly'); ?>><?php esc_html_e('Weekly', 'speed-mate'); ?></option>
                <option value="monthly" <?php selected($schedule, 'monthly'); ?>><?php esc_html_e('Monthly', 'speed-mate'); ?></option>
            </select>
        </td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php esc_html_e('Revisions to Keep', 'speed-mate'); ?></th>
        <td>
            <input type="number" 
                   name="speed_mate_db_optimization_revisions_cleanup_keep_count" 
                   value="<?php echo esc_attr(get_option('speed_mate_db_optimization_revisions_cleanup_keep_count', 5)); ?>" 
                   min="0" />
            <p class="description">
                <?php esc_html_e('Number of recent revisions to keep for each post.', 'speed-mate'); ?>
            </p>
        </td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php esc_html_e('Manual Cleanup', 'speed-mate'); ?></th>
        <td>
            <button id="speed-mate-revisions-cleanup-btn" class="button button-secondary">
                <?php esc_html_e('Run Revisions Cleanup Now', 'speed-mate'); ?>
            </button>
            <div id="speed-mate-revisions-success-message" class="hidden"></div>
        </td>
    </tr>
</table>

<!-- Trash and Spam Cleanup Section -->
<h3><?php esc_html_e('Trash and Spam Cleanup', 'speed-mate'); ?></h3>
<table class="form-table">
    <tr valign="top">
        <th scope="row"><?php esc_html_e('Enable Trash/Spam Cleanup', 'speed-mate'); ?></th>
        <td>
            <input type="checkbox" 
                   name="speed_mate_db_optimization_trash_spam_cleanup_enable" 
                   value="1" 
                   <?php checked(1, get_option('speed_mate_db_optimization_trash_spam_cleanup_enable', 0)); ?> />
            <p class="description">
                <?php esc_html_e('Automatically clean up trashed posts and spam comments.', 'speed-mate'); ?>
            </p>
        </td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php esc_html_e('Cleanup Schedule', 'speed-mate'); ?></th>
        <td>
            <?php
            $schedule = get_option('speed_mate_db_optimization_trash_spam_cleanup_schedule', 'weekly');
            ?>
            <select name="speed_mate_db_optimization_trash_spam_cleanup_schedule">
                <option value="daily" <?php selected($schedule, 'daily'); ?>><?php esc_html_e('Daily', 'speed-mate'); ?></option>
                <option value="weekly" <?php selected($schedule, 'weekly'); ?>><?php esc_html_e('Weekly', 'speed-mate'); ?></option>
                <option value="monthly" <?php selected($schedule, 'monthly'); ?>><?php esc_html_e('Monthly', 'speed-mate'); ?></option>
            </select>
        </td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php esc_html_e('Manual Cleanup', 'speed-mate'); ?></th>
        <td>
            <button id="speed-mate-trash-spam-cleanup-btn" class="button button-secondary">
                <?php esc_html_e('Run Trash/Spam Cleanup Now', 'speed-mate'); ?>
            </button>
            <div id="speed-mate-trash-spam-success-message" class="hidden"></div>
        </td>
    </tr>
</table>

<!-- Database Statistics Section -->
<h3><?php esc_html_e('Database Statistics', 'speed-mate'); ?></h3>
<table class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
            <th><?php esc_html_e('Item', 'speed-mate'); ?></th>
            <th><?php esc_html_e('Count', 'speed-mate'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        
        global $wpdb;

        // Cache key for each statistic
        $cache_key_revisions = 'speed_mate_revisions_count';
        $cache_key_trash_posts = 'speed_mate_trash_posts_count';
        $cache_key_spam_comments = 'speed_mate_spam_comments_count';
        $cache_key_trash_comments = 'speed_mate_trash_comments_count';

        // Try to get the cached values first
        $stats = [
            'revisions' => wp_cache_get($cache_key_revisions, 'speed_mate'),
            'trash_posts' => wp_cache_get($cache_key_trash_posts, 'speed_mate'),
            'spam_comments' => wp_cache_get($cache_key_spam_comments, 'speed_mate'),
            'trash_comments' => wp_cache_get($cache_key_trash_comments, 'speed_mate'),
        ];

        // If cache is empty (first time or cache expired), run the query and set the cache
        if ($stats['revisions'] === false) {
            $stats['revisions'] = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'revision'");
            wp_cache_set($cache_key_revisions, $stats['revisions'], 'speed_mate', 3600); // Cache for 1 hour
        }

        if ($stats['trash_posts'] === false) {
            $stats['trash_posts'] = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_status = 'trash'");
            wp_cache_set($cache_key_trash_posts, $stats['trash_posts'], 'speed_mate', 3600); // Cache for 1 hour
        }

        if ($stats['spam_comments'] === false) {
            $stats['spam_comments'] = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->comments WHERE comment_approved = 'spam'");
            wp_cache_set($cache_key_spam_comments, $stats['spam_comments'], 'speed_mate', 3600); // Cache for 1 hour
        }

        if ($stats['trash_comments'] === false) {
            $stats['trash_comments'] = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->comments WHERE comment_approved = 'trash'");
            wp_cache_set($cache_key_trash_comments, $stats['trash_comments'], 'speed_mate', 3600); // Cache for 1 hour
        }

        ?>
        <tr>
            <td><?php esc_html_e('Post/Page Revisions', 'speed-mate'); ?></td>
            <td><?php echo esc_html(number_format($stats['revisions'])); ?></td>
        </tr>
        <tr>
            <td><?php esc_html_e('Trashed Posts', 'speed-mate'); ?></td>
            <td><?php echo esc_html(number_format($stats['trash_posts'])); ?></td>
        </tr>
        <tr>
            <td><?php esc_html_e('Spam Comments', 'speed-mate'); ?></td>
            <td><?php echo esc_html(number_format($stats['spam_comments'])); ?></td>
        </tr>
        <tr>
            <td><?php esc_html_e('Trashed Comments', 'speed-mate'); ?></td>
            <td><?php echo esc_html(number_format($stats['trash_comments'])); ?></td>
        </tr>
    </tbody>
</table>