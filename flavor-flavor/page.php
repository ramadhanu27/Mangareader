<?php
/**
 * Page Template
 *
 * @package Flavor_Flavor
 */

get_header();
?>

<div class="container">
    <div class="site-main">
        <div id="primary" class="content-area" style="grid-column: 1 / -1; max-width: 900px; margin: 0 auto;">
            
            <?php while (have_posts()): the_post(); ?>
            
            <article id="post-<?php the_ID(); ?>" <?php post_class('card'); ?>>
                <div class="card-body">
                    <header class="entry-header">
                        <h1 class="entry-title"><?php the_title(); ?></h1>
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
                </div>
            </article>
            
            <?php
            if (comments_open() || get_comments_number()):
                comments_template();
            endif;
            ?>
            
            <?php endwhile; ?>
            
        </div><!-- #primary -->
    </div><!-- .site-main -->
</div><!-- .container -->

<?php get_footer(); ?>
