<?php
/**
 * AJAX Handler Class
 * Handles all AJAX requests
 */

if (!defined('ABSPATH')) {
    exit;
}

class MWS_Ajax_Handler {
    
    /**
     * Verify nonce
     */
    private static function verify_nonce() {
        if (!check_ajax_referer('mws_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'manhwa-scraper')], 403);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'manhwa-scraper')], 403);
        }
    }
    
    /**
     * Scrape single URL
     */
    public static function scrape_single() {
        self::verify_nonce();
        
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        
        if (empty($url)) {
            wp_send_json_error(['message' => __('URL is required', 'manhwa-scraper')]);
        }
        
        $scraper_manager = MWS_Scraper_Manager::get_instance();
        $result = $scraper_manager->scrape_url($url);
        
        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message(),
            ]);
        }
        
        wp_send_json_success([
            'message' => __('Scraped successfully', 'manhwa-scraper'),
            'data' => $result,
        ]);
    }
    
    /**
     * Search manhwa from sources
     */
    public static function search_manhwa() {
        self::verify_nonce();
        
        $keyword = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : '';
        $source = isset($_POST['source']) ? sanitize_text_field($_POST['source']) : 'all';
        $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
        
        // Get filter values
        $filters = [
            'type' => isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '',
            'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '',
            'genre' => isset($_POST['genre']) ? sanitize_text_field($_POST['genre']) : '',
            'order' => isset($_POST['order']) ? sanitize_text_field($_POST['order']) : 'popular',
        ];
        
        // Check if we have keyword or filters
        $has_filters = !empty($filters['type']) || !empty($filters['status']) || !empty($filters['genre']);
        
        if (empty($keyword) && !$has_filters) {
            wp_send_json_error(['message' => __('Search keyword or at least one filter is required', 'manhwa-scraper')]);
        }
        
        if (!empty($keyword) && strlen($keyword) < 2) {
            wp_send_json_error(['message' => __('Please enter at least 2 characters', 'manhwa-scraper')]);
        }
        
        $scraper_manager = MWS_Scraper_Manager::get_instance();
        $result = $scraper_manager->search_manhwa($keyword, $source, $filters, $page);
        
        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message(),
            ]);
        }
        
        // Check each result for duplicates in database
        $detector = MWS_Duplicate_Detector::get_instance();
        foreach ($result['results'] as &$item) {
            $dup_check = $detector->check_duplicate([
                'title' => $item['title'],
                'slug' => $item['slug'] ?? '',
                'url' => $item['url'] ?? '',
            ]);
            
            if ($dup_check) {
                $item['exists_in_db'] = true;
                $item['existing_id'] = $dup_check['post']['id'];
                $item['existing_edit_url'] = get_edit_post_link($dup_check['post']['id'], 'raw');
                $item['existing_view_url'] = get_permalink($dup_check['post']['id']);
                $item['match_type'] = $dup_check['match_type'];
            } else {
                $item['exists_in_db'] = false;
            }
        }
        unset($item);
        
        wp_send_json_success([
            'message' => sprintf(__('Found %d results', 'manhwa-scraper'), $result['count']),
            'keyword' => $result['keyword'],
            'results' => $result['results'],
            'count' => $result['count'],
            'page' => $page,
            'errors' => $result['errors'] ?? [],
        ]);
    }
    
    /**
     * Browse popular/latest manhwa from sources
     */
    public static function browse_manhwa() {
        self::verify_nonce();
        
        $source = isset($_POST['source']) ? sanitize_text_field($_POST['source']) : '';
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'popular'; // popular or latest
        $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
        
        if (empty($source)) {
            wp_send_json_error(['message' => __('Source is required', 'manhwa-scraper')]);
        }
        
        $scraper_manager = MWS_Scraper_Manager::get_instance();
        $scraper = $scraper_manager->get_scraper($source);
        
        if (!$scraper) {
            wp_send_json_error(['message' => __('Invalid source', 'manhwa-scraper')]);
        }
        
        // Try to get popular/latest from source
        try {
            if ($type === 'popular') {
                $result = $scraper->get_popular($page);
            } else {
                $result = $scraper->get_latest($page);
            }
            
            if (is_wp_error($result)) {
                wp_send_json_error([
                    'message' => $result->get_error_message(),
                ]);
            }
            
            // Check each result for duplicates in database
            $detector = MWS_Duplicate_Detector::get_instance();
            foreach ($result as &$item) {
                $dup_check = $detector->check_duplicate([
                    'title' => $item['title'],
                    'slug' => $item['slug'] ?? '',
                    'url' => $item['url'] ?? '',
                ]);
                
                if ($dup_check) {
                    $item['exists_in_db'] = true;
                    $item['existing_id'] = $dup_check['post']['id'];
                    $item['existing_edit_url'] = get_edit_post_link($dup_check['post']['id'], 'raw');
                    $item['existing_view_url'] = get_permalink($dup_check['post']['id']);
                } else {
                    $item['exists_in_db'] = false;
                }
            }
            unset($item);
            
            wp_send_json_success([
                'message' => sprintf(__('Found %d %s manhwa', 'manhwa-scraper'), count($result), $type),
                'type' => $type,
                'source' => $source,
                'results' => $result,
                'count' => count($result),
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Check for duplicate manhwa
     */
    public static function check_duplicate() {
        self::verify_nonce();
        
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $slug = isset($_POST['slug']) ? sanitize_text_field($_POST['slug']) : '';
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        
        if (empty($title) && empty($slug)) {
            wp_send_json_error(['message' => __('Title or slug is required', 'manhwa-scraper')]);
        }
        
        $detector = MWS_Duplicate_Detector::get_instance();
        
        $result = $detector->check_duplicate([
            'title' => $title,
            'slug' => $slug,
            'url' => $url,
        ]);
        
        if ($result) {
            wp_send_json_success([
                'is_duplicate' => true,
                'match_type' => $result['match_type'],
                'confidence' => $result['confidence'],
                'existing' => $result['post'],
            ]);
        } else {
            wp_send_json_success([
                'is_duplicate' => false,
            ]);
        }
    }
    
    /**
     * Scrape chapter images from URL
     */
    public static function scrape_chapter_images() {
        self::verify_nonce();
        
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        
        if (empty($url)) {
            wp_send_json_error(['message' => __('Chapter URL is required', 'manhwa-scraper')]);
        }
        
        // Log for debugging
        error_log('[MWS Debug] Scraping chapter URL: ' . $url);
        
        $scraper_manager = MWS_Scraper_Manager::get_instance();
        
        // Check if scraper exists for this URL
        $scraper = $scraper_manager->get_scraper_for_url($url);
        if (!$scraper) {
            error_log('[MWS Debug] No scraper found for URL: ' . $url);
            wp_send_json_error([
                'message' => __('No scraper found for this URL. Supported: manhwaku.id, asura', 'manhwa-scraper'),
                'url' => $url,
            ]);
        }
        
        $result = $scraper_manager->scrape_chapter_images($url);
        
        if (is_wp_error($result)) {
            $proxy_enabled = get_option('mws_proxy_enabled', false);
            $proxy_host = get_option('mws_proxy_host', '');
            $proxy_status = $proxy_enabled && !empty($proxy_host) ? 'ON:' . $proxy_host : 'OFF';
            
            error_log('[MWS Debug] Scrape error: ' . $result->get_error_message() . ' | Proxy: ' . $proxy_status);
            wp_send_json_error([
                'message' => $result->get_error_message() . ' (Proxy: ' . $proxy_status . ')',
                'url' => $url,
                'proxy' => $proxy_status,
            ]);
        }
        
        // Try to detect manhwa post from URL
        $manhwa_post = self::detect_manhwa_from_chapter_url($url);
        if ($manhwa_post) {
            $result['manhwa_post'] = [
                'id' => $manhwa_post->ID,
                'title' => $manhwa_post->post_title,
                'edit_url' => get_edit_post_link($manhwa_post->ID, 'raw'),
            ];
        }
        
        // Get all manhwa posts for selection
        $manhwa_posts = get_posts([
            'post_type' => 'manhwa',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);
        $result['available_manhwa'] = array_map(function($post) {
            return ['id' => $post->ID, 'title' => $post->post_title];
        }, $manhwa_posts);
        
        wp_send_json_success([
            'message' => sprintf(__('Scraped %d images from chapter', 'manhwa-scraper'), $result['total_images']),
            'data' => $result,
        ]);
    }
    
    /**
     * Detect manhwa post from chapter URL
     */
    private static function detect_manhwa_from_chapter_url($url) {
        // Extract slug from URL (e.g., /solo-leveling-chapter-1/ -> solo-leveling)
        if (preg_match('/\/([a-z0-9-]+)-chapter-\d+/i', $url, $matches)) {
            $slug = $matches[1];
            
            // Try to find manhwa post by slug
            $posts = get_posts([
                'post_type' => 'manhwa',
                'name' => $slug,
                'posts_per_page' => 1,
            ]);
            
            if (!empty($posts)) {
                return $posts[0];
            }
            
            // Try partial title match
            $posts = get_posts([
                'post_type' => 'manhwa',
                'posts_per_page' => 1,
                's' => str_replace('-', ' ', $slug),
            ]);
            
            if (!empty($posts)) {
                return $posts[0];
            }
        }
        
        return null;
    }
    
    /**
     * Save chapter to manhwa post
     */
    public static function save_chapter_to_post() {
        self::verify_nonce();
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $chapter_data = isset($_POST['chapter_data']) ? $_POST['chapter_data'] : '';
        
        if (!$post_id) {
            wp_send_json_error(['message' => __('Manhwa post ID is required', 'manhwa-scraper')]);
        }
        
        // Parse chapter data
        if (is_string($chapter_data)) {
            $chapter_data = json_decode(stripslashes($chapter_data), true);
        }
        
        if (empty($chapter_data)) {
            wp_send_json_error(['message' => __('Chapter data is required', 'manhwa-scraper')]);
        }
        
        // Get post
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'manhwa') {
            wp_send_json_error(['message' => __('Invalid manhwa post', 'manhwa-scraper')]);
        }
        
        // Get existing chapters
        $chapters = get_post_meta($post_id, '_manhwa_chapters', true);
        if (!is_array($chapters)) {
            $chapters = [];
        }
        
        // Check if chapter already exists (by URL or chapter number)
        $chapter_exists = false;
        $existing_index = null;
        $chapter_number = $chapter_data['chapter_number'] ?? null;
        
        foreach ($chapters as $index => $existing) {
            // Check by URL
            if (!empty($existing['url']) && $existing['url'] === ($chapter_data['chapter_url'] ?? '')) {
                $chapter_exists = true;
                $existing_index = $index;
                break;
            }
            // Check by chapter number in title
            if ($chapter_number && preg_match('/chapter\s*' . preg_quote($chapter_number, '/') . '\b/i', $existing['title'])) {
                $chapter_exists = true;
                $existing_index = $index;
                break;
            }
        }
        
        if ($chapter_exists && $existing_index !== null) {
            // UPDATE EXISTING: Update date to current date when scraping images
            $chapters[$existing_index]['images'] = $chapter_data['images'] ?? [];
            // Update date to current scrape date
            $chapters[$existing_index]['date'] = current_time('j M Y'); // e.g. "8 Feb 2026"
            $chapters[$existing_index]['added_at'] = current_time('mysql');
            // Update URL if provided
            if (!empty($chapter_data['chapter_url'])) {
                $chapters[$existing_index]['url'] = $chapter_data['chapter_url'];
            }
            // Update title if provided
            if (!empty($chapter_data['chapter_title'])) {
                $chapters[$existing_index]['title'] = $chapter_data['chapter_title'];
            }
            // Mark as images updated
            $chapters[$existing_index]['images_updated_at'] = current_time('mysql');
            
            // Reference for later use
            $saved_chapter = $chapters[$existing_index];
        } else {
            // NEW CHAPTER: Add with current date
            $saved_chapter = [
                'title' => $chapter_data['chapter_title'] ?? 'Chapter ' . ($chapter_data['chapter_number'] ?? ''),
                'url' => $chapter_data['chapter_url'] ?? '',
                'date' => current_time('j M Y'), // e.g. "8 Feb 2026"
                'added_at' => current_time('mysql'), // Full datetime for accurate "X ago"
                'images' => $chapter_data['images'] ?? [],
            ];
            // Add new chapter at the beginning (newest first)
            array_unshift($chapters, $saved_chapter);
        }
        
        // Save chapters
        update_post_meta($post_id, '_manhwa_chapters', $chapters);
        
        // Also save chapter images separately for easier access
        $chapter_key = 'chapter_' . ($chapter_number ?? sanitize_title($saved_chapter['title']));
        update_post_meta($post_id, '_mws_chapter_images_' . $chapter_key, $saved_chapter['images']);
        
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
        // Clear cache
        clean_post_cache($post_id);
        
        wp_send_json_success([
            'message' => sprintf(
                __('Chapter "%s" saved to "%s"', 'manhwa-scraper'),
                $saved_chapter['title'],
                $post->post_title
            ),
            'post_id' => $post_id,
            'post_title' => $post->post_title,
            'edit_url' => get_edit_post_link($post_id, 'raw'),
            'chapter_count' => count($chapters),
        ]);
    }
    
    /**
     * Bulk scrape
     */
    public static function scrape_bulk() {
        self::verify_nonce();
        
        $source_id = isset($_POST['source']) ? sanitize_text_field($_POST['source']) : '';
        $start_page = isset($_POST['start_page']) ? max(1, intval($_POST['start_page'])) : 1;
        $pages = isset($_POST['pages']) ? max(1, intval($_POST['pages'])) : 1;
        $scrape_details = isset($_POST['scrape_details']) && $_POST['scrape_details'] === 'true';
        
        if (empty($source_id)) {
            wp_send_json_error(['message' => __('Source is required', 'manhwa-scraper')]);
        }
        
        // Increase time limit for bulk operations
        set_time_limit(300);
        
        $scraper_manager = MWS_Scraper_Manager::get_instance();
        $result = $scraper_manager->bulk_scrape($source_id, $start_page, $pages, $scrape_details);
        
        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message(),
            ]);
        }
        
        // Check each result for duplicates in database
        $detector = MWS_Duplicate_Detector::get_instance();
        if (!empty($result['items']) && is_array($result['items'])) {
            foreach ($result['items'] as &$item) {
                $dup_check = $detector->check_duplicate([
                    'title' => $item['title'] ?? '',
                    'slug' => $item['slug'] ?? '',
                    'url' => $item['url'] ?? $item['source_url'] ?? '',
                ]);
                
                if ($dup_check) {
                    $item['exists_in_db'] = true;
                    $item['existing_id'] = $dup_check['post']['id'];
                    $item['existing_edit_url'] = get_edit_post_link($dup_check['post']['id'], 'raw');
                    $item['existing_view_url'] = get_permalink($dup_check['post']['id']);
                    $item['match_type'] = $dup_check['match_type'];
                } else {
                    $item['exists_in_db'] = false;
                }
            }
            unset($item);
        }
        
        $end_page = $start_page + $pages - 1;
        wp_send_json_success([
            'message' => sprintf(__('Scraped %d items from pages %d-%d', 'manhwa-scraper'), $result['count'], $start_page, $end_page),
            'data' => $result,
        ]);
    }
    
    /**
     * Import manhwa to WordPress
     */
    public static function import_manhwa() {
        self::verify_nonce();
        
        $data = isset($_POST['data']) ? $_POST['data'] : '';
        $download_cover = isset($_POST['download_cover']) && $_POST['download_cover'] === 'true';
        $create_post = isset($_POST['create_post']) && $_POST['create_post'] === 'true';
        
        if (empty($data)) {
            wp_send_json_error(['message' => __('No data provided', 'manhwa-scraper')]);
        }
        
        // Parse data if it's a string
        if (is_string($data)) {
            $data = json_decode(stripslashes($data), true);
        }
        
        if (empty($data) || !is_array($data)) {
            wp_send_json_error(['message' => __('Invalid data format', 'manhwa-scraper')]);
        }
        
        $result = [
            'imported' => 0,
            'updated' => 0,
            'errors' => [],
            'posts' => [],
            'json' => '',
        ];
        
        // Normalize single item to array
        if (isset($data['title'])) {
            $data = [$data];
        }
        
        foreach ($data as $manhwa) {
            try {
                $post_id = null;
                $is_update = false;
                
                // Check if this is an update request
                if (!empty($manhwa['update_existing']) && !empty($manhwa['existing_id'])) {
                    $is_update = true;
                    $existing_id = intval($manhwa['existing_id']);
                    
                    // Update existing post
                    $detector = MWS_Duplicate_Detector::get_instance();
                    $post_id = $detector->update_existing($existing_id, $manhwa);
                    
                    if (is_wp_error($post_id)) {
                        $result['errors'][] = [
                            'title' => $manhwa['title'] ?? 'Unknown',
                            'error' => $post_id->get_error_message(),
                        ];
                        continue;
                    }
                    
                    // Download cover if requested
                    if ($download_cover && !empty($manhwa['thumbnail_url'])) {
                        $image_downloader = MWS_Image_Downloader::get_instance();
                        $attachment_id = $image_downloader->download_to_media_library(
                            $manhwa['thumbnail_url'],
                            $manhwa['title'] ?? ''
                        );
                        
                        if (!is_wp_error($attachment_id)) {
                            set_post_thumbnail($post_id, $attachment_id);
                        }
                    }
                    
                    $result['updated']++;
                    $result['posts'][] = [
                        'id' => $post_id,
                        'title' => $manhwa['title'],
                        'edit_url' => get_edit_post_link($post_id, 'raw'),
                        'action' => 'updated',
                    ];
                    
                    continue;
                }
                
                // Create post if requested and Manhwa Manager is active
                if ($create_post && post_type_exists('manhwa')) {
                    $post_id = self::create_manhwa_post($manhwa, $download_cover);
                    
                    if (is_wp_error($post_id)) {
                        $result['errors'][] = [
                            'title' => $manhwa['title'] ?? 'Unknown',
                            'error' => $post_id->get_error_message(),
                        ];
                        continue;
                    }
                    
                    $result['posts'][] = [
                        'id' => $post_id,
                        'title' => $manhwa['title'],
                        'edit_url' => get_edit_post_link($post_id, 'raw'),
                        'action' => 'created',
                    ];
                } elseif ($download_cover && !empty($manhwa['thumbnail_url'])) {
                    // Just download cover
                    $image_downloader = MWS_Image_Downloader::get_instance();
                    $attachment_id = $image_downloader->download_to_media_library(
                        $manhwa['thumbnail_url'],
                        $manhwa['title'] ?? ''
                    );
                    
                    if (!is_wp_error($attachment_id)) {
                        $manhwa['local_thumbnail_id'] = $attachment_id;
                        $manhwa['local_thumbnail_url'] = wp_get_attachment_url($attachment_id);
                    }
                }
                
                $result['imported']++;
                
            } catch (Exception $e) {
                $result['errors'][] = [
                    'title' => $manhwa['title'] ?? 'Unknown',
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        // Generate JSON export
        $result['json'] = MWS_Json_Exporter::export_standard($data);
        
        wp_send_json_success([
            'message' => sprintf(__('Imported %d manhwa', 'manhwa-scraper'), $result['imported']),
            'result' => $result,
        ]);
    }
    
    /**
     * Create manhwa post (for Manhwa Manager plugin)
     */
    private static function create_manhwa_post($manhwa, $download_cover = true) {
        // Check if post already exists by slug
        $slug = $manhwa['slug'] ?? sanitize_title($manhwa['title']);
        $existing = get_posts([
            'post_type' => 'manhwa',
            'name' => $slug,
            'posts_per_page' => 1,
        ]);
        
        if (!empty($existing)) {
            // Update existing post
            $post_id = $existing[0]->ID;
            
            // Update post content (use description or synopsis)
            wp_update_post([
                'ID' => $post_id,
                'post_title' => $manhwa['title'],
                'post_content' => $manhwa['description'] ?? $manhwa['synopsis'] ?? '',
            ]);
        } else {
            // Create new post
            $post_id = wp_insert_post([
                'post_type' => 'manhwa',
                'post_title' => $manhwa['title'],
                'post_name' => $slug,
                'post_content' => $manhwa['description'] ?? $manhwa['synopsis'] ?? '',
                'post_excerpt' => '',
                'post_status' => 'publish',
            ], true);
            
            if (is_wp_error($post_id)) {
                return $post_id;
            }
        }
        
        // Meta mapping: scraped_key => manhwa_manager_meta_key
        $meta_mapping = [
            'author' => '_manhwa_author',
            'artist' => '_manhwa_artist',
            'alternative_title' => '_manhwa_alternative_title',
            'type' => '_manhwa_type',
            'status' => '_manhwa_status',
            'rating' => '_manhwa_rating',
            'views' => '_manhwa_views',
            'release_year' => '_manhwa_release_year',
            'released' => '_manhwa_release_year',  // Fallback for 'released' field
            'thumbnail_url' => '_manhwa_cover_url',
            'cover' => '_manhwa_cover_url',  // Fallback for 'cover' field
        ];
        
        foreach ($meta_mapping as $key => $meta_key) {
            if (isset($manhwa[$key]) && $manhwa[$key] !== '' && $manhwa[$key] !== null) {
                update_post_meta($post_id, $meta_key, $manhwa[$key]);
            }
        }
        
        // Save chapters in correct format for Manhwa Manager
        if (!empty($manhwa['chapters']) && is_array($manhwa['chapters'])) {
            $chapters = [];
            foreach ($manhwa['chapters'] as $chapter) {
                $chapters[] = [
                    'title' => $chapter['title'] ?? 'Chapter ' . ($chapter['number'] ?? ''),
                    'url' => $chapter['url'] ?? '',
                    'date' => $chapter['date'] ?? current_time('j M Y'),
                    'added_at' => $chapter['added_at'] ?? current_time('mysql'),
                ];
            }
            update_post_meta($post_id, '_manhwa_chapters', $chapters);
        }
        
        // Save scraper-specific meta for tracking updates
        update_post_meta($post_id, '_mws_source', $manhwa['source'] ?? '');
        update_post_meta($post_id, '_mws_source_url', $manhwa['source_url'] ?? '');
        update_post_meta($post_id, '_mws_total_chapters', $manhwa['total_chapters'] ?? count($manhwa['chapters'] ?? []));
        update_post_meta($post_id, '_mws_latest_chapter', $manhwa['latest_chapter'] ?? '');
        update_post_meta($post_id, '_mws_last_updated', current_time('mysql'));
        update_post_meta($post_id, '_mws_scraped_at', $manhwa['scraped_at'] ?? current_time('c'));
        
        // Set genres taxonomy
        if (!empty($manhwa['genres']) && taxonomy_exists('manhwa_genre')) {
            wp_set_object_terms($post_id, $manhwa['genres'], 'manhwa_genre');
        }
        
        // Download and set featured image (check both 'cover' and 'thumbnail_url')
        $cover_url = $manhwa['cover'] ?? $manhwa['thumbnail_url'] ?? '';
        if ($download_cover && !empty($cover_url)) {
            $image_downloader = MWS_Image_Downloader::get_instance();
            $attachment_id = $image_downloader->get_or_download(
                $cover_url,
                $manhwa['title'],
                $post_id
            );
            
            if (!is_wp_error($attachment_id)) {
                set_post_thumbnail($post_id, $attachment_id);
                update_post_meta($post_id, '_manhwa_cover_id', $attachment_id);
            }
        }
        
        return $post_id;
    }
    
    /**
     * Download cover image
     */
    public static function download_cover() {
        self::verify_nonce();
        
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        
        if (empty($url)) {
            wp_send_json_error(['message' => __('URL is required', 'manhwa-scraper')]);
        }
        
        $image_downloader = MWS_Image_Downloader::get_instance();
        $attachment_id = $image_downloader->download_to_media_library($url, $title);
        
        if (is_wp_error($attachment_id)) {
            wp_send_json_error([
                'message' => $attachment_id->get_error_message(),
            ]);
        }
        
        wp_send_json_success([
            'message' => __('Cover downloaded successfully', 'manhwa-scraper'),
            'attachment_id' => $attachment_id,
            'url' => wp_get_attachment_url($attachment_id),
        ]);
    }
    
    /**
     * Get sources list
     */
    public static function get_sources() {
        self::verify_nonce();
        
        $scraper_manager = MWS_Scraper_Manager::get_instance();
        $sources = $scraper_manager->get_sources_info();
        
        wp_send_json_success([
            'sources' => $sources,
        ]);
    }
    
    /**
     * Test connection to a source
     */
    public static function test_connection() {
        self::verify_nonce();
        
        $source_id = isset($_POST['source']) ? sanitize_text_field($_POST['source']) : '';
        
        if (empty($source_id)) {
            // Test all sources
            $scraper_manager = MWS_Scraper_Manager::get_instance();
            $results = $scraper_manager->test_all_connections();
            
            wp_send_json_success([
                'results' => $results,
            ]);
        } else {
            $scraper_manager = MWS_Scraper_Manager::get_instance();
            $result = $scraper_manager->test_connection($source_id);
            
            wp_send_json_success([
                'source' => $source_id,
                'result' => $result,
            ]);
        }
    }
    
    /**
     * Get manhwa chapters for bulk scraping
     */
    public static function get_manhwa_chapters() {
        self::verify_nonce();
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if (!$post_id) {
            wp_send_json_error(['message' => __('Manhwa ID is required', 'manhwa-scraper')]);
        }
        
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'manhwa') {
            wp_send_json_error(['message' => __('Invalid manhwa post', 'manhwa-scraper')]);
        }
        
        $chapters = get_post_meta($post_id, '_manhwa_chapters', true);
        if (!is_array($chapters)) {
            $chapters = [];
        }
        
        // Prepare chapters data for bulk scraping
        $chapters_data = [];
        foreach ($chapters as $index => $chapter) {
            $chapters_data[] = [
                'index' => $index,
                'number' => $chapter['number'] ?? null,
                'title' => $chapter['title'] ?? 'Chapter ' . ($index + 1),
                'url' => $chapter['url'] ?? '',
                'date' => $chapter['date'] ?? '',
                'images' => $chapter['images'] ?? [],
                'has_images' => !empty($chapter['images']),
                'image_count' => is_array($chapter['images'] ?? null) ? count($chapter['images']) : 0,
            ];
        }
        
        wp_send_json_success([
            'post_id' => $post_id,
            'post_title' => $post->post_title,
            'total_chapters' => count($chapters),
            'chapters' => $chapters_data,
        ]);
    }
    
    /**
     * Download chapter images to local folder
     */
    public static function download_chapter_images() {
        self::verify_nonce();
        
        $start_time = microtime(true);
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $chapter_data = isset($_POST['chapter_data']) ? $_POST['chapter_data'] : '';
        
        if (!$post_id) {
            wp_send_json_error(['message' => __('Manhwa ID is required', 'manhwa-scraper')]);
        }
        
        // Parse chapter data
        if (is_string($chapter_data)) {
            $chapter_data = json_decode(stripslashes($chapter_data), true);
        }
        
        if (empty($chapter_data) || empty($chapter_data['images'])) {
            wp_send_json_error(['message' => __('No images to download', 'manhwa-scraper')]);
        }
        
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'manhwa') {
            wp_send_json_error(['message' => __('Invalid manhwa post', 'manhwa-scraper')]);
        }
        
        // Increase time limit
        set_time_limit(600);
        
        // Get chapter number
        $chapter_number = $chapter_data['chapter_number'] ?? $chapter_data['number'] ?? '1';
        
        // Get concurrent downloads setting
        $concurrent = get_option('mws_concurrent_downloads', 5);
        
        // Download images with parallel processing
        $image_downloader = MWS_Image_Downloader::get_instance();
        $result = $image_downloader->download_chapter_images(
            $chapter_data['images'],
            $post->post_name,
            $chapter_number,
            null, // no progress callback
            $concurrent
        );
        
        // Update chapter data with local URLs
        if (!empty($result['images'])) {
            $chapters = get_post_meta($post_id, '_manhwa_chapters', true);
            if (is_array($chapters)) {
                // Get all possible identifiers from chapter_data
                $data_title = $chapter_data['title'] ?? $chapter_data['chapter_title'] ?? '';
                $data_url = $chapter_data['url'] ?? '';
                $data_number = $chapter_number;
                
                error_log("MWS Download: Attempting to match chapter - Title: '$data_title', URL: '$data_url', Number: '$data_number'");
                error_log("MWS Download: Total chapters in DB: " . count($chapters));
                
                $matched = false;
                foreach ($chapters as $index => $chapter) {
                    // Try multiple matching strategies
                    $match_by_url = !empty($data_url) && isset($chapter['url']) && $chapter['url'] === $data_url;
                    $match_by_title = !empty($data_title) && isset($chapter['title']) && $chapter['title'] === $data_title;
                    $match_by_number = isset($chapter['number']) && $chapter['number'] == $data_number;
                    
                    // Also try partial title match for "Chapter X" format
                    $match_by_partial = false;
                    if (!empty($data_title) && isset($chapter['title'])) {
                        // Extract number from both titles
                        preg_match('/\d+/', $data_title, $m1);
                        preg_match('/\d+/', $chapter['title'], $m2);
                        if (!empty($m1[0]) && !empty($m2[0]) && $m1[0] === $m2[0]) {
                            $match_by_partial = true;
                        }
                    }
                    
                    if ($match_by_url || $match_by_title || $match_by_number || $match_by_partial) {
                        error_log("MWS Download: ✓ Matched at index $index - URL: " . ($match_by_url ? 'YES' : 'NO') . 
                                  ", Title: " . ($match_by_title ? 'YES' : 'NO') . 
                                  ", Number: " . ($match_by_number ? 'YES' : 'NO') . 
                                  ", Partial: " . ($match_by_partial ? 'YES' : 'NO'));
                        
                        // Merge downloaded images with existing images
                        // Only update images that were successfully downloaded (have 'local' => true)
                        $existing_images = $chapters[$index]['images'] ?? [];
                        $updated_images = [];
                        $local_count = 0;
                        
                        foreach ($result['images'] as $img_index => $downloaded_img) {
                            if (isset($downloaded_img['local']) && $downloaded_img['local'] === true) {
                                // Successfully downloaded - use local URL
                                $updated_images[$img_index] = $downloaded_img['url'];
                                $local_count++;
                            } elseif (isset($existing_images[$img_index])) {
                                // Download failed or skipped - keep existing URL
                                $updated_images[$img_index] = is_array($existing_images[$img_index]) 
                                    ? ($existing_images[$img_index]['url'] ?? $existing_images[$img_index]) 
                                    : $existing_images[$img_index];
                            }
                        }
                        
                        // Fill in any missing images from existing data
                        foreach ($existing_images as $img_index => $existing_img) {
                            if (!isset($updated_images[$img_index])) {
                                $updated_images[$img_index] = is_array($existing_img) 
                                    ? ($existing_img['url'] ?? $existing_img) 
                                    : $existing_img;
                            }
                        }
                        
                        $chapters[$index]['images'] = array_values($updated_images);
                        $chapters[$index]['images_local'] = ($local_count === count($updated_images));
                        // Update chapter date to current time
                        $chapters[$index]['date'] = current_time('j M Y');
                        $chapters[$index]['added_at'] = current_time('mysql');
                        $matched = true;
                        
                        error_log("MWS Download: Updated chapter with $local_count local images out of " . count($updated_images) . " total");
                        break;
                    }
                }
                
                // If no match found, log for debugging
                if (!$matched) {
                    error_log("MWS Download: ✗ FAILED to match chapter!");
                    error_log("MWS Download: Available chapters: " . json_encode(array_map(function($ch) {
                        return [
                            'title' => $ch['title'] ?? 'N/A',
                            'number' => $ch['number'] ?? 'N/A',
                            'url' => isset($ch['url']) ? substr($ch['url'], 0, 50) . '...' : 'N/A'
                        ];
                    }, array_slice($chapters, 0, 5))));
                }
                
                update_post_meta($post_id, '_manhwa_chapters', $chapters);
                
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
                // Clear cache
                clean_post_cache($post_id);
            }
        }
        
        // Log download operation with duration
        $duration_ms = round((microtime(true) - $start_time) * 1000);
        global $wpdb;
        $logs_table = $wpdb->prefix . 'mws_logs';
        $wpdb->insert($logs_table, [
            'source' => 'DOWNLOAD',
            'url' => $chapter_data['url'] ?? '',
            'status' => 'success',
            'type' => 'download',
            'message' => sprintf('Downloaded %d images for %s', $result['success'], $chapter_data['title'] ?? 'Chapter'),
            'data' => json_encode([
                'post_id' => $post_id,
                'chapter' => $chapter_data['title'] ?? '',
                'success' => $result['success'],
                'skipped' => $result['skipped'],
                'failed' => $result['failed'],
            ]),
            'duration_ms' => $duration_ms,
            'created_at' => current_time('mysql'),
        ]);
        
        // Prepare local_images array with just URLs for easy consumption
        $local_images_urls = [];
        if (!empty($result['images'])) {
            foreach ($result['images'] as $img) {
                $local_images_urls[] = $img['url'] ?? $img;
            }
        }
        
        wp_send_json_success([
            'message' => sprintf(
                __('Downloaded %d images (%d skipped, %d failed)', 'manhwa-scraper'),
                $result['success'],
                $result['skipped'],
                $result['failed']
            ),
            'result' => $result,
            'local_images' => $local_images_urls, // Simple array of URL strings
        ]);
    }
    
    /**
     * Get statistics for analytics page
     */
    public static function get_statistics() {
        self::verify_nonce();
        
        global $wpdb;
        $logs_table = $wpdb->prefix . 'mws_logs';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$logs_table'") === $logs_table;
        
        // Summary stats
        $total_manhwa = wp_count_posts('manhwa');
        $total_manhwa = (int)($total_manhwa->publish ?? 0);
        
        $total_scrapes = 0;
        $total_success = 0;
        $total_errors = 0;
        
        if ($table_exists) {
            $total_scrapes = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$logs_table}");
            $total_success = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$logs_table} WHERE status = 'success'");
            $total_errors = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$logs_table} WHERE status = 'error'");
        }
        
        $success_rate = $total_scrapes > 0 ? round(($total_success / $total_scrapes) * 100, 1) : 0;
        
        // Activity data (last 30 days)
        $activity = self::get_activity_data($wpdb, $logs_table, $table_exists);
        
        // Source statistics
        $sources = self::get_source_stats($wpdb, $logs_table, $table_exists);
        
        // Recent errors
        $recent_errors = self::get_recent_errors($wpdb, $logs_table, $table_exists);
        
        // Top manhwa
        $top_manhwa = self::get_top_manhwa($wpdb, $logs_table, $table_exists);
        
        // Timeline
        $timeline = self::get_timeline($wpdb, $logs_table, $table_exists);
        
        wp_send_json_success([
            'summary' => [
                'total_manhwa' => $total_manhwa,
                'total_scrapes' => $total_scrapes,
                'success_rate' => $success_rate,
                'total_errors' => $total_errors,
            ],
            'activity' => $activity,
            'sources' => $sources,
            'recent_errors' => $recent_errors,
            'top_manhwa' => $top_manhwa,
            'timeline' => $timeline,
        ]);
    }
    
    /**
     * Get activity data for chart
     */
    private static function get_activity_data($wpdb, $logs_table, $table_exists) {
        $labels = [];
        $success = [];
        $errors = [];
        
        // Generate last 30 days
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date('M j', strtotime($date));
            
            if ($table_exists) {
                $success[] = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$logs_table} WHERE status = 'success' AND DATE(created_at) = %s",
                    $date
                ));
                $errors[] = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$logs_table} WHERE status = 'error' AND DATE(created_at) = %s",
                    $date
                ));
            } else {
                $success[] = 0;
                $errors[] = 0;
            }
        }
        
        return [
            'labels' => $labels,
            'success' => $success,
            'errors' => $errors,
        ];
    }
    
    /**
     * Get source statistics
     */
    private static function get_source_stats($wpdb, $logs_table, $table_exists) {
        $enabled = get_option('mws_enabled_sources', ['manhwaku', 'komikcast']);
        
        // Get all sources from scraper manager
        $all_sources = [];
        if (class_exists('MWS_Scraper_Manager')) {
            $scraper_manager = MWS_Scraper_Manager::get_instance();
            $all_sources = $scraper_manager->get_available_sources();
        }
        
        // Fallback
        if (empty($all_sources)) {
            $all_sources = [
                'manhwaku' => 'Manhwaku.id',
                'komikcast' => 'Komikcast',
                'manhwaland' => 'ManhwaLand',
                'manhwaindo' => 'ManhwaIndo',
                'mangasusuku' => 'Mangasusuku.com',
            ];
        }
        
        $sources = [];
        
        foreach ($all_sources as $id => $name) {
            $total = 0;
            $success = 0;
            $errors_count = 0;
            
            if ($table_exists) {
                $total = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$logs_table} WHERE source = %s",
                    $id
                ));
                $success = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$logs_table} WHERE source = %s AND status = 'success'",
                    $id
                ));
                $errors_count = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$logs_table} WHERE source = %s AND status = 'error'",
                    $id
                ));
            }
            
            $sources[] = [
                'id' => $id,
                'name' => $name,
                'total' => $total,
                'success' => $success,
                'errors' => $errors_count,
                'success_rate' => $total > 0 ? round(($success / $total) * 100, 1) : 0,
                'avg_response_time' => 'N/A', // Could be implemented with timing data
                'active' => in_array($id, $enabled),
            ];
        }
        
        return $sources;
    }
    
    /**
     * Get recent errors
     */
    private static function get_recent_errors($wpdb, $logs_table, $table_exists) {
        if (!$table_exists) {
            return [];
        }
        
        $errors = $wpdb->get_results(
            "SELECT * FROM {$logs_table} WHERE status = 'error' ORDER BY created_at DESC LIMIT 10"
        );
        
        $result = [];
        foreach ($errors as $error) {
            $data = json_decode($error->data, true);
            $title = $data['title'] ?? self::extract_title_from_url($error->url);
            
            $result[] = [
                'title' => $title,
                'message' => $error->message,
                'source' => ucfirst($error->source),
                'time_ago' => self::time_ago($error->created_at),
            ];
        }
        
        return $result;
    }
    
    /**
     * Get top scraped manhwa
     */
    private static function get_top_manhwa($wpdb, $logs_table, $table_exists) {
        if (!$table_exists) {
            return [];
        }
        
        $results = $wpdb->get_results(
            "SELECT url, COUNT(*) as count, MAX(data) as data 
             FROM {$logs_table} 
             WHERE status = 'success' 
             GROUP BY url 
             ORDER BY count DESC 
             LIMIT 5"
        );
        
        $manhwa = [];
        foreach ($results as $row) {
            $data = json_decode($row->data, true);
            $title = $data['title'] ?? self::extract_title_from_url($row->url);
            
            $manhwa[] = [
                'title' => $title,
                'count' => (int) $row->count,
            ];
        }
        
        return $manhwa;
    }
    
    /**
     * Get activity timeline
     */
    private static function get_timeline($wpdb, $logs_table, $table_exists) {
        if (!$table_exists) {
            return [];
        }
        
        $logs = $wpdb->get_results(
            "SELECT * FROM {$logs_table} ORDER BY created_at DESC LIMIT 10"
        );
        
        $timeline = [];
        foreach ($logs as $log) {
            $data = json_decode($log->data, true);
            $title = $data['title'] ?? self::extract_title_from_url($log->url);
            
            $timeline[] = [
                'title' => $title . ' - ' . ucfirst($log->source),
                'status' => $log->status,
                'time_ago' => self::time_ago($log->created_at),
            ];
        }
        
        return $timeline;
    }
    
    /**
     * Extract title from URL
     */
    private static function extract_title_from_url($url) {
        $path = parse_url($url, PHP_URL_PATH);
        $segments = array_filter(explode('/', $path));
        $last = end($segments);
        return ucwords(str_replace(['-', '_'], ' ', $last));
    }
    
    /**
     * Format time ago
     */
    private static function time_ago($datetime) {
        $timestamp = strtotime($datetime);
        $diff = current_time('timestamp') - $timestamp;
        
        if ($diff < 60) return 'Just now';
        if ($diff < 3600) return floor($diff / 60) . ' min ago';
        if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
        if ($diff < 604800) return floor($diff / 86400) . ' days ago';
        return date('M j, Y', $timestamp);
    }
    
    /**
     * Test proxy connection
     */
    public static function test_proxy() {
        self::verify_nonce();
        
        $proxy_host = isset($_POST['proxy_host']) ? sanitize_text_field($_POST['proxy_host']) : '';
        $proxy_port = isset($_POST['proxy_port']) ? intval($_POST['proxy_port']) : '';
        $proxy_username = isset($_POST['proxy_username']) ? sanitize_text_field($_POST['proxy_username']) : '';
        $proxy_password = isset($_POST['proxy_password']) ? $_POST['proxy_password'] : '';
        
        if (empty($proxy_host)) {
            wp_send_json_error(['message' => __('Proxy host is required', 'manhwa-scraper')]);
        }
        
        // Build proxy URL
        $proxy_url = $proxy_host;
        if (!empty($proxy_port)) {
            $proxy_url .= ':' . $proxy_port;
        }
        
        if (!empty($proxy_username)) {
            $auth = $proxy_username;
            if (!empty($proxy_password)) {
                $auth .= ':' . $proxy_password;
            }
            $proxy_url = $auth . '@' . $proxy_url;
        }
        
        // Test with httpbin.org which returns the IP
        $test_url = 'https://httpbin.org/ip';
        
        $response = wp_remote_get($test_url, [
            'timeout' => 30,
            'sslverify' => false,
            'proxy' => $proxy_url,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ],
        ]);
        
        if (is_wp_error($response)) {
            wp_send_json_error([
                'message' => sprintf(
                    __('Proxy connection failed: %s', 'manhwa-scraper'),
                    $response->get_error_message()
                ),
            ]);
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code !== 200) {
            wp_send_json_error([
                'message' => sprintf(
                    __('Proxy returned HTTP %d', 'manhwa-scraper'),
                    $status_code
                ),
            ]);
        }
        
        // Parse the IP response
        $data = json_decode($body, true);
        $ip = isset($data['origin']) ? $data['origin'] : 'Unknown';
        
        wp_send_json_success([
            'message' => sprintf(
                __('Proxy working! Your request IP: %s', 'manhwa-scraper'),
                $ip
            ),
            'ip' => $ip,
        ]);
    }
    
    // ==========================================
    // QUEUE HANDLERS
    // ==========================================
    
    /**
     * Add manhwa to download queue
     */
    public static function add_to_queue() {
        self::verify_nonce();
        
        $manhwa_id = isset($_POST['manhwa_id']) ? intval($_POST['manhwa_id']) : 0;
        $skip_existing = isset($_POST['skip_existing']) && $_POST['skip_existing'] == '1';
        $skip_downloaded = isset($_POST['skip_downloaded']) && $_POST['skip_downloaded'] == '1';
        
        if (!$manhwa_id) {
            wp_send_json_error(['message' => 'Invalid manhwa ID']);
            return;
        }
        
        $queue_manager = MWS_Queue_Manager::get_instance();
        $result = $queue_manager->add_manhwa_to_queue($manhwa_id, $skip_existing, $skip_downloaded);
        
        if ($result['success']) {
            wp_send_json_success([
                'added' => $result['added'],
                'skipped' => $result['skipped']
            ]);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    }
    
    /**
     * Get queue items
     */
    public static function get_queue() {
        self::verify_nonce();
        
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : null;
        
        $queue_manager = MWS_Queue_Manager::get_instance();
        $items = $queue_manager->get_queue($status ?: null, 100);
        $stats = $queue_manager->get_stats();
        
        // Add time_ago to each item
        foreach ($items as &$item) {
            $item->time_ago = human_time_diff(strtotime($item->created_at), current_time('timestamp')) . ' ago';
        }
        
        wp_send_json_success([
            'items' => $items,
            'stats' => $stats
        ]);
    }
    
    /**
     * Process single queue item
     */
    public static function process_queue_item() {
        // Increase limits for long-running process
        @set_time_limit(120);
        @ini_set('memory_limit', '512M');
        
        // Set up error handling
        $error_occurred = false;
        $error_message = '';
        
        // Custom error handler
        set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$error_occurred, &$error_message) {
            $error_occurred = true;
            $error_message = "PHP Error [$errno]: $errstr in $errfile:$errline";
            return true;
        });
        
        self::verify_nonce();
        
        $queue_manager = MWS_Queue_Manager::get_instance();
        $item = null;
        
        try {
            // Check if already processing - reset old ones
            $processing = $queue_manager->get_processing_item();
            if ($processing) {
                // Check if stuck (more than 2 minutes)
                $started = strtotime($processing->started_at);
                if ($started && (time() - $started) > 120) {
                    // Reset stuck item
                    $queue_manager->update_status($processing->id, 'pending', [
                        'retry_count' => $processing->retry_count + 1,
                        'error_message' => 'Reset from stuck state'
                    ]);
                } else {
                    restore_error_handler();
                    wp_send_json_success([
                        'processed' => false,
                        'message' => 'Already processing: ' . $processing->chapter_title
                    ]);
                    return;
                }
            }
            
            // Get next item
            $item = $queue_manager->get_next_pending();
            
            if (!$item) {
                restore_error_handler();
                wp_send_json_success([
                    'processed' => false,
                    'message' => 'No pending items'
                ]);
                return;
            }
            
            // Process item
            $queue_manager->update_status($item->id, 'processing');
            
            // Scrape chapter images
            $scraper_manager = MWS_Scraper_Manager::get_instance();
            $chapter_data = $scraper_manager->scrape_chapter_images($item->chapter_url);
            
            if (!$chapter_data || !isset($chapter_data['images']) || empty($chapter_data['images'])) {
                throw new Exception('No images found from scraper');
            }
            
            $images_total = count($chapter_data['images']);
            $queue_manager->update_status($item->id, 'processing', ['images_total' => $images_total]);
            
            // Download images to local server
            $downloader = MWS_Image_Downloader::get_instance();
            
            // Get manhwa slug for folder organization
            $manhwa_post = get_post($item->manhwa_id);
            $manhwa_slug = $manhwa_post ? $manhwa_post->post_name : 'manhwa-' . $item->manhwa_id;
            $chapter_num = $chapter_data['chapter_number'] ?? $item->chapter_number;
            
            // Ensure parameters are strings
            $manhwa_slug = is_string($manhwa_slug) ? $manhwa_slug : strval($manhwa_slug);
            $chapter_num = is_string($chapter_num) ? $chapter_num : strval($chapter_num);
            
            $download_result = $downloader->download_chapter_images(
                $chapter_data['images'],     // 1st param: images array
                $manhwa_slug,                // 2nd param: manhwa slug (string)
                $chapter_num                 // 3rd param: chapter number (string)
            );
            
            if (!$download_result || !isset($download_result['success'])) {
                throw new Exception('Download function returned invalid result');
            }
            
            if ($download_result['success'] > 0) {
                // Sanitize images - ensure they are all strings (URLs)
                $sanitized_images = [];
                $raw_images = $download_result['images'] ?? [];
                
                if (is_array($raw_images)) {
                    foreach ($raw_images as $img) {
                        $url = '';
                        if (is_string($img)) {
                            $url = $img;
                        } elseif (is_array($img)) {
                            // Extract URL from array - check multiple possible keys
                            foreach (['local_url', 'url', 'src', 'path'] as $key) {
                                if (isset($img[$key]) && is_string($img[$key])) {
                                    $url = $img[$key];
                                    break;
                                }
                            }
                        }
                        if (!empty($url) && is_string($url)) {
                            $sanitized_images[] = strval($url);
                        }
                    }
                }
                
                // Update chapter data in post meta
                $chapters = get_post_meta($item->manhwa_id, '_manhwa_chapters', true);
                
                if (is_array($chapters) && !empty($chapters)) {
                    $updated = false;
                    $clean_chapters = [];
                    
                    foreach ($chapters as $key => $ch) {
                        // Ensure ch is an array
                        if (!is_array($ch)) {
                            continue;
                        }
                        
                        // Create a clean chapter entry
                        $clean_ch = [];
                        foreach ($ch as $field => $value) {
                            if ($field === 'images') {
                                // Always sanitize images array
                                $clean_images = [];
                                if (is_array($value)) {
                                    foreach ($value as $img) {
                                        $img_url = '';
                                        if (is_string($img)) {
                                            $img_url = $img;
                                        } elseif (is_array($img)) {
                                            foreach (['local_url', 'url', 'src', 'path'] as $k) {
                                                if (isset($img[$k]) && is_string($img[$k])) {
                                                    $img_url = $img[$k];
                                                    break;
                                                }
                                            }
                                        }
                                        if (!empty($img_url)) {
                                            $clean_images[] = strval($img_url);
                                        }
                                    }
                                }
                                $clean_ch['images'] = $clean_images;
                            } elseif (is_scalar($value)) {
                                // Only accept scalar values for other fields
                                $clean_ch[$field] = $value;
                            }
                        }
                        
                        // Check if this is the chapter we're updating
                        $ch_title = isset($clean_ch['title']) ? strval($clean_ch['title']) : '';
                        $ch_num = preg_replace('/[^0-9.]/', '', $ch_title);
                        $item_num = preg_replace('/[^0-9.]/', '', strval($item->chapter_number));
                        
                        if ($ch_num == $item_num || $ch_title == $item->chapter_title) {
                            // Update with new images
                            $clean_ch['images'] = $sanitized_images;
                            $updated = true;
                        }
                        
                        $clean_chapters[] = $clean_ch;
                    }
                    
                    if ($updated && !empty($clean_chapters)) {
                        // Use delete + add to avoid serialization issues
                        delete_post_meta($item->manhwa_id, '_manhwa_chapters');
                        add_post_meta($item->manhwa_id, '_manhwa_chapters', $clean_chapters, true);
                    }
                }
                
                $queue_manager->update_status($item->id, 'completed', [
                    'images_downloaded' => $download_result['success'],
                    'images_total' => $images_total
                ]);
                
                restore_error_handler();
                wp_send_json_success([
                    'processed' => true,
                    'item' => [
                        'id' => $item->id,
                        'manhwa_title' => $item->manhwa_title,
                        'chapter_title' => $item->chapter_title,
                        'images_downloaded' => $download_result['success'],
                        'images_total' => $images_total
                    ],
                    'stats' => $queue_manager->get_stats()
                ]);
                return;
            }
            
            throw new Exception('Download failed: ' . ($download_result['message'] ?? '0 images downloaded'));
            
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        } catch (Error $e) {
            $error_message = 'Fatal: ' . $e->getMessage();
        }
        
        // Handle error
        restore_error_handler();
        
        if ($item) {
            if ($item->retry_count < $item->max_retries) {
                $queue_manager->update_status($item->id, 'pending', [
                    'retry_count' => $item->retry_count + 1,
                    'error_message' => $error_message
                ]);
            } else {
                $queue_manager->update_status($item->id, 'failed', [
                    'error_message' => $error_message
                ]);
            }
        }
        
        wp_send_json_error([
            'message' => $error_message ?: 'Unknown error occurred',
            'stats' => $queue_manager->get_stats()
        ]);
    }
    
    /**
     * Retry all failed items
     */
    public static function retry_failed() {
        self::verify_nonce();
        
        $queue_manager = MWS_Queue_Manager::get_instance();
        $count = $queue_manager->retry_failed();
        
        wp_send_json_success(['count' => $count]);
    }
    
    /**
     * Clear queue
     */
    public static function clear_queue() {
        self::verify_nonce();
        
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : null;
        
        $queue_manager = MWS_Queue_Manager::get_instance();
        $queue_manager->clear_queue($status ?: null);
        
        wp_send_json_success();
    }
    
    /**
     * Delete single queue item
     */
    public static function delete_queue_item() {
        self::verify_nonce();
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if (!$id) {
            wp_send_json_error(['message' => 'Invalid ID']);
            return;
        }
        
        $queue_manager = MWS_Queue_Manager::get_instance();
        $queue_manager->delete_item($id);
        
        wp_send_json_success();
    }
    
    /**
     * Reset stuck processing items
     */
    public static function reset_stuck_processing() {
        self::verify_nonce();
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'mws_download_queue';
        
        // Reset items stuck in processing for more than 3 minutes
        $wpdb->query($wpdb->prepare(
            "UPDATE $table_name SET status = 'pending', retry_count = retry_count + 1 
             WHERE status = 'processing' AND started_at < %s",
            date('Y-m-d H:i:s', strtotime('-3 minutes'))
        ));
        
        // Also reset any that have been processing without a start time
        $wpdb->query(
            "UPDATE $table_name SET status = 'pending', retry_count = retry_count + 1 
             WHERE status = 'processing' AND started_at IS NULL"
        );
        
        wp_send_json_success();
    }
    
    /**
     * Force run auto-update check manually
     */
    public static function force_update_check() {
        self::verify_nonce();
        
        // Get cron handler
        $cron_handler = MWS_Cron_Handler::get_instance();
        
        // Set a longer time limit
        set_time_limit(120);
        
        // Get limit from request (default 10 for manual testing)
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        $limit = max(1, min(50, $limit)); // Clamp between 1-50
        
        // Log start
        global $wpdb;
        $table_name = $wpdb->prefix . 'mws_logs';
        
        $wpdb->insert($table_name, [
            'source' => 'system',
            'url' => admin_url('admin.php?page=manhwa-scraper-settings'),
            'status' => 'info',
            'message' => sprintf(__('[MANUAL] Auto-update check started (limit: %d)', 'manhwa-scraper'), $limit),
            'data' => json_encode([
                'triggered_by' => get_current_user_id(),
                'limit' => $limit,
                'timestamp' => current_time('c'),
            ]),
            'created_at' => current_time('mysql'),
        ]);
        
        // Run the update check with limit
        $stats = $cron_handler->check_for_updates($limit);
        
        wp_send_json_success([
            'message' => sprintf(
                __('Checked %d manhwa. Updated: %d, Errors: %d. Check History for details.', 'manhwa-scraper'),
                $stats['checked'],
                $stats['updated'],
                $stats['errors']
            ),
            'stats' => $stats,
        ]);
    }
    
    /**
     * Manual trigger for auto image scraper
     */
    public static function trigger_image_scraper() {
        global $wpdb;
        
        self::verify_nonce();
        
        $batch_size = isset($_POST['batch_size']) ? intval($_POST['batch_size']) : 10;
        
        if ($batch_size < 1 || $batch_size > 50) {
            wp_send_json_error(['message' => __('Invalid batch size. Must be between 1-50.', 'manhwa-scraper')]);
        }
        
        // Get scraper instance
        $scraper = MWS_Auto_Image_Scraper::get_instance();
        
        // Get stats before
        $stats_before = $scraper->get_stats();
        
        // Temporarily set batch size
        $old_batch_size = get_option('mws_image_scraper_batch_size', 10);
        update_option('mws_image_scraper_batch_size', $batch_size);
        
        // Run scraper with force=true (bypass enabled check for manual test)
        $result = $scraper->run_image_scraper(true);
        
        // Restore batch size
        update_option('mws_image_scraper_batch_size', $old_batch_size);
        
        // Get stats after
        $stats_after = $scraper->get_stats();
        
        // Get debug info
        $debug_info = [
            'total_manhwa_in_db' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'manhwa' AND post_status = 'publish'"),
            'manhwa_with_source' => $wpdb->get_var("SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type = 'manhwa' AND pm.meta_key = '_mws_source_url' AND pm.meta_value != ''"),
            'manhwa_with_chapters' => $wpdb->get_var("SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type = 'manhwa' AND pm.meta_key = '_manhwa_chapters' AND pm.meta_value != ''"),
        ];
        
        wp_send_json_success([
            'message' => sprintf(
                __('Image scraper completed! Checked: %d manhwa, Scraped: %d chapters, Found: %d images', 'manhwa-scraper'),
                $result['total_checked'],
                $result['chapters_scraped'],
                $result['images_found']
            ),
            'result' => $result,
            'stats_before' => $stats_before,
            'stats_after' => $stats_after,
            'debug' => $debug_info,
            'improvement' => [
                'completion_rate' => round($stats_after['completion_rate'] - $stats_before['completion_rate'], 2) . '%',
                'manhwa_filled' => $stats_after['manhwa_with_images'] - $stats_before['manhwa_with_images'],
            ],
        ]);
    }
    
    /**
     * Run image scraper (manual trigger from stats page)
     */
    public static function run_image_scraper() {
        // Verify nonce
        if (!check_ajax_referer('mws_ajax_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'manhwa-scraper')], 403);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'manhwa-scraper')], 403);
        }
        
        // Get scraper instance
        $scraper = MWS_Auto_Image_Scraper::get_instance();
        
        // Run scraper with force=true (bypass enabled check for manual test)
        $result = $scraper->run_image_scraper(true);
        
        wp_send_json_success([
            'message' => sprintf(
                __('Image scraper completed! Checked: %d manhwa, Scraped: %d chapters, Found: %d images', 'manhwa-scraper'),
                $result['total_checked'],
                $result['chapters_scraped'],
                $result['images_found']
            ),
            'stats' => $result,
        ]);
    }
    
    /**
     * Run auto discovery (manual trigger from settings page)
     */
    public static function run_discovery() {
        // Verify nonce
        if (!check_ajax_referer('mws_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'manhwa-scraper')], 403);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'manhwa-scraper')], 403);
        }
        
        // Increase time limit for discovery
        set_time_limit(300);
        
        // Get discovery instance
        $discovery = MWS_Auto_Discovery::get_instance();
        
        // Run discovery (force run even if not enabled)
        // Temporarily enable it for manual run
        $was_enabled = get_option('mws_auto_discovery_enabled', false);
        update_option('mws_auto_discovery_enabled', true);
        
        $stats = $discovery->run_discovery();
        
        // Restore original setting
        if (!$was_enabled) {
            update_option('mws_auto_discovery_enabled', false);
        }
        
        if ($stats) {
            wp_send_json_success([
                'message' => sprintf(
                    __('Discovery completed! Sources: %d, Found: %d, Imported: %d', 'manhwa-scraper'),
                    $stats['sources_checked'],
                    $stats['manhwa_found'],
                    $stats['new_imported']
                ),
                'stats' => $stats,
            ]);
        } else {
            wp_send_json_error(['message' => __('Discovery failed or returned no results', 'manhwa-scraper')]);
        }
    }
    
    /**
     * Get manhwa stats for auto-refresh
     */
    public static function get_manhwa_stats() {
        self::verify_nonce();
        
        $upload_dir = wp_upload_dir();
        
        $manhwa_posts = get_posts([
            'post_type' => 'manhwa',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);
        
        $stats = [
            'total' => 0, 
            'need-images' => 0, 
            'missing-external' => 0, 
            'has-external' => 0, 
            'partial-download' => 0, 
            'all-downloaded' => 0, 
            'no-chapters' => 0
        ];
        
        $manhwa_list = [];
        
        foreach ($manhwa_posts as $manhwa) {
            $chapters = get_post_meta($manhwa->ID, '_manhwa_chapters', true);
            $ch_count = is_array($chapters) ? count($chapters) : 0;
            
            // Count chapters with images and downloaded images
            $with_images = 0;
            $with_local = 0;
            
            if (is_array($chapters)) {
                foreach ($chapters as $ch) {
                    if (!empty($ch['images']) && is_array($ch['images'])) {
                        $with_images++;
                        // Check if first image is local
                        $first_img = $ch['images'][0];
                        $img_url = is_array($first_img) ? ($first_img['url'] ?? $first_img['src'] ?? '') : $first_img;
                        if (strpos($img_url, '/wp-content/uploads/manhwa/') !== false) {
                            $with_local++;
                        }
                    }
                }
            }
            
            // Determine status type for filtering
            $status_type = 'no-chapters';
            $status_icon = '⚪';
            $status_text = 'No Chapters';
            
            if ($ch_count > 0) {
                if ($with_local == $ch_count && $ch_count > 0) {
                    $status_type = 'all-downloaded';
                    $status_icon = '🟢';
                    $status_text = 'All Downloaded';
                } elseif ($with_local > 0) {
                    $status_type = 'partial-download';
                    $status_icon = '🟠';
                    $status_text = $with_local . '/' . $ch_count . ' Downloaded';
                } elseif ($with_images > 0 && $with_images < $ch_count) {
                    // Some chapters have external images, but some don't
                    $status_type = 'missing-external';
                    $status_icon = '🟣';
                    $missing = $ch_count - $with_images;
                    $status_text = $missing . ' ch missing images';
                } elseif ($with_images == $ch_count) {
                    // All chapters have external images
                    $status_type = 'has-external';
                    $status_icon = '🟡';
                    $status_text = $with_images . '/' . $ch_count . ' External';
                } else {
                    $status_type = 'need-images';
                    $status_icon = '🔴';
                    $status_text = $ch_count . ' ch - Need Images';
                }
            }
            
            $stats['total']++;
            $stats[$status_type]++;
            
            $manhwa_list[] = [
                'id' => $manhwa->ID,
                'title' => $manhwa->post_title,
                'status_type' => $status_type,
                'display' => $status_icon . ' ' . $manhwa->post_title . ' (' . $ch_count . ' ch) - ' . $status_text
            ];
        }
        
        wp_send_json_success(array_merge($stats, ['manhwa_list' => $manhwa_list]));
    }
    
    /**
     * Scan URLs for migration
     */
    public static function migrate_scan_urls() {
        self::verify_nonce();
        
        $find_pattern = isset($_POST['find_pattern']) ? sanitize_text_field($_POST['find_pattern']) : '';
        $replace_with = isset($_POST['replace_with']) ? sanitize_text_field($_POST['replace_with']) : '';
        $include_source_url = isset($_POST['include_source_url']) && $_POST['include_source_url'] == '1';
        
        if (empty($find_pattern)) {
            wp_send_json_error(['message' => __('Find pattern is required', 'manhwa-scraper')]);
            return;
        }
        
        // Get all manhwa posts
        $manhwa_posts = get_posts([
            'post_type' => 'manhwa',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);
        
        $manhwa_list = [];
        $total_chapters = 0;
        $total_source_urls = 0;
        
        foreach ($manhwa_posts as $manhwa) {
            $chapters = get_post_meta($manhwa->ID, '_manhwa_chapters', true);
            $matching_chapters = 0;
            $source_url_match = false;
            
            // Check source URL
            if ($include_source_url) {
                $source_url = get_post_meta($manhwa->ID, '_mws_source_url', true);
                if (!empty($source_url) && strpos($source_url, $find_pattern) !== false) {
                    $source_url_match = true;
                    $total_source_urls++;
                }
            }
            
            // Check chapters
            if (is_array($chapters)) {
                foreach ($chapters as $ch) {
                    $ch_url = isset($ch['url']) ? $ch['url'] : '';
                    if (!empty($ch_url) && strpos($ch_url, $find_pattern) !== false) {
                        $matching_chapters++;
                    }
                }
            }
            
            // If any match found, add to list
            if ($matching_chapters > 0 || $source_url_match) {
                $manhwa_list[] = [
                    'id' => $manhwa->ID,
                    'title' => $manhwa->post_title,
                    'chapter_count' => $matching_chapters,
                    'source_url_match' => $source_url_match
                ];
                $total_chapters += $matching_chapters;
            }
        }
        
        wp_send_json_success([
            'manhwa_list' => $manhwa_list,
            'total_manhwa' => count($manhwa_list),
            'total_chapters' => $total_chapters,
            'total_source_urls' => $total_source_urls,
            'find_pattern' => $find_pattern,
            'replace_with' => $replace_with
        ]);
    }
    
    /**
     * Execute URL migration for a single manhwa
     */
    public static function migrate_execute() {
        self::verify_nonce();
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $find_pattern = isset($_POST['find_pattern']) ? sanitize_text_field($_POST['find_pattern']) : '';
        $replace_with = isset($_POST['replace_with']) ? sanitize_text_field($_POST['replace_with']) : '';
        $include_source_url = isset($_POST['include_source_url']) && $_POST['include_source_url'] == '1';
        
        if (!$post_id || empty($find_pattern)) {
            wp_send_json_error(['message' => __('Invalid parameters', 'manhwa-scraper')]);
            return;
        }
        
        $updated_chapters = 0;
        $updated_source_url = false;
        
        // Update source URL
        if ($include_source_url) {
            $source_url = get_post_meta($post_id, '_mws_source_url', true);
            if (!empty($source_url) && strpos($source_url, $find_pattern) !== false) {
                $new_source_url = str_replace($find_pattern, $replace_with, $source_url);
                update_post_meta($post_id, '_mws_source_url', $new_source_url);
                $updated_source_url = true;
            }
        }
        
        // Update chapters
        $chapters = get_post_meta($post_id, '_manhwa_chapters', true);
        
        if (is_array($chapters) && !empty($chapters)) {
            $modified = false;
            
            foreach ($chapters as $key => $ch) {
                if (isset($ch['url']) && strpos($ch['url'], $find_pattern) !== false) {
                    $chapters[$key]['url'] = str_replace($find_pattern, $replace_with, $ch['url']);
                    $modified = true;
                    $updated_chapters++;
                }
            }
            
            if ($modified) {
                // Use delete + add to avoid serialization issues
                delete_post_meta($post_id, '_manhwa_chapters');
                add_post_meta($post_id, '_manhwa_chapters', $chapters, true);
            }
        }
        
        wp_send_json_success([
            'post_id' => $post_id,
            'updated_chapters' => $updated_chapters,
            'updated_source_url' => $updated_source_url
        ]);
    }
    
    /**
     * Stats page admin tools (reset index, clear logs)
     */
    public static function stats_admin_tools() {
        // Use a dedicated nonce for stats page tools
        if (!check_ajax_referer('mws_stats_actions', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'manhwa-scraper')], 403);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'manhwa-scraper')], 403);
        }
        
        $tool = isset($_POST['tool']) ? sanitize_text_field($_POST['tool']) : '';
        
        switch ($tool) {
            case 'reset_index':
                update_option('mws_last_update_index', 0);
                wp_send_json_success(['message' => __('Batch index reset to 0.', 'manhwa-scraper')]);
                break;
                
            case 'clear_old_logs':
                if (class_exists('MWS_Logger')) {
                    MWS_Logger::cleanup_old_logs(30);
                }
                wp_send_json_success(['message' => __('Logs older than 30 days cleared.', 'manhwa-scraper')]);
                break;
                
            default:
                wp_send_json_error(['message' => __('Unknown tool action.', 'manhwa-scraper')]);
        }
    }
}

