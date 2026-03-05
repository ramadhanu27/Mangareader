<?php
/**
 * Sidebar Template
 *
 * @package Flavor_Flavor
 */

// Check if sidebar is enabled via Customizer
if (function_exists('flavor_is_sidebar_enabled') && !flavor_is_sidebar_enabled()) {
    return;
}

if (!is_active_sidebar('sidebar-1') && !post_type_exists('manhwa')) {
    return;
}
?>

<aside id="secondary" class="sidebar">
    
    <?php if (is_active_sidebar('sidebar-1')): ?>
        <?php dynamic_sidebar('sidebar-1'); ?>
    <?php endif; ?>
    
    <?php if (is_active_sidebar('sidebar-ads')): ?>
        <div class="fv-sponsor-area">
            <?php dynamic_sidebar('sidebar-ads'); ?>
        </div>
    <?php endif; ?>
    
    <!-- Trending Widget -->
    <?php
    // Get settings from Admin Menu
    $trending_title = get_option('flavor_trending_title', 'Trending');
    $trending_count = get_option('flavor_trending_count', 5);
    $trending_manual_ids = get_option('flavor_trending_manual_ids', '');
    $show_tabs = get_option('flavor_trending_show_tabs', 1);
    $show_genres = get_option('flavor_trending_show_genres', 1);
    $show_rating = get_option('flavor_trending_show_rating', 1);
    
    // Get per-tab sort settings
    $sort_weekly = get_option('flavor_trending_sort_weekly', 'rating');
    $sort_monthly = get_option('flavor_trending_sort_monthly', 'views');
    $sort_all = get_option('flavor_trending_sort_all', 'latest');
    
    // Build query args based on sort type
    $query_args = array(
        'post_type'      => 'manhwa',
        'posts_per_page' => $trending_count,
        'post_status'    => 'publish',
    );
    
    // Default to weekly filter with its sort setting
    $default_period = 'weekly';
    $trending_sort = $sort_weekly;
    $query_args['date_query'] = array(
        array(
            'after' => '1 week ago',
        ),
    );
    
    // Apply sort based on current tab's setting
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
                    $query_args['posts_per_page'] = -1; // Show all specified
                }
            }
            // Remove date_query for manual selection
            unset($query_args['date_query']);
            break;
    }
    
    $trending_manga = get_posts($query_args);
    
    // If weekly has no results, try without date filter
    if (empty($trending_manga)) {
        unset($query_args['date_query']);
        $trending_manga = get_posts($query_args);
        $default_period = 'all';
    }
    ?>
    <div class="widget trending-widget">
        <h3 class="widget-title trending-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                <polyline points="17 6 23 6 23 12"></polyline>
            </svg>
            <?php echo esc_html($trending_title); ?>
        </h3>
        
        <?php if ($show_tabs): ?>
        <!-- Tabs -->
        <div class="trending-tabs">
            <button class="tab-btn <?php echo $default_period === 'weekly' ? 'active' : ''; ?>" data-tab="weekly"><?php esc_html_e('Mingguan', 'flavor-flavor'); ?></button>
            <button class="tab-btn <?php echo $default_period === 'monthly' ? 'active' : ''; ?>" data-tab="monthly"><?php esc_html_e('Bulanan', 'flavor-flavor'); ?></button>
            <button class="tab-btn <?php echo $default_period === 'all' ? 'active' : ''; ?>" data-tab="all"><?php esc_html_e('Semua', 'flavor-flavor'); ?></button>
        </div>
        <?php endif; ?>
        
        <!-- Tab Content -->
        <div class="trending-content" id="trendingContent">
            <?php if ($trending_manga): ?>
                <?php foreach ($trending_manga as $index => $manga): 
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
                <?php endforeach; ?>
            <?php else: ?>
                <div class="trending-empty">
                    <p><?php esc_html_e('Tidak ada data untuk periode ini.', 'flavor-flavor'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Loading indicator -->
        <div class="trending-loading" id="trendingLoading" style="display: none;">
            <div class="loading-spinner"></div>
        </div>
    </div>
    
    <?php if ($show_tabs): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('.trending-tabs .tab-btn');
        const content = document.getElementById('trendingContent');
        const loading = document.getElementById('trendingLoading');
        
        tabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                const period = this.getAttribute('data-tab');
                
                // Update active state
                tabs.forEach(function(t) { t.classList.remove('active'); });
                this.classList.add('active');
                
                // Show loading
                content.style.opacity = '0.5';
                loading.style.display = 'block';
                
                // AJAX request
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=flavor_get_trending&period=' + period + '&nonce=<?php echo wp_create_nonce('flavor_trending_nonce'); ?>'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        content.innerHTML = data.data.html;
                    }
                    content.style.opacity = '1';
                    loading.style.display = 'none';
                })
                .catch(error => {
                    console.error('Error:', error);
                    content.style.opacity = '1';
                    loading.style.display = 'none';
                });
            });
        });
    });
    </script>
    <?php endif; ?>
    
    <!-- Genres Widget -->
    <?php
    $genres = get_terms(array(
        'taxonomy'   => 'manhwa_genre',
        'hide_empty' => true,
        'number'     => 20,
    ));
    
    if (!is_wp_error($genres) && !empty($genres)):
    ?>
    <div class="widget">
        <h3 class="widget-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline; vertical-align: middle; margin-right: 5px;">
                <path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z"></path>
                <path d="M7 7h.01"></path>
            </svg>
            <?php esc_html_e('Genres', 'flavor-flavor'); ?>
        </h3>
        <div class="widget-content">
            <div class="genre-list">
                <?php foreach ($genres as $genre): ?>
                    <a href="<?php echo esc_url(get_term_link($genre)); ?>" class="genre-item">
                        <?php echo esc_html($genre->name); ?>
                        <span class="text-muted" style="font-size: 11px; margin-left: 3px;">(<?php echo $genre->count; ?>)</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Random Manga Widget -->
    <?php
    $random = get_posts(array(
        'post_type'      => 'manhwa',
        'posts_per_page' => 5,
        'orderby'        => 'rand',
    ));
    
    if ($random):
    ?>
    <div class="widget">
        <h3 class="widget-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline; vertical-align: middle; margin-right: 5px;">
                <path d="m18 14 4 4-4 4"></path>
                <path d="m18 2 4 4-4 4"></path>
                <path d="M2 18h1.973a4 4 0 0 0 3.3-1.7l5.454-8.6a4 4 0 0 1 3.3-1.7H22"></path>
                <path d="M2 6h1.972a4 4 0 0 1 3.6 2.2"></path>
                <path d="M22 18h-6.041a4 4 0 0 1-3.3-1.8l-.359-.45"></path>
            </svg>
            <?php esc_html_e('Random', 'flavor-flavor'); ?>
        </h3>
        <div class="widget-content">
            <?php foreach ($random as $manga): ?>
            <div class="popular-item">
                <div class="popular-thumb">
                    <a href="<?php echo get_permalink($manga->ID); ?>">
                        <?php if (has_post_thumbnail($manga->ID)): ?>
                            <?php echo get_the_post_thumbnail($manga->ID, 'chapter-thumb'); ?>
                        <?php else: ?>
                            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/no-image.svg" alt="<?php echo esc_attr($manga->post_title); ?>" class="no-cover-placeholder">
                        <?php endif; ?>
                    </a>
                </div>
                <div class="popular-info">
                    <h4 class="popular-title">
                        <a href="<?php echo get_permalink($manga->ID); ?>">
                            <?php echo esc_html($manga->post_title); ?>
                        </a>
                    </h4>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
</aside><!-- #secondary -->
