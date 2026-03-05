<?php
/**
 * Download Queue Manager Class
 * Handles queuing and processing of chapter image downloads
 */

if (!defined('ABSPATH')) {
    exit;
}

class MWS_Queue_Manager {
    
    private static $instance = null;
    private $table_name;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'mws_download_queue';
    }
    
    /**
     * Create queue table on plugin activation
     */
    public static function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mws_download_queue';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
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
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Add item to queue
     */
    public function add_to_queue($manhwa_id, $chapter_data, $priority = 10) {
        global $wpdb;
        
        // Check if already in queue
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE manhwa_id = %d AND chapter_number = %s AND status IN ('pending', 'processing')",
            $manhwa_id,
            $chapter_data['number'] ?? $chapter_data['title']
        ));
        
        if ($exists) {
            return array('success' => false, 'message' => 'Chapter already in queue');
        }
        
        $manhwa = get_post($manhwa_id);
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'manhwa_id' => $manhwa_id,
                'manhwa_title' => $manhwa ? $manhwa->post_title : 'Unknown',
                'chapter_number' => $chapter_data['number'] ?? $chapter_data['title'] ?? '',
                'chapter_title' => $chapter_data['title'] ?? '',
                'chapter_url' => $chapter_data['url'] ?? '',
                'status' => 'pending',
                'priority' => $priority,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s')
        );
        
        if ($result) {
            return array('success' => true, 'id' => $wpdb->insert_id);
        }
        
        return array('success' => false, 'message' => 'Failed to add to queue');
    }
    
    /**
     * Add all chapters from a manhwa to queue
     */
    public function add_manhwa_to_queue($manhwa_id, $skip_existing = true, $skip_downloaded = true) {
        $chapters = get_post_meta($manhwa_id, '_manhwa_chapters', true);
        
        if (!is_array($chapters) || empty($chapters)) {
            return array('success' => false, 'message' => 'No chapters found');
        }
        
        $added = 0;
        $skipped = 0;
        
        foreach ($chapters as $chapter) {
            // Skip if no URL
            if (empty($chapter['url'])) {
                $skipped++;
                continue;
            }
            
            // Skip if already has images
            if ($skip_existing && !empty($chapter['images']) && is_array($chapter['images'])) {
                // Check if local
                if ($skip_downloaded) {
                    $first_img = $chapter['images'][0];
                    $img_url = is_array($first_img) ? ($first_img['url'] ?? $first_img['src'] ?? '') : $first_img;
                    if (strpos($img_url, '/wp-content/uploads/manhwa/') !== false) {
                        $skipped++;
                        continue;
                    }
                } else {
                    $skipped++;
                    continue;
                }
            }
            
            $result = $this->add_to_queue($manhwa_id, $chapter);
            if ($result['success']) {
                $added++;
            } else {
                $skipped++;
            }
        }
        
        return array(
            'success' => true,
            'added' => $added,
            'skipped' => $skipped
        );
    }
    
    /**
     * Get queue items
     */
    public function get_queue($status = null, $limit = 50, $offset = 0) {
        global $wpdb;
        
        $where = '';
        if ($status) {
            $where = $wpdb->prepare(" WHERE status = %s", $status);
        }
        
        $items = $wpdb->get_results(
            "SELECT * FROM {$this->table_name} {$where} ORDER BY priority ASC, created_at ASC LIMIT {$limit} OFFSET {$offset}"
        );
        
        return $items;
    }
    
    /**
     * Get queue statistics
     */
    public function get_stats() {
        global $wpdb;
        
        $stats = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM {$this->table_name} GROUP BY status",
            OBJECT_K
        );
        
        return array(
            'pending' => isset($stats['pending']) ? $stats['pending']->count : 0,
            'processing' => isset($stats['processing']) ? $stats['processing']->count : 0,
            'completed' => isset($stats['completed']) ? $stats['completed']->count : 0,
            'failed' => isset($stats['failed']) ? $stats['failed']->count : 0,
            'total' => array_sum(array_column((array)$stats, 'count'))
        );
    }
    
    /**
     * Get next pending item
     */
    public function get_next_pending() {
        global $wpdb;
        
        return $wpdb->get_row(
            "SELECT * FROM {$this->table_name} WHERE status = 'pending' ORDER BY priority ASC, created_at ASC LIMIT 1"
        );
    }
    
    /**
     * Update queue item status
     */
    public function update_status($id, $status, $extra_data = array()) {
        global $wpdb;
        
        $data = array('status' => $status);
        $format = array('%s');
        
        if ($status === 'processing') {
            $data['started_at'] = current_time('mysql');
            $format[] = '%s';
        } elseif ($status === 'completed' || $status === 'failed') {
            $data['completed_at'] = current_time('mysql');
            $format[] = '%s';
        }
        
        if (isset($extra_data['images_total'])) {
            $data['images_total'] = $extra_data['images_total'];
            $format[] = '%d';
        }
        
        if (isset($extra_data['images_downloaded'])) {
            $data['images_downloaded'] = $extra_data['images_downloaded'];
            $format[] = '%d';
        }
        
        if (isset($extra_data['error_message'])) {
            $data['error_message'] = $extra_data['error_message'];
            $format[] = '%s';
        }
        
        if (isset($extra_data['retry_count'])) {
            $data['retry_count'] = $extra_data['retry_count'];
            $format[] = '%d';
        }
        
        return $wpdb->update($this->table_name, $data, array('id' => $id), $format, array('%d'));
    }
    
    /**
     * Delete queue item
     */
    public function delete_item($id) {
        global $wpdb;
        return $wpdb->delete($this->table_name, array('id' => $id), array('%d'));
    }
    
    /**
     * Clear queue by status
     */
    public function clear_queue($status = null) {
        global $wpdb;
        
        if ($status) {
            return $wpdb->delete($this->table_name, array('status' => $status), array('%s'));
        }
        
        return $wpdb->query("TRUNCATE TABLE {$this->table_name}");
    }
    
    /**
     * Retry failed items
     */
    public function retry_failed() {
        global $wpdb;
        
        return $wpdb->update(
            $this->table_name,
            array('status' => 'pending', 'error_message' => null),
            array('status' => 'failed'),
            array('%s', '%s'),
            array('%s')
        );
    }
    
    /**
     * Process queue (called by cron or manually)
     */
    public function process_queue($max_items = 1) {
        $processed = 0;
        
        for ($i = 0; $i < $max_items; $i++) {
            $item = $this->get_next_pending();
            
            if (!$item) {
                break;
            }
            
            $result = $this->process_item($item);
            $processed++;
            
            if (!$result['success']) {
                // Retry logic
                if ($item->retry_count < $item->max_retries) {
                    $this->update_status($item->id, 'pending', array(
                        'retry_count' => $item->retry_count + 1,
                        'error_message' => $result['message']
                    ));
                } else {
                    $this->update_status($item->id, 'failed', array(
                        'error_message' => $result['message']
                    ));
                }
            }
        }
        
        return $processed;
    }
    
    /**
     * Process single queue item
     */
    private function process_item($item) {
        // Update status to processing
        $this->update_status($item->id, 'processing');
        
        try {
            // Scrape chapter images
            $scraper_manager = MWS_Scraper_Manager::get_instance();
            $chapter_data = $scraper_manager->scrape_chapter_images($item->chapter_url);
            
            if (!$chapter_data || !isset($chapter_data['images']) || empty($chapter_data['images'])) {
                return array('success' => false, 'message' => 'No images found');
            }
            
            $images_total = count($chapter_data['images']);
            $this->update_status($item->id, 'processing', array('images_total' => $images_total));
            
            // Download images to local server
            $downloader = MWS_Image_Downloader::get_instance();
            $download_result = $downloader->download_chapter_images(
                $item->manhwa_id,
                $chapter_data['chapter_number'] ?? $item->chapter_number,
                $chapter_data['images']
            );
            
            if ($download_result['success'] > 0) {
                // Update chapter data in post meta
                $chapters = get_post_meta($item->manhwa_id, '_manhwa_chapters', true);
                if (is_array($chapters)) {
                    foreach ($chapters as &$ch) {
                        $ch_num = preg_replace('/[^0-9.]/', '', $ch['title'] ?? '');
                        $item_num = preg_replace('/[^0-9.]/', '', $item->chapter_number);
                        
                        if ($ch_num == $item_num || $ch['title'] == $item->chapter_title) {
                            $ch['images'] = $download_result['images'];
                            break;
                        }
                    }
                    update_post_meta($item->manhwa_id, '_manhwa_chapters', $chapters);
                }
                
                $this->update_status($item->id, 'completed', array(
                    'images_downloaded' => $download_result['success'],
                    'images_total' => $images_total
                ));
                
                return array('success' => true);
            }
            
            return array('success' => false, 'message' => 'Download failed: ' . ($download_result['message'] ?? 'Unknown error'));
            
        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }
    
    /**
     * Check if queue is being processed
     */
    public function is_processing() {
        global $wpdb;
        
        $count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'processing'"
        );
        
        return $count > 0;
    }
    
    /**
     * Get processing item
     */
    public function get_processing_item() {
        global $wpdb;
        
        return $wpdb->get_row(
            "SELECT * FROM {$this->table_name} WHERE status = 'processing' LIMIT 1"
        );
    }
}
