<?php
/**
 * Plugin Name: Manhwa Metadata Scraper
 * Plugin URI: http://localhost/wordpress/
 * Description: Multi-source manhwa/manga metadata scraper with anti-blocking features. Supports Manhwaku.id, Asura Scans, and more.
 * Version: 1.0.0
 * Author: Manhwa Reader
 * Author URI: http://localhost/
 * Text Domain: manhwa-scraper
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MWS_VERSION', '1.0.0');
define('MWS_PATH', plugin_dir_path(__FILE__));
define('MWS_URL', plugin_dir_url(__FILE__));
define('MWS_BASENAME', plugin_basename(__FILE__));

/**
 * Autoloader for plugin classes
 */
spl_autoload_register(function ($class) {
    $prefix = 'MWS_';
    
    if (strpos($class, $prefix) !== 0) {
        return;
    }
    
    $class_name = str_replace($prefix, '', $class);
    $class_name = strtolower($class_name);
    $class_name = str_replace('_', '-', $class_name);
    
    // Check in includes directory
    $file = MWS_PATH . 'includes/class-' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }
    
    // Check in scrapers directory
    $file = MWS_PATH . 'includes/scrapers/class-' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }
    
    // Check in admin directory
    $file = MWS_PATH . 'includes/admin/class-' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }
});

/**
 * Main Plugin Class
 */
class Manhwa_Scraper {
    
