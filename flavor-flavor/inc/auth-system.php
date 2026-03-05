<?php
/**
 * Auth System - Login, Register, Forgot Password
 * Custom auth system using WordPress users
 */

if (!defined('ABSPATH')) exit;

// ============================
// ENQUEUE SCRIPTS
// ============================
function flavor_auth_enqueue() {
    wp_enqueue_script(
        'flavor-auth-system',
        get_template_directory_uri() . '/assets/js/auth-system.js',
        array(),
        filemtime(get_template_directory() . '/assets/js/auth-system.js'),
        true
    );
    wp_localize_script('flavor-auth-system', 'flavorAuth', array(
        'ajaxurl'   => admin_url('admin-ajax.php'),
        'nonce'     => wp_create_nonce('flavor_auth_nonce'),
        'logged_in' => is_user_logged_in() ? '1' : '0',
        'user_name' => is_user_logged_in() ? wp_get_current_user()->display_name : '',
        'user_avatar' => is_user_logged_in() ? get_avatar_url(get_current_user_id(), array('size' => 64)) : '',
        'logout_url' => wp_logout_url(home_url()),
    ));
}
add_action('wp_enqueue_scripts', 'flavor_auth_enqueue');

// ============================
// AJAX: Login
// ============================
function flavor_auth_login() {
    check_ajax_referer('flavor_auth_nonce', 'nonce');
    
    $username = sanitize_text_field($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) && $_POST['remember'] === '1';
    
    if (empty($username) || empty($password)) {
        wp_send_json_error('Username dan password wajib diisi.');
    }
    
    $creds = array(
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => $remember,
    );
    
    $user = wp_signon($creds, is_ssl());
    
    if (is_wp_error($user)) {
        $error_code = $user->get_error_code();
        if ($error_code === 'invalid_username' || $error_code === 'invalid_email') {
            wp_send_json_error('Username atau email tidak ditemukan.');
        } elseif ($error_code === 'incorrect_password') {
            wp_send_json_error('Password salah.');
        } else {
            wp_send_json_error('Login gagal. Silakan coba lagi.');
        }
    }
    
    wp_send_json_success(array(
        'message'  => 'Login berhasil!',
        'name'     => $user->display_name,
        'avatar'   => get_avatar_url($user->ID, array('size' => 64)),
    ));
}
add_action('wp_ajax_nopriv_flavor_auth_login', 'flavor_auth_login');
add_action('wp_ajax_flavor_auth_login', 'flavor_auth_login');

// ============================
// AJAX: Register
// ============================
function flavor_auth_register() {
    check_ajax_referer('flavor_auth_nonce', 'nonce');
    
    if (!get_option('users_can_register')) {
        // Allow registration via our system even if WP registration is disabled
        // Remove this check if you want to respect WP setting
    }
    
    $username = sanitize_user($_POST['username'] ?? '');
    $email    = sanitize_email($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        wp_send_json_error('Semua field wajib diisi.');
    }
    
    if (strlen($username) < 3) {
        wp_send_json_error('Username minimal 3 karakter.');
    }
    
    if (strlen($username) > 30) {
        wp_send_json_error('Username maksimal 30 karakter.');
    }
    
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        wp_send_json_error('Username hanya boleh huruf, angka, dan underscore.');
    }
    
    if (!is_email($email)) {
        wp_send_json_error('Format email tidak valid.');
    }
    
    if (strlen($password) < 6) {
        wp_send_json_error('Password minimal 6 karakter.');
    }
    
    if ($password !== $confirm) {
        wp_send_json_error('Konfirmasi password tidak cocok.');
    }
    
    if (username_exists($username)) {
        wp_send_json_error('Username sudah digunakan.');
    }
    
    if (email_exists($email)) {
        wp_send_json_error('Email sudah terdaftar.');
    }
    
    // Create user
    $user_id = wp_create_user($username, $password, $email);
    
    if (is_wp_error($user_id)) {
        wp_send_json_error('Registrasi gagal: ' . $user_id->get_error_message());
    }
    
    // Set role
    $user = new WP_User($user_id);
    $user->set_role('subscriber');
    
    // Auto login
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id, true);
    
    wp_send_json_success(array(
        'message' => 'Registrasi berhasil! Selamat datang, ' . $username . '!',
        'name'    => $username,
        'avatar'  => get_avatar_url($user_id, array('size' => 64)),
    ));
}
add_action('wp_ajax_nopriv_flavor_auth_register', 'flavor_auth_register');

