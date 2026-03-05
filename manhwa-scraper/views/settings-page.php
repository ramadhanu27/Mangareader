<?php
/**
 * Settings Page View
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap mws-wrap">
    <h1 class="mws-title">
        <span class="dashicons dashicons-admin-settings"></span>
        <?php esc_html_e('Scraper Settings', 'manhwa-scraper'); ?>
    </h1>
    
    <?php settings_errors('mws_settings'); ?>
    
    <div class="mws-settings-page">
        <form method="post" action="">
            <?php wp_nonce_field('mws_save_settings', 'mws_settings_nonce'); ?>
            
            <!-- Rate Limiting -->
            <div class="mws-card">
                <h2><?php esc_html_e('Rate Limiting', 'manhwa-scraper'); ?></h2>
                <p class="description">
                    <?php esc_html_e('Configure rate limiting to avoid getting blocked by source websites.', 'manhwa-scraper'); ?>
                </p>
                
                <table class="form-table">
                    <tr>
                        <th>
                            <label for="rate_limit"><?php esc_html_e('Requests per Minute', 'manhwa-scraper'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="rate_limit" name="rate_limit" 
                                   value="<?php echo esc_attr($settings['rate_limit']); ?>" 
                                   min="1" max="60" class="small-text">
                            <p class="description">
                                <?php esc_html_e('Maximum number of requests per minute per domain. Lower values are safer.', 'manhwa-scraper'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="delay_between_requests"><?php esc_html_e('Delay Between Requests (ms)', 'manhwa-scraper'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="delay_between_requests" name="delay_between_requests" 
                                   value="<?php echo esc_attr($settings['delay_between_requests']); ?>" 
                                   min="100" max="10000" class="small-text">
                            <p class="description">
                                <?php esc_html_e('Minimum delay between requests in milliseconds. Recommended: 1000-3000ms.', 'manhwa-scraper'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="concurrent_downloads"><?php esc_html_e('Concurrent Downloads', 'manhwa-scraper'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="concurrent_downloads" name="concurrent_downloads" 
                                   value="<?php echo esc_attr(get_option('mws_concurrent_downloads', 5)); ?>" 
                                   min="1" max="100" class="small-text">
                            <p class="description">
                                <?php esc_html_e('Number of images to download simultaneously. Higher = faster but uses more server resources. Recommended: 10-30. Max: 100.', 'manhwa-scraper'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="plugin_language"><?php esc_html_e('Language', 'manhwa-scraper'); ?></label>
                        </th>
                        <td>
                            <select id="plugin_language" name="plugin_language">
                                <option value="en_US" <?php selected(get_option('mws_plugin_language', 'en_US'), 'en_US'); ?>>
                                    English
                                </option>
                                <option value="id_ID" <?php selected(get_option('mws_plugin_language', 'en_US'), 'id_ID'); ?>>
                                    Bahasa Indonesia
                                </option>
                            </select>
                            <p class="description">
                                <?php esc_html_e('Select the language for the plugin interface.', 'manhwa-scraper'); ?>
                                <br>
                                <strong><?php esc_html_e('Note: Page will reload after saving to apply language changes.', 'manhwa-scraper'); ?></strong>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Auto Update -->
            <div class="mws-card">
                <h2><?php esc_html_e('Auto Update', 'manhwa-scraper'); ?></h2>
                <p class="description">
                    <?php esc_html_e('Configure automatic chapter update checking via WP Cron.', 'manhwa-scraper'); ?>
                </p>
                
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('Enable Auto Update', 'manhwa-scraper'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" id="enable_auto_update" name="enable_auto_update" value="1"
                                    <?php checked(get_option('mws_auto_update_enabled', false)); ?>>
                                <?php esc_html_e('Activate automatic chapter checking', 'manhwa-scraper'); ?>
                            </label>
                            <p class="description">
                                <?php 
                                $next = MWS_Cron_Handler::get_next_scheduled();
                                if ($next) {
                                    echo '<span style="color: green; font-weight: bold;">✓ Active</span> - ';
                                    printf(
                                        esc_html__('Next check: %s', 'manhwa-scraper'),
                                        '<strong>' . esc_html(date('Y-m-d H:i:s', $next)) . '</strong>'
                                    );
                                    echo ' <small>(' . human_time_diff($next, current_time('timestamp')) . ' from now)</small>';
                                } else {
                                    echo '<span style="color: orange; font-weight: bold;">○ Inactive</span> - ';
                                    esc_html_e('Check the box and save to activate', 'manhwa-scraper');
                                }
                                ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="cron_schedule"><?php esc_html_e('Update Schedule', 'manhwa-scraper'); ?></label>
                        </th>
                        <td>
                            <select id="cron_schedule" name="cron_schedule">
                                <?php foreach ($schedules as $key => $label): ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($settings['cron_schedule'], $key); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php esc_html_e('How often to check for new chapters', 'manhwa-scraper'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="auto_download_chapters"><?php esc_html_e('Auto Download Chapters', 'manhwa-scraper'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="auto_download_chapters" name="auto_download_chapters" value="1"
                                    <?php checked(get_option('mws_auto_download_chapters', false)); ?>>
                                <?php esc_html_e('Automatically download images when new chapters are found', 'manhwa-scraper'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('When enabled, cron job will automatically scrape and download chapter images to your server.', 'manhwa-scraper'); ?>
                                <br>
                                <strong style="color: #dc3232;"><?php esc_html_e('Warning: This may use significant bandwidth and storage space!', 'manhwa-scraper'); ?></strong>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="auto_scrape_chapter_images"><?php esc_html_e('Auto Scrape Chapter Images', 'manhwa-scraper'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="auto_scrape_chapter_images" name="auto_scrape_chapter_images" value="1"
                                    <?php checked(get_option('mws_auto_scrape_chapter_images', false)); ?>>
                                <?php esc_html_e('Automatically scrape chapter image URLs (external) when new chapters are found', 'manhwa-scraper'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('When enabled, cron job will scrape chapter image URLs from source website and save them (without downloading to local server).', 'manhwa-scraper'); ?>
                                <br>
                                <strong style="color: #0073aa;"><?php esc_html_e('Note: Images will use external URLs. Enable "Auto Download" above to save images locally.', 'manhwa-scraper'); ?></strong>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Auto Pilot -->
            <div class="mws-card">
                <h2><?php esc_html_e('Auto Pilot', 'manhwa-scraper'); ?></h2>
                <p class="description">
                    <?php esc_html_e('Automated discovery and maintenance features.', 'manhwa-scraper'); ?>
                </p>
                
                <table class="form-table">
                    <tr>
                        <th>
                            <label for="auto_discovery_enabled"><?php esc_html_e('Auto Discovery', 'manhwa-scraper'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="auto_discovery_enabled" name="auto_discovery_enabled" value="1"
                                    <?php checked(get_option('mws_auto_discovery_enabled', false)); ?>>
                                <?php esc_html_e('Enable automatic manhwa discovery', 'manhwa-scraper'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('Automatically discover and import new manhwa from enabled sources daily.', 'manhwa-scraper'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="discovery_max_per_source"><?php esc_html_e('Max Per Source', 'manhwa-scraper'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="discovery_max_per_source" name="discovery_max_per_source" 
                                   value="<?php echo esc_attr(get_option('mws_discovery_max_per_source', 10)); ?>" 
                                   min="1" max="50" class="small-text">
                            <p class="description">
                                <?php esc_html_e('Maximum number of new manhwa to import per source per day. Recommended: 5-10.', 'manhwa-scraper'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label><?php esc_html_e('Test Discovery', 'manhwa-scraper'); ?></label>
                        </th>
                        <td>
                            <button type="button" id="mws-test-discovery-btn" class="button button-secondary">
                                <span class="dashicons dashicons-search" style="margin-top: 4px;"></span>
                                <?php esc_html_e('Run Discovery Now', 'manhwa-scraper'); ?>
                            </button>
                            <span id="mws-test-discovery-spinner" class="spinner"></span>
                            <p class="description">
                                <?php esc_html_e('Manually run the discovery process to test. Will import new manhwa based on settings above.', 'manhwa-scraper'); ?>
                            </p>
                            <div id="mws-discovery-result" style="display: none; margin-top: 10px; padding: 10px; background: #f0f0f0; border-radius: 4px;"></div>
                            
                            <?php 
                            $last_discovery = get_option('mws_last_discovery_time');
                            $last_stats = get_option('mws_last_discovery_stats');
                            if ($last_discovery): 
                            ?>
                            <div style="margin-top: 10px; padding: 10px; background: #e7f3ff; border: 1px solid #2271b1; border-radius: 4px; font-size: 12px;">
                                <strong><?php esc_html_e('Last Run:', 'manhwa-scraper'); ?></strong> 
                                <?php echo esc_html(human_time_diff(strtotime($last_discovery), current_time('timestamp')) . ' ago'); ?>
                                <?php if ($last_stats): ?>
                                <br>
                                <strong><?php esc_html_e('Results:', 'manhwa-scraper'); ?></strong>
                                <?php 
                                echo sprintf(
                                    __('Sources: %d | Found: %d | Imported: %d | Exists: %d | Errors: %d', 'manhwa-scraper'),
                                    $last_stats['sources_checked'] ?? 0,
                                    $last_stats['manhwa_found'] ?? 0,
                                    $last_stats['new_imported'] ?? 0,
                                    $last_stats['already_exists'] ?? 0,
                                    $last_stats['errors'] ?? 0
                                );
                                ?>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="auto_cleanup_enabled"><?php esc_html_e('Auto Cleanup', 'manhwa-scraper'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="auto_cleanup_enabled" name="auto_cleanup_enabled" value="1"
                                    <?php checked(get_option('mws_auto_cleanup_enabled', false)); ?>>
                                <?php esc_html_e('Enable automatic cleanup', 'manhwa-scraper'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('Automatically clean old logs, orphan data, and optimize database weekly.', 'manhwa-scraper'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="cleanup_logs_days"><?php esc_html_e('Keep Logs (Days)', 'manhwa-scraper'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="cleanup_logs_days" name="cleanup_logs_days" 
                                   value="<?php echo esc_attr(get_option('mws_cleanup_logs_days', 30)); ?>" 
                                   min="7" max="365" class="small-text">
                            <p class="description">
                                <?php esc_html_e('Number of days to keep logs. Older logs will be deleted. Recommended: 30 days.', 'manhwa-scraper'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="cleanup_temp_images_days"><?php esc_html_e('Keep Temp Images (Days)', 'manhwa-scraper'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="cleanup_temp_images_days" name="cleanup_temp_images_days" 
                                   value="<?php echo esc_attr(get_option('mws_cleanup_temp_images_days', 7)); ?>" 
                                   min="1" max="30" class="small-text">
                            <p class="description">
                                <?php esc_html_e('Number of days to keep temporary images. Recommended: 7 days.', 'manhwa-scraper'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="auto_image_scraper_enabled"><?php esc_html_e('Auto Image Scraper', 'manhwa-scraper'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="auto_image_scraper_enabled" name="auto_image_scraper_enabled" value="1"
                                    <?php checked(get_option('mws_auto_image_scraper_enabled', false)); ?>>
                                <?php esc_html_e('Enable automatic image scraping', 'manhwa-scraper'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('Automatically scrape chapter images for manhwa that only have metadata.', 'manhwa-scraper'); ?>
                                <br>
                                <strong style="color: #0073aa;"><?php esc_html_e('Perfect for filling missing chapter images!', 'manhwa-scraper'); ?></strong>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="image_scraper_interval"><?php esc_html_e('Scraper Interval', 'manhwa-scraper'); ?></label>
                        </th>
                        <td>
                            <select id="image_scraper_interval" name="image_scraper_interval">
                                <option value="hourly" <?php selected(get_option('mws_image_scraper_interval', 'sixhourly'), 'hourly'); ?>><?php esc_html_e('Every Hour', 'manhwa-scraper'); ?></option>
                                <option value="twohourly" <?php selected(get_option('mws_image_scraper_interval', 'sixhourly'), 'twohourly'); ?>><?php esc_html_e('Every 2 Hours', 'manhwa-scraper'); ?></option>
                                <option value="fourhourly" <?php selected(get_option('mws_image_scraper_interval', 'sixhourly'), 'fourhourly'); ?>><?php esc_html_e('Every 4 Hours', 'manhwa-scraper'); ?></option>
                                <option value="sixhourly" <?php selected(get_option('mws_image_scraper_interval', 'sixhourly'), 'sixhourly'); ?>><?php esc_html_e('Every 6 Hours', 'manhwa-scraper'); ?></option>
                                <option value="twicedaily" <?php selected(get_option('mws_image_scraper_interval', 'sixhourly'), 'twicedaily'); ?>><?php esc_html_e('Twice Daily (12 Hours)', 'manhwa-scraper'); ?></option>
                                <option value="daily" <?php selected(get_option('mws_image_scraper_interval', 'sixhourly'), 'daily'); ?>><?php esc_html_e('Daily (24 Hours)', 'manhwa-scraper'); ?></option>
                            </select>
                            <p class="description">
                                <?php esc_html_e('How often should the auto image scraper run.', 'manhwa-scraper'); ?>
                                <?php 
                                $next_image_scraper = wp_next_scheduled('mws_auto_image_scraper_cron');
                                if ($next_image_scraper) {
                                    echo '<br><span style="color: green; font-weight: bold;">✓ ' . esc_html__('Scheduled', 'manhwa-scraper') . '</span> - ';
                                    printf(
                                        esc_html__('Next run: %s', 'manhwa-scraper'),
                                        '<strong>' . esc_html(wp_date('Y-m-d H:i:s', $next_image_scraper)) . '</strong>'
                                    );
                                    $time_diff = $next_image_scraper - current_time('timestamp');
                                    if ($time_diff > 0) {
                                        echo ' <small>(' . human_time_diff(current_time('timestamp'), $next_image_scraper) . ' ' . esc_html__('from now', 'manhwa-scraper') . ')</small>';
                                    }
                                } else {
                                    echo '<br><span style="color: orange;">○ ' . esc_html__('Not scheduled', 'manhwa-scraper') . '</span>';
                                }
                                ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="image_scraper_batch_size"><?php esc_html_e('Image Scraper Batch', 'manhwa-scraper'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="image_scraper_batch_size" name="image_scraper_batch_size" 
                                   value="<?php echo esc_attr(get_option('mws_image_scraper_batch_size', 10)); ?>" 
                                   min="1" max="50" class="small-text">
                            <p class="description">
                                <?php esc_html_e('Number of manhwa to process per run. Recommended: 10-20.', 'manhwa-scraper'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="auto_scraper_download_local"><?php esc_html_e('Download Images to Local', 'manhwa-scraper'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="auto_scraper_download_local" name="auto_scraper_download_local" value="1"
                                    <?php checked(get_option('mws_auto_scraper_download_local', false)); ?>>
                                <?php esc_html_e('Download images to local server instead of using external URLs', 'manhwa-scraper'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('When enabled, images will be downloaded to your server.', 'manhwa-scraper'); ?>
                                <br>
                                <strong style="color: #d63638;"><?php esc_html_e('Warning: This requires more disk space and takes longer to process!', 'manhwa-scraper'); ?></strong>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label><?php esc_html_e('Manual Test', 'manhwa-scraper'); ?></label>
                        </th>
                        <td>
                            <button type="button" id="test-image-scraper" class="button button-secondary">
                                <span class="dashicons dashicons-images-alt2"></span>
                                <?php esc_html_e('Run Image Scraper Now', 'manhwa-scraper'); ?>
                            </button>
                            <input type="number" id="test-batch-size" value="5" min="1" max="10" class="small-text" style="margin-left: 10px;">
                            <span style="margin-left: 5px;"><?php esc_html_e('manhwa', 'manhwa-scraper'); ?></span>
                            
                            <div id="image-scraper-progress" style="display: none; margin-top: 15px; padding: 15px; background: #f0f0f1; border-left: 4px solid #2271b1; border-radius: 4px;">
                                <p style="margin: 0 0 10px 0;">
                                    <strong><?php esc_html_e('Processing...', 'manhwa-scraper'); ?></strong>
                                </p>
                                <div class="mws-progress-bar" style="height: 20px; background: #fff; border-radius: 10px; overflow: hidden;">
                                    <div id="image-scraper-bar" style="height: 100%; width: 0%; background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); transition: width 0.3s;"></div>
                                </div>
                                <p id="image-scraper-status" style="margin: 10px 0 0 0; font-size: 12px; color: #666;"></p>
                            </div>
                            
                            <div id="image-scraper-result" style="display: none; margin-top: 15px;"></div>
                            
                            <p class="description">
                                <?php esc_html_e('Test the image scraper manually. This will process a few manhwa with empty images.', 'manhwa-scraper'); ?>
                            </p>
                            
                            <script>
                            jQuery(document).ready(function($) {
                                $('#test-image-scraper').on('click', function() {
                                    var button = $(this);
                                    var batchSize = parseInt($('#test-batch-size').val());
                                    var progress = $('#image-scraper-progress');
                                    var result = $('#image-scraper-result');
                                    var bar = $('#image-scraper-bar');
                                    var status = $('#image-scraper-status');
                                    
                                    if (batchSize < 1 || batchSize > 10) {
                                        alert('<?php esc_html_e('Batch size must be between 1-10', 'manhwa-scraper'); ?>');
                                        return;
                                    }
                                    
                                    button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> <?php esc_html_e('Processing...', 'manhwa-scraper'); ?>');
                                    progress.show();
                                    result.hide();
                                    bar.css('width', '0%');
                                    status.text('<?php esc_html_e('Starting image scraper...', 'manhwa-scraper'); ?>');
                                    
                                    // Simulate progress
                                    var progressInterval = setInterval(function() {
                                        var currentWidth = parseInt(bar.css('width'));
                                        var maxWidth = progress.width();
                                        if (currentWidth < maxWidth * 0.9) {
                                            bar.css('width', (currentWidth + maxWidth * 0.05) + 'px');
                                        }
                                    }, 500);
                                    
                                    $.ajax({
                                        url: ajaxurl,
                                        type: 'POST',
                                        data: {
                                            action: 'mws_trigger_image_scraper',
                                            nonce: '<?php echo wp_create_nonce('mws_nonce'); ?>',
                                            batch_size: batchSize
                                        },
                                        success: function(response) {
                                            clearInterval(progressInterval);
                                            bar.css('width', '100%');
                                            
                                            if (response.success) {
                                                var data = response.data;
                                                status.html('<span style="color: #46b450;">✓</span> ' + data.message);
                                                
                                                var html = '<div style="padding: 15px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px;">';
                                                html += '<h3 style="margin-top: 0;"><?php esc_html_e('Results:', 'manhwa-scraper'); ?></h3>';
                                                html += '<table class="widefat" style="margin-bottom: 15px;">';
                                                html += '<tr><th><?php esc_html_e('Manhwa Checked', 'manhwa-scraper'); ?></th><td><strong>' + data.result.total_checked + '</strong></td></tr>';
                                                html += '<tr><th><?php esc_html_e('Chapters Scraped', 'manhwa-scraper'); ?></th><td><strong style="color: #2271b1;">' + data.result.chapters_scraped + '</strong></td></tr>';
                                                html += '<tr><th><?php esc_html_e('Images Found', 'manhwa-scraper'); ?></th><td><strong style="color: #46b450;">' + data.result.images_found + '</strong></td></tr>';
                                                html += '<tr><th><?php esc_html_e('Errors', 'manhwa-scraper'); ?></th><td><strong style="color: ' + (data.result.errors > 0 ? '#dc3232' : '#666') + ';">' + data.result.errors + '</strong></td></tr>';
                                                html += '</table>';
                                                
                                                html += '<h4><?php esc_html_e('Overall Progress:', 'manhwa-scraper'); ?></h4>';
                                                html += '<table class="widefat">';
                                                html += '<tr><th><?php esc_html_e('Total Manhwa', 'manhwa-scraper'); ?></th><td>' + data.stats_after.total_manhwa + '</td></tr>';
                                                html += '<tr><th><?php esc_html_e('With Images', 'manhwa-scraper'); ?></th><td><strong style="color: #46b450;">' + data.stats_after.manhwa_with_images + '</strong> <span style="color: #2271b1;">(+' + data.improvement.manhwa_filled + ')</span></td></tr>';
                                                html += '<tr><th><?php esc_html_e('Still Empty', 'manhwa-scraper'); ?></th><td><strong style="color: #dc3232;">' + data.stats_after.manhwa_with_empty_images + '</strong></td></tr>';
                                                html += '<tr><th><?php esc_html_e('Completion Rate', 'manhwa-scraper'); ?></th><td><strong>' + data.stats_after.completion_rate + '%</strong> <span style="color: #2271b1;">(+' + data.improvement.completion_rate + ')</span></td></tr>';
                                                html += '</table>';
                                                
                                                // Add debug info if no manhwa found
                                                if (data.result.total_checked === 0 && data.debug) {
                                                    html += '<div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">';
                                                    html += '<h4 style="margin-top: 0;"><?php esc_html_e('Debug Info:', 'manhwa-scraper'); ?></h4>';
                                                    html += '<p style="margin: 5px 0;"><strong><?php esc_html_e('Total Manhwa in DB:', 'manhwa-scraper'); ?></strong> ' + data.debug.total_manhwa_in_db + '</p>';
                                                    html += '<p style="margin: 5px 0;"><strong><?php esc_html_e('With Source URL:', 'manhwa-scraper'); ?></strong> ' + data.debug.manhwa_with_source + '</p>';
                                                    html += '<p style="margin: 5px 0;"><strong><?php esc_html_e('With Chapters:', 'manhwa-scraper'); ?></strong> ' + data.debug.manhwa_with_chapters + '</p>';
                                                    html += '<p style="margin: 10px 0 0 0; font-size: 12px; color: #856404;">';
                                                    html += '<?php esc_html_e('No manhwa with empty images found. This means all your manhwa already have chapter images, or they don\'t have chapter metadata yet.', 'manhwa-scraper'); ?>';
                                                    html += '</p>';
                                                    html += '</div>';
                                                }
                                                
                                                html += '</div>';
                                                
                                                result.html(html).show();
                                                
                                                setTimeout(function() {
                                                    progress.fadeOut();
                                                }, 2000);
                                            } else {
                                                status.html('<span style="color: #dc3232;">✗</span> ' + response.data.message);
                                            }
                                        },
                                        error: function() {
                                            clearInterval(progressInterval);
                                            status.html('<span style="color: #dc3232;">✗</span> <?php esc_html_e('Request failed', 'manhwa-scraper'); ?>');
                                        },
                                        complete: function() {
                                            button.prop('disabled', false).html('<span class="dashicons dashicons-images-alt2"></span> <?php esc_html_e('Run Image Scraper Now', 'manhwa-scraper'); ?>');
                                        }
                                    });
                                });
                            });
                            </script>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Sources -->
            <div class="mws-card">
                <h2><?php esc_html_e('Enabled Sources', 'manhwa-scraper'); ?></h2>
                <p class="description">
                    <?php esc_html_e('Select which sources to enable for scraping.', 'manhwa-scraper'); ?>
                </p>
                
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('Sources', 'manhwa-scraper'); ?></th>
                        <td>
                            <?php foreach ($available_sources as $id => $name): ?>
                            <label style="display: block; margin-bottom: 8px;">
                                <input type="checkbox" name="enabled_sources[]" value="<?php echo esc_attr($id); ?>"
                                    <?php checked(in_array($id, $settings['enabled_sources'])); ?>>
                                <?php echo esc_html($name); ?>
                            </label>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Image Download -->
            <div class="mws-card">
                <h2><?php esc_html_e('Image Download', 'manhwa-scraper'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('Auto Download Covers', 'manhwa-scraper'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="auto_download_covers" value="1"
                                    <?php checked($settings['auto_download_covers']); ?>>
                                <?php esc_html_e('Automatically download cover images when importing', 'manhwa-scraper'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- User Agents -->
            <div class="mws-card">
                <h2><?php esc_html_e('User Agents', 'manhwa-scraper'); ?></h2>
                <p class="description">
                    <?php esc_html_e('User agents are rotated automatically to avoid detection. One per line.', 'manhwa-scraper'); ?>
                </p>
                
                <table class="form-table">
                    <tr>
                        <th>
                            <label for="user_agents"><?php esc_html_e('User Agent List', 'manhwa-scraper'); ?></label>
                        </th>
                        <td>
                            <textarea id="user_agents" name="user_agents" class="large-text code" rows="8"><?php 
                                echo esc_textarea(implode("\n", $settings['user_agents'])); 
                            ?></textarea>
                            <p class="description">
                                <?php esc_html_e('Enter one user agent string per line.', 'manhwa-scraper'); ?>
                            </p>
                            <button type="button" class="button button-small" id="mws-reset-ua">
                                <?php esc_html_e('Reset to Defaults', 'manhwa-scraper'); ?>
                            </button>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Proxy Settings -->
            <div class="mws-card">
                <h2>
                    <span class="dashicons dashicons-shield" style="color: #2271b1;"></span>
                    <?php esc_html_e('Proxy Settings', 'manhwa-scraper'); ?>
                </h2>
                <p class="description">
                    <?php esc_html_e('Configure a proxy server to bypass IP blocking. Useful when your hosting IP is blocked by source websites.', 'manhwa-scraper'); ?>
                </p>
                
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('Enable Proxy', 'manhwa-scraper'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="proxy_enabled" value="1"
                                    <?php checked(get_option('mws_proxy_enabled', false)); ?>>
                                <?php esc_html_e('Use proxy for all scraping requests', 'manhwa-scraper'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('When enabled, all HTTP requests will be routed through the proxy server.', 'manhwa-scraper'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="proxy_host"><?php esc_html_e('Proxy Host', 'manhwa-scraper'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="proxy_host" name="proxy_host" class="regular-text"
                                   value="<?php echo esc_attr(get_option('mws_proxy_host', '')); ?>"
                                   placeholder="proxy.example.com or 123.45.67.89">
                            <p class="description">
                                <?php esc_html_e('Proxy server hostname or IP address (without port).', 'manhwa-scraper'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="proxy_port"><?php esc_html_e('Proxy Port', 'manhwa-scraper'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="proxy_port" name="proxy_port" class="small-text"
                                   value="<?php echo esc_attr(get_option('mws_proxy_port', '')); ?>"
                                   placeholder="8080" min="1" max="65535">
                            <p class="description">
                                <?php esc_html_e('Proxy port number (e.g., 8080, 3128).', 'manhwa-scraper'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="proxy_username"><?php esc_html_e('Proxy Username', 'manhwa-scraper'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="proxy_username" name="proxy_username" class="regular-text"
                                   value="<?php echo esc_attr(get_option('mws_proxy_username', '')); ?>"
                                   placeholder="<?php esc_attr_e('Optional', 'manhwa-scraper'); ?>">
                            <p class="description">
                                <?php esc_html_e('Username for proxy authentication (leave empty if not required).', 'manhwa-scraper'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="proxy_password"><?php esc_html_e('Proxy Password', 'manhwa-scraper'); ?></label>
                        </th>
                        <td>
                            <input type="password" id="proxy_password" name="proxy_password" class="regular-text"
                                   value="<?php echo esc_attr(get_option('mws_proxy_password', '')); ?>"
                                   placeholder="<?php esc_attr_e('Optional', 'manhwa-scraper'); ?>">
                            <p class="description">
                                <?php esc_html_e('Password for proxy authentication (leave empty if not required).', 'manhwa-scraper'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Test Proxy', 'manhwa-scraper'); ?></th>
                        <td>
                            <button type="button" class="button" id="mws-test-proxy-btn">
                                <span class="dashicons dashicons-networking" style="margin-top: 4px;"></span>
                                <?php esc_html_e('Test Proxy Connection', 'manhwa-scraper'); ?>
                            </button>
                            <span class="spinner" id="mws-proxy-spinner"></span>
                            <div id="mws-proxy-result" style="margin-top: 10px;"></div>
                        </td>
                    </tr>
                </table>
                
                <div class="notice notice-info" style="margin: 15px 0;">
                    <p>
                        <strong><?php esc_html_e('Where to get a proxy?', 'manhwa-scraper'); ?></strong><br>
                        <?php esc_html_e('You can use services like:', 'manhwa-scraper'); ?>
                        <a href="https://www.webshare.io/" target="_blank">Webshare</a>,
                        <a href="https://brightdata.com/" target="_blank">Bright Data</a>,
                        <a href="https://oxylabs.io/" target="_blank">Oxylabs</a>,
                        <?php esc_html_e('or any HTTP/HTTPS proxy provider.', 'manhwa-scraper'); ?>
                    </p>
                </div>
            </div>
            
            <!-- REST API Settings -->
            <div class="mws-card">
                <h2>
                    <span class="dashicons dashicons-rest-api" style="color: #2271b1;"></span>
                    <?php esc_html_e('REST API', 'manhwa-scraper'); ?>
                </h2>
                <p class="description">
                    <?php esc_html_e('Configure external API access for your manhwa data.', 'manhwa-scraper'); ?>
                </p>
                
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('Enable API', 'manhwa-scraper'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="api_enabled" value="1"
                                    <?php checked(get_option('mws_api_enabled', true)); ?>>
                                <?php esc_html_e('Enable REST API endpoints', 'manhwa-scraper'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('Allow external access to manhwa data via API.', 'manhwa-scraper'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Require API Key', 'manhwa-scraper'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="api_require_key" value="1" id="api_require_key"
                                    <?php checked(get_option('mws_api_require_key', false)); ?>>
                                <?php esc_html_e('Require API Key for access', 'manhwa-scraper'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('When enabled, requests must include X-API-Key header.', 'manhwa-scraper'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="api_key"><?php esc_html_e('API Key', 'manhwa-scraper'); ?></label>
                        </th>
                        <td>
                            <?php $api_key = get_option('mws_api_key', ''); ?>
                            <input type="text" id="api_key" name="api_key" class="regular-text code"
                                   value="<?php echo esc_attr($api_key); ?>"
                                   placeholder="<?php esc_attr_e('Generate or enter API key', 'manhwa-scraper'); ?>">
                            <button type="button" class="button" id="mws-generate-api-key">
                                <span class="dashicons dashicons-randomize" style="margin-top: 4px;"></span>
                                <?php esc_html_e('Generate', 'manhwa-scraper'); ?>
                            </button>
                            <p class="description">
                                <?php esc_html_e('Secret key for API authentication. Keep this safe!', 'manhwa-scraper'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('API Endpoints', 'manhwa-scraper'); ?></th>
                        <td>
                            <div style="background: #f0f0f1; padding: 15px; border-radius: 6px; font-family: monospace; font-size: 12px;">
                                <strong style="color: #135e96;"><?php echo home_url('/wp-json/manhwa/v1/'); ?></strong>
                                <ul style="margin: 10px 0 0 20px; list-style: disc;">
                                    <li><code>GET /manhwa</code> - <?php esc_html_e('List all manhwa', 'manhwa-scraper'); ?></li>
                                    <li><code>GET /manhwa/{id}</code> - <?php esc_html_e('Get manhwa detail', 'manhwa-scraper'); ?></li>
                                    <li><code>GET /manhwa/{id}/chapters</code> - <?php esc_html_e('Get chapters', 'manhwa-scraper'); ?></li>
                                    <li><code>GET /search?q=keyword</code> - <?php esc_html_e('Search manhwa', 'manhwa-scraper'); ?></li>
                                    <li><code>GET /latest</code> - <?php esc_html_e('Latest updates', 'manhwa-scraper'); ?></li>
                                    <li><code>GET /popular</code> - <?php esc_html_e('Popular manhwa', 'manhwa-scraper'); ?></li>
                                    <li><code>GET /genres</code> - <?php esc_html_e('List genres', 'manhwa-scraper'); ?></li>
                                    <li><code>GET /stats</code> - <?php esc_html_e('Statistics', 'manhwa-scraper'); ?></li>
                                </ul>
                            </div>
                            <p class="description" style="margin-top: 10px;">
                                <a href="<?php echo home_url('/wp-json/manhwa/v1/stats'); ?>" target="_blank" class="button button-small">
                                    <span class="dashicons dashicons-external" style="font-size: 14px; line-height: 1.8;"></span>
                                    <?php esc_html_e('Test API', 'manhwa-scraper'); ?>
                                </a>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <p class="submit">
                <input type="submit" name="mws_save_settings" class="button button-primary" 
                       value="<?php esc_attr_e('Save Settings', 'manhwa-scraper'); ?>">
            </p>
        </form>
        
        <!-- Tools -->
        <div class="mws-card">
            <h2><?php esc_html_e('Tools', 'manhwa-scraper'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('Force Update Check', 'manhwa-scraper'); ?></th>
                    <td>
                        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                            <select id="mws-update-limit" style="width: 140px;">
                                <option value="5">5 manhwa</option>
                                <option value="10" selected>10 manhwa</option>
                                <option value="20">20 manhwa</option>
                                <option value="50">50 manhwa (slow)</option>
                            </select>
                            <button type="button" class="button" id="mws-force-update-check">
                                <span class="dashicons dashicons-update" style="margin-top: 3px;"></span>
                                <?php esc_html_e('Run Update Check', 'manhwa-scraper'); ?>
                            </button>
                            <span class="spinner" id="mws-update-spinner"></span>
                        </div>
                        <div id="mws-update-result" style="margin-top: 10px;"></div>
                        <p class="description">
                            <?php 
                            // Count tracked manhwa
                            $tracked_count = 0;
                            if (post_type_exists('manhwa')) {
                                $tracked = get_posts([
                                    'post_type' => 'manhwa',
                                    'posts_per_page' => -1,
                                    'meta_query' => [
                                        ['key' => '_mws_source_url', 'compare' => 'EXISTS'],
                                    ],
                                    'fields' => 'ids',
                                ]);
                                $tracked_count = count($tracked);
                            }
                            printf(
                                esc_html__('Manually check for new chapters. Currently tracking: %d manhwa', 'manhwa-scraper'),
                                $tracked_count
                            );
                            ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Test All Connections', 'manhwa-scraper'); ?></th>
                    <td>
                        <button type="button" class="button" id="mws-test-all-btn">
                            <?php esc_html_e('Test All Sources', 'manhwa-scraper'); ?>
                        </button>
                        <span class="spinner" id="mws-test-spinner"></span>
                        <div id="mws-test-results" style="margin-top: 10px;"></div>
                    </td>
                </tr>
            </table>
        </div>
</div>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

/* ── Design Tokens ── */
.mws-settings-page {
    --font: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --primary: #6366F1;
    --primary-dark: #4F46E5;
    --primary-light: #818CF8;
    --primary-bg: rgba(99,102,241,.06);
    --success: #10B981;
    --success-bg: rgba(16,185,129,.08);
    --warning: #F59E0B;
    --warning-bg: rgba(245,158,11,.08);
    --danger: #EF4444;
    --danger-bg: rgba(239,68,68,.06);
    --purple: #8B5CF6;
    --blue: #3B82F6;
    --blue-bg: rgba(59,130,246,.06);
    --gray-50: #F9FAFB;
    --gray-100: #F3F4F6;
    --gray-200: #E5E7EB;
    --gray-300: #D1D5DB;
    --gray-400: #9CA3AF;
    --gray-500: #6B7280;
    --gray-600: #4B5563;
    --gray-700: #374151;
    --gray-800: #1F2937;
    --radius-sm: 6px;
    --radius: 10px;
    --radius-lg: 14px;
    --shadow-sm: 0 1px 2px rgba(0,0,0,.04), 0 1px 3px rgba(0,0,0,.06);
    --shadow: 0 4px 6px -1px rgba(0,0,0,.06), 0 2px 4px -2px rgba(0,0,0,.06);
    --transition: all .2s cubic-bezier(.4,0,.2,1);
    font-family: var(--font) !important;
    max-width: 900px;
}

