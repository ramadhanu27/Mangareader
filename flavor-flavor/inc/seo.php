<?php
/**
 * Flavor Theme SEO Functions
 * Auto-generate meta tags, Open Graph, Schema markup
 */

if (!defined('ABSPATH')) exit;

/**
 * Helper: detect chapter reader context
 */
function flavor_is_chapter_reader() {
    return (bool) get_query_var('chapter_reader');
}

/**
 * Get SEO Title based on page type
 */
function flavor_get_seo_title() {
    $site_name = get_bloginfo('name');
    $separator = ' - ';
    
    // Chapter reader (loaded via rewrite rule, NOT is_singular)
    if (flavor_is_chapter_reader()) {
        $seo_title = get_query_var('seo_title');
        if ($seo_title) return $seo_title;
        return 'Chapter Reader' . $separator . $site_name;
    }
    
    if (is_front_page() || is_home()) {
        $tagline = get_bloginfo('description');
        return $site_name . ($tagline ? $separator . $tagline : '');
    }
    
    if (is_singular('manhwa')) {
        $title = get_the_title();
        $type = get_post_meta(get_the_ID(), '_manhwa_type', true);
        if ($type) $title = $title . ' ' . $type;
        return $title . $separator . $site_name;
    }
    
    if (is_tax('manhwa_genre')) {
        $term = get_queried_object();
        return 'Manhwa ' . $term->name . ' Terbaru' . $separator . $site_name;
    }
    
    if (is_tax('manhwa_status')) {
        $term = get_queried_object();
        return 'Manhwa ' . $term->name . $separator . $site_name;
    }
    
    if (is_post_type_archive('manhwa')) {
        return 'Daftar Manhwa' . $separator . $site_name;
    }
    
    if (is_search()) {
        return 'Hasil Pencarian: ' . get_search_query() . $separator . $site_name;
    }
    
    if (is_404()) {
        return 'Halaman Tidak Ditemukan' . $separator . $site_name;
    }
    
    if (is_singular()) {
        return get_the_title() . $separator . $site_name;
    }
    
    if (is_archive()) {
        return get_the_archive_title() . $separator . $site_name;
    }
    
    return $site_name;
}

/**
 * Get SEO Description based on page type
 */
function flavor_get_seo_description() {
    $site_name = get_bloginfo('name');
    
    // Chapter reader
    if (flavor_is_chapter_reader()) {
        $seo_desc = get_query_var('seo_description');
        if ($seo_desc) return $seo_desc;
        return 'Baca chapter manhwa bahasa Indonesia gratis di ' . $site_name . '.';
    }
    
    if (is_front_page() || is_home()) {
        $custom_desc = get_option('flavor_seo_home_description', '');
        if ($custom_desc) return $custom_desc;
        return 'Baca manhwa, manhua, dan manga terbaru bahasa Indonesia gratis. Update chapter terbaru setiap hari di ' . $site_name . '.';
    }
    
    if (is_singular('manhwa')) {
        global $post;
        // Use post_content as synopsis
        $content = $post->post_content;
        if ($content) {
            return wp_trim_words(strip_tags($content), 25, '...');
        }
        $excerpt = get_the_excerpt();
        if ($excerpt) {
            return wp_trim_words(strip_tags($excerpt), 25, '...');
        }
        return 'Baca ' . get_the_title() . ' bahasa Indonesia lengkap di ' . $site_name . '.';
    }
    
    if (is_tax('manhwa_genre')) {
        $term = get_queried_object();
        return 'Daftar manhwa genre ' . $term->name . ' lengkap bahasa Indonesia. Baca gratis di ' . $site_name . '.';
    }
    
    if (is_tax('manhwa_status')) {
        $term = get_queried_object();
        return 'Daftar manhwa dengan status ' . $term->name . '. Baca gratis di ' . $site_name . '.';
    }
    
    if (is_post_type_archive('manhwa')) {
        return 'Daftar lengkap semua manhwa bahasa Indonesia di ' . $site_name . '. Update setiap hari.';
    }
    
    if (is_search()) {
        return 'Hasil pencarian untuk "' . get_search_query() . '" di ' . $site_name . '. Temukan manhwa favorit Anda.';
    }
    
    return get_bloginfo('description') ?: 'Baca manhwa bahasa Indonesia gratis di ' . $site_name . '.';
}