    private static $instance = null;
    
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
     * Initialize plugin
     */
    public static function init() {
        return self::get_instance();
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        // Core classes
        require_once MWS_PATH . 'includes/class-rate-limiter.php';
        require_once MWS_PATH . 'includes/class-user-agent-rotator.php';
        require_once MWS_PATH . 'includes/class-http-client.php';
        require_once MWS_PATH . 'includes/class-html-parser.php';
        require_once MWS_PATH . 'includes/class-image-downloader.php';
        require_once MWS_PATH . 'includes/class-json-exporter.php';
        require_once MWS_PATH . 'includes/class-cron-handler.php';
        require_once MWS_PATH . 'includes/class-queue-manager.php';
        require_once MWS_PATH . 'includes/class-logger.php';
        
        // Scrapers
        require_once MWS_PATH . 'includes/scrapers/class-scraper-base.php';
        require_once MWS_PATH . 'includes/scrapers/class-manhwaku-scraper.php';
        // require_once MWS_PATH . 'includes/scrapers/class-asura-scraper.php'; // File doesn't exist
        require_once MWS_PATH . 'includes/scrapers/class-komikcast-scraper.php';
        require_once MWS_PATH . 'includes/scrapers/class-manhwaland-scraper.php';
        require_once MWS_PATH . 'includes/scrapers/class-manhwaindo-scraper.php';
        require_once MWS_PATH . 'includes/scrapers/class-mangasusuku-scraper.php';
        require_once MWS_PATH . 'includes/class-scraper-manager.php';
        require_once MWS_PATH . 'includes/class-duplicate-detector.php';
        
        // Integration
        require_once MWS_PATH . 'includes/class-manager-bridge.php';
        
        // Auto Pilot
        require_once MWS_PATH . 'includes/class-auto-discovery.php';
        require_once MWS_PATH . 'includes/class-auto-cleanup.php';
        require_once MWS_PATH . 'includes/class-auto-image-scraper.php';
        require_once MWS_PATH . 'includes/class-broken-image-fixer.php';
        
        // Language
        require_once MWS_PATH . 'includes/class-language-loader.php';
        
        // Admin
        require_once MWS_PATH . 'includes/admin/class-admin-pages.php';
        require_once MWS_PATH . 'includes/admin/class-ajax-handler.php';
        require_once MWS_PATH . 'includes/admin/class-dashboard-widget.php';
        
        // REST API
        require_once MWS_PATH . 'includes/class-rest-api.php';
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Deactivation hook only (activation is handled by mws_activate function)
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        // Admin initialization
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // Custom cron schedules
        add_filter('cron_schedules', [$this, 'add_custom_cron_schedules']);
        
        // Initialize components
        add_action('init', [$this, 'init_components']);
        
        // AJAX handlers
        add_action('wp_ajax_mws_scrape_single', ['MWS_Ajax_Handler', 'scrape_single']);
        add_action('wp_ajax_mws_scrape_chapter_images', ['MWS_Ajax_Handler', 'scrape_chapter_images']);
        add_action('wp_ajax_mws_save_chapter_to_post', ['MWS_Ajax_Handler', 'save_chapter_to_post']);
        add_action('wp_ajax_mws_scrape_bulk', ['MWS_Ajax_Handler', 'scrape_bulk']);
        add_action('wp_ajax_mws_import_manhwa', ['MWS_Ajax_Handler', 'import_manhwa']);
        add_action('wp_ajax_mws_download_cover', ['MWS_Ajax_Handler', 'download_cover']);
        add_action('wp_ajax_mws_get_sources', ['MWS_Ajax_Handler', 'get_sources']);
        add_action('wp_ajax_mws_test_connection', ['MWS_Ajax_Handler', 'test_connection']);
        add_action('wp_ajax_mws_get_manhwa_chapters', ['MWS_Ajax_Handler', 'get_manhwa_chapters']);
        add_action('wp_ajax_mws_download_chapter_images', ['MWS_Ajax_Handler', 'download_chapter_images']);
        add_action('wp_ajax_mws_get_statistics', ['MWS_Ajax_Handler', 'get_statistics']);
        add_action('wp_ajax_mws_search_manhwa', ['MWS_Ajax_Handler', 'search_manhwa']);
        add_action('wp_ajax_mws_browse_manhwa', ['MWS_Ajax_Handler', 'browse_manhwa']);
        add_action('wp_ajax_mws_check_duplicate', ['MWS_Ajax_Handler', 'check_duplicate']);
        add_action('wp_ajax_mws_test_proxy', ['MWS_Ajax_Handler', 'test_proxy']);
        
        // Queue AJAX handlers
        add_action('wp_ajax_mws_add_to_queue', ['MWS_Ajax_Handler', 'add_to_queue']);
        add_action('wp_ajax_mws_get_queue', ['MWS_Ajax_Handler', 'get_queue']);
        add_action('wp_ajax_mws_process_queue_item', ['MWS_Ajax_Handler', 'process_queue_item']);
        add_action('wp_ajax_mws_retry_failed', ['MWS_Ajax_Handler', 'retry_failed']);
        add_action('wp_ajax_mws_clear_queue', ['MWS_Ajax_Handler', 'clear_queue']);
        add_action('wp_ajax_mws_delete_queue_item', ['MWS_Ajax_Handler', 'delete_queue_item']);
        add_action('wp_ajax_mws_reset_stuck_processing', ['MWS_Ajax_Handler', 'reset_stuck_processing']);
        add_action('wp_ajax_mws_force_update_check', ['MWS_Ajax_Handler', 'force_update_check']);
        add_action('wp_ajax_mws_trigger_image_scraper', ['MWS_Ajax_Handler', 'trigger_image_scraper']);
        add_action('wp_ajax_mws_run_image_scraper', ['MWS_Ajax_Handler', 'run_image_scraper']);
        add_action('wp_ajax_mws_run_discovery', ['MWS_Ajax_Handler', 'run_discovery']);
        add_action('wp_ajax_mws_get_manhwa_stats', ['MWS_Ajax_Handler', 'get_manhwa_stats']);
        add_action('wp_ajax_mws_stats_admin_tools', ['MWS_Ajax_Handler', 'stats_admin_tools']);
        
        // URL Migration handlers
        add_action('wp_ajax_mws_migrate_scan_urls', ['MWS_Ajax_Handler', 'migrate_scan_urls']);
        add_action('wp_ajax_mws_migrate_execute', ['MWS_Ajax_Handler', 'migrate_execute']);
    }
    
