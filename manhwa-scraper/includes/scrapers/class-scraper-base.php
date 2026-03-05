<?php
/**
 * Abstract Scraper Base Class
 * All source scrapers must extend this class
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class MWS_Scraper_Base {
    
    protected $http_client;
    protected $parser;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->http_client = MWS_Http_Client::get_instance();
    }
    
    /**
     * Get source identifier
     *
     * @return string
     */
    abstract public function get_source_id();
    
    /**
     * Get source display name
     *
     * @return string
     */
    abstract public function get_source_name();
    
    /**
     * Get source base URL
     *
     * @return string
     */
    abstract public function get_source_url();
    
    /**
     * Check if this scraper can handle the given URL
     *
     * @param string $url
     * @return bool
     */
    abstract public function can_handle_url($url);
    
    /**
     * Scrape single manhwa from URL
     *
     * @param string $url
     * @return array|WP_Error
     */
    abstract public function scrape_single($url);
    
    /**
     * Scrape manhwa list from page
     *
     * @param int $page Page number
     * @return array|WP_Error Array of manhwa data
     */
    abstract public function scrape_list($page = 1);
    
    /**
     * Get latest chapter info for a manhwa
     *
     * @param string $url Manhwa URL
     * @return array|WP_Error
     */
    abstract public function get_latest_chapter($url);
    
    /**
     * Get popular manhwa list
     * Override in child class for source-specific implementation
     *
     * @param int $page Page number
     * @return array|WP_Error Array of manhwa data
     */
    public function get_popular($page = 1) {
        // Default: use the list scraper with ?order=popular parameter
        // Child classes should override this for source-specific URLs
        return $this->scrape_list($page);
    }
    
    /**
     * Get latest updated manhwa list
     * Override in child class for source-specific implementation
     *
     * @param int $page Page number
     * @return array|WP_Error Array of manhwa data
     */
    public function get_latest($page = 1) {
        // Default: use the list scraper (usually returns latest by default)
        // Child classes should override this for source-specific URLs
        return $this->scrape_list($page);
    }
    
    /**
     * Fetch and parse HTML from URL
     *
     * @param string $url
     * @return MWS_Html_Parser|WP_Error
     */
    protected function fetch_and_parse($url) {
        $html = $this->http_client->get($url);
        
        if (is_wp_error($html)) {
            return $html;
        }
        
        $parser = new MWS_Html_Parser($html);
        return $parser;
    }
    
    /**
     * Parse HTML string (for pre-fetched content)
     *
     * @param string $html
     * @return MWS_Html_Parser
     */
    protected function parse_html($html) {
        return new MWS_Html_Parser($html);
    }
    
    /**
     * Scrape multiple URLs in parallel and return parsed data
     * Override this in child class to implement parallel scraping
     *
     * @param array $urls
     * @return array Array of scraped data keyed by URL
     */
    public function scrape_multiple($urls) {
        // Fetch all HTML in parallel
        $html_results = $this->http_client->get_parallel($urls, 5);
        
        $results = [];
        foreach ($html_results as $url => $html) {
            if (is_wp_error($html)) {
                $results[$url] = $html;
                continue;
            }
            
            // Parse the HTML (child classes should override scrape_single_from_html)
            $data = $this->scrape_single_from_html($url, $html);
            $results[$url] = $data;
        }
        
        return $results;
    }
    
    /**
     * Scrape single manhwa from pre-fetched HTML
     * Override this in child classes to avoid re-fetching
     *
     * @param string $url
     * @param string $html
     * @return array|WP_Error
     */
    public function scrape_single_from_html($url, $html) {
        // Default: just use the regular scrape_single (which re-fetches)
        // Child classes should override this to parse from $html directly
        return $this->scrape_single($url);
    }
    
    /**
     * Normalize URL (ensure absolute URL)
     *
     * @param string $url
     * @param string $base_url
     * @return string
     */
    protected function normalize_url($url, $base_url = '') {
        if (empty($url)) {
            return '';
        }
        
        // First, clean CDN wrapper URLs
        $url = $this->clean_image_url($url);
        
        // Already absolute
        if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
            return $url;
        }
        
        // Protocol-relative
        if (strpos($url, '//') === 0) {
            return 'https:' . $url;
        }
        
        // Use provided base or source URL
        $base = !empty($base_url) ? $base_url : $this->get_source_url();
        $base = rtrim($base, '/');
        
        // Root-relative
        if (strpos($url, '/') === 0) {
            $parsed = parse_url($base);
            return $parsed['scheme'] . '://' . $parsed['host'] . $url;
        }
        
        // Relative
        return $base . '/' . $url;
    }
    
    /**
     * Clean image URL from CDN wrappers
     * Extracts the original image URL from CDN proxy services
     *
     * @param string $url
     * @return string The cleaned original URL
     */
    protected function clean_image_url($url) {
        if (empty($url)) {
            return '';
        }
        
        // ShortPixel CDN wrapper
        // Example: https://sp-ao.shortpixel.ai/client/to_webp,q_glossy,ret_img/https://example.com/image.png
        if (strpos($url, 'shortpixel.ai/') !== false) {
            // Extract URL after the last /http
            if (preg_match('~/(?:https?://[^/]+/[^/]+/)(https?://.+)$~i', $url, $matches)) {
                return $matches[1];
            }
            // Alternative: find the embedded http:// or https:// URL
            if (preg_match('~/(https?://(?!sp-ao\.shortpixel\.ai).+)$~i', $url, $matches)) {
                return $matches[1];
            }
        }
        
        // i0.wp.com, i1.wp.com, i2.wp.com (WordPress.com CDN / Jetpack Photon)
        // Example: https://i0.wp.com/example.com/image.png?resize=300,200
        if (preg_match('~^https?://i\d\.wp\.com/(.+)$~i', $url, $matches)) {
            $cleaned = 'https://' . explode('?', $matches[1])[0];
            return $cleaned;
        }
        
        // Cloudinary CDN
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
        
        // StaticFlickr or other CDNs with embedded URLs
        // Generic pattern: look for embedded http/https URLs in query params
        if (preg_match('~[?&](?:url|src|image)=(https?[^&]+)~i', $url, $matches)) {
            return urldecode($matches[1]);
        }
        
        return $url;
    }
    
    /**
     * Clean text content
     *
     * @param string $text
     * @return string
     */
    protected function clean_text($text) {
        if (empty($text)) {
            return '';
        }
        
        // Remove extra whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Trim
        $text = trim($text);
        
        return $text;
    }
    
    /**
     * Extract slug from URL
     *
     * @param string $url
     * @return string
     */
    protected function extract_slug($url) {
        $path = parse_url($url, PHP_URL_PATH);
        $path = trim($path, '/');
        
        // Get last segment
        $segments = explode('/', $path);
        return end($segments);
    }
    
    /**
     * Parse chapter number from title
     *
     * @param string $title
     * @return float|null
     */
    protected function parse_chapter_number($title) {
        // Match patterns like "Chapter 123", "Ch. 45.5", etc.
        if (preg_match('/(?:chapter|ch\.?)\s*(\d+(?:\.\d+)?)/i', $title, $matches)) {
            return (float) $matches[1];
        }
        
        // Try just numbers
        if (preg_match('/^(\d+(?:\.\d+)?)/', $title, $matches)) {
            return (float) $matches[1];
        }
        
        return null;
    }
    
    /**
     * Parse date from various formats
     *
     * @param string $date_string
     * @return string|null ISO date or null
     */
    protected function parse_date($date_string) {
        if (empty($date_string)) {
            return null;
        }
        
        $date_string = trim($date_string);
        
        // Try relative dates
        $relative_patterns = [
            '/(\d+)\s*(?:second|sec|detik)/i' => '-$1 seconds',
            '/(\d+)\s*(?:minute|min|menit)/i' => '-$1 minutes',
            '/(\d+)\s*(?:hour|jam)/i' => '-$1 hours',
            '/(\d+)\s*(?:day|hari)/i' => '-$1 days',
            '/(\d+)\s*(?:week|minggu)/i' => '-$1 weeks',
            '/(\d+)\s*(?:month|bulan)/i' => '-$1 months',
            '/(\d+)\s*(?:year|tahun)/i' => '-$1 years',
        ];
        
        foreach ($relative_patterns as $pattern => $replacement) {
            if (preg_match($pattern, $date_string, $matches)) {
                $relative = str_replace('$1', $matches[1], $replacement);
                $timestamp = strtotime($relative);
                if ($timestamp) {
                    return date('Y-m-d', $timestamp);
                }
            }
        }
        
        // Try direct parsing
        $timestamp = strtotime($date_string);
        if ($timestamp) {
            return date('Y-m-d', $timestamp);
        }
        
        return null;
    }
    
    /**
     * Normalize status
     *
     * @param string $status
     * @return string
     */
    protected function normalize_status($status) {
        $status = strtolower(trim($status));
        
        $ongoing = ['ongoing', 'berjalan', 'berlangsung', 'publishing'];
        $completed = ['completed', 'tamat', 'selesai', 'end', 'finished'];
        $hiatus = ['hiatus', 'discontinued', 'dropped'];
        
        if (in_array($status, $ongoing)) {
            return 'ongoing';
        }
        if (in_array($status, $completed)) {
            return 'completed';
        }
        if (in_array($status, $hiatus)) {
            return 'hiatus';
        }
        
        return 'ongoing';
    }
    
    /**
     * Normalize type value
     *
     * @param string $type
     * @return string
     */
    protected function normalize_type($type) {
        $type = strtolower(trim($type));
        
        // Remove any HTML entities or extra characters
        $type = html_entity_decode($type);
        $type = preg_replace('/[^a-z]/', '', $type);
        
        $manhwa = ['manhwa', 'korea', 'korean', 'webtoon'];
        $manga = ['manga', 'japan', 'japanese'];
        $manhua = ['manhua', 'china', 'chinese'];
        
        if (in_array($type, $manhwa)) {
            return 'Manhwa';
        }
        if (in_array($type, $manga)) {
            return 'Manga';
        }
        if (in_array($type, $manhua)) {
            return 'Manhua';
        }
        
        // Default to Manhwa for manhwaindo source
        return 'Manhwa';
    }
    
    /**
     * Get source info
     *
     * @return array
     */
    public function get_info() {
        return [
            'id' => $this->get_source_id(),
            'name' => $this->get_source_name(),
            'url' => $this->get_source_url(),
        ];
    }
    
    /**
     * Test connection to source
     *
     * @return array
     */
    public function test_connection() {
        return $this->http_client->test_connection($this->get_source_url());
    }
    
    /**
     * Log scrape operation
     *
     * @param string $url
     * @param string $status
     * @param string $message
     * @param array $data
     * @param string $type Operation type (scrape, download, update, import, auto)
     * @param int $duration_ms Duration in milliseconds
     */
    protected function log($url, $status, $message = '', $data = [], $type = 'scrape', $duration_ms = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mws_logs';
        
        $wpdb->insert($table_name, [
            'source' => $this->get_source_id(),
            'url' => $url,
            'status' => $status,
            'type' => $type,
            'message' => $message,
            'data' => !empty($data) ? json_encode($data) : null,
            'duration_ms' => intval($duration_ms),
            'created_at' => current_time('mysql'),
        ]);
    }
}