/**
 * Get Canonical URL
 */
function flavor_get_canonical_url() {
    // Chapter reader
    if (flavor_is_chapter_reader()) {
        $canonical = get_query_var('canonical_url');
        if ($canonical) return $canonical;
    }
    
    if (is_front_page()) {
        return home_url('/');
    }
    
    if (is_singular()) {
        return get_permalink();
    }
    
    if (is_tax() || is_category() || is_tag()) {
        return get_term_link(get_queried_object());
    }
    
    if (is_post_type_archive()) {
        return get_post_type_archive_link(get_post_type());
    }
    
    if (is_search()) {
        return home_url('/') . '?s=' . urlencode(get_search_query());
    }
    
    return home_url($_SERVER['REQUEST_URI']);
}

/**
 * Get Featured Image URL
 */
function flavor_get_og_image() {
    // Chapter reader — use manhwa cover
    if (flavor_is_chapter_reader()) {
        $cover = get_query_var('cover_url');
        if ($cover) return $cover;
    }
    
    if (is_singular()) {
        if (has_post_thumbnail()) {
            return get_the_post_thumbnail_url(null, 'large');
        }
    }
    
    if (is_tax('manhwa_genre')) {
        $term = get_queried_object();
        $posts = get_posts(['post_type' => 'manhwa', 'tax_query' => [['taxonomy' => 'manhwa_genre', 'terms' => $term->term_id]], 'posts_per_page' => 1]);
        if (!empty($posts) && has_post_thumbnail($posts[0]->ID)) {
            return get_the_post_thumbnail_url($posts[0]->ID, 'large');
        }
    }
    
    // Default OG image from customizer
    $default_og = get_theme_mod('flavor_seo_og_image', '');
    if ($default_og) return $default_og;
    
    // Fallback to site logo
    $custom_logo_id = get_theme_mod('custom_logo');
    if ($custom_logo_id) {
        return wp_get_attachment_image_url($custom_logo_id, 'full');
    }
    
    return '';
}

/**
 * Output SEO Meta Tags
 */
function flavor_output_seo_meta() {
    // Skip if Yoast, Rank Math, or other SEO plugin is active
    if (defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || class_exists('AIOSEO\Plugin\AIOSEO')) {
        return;
    }
    
    $title = flavor_get_seo_title();
    $description = flavor_get_seo_description();
    $canonical = flavor_get_canonical_url();
    $og_image = flavor_get_og_image();
    $site_name = get_bloginfo('name');
    
    // Basic Meta Tags
    echo "\n<!-- Flavor Theme SEO -->\n";
    echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
    echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";
    
    // Open Graph Tags
    echo '<meta property="og:locale" content="id_ID">' . "\n";
    echo '<meta property="og:type" content="' . (is_singular() ? 'article' : 'website') . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url($canonical) . '">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '">' . "\n";
    
    if ($og_image) {
        echo '<meta property="og:image" content="' . esc_url($og_image) . '">' . "\n";
        echo '<meta property="og:image:width" content="1200">' . "\n";
        echo '<meta property="og:image:height" content="630">' . "\n";
    }
    
    // Twitter Card
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
    if ($og_image) {
        echo '<meta name="twitter:image" content="' . esc_url($og_image) . '">' . "\n";
    }
    
    // Article specific
    if (is_singular()) {
        echo '<meta property="article:published_time" content="' . get_the_date('c') . '">' . "\n";
        echo '<meta property="article:modified_time" content="' . get_the_modified_date('c') . '">' . "\n";
    }
    
    echo "<!-- /Flavor Theme SEO -->\n\n";
}
add_action('wp_head', 'flavor_output_seo_meta', 1);

