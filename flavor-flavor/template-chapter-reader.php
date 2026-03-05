<?php
/**
 * Chapter Reader Page Template
 * Based on ZeistManga v5.5 Chapter Layout
 *
 * Template Name: Chapter Reader
 * 
 * This template can be loaded in 2 ways:
 * 1. Via plugin manhwa-manager (receives data via set_query_var)
 * 2. Via WordPress page template (fetches data itself)
 *
 * @package Flavor_Flavor
 */

// === Data fetching SEBELUM get_header() agar setcookie() bisa jalan ===

// Check if loaded by plugin (plugin passes chapter_data)
$plugin_data = get_query_var('chapter_data');

if ($plugin_data) {
    // ===== LOADED BY PLUGIN =====
    // Get all variables from plugin
    $manhwa_id = get_query_var('manhwa_id');
    $manhwa = get_post($manhwa_id);
    $manhwa_slug = get_query_var('manhwa_slug');
    $chapter = get_query_var('chapter');
    $chapter_images = get_query_var('images');
    $prev_chapter = get_query_var('prev_chapter');
    $next_chapter = get_query_var('next_chapter');
    $prev_url = get_query_var('prev_url');
    $next_url = get_query_var('next_url');
    $chapters = get_query_var('all_chapters');
    $chapter_num = get_query_var('current_ch_num');
    $current_chapter = $chapter;
    
    // Get chapter num function from plugin
    $get_chapter_num_func = get_query_var('get_chapter_num');
    $get_chapter_url_func = get_query_var('get_chapter_url_func');
    
} else {
    // ===== LOADED AS PAGE TEMPLATE =====
    // Get chapter data from clean URL or fallback to query parameters
    $manhwa_slug = get_query_var('manhwa_slug', '');
    $chapter_slug = get_query_var('chapter_slug', '');
    
    // Fallback to old query parameters for backwards compatibility
    if (empty($manhwa_slug) && isset($_GET['manhwa_id'])) {
        $manhwa_id = intval($_GET['manhwa_id']);
        $manhwa = get_post($manhwa_id);
    } else {
        // Get manhwa by slug
        $manhwa = get_page_by_path($manhwa_slug, OBJECT, 'manhwa');
        $manhwa_id = $manhwa ? $manhwa->ID : 0;
    }
    
    if (empty($chapter_slug) && isset($_GET['chapter'])) {
        $chapter_num = sanitize_text_field($_GET['chapter']);
    } else {
        // Convert slug back to chapter number (e.g., "chapter-01" -> "Chapter 01")
        $chapter_num = str_replace('-', ' ', $chapter_slug);
        $chapter_num = ucwords($chapter_num);
    }
    
    // Get manhwa data
    $manhwa_meta = $manhwa_id ? flavor_get_manhwa_meta($manhwa_id) : array();
    $chapters = $manhwa_id ? flavor_get_manhwa_chapters($manhwa_id) : array();
    
    // Find current chapter
    $current_chapter = null;
    $current_index = -1;
    $prev_chapter = null;
    $next_chapter = null;
    
    foreach ($chapters as $index => $chapter) {
        $ch_num = $chapter['number'] ?? $chapter['title'] ?? '';
        $ch_slug = sanitize_title($ch_num);
        
        // Match by slug or original number/title
        if ($ch_slug == $chapter_slug || $ch_num == $chapter_num || ($chapter['title'] ?? '') == $chapter_num) {
            $current_chapter = $chapter;
            $current_index = $index;
            
            // Previous chapter (next in array since array is descending)
            if (isset($chapters[$index + 1])) {
                $prev_chapter = $chapters[$index + 1];
            }
            
            // Next chapter (previous in array since array is descending)
            if (isset($chapters[$index - 1])) {
                $next_chapter = $chapters[$index - 1];
            }
            break;
        }
    }
    
    // Get chapter images
    $chapter_images = array();
    if ($current_chapter && !empty($current_chapter['images'])) {
        $chapter_images = $current_chapter['images'];
    }
    
    // Build clean nav URLs using theme function
    $prev_url = $prev_chapter && $manhwa ? flavor_get_chapter_url($manhwa_id, $prev_chapter) : '';
    $next_url = $next_chapter && $manhwa ? flavor_get_chapter_url($manhwa_id, $next_chapter) : '';
    
    // Set up helper functions
    $get_chapter_num_func = function($ch) {
        $num = $ch['number'] ?? $ch['title'] ?? '1';
        if (preg_match('/([\d.]+)/', $num, $m)) {
            return $m[1];
        }
        return '1';
    };
}

// Track chapter view (sebelum get_header agar cookie bisa di-set)
if (!empty($manhwa_id) && !empty($chapter_num)) {
    $view_key = 'chapter_viewed_' . $manhwa_id . '_' . md5($chapter_num);
    if (!isset($_COOKIE[$view_key])) {
        // Increment chapter view
        flavor_increment_chapter_views($manhwa_id, $chapter_num);
        // Increment overall manhwa view  
        flavor_increment_views($manhwa_id);
        // Set cookie untuk mencegah duplikat selama 1 jam
        setcookie($view_key, '1', time() + 3600, '/');
        $_COOKIE[$view_key] = '1';
    }
}

// Sekarang baru panggil get_header() setelah cookie di-set
get_header();
?>

<style>

/* Chapter Reader Specific Styles */
/* Supports both dark and light mode via CSS variables */

/* Override body and wrapper backgrounds */
body.page-template-template-chapter-reader {
    background-color: var(--body-bg) !important;
}

body.page-template-template-chapter-reader .site-wrapper,
body.page-template-template-chapter-reader .site-content,
body.page-template-template-chapter-reader #page,
body.page-template-template-chapter-reader main {
    background-color: var(--body-bg) !important;
}

/* Only target container inside chapter-reader, not in header */
body.page-template-template-chapter-reader .chapter-reader .container {
    background-color: var(--body-bg) !important;
}

