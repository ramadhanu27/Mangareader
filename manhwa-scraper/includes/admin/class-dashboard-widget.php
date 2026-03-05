<?php
/**
 * Dashboard Widget Class
 * Displays scraper statistics on WordPress Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

class MWS_Dashboard_Widget {
    
    private static $instance = null;
    
    /** @var bool|null Cached table existence check */
    private $logs_table_exists = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('wp_dashboard_setup', [$this, 'register_widget']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_styles']);
        add_action('wp_ajax_mws_run_cron_now', [$this, 'ajax_run_cron']);
    }
    
    /**
     * Register dashboard widget (admin only)
     */
    public function register_widget() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        wp_add_dashboard_widget(
            'mws_dashboard_widget',
            '📚 Manhwa Scraper',
            [$this, 'render_widget'],
            null,
            null,
            'normal',
            'high'
        );
    }
    
    /**
     * Enqueue widget styles
     */
    public function enqueue_styles($hook) {
        if ($hook !== 'index.php') {
            return;
        }
        
        wp_add_inline_style('dashboard', $this->get_widget_css());
    }
    
    /**
     * Check if logs table exists (cached per request)
     */
    private function logs_table_exists() {
        if ($this->logs_table_exists === null) {
            global $wpdb;
            $table = $wpdb->prefix . 'mws_logs';
            $this->logs_table_exists = (bool) $wpdb->get_var("SHOW TABLES LIKE '$table'");
        }
        return $this->logs_table_exists;
    }
    
    /**
     * Get widget CSS
     */
    private function get_widget_css() {
        return '
            .mws-widget-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 12px;
                margin-bottom: 20px;
            }
            @media (max-width: 1200px) {
                .mws-widget-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }
            .mws-stat-card {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 10px;
                padding: 16px 14px;
                text-align: center;
                color: #fff;
                box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            }
            .mws-stat-card.green {
                background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
                box-shadow: 0 4px 15px rgba(17, 153, 142, 0.3);
            }
            .mws-stat-card.orange {
                background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                box-shadow: 0 4px 15px rgba(245, 87, 108, 0.3);
            }
            .mws-stat-card.red {
                background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
                box-shadow: 0 4px 15px rgba(235, 51, 73, 0.3);
            }
            .mws-stat-icon-mini {
                font-size: 20px;
                margin-bottom: 8px;
                opacity: 0.9;
            }
            .mws-stat-number {
                font-size: 28px;
                font-weight: 700;
                line-height: 1;
                margin-bottom: 5px;
            }
            .mws-stat-label {
                font-size: 11px;
                opacity: 0.9;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            /* Cron / Queue Info Bar */
            .mws-info-bar {
                display: flex;
                flex-wrap: wrap;
                gap: 15px;
                padding: 10px 14px;
                background: #f6f7f7;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                margin-bottom: 20px;
                font-size: 12px;
                color: #50575e;
            }
            .mws-info-bar-item {
                display: flex;
                align-items: center;
                gap: 5px;
            }
            .mws-info-bar-item .dashicons {
                font-size: 14px;
                width: 14px;
                height: 14px;
                color: #667eea;
            }
            .mws-info-bar-item strong {
                color: #1e1e1e;
            }

            .mws-activity-list {
                margin: 0;
                padding: 0;
                list-style: none;
            }
            .mws-activity-item {
                display: flex;
                align-items: flex-start;
                padding: 12px 0;
                border-bottom: 1px solid #f0f0f1;
            }
            .mws-activity-item:last-child {
                border-bottom: none;
            }
            .mws-activity-icon {
                width: 36px;
                height: 36px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-right: 12px;
                flex-shrink: 0;
                font-size: 16px;
            }
            .mws-activity-icon.is-success {
                background: #d1fae5;
                color: #059669;
            }
            .mws-activity-icon.is-error {
                background: #fee2e2;
                color: #dc2626;
            }
            .mws-activity-icon.is-info {
                background: #dbeafe;
                color: #2563eb;
            }
            .mws-activity-content {
                flex: 1;
                min-width: 0;
            }
            .mws-activity-title {
                font-weight: 500;
                color: #1e1e1e;
                margin-bottom: 2px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .mws-activity-meta {
                font-size: 12px;
                color: #646970;
            }
            .mws-widget-section {
                margin-bottom: 20px;
            }
            .mws-section-title {
                font-size: 13px;
                font-weight: 600;
                color: #1e1e1e;
                margin-bottom: 12px;
                padding-bottom: 8px;
                border-bottom: 2px solid #667eea;
                display: inline-block;
            }
            .mws-quick-actions {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }
            .mws-quick-action {
                display: inline-flex;
                align-items: center;
                padding: 8px 14px;
                background: #f6f7f7;
                border: 1px solid #c3c4c7;
                border-radius: 6px;
                color: #1e1e1e;
                text-decoration: none;
                font-size: 13px;
                transition: all 0.2s;
                cursor: pointer;
            }
            .mws-quick-action:hover {
                background: #667eea;
                color: #fff;
                border-color: #667eea;
            }
            .mws-quick-action .dashicons {
                margin-right: 6px;
                font-size: 16px;
                width: 16px;
                height: 16px;
            }
            .mws-quick-action.running {
                opacity: 0.6;
                pointer-events: none;
            }
            .mws-empty-state {
                text-align: center;
                padding: 30px 20px;
                color: #646970;
            }
            .mws-empty-state .dashicons {
                font-size: 48px;
                width: 48px;
                height: 48px;
                margin-bottom: 10px;
                opacity: 0.5;
            }
            .mws-sources-list {
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
                margin-top: 10px;
            }
            .mws-source-badge {
                display: inline-flex;
                align-items: center;
                padding: 4px 10px;
                background: #f0f0f1;
                border-radius: 20px;
                font-size: 11px;
                color: #1e1e1e;
            }
            .mws-source-badge.active {
                background: #d1fae5;
                color: #059669;
            }
            .mws-source-badge .dot {
                width: 6px;
                height: 6px;
                border-radius: 50%;
                background: currentColor;
                margin-right: 6px;
            }
            .mws-source-counter {
                font-size: 11px;
                font-weight: 600;
                color: #667eea;
                background: #eef2ff;
                padding: 2px 8px;
                border-radius: 10px;
                margin-left: 8px;
            }
            .mws-no-source {
                color: #6b7280;
                font-size: 13px;
            }
            .mws-no-source a {
                color: #667eea;
                text-decoration: none;
            }
            .mws-no-source a:hover {
                text-decoration: underline;
            }
        ';
    }
    
    /**
     * Render widget content
     */
    public function render_widget() {
        $stats = $this->get_stats();
        $recent_activity = $this->get_recent_activity();
        $sources = $this->get_sources_status();
        $cron_info = $this->get_cron_info();
        ?>
        
        <!-- Stats Grid (4 cards) -->
        <div class="mws-widget-grid">
            <div class="mws-stat-card">
                <div class="mws-stat-icon-mini">📚</div>
                <div class="mws-stat-number"><?php echo number_format($stats['total_manhwa']); ?></div>
                <div class="mws-stat-label"><?php esc_html_e('Total Manhwa', 'manhwa-scraper'); ?></div>
            </div>
            <div class="mws-stat-card green">
                <div class="mws-stat-icon-mini">📖</div>
                <div class="mws-stat-number"><?php echo number_format($stats['new_chapters_today']); ?></div>
                <div class="mws-stat-label"><?php esc_html_e('Chapters Today', 'manhwa-scraper'); ?></div>
            </div>
            <div class="mws-stat-card orange">
                <div class="mws-stat-icon-mini">🔄</div>
                <div class="mws-stat-number"><?php echo number_format($stats['scrapes_today']); ?></div>
                <div class="mws-stat-label"><?php esc_html_e('Scrapes Today', 'manhwa-scraper'); ?></div>
            </div>
            <div class="mws-stat-card red">
                <div class="mws-stat-icon-mini">⚠️</div>
                <div class="mws-stat-number"><?php echo number_format($stats['errors_today']); ?></div>
                <div class="mws-stat-label"><?php esc_html_e('Errors Today', 'manhwa-scraper'); ?></div>
            </div>
        </div>
        
        <!-- Cron / Queue Info Bar -->
        <div class="mws-info-bar">
            <div class="mws-info-bar-item">
                <span class="dashicons dashicons-clock"></span>
                <?php esc_html_e('Last Run:', 'manhwa-scraper'); ?>
                <strong><?php echo esc_html($cron_info['last_run']); ?></strong>
            </div>
            <div class="mws-info-bar-item">
                <span class="dashicons dashicons-update"></span>
                <?php esc_html_e('Next Run:', 'manhwa-scraper'); ?>
                <strong><?php echo esc_html($cron_info['next_run']); ?></strong>
            </div>
            <?php if ($cron_info['queue_count'] > 0): ?>
            <div class="mws-info-bar-item">
                <span class="dashicons dashicons-list-view"></span>
                <?php esc_html_e('Queue:', 'manhwa-scraper'); ?>
                <strong><?php echo number_format($cron_info['queue_count']); ?> <?php esc_html_e('pending', 'manhwa-scraper'); ?></strong>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Active Sources -->
        <div class="mws-widget-section">
            <?php 
            $active_count = count(array_filter($sources, function($s) { return $s['active']; }));
            $total_count = count($sources);
            ?>
            <div class="mws-section-title">
                <?php esc_html_e('Active Sources', 'manhwa-scraper'); ?> 
                <span class="mws-source-counter"><?php echo $active_count; ?>/<?php echo $total_count; ?></span>
            </div>
            <div class="mws-sources-list">
                <?php 
                $has_active = false;
                foreach ($sources as $source): 
                    if ($source['active']): 
                        $has_active = true;
                ?>
                    <span class="mws-source-badge active">
                        <span class="dot"></span>
                        <?php echo esc_html($source['name']); ?>
                    </span>
                <?php 
                    endif;
                endforeach; 
                
                if (!$has_active): 
                ?>
                    <span class="mws-no-source"><?php esc_html_e('No active sources.', 'manhwa-scraper'); ?> <a href="<?php echo admin_url('admin.php?page=manhwa-scraper-settings'); ?>"><?php esc_html_e('Configure →', 'manhwa-scraper'); ?></a></span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="mws-widget-section">
            <div class="mws-section-title"><?php esc_html_e('Recent Activity', 'manhwa-scraper'); ?></div>
            <?php if (!empty($recent_activity)): ?>
                <ul class="mws-activity-list">
                    <?php foreach ($recent_activity as $activity): ?>
                        <li class="mws-activity-item">
                            <div class="mws-activity-icon <?php echo esc_attr($activity['type']); ?>">
                                <?php echo $this->get_activity_icon($activity['type']); ?>
                            </div>
                            <div class="mws-activity-content">
                                <div class="mws-activity-title"><?php echo esc_html($activity['title']); ?></div>
                                <div class="mws-activity-meta">
                                    <?php echo esc_html($activity['source']); ?> • 
                                    <?php echo esc_html($this->time_ago($activity['time'])); ?>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="mws-empty-state">
                    <span class="dashicons dashicons-clipboard"></span>
                    <p><?php esc_html_e('No recent activity', 'manhwa-scraper'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Quick Actions -->
        <div class="mws-widget-section">
            <div class="mws-section-title"><?php esc_html_e('Quick Actions', 'manhwa-scraper'); ?></div>
            <div class="mws-quick-actions">
                <a href="<?php echo admin_url('admin.php?page=manhwa-scraper'); ?>" class="mws-quick-action">
                    <span class="dashicons dashicons-download"></span>
                    <?php esc_html_e('Import New', 'manhwa-scraper'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=manhwa-scraper-bulk'); ?>" class="mws-quick-action">
                    <span class="dashicons dashicons-list-view"></span>
                    <?php esc_html_e('Bulk Scrape', 'manhwa-scraper'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=manhwa-scraper-history'); ?>" class="mws-quick-action">
                    <span class="dashicons dashicons-backup"></span>
                    <?php esc_html_e('View History', 'manhwa-scraper'); ?>
                </a>
                <a href="<?php echo admin_url('edit.php?post_type=manhwa'); ?>" class="mws-quick-action">
                    <span class="dashicons dashicons-book"></span>
                    <?php esc_html_e('All Manhwa', 'manhwa-scraper'); ?>
                </a>
                <button type="button" class="mws-quick-action" id="mws-run-cron-btn" onclick="mwsRunCron(this)">
                    <span class="dashicons dashicons-controls-play"></span>
                    <?php esc_html_e('Run Cron Now', 'manhwa-scraper'); ?>
                </button>
            </div>
        </div>
        
        <!-- Run Cron AJAX Script -->
        <script>
        function mwsRunCron(btn) {
            btn.classList.add('running');
            btn.innerHTML = '<span class="dashicons dashicons-update"></span> <?php echo esc_js(__('Running...', 'manhwa-scraper')); ?>';
            
            fetch(ajaxurl, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=mws_run_cron_now&_nonce=<?php echo wp_create_nonce('mws_run_cron'); ?>'
            })
            .then(r => r.json())
            .then(data => {
                btn.classList.remove('running');
                if (data.success) {
                    btn.innerHTML = '<span class="dashicons dashicons-yes"></span> <?php echo esc_js(__('Done!', 'manhwa-scraper')); ?>';
                    setTimeout(() => location.reload(), 1500);
                } else {
                    btn.innerHTML = '<span class="dashicons dashicons-no"></span> <?php echo esc_js(__('Failed', 'manhwa-scraper')); ?>';
                    setTimeout(() => {
                        btn.innerHTML = '<span class="dashicons dashicons-controls-play"></span> <?php echo esc_js(__('Run Cron Now', 'manhwa-scraper')); ?>';
                    }, 3000);
                }
            })
            .catch(() => {
                btn.classList.remove('running');
                btn.innerHTML = '<span class="dashicons dashicons-controls-play"></span> <?php echo esc_js(__('Run Cron Now', 'manhwa-scraper')); ?>';
            });
        }
        </script>
        
        <?php
    }
    
    /**
     * AJAX handler: Run cron manually
     */
    public function ajax_run_cron() {
        check_ajax_referer('mws_run_cron', '_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'manhwa-scraper')]);
        }
        
        // Try to trigger the scraper cron hook
        $ran = false;
        
        // Try common cron hook names
        $hooks = ['mws_auto_scrape', 'mws_cron_scrape', 'manhwa_scraper_cron', 'mws_scheduled_scrape'];
        foreach ($hooks as $hook) {
            if (has_action($hook)) {
                do_action($hook);
                $ran = true;
                break;
            }
        }
        
        // Update last run timestamp
        update_option('mws_last_cron_run', current_time('timestamp'));
        
        // Clear cached stats
        delete_transient('mws_dashboard_stats');
        delete_transient('mws_dashboard_activity');
        
        wp_send_json_success([
            'message' => $ran ? __('Cron executed successfully', 'manhwa-scraper') : __('Cron triggered (no handler found)', 'manhwa-scraper'),
        ]);
    }
    
    /**
     * Get statistics (cached for 15 minutes)
     */
    private function get_stats() {
        $cached = get_transient('mws_dashboard_stats');
        if ($cached !== false && isset($cached['errors_today'])) {
            return $cached;
        }
        
        global $wpdb;
        
        // Total manhwa
        $total_manhwa_posts = wp_count_posts('manhwa');
        $total_manhwa = (int)($total_manhwa_posts->publish ?? 0);
        
        $today = current_time('Y-m-d');
        $new_chapters_today = 0;
        $scrapes_today = 0;
        $errors_today = 0;
        
        if ($this->logs_table_exists()) {
            $logs_table = $wpdb->prefix . 'mws_logs';
            
            // Get all daily counts in a single query
            $row = $wpdb->get_row($wpdb->prepare(
                "SELECT 
                    COUNT(*) AS total_scrapes,
                    SUM(CASE WHEN status = 'success' AND message LIKE '%%chapter%%' THEN 1 ELSE 0 END) AS chapters,
                    SUM(CASE WHEN status != 'success' THEN 1 ELSE 0 END) AS errors
                 FROM {$logs_table} 
                 WHERE DATE(created_at) = %s",
                $today
            ));
            
            if ($row) {
                $scrapes_today = (int) $row->total_scrapes;
                $new_chapters_today = (int) $row->chapters;
                $errors_today = (int) $row->errors;
            }
        }
        
        $stats = [
            'total_manhwa'       => $total_manhwa,
            'new_chapters_today' => $new_chapters_today,
            'scrapes_today'      => $scrapes_today,
            'errors_today'       => $errors_today,
        ];
        
        // Cache for 15 minutes
        set_transient('mws_dashboard_stats', $stats, 15 * MINUTE_IN_SECONDS);
        
        return $stats;
    }
    
    /**
     * Get recent activity from logs (cached for 10 minutes)
     */
    private function get_recent_activity() {
        $cached = get_transient('mws_dashboard_activity');
        if ($cached !== false) {
            return $cached;
        }
        
        if (!$this->logs_table_exists()) {
            return [];
        }
        
        global $wpdb;
        $logs_table = $wpdb->prefix . 'mws_logs';
        
        $logs = $wpdb->get_results(
            "SELECT * FROM {$logs_table} 
             ORDER BY created_at DESC 
             LIMIT 5"
        );
        
        if (empty($logs)) {
            return [];
        }
        
        $activities = [];
        foreach ($logs as $log) {
            $data = json_decode($log->data, true);
            $title = $data['title'] ?? $this->extract_title_from_url($log->url ?? '');
            
            $activities[] = [
                'type'    => $log->status === 'success' ? 'is-success' : 'is-error',
                'title'   => $title ?: __('Scrape operation', 'manhwa-scraper'),
                'source'  => ucfirst($log->source ?? 'unknown'),
                'time'    => $log->created_at,
                'message' => $log->message ?? '',
            ];
        }
        
        // Cache for 10 minutes
        set_transient('mws_dashboard_activity', $activities, 10 * MINUTE_IN_SECONDS);
        
        return $activities;
    }
    
    /**
     * Get cron / queue info
     */
    private function get_cron_info() {
        // Last cron run
        $last_run_ts = get_option('mws_last_cron_run', 0);
        $last_run = $last_run_ts ? $this->time_ago(date('Y-m-d H:i:s', $last_run_ts)) : __('Never', 'manhwa-scraper');
        
        // Next scheduled run
        $next_run = __('Not scheduled', 'manhwa-scraper');
        $hooks = ['mws_auto_scrape', 'mws_cron_scrape', 'manhwa_scraper_cron', 'mws_scheduled_scrape'];
        foreach ($hooks as $hook) {
            $next_ts = wp_next_scheduled($hook);
            if ($next_ts) {
                $diff = $next_ts - current_time('timestamp');
                if ($diff <= 0) {
                    $next_run = __('Overdue', 'manhwa-scraper');
                } elseif ($diff < 3600) {
                    $mins = max(1, (int) floor($diff / 60));
                    /* translators: %d: number of minutes */
                    $next_run = sprintf(__('In %d min', 'manhwa-scraper'), $mins);
                } elseif ($diff < 86400) {
                    $hours = (int) floor($diff / 3600);
                    /* translators: %d: number of hours */
                    $next_run = sprintf(__('In %d hr', 'manhwa-scraper'), $hours);
                } else {
                    $next_run = date_i18n('M j, H:i', $next_ts);
                }
                break;
            }
        }
        
        // Queue count (pending scrape tasks)
        $queue_count = 0;
        $queue_option = get_option('mws_scrape_queue', []);
        if (is_array($queue_option)) {
            $queue_count = count($queue_option);
        }
        
        return [
            'last_run'    => $last_run,
            'next_run'    => $next_run,
            'queue_count' => $queue_count,
        ];
    }
    
    /**
     * Extract title from URL
     */
    private function extract_title_from_url($url) {
        if (empty($url)) return '';
        
        $path = parse_url($url, PHP_URL_PATH);
        $segments = array_filter(explode('/', $path));
        $last = end($segments);
        
        // Clean up slug
        $title = str_replace(['-', '_'], ' ', $last);
        $title = ucwords($title);
        
        return $title;
    }
    
    /**
     * Get sources status - fetch from scraper manager
     */
    private function get_sources_status() {
        $enabled = get_option('mws_enabled_sources', ['manhwaku', 'komikcast']);
        if (!is_array($enabled)) {
            $enabled = [];
        }
        
        // Get all available sources from scraper manager
        $all_sources = [];
        if (class_exists('MWS_Scraper_Manager')) {
            $scraper_manager = MWS_Scraper_Manager::get_instance();
            $available = $scraper_manager->get_available_sources();
            foreach ($available as $id => $name) {
                $all_sources[$id] = $name;
            }
        }
        
        // Fallback if scraper manager not available
        if (empty($all_sources)) {
            $all_sources = [
                'manhwaku'  => 'Manhwaku.id',
                'asura'     => 'Asura Scans',
                'komikcast' => 'Komikcast',
                'kiryuu'    => 'Kiryuu',
                'komikindo' => 'Komikindo',
                'mangaplus' => 'MangaPlus',
            ];
        }
        
        $sources = [];
        
        // First add enabled sources
        foreach ($all_sources as $id => $name) {
            if (in_array($id, $enabled)) {
                $sources[] = [
                    'id'     => $id,
                    'name'   => $name,
                    'active' => true,
                ];
            }
        }
        
        // Then add disabled sources
        foreach ($all_sources as $id => $name) {
            if (!in_array($id, $enabled)) {
                $sources[] = [
                    'id'     => $id,
                    'name'   => $name,
                    'active' => false,
                ];
            }
        }
        
        return $sources;
    }
    
    /**
     * Get activity icon
     */
    private function get_activity_icon($type) {
        switch ($type) {
            case 'is-success':
                return '✓';
            case 'is-error':
                return '✕';
            case 'is-info':
            default:
                return 'ℹ';
        }
    }
    
    /**
     * Format time ago
     */
    private function time_ago($datetime) {
        $timestamp = strtotime($datetime);
        if (!$timestamp) return __('Unknown', 'manhwa-scraper');
        
        $diff = current_time('timestamp') - $timestamp;
        
        if ($diff < 60) {
            return __('Just now', 'manhwa-scraper');
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            /* translators: %d: number of minutes */
            return sprintf(_n('%d min ago', '%d mins ago', $mins, 'manhwa-scraper'), $mins);
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            /* translators: %d: number of hours */
            return sprintf(_n('%d hour ago', '%d hours ago', $hours, 'manhwa-scraper'), $hours);
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            /* translators: %d: number of days */
            return sprintf(_n('%d day ago', '%d days ago', $days, 'manhwa-scraper'), $days);
        } else {
            return date_i18n('M j, Y', $timestamp);
        }
    }
}
