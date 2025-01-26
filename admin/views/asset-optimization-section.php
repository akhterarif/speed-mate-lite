<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>

<h2><?php esc_html_e('Asset Optimization Settings', 'site-fastify'); ?></h2>
<div class="notice notice-info">
    <p><?php esc_html_e('Optimize your website\'s assets to improve loading times and performance.', 'site-fastify'); ?></p>
</div>

<table class="form-table">
    <tr valign="top">
        <th scope="row"><?php esc_html_e('CSS/JS Minification', 'site-fastify'); ?></th>
        <td>
            <input type="checkbox" 
                   name="wp_fastify_asset_optimization_enable_minification" 
                   value="1" 
                   <?php checked(1, get_option('wp_fastify_asset_optimization_enable_minification', 0)); ?> />
            <p class="description">
                <?php esc_html_e('Minify CSS and JavaScript files to reduce their size by removing unnecessary characters.', 'site-fastify'); ?>
            </p>
        </td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php esc_html_e('HTML Minification', 'site-fastify'); ?></th>
        <td>
            <input type="checkbox" 
                   name="wp_fastify_asset_optimization_enable_html_minification" 
                   value="1" 
                   <?php checked(1, get_option('wp_fastify_asset_optimization_enable_html_minification', 0)); ?> />
            <p class="description">
                <?php esc_html_e('Minify HTML output to reduce page size and improve load times.', 'site-fastify'); ?>
            </p>
        </td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php esc_html_e('Image Lazy Loading', 'site-fastify'); ?></th>
        <td>
            <input type="checkbox" 
                   name="wp_fastify_asset_optimization_enable_image_lazy_loading" 
                   value="1" 
                   <?php checked(1, get_option('wp_fastify_asset_optimization_enable_image_lazy_loading', 0)); ?> />
            <p class="description">
                <?php esc_html_e('Enable lazy loading for images to improve initial page load time.', 'site-fastify'); ?>
            </p>
        </td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php esc_html_e('Excluded Files', 'site-fastify'); ?></th>
        <td>
            <textarea name="wp_fastify_asset_optimization_exclusions" 
                      rows="4" 
                      class="large-text code"><?php echo esc_textarea(get_option('wp_fastify_asset_optimization_exclusions', '')); ?></textarea>
            <p class="description">
                <?php esc_html_e('Enter one URL per line to exclude files from optimization. Wildcards (*) are supported.', 'site-fastify'); ?>
            </p>
        </td>
    </tr>
</table>