/* Override site header for chapter reader */
body.page-template-template-chapter-reader .site-header,
body.page-template-template-chapter-reader header.site-header {
    background: linear-gradient(to right, var(--primary-color), var(--secondary-color, #ff5722)) !important;
    border-bottom: none;
    position: relative !important;
}

body.page-template-template-chapter-reader .header-inner {
    background: transparent !important;
}

/* Override all cards in chapter reader */
.chapter-reader .card,
.chapter-reader .card-body,
.chapter-reader .card-header {
    background-color: var(--card-bg) !important;
    border-color: var(--border-color) !important;
    color: var(--text-color);
}

.chapter-reader .card-header {
    border-bottom-color: var(--border-color) !important;
}

.chapter-reader {
    background-color: var(--body-bg);
    min-height: 100vh;
    padding-top: 10px;
}

.chapter-reader .container {
    max-width: 950px;
    background-color: transparent;
}

.chapter-reader * {
    box-sizing: border-box;
}

.reader-header {
    padding: 35px 0 20px;
    text-align: center;
}

.reader-title {
    font-size: 22px;
    color: var(--text-color);
    margin: 0 0 10px 0;
}

.reader-title a {
    color: var(--text-color);
}

.reader-title a:hover {
    color: var(--primary-color);
}

.reader-subtitle {
    text-align: center;
    font-size: 14px;
    color: var(--text-muted);
    margin-bottom: 15px;
}

.reader-subtitle a {
    color: var(--primary-color);
    font-weight: 600;
}

/* Share Buttons in Reader */
.reader-share {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-bottom: 20px;
}

.reader-share .share-btn {
    width: 32px;
    height: 32px;
}

/* Breadcrumb */
.reader-breadcrumb {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 6px;
    background: var(--card-bg);
    padding: 10px 15px;
    border-radius: var(--border-radius);
    margin-bottom: 20px;
    font-size: 13px;
    color: var(--text-muted);
    border: 1px solid var(--border-color);
    line-height: 1;
}

.reader-breadcrumb a {
    color: var(--text-muted);
    text-decoration: none;
    transition: color 0.2s;
}

.reader-breadcrumb a:hover {
    color: var(--primary-color);
}

.reader-breadcrumb .bc-home {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.reader-breadcrumb .bc-chevron {
    flex-shrink: 0;
    color: var(--text-muted);
    opacity: 0.35;
}

.reader-breadcrumb .current {
    color: var(--text-color);
    font-weight: 500;
}

/* Reader Info Text */
.reader-info {
    text-align: center;
    font-size: 14px;
    color: var(--text-muted);
    margin-bottom: 15px;
    padding: 15px;
    background: var(--card-bg);
    border-radius: var(--border-radius);
    border: 1px solid var(--border-color);
    line-height: 1.7;
}

.reader-info strong {
    color: var(--primary-color);
}

/* Report Button */
.reader-report {
    display: block;
    text-align: center;
    background: var(--primary-color);
    color: white;
    padding: 8px 15px;
    border-radius: 5px;
    max-width: 950px;
    margin: 0 auto 20px;
    font-size: 14px;
}

.reader-report a {
    color: #fff;
    font-weight: 600;
    text-decoration: underline;
}

/* Chapter Navigation */
.reader-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    padding: 15px;
    background: var(--card-bg);
    border-radius: var(--border-radius);
    margin-bottom: 15px;
    border: 1px solid var(--border-color);
}

.reader-nav .nav-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 10px 20px;
    background: var(--primary-color);
    color: white;
    border-radius: var(--border-radius);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: background 0.2s, transform 0.2s;
}

.reader-nav .nav-btn:hover {
    background: #e64a19;
    color: white;
    transform: scale(1.02);
}

.reader-nav .nav-btn.disabled {
    background: var(--border-color);
    color: var(--text-muted);
    pointer-events: none;
}

.reader-nav .nav-btn svg {
    width: 18px;
    height: 18px;
}

.chapter-select-wrap {
    flex: 1;
    max-width: 300px;
}

.chapter-select {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    background: var(--card-bg);
    color: var(--text-color);
    font-size: 14px;
    cursor: pointer;
}

.chapter-select:focus {
    outline: 2px solid var(--primary-color);
}

/* Chapter Images Container */
.reader-images-wrap {
    background: var(--body-bg);
}

.reader-images {
    max-width: 950px;
    margin: 0 auto;
    background: #000;
}

.reader-images img {
    display: block;
    width: 100%;
    height: auto;
    margin: 0 auto;
}

/* Lazy Loading Styles */
.lazy-image-container {
    position: relative;
    width: 100%;
    min-height: 200px;
    background: var(--card-bg);
    overflow: hidden;
}

.lazy-image-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(128,128,128,0.1), transparent);
    animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
    0% { left: -100%; }
    100% { left: 100%; }
}

.lazy-image-container .lazy-placeholder {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: var(--text-muted);
}

.lazy-image-container img {
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
}

.lazy-image-container img.loaded {
    opacity: 1;
}

.lazy-image-container.loaded {
    min-height: auto;
    background: transparent;
}

.lazy-image-container.loaded::before {
    display: none;
}

.lazy-image-container.loaded .lazy-placeholder {
    display: none;
}

/* Reading progress bar */
.reading-progress {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    pointer-events: none;
}

.reading-progress-bar {
    height: 100%;
    background: linear-gradient(to right, var(--primary-color), #ff9800);
    width: 0%;
    transition: width 0.15s ease-out;
    box-shadow: 0 0 10px var(--primary-color);
}

/* Chapter Tags */
.reader-tags {
    background: var(--card-bg);
    padding: 15px;
    border-radius: var(--border-radius);
    margin-top: 20px;
    font-size: 13px;
    color: var(--text-muted);
    line-height: 1.8;
    border: 1px solid var(--border-color);
}

/* Related Manga in Reader */
.reader-related {
    margin-top: 20px;
}

/* Floating navigation */
.floating-nav {
    position: fixed;
    bottom: 20px;
    right: 20px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    z-index: 999;
}

.floating-nav .fab {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: rgba(23, 21, 27, 0.92);
    color: rgba(255,255,255,0.8);
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0,0,0,0.35), 0 0 0 1px rgba(255,255,255,0.06);
    backdrop-filter: blur(16px);
    transition: all 0.2s;
}

.floating-nav .fab:hover {
    background: rgba(255,255,255,0.1);
    color: #fff;
    transform: scale(1.05);
}

.floating-nav .fab.disabled {
    opacity: 0.4;
    pointer-events: none;
    background: rgba(23, 21, 27, 0.5);
    box-shadow: 0 0 0 1px rgba(255,255,255,0.04);
}

.floating-nav .fab svg {
    width: 20px;
    height: 20px;
}

/* Scroll to top (inside floating nav) */
.scroll-to-top {
    display: none;
}

.scroll-to-top.visible {
    display: flex;
}

/* ── Reader Toolbar (Unified Zoom + Auto-scroll) ── */
.reader-toolbar {
    position: fixed;
    bottom: 20px;
    left: 20px;
    display: flex;
    flex-direction: column;
    gap: 0;
    background: rgba(23, 21, 27, 0.92);
    border-radius: 16px;
    z-index: 1000;
    box-shadow: 0 8px 32px rgba(0,0,0,0.35), 0 0 0 1px rgba(255,255,255,0.06);
    backdrop-filter: blur(16px);
    overflow: hidden;
    min-width: 180px;
    transition: opacity 0.3s, transform 0.3s;
}

/* Row shared styles */
.reader-toolbar .tb-row {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
}

.reader-toolbar .tb-divider {
    height: 1px;
    background: rgba(255,255,255,0.08);
    margin: 0 10px;
}

/* Labels */
.reader-toolbar .tb-label {
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: rgba(255,255,255,0.35);
    min-width: 32px;
}

/* Circular buttons (shared) */
.reader-toolbar .tb-btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(255,255,255,0.08);
    color: rgba(255,255,255,0.7);
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    padding: 0;
}

