<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$metrics = [
    'Total Scripts' => count(wp_scripts()->queue),
    'Total Styles' => count(wp_styles()->queue),
    'Total Requests' => count(wp_scripts()->queue) + count(wp_styles()->queue),
    'Page Load Time (seconds)' => timer_stop(),
    'Estimated Page Size' => round(ob_get_length() / 1024, 2) . ' KB',
];
?>

<h3><?php _e('Page Health', 'wp-fastify'); ?></h3>

<table class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
            <th><?php _e('Item', 'wp-fastify'); ?></th>
            <th><?php _e('Count', 'wp-fastify'); ?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><?php _e('Total Scripts', 'wp-fastify'); ?></td>
            <td><?php echo $metrics['Total Scripts']; ?></td>
        </tr>
        <tr>
            <td><?php _e('Total Styles', 'wp-fastify'); ?></td>
            <td><?php echo $metrics['Total Styles']; ?></td>
        </tr>
        <tr>
            <td><?php _e('Total Requests', 'wp-fastify'); ?></td>
            <td><?php echo $metrics['Total Requests']; ?></td>
        </tr>
        <tr>
            <td><?php _e('Page Load Time (seconds)', 'wp-fastify'); ?></td>
            <td><?php echo $metrics['Page Load Time (seconds)']; ?></td>
        </tr>
        <tr>
            <td><?php _e('Estimated Page Size', 'wp-fastify'); ?></td>
            <td><?php echo $metrics['Estimated Page Size']; ?></td>
        </tr>
    </tbody>
</table>

<div class="wrap" id="performance-analysis-section">
    <h1>Performance Analysis</h1>
    <div id="performance-results">
        <h2>Page Speed Score: <span id="page-speed-score">--</span></h2>
        <table class="widefat">
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="performance-metrics">
                <!-- Dynamic rows will be injected here -->
            </tbody>
        </table>
        <h3>Recommendations</h3>
        <ul id="performance-recommendations">
            <!-- Recommendations go here -->
        </ul>
    </div>
</div>


