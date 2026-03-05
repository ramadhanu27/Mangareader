<?php
/**
 * Auto Image Scraper
 * Automatically scrape chapter images for manhwa that only have metadata
 */

if (!defined('ABSPATH')) {
    exit;
}

class MWS_Auto_Image_Scraper {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Cron hook name
     */
    const HOOK_NAME = 'mws_auto_image_scraper';
    
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
        add_action(self::HOOK_NAME, [$this, 'run_image_scraper']);
    }
    
    /**
     * Schedule image scraper cron
     */
    public static function schedule() {
        if (!wp_next_scheduled(self::HOOK_NAME)) {
            $interval = get_option('mws_image_scraper_interval', 'sixhourly');
            
            // Map interval to WP cron schedule name
            $schedule_map = [
                'hourly' => 'hourly',
                'twohourly' => 'mws_every_2_hours',
                'fourhourly' => 'mws_every_4_hours',
                'sixhourly' => 'mws_every_6_hours',
                'twicedaily' => 'twicedaily',
                'daily' => 'daily',
            ];
            
            $schedule = isset($schedule_map[$interval]) ? $schedule_map[$interval] : 'mws_every_6_hours';
            
            wp_schedule_event(time(), $schedule, self::HOOK_NAME);
        }
    }
    
    /**
     * Unschedule image scraper cron
     */
    public static function unschedule() {
        $timestamp = wp_next_scheduled(self::HOOK_NAME);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::HOOK_NAME);
        }
    }
    
    /**
     * Reschedule with new interval
     */
    public static function reschedule($new_interval = null) {
        // Unschedule existing
        self::unschedule();
        
        // Update option if new interval provided
        if ($new_interval) {
            update_option('mws_image_scraper_interval', $new_interval);
        }
        
        // Schedule with new interval
        self::schedule();
    }
    
    /**
     * Run image scraper process
     */
    public function run_image_scraper($force = false) {
        // Skip enabled check if forced (for manual testing)
        if (!$force && !get_option('mws_auto_image_scraper_enabled', false)) {
            $this->log('Image scraper is disabled. Enable it in settings or use force parameter.');
            return [
                'total_checked' => 0,
                'chapters_scraped' => 0,
                'images_found' => 0,
                'errors' => 0,
                'batch_progress' => '',
            ];
        }
        
        $this->log('Starting auto image scraper...');
        
        $stats = [
            'total_checked' => 0,
            'chapters_scraped' => 0,
            'images_found' => 0,
            'errors' => 0,
            'batch_progress' => '',
        ];
        
        // Get batch size
        $batch_size = get_option('mws_image_scraper_batch_size', 10);
        
        // Get manhwa with empty chapter images
        $manhwa_list = $this->get_manhwa_with_empty_images($batch_size);
        
        if (empty($manhwa_list)) {
            $this->log('No manhwa with empty images found');
            return $stats;
        }
        
        $stats['total_checked'] = count($manhwa_list);
        
        $scraper_manager = MWS_Scraper_Manager::get_instance();
        
        foreach ($manhwa_list as $manhwa) {
            try {
                $result = $this->scrape_manhwa_images($manhwa, $scraper_manager);
                
                if ($result['success']) {
                    $stats['chapters_scraped'] += $result['chapters_scraped'];
                    $stats['images_found'] += $result['images_found'];
                    
                    $this->log("Scraped images for: {$manhwa['title']} ({$result['chapters_scraped']} chapters, {$result['images_found']} images)");
                } else {
                    $stats['errors']++;
                }
                
            } catch (Exception $e) {
                $stats['errors']++;
                $this->log("Error scraping {$manhwa['title']}: " . $e->getMessage());
            }
            
            // Delay between manhwa
            usleep(500000); // 0.5 seconds
        }
        
        $this->log(sprintf(
            'Image scraper completed. Checked: %d, Chapters: %d, Images: %d, Errors: %d',
            $stats['total_checked'],
            $stats['chapters_scraped'],
            $stats['images_found'],
            $stats['errors']
        ));
        
        // Save stats
        update_option('mws_last_image_scraper_stats', $stats);
        update_option('mws_last_image_scraper_time', current_time('mysql'));
        
        // Fire action
        do_action('mws_image_scraper_completed', $stats);
        
        return $stats;
    }
    
    /**
     * Get manhwa with empty chapter images
     */
    private function get_manhwa_with_empty_images($limit = 10) {
        global $wpdb;
        
        $this->log("Searching for manhwa with empty images...");
        
        // Get all manhwa that have source URL and chapters
        $manhwa_ids = $wpdb->get_results("
            SELECT p.ID, p.post_title, pm1.meta_value as source_url, pm2.meta_value as chapters
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_mws_source_url'
            LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_manhwa_chapters'
            WHERE p.post_type = 'manhwa'
            AND p.post_status = 'publish'
            AND pm1.meta_value != ''
            ORDER BY p.post_modified DESC
            LIMIT 100
        ", ARRAY_A);
        
        $this->log("Found " . count($manhwa_ids) . " manhwa with source URLs");
        
        $result = [];
        $checked = 0;
        
        foreach ($manhwa_ids as $manhwa) {
            $chapters = maybe_unserialize($manhwa['chapters']);
            
            // Case 1: No chapters metadata at all
            if (empty($chapters) || !is_array($chapters)) {
                $this->log("  - {$manhwa['post_title']}: No chapters metadata");
                continue;
            }
            
            // Case 2: Has chapters but check for empty images
            $has_empty = false;
            $empty_count = 0;
            $total_chapters = count($chapters);
            
            foreach ($chapters as $chapter) {
                // Check various conditions for empty images
                if (
                    !isset($chapter['images']) || 
                    empty($chapter['images']) || 
                    !is_array($chapter['images']) ||
                    count($chapter['images']) === 0
                ) {
                    $has_empty = true;
                    $empty_count++;
                }
            }
            
            if ($has_empty) {
                $this->log("  ✓ {$manhwa['post_title']}: {$empty_count}/{$total_chapters} chapters need images");
                
                $result[] = [
                    'id' => $manhwa['ID'],
                    'title' => $manhwa['post_title'],
                    'source_url' => $manhwa['source_url'],
                    'chapters' => $chapters,
                    'empty_count' => $empty_count,
                    'total_chapters' => $total_chapters,
                ];
                
                $checked++;
                
                // Stop when we have enough
                if ($checked >= $limit) {
                    break;
                }
            } else {
                $this->log("  - {$manhwa['post_title']}: All {$total_chapters} chapters have images");
            }
        }
        
        $this->log("Result: Found " . count($result) . " manhwa with empty images");
        
        return $result;
    }
    
    /**
     * Scrape images for manhwa
     */
    private function scrape_manhwa_images($manhwa, $scraper_manager) {
        $result = [
            'success' => false,
            'chapters_scraped' => 0,
            'images_found' => 0,
            'images_downloaded' => 0,
            'total_size' => 0,
        ];
        
        // Check if download to local is enabled
        $download_local = get_option('mws_auto_scraper_download_local', false);
        $image_downloader = null;
        
        if ($download_local) {
            $image_downloader = MWS_Image_Downloader::get_instance();
            $this->log("  Download to local enabled");
        }
        
        $chapters = $manhwa['chapters'];
        $updated_chapters = [];
        
        // Get post slug for local storage
        $post = get_post($manhwa['id']);
        $post_slug = $post ? $post->post_name : sanitize_title($manhwa['title']);
        
        // Track which chapters were scraped for logging
        $scraped_chapters_detail = [];
        
        foreach ($chapters as $chapter) {
            // Skip if already has images
            if (!empty($chapter['images']) && is_array($chapter['images']) && count($chapter['images']) > 0) {
                $updated_chapters[] = $chapter;
                continue;
            }
            
            // Skip if no chapter URL
            if (empty($chapter['url'])) {
                $updated_chapters[] = $chapter;
                continue;
            }
            
            try {
                // Scrape chapter images
                $scraped_images = $scraper_manager->scrape_chapter_images($chapter['url']);
                
                if (!is_wp_error($scraped_images) && !empty($scraped_images)) {
                    // Extract image URLs from scraper response
                    // Handle different response formats from different scrapers
                    $image_urls = [];
                    
                    // Format 1: Direct array of URLs
                    if (is_array($scraped_images) && !isset($scraped_images['images'])) {
                        foreach ($scraped_images as $img) {
                            if (is_string($img)) {
                                $image_urls[] = $img;
                            } elseif (is_array($img) && isset($img['url'])) {
                                $image_urls[] = $img['url'];
                            }
                        }
                    }
                    // Format 2: Array with 'images' key
                    elseif (isset($scraped_images['images']) && is_array($scraped_images['images'])) {
                        foreach ($scraped_images['images'] as $img) {
                            if (is_array($img) && isset($img['url'])) {
                                $image_urls[] = $img['url'];
                            } elseif (is_string($img)) {
                                $image_urls[] = $img;
                            }
                        }
                    }
                    
                    if (!empty($image_urls)) {
                        $chapter_number = $chapter['number'] ?? preg_replace('/[^0-9.]/', '', $chapter['title'] ?? '1');
                        $chapter_size = 0;
                        
                        // Check if download to local is enabled
                        if ($download_local && $image_downloader) {
                            $this->log("  Downloading images for Chapter {$chapter_number}...");
                            
                            // Download images to local
                            $download_result = $image_downloader->download_chapter_images(
                                $image_urls,
                                $post_slug,
                                $chapter_number
                            );
                            
                            if (!empty($download_result['images'])) {
                                // Use local URLs
                                $local_urls = [];
                                foreach ($download_result['images'] as $img) {
                                    if (isset($img['local']) && $img['local'] && isset($img['url'])) {
                                        $local_urls[] = $img['url'];
                                        $result['images_downloaded']++;
                                        
                                        // Calculate file size
                                        $local_path = $this->url_to_local_path($img['url']);
                                        if ($local_path && file_exists($local_path)) {
                                            $chapter_size += filesize($local_path);
                                        }
                                    } elseif (isset($img['url'])) {
                                        // Fallback to original URL if download failed
                                        $local_urls[] = $img['url'];
                                    }
                                }
                                $chapter['images'] = $local_urls;
                                $this->log("  ✓ Chapter {$chapter_number}: " . count($local_urls) . " images downloaded (" . $this->format_size($chapter_size) . ")");
                            } else {
                                // Fallback to external URLs
                                $chapter['images'] = $image_urls;
                                $this->log("  ⚠ Chapter {$chapter_number}: Using external URLs (download failed)");
                            }
                        } else {
                            // Use external URLs
                            $chapter['images'] = $image_urls;
                        }
                        
                        $result['chapters_scraped']++;
                        $result['images_found'] += count($chapter['images']);
                        $result['total_size'] += $chapter_size;
                        
                        // Track detail for logging
                        $scraped_chapters_detail[] = [
                            'chapter' => $chapter_number,
                            'images' => count($chapter['images']),
                            'size' => $chapter_size,
                        ];
                        
                        if (!$download_local) {
                            $this->log("  ✓ Chapter {$chapter_number}: " . count($image_urls) . " images");
                        }
                    } else {
                        $this->log("  ✗ Chapter " . ($chapter['number'] ?? '?') . ": No images found in response");
                    }
                }
                
                $updated_chapters[] = $chapter;
                
                // Delay between chapters (longer if downloading)
                if ($download_local) {
                    usleep(500000); // 0.5 seconds
                } else {
                    usleep(300000); // 0.3 seconds
                }
                
            } catch (Exception $e) {
                $this->log("  ✗ Chapter " . ($chapter['number'] ?? '?') . ": " . $e->getMessage());
                $updated_chapters[] = $chapter;
            }
        }
        
        // Update manhwa chapters with images
        if ($result['chapters_scraped'] > 0) {
            update_post_meta($manhwa['id'], '_manhwa_chapters', $updated_chapters);
            update_post_meta($manhwa['id'], '_mws_images_scraped', current_time('mysql'));
            update_post_meta($manhwa['id'], '_mws_total_downloaded_size', $result['total_size']);
            
            if ($download_local && $result['images_downloaded'] > 0) {
                update_post_meta($manhwa['id'], '_mws_images_downloaded', true);
            }
            
            $result['success'] = true;
            
            // Log to database
            if (class_exists('MWS_Logger')) {
                MWS_Logger::log_image_scrape(
                    $manhwa['id'],
                    $manhwa['title'],
                    $result['chapters_scraped'],
                    $result['images_found'],
                    $result['images_downloaded'],
                    $result['total_size'],
                    'success',
                    $scraped_chapters_detail
                );
            }
        }
        
        return $result;
    }
    
    /**
     * Convert URL to local file path
     */
    private function url_to_local_path($url) {
        $upload_dir = wp_upload_dir();
        $base_url = $upload_dir['baseurl'];
        $base_path = $upload_dir['basedir'];
        
        if (strpos($url, $base_url) === 0) {
            return str_replace($base_url, $base_path, $url);
        }
        
        return false;
    }
    
    /**
     * Format file size
     */
    private function format_size($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
    
    /**
     * Get statistics
     */
    public function get_stats() {
        global $wpdb;
        
        // Count manhwa with empty images
        $empty_count = 0;
        
        $all_manhwa = $wpdb->get_results("
            SELECT p.ID, pm.meta_value as chapters
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_manhwa_chapters'
            WHERE p.post_type = 'manhwa'
            AND p.post_status = 'publish'
        ", ARRAY_A);
        
        foreach ($all_manhwa as $manhwa) {
            $chapters = maybe_unserialize($manhwa['chapters']);
            
            if (!is_array($chapters)) {
                continue;
            }
            
            foreach ($chapters as $chapter) {
                if (empty($chapter['images']) || !is_array($chapter['images'])) {
                    $empty_count++;
                    break; // Count manhwa once
                }
            }
        }
        
        return [
            'total_manhwa' => count($all_manhwa),
            'manhwa_with_empty_images' => $empty_count,
            'manhwa_with_images' => count($all_manhwa) - $empty_count,
            'completion_rate' => count($all_manhwa) > 0 ? round((count($all_manhwa) - $empty_count) / count($all_manhwa) * 100, 2) : 0,
        ];
    }
    
    /**
     * Manual trigger for specific manhwa
     */
    public function scrape_manhwa_by_id($post_id) {
        $post = get_post($post_id);
        
        if (!$post || $post->post_type !== 'manhwa') {
            return new WP_Error('invalid_post', 'Invalid manhwa post');
        }
        
        $source_url = get_post_meta($post_id, '_mws_source_url', true);
        $chapters = get_post_meta($post_id, '_manhwa_chapters', true);
        
        if (empty($source_url) || empty($chapters)) {
            return new WP_Error('no_data', 'No source URL or chapters found');
        }
        
        $manhwa = [
            'id' => $post_id,
            'title' => $post->post_title,
            'source_url' => $source_url,
            'chapters' => $chapters,
        ];
        
        $scraper_manager = MWS_Scraper_Manager::get_instance();
        
        return $this->scrape_manhwa_images($manhwa, $scraper_manager);
    }
    
    /**
     * Log message
     */
    private function log($message) {
        if (class_exists('MWS_Logger')) {
            MWS_Logger::log($message, 'auto_image_scraper');
        }
        error_log('[MWS Auto Image Scraper] ' . $message);
    }
}

// Initialize
MWS_Auto_Image_Scraper::get_instance();
