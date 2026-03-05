<?php
/**
 * Reading History System
 * Server-side reading history for logged-in users
 */
if (!defined('ABSPATH')) exit;

// ============================
// AJAX: Save to History
// ============================
function flavor_history_save() {
    check_ajax_referer('flavor_auth_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Login diperlukan');
    }
    
    $user_id    = get_current_user_id();
    $manhwa_id  = intval($_POST['manhwa_id'] ?? 0);
    $chapter    = sanitize_text_field($_POST['chapter'] ?? '');
    $chapter_key = sanitize_text_field($_POST['chapter_key'] ?? '');
    $url        = esc_url_raw($_POST['url'] ?? '');
    $manhwa_title = sanitize_text_field($_POST['manhwa_title'] ?? '');
    $manhwa_url   = esc_url_raw($_POST['manhwa_url'] ?? '');
    $thumbnail    = esc_url_raw($_POST['thumbnail'] ?? '');
    
    if (empty($manhwa_id) || empty($chapter) || empty($url)) {
        wp_send_json_error('Data tidak lengkap');
    }
    
    // Get existing history
    $history = get_user_meta($user_id, 'reading_history', true);
    if (!is_array($history)) {
        $history = array();
    }
    
    // Find existing entry for this manhwa
    $found = false;
    foreach ($history as &$entry) {
        if (intval($entry['manhwa_id']) === $manhwa_id) {
            // Update existing manhwa entry
            $entry['chapter']     = $chapter;
            $entry['chapter_key'] = $chapter_key;
            $entry['url']         = $url;
            $entry['timestamp']   = time();
            if (!empty($manhwa_title)) $entry['manhwa_title'] = $manhwa_title;
            if (!empty($manhwa_url)) $entry['manhwa_url'] = $manhwa_url;
            if (!empty($thumbnail)) $entry['thumbnail'] = $thumbnail;
            $found = true;
            break;
        }
    }
    unset($entry);
    
    if (!$found) {
        // Add new entry
        array_unshift($history, array(
            'manhwa_id'    => $manhwa_id,
            'manhwa_title' => $manhwa_title,
            'manhwa_url'   => $manhwa_url,
            'thumbnail'    => $thumbnail,
            'chapter'      => $chapter,
            'chapter_key'  => $chapter_key,
            'url'          => $url,
            'timestamp'    => time(),
        ));
    }
    
    // Sort by timestamp desc
    usort($history, function($a, $b) {
        return ($b['timestamp'] ?? 0) - ($a['timestamp'] ?? 0);
    });
    
    // Keep only last 50 entries
    $history = array_slice($history, 0, 50);
    
    update_user_meta($user_id, 'reading_history', $history);
    
    wp_send_json_success(array('message' => 'History saved'));
}
add_action('wp_ajax_flavor_history_save', 'flavor_history_save');

// ============================
// AJAX: Get History for a specific manhwa
// ============================
function flavor_history_get() {
    check_ajax_referer('flavor_auth_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Login diperlukan');
    }
    
    $user_id    = get_current_user_id();
    $manhwa_id  = intval($_POST['manhwa_id'] ?? 0);
    
    $history = get_user_meta($user_id, 'reading_history', true);
    if (!is_array($history)) {
        $history = array();
    }
    
    if ($manhwa_id > 0) {
        // Filter for specific manhwa - get all chapters read
        $manhwa_history = array();
        foreach ($history as $entry) {
            if (intval($entry['manhwa_id']) === $manhwa_id) {
                $manhwa_history[] = $entry;
            }
        }
        wp_send_json_success(array('history' => $manhwa_history));
    } else {
        // Return all history
        wp_send_json_success(array('history' => $history));
    }
}
add_action('wp_ajax_flavor_history_get', 'flavor_history_get');

