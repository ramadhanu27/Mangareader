<?php
/**
 * Custom Comments System
 * Sistem komentar custom tanpa menggunakan WordPress comments
 */

if (!defined('ABSPATH')) exit;

// ============================
// DATABASE SETUP
// ============================
function flavor_comments_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'flavor_comments';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        post_id BIGINT UNSIGNED NOT NULL,
        parent_id BIGINT UNSIGNED DEFAULT 0,
        author_name VARCHAR(100) NOT NULL,
        author_token VARCHAR(64) NOT NULL,
        author_color VARCHAR(7) DEFAULT '#4a90d9',
        content TEXT NOT NULL,
        likes INT DEFAULT 0,
        liked_by TEXT,
        is_edited TINYINT(1) DEFAULT 0,
        status VARCHAR(20) DEFAULT 'approved',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_post_id (post_id),
        INDEX idx_parent_id (parent_id),
        INDEX idx_author_token (author_token)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
add_action('after_switch_theme', 'flavor_comments_create_table');

// Auto-create table if not exists
function flavor_comments_check_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'flavor_comments';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
        flavor_comments_create_table();
    }
}
add_action('init', 'flavor_comments_check_table');

// ============================
// USER TOKEN (User ID-based for logged-in, cookie for legacy)
// ============================
function flavor_get_comment_token() {
    if (is_user_logged_in()) {
        return 'user_' . get_current_user_id();
    }
    if (isset($_COOKIE['flavor_comment_token'])) {
        return sanitize_text_field($_COOKIE['flavor_comment_token']);
    }
    return '';
}

function flavor_get_comment_author() {
    if (isset($_COOKIE['flavor_comment_name'])) {
        return sanitize_text_field($_COOKIE['flavor_comment_name']);
    }
    return '';
}

function flavor_get_comment_color() {
    if (isset($_COOKIE['flavor_comment_color'])) {
        return sanitize_hex_color($_COOKIE['flavor_comment_color']);
    }
    $colors = ['#4a90d9', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#e67e22', '#3498db'];
    return $colors[array_rand($colors)];
}

// ============================
// ENQUEUE SCRIPTS
// ============================
function flavor_comments_enqueue() {
    if (is_singular()) {
        wp_enqueue_script(
            'flavor-custom-comments',
            get_template_directory_uri() . '/assets/js/custom-comments.js',
            array(),
            filemtime(get_template_directory() . '/assets/js/custom-comments.js'),
            true
        );
        wp_localize_script('flavor-custom-comments', 'flavorComments', array(
            'ajaxurl'         => admin_url('admin-ajax.php'),
            'nonce'           => wp_create_nonce('flavor_comments_nonce'),
            'post_id'         => get_the_ID(),
            'user_token'      => flavor_get_comment_token(),
            'user_name'       => flavor_get_comment_author(),
            'user_color'      => flavor_get_comment_color(),
            'is_admin'        => current_user_can('manage_options') ? '1' : '0',
            'is_logged_in'    => is_user_logged_in() ? '1' : '0',
            'max_upload_size' => '2',
        ));
    }
}
add_action('wp_enqueue_scripts', 'flavor_comments_enqueue');

// ============================
// AJAX: Set User Info
// ============================
function flavor_comments_set_user() {
    check_ajax_referer('flavor_comments_nonce', 'nonce');
    
    $name = sanitize_text_field($_POST['name'] ?? '');
    if (empty($name)) {
        wp_send_json_error('Nama harus diisi');
    }
    
    $token = flavor_get_comment_token();
    if (empty($token)) {
        $token = wp_generate_password(32, false);
    }
    
    $color = flavor_get_comment_color();
    
    // Set cookies for 1 year
    $expire = time() + (365 * DAY_IN_SECONDS);
    setcookie('flavor_comment_token', $token, $expire, '/');
    setcookie('flavor_comment_name', $name, $expire, '/');
    setcookie('flavor_comment_color', $color, $expire, '/');
    
    wp_send_json_success(array(
        'token' => $token,
        'name'  => $name,
        'color' => $color,
    ));
}
add_action('wp_ajax_flavor_comment_set_user', 'flavor_comments_set_user');
add_action('wp_ajax_nopriv_flavor_comment_set_user', 'flavor_comments_set_user');

// ============================
// AJAX: Add Comment
// ============================
function flavor_comments_add() {
    check_ajax_referer('flavor_comments_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Silakan login untuk berkomentar.');
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'flavor_comments';
    
    $post_id   = intval($_POST['post_id'] ?? 0);
    $parent_id = intval($_POST['parent_id'] ?? 0);
    $content   = wp_kses($_POST['content'] ?? '', array(
        'b'      => array(),
        'strong' => array(),
        'i'      => array(),
        'em'     => array(),
        's'      => array(),
        'del'    => array(),
        'span'   => array('class' => array()),
        'br'     => array(),
        'img'    => array('src' => array(), 'alt' => array(), 'class' => array(), 'loading' => array()),
    ));
    $token = flavor_get_comment_token();
    $wp_user = wp_get_current_user();
    $name  = $wp_user->display_name;
    $color = sanitize_hex_color($_POST['color'] ?? '#4a90d9');
    
    if (empty($post_id) || empty($content) || empty($token) || empty($name)) {
        wp_send_json_error('Data tidak lengkap');
    }
    
    if (mb_strlen(strip_tags($content)) > 1000) {
        wp_send_json_error('Komentar terlalu panjang (max 1000 karakter)');
    }
    
    $wpdb->insert($table, array(
        'post_id'      => $post_id,
        'parent_id'    => $parent_id,
        'author_name'  => $name,
        'author_token' => $token,
        'author_color' => $color,
        'content'      => $content,
        'likes'        => 0,
        'liked_by'     => '',
        'is_edited'    => 0,
        'status'       => 'approved',
        'created_at'   => current_time('mysql'),
    ));
    
    $comment_id = $wpdb->insert_id;
    
    if (!$comment_id) {
        wp_send_json_error('Gagal menyimpan komentar');
    }
    
    $comment = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $comment_id));
    
    wp_send_json_success(array(
        'comment' => flavor_format_comment($comment, $token),
    ));
}
add_action('wp_ajax_flavor_comment_add', 'flavor_comments_add');