.reader-toolbar .tb-btn:hover {
    background: rgba(255,255,255,0.15);
    color: #fff;
    transform: scale(1.1);
}

.reader-toolbar .tb-btn:active {
    transform: scale(0.92);
}

/* Play button — accent colored */
.reader-toolbar .tb-play {
    width: 36px;
    height: 36px;
    background: var(--primary-color);
    color: #fff;
    box-shadow: 0 2px 8px rgba(255, 87, 34, 0.3);
}

.reader-toolbar .tb-play:hover {
    background: #ff6e40;
    box-shadow: 0 4px 16px rgba(255, 87, 34, 0.5);
}

.reader-toolbar .tb-play.active {
    background: #f44336;
    box-shadow: 0 2px 12px rgba(244, 67, 54, 0.4);
    animation: tb-pulse 2s ease-in-out infinite;
}

@keyframes tb-pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(244,67,54,0.35); }
    50% { box-shadow: 0 0 0 8px rgba(244,67,54,0); }
}

/* Value display */
.reader-toolbar .tb-value {
    font-size: 13px;
    font-weight: 700;
    color: #fff;
    min-width: 36px;
    text-align: center;
    user-select: none;
}

/* Small round buttons (speed +/-, zoom +/-) */
.reader-toolbar .tb-sm {
    width: 28px;
    height: 28px;
    font-size: 15px;
    font-weight: 600;
}

/* Reset button */
.reader-toolbar .tb-reset {
    padding: 4px 10px;
    border-radius: 12px;
    background: rgba(255,255,255,0.06);
    color: rgba(255,255,255,0.4);
    border: none;
    font-size: 11px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    margin-left: auto;
}

.reader-toolbar .tb-reset:hover {
    background: rgba(255,255,255,0.12);
    color: rgba(255,255,255,0.8);
}

/* Zoom Level Classes - Applied to reader-images */
.reader-images.zoom-50 {
    max-width: 475px;
}

.reader-images.zoom-75 {
    max-width: 712px;
}

.reader-images.zoom-100 {
    max-width: 950px;
}

.reader-images.zoom-125 {
    max-width: 1187px;
}

.reader-images.zoom-150 {
    max-width: 1425px;
}

.reader-images.zoom-175 {
    max-width: 1662px;
}

.reader-images.zoom-200 {
    max-width: 1900px;
}

@media (max-width: 768px) {
    .reader-toolbar {
        bottom: 15px;
        left: 10px;
        min-width: auto;
        border-radius: 14px;
    }
    .reader-toolbar .tb-row { padding: 8px 10px; gap: 6px; }
    .reader-toolbar .tb-zoom-row { display: none; }
    .reader-toolbar .tb-divider { display: none; }
    .reader-toolbar .tb-play { width: 32px; height: 32px; }
    .reader-toolbar .tb-sm { width: 24px; height: 24px; font-size: 13px; }
    .reader-toolbar .tb-value { font-size: 12px; min-width: 28px; }
    .reader-toolbar .tb-label { display: none; }
    .reader-title {
        font-size: 18px;
    }
    
    .reader-nav {
        flex-wrap: wrap;
    }
    
    .reader-nav .nav-btn {
        padding: 8px 12px;
        font-size: 13px;
    }
    
    .chapter-select-wrap {
        order: -1;
        width: 100%;
        max-width: 100%;
    }
    
    .floating-nav {
        bottom: 15px;
        right: 15px;
    }
    
    .floating-nav .fab {
        width: 45px;
        height: 45px;
    }
}
</style>