// ============================
// AJAX: Forgot Password
// ============================
function flavor_auth_forgot_password() {
    check_ajax_referer('flavor_auth_nonce', 'nonce');
    
    $email = sanitize_email($_POST['email'] ?? '');
    
    if (empty($email) || !is_email($email)) {
        wp_send_json_error('Masukkan email yang valid.');
    }
    
    $user = get_user_by('email', $email);
    
    if (!$user) {
        // Don't reveal if email exists or not (security)
        wp_send_json_success(array(
            'message' => 'Jika email terdaftar, link reset password telah dikirim. Cek inbox dan folder spam.',
        ));
        return;
    }
    
    // Generate reset key
    $reset_key = get_password_reset_key($user);
    
    if (is_wp_error($reset_key)) {
        wp_send_json_error('Gagal mengirim email reset. Coba lagi nanti.');
    }
    
    // Build reset URL (custom page)
    $reset_url = home_url('/reset-password/') . '?key=' . $reset_key . '&login=' . rawurlencode($user->user_login);
    
    // Send email
    $site_name = get_bloginfo('name');
    $subject = "[$site_name] Reset Password";
    $message = "Halo {$user->display_name},\n\n";
    $message .= "Seseorang meminta reset password untuk akun kamu di $site_name.\n\n";
    $message .= "Klik link berikut untuk membuat password baru:\n";
    $message .= "$reset_url\n\n";
    $message .= "Jika kamu tidak meminta reset password, abaikan email ini.\n\n";
    $message .= "Terima kasih,\n$site_name";
    
    // Send email
    $sent = wp_mail($user->user_email, $subject, $message);
    
    wp_send_json_success(array(
        'message' => 'Jika email terdaftar, link reset password telah dikirim. Cek inbox dan folder spam.',
    ));
}
add_action('wp_ajax_nopriv_flavor_auth_forgot_password', 'flavor_auth_forgot_password');
add_action('wp_ajax_flavor_auth_forgot_password', 'flavor_auth_forgot_password');

// ============================
// AJAX: Logout
// ============================
function flavor_auth_logout() {
    check_ajax_referer('flavor_auth_nonce', 'nonce');
    wp_logout();
    wp_send_json_success(array('message' => 'Berhasil logout.'));
}
add_action('wp_ajax_flavor_auth_logout', 'flavor_auth_logout');

// ============================
// REDIRECT wp-login.php RESET to custom page
// ============================
function flavor_redirect_reset_password() {
    if (isset($_GET['action']) && $_GET['action'] === 'rp' && isset($_GET['key']) && isset($_GET['login'])) {
        $url = home_url('/reset-password/') . '?key=' . urlencode($_GET['key']) . '&login=' . urlencode($_GET['login']);
        wp_redirect($url);
        exit;
    }
}
add_action('login_init', 'flavor_redirect_reset_password');

// Auto-create Reset Password page
function flavor_create_reset_password_page() {
    $page = get_page_by_path('reset-password');
    if (!$page) {
        $page_id = wp_insert_post(array(
            'post_title'   => 'Reset Password',
            'post_name'    => 'reset-password',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '',
        ));
        if ($page_id && !is_wp_error($page_id)) {
            update_post_meta($page_id, '_wp_page_template', 'page-reset-password.php');
        }
    }
}
add_action('after_switch_theme', 'flavor_create_reset_password_page');
add_action('init', 'flavor_create_reset_password_page');
