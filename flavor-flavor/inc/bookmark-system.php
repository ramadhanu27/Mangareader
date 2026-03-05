<?php
/**
 * Bookmark System - Server-side bookmarks for logged-in users
 * Extends the localStorage bookmark system with user_meta persistence
 */

if (!defined('ABSPATH')) exit;

// ============================
// AJAX: Toggle Bookmark
// ============================
function flavor_bookmark_toggle() {
    check_ajax_referer('flavor_auth_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Silakan login untuk menyimpan bookmark.');
    }
    
    $user_id = get_current_user_id();
    $post_id = intval($_POST['post_id'] ?? 0);
    
    if (empty($post_id)) {
        wp_send_json_error('Post ID diperlukan.');
    }
    
    $bookmarks = get_user_meta($user_id, 'flavor_bookmarks', true);
    if (!is_array($bookmarks)) {
        $bookmarks = array();
    }
    
    if (in_array($post_id, $bookmarks)) {
        // Remove bookmark
        $bookmarks = array_values(array_diff($bookmarks, array($post_id)));
        update_user_meta($user_id, 'flavor_bookmarks', $bookmarks);
        wp_send_json_success(array(
            'action'    => 'removed',
            'message'   => 'Bookmark dihapus.',
            'bookmarks' => flavor_get_bookmark_data($user_id),
            'count'     => count($bookmarks),
        ));
    } else {
        // Add bookmark
        array_unshift($bookmarks, $post_id);
        update_user_meta($user_id, 'flavor_bookmarks', $bookmarks);
        wp_send_json_success(array(
            'action'    => 'added',
            'message'   => 'Ditambahkan ke bookmark!',
            'bookmarks' => flavor_get_bookmark_data($user_id),
            'count'     => count($bookmarks),
        ));
    }
}
add_action('wp_ajax_flavor_bookmark_toggle', 'flavor_bookmark_toggle');

// ============================
// AJAX: Sync Bookmarks (merge localStorage with server)
// ============================
function flavor_bookmark_sync() {
    check_ajax_referer('flavor_auth_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in');
    }
    
    $user_id = get_current_user_id();
    $local_ids = json_decode(stripslashes($_POST['local_ids'] ?? '[]'), true);
    
    if (!is_array($local_ids)) {
        $local_ids = array();
    }
    
    // Sanitize
    $local_ids = array_map('intval', $local_ids);
    $local_ids = array_filter($local_ids);
    
    // Get server bookmarks
    $server_bookmarks = get_user_meta($user_id, 'flavor_bookmarks', true);
    if (!is_array($server_bookmarks)) {
        $server_bookmarks = array();
    }
    
    // Merge: local + server, remove duplicates, keep order (local first)
    $merged = array_unique(array_merge($local_ids, $server_bookmarks));
    
    // Verify all IDs are valid posts
    $valid = array();
    foreach ($merged as $pid) {
        if (get_post_status($pid)) {
            $valid[] = $pid;
        }
    }
    
    update_user_meta($user_id, 'flavor_bookmarks', $valid);
    
    wp_send_json_success(array(
        'bookmarks' => flavor_get_bookmark_data($user_id),
        'count'     => count($valid),
        'ids'       => $valid,
    ));
}
add_action('wp_ajax_flavor_bookmark_sync', 'flavor_bookmark_sync');

// ============================
// AJAX: Get Bookmarks
// ============================
function flavor_bookmark_get() {
    check_ajax_referer('flavor_auth_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in');
    }
    
    $user_id = get_current_user_id();
    
    wp_send_json_success(array(
        'bookmarks' => flavor_get_bookmark_data($user_id),
        'count'     => count(flavor_get_user_bookmark_ids($user_id)),
        'ids'       => flavor_get_user_bookmark_ids($user_id),
    ));
}
add_action('wp_ajax_flavor_bookmark_get', 'flavor_bookmark_get');

// ============================
// HELPERS
// ============================
function flavor_get_user_bookmark_ids($user_id) {
    $bookmarks = get_user_meta($user_id, 'flavor_bookmarks', true);
    return is_array($bookmarks) ? $bookmarks : array();
}

function flavor_get_bookmark_data($user_id) {
    $ids = flavor_get_user_bookmark_ids($user_id);
    $data = array();
    
    foreach ($ids as $post_id) {
        $post = get_post($post_id);
        if (!$post) continue;
        
        $thumb = get_the_post_thumbnail_url($post_id, 'manga-thumb');
        if (!$thumb) {
            $thumb = get_the_post_thumbnail_url($post_id, 'medium');
        }
        
        // Get latest chapter
        $chapters = get_post_meta($post_id, 'manhwa_chapters', true);
        $latest_ch = '';
        if (is_array($chapters) && !empty($chapters)) {
            $last = end($chapters);
            $latest_ch = isset($last['title']) ? $last['title'] : '';
        }
        
        $data[] = array(
            'id'         => $post_id,
            'title'      => $post->post_title,
            'url'        => get_permalink($post_id),
            'image'      => $thumb ?: '',
            'latest_ch'  => $latest_ch,
            'date'       => get_the_date('Y-m-d', $post_id),
        );
    }
    
    return $data;
}

// ============================
// RESTRICT WP-ADMIN
// ============================
function flavor_restrict_admin_access() {
    if (defined('DOING_AJAX') && DOING_AJAX) return;
    if (defined('DOING_CRON') && DOING_CRON) return;
    
    if (is_admin() && !current_user_can('manage_options')) {
        wp_redirect(home_url('/profile/'));
        exit;
    }
}
add_action('admin_init', 'flavor_restrict_admin_access');

// Hide admin bar for non-admin users
function flavor_hide_admin_bar($show) {
    if (!current_user_can('manage_options')) {
        return false;
    }
    return $show;
}
add_filter('show_admin_bar', 'flavor_hide_admin_bar');