<div class="chapter-reader">
    <!-- Reading Progress Bar -->
    <div class="reading-progress">
        <div class="reading-progress-bar" id="readingProgress"></div>
    </div>
    
    <div class="container">
        
        <?php if ($manhwa && $current_chapter): ?>
        
        
        
        <!-- Header -->
        <div class="reader-header">
            <h1 class="reader-title">
                <a href="<?php echo get_permalink($manhwa_id); ?>"><?php echo esc_html($manhwa->post_title); ?></a>
                - <?php echo wp_kses_post(flavor_format_chapter_number($chapter_num)); ?>
            </h1>
            <div class="reader-subtitle">
                <?php esc_html_e('All chapters are in', 'flavor-flavor'); ?>
                <a href="<?php echo get_permalink($manhwa_id); ?>"><?php echo esc_html($manhwa->post_title); ?></a>
            </div>
            
            <!-- Share Buttons -->
            <div class="reader-share">
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(get_permalink()); ?>" target="_blank" class="share-btn facebook" rel="nofollow noopener">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18.77 7.46H14.5v-1.9c0-.9.6-1.1 1-1.1h3V.5h-4.33C10.24.5 9.5 3.44 9.5 5.32v2.15h-3v4h3v12h5v-12h3.85l.42-4z"/></svg>
                </a>
                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(get_permalink()); ?>&text=<?php echo urlencode($manhwa->post_title . ' - ' . flavor_format_chapter_number($chapter_num)); ?>" target="_blank" class="share-btn twitter" rel="nofollow noopener">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M23.44 4.83c-.8.37-1.5.38-2.22.02.93-.56.98-.96 1.32-2.02-.88.52-1.86.9-2.9 1.1-.82-.88-2-1.43-3.3-1.43-2.5 0-4.55 2.04-4.55 4.54 0 .36.03.7.1 1.04-3.77-.2-7.12-2-9.36-4.75-.4.67-.6 1.45-.6 2.3 0 1.56.8 2.95 2 3.77-.74-.03-1.44-.23-2.05-.57v.06c0 2.2 1.56 4.03 3.64 4.44-.67.2-1.37.2-2.06.08.58 1.8 2.26 3.12 4.25 3.16C5.78 18.1 3.37 18.74 1 18.46c2 1.3 4.4 2.04 6.97 2.04 8.35 0 12.92-6.92 12.92-12.93 0-.2 0-.4-.02-.6.9-.63 1.96-1.22 2.56-2.14z"/></svg>
                </a>
                <a href="https://wa.me/?text=<?php echo urlencode($manhwa->post_title . ' - ' . flavor_format_chapter_number($chapter_num) . ' ' . get_permalink()); ?>" target="_blank" class="share-btn whatsapp" rel="nofollow noopener">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                </a>
            </div>
        </div>
        
        <!-- Breadcrumb -->
        <nav class="reader-breadcrumb" aria-label="Breadcrumb">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="bc-home">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                <span>Home</span>
            </a>
            <svg class="bc-chevron" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            <a href="<?php echo esc_url(get_post_type_archive_link('manhwa')); ?>">Manhwa</a>
            <svg class="bc-chevron" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            <a href="<?php echo esc_url(get_permalink($manhwa_id)); ?>"><?php echo esc_html($manhwa->post_title); ?></a>
            <svg class="bc-chevron" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            <span class="current"><?php echo wp_kses_post(flavor_format_chapter_number($chapter_num)); ?></span>
        </nav>
        
        <!-- Info Text -->
        <div class="reader-info">
            <?php printf(
                esc_html__('Read manga %1$s %2$s in Indonesian latest at %3$s. Manga %1$s in Indonesian always updated at %3$s. Dont forget to read other manga updates. The %3$s manga collection list is in the Manga List menu.', 'flavor-flavor'),
                '<strong>' . esc_html($manhwa->post_title) . '</strong>',
                '<strong>' . wp_kses_post(flavor_format_chapter_number($chapter_num)) . '</strong>',
                '<strong>' . get_bloginfo('name') . '</strong>'
            ); ?>
        </div>
        
        <!-- Report Button -->
        <div class="reader-report">
            <button type="button" class="report-btn" id="openReportModal">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path>
                    <line x1="4" y1="22" x2="4" y2="15"></line>
                </svg>
                <?php esc_html_e('Laporkan Masalah', 'flavor-flavor'); ?>
            </button>
        </div>
        
        <!-- Report Modal -->
        <div class="report-modal-overlay" id="reportModalOverlay" style="display: none;">
            <div class="report-modal">
                <div class="report-modal-header">
                    <h3><?php esc_html_e('Laporkan Masalah Chapter', 'flavor-flavor'); ?></h3>
                    <button type="button" class="close-modal" id="closeReportModal">&times;</button>
                </div>
                <form id="reportForm" class="report-form">
                    <input type="hidden" name="action" value="flavor_submit_report">
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('flavor_report_nonce'); ?>">
                    <input type="hidden" name="chapter_id" value="<?php echo get_the_ID(); ?>">
                    <input type="hidden" name="manhwa_id" value="<?php echo $manhwa_id; ?>">
                    <input type="hidden" name="chapter_url" value="<?php echo esc_url(get_permalink()); ?>">
                    <input type="hidden" name="chapter_number" value="<?php echo esc_attr($chapter_num); ?>">
                    
                    <div class="form-group">
                        <label><?php esc_html_e('Jenis Masalah', 'flavor-flavor'); ?></label>
                        <select name="issue_type" required>
                            <option value=""><?php esc_html_e('Pilih masalah...', 'flavor-flavor'); ?></option>
                            <option value="broken_images"><?php esc_html_e('🖼️ Gambar Rusak / Tidak Muncul', 'flavor-flavor'); ?></option>
                            <option value="missing_pages"><?php esc_html_e('📄 Halaman Hilang / Kurang', 'flavor-flavor'); ?></option>
                            <option value="wrong_chapter"><?php esc_html_e('🔢 Urutan Chapter Salah', 'flavor-flavor'); ?></option>
                            <option value="duplicate"><?php esc_html_e('📑 Konten Duplikat', 'flavor-flavor'); ?></option>
                            <option value="inappropriate"><?php esc_html_e('⚠️ Konten Tidak Pantas', 'flavor-flavor'); ?></option>
                            <option value="other"><?php esc_html_e('❓ Masalah Lainnya', 'flavor-flavor'); ?></option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><?php esc_html_e('Deskripsi (opsional)', 'flavor-flavor'); ?></label>
                        <textarea name="description" rows="3" placeholder="<?php esc_attr_e('Jelaskan masalahnya...', 'flavor-flavor'); ?>"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label><?php esc_html_e('Email Anda (opsional)', 'flavor-flavor'); ?></label>
                        <input type="email" name="reporter_email" placeholder="<?php esc_attr_e('email@anda.com', 'flavor-flavor'); ?>">
                        <small><?php esc_html_e('Kami akan memberitahu saat masalah sudah diperbaiki.', 'flavor-flavor'); ?></small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn-cancel" id="cancelReport"><?php esc_html_e('Batal', 'flavor-flavor'); ?></button>
                        <button type="submit" class="btn-submit" id="submitReport">
                            <span class="btn-text"><?php esc_html_e('Kirim Laporan', 'flavor-flavor'); ?></span>
                            <span class="btn-loading" style="display: none;"><?php esc_html_e('Mengirim...', 'flavor-flavor'); ?></span>
                        </button>
                    </div>
                    
                    <div class="report-message" id="reportMessage" style="display: none;"></div>
                </form>
            </div>
        </div>
        
        <!-- Top Navigation -->
        <div class="reader-nav">
            <?php if ($prev_chapter): ?>
                <a href="<?php echo esc_url($prev_url); ?>" class="nav-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m15 18-6-6 6-6"></path>
                    </svg>
                    <?php esc_html_e('Prev', 'flavor-flavor'); ?>
                </a>
            <?php else: ?>
                <span class="nav-btn disabled">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m15 18-6-6 6-6"></path>
                    </svg>
                    <?php esc_html_e('Prev', 'flavor-flavor'); ?>
                </span>
            <?php endif; ?>
            
            <div class="chapter-select-wrap">
                <!-- Desktop: Native Select -->
                <select class="chapter-select desktop-only" id="chapterSelect" onchange="window.location.href=this.value">
                    <?php foreach ($chapters as $ch): 
                        $ch_num_raw = $ch['number'] ?? $ch['title'] ?? '';
                        $ch_num_clean = preg_replace('/[^0-9.]/', '', $ch_num_raw);
                        $ch_url = flavor_get_chapter_url($manhwa_id, $ch);
                        $selected = ($ch_num_clean == $chapter_num || $ch_num_raw == $chapter_num) ? 'selected' : '';
                    ?>
                        <option value="<?php echo esc_url($ch_url); ?>" <?php echo $selected; ?>>
                            <?php echo wp_kses_post($ch['title'] ?? flavor_format_chapter_number($ch_num_raw)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <!-- Mobile: Custom Button -->
                <button type="button" class="chapter-select-btn mobile-only" id="openChapterModal">
                    <?php echo wp_kses_post(flavor_format_chapter_number($chapter_num)); ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>
            </div>
            
            <!-- Chapter Modal for Mobile -->
            <div class="chapter-modal" id="chapterModal">
                <div class="chapter-modal-overlay" onclick="closeChapterModal()"></div>
                <div class="chapter-modal-content">
                    <div class="chapter-modal-header">
                        <h3><?php esc_html_e('Pilih Chapter', 'flavor-flavor'); ?></h3>
                        <button type="button" class="chapter-modal-close" onclick="closeChapterModal()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                    <div class="chapter-modal-list">
                        <?php foreach ($chapters as $ch): 
                            $ch_num_raw = $ch['number'] ?? $ch['title'] ?? '';
                            $ch_num_clean = preg_replace('/[^0-9.]/', '', $ch_num_raw);
                            $ch_url = flavor_get_chapter_url($manhwa_id, $ch);
                            $is_current = ($ch_num_clean == $chapter_num || $ch_num_raw == $chapter_num);
                        ?>
                            <a href="<?php echo esc_url($ch_url); ?>" class="chapter-modal-item <?php echo $is_current ? 'current' : ''; ?>">
                                <?php echo wp_kses_post($ch['title'] ?? flavor_format_chapter_number($ch_num_raw)); ?>
                                <?php if ($is_current): ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <?php if ($next_chapter): ?>
                <a href="<?php echo esc_url($next_url); ?>" class="nav-btn">
                    <?php esc_html_e('Next', 'flavor-flavor'); ?>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m9 18 6-6-6-6"></path>
                    </svg>
                </a>
            <?php else: ?>
                <span class="nav-btn disabled">
                    <?php esc_html_e('Next', 'flavor-flavor'); ?>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m9 18 6-6-6-6"></path>
                    </svg>
                </span>
            <?php endif; ?>
        </div>
        
    </div><!-- .container -->
    
    <!-- Ad: Before Reading Chapter -->
    <?php if (get_theme_mod('flavor_ads_enable', true) && get_theme_mod('flavor_ads_before_chapter')): ?>
    <div class="fvo-ad-container" style="text-align: center; margin: 20px auto; max-width: 950px; padding: 0 15px;">
        <?php echo do_shortcode(get_theme_mod('flavor_ads_before_chapter')); ?>
    </div>
    <?php endif; ?>

    <!-- Chapter Images -->
    <div class="reader-images-wrap">
        <div class="reader-images" id="readerImages">
            <?php if (!empty($chapter_images)): ?>
                <?php foreach ($chapter_images as $index => $image): 
                    // Handle both string and array format
                    $img_url = is_array($image) ? ($image['url'] ?? $image['src'] ?? '') : $image;
                ?>
                    <div class="lazy-image-container" data-index="<?php echo $index; ?>">
                        <div class="lazy-placeholder">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                                <circle cx="9" cy="9" r="2"/>
                                <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                            </svg>
                        </div>
                        <img data-src="<?php echo esc_url($img_url); ?>" 
                             alt="<?php echo function_exists('flavor_chapter_img_alt') ? flavor_chapter_img_alt($manhwa->post_title, $current_chapter['title'] ?? 'Chapter', $index + 1) : esc_attr(sprintf(__('Page %d', 'flavor-flavor'), $index + 1)); ?>"
                             decoding="async"
                             onerror="this.onerror=null; this.src='<?php echo esc_url(flavor_get_placeholder_image()); ?>'; this.classList.add('loaded'); this.parentElement.classList.add('loaded');">
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 60px 20px; color: #999;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="margin-bottom: 20px;">
                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                        <path d="M12 9v4"></path>
                        <path d="M12 17h.01"></path>
                    </svg>
                    <h3 style="color: #ddd; margin-bottom: 10px;"><?php esc_html_e('No images available', 'flavor-flavor'); ?></h3>
                    <p><?php esc_html_e('This chapter has no images yet.', 'flavor-flavor'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Ad: After Reading Chapter -->
    <?php if (get_theme_mod('flavor_ads_enable', true) && get_theme_mod('flavor_ads_after_chapter')): ?>
    <div class="fvo-ad-container" style="text-align: center; margin: 20px auto; max-width: 950px; padding: 0 15px;">
        <?php echo do_shortcode(get_theme_mod('flavor_ads_after_chapter')); ?>
    </div>
    <?php endif; ?>
    
    <div class="container">
        
        <!-- Bottom Navigation -->
        <div class="reader-nav">
            <?php if ($prev_chapter): ?>
                <a href="<?php echo esc_url($prev_url); ?>" class="nav-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m15 18-6-6 6-6"></path>
                    </svg>
                    <?php esc_html_e('Previous Chapter', 'flavor-flavor'); ?>
                </a>
            <?php else: ?>
                <span class="nav-btn disabled">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m15 18-6-6 6-6"></path>
                    </svg>
                    <?php esc_html_e('Previous Chapter', 'flavor-flavor'); ?>
                </span>
            <?php endif; ?>
            
            <a href="<?php echo get_permalink($manhwa_id); ?>" class="nav-btn" style="background: #666;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                    <line x1="8" x2="21" y1="6" y2="6"></line>
                    <line x1="8" x2="21" y1="12" y2="12"></line>
                    <line x1="8" x2="21" y1="18" y2="18"></line>
                    <line x1="3" x2="3.01" y1="6" y2="6"></line>
                    <line x1="3" x2="3.01" y1="12" y2="12"></line>
                    <line x1="3" x2="3.01" y1="18" y2="18"></line>
                </svg>
                <?php esc_html_e('Chapter List', 'flavor-flavor'); ?>
            </a>
            
            <?php if ($next_chapter): ?>
                <a href="<?php echo esc_url($next_url); ?>" class="nav-btn">
                    <?php esc_html_e('Next Chapter', 'flavor-flavor'); ?>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m9 18 6-6-6-6"></path>
                    </svg>
                </a>
            <?php else: ?>
                <span class="nav-btn disabled">
                    <?php esc_html_e('Next Chapter', 'flavor-flavor'); ?>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m9 18 6-6-6-6"></path>
                    </svg>
                </span>
            <?php endif; ?>
        </div>
        
        <!-- Chapter Tags (SEO) -->
        <div class="reader-tags">
            <?php printf(
                esc_html__('Tags: read manga %1$s %2$s Indonesian, comic %1$s Indonesian comic, read %2$s online, %2$s new komiku, %1$s %2$s chapter, high quality sub indo, manhwa web, %3$s, %4$s', 'flavor-flavor'),
                esc_html($manhwa->post_title),
                wp_kses_post(flavor_format_chapter_number($chapter_num)),
                get_the_date('', $manhwa_id),
                get_the_author_meta('display_name', $manhwa->post_author)
            ); ?>
        </div>
        
        <!-- Related Manga -->
        <?php 
        $genres = get_the_terms($manhwa_id, 'manhwa_genre');
        if ($genres && !is_wp_error($genres)): 
            $genre_ids = wp_list_pluck($genres, 'term_id');
            $related = get_posts(array(
                'post_type'      => 'manhwa',
                'posts_per_page' => 6,
                'post__not_in'   => array($manhwa_id),
                'tax_query'      => array(
                    array(
                        'taxonomy' => 'manhwa_genre',
                        'field'    => 'term_id',
                        'terms'    => $genre_ids,
                    ),
                ),
            ));
            
            if ($related):
        ?>
        <div class="reader-related">
            <section class="card">
                <div class="card-header">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect width="7" height="7" x="3" y="3" rx="1"></rect>
                        <rect width="7" height="7" x="14" y="3" rx="1"></rect>
                        <rect width="7" height="7" x="14" y="14" rx="1"></rect>
                        <rect width="7" height="7" x="3" y="14" rx="1"></rect>
                    </svg>
                    <?php esc_html_e('Related Manga', 'flavor-flavor'); ?>
                </div>
                <div class="card-body">
                    <div class="manga-grid">
                        <?php foreach ($related as $rel): 
                            $rel_meta = flavor_get_manhwa_meta($rel->ID);
                        ?>
                        <article class="manga-item">
                            <div class="manga-thumb">
                                <a href="<?php echo get_permalink($rel->ID); ?>">
                                    <?php if (has_post_thumbnail($rel->ID)): ?>
                                        <?php echo get_the_post_thumbnail($rel->ID, 'manga-thumb'); ?>
                                    <?php else: ?>
                                        <img src="<?php echo esc_url(flavor_get_placeholder_image()); ?>" alt="<?php echo esc_attr($rel->post_title); ?>">
                                    <?php endif; ?>
                                </a>
                                
                                <!-- Type Badge Image (Flag) -->
                                <?php if (!empty($rel_meta['type'])): 
                                    $type_lower = strtolower($rel_meta['type']);
                                    $type_image = '';
                                    if (in_array($type_lower, ['manga', 'manhua', 'manhwa'])) {
                                        $type_image = get_template_directory_uri() . '/assets/images/' . $type_lower . '.png';
                                    }
                                    if ($type_image):
                                ?>
                                    <img src="<?php echo esc_url($type_image); ?>" alt="<?php echo esc_attr($rel_meta['type']); ?>" class="type-badge-img">
                                <?php endif; endif; ?>
                                
                                <!-- Rating Badge -->
                                <?php if (!empty($rel_meta['rating']) && $rel_meta['rating'] > 0): ?>
                                    <span class="score-badge">
                                        <svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                                        <?php echo esc_html($rel_meta['rating']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="manga-info">
                                <h3 class="manga-title">
                                    <a href="<?php echo get_permalink($rel->ID); ?>"><?php echo esc_html($rel->post_title); ?></a>
                                </h3>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        </div>
        <?php endif; endif; ?>
        
        
        
        <?php else: ?>
        
        <!-- Error: Chapter not found -->
        <div style="text-align: center; padding: 100px 20px; color: #999;">
            <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="var(--primary-color)" stroke-width="1" style="margin-bottom: 20px;">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M12 16v-4"></path>
                <path d="M12 8h.01"></path>
            </svg>
            <h2 style="color: #ddd; margin-bottom: 15px;"><?php esc_html_e('Chapter Not Found', 'flavor-flavor'); ?></h2>
            <p style="margin-bottom: 25px;"><?php esc_html_e('The chapter you are looking for could not be found.', 'flavor-flavor'); ?></p>
            <a href="<?php echo esc_url(home_url('/')); ?>" class="nav-btn" style="display: inline-flex;">
                <?php esc_html_e('Go Home', 'flavor-flavor'); ?>
            </a>
        </div>
        
        <?php endif; ?>
        
    </div><!-- .container -->
    
    <!-- Floating Navigation -->
    <div class="floating-nav">
        <!-- Scroll to Top (moved inside floating-nav) -->
        <button class="fab scroll-to-top" id="scrollToTop" title="<?php esc_attr_e('Scroll to top', 'flavor-flavor'); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="m18 15-6-6-6 6"/>
            </svg>
        </button>

        <?php if ($prev_chapter): ?>
            <a href="<?php echo esc_url($prev_url); ?>" class="fab" title="<?php esc_attr_e('Previous Chapter', 'flavor-flavor'); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m15 18-6-6 6-6"></path>
                </svg>
            </a>
        <?php else: ?>
            <span class="fab disabled">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m15 18-6-6 6-6"></path>
                </svg>
            </span>
        <?php endif; ?>
        
        <?php if ($next_chapter): ?>
            <a href="<?php echo esc_url($next_url); ?>" class="fab" title="<?php esc_attr_e('Next Chapter', 'flavor-flavor'); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m9 18 6-6-6-6"></path>
                </svg>
            </a>
        <?php else: ?>
            <span class="fab disabled">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m9 18 6-6-6-6"></path>
                </svg>
            </span>
        <?php endif; ?>
    </div>
    
    <!-- Reader Toolbar (Unified Zoom + Auto-Scroll) -->
    <div class="reader-toolbar" id="readerToolbar">
        <!-- Auto-Scroll Row -->
        <div class="tb-row">
            <span class="tb-label">Scroll</span>
            <button class="tb-btn tb-play" id="asToggle" title="<?php esc_attr_e('Auto Scroll (Space)', 'flavor-flavor'); ?>">
                <svg id="asPlayIcon" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><polygon points="6 3 20 12 6 21 6 3"></polygon></svg>
                <svg id="asPauseIcon" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="currentColor" style="display:none"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>
            </button>
            <button class="tb-btn tb-sm" id="asSlower" title="<?php esc_attr_e('Slower', 'flavor-flavor'); ?>">−</button>
            <span class="tb-value" id="asSpeedLabel">3x</span>
            <button class="tb-btn tb-sm" id="asFaster" title="<?php esc_attr_e('Faster', 'flavor-flavor'); ?>">+</button>
        </div>
        
        <!-- Divider -->
        <div class="tb-divider"></div>
        
        <!-- Zoom Row -->
        <div class="tb-row tb-zoom-row">
            <span class="tb-label">Zoom</span>
            <div style="width: 36px; height: 36px;"></div> <!-- Spacer matching tb-play size -->
            <button class="tb-btn tb-sm" id="zoomOut" title="<?php esc_attr_e('Zoom Out', 'flavor-flavor'); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            </button>
            <span class="tb-value" id="zoomLevel">100%</span>
            <button class="tb-btn tb-sm" id="zoomIn" title="<?php esc_attr_e('Zoom In', 'flavor-flavor'); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            </button>
            <button class="tb-reset" id="zoomReset" title="<?php esc_attr_e('Reset', 'flavor-flavor'); ?>">Reset</button>
        </div>
    </div>
    
</div><!-- .chapter-reader -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ========================================
    // Report Modal Functionality
    // ========================================
    const reportBtn = document.getElementById('openReportModal');
    const reportOverlay = document.getElementById('reportModalOverlay');
    const closeBtn = document.getElementById('closeReportModal');
    const cancelBtn = document.getElementById('cancelReport');
    const reportForm = document.getElementById('reportForm');
    const reportMessage = document.getElementById('reportMessage');
    
    function openModal() {
        reportOverlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function closeModal() {
        reportOverlay.style.display = 'none';
        document.body.style.overflow = '';
        reportForm.reset();
        reportMessage.style.display = 'none';
    }
    
    if (reportBtn) reportBtn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    
    // Close on overlay click
    if (reportOverlay) {
        reportOverlay.addEventListener('click', function(e) {
            if (e.target === reportOverlay) closeModal();
        });
    }
    
    // Close on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && reportOverlay && reportOverlay.style.display === 'flex') {
            closeModal();
        }
    });
    
    // Form submit
    if (reportForm) {
        reportForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitReport');
            const btnText = submitBtn.querySelector('.btn-text');
            const btnLoading = submitBtn.querySelector('.btn-loading');
            
            // Show loading
            btnText.style.display = 'none';
            btnLoading.style.display = 'inline';
            submitBtn.disabled = true;
            
            // Get form data
            const formData = new FormData(reportForm);
            
            // AJAX submit
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                reportMessage.style.display = 'block';
                if (data.success) {
                    reportMessage.className = 'report-message success';
                    reportMessage.innerHTML = '✅ ' + data.data.message;
                    // Close modal after 2 seconds
                    setTimeout(closeModal, 2000);
                } else {
                    reportMessage.className = 'report-message error';
                    reportMessage.innerHTML = '❌ ' + (data.data?.message || 'Failed to submit report');
                }
            })
            .catch(error => {
                reportMessage.style.display = 'block';
                reportMessage.className = 'report-message error';
                reportMessage.innerHTML = '❌ Network error. Please try again.';
            })
            .finally(() => {
                btnText.style.display = 'inline';
                btnLoading.style.display = 'none';
                submitBtn.disabled = false;
            });
        });
    }
    
    // ========================================
    // Lazy Loading with Intersection Observer
    const lazyImageContainers = document.querySelectorAll('.lazy-image-container');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const container = entry.target;
                const img = container.querySelector('img[data-src]');
                
                if (img && img.dataset.src) {
                    // Start loading the image
                    img.src = img.dataset.src;
                    
                    img.onload = function() {
                        img.classList.add('loaded');
                        container.classList.add('loaded');
                        // Remove data-src to prevent reloading
                        delete img.dataset.src;
                    };
                    
                    // Stop observing this container
                    observer.unobserve(container);
                }
            }
        });
    }, {
        rootMargin: '200px 0px', // Start loading 200px before visible
        threshold: 0.01
    });
    
    // Observe all lazy image containers
    lazyImageContainers.forEach(container => {
        imageObserver.observe(container);
    });
    
    // Fallback: Load first 3 images immediately for better UX
    lazyImageContainers.forEach((container, index) => {
        if (index < 3) {
            const img = container.querySelector('img[data-src]');
            if (img && img.dataset.src) {
                img.src = img.dataset.src;
                img.onload = function() {
                    img.classList.add('loaded');
                    container.classList.add('loaded');
                    delete img.dataset.src;
                };
                imageObserver.unobserve(container);
            }
        }
    });
    
    // Reading progress
    const progressBar = document.getElementById('readingProgress');
    const readerImages = document.getElementById('readerImages');
    
    if (progressBar && readerImages) {
        window.addEventListener('scroll', function() {
            const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const scrolled = (winScroll / height) * 100;
            progressBar.style.width = scrolled + '%';
        });
    }
    
    // Scroll to top
    const scrollBtn = document.getElementById('scrollToTop');
    if (scrollBtn) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                scrollBtn.classList.add('visible');
            } else {
                scrollBtn.classList.remove('visible');
            }
        });
        
        scrollBtn.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
    
    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        <?php if ($prev_chapter): ?>
        if (e.key === 'ArrowLeft') {
            window.location.href = '<?php echo esc_url($prev_url); ?>';
        }
        <?php endif; ?>
        
        <?php if ($next_chapter): ?>
        if (e.key === 'ArrowRight') {
            window.location.href = '<?php echo esc_url($next_url); ?>';
        }
        <?php endif; ?>
    });
    
    // Add to reading history (server-side)
    <?php if (is_user_logged_in()): ?>
    (function() {
        var hAjaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
        var hNonce = '<?php echo wp_create_nonce('flavor_auth_nonce'); ?>';
        var hManhwaId = <?php echo $manhwa_id; ?>;
        var hChapter = '<?php echo esc_js($chapter_num); ?>';
        var hChapterKey = '<?php echo esc_js(strtolower($chapter_num)); ?>';
        var hUrl = window.location.href;
        var hTitle = '<?php echo esc_js($manhwa ? $manhwa->post_title : ''); ?>';
        var hManhwaUrl = '<?php echo esc_js($manhwa ? get_permalink($manhwa->ID) : ''); ?>';
        var hThumb = '<?php echo esc_js($manhwa ? (get_the_post_thumbnail_url($manhwa->ID, 'medium') ?: '') : ''); ?>';
        
        // Save per-manhwa chapter history
        var fd = new FormData();
        fd.append('action', 'flavor_history_save_chapter');
        fd.append('nonce', hNonce);
        fd.append('manhwa_id', hManhwaId);
        fd.append('chapter', hChapter);
        fd.append('chapter_key', hChapterKey);
        fd.append('url', hUrl);
        fetch(hAjaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' }).catch(function(){});
        
        // Save global reading history (for profile)
        var fd2 = new FormData();
        fd2.append('action', 'flavor_history_save');
        fd2.append('nonce', hNonce);
        fd2.append('manhwa_id', hManhwaId);
        fd2.append('chapter', hChapter);
        fd2.append('chapter_key', hChapterKey);
        fd2.append('url', hUrl);
        fd2.append('manhwa_title', hTitle);
        fd2.append('manhwa_url', hManhwaUrl);
        fd2.append('thumbnail', hThumb);
        fetch(hAjaxUrl, { method: 'POST', body: fd2, credentials: 'same-origin' }).catch(function(){});
    })();
    <?php endif; ?>
    
    // Zoom Controls
    const readerToolbar = document.getElementById('readerToolbar');
    const zoomInBtn = document.getElementById('zoomIn');
    const zoomOutBtn = document.getElementById('zoomOut');
    const zoomResetBtn = document.getElementById('zoomReset');
    const zoomLevelDisplay = document.getElementById('zoomLevel');
    const readerImagesContainer = document.getElementById('readerImages');
    
    // Zoom levels available
    const zoomLevels = [50, 75, 100, 125, 150, 175, 200];
    let currentZoomIndex = 2; // Start at 100%
    
    // Load saved zoom preference
    try {
        const savedZoom = localStorage.getItem('flavor_reader_zoom');
        if (savedZoom) {
            const zoomValue = parseInt(savedZoom);
            const idx = zoomLevels.indexOf(zoomValue);
            if (idx !== -1) {
                currentZoomIndex = idx;
            }
        }
    } catch(e) {}
    
    function applyZoom() {
        if (!readerImagesContainer) return;
        
        // Remove all zoom classes
        zoomLevels.forEach(z => {
            readerImagesContainer.classList.remove('zoom-' + z);
        });
        
        // Add current zoom class
        const currentZoom = zoomLevels[currentZoomIndex];
        readerImagesContainer.classList.add('zoom-' + currentZoom);
        
        // Update display
        if (zoomLevelDisplay) {
            zoomLevelDisplay.textContent = currentZoom + '%';
        }
        
        // Save preference
        try {
            localStorage.setItem('flavor_reader_zoom', currentZoom.toString());
        } catch(e) {}
    }
    
    // Apply initial zoom
    applyZoom();
    
    // Zoom In
    if (zoomInBtn) {
        zoomInBtn.addEventListener('click', function() {
            if (currentZoomIndex < zoomLevels.length - 1) {
                currentZoomIndex++;
                applyZoom();
            }
        });
    }
    
    // Zoom Out
    if (zoomOutBtn) {
        zoomOutBtn.addEventListener('click', function() {
            if (currentZoomIndex > 0) {
                currentZoomIndex--;
                applyZoom();
            }
        });
    }
    
    // Reset Zoom to 100%
    if (zoomResetBtn) {
        zoomResetBtn.addEventListener('click', function() {
            currentZoomIndex = 2; // 100%
            applyZoom();
        });
    }
    
    // Keyboard shortcuts for zoom (Ctrl + / Ctrl -)
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey || e.metaKey) {
            if (e.key === '=' || e.key === '+') {
                e.preventDefault();
                if (currentZoomIndex < zoomLevels.length - 1) {
                    currentZoomIndex++;
                    applyZoom();
                }
            } else if (e.key === '-') {
                e.preventDefault();
                if (currentZoomIndex > 0) {
                    currentZoomIndex--;
                    applyZoom();
                }
            } else if (e.key === '0') {
                e.preventDefault();
                currentZoomIndex = 0; // Reset to 75%
                applyZoom();
            }
        }
    });
    
    // ========================================
    // Auto-Scroll System
    // ========================================
    const asToggle = document.getElementById('asToggle');
    const asPlayIcon = document.getElementById('asPlayIcon');
    const asPauseIcon = document.getElementById('asPauseIcon');
    const asSlower = document.getElementById('asSlower');
    const asFaster = document.getElementById('asFaster');
    const asSpeedLabel = document.getElementById('asSpeedLabel');
    
    let asRunning = false;
    let asSpeed = 3; // 1-10
    let asRafId = null;
    let asPaused = false; // temporarily paused by user input
    let asPauseTimer = null;
    let asLastTime = 0;
    let asAccum = 0; // accumulated fractional pixels
    
    // Load saved speed
    try {
        const saved = localStorage.getItem('flavor_autoscroll_speed');
        if (saved) {
            const v = parseInt(saved);
            if (v >= 1 && v <= 10) asSpeed = v;
        }
    } catch(e) {}
    
    function asUpdateUI() {
        if (asSpeedLabel) asSpeedLabel.textContent = asSpeed + 'x';
        if (asPlayIcon) asPlayIcon.style.display = asRunning ? 'none' : '';
        if (asPauseIcon) asPauseIcon.style.display = asRunning ? '' : 'none';
        if (asToggle) asToggle.classList.toggle('active', asRunning);
    }
    
    asUpdateUI();
    
    function asStep(timestamp) {
        if (!asRunning) return;
        
        if (!asLastTime) asLastTime = timestamp;
        const delta = timestamp - asLastTime;
        asLastTime = timestamp;
        
        // Skip if temporarily paused by user input
        if (asPaused) {
            asRafId = requestAnimationFrame(asStep);
            return;
        }
        
        // Check if at bottom
        const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
        if (window.scrollY >= maxScroll - 2) {
            asStop();
            return;
        }
        
        // Calculate pixels to scroll: speed * pixels-per-ms * delta
        // Speed 1 = ~30px/sec, Speed 10 = ~300px/sec
        asAccum += asSpeed * 0.03 * delta;
        
        if (asAccum >= 1) {
            const px = Math.floor(asAccum);
            asAccum -= px;
            window.scrollBy(0, px);
        }
        
        asRafId = requestAnimationFrame(asStep);
    }
    
    function asStart() {
        if (asRunning) return;
        asRunning = true;
        asPaused = false;
        asLastTime = 0;
        asAccum = 0;
        asUpdateUI();
        asRafId = requestAnimationFrame(asStep);
    }
    
    function asStop() {
        asRunning = false;
        asPaused = false;
        asUpdateUI();
        if (asRafId) {
            cancelAnimationFrame(asRafId);
            asRafId = null;
        }
        asLastTime = 0;
        asAccum = 0;
    }
    
    function asToggleScroll() {
        asRunning ? asStop() : asStart();
    }
    
    // Temporarily pause when user scrolls manually (wheel/touch)
    function asUserInterrupt() {
        if (!asRunning) return;
        asPaused = true;
        clearTimeout(asPauseTimer);
        asPauseTimer = setTimeout(function() {
            asPaused = false;
            asLastTime = 0; // reset delta so no jump
        }, 600);
    }
    
    window.addEventListener('wheel', asUserInterrupt, { passive: true });
    window.addEventListener('touchstart', asUserInterrupt, { passive: true });
    
    // Toggle button
    if (asToggle) {
        asToggle.addEventListener('click', asToggleScroll);
    }
    
    // Speed controls
    if (asSlower) {
        asSlower.addEventListener('click', function() {
            if (asSpeed > 1) {
                asSpeed--;
                asUpdateUI();
                try { localStorage.setItem('flavor_autoscroll_speed', asSpeed.toString()); } catch(e) {}
            }
        });
    }
    
    if (asFaster) {
        asFaster.addEventListener('click', function() {
            if (asSpeed < 10) {
                asSpeed++;
                asUpdateUI();
                try { localStorage.setItem('flavor_autoscroll_speed', asSpeed.toString()); } catch(e) {}
            }
        });
    }
    
    // Keyboard: Space to toggle auto-scroll
    document.addEventListener('keydown', function(e) {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT' || e.target.isContentEditable) return;
        
        if (e.code === 'Space') {
            e.preventDefault();
            asToggleScroll();
        }
    });
});

