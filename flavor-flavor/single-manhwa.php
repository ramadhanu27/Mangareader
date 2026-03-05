<?php
/**
 * Single Manhwa Template
 * Based on ZeistManga v5.5 layout
 *
 * @package Flavor_Flavor
 */

get_header();

while (have_posts()): the_post();
    $meta = flavor_get_manhwa_meta(get_the_ID());
    $chapters = flavor_get_manhwa_chapters(get_the_ID());
    $genres = get_the_terms(get_the_ID(), 'manhwa_genre');
    
    // Increment view count
    flavor_increment_views(get_the_ID());
    
    // Check for adult content warning
    $has_adult_content = false;
    $adult_genres = array('Ecchi', 'Gore', 'Sexual Violence', 'Smut', 'Adult', 'Mature');
    if ($genres && !is_wp_error($genres)) {
        foreach ($genres as $genre) {
            if (in_array($genre->name, $adult_genres)) {
                $has_adult_content = true;
                break;
            }
        }
    }
?>

<div class="container">
    
    <!-- Hero Background -->
    <?php if (has_post_thumbnail()): ?>
    <div class="bg-photo-container">
        <div class="hero-background" style="background-image: url('<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'full')); ?>');"></div>
    </div>
    <?php endif; ?>
    
    <!-- Main Grid Layout -->
    <div class="manga-detail-grid<?php echo !has_post_thumbnail() ? ' no-hero' : ''; ?>">
        <!-- Left Column (a1) - Cover, Bookmark, Score, Meta -->
        <div class="manga-sidebar">
            <!-- Cover Image (figure) -->
            <div class="manga-cover-container">
                <?php if (has_post_thumbnail()): ?>
                    <?php the_post_thumbnail('manga-cover', array('class' => 'manga-cover-img')); ?>
                <?php else: ?>
                    <img src="<?php echo esc_url(flavor_get_placeholder_image()); ?>" alt="<?php the_title_attribute(); ?>" class="manga-cover-img">
                <?php endif; ?>
            </div>
            
            <!-- Bookmark Button -->
            <button type="button" class="bookmark-btn" id="bookmarkBtn" 
                    data-id="<?php the_ID(); ?>"
                    data-title="<?php the_title_attribute(); ?>"
                    data-url="<?php the_permalink(); ?>"
                    data-image="<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'manga-thumb')); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="1em" height="1em">
                    <path d="M1 3.25C1 2.56 1.56 2 2.249 2h.5c.69 0 1.248.56 1.248 1.25v9.495c0 .69-.559 1.25-1.248 1.25h-.5A1.25 1.25 0 0 1 1 12.744V3.249ZM2.249 3a.25.25 0 0 0-.25.25v9.495c0 .138.112.25.25.25h.5a.25.25 0 0 0 .25-.25V3.249a.25.25 0 0 0-.25-.25h-.5Zm2.748.25c0-.69.559-1.25 1.249-1.25h.5c.689 0 1.248.56 1.248 1.25v9.495c0 .69-.56 1.25-1.249 1.25h-.5a1.25 1.25 0 0 1-1.248-1.25V3.249ZM6.246 3a.25.25 0 0 0-.25.25v9.495c0 .138.112.25.25.25h.5a.25.25 0 0 0 .249-.25V3.249a.25.25 0 0 0-.25-.25h-.5Zm5.726 1.777a1.249 1.249 0 0 0-1.57-.713l-.583.204a1.25 1.25 0 0 0-.746 1.645l2.937 7.304c.249.62.94.933 1.571.713l.582-.204a1.25 1.25 0 0 0 .746-1.646l-2.937-7.303Zm-1.24.23a.25.25 0 0 1 .313.143l2.937 7.303a.25.25 0 0 1-.149.33l-.582.203a.25.25 0 0 1-.314-.142L10 5.54a.25.25 0 0 1 .149-.329l.582-.204Z" fill="currentColor"/>
                </svg>
                <span><?php esc_html_e('Bookmark', 'flavor-flavor'); ?></span>
            </button>
            
            <!-- Score/Rating (ScoreMultiItem) -->
            <?php if (!empty($meta['rating']) && $meta['rating'] > 0): ?>
            <div class="score-section">
                <div class="score-display">
                    <div class="score-stars">
                        <?php 
                        $score = floatval($meta['rating']);
                        $full_stars = floor($score / 2);
                        $half_star = ($score / 2) - $full_stars >= 0.5;
                        for ($i = 0; $i < 5; $i++):
                            if ($i < $full_stars):
                        ?>
                            <svg viewBox="0 0 24 24" class="star filled"><path fill="currentColor" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                        <?php elseif ($i == $full_stars && $half_star): ?>
                            <svg viewBox="0 0 24 24" class="star half"><path fill="currentColor" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                        <?php else: ?>
                            <svg viewBox="0 0 24 24" class="star empty"><path fill="none" stroke="currentColor" stroke-width="1" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                        <?php endif; endfor; ?>
                    </div>
                    <div class="score-number">
                        <strong><?php echo esc_html($meta['rating']); ?></strong>
                        <span class="text-muted">/ 10</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Progress Bar (based on views/popularity) -->
            <?php 
            $views = get_post_meta(get_the_ID(), '_manhwa_views', true);
            if ($views && $views > 0): 
                $popularity = min(($views / 1000) * 100, 100);
            ?>
            <div class="popularity-bar">
                <div class="popularity-label">
                    <span><?php esc_html_e('Popularity', 'flavor-flavor'); ?></span>
                    <span class="text-muted"><?php echo number_format($views); ?> <?php esc_html_e('views', 'flavor-flavor'); ?></span>
                </div>
                <div class="progress">
                    <div class="progress-bar" style="width: <?php echo esc_attr($popularity); ?>%;"></div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Aside Info (aside) -->
            <div class="manga-info-list">
                <?php if (!empty($meta['status'])): ?>
                <div class="info-item">
                    <span class="info-label"><?php esc_html_e('Status', 'flavor-flavor'); ?></span>
                    <span class="info-value status-<?php echo esc_attr(strtolower($meta['status'])); ?>">
                        <?php echo esc_html($meta['status']); ?>
                    </span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($meta['type'])): ?>
                <div class="info-item">
                    <span class="info-label"><?php esc_html_e('Type', 'flavor-flavor'); ?></span>
                    <span class="info-value"><?php echo esc_html($meta['type']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($meta['author'])): ?>
                <div class="info-item">
                    <span class="info-label"><?php esc_html_e('Author', 'flavor-flavor'); ?></span>
                    <span class="info-value"><?php echo esc_html($meta['author']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($meta['artist'])): ?>
                <div class="info-item">
                    <span class="info-label"><?php esc_html_e('Artist', 'flavor-flavor'); ?></span>
                    <span class="info-value"><?php echo esc_html($meta['artist']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($meta['release_year'])): ?>
                <div class="info-item">
                    <span class="info-label"><?php esc_html_e('Released', 'flavor-flavor'); ?></span>
                    <span class="info-value"><?php echo esc_html($meta['release_year']); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="info-item">
                    <span class="info-label"><?php esc_html_e('Chapters', 'flavor-flavor'); ?></span>
                    <span class="info-value"><?php echo count($chapters); ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label"><?php esc_html_e('Updated', 'flavor-flavor'); ?></span>
                    <span class="info-value"><?php echo get_the_modified_date(); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Right Column (a2) - Title, Synopsis, Chapters -->
        <article class="manga-content">
            <!-- Header Section -->
            <div class="manga-header-card card">
                <div class="card-body">
                    <header>
                        <h1 class="manga-title-main"><?php the_title(); ?></h1>
                        <?php if (!empty($meta['alt_title'])): ?>
                            <p class="manga-alternative text-muted"><?php echo esc_html($meta['alt_title']); ?></p>
                        <?php endif; ?>
                    </header>
                    
                    <!-- Genres (postLabels) -->
                    <?php if ($genres && !is_wp_error($genres)): ?>
                    <div class="manga-genres">
                        <?php foreach ($genres as $genre): ?>
                            <a href="<?php echo esc_url(get_term_link($genre)); ?>" class="genre-tag">
                                <?php echo esc_html($genre->name); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Synopsis Section -->
                    <div class="synopsis-section">
                        <h2 class="synopsis-title">
                            <?php printf(esc_html__('Read %s', 'flavor-flavor'), get_the_title()); ?>
                        </h2>
                        <div class="synopsis-content">
                            <?php the_content(); ?>
                        </div>
                        <p class="synopsis-footer text-muted">
                            <?php printf(
                                esc_html__('Read %1$s in Indonesian complete and new at %2$s. We provide Comics, Manhua, Manhwa, and Novels that you can read online for free.', 'flavor-flavor'),
                                '<strong>' . get_the_title() . '</strong>',
                                '<strong>' . get_bloginfo('name') . '</strong>'
                            ); ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Share Buttons (postShareButtons) -->
            <div class="share-buttons">
                <span class="share-label"><?php esc_html_e('Share:', 'flavor-flavor'); ?></span>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(get_permalink()); ?>" target="_blank" class="share-btn facebook" rel="nofollow noopener">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18.77 7.46H14.5v-1.9c0-.9.6-1.1 1-1.1h3V.5h-4.33C10.24.5 9.5 3.44 9.5 5.32v2.15h-3v4h3v12h5v-12h3.85l.42-4z"/></svg>
                </a>
                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(get_permalink()); ?>&text=<?php echo urlencode(get_the_title()); ?>" target="_blank" class="share-btn twitter" rel="nofollow noopener">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M23.44 4.83c-.8.37-1.5.38-2.22.02.93-.56.98-.96 1.32-2.02-.88.52-1.86.9-2.9 1.1-.82-.88-2-1.43-3.3-1.43-2.5 0-4.55 2.04-4.55 4.54 0 .36.03.7.1 1.04-3.77-.2-7.12-2-9.36-4.75-.4.67-.6 1.45-.6 2.3 0 1.56.8 2.95 2 3.77-.74-.03-1.44-.23-2.05-.57v.06c0 2.2 1.56 4.03 3.64 4.44-.67.2-1.37.2-2.06.08.58 1.8 2.26 3.12 4.25 3.16C5.78 18.1 3.37 18.74 1 18.46c2 1.3 4.4 2.04 6.97 2.04 8.35 0 12.92-6.92 12.92-12.93 0-.2 0-.4-.02-.6.9-.63 1.96-1.22 2.56-2.14z"/></svg>
                </a>
                <a href="https://wa.me/?text=<?php echo urlencode(get_the_title() . ' ' . get_permalink()); ?>" target="_blank" class="share-btn whatsapp" rel="nofollow noopener">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                </a>
                <button type="button" class="share-btn copy-link" onclick="navigator.clipboard.writeText('<?php echo esc_url(get_permalink()); ?>');this.textContent='Copied!';">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                </button>
            </div>
            
            <!-- Adult Content Warning -->
            <?php if ($has_adult_content): ?>
            <div class="content-warning">
                <svg viewBox="0 0 24 24" class="warning-icon">
                    <path d="M12 2C11.5 2 11 2.19 10.59 2.59L2.59 10.59C1.8 11.37 1.8 12.63 2.59 13.41L10.59 21.41C11.37 22.2 12.63 22.2 13.41 21.41L21.41 13.41C22.2 12.63 22.2 11.37 21.41 10.59L13.41 2.59C13 2.19 12.5 2 12 2M11 7H13V13H11V7M11 15H13V17H11V15Z" fill="currentColor"/>
                </svg>
                <p>
                    <?php printf(
                        esc_html__('Warning, the series titled "%s" may contain violence, blood or sexual content that is not appropriate for minors.', 'flavor-flavor'),
                        '<strong>' . get_the_title() . '</strong>'
                    ); ?>
                </p>
            </div>
            <?php endif; ?>
            
            <!-- Chapter List (postBody) -->
            <div class="chapter-list-card card">
                <h2 class="chapter-header">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                        <path d="M3 6h8v13H3z" opacity=".3"/>
                        <path d="M21 4H3c-1.1 0-2 .9-2 2v13c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zM11 19H3V6h8v13zm10 0h-8V6h8v13zm-7-9.5h6V11h-6zm0 2.5h6v1.5h-6zm0 2.5h6V16h-6z"/>
                    </svg>
                    <?php printf(esc_html__('Read %s', 'flavor-flavor'), get_the_title()); ?>
                </h2>
                
                <div class="chapter-body">
                    <!-- Chapter Search -->
                    <div class="chapter-search">
                        <input type="text" id="chapterSearch" class="search-input" 
                               placeholder="<?php esc_attr_e('Search for names..', 'flavor-flavor'); ?>">
                    </div>
                    
                    <?php if (!empty($chapters)): 
                        $first_chapter = end($chapters);
                        $latest_chapter = reset($chapters);
                        $first_chapter_num = $first_chapter['number'] ?? $first_chapter['title'] ?? '';
                        $latest_chapter_num = $latest_chapter['number'] ?? $latest_chapter['title'] ?? '';
                        $first_url = flavor_get_chapter_url(get_the_ID(), $first_chapter);
                        $latest_url = flavor_get_chapter_url(get_the_ID(), $latest_chapter);
                    ?>
                    
                    <!-- Reading History Section - Will be populated by JS -->
                    <div class="reading-history-section" id="readingHistorySection" style="display: none;">
                        <h3 class="history-header">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <?php esc_html_e('History', 'flavor-flavor'); ?>
                        </h3>
                        <div class="history-list" id="historyList">
                            <!-- History items will be inserted here by JavaScript -->
                        </div>
                    </div>
                    
                    <!-- Chapter Shortcuts -->
                    <div class="chapter-shortcuts">
                        <a href="<?php echo esc_url($first_url); ?>" class="shortcut-btn shortcut-first">
                            <span class="shortcut-label"><?php esc_html_e('First Chapter', 'flavor-flavor'); ?></span>
                            <span class="shortcut-chapter"><?php echo wp_kses_post(flavor_format_chapter_number($first_chapter_num)); ?></span>
                        </a>
                        <a href="<?php echo esc_url($latest_url); ?>" class="shortcut-btn shortcut-latest">
                            <span class="shortcut-label"><?php esc_html_e('New Chapter', 'flavor-flavor'); ?></span>
                            <span class="shortcut-chapter"><?php echo wp_kses_post(flavor_format_chapter_number($latest_chapter_num)); ?></span>
                        </a>
                    </div>
                    
                    <!-- Chapter Grid -->
                    <div class="chapter-grid" id="chapterList">
                        <?php 
                        $total_chapters = count($chapters);
                        $all_chapter_views = flavor_get_all_chapter_views(get_the_ID());
                        foreach ($chapters as $index => $chapter): 
                            $chapter_num = $chapter['number'] ?? $chapter['title'] ?? '';
                            $chapter_url = flavor_get_chapter_url(get_the_ID(), $chapter);
                            $is_last_chapter = ($index === 0); // First in array = last/newest chapter (desc order)
                            $display_time = !empty($chapter['added_at']) ? $chapter['added_at'] : ($chapter['date'] ?? '');
                            $ch_view_key = flavor_normalize_chapter_key($chapter_num);
                            $ch_views = isset($all_chapter_views[$ch_view_key]) ? intval($all_chapter_views[$ch_view_key]) : 0;
                        ?>
                        <a href="<?php echo esc_url($chapter_url); ?>" class="chapter-box" data-chapter="<?php echo esc_attr(strtolower($chapter_num)); ?>">
                            <span class="chapter-num"><?php echo flavor_format_chapter_number($chapter_num, $is_last_chapter, $meta['status']); ?></span>
                            <span class="chapter-meta-row">
                                <span class="chapter-views">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                    <?php echo flavor_format_views($ch_views); ?>
                                </span>
                                <?php if (!empty($display_time)): ?>
                                    <span class="chapter-date"><?php echo esc_html(flavor_time_ago($display_time)); ?></span>
                                <?php endif; ?>
                            </span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php else: ?>
                    <div class="alert alert-info" style="margin: 15px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M12 16v-4"></path>
                            <path d="M12 8h.01"></path>
                        </svg>
                        <?php esc_html_e('No chapters available yet.', 'flavor-flavor'); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Breadcrumb -->
            <nav class="breadcrumb-nav" aria-label="<?php esc_attr_e('Breadcrumb', 'flavor-flavor'); ?>">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="bc-home">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    <span>Home</span>
                </a>
                <svg class="bc-chevron" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                <a href="<?php echo esc_url(get_post_type_archive_link('manhwa')); ?>">Manhwa</a>
                <?php if (!empty($meta['type']) && strtolower($meta['type']) !== 'manhwa'): ?>
                    <svg class="bc-chevron" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    <a href="<?php echo esc_url(get_post_type_archive_link('manhwa') . 'type/' . strtolower($meta['type']) . '/'); ?>"><?php echo esc_html($meta['type']); ?></a>
                <?php endif; ?>
                <svg class="bc-chevron" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                <span class="current"><?php the_title(); ?></span>
            </nav>
        </article>
        
        
        
    </div><!-- .manga-detail-grid -->
    <!-- Related Manga (relatedPost) -->
        <?php if ($genres && !is_wp_error($genres)): 
            $genre_ids = wp_list_pluck($genres, 'term_id');
            $related = get_posts(array(
                'post_type'      => 'manhwa',
                'posts_per_page' => 6,
                'post__not_in'   => array(get_the_ID()),
                'tax_query'      => array(
                    array(
                        'taxonomy' => 'manhwa_genre',
                        'field'    => 'term_id',
                        'terms'    => $genre_ids,
                    ),
                ),
            ));
            
            if ($related):
        ?>
        <section class="related-manga-section">
            <div class="card">
                <div class="card-header">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect width="7" height="7" x="3" y="3" rx="1"></rect>
                        <rect width="7" height="7" x="14" y="3" rx="1"></rect>
                        <rect width="7" height="7" x="14" y="14" rx="1"></rect>
                        <rect width="7" height="7" x="3" y="14" rx="1"></rect>
                    </svg>
                    <?php esc_html_e('Related Manga', 'flavor-flavor'); ?>
                </div>
                <div class="card-body">
                    <div class="related-grid">
                        <?php foreach ($related as $rel): 
                            $rel_meta = flavor_get_manhwa_meta($rel->ID);
                        ?>
                        <article class="manga-item">
                            <div class="manga-thumb">
                                <a href="<?php echo get_permalink($rel->ID); ?>">
                                    <?php if (has_post_thumbnail($rel->ID)): ?>
                                        <?php echo get_the_post_thumbnail($rel->ID, 'manga-thumb'); ?>
                                    <?php else: ?>
                                        <img src="<?php echo esc_url(flavor_get_placeholder_image()); ?>" alt="<?php echo esc_attr($rel->post_title); ?>">
                                    <?php endif; ?>
                                </a>
                                
                                <!-- Type Badge Image (Flag) -->
                                <?php if (!empty($rel_meta['type'])): 
                                    $type_lower = strtolower($rel_meta['type']);
                                    $type_image = '';
                                    if (in_array($type_lower, ['manga', 'manhua', 'manhwa'])) {
                                        $type_image = get_template_directory_uri() . '/assets/images/' . $type_lower . '.png';
                                    }
                                    if ($type_image):
                                ?>
                                    <img src="<?php echo esc_url($type_image); ?>" alt="<?php echo esc_attr($rel_meta['type']); ?>" class="type-badge-img">
                                <?php endif; endif; ?>
                                
                                <?php if (!empty($rel_meta['rating']) && $rel_meta['rating'] > 0): ?>
                                    <span class="score-badge">
                                        <svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                                        <?php echo esc_html($rel_meta['rating']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="manga-info">
                                <h3 class="manga-title">
                                    <a href="<?php echo get_permalink($rel->ID); ?>"><?php echo esc_html($rel->post_title); ?></a>
                                </h3>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; endif; ?>
    
    
</div><!-- .container -->

<script>
// Chapter search filter
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('chapterSearch');
    const sortBtn = document.getElementById('chapterSort');
    const chapterList = document.getElementById('chapterList');
    let isReversed = false;
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            const chapters = document.querySelectorAll('#chapterList .chapter-box');
            let found = 0;
            
            chapters.forEach(function(chapter) {
                const chapterText = chapter.textContent.toLowerCase();
                const chapterData = chapter.getAttribute('data-chapter') || '';
                
                if (query === '' || chapterText.includes(query) || chapterData.includes(query)) {
                    chapter.style.display = '';
                    found++;
                } else {
                    chapter.style.display = 'none';
                }
            });
            
            // Show/hide no results message
            let noResults = document.getElementById('chapterNoResults');
            if (found === 0 && query !== '') {
                if (!noResults) {
                    noResults = document.createElement('div');
                    noResults.id = 'chapterNoResults';
                    noResults.className = 'chapter-no-results';
                    noResults.textContent = 'No chapters found for "' + query + '"';
                    document.getElementById('chapterList').after(noResults);
                } else {
                    noResults.textContent = 'No chapters found for "' + query + '"';
                    noResults.style.display = '';
                }
            } else if (noResults) {
                noResults.style.display = 'none';
            }
        });
    }
    
    if (sortBtn && chapterList) {
        sortBtn.addEventListener('click', function() {
            const chapters = Array.from(chapterList.children);
            chapters.reverse();
            chapterList.innerHTML = '';
            chapters.forEach(ch => chapterList.appendChild(ch));
            isReversed = !isReversed;
            this.classList.toggle('reversed', isReversed);
        });
    }
    
    // Reading History Feature
    const manhwaId = <?php echo get_the_ID(); ?>;
    const isLoggedIn = <?php echo is_user_logged_in() ? 'true' : 'false'; ?>;
    const ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
    const authNonce = '<?php echo wp_create_nonce('flavor_auth_nonce'); ?>';
    const manhwaTitle = '<?php echo esc_js(get_the_title()); ?>';
    const manhwaUrl = '<?php echo esc_js(get_permalink()); ?>';
    const manhwaThumbnail = '<?php echo esc_js(get_the_post_thumbnail_url(get_the_ID(), 'medium') ?: ''); ?>';
    
    // Load and display history
    function loadHistory() {
        const historySection = document.getElementById('readingHistorySection');
        const historyList = document.getElementById('historyList');
        if (!historySection || !historyList) return;

        if (isLoggedIn) {
            // Load from server
            const fd = new FormData();
            fd.append('action', 'flavor_history_get_chapters');
            fd.append('nonce', authNonce);
            fd.append('manhwa_id', manhwaId);
            fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(r => r.json())
                .then(res => {
                    if (res.success && res.data.chapters && res.data.chapters.length > 0) {
                        const recent = res.data.chapters.slice(0, 5);
                        let html = '';
                        recent.forEach(function(item) {
                            html += '<a href="' + item.url + '" class="history-item">' +
                                '<span class="history-chapter">' + item.chapter + '</span>' +
                                '</a>';
                        });
                        historyList.innerHTML = html;
                        historySection.style.display = '';
                    }
                })
                .catch(() => {});
        }
    }
    
    // Highlight read chapters in grid
    function highlightReadChapters() {
        if (isLoggedIn) {
            const fd = new FormData();
            fd.append('action', 'flavor_history_get_chapters');
            fd.append('nonce', authNonce);
            fd.append('manhwa_id', manhwaId);
            fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(r => r.json())
                .then(res => {
                    if (res.success && res.data.chapters) {
                        const readChapters = res.data.chapters.map(c => c.chapter_key);
                        const chapterBoxes = document.querySelectorAll('#chapterList .chapter-box');
                        chapterBoxes.forEach(function(box) {
                            const chapterData = box.getAttribute('data-chapter');
                            if (readChapters.includes(chapterData)) {
                                box.classList.add('chapter-read');
                            }
                        });
                    }
                })
                .catch(() => {});
        }
    }
    
    // Save chapter to history when clicked
    function saveToHistory(url, chapterNum, chapterKey) {
        if (!isLoggedIn) return;
        
        // Save to server - per-manhwa chapter history
        const fd = new FormData();
        fd.append('action', 'flavor_history_save_chapter');
        fd.append('nonce', authNonce);
        fd.append('manhwa_id', manhwaId);
        fd.append('chapter', chapterNum);
        fd.append('chapter_key', chapterKey);
        fd.append('url', url);
        fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' }).catch(() => {});
        
        // Also save to global reading history (for profile)
        const fd2 = new FormData();
        fd2.append('action', 'flavor_history_save');
        fd2.append('nonce', authNonce);
        fd2.append('manhwa_id', manhwaId);
        fd2.append('chapter', chapterNum);
        fd2.append('chapter_key', chapterKey);
        fd2.append('url', url);
        fd2.append('manhwa_title', manhwaTitle);
        fd2.append('manhwa_url', manhwaUrl);
        fd2.append('thumbnail', manhwaThumbnail);
        fetch(ajaxUrl, { method: 'POST', body: fd2, credentials: 'same-origin' }).catch(() => {});
    }
    
    // Attach click handlers to chapter boxes
    const chapterBoxes = document.querySelectorAll('#chapterList .chapter-box');
    chapterBoxes.forEach(function(box) {
        box.addEventListener('click', function() {
            const url = this.getAttribute('href');
            const chapterData = this.getAttribute('data-chapter');
            const chapterNumEl = this.querySelector('.chapter-num');
            const chapterText = chapterNumEl ? chapterNumEl.textContent.trim() : this.getAttribute('data-chapter');
            saveToHistory(url, chapterText, chapterData);
        });
    });
    
    // Also track shortcut clicks
    const shortcutBtns = document.querySelectorAll('.shortcut-btn');
    shortcutBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const url = this.getAttribute('href');
            const chapterSpan = this.querySelector('.shortcut-chapter');
            if (chapterSpan) {
                const chapterText = chapterSpan.textContent.trim();
                const chapterKey = chapterText.toLowerCase().replace(/[^\d.]/g, '');
                if (chapterText) {
                    saveToHistory(url, chapterText, chapterKey);
                }
            }
        });
    });
    
    // Initialize
    loadHistory();
    highlightReadChapters();
});
</script>

<?php endwhile; ?>

<?php // Post Reactions & Comments Section ?>
<div class="container" style="max-width: var(--blog-width); margin: 0 auto; padding: 0 15px;">
    <?php get_template_part('template-parts/post-reactions'); ?>
    <?php get_template_part('template-parts/custom-comments'); ?>
</div>

<?php get_footer(); ?>
