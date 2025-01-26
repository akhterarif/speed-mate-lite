<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>

<h2><?php esc_html_e('Caching Settings', 'site-fastify'); ?></h2>
<table class="form-table">
    <tr valign="top">
        <th scope="row"><?php esc_html_e('Enable Page Caching', 'site-fastify'); ?></th>
        <td>
            <input type="checkbox" 
                   name="wp_fastify_caching_enable_cache" 
                   value="1" 
                   <?php checked(1, get_option('wp_fastify_caching_enable_cache', 0)); ?> />
            <p class="description">
                <?php esc_html_e('Enable caching to improve page load times.', 'site-fastify'); ?>
            </p>
        </td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php esc_html_e('Cache Duration', 'site-fastify'); ?></th>
        <td>
            <input type="number" 
                   name="wp_fastify_caching_cache_duration" 
                   value="<?php echo esc_attr(get_option('wp_fastify_caching_cache_duration', 31536000)); ?>" 
                   min="0" 
                   step="1" />
            <p class="description">
                <?php esc_html_e('Specify how long to cache files (in seconds). Default is 31536000 (1 year).', 'site-fastify'); ?>
            </p>
        </td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php esc_html_e('Static Asset Caching', 'site-fastify'); ?></th>
        <td>
            <input type="checkbox" 
                   name="wp_fastify_caching_enable_static_caching" 
                   value="1" 
                   <?php checked(1, get_option('wp_fastify_caching_enable_static_caching', 0)); ?> />
            <p class="description">
                <?php esc_html_e('Add caching rules to .htaccess for static assets (CSS, JS, images, etc).', 'site-fastify'); ?>
            </p>
        </td>
    </tr>
</table>

<h3><?php esc_html_e('Server Configuration', 'site-fastify'); ?></h3>
<div class="site-fastify-server-config">
    <p><?php esc_html_e('If you are using Nginx, add the following to your server block:', 'site-fastify'); ?></p>
    <pre class="code-block">
# SiteFastify Static Asset Caching
location ~* \.(css|js|jpg|jpeg|png|gif|webp|svg|ico|woff|woff2|ttf|otf|eot|mp4)$ {
    expires 1y;
    add_header Cache-Control "public";
}
    </pre>
</div>