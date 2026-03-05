<?php
/**
 * Duplicate Detector Class
 * Detects duplicate manhwa before import
 */

if (!defined('ABSPATH')) {
    exit;
}

class MWS_Duplicate_Detector {
    
    private static $instance = null;
    
    /**
     * Similarity threshold for title matching (0-100)
     */
    const TITLE_SIMILARITY_THRESHOLD = 80;
    
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
     * Check if manhwa already exists
     *
     * @param array $manhwa_data Manhwa data to check
     * @return array|false Returns matching post data or false if no match
     */
    public function check_duplicate($manhwa_data) {
        $title = $manhwa_data['title'] ?? '';
        $slug = $manhwa_data['slug'] ?? '';
        $source_url = $manhwa_data['url'] ?? '';
        
        if (empty($title) && empty($slug)) {
            return false;
        }
        
        // Check by source URL first (most accurate)
        if (!empty($source_url)) {
            $match = $this->find_by_source_url($source_url);
            if ($match) {
                return [
                    'match_type' => 'source_url',
                    'confidence' => 100,
                    'post' => $match,
                ];
            }
        }
        
        // Check by exact slug
        if (!empty($slug)) {
            $match = $this->find_by_slug($slug);
            if ($match) {
                return [
                    'match_type' => 'slug',
                    'confidence' => 100,
                    'post' => $match,
                ];
            }
        }
        
        // Check by exact title
        if (!empty($title)) {
            $match = $this->find_by_exact_title($title);
            if ($match) {
                return [
                    'match_type' => 'title_exact',
                    'confidence' => 100,
                    'post' => $match,
                ];
            }
            
            // Check by similar title
            $match = $this->find_by_similar_title($title);
            if ($match) {
                return $match;
            }
        }
        
        return false;
    }
    
