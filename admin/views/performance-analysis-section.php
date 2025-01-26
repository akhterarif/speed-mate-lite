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
    <p>Run a performance analysis on your site to identify areas for improvement.</p>

    <h2>Google PageSpeed Insights</h2>
    <p>To use Google PageSpeed Insights, you need to provide a Google API key. Follow the instructions below to create a Google API key:</p>
    <ol>
        <li>Go to the <a href="https://console.developers.google.com/" target="_blank">Google Developers Console</a>.</li>
        <li>Create a new project or select an existing project.</li>
        <li>Navigate to the "Credentials" section.</li>
        <li>Click on "Create credentials" and select "API key".</li>
        <li>Copy the generated API key and paste it in the input field below.</li>
        <li>While you click on the <strong>Performance Analysis</strong> button, it will load data in the Page-insight section using the above API-KEY.</li>
    </ol>

        <label for="google-api-key"><?php _e('Google API Key', 'wp-fastify'); ?>:</label>
        <input type="text" id="wp-fastify-pa-google-api-key" name="wp_fastify_pa_google_api_key" value="<?php echo esc_attr(get_option('wp_fastify_pa_google_api_key')); ?>" class="" />


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


