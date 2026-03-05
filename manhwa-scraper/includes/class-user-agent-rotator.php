<?php
/**
 * User Agent Rotator Class
 * Rotates user agents to avoid detection
 */

if (!defined('ABSPATH')) {
    exit;
}

class MWS_User_Agent_Rotator {
    
    private static $instance = null;
    private $user_agents = [];
    private $current_index = 0;
    
    /**
     * Default user agents
     */
    private static $defaults = [
        // Chrome Windows
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36',
        // Chrome Mac
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        // Firefox Windows
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:120.0) Gecko/20100101 Firefox/120.0',
        // Firefox Mac
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:121.0) Gecko/20100101 Firefox/121.0',
        // Safari
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15',
        // Edge
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0',
        // Mobile Chrome Android
        'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
        // Mobile Safari iOS
        'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
    ];
    
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
        $saved_agents = get_option('mws_user_agents', []);
        $this->user_agents = !empty($saved_agents) ? $saved_agents : self::$defaults;
        
        // Randomize starting index
        $this->current_index = rand(0, count($this->user_agents) - 1);
    }
    
    /**
     * Get a random user agent
     *
     * @return string
     */
    public function get_random() {
        return $this->user_agents[array_rand($this->user_agents)];
    }
    
    /**
     * Get user agent with rotation (sequential)
     *
     * @return string
     */
    public function get_with_rotation() {
        $agent = $this->user_agents[$this->current_index];
        $this->current_index = ($this->current_index + 1) % count($this->user_agents);
        return $agent;
    }
    
    /**
     * Get all user agents
     *
     * @return array
     */
    public function get_all() {
        return $this->user_agents;
    }
    
    /**
     * Set user agents
     *
     * @param array $agents
     */
    public function set_user_agents($agents) {
        if (!empty($agents) && is_array($agents)) {
            $this->user_agents = array_filter($agents);
            update_option('mws_user_agents', $this->user_agents);
        }
    }
    
    /**
     * Add a user agent
     *
     * @param string $agent
     */
    public function add_user_agent($agent) {
        if (!empty($agent) && !in_array($agent, $this->user_agents)) {
            $this->user_agents[] = $agent;
            update_option('mws_user_agents', $this->user_agents);
        }
    }
    
    /**
     * Remove a user agent
     *
     * @param string $agent
     */
    public function remove_user_agent($agent) {
        $key = array_search($agent, $this->user_agents);
        if ($key !== false) {
            unset($this->user_agents[$key]);
            $this->user_agents = array_values($this->user_agents);
            update_option('mws_user_agents', $this->user_agents);
        }
    }
    
    /**
     * Reset to defaults
     */
    public function reset_to_defaults() {
        $this->user_agents = self::$defaults;
        update_option('mws_user_agents', $this->user_agents);
    }
    
    /**
     * Get default user agents
     *
     * @return array
     */
    public static function get_defaults() {
        return self::$defaults;
    }
}
