<?php
/**
 * MWS Logger Class
 * Logs scraping activities to database for history tracking
 */

if (!defined('ABSPATH')) {
    exit;
}

class MWS_Logger {
    
    /**
     * Table name
     */
    private static $table_name = 'mws_scrape_logs';
    
    /**
     * Get full table name with prefix
     */
    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . self::$table_name;
    }
    
    /**
     * Create log table on plugin activation
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            manhwa_id BIGINT(20) UNSIGNED DEFAULT 0,
            manhwa_title VARCHAR(500) DEFAULT '',
            action VARCHAR(100) NOT NULL DEFAULT 'scrape',
            type VARCHAR(50) DEFAULT 'auto_image_scraper',
            status VARCHAR(20) DEFAULT 'success',
            chapters_scraped INT DEFAULT 0,
            images_found INT DEFAULT 0,
            images_downloaded INT DEFAULT 0,
            total_size BIGINT(20) DEFAULT 0,
            message TEXT,
            details LONGTEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY manhwa_id (manhwa_id),
            KEY type (type),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Log a message
     *
     * @param string $message Log message
     * @param string $type Log type (auto_image_scraper, auto_update, etc.)
     * @param string $status Status (success, error, info)
     * @param array $data Additional data
     */
    public static function log($message, $type = 'general', $status = 'info', $data = []) {
        global $wpdb;
        
        $table_name = self::get_table_name();
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            self::create_table();
        }
        
        $insert_data = [
            'manhwa_id' => isset($data['manhwa_id']) ? intval($data['manhwa_id']) : 0,
            'manhwa_title' => isset($data['manhwa_title']) ? sanitize_text_field($data['manhwa_title']) : '',
            'action' => isset($data['action']) ? sanitize_text_field($data['action']) : 'scrape',
            'type' => sanitize_text_field($type),
            'status' => sanitize_text_field($status),
            'chapters_scraped' => isset($data['chapters_scraped']) ? intval($data['chapters_scraped']) : 0,
            'images_found' => isset($data['images_found']) ? intval($data['images_found']) : 0,
            'images_downloaded' => isset($data['images_downloaded']) ? intval($data['images_downloaded']) : 0,
            'total_size' => isset($data['total_size']) ? intval($data['total_size']) : 0,
            'message' => sanitize_text_field($message),
            'details' => isset($data['details']) ? wp_json_encode($data['details']) : '',
            'created_at' => current_time('mysql'),
        ];
        
        $wpdb->insert($table_name, $insert_data);
        
        // Also log to error_log for debugging
        error_log('[MWS ' . strtoupper($type) . '] ' . $message);
        
        return $wpdb->insert_id;
    }
    
    /**
     * Log image scraper activity
     */
    public static function log_image_scrape($manhwa_id, $manhwa_title, $chapters_scraped, $images_found, $images_downloaded, $total_size, $status = 'success', $details = []) {
        $message = sprintf(
            '%s: %d chapters, %d images, %s downloaded',
            $manhwa_title,
            $chapters_scraped,
            $images_found,
            self::format_bytes($total_size)
        );
        
        return self::log($message, 'auto_image_scraper', $status, [
            'manhwa_id' => $manhwa_id,
            'manhwa_title' => $manhwa_title,
            'action' => 'image_scrape',
            'chapters_scraped' => $chapters_scraped,
            'images_found' => $images_found,
            'images_downloaded' => $images_downloaded,
            'total_size' => $total_size,
            'details' => $details,
        ]);
    }
    
    /**
     * Get logs with pagination
     */
    public static function get_logs($args = []) {
        global $wpdb;
        
        $defaults = [
            'type' => '',
            'status' => '',
            'manhwa_id' => 0,
            'limit' => 50,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'date_from' => '',
            'date_to' => '',
        ];
        
        $args = wp_parse_args($args, $defaults);
        $table_name = self::get_table_name();
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        if (!$table_exists) {
            return [];
        }
        
        $where = ['1=1'];
        $values = [];
        
        if (!empty($args['type'])) {
            $where[] = 'type = %s';
            $values[] = $args['type'];
        }
        
        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }
        
        if ($args['manhwa_id'] > 0) {
            $where[] = 'manhwa_id = %d';
            $values[] = $args['manhwa_id'];
        }
        
        if (!empty($args['date_from'])) {
            $where[] = 'created_at >= %s';
            $values[] = $args['date_from'];
        }
        
        if (!empty($args['date_to'])) {
            $where[] = 'created_at <= %s';
            $values[] = $args['date_to'];
        }
        
        $where_sql = implode(' AND ', $where);
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        
        $sql = "SELECT * FROM $table_name WHERE $where_sql ORDER BY $orderby LIMIT %d OFFSET %d";
        $values[] = $args['limit'];
        $values[] = $args['offset'];
        
        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }
        
        return $wpdb->get_results($sql, ARRAY_A);
    }
    
    /**
     * Get log count
     */
    public static function get_log_count($args = []) {
        global $wpdb;
        
        $table_name = self::get_table_name();
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        if (!$table_exists) {
            return 0;
        }
        
        $where = ['1=1'];
        $values = [];
        
        if (!empty($args['type'])) {
            $where[] = 'type = %s';
            $values[] = $args['type'];
        }
        
        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }
        
        $where_sql = implode(' AND ', $where);
        
        $sql = "SELECT COUNT(*) FROM $table_name WHERE $where_sql";
        
        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }
        
        return (int) $wpdb->get_var($sql);
    }
    
    /**
     * Get summary stats
     */
    public static function get_summary_stats($type = 'auto_image_scraper', $days = 7) {
        global $wpdb;
        
        $table_name = self::get_table_name();
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        if (!$table_exists) {
            return [
                'total_scrapes' => 0,
                'total_chapters' => 0,
                'total_images' => 0,
                'total_size' => 0,
                'success_count' => 0,
                'error_count' => 0,
            ];
        }
        
        $date_limit = date('Y-m-d H:i:s', strtotime("-$days days"));
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_scrapes,
                COALESCE(SUM(chapters_scraped), 0) as total_chapters,
                COALESCE(SUM(images_found), 0) as total_images,
                COALESCE(SUM(total_size), 0) as total_size,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_count,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as error_count
            FROM $table_name 
            WHERE type = %s 
            AND created_at >= %s
        ", $type, $date_limit), ARRAY_A);
    }
    
    /**
     * Get daily stats for chart
     */
    public static function get_daily_stats($type = 'auto_image_scraper', $days = 30) {
        global $wpdb;
        
        $table_name = self::get_table_name();
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        if (!$table_exists) {
            return [];
        }
        
        $date_limit = date('Y-m-d', strtotime("-$days days"));
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as scrapes,
                COALESCE(SUM(chapters_scraped), 0) as chapters,
                COALESCE(SUM(images_found), 0) as images,
                COALESCE(SUM(total_size), 0) as total_size
            FROM $table_name 
            WHERE type = %s 
            AND DATE(created_at) >= %s
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", $type, $date_limit), ARRAY_A);
    }
    
    /**
     * Delete old logs
     */
    public static function cleanup_old_logs($days = 30) {
        global $wpdb;
        
        $table_name = self::get_table_name();
        $date_limit = date('Y-m-d H:i:s', strtotime("-$days days"));
        
        return $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE created_at < %s",
            $date_limit
        ));
    }
    
    /**
     * Format bytes to human readable
     */
    public static function format_bytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    /**
     * Get top scraped manhwa
     */
    public static function get_top_scraped($limit = 10, $days = 30) {
        global $wpdb;
        
        $table_name = self::get_table_name();
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        if (!$table_exists) {
            return [];
        }
        
        $date_limit = date('Y-m-d H:i:s', strtotime("-$days days"));
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                manhwa_id,
                manhwa_title,
                COUNT(*) as scrape_count,
                SUM(chapters_scraped) as total_chapters,
                SUM(images_found) as total_images,
                SUM(total_size) as total_size,
                MAX(created_at) as last_scraped
            FROM $table_name 
            WHERE type = 'auto_image_scraper'
            AND manhwa_id > 0
            AND created_at >= %s
            GROUP BY manhwa_id, manhwa_title
            ORDER BY total_size DESC
            LIMIT %d
        ", $date_limit, $limit), ARRAY_A);
    }
}

// Create table on plugin load if not exists
add_action('plugins_loaded', function() {
    MWS_Logger::create_table();
});