// ============================
// AJAX: Upload Comment Image
// ============================
function flavor_comment_upload_image() {
    check_ajax_referer('flavor_comments_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Silakan login untuk upload gambar.');
    }
    
    if (empty($_FILES['image'])) {
        wp_send_json_error('Tidak ada file yang diupload.');
    }
    
    $file = $_FILES['image'];
    
    // Validate file size (max 2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        wp_send_json_error('Ukuran file maksimal 2MB.');
    }
    
    // Validate file type
    $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime, $allowed_types)) {
        wp_send_json_error('Format file harus JPG, PNG, GIF, atau WebP.');
    }
    
    // Create upload directory
    $upload_dir = wp_upload_dir();
    $comment_dir = $upload_dir['basedir'] . '/comment-images';
    if (!file_exists($comment_dir)) {
        wp_mkdir_p($comment_dir);
    }
    
    // Generate unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $ext = strtolower($ext);
    if (!in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'webp'))) {
        $ext = 'jpg';
    }
    $filename = 'comment_' . get_current_user_id() . '_' . time() . '_' . wp_rand(1000, 9999) . '.' . $ext;
    $filepath = $comment_dir . '/' . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        wp_send_json_error('Gagal menyimpan file.');
    }
    
    $url = $upload_dir['baseurl'] . '/comment-images/' . $filename;
    
    wp_send_json_success(array('url' => $url));
}
add_action('wp_ajax_flavor_comment_upload_image', 'flavor_comment_upload_image');

