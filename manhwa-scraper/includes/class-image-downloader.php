<?php
/**
 * Image Downloader Class
 * Downloads images and saves to WordPress Media Library
 */

if (!defined('ABSPATH')) {
    exit;
}

class MWS_Image_Downloader {
    
    private static $instance = null;
    private $http_client;
    
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
        $this->http_client = MWS_Http_Client::get_instance();
    }
    
    /**
     * Download image and add to media library
     *
     * @param string $image_url URL of the image
     * @param string $title Title for the image
     * @param int $post_id Optional post ID to attach the image to
     * @return int|WP_Error Attachment ID or error
     */
    public function download_to_media_library($image_url, $title = '', $post_id = 0) {
        // Require media functions
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // Generate a proper filename
        $filename = $this->generate_filename($image_url, $title);
        
        // Get temp file path
        $temp_file = $this->download_to_temp($image_url);
        
        if (is_wp_error($temp_file)) {
            return $temp_file;
        }
        
        // Get file type
        $filetype = wp_check_filetype($filename, null);
        
        if (empty($filetype['type'])) {
            // Try to determine from downloaded file
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($temp_file);
            $extension = $this->mime_to_extension($mime);
            $filename = pathinfo($filename, PATHINFO_FILENAME) . '.' . $extension;
            $filetype = wp_check_filetype($filename, null);
        }
        
        // Prepare file array for sideload
        $file_array = [
            'name' => $filename,
            'tmp_name' => $temp_file,
        ];
        
        // Sideload the file
        $attachment_id = media_handle_sideload($file_array, $post_id, $title);
        
        // Clean up temp file
        @unlink($temp_file);
        
        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }
        
        // Update attachment metadata
        update_post_meta($attachment_id, '_mws_source_url', $image_url);
        update_post_meta($attachment_id, '_mws_downloaded_at', current_time('mysql'));
        
        return $attachment_id;
    }
    
    /**
     * Download image to temporary file
     *
     * @param string $image_url
     * @return string|WP_Error Path to temp file or error
     */
    private function download_to_temp($image_url) {
        // Create temp file
        $temp_file = wp_tempnam();
        
        if (!$temp_file) {
            return new WP_Error('temp_file', __('Could not create temp file', 'manhwa-scraper'));
        }
        
        // Download
        $result = $this->http_client->download_file($image_url, $temp_file);
        
        if (is_wp_error($result)) {
            @unlink($temp_file);
            return $result;
        }
        
        // Verify the file was downloaded
        if (!file_exists($temp_file) || filesize($temp_file) < 100) {
            @unlink($temp_file);
            return new WP_Error('download_failed', __('Download failed or file is empty', 'manhwa-scraper'));
        }
        
        return $temp_file;
    }
    
    /**
     * Generate a proper filename from URL and title
     *
     * @param string $url
     * @param string $title
     * @return string
     */
    private function generate_filename($url, $title) {
        // Try to get filename from URL
        $path = parse_url($url, PHP_URL_PATH);
        $basename = basename($path);
        
        // If we have a valid filename with extension
        if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $basename)) {
            return sanitize_file_name($basename);
        }
        
        // Generate from title
        if (!empty($title)) {
            $slug = sanitize_title($title);
            return $slug . '.jpg';
        }
        
        // Fallback to random name
        return 'manhwa-cover-' . wp_generate_password(8, false) . '.jpg';
    }
    
    /**
     * Convert MIME type to file extension
     *
     * @param string $mime
     * @return string
     */
    private function mime_to_extension($mime) {
        $map = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
        ];
        
        return isset($map[$mime]) ? $map[$mime] : 'jpg';
    }
    
    /**
     * Set image as featured image for a post
     *
     * @param int $post_id
     * @param int $attachment_id
     * @return bool
     */
    public function set_featured_image($post_id, $attachment_id) {
        return set_post_thumbnail($post_id, $attachment_id);
    }
    
    /**
     * Download and set as featured image in one step
     *
     * @param string $image_url
     * @param int $post_id
     * @param string $title
     * @return int|WP_Error Attachment ID or error
     */
    public function download_and_set_featured($image_url, $post_id, $title = '') {
        $attachment_id = $this->download_to_media_library($image_url, $title, $post_id);
        
        if (!is_wp_error($attachment_id)) {
            $this->set_featured_image($post_id, $attachment_id);
        }
        
        return $attachment_id;
    }
    
    /**
     * Check if image already exists in media library
     *
     * @param string $image_url
     * @return int|false Attachment ID if exists, false otherwise
     */
    public function image_exists($image_url) {
        global $wpdb;
        
        $attachment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_mws_source_url' AND meta_value = %s LIMIT 1",
            $image_url
        ));
        
        return $attachment_id ? (int) $attachment_id : false;
    }
    
    /**
     * Get or download image
     * Returns existing attachment ID if already downloaded, otherwise downloads
     *
     * @param string $image_url
     * @param string $title
     * @param int $post_id
     * @return int|WP_Error
     */
    public function get_or_download($image_url, $title = '', $post_id = 0) {
        $existing = $this->image_exists($image_url);
        
        if ($existing) {
            return $existing;
        }
        
        return $this->download_to_media_library($image_url, $title, $post_id);
    }
    
    /**
     * Download chapter images to local folder
     * Saves images to /wp-content/uploads/manhwa/chapters/{manhwa-slug}/{chapter}/
     * Uses parallel downloads for faster processing
     *
     * @param array $images Array of image URLs
     * @param string $manhwa_slug Manhwa slug for folder organization
     * @param string $chapter_number Chapter number/identifier
     * @param callable $progress_callback Optional callback for progress updates
     * @param int $concurrent Number of concurrent downloads (default 5)
     * @return array Array with local URLs and download results
     */
    public function download_chapter_images($images, $manhwa_slug, $chapter_number, $progress_callback = null, $concurrent = 5) {
        // Create upload directory
        $upload_dir = wp_upload_dir();
        $base_path = $upload_dir['basedir'] . '/manhwa/chapters/' . sanitize_file_name($manhwa_slug) . '/chapter-' . sanitize_file_name($chapter_number);
        $base_url = $upload_dir['baseurl'] . '/manhwa/chapters/' . sanitize_file_name($manhwa_slug) . '/chapter-' . sanitize_file_name($chapter_number);
        
        // Create directory if not exists
        if (!file_exists($base_path)) {
            wp_mkdir_p($base_path);
        }
        
        // Protect directory with .htaccess to prevent directory listing
        $htaccess_path = $upload_dir['basedir'] . '/manhwa/.htaccess';
        if (!file_exists($htaccess_path)) {
            file_put_contents($htaccess_path, "Options -Indexes\n");
        }
        
        $results = [
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'images' => [],
            'errors' => [],
        ];
        
        $total = count($images);
        
        // Prepare download tasks
        $download_tasks = [];
        $skipped_images = [];
        
        foreach ($images as $index => $image) {
            $image_url = is_array($image) ? ($image['url'] ?? '') : $image;
            
            if (empty($image_url)) {
                $results['skipped']++;
                continue;
            }
            
            // Clean CDN wrapper URLs (ShortPixel, etc.)
            $image_url = $this->clean_cdn_url($image_url);
            
            // Generate filename with page number
            $page_num = str_pad($index + 1, 3, '0', STR_PAD_LEFT);
            $extension = $this->get_extension_from_url($image_url);
            $filename = 'page-' . $page_num . '.' . $extension;
            $local_path = $base_path . '/' . $filename;
            $local_url = $base_url . '/' . $filename;
            
            // Check if already downloaded
            if (file_exists($local_path) && filesize($local_path) > 100) {
                $results['skipped']++;
                $skipped_images[$index] = [
                    'url' => $local_url,
                    'page' => $index + 1,
                    'local' => true,
                    'source' => $image_url,
                ];
                continue;
            }
            
            $download_tasks[] = [
                'index' => $index,
                'url' => $image_url,
                'path' => $local_path,
                'local_url' => $local_url,
            ];
        }
        
        // Download in parallel batches
        $downloaded_images = $this->parallel_download($download_tasks, $concurrent, $progress_callback, $total, $results);
        
        // Merge skipped and downloaded images, maintaining order and indices
        $all_images = $skipped_images + $downloaded_images;
        ksort($all_images);
        $results['images'] = $all_images; // Keep indices for proper merging
        
        return $results;
    }
    
    /**
     * Parallel download using curl_multi
     *
     * @param array $tasks Download tasks
     * @param int $concurrent Number of concurrent downloads
     * @param callable $progress_callback Progress callback
     * @param int $total Total images count
     * @param array &$results Results array (passed by reference)
     * @return array Downloaded images array
     */
    private function parallel_download($tasks, $concurrent, $progress_callback, $total, &$results) {
        $downloaded = [];
        $chunks = array_chunk($tasks, $concurrent);
        $completed = 0;
        
        foreach ($chunks as $chunk) {
            // Initialize curl_multi
            $multi_handle = curl_multi_init();
            $curl_handles = [];
            
            // Create curl handles for each task in chunk
            foreach ($chunk as $task) {
                $ch = curl_init();
                
                curl_setopt_array($ch, [
                    CURLOPT_URL => $task['url'],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_TIMEOUT => 60,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    CURLOPT_REFERER => parse_url($task['url'], PHP_URL_SCHEME) . '://' . parse_url($task['url'], PHP_URL_HOST) . '/',
                    CURLOPT_HTTPHEADER => [
                        'Accept: image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
                        'Accept-Language: en-US,en;q=0.9',
                    ],
                ]);
                
                curl_multi_add_handle($multi_handle, $ch);
                $curl_handles[(int)$ch] = [
                    'handle' => $ch,
                    'task' => $task,
                ];
            }
            
            // Execute all handles
            $running = null;
            do {
                $status = curl_multi_exec($multi_handle, $running);
                if ($running > 0) {
                    curl_multi_select($multi_handle);
                }
            } while ($running > 0 && $status === CURLM_OK);
            
            // Process results
            foreach ($curl_handles as $info) {
                $ch = $info['handle'];
                $task = $info['task'];
                
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $content = curl_multi_getcontent($ch);
                $error = curl_error($ch);
                
                $completed++;
                
                if ($http_code === 200 && !empty($content) && strlen($content) > 100) {
                    // Save to file
                    $saved = file_put_contents($task['path'], $content);
                    
                    if ($saved !== false && $saved > 100) {
                        $results['success']++;
                        $downloaded[$task['index']] = [
                            'url' => $task['local_url'],
                            'page' => $task['index'] + 1,
                            'local' => true,
                            'source' => $task['url'],
                        ];
                    } else {
                        $results['failed']++;
                        $results['errors'][] = "Failed to save page " . ($task['index'] + 1);
                        // DON'T add external URL to downloaded array - keep existing URL in database
                    }
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Failed page " . ($task['index'] + 1) . ": HTTP $http_code" . ($error ? " - $error" : "");
                    // DON'T add external URL to downloaded array - keep existing URL in database
                }
                
                curl_multi_remove_handle($multi_handle, $ch);
                curl_close($ch);
            }
            
            curl_multi_close($multi_handle);
            
            // Progress callback after each batch
            if (is_callable($progress_callback)) {
                $progress_callback($completed + $results['skipped'], $total, $results);
            }
        }
        
        return $downloaded;
    }
    
    /**
     * Download single image to specified path
     *
     * @param string $url Image URL
     * @param string $path Local file path
     * @return bool Success
     */
    private function download_image_to_path($url, $path) {
        // Use WordPress HTTP API
        $response = wp_remote_get($url, [
            'timeout' => 60,
            'sslverify' => false,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Referer' => parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST) . '/',
            ],
        ]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $code = wp_remote_retrieve_response_code($response);
        
        if ($code !== 200 || empty($body)) {
            return false;
        }
        
        // Save to file
        $saved = file_put_contents($path, $body);
        
        return $saved !== false && $saved > 100;
    }
    
    /**
     * Get file extension from URL
     *
     * @param string $url
     * @return string
     */
    private function get_extension_from_url($url) {
        $path = parse_url($url, PHP_URL_PATH);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        // Validate extension
        $valid_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($extension, $valid_extensions)) {
            return $extension;
        }
        
        // Default to jpg
        return 'jpg';
    }
    
    /**
     * Get local chapter images path info
     *
     * @param string $manhwa_slug
     * @param string $chapter_number
     * @return array
     */
    public function get_chapter_path_info($manhwa_slug, $chapter_number) {
        $upload_dir = wp_upload_dir();
        $base_path = $upload_dir['basedir'] . '/manhwa/chapters/' . sanitize_file_name($manhwa_slug) . '/chapter-' . sanitize_file_name($chapter_number);
        $base_url = $upload_dir['baseurl'] . '/manhwa/chapters/' . sanitize_file_name($manhwa_slug) . '/chapter-' . sanitize_file_name($chapter_number);
        
        return [
            'path' => $base_path,
            'url' => $base_url,
            'exists' => file_exists($base_path),
            'files' => file_exists($base_path) ? glob($base_path . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE) : [],
        ];
    }
    
    /**
     * Delete local chapter images
     *
     * @param string $manhwa_slug
     * @param string $chapter_number
     * @return bool
     */
    public function delete_chapter_images($manhwa_slug, $chapter_number) {
        $path_info = $this->get_chapter_path_info($manhwa_slug, $chapter_number);
        
        if (!$path_info['exists']) {
            return true;
        }
        
        // Delete all files in the directory
        foreach ($path_info['files'] as $file) {
            @unlink($file);
        }
        
        // Remove directory
        @rmdir($path_info['path']);
        
        return true;
    }
    
    /**
     * Clean CDN wrapper URLs to get the original image URL
     * Handles: ShortPixel, WordPress CDN, Cloudinary, imgix, weserv.nl
     *
     * @param string $url Image URL potentially wrapped by CDN
     * @return string Original image URL
     */
    private function clean_cdn_url($url) {
        if (empty($url)) {
            return '';
        }
        
        // ShortPixel CDN wrapper
        // Example: https://sp-ao.shortpixel.ai/client/to_webp,q_glossy,ret_img/https://example.com/image.png
        if (strpos($url, 'shortpixel.ai/') !== false) {
            // Find the embedded real URL after the CDN path
            if (preg_match('~shortpixel\.ai/[^/]+/[^/]+/(https?://.+)$~i', $url, $matches)) {
                return $matches[1];
            }
            // Alternative pattern: look for /http in the URL
            if (preg_match('~/(https?://(?!.*shortpixel\.ai).+)$~i', $url, $matches)) {
                return $matches[1];
            }
        }
        
        // i0.wp.com, i1.wp.com, i2.wp.com (WordPress.com CDN / Jetpack Photon)
        // Example: https://i0.wp.com/example.com/image.png?resize=300,200
        if (preg_match('~^https?://i\d\.wp\.com/(.+)$~i', $url, $matches)) {
            $cleaned = 'https://' . explode('?', $matches[1])[0];
            return $cleaned;
        }
        
        // Cloudinary CDN fetch
        // Example: https://res.cloudinary.com/xxx/image/fetch/https://example.com/image.png
        if (strpos($url, 'cloudinary.com/') !== false && strpos($url, '/fetch/') !== false) {
            if (preg_match('~/fetch/(https?://.+)$~i', $url, $matches)) {
                return $matches[1];
            }
        }
        
        // imgix CDN
        // Example: https://xxx.imgix.net/https://example.com/image.png
        if (strpos($url, '.imgix.net/') !== false) {
            if (preg_match('~\.imgix\.net/(https?://.+)$~i', $url, $matches)) {
                return explode('?', $matches[1])[0];
            }
        }
        
        // weserv.nl image proxy
        // Example: https://images.weserv.nl/?url=https://example.com/image.png
        if (strpos($url, 'weserv.nl/') !== false) {
            if (preg_match('~[?&]url=(https?://[^&]+)~i', $url, $matches)) {
                return urldecode($matches[1]);
            }
            if (preg_match('~[?&]url=([^&]+)~i', $url, $matches)) {
                $decoded = urldecode($matches[1]);
                if (strpos($decoded, 'http') === 0) {
                    return $decoded;
                }
                return 'https://' . $decoded;
            }
        }
        
        // Generic: look for embedded URLs in query params
        if (preg_match('~[?&](?:url|src|image)=(https?[^&]+)~i', $url, $matches)) {
            return urldecode($matches[1]);
        }
        
        return $url;
    }
}
