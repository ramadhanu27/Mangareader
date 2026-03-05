<?php
/**
 * Broken Image Fixer Class
 * Detects and fixes broken/inaccessible images in manhwa chapters
 * 
 * @package Manhwa_Scraper
 */

if (!defined('ABSPATH')) {
    exit;
}

class MWS_Broken_Image_Fixer {

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
     * Constructor
     */
    private function __construct() {
        // Register AJAX handlers
        add_action('wp_ajax_mws_scan_broken_images', [$this, 'ajax_scan_broken_images']);
        add_action('wp_ajax_mws_check_image_url', [$this, 'ajax_check_image_url']);
        add_action('wp_ajax_mws_fix_broken_chapter', [$this, 'ajax_fix_broken_chapter']);
        add_action('wp_ajax_mws_get_broken_stats', [$this, 'ajax_get_broken_stats']);
    }

    /**
     * Check if a single image URL is accessible
     * 
     * @param string $url Image URL to check
     * @param int $timeout Timeout in seconds (default 5)
     * @return array ['accessible' => bool, 'status_code' => int, 'error' => string]
     */
    public function check_image_accessible($url, $timeout = 5) {
        if (empty($url)) {
            return ['accessible' => false, 'status_code' => 0, 'error' => 'Empty URL'];
        }

        // Skip local images (already on our server)
        if (strpos($url, '/wp-content/uploads/manhwa/') !== false) {
            // Check if file exists locally
            $upload_dir = wp_upload_dir();
            $local_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $url);
            
            if (file_exists($local_path)) {
                return ['accessible' => true, 'status_code' => 200, 'error' => '', 'local' => true];
            } else {
                return ['accessible' => false, 'status_code' => 404, 'error' => 'Local file not found', 'local' => true];
            }
        }