// ============================
// AJAX: Edit Comment
// ============================
function flavor_comments_edit() {
    check_ajax_referer('flavor_comments_nonce', 'nonce');
    global $wpdb;
    $table = $wpdb->prefix . 'flavor_comments';
    
    $comment_id = intval($_POST['comment_id'] ?? 0);
    $content    = wp_kses($_POST['content'] ?? '', array(
        'b'      => array(),
        'strong' => array(),
        'i'      => array(),
        'em'     => array(),
        's'      => array(),
        'del'    => array(),
        'span'   => array('class' => array()),
        'br'     => array(),
        'img'    => array('src' => array(), 'alt' => array(), 'class' => array(), 'loading' => array()),
    ));
    $token = flavor_get_comment_token();
    
    if (empty($comment_id) || empty($content) || empty($token)) {
        wp_send_json_error('Data tidak lengkap');
    }
    
    // Verify ownership
    $comment = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $comment_id));
    if (!$comment) {
        wp_send_json_error('Komentar tidak ditemukan');
    }
    
    $is_admin = current_user_can('manage_options');
    if ($comment->author_token !== $token && !$is_admin) {
        wp_send_json_error('Tidak memiliki akses');
    }
    
    $wpdb->update($table, array(
        'content'    => $content,
        'is_edited'  => 1,
        'updated_at' => current_time('mysql'),
    ), array('id' => $comment_id));
    
    wp_send_json_success(array('content' => $content));
}
add_action('wp_ajax_flavor_comment_edit', 'flavor_comments_edit');
add_action('wp_ajax_nopriv_flavor_comment_edit', 'flavor_comments_edit');

// ============================
// AJAX: Delete Comment
// ============================
function flavor_comments_delete() {
    check_ajax_referer('flavor_comments_nonce', 'nonce');
    global $wpdb;
    $table = $wpdb->prefix . 'flavor_comments';
    
    $comment_id = intval($_POST['comment_id'] ?? 0);
    $token = sanitize_text_field($_POST['token'] ?? '');
    
    $comment = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $comment_id));
    if (!$comment) {
        wp_send_json_error('Komentar tidak ditemukan');
    }
    
    $is_admin = current_user_can('manage_options');
    if ($comment->author_token !== $token && !$is_admin) {
        wp_send_json_error('Tidak memiliki akses');
    }
    
    // Delete comment and its replies
    $wpdb->delete($table, array('id' => $comment_id));
    $wpdb->delete($table, array('parent_id' => $comment_id));
    
    wp_send_json_success();
}
add_action('wp_ajax_flavor_comment_delete', 'flavor_comments_delete');
add_action('wp_ajax_nopriv_flavor_comment_delete', 'flavor_comments_delete');

// ============================
// AJAX: Like Comment
// ============================
function flavor_comments_like() {
    check_ajax_referer('flavor_comments_nonce', 'nonce');
    global $wpdb;
    $table = $wpdb->prefix . 'flavor_comments';
    
    $comment_id = intval($_POST['comment_id'] ?? 0);
    $token = flavor_get_comment_token();
    
    if (empty($comment_id) || empty($token)) {
        wp_send_json_error('Data tidak lengkap');
    }
    
    $comment = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $comment_id));
    if (!$comment) {
        wp_send_json_error('Komentar tidak ditemukan');
    }
    
    $liked_by = !empty($comment->liked_by) ? explode(',', $comment->liked_by) : array();
    
    if (in_array($token, $liked_by)) {
        // Unlike
        $liked_by = array_diff($liked_by, array($token));
        $likes = max(0, $comment->likes - 1);
        $action = 'unliked';
    } else {
        // Like
        $liked_by[] = $token;
        $likes = $comment->likes + 1;
        $action = 'liked';
    }
    
    $wpdb->update($table, array(
        'likes'    => $likes,
        'liked_by' => implode(',', $liked_by),
    ), array('id' => $comment_id));
    
    wp_send_json_success(array(
        'likes'  => $likes,
        'action' => $action,
    ));
}
add_action('wp_ajax_flavor_comment_like', 'flavor_comments_like');
add_action('wp_ajax_nopriv_flavor_comment_like', 'flavor_comments_like');