// ============================
// AJAX: Get per-manhwa chapter history (all chapters read)
// ============================
function flavor_history_get_chapters() {
    check_ajax_referer('flavor_auth_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Login diperlukan');
    }
    
    $user_id   = get_current_user_id();
    $manhwa_id = intval($_POST['manhwa_id'] ?? 0);
    
    if (empty($manhwa_id)) {
        wp_send_json_error('Manhwa ID diperlukan');
    }
    
    // Get chapter-level history for this manhwa
    $key = 'reading_chapters_' . $manhwa_id;
    $chapters = get_user_meta($user_id, $key, true);
    if (!is_array($chapters)) {
        $chapters = array();
    }
    
    wp_send_json_success(array('chapters' => $chapters));
}
add_action('wp_ajax_flavor_history_get_chapters', 'flavor_history_get_chapters');

// ============================
// AJAX: Save per-manhwa chapter history
// ============================
function flavor_history_save_chapter() {
    check_ajax_referer('flavor_auth_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Login diperlukan');
    }
    
    $user_id     = get_current_user_id();
    $manhwa_id   = intval($_POST['manhwa_id'] ?? 0);
    $chapter     = sanitize_text_field($_POST['chapter'] ?? '');
    $chapter_key = sanitize_text_field($_POST['chapter_key'] ?? '');
    $url         = esc_url_raw($_POST['url'] ?? '');
    
    if (empty($manhwa_id) || empty($chapter_key)) {
        wp_send_json_error('Data tidak lengkap');
    }
    
    $key = 'reading_chapters_' . $manhwa_id;
    $chapters = get_user_meta($user_id, $key, true);
    if (!is_array($chapters)) {
        $chapters = array();
    }
    
    // Remove existing entry
    $chapters = array_filter($chapters, function($c) use ($chapter_key) {
        return ($c['chapter_key'] ?? '') !== $chapter_key;
    });
    
    // Add new entry at beginning
    array_unshift($chapters, array(
        'chapter'     => $chapter,
        'chapter_key' => $chapter_key,
        'url'         => $url,
        'timestamp'   => time(),
    ));
    
    // Keep last 30 chapters per manhwa
    $chapters = array_slice(array_values($chapters), 0, 30);
    
    update_user_meta($user_id, $key, $chapters);
    
    wp_send_json_success(array('message' => 'Chapter history saved'));
}
add_action('wp_ajax_flavor_history_save_chapter', 'flavor_history_save_chapter');

// ============================
// AJAX: Sync localStorage history to server
// ============================
function flavor_history_sync() {
    check_ajax_referer('flavor_auth_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Login diperlukan');
    }
    
    $user_id = get_current_user_id();
    $local_history = json_decode(stripslashes($_POST['history'] ?? '[]'), true);
    
    if (!is_array($local_history) || empty($local_history)) {
        wp_send_json_success(array('message' => 'Nothing to sync'));
        return;
    }
    
    $server_history = get_user_meta($user_id, 'reading_history', true);
    if (!is_array($server_history)) {
        $server_history = array();
    }
    
    // Merge: local entries that don't exist on server
    $server_ids = array_column($server_history, 'manhwa_id');
    foreach ($local_history as $local) {
        $mid = intval($local['manhwa_id'] ?? 0);
        if ($mid > 0 && !in_array($mid, $server_ids)) {
            $server_history[] = array(
                'manhwa_id'    => $mid,
                'manhwa_title' => sanitize_text_field($local['manhwa_title'] ?? ''),
                'manhwa_url'   => esc_url_raw($local['manhwa_url'] ?? ''),
                'thumbnail'    => esc_url_raw($local['thumbnail'] ?? ''),
                'chapter'      => sanitize_text_field($local['chapter'] ?? ''),
                'chapter_key'  => sanitize_text_field($local['chapter_key'] ?? ''),
                'url'          => esc_url_raw($local['url'] ?? ''),
                'timestamp'    => intval($local['timestamp'] ?? time()),
            );
        }
    }
    
    // Sort by timestamp desc
    usort($server_history, function($a, $b) {
        return ($b['timestamp'] ?? 0) - ($a['timestamp'] ?? 0);
    });
    
    // Keep last 50
    $server_history = array_slice($server_history, 0, 50);
    
    update_user_meta($user_id, 'reading_history', $server_history);
    
    wp_send_json_success(array('message' => 'History synced'));
}
add_action('wp_ajax_flavor_history_sync', 'flavor_history_sync');