/* ── Page Title ── */
.mws-settings-page ~ .mws-title,
.mws-wrap > .mws-title {
    display: flex !important;
    align-items: center;
    gap: 12px;
    font-family: var(--font) !important;
    font-size: 22px !important;
    font-weight: 800 !important;
    color: var(--gray-800) !important;
    margin: 0 0 24px 0 !important;
    padding: 0 !important;
}
.mws-wrap > .mws-title .dashicons {
    font-size: 28px; width: 28px; height: 28px;
    color: var(--primary);
}

/* ── Cards ── */
.mws-settings-page .mws-card {
    background: #fff !important;
    border: 1px solid var(--gray-200) !important;
    border-radius: var(--radius-lg) !important;
    padding: 24px 28px !important;
    box-shadow: var(--shadow-sm) !important;
    margin-bottom: 22px;
    position: relative;
    overflow: hidden;
    transition: var(--transition);
}
.mws-settings-page .mws-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--primary), var(--purple));
    opacity: .7;
}
.mws-settings-page .mws-card:hover {
    box-shadow: var(--shadow) !important;
}

/* ── Card Headings ── */
.mws-settings-page .mws-card h2 {
    display: flex;
    align-items: center;
    gap: 10px;
    font-family: var(--font) !important;
    font-size: 15px !important;
    font-weight: 700 !important;
    color: var(--gray-800) !important;
    margin: 0 0 4px 0 !important;
    padding: 0 0 14px 0 !important;
    border-bottom: 1px solid var(--gray-200);
}
.mws-settings-page .mws-card h2 .dashicons {
    color: var(--primary);
    font-size: 20px; width: 20px; height: 20px;
}
.mws-settings-page .mws-card > p.description {
    font-family: var(--font);
    font-size: 12.5px;
    color: var(--gray-400);
    margin: 12px 0 6px 0;
    line-height: 1.5;
}

