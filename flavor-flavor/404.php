<?php
/**
 * 404 Page Template
 *
 * @package Flavor_Flavor
 */

get_header();
?>

<div class="container">
    <div class="site-main">
        <div id="primary" class="content-area" style="grid-column: 1 / -1;">
            
            <section class="card">
                <div class="card-body text-center" style="padding: 60px 20px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 24 24" fill="none" stroke="var(--primary-color)" stroke-width="1" style="margin-bottom: 20px;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M16 16s-1.5-2-4-2-4 2-4 2"></path>
                        <line x1="9" x2="9.01" y1="9" y2="9"></line>
                        <line x1="15" x2="15.01" y1="9" y2="9"></line>
                    </svg>
                    
                    <h1 style="font-size: 80px; margin: 0; color: var(--primary-color);">404</h1>
                    <h2 style="margin-bottom: 20px;"><?php esc_html_e('Page Not Found', 'flavor-flavor'); ?></h2>
                    <p class="text-muted" style="max-width: 500px; margin: 0 auto 30px;">
                        <?php esc_html_e('The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.', 'flavor-flavor'); ?>
                    </p>
                    
                    <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                        <a href="<?php echo esc_url(home_url('/')); ?>" class="search-btn" style="text-decoration: none;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 5px;">
                                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                            <?php esc_html_e('Go Home', 'flavor-flavor'); ?>
                        </a>
                        
                        <?php if (post_type_exists('manhwa')): ?>
                        <a href="<?php echo esc_url(get_post_type_archive_link('manhwa')); ?>" class="search-btn" style="text-decoration: none; background: #666;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 5px;">
                                <rect width="7" height="7" x="3" y="3" rx="1"></rect>
                                <rect width="7" height="7" x="14" y="3" rx="1"></rect>
                                <rect width="7" height="7" x="14" y="14" rx="1"></rect>
                                <rect width="7" height="7" x="3" y="14" rx="1"></rect>
                            </svg>
                            <?php esc_html_e('Browse Manga', 'flavor-flavor'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Search Form -->
                    <div style="max-width: 400px; margin: 40px auto 0;">
                        <p class="text-muted mb-15"><?php esc_html_e('Or try searching:', 'flavor-flavor'); ?></p>
                        <form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
                            <input type="search" class="search-input" placeholder="<?php esc_attr_e('Search manga...', 'flavor-flavor'); ?>" name="s">
                            <input type="hidden" name="post_type" value="manhwa">
                            <button type="submit" class="search-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <path d="m21 21-4.3-4.3"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </section>
            
            <!-- Popular Manga Suggestions -->
            <?php
            $popular = get_posts(array(
                'post_type'      => 'manhwa',
                'posts_per_page' => 6,
                'meta_key'       => '_manhwa_views',
                'orderby'        => 'meta_value_num',
                'order'          => 'DESC',
            ));
            
            if ($popular):
            ?>
            <section class="card mt-20">
                <div class="card-header">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline; vertical-align: middle;">
                        <path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"></path>
                    </svg>
                    <?php esc_html_e('Popular Manga', 'flavor-flavor'); ?>
                </div>
                <div class="card-body">
                    <div class="manga-grid">
                        <?php foreach ($popular as $manga): 
                            $meta = flavor_get_manhwa_meta($manga->ID);
                        ?>
                        <article class="manga-item">
                            <div class="manga-thumb">
                                <a href="<?php echo get_permalink($manga->ID); ?>">
                                    <?php if (has_post_thumbnail($manga->ID)): ?>
                                        <?php echo get_the_post_thumbnail($manga->ID, 'manga-thumb'); ?>
                                    <?php else: ?>
                                        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/no-image.svg" alt="<?php echo esc_attr($manga->post_title); ?>" class="no-cover-placeholder">
                                    <?php endif; ?>
                                </a>
                                
                                <?php if (!empty($meta['type'])): ?>
                                    <span class="type-badge <?php echo flavor_get_type_class($meta['type']); ?>">
                                        <?php echo esc_html($meta['type']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="manga-info">
                                <h3 class="manga-title">
                                    <a href="<?php echo get_permalink($manga->ID); ?>"><?php echo esc_html($manga->post_title); ?></a>
                                </h3>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php endif; ?>
            
        </div><!-- #primary -->
    </div><!-- .site-main -->
</div><!-- .container -->

<?php get_footer(); ?>