/**
 * Filter document title
 */
function flavor_filter_document_title($title) {
    // Skip if SEO plugin is active
    if (defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || class_exists('AIOSEO\Plugin\AIOSEO')) {
        return $title;
    }
    
    return flavor_get_seo_title();
}
add_filter('pre_get_document_title', 'flavor_filter_document_title', 100);

/**
 * Output Schema.org JSON-LD
 */
function flavor_output_schema_markup() {
    // Skip if SEO plugin is active
    if (defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || class_exists('AIOSEO\Plugin\AIOSEO')) {
        return;
    }
    
    $site_name = get_bloginfo('name');
    $site_url = home_url('/');
    $logo_url = '';
    
    $custom_logo_id = get_theme_mod('custom_logo');
    if ($custom_logo_id) {
        $logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
    }
    
    $schema = [];
    
    // WebSite Schema (always)
    $website_schema = [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => $site_name,
        'url' => $site_url,
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => $site_url . '?s={search_term_string}'
            ],
            'query-input' => 'required name=search_term_string'
        ]
    ];
    
    if ($logo_url) {
        $website_schema['publisher'] = [
            '@type' => 'Organization',
            'name' => $site_name,
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $logo_url
            ]
        ];
    }
    
    $schema[] = $website_schema;
    
    // Manhwa Detail Schema — ComicSeries
    if (is_singular('manhwa')) {
        global $post;
        $meta = flavor_get_manhwa_meta($post->ID);
        
        $manhwa_schema = [
            '@context' => 'https://schema.org',
            '@type' => 'ComicSeries',
            'name' => get_the_title(),
            'url' => get_permalink(),
            'description' => flavor_get_seo_description(),
            'datePublished' => get_the_date('c'),
            'dateModified' => get_the_modified_date('c'),
            'inLanguage' => 'id',
        ];
        
        // Image
        if (has_post_thumbnail()) {
            $manhwa_schema['image'] = get_the_post_thumbnail_url(null, 'large');
        }
        
        // Rating (correct meta key: _manhwa_rating)
        if (!empty($meta['rating']) && floatval($meta['rating']) > 0) {
            $views = (int) get_post_meta($post->ID, '_manhwa_views', true);
            $manhwa_schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => floatval($meta['rating']),
                'bestRating' => 10,
                'worstRating' => 0,
                'ratingCount' => max(1, $views)
            ];
        }
        
        // Author (correct meta key: _manhwa_author)
        if (!empty($meta['author']) && $meta['author'] !== '-') {
            $manhwa_schema['author'] = [
                '@type' => 'Person',
                'name' => $meta['author']
            ];
        }
        
        // Artist
        if (!empty($meta['artist']) && $meta['artist'] !== '-') {
            $manhwa_schema['creator'] = [
                '@type' => 'Person',
                'name' => $meta['artist']
            ];
        }
        
        // Status
        if (!empty($meta['status'])) {
            $manhwa_schema['creativeWorkStatus'] = $meta['status'];
        }
        
        // Genres
        $genres = get_the_terms($post->ID, 'manhwa_genre');
        if ($genres && !is_wp_error($genres)) {
            $manhwa_schema['genre'] = wp_list_pluck($genres, 'name');
        }
        
        // Chapters count
        $chapters = get_post_meta($post->ID, '_manhwa_chapters', true);
        if (is_array($chapters) && !empty($chapters)) {
            $manhwa_schema['numberOfEpisodes'] = count($chapters);
        }
        
        $schema[] = $manhwa_schema;
    }
    
    // Chapter Reader Schema — ComicIssue
    if (flavor_is_chapter_reader()) {
        $manhwa_id = get_query_var('manhwa_id');
        $chapter   = get_query_var('chapter');
        $ch_title  = is_array($chapter) ? ($chapter['title'] ?? '') : '';
        
        $chapter_schema = [
            '@context' => 'https://schema.org',
            '@type' => 'ComicIssue',
            'name' => flavor_get_seo_title(),
            'url' => flavor_get_canonical_url(),
            'inLanguage' => 'id',
            'publisher' => [
                '@type' => 'Organization',
                'name' => $site_name,
                'url' => $site_url
            ]
        ];
        
        // Cover image
        $cover = get_query_var('cover_url');
        if ($cover) {
            $chapter_schema['image'] = $cover;
        }
        
        if ($manhwa_id) {
            $chapter_schema['isPartOf'] = [
                '@type' => 'ComicSeries',
                'name' => get_the_title($manhwa_id),
                'url' => get_permalink($manhwa_id)
            ];
        }
        
        $schema[] = $chapter_schema;
    }
    
    // BreadcrumbList Schema
    $breadcrumbs = flavor_get_breadcrumb_schema();
    if ($breadcrumbs) {
        $schema[] = $breadcrumbs;
    }
    
    // Output all schemas
    foreach ($schema as $s) {
        echo '<script type="application/ld+json">' . json_encode($s, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>' . "\n";
    }
}
add_action('wp_head', 'flavor_output_schema_markup', 2);

