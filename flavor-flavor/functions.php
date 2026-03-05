<?php
/**
 * Flavor Flavor Theme Functions
 *
 * @package Flavor_Flavor
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Custom Comments System
require_once get_template_directory() . '/inc/custom-comments.php';

// Post Reactions System
require_once get_template_directory() . '/inc/post-reactions.php';

// Auth System (Login/Register/Forgot Password)
require_once get_template_directory() . '/inc/auth-system.php';

// Bookmark System (Server-side)
require_once get_template_directory() . '/inc/bookmark-system.php';

// Reading History System (Server-side)
require_once get_template_directory() . '/inc/reading-history.php';

// Maintenance Mode
require_once get_template_directory() . '/inc/maintenance-mode.php';

/**
 * Sanitize HTML/Script Code for Tracking
 * Customizer is admin-only, so we trust the input
 */
function flavor_sanitize_html_code($input) {
    // Return raw input - Customizer is only accessible by admins
    return $input;
}

// Include SEO Functions
require_once get_template_directory() . '/inc/seo.php';

// Include HTML Minification
require_once get_template_directory() . '/inc/minify.php';

// Admin Options Page
require_once get_template_directory() . '/inc/admin-options.php';
require_once get_template_directory() . '/inc/admin-options-tabs.php';

// Manhwa Core (native CPT, taxonomy, meta boxes, chapter routing)
require_once get_template_directory() . '/inc/manhwa-core.php';

// Manhwa CMS Admin Page
require_once get_template_directory() . '/inc/manhwa-admin.php';
require_once get_template_directory() . '/inc/manhwa-export-import.php';

/**
 * Theme Setup
 */
function flavor_theme_setup() {
    // Add theme support
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', array(
        'height'      => 100,
        'width'       => 300,
        'flex-height' => true,
        'flex-width'  => true,
    ));
    add_theme_support('html5', array(
        'search-form',
        'comment-form', 
        'comment-list',
        'gallery',
        'caption',
    ));
    add_theme_support('customize-selective-refresh-widgets');
    
    // Add image sizes
    add_image_size('manga-thumb', 300, 400, true);
    add_image_size('manga-cover', 500, 700, true);
    add_image_size('chapter-thumb', 150, 200, true);
    
    // Register navigation menus
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'flavor-flavor'),
        'footer'  => __('Footer Menu', 'flavor-flavor'),
    ));
}
add_action('after_setup_theme', 'flavor_theme_setup');

/**
 * Register Widget Areas (Sidebars)
 */
