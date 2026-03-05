<?php
/**
 * Main Index Template
 *
 * @package Flavor_Flavor
 */

get_header();
?>

<div class="container">
    <div class="site-main">
        <div id="primary" class="content-area">
            
            <?php if (is_home() && !is_paged()): ?>
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
                    <div class="hot-grid">
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
                                            <span class="chapter-name"><?php echo esc_html(flavor_format_chapter_number($chapter['number'] ?? $chapter['title'] ?? '')); ?></span>
                                            <?php 
                                                // Use added_at (full datetime) if available, fallback to date
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
            
            <!-- Latest Updates Section -->
            <section class="card">
                <div class="card-header">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <?php esc_html_e('Latest Updates', 'flavor-flavor'); ?>
                </div>
                <div class="card-body">
                    <?php
                    // Get paged value from query string or query var
                    $paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
                    if ($paged < 1) $paged = 1;
                    $latest_manga = new WP_Query(array(
                        'post_type'      => 'manhwa',
                        'posts_per_page' => 18,
                        'paged'          => $paged,
                        'orderby'        => 'modified',
                        'order'          => 'DESC',
                    ));
                    
                    if ($latest_manga->have_posts()):
                    ?>
                    <div class="updates-grid">
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
                                            <?php echo esc_html(flavor_format_chapter_number($chapter['number'] ?? $chapter['title'] ?? '')); ?>
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

<?php get_footer(); ?>
