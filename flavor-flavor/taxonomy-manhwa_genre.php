<?php
/**
 * Genre Taxonomy Archive Template
 *
 * @package Flavor_Flavor
 */

get_header();

$term = get_queried_object();
$paged = get_query_var('paged') ? get_query_var('paged') : 1;
?>

<div class="container">
    <div class="site-main">
        <div id="primary" class="content-area">
            
            <section class="card">
                <div class="card-header">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline; vertical-align: middle;">
                        <path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z"></path>
                        <path d="M7 7h.01"></path>
                    </svg>
                    <?php esc_html_e('Genre:', 'flavor-flavor'); ?> 
                    <span class="text-primary"><?php single_term_title(); ?></span>
                </div>
                
                <?php if (term_description()): ?>
                <div class="card-body" style="border-bottom: 1px solid var(--border-color);">
                    <p class="text-muted"><?php echo term_description(); ?></p>
                </div>
                <?php endif; ?>
                
                <div class="card-body">
                    <?php if (have_posts()): ?>
                        <p class="text-muted mb-15">
                            <?php
                            printf(
                                esc_html(_n('%d manga in this genre', '%d manga in this genre', $term->count, 'flavor-flavor')),
                                $term->count
                            );
                            ?>
                        </p>
                        
                        <div class="manga-grid">
                            <?php while (have_posts()): the_post();
                                $meta = flavor_get_manhwa_meta(get_the_ID());
                                $chapters = flavor_get_manhwa_chapters(get_the_ID(), 1);
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
                                    
                                    <?php if (!empty($meta['type'])): ?>
                                        <span class="type-badge <?php echo flavor_get_type_class($meta['type']); ?>">
                                            <?php echo esc_html($meta['type']); ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($meta['rating']) && $meta['rating'] > 0): ?>
                                        <span class="score-badge">
                                            <svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                                            <?php echo esc_html($meta['rating']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="manga-info">
                                    <h3 class="manga-title">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h3>
                                    
                                    <?php if (!empty($chapters)): ?>
                                    <div class="manga-chapters">
                                        <?php foreach ($chapters as $chapter): ?>
                                            <a href="<?php echo esc_url(flavor_get_chapter_url(get_the_ID(), $chapter)); ?>">
                                                <span class="chapter-name"><?php echo wp_kses_post(flavor_format_chapter_number($chapter['number'] ?? '')); ?></span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
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
                        <div class="alert alert-info">
                            <?php esc_html_e('No manga found in this genre.', 'flavor-flavor'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
            
            <!-- Other Genres -->
            <?php
            $all_genres = get_terms(array(
                'taxonomy'   => 'genre',
                'hide_empty' => true,
                'exclude'    => array($term->term_id),
                'number'     => 20,
            ));
            
            if (!is_wp_error($all_genres) && !empty($all_genres)):
            ?>
            <section class="card mt-20">
                <div class="card-header">
                    <?php esc_html_e('Other Genres', 'flavor-flavor'); ?>
                </div>
                <div class="card-body">
                    <div class="genre-list">
                        <?php foreach ($all_genres as $genre): ?>
                            <a href="<?php echo esc_url(get_term_link($genre)); ?>" class="genre-item">
                                <?php echo esc_html($genre->name); ?>
                                <span class="text-muted" style="font-size: 11px;">(<?php echo $genre->count; ?>)</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php endif; ?>
            
        </div><!-- #primary -->
        
        <!-- Sidebar -->
        <?php get_sidebar(); ?>
        
    </div><!-- .site-main -->
</div><!-- .container -->

<?php get_footer(); ?>