function flavor_widgets_init() {
    // Main Sidebar
    register_sidebar(array(
        'name'          => __('Sidebar', 'flavor-flavor'),
        'id'            => 'sidebar-1',
        'description'   => __('Add widgets here to appear in sidebar.', 'flavor-flavor'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));
    
    // Sidebar Ads
    register_sidebar(array(
        'name'          => __('Sidebar Ads', 'flavor-flavor'),
        'id'            => 'sidebar-ads',
        'description'   => __('Add advertisement widgets here.', 'flavor-flavor'),
        'before_widget' => '<div id="%1$s" class="widget fv-sponsor %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));
    
    // Header Ads
    register_sidebar(array(
        'name'          => __('Header Ads', 'flavor-flavor'),
        'id'            => 'header-ads',
        'description'   => __('Header advertisement area.', 'flavor-flavor'),
        'before_widget' => '<div id="%1$s" class="widget fv-header-sponsor %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '',
        'after_title'   => '',
    ));
    
    // Footer Ads
    register_sidebar(array(
        'name'          => __('Footer Ads', 'flavor-flavor'),
        'id'            => 'footer-ads',
        'description'   => __('Footer advertisement area.', 'flavor-flavor'),
        'before_widget' => '<div id="%1$s" class="widget footer-ad-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '',
        'after_title'   => '',
    ));
    
    // Footer 1
    register_sidebar(array(
        'name'          => __('Footer 1', 'flavor-flavor'),
        'id'            => 'footer-1',
        'description'   => __('First footer widget area.', 'flavor-flavor'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ));
    
    // Footer 2
    register_sidebar(array(
        'name'          => __('Footer 2', 'flavor-flavor'),
        'id'            => 'footer-2',
        'description'   => __('Second footer widget area.', 'flavor-flavor'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ));
    
    // Footer 3
    register_sidebar(array(
        'name'          => __('Footer 3', 'flavor-flavor'),
        'id'            => 'footer-3',
        'description'   => __('Third footer widget area.', 'flavor-flavor'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ));
}
add_action('widgets_init', 'flavor_widgets_init');

/**
 * Fix front page pagination - prevent 404 on paginated pages
 */
function flavor_fix_front_page_pagination($query) {
    if (!is_admin() && $query->is_main_query()) {
        // Handle front page with custom query
        if (is_front_page() || is_home()) {
            // Get paged from query string
            $paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
            if ($paged > 0) {
                $query->set('paged', $paged);
                // Set post type to manhwa for main query
                $query->set('post_type', 'manhwa');
                $query->set('posts_per_page', 18);
            }
        }
    }
}
add_action('pre_get_posts', 'flavor_fix_front_page_pagination');

/**
 * Prevent 404 on front page pagination
 */
function flavor_prevent_404_on_paged() {
    if ((is_front_page() || is_home()) && isset($_GET['paged'])) {
        global $wp_query;
        $wp_query->is_404 = false;
        status_header(200);
    }
}
add_action('wp', 'flavor_prevent_404_on_paged');

/**
 * Prevent canonical redirect on front page pagination
 */
function flavor_disable_redirect_on_paged($redirect_url) {
    if (isset($_GET['paged']) && (is_front_page() || is_home())) {
        return false; // Disable redirect
    }
    return $redirect_url;
}
add_filter('redirect_canonical', 'flavor_disable_redirect_on_paged');

/**
 * Live Search AJAX Handler
 */
function flavor_live_search() {
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    
    if (strlen($search) < 2) {
        wp_send_json([]);
        return;
    }
    
    $args = array(
        'post_type'      => 'manhwa',
        'posts_per_page' => 8,
        's'              => $search,
        'post_status'    => 'publish',
    );
    
    $query = new WP_Query($args);
    $results = [];
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $meta = flavor_get_manhwa_meta(get_the_ID());
            $chapters = flavor_get_manhwa_chapters(get_the_ID(), 1);
            $latest_chapter = !empty($chapters) ? $chapters[0] : null;
            
            $results[] = [
                'id'        => get_the_ID(),
                'title'     => get_the_title(),
                'url'       => get_permalink(),
                'thumbnail' => get_the_post_thumbnail_url(get_the_ID(), 'thumbnail') ?: get_template_directory_uri() . '/assets/images/no-image.svg',
                'type'      => $meta['type'] ?? '',
                'status'    => $meta['status'] ?? '',
                'chapter'   => $latest_chapter ? flavor_format_chapter_number($latest_chapter['number'] ?? $latest_chapter['title'] ?? '') : '',
            ];
        }
        wp_reset_postdata();
    }
    
    wp_send_json($results);
}
add_action('wp_ajax_flavor_live_search', 'flavor_live_search');
add_action('wp_ajax_nopriv_flavor_live_search', 'flavor_live_search');

/**
 * Enqueue Scripts and Styles
 */
function flavor_enqueue_assets() {
    // Google Fonts
    wp_enqueue_style('flavor-google-fonts', 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap', array(), null);
    
    // Theme Styles
    wp_enqueue_style('flavor-style', get_stylesheet_uri(), array(), wp_get_theme()->get('Version'));
    
    // Theme Scripts
    wp_enqueue_script('flavor-theme', get_template_directory_uri() . '/assets/js/theme.js', array('jquery'), wp_get_theme()->get('Version'), true);
    
    // Localize script
    wp_localize_script('flavor-theme', 'flavorData', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('flavor_nonce'),
        'homeUrl' => home_url('/'),
        'strings' => array(
            'bookmarkAdded'   => __('Added to Bookmarks!', 'flavor-flavor'),
            'bookmarkRemoved' => __('Removed from Bookmarks!', 'flavor-flavor'),
            'noBookmarks'     => __('No bookmarks yet', 'flavor-flavor'),
        ),
    ));
}
add_action('wp_enqueue_scripts', 'flavor_enqueue_assets');



/**
 * Get manga status badge class
 */
function flavor_get_status_class($status) {
    $status = strtolower($status);
    $classes = array(
        'completed' => 'status-completed',
        'ongoing'   => 'status-ongoing',
        'dropped'   => 'status-dropped',
        'hiatus'    => 'status-hiatus',
    );
    return isset($classes[$status]) ? $classes[$status] : 'status-ongoing';
}

/**
 * Get manga type badge class
 */
function flavor_get_type_class($type) {
    $type = strtolower($type);
    $classes = array(
        'manga'  => 'type-manga',
        'manhwa' => 'type-manhwa',
        'manhua' => 'type-manhua',
    );
    return isset($classes[$type]) ? $classes[$type] : 'type-manga';
}

/**
 * Get formatted chapter number
 * @param string $chapter_num Chapter number/title
 * @param bool $is_last_chapter Whether this is the last chapter
 * @param string $status Manhwa status (completed, ongoing, etc)
 */
function flavor_format_chapter_number($chapter_num, $is_last_chapter = false, $status = '') {
    // Clean the chapter number first
    $chapter_num = trim($chapter_num);
    
    // Remove existing 'end' suffix to normalize, we'll add it back if needed
    $has_end = preg_match('/[\s\-:|\~]*(end|final|tamat|selesai|the\s*end)$/i', $chapter_num);
    $chapter_num = preg_replace('/[\s\-:|\~]*(end|final|tamat|selesai|the\s*end)$/i', '', $chapter_num);
    $chapter_num = rtrim($chapter_num, ' -:~|');
    
    // Format the chapter number
    $formatted = $chapter_num;
    
    // If already contains "Chapter", replace with "Ch"
    if (stripos($chapter_num, 'chapter') !== false) {
        $formatted = str_ireplace('chapter', 'Ch', $chapter_num);
    }
    // If just a number, add "Ch" prefix
    elseif (is_numeric($chapter_num)) {
        $formatted = 'Ch ' . $chapter_num;
    }
    
    // Add "End" suffix if this is the last chapter AND status is completed, OR if original had 'end'
    $is_complete = in_array(strtolower($status), ['completed', 'complete', 'tamat', 'end']);
    if (($is_last_chapter && $is_complete) || $has_end) {
        $formatted .= ' <span class="chapter-end-badge">End</span>';
    }
    
    return $formatted;
}

/**
 * Get time ago string
 */
function flavor_time_ago($datetime) {
    $time = strtotime($datetime);
    if (!$time) {
        return $datetime;
    }
    
    $diff = time() - $time;
    
    if ($diff < 60) {
        return 'Baru saja';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' menit lalu';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' jam lalu';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' hari lalu';
    } elseif ($diff < 2592000) {
        // Kurang dari 30 hari - tampilkan minggu
        $weeks = floor($diff / 604800);
        return $weeks . ' minggu lalu';
    } else {
        // Format tanggal pendek: "29 Agu 2024"
        return date_i18n('j M Y', $time);
    }
}

/**
 * Custom excerpt length
 */
function flavor_excerpt_length($length) {
    return 30;
}
add_filter('excerpt_length', 'flavor_excerpt_length');

/**
 * Custom excerpt more
 */
function flavor_excerpt_more($more) {
    return '...';
}
add_filter('excerpt_more', 'flavor_excerpt_more');

/**
 * Get manhwa chapters
 */
function flavor_get_manhwa_chapters($post_id, $limit = -1) {
    $chapters = get_post_meta($post_id, '_manhwa_chapters', true);
    
    if (!is_array($chapters)) {
        return array();
    }
    
    // Sort by chapter number (descending - largest first)
    usort($chapters, function($a, $b) {
        // Extract numbers from chapter number/title
        $num_a = flavor_extract_chapter_number($a['number'] ?? $a['title'] ?? '0');
        $num_b = flavor_extract_chapter_number($b['number'] ?? $b['title'] ?? '0');
        
        // Compare as floats for proper numeric sorting (descending)
        if ($num_b > $num_a) return 1;
        if ($num_b < $num_a) return -1;
        return 0;
    });
    
    if ($limit > 0) {
        $chapters = array_slice($chapters, 0, $limit);
    }
    
    return $chapters;
}

/**
 * Extract chapter number from string
 * Handles formats like: "Chapter 22", "Ch 01", "01", "165", "Chapter 22.5", etc.
 */
function flavor_extract_chapter_number($str) {
    // First, try to get just numeric value
    if (is_numeric($str)) {
        return floatval($str);
    }
    
    // Extract numbers (including decimals) from string
    if (preg_match('/(\d+(?:\.\d+)?)/', $str, $matches)) {
        return floatval($matches[1]);
    }
    
    return 0;
}

/**
 * Get manhwa metadata
 */
function flavor_get_manhwa_meta($post_id) {
    return array(
        'status'       => get_post_meta($post_id, '_manhwa_status', true) ?: 'Ongoing',
        'type'         => get_post_meta($post_id, '_manhwa_type', true) ?: 'Manhwa',
        'author'       => get_post_meta($post_id, '_manhwa_author', true) ?: '-',
        'artist'       => get_post_meta($post_id, '_manhwa_artist', true) ?: '-',
        'rating'       => get_post_meta($post_id, '_manhwa_rating', true) ?: '0',
        'release_year' => get_post_meta($post_id, '_manhwa_release_year', true) ?: '',
        'alt_title'    => get_post_meta($post_id, '_manhwa_alternative_title', true) ?: '',
    );
}

/**
 * Display star rating
 */
function flavor_star_rating($rating) {
    $rating = floatval($rating);
    $full_stars = floor($rating);
    $half_star = ($rating - $full_stars) >= 0.5;
    $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
    
    $output = '<div class="star-rating">';
    
    for ($i = 0; $i < $full_stars; $i++) {
        $output .= '<svg class="star star-full" viewBox="0 0 24 24"><path fill="currentColor" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>';
    }
    
    if ($half_star) {
        $output .= '<svg class="star star-half" viewBox="0 0 24 24"><path fill="currentColor" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>';
    }
    
    for ($i = 0; $i < $empty_stars; $i++) {
        $output .= '<svg class="star star-empty" viewBox="0 0 24 24"><path fill="currentColor" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>';
    }
    
    $output .= '</div>';
    
    return $output;
}

/**
 * Get latest updated manhwa
 */
function flavor_get_latest_manhwa($count = 12) {
    return get_posts(array(
        'post_type'      => 'manhwa',
        'posts_per_page' => $count,
        'orderby'        => 'modified',
        'order'          => 'DESC',
    ));
}

/**
 * Get popular manhwa
 */
function flavor_get_popular_manhwa($count = 10) {
    return get_posts(array(
        'post_type'      => 'manhwa',
        'posts_per_page' => $count,
        'meta_key'       => '_manhwa_views',
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
    ));
}
/**
 * Increment manhwa view count
 */
function flavor_increment_views($post_id) {
    $views = get_post_meta($post_id, '_manhwa_views', true);
    $views = $views ? intval($views) + 1 : 1;
    update_post_meta($post_id, '_manhwa_views', $views);
}

/**
 * Normalisasi key chapter agar konsisten
 * Mengekstrak angka saja dari identifier chapter
 * Contoh: "Chapter 170" -> "170", "Ch 01" -> "1", "170" -> "170"
 * 
 * @param string $chapter_key Identifier chapter (bisa berupa "Chapter 170", "170", dll)
 * @return string Key yang sudah dinormalisasi (angka saja)
 */
function flavor_normalize_chapter_key($chapter_key) {
    $chapter_key = trim($chapter_key);
    // Ekstrak angka (termasuk desimal) dari string
    if (preg_match('/(\d+(?:\.\d+)?)/', $chapter_key, $matches)) {
        return $matches[1];
    }
    // Fallback: lowercase dari input asli
    return strtolower($chapter_key);
}

/**
 * Increment chapter view count
 * Menyimpan view per chapter di _chapter_views meta sebagai associative array
 * 
 * @param int    $post_id     ID post manhwa
 * @param string $chapter_key Identifier chapter (number/title)
 */
function flavor_increment_chapter_views($post_id, $chapter_key) {
    $chapter_key = flavor_normalize_chapter_key($chapter_key);
    if (empty($chapter_key)) return;
    
    $chapter_views = get_post_meta($post_id, '_chapter_views', true);
    if (!is_array($chapter_views)) {
        $chapter_views = array();
    }
    
    $current = isset($chapter_views[$chapter_key]) ? intval($chapter_views[$chapter_key]) : 0;
    $chapter_views[$chapter_key] = $current + 1;
    
    update_post_meta($post_id, '_chapter_views', $chapter_views);
}

/**
 * Ambil jumlah view untuk chapter tertentu
 * 
 * @param int    $post_id     ID post manhwa
 * @param string $chapter_key Identifier chapter (number/title)
 * @return int   Jumlah view
 */
function flavor_get_chapter_views($post_id, $chapter_key) {
    $chapter_key = flavor_normalize_chapter_key($chapter_key);
    $chapter_views = get_post_meta($post_id, '_chapter_views', true);
    
    if (!is_array($chapter_views) || !isset($chapter_views[$chapter_key])) {
        return 0;
    }
    
    return intval($chapter_views[$chapter_key]);
}

/**
 * Ambil semua view chapter untuk sebuah manhwa
 * 
 * @param int $post_id ID post manhwa
 * @return array Associative array chapter_key => view_count
 */
function flavor_get_all_chapter_views($post_id) {
    $chapter_views = get_post_meta($post_id, '_chapter_views', true);
    return is_array($chapter_views) ? $chapter_views : array();
}

/**
 * Format jumlah view untuk ditampilkan (contoh: 1.2K, 3.5M)
 * 
 * @param int $views Jumlah view mentah
 * @return string Jumlah view yang diformat
 */
function flavor_format_views($views) {
    $views = intval($views);
    if ($views >= 1000000) {
        return round($views / 1000000, 1) . 'M';
    } elseif ($views >= 1000) {
        return round($views / 1000, 1) . 'K';
    }
    return (string) $views;
}


/**
 * Add body classes
 */
function flavor_body_classes($classes) {
    if (is_singular('manhwa')) {
        $classes[] = 'single-manhwa-page';
    }
    
    if (is_post_type_archive('manhwa') || is_tax('manhwa_genre')) {
        $classes[] = 'manhwa-archive-page';
    }
    
    // Add has-ticker class if ticker is enabled
    $ticker_enable = get_theme_mod('ticker_enable', false);
    $ticker_text = get_theme_mod('ticker_text', '');
    if ($ticker_enable && !empty($ticker_text)) {
        $classes[] = 'has-ticker';
    }
    
    // Add no-sidebar class if sidebar is disabled
    if (function_exists('flavor_is_sidebar_enabled') && !flavor_is_sidebar_enabled()) {
        $classes[] = 'no-sidebar';
    }
    
    return $classes;
}
add_filter('body_class', 'flavor_body_classes');

/**
 * Customize login logo
 */
function flavor_login_logo() {
    $custom_logo_id = get_theme_mod('custom_logo');
    if ($custom_logo_id) {
        $logo = wp_get_attachment_image_src($custom_logo_id, 'full');
        ?>
        <style type="text/css">
            #login h1 a {
                background-image: url(<?php echo esc_url($logo[0]); ?>);
                background-size: contain;
                width: 100%;
            }
        </style>
        <?php
    }
}
add_action('login_enqueue_scripts', 'flavor_login_logo');

/**
 * AJAX: Get bookmark data
 */
function flavor_ajax_get_bookmarks() {
    check_ajax_referer('flavor_nonce', 'nonce');
    
    // Bookmarks are handled client-side with localStorage
    wp_send_json_success(array('message' => 'Bookmarks are stored in browser'));
}
add_action('wp_ajax_flavor_get_bookmarks', 'flavor_ajax_get_bookmarks');
add_action('wp_ajax_nopriv_flavor_get_bookmarks', 'flavor_ajax_get_bookmarks');

/**
 * Add Customizer settings
 */
function flavor_customize_register($wp_customize) {
    // Theme Colors Section
    $wp_customize->add_section('flavor_colors', array(
        'title'    => __('Theme Colors', 'flavor-flavor'),
        'priority' => 30,
    ));
    
    // Primary Color
    $wp_customize->add_setting('primary_color', array(
        'default'           => '#ff5722',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'refresh',
    ));
    
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'primary_color', array(
        'label'    => __('Primary Color', 'flavor-flavor'),
        'section'  => 'flavor_colors',
        'settings' => 'primary_color',
    )));
    
    // Secondary Color
    $wp_customize->add_setting('secondary_color', array(
        'default'           => '#ff5722',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'refresh',
    ));
    
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'secondary_color', array(
        'label'    => __('Secondary Color', 'flavor-flavor'),
        'section'  => 'flavor_colors',
        'settings' => 'secondary_color',
    )));
    
    // =========================================
    // Announcement Section
    // =========================================
    $wp_customize->add_section('flavor_announcement', array(
        'title'       => __('Announcements', 'flavor-flavor'),
        'description' => __('Configure announcement bar and ticker text', 'flavor-flavor'),
        'priority'    => 31,
    ));
    
    // --- Announcement Bar Settings ---
    $wp_customize->add_setting('announcement_bar_enable', array(
        'default'           => false,
        'sanitize_callback' => 'wp_validate_boolean',
    ));
    
    $wp_customize->add_control('announcement_bar_enable', array(
        'label'   => __('Enable Announcement Bar', 'flavor-flavor'),
        'description' => __('Displays below hero slider on homepage', 'flavor-flavor'),
        'section' => 'flavor_announcement',
        'type'    => 'checkbox',
    ));
    
    $wp_customize->add_setting('announcement_bar_title', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    
    $wp_customize->add_control('announcement_bar_title', array(
        'label'       => __('Announcement Title', 'flavor-flavor'),
        'description' => __('Title shown at top of announcement box', 'flavor-flavor'),
        'section'     => 'flavor_announcement',
        'type'        => 'text',
    ));
    
    $wp_customize->add_setting('announcement_bar_text', array(
        'default'           => '',
        'sanitize_callback' => 'wp_kses_post',
    ));
    
    $wp_customize->add_control('announcement_bar_text', array(
        'label'       => __('Announcement Content', 'flavor-flavor'),
        'description' => __('HTML allowed. Use &lt;p&gt; for paragraphs, &lt;a&gt; for links, &lt;br&gt; for line breaks.', 'flavor-flavor'),
        'section'     => 'flavor_announcement',
        'type'        => 'textarea',
    ));
    
    $wp_customize->add_setting('announcement_bar_bg', array(
        'default'           => '#ff5722',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'announcement_bar_bg', array(
        'label'   => __('Announcement Bar Background', 'flavor-flavor'),
        'section' => 'flavor_announcement',
    )));
    
    $wp_customize->add_setting('announcement_bar_dismissible', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ));
    
    $wp_customize->add_control('announcement_bar_dismissible', array(
        'label'   => __('Allow users to close announcement', 'flavor-flavor'),
        'section' => 'flavor_announcement',
        'type'    => 'checkbox',
    ));
    
    // --- Ticker Settings ---
    $wp_customize->add_setting('ticker_enable', array(
        'default'           => false,
        'sanitize_callback' => 'wp_validate_boolean',
    ));
    
    $wp_customize->add_control('ticker_enable', array(
        'label'   => __('Enable Ticker/Running Text', 'flavor-flavor'),
        'section' => 'flavor_announcement',
        'type'    => 'checkbox',
    ));
    
    $wp_customize->add_setting('ticker_label', array(
        'default'           => 'INFO',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    
    $wp_customize->add_control('ticker_label', array(
        'label'       => __('Ticker Label', 'flavor-flavor'),
        'description' => __('E.g., INFO, NEWS, UPDATE', 'flavor-flavor'),
        'section'     => 'flavor_announcement',
        'type'        => 'text',
    ));
    
    $wp_customize->add_setting('ticker_text', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    
    $wp_customize->add_control('ticker_text', array(
        'label'       => __('Ticker Text', 'flavor-flavor'),
        'description' => __('Text that scrolls/runs. Keep it short.', 'flavor-flavor'),
        'section'     => 'flavor_announcement',
        'type'        => 'text',
    ));
    
    $wp_customize->add_setting('ticker_speed', array(
        'default'           => 30,
        'sanitize_callback' => 'absint',
    ));
    
    $wp_customize->add_control('ticker_speed', array(
        'label'       => __('Ticker Speed (seconds)', 'flavor-flavor'),
        'description' => __('Duration for one complete scroll', 'flavor-flavor'),
        'section'     => 'flavor_announcement',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 10,
            'max'  => 120,
            'step' => 5,
        ),
    ));
    
    $wp_customize->add_setting('ticker_link', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ));
    
    $wp_customize->add_control('ticker_link', array(
        'label'       => __('Ticker Link URL (optional)', 'flavor-flavor'),
        'section'     => 'flavor_announcement',
        'type'        => 'url',
    ));
    
    // =========================================
    // Hero Slider Section
    // =========================================
    $wp_customize->add_section('flavor_hero_slider', array(
        'title'       => __('Hero Slider', 'flavor-flavor'),
        'description' => __('Configure the hero slider on the homepage', 'flavor-flavor'),
        'priority'    => 30,
    ));
    
    // Enable/disable slider
    $wp_customize->add_setting('flavor_hero_slider_enabled', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ));
    
    $wp_customize->add_control('flavor_hero_slider_enabled', array(
        'label'   => __('Enable Hero Slider', 'flavor-flavor'),
        'section' => 'flavor_hero_slider',
        'type'    => 'checkbox',
    ));
    
    // Slider Mode Setting
    $wp_customize->add_setting('flavor_hero_slider_mode', array(
        'default'           => 'views',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    
    $wp_customize->add_control('flavor_hero_slider_mode', array(
        'label'    => __('Slider Mode', 'flavor-flavor'),
        'section'  => 'flavor_hero_slider',
        'type'     => 'select',
        'choices'  => array(
            'manual'  => __('Manual (Featured)', 'flavor-flavor'),
            'views'   => __('Most Viewed', 'flavor-flavor'),
            'rating'  => __('Highest Rated', 'flavor-flavor'),
            'latest'  => __('Latest Updated', 'flavor-flavor'),
        ),
    ));
    
    // Number of slides
    $wp_customize->add_setting('flavor_hero_slider_count', array(
        'default'           => 8,
        'sanitize_callback' => 'absint',
    ));
    
    $wp_customize->add_control('flavor_hero_slider_count', array(
        'label'       => __('Number of Slides', 'flavor-flavor'),
        'section'     => 'flavor_hero_slider',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 3,
            'max'  => 15,
            'step' => 1,
        ),
    ));
    
    // Auto-play speed
    $wp_customize->add_setting('flavor_hero_slider_speed', array(
        'default'           => 5000,
        'sanitize_callback' => 'absint',
    ));
    
    $wp_customize->add_control('flavor_hero_slider_speed', array(
        'label'       => __('Auto-play Speed (ms)', 'flavor-flavor'),
        'section'     => 'flavor_hero_slider',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 2000,
            'max'  => 10000,
            'step' => 500,
        ),
    ));
    
    // =========================================
    // Contact Settings Section
    // =========================================
    $wp_customize->add_section('flavor_contact', array(
        'title'       => __('Contact Settings', 'flavor-flavor'),
        'description' => __('Configure contact information for the contact page', 'flavor-flavor'),
        'priority'    => 32,
    ));
    
    // Contact Email
    $wp_customize->add_setting('contact_email', array(
        'default'           => get_option('admin_email'),
        'sanitize_callback' => 'sanitize_email',
    ));
    
    $wp_customize->add_control('contact_email', array(
        'label'   => __('Email Address', 'flavor-flavor'),
        'section' => 'flavor_contact',
        'type'    => 'email',
    ));
    
    // WhatsApp Number
    $wp_customize->add_setting('contact_whatsapp', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    
    $wp_customize->add_control('contact_whatsapp', array(
        'label'       => __('WhatsApp Number', 'flavor-flavor'),
        'description' => __('Include country code, e.g., +628123456789', 'flavor-flavor'),
        'section'     => 'flavor_contact',
        'type'        => 'text',
    ));
    
    // Telegram Username
    $wp_customize->add_setting('contact_telegram', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    
    $wp_customize->add_control('contact_telegram', array(
        'label'       => __('Telegram Username', 'flavor-flavor'),
        'description' => __('Without @ symbol', 'flavor-flavor'),
        'section'     => 'flavor_contact',
        'type'        => 'text',
    ));
    
    // =========================================
    // Social Media Section
    // =========================================
    $wp_customize->add_section('flavor_social', array(
        'title'       => __('Social Media', 'flavor-flavor'),
        'description' => __('Add your social media URLs. Leave empty to hide icon.', 'flavor-flavor'),
        'priority'    => 33,
    ));
    
    // Facebook
    $wp_customize->add_setting('social_facebook', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ));
    
    $wp_customize->add_control('social_facebook', array(
        'label'   => __('Facebook URL', 'flavor-flavor'),
        'section' => 'flavor_social',
        'type'    => 'url',
    ));
    
    // Instagram
    $wp_customize->add_setting('social_instagram', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ));
    
    $wp_customize->add_control('social_instagram', array(
        'label'   => __('Instagram URL', 'flavor-flavor'),
        'section' => 'flavor_social',
        'type'    => 'url',
    ));
    
    // Twitter/X
    $wp_customize->add_setting('social_twitter', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ));
    
    $wp_customize->add_control('social_twitter', array(
        'label'   => __('Twitter/X URL', 'flavor-flavor'),
        'section' => 'flavor_social',
        'type'    => 'url',
    ));
    
    // Discord
    $wp_customize->add_setting('social_discord', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ));
    
    $wp_customize->add_control('social_discord', array(
        'label'   => __('Discord Server URL', 'flavor-flavor'),
        'section' => 'flavor_social',
        'type'    => 'url',
    ));
    
    // TikTok
    $wp_customize->add_setting('social_tiktok', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ));
    
    $wp_customize->add_control('social_tiktok', array(
        'label'   => __('TikTok URL', 'flavor-flavor'),
        'section' => 'flavor_social',
        'type'    => 'url',
    ));
    
    // YouTube
    $wp_customize->add_setting('social_youtube', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ));
    
    $wp_customize->add_control('social_youtube', array(
        'label'   => __('YouTube URL', 'flavor-flavor'),
        'section' => 'flavor_social',
        'type'    => 'url',
    ));
    
    // Telegram Channel/Group
    $wp_customize->add_setting('social_telegram', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ));
    
    $wp_customize->add_control('social_telegram', array(
        'label'   => __('Telegram Channel/Group URL', 'flavor-flavor'),
        'section' => 'flavor_social',
        'type'    => 'url',
    ));
    
    // =========================================
    // Advertising Slots Section
    // =========================================
    $wp_customize->add_section('flavor_ad_slots', array(
        'title'       => __('Ad Slots', 'flavor-flavor'),
        'description' => __('Configure advertising slots displayed on contact page', 'flavor-flavor'),
        'priority'    => 33,
    ));
    
    // Ad Slots (1-4)
    for ($i = 1; $i <= 4; $i++) {
        // Ad Slot Name
        $wp_customize->add_setting("ad_slot_{$i}_name", array(
            'default'           => '',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        
        $wp_customize->add_control("ad_slot_{$i}_name", array(
            'label'   => sprintf(__('Ad Slot %d - Name', 'flavor-flavor'), $i),
            'section' => 'flavor_ad_slots',
            'type'    => 'text',
        ));
        
        // Ad Slot Size
        $wp_customize->add_setting("ad_slot_{$i}_size", array(
            'default'           => '',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        
        $wp_customize->add_control("ad_slot_{$i}_size", array(
            'label'   => sprintf(__('Ad Slot %d - Size', 'flavor-flavor'), $i),
            'section' => 'flavor_ad_slots',
            'type'    => 'text',
            'description' => __('e.g., 728x90, 300x250', 'flavor-flavor'),
        ));
        
        // Ad Slot Description
        $wp_customize->add_setting("ad_slot_{$i}_desc", array(
            'default'           => '',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        
        $wp_customize->add_control("ad_slot_{$i}_desc", array(
            'label'   => sprintf(__('Ad Slot %d - Description', 'flavor-flavor'), $i),
            'section' => 'flavor_ad_slots',
            'type'    => 'text',
        ));
        
        // Ad Slot Status
        $wp_customize->add_setting("ad_slot_{$i}_status", array(
            'default'           => 'available',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        
        $wp_customize->add_control("ad_slot_{$i}_status", array(
            'label'   => sprintf(__('Ad Slot %d - Status', 'flavor-flavor'), $i),
            'section' => 'flavor_ad_slots',
            'type'    => 'select',
            'choices' => array(
                'available' => __('Available', 'flavor-flavor'),
                'sold'      => __('Sold', 'flavor-flavor'),
                'hidden'    => __('Hidden', 'flavor-flavor'),
            ),
        ));
    }
    
    // =========================================
    // Trending Widget Section
    // =========================================
    $wp_customize->add_section('flavor_trending', array(
        'title'       => __('Trending Widget', 'flavor-flavor'),
        'description' => __('Configure the Trending widget in sidebar', 'flavor-flavor'),
        'priority'    => 35,
    ));
    
    // Trending Title
    $wp_customize->add_setting('trending_title', array(
        'default'           => 'Trending',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));
    
    $wp_customize->add_control('trending_title', array(
        'label'       => __('Widget Title', 'flavor-flavor'),
        'section'     => 'flavor_trending',
        'type'        => 'text',
    ));
    
    // Trending Sort Type
    $wp_customize->add_setting('trending_sort', array(
        'default'           => 'views',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));
    
    $wp_customize->add_control('trending_sort', array(
        'label'       => __('Sort By', 'flavor-flavor'),
        'section'     => 'flavor_trending',
        'type'        => 'select',
        'choices'     => array(
            'views'   => __('Most Views', 'flavor-flavor'),
            'rating'  => __('Highest Rating', 'flavor-flavor'),
            'latest'  => __('Latest Added', 'flavor-flavor'),
            'random'  => __('Random', 'flavor-flavor'),
            'manual'  => __('Manual Selection', 'flavor-flavor'),
        ),
    ));
    
    // Number of Posts
    $wp_customize->add_setting('trending_count', array(
        'default'           => 5,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ));
    
    $wp_customize->add_control('trending_count', array(
        'label'       => __('Number of Manga', 'flavor-flavor'),
        'section'     => 'flavor_trending',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 1,
            'max'  => 20,
            'step' => 1,
        ),
    ));
    
    // Manual Selection (comma-separated post IDs)
    $wp_customize->add_setting('trending_manual_ids', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));
    
    $wp_customize->add_control('trending_manual_ids', array(
        'label'       => __('Manual Post IDs', 'flavor-flavor'),
        'description' => __('Enter comma-separated post IDs (e.g., 123, 456, 789). Only used when Sort By is set to Manual.', 'flavor-flavor'),
        'section'     => 'flavor_trending',
        'type'        => 'text',
    ));
    
    // Show Tabs
    $wp_customize->add_setting('trending_show_tabs', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
        'transport'         => 'refresh',
    ));
    
    $wp_customize->add_control('trending_show_tabs', array(
        'label'       => __('Show Tabs (Mingguan/Bulanan/Semua)', 'flavor-flavor'),
        'section'     => 'flavor_trending',
        'type'        => 'checkbox',
    ));
    
    // Show Genres
    $wp_customize->add_setting('trending_show_genres', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
        'transport'         => 'refresh',
    ));
    
    $wp_customize->add_control('trending_show_genres', array(
        'label'       => __('Show Genres', 'flavor-flavor'),
        'section'     => 'flavor_trending',
        'type'        => 'checkbox',
    ));
    
    // Show Rating
    $wp_customize->add_setting('trending_show_rating', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
        'transport'         => 'refresh',
    ));
    
    $wp_customize->add_control('trending_show_rating', array(
        'label'       => __('Show Rating Stars', 'flavor-flavor'),
        'section'     => 'flavor_trending',
        'type'        => 'checkbox',
    ));
    
    // ========================================
    // Comments Section
    // ========================================
    $wp_customize->add_section('flavor_comments', array(
        'title'       => __('Comments Settings', 'flavor-flavor'),
        'description' => __('Pengaturan sistem komentar untuk halaman manhwa.', 'flavor-flavor'),
        'priority'    => 95,
    ));
    
    // Enable Comments
    $wp_customize->add_setting('comments_enabled', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
        'transport'         => 'refresh',
    ));
    
    $wp_customize->add_control('comments_enabled', array(
        'label'       => __('Enable Comments', 'flavor-flavor'),
        'description' => __('Tampilkan bagian komentar di halaman manhwa.', 'flavor-flavor'),
        'section'     => 'flavor_comments',
        'type'        => 'checkbox',
    ));
    
    // Comment System Type
    $wp_customize->add_setting('comments_system', array(
        'default'           => 'wordpress',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));
    
    $wp_customize->add_control('comments_system', array(
        'label'       => __('Comment System', 'flavor-flavor'),
        'description' => __('Pilih sistem komentar yang digunakan.', 'flavor-flavor'),
        'section'     => 'flavor_comments',
        'type'        => 'select',
        'choices'     => array(
            'wordpress' => __('WordPress Comments', 'flavor-flavor'),
            'disqus'    => __('Disqus', 'flavor-flavor'),
        ),
    ));
    
    // Disqus Shortname
    $wp_customize->add_setting('disqus_shortname', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));
    
    $wp_customize->add_control('disqus_shortname', array(
        'label'       => __('Disqus Shortname', 'flavor-flavor'),
        'description' => __('Masukkan shortname Disqus Anda (dari disqus.com/admin). Kosongkan jika menggunakan WordPress Comments.', 'flavor-flavor'),
        'section'     => 'flavor_comments',
        'type'        => 'text',
    ));
    
    // Comments Disabled Message
    $wp_customize->add_setting('comments_disabled_message', array(
        'default'           => 'Komentar untuk sementara dinonaktifkan oleh admin. Silakan kembali lagi nanti.',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));
    
    $wp_customize->add_control('comments_disabled_message', array(
        'label'       => __('Pesan Komentar Nonaktif', 'flavor-flavor'),
        'description' => __('Pesan yang ditampilkan saat komentar dinonaktifkan.', 'flavor-flavor'),
        'section'     => 'flavor_comments',
        'type'        => 'textarea',
    ));
    
    // ========================================
    // Tracking & Analytics Section
    // ========================================
    $wp_customize->add_section('flavor_tracking', array(
        'title'       => __('Tracking & Analytics', 'flavor-flavor'),
        'description' => __('Tambahkan kode tracking seperti Histats, Google Analytics, atau kode tracking lainnya.', 'flavor-flavor'),
        'priority'    => 200,
    ));
    
    // Histats Counter Code
    $wp_customize->add_setting('tracking_histats_code', array(
        'default'           => '',
        'type'              => 'option',
        'capability'        => 'unfiltered_html',
        'sanitize_callback' => 'flavor_sanitize_html_code',
        'transport'         => 'refresh',
    ));
    
    $wp_customize->add_control('tracking_histats_code', array(
        'label'       => __('Histats Counter Code', 'flavor-flavor'),
        'description' => __('Paste kode Histats counter disini. Contoh: &lt;script type="text/javascript"&gt;...&lt;/script&gt;', 'flavor-flavor'),
        'section'     => 'flavor_tracking',
        'type'        => 'textarea',
    ));
    
    // Google Analytics Code
    $wp_customize->add_setting('tracking_google_analytics', array(
        'default'           => '',
        'sanitize_callback' => 'flavor_sanitize_html_code',
        'transport'         => 'refresh',
    ));
    
    $wp_customize->add_control('tracking_google_analytics', array(
        'label'       => __('Google Analytics / Tag Manager', 'flavor-flavor'),
        'description' => __('Paste kode Google Analytics atau GTM disini.', 'flavor-flavor'),
        'section'     => 'flavor_tracking',
        'type'        => 'textarea',
    ));
    
    // Custom Footer Scripts
    $wp_customize->add_setting('tracking_footer_scripts', array(
        'default'           => '',
        'sanitize_callback' => 'flavor_sanitize_html_code',
        'transport'         => 'refresh',
    ));
    
    $wp_customize->add_control('tracking_footer_scripts', array(
        'label'       => __('Custom Footer Scripts', 'flavor-flavor'),
        'description' => __('Tambahkan script custom lainnya yang akan dimuat di footer.', 'flavor-flavor'),
        'section'     => 'flavor_tracking',
        'type'        => 'textarea',
    ));
    
    // Custom Header Scripts
    $wp_customize->add_setting('tracking_header_scripts', array(
        'default'           => '',
        'sanitize_callback' => 'flavor_sanitize_html_code',
        'transport'         => 'refresh',
    ));
    
    $wp_customize->add_control('tracking_header_scripts', array(
        'label'       => __('Custom Header Scripts', 'flavor-flavor'),
        'description' => __('Tambahkan script custom yang akan dimuat di &lt;head&gt;.', 'flavor-flavor'),
        'section'     => 'flavor_tracking',
        'type'        => 'textarea',
    ));
    
    // =========================================
    // Sidebar Settings Section
    // =========================================
    $wp_customize->add_section('flavor_sidebar', array(
        'title'       => __('Sidebar Settings', 'flavor-flavor'),
        'description' => __('Enable or disable the sidebar on each page type.', 'flavor-flavor'),
        'priority'    => 35,
    ));
    
    // Global Sidebar Toggle
    $wp_customize->add_setting('sidebar_global_enable', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ));
    
    $wp_customize->add_control('sidebar_global_enable', array(
        'label'       => __('Enable Sidebar (Global)', 'flavor-flavor'),
        'description' => __('Master toggle. When unchecked, sidebar is hidden on ALL pages.', 'flavor-flavor'),
        'section'     => 'flavor_sidebar',
        'type'        => 'checkbox',
    ));
    
    // Sidebar on Homepage
    $wp_customize->add_setting('sidebar_homepage', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ));
    
    $wp_customize->add_control('sidebar_homepage', array(
        'label'   => __('Show Sidebar on Homepage', 'flavor-flavor'),
        'section' => 'flavor_sidebar',
        'type'    => 'checkbox',
    ));
    
    // Sidebar on Single Post (Manhwa Detail)
    $wp_customize->add_setting('sidebar_single', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ));
    
    $wp_customize->add_control('sidebar_single', array(
        'label'   => __('Show Sidebar on Single Post / Manhwa Detail', 'flavor-flavor'),
        'section' => 'flavor_sidebar',
        'type'    => 'checkbox',
    ));
    
    // Sidebar on Archive
    $wp_customize->add_setting('sidebar_archive', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ));
    
    $wp_customize->add_control('sidebar_archive', array(
        'label'   => __('Show Sidebar on Archive / Manhwa List', 'flavor-flavor'),
        'section' => 'flavor_sidebar',
        'type'    => 'checkbox',
    ));
    
    // Sidebar on Search
    $wp_customize->add_setting('sidebar_search', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ));
    
    $wp_customize->add_control('sidebar_search', array(
        'label'   => __('Show Sidebar on Search Results', 'flavor-flavor'),
        'section' => 'flavor_sidebar',
        'type'    => 'checkbox',
    ));
    
    // Sidebar on Taxonomy (Genre)
    $wp_customize->add_setting('sidebar_taxonomy', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ));
    
    $wp_customize->add_control('sidebar_taxonomy', array(
        'label'   => __('Show Sidebar on Genre / Taxonomy Pages', 'flavor-flavor'),
        'section' => 'flavor_sidebar',
        'type'    => 'checkbox',
    ));
}
add_action('customize_register', 'flavor_customize_register');

