<?php
/**
 * Flavor Theme HTML Minification
 * Minify HTML output to reduce page source lines
 */

if (!defined('ABSPATH')) exit;

/**
 * Start output buffering for HTML minification
 */
function flavor_start_html_minify() {
    // Don't minify in admin area
    if (is_admin()) {
        return;
    }
    
    // Check if minification is enabled (default: true)
    $enabled = get_theme_mod('flavor_enable_minify', true);
    if (!$enabled) {
        return;
    }
    
    ob_start('flavor_minify_html');
}
add_action('template_redirect', 'flavor_start_html_minify', 1);

/**
 * Minify HTML callback function
 */
function flavor_minify_html($html) {
    if (empty($html)) {
        return $html;
    }
    
    // Don't minify if it's not HTML
    if (strpos($html, '<html') === false) {
        return $html;
    }
    
    // Preserve content that shouldn't be minified
    $preserved = [];
    $index = 0;
    
    // Preserve content wrapped in <!--NOMIN-->...<!--/NOMIN--> (for tracking codes)
    $html = preg_replace_callback('/<!--NOMIN-->(.*?)<!--\/NOMIN-->/is', function($matches) use (&$preserved, &$index) {
        $placeholder = '<!--PRESERVED' . $index . 'PRESERVED-->';
        $preserved[$placeholder] = $matches[1]; // Keep content without markers
        $index++;
        return $placeholder;
    }, $html);
    
    // Preserve <pre> tags
    $html = preg_replace_callback('/<pre[^>]*>.*?<\/pre>/is', function($matches) use (&$preserved, &$index) {
        $placeholder = '<!--PRESERVED' . $index . 'PRESERVED-->';
        $preserved[$placeholder] = $matches[0];
        $index++;
        return $placeholder;
    }, $html);
    
    // Preserve <textarea> tags
    $html = preg_replace_callback('/<textarea[^>]*>.*?<\/textarea>/is', function($matches) use (&$preserved, &$index) {
        $placeholder = '<!--PRESERVED' . $index . 'PRESERVED-->';
        $preserved[$placeholder] = $matches[0];
        $index++;
        return $placeholder;
    }, $html);
    
    // Preserve <script> tags content (but not the tags themselves)
    $html = preg_replace_callback('/<script[^>]*>(.*?)<\/script>/is', function($matches) use (&$preserved, &$index) {
        if (empty(trim($matches[1]))) {
            return $matches[0];
        }
        
        // Minify inline JavaScript
        $js = $matches[1];
        $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js); // Remove multi-line comments
        $js = preg_replace('/(?<![:\'\"])\/\/(?![^\s\'\";,\)]+\.[a-z]{2,}).*$/m', '', $js); // Remove single-line comments but preserve URLs like //domain.com
        $js = preg_replace('/\s+/', ' ', $js); // Collapse whitespace
        $js = preg_replace('/\s*([\{\}\(\)\[\];,:])\s*/', '$1', $js); // Remove space around operators
        $js = trim($js);
        
        $placeholder = '<!--PRESERVED' . $index . 'PRESERVED-->';
        $preserved[$placeholder] = '<script' . (preg_match('/<script([^>]*)>/', $matches[0], $attrs) ? $attrs[1] : '') . '>' . $js . '</script>';
        $index++;
        return $placeholder;
    }, $html);
    
    // Preserve <style> tags content
    $html = preg_replace_callback('/<style[^>]*>(.*?)<\/style>/is', function($matches) use (&$preserved, &$index) {
        if (empty(trim($matches[1]))) {
            return $matches[0];
        }
        
        // Minify inline CSS
        $css = $matches[1];
        $css = preg_replace('/\/\*[\s\S]*?\*\//', '', $css); // Remove comments
        $css = preg_replace('/\s+/', ' ', $css); // Collapse whitespace
        $css = preg_replace('/\s*([\{\}:;,])\s*/', '$1', $css); // Remove space around operators
        $css = str_replace(';}', '}', $css); // Remove last semicolon
        $css = trim($css);
        
        $placeholder = '<!--PRESERVED' . $index . 'PRESERVED-->';
        $preserved[$placeholder] = '<style' . (preg_match('/<style([^>]*)>/', $matches[0], $attrs) ? $attrs[1] : '') . '>' . $css . '</style>';
        $index++;
        return $placeholder;
    }, $html);
    
    // Remove HTML comments (except IE conditionals and preserved)
    $html = preg_replace('/<!--(?!PRESERVED|\[if|\[endif).*?-->/s', '', $html);
    
    // Remove whitespace between tags
    $html = preg_replace('/>\s+</', '><', $html);
    
    // Remove multiple spaces
    $html = preg_replace('/\s{2,}/', ' ', $html);
    
    // Remove newlines and tabs
    $html = preg_replace('/[\r\n\t]+/', '', $html);
    
    // Remove spaces around = in attributes
    $html = preg_replace('/\s*=\s*/', '=', $html);
    
    // Restore preserved content
    foreach ($preserved as $placeholder => $content) {
        $html = str_replace($placeholder, $content, $html);
    }
    
    // Add one newline after doctype for validity
    $html = preg_replace('/<!DOCTYPE[^>]*>/i', '$0' . "\n", $html);
    
    return $html;
}

