<?php
/**
 * Flavor Theme - Admin Options Page
 * Modern tabbed UI for all theme settings
 */
if (!defined('ABSPATH')) exit;

// Enqueue admin assets
function flavor_admin_options_enqueue($hook) {
    if ($hook !== 'toplevel_page_flavor-theme-options') return;
    
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_media();
    wp_enqueue_script('wp-color-picker');
    wp_enqueue_style('flavor-admin-options', get_template_directory_uri() . '/assets/css/admin-options.css', array(), '1.0.0');
    wp_enqueue_script('flavor-admin-options', get_template_directory_uri() . '/assets/js/admin-options.js', array('jquery', 'wp-color-picker'), '1.0.0', true);
}
add_action('admin_enqueue_scripts', 'flavor_admin_options_enqueue');

// Handle save on admin_init (early, before page renders)
function flavor_handle_options_save() {
    // Only run on our page
    if (!isset($_GET['page']) || $_GET['page'] !== 'flavor-theme-options') return;
    if (!isset($_POST['flavor_save_action'])) return;
    if (!isset($_POST['flavor_options_nonce']) || !wp_verify_nonce($_POST['flavor_options_nonce'], 'flavor_save_options')) return;
    if (!current_user_can('manage_options')) return;

    // Theme mod fields (checkbox = boolean)
    $bool_fields = array(
        'announcement_bar_enable', 'announcement_bar_dismissible', 'ticker_enable',
        'flavor_hero_slider_enabled', 'flavor_most_viewed_enabled', 'sidebar_global_enable', 'sidebar_homepage',
        'sidebar_single', 'sidebar_archive', 'sidebar_search', 'sidebar_taxonomy',
        'comments_enabled',
        'flavor_ads_enable', 'flavor_ads_direct_link_enable',
        'flavor_enable_minify', 'flavor_remove_wp_version', 'flavor_remove_query_strings',
        'flavor_maintenance_mode', 'flavor_maintenance_timer',
        'flavor_seo_enable_schema',
    );
    foreach ($bool_fields as $f) {
        set_theme_mod($f, isset($_POST[$f]) ? true : false);
    }

    // Text / URL / Email fields
    $text_fields = array(
        'announcement_bar_title' => 'sanitize_text_field',
        'announcement_bar_text' => 'wp_kses_post',
        'announcement_bar_bg' => 'sanitize_hex_color',
        'ticker_label' => 'sanitize_text_field',
        'ticker_text' => 'sanitize_text_field',
        'ticker_speed' => 'absint',
        'ticker_link' => 'esc_url_raw',
        'primary_color' => 'sanitize_hex_color',
        'secondary_color' => 'sanitize_hex_color',
        'flavor_hero_slider_mode' => 'sanitize_text_field',
        'flavor_hero_slider_count' => 'absint',
        'flavor_hero_slider_speed' => 'absint',
        'flavor_most_viewed_count' => 'absint',
        'contact_email' => 'sanitize_email',
        'contact_whatsapp' => 'sanitize_text_field',
        'contact_telegram' => 'sanitize_text_field',
        'social_facebook' => 'esc_url_raw',
        'social_instagram' => 'esc_url_raw',
        'social_twitter' => 'esc_url_raw',
        'social_discord' => 'esc_url_raw',
        'social_tiktok' => 'esc_url_raw',
        'social_youtube' => 'esc_url_raw',
        'social_telegram' => 'esc_url_raw',
        'trending_sort' => 'sanitize_text_field',
        'comments_system' => 'sanitize_text_field',
        'disqus_shortname' => 'sanitize_text_field',
        'comments_disabled_message' => 'sanitize_text_field',
        'tracking_google_analytics' => 'flavor_sanitize_html_code',
        'tracking_footer_scripts' => 'flavor_sanitize_html_code',
        'tracking_header_scripts' => 'flavor_sanitize_html_code',
        'flavor_maintenance_title' => 'sanitize_text_field',
        'flavor_maintenance_message' => 'sanitize_textarea_field',
        'flavor_maintenance_bg_color' => 'sanitize_hex_color',
        'flavor_maintenance_accent' => 'sanitize_hex_color',
        'flavor_maintenance_timer_date' => 'sanitize_text_field',
        'flavor_seo_og_image' => 'esc_url_raw',
        'flavor_ads_direct_link_url' => 'esc_url_raw',
        'flavor_ads_direct_link_max' => 'absint',
        'flavor_ads_direct_link_exclude' => 'sanitize_text_field',
    );
    foreach ($text_fields as $f => $sanitize) {
        if (isset($_POST[$f])) {
            set_theme_mod($f, call_user_func($sanitize, wp_unslash($_POST[$f])));
        }
    }

    // Ad code fields (no sanitize)
    $ad_fields = array('flavor_ads_header','flavor_ads_after_content','flavor_ads_sidebar','flavor_ads_before_footer','flavor_ads_in_article','flavor_ads_float_bottom','flavor_ads_before_chapter','flavor_ads_after_chapter');
    foreach ($ad_fields as $f) {
        if (isset($_POST[$f])) {
            set_theme_mod($f, wp_unslash($_POST[$f]));
        }
    }

    // Option-based fields
    if (isset($_POST['tracking_histats_code'])) {
        update_option('tracking_histats_code', wp_unslash($_POST['tracking_histats_code']));
    }
    if (isset($_POST['flavor_seo_home_description'])) {
        update_option('flavor_seo_home_description', sanitize_textarea_field(wp_unslash($_POST['flavor_seo_home_description'])));
    }

    // Trending Widget fields - saved as options with 'flavor_' prefix
    $trending_option_fields = array(
        'flavor_trending_title'        => 'sanitize_text_field',
        'flavor_trending_count'        => 'absint',
        'flavor_trending_manual_ids'   => 'sanitize_text_field',
        'flavor_trending_sort_weekly'  => 'sanitize_text_field',
        'flavor_trending_sort_monthly' => 'sanitize_text_field',
        'flavor_trending_sort_all'     => 'sanitize_text_field',
    );
    foreach ($trending_option_fields as $f => $sanitize) {
        if (isset($_POST[$f])) {
            update_option($f, call_user_func($sanitize, wp_unslash($_POST[$f])));
        }
    }
    // Trending boolean options
    $trending_bool_options = array('flavor_trending_show_tabs', 'flavor_trending_show_genres', 'flavor_trending_show_rating');
    foreach ($trending_bool_options as $f) {
        update_option($f, isset($_POST[$f]) ? 1 : 0);
    }

    // Ad slots 1-4
    for ($i = 1; $i <= 4; $i++) {
        foreach (array('name','size','desc') as $k) {
            $key = "ad_slot_{$i}_{$k}";
            if (isset($_POST[$key])) set_theme_mod($key, sanitize_text_field(wp_unslash($_POST[$key])));
        }
        $status_key = "ad_slot_{$i}_status";
        if (isset($_POST[$status_key])) set_theme_mod($status_key, sanitize_text_field(wp_unslash($_POST[$status_key])));
    }

    // Redirect back with success flag and active tab
    $active_tab = isset($_POST['fvo_active_tab']) ? sanitize_text_field($_POST['fvo_active_tab']) : 'general';
    wp_redirect(admin_url('admin.php?page=flavor-theme-options&saved=1#' . $active_tab));
    exit;
}
add_action('admin_init', 'flavor_handle_options_save');