/**
 * Check if sidebar should be displayed for current page
 *
 * @return bool
 */
function flavor_is_sidebar_enabled() {
    // Global toggle - if off, sidebar is hidden everywhere
    if (!get_theme_mod('sidebar_global_enable', true)) {
        return false;
    }
    
    // Per-page checks
    if (is_front_page() || is_home()) {
        return get_theme_mod('sidebar_homepage', true);
    }
    
    if (is_singular()) {
        return get_theme_mod('sidebar_single', true);
    }
    
    if (is_post_type_archive()) {
        return get_theme_mod('sidebar_archive', true);
    }
    
    if (is_search()) {
        return get_theme_mod('sidebar_search', true);
    }
    
    if (is_tax()) {
        return get_theme_mod('sidebar_taxonomy', true);
    }
    
    // Default: show sidebar
    return true;
}


/**
 * Output custom CSS from customizer
 */
function flavor_customizer_css() {
    $primary_color = get_theme_mod('primary_color', '#ff5722');
    $secondary_color = get_theme_mod('secondary_color', '#ff5722');
    ?>
    <style type="text/css">
        :root {
            --primary-color: <?php echo esc_attr($primary_color); ?>;
            --secondary-color: <?php echo esc_attr($secondary_color); ?>;
        }
    </style>
    <?php
}
add_action('wp_head', 'flavor_customizer_css');

