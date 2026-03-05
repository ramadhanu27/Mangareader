<?php
/**
 * HTTP Client Class
 * Handles HTTP requests with rate limiting, user agent rotation, and proxy support
 */

if (!defined('ABSPATH')) {
    exit;
}

class MWS_Http_Client {
    
    private static $instance = null;
    private $rate_limiter;
    private $ua_rotator;
    private $timeout = 30;
    private $last_error = '';
    private $proxy_enabled = false;
    private $proxy_host = '';
    private $proxy_port = '';
    private $proxy_username = '';
    private $proxy_password = '';
    
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
        $this->rate_limiter = MWS_Rate_Limiter::get_instance();
        $this->ua_rotator = MWS_User_Agent_Rotator::get_instance();
        $this->load_proxy_settings();
    }
    
    /**
     * Load proxy settings from WordPress options
     */
    private function load_proxy_settings() {
        $this->proxy_enabled = get_option('mws_proxy_enabled', false);
        $this->proxy_host = get_option('mws_proxy_host', '');
        $this->proxy_port = get_option('mws_proxy_port', '');
        $this->proxy_username = get_option('mws_proxy_username', '');
        $this->proxy_password = get_option('mws_proxy_password', '');
    }
    
    /**
     * Get proxy configuration for wp_remote requests
     * Always loads fresh settings to ensure changes are picked up
     */
    private function get_proxy_args() {
        // Always reload settings to get latest values
        $proxy_enabled = get_option('mws_proxy_enabled', false);
        $proxy_host = get_option('mws_proxy_host', '');
        $proxy_port = get_option('mws_proxy_port', '');
        $proxy_username = get_option('mws_proxy_username', '');
        $proxy_password = get_option('mws_proxy_password', '');
        
        if (!$proxy_enabled || empty($proxy_host)) {
            error_log('[MWS Proxy] Proxy disabled or no host configured');
            return [];
        }
        
        $proxy_url = $proxy_host;
        if (!empty($proxy_port)) {
            $proxy_url .= ':' . $proxy_port;
        }
        
        // Add authentication if provided
        if (!empty($proxy_username)) {
            $auth = $proxy_username;
            if (!empty($proxy_password)) {
                $auth .= ':' . $proxy_password;
            }
            $proxy_url = $auth . '@' . $proxy_url;
        }
        
        error_log('[MWS Proxy] Using proxy: ' . $proxy_host . ':' . $proxy_port);
        
        return [
            'proxy' => $proxy_url,
        ];
    }
    
    /**
     * Check if proxy is enabled
     */
    public function is_proxy_enabled() {
        return $this->proxy_enabled && !empty($this->proxy_host);
    }
    
    /**
     * Make GET request with retry support
     *
     * @param string $url
     * @param array $headers
     * @param int $max_retries
     * @return string|WP_Error
     */
    public function get($url, $headers = [], $max_retries = 3) {
        // Extract domain for rate limiting
        $domain = parse_url($url, PHP_URL_HOST);
        
        $last_error = null;
        
        for ($attempt = 1; $attempt <= $max_retries; $attempt++) {
            // Wait if rate limited (with exponential backoff on retries)
            if ($attempt > 1) {
                $wait_seconds = pow(2, $attempt); // 2, 4, 8 seconds
                sleep($wait_seconds);
            }
            $this->rate_limiter->wait_if_needed($domain);
            
            // Get fresh user agent for each attempt
            $user_agent = $this->ua_rotator->get_with_rotation();
            
            // Prepare headers - more realistic browser headers
            $default_headers = [
                'User-Agent' => $user_agent,
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9,id;q=0.8',
                'Accept-Encoding' => 'gzip, deflate',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
                'Cache-Control' => 'max-age=0',
                'Sec-Fetch-Dest' => 'document',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Site' => 'same-origin',
                'Sec-Fetch-User' => '?1',
                'sec-ch-ua' => '"Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
                'sec-ch-ua-mobile' => '?0',
                'sec-ch-ua-platform' => '"Windows"',
            ];
            
            $headers = array_merge($default_headers, $headers);
            
            // Add referer for the same domain
            if (!isset($headers['Referer'])) {
                $headers['Referer'] = 'https://' . $domain . '/';
            }
            
            // Make request using WordPress HTTP API
            $request_args = array_merge([
                'timeout' => $this->timeout,
                'headers' => $headers,
                'sslverify' => false,
                'redirection' => 5,
            ], $this->get_proxy_args());
            
            $response = wp_remote_get($url, $request_args);
            
            if (is_wp_error($response)) {
                $last_error = $response;
                continue; // Retry
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            
            // Success
            if ($status_code === 200) {
                $body = wp_remote_retrieve_body($response);
                
                // Handle gzip encoding if needed
                if (substr($body, 0, 2) === "\x1f\x8b") {
                    $body = gzdecode($body);
                }
                
                // Check if response is actually blocked/captcha page
                if ($this->is_blocked_response($body)) {
                    $last_error = new WP_Error('blocked', 'Response appears to be a captcha or block page');
                    continue; // Retry
                }
                
                return $body;
            }
            
            // Rate limited - definite retry
            if ($status_code === 429 || $status_code === 503) {
                $last_error = new WP_Error('rate_limited', "HTTP $status_code - Rate limited, retrying...");
                continue;
            }
            
            // Other errors
            $last_error = new WP_Error('http_error', "HTTP Error: $status_code");
            
            // Don't retry on permanent errors
            if ($status_code >= 400 && $status_code < 500 && $status_code !== 429) {
                break;
            }
        }
        
        $this->last_error = is_wp_error($last_error) ? $last_error->get_error_message() : 'Unknown error';
        return $last_error;
    }
    
    /**
     * Check if response body indicates blocking/captcha
     *
     * @param string $body
     * @return bool
     */
    private function is_blocked_response($body) {
        $blocked_indicators = [
            'cf-browser-verification',
            'challenge-running',
            'Checking your browser',
            'Please Wait... | Cloudflare',
            'Access denied',
            'blocked',
            'captcha',
            'recaptcha',
        ];
        
        $body_lower = strtolower($body);
        foreach ($blocked_indicators as $indicator) {
            if (strpos($body_lower, strtolower($indicator)) !== false) {
                // Additional check - make sure it's not actual manga content
                if (strpos($body_lower, 'readerarea') === false && strpos($body_lower, 'chapter') === false) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Make POST request
     *
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return string|WP_Error
     */
    public function post($url, $data = [], $headers = []) {
        // Extract domain for rate limiting
        $domain = parse_url($url, PHP_URL_HOST);
        
        // Wait if rate limited
        $this->rate_limiter->wait_if_needed($domain);
        
        // Prepare headers
        $default_headers = [
            'User-Agent' => $this->ua_rotator->get_with_rotation(),
            'Accept' => 'application/json, text/plain, */*',
            'Accept-Language' => 'en-US,en;q=0.5',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];
        
        $headers = array_merge($default_headers, $headers);
        
        // Make request
        $response = wp_remote_post($url, [
            'timeout' => $this->timeout,
            'headers' => $headers,
            'body' => $data,
            'sslverify' => false,
        ]);
        
        if (is_wp_error($response)) {
            $this->last_error = $response->get_error_message();
            return $response;
        }
        
        return wp_remote_retrieve_body($response);
    }
    
    /**
     * Download file
     *
     * @param string $url
     * @param string $destination
     * @return bool|WP_Error
     */
    public function download_file($url, $destination) {
        $domain = parse_url($url, PHP_URL_HOST);
        $this->rate_limiter->wait_if_needed($domain);
        
        $headers = [
            'User-Agent' => $this->ua_rotator->get_with_rotation(),
            'Referer' => 'https://' . $domain . '/',
        ];
        
        $response = wp_remote_get($url, [
            'timeout' => 60,
            'headers' => $headers,
            'sslverify' => false,
            'stream' => true,
            'filename' => $destination,
        ]);
        
        if (is_wp_error($response)) {
            $this->last_error = $response->get_error_message();
            return $response;
        }
        
        return true;
    }
    
    /**
     * Get last error
     *
     * @return string
     */
    public function get_last_error() {
        return $this->last_error;
    }
    
    /**
     * Set timeout
     *
     * @param int $seconds
     */
    public function set_timeout($seconds) {
        $this->timeout = max(5, (int) $seconds);
    }
    
    /**
     * Test connection to a URL
     *
     * @param string $url
     * @return array
     */
    public function test_connection($url) {
        $start = microtime(true);
        $response = $this->get($url);
        $duration = round((microtime(true) - $start) * 1000);
        
        return [
            'success' => !is_wp_error($response),
            'duration_ms' => $duration,
            'error' => is_wp_error($response) ? $response->get_error_message() : null,
            'content_length' => !is_wp_error($response) ? strlen($response) : 0,
        ];
    }
    
    /**
     * Fetch multiple URLs in parallel using curl_multi
     *
     * @param array $urls Array of URLs to fetch
     * @param int $batch_size Number of concurrent requests (default 5)
     * @return array Array of results keyed by URL
     */
    public function get_parallel($urls, $batch_size = 5) {
        if (!function_exists('curl_multi_init')) {
            // Fallback to sequential if curl_multi not available
            $results = [];
            foreach ($urls as $url) {
                $results[$url] = $this->get($url);
            }
            return $results;
        }
        
        $results = [];
        $url_chunks = array_chunk($urls, $batch_size);
        
        foreach ($url_chunks as $chunk) {
            $chunk_results = $this->fetch_batch($chunk);
            $results = array_merge($results, $chunk_results);
            
            // Small delay between batches to be nice to servers
            usleep(500000); // 0.5 seconds
        }
        
        return $results;
    }
    
    /**
     * Fetch a batch of URLs concurrently
     *
     * @param array $urls
     * @return array
     */
    private function fetch_batch($urls) {
        $multi_handle = curl_multi_init();
        $handles = [];
        $results = [];
        
        // Get shared user agent for this batch
        $user_agent = $this->ua_rotator->get_with_rotation();
        
        foreach ($urls as $url) {
            $ch = curl_init();
            
            $domain = parse_url($url, PHP_URL_HOST);
            
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_ENCODING => 'gzip, deflate',
                CURLOPT_HTTPHEADER => [
                    'User-Agent: ' . $user_agent,
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language: en-US,en;q=0.5',
                    'Connection: keep-alive',
                    'Upgrade-Insecure-Requests: 1',
                    'Referer: https://' . $domain . '/',
                ],
            ]);
            
            curl_multi_add_handle($multi_handle, $ch);
            $handles[$url] = $ch;
        }
        
        // Execute all requests
        $running = null;
        do {
            curl_multi_exec($multi_handle, $running);
            curl_multi_select($multi_handle);
        } while ($running > 0);
        
        // Collect results
        foreach ($handles as $url => $ch) {
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            if ($error) {
                $results[$url] = new WP_Error('curl_error', $error);
            } elseif ($http_code !== 200) {
                $results[$url] = new WP_Error('http_error', "HTTP Error: $http_code");
            } else {
                $results[$url] = curl_multi_getcontent($ch);
            }
            
            curl_multi_remove_handle($multi_handle, $ch);
            curl_close($ch);
        }
        
        curl_multi_close($multi_handle);
        
        return $results;
    }
}
