<?php
/**
 * Maintenance Mode System
 * Toggle dari Customizer: Appearance > Customize > Maintenance Mode
 * Admin tetap bisa akses, non-admin melihat halaman maintenance
 */

if (!defined('ABSPATH')) exit;

// ============================
// MAINTENANCE MODE CHECK
// ============================
function flavor_maintenance_mode() {
    // Skip if in admin area or doing AJAX or customizer preview
    if (is_admin() || wp_doing_ajax() || is_customize_preview()) {
        return;
    }
    
    // Skip if on wp-login.php
    if (strpos($_SERVER['REQUEST_URI'], 'wp-login.php') !== false) {
        return;
    }
    
    // Check if maintenance mode is enabled
    $enabled = get_theme_mod('flavor_maintenance_mode', false);
    if (!$enabled) {
        return;
    }
    
    // Allow admins to bypass
    if (is_user_logged_in() && current_user_can('manage_options')) {
        return;
    }
    
    // Auto-disable: if timer is set and time has passed, turn off maintenance
    $show_timer = get_theme_mod('flavor_maintenance_timer', false);
    $timer_date = get_theme_mod('flavor_maintenance_timer_date', '');
    if ($show_timer && !empty($timer_date)) {
        $target_time = strtotime($timer_date);
        if ($target_time && $target_time <= current_time('timestamp')) {
            set_theme_mod('flavor_maintenance_mode', false);
            return; // Maintenance is now off, let user through
        }
    }
    
    // Set 503 status
    http_response_code(503);
    header('Retry-After: 3600');
    
    // Get custom settings
    $title = get_theme_mod('flavor_maintenance_title', 'Sedang Maintenance');
    $message = get_theme_mod('flavor_maintenance_message', 'Website sedang dalam perbaikan. Kami akan segera kembali!');
    $show_timer = get_theme_mod('flavor_maintenance_timer', false);
    $timer_date = get_theme_mod('flavor_maintenance_timer_date', '');
    $bg_color = get_theme_mod('flavor_maintenance_bg_color', '#0f172a');
    $accent_color = get_theme_mod('flavor_maintenance_accent', '#6366f1');
    
    // Get site info
    $site_name = get_bloginfo('name');
    $site_icon = get_site_icon_url(64);
    $custom_logo_id = get_theme_mod('custom_logo');
    $custom_logo_url = $custom_logo_id ? wp_get_attachment_image_url($custom_logo_id, 'medium') : '';
    ?>
<!DOCTYPE html>
<html lang="<?php echo get_locale(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo esc_html($title . ' - ' . $site_name); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: <?php echo esc_attr($bg_color); ?>;
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }
        
        /* Animated Background */
        .mt-bg {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            z-index: 0;
            overflow: hidden;
        }
        
        .mt-bg-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.15;
            animation: mtFloat 20s ease-in-out infinite;
        }
        
        .mt-bg-orb:nth-child(1) {
            width: 400px; height: 400px;
            background: <?php echo esc_attr($accent_color); ?>;
            top: -100px; right: -100px;
            animation-delay: 0s;
        }
        
        .mt-bg-orb:nth-child(2) {
            width: 300px; height: 300px;
            background: #ec4899;
            bottom: -80px; left: -80px;
            animation-delay: -7s;
        }
        
        .mt-bg-orb:nth-child(3) {
            width: 250px; height: 250px;
            background: #06b6d4;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: -14s;
        }
        
        @keyframes mtFloat {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(30px, -40px) scale(1.1); }
            50% { transform: translate(-20px, 20px) scale(0.9); }
            75% { transform: translate(40px, 30px) scale(1.05); }
        }
        
        /* Content */
        .mt-container {
            position: relative;
            z-index: 1;
            text-align: center;
            padding: 40px 24px;
            max-width: 520px;
            width: 100%;
        }
        
        /* Logo/Icon */
        .mt-logo {
            margin-bottom: 32px;
        }
        
        .mt-logo img {
            max-height: 60px;
            width: auto;
            border-radius: 10px;
        }
        
        .mt-logo-text {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            background: <?php echo esc_attr($accent_color); ?>;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 800;
            color: #fff;
        }
        
        /* Gear Animation */
        .mt-gear {
            margin-bottom: 28px;
            display: inline-block;
        }
        
        .mt-gear svg {
            width: 64px;
            height: 64px;
            color: <?php echo esc_attr($accent_color); ?>;
            animation: mtSpin 8s linear infinite;
            opacity: 0.6;
        }
        
        @keyframes mtSpin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* Title */
        .mt-title {
            font-size: 32px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 12px;
            letter-spacing: -0.5px;
            line-height: 1.2;
        }
        
        /* Message */
        .mt-message {
            font-size: 16px;
            line-height: 1.6;
            color: #94a3b8;
            margin-bottom: 36px;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Timer */
        .mt-timer {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin-bottom: 36px;
        }
        
        .mt-timer-block {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 16px 12px;
            min-width: 72px;
            backdrop-filter: blur(10px);
        }
        
        .mt-timer-num {
            font-size: 28px;
            font-weight: 800;
            color: #fff;
            line-height: 1;
            margin-bottom: 4px;
            font-variant-numeric: tabular-nums;
        }
        
        .mt-timer-label {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
        }
        
        /* Progress Bar */
        .mt-progress {
            width: 200px;
            height: 3px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 2px;
            margin: 0 auto 24px;
            overflow: hidden;
        }
        
        .mt-progress-bar {
            width: 40%;
            height: 100%;
            background: <?php echo esc_attr($accent_color); ?>;
            border-radius: 2px;
            animation: mtProgress 2s ease-in-out infinite;
        }
        
        @keyframes mtProgress {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(350%); }
        }
        
        /* Footer */
        .mt-footer {
            font-size: 12px;
            color: #475569;
        }
        
        .mt-footer a {
            color: #64748b;
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .mt-footer a:hover {
            color: <?php echo esc_attr($accent_color); ?>;
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .mt-title { font-size: 24px; }
            .mt-message { font-size: 14px; }
            .mt-timer { gap: 10px; }
            .mt-timer-block { min-width: 60px; padding: 12px 8px; }
            .mt-timer-num { font-size: 22px; }
            .mt-gear svg { width: 48px; height: 48px; }
        }
    </style>
</head>
<body>

    <!-- Animated Background -->
    <div class="mt-bg">
        <div class="mt-bg-orb"></div>
        <div class="mt-bg-orb"></div>
        <div class="mt-bg-orb"></div>
    </div>

    <div class="mt-container">
        
        <!-- Logo -->
        <div class="mt-logo">
            <?php if ($custom_logo_url): ?>
                <img src="<?php echo esc_url($custom_logo_url); ?>" alt="<?php echo esc_attr($site_name); ?>">
            <?php elseif ($site_icon): ?>
                <img src="<?php echo esc_url($site_icon); ?>" alt="<?php echo esc_attr($site_name); ?>" style="width:56px;height:56px;border-radius:14px;">
            <?php else: ?>
                <span class="mt-logo-text"><?php echo esc_html(mb_strtoupper(mb_substr($site_name, 0, 1))); ?></span>
            <?php endif; ?>
        </div>

        <!-- Gear Icon -->
        <div class="mt-gear">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
        </div>

        <!-- Title & Message -->
        <h1 class="mt-title"><?php echo esc_html($title); ?></h1>
        <p class="mt-message"><?php echo nl2br(esc_html($message)); ?></p>

        <?php if ($show_timer && !empty($timer_date)): ?>
        <!-- Countdown Timer -->
        <div class="mt-timer" id="mtTimer">
            <div class="mt-timer-block">
                <div class="mt-timer-num" id="mtDays">00</div>
                <div class="mt-timer-label">Hari</div>
            </div>
            <div class="mt-timer-block">
                <div class="mt-timer-num" id="mtHours">00</div>
                <div class="mt-timer-label">Jam</div>
            </div>
            <div class="mt-timer-block">
                <div class="mt-timer-num" id="mtMins">00</div>
                <div class="mt-timer-label">Menit</div>
            </div>
            <div class="mt-timer-block">
                <div class="mt-timer-num" id="mtSecs">00</div>
                <div class="mt-timer-label">Detik</div>
            </div>
        </div>
        <script>
        (function() {
            var target = new Date('<?php echo esc_js($timer_date); ?>').getTime();
            function update() {
                var now = Date.now();
                var diff = Math.max(0, target - now);
                var d = Math.floor(diff / 86400000);
                var h = Math.floor((diff % 86400000) / 3600000);
                var m = Math.floor((diff % 3600000) / 60000);
                var s = Math.floor((diff % 60000) / 1000);
                document.getElementById('mtDays').textContent = String(d).padStart(2, '0');
                document.getElementById('mtHours').textContent = String(h).padStart(2, '0');
                document.getElementById('mtMins').textContent = String(m).padStart(2, '0');
                document.getElementById('mtSecs').textContent = String(s).padStart(2, '0');
                if (diff > 0) requestAnimationFrame(function() { setTimeout(update, 1000); });
                else window.location.reload();
            }
            update();
        })();
        </script>
        <?php endif; ?>

        <!-- Progress Bar -->
        <div class="mt-progress">
            <div class="mt-progress-bar"></div>
        </div>

        <!-- Footer -->
        <div class="mt-footer">
            <span>&copy; <?php echo date('Y'); ?> <?php echo esc_html($site_name); ?></span>
        </div>
    </div>

</body>
</html>
    <?php
    exit;
}
add_action('template_redirect', 'flavor_maintenance_mode');


