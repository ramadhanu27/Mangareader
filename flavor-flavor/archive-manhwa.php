<?php
/**
 * Manhwa Archive Template
 *
 * @package Flavor_Flavor
 */

get_header();

// Parse filter parameters (supports both clean URLs and query params)
$filters = flavor_parse_filter_url();
$current_genres = $filters['genre'];
$current_status = $filters['status'];
$current_type = $filters['type'];
$current_order = $filters['order'];

// Build query args
$paged = get_query_var('paged') ? get_query_var('paged') : 1;
$args = array(
    'post_type'      => 'manhwa',
    'posts_per_page' => 24,
    'paged'          => $paged,
);

// Apply ordering
switch ($current_order) {
    case 'title':
        $args['orderby'] = 'title';
        $args['order'] = 'ASC';
        break;
    case 'title-desc':
        $args['orderby'] = 'title';
        $args['order'] = 'DESC';
        break;
    case 'popular':
        $args['meta_key'] = '_manhwa_views';
        $args['orderby'] = 'meta_value_num';
        $args['order'] = 'DESC';
        break;
    case 'rating':
        $args['meta_key'] = '_manhwa_rating';
        $args['orderby'] = 'meta_value_num';
        $args['order'] = 'DESC';
        break;
    case 'latest':
    default:
        $args['orderby'] = 'modified';
        $args['order'] = 'DESC';
        break;
}

// Apply status filter (supports multiple values, case-insensitive)
if (!empty($current_status)) {
    if (count($current_status) === 1) {
        $args['meta_query'][] = array(
            'key'     => '_manhwa_status',
            'value'   => $current_status[0],
            'compare' => 'LIKE',
        );
    } else {
        $status_conditions = array('relation' => 'OR');
        foreach ($current_status as $status) {
            $status_conditions[] = array(
                'key'     => '_manhwa_status',
                'value'   => $status,
                'compare' => 'LIKE',
            );
        }
        $args['meta_query'][] = $status_conditions;
    }
}

// Apply type filter (supports multiple values, case-insensitive)
if (!empty($current_type)) {
    if (count($current_type) === 1) {
        $args['meta_query'][] = array(
            'key'     => '_manhwa_type',
            'value'   => $current_type[0],
            'compare' => 'LIKE',
        );
    } else {
        $type_conditions = array('relation' => 'OR');
        foreach ($current_type as $type) {
            $type_conditions[] = array(
                'key'     => '_manhwa_type',
                'value'   => $type,
                'compare' => 'LIKE',
            );
        }
        $args['meta_query'][] = $type_conditions;
    }
}

// Apply genre filter
if (!empty($current_genres)) {
    $args['tax_query'][] = array(
        'taxonomy' => 'manhwa_genre',
        'field'    => 'slug',
        'terms'    => $current_genres,
        'operator' => 'IN',
    );
}

$manga_query = new WP_Query($args);
?>

