<?php
/**
 * Template Name: A-Z Manga List
 * 
 * Halaman daftar manga A-Z dengan fitur:
 * - Navigasi huruf A-Z + #(angka/simbol)
 * - Filter: Genre, Status, Type (checkbox dropdown)
 * - Tampilan list dengan info lengkap
 * - Jumlah manga per huruf
 * - Search bar
 *
 * @package Flavor_Flavor
 */

get_header();

// Get current letter filter
$current_letter = isset($_GET['letter']) ? strtoupper(sanitize_text_field($_GET['letter'])) : '';
$current_genres = isset($_GET['genre']) ? array_map('sanitize_text_field', (array)$_GET['genre']) : array();
$current_status = isset($_GET['status']) ? array_map('sanitize_text_field', (array)$_GET['status']) : array();
$current_type = isset($_GET['type']) ? array_map('sanitize_text_field', (array)$_GET['type']) : array();
$search_query = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$az_page = isset($_GET['az_page']) ? max(1, absint($_GET['az_page'])) : 1;
$per_page = 30;

// Letters array
$letters = array_merge(['#'], range('A', 'Z'));

// Build base query for counting all letters
$count_args = array(
    'post_type'      => 'manhwa',
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'orderby'        => 'title',
    'order'          => 'ASC',
);

// Apply genre filter to count
if (!empty($current_genres)) {
    $count_args['tax_query'][] = array(
        'taxonomy' => 'manhwa_genre',
        'field'    => 'slug',
        'terms'    => $current_genres,
        'operator' => 'IN',
    );
}

// Apply status filter to count
if (!empty($current_status)) {
    if (count($current_status) === 1) {
        $count_args['meta_query'][] = array(
            'key'     => '_manhwa_status',
            'value'   => $current_status[0],
            'compare' => 'LIKE',
        );
    } else {
        $status_conditions = array('relation' => 'OR');
        foreach ($current_status as $s) {
            $status_conditions[] = array(
                'key'     => '_manhwa_status',
                'value'   => $s,
                'compare' => 'LIKE',
            );
        }
        $count_args['meta_query'][] = $status_conditions;
    }
}

// Apply type filter to count
if (!empty($current_type)) {
    if (count($current_type) === 1) {
        $count_args['meta_query'][] = array(
            'key'     => '_manhwa_type',
            'value'   => $current_type[0],
            'compare' => 'LIKE',
        );
    } else {
        $type_conditions = array('relation' => 'OR');
        foreach ($current_type as $t) {
            $type_conditions[] = array(
                'key'     => '_manhwa_type',
                'value'   => $t,
                'compare' => 'LIKE',
            );
        }
        $count_args['meta_query'][] = $type_conditions;
    }
}

// Get all manhwa IDs for letter counting
$all_manhwa = new WP_Query($count_args);
$letter_counts = array();
foreach ($letters as $letter) {
    $letter_counts[$letter] = 0;
}

if ($all_manhwa->have_posts()) {
    while ($all_manhwa->have_posts()) {
        $all_manhwa->the_post();
        $title = get_the_title();
        $first_char = strtoupper(mb_substr($title, 0, 1));
        if (ctype_alpha($first_char)) {
            if (isset($letter_counts[$first_char])) {
                $letter_counts[$first_char]++;
            }
        } else {
            $letter_counts['#']++;
        }
    }
    wp_reset_postdata();
}

$total_count = $all_manhwa->found_posts;

// Now build query for displaying results
$display_args = array(
    'post_type'      => 'manhwa',
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
);

// Apply letter filter
if (!empty($current_letter)) {
    if ($current_letter === '#') {
        $display_args['posts_per_page'] = -1;
    } else {
        $display_args['title_starts_with'] = $current_letter;
    }
}

// Apply search
if (!empty($search_query)) {
    $display_args['s'] = $search_query;
}