/**
 * Add minify setting to Customizer
 */
function flavor_minify_customizer($wp_customize) {
    // Add to existing SEO section or create Performance section
    $wp_customize->add_section('flavor_performance_section', [
        'title' => __('Performance Settings', 'flavor-flavor'),
        'priority' => 36,
    ]);
    
    // Enable HTML Minification
    $wp_customize->add_setting('flavor_enable_minify', [
        'default' => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ]);
    
    $wp_customize->add_control('flavor_enable_minify', [
        'label' => __('Enable HTML Minification', 'flavor-flavor'),
        'description' => __('Minify HTML output to reduce page source size. Disable if you experience issues.', 'flavor-flavor'),
        'section' => 'flavor_performance_section',
        'type' => 'checkbox',
    ]);
    
    // Remove WordPress Version
    $wp_customize->add_setting('flavor_remove_wp_version', [
        'default' => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ]);
    
    $wp_customize->add_control('flavor_remove_wp_version', [
        'label' => __('Remove WordPress Version', 'flavor-flavor'),
        'description' => __('Remove WordPress version from page source for security.', 'flavor-flavor'),
        'section' => 'flavor_performance_section',
        'type' => 'checkbox',
    ]);
    
    // Remove Query Strings from Static Resources
    $wp_customize->add_setting('flavor_remove_query_strings', [
        'default' => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ]);
    
    $wp_customize->add_control('flavor_remove_query_strings', [
        'label' => __('Remove Query Strings', 'flavor-flavor'),
        'description' => __('Remove version query strings from CSS/JS files for better caching.', 'flavor-flavor'),
        'section' => 'flavor_performance_section',
        'type' => 'checkbox',
    ]);
}
add_action('customize_register', 'flavor_minify_customizer');

/**
 * Remove WordPress version from header
 */
function flavor_remove_wp_version() {
    return '';
}
add_filter('the_generator', 'flavor_remove_wp_version');

/**
 * Remove version from scripts and styles
 */
function flavor_remove_version_query($src) {
    if (strpos($src, 'ver=')) {
        $src = remove_query_arg('ver', $src);
    }
    return $src;
}
add_filter('script_loader_src', 'flavor_remove_version_query', 15, 1);
add_filter('style_loader_src', 'flavor_remove_version_query', 15, 1);

/**
 * Remove unnecessary meta tags
 */
function flavor_cleanup_head() {
    // Remove RSD link
    remove_action('wp_head', 'rsd_link');
    
    // Remove Windows Live Writer manifest
    remove_action('wp_head', 'wlwmanifest_link');
    
    // Remove shortlink
    remove_action('wp_head', 'wp_shortlink_wp_head');
    
    // Remove REST API link
    remove_action('wp_head', 'rest_output_link_wp_head');
    
    // Remove oEmbed discovery links
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    
    // Remove WordPress emoji scripts
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('admin_print_styles', 'print_emoji_styles');
}
add_action('init', 'flavor_cleanup_head');

/**
 * Disable XML-RPC for security
 */
add_filter('xmlrpc_enabled', '__return_false');

/**
 * Remove feed links if not needed
 */
function flavor_remove_feed_links() {
    remove_action('wp_head', 'feed_links', 2);
    remove_action('wp_head', 'feed_links_extra', 3);
}
// Uncomment if you want to remove RSS feeds:
// add_action('after_setup_theme', 'flavor_remove_feed_links');