// ============================
// ADMIN BAR NOTICE
// ============================
function flavor_maintenance_admin_notice() {
    if (!get_theme_mod('flavor_maintenance_mode', false)) return;
    if (!current_user_can('manage_options')) return;
    
    // Show notice in admin bar
    ?>
    <style>
        #wpadminbar { border-bottom: 3px solid #f59e0b !important; }
        .mt-admin-notice {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #1e1b4b;
            color: #e2e8f0;
            padding: 12px 20px;
            border-radius: 10px;
            font-size: 13px;
            font-family: -apple-system, BlinkMacSystemFont, sans-serif;
            z-index: 99999;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            border: 1px solid rgba(99, 102, 241, 0.3);
            animation: mtNoticeIn 0.3s ease;
        }
        .mt-admin-notice-dot {
            width: 8px; height: 8px;
            background: #f59e0b;
            border-radius: 50%;
            animation: mtPulse 2s ease infinite;
        }
        @keyframes mtPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        @keyframes mtNoticeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .mt-admin-notice a {
            color: #818cf8;
            text-decoration: none;
            font-weight: 600;
        }
        .mt-admin-notice-close {
            background: none; border: none; color: #64748b;
            cursor: pointer; font-size: 16px; padding: 0 0 0 8px;
        }
    </style>
    <div class="mt-admin-notice" id="mtAdminNotice">
        <span class="mt-admin-notice-dot"></span>
        <span>🔧 <strong>Maintenance Mode Aktif</strong> — Pengunjung tidak bisa akses website. <a href="<?php echo admin_url('customize.php?autofocus[section]=flavor_maintenance'); ?>">Nonaktifkan</a></span>
        <button class="mt-admin-notice-close" onclick="document.getElementById('mtAdminNotice').remove()">✕</button>
    </div>
    <?php
}
add_action('wp_footer', 'flavor_maintenance_admin_notice');
add_action('admin_footer', 'flavor_maintenance_admin_notice');


