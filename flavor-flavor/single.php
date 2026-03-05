<?php
/**
 * Single Post/Page Template
 *
 * @package Flavor_Flavor
 */

get_header();
?>

<div class="container">
    <div class="site-main">
        <div id="primary" class="content-area">
            
            <?php while (have_posts()): the_post(); ?>
            
            <article id="post-<?php the_ID(); ?>" <?php post_class('card'); ?>>
                <?php if (has_post_thumbnail()): ?>
                <div class="post-thumbnail">
                    <?php the_post_thumbnail('large'); ?>
                </div>
                <?php endif; ?>
                
                <div class="card-body">
                    <header class="entry-header">
                        <h1 class="entry-title"><?php the_title(); ?></h1>
                        
                        <?php if (get_post_type() === 'post'): ?>
                        <div class="entry-meta text-muted" style="font-size: 13px; margin-bottom: 20px;">
                            <span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle;">
                                    <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                                    <line x1="16" x2="16" y1="2" y2="6"></line>
                                    <line x1="8" x2="8" y1="2" y2="6"></line>
                                    <line x1="3" x2="21" y1="10" y2="10"></line>
                                </svg>
                                <?php echo get_the_date(); ?>
                            </span>
                            <span style="margin-left: 15px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle;">
                                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                <?php the_author(); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </header>
                    
                    <div class="entry-content">
                        <?php the_content(); ?>
                        
                        <?php
                        wp_link_pages(array(
                            'before' => '<div class="page-links">' . __('Pages:', 'flavor-flavor'),
                            'after'  => '</div>',
                        ));
                        ?>
                    </div>
                    
                    <?php if (get_post_type() === 'post'): ?>
                    <footer class="entry-footer" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                        <?php
                        $categories = get_the_category();
                        if ($categories):
                        ?>
                        <div class="cat-links" style="margin-bottom: 10px;">
                            <strong><?php esc_html_e('Categories:', 'flavor-flavor'); ?></strong>
                            <?php foreach ($categories as $cat): ?>
                                <a href="<?php echo esc_url(get_category_link($cat->term_id)); ?>" class="genre-item" style="font-size: 12px; padding: 2px 8px;">
                                    <?php echo esc_html($cat->name); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php
                        $tags = get_the_tags();
                        if ($tags):
                        ?>
                        <div class="tag-links">
                            <strong><?php esc_html_e('Tags:', 'flavor-flavor'); ?></strong>
                            <?php foreach ($tags as $tag): ?>
                                <a href="<?php echo esc_url(get_tag_link($tag->term_id)); ?>" class="text-muted" style="font-size: 13px;">
                                    #<?php echo esc_html($tag->name); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </footer>
                    <?php endif; ?>
                </div>
            </article>
            
            <?php
            // If comments are open or we have at least one comment
            if (comments_open() || get_comments_number()):
                comments_template();
            endif;
            ?>
            
            <?php endwhile; ?>
            
        </div><!-- #primary -->
        
        <!-- Sidebar -->
        <?php get_sidebar(); ?>
        
    </div><!-- .site-main -->
</div><!-- .container -->

<?php get_footer(); ?>