// Apply filters
if (!empty($current_genres)) {
    $display_args['tax_query'][] = array(
        'taxonomy' => 'manhwa_genre',
        'field'    => 'slug',
        'terms'    => $current_genres,
        'operator' => 'IN',
    );
}

if (!empty($current_status)) {
    if (count($current_status) === 1) {
        $display_args['meta_query'][] = array(
            'key'     => '_manhwa_status',
            'value'   => $current_status[0],
            'compare' => 'LIKE',
        );
    } else {
        $status_conditions = array('relation' => 'OR');
        foreach ($current_status as $s) {
            $status_conditions[] = array(
                'key'     => '_manhwa_status',
                'value'   => $s,
                'compare' => 'LIKE',
            );
        }
        $display_args['meta_query'][] = $status_conditions;
    }
}

if (!empty($current_type)) {
    if (count($current_type) === 1) {
        $display_args['meta_query'][] = array(
            'key'     => '_manhwa_type',
            'value'   => $current_type[0],
            'compare' => 'LIKE',
        );
    } else {
        $type_conditions = array('relation' => 'OR');
        foreach ($current_type as $t) {
            $type_conditions[] = array(
                'key'     => '_manhwa_type',
                'value'   => $t,
                'compare' => 'LIKE',
            );
        }
        $display_args['meta_query'][] = $type_conditions;
    }
}

// Custom filter for title_starts_with
add_filter('posts_where', function($where) use ($current_letter) {
    global $wpdb;
    if (!empty($current_letter) && $current_letter !== '#') {
        $where .= $wpdb->prepare(" AND {$wpdb->posts}.post_title LIKE %s", $current_letter . '%');
    }
    return $where;
});

$manga_query = new WP_Query($display_args);

// Remove the filter
remove_all_filters('posts_where');

// Filter results for '#' (non-alphabetic)
$filtered_posts = array();
if ($manga_query->have_posts()) {
    while ($manga_query->have_posts()) {
        $manga_query->the_post();
        $title = get_the_title();
        $first_char = strtoupper(mb_substr($title, 0, 1));
        
        if ($current_letter === '#') {
            if (!ctype_alpha($first_char)) {
                $filtered_posts[] = get_post();
            }
        } else {
            $filtered_posts[] = get_post();
        }
    }
    wp_reset_postdata();
}

// Pagination calculations
$total_filtered = count($filtered_posts);
$total_pages = ceil($total_filtered / $per_page);
$az_page = min($az_page, max(1, $total_pages));
$offset = ($az_page - 1) * $per_page;
$paged_posts = array_slice($filtered_posts, $offset, $per_page);

// Get all genres for filter
$genres = get_terms(array(
    'taxonomy'   => 'manhwa_genre',
    'hide_empty' => true,
    'orderby'    => 'name',
));

// Count active filters
$filter_count = count($current_genres) + count($current_status) + count($current_type);

$page_url = get_permalink();
?>

