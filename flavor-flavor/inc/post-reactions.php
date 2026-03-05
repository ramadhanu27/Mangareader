<?php
/**
 * Post Reactions System
 * Sistem reaksi (Like, Funny, Nice, Sad, Angry) per post
 */

if (!defined('ABSPATH')) exit;

// ============================
// DATABASE SETUP
// ============================
function flavor_reactions_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'flavor_reactions';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        post_id BIGINT UNSIGNED NOT NULL,
        reaction_type VARCHAR(20) NOT NULL,
        user_token VARCHAR(64) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_post_id (post_id),
        INDEX idx_user_token (user_token),
        UNIQUE KEY unique_reaction (post_id, user_token)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
add_action('after_switch_theme', 'flavor_reactions_create_table');

// Auto-create table
function flavor_reactions_check_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'flavor_reactions';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
        flavor_reactions_create_table();
    }
}
add_action('init', 'flavor_reactions_check_table');

// ============================
// REACTION TOKEN (anonymous / no login needed)
// ============================
function flavor_get_reaction_token() {
    // Logged-in users use their user ID
    if (is_user_logged_in()) {
        return 'user_' . get_current_user_id();
    }
    // Guests: read from cookie (JS will auto-generate & set if missing)
    if (isset($_COOKIE['flavor_reaction_token'])) {
        return sanitize_text_field($_COOKIE['flavor_reaction_token']);
    }
    return '';
}

// ============================
// ENQUEUE SCRIPTS
// ============================
function flavor_reactions_enqueue() {
    if (is_singular()) {
        wp_enqueue_script(
            'flavor-post-reactions',
            get_template_directory_uri() . '/assets/js/post-reactions.js',
            array(),
            filemtime(get_template_directory() . '/assets/js/post-reactions.js'),
            true
        );
        wp_localize_script('flavor-post-reactions', 'flavorReactions', array(
            'ajaxurl'    => admin_url('admin-ajax.php'),
            'nonce'      => wp_create_nonce('flavor_reactions_nonce'),
            'post_id'    => get_the_ID(),
            'user_token' => flavor_get_reaction_token(),
            'images_url' => get_template_directory_uri() . '/assets/images/',
        ));
    }
}
add_action('wp_enqueue_scripts', 'flavor_reactions_enqueue');

// ============================
// AJAX: React to Post
// ============================
function flavor_reactions_react() {
    check_ajax_referer('flavor_reactions_nonce', 'nonce');
    global $wpdb;
    $table = $wpdb->prefix . 'flavor_reactions';
    
    $post_id  = intval($_POST['post_id'] ?? 0);
    $type     = sanitize_text_field($_POST['type'] ?? '');
    $token    = sanitize_text_field($_POST['token'] ?? '');
    
    $valid_types = array('like', 'funny', 'nice', 'sad', 'angry');
    
    if (empty($post_id) || empty($type) || empty($token)) {
        wp_send_json_error('Data tidak lengkap');
    }
    
    if (!in_array($type, $valid_types)) {
        wp_send_json_error('Tipe reaksi tidak valid');
    }
    
    // Check existing reaction
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE post_id = %d AND user_token = %s",
        $post_id, $token
    ));
    
    if ($existing) {
        if ($existing->reaction_type === $type) {
            // Same reaction = remove it
            $wpdb->delete($table, array('id' => $existing->id));
            $action = 'removed';
        } else {
            // Different reaction = update
            $wpdb->update($table, array(
                'reaction_type' => $type,
                'created_at'    => current_time('mysql'),
            ), array('id' => $existing->id));
            $action = 'changed';
        }
    } else {
        // New reaction
        $wpdb->insert($table, array(
            'post_id'       => $post_id,
            'reaction_type' => $type,
            'user_token'    => $token,
            'created_at'    => current_time('mysql'),
        ));
        $action = 'added';
    }
    
    // Get updated counts
    $counts = flavor_get_reaction_counts($post_id);
    $user_reaction = '';
    if ($action !== 'removed') {
        $user_reaction = $type;
    }
    
    wp_send_json_success(array(
        'action'        => $action,
        'counts'        => $counts,
        'user_reaction' => $user_reaction,
        'total'         => array_sum($counts),
    ));
}
add_action('wp_ajax_flavor_reaction_react', 'flavor_reactions_react');
add_action('wp_ajax_nopriv_flavor_reaction_react', 'flavor_reactions_react');

// ============================
// AJAX: Load Reactions
// ============================
function flavor_reactions_load() {
    check_ajax_referer('flavor_reactions_nonce', 'nonce');
    global $wpdb;
    $table = $wpdb->prefix . 'flavor_reactions';
    
    $post_id = intval($_POST['post_id'] ?? 0);
    $token   = sanitize_text_field($_POST['token'] ?? '');
    
    if (empty($post_id)) {
        wp_send_json_error('Post ID diperlukan');
    }
    
    $counts = flavor_get_reaction_counts($post_id);
    
    // Get user's reaction
    $user_reaction = '';
    if (!empty($token)) {
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT reaction_type FROM $table WHERE post_id = %d AND user_token = %s",
            $post_id, $token
        ));
        if ($existing) {
            $user_reaction = $existing;
        }
    }
    
    wp_send_json_success(array(
        'counts'        => $counts,
        'user_reaction' => $user_reaction,
        'total'         => array_sum($counts),
    ));
}
add_action('wp_ajax_flavor_reaction_load', 'flavor_reactions_load');
add_action('wp_ajax_nopriv_flavor_reaction_load', 'flavor_reactions_load');

// ============================
// HELPER: Get Reaction Counts
// ============================
function flavor_get_reaction_counts($post_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'flavor_reactions';
    
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT reaction_type, COUNT(*) as count FROM $table WHERE post_id = %d GROUP BY reaction_type",
        $post_id
    ));
    
    $counts = array(
        'like'  => 0,
        'funny' => 0,
        'nice'  => 0,
        'sad'   => 0,
        'angry' => 0,
    );
    
    foreach ($results as $row) {
        if (isset($counts[$row->reaction_type])) {
            $counts[$row->reaction_type] = intval($row->count);
        }
    }
    
    return $counts;
}
