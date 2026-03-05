<?php
/**
 * Search Results Template
 *
 * @package Flavor_Flavor
 */

get_header();
?>

<div class="container">
    <div class="site-main">
        <div id="primary" class="content-area">
            
            <section class="card">
                <div class="card-header">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline; vertical-align: middle;">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.3-4.3"></path>
                    </svg>
                    <?php
                    printf(
                        esc_html__('Search Results for: %s', 'flavor-flavor'),
                        '<span class="text-primary">' . get_search_query() . '</span>'
                    );
                    ?>
                </div>
                
                <div class="card-body">
                    <!-- Search Form -->
                    <form role="search" method="get" class="search-form mb-20" action="<?php echo esc_url(home_url('/')); ?>">
                        <input type="search" class="search-input" placeholder="<?php esc_attr_e('Search manga...', 'flavor-flavor'); ?>" value="<?php echo get_search_query(); ?>" name="s">
                        <input type="hidden" name="post_type" value="manhwa">
                        <button type="submit" class="search-btn">
                            <?php esc_html_e('Search', 'flavor-flavor'); ?>
                        </button>
                    </form>
                    
                    <?php if (have_posts()): ?>                  
                        <div class="manga-grid">
                            <?php while (have_posts()): the_post();
                                $meta = flavor_get_manhwa_meta(get_the_ID());
                            ?>
                            <article class="manga-item">
                                <div class="manga-thumb">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php if (has_post_thumbnail()): ?>
                                            <?php the_post_thumbnail('manga-thumb'); ?>
                                        <?php else: ?>
                                            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/no-image.svg" alt="<?php the_title_attribute(); ?>" class="no-cover-placeholder">
                                        <?php endif; ?>
                                    </a>
                                    
                                    <?php if (!empty($meta['status'])): ?>
                                        <span class="status-badge <?php echo flavor_get_status_class($meta['status']); ?>">
                                            <?php echo esc_html($meta['status']); ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    
                                    <!-- Type Badge Image (Flag) -->
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
                                
                                <div class="manga-info">
                                    <h3 class="manga-title">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h3>
                                </div>
                            </article>
                            <?php endwhile; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="pagination">
                            <?php
                            echo paginate_links(array(
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                            ));
                            ?>
                        </div>
                        
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M12 16v-4"></path>
                                <path d="M12 8h.01"></path>
                            </svg>
                            <?php esc_html_e('No manga found matching your search.', 'flavor-flavor'); ?>
                        </div>
                        
                        <div class="mt-20">
                            <h4><?php esc_html_e('Suggestions:', 'flavor-flavor'); ?></h4>
                            <ul style="margin-left: 20px; margin-top: 10px;">
                                <li><?php esc_html_e('Check your spelling', 'flavor-flavor'); ?></li>
                                <li><?php esc_html_e('Try different keywords', 'flavor-flavor'); ?></li>
                                <li><?php esc_html_e('Try more general keywords', 'flavor-flavor'); ?></li>
                            </ul>
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
