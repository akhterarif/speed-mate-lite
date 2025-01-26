<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Check for cleanup success messages
if (isset($_GET['cleanup']) && $_GET['cleanup'] === 'success') {
    echo '<div class="notice notice-success is-dismissible"><p>Cleanup executed successfully.</p></div>';
}

// Prepare tab URLs
$tabs = [
    'caching' => [
        'label' => __('Caching', 'site-fastify'),
        'button' => __('Save Caching Settings', 'site-fastify')
    ],
    'asset_optimization' => [
        'label' => __('Asset Optimization', 'site-fastify'),
        'button' => __('Save Optimization Settings', 'site-fastify')
    ],
    'db_optimization' => [
        'label' => __('Database Optimization', 'site-fastify'),
        'button' => __('Save Database Settings', 'site-fastify')
    ],
    'performance_analysis' => [
        'label' => __('Performance Analysis', 'site-fastify'),
        'button' => __('Performance Analysis', 'site-fastify')
    ]
];

$current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'caching';
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <h2 class="nav-tab-wrapper">
        <?php foreach ($tabs as $tab_key => $tab_data) : ?>
            <a href="?page=site-fastify&tab=<?php echo esc_attr($tab_key); ?>" 
               class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html($tab_data['label']); ?>
            </a>
        <?php endforeach; ?>
    </h2>
    <?php if ($current_tab !== 'performance_analysis') : ?>
    <div class="notice notice-success settings-success hidden">
        <p><?php esc_html_e('Settings saved successfully!', 'site-fastify'); ?></p>
    </div>
    <?php endif; ?>

    <div class="notice notice-error settings-error hidden">
        <p><?php esc_html_e('Error saving settings. Please try again.', 'site-fastify'); ?></p>
    </div>

    <form id="site-fastify-settings-form" method="post" data-tab="<?php echo esc_attr($current_tab); ?>">
        <?php
        wp_nonce_field('site_fastify_' . $current_tab . '_nonce', 'site_fastify_nonce');

        // Load the appropriate section template
        switch ($current_tab) {
            case 'caching':
                require_once plugin_dir_path(__FILE__) . 'caching-section.php';
                break;

            case 'asset_optimization':
                require_once plugin_dir_path(__FILE__) . 'asset-optimization-section.php';
                break;

            case 'db_optimization':
                require_once plugin_dir_path(__FILE__) . 'db-optimization-section.php';
                break;

            case 'performance_analysis':
                require_once plugin_dir_path(__FILE__) . 'performance-analysis-section.php';
                break;
        }
        ?>

        <div class="submit-wrapper">
            <button type="submit" class="button button-primary" id="site-fastify-save-settings">
                <?php echo esc_html($tabs[$current_tab]['button']); ?>
            </button>
            <span class="spinner"></span>
        </div>
    </form>
</div>

<style>
.submit-wrapper {
    margin-top: 20px;
    position: relative;
}

.submit-wrapper .spinner {
    float: none;
    margin-top: 0;
    margin-left: 10px;
    vertical-align: middle;
}

.settings-success,
.settings-error {
    display: none;
}

.settings-success.visible,
.settings-error.visible {
    display: block;
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    const form = $('#site-fastify-settings-form');
    const successNotice = $('.settings-success');
    const errorNotice = $('.settings-error');
    const submitButton = $('#site-fastify-save-settings');
    const spinner = $('.spinner');

    form.on('submit', function(e) {
        e.preventDefault();
        
        // Hide any existing notices
        successNotice.removeClass('visible');
        errorNotice.removeClass('visible');
        
        // Disable submit button and show spinner
        submitButton.prop('disabled', true);
        spinner.addClass('is-active');

        // Collect form data
        const formData = new FormData(this);
        formData.append('action', 'site_fastify_save_settings');
        formData.append('tab', form.data('tab'));

        // Make AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    successNotice.addClass('visible');
                    
                    // If there are any specific actions to take based on the settings
                    if (response.data.reload) {
                        location.reload();
                    }
                    
                    // Handle any tab-specific success actions
                    switch(form.data('tab')) {
                        case 'caching':
                            if (response.data.htaccess_updated) {
                                // Maybe show additional success message
                            }
                            break;
                        case 'asset_optimization':
                            if (response.data.cache_cleared) {
                                // Maybe show additional success message
                            }
                            break;
                        case 'performance_analysis':
                            let data = response.data;
                            $('#page-speed-score').text(data.score);
                            let metricsHtml = '';
                            data.metrics.forEach(metric => {
                                metricsHtml += `
                                    <tr>
                                        <td>${metric.name}</td>
                                        <td>${metric.value}</td>
                                        <td>${metric.status}</td>
                                    </tr>
                                `;
                            });
                            $('#performance-metrics').html(metricsHtml);
                            $('#performance-recommendations').html(data.recommendations.map(r => `<li>${r}</li>`).join(''));
                            $('#performance-results').show();

                            break;
            
                        
                    }
                } else {
                    errorNotice.find('p').text(response.data.message || 'Error saving settings.');
                    errorNotice.addClass('visible');
                }
            },
            error: function(response) {
                errorNotice.addClass('visible');                
            },
            complete: function(response) {
                (response.success) && $('#site-fastify-pa-google-api-key').addClass('error');

                // Re-enable submit button and hide spinner
                submitButton.prop('disabled', false);
                spinner.removeClass('is-active');
                
                // Scroll to the notice
                $('html, body').animate({
                    scrollTop: form.offset().top - 50
                }, 500);
            }
        });
    });

    // Handle tab-specific UI interactions
    switch(form.data('tab')) {
        case 'caching':
            // Add any caching-specific UI handlers
            $('[name="site_fastify_caching_enable_cache"]').on('change', function() {
                // Handle dependencies
            });
            break;
            
        case 'asset_optimization':
            // Add any asset optimization-specific UI handlers
            $('[name="site_fastify_asset_optimization_enable_minification"]').on('change', function() {
                // Handle dependencies
            });
            break;
            
        case 'db_optimization':
            // The existing cleanup button handlers remain unchanged
            break;

        case 'performance_analysis':
            
            // Add any performance analysis-specific UI handlers
            // $('#page-speed-score').text(data.score);
            //         let metricsHtml = '';
            //         data.metrics.forEach(metric => {
            //             metricsHtml += `
            //                 <tr>
            //                     <td>${metric.name}</td>
            //                     <td>${metric.value}</td>
            //                     <td>${metric.status}</td>
            //                 </tr>
            //             `;
            //         });
            //         $('#performance-metrics').html(metricsHtml);
            //         $('#performance-recommendations').html(data.recommendations.map(r => `<li>${r}</li>`).join(''));
            //         $('#performance-results').show();
            break;
    }
});
</script>