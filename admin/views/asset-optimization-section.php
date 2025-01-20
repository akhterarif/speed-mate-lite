<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>

<h2><?php _e('Asset Optimization Settings', 'wp-fastify'); ?></h2>
<div class="notice notice-info">
    <p><?php _e('Optimize your website\'s assets to improve loading times and performance.', 'wp-fastify'); ?></p>
</div>

<table class="form-table">
    <tr valign="top">
        <th scope="row"><?php _e('CSS/JS Minification', 'wp-fastify'); ?></th>
        <td>
            <input type="checkbox" 
                   name="wp_fastify_asset_optimization_enable_minification" 
                   value="1" 
                   <?php checked(1, get_option('wp_fastify_asset_optimization_enable_minification', 0)); ?> />
            <p class="description">
                <?php _e('Minify CSS and JavaScript files to reduce their size by removing unnecessary characters.', 'wp-fastify'); ?>
            </p>
        </td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php _e('HTML Minification', 'wp-fastify'); ?></th>
        <td>
            <input type="checkbox" 
                   name="wp_fastify_asset_optimization_enable_html_minification" 
                   value="1" 
                   <?php checked(1, get_option('wp_fastify_asset_optimization_enable_html_minification', 0)); ?> />
            <p class="description">
                <?php _e('Minify HTML output to reduce page size and improve load times.', 'wp-fastify'); ?>
            </p>
        </td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php _e('Image Lazy Loading', 'wp-fastify'); ?></th>
        <td>
            <input type="checkbox" 
                   name="wp_fastify_asset_optimization_enable_image_lazy_loading" 
                   value="1" 
                   <?php checked(1, get_option('wp_fastify_asset_optimization_enable_image_lazy_loading', 0)); ?> />
            <p class="description">
                <?php _e('Enable lazy loading for images to improve initial page load time.', 'wp-fastify'); ?>
            </p>
        </td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php _e('Excluded Files', 'wp-fastify'); ?></th>
        <td>
            <textarea name="wp_fastify_asset_optimization_exclusions" 
                      rows="4" 
                      class="large-text code"><?php echo esc_textarea(get_option('wp_fastify_asset_optimization_exclusions', '')); ?></textarea>
            <p class="description">
                <?php _e('Enter one URL per line to exclude files from optimization. Wildcards (*) are supported.', 'wp-fastify'); ?>
            </p>
        </td>
    </tr>
</table>

<h3><?php _e('Advanced Settings', 'wp-fastify'); ?></h3>
<table class="form-table">
    <tr valign="top">
        <th scope="row"><?php _e('Combine CSS Files', 'wp-fastify'); ?></th>
        <td>
            <input type="checkbox" 
                   name="wp_fastify_asset_optimization_combine_css" 
                   value="1" 
                   <?php checked(1, get_option('wp_fastify_asset_optimization_combine_css', 0)); ?> />
            <p class="description">
                <?php _e('Combine multiple CSS files into one to reduce HTTP requests.', 'wp-fastify'); ?>
            </p>
        </td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php _e('Combine JavaScript Files', 'wp-fastify'); ?></th>
        <td>
            <input type="checkbox" 
                   name="wp_fastify_asset_optimization_combine_js" 
                   value="1" 
                   <?php checked(1, get_option('wp_fastify_asset_optimization_combine_js', 0)); ?> />
            <p class="description">
                <?php _e('Combine multiple JavaScript files into one to reduce HTTP requests.', 'wp-fastify'); ?>
            </p>
        </td>
    </tr>
</table>