// ============ RENDER HELPERS ============
function fvo_text($name, $label, $desc = '', $placeholder = '', $type = 'text') {
    $val = get_theme_mod($name, '');
    echo '<div class="fvo-field">';
    echo '<label class="fvo-label">' . esc_html($label) . '</label>';
    echo '<input type="' . esc_attr($type) . '" name="' . esc_attr($name) . '" value="' . esc_attr($val) . '" placeholder="' . esc_attr($placeholder) . '" class="fvo-input">';
    if ($desc) echo '<p class="fvo-desc">' . esc_html($desc) . '</p>';
    echo '</div>';
}

function fvo_textarea($name, $label, $desc = '', $rows = 3) {
    $val = get_theme_mod($name, '');
    echo '<div class="fvo-field">';
    echo '<label class="fvo-label">' . esc_html($label) . '</label>';
    echo '<textarea name="' . esc_attr($name) . '" rows="' . $rows . '" class="fvo-textarea">' . esc_textarea($val) . '</textarea>';
    if ($desc) echo '<p class="fvo-desc">' . esc_html($desc) . '</p>';
    echo '</div>';
}

function fvo_textarea_raw($name, $label, $desc = '', $rows = 3) {
    $val = get_theme_mod($name, '');
    echo '<div class="fvo-field">';
    echo '<label class="fvo-label">' . esc_html($label) . '</label>';
    echo '<textarea name="' . esc_attr($name) . '" rows="' . $rows . '" class="fvo-textarea">' . esc_textarea($val) . '</textarea>';
    if ($desc) echo '<p class="fvo-desc">' . $desc . '</p>';
    echo '</div>';
}