// Chapter Modal Functions
function openChapterModal() {
    document.getElementById('chapterModal').classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // Scroll to current chapter
    setTimeout(() => {
        const currentItem = document.querySelector('.chapter-modal-item.current');
        if (currentItem) {
            currentItem.scrollIntoView({ block: 'center' });
        }
    }, 100);
}

function closeChapterModal() {
    document.getElementById('chapterModal').classList.remove('active');
    document.body.style.overflow = '';
}

// Open modal when button clicked
document.getElementById('openChapterModal')?.addEventListener('click', openChapterModal);

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeChapterModal();
    }
});
</script>

<?php 
// Set post ID for comments/reactions on chapter pages
// Use a unique hash based on manhwa_id + chapter to keep comments per-chapter
global $flavor_comment_post_id;
$flavor_comment_post_id = isset($manhwa_id) ? $manhwa_id : get_the_ID();
// If we have chapter info, create unique ID per chapter using a simple offset
if (!empty($chapter_num)) {
    // Use manhwa_id * 10000 + hash to create unique IDs per chapter
    $ch_hash = abs(crc32($manhwa_id . '_' . $chapter_num)) % 900000 + 100000;
    $flavor_comment_post_id = $ch_hash;
}
?>

<?php // Post Reactions & Comments Section ?>
<div class="container" style="max-width: var(--blog-width); margin: 0 auto; padding: 0 15px;">
    <?php get_template_part('template-parts/post-reactions'); ?>
    <?php get_template_part('template-parts/custom-comments'); ?>
</div>

<?php get_footer(); ?>
