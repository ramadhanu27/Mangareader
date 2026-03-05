<?php
/**
 * Admin Pages Class
 * Renders admin interface pages
 */

if (!defined('ABSPATH')) {
    exit;
}

class MWS_Admin_Pages {
    
    /**
     * Render dashboard page
     */
    public static function render_dashboard() {
        $scraper_manager = MWS_Scraper_Manager::get_instance();
        $sources = $scraper_manager->get_sources_info();
        $next_cron = MWS_Cron_Handler::get_next_scheduled();
        
        // Get recent logs
        global $wpdb;
        $table_name = $wpdb->prefix . 'mws_logs';
        $recent_logs = $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 10"
        );
        
        // Get stats
        $stats = [
            'total_scraped' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'success'"),
            'total_errors' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'error'"),
            'today_scraped' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE status = 'success' AND DATE(created_at) = %s",
                current_time('Y-m-d')
            )),
        ];
        
        // ── Chart data: last 7 days ──
        $chart_data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $label = date('M j', strtotime("-{$i} days"));
            $success = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE status = 'success' AND DATE(created_at) = %s", $date
            ));
            $errors = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE status = 'error' AND DATE(created_at) = %s", $date
            ));
            $chart_data[] = ['label' => $label, 'success' => $success, 'errors' => $errors];
        }

        // ── Queue count ──
        $queue_table = $wpdb->prefix . 'mws_queue';
        $queue_count = 0;
        if ($wpdb->get_var("SHOW TABLES LIKE '$queue_table'") === $queue_table) {
            $queue_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $queue_table WHERE status IN ('pending','processing')");
        }

        // ── Storage size ──
        $upload_dir = wp_upload_dir();
        $manhwa_dir = $upload_dir['basedir'] . '/manhwa';
        $storage_size = 0;
        if (is_dir($manhwa_dir)) {
            $storage_size = self::get_directory_size($manhwa_dir);
        }


        // ── Top failing manhwa (last 7 days) ──
        $top_errors = $wpdb->get_results(
            "SELECT url, COUNT(*) as error_count, MAX(message) as last_message
             FROM $table_name
             WHERE status = 'error' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             GROUP BY url ORDER BY error_count DESC LIMIT 3"
        );

        // ── Success rate (last 7 days) ──
        $week_total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $week_success = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'success' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $success_rate = $week_total > 0 ? round(($week_success / $week_total) * 100, 1) : 100;

        // ── Auto-pilot status ──
        $auto_update_on = (bool) get_option('mws_auto_update_enabled', false);
        $auto_discovery_on = (bool) get_option('mws_auto_discovery_enabled', false);
        $auto_image_on = (bool) get_option('mws_auto_image_scraper_enabled', false);

        // ── Tracked manhwa count ──
        $tracked_manhwa = count(get_option('mws_tracked_manhwa', []));

        include MWS_PATH . 'views/dashboard-page.php';
    }
    
    /**
     * Calculate directory size recursively
     */
    private static function get_directory_size($dir) {
        $size = 0;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $file) {
            $size += $file->getSize();
        }
        return $size;
    }
    
    /**
     * Render import page
     */
    public static function render_import() {
        $scraper_manager = MWS_Scraper_Manager::get_instance();
        $sources = $scraper_manager->get_sources_info();
        
        include MWS_PATH . 'views/import-page.php';
    }
    
    /**
     * Render bulk scrape page
     */
    public static function render_bulk() {
        $scraper_manager = MWS_Scraper_Manager::get_instance();
        $sources = $scraper_manager->get_sources_info();
        
        include MWS_PATH . 'views/bulk-scrape-page.php';
    }
    
    /**
     * Render search page
     */
    public static function render_search() {
        $scraper_manager = MWS_Scraper_Manager::get_instance();
        $sources = $scraper_manager->get_sources_info();
        
        include MWS_PATH . 'views/search-page.php';
    }
    
    /**
     * Render chapter scraper page
     */
    public static function render_chapter() {
        include MWS_PATH . 'views/chapter-page.php';
    }
    
    /**
     * Render download queue page
     */
    public static function render_queue() {
        include MWS_PATH . 'views/queue-page.php';
    }
    
    /**
     * Render history page
     */
    public static function render_history() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mws_logs';
        
        // Handle clear logs
        if (isset($_POST['mws_clear_logs_nonce']) && wp_verify_nonce($_POST['mws_clear_logs_nonce'], 'mws_clear_logs')) {
            $clear_period = isset($_POST['clear_period']) ? sanitize_text_field($_POST['clear_period']) : '30';
            
            if ($clear_period === 'all') {
                // Delete all logs
                $deleted = $wpdb->query("TRUNCATE TABLE {$table_name}");
                if ($deleted !== false) {
                    add_settings_error('mws_history', 'logs_cleared', __('All logs have been deleted.', 'manhwa-scraper'), 'success');
                }
            } else {
                // Delete logs older than X days
                $days = intval($clear_period);
                $deleted = $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$table_name} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                    $days
                ));
                if ($deleted !== false) {
                    add_settings_error('mws_history', 'logs_cleared', sprintf(__('%d logs older than %d days have been deleted.', 'manhwa-scraper'), $deleted, $days), 'success');
                }
            }
        }
        
        // Pagination
        $per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;
        
        // Filter
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $source_filter = isset($_GET['source']) ? sanitize_text_field($_GET['source']) : '';
        
        $where = "1=1";
        if (!empty($status_filter)) {
            $where .= $wpdb->prepare(" AND status = %s", $status_filter);
        }
        if (!empty($source_filter)) {
            $where .= $wpdb->prepare(" AND source = %s", $source_filter);
        }
        
        // Get logs
        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE $where ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));
        
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE $where");
        $total_pages = ceil($total_items / $per_page);
        
        // Get unique sources for filter
        $sources = $wpdb->get_col("SELECT DISTINCT source FROM $table_name ORDER BY source");
        
        include MWS_PATH . 'views/history-page.php';
    }
    
    /**
     * Render statistics page
     */
    public static function render_statistics() {
        include MWS_PATH . 'views/statistics-page.php';
    }
    
    /**
     * Render auto update statistics page
     */
    public static function render_auto_update_stats() {
        include MWS_PATH . 'views/auto-update-stats-page.php';
    }
    
    /**
     * Render settings page
     */
    public static function render_settings() {
        // Handle settings save
        if (isset($_POST['mws_save_settings']) && wp_verify_nonce($_POST['mws_settings_nonce'], 'mws_save_settings')) {
            self::save_settings();
        }
        
        // Get current settings
        $settings = [
            'rate_limit' => get_option('mws_rate_limit', 10),
            'delay_between_requests' => get_option('mws_delay_between_requests', 2000),
            'cron_schedule' => get_option('mws_cron_schedule', 'twicedaily'),
            'auto_download_covers' => get_option('mws_auto_download_covers', true),
            'enabled_sources' => get_option('mws_enabled_sources', ['manhwaku', 'komikcast', 'manhwaland', 'manhwaindo', 'mangasusuku']),
            'user_agents' => get_option('mws_user_agents', MWS_User_Agent_Rotator::get_defaults()),
        ];
        
        $available_sources = MWS_Scraper_Manager::get_instance()->get_available_sources();
        
        $schedules = [
            'hourly' => __('Hourly', 'manhwa-scraper'),
            'twicedaily' => __('Twice Daily', 'manhwa-scraper'),
            'daily' => __('Daily', 'manhwa-scraper'),
            'mws_every_4_hours' => __('Every 4 Hours', 'manhwa-scraper'),
            'mws_every_6_hours' => __('Every 6 Hours', 'manhwa-scraper'),
        ];
        
        include MWS_PATH . 'views/settings-page.php';
    }
    
    /**
     * Save settings
     */
    private static function save_settings() {
        // Rate limit
        if (isset($_POST['rate_limit'])) {
            update_option('mws_rate_limit', max(1, intval($_POST['rate_limit'])));
        }
        
        // Delay between requests
        if (isset($_POST['delay_between_requests'])) {
            update_option('mws_delay_between_requests', max(100, intval($_POST['delay_between_requests'])));
        }
        
        // Concurrent downloads
        if (isset($_POST['concurrent_downloads'])) {
            $concurrent = max(1, min(100, intval($_POST['concurrent_downloads'])));
            update_option('mws_concurrent_downloads', $concurrent);
        }
        
        // Cron schedule and enable/disable
        $enable_auto_update = isset($_POST['enable_auto_update']);
        $old_enabled = get_option('mws_auto_update_enabled', false);
        update_option('mws_auto_update_enabled', $enable_auto_update);
        
        if (isset($_POST['cron_schedule'])) {
            $old_schedule = get_option('mws_cron_schedule');
            $new_schedule = sanitize_text_field($_POST['cron_schedule']);
            
            if ($old_schedule !== $new_schedule) {
                update_option('mws_cron_schedule', $new_schedule);
                
                // If auto update is enabled, reschedule with new interval
                if ($enable_auto_update) {
                    MWS_Cron_Handler::reschedule($new_schedule);
                }
            }
        }
        
        // Handle enable/disable state change
        if ($enable_auto_update && !$old_enabled) {
            // Just enabled - schedule cron
            MWS_Cron_Handler::schedule();
            add_settings_error('mws_settings', 'cron_enabled', __('Auto update activated!', 'manhwa-scraper'), 'updated');
        } elseif (!$enable_auto_update && $old_enabled) {
            // Just disabled - unschedule cron
            MWS_Cron_Handler::unschedule();
            add_settings_error('mws_settings', 'cron_disabled', __('Auto update deactivated.', 'manhwa-scraper'), 'updated');
        }
        
        // Auto download chapters
        update_option('mws_auto_download_chapters', isset($_POST['auto_download_chapters']));
        
        // Auto scrape chapter images
        update_option('mws_auto_scrape_chapter_images', isset($_POST['auto_scrape_chapter_images']));
        
        // Auto download covers
        update_option('mws_auto_download_covers', isset($_POST['auto_download_covers']));
        
        // Enabled sources
        if (isset($_POST['enabled_sources']) && is_array($_POST['enabled_sources'])) {
            update_option('mws_enabled_sources', array_map('sanitize_text_field', $_POST['enabled_sources']));
        } else {
            update_option('mws_enabled_sources', []);
        }
        
        // User agents
        if (isset($_POST['user_agents'])) {
            $agents = array_filter(array_map('trim', explode("\n", $_POST['user_agents'])));
            if (!empty($agents)) {
                update_option('mws_user_agents', $agents);
            }
        }
        
        // Auto Pilot - Auto Discovery
        $enable_auto_discovery = isset($_POST['auto_discovery_enabled']);
        $old_discovery_enabled = get_option('mws_auto_discovery_enabled', false);
        update_option('mws_auto_discovery_enabled', $enable_auto_discovery);
        
        if (isset($_POST['discovery_max_per_source'])) {
            $max_per_source = intval($_POST['discovery_max_per_source']);
            if ($max_per_source >= 1 && $max_per_source <= 50) {
                update_option('mws_discovery_max_per_source', $max_per_source);
            }
        }
        
        // Handle auto discovery enable/disable
        if ($enable_auto_discovery && !$old_discovery_enabled) {
            MWS_Auto_Discovery::schedule();
            add_settings_error('mws_settings', 'discovery_enabled', __('Auto discovery activated!', 'manhwa-scraper'), 'updated');
        } elseif (!$enable_auto_discovery && $old_discovery_enabled) {
            MWS_Auto_Discovery::unschedule();
            add_settings_error('mws_settings', 'discovery_disabled', __('Auto discovery deactivated.', 'manhwa-scraper'), 'updated');
        }
        
        // Auto Pilot - Auto Cleanup
        $enable_auto_cleanup = isset($_POST['auto_cleanup_enabled']);
        $old_cleanup_enabled = get_option('mws_auto_cleanup_enabled', false);
        update_option('mws_auto_cleanup_enabled', $enable_auto_cleanup);
        
        if (isset($_POST['cleanup_logs_days'])) {
            $logs_days = intval($_POST['cleanup_logs_days']);
            if ($logs_days >= 7 && $logs_days <= 365) {
                update_option('mws_cleanup_logs_days', $logs_days);
            }
        }
        
        if (isset($_POST['cleanup_temp_images_days'])) {
            $temp_days = intval($_POST['cleanup_temp_images_days']);
            if ($temp_days >= 1 && $temp_days <= 30) {
                update_option('mws_cleanup_temp_images_days', $temp_days);
            }
        }
        
        // Handle auto cleanup enable/disable
        if ($enable_auto_cleanup && !$old_cleanup_enabled) {
            MWS_Auto_Cleanup::schedule();
            add_settings_error('mws_settings', 'cleanup_enabled', __('Auto cleanup activated!', 'manhwa-scraper'), 'updated');
        } elseif (!$enable_auto_cleanup && $old_cleanup_enabled) {
            MWS_Auto_Cleanup::unschedule();
            add_settings_error('mws_settings', 'cleanup_disabled', __('Auto cleanup deactivated.', 'manhwa-scraper'), 'updated');
        }
        
        // Auto Pilot - Auto Image Scraper
        $enable_auto_image_scraper = isset($_POST['auto_image_scraper_enabled']);
        $old_image_scraper_enabled = get_option('mws_auto_image_scraper_enabled', false);
        update_option('mws_auto_image_scraper_enabled', $enable_auto_image_scraper);
        
        if (isset($_POST['image_scraper_batch_size'])) {
            $batch_size = intval($_POST['image_scraper_batch_size']);
            if ($batch_size >= 1 && $batch_size <= 50) {
                update_option('mws_image_scraper_batch_size', $batch_size);
            }
        }
        
        // Image scraper interval
        if (isset($_POST['image_scraper_interval'])) {
            $old_interval = get_option('mws_image_scraper_interval', 'sixhourly');
            $new_interval = sanitize_text_field($_POST['image_scraper_interval']);
            
            // Validate interval
            $allowed_intervals = ['hourly', 'twohourly', 'fourhourly', 'sixhourly', 'twicedaily', 'daily'];
            if (in_array($new_interval, $allowed_intervals)) {
                update_option('mws_image_scraper_interval', $new_interval);
                
                // If interval changed and scraper is enabled, reschedule
                if ($old_interval !== $new_interval && $enable_auto_image_scraper) {
                    MWS_Auto_Image_Scraper::reschedule($new_interval);
                }
            }
        }
        
        // Auto scraper download to local
        $download_local = isset($_POST['auto_scraper_download_local']);
        update_option('mws_auto_scraper_download_local', $download_local);
        
        // Handle auto image scraper enable/disable
        if ($enable_auto_image_scraper && !$old_image_scraper_enabled) {
            MWS_Auto_Image_Scraper::schedule();
            add_settings_error('mws_settings', 'image_scraper_enabled', __('Auto image scraper activated!', 'manhwa-scraper'), 'updated');
        } elseif (!$enable_auto_image_scraper && $old_image_scraper_enabled) {
            MWS_Auto_Image_Scraper::unschedule();
            add_settings_error('mws_settings', 'image_scraper_disabled', __('Auto image scraper deactivated.', 'manhwa-scraper'), 'updated');
        }
        
        // Plugin language
        if (isset($_POST['plugin_language'])) {
            $language = sanitize_text_field($_POST['plugin_language']);
            $allowed_languages = ['en_US', 'id_ID'];
            
            if (in_array($language, $allowed_languages)) {
                $old_language = get_option('mws_plugin_language', 'en_US');
                update_option('mws_plugin_language', $language);
                
                // If language changed, add notice to reload
                if ($old_language !== $language) {
                    add_settings_error('mws_settings', 'language_changed', 
                        __('Language changed. Reloading page...', 'manhwa-scraper'), 'updated');
                    
                    // Add JavaScript to reload page
                    add_action('admin_footer', function() {
                        echo '<script>setTimeout(function(){ location.reload(); }, 1500);</script>';
                    });
                }
            }
        }
        
        // Proxy settings
        update_option('mws_proxy_enabled', isset($_POST['proxy_enabled']));
        
        if (isset($_POST['proxy_host'])) {
            update_option('mws_proxy_host', sanitize_text_field($_POST['proxy_host']));
        }
        
        if (isset($_POST['proxy_port'])) {
            $port = intval($_POST['proxy_port']);
            if ($port > 0 && $port <= 65535) {
                update_option('mws_proxy_port', $port);
            } else {
                update_option('mws_proxy_port', '');
            }
        }
        
        if (isset($_POST['proxy_username'])) {
            update_option('mws_proxy_username', sanitize_text_field($_POST['proxy_username']));
        }
        
        if (isset($_POST['proxy_password'])) {
            // Don't sanitize password too aggressively as it may contain special chars
            update_option('mws_proxy_password', $_POST['proxy_password']);
        }
        
        // REST API settings
        update_option('mws_api_enabled', isset($_POST['api_enabled']));
        update_option('mws_api_require_key', isset($_POST['api_require_key']));
        
        if (isset($_POST['api_key'])) {
            update_option('mws_api_key', sanitize_text_field($_POST['api_key']));
        }
        
        add_settings_error('mws_settings', 'settings_updated', __('Settings saved.', 'manhwa-scraper'), 'updated');
    }
}
