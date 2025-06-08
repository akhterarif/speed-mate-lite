<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>

<h2><?php esc_html_e('Asset Optimization Settings', 'speed-mate'); ?></h2>
<div class="notice notice-info">
    <p><?php esc_html_e('Optimize your website\'s assets to improve loading times and performance.', 'speed-mate'); ?></p>
</div>

<table class="form-table">
    <tr valign="top">
        <th scope="row"><?php esc_html_e('CSS/JS Minification', 'speed-mate'); ?></th>
        <td>
            <input type="checkbox" 
                   name="speed_mate_asset_optimization_enable_minification" 
                   value="1" 
                   <?php checked(1, get_option('speed_mate_asset_optimization_enable_minification', 0)); ?> />
            <p class="description">
                <?php esc_html_e('Minify CSS and JavaScript files to reduce their size by removing unnecessary characters.', 'speed-mate'); ?>
            </p>
        </td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php esc_html_e('Image Lazy Loading', 'speed-mate'); ?></th>
        <td>
            <input type="checkbox" 
                   name="speed_mate_asset_optimization_enable_image_lazy_loading" 
                   value="1" 
                   <?php checked(1, get_option('speed_mate_asset_optimization_enable_image_lazy_loading', 0)); ?> />
            <p class="description">
                <?php esc_html_e('Enable lazy loading for images to improve initial page load time.', 'speed-mate'); ?>
            </p>
        </td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php esc_html_e('Excluded Files', 'speed-mate'); ?></th>
        <td>
            <textarea name="speed_mate_asset_optimization_exclusions" 
                      rows="4" 
                      class="large-text code"><?php echo esc_textarea(get_option('speed_mate_asset_optimization_exclusions', '')); ?></textarea>
            <p class="description">
                <?php esc_html_e('Enter one URL per line to exclude files from optimization. Wildcards (*) are supported.', 'speed-mate'); ?>
            </p>
        </td>
    </tr>
</table>