/**
 * Get Breadcrumb Schema
 */
function flavor_get_breadcrumb_schema() {
    $items = [];
    $position = 1;
    $site_name = get_bloginfo('name');
    
    // Home
    $items[] = [
        '@type' => 'ListItem',
        'position' => $position++,
        'name' => 'Home',
        'item' => home_url('/')
    ];
    
    if (flavor_is_chapter_reader()) {
        $manhwa_id = get_query_var('manhwa_id');
        $chapter   = get_query_var('chapter');
        $ch_title  = is_array($chapter) ? ($chapter['title'] ?? 'Chapter') : 'Chapter';
        
        $items[] = [
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => 'Manhwa',
            'item' => get_post_type_archive_link('manhwa')
        ];
        
        if ($manhwa_id) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => get_the_title($manhwa_id),
                'item' => get_permalink($manhwa_id)
            ];
        }
        
        $items[] = [
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => $ch_title
        ];
    } elseif (is_singular('manhwa')) {
        $items[] = [
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => 'Manhwa',
            'item' => get_post_type_archive_link('manhwa')
        ];
        $items[] = [
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => get_the_title()
        ];
    } elseif (is_tax('manhwa_genre')) {
        $term = get_queried_object();
        $items[] = [
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => 'Genre',
            'item' => home_url('/manhwa/')
        ];
        $items[] = [
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => $term->name
        ];
    } elseif (is_post_type_archive('manhwa')) {
        $items[] = [
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => 'Daftar Manhwa'
        ];
    }
    
    if (count($items) > 1) {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items
        ];
    }
    
    return null;
}

/**
 * Add SEO Settings to Customizer
 */
function flavor_seo_customizer($wp_customize) {
    // SEO Section
    $wp_customize->add_section('flavor_seo_section', [
        'title' => __('SEO Settings', 'flavor-flavor'),
        'priority' => 35,
    ]);
    
    // Home Meta Description
    $wp_customize->add_setting('flavor_seo_home_description', [
        'default' => '',
        'sanitize_callback' => 'sanitize_textarea_field',
    ]);
    
    $wp_customize->add_control('flavor_seo_home_description', [
        'label' => __('Homepage Meta Description', 'flavor-flavor'),
        'description' => __('Custom meta description for homepage. Leave empty for auto-generate.', 'flavor-flavor'),
        'section' => 'flavor_seo_section',
        'type' => 'textarea',
    ]);
    
    // Default OG Image
    $wp_customize->add_setting('flavor_seo_og_image', [
        'default' => '',
        'sanitize_callback' => 'esc_url_raw',
    ]);
    
    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'flavor_seo_og_image', [
        'label' => __('Default Social Share Image', 'flavor-flavor'),
        'description' => __('Default image for social sharing (recommended: 1200x630px)', 'flavor-flavor'),
        'section' => 'flavor_seo_section',
    ]));
    
    // Enable/Disable Schema
    $wp_customize->add_setting('flavor_seo_enable_schema', [
        'default' => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ]);
    
    $wp_customize->add_control('flavor_seo_enable_schema', [
        'label' => __('Enable Schema Markup', 'flavor-flavor'),
        'description' => __('Output JSON-LD structured data for rich snippets', 'flavor-flavor'),
        'section' => 'flavor_seo_section',
        'type' => 'checkbox',
    ]);
}
add_action('customize_register', 'flavor_seo_customizer');