/* ── Form Table ── */
.mws-settings-page .form-table {
    margin-top: 0;
}
.mws-settings-page .form-table th {
    font-family: var(--font);
    font-size: 13px;
    font-weight: 600;
    color: var(--gray-700);
    padding: 18px 12px 18px 0;
    vertical-align: top;
    width: 220px;
}
.mws-settings-page .form-table td {
    font-family: var(--font);
    font-size: 13px;
    color: var(--gray-600);
    padding: 18px 0;
}
.mws-settings-page .form-table tr {
    border-bottom: 1px solid var(--gray-100);
}
.mws-settings-page .form-table tr:last-child {
    border-bottom: none;
}
.mws-settings-page .form-table td p.description {
    font-family: var(--font);
    font-size: 11.5px;
    color: var(--gray-400);
    margin-top: 6px;
    line-height: 1.5;
}
.mws-settings-page .form-table td p.description strong {
    font-weight: 700;
}

/* ── Form Inputs ── */
.mws-settings-page input[type="text"],
.mws-settings-page input[type="number"],
.mws-settings-page input[type="password"],
.mws-settings-page select,
.mws-settings-page textarea {
    font-family: var(--font) !important;
    font-size: 13px !important;
    border: 1.5px solid var(--gray-300) !important;
    border-radius: var(--radius-sm) !important;
    padding: 8px 12px !important;
    transition: var(--transition);
    background: #fff;
}
.mws-settings-page input[type="text"]:focus,
.mws-settings-page input[type="number"]:focus,
.mws-settings-page input[type="password"]:focus,
.mws-settings-page select:focus,
.mws-settings-page textarea:focus {
    border-color: var(--primary) !important;
    box-shadow: 0 0 0 3px rgba(99,102,241,.1) !important;
    outline: none !important;
}
.mws-settings-page .small-text {
    width: 80px !important;
}
.mws-settings-page .regular-text {
    width: 100% !important;
    max-width: 400px;
}
.mws-settings-page textarea.large-text {
    width: 100% !important;
    max-width: 100%;
    font-family: 'SF Mono', 'Consolas', 'Monaco', monospace !important;
    font-size: 12px !important;
    line-height: 1.6;
    border-radius: var(--radius) !important;
    padding: 14px !important;
    background: var(--gray-50);
}