    /**
     * Find by source URL
     */
    private function find_by_source_url($url) {
        $args = [
            'post_type' => 'manhwa',
            'post_status' => 'any',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => '_mws_source_url',
                    'value' => $url,
                    'compare' => '=',
                ],
            ],
        ];
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            return $this->format_post_data($query->posts[0]);
        }
        
        return false;
    }
    
    /**
     * Find by slug
     */
    private function find_by_slug($slug) {
        $args = [
            'post_type' => 'manhwa',
            'post_status' => 'any',
            'name' => $slug,
            'posts_per_page' => 1,
        ];
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            return $this->format_post_data($query->posts[0]);
        }
        
        // Also check meta
        $args = [
            'post_type' => 'manhwa',
            'post_status' => 'any',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => '_mws_slug',
                    'value' => $slug,
                    'compare' => '=',
                ],
            ],
        ];
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            return $this->format_post_data($query->posts[0]);
        }
        
        return false;
    }
    
    /**
     * Find by exact title
     */
    private function find_by_exact_title($title) {
        $args = [
            'post_type' => 'manhwa',
            'post_status' => 'any',
            'title' => $title,
            'posts_per_page' => 1,
        ];
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            // Verify exact match
            if (strtolower($query->posts[0]->post_title) === strtolower($title)) {
                return $this->format_post_data($query->posts[0]);
            }
        }
        
        return false;
    }
    
    /**
     * Find by similar title
     */
    private function find_by_similar_title($title) {
        // Normalize title
        $normalized_title = $this->normalize_title($title);
        
        $args = [
            'post_type' => 'manhwa',
            'post_status' => 'any',
            'posts_per_page' => 50,
            's' => $title, // Search
        ];
        
        $query = new WP_Query($args);
        
        if (!$query->have_posts()) {
            return false;
        }
        
        $best_match = null;
        $best_similarity = 0;
        
        foreach ($query->posts as $post) {
            $post_title_normalized = $this->normalize_title($post->post_title);
            
            // Calculate similarity
            similar_text($normalized_title, $post_title_normalized, $similarity);
            
            // Also try Levenshtein if titles are similar length
            $lev_distance = levenshtein($normalized_title, $post_title_normalized);
            $max_len = max(strlen($normalized_title), strlen($post_title_normalized));
            $lev_similarity = $max_len > 0 ? (1 - ($lev_distance / $max_len)) * 100 : 0;
            
            // Use the higher similarity score
            $final_similarity = max($similarity, $lev_similarity);
            
            if ($final_similarity >= self::TITLE_SIMILARITY_THRESHOLD && $final_similarity > $best_similarity) {
                $best_similarity = $final_similarity;
                $best_match = $post;
            }
        }
        
        if ($best_match) {
            return [
                'match_type' => 'title_similar',
                'confidence' => round($best_similarity, 1),
                'post' => $this->format_post_data($best_match),
            ];
        }
        
        return false;
    }
    
    /**
     * Normalize title for comparison
     */
    private function normalize_title($title) {
        // Convert to lowercase
        $title = strtolower($title);
        
        // Remove special characters
        $title = preg_replace('/[^a-z0-9\s]/', '', $title);
        
        // Remove extra whitespace
        $title = preg_replace('/\s+/', ' ', $title);
        
        return trim($title);
    }
    
    /**
     * Format post data for response
     */
    private function format_post_data($post) {
        $thumbnail_id = get_post_thumbnail_id($post->ID);
        $thumbnail_url = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : '';
        
        return [
            'id' => $post->ID,
            'title' => $post->post_title,
            'slug' => $post->post_name,
            'status' => $post->post_status,
            'thumbnail_url' => $thumbnail_url,
            'edit_url' => get_edit_post_link($post->ID, 'raw'),
            'source_url' => get_post_meta($post->ID, '_mws_source_url', true),
            'total_chapters' => get_post_meta($post->ID, '_mws_total_chapters', true) ?: 0,
        ];
    }
    
    /**
     * Check multiple manhwa for duplicates
     *
     * @param array $manhwa_list Array of manhwa data
     * @return array Results with duplicate info
     */
    public function check_multiple($manhwa_list) {
        $results = [];
        
        foreach ($manhwa_list as $index => $manhwa) {
            $duplicate = $this->check_duplicate($manhwa);
            
            $results[] = [
                'index' => $index,
                'manhwa' => $manhwa,
                'is_duplicate' => $duplicate !== false,
                'duplicate_info' => $duplicate ?: null,
            ];
        }
        
        return $results;
    }
    
    /**
     * Update existing manhwa post
     *
     * @param int $post_id Existing post ID
     * @param array $manhwa_data New data to update
     * @return int|WP_Error Post ID or error
     */
    public function update_existing($post_id, $manhwa_data) {
        $post_data = [
            'ID' => $post_id,
        ];
        
        // Update title if provided
        if (!empty($manhwa_data['title'])) {
            $post_data['post_title'] = $manhwa_data['title'];
        }
        
        // Update content/description
        if (!empty($manhwa_data['description'])) {
            $post_data['post_content'] = $manhwa_data['description'];
        }
        
        // Update post
        $result = wp_update_post($post_data, true);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Update meta
        $meta_fields = [
            'alternative_title' => '_mws_alternative_title',
            'status' => '_mws_status',
            'type' => '_mws_type',
            'author' => '_mws_author',
            'artist' => '_mws_artist',
            'release_year' => '_mws_release_year',
            'rating' => '_mws_rating',
            'total_chapters' => '_mws_total_chapters',
            'url' => '_mws_source_url',
        ];
        
        foreach ($meta_fields as $key => $meta_key) {
            if (isset($manhwa_data[$key])) {
                update_post_meta($post_id, $meta_key, $manhwa_data[$key]);
            }
        }
        
        // Update genres if provided
        if (!empty($manhwa_data['genres']) && taxonomy_exists('manhwa_genre')) {
            wp_set_object_terms($post_id, $manhwa_data['genres'], 'manhwa_genre');
        }
        
        // Update chapters if provided
        if (!empty($manhwa_data['chapters'])) {
            update_post_meta($post_id, '_mws_chapters', $manhwa_data['chapters']);
        }
        
        return $post_id;
    }
}