function fvo_color($name, $label, $default = '#ff5722') {
    $val = get_theme_mod($name, $default);
    echo '<div class="fvo-field">';
    echo '<label class="fvo-label">' . esc_html($label) . '</label>';
    echo '<div class="fvo-color-field">';
    echo '<input type="text" name="' . esc_attr($name) . '" value="' . esc_attr($val) . '" class="fvo-color-picker" data-default-color="' . esc_attr($default) . '">';
    echo '</div></div>';
}

function fvo_toggle($name, $label, $desc = '', $default = false) {
    $val = get_theme_mod($name, $default);
    echo '<div class="fvo-toggle-row">';
    echo '<div class="fvo-toggle-info"><h4>' . esc_html($label) . '</h4>';
    if ($desc) echo '<p>' . esc_html($desc) . '</p>';
    echo '</div>';
    echo '<label class="fvo-toggle"><input type="checkbox" name="' . esc_attr($name) . '" value="1"' . checked($val, true, false) . '><span class="fvo-toggle-slider"></span></label>';
    echo '</div>';
}

function fvo_select($name, $label, $choices, $desc = '', $default = '') {
    $val = get_theme_mod($name, $default);
    echo '<div class="fvo-field">';
    echo '<label class="fvo-label">' . esc_html($label) . '</label>';
    echo '<select name="' . esc_attr($name) . '" class="fvo-select">';
    foreach ($choices as $k => $v) {
        echo '<option value="' . esc_attr($k) . '"' . selected($val, $k, false) . '>' . esc_html($v) . '</option>';
    }
    echo '</select>';
    if ($desc) echo '<p class="fvo-desc">' . esc_html($desc) . '</p>';
    echo '</div>';
}

function fvo_number($name, $label, $default = 0, $min = 0, $max = 100, $desc = '') {
    $val = get_theme_mod($name, $default);
    echo '<div class="fvo-field">';
    echo '<label class="fvo-label">' . esc_html($label) . '</label>';
    echo '<input type="number" name="' . esc_attr($name) . '" value="' . esc_attr($val) . '" min="' . $min . '" max="' . $max . '" class="fvo-input fvo-input-sm">';
    if ($desc) echo '<p class="fvo-desc">' . esc_html($desc) . '</p>';
    echo '</div>';
}

// Social row helper
function fvo_social($name, $label, $icon, $placeholder) {
    $val = get_theme_mod($name, '');
    echo '<div class="fvo-social-row">';
    echo '<span class="fvo-social-label">' . esc_html($label) . '</span>';
    echo '<div class="fvo-input-icon" style="flex:1">';
    echo '<span class="material-symbols-outlined">' . esc_html($icon) . '</span>';
    echo '<input type="url" name="' . esc_attr($name) . '" value="' . esc_attr($val) . '" placeholder="' . esc_attr($placeholder) . '" class="fvo-input">';
    echo '</div></div>';
}
