<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>

<h2><?php _e('Database Optimization Settings', 'site-fastify'); ?></h2>
<div class="notice notice-info">
    <p><?php _e('Optimize your WordPress database by cleaning up unnecessary data.', 'site-fastify'); ?></p>
</div>

<!-- Revisions Cleanup Section -->
<h3><?php _e('Post Revisions Cleanup', 'site-fastify'); ?></h3>
<table class="form-table">
    <tr valign="top">
        <th scope="row"><?php _e('Enable Post/Page Revisions Cleanup', 'site-fastify'); ?></th>
        <td>
            <input type="checkbox" 
                   name="wp_fastify_db_optimization_revisions_cleanup_enable" 
                   value="1" 
                   <?php checked(1, get_option('wp_fastify_db_optimization_revisions_cleanup_enable', 0)); ?> />
            <p class="description">
                <?php _e('Automatically clean up old post/page revisions from the database.', 'site-fastify'); ?>
            </p>
        </td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php _e('Cleanup Schedule', 'site-fastify'); ?></th>
        <td>
            <?php
            $schedule = get_option('wp_fastify_db_optimization_revisions_cleanup_schedule', 'weekly');
            ?>
            <select name="wp_fastify_db_optimization_revisions_cleanup_schedule">
                <option value="daily" <?php selected($schedule, 'daily'); ?>><?php _e('Daily', 'site-fastify'); ?></option>
                <option value="weekly" <?php selected($schedule, 'weekly'); ?>><?php _e('Weekly', 'site-fastify'); ?></option>
                <option value="monthly" <?php selected($schedule, 'monthly'); ?>><?php _e('Monthly', 'site-fastify'); ?></option>
            </select>
        </td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php _e('Revisions to Keep', 'site-fastify'); ?></th>
        <td>
            <input type="number" 
                   name="wp_fastify_db_optimization_revisions_cleanup_keep_count" 
                   value="<?php echo esc_attr(get_option('wp_fastify_db_optimization_revisions_cleanup_keep_count', 5)); ?>" 
                   min="0" />
            <p class="description">
                <?php _e('Number of recent revisions to keep for each post.', 'site-fastify'); ?>
            </p>
        </td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php _e('Manual Cleanup', 'site-fastify'); ?></th>
        <td>
            <button id="site-fastify-revisions-cleanup-btn" class="button button-secondary">
                <?php _e('Run Revisions Cleanup Now', 'site-fastify'); ?>
            </button>
            <div id="site-fastify-revisions-success-message" class="hidden"></div>
        </td>
    </tr>
</table>

<!-- Trash and Spam Cleanup Section -->
<h3><?php _e('Trash and Spam Cleanup', 'site-fastify'); ?></h3>
<table class="form-table">
    <tr valign="top">
        <th scope="row"><?php _e('Enable Trash/Spam Cleanup', 'site-fastify'); ?></th>
        <td>
            <input type="checkbox" 
                   name="wp_fastify_db_optimization_trash_spam_cleanup_enable" 
                   value="1" 
                   <?php checked(1, get_option('wp_fastify_db_optimization_trash_spam_cleanup_enable', 0)); ?> />
            <p class="description">
                <?php _e('Automatically clean up trashed posts and spam comments.', 'site-fastify'); ?>
            </p>
        </td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php _e('Cleanup Schedule', 'site-fastify'); ?></th>
        <td>
            <?php
            $schedule = get_option('wp_fastify_db_optimization_trash_spam_cleanup_schedule', 'weekly');
            ?>
            <select name="wp_fastify_db_optimization_trash_spam_cleanup_schedule">
                <option value="daily" <?php selected($schedule, 'daily'); ?>><?php _e('Daily', 'site-fastify'); ?></option>
                <option value="weekly" <?php selected($schedule, 'weekly'); ?>><?php _e('Weekly', 'site-fastify'); ?></option>
                <option value="monthly" <?php selected($schedule, 'monthly'); ?>><?php _e('Monthly', 'site-fastify'); ?></option>
            </select>
        </td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php _e('Manual Cleanup', 'site-fastify'); ?></th>
        <td>
            <button id="site-fastify-trash-spam-cleanup-btn" class="button button-secondary">
                <?php _e('Run Trash/Spam Cleanup Now', 'site-fastify'); ?>
            </button>
            <div id="site-fastify-trash-spam-success-message" class="hidden"></div>
        </td>
    </tr>
</table>

<!-- Database Statistics Section -->
<h3><?php _e('Database Statistics', 'site-fastify'); ?></h3>
<table class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
            <th><?php _e('Item', 'site-fastify'); ?></th>
            <th><?php _e('Count', 'site-fastify'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        global $wpdb;
        $stats = [
            'revisions' => $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'revision'"),
            'trash_posts' => $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_status = 'trash'"),
            'spam_comments' => $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->comments WHERE comment_approved = 'spam'"),
            'trash_comments' => $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->comments WHERE comment_approved = 'trash'")
        ];
        ?>
        <tr>
            <td><?php _e('Post/Page Revisions', 'site-fastify'); ?></td>
            <td><?php echo number_format($stats['revisions']); ?></td>
        </tr>
        <tr>
            <td><?php _e('Trashed Posts', 'site-fastify'); ?></td>
            <td><?php echo number_format($stats['trash_posts']); ?></td>
        </tr>
        <tr>
            <td><?php _e('Spam Comments', 'site-fastify'); ?></td>
            <td><?php echo number_format($stats['spam_comments']); ?></td>
        </tr>
        <tr>
            <td><?php _e('Trashed Comments', 'site-fastify'); ?></td>
            <td><?php echo number_format($stats['trash_comments']); ?></td>
        </tr>
    </tbody>
</table>