    /**
     * Add custom cron schedules
     */
    public function add_custom_cron_schedules($schedules) {
        // Every 2 hours
        $schedules['mws_every_2_hours'] = [
            'interval' => 2 * HOUR_IN_SECONDS,
            'display' => __('Every 2 Hours', 'manhwa-scraper'),
        ];
        
        // Every 4 hours
        $schedules['mws_every_4_hours'] = [
            'interval' => 4 * HOUR_IN_SECONDS,
            'display' => __('Every 4 Hours', 'manhwa-scraper'),
        ];
        
        // Every 6 hours
        $schedules['mws_every_6_hours'] = [
            'interval' => 6 * HOUR_IN_SECONDS,
            'display' => __('Every 6 Hours', 'manhwa-scraper'),
        ];
        
        return $schedules;
    }
    
    /**
     * Initialize plugin components
     */
    public function init_components() {
        // Initialize scraper manager
        MWS_Scraper_Manager::get_instance();
        
        // Initialize cron handler
        MWS_Cron_Handler::get_instance();
        
        // Initialize dashboard widget (admin only)
        if (is_admin()) {
            MWS_Dashboard_Widget::get_instance();
        }
        
        // Add technology detection fingerprints (frontend only)
        add_action('wp_head', [$this, 'add_tech_fingerprint'], 1);
    }
    
    /**
     * Add technology fingerprints for detection by Wappalyzer, BuiltWith, etc.
     */
    public function add_tech_fingerprint() {
        if (is_admin()) {
            return;
        }
        
        echo '<!-- Manhwa Scraper v' . MWS_VERSION . ' -->' . "\n";
        echo '<meta name="generator" content="Manhwa Scraper ' . MWS_VERSION . '" />' . "\n";
        echo '<script>window.ManhwaScraper={version:"' . MWS_VERSION . '"};</script>' . "\n";
    }
    
    /**
     * Register admin menu
     */
    public function register_admin_menu() {
        add_menu_page(
            __('Manhwa Scraper', 'manhwa-scraper'),
            __('Manhwa Scraper', 'manhwa-scraper'),
            'manage_options',
            'manhwa-scraper',
            ['MWS_Admin_Pages', 'render_dashboard'],
            'dashicons-download',
            30
        );
        
        add_submenu_page(
            'manhwa-scraper',
            __('Dashboard', 'manhwa-scraper'),
            __('Dashboard', 'manhwa-scraper'),
            'manage_options',
            'manhwa-scraper',
            ['MWS_Admin_Pages', 'render_dashboard']
        );
        
        add_submenu_page(
            'manhwa-scraper',
            __('Import Single', 'manhwa-scraper'),
            __('Import Single', 'manhwa-scraper'),
            'manage_options',
            'manhwa-scraper-import',
            ['MWS_Admin_Pages', 'render_import']
        );
        
        add_submenu_page(
            'manhwa-scraper',
            __('Search', 'manhwa-scraper'),
            __('Search', 'manhwa-scraper'),
            'manage_options',
            'manhwa-scraper-search',
            ['MWS_Admin_Pages', 'render_search']
        );
        
        add_submenu_page(
            'manhwa-scraper',
            __('Bulk Scrape', 'manhwa-scraper'),
            __('Bulk Scrape', 'manhwa-scraper'),
            'manage_options',
            'manhwa-scraper-bulk',
            ['MWS_Admin_Pages', 'render_bulk']
        );
        
        add_submenu_page(
            'manhwa-scraper',
            __('Chapter Scraper', 'manhwa-scraper'),
            __('Chapter Scraper', 'manhwa-scraper'),
            'manage_options',
            'manhwa-scraper-chapter',
            ['MWS_Admin_Pages', 'render_chapter']
        );
        
        add_submenu_page(
            'manhwa-scraper',
            __('Download Queue', 'manhwa-scraper'),
            __('Download Queue', 'manhwa-scraper'),
            'manage_options',
            'manhwa-scraper-queue',
            ['MWS_Admin_Pages', 'render_queue']
        );
        
        add_submenu_page(
            'manhwa-scraper',
            __('History', 'manhwa-scraper'),
            __('History', 'manhwa-scraper'),
            'manage_options',
            'manhwa-scraper-history',
            ['MWS_Admin_Pages', 'render_history']
        );
        
        add_submenu_page(
            'manhwa-scraper',
            __('Statistics', 'manhwa-scraper'),
            __('Statistics', 'manhwa-scraper'),
            'manage_options',
            'manhwa-scraper-statistics',
            ['MWS_Admin_Pages', 'render_statistics']
        );
        
        add_submenu_page(
            'manhwa-scraper',
            __('Auto Update Stats', 'manhwa-scraper'),
            __('Auto Update Stats', 'manhwa-scraper'),
            'manage_options',
            'manhwa-scraper-auto-update-stats',
            ['MWS_Admin_Pages', 'render_auto_update_stats']
        );
        
        add_submenu_page(
            'manhwa-scraper',
            __('Settings', 'manhwa-scraper'),
            __('Settings', 'manhwa-scraper'),
            'manage_options',
            'manhwa-scraper-settings',
            ['MWS_Admin_Pages', 'render_settings']
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'manhwa-scraper') === false) {
            return;
        }
        