/* ── Checkboxes ── */
.mws-settings-page input[type="checkbox"] {
    accent-color: var(--primary);
    width: 16px;
    height: 16px;
    margin-right: 6px;
    cursor: pointer;
}
.mws-settings-page label {
    font-family: var(--font);
    font-size: 13px;
    color: var(--gray-600);
    cursor: pointer;
}

/* ── Buttons ── */
.mws-settings-page .button {
    font-family: var(--font) !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    border-radius: var(--radius-sm) !important;
    padding: 7px 14px !important;
    height: auto !important;
    min-height: auto !important;
    display: inline-flex !important;
    align-items: center;
    gap: 5px;
    transition: var(--transition);
    line-height: 1.4 !important;
}
.mws-settings-page .button .dashicons {
    font-size: 14px; width: 14px; height: 14px;
    margin-top: 0 !important;
}
.mws-settings-page .button-primary,
.mws-settings-page input[type="submit"].button-primary {
    background: linear-gradient(135deg, var(--primary), var(--purple)) !important;
    border: none !important;
    color: #fff !important;
    box-shadow: 0 2px 8px rgba(99,102,241,.25) !important;
    text-shadow: none !important;
    padding: 9px 22px !important;
    font-size: 13px !important;
}
.mws-settings-page .button-primary:hover,
.mws-settings-page input[type="submit"].button-primary:hover {
    opacity: .9;
    box-shadow: 0 4px 12px rgba(99,102,241,.35) !important;
}
.mws-settings-page .button-secondary {
    background: var(--gray-50) !important;
    border: 1.5px solid var(--gray-300) !important;
    color: var(--gray-700) !important;
}
.mws-settings-page .button-secondary:hover {
    background: var(--gray-100) !important;
    border-color: var(--gray-400) !important;
}
.mws-settings-page .button-small {
    padding: 5px 10px !important;
    font-size: 11px !important;
}