/**
 * =========================================
 * Theme Options Admin Menu
 * =========================================
 */

// Add admin menu
function flavor_add_admin_menu() {
    add_menu_page(
        __('Flavor Theme', 'flavor-flavor'),
        __('Flavor Theme', 'flavor-flavor'),
        'manage_options',
        'flavor-theme-options',
        'flavor_options_page',
        'dashicons-admin-customizer',
        61
    );
    
    // Add Reports submenu
    add_submenu_page(
        'flavor-theme-options',
        __('Chapter Reports', 'flavor-flavor'),
        __('Reports', 'flavor-flavor') . flavor_get_reports_count_badge(),
        'manage_options',
        'flavor-reports',
        'flavor_reports_page'
    );
}
add_action('admin_menu', 'flavor_add_admin_menu');

/**
 * Get unread reports count badge
 */
function flavor_get_reports_count_badge() {
    $unread = get_option('flavor_unread_reports', 0);
    if ($unread > 0) {
        return ' <span class="awaiting-mod">' . $unread . '</span>';
    }
    return '';
}

/**
 * Create reports table on theme activation
 */
function flavor_create_reports_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'flavor_reports';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        chapter_id bigint(20) NOT NULL,
        manhwa_id bigint(20) NOT NULL,
        chapter_url varchar(500) NOT NULL,
        chapter_number varchar(20) DEFAULT NULL,
        issue_type varchar(50) NOT NULL,
        description text,
        reporter_email varchar(100),
        reporter_ip varchar(45),
        status varchar(20) DEFAULT 'pending',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        resolved_at datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY chapter_id (chapter_id),
        KEY status (status)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