<div class="container">
    <?php if (function_exists('flavor_render_breadcrumbs')) flavor_render_breadcrumbs(); ?>
    
    <div class="site-main">
        <div id="primary" class="content-area">
            
            <section class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <span>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline; vertical-align: middle;">
                            <rect width="7" height="7" x="3" y="3" rx="1"></rect>
                            <rect width="7" height="7" x="14" y="3" rx="1"></rect>
                            <rect width="7" height="7" x="14" y="14" rx="1"></rect>
                            <rect width="7" height="7" x="3" y="14" rx="1"></rect>
                        </svg>
                        <?php 
                        if (is_tax('manhwa_genre')) {
                            single_term_title();
                        } else {
                            esc_html_e('All Manga', 'flavor-flavor');
                        }
                        ?>
                    </span>
                </div>
                
                <!-- Filters -->
                <div class="card-body filter-section">
                    <form method="get" class="filter-form-collapsible" id="filterForm">
                        <?php 
                        // Count active filters
                        $filter_count = count($current_genres) + count($current_status) + count($current_type) + ($current_order !== 'latest' ? 1 : 0);
                        
                        // Get all genres
                        $genres = get_terms(array(
                            'taxonomy' => 'manhwa_genre',
                            'hide_empty' => true,
                        ));
                        ?>
                        
                        <div class="filter-dropdowns">
                            <!-- Genre Filter -->
                            <div class="filter-dropdown">
                                <button type="button" class="filter-dropdown-toggle" data-target="genreFilter">
                                    <span class="filter-label">Genre</span>
                                    <span class="filter-value"><?php echo !empty($current_genres) ? count($current_genres) . ' selected' : 'All'; ?></span>
                                </button>
                                <div class="filter-dropdown-content" id="genreFilter">
                                    <div class="filter-dropdown-scroll">
                                        <?php if (!empty($genres) && !is_wp_error($genres)): ?>
                                            <?php foreach ($genres as $genre): ?>
                                                <label class="filter-checkbox-item">
                                                    <input type="checkbox" name="genre[]" value="<?php echo esc_attr($genre->slug); ?>" <?php echo in_array($genre->slug, $current_genres) ? 'checked' : ''; ?>>
                                                    <span class="checkbox-icon"></span>
                                                    <span><?php echo esc_html($genre->name); ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Status Filter -->
                            <div class="filter-dropdown">
                                <button type="button" class="filter-dropdown-toggle" data-target="statusFilter">
                                    <span class="filter-label">Status</span>
                                    <span class="filter-value"><?php echo !empty($current_status) ? implode(', ', $current_status) : 'All'; ?></span>
                                </button>
                                <div class="filter-dropdown-content" id="statusFilter">
                                    <?php 
                                    $statuses = array('Ongoing', 'Completed', 'Hiatus', 'Dropped');
                                    foreach ($statuses as $status): ?>
                                        <label class="filter-checkbox-item">
                                            <input type="checkbox" name="status[]" value="<?php echo esc_attr($status); ?>" <?php echo in_array($status, $current_status) ? 'checked' : ''; ?>>
                                            <span class="checkbox-icon"></span>
                                            <span><?php echo esc_html($status); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Type Filter -->
                            <div class="filter-dropdown">
                                <button type="button" class="filter-dropdown-toggle" data-target="typeFilter">
                                    <span class="filter-label">Type</span>
                                    <span class="filter-value"><?php echo !empty($current_type) ? implode(', ', $current_type) : 'All'; ?></span>
                                </button>
                                <div class="filter-dropdown-content" id="typeFilter">
                                    <?php 
                                    $types = array('Manga', 'Manhwa', 'Manhua');
                                    foreach ($types as $type): ?>
                                        <label class="filter-checkbox-item">
                                            <input type="checkbox" name="type[]" value="<?php echo esc_attr($type); ?>" <?php echo in_array($type, $current_type) ? 'checked' : ''; ?>>
                                            <span class="checkbox-icon"></span>
                                            <span><?php echo esc_html($type); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Order Filter -->
                            <div class="filter-dropdown">
                                <button type="button" class="filter-dropdown-toggle" data-target="orderFilter">
                                    <span class="filter-label">Order</span>
                                    <span class="filter-value">
                                        <?php 
                                        $order_labels = array(
                                            'latest' => 'Latest',
                                            'popular' => 'Popular', 
                                            'rating' => 'Rating',
                                            'title' => 'A-Z',
                                            'title-desc' => 'Z-A'
                                        );
                                        echo $order_labels[$current_order] ?? 'Latest';
                                        ?>
                                    </span>
                                </button>
                                <div class="filter-dropdown-content" id="orderFilter">
                                    <?php foreach ($order_labels as $value => $label): ?>
                                        <label class="filter-checkbox-item filter-radio-item">
                                            <input type="radio" name="order" value="<?php echo esc_attr($value); ?>" <?php checked($current_order, $value); ?>>
                                            <span class="checkbox-icon"></span>
                                            <span><?php echo esc_html($label); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="filter-submit-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.3-4.3"></path>
                            </svg>
                            Filter <?php echo $filter_count > 0 ? '<span class="filter-count">' . $filter_count . '</span>' : ''; ?>
                        </button>
                    </form>
                </div>
                
                <div class="card-body">
                    <?php if ($manga_query->have_posts()): ?>
                    <div class="manga-grid">
                        <?php while ($manga_query->have_posts()): $manga_query->the_post(); 
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
                                
                                <!-- Status Badge -->
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
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h3>
                                
                                <?php if (!empty($chapters)): ?>
                                <?php endif; ?>
                            </div>
                        </article>
                        <?php endwhile; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="pagination">
                        <?php
                        $pagination_args = array(
                            'total'     => $manga_query->max_num_pages,
                            'current'   => $paged,
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                        );
                        
                        // Preserve filter params in pagination
                        if (!empty($current_status)) {
                            $pagination_args['add_args']['status'] = $current_status;
                        }
                        if (!empty($current_type)) {
                            $pagination_args['add_args']['type'] = $current_type;
                        }
                        if ($current_order !== 'latest') {
                            $pagination_args['add_args']['order'] = $current_order;
                        }
                        
                        echo paginate_links($pagination_args);
                        ?>
                    </div>
                    
                    <?php wp_reset_postdata(); ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M12 16v-4"></path>
                                <path d="M12 8h.01"></path>
                            </svg>
                            <?php esc_html_e('No manga found matching your criteria.', 'flavor-flavor'); ?>
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
// Handle filter form submission with clean URLs
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('filterForm');
    if (!filterForm) return;
    
    filterForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const baseUrl = '<?php echo home_url('/manhwa/'); ?>';
        const segments = [];
        
        // Get selected genres
        const genreCheckboxes = filterForm.querySelectorAll('input[name="genre[]"]:checked');
        if (genreCheckboxes.length > 0) {
            const genres = Array.from(genreCheckboxes).map(cb => cb.value);
            segments.push('genre/' + genres.join(','));
        }
        
        // Get selected status
        const statusCheckboxes = filterForm.querySelectorAll('input[name="status[]"]:checked');
        if (statusCheckboxes.length > 0) {
            const statuses = Array.from(statusCheckboxes).map(cb => cb.value.toLowerCase());
            segments.push('status/' + statuses.join(','));
        }
        
        // Get selected type
        const typeCheckboxes = filterForm.querySelectorAll('input[name="type[]"]:checked');
        if (typeCheckboxes.length > 0) {
            const types = Array.from(typeCheckboxes).map(cb => cb.value.toLowerCase());
            segments.push('type/' + types.join(','));
        }
        
        // Get selected order
        const orderRadio = filterForm.querySelector('input[name="order"]:checked');
        const order = orderRadio ? orderRadio.value : 'latest';
        if (order && order !== 'latest') {
            segments.push('order/' + order);
        }
        
        // Build final URL
        let finalUrl = baseUrl;
        if (segments.length > 0) {
            finalUrl += segments.join('/') + '/';
        }
        
        // Redirect
        window.location.href = finalUrl;
    });
});
</script>

<?php get_footer(); ?>