/* ── Submit Section ── */
.mws-settings-page .submit {
    padding-top: 10px;
    border-top: none;
    margin-top: 0;
}

/* ── Source Checkboxes ── */
.mws-settings-page td label[style*="display: block"] {
    display: flex !important;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: var(--gray-50);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm);
    margin-bottom: 8px !important;
    transition: var(--transition);
}
.mws-settings-page td label[style*="display: block"]:hover {
    background: var(--primary-bg);
    border-color: var(--primary-light);
}

/* ── API Endpoints Panel ── */
.mws-settings-page td div[style*="font-family: monospace"] {
    background: var(--gray-800) !important;
    color: #E5E7EB !important;
    padding: 18px 20px !important;
    border-radius: var(--radius) !important;
    font-family: 'SF Mono', 'Consolas', 'Monaco', monospace !important;
    font-size: 12px !important;
}
.mws-settings-page td div[style*="font-family: monospace"] strong {
    color: var(--primary-light) !important;
}
.mws-settings-page td div[style*="font-family: monospace"] ul {
    margin: 12px 0 0 20px !important;
}
.mws-settings-page td div[style*="font-family: monospace"] li {
    margin-bottom: 6px;
    color: var(--gray-300);
}
.mws-settings-page td div[style*="font-family: monospace"] code {
    background: rgba(99,102,241,.15);
    color: var(--primary-light);
    padding: 2px 7px;
    border-radius: 4px;
    font-size: 11.5px;
}