add_action('after_switch_theme', 'flavor_create_reports_table');

// Run on init to ensure table exists and has all columns
add_action('init', function() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'flavor_reports';
    
    // Create table if doesn't exist
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        flavor_create_reports_table();
    } else {
        // Check if chapter_number column exists, add if not
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'chapter_number'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN chapter_number varchar(20) DEFAULT NULL AFTER chapter_url");
        }
    }
});

/**
 * AJAX Handler: Submit Report
 */
function flavor_submit_report_ajax() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'flavor_report_nonce')) {
        wp_send_json_error(['message' => 'Invalid security token']);
        return;
    } 
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'flavor_reports';
    
    // Sanitize input
    $chapter_id = intval($_POST['chapter_id'] ?? 0);
    $manhwa_id = intval($_POST['manhwa_id'] ?? 0);
    $chapter_url = esc_url_raw($_POST['chapter_url'] ?? '');
    $chapter_number = sanitize_text_field($_POST['chapter_number'] ?? '');
    $issue_type = sanitize_text_field($_POST['issue_type'] ?? '');
    $description = sanitize_textarea_field($_POST['description'] ?? '');
    $reporter_email = sanitize_email($_POST['reporter_email'] ?? '');
    $reporter_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    // Validate required fields
    if (!$chapter_id || !$issue_type) {
        wp_send_json_error(['message' => 'Silakan pilih jenis masalah']);
        return;
    }
    
    // Prevent spam - check if same IP reported same chapter in last 5 minutes
    // Skip spam check for admins and editors
    if (!current_user_can('edit_posts')) {
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE chapter_id = %d AND reporter_ip = %s AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)",
            $chapter_id, $reporter_ip
        ));
        
        if ($existing) {
            wp_send_json_error(['message' => 'Anda sudah melaporkan chapter ini. Silakan tunggu beberapa menit.']);
            return;
        }
    }
    
    // Insert report
    $result = $wpdb->insert($table_name, [
        'chapter_id' => $chapter_id,
        'manhwa_id' => $manhwa_id,
        'chapter_url' => $chapter_url,
        'chapter_number' => $chapter_number,
        'issue_type' => $issue_type,
        'description' => $description,
        'reporter_email' => $reporter_email,
        'reporter_ip' => $reporter_ip,
        'status' => 'pending',
    ]);
    
    if ($result) {
        // Increment unread count
        $unread = get_option('flavor_unread_reports', 0);
        update_option('flavor_unread_reports', $unread + 1);
        
        // Send email notification to admin
        $admin_email = get_option('admin_email');
        $chapter_title = get_the_title($chapter_id);
        $manhwa_title = get_the_title($manhwa_id);
        
        $issue_labels = [
            'broken_images' => 'Broken / Not Loading Images',
            'missing_pages' => 'Missing Pages',
            'wrong_chapter' => 'Wrong Chapter Order',
            'duplicate' => 'Duplicate Content',
            'inappropriate' => 'Inappropriate Content',
            'other' => 'Other Issue',
        ];
        
        $subject = sprintf('[%s] New Chapter Report: %s', get_bloginfo('name'), $issue_labels[$issue_type] ?? $issue_type);
        
        $message = sprintf(
            "New chapter report received:\n\n" .
            "Manhwa: %s\n" .
            "Chapter: %s\n" .
            "Issue Type: %s\n" .
            "Description: %s\n" .
            "Reporter Email: %s\n\n" .
            "Chapter URL: %s\n\n" .
            "View all reports: %s",
            $manhwa_title,
            $chapter_title,
            $issue_labels[$issue_type] ?? $issue_type,
            $description ?: '(No description)',
            $reporter_email ?: '(Not provided)',
            $chapter_url,
            admin_url('admin.php?page=flavor-reports')
        );
        
        wp_mail($admin_email, $subject, $message);
        
        wp_send_json_success(['message' => 'Laporan berhasil dikirim. Terima kasih!']);
    } else {
        wp_send_json_error(['message' => 'Gagal mengirim laporan. Silakan coba lagi.']);
    }
}
add_action('wp_ajax_flavor_submit_report', 'flavor_submit_report_ajax');
add_action('wp_ajax_nopriv_flavor_submit_report', 'flavor_submit_report_ajax');

/**
 * Admin Page: Reports List
 */
function flavor_reports_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'flavor_reports';
    
    // Handle actions (POST method)
    if (isset($_POST['report_action']) && isset($_POST['report_id'])) {
        $report_id = intval($_POST['report_id']);
        
        // Verify nonce
        if (wp_verify_nonce($_POST['report_nonce'] ?? '', 'flavor_report_action') && current_user_can('manage_options')) {
            if ($_POST['report_action'] === 'resolve') {
                $wpdb->update($table_name, ['status' => 'resolved', 'resolved_at' => current_time('mysql')], ['id' => $report_id]);
            } elseif ($_POST['report_action'] === 'delete') {
                $wpdb->delete($table_name, ['id' => $report_id]);
            }
            
            // Recalculate unread count
            $unread = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'pending'");
            update_option('flavor_unread_reports', $unread);
            
            wp_redirect(admin_url('admin.php?page=flavor-reports&updated=1'));
            exit;
        }
    }
    
    // Mark all as read when viewing
    update_option('flavor_unread_reports', 0);
    
    // Get reports
    $status_filter = sanitize_text_field($_GET['status'] ?? 'all');
    $where = $status_filter !== 'all' ? $wpdb->prepare("WHERE status = %s", $status_filter) : '';
    $reports = $wpdb->get_results("SELECT * FROM $table_name $where ORDER BY created_at DESC LIMIT 100");
    
    $issue_labels = [
        'broken_images' => '🖼️ Broken Images',
        'missing_pages' => '📄 Missing Pages',
        'wrong_chapter' => '🔢 Wrong Order',
        'duplicate' => '📑 Duplicate',
        'inappropriate' => '⚠️ Inappropriate',
        'other' => '❓ Other',
    ];
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Chapter Reports', 'flavor-flavor'); ?></h1>
        
        <?php if (isset($_GET['updated'])): ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Report updated.', 'flavor-flavor'); ?></p></div>
        <?php endif; ?>
        
        <ul class="subsubsub">
            <li><a href="?page=flavor-reports&status=all" class="<?php echo $status_filter === 'all' ? 'current' : ''; ?>"><?php esc_html_e('All', 'flavor-flavor'); ?></a> |</li>
            <li><a href="?page=flavor-reports&status=pending" class="<?php echo $status_filter === 'pending' ? 'current' : ''; ?>"><?php esc_html_e('Pending', 'flavor-flavor'); ?></a> |</li>
            <li><a href="?page=flavor-reports&status=resolved" class="<?php echo $status_filter === 'resolved' ? 'current' : ''; ?>"><?php esc_html_e('Resolved', 'flavor-flavor'); ?></a></li>
        </ul>
        
        <table class="wp-list-table widefat fixed striped" style="margin-top: 15px;">
            <thead>
                <tr>
                    <th style="width: 50px;"><?php esc_html_e('ID', 'flavor-flavor'); ?></th>
                    <th><?php esc_html_e('Manhwa', 'flavor-flavor'); ?></th>
                    <th style="width: 100px;"><?php esc_html_e('Chapter', 'flavor-flavor'); ?></th>
                    <th style="width: 120px;"><?php esc_html_e('Issue Type', 'flavor-flavor'); ?></th>
                    <th><?php esc_html_e('Description', 'flavor-flavor'); ?></th>
                    <th style="width: 150px;"><?php esc_html_e('Reporter', 'flavor-flavor'); ?></th>
                    <th style="width: 100px;"><?php esc_html_e('Status', 'flavor-flavor'); ?></th>
                    <th style="width: 140px;"><?php esc_html_e('Date', 'flavor-flavor'); ?></th>
                    <th style="width: 120px;"><?php esc_html_e('Actions', 'flavor-flavor'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reports)): ?>
                <tr><td colspan="9"><?php esc_html_e('No reports found.', 'flavor-flavor'); ?></td></tr>
                <?php else: foreach ($reports as $report): 
                    // Use chapter_number from database, fallback to title extraction
                    $chapter_num = !empty($report->chapter_number) ? $report->chapter_number : '-';
                ?>
                <tr>
                    <td><?php echo $report->id; ?></td>
                    <td>
                        <strong><a href="<?php echo get_permalink($report->manhwa_id); ?>" target="_blank"><?php echo esc_html(get_the_title($report->manhwa_id)); ?></a></strong>
                    </td>
                    <td>
                        <a href="<?php echo esc_url($report->chapter_url); ?>" target="_blank" style="color: #2271b1; font-weight: 500;"><?php echo esc_html($chapter_num); ?></a>
                    </td>
                    <td><?php echo $issue_labels[$report->issue_type] ?? $report->issue_type; ?></td>
                    <td><?php echo esc_html($report->description ?: '-'); ?></td>
                    <td>
                        <?php if ($report->reporter_email): ?>
                            <a href="mailto:<?php echo esc_attr($report->reporter_email); ?>"><?php echo esc_html($report->reporter_email); ?></a>
                        <?php else: ?>
                            <span style="color: #999;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($report->status === 'pending'): ?>
                            <span style="color: #d63638; font-weight: bold;">● <?php esc_html_e('Pending', 'flavor-flavor'); ?></span>
                        <?php else: ?>
                            <span style="color: #00a32a;">✓ <?php esc_html_e('Resolved', 'flavor-flavor'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo date_i18n('M j, Y H:i', strtotime($report->created_at)); ?></td>
                    <td style="white-space: nowrap;">
                        <?php if ($report->status === 'pending'): ?>
                        <form method="post" style="display: inline;" onsubmit="return confirm('Tandai laporan ini sebagai selesai?');">
                            <input type="hidden" name="report_action" value="resolve">
                            <input type="hidden" name="report_id" value="<?php echo $report->id; ?>">
                            <?php wp_nonce_field('flavor_report_action', 'report_nonce'); ?>
                            <button type="submit" class="button button-small button-primary"><?php esc_html_e('Resolve', 'flavor-flavor'); ?></button>
                        </form>
                        <?php endif; ?>
                        <form method="post" style="display: inline;" onsubmit="return confirm('Hapus laporan ini? Tindakan ini tidak dapat dibatalkan.');">
                            <input type="hidden" name="report_action" value="delete">
                            <input type="hidden" name="report_id" value="<?php echo $report->id; ?>">
                            <?php wp_nonce_field('flavor_report_action', 'report_nonce'); ?>
                            <button type="submit" class="button button-small" style="color: #d63638;"><?php esc_html_e('Delete', 'flavor-flavor'); ?></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Register settings
function flavor_register_settings() {
    register_setting('flavor_options', 'flavor_trending_title');
    register_setting('flavor_options', 'flavor_trending_sort'); // Legacy - keep for backward compatibility
    register_setting('flavor_options', 'flavor_trending_sort_weekly');
    register_setting('flavor_options', 'flavor_trending_sort_monthly');
    register_setting('flavor_options', 'flavor_trending_sort_all');
    register_setting('flavor_options', 'flavor_trending_count');
    register_setting('flavor_options', 'flavor_trending_manual_ids');
    register_setting('flavor_options', 'flavor_trending_show_tabs');
    register_setting('flavor_options', 'flavor_trending_show_genres');
    register_setting('flavor_options', 'flavor_trending_show_rating');
}
add_action('admin_init', 'flavor_register_settings');

/**
 * AJAX handler for trending widget tabs (Mingguan/Bulanan/Semua)
 */
function flavor_get_trending_ajax() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'flavor_trending_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
        return;
    }
    
    $period = sanitize_text_field($_POST['period'] ?? 'all');
    
    // Get settings - use per-tab sort settings
    $trending_count = get_option('flavor_trending_count', 5);
    $trending_manual_ids = get_option('flavor_trending_manual_ids', '');
    $show_genres = get_option('flavor_trending_show_genres', 1);
    $show_rating = get_option('flavor_trending_show_rating', 1);
    
    // Get sort setting based on period
    switch ($period) {
        case 'weekly':
            $trending_sort = get_option('flavor_trending_sort_weekly', 'rating');
            break;
        case 'monthly':
            $trending_sort = get_option('flavor_trending_sort_monthly', 'views');
            break;
        case 'all':
        default:
            $trending_sort = get_option('flavor_trending_sort_all', 'latest');
            break;
    }
    
    // Build query args
    $query_args = array(
        'post_type'      => 'manhwa',
        'posts_per_page' => $trending_count,
        'post_status'    => 'publish',
    );
    
    // Apply date filter based on period
    switch ($period) {
        case 'weekly':
            $query_args['date_query'] = array(
                array('after' => '1 week ago'),
            );
            break;
        case 'monthly':
            $query_args['date_query'] = array(
                array('after' => '1 month ago'),
            );
            break;
        case 'all':
        default:
            // No date filter
            break;
    }
    
    // Apply sort
    switch ($trending_sort) {
        case 'views':
            $query_args['meta_key'] = '_manhwa_views';
            $query_args['orderby'] = 'meta_value_num';
            $query_args['order'] = 'DESC';
            break;
        case 'rating':
            $query_args['meta_key'] = '_manhwa_rating';
            $query_args['orderby'] = 'meta_value_num';
            $query_args['order'] = 'DESC';
            break;
        case 'latest':
            $query_args['orderby'] = 'date';
            $query_args['order'] = 'DESC';
            break;
        case 'random':
            $query_args['orderby'] = 'rand';
            break;
        case 'manual':
            if (!empty($trending_manual_ids)) {
                $ids = array_map('trim', explode(',', $trending_manual_ids));
                $ids = array_filter($ids, 'is_numeric');
                if (!empty($ids)) {
                    $query_args['post__in'] = array_map('intval', $ids);
                    $query_args['orderby'] = 'post__in';
                    $query_args['posts_per_page'] = -1;
                }
            }
            // Remove date filter for manual
            unset($query_args['date_query']);
            break;
    }
    
    $trending_manga = get_posts($query_args);
    
    // Build HTML
    ob_start();
    
    if ($trending_manga) {
        foreach ($trending_manga as $index => $manga) {
            $meta = flavor_get_manhwa_meta($manga->ID);
            $genres = get_the_terms($manga->ID, 'manhwa_genre');
            ?>
            <div class="trending-item">
                <div class="trending-rank"><?php echo $index + 1; ?></div>
                <div class="trending-thumb">
                    <a href="<?php echo get_permalink($manga->ID); ?>">
                        <?php if (has_post_thumbnail($manga->ID)): ?>
                            <?php echo get_the_post_thumbnail($manga->ID, 'chapter-thumb'); ?>
                        <?php else: ?>
                            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/no-image.svg" alt="<?php echo esc_attr($manga->post_title); ?>" class="no-cover-placeholder">
                        <?php endif; ?>
                    </a>
                </div>
                <div class="trending-info">
                    <h4 class="trending-name">
                        <a href="<?php echo get_permalink($manga->ID); ?>">
                            <?php echo esc_html($manga->post_title); ?>
                        </a>
                    </h4>
                    <?php if ($show_genres && $genres && !is_wp_error($genres)): ?>
                    <div class="trending-genres">
                        <span class="genres-label"><?php esc_html_e('Genres', 'flavor-flavor'); ?></span>
                        <?php 
                        $genre_links = array();
                        foreach (array_slice($genres, 0, 3) as $genre) {
                            $genre_links[] = '<a href="' . esc_url(get_term_link($genre)) . '">' . esc_html($genre->name) . '</a>';
                        }
                        echo implode(', ', $genre_links);
                        ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($show_rating): ?>
                    <div class="trending-rating">
                        <?php 
                        $rating = floatval($meta['rating'] ?? 0);
                        $full_stars = floor($rating / 2);
                        $half_star = ($rating / 2) - $full_stars >= 0.5;
                        
                        for ($i = 0; $i < 5; $i++) {
                            if ($i < $full_stars) {
                                echo '<svg class="star filled" viewBox="0 0 24 24"><path fill="currentColor" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>';
                            } elseif ($i == $full_stars && $half_star) {
                                echo '<svg class="star half" viewBox="0 0 24 24"><path fill="currentColor" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>';
                            } else {
                                echo '<svg class="star empty" viewBox="0 0 24 24"><path fill="currentColor" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>';
                            }
                        }
                        ?>
                        <span class="rating-score"><?php echo esc_html($rating ?: '-'); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php
        }
    } else {
        ?>
        <div class="trending-empty">
            <p><?php esc_html_e('Tidak ada data untuk periode ini.', 'flavor-flavor'); ?></p>
        </div>
        <?php
    }
    
    $html = ob_get_clean();
    
    wp_send_json_success(['html' => $html]);
}
add_action('wp_ajax_flavor_get_trending', 'flavor_get_trending_ajax');
add_action('wp_ajax_nopriv_flavor_get_trending', 'flavor_get_trending_ajax');

