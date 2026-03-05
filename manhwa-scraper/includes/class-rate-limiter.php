<?php
/**
 * Rate Limiter Class
 * Prevents too many requests to source websites
 */

if (!defined('ABSPATH')) {
    exit;
}

class MWS_Rate_Limiter {
    
    private static $instance = null;
    private $requests = [];
    private $rate_limit;
    private $delay_ms;
    
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
        $this->rate_limit = (int) get_option('mws_rate_limit', 10);
        $this->delay_ms = (int) get_option('mws_delay_between_requests', 2000);
    }
    
    /**
     * Wait if rate limit is reached
     *
     * @param string $domain The domain being accessed
     * @return void
     */
    public function wait_if_needed($domain) {
        $now = microtime(true) * 1000; // milliseconds
        
        // Clean old requests (older than 1 minute)
        if (isset($this->requests[$domain])) {
            $this->requests[$domain] = array_filter(
                $this->requests[$domain],
                function($time) use ($now) {
                    return ($now - $time) < 60000; // 60 seconds
                }
            );
        } else {
            $this->requests[$domain] = [];
        }
        
        // Check if we've exceeded rate limit
        if (count($this->requests[$domain]) >= $this->rate_limit) {
            // Wait until oldest request is more than 1 minute old
            $oldest = min($this->requests[$domain]);
            $wait_time = 60000 - ($now - $oldest);
            
            if ($wait_time > 0) {
                usleep($wait_time * 1000); // Convert to microseconds
            }
        }
        
        // Check delay between requests
        if (!empty($this->requests[$domain])) {
            $last = max($this->requests[$domain]);
            $elapsed = $now - $last;
            
            if ($elapsed < $this->delay_ms) {
                usleep(($this->delay_ms - $elapsed) * 1000);
            }
        }
        
        // Record this request
        $this->requests[$domain][] = microtime(true) * 1000;
    }
    
    /**
     * Set rate limit
     *
     * @param int $limit Requests per minute
     */
    public function set_rate_limit($limit) {
        $this->rate_limit = max(1, (int) $limit);
        update_option('mws_rate_limit', $this->rate_limit);
    }
    
    /**
     * Set delay between requests
     *
     * @param int $delay_ms Delay in milliseconds
     */
    public function set_delay($delay_ms) {
        $this->delay_ms = max(100, (int) $delay_ms);
        update_option('mws_delay_between_requests', $this->delay_ms);
    }
    
    /**
     * Get current rate limit
     *
     * @return int
     */
    public function get_rate_limit() {
        return $this->rate_limit;
    }
    
    /**
     * Get current delay
     *
     * @return int
     */
    public function get_delay() {
        return $this->delay_ms;
    }
    
    /**
     * Get request count for domain
     *
     * @param string $domain
     * @return int
     */
    public function get_request_count($domain) {
        return isset($this->requests[$domain]) ? count($this->requests[$domain]) : 0;
    }
    
    /**
     * Reset request count for domain
     *
     * @param string $domain
     */
    public function reset($domain = null) {
        if ($domain) {
            unset($this->requests[$domain]);
        } else {
            $this->requests = [];
        }
    }
}