/* ── Progress Bar ── */
.mws-settings-page .mws-progress-bar {
    height: 8px !important;
    background: var(--gray-200) !important;
    border-radius: 10px !important;
    overflow: hidden;
}
.mws-settings-page .mws-progress-bar > div,
#image-scraper-bar {
    background: linear-gradient(90deg, var(--primary), var(--purple)) !important;
    border-radius: 10px;
}

/* ── Notices ── */
.mws-settings-page .notice {
    font-family: var(--font) !important;
    border-radius: var(--radius-sm) !important;
    font-size: 12.5px;
    padding: 12px 16px !important;
    margin: 16px 0 !important;
}
.mws-settings-page .notice a {
    color: var(--primary);
    font-weight: 600;
}

/* ── Discovery Last Run Box ── */
.mws-settings-page td div[style*="background: #e7f3ff"] {
    background: var(--blue-bg) !important;
    border: 1px solid rgba(59,130,246,.15) !important;
    border-radius: var(--radius-sm) !important;
    padding: 12px 14px !important;
    font-family: var(--font) !important;
    font-size: 12px !important;
}

/* ── Spinner ── */
.mws-settings-page .spinner {
    margin-top: 0 !important;
}

/* ── Dashicons spin animation ── */
.dashicons.spin {
    animation: mws-spin 1s linear infinite;
}
@keyframes mws-spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* ── Responsive ── */
@media (max-width: 782px) {
    .mws-settings-page {
        max-width: 100%;
    }
    .mws-settings-page .mws-card {
        padding: 18px 16px !important;
    }
    .mws-settings-page .form-table th {
        width: auto;
        padding: 14px 0 4px 0;
        display: block;
    }
    .mws-settings-page .form-table td {
        padding: 4px 0 14px 0;
        display: block;
    }
    .mws-settings-page .regular-text {
        max-width: 100%;
    }
}
@media (max-width: 600px) {
    .mws-wrap > .mws-title {
        font-size: 18px !important;
    }
    .mws-settings-page .mws-card h2 {
        font-size: 14px !important;
    }
}
</style>