// ============================
// AJAX: Load Comments
// ============================
function flavor_comments_load() {
    check_ajax_referer('flavor_comments_nonce', 'nonce');
    global $wpdb;
    $table = $wpdb->prefix . 'flavor_comments';
    
    $post_id = intval($_POST['post_id'] ?? 0);
    $sort    = sanitize_text_field($_POST['sort'] ?? 'latest');
    $token   = flavor_get_comment_token();
    
    if (empty($post_id)) {
        wp_send_json_error('Post ID diperlukan');
    }
    
    // Sort order
    switch ($sort) {
        case 'popular':
            $orderby = 'likes DESC, created_at DESC';
            break;
        case 'oldest':
            $orderby = 'created_at ASC';
            break;
        default: // latest
            $orderby = 'created_at DESC';
            break;
    }
    
    // Get top-level comments
    $comments = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table WHERE post_id = %d AND parent_id = 0 AND status = 'approved' ORDER BY $orderby",
        $post_id
    ));
    
    // Get all replies for this post
    $replies = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table WHERE post_id = %d AND parent_id > 0 AND status = 'approved' ORDER BY created_at ASC",
        $post_id
    ));
    
    // Group replies by parent
    $replies_grouped = array();
    foreach ($replies as $reply) {
        $replies_grouped[$reply->parent_id][] = flavor_format_comment($reply, $token);
    }
    
    // Format comments
    $formatted = array();
    foreach ($comments as $comment) {
        $c = flavor_format_comment($comment, $token);
        $c['replies'] = $replies_grouped[$comment->id] ?? array();
        $c['reply_count'] = count($c['replies']);
        $formatted[] = $c;
    }
    
    // Total count
    $total = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE post_id = %d AND status = 'approved'",
        $post_id
    ));
    
    wp_send_json_success(array(
        'comments' => $formatted,
        'total'    => intval($total),
    ));
}
add_action('wp_ajax_flavor_comment_load', 'flavor_comments_load');
add_action('wp_ajax_nopriv_flavor_comment_load', 'flavor_comments_load');

// ============================
// AJAX: Report Comment
// ============================
function flavor_comments_report() {
    check_ajax_referer('flavor_comments_nonce', 'nonce');
    global $wpdb;
    $table = $wpdb->prefix . 'flavor_comments';
    
    $comment_id = intval($_POST['comment_id'] ?? 0);
    
    if (empty($comment_id)) {
        wp_send_json_error('Data tidak lengkap');
    }
    
    // Mark as reported (pending review)
    $wpdb->update($table, array(
        'status' => 'reported',
    ), array('id' => $comment_id));
    
    wp_send_json_success(array('message' => 'Komentar telah dilaporkan'));
}
add_action('wp_ajax_flavor_comment_report', 'flavor_comments_report');
add_action('wp_ajax_nopriv_flavor_comment_report', 'flavor_comments_report');

// ============================
// HELPER: Format Comment
// ============================
function flavor_format_comment($comment, $current_token = '') {
    $is_owner = ($comment->author_token === $current_token && !empty($current_token));
    
    // Also match by user ID for logged-in users
    if (!$is_owner && is_user_logged_in()) {
        $user_token = 'user_' . get_current_user_id();
        $is_owner = ($comment->author_token === $user_token);
    }
    
    $is_admin = current_user_can('manage_options');
    $liked_by = !empty($comment->liked_by) ? explode(',', $comment->liked_by) : array();
    
    // Parse BBCode spoiler tags to HTML
    $content = $comment->content;
    $content = preg_replace(
        '/\[spoiler\](.*?)\[\/spoiler\]/s',
        '<span class="fc-spoiler">$1</span>',
        $content
    );
    
    return array(
        'id'          => intval($comment->id),
        'parent_id'   => intval($comment->parent_id),
        'author_name' => $comment->author_name,
        'author_color' => $comment->author_color,
        'initial'     => mb_strtoupper(mb_substr($comment->author_name, 0, 1)),
        'content'     => $content,
        'likes'       => intval($comment->likes),
        'is_liked'    => in_array($current_token, $liked_by),
        'is_edited'   => (bool) $comment->is_edited,
        'is_owner'    => $is_owner,
        'can_edit'    => $is_owner || $is_admin,
        'can_delete'  => $is_admin,
        'time_ago'    => flavor_comment_time_ago($comment->created_at),
        'created_at'  => $comment->created_at,
    );
}

// ============================
// HELPER: Time Ago (Indonesian)
// ============================
function flavor_comment_time_ago($datetime) {
    $now = current_time('timestamp');
    $time = strtotime($datetime);
    $diff = $now - $time;
    
    if ($diff < 60) return 'Baru saja';
    if ($diff < 3600) return floor($diff / 60) . ' menit yang lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam yang lalu';
    if ($diff < 604800) return floor($diff / 86400) . ' hari yang lalu';
    if ($diff < 2592000) return floor($diff / 604800) . ' minggu yang lalu';
    if ($diff < 31536000) return floor($diff / 2592000) . ' bulan yang lalu';
    return floor($diff / 31536000) . ' tahun yang lalu';
}
