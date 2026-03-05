<?php
/**
 * Manhwa Export & Import Handler
 * Handles exporting manhwa data to JSON and importing from JSON files.
 *
 * @package Flavor_Flavor
 */

if (!defined('ABSPATH')) exit;

/* ================================================================
 * HOOKS
 * ============================================================= */

add_action('admin_init', 'flavor_handle_manhwa_export');
add_action('admin_init', 'flavor_handle_manhwa_import');

/* ================================================================
 * EXPORT HANDLER
 * ============================================================= */

function flavor_handle_manhwa_export() {
    if (!isset($_POST['mcms_export_nonce'])) return;
    if (!wp_verify_nonce($_POST['mcms_export_nonce'], 'mcms_export_manhwa')) return;
    if (!current_user_can('edit_posts')) return;

    $mode = isset($_POST['export_mode']) ? sanitize_text_field($_POST['export_mode']) : 'all';

    // Determine which post IDs to export
    $post_ids = [];

    if ($mode === 'selected' && !empty($_POST['export_ids'])) {
        $post_ids = array_map('intval', (array) $_POST['export_ids']);
    } else {
        // Export all
        $args = [
            'post_type'      => 'manhwa',
            'post_status'    => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ];
        $post_ids = get_posts($args);
    }

    if (empty($post_ids)) {
        set_transient('mcms_export_notice', 'No manhwa found to export.', 30);
        return;
    }

    // Build export data
    $export = [
        'version'     => '1.0',
        'exported_at' => current_time('c'),
        'site_url'    => home_url(),
        'count'       => count($post_ids),
        'manhwa'      => [],
    ];

    foreach ($post_ids as $pid) {
        $post = get_post($pid);
        if (!$post) continue;

        $meta = flavor_get_manhwa_meta($pid);
        $genres = wp_get_post_terms($pid, 'manhwa_genre', ['fields' => 'names']);
        if (is_wp_error($genres)) $genres = [];

        $chapters = get_post_meta($pid, '_manhwa_chapters', true);
        if (!is_array($chapters)) $chapters = [];

        // Get cover image URL
        $cover_url = '';
        if (has_post_thumbnail($pid)) {
            $cover_url = get_the_post_thumbnail_url($pid, 'full');
        }

        // Source URL from scraper (if available)
        $source_url = get_post_meta($pid, '_mws_source_url', true) ?: '';

        // Build chapter export (structure only, no images data)
        $chapters_export = [];
        foreach ($chapters as $ch) {
            $chapters_export[] = [
                'title'   => $ch['title'] ?? '',
                'slug'    => $ch['slug'] ?? '',
                'date'    => $ch['date'] ?? '',
                'url'     => $ch['url'] ?? '',
                'images'  => isset($ch['images']) && is_array($ch['images']) ? $ch['images'] : [],
            ];
        }

        $export['manhwa'][] = [
            'title'          => $post->post_title,
            'slug'           => $post->post_name,
            'content'        => $post->post_content,
            'excerpt'        => $post->post_excerpt,
            'post_status'    => $post->post_status,
            'date'           => $post->post_date,
            'cover_url'      => $cover_url,
            'source_url'     => $source_url,
            'metadata'       => [
                'status'       => $meta['status'],
                'type'         => $meta['type'],
                'author'       => $meta['author'],
                'artist'       => $meta['artist'],
                'rating'       => $meta['rating'],
                'release_year' => $meta['release_year'],
                'alt_title'    => $meta['alt_title'],
            ],
            'genres'         => $genres,
            'chapters'       => $chapters_export,
            'views'          => (int) get_post_meta($pid, '_manhwa_views', true),
        ];
    }

    // Output JSON file for download
    $filename = 'manhwa-export-' . date('Ymd-His') . '.json';
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ================================================================
 * IMPORT HANDLER
 * ============================================================= */

function flavor_handle_manhwa_import() {
    if (!isset($_POST['mcms_import_nonce'])) return;
    if (!wp_verify_nonce($_POST['mcms_import_nonce'], 'mcms_import_manhwa')) return;
    if (!current_user_can('edit_posts')) return;

    if (empty($_FILES['import_file']['tmp_name'])) {
        set_transient('mcms_import_notice', ['type' => 'error', 'msg' => 'Please select a JSON file to import.'], 30);
        return;
    }

    $file = $_FILES['import_file']['tmp_name'];
    $json = file_get_contents($file);
    $data = json_decode($json, true);

    if (!$data || !isset($data['manhwa']) || !is_array($data['manhwa'])) {
        set_transient('mcms_import_notice', ['type' => 'error', 'msg' => 'Invalid export file format.'], 30);
        return;
    }

    $duplicate_mode = isset($_POST['duplicate_mode']) ? sanitize_text_field($_POST['duplicate_mode']) : 'skip';
    $import_status  = isset($_POST['import_status']) ? sanitize_text_field($_POST['import_status']) : 'keep';

    $imported = 0;
    $skipped  = 0;
    $updated  = 0;
    $errors   = 0;

    // Needed for media_sideload_image
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    foreach ($data['manhwa'] as $item) {
        $title = sanitize_text_field($item['title'] ?? '');
        if (empty($title)) { $errors++; continue; }

        // Check for existing post with same title
        $existing = get_posts([
            'post_type'   => 'manhwa',
            'title'       => $title,
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'numberposts' => 1,
        ]);

        $existing_id = !empty($existing) ? $existing[0]->ID : 0;

        if ($existing_id && $duplicate_mode === 'skip') {
            $skipped++;
            continue;
        }

        // Determine post status
        $status = 'publish';
        if ($import_status === 'keep' && !empty($item['post_status'])) {
            $status = sanitize_text_field($item['post_status']);
        } elseif ($import_status === 'draft') {
            $status = 'draft';
        }

        $post_data = [
            'post_title'   => $title,
            'post_content' => wp_kses_post($item['content'] ?? ''),
            'post_excerpt' => sanitize_textarea_field($item['excerpt'] ?? ''),
            'post_type'    => 'manhwa',
            'post_status'  => $status,
        ];

        if (!empty($item['slug'])) {
            $post_data['post_name'] = sanitize_title($item['slug']);
        }

        if ($existing_id && $duplicate_mode === 'update') {
            $post_data['ID'] = $existing_id;
            $pid = wp_update_post($post_data, true);
            if (is_wp_error($pid)) { $errors++; continue; }
            $pid = $existing_id;
            $updated++;
        } else {
            $pid = wp_insert_post($post_data, true);
            if (is_wp_error($pid)) { $errors++; continue; }
            $imported++;
        }

        // Save metadata
        $meta = $item['metadata'] ?? [];
        if (!empty($meta['status']))       update_post_meta($pid, '_manhwa_status', sanitize_text_field($meta['status']));
        if (!empty($meta['type']))         update_post_meta($pid, '_manhwa_type', sanitize_text_field($meta['type']));
        if (!empty($meta['author']))       update_post_meta($pid, '_manhwa_author', sanitize_text_field($meta['author']));
        if (!empty($meta['artist']))       update_post_meta($pid, '_manhwa_artist', sanitize_text_field($meta['artist']));
        if (isset($meta['rating']))        update_post_meta($pid, '_manhwa_rating', sanitize_text_field($meta['rating']));
        if (!empty($meta['release_year'])) update_post_meta($pid, '_manhwa_release_year', sanitize_text_field($meta['release_year']));
        if (!empty($meta['alt_title']))    update_post_meta($pid, '_manhwa_alternative_title', sanitize_text_field($meta['alt_title']));

        // Views
        if (isset($item['views'])) update_post_meta($pid, '_manhwa_views', intval($item['views']));

        // Source URL
        if (!empty($item['source_url'])) update_post_meta($pid, '_mws_source_url', esc_url_raw($item['source_url']));

        // Genres
        if (!empty($item['genres']) && is_array($item['genres'])) {
            wp_set_object_terms($pid, $item['genres'], 'manhwa_genre');
        }

        // Chapters
        if (!empty($item['chapters']) && is_array($item['chapters'])) {
            $chapters = [];
            foreach ($item['chapters'] as $ch) {
                $chapters[] = [
                    'title'  => sanitize_text_field($ch['title'] ?? ''),
                    'slug'   => sanitize_title($ch['slug'] ?? ''),
                    'date'   => sanitize_text_field($ch['date'] ?? ''),
                    'url'    => esc_url_raw($ch['url'] ?? ''),
                    'images' => isset($ch['images']) && is_array($ch['images']) ? array_map('esc_url_raw', $ch['images']) : [],
                ];
            }
            update_post_meta($pid, '_manhwa_chapters', $chapters);
        }

        // Download cover image (only for new imports or updates without existing thumbnail)
        if (!empty($item['cover_url']) && (!$existing_id || !has_post_thumbnail($pid))) {
            $cover_id = media_sideload_image($item['cover_url'], $pid, $title . ' Cover', 'id');
            if (!is_wp_error($cover_id)) {
                set_post_thumbnail($pid, $cover_id);
            }
        }
    }

    $msg = sprintf(
        '%d imported, %d updated, %d skipped, %d errors (Total: %d manhwa in file)',
        $imported, $updated, $skipped, $errors, count($data['manhwa'])
    );
    set_transient('mcms_import_notice', ['type' => 'success', 'msg' => $msg], 60);

    wp_redirect(admin_url('admin.php?page=manhwa-cms&mcms_imported=1'));
    exit;
}