/**
 * Add robots meta tag
 */
function flavor_robots_meta() {
    // Skip if SEO plugin is active
    if (defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || class_exists('AIOSEO\Plugin\AIOSEO')) {
        return;
    }
    
    $robots = 'index, follow';
    
    // No index for search, 404, and paginated archives beyond page 2
    if (is_search() || is_404()) {
        $robots = 'noindex, follow';
    }
    
    // No index for author archives (usually not needed for manhwa sites)
    if (is_author()) {
        $robots = 'noindex, follow';
    }
    
    echo '<meta name="robots" content="' . esc_attr($robots) . '">' . "\n";
}
add_action('wp_head', 'flavor_robots_meta', 1);

/**
 * Add preconnect for faster external resource loading
 */
function flavor_resource_hints() {
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
    echo '<link rel="dns-prefetch" href="//www.googletagmanager.com">' . "\n";
}
add_action('wp_head', 'flavor_resource_hints', 1);

/* ================================================================
 * VISUAL BREADCRUMBS (HTML)
 * ============================================================= */

/**
 * Render visible HTML breadcrumbs
 * Call this function in templates: <?php flavor_render_breadcrumbs(); ?>
 */
function flavor_render_breadcrumbs() {
    $items = [];

    // Home
    $items[] = '<a href="' . esc_url(home_url('/')) . '">Home</a>';

    if (flavor_is_chapter_reader()) {
        $manhwa_id = get_query_var('manhwa_id');
        $chapter   = get_query_var('chapter');
        $ch_title  = is_array($chapter) ? ($chapter['title'] ?? 'Chapter') : 'Chapter';

        $items[] = '<a href="' . esc_url(get_post_type_archive_link('manhwa')) . '">Manhwa</a>';
        if ($manhwa_id) {
            $items[] = '<a href="' . esc_url(get_permalink($manhwa_id)) . '">' . esc_html(get_the_title($manhwa_id)) . '</a>';
        }
        $items[] = '<span>' . esc_html($ch_title) . '</span>';

    } elseif (is_singular('manhwa')) {
        $items[] = '<a href="' . esc_url(get_post_type_archive_link('manhwa')) . '">Manhwa</a>';
        $items[] = '<span>' . esc_html(get_the_title()) . '</span>';

    } elseif (is_tax('manhwa_genre')) {
        $term = get_queried_object();
        $items[] = '<a href="' . esc_url(get_post_type_archive_link('manhwa')) . '">Manhwa</a>';
        $items[] = '<span>' . esc_html($term->name) . '</span>';

    } elseif (is_post_type_archive('manhwa')) {
        $items[] = '<span>Daftar Manhwa</span>';

    } elseif (is_search()) {
        $items[] = '<span>Pencarian: ' . esc_html(get_search_query()) . '</span>';
    }

    if (count($items) > 1) {
        echo '<nav class="flavor-breadcrumbs" aria-label="Breadcrumb">';
        echo implode(' <span class="bc-sep">›</span> ', $items);
        echo '</nav>';
    }
}

/* ================================================================
 * CHAPTER XML SITEMAP
 * Auto-detects: Yoast SEO, Rank Math, or WordPress Core Sitemaps
 * Splits into parts of max 1000 URLs each
 * ============================================================= */

