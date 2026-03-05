<?php
/**
 * Front Page Template
 *
 * @package Flavor_Flavor
 */

get_header();

// Get current page number
$paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
if ($paged < 1) $paged = 1;
?>

<div class="container">
    <div class="site-main">
        <div id="primary" class="content-area">
            
            <!-- SEO H1 Heading (visually hidden) -->
            <h1 class="sr-only"><?php echo esc_html(get_bloginfo('name')); ?> - <?php echo esc_html(get_bloginfo('description') ?: __('Baca Manhwa Bahasa Indonesia', 'flavor-flavor')); ?></h1>
            
            <?php if ($paged <= 1): ?>
            
            <!-- Hero Slider Section -->
            <?php
            // Get Customizer settings
            $slider_enabled = get_theme_mod('flavor_hero_slider_enabled', true);
            $slider_mode = get_theme_mod('flavor_hero_slider_mode', 'views');
            $slider_count = get_theme_mod('flavor_hero_slider_count', 8);
            $slider_speed = get_theme_mod('flavor_hero_slider_speed', 5000);
            
            if ($slider_enabled):
            
            // Build query based on mode
            $hero_args = array(
                'post_type'      => 'manhwa',
                'posts_per_page' => $slider_count,
            );
            
            switch ($slider_mode) {
                case 'manual':
                    // Get manually featured manhwa
                    $hero_args['meta_query'] = array(
                        array(
                            'key'     => '_manhwa_featured',
                            'value'   => '1',
                            'compare' => '='
                        )
                    );
                    break;
                    
                case 'views':
                    // Get by most views
                    $hero_args['meta_key'] = '_manhwa_views';
                    $hero_args['orderby'] = 'meta_value_num';
                    $hero_args['order'] = 'DESC';
                    break;
                    
                case 'rating':
                    // Get by highest rating
                    $hero_args['meta_key'] = '_manhwa_rating';
                    $hero_args['orderby'] = 'meta_value_num';
                    $hero_args['order'] = 'DESC';
                    break;
                    
                case 'latest':
                default:
                    // Get latest updated
                    $hero_args['orderby'] = 'modified';
                    $hero_args['order'] = 'DESC';
                    break;
            }
            
            $featured_manhwa = get_posts($hero_args);
            
            // Fallback to latest if no results (for manual mode)
            if (empty($featured_manhwa) && $slider_mode === 'manual') {
                $hero_args = array(
                    'post_type'      => 'manhwa',
                    'posts_per_page' => $slider_count,
                    'orderby'        => 'modified',
                    'order'          => 'DESC',
                );
                $featured_manhwa = get_posts($hero_args);
            }
            
            if ($featured_manhwa):
            ?>
            <section class="hero-slider mb-20" data-speed="<?php echo esc_attr($slider_speed); ?>">
                <div class="hero-slider-container" id="heroSlider">
                    <?php foreach ($featured_manhwa as $index => $manga): 
                        $meta = flavor_get_manhwa_meta($manga->ID);
                        $chapters = flavor_get_manhwa_chapters($manga->ID, 1);
                        $latest_chapter = !empty($chapters) ? $chapters[0] : null;
                        $thumbnail = get_the_post_thumbnail_url($manga->ID, 'large');
                    ?>
                    <div class="hero-slide <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>">
                        <div class="hero-slide-bg" style="background-image: url('<?php echo esc_url($thumbnail); ?>')"></div>
                        <div class="hero-slide-overlay"></div>
                        <div class="hero-slide-content">
                            <div class="hero-slide-info">
                                <?php if (!empty($meta['type'])): ?>
                                    <span class="hero-type-badge <?php echo flavor_get_type_class($meta['type']); ?>">
                                        <?php echo esc_html($meta['type']); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <h2 class="hero-title">
                                    <a href="<?php echo get_permalink($manga->ID); ?>">
                                        <?php echo esc_html($manga->post_title); ?>
                                    </a>
                                </h2>
                                
                                <div class="hero-meta">
                                    <?php if (!empty($meta['rating']) && $meta['rating'] > 0): ?>
                                        <span class="hero-rating">
                                            <svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                                            <?php echo esc_html($meta['rating']); ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($meta['status'])): ?>
                                        <span class="hero-status"><?php echo esc_html($meta['status']); ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <p class="hero-synopsis">
                                    <?php echo wp_trim_words(get_the_excerpt($manga->ID), 20, '...'); ?>
                                </p>
                                
                                <div class="hero-actions">
                                    <a href="<?php echo get_permalink($manga->ID); ?>" class="hero-btn hero-btn-primary">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                        Detail
                                    </a>
                                    
                                    <?php if ($latest_chapter): 
                                        $chapter_url = flavor_get_chapter_url($manga->ID, $latest_chapter);
                                    ?>
                                        <a href="<?php echo esc_url($chapter_url); ?>" class="hero-btn hero-btn-secondary">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                                                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                                            </svg>
                                            <?php echo wp_kses_post(flavor_format_chapter_number($latest_chapter['number'] ?? $latest_chapter['title'] ?? '')); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="hero-slide-thumb">
                                <?php if ($thumbnail): ?>
                                    <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($manga->post_title); ?>">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Slider Dots -->
                <div class="hero-slider-dots" id="heroSliderDots">
                    <?php for ($i = 0; $i < count($featured_manhwa); $i++): ?>
                        <button class="hero-dot <?php echo $i === 0 ? 'active' : ''; ?>" data-index="<?php echo $i; ?>"></button>
                    <?php endfor; ?>
                </div>
            </section>
            <?php endif; // $featured_manhwa ?>
            <?php endif; // $slider_enabled ?>
            
            <?php 
            // Announcement Box - Display after hero slider
            $announcement_enable = get_theme_mod('announcement_bar_enable', false);
            $announcement_text = get_theme_mod('announcement_bar_text', '');
            if ($announcement_enable && !empty($announcement_text)):
                $announcement_title = get_theme_mod('announcement_bar_title', '');
                $announcement_dismissible = get_theme_mod('announcement_bar_dismissible', true);
                $announcement_id = md5($announcement_text . $announcement_title);
            ?>
            <section class="announcement-box" id="announcementBar" data-id="<?php echo esc_attr($announcement_id); ?>">
                <?php if (!empty($announcement_title)): ?>
                <div class="announcement-header">
                    <h3 class="announcement-title"><?php echo esc_html($announcement_title); ?></h3>
                    <?php if ($announcement_dismissible): ?>
                    <button type="button" class="announcement-close" id="announcementClose" aria-label="<?php esc_attr_e('Close', 'flavor-flavor'); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6 6 18"></path>
                            <path d="m6 6 12 12"></path>
                        </svg>
                    </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <div class="announcement-body">
                    <?php echo wp_kses_post($announcement_text); ?>
                </div>
            </section>
            <?php endif; ?>
            
            <!-- Featured/Slider Section -->
            <section class="card mb-20">
                <div class="card-header">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                    </svg>
                    <?php esc_html_e('Hot Updates', 'flavor-flavor'); ?>
                </div>
                <div class="card-body">
                    <?php
                    $hot_manga = get_posts(array(
                        'post_type'      => 'manhwa',
                        'posts_per_page' => 6,
                        'orderby'        => 'modified',
                        'order'          => 'DESC',
                    ));
                    
                    if ($hot_manga):
                    ?>
                    <div class="manga-grid">
                        <?php foreach ($hot_manga as $manga):
                            $meta = flavor_get_manhwa_meta($manga->ID);
                            $chapters = flavor_get_manhwa_chapters($manga->ID, 2);
                        ?>
                        <article class="manga-item">
                            <div class="manga-thumb">
                                <?php if (has_post_thumbnail($manga->ID)): ?>
                                    <a href="<?php echo get_permalink($manga->ID); ?>">
                                        <?php echo get_the_post_thumbnail($manga->ID, 'manga-thumb'); ?>
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo get_permalink($manga->ID); ?>">
                                        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/no-image.svg" alt="<?php echo esc_attr($manga->post_title); ?>" class="no-cover-placeholder">
                                    </a>
                                <?php endif; ?>
                                
                                <!-- Status Badge -->
                                <?php if (!empty($meta['status'])): ?>
                                    <span class="status-badge <?php echo flavor_get_status_class($meta['status']); ?>">
                                        <?php echo esc_html($meta['status']); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <!-- Type Badge Image -->
                                <?php if (!empty($meta['type'])): 
                                    $type_lower = strtolower($meta['type']);
                                    $type_image = '';
                                    if (in_array($type_lower, ['manga', 'manhua', 'manhwa'])) {
                                        $type_image = get_template_directory_uri() . '/assets/images/' . $type_lower . '.png';
                                    }
                                    if ($type_image):
                                ?>
                                    <img src="<?php echo esc_url($type_image); ?>" alt="<?php echo esc_attr($meta['type']); ?>" class="type-badge-img">
                                <?php endif; endif; ?>
                                
                                <!-- Score Badge -->
                                <?php if (!empty($meta['rating']) && $meta['rating'] > 0): ?>
                                    <span class="score-badge">
                                        <svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                                        <?php echo esc_html($meta['rating']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="manga-info">
                                <h3 class="manga-title">
                                    <a href="<?php echo get_permalink($manga->ID); ?>">
                                        <?php echo esc_html($manga->post_title); ?>
                                    </a>
                                </h3>
                                
                                <?php if (!empty($chapters)): ?>
                                <div class="manga-chapters">
                                    <?php foreach ($chapters as $chapter): ?>
                                        <a href="<?php echo esc_url(flavor_get_chapter_url($manga->ID, $chapter)); ?>">
                                            <span class="chapter-name"><?php echo wp_kses_post(flavor_format_chapter_number($chapter['number'] ?? $chapter['title'] ?? '')); ?></span>
                                            <?php 
                                                $display_time = !empty($chapter['added_at']) ? $chapter['added_at'] : ($chapter['date'] ?? '');
                                            ?>
                                            <?php if (!empty($display_time)): ?>
                                                <span class="chapter-date"><?php echo esc_html(flavor_time_ago($display_time)); ?></span>
                                            <?php endif; ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                        <p class="text-center text-muted"><?php esc_html_e('No manga found.', 'flavor-flavor'); ?></p>
                    <?php endif; ?>
                </div>
            </section>
            <?php endif; ?>
            
            <?php if ($paged <= 1): ?>
            <!-- Most Viewed Section -->
            <?php
            $mv_enabled = get_theme_mod('flavor_most_viewed_enabled', true);
            $mv_count   = get_theme_mod('flavor_most_viewed_count', 10);
            if ($mv_count < 3) $mv_count = 10;
            
            if ($mv_enabled):
            $most_viewed = get_posts(array(
                'post_type'      => 'manhwa',
                'posts_per_page' => $mv_count,
                'meta_key'       => '_manhwa_views',
                'orderby'        => 'meta_value_num',
                'order'          => 'DESC',
                'post_status'    => 'publish',
            ));
            
            if ($most_viewed):
            ?>
            <section class="card mb-20">
                <div class="card-header">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    <?php esc_html_e('Most Viewed', 'flavor-flavor'); ?>
                </div>
                <div class="card-body" style="padding: 12px;">
                    <div class="most-viewed-scroll">
                        <?php foreach ($most_viewed as $mv_index => $manga):
                            $meta = flavor_get_manhwa_meta($manga->ID);
                            $views = intval(get_post_meta($manga->ID, '_manhwa_views', true));
                            $thumbnail = get_the_post_thumbnail_url($manga->ID, 'manga-thumb');
                        ?>
                        <a href="<?php echo get_permalink($manga->ID); ?>" class="mv-card">
                            <div class="mv-thumb">
                                <?php if ($thumbnail): ?>
                                    <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($manga->post_title); ?>" loading="lazy">
                                <?php else: ?>
                                    <img src="<?php echo get_template_directory_uri(); ?>/assets/images/no-image.svg" alt="<?php echo esc_attr($manga->post_title); ?>" class="no-cover-placeholder">
                                <?php endif; ?>
                                <span class="mv-rank <?php echo $mv_index < 3 ? 'mv-rank-top' : ''; ?>"><?php echo $mv_index + 1; ?></span>
                                <?php if (!empty($meta['rating']) && $meta['rating'] > 0): ?>
                                <span class="mv-rating">
                                    <svg viewBox="0 0 24 24" width="10" height="10"><path fill="currentColor" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                                    <?php echo esc_html($meta['rating']); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <div class="mv-info">
                                <h3 class="mv-title"><?php echo esc_html($manga->post_title); ?></h3>
                                <span class="mv-views">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                    <?php echo flavor_format_number($views); ?>
                                </span>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php endif; // $most_viewed ?>
            <?php endif; // $mv_enabled ?>
            <?php endif; // paged check ?>
            
            <!-- Latest Updates Section -->
            <section class="card" id="latestUpdatesSection">
                <div class="card-header">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <?php esc_html_e('Latest Updates', 'flavor-flavor'); ?>
                    
                    <div class="view-toggle" id="viewToggle">
                        <button type="button" class="view-toggle-btn active" data-view="list" id="btnListView" title="List View">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="8" y1="6" x2="21" y2="6"></line>
                                <line x1="8" y1="12" x2="21" y2="12"></line>
                                <line x1="8" y1="18" x2="21" y2="18"></line>
                                <line x1="3" y1="6" x2="3.01" y2="6"></line>
                                <line x1="3" y1="12" x2="3.01" y2="12"></line>
                                <line x1="3" y1="18" x2="3.01" y2="18"></line>
                            </svg>
                        </button>
                        <button type="button" class="view-toggle-btn" data-view="grid" id="btnGridView" title="Grid View">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php
                    // Use $paged from top of file
                    $latest_manga = new WP_Query(array(
                        'post_type'      => 'manhwa',
                        'posts_per_page' => 18,
                        'paged'          => $paged,
                        'orderby'        => 'modified',
                        'order'          => 'DESC',
                    ));
                    
                    if ($latest_manga->have_posts()):
                    ?>
                    <div class="updates-grid" id="updatesGrid">

                        <?php while ($latest_manga->have_posts()): $latest_manga->the_post(); 
                            $meta = flavor_get_manhwa_meta(get_the_ID());
                            $chapters = flavor_get_manhwa_chapters(get_the_ID(), 2);
                        ?>
                        <article class="update-item">
                            <div class="update-thumb">
                                <a href="<?php the_permalink(); ?>">
                                    <?php if (has_post_thumbnail()): ?>
                                        <?php the_post_thumbnail('chapter-thumb'); ?>
                                    <?php else: ?>
                                        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/no-image.svg" alt="<?php the_title_attribute(); ?>" class="no-cover-placeholder">
                                    <?php endif; ?>
                                </a>
                                
                                <!-- Type Badge Image -->
                                <?php if (!empty($meta['type'])): 
                                    $type_lower = strtolower($meta['type']);
                                    $type_image = '';
                                    if (in_array($type_lower, ['manga', 'manhua', 'manhwa'])) {
                                        $type_image = get_template_directory_uri() . '/assets/images/' . $type_lower . '.png';
                                    }
                                    if ($type_image):
                                ?>
                                    <img src="<?php echo esc_url($type_image); ?>" alt="<?php echo esc_attr($meta['type']); ?>" class="type-badge-img">
                                <?php endif; endif; ?>
                            </div>
                            
                            <div class="update-info">
                                <h3>
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h3>
                                
                                <?php if (!empty($chapters)): ?>
                                <ul class="update-chapters">
                                    <?php foreach ($chapters as $chapter): ?>
                                    <li>
                                        <a href="<?php echo esc_url(flavor_get_chapter_url(get_the_ID(), $chapter)); ?>" class="chapter-name">
                                            <?php echo wp_kses_post(flavor_format_chapter_number($chapter['number'] ?? $chapter['title'] ?? '')); ?>
                                        </a>
                                        <?php 
                                            $display_time = !empty($chapter['added_at']) ? $chapter['added_at'] : ($chapter['date'] ?? '');
                                        ?>
                                        <?php if (!empty($display_time)): ?>
                                            <span class="chapter-date"><?php echo esc_html(flavor_time_ago($display_time)); ?>
                                        <?php endif; ?>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php endif; ?>
                                
                                <?php if (!empty($meta['status'])): ?>
                                <span class="update-status-badge <?php echo esc_attr(flavor_get_status_class($meta['status'])); ?>">
                                    <span class="status-dot"></span>
                                    <?php echo esc_html($meta['status']); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </article>
                        <?php endwhile; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($latest_manga->max_num_pages > 1): ?>
                    <div class="pagination">
                        <ul class="page-numbers">
                            <?php
                            $total_pages = $latest_manga->max_num_pages;
                            $current_page = $paged;
                            $base_url = home_url('/');
                            
                            // Previous
                            if ($current_page > 1): ?>
                                <li><a class="page-numbers prev" href="<?php echo esc_url(add_query_arg('paged', $current_page - 1, $base_url)); ?>">&laquo;</a></li>
                            <?php endif;
                            
                            // Smart pagination with truncation
                            $show_dots_start = false;
                            $show_dots_end = false;
                            
                            for ($i = 1; $i <= $total_pages; $i++):
                                // Always show first 2 pages, last 2 pages, and pages around current
                                $show_page = false;
                                
                                if ($i <= 2) { // First 2 pages
                                    $show_page = true;
                                } elseif ($i > $total_pages - 2) { // Last 2 pages
                                    $show_page = true;
                                } elseif ($i >= $current_page - 1 && $i <= $current_page + 1) { // Around current
                                    $show_page = true;
                                }
                                
                                if ($show_page):
                                    if ($i == $current_page): ?>
                                        <li><span class="page-numbers current"><?php echo $i; ?></span></li>
                                    <?php else: ?>
                                        <li><a class="page-numbers" href="<?php echo esc_url(add_query_arg('paged', $i, $base_url)); ?>"><?php echo $i; ?></a></li>
                                    <?php endif;
                                else:
                                    // Show dots
                                    if ($i < $current_page && !$show_dots_start):
                                        $show_dots_start = true; ?>
                                        <li><span class="page-numbers dots">...</span></li>
                                    <?php elseif ($i > $current_page && !$show_dots_end):
                                        $show_dots_end = true; ?>
                                        <li><span class="page-numbers dots">...</span></li>
                                    <?php endif;
                                endif;
                            endfor;
                            
                            // Next
                            if ($current_page < $total_pages): ?>
                                <li><a class="page-numbers next" href="<?php echo esc_url(add_query_arg('paged', $current_page + 1, $base_url)); ?>">&raquo;</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <?php wp_reset_postdata(); ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M12 16v-4"></path>
                                <path d="M12 8h.01"></path>
                            </svg>
                            <?php esc_html_e('No manga found. Start importing manga using the Manhwa Scraper plugin!', 'flavor-flavor'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
            
        </div><!-- #primary -->
        
        <!-- Sidebar -->
        <?php get_sidebar(); ?>
        
    </div><!-- .site-main -->
</div><!-- .container -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    var grid = document.getElementById('updatesGrid');
    var btnList = document.getElementById('btnListView');
    var btnGrid = document.getElementById('btnGridView');
    
    if (!grid || !btnList || !btnGrid) return;
    
    // Load saved preference
    var saved = localStorage.getItem('updates_view');
    if (saved === 'grid') {
        grid.classList.add('grid-view');
        btnGrid.classList.add('active');
        btnList.classList.remove('active');
    }
    
    btnList.addEventListener('click', function() {
        grid.classList.remove('grid-view');
        btnList.classList.add('active');
        btnGrid.classList.remove('active');
        localStorage.setItem('updates_view', 'list');
    });
    
    btnGrid.addEventListener('click', function() {
        grid.classList.add('grid-view');
        btnGrid.classList.add('active');
        btnList.classList.remove('active');
        localStorage.setItem('updates_view', 'grid');
    });
});
</script>

<?php get_footer(); ?>
