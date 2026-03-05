<?php
/**
 * Manhwa Scraper to Manager Bridge
 * 
 * This file integrates Manhwa Scraper with Manhwa Manager
 * Automatically syncs scraped data to Manhwa Manager format
 */

if (!defined('ABSPATH')) {
    exit;
}

class MWS_Manager_Bridge {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Hook into scraper save action
        add_action('mws_manhwa_imported', [$this, 'sync_to_manager'], 10, 2);
        add_action('mws_chapter_saved', [$this, 'sync_chapter_to_manager'], 10, 3);
        
        // Add sync button to scraper pages
        add_action('admin_notices', [$this, 'add_sync_notice']);
        
        // AJAX handlers
        add_action('wp_ajax_mws_sync_to_manager', [$this, 'ajax_sync_to_manager']);
        add_action('wp_ajax_mws_bulk_sync_to_manager', [$this, 'ajax_bulk_sync_to_manager']);
    }
    
    /**
     * Sync scraped manhwa to Manager format
     */
    public function sync_to_manager($post_id, $scraped_data) {
        // Check if Manhwa Manager is active
        if (!post_type_exists('manhwa')) {
            return;
        }
        
        // Get existing manhwa or create new
        $manhwa_post = $this->get_or_create_manhwa($scraped_data);
        
        if (is_wp_error($manhwa_post)) {
            error_log('[MWS Bridge] Failed to sync: ' . $manhwa_post->get_error_message());
            return;
        }
        
        // Update manhwa metadata
        $this->update_manhwa_metadata($manhwa_post, $scraped_data);
        
        // Sync chapters
        $this->sync_chapters($manhwa_post, $scraped_data);
        
        // Store cross-reference
        update_post_meta($post_id, '_mws_manager_post_id', $manhwa_post);
        update_post_meta($manhwa_post, '_mws_scraper_post_id', $post_id);
        
        do_action('mws_synced_to_manager', $manhwa_post, $post_id, $scraped_data);
    }
    
    /**
     * Get or create manhwa post in Manager
     */
    private function get_or_create_manhwa($scraped_data) {
        $title = $scraped_data['title'] ?? '';
        
        if (empty($title)) {
            return new WP_Error('no_title', 'Manhwa title is required');
        }
        
        // Check if already exists
        $existing = get_posts([
            'post_type' => 'manhwa',
            'title' => $title,
            'post_status' => 'any',
            'numberposts' => 1,
            'fields' => 'ids'
        ]);
        
        if (!empty($existing)) {
            return $existing[0];
        }
        
        // Create new manhwa post
        $post_data = [
            'post_title' => $title,
            'post_content' => $scraped_data['synopsis'] ?? $scraped_data['description'] ?? '',
            'post_status' => 'publish',
            'post_type' => 'manhwa',
            'post_author' => get_current_user_id()
        ];
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        
        return $post_id;
    }
    
    /**
     * Update manhwa metadata
     */
    private function update_manhwa_metadata($post_id, $scraped_data) {
        // Basic metadata
        $metadata = [
            '_manhwa_type' => $scraped_data['type'] ?? 'Manhwa',
            '_manhwa_status' => $this->normalize_status($scraped_data['status'] ?? 'ongoing'),
            '_manhwa_author' => $scraped_data['author'] ?? '',
            '_manhwa_artist' => $scraped_data['artist'] ?? $scraped_data['author'] ?? '',
            '_manhwa_rating' => $scraped_data['rating'] ?? 0,
            '_manhwa_views' => $scraped_data['views'] ?? 0,
            '_manhwa_release_year' => $scraped_data['release_year'] ?? $scraped_data['released'] ?? date('Y'),
            '_manhwa_alternative_titles' => $scraped_data['alternative_titles'] ?? $scraped_data['alternative_title'] ?? [],
            '_mws_source_url' => $scraped_data['source_url'] ?? '',
            '_mws_total_chapters' => $scraped_data['total_chapters'] ?? 0,
            '_mws_latest_chapter' => $scraped_data['latest_chapter'] ?? '',
            '_mws_last_updated' => current_time('mysql')
        ];
        
        foreach ($metadata as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }
        
        // Genres/Tags
        if (!empty($scraped_data['genres'])) {
            $this->sync_genres($post_id, $scraped_data['genres']);
        }
        
        // Cover image
        if (!empty($scraped_data['cover_url'])) {
            $this->sync_cover_image($post_id, $scraped_data['cover_url']);
        }
    }
    
    /**
     * Sync chapters to Manager format
     */
    private function sync_chapters($post_id, $scraped_data) {
        if (empty($scraped_data['chapters'])) {
            return;
        }
        
        // Convert scraper format to manager format
        $manager_chapters = [];
        
        foreach ($scraped_data['chapters'] as $chapter) {
            $manager_chapters[] = [
                'title' => $chapter['title'] ?? 'Chapter ' . ($chapter['number'] ?? ''),
                'url' => $chapter['url'] ?? '',
                'date' => $chapter['date'] ?? current_time('j M Y'),
                'images' => $chapter['images'] ?? []
            ];
        }
        
        update_post_meta($post_id, '_manhwa_chapters', $manager_chapters);
    }
    
    /**
     * Sync chapter when saved
     */
    public function sync_chapter_to_manager($post_id, $chapter_data, $images) {
        $manager_post_id = get_post_meta($post_id, '_mws_manager_post_id', true);
        
        if (!$manager_post_id) {
            return;
        }
        
        // Get existing chapters
        $chapters = get_post_meta($manager_post_id, '_manhwa_chapters', true);
        if (!is_array($chapters)) {
            $chapters = [];
        }
        
        // Add or update chapter
        $chapter_title = $chapter_data['chapter_title'] ?? '';
        $found = false;
        
        foreach ($chapters as $index => $chapter) {
            if ($chapter['title'] === $chapter_title) {
                $chapters[$index]['images'] = $images;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $chapters[] = [
                'title' => $chapter_title,
                'url' => $chapter_data['chapter_url'] ?? '',
                'date' => current_time('j M Y'),
                'images' => $images
            ];
        }
        
        update_post_meta($manager_post_id, '_manhwa_chapters', $chapters);
    }
    
    /**
     * Sync genres/tags
     */
    private function sync_genres($post_id, $genres) {
        if (!is_array($genres)) {
            $genres = explode(',', $genres);
        }
        
        $genre_ids = [];
        
        foreach ($genres as $genre_name) {
            $genre_name = trim($genre_name);
            
            if (empty($genre_name)) {
                continue;
            }
            
            // Get or create genre term
            $term = get_term_by('name', $genre_name, 'genre');
            
            if (!$term) {
                $term = wp_insert_term($genre_name, 'genre');
                if (!is_wp_error($term)) {
                    $genre_ids[] = $term['term_id'];
                }
            } else {
                $genre_ids[] = $term->term_id;
            }
        }
        
        if (!empty($genre_ids)) {
            wp_set_post_terms($post_id, $genre_ids, 'genre');
        }
    }
    
    /**
     * Sync cover image
     */
    private function sync_cover_image($post_id, $cover_url) {
        // Check if already has thumbnail
        if (has_post_thumbnail($post_id)) {
            return;
        }
        
        // Download and set as featured image
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $attachment_id = media_sideload_image($cover_url, $post_id, null, 'id');
        
        if (!is_wp_error($attachment_id)) {
            set_post_thumbnail($post_id, $attachment_id);
        }
    }
    
    /**
     * Normalize status
     */
    private function normalize_status($status) {
        $status = strtolower($status);
        
        $status_map = [
            'ongoing' => 'ongoing',
            'completed' => 'completed',
            'complete' => 'completed',
            'hiatus' => 'hiatus',
            'dropped' => 'dropped',
            'cancelled' => 'dropped'
        ];
        
        return $status_map[$status] ?? 'ongoing';
    }
    
    /**
     * Add sync notice in admin
     */
    public function add_sync_notice() {
        $screen = get_current_screen();
        
        if ($screen->id !== 'manhwa-scraper_page_manhwa-scraper-import') {
            return;
        }
        
        if (!post_type_exists('manhwa')) {
            echo '<div class="notice notice-warning"><p>';
            echo '<strong>Manhwa Manager not active.</strong> Install and activate Manhwa Manager plugin to enable auto-sync.';
            echo '</p></div>';
            return;
        }
        
        echo '<div class="notice notice-info"><p>';
        echo '<strong>✓ Manhwa Manager Integration Active</strong> - Scraped data will automatically sync to Manhwa Manager.';
        echo ' <a href="#" id="mws-bulk-sync" class="button button-small">Sync All Existing</a>';
        echo '</p></div>';
        
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('#mws-bulk-sync').on('click', function(e) {
                e.preventDefault();
                
                if (!confirm('Sync all existing manhwa to Manager? This may take a while.')) {
                    return;
                }
                
                $(this).prop('disabled', true).text('Syncing...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'mws_bulk_sync_to_manager',
                        nonce: '<?php echo wp_create_nonce('mws_bulk_sync'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✓ Synced ' + response.data.synced + ' manhwa to Manager!');
                            location.reload();
                        } else {
                            alert('Error: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('AJAX error occurred');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX: Sync single manhwa
     */
    public function ajax_sync_to_manager() {
        check_ajax_referer('mws_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!$post_id) {
            wp_send_json_error(['message' => 'Invalid post ID']);
        }
        
        // Get scraped data
        $scraped_data = [
            'title' => get_the_title($post_id),
            'synopsis' => get_post_meta($post_id, '_mws_synopsis', true),
            'type' => get_post_meta($post_id, '_mws_type', true),
            'status' => get_post_meta($post_id, '_mws_status', true),
            'author' => get_post_meta($post_id, '_mws_author', true),
            'artist' => get_post_meta($post_id, '_mws_artist', true),
            'rating' => get_post_meta($post_id, '_mws_rating', true),
            'genres' => get_post_meta($post_id, '_mws_genres', true),
            'cover_url' => get_post_meta($post_id, '_mws_cover_url', true),
            'source_url' => get_post_meta($post_id, '_mws_source_url', true),
            'chapters' => get_post_meta($post_id, '_manhwa_chapters', true)
        ];
        
        $this->sync_to_manager($post_id, $scraped_data);
        
        wp_send_json_success(['message' => 'Synced to Manager']);
    }
    
    /**
     * AJAX: Bulk sync all manhwa
     */
    public function ajax_bulk_sync_to_manager() {
        check_ajax_referer('mws_bulk_sync', 'nonce');
        
        // Get all posts (assuming scraper saves to regular posts or custom type)
        $posts = get_posts([
            'post_type' => 'any',
            'posts_per_page' => -1,
            'meta_query' => [
                ['key' => '_mws_source_url', 'compare' => 'EXISTS']
            ]
        ]);
        
        $synced = 0;
        
        foreach ($posts as $post) {
            $scraped_data = [
                'title' => $post->post_title,
                'synopsis' => get_post_meta($post->ID, '_mws_synopsis', true),
                'type' => get_post_meta($post->ID, '_mws_type', true),
                'status' => get_post_meta($post->ID, '_mws_status', true),
                'author' => get_post_meta($post->ID, '_mws_author', true),
                'artist' => get_post_meta($post->ID, '_mws_artist', true),
                'rating' => get_post_meta($post->ID, '_mws_rating', true),
                'genres' => get_post_meta($post->ID, '_mws_genres', true),
                'cover_url' => get_post_meta($post->ID, '_mws_cover_url', true),
                'source_url' => get_post_meta($post->ID, '_mws_source_url', true),
                'chapters' => get_post_meta($post->ID, '_manhwa_chapters', true)
            ];
            
            $this->sync_to_manager($post->ID, $scraped_data);
            $synced++;
        }
        
        wp_send_json_success([
            'synced' => $synced,
            'message' => "Synced $synced manhwa to Manager"
        ]);
    }
}

// Initialize bridge
function mws_manager_bridge_init() {
    // Only initialize if both plugins are active
    if (class_exists('Manhwa_Manager') && class_exists('Manhwa_Scraper')) {
        return MWS_Manager_Bridge::get_instance();
    }
}
add_action('plugins_loaded', 'mws_manager_bridge_init');