<script>
// Default user agents for reset
var mwsDefaultUserAgents = <?php echo json_encode(MWS_User_Agent_Rotator::get_defaults()); ?>;

jQuery(document).ready(function($) {
    // Reset user agents
    $('#mws-reset-ua').on('click', function() {
        $('#user_agents').val(mwsDefaultUserAgents.join('\n'));
    });
    
    // Generate API Key
    $('#mws-generate-api-key').on('click', function() {
        var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        var key = 'mws_';
        for (var i = 0; i < 32; i++) {
            key += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        $('#api_key').val(key);
    });
    
    // Force update check
    $('#mws-force-update-check').on('click', function() {
        var $btn = $(this);
        var $spinner = $('#mws-update-spinner');
        var $result = $('#mws-update-result');
        var limit = $('#mws-update-limit').val() || 10;
        var originalHtml = $btn.html();
        
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> <?php esc_html_e('Checking...', 'manhwa-scraper'); ?>');
        $spinner.addClass('is-active');
        $result.html('<em><?php esc_html_e('Checking for new chapters...', 'manhwa-scraper'); ?></em>');
        
        $.ajax({
            url: mwsData.ajaxUrl,
            type: 'POST',
            timeout: 120000, // 2 minute timeout
            data: {
                action: 'mws_force_update_check',
                nonce: mwsData.nonce,
                limit: limit
            },
            success: function(response) {
                $btn.prop('disabled', false).html(originalHtml);
                $spinner.removeClass('is-active');
                
                if (response.success) {
                    var stats = response.data.stats || {};
                    var html = '<div style="padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;">';
                    html += '<strong style="color: #155724;">✓ ' + response.data.message + '</strong>';
                    if (stats.total_tracked) {
                        html += '<br><small style="color: #155724;">Total tracked: ' + stats.total_tracked + ' manhwa</small>';
                    }
                    html += ' <a href="<?php echo admin_url('admin.php?page=manhwa-scraper-history&status=auto_update'); ?>" class="button button-small" style="margin-left: 10px;">View History</a>';
                    html += '</div>';
                    $result.html(html);
                } else {
                    $result.html('<div style="padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24;">Error: ' + (response.data.message || 'Unknown error') + '</div>');
                }
            },
            error: function(xhr, status, error) {
                $btn.prop('disabled', false).html(originalHtml);
                $spinner.removeClass('is-active');
                var msg = error;
                if (status === 'timeout') {
                    msg = '<?php esc_html_e('Request timed out. Try with fewer manhwa.', 'manhwa-scraper'); ?>';
                }
                $result.html('<div style="padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24;">Error: ' + msg + '</div>');
            }
        });
    });
    
    // Test proxy connection
    $('#mws-test-proxy-btn').on('click', function() {
        var $btn = $(this);
        var $spinner = $('#mws-proxy-spinner');
        var $result = $('#mws-proxy-result');
        
        var proxyHost = $('#proxy_host').val();
        var proxyPort = $('#proxy_port').val();
        var proxyUsername = $('#proxy_username').val();
        var proxyPassword = $('#proxy_password').val();
        
        if (!proxyHost) {
            $result.html('<span style="color: #dc3232;">Please enter a proxy host first.</span>');
            return;
        }
        
        $btn.prop('disabled', true);
        $spinner.addClass('is-active');
        $result.html('');
        
        $.ajax({
            url: mwsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mws_test_proxy',
                nonce: mwsData.nonce,
                proxy_host: proxyHost,
                proxy_port: proxyPort,
                proxy_username: proxyUsername,
                proxy_password: proxyPassword
            },
            success: function(response) {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
                
                if (response.success) {
                    $result.html('<span style="color: #46b450;"><span class="dashicons dashicons-yes"></span> ' + response.data.message + '</span>');
                } else {
                    $result.html('<span style="color: #dc3232;"><span class="dashicons dashicons-no"></span> ' + response.data.message + '</span>');
                }
            },
            error: function(xhr, status, error) {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
                $result.html('<span style="color: #dc3232;">Connection error: ' + error + '</span>');
            }
        });
    });
    
    // Test Discovery Button
    $('#mws-test-discovery-btn').on('click', function() {
        var $btn = $(this);
        var $spinner = $('#mws-test-discovery-spinner');
        var $result = $('#mws-discovery-result');
        
        $btn.prop('disabled', true);
        $spinner.addClass('is-active');
        $result.html('<?php esc_html_e('Running discovery... This may take a few minutes.', 'manhwa-scraper'); ?>').show();
        
        $.ajax({
            url: mwsData.ajaxUrl,
            type: 'POST',
            timeout: 300000, // 5 minutes
            data: {
                action: 'mws_run_discovery',
                nonce: mwsData.nonce
            },
            success: function(response) {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
                
                if (response.success) {
                    var stats = response.data.stats;
                    var html = '<strong style="color: #46b450;">✅ <?php esc_html_e('Discovery completed!', 'manhwa-scraper'); ?></strong><br>';
                    html += '<?php esc_html_e('Sources checked:', 'manhwa-scraper'); ?> ' + stats.sources_checked + '<br>';
                    html += '<?php esc_html_e('Manhwa found:', 'manhwa-scraper'); ?> ' + stats.manhwa_found + '<br>';
                    html += '<?php esc_html_e('New imported:', 'manhwa-scraper'); ?> <strong style="color: #2271b1;">' + stats.new_imported + '</strong><br>';
                    html += '<?php esc_html_e('Already exists:', 'manhwa-scraper'); ?> ' + stats.already_exists + '<br>';
                    html += '<?php esc_html_e('Errors:', 'manhwa-scraper'); ?> ' + stats.errors;
                    $result.html(html);
                } else {
                    $result.html('<span style="color: #dc3232;">❌ ' + (response.data.message || 'Discovery failed') + '</span>');
                }
            },
            error: function(xhr, status, error) {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
                $result.html('<span style="color: #dc3232;">❌ Error: ' + error + '</span>');
            }
        });
    });
});
</script>