// ============================
// CUSTOMIZER SETTINGS
// ============================
function flavor_maintenance_customizer($wp_customize) {
    
    // Section
    $wp_customize->add_section('flavor_maintenance', array(
        'title'    => '🔧 Maintenance Mode',
        'priority' => 200,
    ));
    
    // Enable/Disable
    $wp_customize->add_setting('flavor_maintenance_mode', array(
        'default'           => false,
        'sanitize_callback' => 'wp_validate_boolean',
    ));
    $wp_customize->add_control('flavor_maintenance_mode', array(
        'label'       => 'Aktifkan Maintenance Mode',
        'description' => 'Jika aktif, hanya admin yang bisa akses website. Pengunjung akan melihat halaman maintenance.',
        'section'     => 'flavor_maintenance',
        'type'        => 'checkbox',
    ));
    
    // Title
    $wp_customize->add_setting('flavor_maintenance_title', array(
        'default'           => 'Sedang Maintenance',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('flavor_maintenance_title', array(
        'label'   => 'Judul',
        'section' => 'flavor_maintenance',
        'type'    => 'text',
    ));
    
    // Message
    $wp_customize->add_setting('flavor_maintenance_message', array(
        'default'           => 'Website sedang dalam perbaikan. Kami akan segera kembali!',
        'sanitize_callback' => 'sanitize_textarea_field',
    ));
    $wp_customize->add_control('flavor_maintenance_message', array(
        'label'   => 'Pesan',
        'section' => 'flavor_maintenance',
        'type'    => 'textarea',
    ));
    
    // Background Color
    $wp_customize->add_setting('flavor_maintenance_bg_color', array(
        'default'           => '#0f172a',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'flavor_maintenance_bg_color', array(
        'label'   => 'Warna Background',
        'section' => 'flavor_maintenance',
    )));
    
    // Accent Color
    $wp_customize->add_setting('flavor_maintenance_accent', array(
        'default'           => '#6366f1',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'flavor_maintenance_accent', array(
        'label'   => 'Warna Aksen',
        'section' => 'flavor_maintenance',
    )));
    
    // Show Timer
    $wp_customize->add_setting('flavor_maintenance_timer', array(
        'default'           => false,
        'sanitize_callback' => 'wp_validate_boolean',
    ));
    $wp_customize->add_control('flavor_maintenance_timer', array(
        'label'       => 'Tampilkan Countdown Timer',
        'section'     => 'flavor_maintenance',
        'type'        => 'checkbox',
    ));
    
    // Timer Date
    $wp_customize->add_setting('flavor_maintenance_timer_date', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('flavor_maintenance_timer_date', array(
        'label'       => 'Target Waktu Selesai',
        'description' => 'Format: 2026-02-15T12:00:00 (YYYY-MM-DDTHH:MM:SS)',
        'section'     => 'flavor_maintenance',
        'type'        => 'datetime-local',
    ));
}
add_action('customize_register', 'flavor_maintenance_customizer');
