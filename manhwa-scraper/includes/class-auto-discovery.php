<?php
/**
 * Auto Discovery System
 * Automatically discover and import new manhwa from sources
 */

if (!defined('ABSPATH')) {
    exit;
}

class MWS_Auto_Discovery {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Cron hook name
     */
    const HOOK_NAME = 'mws_auto_discovery';
    
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
        add_action(self::HOOK_NAME, [$this, 'run_discovery']);
    }
    
    /**
     * Schedule discovery cron
     */
    public static function schedule() {
        if (!wp_next_scheduled(self::HOOK_NAME)) {
            wp_schedule_event(time(), 'daily', self::HOOK_NAME);
        }
    }
    
    /**
     * Unschedule discovery cron
     */
    public static function unschedule() {
        $timestamp = wp_next_scheduled(self::HOOK_NAME);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::HOOK_NAME);
        }
    }
    
    /**
     * Run discovery process
     */
    public function run_discovery() {
        if (!get_option('mws_auto_discovery_enabled', false)) {
            return;
        }
        
        $this->log('Starting auto discovery...');
        
        $stats = [
            'sources_checked' => 0,
            'manhwa_found' => 0,
            'new_imported' => 0,
            'already_exists' => 0,
            'errors' => 0,
        ];
        
        $scraper_manager = MWS_Scraper_Manager::get_instance();
        $enabled_sources = get_option('mws_enabled_sources', ['manhwaku', 'komikcast']);
        $max_per_source = get_option('mws_discovery_max_per_source', 10);
        
        // Ensure we have sources to check
        if (empty($enabled_sources) || !is_array($enabled_sources)) {
            $enabled_sources = ['manhwaku', 'komikcast'];
        }
        
        foreach ($enabled_sources as $source_id) {
            $stats['sources_checked']++;
            
            try {
                $latest_manhwa = $this->scrape_latest_from_source($source_id, $max_per_source);
                
                if (empty($latest_manhwa)) {
                    $this->log("No latest manhwa found from: $source_id");
                    continue;
                }
                
                $stats['manhwa_found'] += count($latest_manhwa);
                
                foreach ($latest_manhwa as $manhwa) {
                    // Check if already exists
                    if ($this->manhwa_exists($manhwa['title'], $manhwa['url'])) {
                        $stats['already_exists']++;
                        continue;
                    }
                    
                    // Auto-import
                    $result = $this->auto_import_manhwa($manhwa['url']);
                    
                    if ($result) {
                        $stats['new_imported']++;
                        $this->log("Auto-imported: {$manhwa['title']}");
                    } else {
                        $stats['errors']++;
                        $this->log("Failed to import: {$manhwa['title']}");
                    }
                    
                    // Delay between imports
                    usleep(500000); // 0.5 seconds
                }
                
            } catch (Exception $e) {
                $stats['errors']++;
                $this->log("Error discovering from $source_id: " . $e->getMessage());
            }
            
            // Delay between sources
            sleep(2);
        }
        
        $this->log(sprintf(
            'Discovery completed. Sources: %d, Found: %d, Imported: %d, Exists: %d, Errors: %d',
            $stats['sources_checked'],
            $stats['manhwa_found'],
            $stats['new_imported'],
            $stats['already_exists'],
            $stats['errors']
        ));
        
        // Save stats
        update_option('mws_last_discovery_stats', $stats);
        update_option('mws_last_discovery_time', current_time('mysql'));
        
        // Fire action
        do_action('mws_discovery_completed', $stats);
        
        return $stats;
    }
    
    /**
     * Scrape latest manhwa from source
     */
    private function scrape_latest_from_source($source_id, $limit = 10) {
        $scraper_manager = MWS_Scraper_Manager::get_instance();
        $scraper = $scraper_manager->get_scraper($source_id);
        
        if (!$scraper) {
            return [];
        }
        
        // Get latest page URL based on source
        $latest_urls = [
            'manhwaku' => 'https://manhwaku.id/manga/?status=&type=&order=update',
            'asura' => 'https://asuracomic.net/series?order=update',
            'komikcast' => 'https://komikcast03.com/daftar-komik/?orderby=update',
            'manhwaland' => 'https://02.manhwaland.land/manga/?order=update',
        ];
        
        $url = $latest_urls[$source_id] ?? null;
        
        if (!$url) {
            return [];
        }
        
        try {
            $html = $this->fetch_html($url);
            
            if (!$html) {
                return [];
            }
            
            $manhwa_list = $this->parse_latest_page($html, $source_id);
            
            // Limit results
            return array_slice($manhwa_list, 0, $limit);
            
        } catch (Exception $e) {
            $this->log("Error scraping latest from $source_id: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Parse latest manhwa page
     */
    private function parse_latest_page($html, $source_id) {
        $manhwa_list = [];
        
        // Parse based on source structure
        switch ($source_id) {
            case 'manhwaku':
                // Parse manhwaku structure
                preg_match_all('/<div class="bsx">.*?<a href="([^"]+)".*?<img[^>]+alt="([^"]+)".*?<\/div>/s', $html, $matches);
                
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $index => $url) {
                        $manhwa_list[] = [
                            'title' => $matches[2][$index] ?? '',
                            'url' => $url,
                            'source' => $source_id,
                        ];
                    }
                }
                break;
                
            case 'asura':
            case 'komikcast':
            case 'manhwaland':
                // Similar parsing logic for other sources
                // Simplified for now
                preg_match_all('/<a[^>]+href="([^"]+)"[^>]*>.*?<img[^>]+alt="([^"]+)"/s', $html, $matches);
                
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $index => $url) {
                        if (strpos($url, 'http') === 0) {
                            $manhwa_list[] = [
                                'title' => strip_tags($matches[2][$index] ?? ''),
                                'url' => $url,
                                'source' => $source_id,
                            ];
                        }
                    }
                }
                break;
        }
        
        return $manhwa_list;
    }
    
    /**
     * Fetch HTML from URL
     */
    private function fetch_html($url) {
        $response = wp_remote_get($url, [
            'timeout' => 30,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        return wp_remote_retrieve_body($response);
    }
    
    /**
     * Check if manhwa already exists (Enhanced duplicate detection)
     */
    private function manhwa_exists($title, $url) {
        global $wpdb;
        
        // 1. Check by exact source URL (highest priority)
        $exists = $wpdb->get_var($wpdb->prepare("
            SELECT p.ID
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'manhwa'
            AND pm.meta_key = '_mws_source_url'
            AND pm.meta_value = %s
            LIMIT 1
        ", $url));
        
        if ($exists) {
            $this->log("Duplicate found by URL: $title");
            return true;
        }
        
        // 2. Check by exact title match (case-insensitive)
        $exact_title = $wpdb->get_var($wpdb->prepare("
            SELECT ID
            FROM {$wpdb->posts}
            WHERE post_type = 'manhwa'
            AND LOWER(post_title) = LOWER(%s)
            LIMIT 1
        ", $title));
        
        if ($exact_title) {
            $this->log("Duplicate found by exact title: $title");
            return true;
        }
        
        // 3. Check by normalized title (remove special chars, extra spaces)
        $normalized_title = $this->normalize_title($title);
        
        $all_manhwa = $wpdb->get_results("
            SELECT ID, post_title
            FROM {$wpdb->posts}
            WHERE post_type = 'manhwa'
            AND post_status = 'publish'
        ", ARRAY_A);
        
        foreach ($all_manhwa as $manhwa) {
            $existing_normalized = $this->normalize_title($manhwa['post_title']);
            
            // Exact normalized match
            if ($existing_normalized === $normalized_title) {
                $this->log("Duplicate found by normalized title: $title = {$manhwa['post_title']}");
                return true;
            }
            
            // High similarity match (>90%)
            $similarity = $this->calculate_similarity($normalized_title, $existing_normalized);
            if ($similarity > 90) {
                $this->log("Duplicate found by similarity ({$similarity}%): $title ≈ {$manhwa['post_title']}");
                return true;
            }
        }
        
        // 4. Check by slug similarity
        $slug = sanitize_title($title);
        $similar_slug = $wpdb->get_var($wpdb->prepare("
            SELECT ID
            FROM {$wpdb->posts}
            WHERE post_type = 'manhwa'
            AND post_name = %s
            LIMIT 1
        ", $slug));
        
        if ($similar_slug) {
            $this->log("Duplicate found by slug: $title");
            return true;
        }
        
        // No duplicate found
        return false;
    }
    
    /**
     * Normalize title for comparison
     */
    private function normalize_title($title) {
        // Convert to lowercase
        $title = strtolower($title);
        
        // Remove common prefixes/suffixes
        $title = preg_replace('/\s*(manga|manhwa|manhua|webtoon)\s*/i', '', $title);
        
        // Remove special characters
        $title = preg_replace('/[^a-z0-9\s]/', '', $title);
        
        // Remove extra spaces
        $title = preg_replace('/\s+/', ' ', $title);
        
        // Trim
        $title = trim($title);
        
        return $title;
    }
    
    /**
     * Calculate similarity percentage between two strings
     */
    private function calculate_similarity($str1, $str2) {
        // Use Levenshtein distance
        $lev = levenshtein($str1, $str2);
        $max_len = max(strlen($str1), strlen($str2));
        
        if ($max_len == 0) {
            return 100;
        }
        
        $similarity = (1 - ($lev / $max_len)) * 100;
        
        return round($similarity, 2);
    }
    
    /**
     * Auto-import manhwa
     */
    private function auto_import_manhwa($url) {
        try {
            $scraper_manager = MWS_Scraper_Manager::get_instance();
            
            // Scrape full data
            $scraped = $scraper_manager->scrape_url($url);
            
            if (is_wp_error($scraped)) {
                return false;
            }
            
            // Create post
            $post_id = wp_insert_post([
                'post_title' => $scraped['title'] ?? 'Untitled',
                'post_type' => 'manhwa',
                'post_status' => 'publish',
                'post_content' => $scraped['synopsis'] ?? '',
            ]);
            
            if (is_wp_error($post_id)) {
                return false;
            }
            
            // Save metadata
            update_post_meta($post_id, '_manhwa_type', $scraped['type'] ?? 'Manhwa');
            update_post_meta($post_id, '_manhwa_status', $scraped['status'] ?? 'ongoing');
            update_post_meta($post_id, '_manhwa_author', $scraped['author'] ?? '');
            update_post_meta($post_id, '_manhwa_artist', $scraped['artist'] ?? '');
            update_post_meta($post_id, '_manhwa_rating', $scraped['rating'] ?? 0);
            update_post_meta($post_id, '_manhwa_alternative_title', $scraped['alternative_title'] ?? '');
            update_post_meta($post_id, '_manhwa_release_year', $scraped['release_year'] ?? '');
            update_post_meta($post_id, '_mws_source_url', $url);
            update_post_meta($post_id, '_mws_total_chapters', $scraped['total_chapters'] ?? 0);
            update_post_meta($post_id, '_mws_latest_chapter', $scraped['latest_chapter'] ?? '');
            update_post_meta($post_id, '_manhwa_chapters', $scraped['chapters'] ?? []);
            update_post_meta($post_id, '_mws_auto_discovered', current_time('mysql'));
            
            // Set genres (using correct taxonomy)
            if (!empty($scraped['genres'])) {
                $genre_ids = [];
                foreach ($scraped['genres'] as $genre_name) {
                    // Try manhwa_genre taxonomy first
                    $term = get_term_by('name', $genre_name, 'manhwa_genre');
                    if (!$term) {
                        $term = wp_insert_term($genre_name, 'manhwa_genre');
                        if (!is_wp_error($term)) {
                            $genre_ids[] = $term['term_id'];
                        }
                    } else {
                        $genre_ids[] = $term->term_id;
                    }
                }
                if (!empty($genre_ids)) {
                    wp_set_post_terms($post_id, $genre_ids, 'manhwa_genre');
                }
            }
            
            // Download cover if enabled
            if (get_option('mws_auto_download_covers', true) && !empty($scraped['cover_url'])) {
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                
                $attachment_id = media_sideload_image($scraped['cover_url'], $post_id, $scraped['title'], 'id');
                if (!is_wp_error($attachment_id)) {
                    set_post_thumbnail($post_id, $attachment_id);
                }
            }
            
            // Fire action
            do_action('mws_manhwa_auto_discovered', $post_id, $scraped);
            
            return $post_id;
            
        } catch (Exception $e) {
            $this->log("Error auto-importing: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log message
     */
    private function log($message) {
        if (class_exists('MWS_Logger')) {
            MWS_Logger::log($message, 'auto_discovery');
        }
        error_log('[MWS Auto Discovery] ' . $message);
    }
}

// Initialize
MWS_Auto_Discovery::get_instance();