/**
 * Build flat array of all chapter data for sitemap
 * Uses transient cache for performance
 */
function flavor_get_sitemap_chapters() {
    $cached = get_transient('flavor_sitemap_chapters');
    if ($cached !== false) {
        return $cached;
    }

    $chapters_list = [];

    $manhwa_posts = get_posts([
        'post_type'      => 'manhwa',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ]);

    foreach ($manhwa_posts as $manhwa_id) {
        $chapters = get_post_meta($manhwa_id, '_manhwa_chapters', true);
        if (!is_array($chapters) || empty($chapters)) continue;

        $manhwa_modified = get_post_modified_time('Y-m-d\TH:i:sP', true, $manhwa_id);

        foreach ($chapters as $ch) {
            $url = flavor_get_chapter_url($manhwa_id, $ch);
            if (!$url) continue;

            $lastmod = $manhwa_modified;
            if (!empty($ch['date'])) {
                $ts = strtotime($ch['date']);
                if ($ts) $lastmod = date('Y-m-d\TH:i:sP', $ts);
            }

            $chapters_list[] = [
                'url'     => $url,
                'lastmod' => $lastmod,
            ];
        }
    }

    set_transient('flavor_sitemap_chapters', $chapters_list, 12 * HOUR_IN_SECONDS);
    return $chapters_list;
}

/**
 * Clear sitemap cache when chapters are updated
 */
function flavor_clear_sitemap_cache($meta_id, $post_id, $meta_key) {
    if ($meta_key === '_manhwa_chapters') {
        delete_transient('flavor_sitemap_chapters');
    }
}
add_action('updated_post_meta', 'flavor_clear_sitemap_cache', 10, 3);
add_action('added_post_meta', 'flavor_clear_sitemap_cache', 10, 3);

define('FLAVOR_SITEMAP_PER_PAGE', 1000);

/**
 * Yoast SEO: Add chapter sitemaps to index
 */
function flavor_yoast_sitemap_index($index_links) {
    $all = flavor_get_sitemap_chapters();
    $total = count($all);
    if ($total === 0) return $index_links;

    $pages = (int) ceil($total / FLAVOR_SITEMAP_PER_PAGE);

    for ($i = 1; $i <= $pages; $i++) {
        $offset = ($i - 1) * FLAVOR_SITEMAP_PER_PAGE;
        $slice  = array_slice($all, $offset, FLAVOR_SITEMAP_PER_PAGE);
        $lastmod = '';
        foreach ($slice as $ch) {
            if (!empty($ch['lastmod']) && $ch['lastmod'] > $lastmod) {
                $lastmod = $ch['lastmod'];
            }
        }

        $index_links .= '<sitemap>' . "\n";
        $index_links .= '<loc>' . home_url('/chapters-' . $i . '-sitemap.xml') . '</loc>' . "\n";
        if ($lastmod) {
            $index_links .= '<lastmod>' . esc_html($lastmod) . '</lastmod>' . "\n";
        }
        $index_links .= '</sitemap>' . "\n";
    }

    return $index_links;
}
add_filter('wpseo_sitemap_index', 'flavor_yoast_sitemap_index');

/**
 * Yoast SEO: Register chapter sitemap handlers
 */
function flavor_yoast_register_sitemaps() {
    if (!defined('WPSEO_VERSION')) return;

    global $wpseo_sitemaps;
    if (!isset($wpseo_sitemaps) || !is_object($wpseo_sitemaps)) return;

    $all = flavor_get_sitemap_chapters();
    $pages = max(1, (int) ceil(count($all) / FLAVOR_SITEMAP_PER_PAGE));

    for ($i = 1; $i <= $pages; $i++) {
        $wpseo_sitemaps->register_sitemap('chapters-' . $i, 'flavor_yoast_chapter_sitemap_output');
    }
}
add_action('init', 'flavor_yoast_register_sitemaps', 20);

