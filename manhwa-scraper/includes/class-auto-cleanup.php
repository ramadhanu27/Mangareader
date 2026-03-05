<?php
/**
 * Auto Cleanup System
 * Automatically clean old data and optimize database
 */

if (!defined('ABSPATH')) {
    exit;
}

class MWS_Auto_Cleanup {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Cron hook name
     */
    const HOOK_NAME = 'mws_auto_cleanup';
    
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
        add_action(self::HOOK_NAME, [$this, 'run_cleanup']);
    }
    
    /**
     * Schedule cleanup cron
     */
    public static function schedule() {
        if (!wp_next_scheduled(self::HOOK_NAME)) {
            wp_schedule_event(time(), 'weekly', self::HOOK_NAME);
        }
    }
    
    /**
     * Unschedule cleanup cron
     */
    public static function unschedule() {
        $timestamp = wp_next_scheduled(self::HOOK_NAME);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::HOOK_NAME);
        }
    }
    
    /**
     * Run cleanup process
     */
    public function run_cleanup() {
        if (!get_option('mws_auto_cleanup_enabled', false)) {
            return;
        }
        
        $this->log('Starting auto cleanup...');
        
        $stats = [
            'logs_deleted' => 0,
            'orphan_meta_deleted' => 0,
            'old_images_deleted' => 0,
            'temp_files_deleted' => 0,
            'database_optimized' => false,
        ];
        
        // 1. Clean old logs
        $stats['logs_deleted'] = $this->clean_old_logs();
        
        // 2. Clean orphan metadata
        $stats['orphan_meta_deleted'] = $this->clean_orphan_metadata();
        
        // 3. Clean old temporary images
        $stats['old_images_deleted'] = $this->clean_old_temp_images();
        
        // 4. Clean temporary files
        $stats['temp_files_deleted'] = $this->clean_temp_files();
        
        // 5. Optimize database tables
        $stats['database_optimized'] = $this->optimize_database();
        
        $this->log(sprintf(
            'Cleanup completed. Logs: %d, Orphan Meta: %d, Images: %d, Temp Files: %d, DB Optimized: %s',
            $stats['logs_deleted'],
            $stats['orphan_meta_deleted'],
            $stats['old_images_deleted'],
            $stats['temp_files_deleted'],
            $stats['database_optimized'] ? 'Yes' : 'No'
        ));
        
        // Save stats
        update_option('mws_last_cleanup_stats', $stats);
        update_option('mws_last_cleanup_time', current_time('mysql'));
        
        // Fire action
        do_action('mws_cleanup_completed', $stats);
        
        return $stats;
    }
    
    /**
     * Clean old logs
     */
    private function clean_old_logs() {
        global $wpdb;
        
        $days_to_keep = get_option('mws_cleanup_logs_days', 30);
        $table_name = $wpdb->prefix . 'mws_logs';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return 0;
        }
        
        $deleted = $wpdb->query($wpdb->prepare("
            DELETE FROM $table_name
            WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $days_to_keep));
        
        $this->log("Deleted $deleted old log entries (older than $days_to_keep days)");
        
        return (int) $deleted;
    }
    
    /**
     * Clean orphan metadata
     */
    private function clean_orphan_metadata() {
        global $wpdb;
        
        // Delete postmeta for non-existent posts
        $deleted = $wpdb->query("
            DELETE pm FROM {$wpdb->postmeta} pm
            LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.ID IS NULL
        ");
        
        $this->log("Deleted $deleted orphan postmeta entries");
        
        return (int) $deleted;
    }
    
    /**
     * Clean old temporary images
     */
    private function clean_old_temp_images() {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/manhwa/temp/';
        
        if (!file_exists($temp_dir)) {
            return 0;
        }
        
        $days_to_keep = get_option('mws_cleanup_temp_images_days', 7);
        $cutoff_time = time() - ($days_to_keep * DAY_IN_SECONDS);
        $deleted = 0;
        
        $files = glob($temp_dir . '*');
        
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoff_time) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }
        
        $this->log("Deleted $deleted temporary images (older than $days_to_keep days)");
        
        return $deleted;
    }
    
    /**
     * Clean temporary files
     */
    private function clean_temp_files() {
        $temp_dirs = [
            WP_CONTENT_DIR . '/uploads/manhwa/temp/',
            WP_CONTENT_DIR . '/cache/manhwa/',
        ];
        
        $deleted = 0;
        
        foreach ($temp_dirs as $dir) {
            if (!file_exists($dir)) {
                continue;
            }
            
            $files = glob($dir . '*.tmp');
            
            foreach ($files as $file) {
                if (is_file($file) && unlink($file)) {
                    $deleted++;
                }
            }
        }
        
        $this->log("Deleted $deleted temporary files");
        
        return $deleted;
    }
    
    /**
     * Optimize database tables
     */
    private function optimize_database() {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'mws_logs',
            $wpdb->posts,
            $wpdb->postmeta,
        ];
        
        $optimized = 0;
        
        foreach ($tables as $table) {
            // Check if table exists
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
                continue;
            }
            
            $result = $wpdb->query("OPTIMIZE TABLE $table");
            
            if ($result !== false) {
                $optimized++;
            }
        }
        
        $this->log("Optimized $optimized database tables");
        
        return $optimized > 0;
    }
    
    /**
     * Clean specific manhwa data (for dropped/cancelled)
     */
    public function clean_dropped_manhwa() {
        global $wpdb;
        
        if (!get_option('mws_cleanup_dropped_manhwa', false)) {
            return 0;
        }
        
        $days_inactive = get_option('mws_cleanup_dropped_days', 90);
        
        // Find dropped/cancelled manhwa not updated in X days
        $manhwa_to_clean = $wpdb->get_col($wpdb->prepare("
            SELECT p.ID
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_manhwa_status'
            LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_mws_last_updated'
            WHERE p.post_type = 'manhwa'
            AND pm1.meta_value IN ('dropped', 'cancelled')
            AND (
                pm2.meta_value IS NULL 
                OR pm2.meta_value < DATE_SUB(NOW(), INTERVAL %d DAY)
            )
        ", $days_inactive));
        
        $deleted = 0;
        
        foreach ($manhwa_to_clean as $post_id) {
            // Move to trash instead of permanent delete
            if (wp_trash_post($post_id)) {
                $deleted++;
                $this->log("Moved dropped manhwa to trash: Post ID $post_id");
            }
        }
        
        $this->log("Cleaned $deleted dropped/cancelled manhwa");
        
        return $deleted;
    }
    
    /**
     * Clean duplicate images
     */
    public function clean_duplicate_images() {
        $upload_dir = wp_upload_dir();
        $manhwa_dir = $upload_dir['basedir'] . '/manhwa/';
        
        if (!file_exists($manhwa_dir)) {
            return 0;
        }
        
        $deleted = 0;
        $file_hashes = [];
        
        // Scan all images
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($manhwa_dir)
        );
        
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            
            $filepath = $file->getPathname();
            $hash = md5_file($filepath);
            
            if (isset($file_hashes[$hash])) {
                // Duplicate found, delete
                if (unlink($filepath)) {
                    $deleted++;
                    $this->log("Deleted duplicate image: " . basename($filepath));
                }
            } else {
                $file_hashes[$hash] = $filepath;
            }
        }
        
        $this->log("Deleted $deleted duplicate images");
        
        return $deleted;
    }
    
    /**
     * Get cleanup statistics
     */
    public function get_cleanup_stats() {
        global $wpdb;
        
        $stats = [];
        
        // Count old logs
        $days_to_keep = get_option('mws_cleanup_logs_days', 30);
        $table_name = $wpdb->prefix . 'mws_logs';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
            $stats['old_logs'] = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*)
                FROM $table_name
                WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)
            ", $days_to_keep));
        } else {
            $stats['old_logs'] = 0;
        }
        
        // Count orphan metadata
        $stats['orphan_meta'] = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->postmeta} pm
            LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.ID IS NULL
        ");
        
        // Count dropped manhwa
        $stats['dropped_manhwa'] = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'manhwa'
            AND pm.meta_key = '_manhwa_status'
            AND pm.meta_value IN ('dropped', 'cancelled')
        ");
        
        // Disk space used
        $upload_dir = wp_upload_dir();
        $manhwa_dir = $upload_dir['basedir'] . '/manhwa/';
        $stats['disk_space_mb'] = $this->get_directory_size($manhwa_dir) / 1024 / 1024;
        
        return $stats;
    }
    
    /**
     * Get directory size
     */
    private function get_directory_size($dir) {
        if (!file_exists($dir)) {
            return 0;
        }
        
        $size = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
        
        return $size;
    }
    
    /**
     * Log message
     */
    private function log($message) {
        if (class_exists('MWS_Logger')) {
            MWS_Logger::log($message, 'auto_cleanup');
        }
        error_log('[MWS Auto Cleanup] ' . $message);
    }
}

// Initialize
MWS_Auto_Cleanup::get_instance();