<div class="container">
    <div class="site-main" style="grid-template-columns: 1fr;">
        <div id="primary" class="content-area">
            
            <section class="card az-list-page">
                <!-- Header -->
                <div class="card-header az-header">
                    <span>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline; vertical-align: middle;">
                            <path d="M3 7V5c0-1.1.9-2 2-2h2"></path>
                            <path d="M17 3h2c1.1 0 2 .9 2 2v2"></path>
                            <path d="M21 17v2c0 1.1-.9 2-2 2h-2"></path>
                            <path d="M7 21H5c-1.1 0-2-.9-2-2v-2"></path>
                            <path d="M8 7h8"></path>
                            <path d="M8 12h8"></path>
                            <path d="M8 17h8"></path>
                        </svg>
                        A-Z Manga List
                        <span class="text-muted" style="font-size: 13px; margin-left: 10px;">
                            (<?php echo $total_count; ?> titles)
                        </span>
                    </span>
                </div>

                <!-- Search Bar -->
                <div class="az-search-bar">
                    <form method="get" action="<?php echo esc_url($page_url); ?>" class="az-search-form">
                        <div class="az-search-input-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.3-4.3"></path>
                            </svg>
                            <input type="text" name="search" value="<?php echo esc_attr($search_query); ?>" placeholder="Cari judul manga..." class="az-search-input">
                        </div>
                        <?php foreach ($current_genres as $g): ?>
                            <input type="hidden" name="genre[]" value="<?php echo esc_attr($g); ?>">
                        <?php endforeach; ?>
                        <?php foreach ($current_status as $s): ?>
                            <input type="hidden" name="status[]" value="<?php echo esc_attr($s); ?>">
                        <?php endforeach; ?>
                        <?php foreach ($current_type as $t): ?>
                            <input type="hidden" name="type[]" value="<?php echo esc_attr($t); ?>">
                        <?php endforeach; ?>
                        <button type="submit" class="az-search-btn">Cari</button>
                    </form>
                </div>

                <!-- Letter Navigation -->
                <div class="az-letter-nav">
                    <a href="<?php echo esc_url($page_url); ?>" 
                       class="az-letter-item <?php echo empty($current_letter) && empty($current_genres) && empty($current_status) && empty($current_type) ? 'active' : ''; ?>">
                        All
                        <span class="az-letter-count"><?php echo $total_count; ?></span>
                    </a>
                    <?php foreach ($letters as $letter): ?>
                        <a href="<?php echo esc_url(add_query_arg('letter', $letter, $page_url)); ?>" 
                           class="az-letter-item <?php echo ($current_letter === $letter) ? 'active' : ''; ?> <?php echo ($letter_counts[$letter] === 0) ? 'empty' : ''; ?>">
                            <?php echo esc_html($letter); ?>
                            <span class="az-letter-count"><?php echo $letter_counts[$letter]; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Filters (Checkbox Dropdowns) -->
                <div class="az-filters">
                    <form method="get" action="<?php echo esc_url($page_url); ?>" class="az-filter-form" id="azFilterForm">
                        <?php if (!empty($current_letter)): ?>
                            <input type="hidden" name="letter" value="<?php echo esc_attr($current_letter); ?>">
                        <?php endif; ?>
                        
                        <div class="az-filter-dropdowns">
                            <!-- Genre Filter -->
                            <div class="az-filter-dropdown">
                                <button type="button" class="az-filter-toggle" data-target="azGenreFilter">
                                    <span class="az-filter-label">Genre</span>
                                    <span class="az-filter-value"><?php echo !empty($current_genres) ? count($current_genres) . ' dipilih' : 'Semua Genre'; ?></span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                </button>
                                <div class="az-filter-content" id="azGenreFilter">
                                    <div class="az-filter-scroll">
                                        <?php if (!empty($genres) && !is_wp_error($genres)): ?>
                                            <?php foreach ($genres as $genre): ?>
                                                <label class="az-checkbox-item">
                                                    <input type="checkbox" name="genre[]" value="<?php echo esc_attr($genre->slug); ?>" <?php echo in_array($genre->slug, $current_genres) ? 'checked' : ''; ?>>
                                                    <span class="az-checkbox-icon"></span>
                                                    <span class="az-checkbox-label"><?php echo esc_html($genre->name); ?></span>
                                                    <span class="az-checkbox-count"><?php echo $genre->count; ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Status Filter -->
                            <div class="az-filter-dropdown">
                                <button type="button" class="az-filter-toggle" data-target="azStatusFilter">
                                    <span class="az-filter-label">Status</span>
                                    <span class="az-filter-value"><?php echo !empty($current_status) ? implode(', ', $current_status) : 'Semua Status'; ?></span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                </button>
                                <div class="az-filter-content" id="azStatusFilter">
                                    <?php 
                                    $statuses = array('Ongoing', 'Completed', 'Hiatus', 'Dropped');
                                    foreach ($statuses as $status): ?>
                                        <label class="az-checkbox-item">
                                            <input type="checkbox" name="status[]" value="<?php echo esc_attr($status); ?>" <?php echo in_array($status, $current_status) ? 'checked' : ''; ?>>
                                            <span class="az-checkbox-icon"></span>
                                            <span class="az-checkbox-label"><?php echo esc_html($status); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Type Filter -->
                            <div class="az-filter-dropdown">
                                <button type="button" class="az-filter-toggle" data-target="azTypeFilter">
                                    <span class="az-filter-label">Type</span>
                                    <span class="az-filter-value"><?php echo !empty($current_type) ? implode(', ', $current_type) : 'Semua Type'; ?></span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                </button>
                                <div class="az-filter-content" id="azTypeFilter">
                                    <?php 
                                    $types = array('Manga', 'Manhwa', 'Manhua');
                                    foreach ($types as $type): ?>
                                        <label class="az-checkbox-item">
                                            <input type="checkbox" name="type[]" value="<?php echo esc_attr($type); ?>" <?php echo in_array($type, $current_type) ? 'checked' : ''; ?>>
                                            <span class="az-checkbox-icon"></span>
                                            <span class="az-checkbox-label"><?php echo esc_html($type); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="az-filter-actions">
                            <button type="submit" class="az-filter-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                                </svg>
                                Filter <?php if ($filter_count > 0): ?><span class="az-filter-badge"><?php echo $filter_count; ?></span><?php endif; ?>
                            </button>
                            <?php if ($filter_count > 0): ?>
                                <a href="<?php echo esc_url(add_query_arg(array_filter(['letter' => $current_letter]), $page_url)); ?>" class="az-filter-reset">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M18 6 6 18"></path>
                                        <path d="m6 6 12 12"></path>
                                    </svg>
                                    Reset
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Results -->
                <div class="card-body">
                    <?php if (!empty($search_query)): ?>
                        <div class="az-search-info">
                            Hasil pencarian untuk: <strong>"<?php echo esc_html($search_query); ?>"</strong>
                            — <?php echo count($filtered_posts); ?> judul ditemukan
                            <a href="<?php echo esc_url($page_url); ?>" class="az-search-clear">Hapus pencarian</a>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($paged_posts)): ?>
                        <?php if (empty($current_letter) && empty($search_query)): ?>
                            <!-- Grouped by letter (paginated) -->
                            <?php
                            $grouped = array();
                            foreach ($paged_posts as $post) {
                                $first_char = strtoupper(mb_substr($post->post_title, 0, 1));
                                if (!ctype_alpha($first_char)) {
                                    $first_char = '#';
                                }
                                $grouped[$first_char][] = $post;
                            }
                            uksort($grouped, function($a, $b) {
                                if ($a === '#') return -1;
                                if ($b === '#') return 1;
                                return strcmp($a, $b);
                            });
                            ?>
                            
                            <?php foreach ($grouped as $letter => $posts): ?>
                                <div class="az-letter-group" id="letter-<?php echo esc_attr($letter); ?>">
                                    <div class="az-letter-heading">
                                        <span class="az-letter-char"><?php echo esc_html($letter); ?></span>
                                        <span class="az-letter-total"><?php echo count($posts); ?> titles</span>
                                        <div class="az-letter-line"></div>
                                    </div>
                                    <div class="az-manga-list">
                                        <?php foreach ($posts as $post): 
                                            setup_postdata($post);
                                            $meta = flavor_get_manhwa_meta($post->ID);
                                            $genres_list = get_the_terms($post->ID, 'manhwa_genre');
                                            $chapters = get_post_meta($post->ID, '_mws_chapters', true);
                                            $chapter_count = is_array($chapters) ? count($chapters) : 0;
                                        ?>
                                            <div class="az-manga-item">
                                                <div class="az-manga-thumb">
                                                    <a href="<?php echo get_permalink($post->ID); ?>">
                                                        <?php if (has_post_thumbnail($post->ID)): ?>
                                                            <img src="<?php echo esc_url(get_the_post_thumbnail_url($post->ID, 'chapter-thumb')); ?>" alt="<?php echo esc_attr($post->post_title); ?>" loading="lazy" width="60" height="80">
                                                        <?php else: ?>
                                                            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/no-image.svg" alt="<?php echo esc_attr($post->post_title); ?>" class="no-cover-placeholder" loading="lazy">
                                                        <?php endif; ?>
                                                    </a>
                                                </div>
                                                <div class="az-manga-info">
                                                    <h3 class="az-manga-title">
                                                        <a href="<?php echo get_permalink($post->ID); ?>"><?php echo esc_html($post->post_title); ?></a>
                                                    </h3>
                                                    <div class="az-manga-meta">
                                                        <?php if (!empty($meta['type'])): ?>
                                                            <span class="az-meta-badge az-type-<?php echo esc_attr(strtolower($meta['type'])); ?>">
                                                                <?php echo esc_html($meta['type']); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if (!empty($meta['status'])): ?>
                                                            <span class="az-meta-badge <?php echo flavor_get_status_class($meta['status']); ?>">
                                                                <?php echo esc_html($meta['status']); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if (!empty($meta['rating']) && $meta['rating'] > 0): ?>
                                                            <span class="az-meta-rating">
                                                                <svg viewBox="0 0 24 24" width="12" height="12"><path fill="currentColor" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                                                                <?php echo esc_html($meta['rating']); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if ($chapter_count > 0): ?>
                                                            <span class="az-meta-chapters">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                                                                    <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                                                                </svg>
                                                                <?php echo $chapter_count; ?> Ch
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if (!empty($genres_list) && !is_wp_error($genres_list)): ?>
                                                        <div class="az-manga-genres">
                                                            <?php 
                                                            $genre_names = array_slice(wp_list_pluck($genres_list, 'name'), 0, 5);
                                                            echo esc_html(implode(', ', $genre_names));
                                                            if (count($genres_list) > 5) echo ' ...';
                                                            ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; wp_reset_postdata(); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Flat list (filtered by letter or search) -->
                            <div class="az-manga-list">
                                <?php foreach ($paged_posts as $post): 
                                    setup_postdata($post);
                                    $meta = flavor_get_manhwa_meta($post->ID);
                                    $genres_list = get_the_terms($post->ID, 'manhwa_genre');
                                    $chapters = get_post_meta($post->ID, '_mws_chapters', true);
                                    $chapter_count = is_array($chapters) ? count($chapters) : 0;
                                ?>
                                    <div class="az-manga-item">
                                        <div class="az-manga-thumb">
                                            <a href="<?php echo get_permalink($post->ID); ?>">
                                                <?php if (has_post_thumbnail($post->ID)): ?>
                                                    <img src="<?php echo esc_url(get_the_post_thumbnail_url($post->ID, 'chapter-thumb')); ?>" alt="<?php echo esc_attr($post->post_title); ?>" loading="lazy" width="60" height="80">
                                                <?php else: ?>
                                                    <img src="<?php echo get_template_directory_uri(); ?>/assets/images/no-image.svg" alt="<?php echo esc_attr($post->post_title); ?>" class="no-cover-placeholder" loading="lazy">
                                                <?php endif; ?>
                                            </a>
                                        </div>
                                        <div class="az-manga-info">
                                            <h3 class="az-manga-title">
                                                <a href="<?php echo get_permalink($post->ID); ?>"><?php echo esc_html($post->post_title); ?></a>
                                            </h3>
                                            <div class="az-manga-meta">
                                                <?php if (!empty($meta['type'])): ?>
                                                    <span class="az-meta-badge az-type-<?php echo esc_attr(strtolower($meta['type'])); ?>">
                                                        <?php echo esc_html($meta['type']); ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (!empty($meta['status'])): ?>
                                                    <span class="az-meta-badge <?php echo flavor_get_status_class($meta['status']); ?>">
                                                        <?php echo esc_html($meta['status']); ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (!empty($meta['rating']) && $meta['rating'] > 0): ?>
                                                    <span class="az-meta-rating">
                                                        <svg viewBox="0 0 24 24" width="12" height="12"><path fill="currentColor" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                                                        <?php echo esc_html($meta['rating']); ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($chapter_count > 0): ?>
                                                    <span class="az-meta-chapters">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                                                            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                                                        </svg>
                                                        <?php echo $chapter_count; ?> Ch
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($genres_list) && !is_wp_error($genres_list)): ?>
                                                <div class="az-manga-genres">
                                                    <?php 
                                                    $genre_names = array_slice(wp_list_pluck($genres_list, 'name'), 0, 5);
                                                    echo esc_html(implode(', ', $genre_names));
                                                    if (count($genres_list) > 5) echo ' ...';
                                                    ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; wp_reset_postdata(); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php // Pagination ?>
                        <?php if ($total_pages > 1): ?>
                            <div class="az-pagination">
                                <?php
                                // Build base URL with existing query params
                                $query_params = $_GET;
                                unset($query_params['az_page']);
                                $base_url = $page_url . '?' . http_build_query($query_params);
                                $base_url .= empty($query_params) ? 'az_page=' : '&az_page=';
                                
                                // Previous button
                                if ($az_page > 1): ?>
                                    <a href="<?php echo esc_url($base_url . ($az_page - 1)); ?>" class="prev">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                                    </a>
                                <?php endif;
                                
                                // Page numbers
                                $range = 2;
                                $start = max(1, $az_page - $range);
                                $end = min($total_pages, $az_page + $range);
                                
                                if ($start > 1) {
                                    echo '<a href="' . esc_url($base_url . '1') . '">1</a>';
                                    if ($start > 2) echo '<span class="dots">...</span>';
                                }
                                
                                for ($i = $start; $i <= $end; $i++) {
                                    if ($i == $az_page) {
                                        echo '<span class="current">' . $i . '</span>';
                                    } else {
                                        echo '<a href="' . esc_url($base_url . $i) . '">' . $i . '</a>';
                                    }
                                }
                                
                                if ($end < $total_pages) {
                                    if ($end < $total_pages - 1) echo '<span class="dots">...</span>';
                                    echo '<a href="' . esc_url($base_url . $total_pages) . '">' . $total_pages . '</a>';
                                }
                                
                                // Next button
                                if ($az_page < $total_pages): ?>
                                    <a href="<?php echo esc_url($base_url . ($az_page + 1)); ?>" class="next">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="az-pagination-info">
                                <?php printf(
                                    __('Showing %d-%d of %d titles', 'flavor-flavor'),
                                    $offset + 1,
                                    min($offset + $per_page, $total_filtered),
                                    $total_filtered
                                ); ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="az-empty">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.3-4.3"></path>
                                <path d="M8 11h6"></path>
                            </svg>
                            <p>Tidak ada manga ditemukan<?php echo !empty($current_letter) ? ' untuk huruf "' . esc_html($current_letter) . '"' : ''; ?>.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

        </div>
    </div>
</div>

<script>
// Filter Dropdown Toggle for A-Z Page
document.addEventListener('DOMContentLoaded', function() {
    var toggleButtons = document.querySelectorAll('#azFilterForm .az-filter-toggle');
    
    toggleButtons.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var dropdown = this.closest('.az-filter-dropdown');
            var isActive = dropdown.classList.contains('active');
            
            // Close all dropdowns first
            document.querySelectorAll('#azFilterForm .az-filter-dropdown').forEach(function(d) {
                d.classList.remove('active');
            });
            
            // Toggle current
            if (!isActive) {
                dropdown.classList.add('active');
            }
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.az-filter-dropdown')) {
            document.querySelectorAll('#azFilterForm .az-filter-dropdown').forEach(function(d) {
                d.classList.remove('active');
            });
        }
    });
    
    // Prevent dropdown content clicks from closing the dropdown
    document.querySelectorAll('.az-filter-content').forEach(function(content) {
        content.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
});
</script>

<?php get_footer(); ?>
 