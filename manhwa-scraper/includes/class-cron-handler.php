<?php
/**
 * Cron Handler Class
 * Handles scheduled updates for manhwa
 */

if (!defined('ABSPATH')) {
    exit;
}

class MWS_Cron_Handler {
    
    private static $instance = null;
    const HOOK_NAME = 'mws_check_updates';
    
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
        // Register cron action
        add_action(self::HOOK_NAME, [$this, 'check_for_updates']);
        
        // Add custom cron schedule
        add_filter('cron_schedules', [$this, 'add_custom_schedules']);
    }
    
    /**
     * Add custom cron schedules
     *
     * @param array $schedules
     * @return array
     */
    public function add_custom_schedules($schedules) {
        // Every 6 hours
        $schedules['mws_every_6_hours'] = [
            'interval' => 6 * HOUR_IN_SECONDS,
            'display' => __('Every 6 hours', 'manhwa-scraper'),
        ];
        
        // Every 4 hours
        $schedules['mws_every_4_hours'] = [
            'interval' => 4 * HOUR_IN_SECONDS,
            'display' => __('Every 4 hours', 'manhwa-scraper'),
        ];
        
        return $schedules;
    }
    
    /**
     * Schedule the cron job
     */
    public static function schedule() {
        if (!wp_next_scheduled(self::HOOK_NAME)) {
            $schedule = get_option('mws_cron_schedule', 'twicedaily');
            wp_schedule_event(time(), $schedule, self::HOOK_NAME);
        }
    }
    
    /**
     * Unschedule the cron job
     */
    public static function unschedule() {
        $timestamp = wp_next_scheduled(self::HOOK_NAME);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::HOOK_NAME);
        }
    }
    
    /**
     * Reschedule with new interval
     *
     * @param string $schedule
     */
    public static function reschedule($schedule) {
        self::unschedule();
        update_option('mws_cron_schedule', $schedule);
        wp_schedule_event(time(), $schedule, self::HOOK_NAME);
    }
    
    /**
     * Check for updates on all tracked manhwa with batching
     * 
     * @param int $batch_size Number of manhwa to check per run (default: 20)
     * @return array Update statistics
     */
    public function check_for_updates($batch_size = 20) {
        $all_tracked = $this->get_tracked_manhwa();
        
        $stats = [
            'total_tracked' => count($all_tracked),
            'batch_size' => $batch_size,
            'checked' => 0,
            'updated' => 0,
            'errors' => 0,
            'skipped' => 0,
            'batch_progress' => '',
        ];
        
        if (empty($all_tracked)) {
            $this->log('No tracked manhwa to update');
            return $stats;
        }
        
        // Get last processed index for batching
        $last_index = get_option('mws_last_update_index', 0);
        
        // Sort by priority (ongoing first, then hiatus, then completed)
        $all_tracked = $this->sort_by_priority($all_tracked);
        
        // Get current batch
        $batch = array_slice($all_tracked, $last_index, $batch_size);
        
        // Calculate next index
        $next_index = $last_index + $batch_size;
        if ($next_index >= count($all_tracked)) {
            $next_index = 0; // Reset to start for next cycle
            $this->log('Batch cycle completed. Restarting from beginning.');
        }
        
        // Update progress
        $stats['batch_progress'] = sprintf(
            '%d-%d of %d (%.1f%%)',
            $last_index + 1,
            min($last_index + $batch_size, count($all_tracked)),
            count($all_tracked),
            (min($last_index + $batch_size, count($all_tracked)) / count($all_tracked)) * 100
        );
        
        $this->log(sprintf(
            'Starting batch update: %s manhwa (batch %s)',
            count($batch),
            $stats['batch_progress']
        ));
        
        $scraper_manager = MWS_Scraper_Manager::get_instance();
        
        foreach ($batch as $manhwa) {
            // Skip completed manhwa (unless it's been > 30 days since last check)
            if ($this->should_skip_manhwa($manhwa)) {
                $stats['skipped']++;
                $this->log("Skipped (completed): {$manhwa['title']}");
                continue;
            }
            
            $stats['checked']++;
            
            $result = $this->update_single($manhwa, $scraper_manager);
            
            if ($result === true) {
                $stats['updated']++;
            } elseif ($result === false) {
                // No update needed
            } else {
                $stats['errors']++;
            }
            
            // Add small delay between updates
            usleep(200000); // 0.2 seconds
        }
        
        // Save progress for next run
        update_option('mws_last_update_index', $next_index);
        update_option('mws_last_batch_stats', $stats);
        update_option('mws_last_batch_time', current_time('mysql'));
        
        $this->log(sprintf(
            'Batch completed. Checked: %d, Updated: %d, Skipped: %d, Errors: %d. Next batch starts at index: %d',
            $stats['checked'],
            $stats['updated'],
            $stats['skipped'],
            $stats['errors'],
            $next_index
        ));
        
        // Fire action for notifications
        do_action('mws_updates_checked', $stats['updated'], $stats['checked'], $stats);
        
        return $stats;
    }
    
    /**
     * Sort manhwa by priority
     * Priority: ongoing > hiatus > completed
     * 
     * @param array $manhwa_list
     * @return array
     */
    private function sort_by_priority($manhwa_list) {
        usort($manhwa_list, function($a, $b) {
            $status_a = get_post_meta($a['post_id'], '_manhwa_status', true);
            $status_b = get_post_meta($b['post_id'], '_manhwa_status', true);
            
            // Priority weights (lower = higher priority)
            $priority = [
                'ongoing' => 1,
                'hiatus' => 2,
                'completed' => 3,
                'dropped' => 4,
                'cancelled' => 5,
            ];
            
            $weight_a = $priority[$status_a] ?? 99;
            $weight_b = $priority[$status_b] ?? 99;
            
            // If same priority, sort by last update time (older first)
            if ($weight_a === $weight_b) {
                $time_a = get_post_meta($a['post_id'], '_mws_last_checked', true);
                $time_b = get_post_meta($b['post_id'], '_mws_last_checked', true);
                return strtotime($time_a ?: '2000-01-01') - strtotime($time_b ?: '2000-01-01');
            }
            
            return $weight_a - $weight_b;
        });
        
        return $manhwa_list;
    }
    
    /**
     * Check if manhwa should be skipped
     * 
     * @param array $manhwa
     * @return bool
     */
    private function should_skip_manhwa($manhwa) {
        $status = get_post_meta($manhwa['post_id'], '_manhwa_status', true);
        
        // Always check ongoing and hiatus
        if (in_array($status, ['ongoing', 'hiatus'])) {
            return false;
        }
        
        // For completed manhwa, check once per month
        if ($status === 'completed') {
            $last_checked = get_post_meta($manhwa['post_id'], '_mws_last_checked', true);
            
            if (empty($last_checked)) {
                return false; // Never checked, do check
            }
            
            $days_since_check = (time() - strtotime($last_checked)) / DAY_IN_SECONDS;
            
            // Skip if checked within last 30 days
            if ($days_since_check < 30) {
                return true;
            }
        }
        
        // Skip dropped/cancelled
        if (in_array($status, ['dropped', 'cancelled'])) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Update single manhwa
     *
     * @param array $manhwa
     * @param MWS_Scraper_Manager $scraper_manager
     * @return bool
     */
    private function update_single($manhwa, $scraper_manager) {
        $source_url = $manhwa['source_url'] ?? '';
        $post_id = $manhwa['post_id'] ?? 0;
        $current_chapters = $manhwa['total_chapters'] ?? 0;
        
        if (empty($source_url)) {
            return false;
        }
        
        try {
            // Scrape current data
            $scraped = $scraper_manager->scrape_url($source_url);
            
            if (is_wp_error($scraped)) {
                $this->log("Failed to scrape: $source_url - " . $scraped->get_error_message());
                return false;
            }
            
            $new_chapters = $scraped['total_chapters'] ?? count($scraped['chapters'] ?? []);
            
            // Check if there are new chapters
            if ($new_chapters > $current_chapters) {
                $this->log("New chapters found for: " . ($scraped['title'] ?? $source_url) . " ($current_chapters -> $new_chapters)");
                
                // Update post meta if we have post ID
                if ($post_id) {
                    update_post_meta($post_id, '_mws_total_chapters', $new_chapters);
                    update_post_meta($post_id, '_mws_latest_chapter', $scraped['latest_chapter'] ?? '');
                    update_post_meta($post_id, '_mws_last_updated', current_time('mysql'));
                    update_post_meta($post_id, '_mws_last_checked', current_time('mysql')); // Track check time
                    
                    // Get existing chapters
                    $existing_chapters = get_post_meta($post_id, '_manhwa_chapters', true);
                    if (!is_array($existing_chapters)) {
                        $existing_chapters = [];
                    }
                    
                    // Find new chapters to scrape
                    $new_chapter_data = $this->get_new_chapters($scraped['chapters'] ?? [], $existing_chapters);
                    
                    // Auto-scrape chapter images if enabled (without downloading)
                    if (get_option('mws_auto_scrape_chapter_images', false) && !empty($new_chapter_data)) {
                        $this->auto_scrape_new_chapter_images($post_id, $new_chapter_data, $scraped['title'] ?? '');
                    }
                    
                    // Auto-download new chapters if enabled (download to local)
                    if (get_option('mws_auto_download_chapters', false) && !empty($new_chapter_data)) {
                        $this->auto_download_new_chapters($post_id, $new_chapter_data, $scraped['title'] ?? '');
                    }
                    
                    // Update chapters meta (merge new with existing)
                    if (!empty($scraped['chapters'])) {
                        $merged_chapters = $this->merge_chapters($existing_chapters, $scraped['chapters']);
                        update_post_meta($post_id, '_manhwa_chapters', $merged_chapters);
                        
                        // Force update post modified date so theme shows "last update"
                        global $wpdb;
                        $wpdb->update(
                            $wpdb->posts,
                            [
                                'post_modified' => current_time('mysql'),
                                'post_modified_gmt' => current_time('mysql', true),
                            ],
                            ['ID' => $post_id],
                            ['%s', '%s'],
                            ['%d']
                        );
                        clean_post_cache($post_id);
                    }
                }
                
                // Log the update
                $this->log_update($manhwa, $scraped, $current_chapters, $new_chapters);
                
                // Fire action for notifications
                do_action('mws_new_chapters_found', $manhwa, $scraped, $new_chapters - $current_chapters);
                
                return true;
            } else {
                // No new chapters, but still update last checked time
                if ($post_id) {
                    update_post_meta($post_id, '_mws_last_checked', current_time('mysql'));
                }
            }
            
        } catch (Exception $e) {
            $this->log("Error updating: $source_url - " . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Get new chapters that don't exist in current chapters
     *
     * @param array $scraped_chapters
     * @param array $existing_chapters
     * @return array
     */
    private function get_new_chapters($scraped_chapters, $existing_chapters) {
        $existing_titles = array_map(function($ch) {
            return strtolower(trim($ch['title'] ?? ''));
        }, $existing_chapters);
        
        $new_chapters = [];
        foreach ($scraped_chapters as $chapter) {
            $title = strtolower(trim($chapter['title'] ?? ''));
            if (!empty($title) && !in_array($title, $existing_titles)) {
                $new_chapters[] = $chapter;
            }
        }
        
        return $new_chapters;
    }
    
    /**
     * Merge new chapters with existing ones
     *
     * @param array $existing
     * @param array $new
     * @return array
     */
    private function merge_chapters($existing, $new) {
        $existing_urls = array_map(function($ch) {
            return $ch['url'] ?? '';
        }, $existing);
        
        foreach ($new as $chapter) {
            if (!empty($chapter['url']) && !in_array($chapter['url'], $existing_urls)) {
                // Always use current date as scrape date
                $chapter['date'] = current_time('j M Y'); // e.g. "8 Feb 2026"
                $chapter['added_at'] = current_time('mysql'); // Full datetime for accurate "X ago"
                $existing[] = $chapter;
            }
        }
        
        // Sort by chapter number/title
        usort($existing, function($a, $b) {
            $num_a = (int) preg_replace('/[^0-9]/', '', $a['title'] ?? '');
            $num_b = (int) preg_replace('/[^0-9]/', '', $b['title'] ?? '');
            return $num_a - $num_b;
        });
        
        return $existing;
    }
    
    /**
     * Auto-download new chapter images
     *
     * @param int $post_id
     * @param array $new_chapters
     * @param string $manhwa_title
     */
    private function auto_download_new_chapters($post_id, $new_chapters, $manhwa_title) {
        $post = get_post($post_id);
        if (!$post) {
            return;
        }
        
        $manhwa_slug = $post->post_name;
        $scraper_manager = MWS_Scraper_Manager::get_instance();
        $image_downloader = MWS_Image_Downloader::get_instance();
        $concurrent = get_option('mws_concurrent_downloads', 5);
        
        $this->log("Auto-downloading " . count($new_chapters) . " new chapters for: $manhwa_title");
        
        foreach ($new_chapters as $chapter) {
            $chapter_url = $chapter['url'] ?? '';
            $chapter_title = $chapter['title'] ?? '';
            
            if (empty($chapter_url)) {
                continue;
            }
            
            // Extract chapter number from title
            preg_match('/(\d+)/', $chapter_title, $matches);
            $chapter_number = $matches[1] ?? '0';
            
            try {
                // Scrape chapter images
                $images = $scraper_manager->scrape_chapter_images($chapter_url);
                
                if (is_wp_error($images)) {
                    $this->log("Failed to scrape chapter images: $chapter_title - " . $images->get_error_message());
                    continue;
                }
                
                // Download images
                if (!empty($images['images'])) {
                    $result = $image_downloader->download_chapter_images(
                        $images['images'],
                        $manhwa_slug,
                        $chapter_number,
                        null,
                        $concurrent
                    );
                    
                    $this->log("Downloaded chapter $chapter_title: {$result['success']} success, {$result['failed']} failed");
                    
                    // Update chapter with local images
                    $this->update_chapter_images($post_id, $chapter_title, $result['images']);
                }
                
                // Small delay between chapters
                sleep(2);
                
            } catch (Exception $e) {
                $this->log("Error downloading chapter $chapter_title: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Auto-scrape new chapter images (without downloading)
     *
     * @param int $post_id
     * @param array $new_chapters
     * @param string $manhwa_title
     */
    private function auto_scrape_new_chapter_images($post_id, $new_chapters, $manhwa_title) {
        $post = get_post($post_id);
        if (!$post) {
            return;
        }
        
        $scraper_manager = MWS_Scraper_Manager::get_instance();
        
        $this->log("Auto-scraping " . count($new_chapters) . " new chapter images for: $manhwa_title");
        
        foreach ($new_chapters as $chapter) {
            $chapter_url = $chapter['url'] ?? '';
            $chapter_title = $chapter['title'] ?? '';
            
            if (empty($chapter_url)) {
                continue;
            }
            
            try {
                // Scrape chapter images
                $images = $scraper_manager->scrape_chapter_images($chapter_url);
                
                if (is_wp_error($images)) {
                    $this->log("Failed to scrape chapter images: $chapter_title - " . $images->get_error_message());
                    continue;
                }
                
                // Save external image URLs to chapter
            if (!empty($images['images'])) {
                // Extract just the URLs - handle both formats
                // Format 1 (Manhwaku): [['url' => '...'], ...]
                // Format 2 (Komikcast): ['url1', 'url2', ...]
                $image_urls = [];
                
                foreach ($images['images'] as $img) {
                    if (is_array($img) && isset($img['url'])) {
                        // Format 1: Array with 'url' key
                        $image_urls[] = $img['url'];
                    } elseif (is_string($img)) {
                        // Format 2: Direct URL string
                        $image_urls[] = $img;
                    }
                }
                
                if (!empty($image_urls)) {
                    $this->log("Scraped chapter $chapter_title: " . count($image_urls) . " images (external URLs)");
                    
                    // Update chapter with external image URLs
                    $this->update_chapter_images($post_id, $chapter_title, $image_urls);
                } else {
                    $this->log("No valid image URLs found for chapter: $chapter_title");
                }
            }
            
            // Small delay between chapters
            sleep(2);
            
        } catch (Exception $e) {
            $this->log("Error scraping chapter $chapter_title: " . $e->getMessage());
        }
    }
}
    
    /**
     * Update chapter images in post meta
     *
     * @param int $post_id
     * @param string $chapter_title
     * @param array $images
     */
    private function update_chapter_images($post_id, $chapter_title, $images) {
        $chapters = get_post_meta($post_id, '_manhwa_chapters', true);
        if (!is_array($chapters)) {
            return;
        }
        
        foreach ($chapters as $index => $chapter) {
            if (strtolower(trim($chapter['title'] ?? '')) === strtolower(trim($chapter_title))) {
                $chapters[$index]['images'] = $images;
                $chapters[$index]['images_local'] = true;
                break;
            }
        }
        
        update_post_meta($post_id, '_manhwa_chapters', $chapters);
    }
    
    /**
     * Get tracked manhwa list
     *
     * @return array
     */
    private function get_tracked_manhwa() {
        // Get from option storage
        $tracked = get_option('mws_tracked_manhwa', []);
        
        // Also get from linked posts (if Manhwa Manager is active)
        if (post_type_exists('manhwa')) {
            $posts = get_posts([
                'post_type' => 'manhwa',
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => '_mws_source_url',
                        'compare' => 'EXISTS',
                    ],
                ],
            ]);
            
            foreach ($posts as $post) {
                $source_url = get_post_meta($post->ID, '_mws_source_url', true);
                $total_chapters = get_post_meta($post->ID, '_mws_total_chapters', true);
                
                if (!empty($source_url)) {
                    $tracked[] = [
                        'post_id' => $post->ID,
                        'title' => $post->post_title,
                        'source_url' => $source_url,
                        'total_chapters' => (int) $total_chapters,
                    ];
                }
            }
        }
        
        return $tracked;
    }
    
    /**
     * Add manhwa to tracking
     *
     * @param array $manhwa
     */
    public function add_to_tracking($manhwa) {
        $tracked = get_option('mws_tracked_manhwa', []);
        
        // Check if already exists
        foreach ($tracked as $item) {
            if ($item['source_url'] === $manhwa['source_url']) {
                return; // Already tracked
            }
        }
        
        $tracked[] = [
            'source_url' => $manhwa['source_url'],
            'title' => $manhwa['title'],
            'total_chapters' => $manhwa['total_chapters'] ?? 0,
            'added_at' => current_time('mysql'),
        ];
        
        update_option('mws_tracked_manhwa', $tracked);
    }
    
    /**
     * Remove manhwa from tracking
     *
     * @param string $source_url
     */
    public function remove_from_tracking($source_url) {
        $tracked = get_option('mws_tracked_manhwa', []);
        
        $tracked = array_filter($tracked, function($item) use ($source_url) {
            return $item['source_url'] !== $source_url;
        });
        
        update_option('mws_tracked_manhwa', array_values($tracked));
    }
    
    /**
     * Log update to database
     *
     * @param array $manhwa
     * @param array $scraped
     * @param int $old_chapters
     * @param int $new_chapters
     */
    private function log_update($manhwa, $scraped, $old_chapters, $new_chapters) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mws_logs';
        
        // Get list of new chapter titles
        $new_chapter_list = [];
        $existing_chapters = [];
        
        if (!empty($manhwa['post_id'])) {
            $existing_chapters = get_post_meta($manhwa['post_id'], '_manhwa_chapters', true);
            if (!is_array($existing_chapters)) {
                $existing_chapters = [];
            }
        }
        
        $existing_titles = array_map(function($ch) {
            return strtolower(trim($ch['title'] ?? ''));
        }, $existing_chapters);
        
        foreach ($scraped['chapters'] ?? [] as $chapter) {
            $title = strtolower(trim($chapter['title'] ?? ''));
            if (!empty($title) && !in_array($title, $existing_titles)) {
                $new_chapter_list[] = [
                    'title' => $chapter['title'] ?? '',
                    'number' => $chapter['number'] ?? '',
                    'url' => $chapter['url'] ?? '',
                ];
            }
        }
        
        $wpdb->insert($table_name, [
            'source' => $scraped['source'] ?? 'unknown',
            'url' => $manhwa['source_url'],
            'status' => 'auto_update',
            'message' => sprintf(
                '[AUTO] %s: %d → %d (+%d new chapters)',
                $scraped['title'] ?? 'Unknown',
                $old_chapters,
                $new_chapters,
                $new_chapters - $old_chapters
            ),
            'data' => json_encode([
                'title' => $scraped['title'] ?? '',
                'post_id' => $manhwa['post_id'] ?? null,
                'old_chapters' => $old_chapters,
                'new_chapters' => $new_chapters,
                'chapters_added' => $new_chapters - $old_chapters,
                'latest_chapter' => $scraped['latest_chapter'] ?? '',
                'new_chapter_list' => $new_chapter_list,
                'auto_download' => get_option('mws_auto_download_chapters', false),
                'auto_scrape_images' => get_option('mws_auto_scrape_chapter_images', false),
                'updated_at' => current_time('c'),
            ]),
            'type' => 'auto',
            'created_at' => current_time('mysql'),
        ]);
    }
    
    /**
     * Log message
     *
     * @param string $message
     */
    private function log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[MWS Cron] ' . $message);
        }
    }
    
    /**
     * Get next scheduled time
     *
     * @return int|false
     */
    public static function get_next_scheduled() {
        return wp_next_scheduled(self::HOOK_NAME);
    }
    
    /**
     * Force run update check now
     */
    public function run_now() {
        $this->check_for_updates();
    }
}