// Options page HTML - Modern Tabbed UI
function flavor_options_page() {
    $saved = isset($_GET['saved']) && $_GET['saved'] == '1';
    $tabs = array(
        'general'       => array('icon' => 'tune',                  'label' => 'General'),
        'announcements' => array('icon' => 'campaign',              'label' => 'Announcements'),
        'contact'       => array('icon' => 'share',                 'label' => 'Contact & Social'),
        'ads'           => array('icon' => 'ad_units',              'label' => 'Ads Management'),
        'widgets'       => array('icon' => 'widgets',               'label' => 'Widgets'),
        'seo'           => array('icon' => 'search',                'label' => 'SEO & Tracking'),
        'advanced'      => array('icon' => 'bolt',                  'label' => 'Advanced'),
    );
    ?>
    <div class="fvo-wrap">
        <?php if ($saved): ?>
        <div class="fvo-saved-notice"><span class="material-symbols-outlined" style="font-size:18px">check_circle</span> Settings saved successfully!</div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=flavor-theme-options')); ?>">
            <?php wp_nonce_field('flavor_save_options', 'flavor_options_nonce'); ?>
            <input type="hidden" name="flavor_save_action" value="1">
            <input type="hidden" name="fvo_active_tab" id="fvo_active_tab" value="<?php echo isset($_POST['fvo_active_tab']) ? esc_attr($_POST['fvo_active_tab']) : 'general'; ?>">

            <!-- Header -->
            <div class="fvo-header">
                <div class="fvo-header-left">
                    <a class="fvo-header-logo" href="<?php echo esc_url(admin_url('admin.php?page=flavor-theme-options')); ?>">
                        <span class="material-symbols-outlined">settings_heart</span>
                        <span class="fvo-logo-text">Flavor Theme</span>
                    </a>
                    <span class="fvo-header-version">Theme Options</span>
                </div>
                <div class="fvo-header-right">
                    <a href="<?php echo esc_url(admin_url('customize.php')); ?>" class="fvo-btn-customizer">
                        <span class="material-symbols-outlined">palette</span> Customizer
                    </a>
                    <input type="submit" name="flavor_submit" value="💾 Save Changes" class="fvo-btn-save">
                </div>
            </div>

            <!-- Layout -->
            <div class="fvo-layout">
                <!-- Sidebar -->
                <aside class="fvo-sidebar">
                    <p class="fvo-nav-group-label">Settings</p>
                    <nav>
                        <?php $first = true; foreach ($tabs as $key => $tab): ?>
                        <a href="#<?php echo $key; ?>" class="fvo-nav-item<?php echo $first ? ' active' : ''; ?>" data-tab="<?php echo $key; ?>">
                            <span class="material-symbols-outlined"><?php echo esc_html($tab['icon']); ?></span>
                            <span><?php echo esc_html($tab['label']); ?></span>
                        </a>
                        <?php $first = false; endforeach; ?>
                    </nav>
                </aside>

                <!-- Main Content -->
                <div class="fvo-main">
                    <div class="fvo-container">
                        <div id="fvo-tab-general" class="fvo-tab-panel active"><?php flavor_render_tab_general(); ?></div>
                        <div id="fvo-tab-announcements" class="fvo-tab-panel"><?php flavor_render_tab_announcements(); ?></div>
                        <div id="fvo-tab-contact" class="fvo-tab-panel"><?php flavor_render_tab_contact(); ?></div>
                        <div id="fvo-tab-ads" class="fvo-tab-panel"><?php flavor_render_tab_ads(); ?></div>
                        <div id="fvo-tab-widgets" class="fvo-tab-panel"><?php flavor_render_tab_widgets(); ?></div>
                        <div id="fvo-tab-seo" class="fvo-tab-panel"><?php flavor_render_tab_seo(); ?></div>
                        <div id="fvo-tab-advanced" class="fvo-tab-panel"><?php flavor_render_tab_advanced(); ?></div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <?php
}

/**
 * Include template parts
 */
function flavor_get_template_part($slug, $name = null, $args = array()) {
    get_template_part($slug, $name, $args);
}

/**
 * Get placeholder image URL
 */
function flavor_get_placeholder_image() {
    return get_template_directory_uri() . '/assets/images/no-image.svg';
}

/**
 * Check if Manhwa Manager plugin is active (for backward compat)
 */
function flavor_is_manhwa_manager_active() {
    return post_type_exists('manhwa');
}

/**
 * Admin notice if Manhwa Manager PLUGIN is still active (conflict warning)
 */