        // Check external URL with HEAD request (faster than GET)
        $args = [
            'timeout' => $timeout,
            'redirection' => 3,
            'sslverify' => false,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'headers' => [
                'Accept' => 'image/webp,image/apng,image/*,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Referer' => parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST) . '/',
            ],
        ];

        $response = wp_remote_head($url, $args);

        if (is_wp_error($response)) {
            return [
                'accessible' => false,
                'status_code' => 0,
                'error' => $response->get_error_message()
            ];
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $content_type = wp_remote_retrieve_header($response, 'content-type');
        
        // Check if it's an image
        $is_image = strpos($content_type, 'image/') !== false;
        $is_accessible = $status_code >= 200 && $status_code < 400;

        return [
            'accessible' => $is_accessible && ($is_image || $status_code === 200),
            'status_code' => $status_code,
            'content_type' => $content_type,
            'error' => $is_accessible ? '' : "HTTP {$status_code}"
        ];
    }

    /**
     * Scan a manhwa's chapters for broken images
     * 
     * @param int $manhwa_id Post ID
     * @param int $sample_size Number of images to check per chapter (0 = all)
     * @return array Scan results
     */
    public function scan_manhwa_chapters($manhwa_id, $sample_size = 1) {
        $chapters = get_post_meta($manhwa_id, '_manhwa_chapters', true);
        
        if (!is_array($chapters) || empty($chapters)) {
            return ['error' => 'No chapters found', 'chapters' => []];
        }

        $results = [
            'manhwa_id' => $manhwa_id,
            'manhwa_title' => get_the_title($manhwa_id),
            'total_chapters' => count($chapters),
            'broken_chapters' => [],
            'healthy_chapters' => 0,
            'no_images_chapters' => 0,
            'scanned' => 0
        ];

        foreach ($chapters as $index => $chapter) {
            $chapter_num = $chapter['number'] ?? $chapter['title'] ?? ($index + 1);
            $images = $chapter['images'] ?? [];
            
            if (empty($images)) {
                $results['no_images_chapters']++;
                continue;
            }

            // Get sample images to check
            $images_to_check = [];
            if ($sample_size > 0 && count($images) > $sample_size) {
                // Check first, middle, and last images
                $images_to_check[] = $images[0];
                if (count($images) > 2) {
                    $images_to_check[] = $images[floor(count($images) / 2)];
                }
                $images_to_check[] = $images[count($images) - 1];
            } else {
                $images_to_check = array_slice($images, 0, $sample_size > 0 ? $sample_size : count($images));
            }

            $broken_count = 0;
            $broken_urls = [];

            foreach ($images_to_check as $img) {
                $img_url = is_array($img) ? ($img['url'] ?? $img['src'] ?? '') : $img;
                
                if (empty($img_url)) continue;

                $check = $this->check_image_accessible($img_url, 3);
                
                if (!$check['accessible']) {
                    $broken_count++;
                    $broken_urls[] = [
                        'url' => $img_url,
                        'error' => $check['error'],
                        'status' => $check['status_code']
                    ];
                }
            }

            $results['scanned']++;

            if ($broken_count > 0) {
                $results['broken_chapters'][] = [
                    'chapter_number' => $chapter_num,
                    'chapter_index' => $index,
                    'total_images' => count($images),
                    'broken_sample' => $broken_count,
                    'broken_urls' => $broken_urls,
                    'source_url' => $chapter['url'] ?? $chapter['source_url'] ?? ''
                ];
            } else {
                $results['healthy_chapters']++;
            }
        }

        return $results;
    }

    /**
     * Get broken image statistics for all manhwa
     * 
     * @return array Statistics
     */
    public function get_broken_stats() {
        $manhwa_posts = get_posts([
            'post_type' => 'manhwa',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ]);

        $stats = [
            'total_manhwa' => count($manhwa_posts),
            'manhwa_with_issues' => 0,
            'total_broken_chapters' => 0,
            'broken_domains' => [],
            'manhwa_list' => []
        ];

        foreach ($manhwa_posts as $manhwa_id) {
            $chapters = get_post_meta($manhwa_id, '_manhwa_chapters', true);
            
            if (!is_array($chapters)) continue;

            $has_external = false;
            $external_domains = [];

            foreach ($chapters as $chapter) {
                $images = $chapter['images'] ?? [];
                
                if (empty($images)) continue;

                // Check first image only for quick scan
                $first_img = $images[0];
                $img_url = is_array($first_img) ? ($first_img['url'] ?? $first_img['src'] ?? '') : $first_img;
                
                if (empty($img_url)) continue;

                // Check if it's external (not local)
                if (strpos($img_url, '/wp-content/uploads/manhwa/') === false) {
                    $has_external = true;
                    $domain = parse_url($img_url, PHP_URL_HOST);
                    if ($domain) {
                        $external_domains[$domain] = ($external_domains[$domain] ?? 0) + 1;
                        
                        if (!isset($stats['broken_domains'][$domain])) {
                            $stats['broken_domains'][$domain] = 0;
                        }
                        $stats['broken_domains'][$domain]++;
                    }
                }
            }

            if ($has_external) {
                $stats['manhwa_with_issues']++;
                $stats['manhwa_list'][] = [
                    'id' => $manhwa_id,
                    'title' => get_the_title($manhwa_id),
                    'chapters' => count($chapters),
                    'domains' => $external_domains
                ];
            }
        }

        // Sort domains by count
        arsort($stats['broken_domains']);

        return $stats;
    }

    /**
     * AJAX: Scan broken images for a manhwa
     */
    public function ajax_scan_broken_images() {
        check_ajax_referer('mws_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $manhwa_id = isset($_POST['manhwa_id']) ? intval($_POST['manhwa_id']) : 0;
        $sample_size = isset($_POST['sample_size']) ? intval($_POST['sample_size']) : 1;

        if (!$manhwa_id) {
            wp_send_json_error(['message' => 'Invalid manhwa ID']);
        }

        $results = $this->scan_manhwa_chapters($manhwa_id, $sample_size);

        wp_send_json_success($results);
    }

    /**
     * AJAX: Check single image URL
     */
    public function ajax_check_image_url() {
        check_ajax_referer('mws_nonce', 'nonce');

        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';

        if (empty($url)) {
            wp_send_json_error(['message' => 'Invalid URL']);
        }

        $result = $this->check_image_accessible($url);
        wp_send_json_success($result);
    }

    /**
     * AJAX: Fix broken chapter by re-scraping
     */
    public function ajax_fix_broken_chapter() {
        check_ajax_referer('mws_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $manhwa_id = isset($_POST['manhwa_id']) ? intval($_POST['manhwa_id']) : 0;
        $chapter_index = isset($_POST['chapter_index']) ? intval($_POST['chapter_index']) : -1;
        $download_local = isset($_POST['download_local']) ? filter_var($_POST['download_local'], FILTER_VALIDATE_BOOLEAN) : true;

        if (!$manhwa_id || $chapter_index < 0) {
            wp_send_json_error(['message' => 'Invalid parameters']);
        }

        $chapters = get_post_meta($manhwa_id, '_manhwa_chapters', true);
        
        if (!is_array($chapters) || !isset($chapters[$chapter_index])) {
            wp_send_json_error(['message' => 'Chapter not found']);
        }

        $chapter = $chapters[$chapter_index];
        $source_url = $chapter['url'] ?? $chapter['source_url'] ?? '';

        if (empty($source_url)) {
            wp_send_json_error(['message' => 'No source URL for this chapter. Cannot re-scrape.']);
        }

        // Get the scraper manager
        if (!class_exists('MWS_Scraper_Manager')) {
            wp_send_json_error(['message' => 'Scraper manager not loaded']);
        }

        $scraper_manager = MWS_Scraper_Manager::get_instance();
        
        // Scrape chapter images from source
        $result = $scraper_manager->scrape_chapter_images($source_url);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        if (empty($result['images'])) {
            wp_send_json_error(['message' => 'No images found from source']);
        }

        $new_images = $result['images'];

        // Download to local if requested
        if ($download_local && class_exists('MWS_Image_Downloader')) {
            $downloader = MWS_Image_Downloader::get_instance();
            $manhwa_slug = get_post_field('post_name', $manhwa_id);
            $chapter_num = $chapter['number'] ?? $chapter['title'] ?? ($chapter_index + 1);

            $download_result = $downloader->download_chapter_images(
                $new_images,
                $manhwa_slug,
                $chapter_num
            );

            if (!empty($download_result['images'])) {
                $new_images = $download_result['images'];
            }
        }

        // Update chapter with new images
        $chapters[$chapter_index]['images'] = $new_images;
        $chapters[$chapter_index]['fixed_at'] = current_time('mysql');
        
        update_post_meta($manhwa_id, '_manhwa_chapters', $chapters);

        // Update post modified date
        wp_update_post([
            'ID' => $manhwa_id,
            'post_modified' => current_time('mysql'),
            'post_modified_gmt' => current_time('mysql', 1)
        ]);

        wp_send_json_success([
            'message' => 'Chapter fixed successfully',
            'chapter_number' => $chapter['number'] ?? $chapter['title'] ?? ($chapter_index + 1),
            'images_count' => count($new_images),
            'downloaded_local' => $download_local
        ]);
    }

    /**
     * AJAX: Get broken image statistics
     */
    public function ajax_get_broken_stats() {
        check_ajax_referer('mws_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $stats = $this->get_broken_stats();
        wp_send_json_success($stats);
    }
}

// Initialize
MWS_Broken_Image_Fixer::get_instance();
