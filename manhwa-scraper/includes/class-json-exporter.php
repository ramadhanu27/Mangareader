<?php
/**
 * JSON Exporter Class
 * Exports scraped data to JSON format compatible with Manhwa Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

class MWS_Json_Exporter {
    
    /**
     * Export to Standard format (Manhwa Manager compatible)
     *
     * @param array $manhwa_data Array of manhwa data
     * @return string JSON string
     */
    public static function export_standard($manhwa_data) {
        $export = [
            'export_date' => current_time('Y-m-d H:i:s'),
            'version' => '1.0',
            'source' => 'Manhwa Metadata Scraper',
            'manhwa' => [],
        ];
        
        foreach ($manhwa_data as $manhwa) {
            $export['manhwa'][] = self::normalize_to_standard($manhwa);
        }
        
        return json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * Export to Komiku format (array only)
     *
     * @param array $manhwa_data
     * @return string JSON string
     */
    public static function export_komiku($manhwa_data) {
        $export = [];
        
        foreach ($manhwa_data as $manhwa) {
            $export[] = self::normalize_to_komiku($manhwa);
        }
        
        return json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * Export single manhwa
     *
     * @param array $manhwa
     * @param string $format 'standard' or 'komiku'
     * @return string JSON string
     */
    public static function export_single($manhwa, $format = 'standard') {
        if ($format === 'komiku') {
            $data = self::normalize_to_komiku($manhwa);
        } else {
            $data = self::normalize_to_standard($manhwa);
        }
        
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * Normalize data to standard format
     *
     * @param array $manhwa
     * @return array
     */
    private static function normalize_to_standard($manhwa) {
        return [
            'title' => $manhwa['title'] ?? '',
            'description' => $manhwa['description'] ?? $manhwa['synopsis'] ?? '',
            'excerpt' => self::generate_excerpt($manhwa['description'] ?? $manhwa['synopsis'] ?? ''),
            'slug' => $manhwa['slug'] ?? sanitize_title($manhwa['title'] ?? ''),
            'author' => $manhwa['author'] ?? '',
            'artist' => $manhwa['artist'] ?? '',
            'genres' => $manhwa['genres'] ?? [],
            'status' => strtolower($manhwa['status'] ?? 'ongoing'),
            'type' => $manhwa['type'] ?? 'Manhwa',
            'rating' => isset($manhwa['rating']) ? (float) $manhwa['rating'] : null,
            'views' => isset($manhwa['views']) ? (int) $manhwa['views'] : 0,
            'release_year' => isset($manhwa['release_year']) ? (int) $manhwa['release_year'] : null,
            'thumbnail_url' => $manhwa['thumbnail_url'] ?? $manhwa['image'] ?? $manhwa['cover'] ?? '',
            'source_url' => $manhwa['source_url'] ?? $manhwa['url'] ?? '',
            'source' => $manhwa['source'] ?? '',
            'chapters' => self::normalize_chapters($manhwa['chapters'] ?? []),
            'latest_chapter' => $manhwa['latest_chapter'] ?? null,
            'total_chapters' => isset($manhwa['total_chapters']) ? (int) $manhwa['total_chapters'] : count($manhwa['chapters'] ?? []),
            'scraped_at' => $manhwa['scraped_at'] ?? current_time('c'),
        ];
    }
    
    /**
     * Normalize data to Komiku format
     *
     * @param array $manhwa
     * @return array
     */
    private static function normalize_to_komiku($manhwa) {
        return [
            'slug' => $manhwa['slug'] ?? sanitize_title($manhwa['title'] ?? ''),
            'title' => $manhwa['title'] ?? '',
            'image' => $manhwa['thumbnail_url'] ?? $manhwa['image'] ?? $manhwa['cover'] ?? '',
            'synopsis' => $manhwa['description'] ?? $manhwa['synopsis'] ?? '',
            'genres' => $manhwa['genres'] ?? [],
            'status' => ucfirst(strtolower($manhwa['status'] ?? 'Ongoing')),
            'type' => $manhwa['type'] ?? 'Manhwa',
            'rating' => isset($manhwa['rating']) ? (float) $manhwa['rating'] : null,
            'totalChapters' => isset($manhwa['total_chapters']) ? (int) $manhwa['total_chapters'] : count($manhwa['chapters'] ?? []),
            'scrapedAt' => $manhwa['scraped_at'] ?? current_time('c'),
        ];
    }
    
    /**
     * Normalize chapters array
     *
     * @param array $chapters
     * @return array
     */
    private static function normalize_chapters($chapters) {
        $normalized = [];
        
        foreach ($chapters as $chapter) {
            $normalized[] = [
                'title' => $chapter['title'] ?? '',
                'number' => $chapter['number'] ?? null,
                'url' => $chapter['url'] ?? '',
                'date' => $chapter['date'] ?? '',
            ];
        }
        
        return $normalized;
    }
    
    /**
     * Generate excerpt from description
     *
     * @param string $description
     * @param int $length
     * @return string
     */
    private static function generate_excerpt($description, $length = 200) {
        $description = wp_strip_all_tags($description);
        
        if (strlen($description) <= $length) {
            return $description;
        }
        
        $excerpt = substr($description, 0, $length);
        $last_space = strrpos($excerpt, ' ');
        
        if ($last_space !== false) {
            $excerpt = substr($excerpt, 0, $last_space);
        }
        
        return $excerpt . '...';
    }
    
    /**
     * Save export to file
     *
     * @param array $manhwa_data
     * @param string $format
     * @param string $filename
     * @return string|WP_Error File path or error
     */
    public static function save_to_file($manhwa_data, $format = 'standard', $filename = '') {
        // Generate filename if not provided
        if (empty($filename)) {
            $filename = 'manhwa-export-' . date('Y-m-d-His') . '.json';
        }
        
        // Get upload directory
        $upload_dir = wp_upload_dir();
        $export_dir = $upload_dir['basedir'] . '/manhwa-scraper-exports';
        
        // Create directory if not exists
        if (!file_exists($export_dir)) {
            wp_mkdir_p($export_dir);
            
            // Add index file for security
            file_put_contents($export_dir . '/index.php', '<?php // Silence is golden');
        }
        
        // Generate export
        if ($format === 'komiku') {
            $json = self::export_komiku($manhwa_data);
        } else {
            $json = self::export_standard($manhwa_data);
        }
        
        // Save file
        $file_path = $export_dir . '/' . $filename;
        $result = file_put_contents($file_path, $json);
        
        if ($result === false) {
            return new WP_Error('save_failed', __('Failed to save export file', 'manhwa-scraper'));
        }
        
        return $file_path;
    }
    
    /**
     * Get download URL for export file
     *
     * @param string $file_path
     * @return string
     */
    public static function get_download_url($file_path) {
        $upload_dir = wp_upload_dir();
        return str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $file_path);
    }
}
