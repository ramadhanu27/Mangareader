<?php
/**
 * REST API Endpoints for Manhwa Scraper
 * 
 * @package Manhwa_Scraper
 */

if (!defined('ABSPATH')) {
    exit;
}

class MWS_REST_API {
    
    private static $instance = null;
    private $namespace = 'manhwa/v1';
    
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
        add_action('rest_api_init', [$this, 'register_routes']);
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        
        // Get all manhwa
        register_rest_route($this->namespace, '/manhwa', [
            'methods'  => 'GET',
            'callback' => [$this, 'get_manhwa_list'],
            'permission_callback' => [$this, 'check_api_permission'],
            'args' => [
                'page' => [
                    'default' => 1,
                    'sanitize_callback' => 'absint',
                ],
                'per_page' => [
                    'default' => 20,
                    'sanitize_callback' => 'absint',
                ],
                'status' => [
                    'default' => '',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'genre' => [
                    'default' => '',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'orderby' => [
                    'default' => 'date',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'order' => [
                    'default' => 'DESC',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
        
        // Get single manhwa
        register_rest_route($this->namespace, '/manhwa/(?P<id>\d+)', [
            'methods'  => 'GET',
            'callback' => [$this, 'get_manhwa_single'],
            'permission_callback' => [$this, 'check_api_permission'],
        ]);
        
        // Get manhwa by slug
        register_rest_route($this->namespace, '/manhwa/slug/(?P<slug>[a-zA-Z0-9-]+)', [
            'methods'  => 'GET',
            'callback' => [$this, 'get_manhwa_by_slug'],
            'permission_callback' => [$this, 'check_api_permission'],
        ]);
        
        // Get chapters of manhwa
        register_rest_route($this->namespace, '/manhwa/(?P<id>\d+)/chapters', [
            'methods'  => 'GET',
            'callback' => [$this, 'get_manhwa_chapters'],
            'permission_callback' => [$this, 'check_api_permission'],
        ]);
        
        // Get single chapter
        register_rest_route($this->namespace, '/chapter/(?P<id>\d+)', [
            'methods'  => 'GET',
            'callback' => [$this, 'get_chapter'],
            'permission_callback' => [$this, 'check_api_permission'],
        ]);
        
        // Get chapter images
        register_rest_route($this->namespace, '/chapter/(?P<id>\d+)/images', [
            'methods'  => 'GET',
            'callback' => [$this, 'get_chapter_images'],
            'permission_callback' => [$this, 'check_api_permission'],
        ]);
        
        // Search manhwa
        register_rest_route($this->namespace, '/search', [
            'methods'  => 'GET',
            'callback' => [$this, 'search_manhwa'],
            'permission_callback' => [$this, 'check_api_permission'],
            'args' => [
                'q' => [
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'limit' => [
                    'default' => 10,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);
        
        // Get genres
        register_rest_route($this->namespace, '/genres', [
            'methods'  => 'GET',
            'callback' => [$this, 'get_genres'],
            'permission_callback' => [$this, 'check_api_permission'],
        ]);
        
        // Get latest updates
        register_rest_route($this->namespace, '/latest', [
            'methods'  => 'GET',
            'callback' => [$this, 'get_latest_updates'],
            'permission_callback' => [$this, 'check_api_permission'],
            'args' => [
                'limit' => [
                    'default' => 20,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);
        
        // Get popular manhwa
        register_rest_route($this->namespace, '/popular', [
            'methods'  => 'GET',
            'callback' => [$this, 'get_popular'],
            'permission_callback' => [$this, 'check_api_permission'],
            'args' => [
                'limit' => [
                    'default' => 10,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);
        
        // Get statistics
        register_rest_route($this->namespace, '/stats', [
            'methods'  => 'GET',
            'callback' => [$this, 'get_stats'],
            'permission_callback' => [$this, 'check_api_permission'],
        ]);
    }
    
    /**
     * Check API permission
     */
    public function check_api_permission($request) {
        $api_enabled = get_option('mws_api_enabled', true);
        if (!$api_enabled) {
            return new WP_Error('api_disabled', 'API is disabled', ['status' => 403]);
        }
        
        // Check API key if required
        $require_key = get_option('mws_api_require_key', false);
        if ($require_key) {
            $api_key = $request->get_header('X-API-Key');
            $valid_key = get_option('mws_api_key', '');
            
            if (empty($api_key) || $api_key !== $valid_key) {
                return new WP_Error('invalid_api_key', 'Invalid or missing API key', ['status' => 401]);
            }
        }
        
        return true;
    }
    
    /**
     * Get manhwa list
     */
    public function get_manhwa_list($request) {
        $page = $request->get_param('page');
        $per_page = min($request->get_param('per_page'), 100);
        $status = $request->get_param('status');
        $genre = $request->get_param('genre');
        $orderby = $request->get_param('orderby');
        $order = $request->get_param('order');
        
        $args = [
            'post_type' => 'manhwa',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => $orderby,
            'order' => $order,
        ];
        
        // Filter by status
        if (!empty($status)) {
            $args['meta_query'][] = [
                'key' => '_manhwa_status',
                'value' => $status,
                'compare' => '=',
            ];
        }
        
        // Filter by genre
        if (!empty($genre)) {
            $args['tax_query'][] = [
                'taxonomy' => 'genre',
                'field' => 'slug',
                'terms' => $genre,
            ];
        }
        
        $query = new WP_Query($args);
        $manhwa_list = [];
        
        foreach ($query->posts as $post) {
            $manhwa_list[] = $this->format_manhwa($post);
        }
        
        return rest_ensure_response([
            'success' => true,
            'data' => $manhwa_list,
            'meta' => [
                'total' => $query->found_posts,
                'pages' => $query->max_num_pages,
                'page' => $page,
                'per_page' => $per_page,
            ],
        ]);
    }
    
    /**
     * Get single manhwa
     */
    public function get_manhwa_single($request) {
        $id = $request->get_param('id');
        $post = get_post($id);
        
        if (!$post || $post->post_type !== 'manhwa') {
            return new WP_Error('not_found', 'Manhwa not found', ['status' => 404]);
        }
        
        return rest_ensure_response([
            'success' => true,
            'data' => $this->format_manhwa($post, true),
        ]);
    }
    
    /**
     * Get manhwa by slug
     */
    public function get_manhwa_by_slug($request) {
        $slug = $request->get_param('slug');
        
        $posts = get_posts([
            'post_type' => 'manhwa',
            'name' => $slug,
            'posts_per_page' => 1,
        ]);
        
        if (empty($posts)) {
            return new WP_Error('not_found', 'Manhwa not found', ['status' => 404]);
        }
        
        return rest_ensure_response([
            'success' => true,
            'data' => $this->format_manhwa($posts[0], true),
        ]);
    }
    
    /**
     * Get manhwa chapters
     */
    public function get_manhwa_chapters($request) {
        $manhwa_id = $request->get_param('id');
        
        // Get chapters from meta
        $chapters = get_post_meta($manhwa_id, '_manhwa_chapters', true);
        
        if (empty($chapters)) {
            $chapters = [];
        }
        
        // Format chapters
        $formatted_chapters = [];
        foreach ($chapters as $index => $chapter) {
            $formatted_chapters[] = [
                'index' => $index,
                'number' => $chapter['number'] ?? '',
                'title' => $chapter['title'] ?? '',
                'url' => $chapter['url'] ?? '',
                'date' => $chapter['date'] ?? '',
            ];
        }
        
        return rest_ensure_response([
            'success' => true,
            'data' => $formatted_chapters,
            'total' => count($formatted_chapters),
        ]);
    }
    
    /**
     * Get chapter details
     */
    public function get_chapter($request) {
        $chapter_id = $request->get_param('id');
        $post = get_post($chapter_id);
        
        if (!$post || $post->post_type !== 'chapter') {
            return new WP_Error('not_found', 'Chapter not found', ['status' => 404]);
        }
        
        $manhwa_id = get_post_meta($chapter_id, '_chapter_manhwa_id', true);
        $chapter_number = get_post_meta($chapter_id, '_chapter_number', true);
        $images = get_post_meta($chapter_id, '_chapter_images', true);
        
        return rest_ensure_response([
            'success' => true,
            'data' => [
                'id' => $post->ID,
                'title' => $post->post_title,
                'slug' => $post->post_name,
                'manhwa_id' => $manhwa_id,
                'chapter_number' => $chapter_number,
                'images' => $images ?: [],
                'date' => $post->post_date,
            ],
        ]);
    }
    
    /**
     * Get chapter images
     */
    public function get_chapter_images($request) {
        $chapter_id = $request->get_param('id');
        $images = get_post_meta($chapter_id, '_chapter_images', true);
        
        if (empty($images)) {
            return new WP_Error('not_found', 'Images not found', ['status' => 404]);
        }
        
        return rest_ensure_response([
            'success' => true,
            'data' => $images,
            'total' => count($images),
        ]);
    }
    
    /**
     * Search manhwa
     */
    public function search_manhwa($request) {
        $query = $request->get_param('q');
        $limit = min($request->get_param('limit'), 50);
        
        $posts = get_posts([
            'post_type' => 'manhwa',
            'post_status' => 'publish',
            's' => $query,
            'posts_per_page' => $limit,
        ]);
        
        $results = [];
        foreach ($posts as $post) {
            $results[] = $this->format_manhwa($post);
        }
        
        return rest_ensure_response([
            'success' => true,
            'data' => $results,
            'query' => $query,
            'total' => count($results),
        ]);
    }
    
    /**
     * Get genres
     */
    public function get_genres($request) {
        $terms = get_terms([
            'taxonomy' => 'genre',
            'hide_empty' => true,
        ]);
        
        $genres = [];
        foreach ($terms as $term) {
            $genres[] = [
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'count' => $term->count,
            ];
        }
        
        return rest_ensure_response([
            'success' => true,
            'data' => $genres,
        ]);
    }
    
    /**
     * Get latest updates
     */
    public function get_latest_updates($request) {
        $limit = min($request->get_param('limit'), 50);
        
        $posts = get_posts([
            'post_type' => 'manhwa',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'orderby' => 'modified',
            'order' => 'DESC',
        ]);
        
        $results = [];
        foreach ($posts as $post) {
            $data = $this->format_manhwa($post);
            $data['latest_chapter'] = get_post_meta($post->ID, '_mws_latest_chapter', true);
            $results[] = $data;
        }
        
        return rest_ensure_response([
            'success' => true,
            'data' => $results,
        ]);
    }
    
    /**
     * Get popular manhwa
     */
    public function get_popular($request) {
        $limit = min($request->get_param('limit'), 50);
        
        $posts = get_posts([
            'post_type' => 'manhwa',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'meta_key' => '_manhwa_views',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
        ]);
        
        $results = [];
        foreach ($posts as $post) {
            $data = $this->format_manhwa($post);
            $data['views'] = (int) get_post_meta($post->ID, '_manhwa_views', true);
            $results[] = $data;
        }
        
        return rest_ensure_response([
            'success' => true,
            'data' => $results,
        ]);
    }
    
    /**
     * Get statistics
     */
    public function get_stats($request) {
        global $wpdb;
        
        $total_manhwa = wp_count_posts('manhwa')->publish;
        $total_chapters = wp_count_posts('chapter')->publish;
        
        $genres = wp_count_terms('genre');
        
        return rest_ensure_response([
            'success' => true,
            'data' => [
                'total_manhwa' => (int) $total_manhwa,
                'total_chapters' => (int) $total_chapters,
                'total_genres' => (int) $genres,
                'last_updated' => current_time('mysql'),
            ],
        ]);
    }
    
    /**
     * Format manhwa data
     */
    private function format_manhwa($post, $full = false) {
        $thumbnail = get_the_post_thumbnail_url($post->ID, 'medium');
        
        $data = [
            'id' => $post->ID,
            'title' => $post->post_title,
            'slug' => $post->post_name,
            'url' => get_permalink($post->ID),
            'thumbnail' => $thumbnail ?: null,
            'status' => get_post_meta($post->ID, '_manhwa_status', true) ?: 'unknown',
            'type' => get_post_meta($post->ID, '_manhwa_type', true) ?: 'manhwa',
            'rating' => floatval(get_post_meta($post->ID, '_manhwa_rating', true)),
            'date' => $post->post_date,
            'modified' => $post->post_modified,
        ];
        
        // Add full details
        if ($full) {
            $data['content'] = apply_filters('the_content', $post->post_content);
            $data['excerpt'] = get_the_excerpt($post);
            $data['author'] = get_post_meta($post->ID, '_manhwa_author', true);
            $data['artist'] = get_post_meta($post->ID, '_manhwa_artist', true);
            $data['alternative_titles'] = get_post_meta($post->ID, '_manhwa_alternative', true);
            $data['views'] = (int) get_post_meta($post->ID, '_manhwa_views', true);
            $data['total_chapters'] = (int) get_post_meta($post->ID, '_mws_total_chapters', true);
            $data['latest_chapter'] = get_post_meta($post->ID, '_mws_latest_chapter', true);
            
            // Get genres
            $genres = wp_get_post_terms($post->ID, 'genre', ['fields' => 'names']);
            $data['genres'] = $genres ?: [];
            
            // Get thumbnail full size
            $data['thumbnail_full'] = get_the_post_thumbnail_url($post->ID, 'full');
        }
        
        return $data;
    }
}

// Initialize
MWS_REST_API::get_instance();