/**
 * Yoast SEO: Output chapter sitemap XML
 */
function flavor_yoast_chapter_sitemap_output() {
    global $wpseo_sitemaps;

    $type = '';
    if (method_exists($wpseo_sitemaps, 'get_sitemap_type_from_request')) {
        $type = $wpseo_sitemaps->get_sitemap_type_from_request();
    }

    $page_num = 1;
    if (preg_match('/chapters-(\d+)/', $type, $m)) {
        $page_num = (int) $m[1];
    }

    $all = flavor_get_sitemap_chapters();
    $offset = ($page_num - 1) * FLAVOR_SITEMAP_PER_PAGE;
    $slice  = array_slice($all, $offset, FLAVOR_SITEMAP_PER_PAGE);

    $xml  = '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ';
    $xml .= 'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ';
    $xml .= 'xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';
    $xml .= "\n";

    foreach ($slice as $item) {
        $xml .= '<url>' . "\n";
        $xml .= '  <loc>' . esc_url($item['url']) . '</loc>' . "\n";
        if (!empty($item['lastmod'])) {
            $xml .= '  <lastmod>' . esc_html($item['lastmod']) . '</lastmod>' . "\n";
        }
        $xml .= '</url>' . "\n";
    }

    $xml .= '</urlset>';
    $wpseo_sitemaps->set_sitemap($xml);
}

/**
 * WordPress Core Sitemaps API fallback (when no SEO plugin is active)
 */
function flavor_add_chapter_sitemap_provider() {
    if (defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION')) return;
    if (!class_exists('WP_Sitemaps_Provider')) return;

    $provider = new Flavor_Chapter_Sitemap_Core_Provider();
    wp_register_sitemap_provider('chapters', $provider);
}
add_action('init', 'flavor_add_chapter_sitemap_provider');

if (!class_exists('Flavor_Chapter_Sitemap_Core_Provider') && class_exists('WP_Sitemaps_Provider')) {
    class Flavor_Chapter_Sitemap_Core_Provider extends WP_Sitemaps_Provider {
        public function __construct() {
            $this->name        = 'chapters';
            $this->object_type = 'chapter';
        }
        public function get_url_list($page_num, $object_subtype = '') {
            $urls = [];
            $all = flavor_get_sitemap_chapters();
            $offset = ($page_num - 1) * FLAVOR_SITEMAP_PER_PAGE;
            $slice  = array_slice($all, $offset, FLAVOR_SITEMAP_PER_PAGE);
            foreach ($slice as $item) {
                $entry = ['loc' => $item['url']];
                if (!empty($item['lastmod'])) $entry['lastmod'] = $item['lastmod'];
                $urls[] = $entry;
            }
            return $urls;
        }
        public function get_max_num_pages($object_subtype = '') {
            $total = count(flavor_get_sitemap_chapters());
            return $total ? (int) ceil($total / FLAVOR_SITEMAP_PER_PAGE) : 0;
        }
    }
}

/**
 * Add sitemap reference to robots.txt (only when no SEO plugin handles it)
 */
function flavor_robots_txt_sitemap($output, $public) {
    if ($public && !defined('WPSEO_VERSION') && !defined('RANK_MATH_VERSION')) {
        $output .= "\nSitemap: " . home_url('/wp-sitemap.xml') . "\n";
    }
    return $output;
}
add_filter('robots_txt', 'flavor_robots_txt_sitemap', 10, 2);


/* ================================================================
 * CHAPTER IMAGE ALT TEXT HELPER
 * ============================================================= */

/**
 * Generate SEO-friendly alt text for chapter images
 * Usage: flavor_chapter_img_alt($manhwa_title, $chapter_title, $page_num)
 */
function flavor_chapter_img_alt($manhwa_title, $chapter_title, $page_num) {
    return esc_attr(sprintf('%s %s - Halaman %d', $manhwa_title, $chapter_title, $page_num));
}