function flavor_admin_notice_manhwa_manager() {
    if (class_exists('Flavor_Manager')) {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong><?php esc_html_e('Flavor Theme:', 'flavor-flavor'); ?></strong>
                <?php esc_html_e('Manhwa Manager features are now built into the theme. You can safely deactivate the Manhwa Manager plugin to avoid conflicts.', 'flavor-flavor'); ?>
            </p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'flavor_admin_notice_manhwa_manager');

/**
 * Get chapter reading URL - generates clean URLs
 * Uses format: /manhwa-slug-chapter-XX/
 */
function flavor_get_chapter_url($manhwa_id, $chapter) {
    $manhwa = get_post($manhwa_id);
    if (!$manhwa) {
        return $chapter['url'] ?? '#';
    }
    
    // Try 'number' field first (skip if empty)
    $num_field = isset($chapter['number']) ? trim($chapter['number']) : '';
    if ($num_field !== '') {
        $clean_num = preg_replace('/[^0-9.]/', '', $num_field);
        if ($clean_num !== '') {
            return home_url('/' . $manhwa->post_name . '-chapter-' . $clean_num . '/');
        }
    }
    
    // Fallback: extract chapter number from title intelligently
    // e.g. "Chapter 129 S1 END" → 129, "Ch 05" → 05, "Episode 42" → 42
    $title = $chapter['title'] ?? '';
    $clean_num = '0';
    
    if (preg_match('/(?:chapter|ch|episode|ep)[\s._-]*(\d+(?:\.\d+)?)/i', $title, $m)) {
        $clean_num = $m[1];
    } elseif (preg_match('/^(\d+(?:\.\d+)?)/', trim($title), $m)) {
        // Title starts with a number like "129 - The Battle"
        $clean_num = $m[1];
    } elseif (preg_match('/(\d+(?:\.\d+)?)/', $title, $m)) {
        // Last resort: first number found
        $clean_num = $m[1];
    }
    
    return home_url('/' . $manhwa->post_name . '-chapter-' . $clean_num . '/');
}

/**
 * Create missing theme pages via admin action
 */
function flavor_maybe_create_theme_pages() {
    if (is_admin() && current_user_can('manage_options')) {
        if (isset($_GET['flavor_create_missing_pages']) && wp_verify_nonce($_GET['_wpnonce'], 'flavor_create_missing_pages')) {
            $required_pages = array(
                array('slug' => 'bookmarks',      'title' => 'Bookmarks',             'template' => 'template-bookmarks.php'),
                array('slug' => 'profile',        'title' => 'Profile',               'template' => 'page-profile.php'),
                array('slug' => 'a-z-list',       'title' => 'A-Z Manga List',        'template' => 'page-az-list.php'),
                array('slug' => 'contact',        'title' => 'Contact & Advertising', 'template' => 'page-contact.php'),
                array('slug' => 'reset-password', 'title' => 'Reset Password',        'template' => 'page-reset-password.php'),
            );

            $created = 0;
            foreach ($required_pages as $page) {
                $existing = get_page_by_path($page['slug']);
                if (!$existing) {
                    $page_id = wp_insert_post(array(
                        'post_title'  => $page['title'],
                        'post_name'   => $page['slug'],
                        'post_status' => 'publish',
                        'post_type'   => 'page',
                        'post_content'=> '',
                    ));
                    if ($page_id && !is_wp_error($page_id)) {
                        update_post_meta($page_id, '_wp_page_template', $page['template']);
                        $created++;
                    }
                }
            }

            wp_redirect(admin_url('edit.php?post_type=page&pages_created=' . $created));
            exit;
        }
    }
}
add_action('admin_init', 'flavor_maybe_create_theme_pages');

/**
 * Admin notice for missing theme pages
 * Note: Reader page is NOT required — chapter reader is handled by rewrite rules.
 */
function flavor_admin_notice_missing_pages() {
    if (!current_user_can('manage_options')) return;

    $required_pages = array(
        array('slug' => 'bookmarks',      'title' => 'Bookmarks',             'template' => 'template-bookmarks.php'),
        array('slug' => 'profile',        'title' => 'Profile',               'template' => 'page-profile.php'),
        array('slug' => 'a-z-list',       'title' => 'A-Z Manga List',        'template' => 'page-az-list.php'),
        array('slug' => 'contact',        'title' => 'Contact & Advertising', 'template' => 'page-contact.php'),
        array('slug' => 'reset-password', 'title' => 'Reset Password',        'template' => 'page-reset-password.php'),
    );

    $missing = array();
    foreach ($required_pages as $page) {
        $existing = get_page_by_path($page['slug']);
        if (!$existing) {
            $missing[] = $page;
        }
    }

    if (empty($missing)) return;

    $create_url = wp_nonce_url(admin_url('?flavor_create_missing_pages=1'), 'flavor_create_missing_pages');
    $names = wp_list_pluck($missing, 'title');
    ?>
    <div class="notice notice-warning">
        <p>
            <strong><?php esc_html_e('Flavor Theme:', 'flavor-flavor'); ?></strong>
            <?php printf(
                esc_html__('The following required pages are missing: %s', 'flavor-flavor'),
                '<code>' . implode('</code>, <code>', array_map('esc_html', $names)) . '</code>'
            ); ?>
            <a href="<?php echo esc_url($create_url); ?>" class="button button-primary" style="margin-left: 10px;">
                <?php esc_html_e('Create All Missing Pages', 'flavor-flavor'); ?>
            </a>
        </p>
    </div>
    <?php
}
add_action('admin_notices', 'flavor_admin_notice_missing_pages');

/**
 * Note: Chapter reader URL handling is done by rewrite rules in inc/manhwa-core.php
 * URL format: /manhwa-slug-chapter-XX/
 * No WordPress "Reader" page is needed.
 */

/**
 * Auto-create Bookmarks page
 */
function flavor_create_bookmarks_page() {
    $bookmark_page = get_page_by_path('bookmarks');
    
    if (!$bookmark_page) {
        $page_data = array(
            'post_title'   => __('Bookmarks', 'flavor-flavor'),
            'post_name'    => 'bookmarks',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '',
        );
        
        $page_id = wp_insert_post($page_data);
        
        if ($page_id && !is_wp_error($page_id)) {
            update_post_meta($page_id, '_wp_page_template', 'template-bookmarks.php');
        }
    }
}
add_action('after_switch_theme', 'flavor_create_bookmarks_page');
add_action('init', 'flavor_create_bookmarks_page');

// Auto-create Profile page
function flavor_create_profile_page() {
    $profile_page = get_page_by_path('profile');
    
    if (!$profile_page) {
        $page_id = wp_insert_post(array(
            'post_title'   => 'Profile',
            'post_name'    => 'profile',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '',
        ));
        
        if ($page_id && !is_wp_error($page_id)) {
            update_post_meta($page_id, '_wp_page_template', 'page-profile.php');
        }
    }
}
add_action('after_switch_theme', 'flavor_create_profile_page');
add_action('init', 'flavor_create_profile_page');

/**
 * =========================================
 * ADS MANAGEMENT
 * =========================================
 */

/**
 * Register Ads Customizer Settings
 */
function flavor_ads_customizer($wp_customize) {
    // Ads Section
    $wp_customize->add_section('flavor_ads_section', array(
        'title'       => __('Ads Management', 'flavor-flavor'),
        'description' => __('Manage advertisement banners. Supports HTML/JavaScript ad codes.', 'flavor-flavor'),
        'priority'    => 35,
    ));
    
    // Enable/Disable Ads
    $wp_customize->add_setting('flavor_ads_enable', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ));
    $wp_customize->add_control('flavor_ads_enable', array(
        'label'    => __('Enable Advertisements', 'flavor-flavor'),
        'section'  => 'flavor_ads_section',
        'type'     => 'checkbox',
    ));
    
    // Header Ad (728x90)
    $wp_customize->add_setting('flavor_ads_header', array(
        'default'           => '',
        'sanitize_callback' => 'flavor_sanitize_ad_code',
    ));
    $wp_customize->add_control('flavor_ads_header', array(
        'label'       => __('Header Ad (728x90)', 'flavor-flavor'),
        'description' => __('Appears below the header. Recommended size: 728x90', 'flavor-flavor'),
        'section'     => 'flavor_ads_section',
        'type'        => 'textarea',
    ));
    
    // After Content Ad (728x90)
    $wp_customize->add_setting('flavor_ads_after_content', array(
        'default'           => '',
        'sanitize_callback' => 'flavor_sanitize_ad_code',
    ));
    $wp_customize->add_control('flavor_ads_after_content', array(
        'label'       => __('After Content Ad (728x90)', 'flavor-flavor'),
        'description' => __('Appears after main content. Recommended size: 728x90', 'flavor-flavor'),
        'section'     => 'flavor_ads_section',
        'type'        => 'textarea',
    ));
    
    // Sidebar Ad (300x250)
    $wp_customize->add_setting('flavor_ads_sidebar', array(
        'default'           => '',
        'sanitize_callback' => 'flavor_sanitize_ad_code',
    ));
    $wp_customize->add_control('flavor_ads_sidebar', array(
        'label'       => __('Sidebar Ad (300x250)', 'flavor-flavor'),
        'description' => __('Appears in sidebar. Recommended size: 300x250', 'flavor-flavor'),
        'section'     => 'flavor_ads_section',
        'type'        => 'textarea',
    ));
    
    // Before Footer Ad (728x90)
    $wp_customize->add_setting('flavor_ads_before_footer', array(
        'default'           => '',
        'sanitize_callback' => 'flavor_sanitize_ad_code',
    ));
    $wp_customize->add_control('flavor_ads_before_footer', array(
        'label'       => __('Before Footer Ad (728x90)', 'flavor-flavor'),
        'description' => __('Appears before footer. Recommended size: 728x90', 'flavor-flavor'),
        'section'     => 'flavor_ads_section',
        'type'        => 'textarea',
    ));
    
    // In-Article Ad (responsive)
    $wp_customize->add_setting('flavor_ads_in_article', array(
        'default'           => '',
        'sanitize_callback' => 'flavor_sanitize_ad_code',
    ));
    $wp_customize->add_control('flavor_ads_in_article', array(
        'label'       => __('In-Article Ad', 'flavor-flavor'),
        'description' => __('Appears within article content (chapter reader).', 'flavor-flavor'),
        'section'     => 'flavor_ads_section',
        'type'        => 'textarea',
    ));
    
    // Float Bottom Ad (sticky banner)
    $wp_customize->add_setting('flavor_ads_float_bottom', array(
        'default'           => '',
        'sanitize_callback' => 'flavor_sanitize_ad_code',
    ));
    $wp_customize->add_control('flavor_ads_float_bottom', array(
        'label'       => __('Float Bottom Ad (728x90)', 'flavor-flavor'),
        'description' => __('Sticky banner yang muncul di bawah layar. Bisa ditutup oleh user.', 'flavor-flavor'),
        'section'     => 'flavor_ads_section',
        'type'        => 'textarea',
    ));
    
    // Before Chapter Ad
    $wp_customize->add_setting('flavor_ads_before_chapter', array(
        'default'           => '',
        'sanitize_callback' => 'flavor_sanitize_ad_code',
    ));
    $wp_customize->add_control('flavor_ads_before_chapter', array(
        'label'       => __('Before Reading Chapter Ad', 'flavor-flavor'),
        'description' => __('Appears just before the chapter images in the reader page.', 'flavor-flavor'),
        'section'     => 'flavor_ads_section',
        'type'        => 'textarea',
    ));
    
    // After Chapter Ad
    $wp_customize->add_setting('flavor_ads_after_chapter', array(
        'default'           => '',
        'sanitize_callback' => 'flavor_sanitize_ad_code',
    ));
    $wp_customize->add_control('flavor_ads_after_chapter', array(
        'label'       => __('After Reading Chapter Ad', 'flavor-flavor'),
        'description' => __('Appears just after the chapter images in the reader page.', 'flavor-flavor'),
        'section'     => 'flavor_ads_section',
        'type'        => 'textarea',
    ));
    
    // ===== DIRECT LINK AD =====
    
    // Enable Direct Link
    $wp_customize->add_setting('flavor_ads_direct_link_enable', array(
        'default'           => false,
        'sanitize_callback' => 'wp_validate_boolean',
    ));
    $wp_customize->add_control('flavor_ads_direct_link_enable', array(
        'label'       => __('Enable Direct Link', 'flavor-flavor'),
        'description' => __('Buka URL iklan di tab baru saat user klik area kosong di halaman.', 'flavor-flavor'),
        'section'     => 'flavor_ads_section',
        'type'        => 'checkbox',
    ));
    
    // Direct Link URL
    $wp_customize->add_setting('flavor_ads_direct_link_url', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ));
    $wp_customize->add_control('flavor_ads_direct_link_url', array(
        'label'       => __('Direct Link URL', 'flavor-flavor'),
        'description' => __('URL tujuan saat user klik area kosong.', 'flavor-flavor'),
        'section'     => 'flavor_ads_section',
        'type'        => 'url',
    ));
    
    // Direct Link Max Clicks
    $wp_customize->add_setting('flavor_ads_direct_link_max', array(
        'default'           => 2,
        'sanitize_callback' => 'absint',
    ));
    $wp_customize->add_control('flavor_ads_direct_link_max', array(
        'label'       => __('Max Clicks Per Session', 'flavor-flavor'),
        'description' => __('Batas maksimal direct link terbuka per sesi kunjungan user. (0 = unlimited)', 'flavor-flavor'),
        'section'     => 'flavor_ads_section',
        'type'        => 'number',
        'input_attrs' => array(
            'min' => 0,
            'max' => 20,
        ),
    ));
    
    // Direct Link - Exclude Pages
    $wp_customize->add_setting('flavor_ads_direct_link_exclude', array(
        'default'           => 'admin,login',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('flavor_ads_direct_link_exclude', array(
        'label'       => __('Exclude Pages', 'flavor-flavor'),
        'description' => __('Halaman yang tidak menampilkan direct link (pisah koma). Contoh: admin,login,contact', 'flavor-flavor'),
        'section'     => 'flavor_ads_section',
        'type'        => 'text',
    ));
}
add_action('customize_register', 'flavor_ads_customizer');

/**
 * Sanitize ad code - allow HTML and scripts
 */
function flavor_sanitize_ad_code($input) {
    return $input; // Don't sanitize to allow ad scripts
}

/**
 * Display Ad
 * 
 * @param string $position Ad position (header, after_content, sidebar, before_footer, in_article)
 * @param bool $echo Echo or return
 * @return string|void
 */
function flavor_display_ad($position, $echo = true) {
    // Check if ads are enabled
    if (!get_theme_mod('flavor_ads_enable', true)) {
        return '';
    }
    
    $ad_code = get_theme_mod('flavor_ads_' . $position, '');
    
    if (empty($ad_code)) {
        return '';
    }
    
    $output = '<div class="fv-promo fv-promo-' . esc_attr($position) . '">';
    $output .= '<div class="fv-promo-inner">';
    $output .= $ad_code;
    $output .= '</div>';
    $output .= '</div>';
    
    if ($echo) {
        echo $output;
    } else {
        return $output;
    }
}

/**
 * Auto-insert header ad after header
 */
function flavor_header_ad() {
    flavor_display_ad('header');
}
add_action('flavor_after_header', 'flavor_header_ad');

/**
 * Auto-insert before footer ad
 */
function flavor_before_footer_ad() {
    flavor_display_ad('before_footer');
}
add_action('flavor_before_footer', 'flavor_before_footer_ad');

/**
 * Clean URL Rewrite Rules for Manhwa Filter
 * Converts: /manhwa/?genre[]=action&status[]=ongoing&type[]=manhwa&order=popular
 * To: /manhwa/genre/action/status/ongoing/type/manhwa/order/popular/
 */
function flavor_manhwa_filter_rewrite_rules() {
    // Pattern: /manhwa/genre/{genres}/status/{status}/type/{type}/order/{order}/page/{paged}/
    // Each segment is optional
    
    // Add query vars
    add_rewrite_tag('%filter_genre%', '([^/]+)');
    add_rewrite_tag('%filter_status%', '([^/]+)');
    add_rewrite_tag('%filter_type%', '([^/]+)');
    add_rewrite_tag('%filter_order%', '([^/]+)');
    
    // Full filter with all params and pagination
    add_rewrite_rule(
        'manhwa/genre/([^/]+)/status/([^/]+)/type/([^/]+)/order/([^/]+)/page/([0-9]+)/?$',
        'index.php?post_type=manhwa&filter_genre=$matches[1]&filter_status=$matches[2]&filter_type=$matches[3]&filter_order=$matches[4]&paged=$matches[5]',
        'top'
    );
    
    // Full filter without pagination
    add_rewrite_rule(
        'manhwa/genre/([^/]+)/status/([^/]+)/type/([^/]+)/order/([^/]+)/?$',
        'index.php?post_type=manhwa&filter_genre=$matches[1]&filter_status=$matches[2]&filter_type=$matches[3]&filter_order=$matches[4]',
        'top'
    );
    
    // Genre + Status + Type (no order)
    add_rewrite_rule(
        'manhwa/genre/([^/]+)/status/([^/]+)/type/([^/]+)/page/([0-9]+)/?$',
        'index.php?post_type=manhwa&filter_genre=$matches[1]&filter_status=$matches[2]&filter_type=$matches[3]&paged=$matches[4]',
        'top'
    );
    add_rewrite_rule(
        'manhwa/genre/([^/]+)/status/([^/]+)/type/([^/]+)/?$',
        'index.php?post_type=manhwa&filter_genre=$matches[1]&filter_status=$matches[2]&filter_type=$matches[3]',
        'top'
    );
    
    // Genre + Status only
    add_rewrite_rule(
        'manhwa/genre/([^/]+)/status/([^/]+)/page/([0-9]+)/?$',
        'index.php?post_type=manhwa&filter_genre=$matches[1]&filter_status=$matches[2]&paged=$matches[3]',
        'top'
    );
    add_rewrite_rule(
        'manhwa/genre/([^/]+)/status/([^/]+)/?$',
        'index.php?post_type=manhwa&filter_genre=$matches[1]&filter_status=$matches[2]',
        'top'
    );
    
    // Genre + Order only
    add_rewrite_rule(
        'manhwa/genre/([^/]+)/order/([^/]+)/page/([0-9]+)/?$',
        'index.php?post_type=manhwa&filter_genre=$matches[1]&filter_order=$matches[2]&paged=$matches[3]',
        'top'
    );
    add_rewrite_rule(
        'manhwa/genre/([^/]+)/order/([^/]+)/?$',
        'index.php?post_type=manhwa&filter_genre=$matches[1]&filter_order=$matches[2]',
        'top'
    );
    
    // Genre only
    add_rewrite_rule(
        'manhwa/genre/([^/]+)/page/([0-9]+)/?$',
        'index.php?post_type=manhwa&filter_genre=$matches[1]&paged=$matches[2]',
        'top'
    );
    add_rewrite_rule(
        'manhwa/genre/([^/]+)/?$',
        'index.php?post_type=manhwa&filter_genre=$matches[1]',
        'top'
    );
    
    // Status + Type + Order
    add_rewrite_rule(
        'manhwa/status/([^/]+)/type/([^/]+)/order/([^/]+)/page/([0-9]+)/?$',
        'index.php?post_type=manhwa&filter_status=$matches[1]&filter_type=$matches[2]&filter_order=$matches[3]&paged=$matches[4]',
        'top'
    );
    add_rewrite_rule(
        'manhwa/status/([^/]+)/type/([^/]+)/order/([^/]+)/?$',
        'index.php?post_type=manhwa&filter_status=$matches[1]&filter_type=$matches[2]&filter_order=$matches[3]',
        'top'
    );
    
    // Status + Type
    add_rewrite_rule(
        'manhwa/status/([^/]+)/type/([^/]+)/page/([0-9]+)/?$',
        'index.php?post_type=manhwa&filter_status=$matches[1]&filter_type=$matches[2]&paged=$matches[3]',
        'top'
    );
    add_rewrite_rule(
        'manhwa/status/([^/]+)/type/([^/]+)/?$',
        'index.php?post_type=manhwa&filter_status=$matches[1]&filter_type=$matches[2]',
        'top'
    );
    
    // Status only
    add_rewrite_rule(
        'manhwa/status/([^/]+)/page/([0-9]+)/?$',
        'index.php?post_type=manhwa&filter_status=$matches[1]&paged=$matches[2]',
        'top'
    );
    add_rewrite_rule(
        'manhwa/status/([^/]+)/?$',
        'index.php?post_type=manhwa&filter_status=$matches[1]',
        'top'
    );
    
    // Type + Order
    add_rewrite_rule(
        'manhwa/type/([^/]+)/order/([^/]+)/page/([0-9]+)/?$',
        'index.php?post_type=manhwa&filter_type=$matches[1]&filter_order=$matches[2]&paged=$matches[3]',
        'top'
    );
    add_rewrite_rule(
        'manhwa/type/([^/]+)/order/([^/]+)/?$',
        'index.php?post_type=manhwa&filter_type=$matches[1]&filter_order=$matches[2]',
        'top'
    );
    
    // Type only
    add_rewrite_rule(
        'manhwa/type/([^/]+)/page/([0-9]+)/?$',
        'index.php?post_type=manhwa&filter_type=$matches[1]&paged=$matches[2]',
        'top'
    );
    add_rewrite_rule(
        'manhwa/type/([^/]+)/?$',
        'index.php?post_type=manhwa&filter_type=$matches[1]',
        'top'
    );
    
    // Order only
    add_rewrite_rule(
        'manhwa/order/([^/]+)/page/([0-9]+)/?$',
        'index.php?post_type=manhwa&filter_order=$matches[1]&paged=$matches[2]',
        'top'
    );
    add_rewrite_rule(
        'manhwa/order/([^/]+)/?$',
        'index.php?post_type=manhwa&filter_order=$matches[1]',
        'top'
    );
}
add_action('init', 'flavor_manhwa_filter_rewrite_rules');

/**
 * Register query vars for filter
 */
function flavor_manhwa_filter_query_vars($vars) {
    $vars[] = 'filter_genre';
    $vars[] = 'filter_status';
    $vars[] = 'filter_type';
    $vars[] = 'filter_order';
    return $vars;
}
add_filter('query_vars', 'flavor_manhwa_filter_query_vars');

/**
 * Helper function to build clean filter URL
 */
function flavor_build_filter_url($genres = [], $status = [], $type = [], $order = 'latest', $paged = 1) {
    $base_url = home_url('/manhwa/');
    $segments = [];
    
    // Genre segment (comma separated for multiple)
    if (!empty($genres)) {
        $segments[] = 'genre/' . implode(',', array_map('sanitize_title', $genres));
    }
    
    // Status segment
    if (!empty($status)) {
        $segments[] = 'status/' . implode(',', array_map('strtolower', $status));
    }
    
    // Type segment
    if (!empty($type)) {
        $segments[] = 'type/' . implode(',', array_map('strtolower', $type));
    }
    
    // Order segment (only if not default)
    if ($order && $order !== 'latest') {
        $segments[] = 'order/' . sanitize_title($order);
    }
    
    // Page segment
    if ($paged > 1) {
        $segments[] = 'page/' . intval($paged);
    }
    
    if (empty($segments)) {
        return $base_url;
    }
    
    return $base_url . implode('/', $segments) . '/';
}

/**
 * Parse clean URL filter values
 */
function flavor_parse_filter_url() {
    $filters = [
        'genre' => [],
        'status' => [],
        'type' => [],
        'order' => 'latest',
    ];
    
    // Get from clean URL query vars
    $filter_genre = get_query_var('filter_genre', '');
    $filter_status = get_query_var('filter_status', '');
    $filter_type = get_query_var('filter_type', '');
    $filter_order = get_query_var('filter_order', '');
    
    // Parse comma-separated values
    if (!empty($filter_genre)) {
        $filters['genre'] = array_filter(explode(',', $filter_genre));
    }
    if (!empty($filter_status)) {
        $filters['status'] = array_map('ucfirst', array_filter(explode(',', $filter_status)));
    }
    if (!empty($filter_type)) {
        $filters['type'] = array_map('ucfirst', array_filter(explode(',', $filter_type)));
    }
    if (!empty($filter_order)) {
        $filters['order'] = sanitize_text_field($filter_order);
    }
    
    // Fallback to GET params (for backwards compatibility)
    if (empty($filters['genre']) && isset($_GET['genre'])) {
        $filters['genre'] = array_map('sanitize_text_field', (array)$_GET['genre']);
    }
    if (empty($filters['status']) && isset($_GET['status'])) {
        $filters['status'] = array_map('sanitize_text_field', (array)$_GET['status']);
    }
    if (empty($filters['type']) && isset($_GET['type'])) {
        $filters['type'] = array_map('sanitize_text_field', (array)$_GET['type']);
    }
    if ($filters['order'] === 'latest' && isset($_GET['order'])) {
        $filters['order'] = sanitize_text_field($_GET['order']);
    }
    
    return $filters;
}

