<?php
/**
 * Scraper Manager Class
 * Registry and manager for all scrapers
 */

if (!defined('ABSPATH')) {
    exit;
}

class MWS_Scraper_Manager {
    
    private static $instance = null;
    private $scrapers = [];
    
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
        $this->register_default_scrapers();
    }
    
    /**
     * Register default scrapers
     */
    private function register_default_scrapers() {
        // Get enabled sources
        $enabled = get_option('mws_enabled_sources', ['manhwaku', 'komikcast', 'manhwaland', 'manhwaindo', 'mangasusuku']);
        
        // Register Manhwaku scraper
        if (in_array('manhwaku', $enabled)) {
            $this->register(new MWS_Manhwaku_Scraper());
        }
        
        // Register Asura scraper (file doesn't exist)
        // if (in_array('asura', $enabled)) {
        //     $this->register(new MWS_Asura_Scraper());
        // }
        
        // Register Komikcast scraper
        if (in_array('komikcast', $enabled)) {
            $this->register(new MWS_Komikcast_Scraper());
        }
        
        // Register ManhwaLand scraper
        if (in_array('manhwaland', $enabled)) {
            $this->register(new MWS_Manhwaland_Scraper());
        }
        
        // Register ManhwaIndo scraper
        if (in_array('manhwaindo', $enabled)) {
            $this->register(new MWS_Manhwaindo_Scraper());
        }
        
        // Register Mangasusuku scraper
        if (in_array('mangasusuku', $enabled)) {
            $this->register(new MWS_Mangasusuku_Scraper());
        }
        
        // Allow plugins to register additional scrapers
        do_action('mws_register_scrapers', $this);
    }
    
    /**
     * Register a scraper
     *
     * @param MWS_Scraper_Base $scraper
     */
    public function register($scraper) {
        if (!($scraper instanceof MWS_Scraper_Base)) {
            return;
        }
        
        $this->scrapers[$scraper->get_source_id()] = $scraper;
    }
    
    /**
     * Unregister a scraper
     *
     * @param string $source_id
     */
    public function unregister($source_id) {
        unset($this->scrapers[$source_id]);
    }
    
    /**
     * Get scraper by source ID
     *
     * @param string $source_id
     * @return MWS_Scraper_Base|null
     */
    public function get_scraper($source_id) {
        return isset($this->scrapers[$source_id]) ? $this->scrapers[$source_id] : null;
    }
    
    /**
     * Get scraper that can handle the given URL
     *
     * @param string $url
     * @return MWS_Scraper_Base|null
     */
    public function get_scraper_for_url($url) {
        foreach ($this->scrapers as $scraper) {
            if ($scraper->can_handle_url($url)) {
                return $scraper;
            }
        }
        
        return null;
    }
    
    /**
     * Get all registered scrapers
     *
     * @return array
     */
    public function get_all_scrapers() {
        return $this->scrapers;
    }
    
    /**
     * Get info about all scrapers
     *
     * @return array
     */
    public function get_sources_info() {
        $info = [];
        
        foreach ($this->scrapers as $scraper) {
            $info[] = $scraper->get_info();
        }
        
        return $info;
    }
    
    /**
     * Scrape single manhwa from URL
     *
     * @param string $url
     * @return array|WP_Error
     */
    public function scrape_url($url) {
        $scraper = $this->get_scraper_for_url($url);
        
        if (!$scraper) {
            return new WP_Error('no_scraper', __('No scraper found for this URL', 'manhwa-scraper'));
        }
        
        return $scraper->scrape_single($url);
    }
    
    /**
     * Scrape chapter images from URL
     *
     * @param string $url Chapter URL
     * @return array|WP_Error
     */
    public function scrape_chapter_images($url) {
        $scraper = $this->get_scraper_for_url($url);
        
        if (!$scraper) {
            return new WP_Error('no_scraper', __('No scraper found for this URL', 'manhwa-scraper'));
        }
        
        // Check if the scraper has chapter image scraping capability
        if (!method_exists($scraper, 'scrape_chapter_images')) {
            return new WP_Error('not_supported', __('This scraper does not support chapter image scraping', 'manhwa-scraper'));
        }
        
        return $scraper->scrape_chapter_images($url);
    }
    
    /**
     * Bulk scrape from a source
     *
     * @param string $source_id
     * @param int $start_page Starting page number
     * @param int $pages Number of pages to scrape
     * @param bool $scrape_details Whether to scrape full details for each
     * @return array|WP_Error
     */
    public function bulk_scrape($source_id, $start_page = 1, $pages = 1, $scrape_details = false) {
        $scraper = $this->get_scraper($source_id);
        
        if (!$scraper) {
            return new WP_Error('invalid_source', __('Invalid source ID', 'manhwa-scraper'));
        }
        
        $all_items = [];
        $errors = [];
        $urls_to_scrape = [];
        $end_page = $start_page + $pages - 1;
        
        // First, collect all items from list pages
        for ($page = $start_page; $page <= $end_page; $page++) {
            $result = $scraper->scrape_list($page);
            
            if (is_wp_error($result)) {
                $errors[] = [
                    'page' => $page,
                    'error' => $result->get_error_message(),
                ];
                continue;
            }
            
            foreach ($result['items'] as $item) {
                if ($scrape_details && !empty($item['url'])) {
                    // Collect URLs for parallel scraping
                    $urls_to_scrape[] = $item['url'];
                } else {
                    $all_items[] = $item;
                }
            }
            
            // If no items found, stop pagination
            if (empty($result['items'])) {
                break;
            }
        }
        
        // If we need to scrape details, do it in parallel
        if ($scrape_details && !empty($urls_to_scrape)) {
            $details_results = $scraper->scrape_multiple($urls_to_scrape);
            
            foreach ($details_results as $url => $details) {
                if (!is_wp_error($details)) {
                    $all_items[] = $details;
                } else {
                    // Add error item with basic info
                    $all_items[] = [
                        'url' => $url,
                        'slug' => basename(rtrim($url, '/')),
                        'error' => $details->get_error_message(),
                        'source' => $source_id,
                    ];
                }
            }
        }
        
        return [
            'source' => $source_id,
            'pages_scraped' => $pages,
            'items' => $all_items,
            'count' => count($all_items),
            'errors' => $errors,
        ];
    }
    
    /**
     * Test connection to a source
     *
     * @param string $source_id
     * @return array
     */
    public function test_connection($source_id) {
        $scraper = $this->get_scraper($source_id);
        
        if (!$scraper) {
            return [
                'success' => false,
                'error' => __('Invalid source ID', 'manhwa-scraper'),
            ];
        }
        
        return $scraper->test_connection();
    }
    
    /**
     * Test all connections
     *
     * @return array
     */
    public function test_all_connections() {
        $results = [];
        
        foreach ($this->scrapers as $id => $scraper) {
            $results[$id] = $scraper->test_connection();
            $results[$id]['name'] = $scraper->get_source_name();
        }
        
        return $results;
    }
    
    /**
     * Check if source is enabled
     *
     * @param string $source_id
     * @return bool
     */
    public function is_enabled($source_id) {
        return isset($this->scrapers[$source_id]);
    }
    
    /**
     * Get available source IDs
     *
     * @return array
     */
    public function get_available_sources() {
        return [
            'manhwaku' => 'Manhwaku.id',
            'asura' => 'Asura Scans',
            'komikcast' => 'Komikcast',
            'manhwaland' => 'ManhwaLand',
            'manhwaindo' => 'ManhwaIndo',
            'mangasusuku' => 'Mangasusuku.com',
        ];
    }
    
    /**
     * Search manhwa from source(s)
     *
     * @param string $keyword Search keyword (can be empty if filters provided)
     * @param string $source_id Optional specific source, or 'all' for all sources
     * @param array $filters Optional filters (type, status, genre, order)
     * @param int $page Page number for pagination
     * @return array|WP_Error
     */
    public function search_manhwa($keyword, $source_id = 'all', $filters = [], $page = 1) {
        // Check if we have keyword or filters
        $has_filters = !empty($filters['type']) || !empty($filters['status']) || !empty($filters['genre']);
        
        if (empty($keyword) && !$has_filters) {
            return new WP_Error('empty_search', __('Search keyword or filter is required', 'manhwa-scraper'));
        }
        
        $all_results = [];
        $errors = [];
        
        // If no keyword but has filters, use browse/filter mode
        $use_browse = empty($keyword) && $has_filters;
        
        if ($source_id === 'all') {
            // Search all enabled sources
            foreach ($this->scrapers as $id => $scraper) {
                if ($use_browse) {
                    // Use browse with filters - get single page (server-side pagination)
                    if (method_exists($scraper, 'browse_with_filters')) {
                        $result = $scraper->browse_with_filters($filters, $page);
                    } elseif (method_exists($scraper, 'get_popular')) {
                        // Get single page based on order
                        $order = !empty($filters['order']) ? $filters['order'] : 'popular';
                        if ($order === 'latest') {
                            $popular = $scraper->get_latest($page);
                        } else {
                            $popular = $scraper->get_popular($page);
                        }
                        
                        if (!is_wp_error($popular) && is_array($popular)) {
                            $result = ['results' => $popular];
                        } else {
                            $result = $popular;
                        }
                    } else {
                        continue;
                    }
                } else {
                    // Normal search
                    if (!method_exists($scraper, 'search_manhwa')) {
                        continue;
                    }
                    $result = $scraper->search_manhwa($keyword);
                }
                
                if (is_wp_error($result)) {
                    $errors[] = [
                        'source' => $id,
                        'error' => $result->get_error_message(),
                    ];
                } else {
                    $results = $result['results'] ?? $result;
                    
                    // Apply local filters if we have them
                    if (!empty($keyword) && $has_filters) {
                        $results = $this->filter_results($results, $filters);
                    }
                    
                    $all_results = array_merge($all_results, $results);
                }
            }
        } else {
            // Search specific source
            $scraper = $this->get_scraper($source_id);
            
            if (!$scraper) {
                return new WP_Error('invalid_source', __('Invalid source ID', 'manhwa-scraper'));
            }
            
            if ($use_browse) {
                // Use browse with filters - get single page (server-side pagination)
                if (method_exists($scraper, 'browse_with_filters')) {
                    $result = $scraper->browse_with_filters($filters, $page);
                } elseif (method_exists($scraper, 'get_popular')) {
                    // Get single page based on order
                    $order = !empty($filters['order']) ? $filters['order'] : 'popular';
                    if ($order === 'latest') {
                        $popular = $scraper->get_latest($page);
                    } else {
                        $popular = $scraper->get_popular($page);
                    }
                    
                    if (!is_wp_error($popular) && is_array($popular)) {
                        $result = ['results' => $popular];
                    } else {
                        $result = $popular;
                    }
                } else {
                    return new WP_Error('not_supported', __('This source does not support filter browsing', 'manhwa-scraper'));
                }
            } else {
                // Normal search
                if (!method_exists($scraper, 'search_manhwa')) {
                    return new WP_Error('not_supported', __('This source does not support search', 'manhwa-scraper'));
                }
                $result = $scraper->search_manhwa($keyword);
            }
            
            if (is_wp_error($result)) {
                return $result;
            }
            
            $all_results = $result['results'] ?? $result;
            
            // Apply local filters if we have both keyword and filters
            if (!empty($keyword) && $has_filters) {
                $all_results = $this->filter_results($all_results, $filters);
            }
        }
        
        return [
            'keyword' => $keyword ?: __('(Filter Browse)', 'manhwa-scraper'),
            'results' => $all_results,
            'count' => count($all_results),
            'errors' => $errors,
        ];
    }
    
    /**
     * Filter results locally based on filters
     * Only filter if item has the corresponding field
     * 
     * @param array $results Results to filter
     * @param array $filters Filters to apply
     * @return array Filtered results
     */
    private function filter_results($results, $filters) {
        if (empty($filters)) {
            return $results;
        }
        
        // Check if any meaningful filter is set
        $has_type_filter = !empty($filters['type']);
        $has_status_filter = !empty($filters['status']);
        $has_genre_filter = !empty($filters['genre']);
        
        // If no meaningful filters, return all results
        if (!$has_type_filter && !$has_status_filter && !$has_genre_filter) {
            return $results;
        }
        
        return array_values(array_filter($results, function($item) use ($filters, $has_type_filter, $has_status_filter, $has_genre_filter) {
            // Filter by type - only if filter is set AND item has type field
            if ($has_type_filter && isset($item['type']) && !empty($item['type'])) {
                $item_type = strtolower($item['type']);
                if (strpos($item_type, strtolower($filters['type'])) === false) {
                    return false;
                }
            }
            
            // Filter by status - only if filter is set AND item has status field
            if ($has_status_filter && isset($item['status']) && !empty($item['status'])) {
                $item_status = strtolower($item['status']);
                if (strpos($item_status, strtolower($filters['status'])) === false) {
                    return false;
                }
            }
            
            // Filter by genre - only if filter is set AND item has genres field
            if ($has_genre_filter && isset($item['genres']) && is_array($item['genres']) && !empty($item['genres'])) {
                $item_genres = array_map('strtolower', $item['genres']);
                if (!in_array(strtolower($filters['genre']), $item_genres)) {
                    return false;
                }
            }
            
            return true;
        }));
    }
}