        // CSS
        wp_enqueue_style(
            'mws-admin',
            MWS_URL . 'assets/css/admin.css',
            [],
            MWS_VERSION
        );
        
        // JS
        wp_enqueue_script(
            'mws-admin',
            MWS_URL . 'assets/js/admin.js',
            ['jquery'],
            time(), // Use timestamp to force reload on changes
            true
        );
        
        // Localize script
        wp_localize_script('mws-admin', 'mwsData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mws_nonce'),
            'strings' => [
                'scraping' => __('Scraping...', 'manhwa-scraper'),
                'success' => __('Success!', 'manhwa-scraper'),
                'error' => __('Error occurred', 'manhwa-scraper'),
                'confirm_import' => __('Import this manhwa?', 'manhwa-scraper'),
            ]
        ]);
    }
    
    /**
     * Plugin activation (backup - main activation is in mws_activate function)
     */
    public function activate() {
        // Call the main activation function
        if (function_exists('mws_activate')) {
            mws_activate();
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled cron
        MWS_Cron_Handler::unschedule();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mws_logs';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            source varchar(100) NOT NULL,
            url text NOT NULL,
            status varchar(50) NOT NULL,
            type varchar(50) DEFAULT 'scrape',
            message text,
            data longtext,
            duration_ms int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY source (source),
            KEY status (status),
            KEY type (type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Add new columns if they don't exist (for existing installations)
        $this->maybe_add_column($table_name, 'type', "ALTER TABLE $table_name ADD COLUMN type varchar(50) DEFAULT 'scrape' AFTER status");
        $this->maybe_add_column($table_name, 'duration_ms', "ALTER TABLE $table_name ADD COLUMN duration_ms int(11) DEFAULT 0 AFTER data");
        
        // Create queue table
        MWS_Queue_Manager::create_table();
    }
    
    /**
     * Add column if not exists
     */
    private function maybe_add_column($table_name, $column_name, $sql) {
        global $wpdb;
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE '$column_name'");
        if (empty($column_exists)) {
            $wpdb->query($sql);
        }
    }
    
    /**
     * Set default options
     */
    private function set_default_options() {
        $defaults = [
            'rate_limit' => 10, // requests per minute
            'delay_between_requests' => 2000, // milliseconds
            'cron_schedule' => 'twicedaily',
            'auto_download_covers' => true,
            'user_agents' => [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15',
            ],
            'enabled_sources' => ['manhwaku', 'asura'],
        ];
        
        foreach ($defaults as $key => $value) {
            if (get_option('mws_' . $key) === false) {
                update_option('mws_' . $key, $value);
            }
        }
    }
    
    /**
     * Get scraper manager instance
     */
    public static function scraper_manager() {
        return MWS_Scraper_Manager::get_instance();
    }
}


/**
 * Initialize plugin
 */
function mws_init() {
    return Manhwa_Scraper::init();
}
add_action('plugins_loaded', 'mws_init');

/**
 * Plugin activation
 */
function mws_activate() {
    global $wpdb;
    
    // Include upgrade.php for dbDelta
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // ==========================================
    // Create mws_logs table
    // ==========================================
    $table_logs = $wpdb->prefix . 'mws_logs';
    $sql_logs = "CREATE TABLE IF NOT EXISTS $table_logs (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        source varchar(100) NOT NULL,
        url text NOT NULL,
        status varchar(50) NOT NULL,
        type varchar(50) DEFAULT 'scrape',
        message text,
        data longtext,
        duration_ms int(11) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY source (source),
        KEY status (status),
        KEY type (type),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    dbDelta($sql_logs);
    
    // ==========================================
    // Create mws_download_queue table
    // ==========================================
    $table_queue = $wpdb->prefix . 'mws_download_queue';
    $sql_queue = "CREATE TABLE IF NOT EXISTS $table_queue (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        manhwa_id bigint(20) NOT NULL,
        manhwa_title varchar(255) NOT NULL,
        chapter_number varchar(50) NOT NULL,
        chapter_title varchar(255) NOT NULL,
        chapter_url text NOT NULL,
        status varchar(20) DEFAULT 'pending',
        priority int(11) DEFAULT 10,
        retry_count int(11) DEFAULT 0,
        max_retries int(11) DEFAULT 3,
        images_total int(11) DEFAULT 0,
        images_downloaded int(11) DEFAULT 0,
        error_message text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        started_at datetime DEFAULT NULL,
        completed_at datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY manhwa_id (manhwa_id),
        KEY status (status),
        KEY priority (priority)
    ) $charset_collate;";
    
    dbDelta($sql_queue);
    
    // ==========================================
    // Set default options
    // ==========================================
    $defaults = array(
        'mws_rate_limit' => 10,
        'mws_delay_between_requests' => 2000,
        'mws_cron_schedule' => 'twicedaily',
        'mws_auto_download_covers' => true,
        'mws_user_agents' => array(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15',
        ),
        'mws_enabled_sources' => array('manhwaku', 'komikcast', 'manhwaland', 'manhwaindo', 'mangasusuku'),
    );
    
    foreach ($defaults as $key => $value) {
        if (get_option($key) === false) {
            update_option($key, $value);
        }
    }
    
    // ==========================================
    // Schedule cron jobs if enabled
    // ==========================================
    if (get_option('mws_auto_discovery_enabled', false) && class_exists('MWS_Auto_Discovery')) {
        MWS_Auto_Discovery::schedule();
    }
    
    if (get_option('mws_auto_cleanup_enabled', false) && class_exists('MWS_Auto_Cleanup')) {
        MWS_Auto_Cleanup::schedule();
    }
    
    if (get_option('mws_auto_image_scraper_enabled', false) && class_exists('MWS_Auto_Image_Scraper')) {
        MWS_Auto_Image_Scraper::schedule();
    }
    
    if (get_option('mws_auto_update_enabled', false) && class_exists('MWS_Cron_Handler')) {
        MWS_Cron_Handler::schedule();
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Log activation
    error_log('Manhwa Scraper: Plugin activated. Database tables created.');
}
register_activation_hook(__FILE__, 'mws_activate');

/**
 * Plugin deactivation
 */
function mws_deactivate() {
    // Unschedule all cron jobs
    MWS_Auto_Discovery::unschedule();
    MWS_Auto_Cleanup::unschedule();
    MWS_Auto_Image_Scraper::unschedule();
    MWS_Cron_Handler::unschedule();
}
register_deactivation_hook(__FILE__, 'mws_deactivate');

/**
 * Helper function to get plugin instance
 */
function MWS() {
    return Manhwa_Scraper::get_instance();
}
