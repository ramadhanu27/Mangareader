<?php
/**
 * Manhwa CMS Admin Page
 * Custom admin interface for managing Manhwa posts
 *
 * @package Flavor_Flavor
 */

if (!defined('ABSPATH')) exit;

add_action('admin_menu', 'flavor_manhwa_cms_menu', 20);
add_action('admin_enqueue_scripts', 'flavor_manhwa_cms_assets');
add_action('admin_init', 'flavor_handle_manhwa_edit_save');
add_action('admin_init', 'flavor_handle_manhwa_add_save');
add_action('wp_ajax_mcms_save_chapter_images', 'flavor_ajax_save_chapter_images');

/* ================================================================
 * MENU REGISTRATION
 * ============================================================= */

function flavor_manhwa_cms_menu() {
    add_menu_page(
        'Manhwa CMS',
        'Manhwa CMS',
        'edit_posts',
        'manhwa-cms',
        'flavor_render_manhwa_cms_page',
        'dashicons-book-alt',
        4
    );
    add_submenu_page('manhwa-cms', 'All Manhwa', 'All Manhwa', 'edit_posts', 'manhwa-cms');
    add_submenu_page('manhwa-cms', 'Add New', 'Add New', 'edit_posts', 'manhwa-cms-add', 'flavor_render_manhwa_cms_page_add');
    add_submenu_page('manhwa-cms', 'Genres', 'Genres', 'manage_categories', 'edit-tags.php?taxonomy=manhwa_genre&post_type=manhwa');
    add_submenu_page('manhwa-cms', 'Chapters', 'Chapters', 'edit_posts', 'manhwa-chapters', 'flavor_render_manhwa_chapters_page');
    add_submenu_page('manhwa-cms', 'Statistics', 'Statistics', 'edit_posts', 'manhwa-stats', 'flavor_render_manhwa_stats_page');
}

/* ================================================================
 * ASSETS
 * ============================================================= */

function flavor_manhwa_cms_assets($hook) {
    if (strpos($hook, 'manhwa-cms') === false && strpos($hook, 'manhwa-chapters') === false && strpos($hook, 'manhwa-stats') === false) return;
    wp_enqueue_style('flavor-manhwa-cms', get_template_directory_uri() . '/assets/css/admin-manhwa.css', array(), '1.2');
    wp_enqueue_style('google-fonts-inter', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
    wp_enqueue_style('material-symbols', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap');

    // Edit page & chapter images need media uploader
    if ((isset($_GET['action']) && $_GET['action'] === 'edit') || isset($_GET['chapter_idx'])) {
        wp_enqueue_media();
    }
    // Chapter images page needs SortableJS
    if (isset($_GET['chapter_idx'])) {
        wp_enqueue_script('sortablejs', 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js', array(), '1.15.6', true);
    }
}

/* ================================================================
 * HELPERS
 * ============================================================= */

function flavor_format_number($num) {
    $num = intval($num);
    if ($num >= 1000000) return round($num / 1000000, 1) . 'M';
    if ($num >= 1000) return round($num / 1000, 1) . 'k';
    return number_format($num);
}

function flavor_render_stars($rating) {
    $rating = floatval($rating);
    $stars_val = $rating / 2; // Convert 10-scale to 5-star display
    $full  = floor($stars_val);
    $half  = ($stars_val - $full) >= 0.25 ? 1 : 0;
    $empty = 5 - $full - $half;
    $html = '';
    for ($i = 0; $i < $full; $i++) $html .= '<span class="material-symbols-outlined mcms-star-fill">star</span>';
    if ($half) $html .= '<span class="material-symbols-outlined mcms-star-fill">star_half</span>';
    for ($i = 0; $i < $empty; $i++) $html .= '<span class="material-symbols-outlined mcms-star-empty">star</span>';
    // Show original 1-10 rating value
    $html .= '<span class="mcms-rating-num">' . number_format($rating, 1) . '</span>';
    return $html;
}

function flavor_status_badge($status) {
    $map = array(
        'ongoing'   => array('label' => 'Ongoing',   'class' => 'mcms-badge-green'),
        'completed' => array('label' => 'Completed', 'class' => 'mcms-badge-blue'),
        'hiatus'    => array('label' => 'Hiatus',    'class' => 'mcms-badge-amber'),
    );
    $s = strtolower($status);
    $d = $map[$s] ?? array('label' => ucfirst($status), 'class' => 'mcms-badge-gray');
    return '<span class="mcms-badge ' . $d['class'] . '">' . $d['label'] . '</span>';
}

function flavor_mcms_edit_url($post_id) {
    return admin_url('admin.php?page=manhwa-cms&action=edit&post_id=' . $post_id);
}

/* ================================================================
 * HANDLE ACTIONS (Trash, Delete, Restore)
 * ============================================================= */

function flavor_handle_manhwa_cms_actions() {
    if (!isset($_GET['mcms_action']) || !isset($_GET['post_id'])) return;
    $action  = sanitize_text_field($_GET['mcms_action']);
    $post_id = intval($_GET['post_id']);
    if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'mcms_action_' . $post_id)) return;
    if (!current_user_can('edit_post', $post_id)) return;

    switch ($action) {
        case 'trash':   wp_trash_post($post_id); break;
        case 'restore': wp_untrash_post($post_id); break;
        case 'delete':  wp_delete_post($post_id, true); break;
    }
    wp_redirect(remove_query_arg(array('mcms_action', 'post_id', '_wpnonce')));
    exit;
}

/* ================================================================
 * SAVE EDIT PAGE
 * ============================================================= */

function flavor_handle_manhwa_edit_save() {
    if (!isset($_POST['mcms_save_edit']) || !isset($_POST['mcms_edit_nonce'])) return;
    if (!wp_verify_nonce($_POST['mcms_edit_nonce'], 'mcms_edit_manhwa')) return;

    $post_id = intval($_POST['post_id'] ?? 0);
    if (!$post_id || !current_user_can('edit_post', $post_id)) return;

    // Update post
    wp_update_post(array(
        'ID'           => $post_id,
        'post_title'   => sanitize_text_field($_POST['post_title'] ?? ''),
        'post_content' => wp_kses_post($_POST['post_content'] ?? ''),
        'post_status'  => sanitize_text_field($_POST['post_status'] ?? 'draft'),
    ));

    // Cover image
    $cover_id = intval($_POST['cover_image_id'] ?? 0);
    if ($cover_id > 0) {
        set_post_thumbnail($post_id, $cover_id);
    } else {
        delete_post_thumbnail($post_id);
    }

    // Meta fields
    if (isset($_POST['manhwa_author']))       update_post_meta($post_id, '_manhwa_author', sanitize_text_field($_POST['manhwa_author']));
    if (isset($_POST['manhwa_artist']))       update_post_meta($post_id, '_manhwa_artist', sanitize_text_field($_POST['manhwa_artist']));
    if (isset($_POST['manhwa_alt_title']))    update_post_meta($post_id, '_manhwa_alternative_title', sanitize_text_field($_POST['manhwa_alt_title']));
    if (isset($_POST['manhwa_type']))         update_post_meta($post_id, '_manhwa_type', sanitize_text_field($_POST['manhwa_type']));
    if (isset($_POST['manhwa_status_tax']))   update_post_meta($post_id, '_manhwa_status', sanitize_text_field($_POST['manhwa_status_tax']));
    if (isset($_POST['manhwa_source_url']))   update_post_meta($post_id, '_manhwa_source_url', esc_url_raw($_POST['manhwa_source_url']));
    if (isset($_POST['manhwa_rating']))       update_post_meta($post_id, '_manhwa_rating', floatval($_POST['manhwa_rating']));
    if (isset($_POST['manhwa_release_year'])) update_post_meta($post_id, '_manhwa_release_year', intval($_POST['manhwa_release_year']));

    // Genres
    if (isset($_POST['manhwa_genres'])) {
        $genre_ids = array_map('intval', $_POST['manhwa_genres']);
        wp_set_post_terms($post_id, $genre_ids, 'manhwa_genre');
    } else {
        wp_set_post_terms($post_id, array(), 'manhwa_genre');
    }

    // Chapters
    if (isset($_POST['chapters']) && is_array($_POST['chapters'])) {
        $chapters = array();
        foreach ($_POST['chapters'] as $ch) {
            $images = array();
            if (!empty($ch['images'])) {
                $decoded = json_decode(stripslashes($ch['images']), true);
                if (is_array($decoded)) {
                    $images = $decoded;
                }
            }
            $chapters[] = array(
                'number'   => sanitize_text_field($ch['number'] ?? ''),
                'title'    => sanitize_text_field($ch['title'] ?? ''),
                'date'     => sanitize_text_field($ch['date'] ?? ''),
                'images'   => $images,
            );
        }
        update_post_meta($post_id, '_manhwa_chapters', $chapters);
    }

    // Status taxonomy
    if (isset($_POST['manhwa_status_tax'])) {
        $status_val = sanitize_text_field($_POST['manhwa_status_tax']);
        wp_set_post_terms($post_id, array($status_val), 'manhwa_status');
    }

    wp_redirect(add_query_arg('saved', '1', flavor_mcms_edit_url($post_id)));
    exit;
}


/* ================================================================
 * MAIN PAGE ROUTER
 * ============================================================= */

function flavor_render_manhwa_cms_page() {
    // Route to edit page
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['post_id'])) {
        flavor_render_manhwa_edit_page(intval($_GET['post_id']));
        return;
    }
    // Route to add new
    if (isset($_GET['action']) && $_GET['action'] === 'new') {
        flavor_render_manhwa_add_page();
        return;
    }
    flavor_render_manhwa_list_page();
}

// Callback for the "Add New" submenu page
function flavor_render_manhwa_cms_page_add() {
    flavor_render_manhwa_add_page();
}

/* ================================================================
 * LIST PAGE
 * ============================================================= */

function flavor_render_manhwa_list_page() {
    flavor_handle_manhwa_cms_actions();

    $search  = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $status  = isset($_GET['post_status']) ? sanitize_text_field($_GET['post_status']) : '';
    $genre   = isset($_GET['manhwa_genre']) ? sanitize_text_field($_GET['manhwa_genre']) : '';
    $mdate   = isset($_GET['m']) ? sanitize_text_field($_GET['m']) : '';
    $paged   = max(1, intval($_GET['paged'] ?? 1));
    $per_page = 20;
    $base_url = admin_url('admin.php?page=manhwa-cms');

    if ($status === 'trash') $query_status = 'trash';
    elseif ($status === 'draft') $query_status = 'draft';
    elseif ($status === 'publish') $query_status = 'publish';
    else $query_status = array('publish', 'draft', 'pending', 'private');

    $args = array(
        'post_type' => 'manhwa', 'posts_per_page' => $per_page, 'paged' => $paged,
        'post_status' => $query_status, 'orderby' => 'date', 'order' => 'DESC',
    );
    if ($search) $args['s'] = $search;
    if ($genre) $args['tax_query'] = array(array('taxonomy' => 'manhwa_genre', 'field' => 'slug', 'terms' => $genre));
    if ($mdate) { $args['year'] = substr($mdate, 0, 4); $args['monthnum'] = intval(substr($mdate, 4, 2)); }

    $query = new WP_Query($args);
    $total_items = $query->found_posts;
    $total_pages = $query->max_num_pages;

    $counts = wp_count_posts('manhwa');
    $c_all   = ($counts->publish ?? 0) + ($counts->draft ?? 0) + ($counts->pending ?? 0) + ($counts->private ?? 0);
    $c_pub   = $counts->publish ?? 0;
    $c_draft = $counts->draft ?? 0;
    $c_trash = $counts->trash ?? 0;

    $genres = get_terms(array('taxonomy' => 'manhwa_genre', 'hide_empty' => false));

    global $wpdb;
    $months = $wpdb->get_results("SELECT DISTINCT YEAR(post_date) as y, MONTH(post_date) as m FROM $wpdb->posts WHERE post_type='manhwa' AND post_status IN ('publish','draft') ORDER BY post_date DESC");

    $add_new_url = admin_url('admin.php?page=manhwa-cms&action=new');

    ?>
    <div class="mcms-wrap">
        <div class="mcms-header">
            <div class="mcms-header-left">
                <h1 class="mcms-page-title">Manhwa</h1>
                <a href="<?php echo esc_url($add_new_url); ?>" class="mcms-btn-add">Add New</a>
                <button type="button" class="mcms-btn-export" onclick="mcmsOpenExportModal()" title="Export Manhwa">
                    <span class="material-symbols-outlined">download</span> Export
                </button>
                <button type="button" class="mcms-btn-import" onclick="mcmsOpenImportModal()" title="Import Manhwa">
                    <span class="material-symbols-outlined">upload</span> Import
                </button>
            </div>
            <div class="mcms-header-right">
                <a href="<?php echo home_url('/manhwa/'); ?>" target="_blank" class="mcms-visit-link">
                    <span class="material-symbols-outlined">open_in_new</span> Visit Site
                </a>
            </div>
        </div>

        <div class="mcms-tabs">
            <a href="<?php echo esc_url($base_url); ?>" class="mcms-tab <?php echo !$status ? 'active' : ''; ?>">All <span class="mcms-tab-count">(<?php echo number_format($c_all); ?>)</span></a>
            <a href="<?php echo esc_url(add_query_arg('post_status', 'publish', $base_url)); ?>" class="mcms-tab <?php echo $status === 'publish' ? 'active' : ''; ?>">Published <span class="mcms-tab-count">(<?php echo number_format($c_pub); ?>)</span></a>
            <a href="<?php echo esc_url(add_query_arg('post_status', 'draft', $base_url)); ?>" class="mcms-tab <?php echo $status === 'draft' ? 'active' : ''; ?>">Draft <span class="mcms-tab-count">(<?php echo number_format($c_draft); ?>)</span></a>
            <a href="<?php echo esc_url(add_query_arg('post_status', 'trash', $base_url)); ?>" class="mcms-tab <?php echo $status === 'trash' ? 'active' : ''; ?>">Trash <span class="mcms-tab-count">(<?php echo number_format($c_trash); ?>)</span></a>
        </div>

        <div class="mcms-filters">
            <div class="mcms-filters-left">
                <select class="mcms-select" id="mcms-date-filter">
                    <option value="">All Dates</option>
                    <?php foreach ($months as $mo): $mv = $mo->y . str_pad($mo->m, 2, '0', STR_PAD_LEFT); ?>
                        <option value="<?php echo $mv; ?>" <?php selected($mdate, $mv); ?>><?php echo date_i18n('F Y', mktime(0,0,0,$mo->m,1,$mo->y)); ?></option>
                    <?php endforeach; ?>
                </select>
                <select class="mcms-select" id="mcms-genre-filter">
                    <option value="">All Genres</option>
                    <?php if (!is_wp_error($genres)) foreach ($genres as $g): ?>
                        <option value="<?php echo esc_attr($g->slug); ?>" <?php selected($genre, $g->slug); ?>><?php echo esc_html($g->name); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="mcms-btn-filter" onclick="mcmsApplyFilters()">Filter</button>
            </div>
            <div class="mcms-filters-right">
                <form method="get" action="<?php echo admin_url('admin.php'); ?>" class="mcms-search-form">
                    <input type="hidden" name="page" value="manhwa-cms" />
                    <?php if ($status): ?><input type="hidden" name="post_status" value="<?php echo esc_attr($status); ?>" /><?php endif; ?>
                    <span class="material-symbols-outlined mcms-search-icon">search</span>
                    <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search Manhwa..." class="mcms-search-input" />
                </form>
            </div>
        </div>

        <div class="mcms-table-wrap">
            <table class="mcms-table">
                <thead>
                    <tr>
                        <th class="mcms-th-check"><input type="checkbox" class="mcms-checkbox" id="mcms-select-all" /></th>
                        <th class="mcms-th-thumb">Cover</th>
                        <th class="mcms-th-title">Title</th>
                        <th class="mcms-th-genre">Genre</th>
                        <th class="mcms-th-status">Status</th>
                        <th class="mcms-th-type">Type</th>
                        <th class="mcms-th-rating">Rating</th>
                        <th class="mcms-th-views">Views</th>
                        <th class="mcms-th-chapters">Ch.</th>
                        <th class="mcms-th-date">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($query->have_posts()): while ($query->have_posts()): $query->the_post();
                        $pid = get_the_ID();
                        $meta = flavor_get_manhwa_meta($pid);
                        $genres_list = wp_get_post_terms($pid, 'manhwa_genre', array('fields' => 'names'));
                        $chapters = get_post_meta($pid, '_manhwa_chapters', true);
                        $ch_count = is_array($chapters) ? count($chapters) : 0;
                        $views = get_post_meta($pid, '_manhwa_views', true) ?: 0;
                        $rating = $meta['rating'] ?: 0;
                        $m_status = $meta['status'] ?: '';
                        $m_type = $meta['type'] ?: '-';
                        $post_status_label = get_post_status() === 'publish' ? 'Published' : ucfirst(get_post_status());
                        $nonce = wp_create_nonce('mcms_action_' . $pid);
                        $edit_url = flavor_mcms_edit_url($pid);
                        $view_url = get_permalink($pid);
                        $trash_url = add_query_arg(array('mcms_action' => 'trash', 'post_id' => $pid, '_wpnonce' => $nonce), $base_url);
                        $restore_url = add_query_arg(array('mcms_action' => 'restore', 'post_id' => $pid, '_wpnonce' => $nonce), $base_url . '&post_status=trash');
                        $delete_url = add_query_arg(array('mcms_action' => 'delete', 'post_id' => $pid, '_wpnonce' => $nonce), $base_url . '&post_status=trash');
                        $chapters_url = admin_url('admin.php?page=manhwa-chapters&manhwa_id=' . $pid);
                    ?>
                    <tr class="mcms-row">
                        <td class="mcms-td-check"><input type="checkbox" class="mcms-checkbox" value="<?php echo $pid; ?>" /></td>
                        <td class="mcms-td-thumb">
                            <div class="mcms-thumb">
                                <?php if (has_post_thumbnail($pid)): ?>
                                    <img src="<?php echo get_the_post_thumbnail_url($pid, 'thumbnail'); ?>" alt="" />
                                <?php else: ?>
                                    <span class="material-symbols-outlined">image</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="mcms-td-title">
                            <a href="<?php echo esc_url($edit_url); ?>" class="mcms-title-link"><?php the_title(); ?></a>
                            <div class="mcms-row-actions">
                                <?php if (get_post_status() === 'trash'): ?>
                                    <a href="<?php echo esc_url($restore_url); ?>">Restore</a>
                                    <span>|</span>
                                    <a href="<?php echo esc_url($delete_url); ?>" class="mcms-action-delete" onclick="return confirm('Delete permanently?')">Delete</a>
                                <?php else: ?>
                                    <a href="<?php echo esc_url($edit_url); ?>">Edit</a>
                                    <span>|</span>
                                    <a href="<?php echo esc_url($chapters_url); ?>">Chapters</a>
                                    <span>|</span>
                                    <a href="<?php echo esc_url($trash_url); ?>" class="mcms-action-delete">Trash</a>
                                    <span>|</span>
                                    <a href="<?php echo esc_url($view_url); ?>" target="_blank">View</a>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="mcms-td-genre">
                            <div class="mcms-genre-tags">
                                <?php if (!is_wp_error($genres_list) && !empty($genres_list)):
                                    foreach (array_slice($genres_list, 0, 3) as $gn): ?>
                                        <span class="mcms-genre-tag"><?php echo esc_html($gn); ?></span>
                                    <?php endforeach;
                                    if (count($genres_list) > 3): ?>
                                        <span class="mcms-genre-tag">+<?php echo count($genres_list) - 3; ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="mcms-text-muted">—</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="mcms-td-status"><?php echo flavor_status_badge($m_status); ?></td>
                        <td class="mcms-td-type"><span class="mcms-text-secondary"><?php echo esc_html($m_type); ?></span></td>
                        <td class="mcms-td-rating"><div class="mcms-stars"><?php echo flavor_render_stars($rating); ?></div></td>
                        <td class="mcms-td-views"><span class="mcms-text-bold"><?php echo flavor_format_number($views); ?></span></td>
                        <td class="mcms-td-chapters"><a href="<?php echo esc_url($chapters_url); ?>" class="mcms-ch-link"><?php echo $ch_count; ?></a></td>
                        <td class="mcms-td-date">
                            <div class="mcms-date-info"><?php echo $post_status_label; ?><br/><?php echo get_the_date('Y/m/d'); ?></div>
                        </td>
                    </tr>
                    <?php endwhile; wp_reset_postdata(); else: ?>
                    <tr><td colspan="10" class="mcms-empty">No manhwa found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="mcms-pagination">
                <div class="mcms-pagination-info"><?php echo number_format($total_items); ?> items</div>
                <div class="mcms-pagination-nav">
                    <?php if ($total_pages > 1): ?>
                        <span class="mcms-page-info"><?php echo $paged; ?> of <?php echo $total_pages; ?> pages</span>
                        <?php if ($paged > 1): ?>
                            <a href="<?php echo esc_url(add_query_arg('paged', $paged - 1)); ?>" class="mcms-page-btn"><span class="material-symbols-outlined">chevron_left</span></a>
                        <?php else: ?>
                            <span class="mcms-page-btn disabled"><span class="material-symbols-outlined">chevron_left</span></span>
                        <?php endif; ?>
                        <?php if ($paged < $total_pages): ?>
                            <a href="<?php echo esc_url(add_query_arg('paged', $paged + 1)); ?>" class="mcms-page-btn"><span class="material-symbols-outlined">chevron_right</span></a>
                        <?php else: ?>
                            <span class="mcms-page-btn disabled"><span class="material-symbols-outlined">chevron_right</span></span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ EXPORT MODAL ═══════════════ -->
    <div id="mcms-export-modal" class="mcms-modal-overlay" style="display:none">
        <div class="mcms-modal">
            <div class="mcms-modal-header">
                <h2><span class="material-symbols-outlined">download</span> Export Manhwa</h2>
                <button type="button" class="mcms-modal-close" onclick="mcmsCloseModals()">&times;</button>
            </div>
            <form method="post" action="">
                <?php wp_nonce_field('mcms_export_manhwa', 'mcms_export_nonce'); ?>
                <div class="mcms-modal-body">
                    <div class="mcms-export-options">
                        <label class="mcms-radio-card">
                            <input type="radio" name="export_mode" value="all" checked />
                            <div class="mcms-radio-content">
                                <strong>Export All</strong>
                                <p>Export semua <?php echo number_format($c_all); ?> manhwa</p>
                            </div>
                        </label>
                        <label class="mcms-radio-card">
                            <input type="radio" name="export_mode" value="selected" />
                            <div class="mcms-radio-content">
                                <strong>Export Selected</strong>
                                <p id="mcms-selected-count">0 manhwa dipilih (centang di tabel)</p>
                            </div>
                        </label>
                    </div>
                    <div id="mcms-export-ids-container"></div>
                    <p class="mcms-modal-note">
                        <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle">info</span>
                        File JSON berisi: judul, sinopsis, metadata, genre, daftar chapter & URL gambar, dan cover URL.
                    </p>
                </div>
                <div class="mcms-modal-footer">
                    <button type="button" class="mcms-btn-cancel" onclick="mcmsCloseModals()">Cancel</button>
                    <button type="submit" class="mcms-btn-primary">
                        <span class="material-symbols-outlined">download</span> Download JSON
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ═══════════════ IMPORT MODAL ═══════════════ -->
    <div id="mcms-import-modal" class="mcms-modal-overlay" style="display:none">
        <div class="mcms-modal">
            <div class="mcms-modal-header">
                <h2><span class="material-symbols-outlined">upload</span> Import Manhwa</h2>
                <button type="button" class="mcms-modal-close" onclick="mcmsCloseModals()">&times;</button>
            </div>
            <form method="post" action="" enctype="multipart/form-data">
                <?php wp_nonce_field('mcms_import_manhwa', 'mcms_import_nonce'); ?>
                <div class="mcms-modal-body">
                    <!-- File Upload -->
                    <div class="mcms-upload-zone" id="mcms-upload-zone">
                        <span class="material-symbols-outlined" style="font-size:48px;color:#94a3b8">upload_file</span>
                        <p><strong>Pilih file JSON</strong> atau drag & drop ke sini</p>
                        <input type="file" name="import_file" id="mcms-import-file" accept=".json" required
                               onchange="mcmsFileSelected(this)" />
                        <p id="mcms-file-name" class="mcms-file-name" style="display:none"></p>
                    </div>

                    <!-- Duplicate Handling -->
                    <div class="mcms-import-option">
                        <label><strong>Jika manhwa sudah ada:</strong></label>
                        <select name="duplicate_mode" class="mcms-select" style="width:100%;margin-top:4px">
                            <option value="skip">Lewati / Skip (jangan import ulang)</option>
                            <option value="update">Update (timpa data lama)</option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div class="mcms-import-option">
                        <label><strong>Status setelah diimport:</strong></label>
                        <select name="import_status" class="mcms-select" style="width:100%;margin-top:4px">
                            <option value="keep">Gunakan status dari file export</option>
                            <option value="publish">Publish semua</option>
                            <option value="draft">Draft semua (untuk review dulu)</option>
                        </select>
                    </div>
                    <p class="mcms-modal-note">
                        <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle">info</span>
                        Cover image akan otomatis di-download. Proses import mungkin memakan waktu beberapa menit tergantung jumlah data.
                    </p>
                </div>
                <div class="mcms-modal-footer">
                    <button type="button" class="mcms-btn-cancel" onclick="mcmsCloseModals()">Cancel</button>
                    <button type="submit" class="mcms-btn-primary">
                        <span class="material-symbols-outlined">upload</span> Start Import
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php
    // Show import result notice
    $import_notice = get_transient('mcms_import_notice');
    if ($import_notice) {
        delete_transient('mcms_import_notice');
        $type = is_array($import_notice) ? $import_notice['type'] : 'info';
        $msg  = is_array($import_notice) ? $import_notice['msg'] : $import_notice;
        echo '<div class="mcms-notice mcms-notice-' . esc_attr($type) . '">';
        echo '<span class="material-symbols-outlined">' . ($type === 'success' ? 'check_circle' : 'error') . '</span> ';
        echo esc_html($msg);
        echo '</div>';
    }
    $export_notice = get_transient('mcms_export_notice');
    if ($export_notice) {
        delete_transient('mcms_export_notice');
        echo '<div class="mcms-notice mcms-notice-error"><span class="material-symbols-outlined">error</span> ' . esc_html($export_notice) . '</div>';
    }
    ?>

    <script>
    function mcmsApplyFilters() {
        var url = '<?php echo esc_url($base_url); ?>';
        var genre = document.getElementById('mcms-genre-filter').value;
        var date = document.getElementById('mcms-date-filter').value;
        if (genre) url += '&manhwa_genre=' + genre;
        if (date) url += '&m=' + date;
        <?php if ($status): ?>url += '&post_status=<?php echo esc_js($status); ?>';<?php endif; ?>
        window.location.href = url;
    }
    document.getElementById('mcms-select-all').addEventListener('change', function() {
        var cbs = document.querySelectorAll('.mcms-checkbox');
        for (var i = 0; i < cbs.length; i++) cbs[i].checked = this.checked;
    });

    /* ── Modal helpers ── */
    function mcmsOpenExportModal() {
        // Gather selected IDs
        var cbs = document.querySelectorAll('.mcms-checkbox:checked');
        var ids = [];
        cbs.forEach(function(cb) {
            if (cb.id !== 'mcms-select-all' && cb.value) ids.push(cb.value);
        });
        document.getElementById('mcms-selected-count').textContent = ids.length + ' manhwa dipilih (centang di tabel)';
        var container = document.getElementById('mcms-export-ids-container');
        container.innerHTML = '';
        ids.forEach(function(id) {
            var input = document.createElement('input');
            input.type = 'hidden'; input.name = 'export_ids[]'; input.value = id;
            container.appendChild(input);
        });
        document.getElementById('mcms-export-modal').style.display = 'flex';
    }
    function mcmsOpenImportModal() {
        document.getElementById('mcms-import-modal').style.display = 'flex';
    }
    function mcmsCloseModals() {
        document.getElementById('mcms-export-modal').style.display = 'none';
        document.getElementById('mcms-import-modal').style.display = 'none';
    }
    function mcmsFileSelected(input) {
        var nameEl = document.getElementById('mcms-file-name');
        if (input.files.length) {
            nameEl.textContent = input.files[0].name + ' (' + (input.files[0].size / 1024).toFixed(1) + ' KB)';
            nameEl.style.display = 'block';
        } else {
            nameEl.style.display = 'none';
        }
    }
    // Close modal on overlay click
    document.querySelectorAll('.mcms-modal-overlay').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (e.target === el) mcmsCloseModals();
        });
    });
    // Drag & drop
    var zone = document.getElementById('mcms-upload-zone');
    if (zone) {
        ['dragenter','dragover'].forEach(function(ev) {
            zone.addEventListener(ev, function(e) { e.preventDefault(); zone.classList.add('dragover'); });
        });
        ['dragleave','drop'].forEach(function(ev) {
            zone.addEventListener(ev, function(e) { e.preventDefault(); zone.classList.remove('dragover'); });
        });
        zone.addEventListener('drop', function(e) {
            var dt = e.dataTransfer;
            if (dt.files.length) {
                document.getElementById('mcms-import-file').files = dt.files;
                mcmsFileSelected(document.getElementById('mcms-import-file'));
            }
        });
    }
    </script>
    <?php
}

/* ================================================================
 * SAVE ADD NEW PAGE
 * ============================================================= */

function flavor_handle_manhwa_add_save() {
    if (!isset($_POST['mcms_save_new']) || !isset($_POST['mcms_add_nonce'])) return;
    if (!wp_verify_nonce($_POST['mcms_add_nonce'], 'mcms_add_manhwa')) return;
    if (!current_user_can('edit_posts')) return;

    // Create post
    $post_id = wp_insert_post(array(
        'post_type'    => 'manhwa',
        'post_title'   => sanitize_text_field($_POST['post_title'] ?? 'Untitled'),
        'post_content' => wp_kses_post($_POST['post_content'] ?? ''),
        'post_status'  => sanitize_text_field($_POST['post_status'] ?? 'draft'),
    ));

    if (!$post_id || is_wp_error($post_id)) return;

    // Cover image
    $cover_id = intval($_POST['cover_image_id'] ?? 0);
    if ($cover_id > 0) {
        set_post_thumbnail($post_id, $cover_id);
    }

    // Meta fields
    if (isset($_POST['manhwa_author']))       update_post_meta($post_id, '_manhwa_author', sanitize_text_field($_POST['manhwa_author']));
    if (isset($_POST['manhwa_artist']))       update_post_meta($post_id, '_manhwa_artist', sanitize_text_field($_POST['manhwa_artist']));
    if (isset($_POST['manhwa_alt_title']))    update_post_meta($post_id, '_manhwa_alternative_title', sanitize_text_field($_POST['manhwa_alt_title']));
    if (isset($_POST['manhwa_type']))         update_post_meta($post_id, '_manhwa_type', sanitize_text_field($_POST['manhwa_type']));
    if (isset($_POST['manhwa_status_tax']))   update_post_meta($post_id, '_manhwa_status', sanitize_text_field($_POST['manhwa_status_tax']));
    if (isset($_POST['manhwa_source_url']))   update_post_meta($post_id, '_manhwa_source_url', esc_url_raw($_POST['manhwa_source_url']));
    if (isset($_POST['manhwa_rating']))       update_post_meta($post_id, '_manhwa_rating', floatval($_POST['manhwa_rating']));
    if (isset($_POST['manhwa_release_year'])) update_post_meta($post_id, '_manhwa_release_year', intval($_POST['manhwa_release_year']));

    // Genres
    if (isset($_POST['manhwa_genres'])) {
        $genre_ids = array_map('intval', $_POST['manhwa_genres']);
        wp_set_post_terms($post_id, $genre_ids, 'manhwa_genre');
    }

    // Status taxonomy
    if (isset($_POST['manhwa_status_tax'])) {
        $status_val = sanitize_text_field($_POST['manhwa_status_tax']);
        wp_set_post_terms($post_id, array($status_val), 'manhwa_status');
    }

    // Chapters
    if (isset($_POST['chapters']) && is_array($_POST['chapters'])) {
        $chapters = array();
        foreach ($_POST['chapters'] as $ch) {
            $images = array();
            if (!empty($ch['images'])) {
                $decoded = json_decode(stripslashes($ch['images']), true);
                if (is_array($decoded)) {
                    $images = $decoded;
                }
            }
            $chapters[] = array(
                'number'   => sanitize_text_field($ch['number'] ?? ''),
                'title'    => sanitize_text_field($ch['title'] ?? ''),
                'date'     => sanitize_text_field($ch['date'] ?? date('Y-m-d')),
                'images'   => $images,
            );
        }
        update_post_meta($post_id, '_manhwa_chapters', $chapters);
    }

    wp_redirect(add_query_arg('saved', '1', flavor_mcms_edit_url($post_id)));
    exit;
}

/* ================================================================
 * ADD NEW PAGE
 * ============================================================= */

function flavor_render_manhwa_add_page() {
    $all_genres = get_terms(array('taxonomy' => 'manhwa_genre', 'hide_empty' => false));
    $list_url = admin_url('admin.php?page=manhwa-cms');
    ?>
    <div class="mcms-wrap">
        <form method="post" id="mcms-add-form">
            <?php wp_nonce_field('mcms_add_manhwa', 'mcms_add_nonce'); ?>
            <input type="hidden" name="mcms_save_new" value="1" />
            <input type="hidden" name="cover_image_id" id="cover_image_id" value="" />

            <!-- Header -->
            <div class="mcms-header">
                <div class="mcms-header-left">
                    <a href="<?php echo esc_url($list_url); ?>" class="mcms-back-link">
                        <span class="material-symbols-outlined">arrow_back</span>
                    </a>
                    <h1 class="mcms-page-title">Add New Manhwa</h1>
                </div>
                <div class="mcms-header-right">
                    <button type="submit" class="mcms-btn-save">
                        <span class="material-symbols-outlined">publish</span> Publish
                    </button>
                </div>
            </div>

            <!-- Breadcrumbs -->
            <div class="mcms-breadcrumb">
                <a href="<?php echo admin_url('admin.php?page=manhwa-cms'); ?>">All Manhwa</a>
                <span class="material-symbols-outlined" style="font-size:14px;color:#94a3b8">chevron_right</span>
                <span class="mcms-text-bold">Add New</span>
            </div>

            <!-- Main Layout -->
            <div class="mcms-edit-body">
                <!-- Left Column -->
                <div class="mcms-edit-main">
                    <!-- Title -->
                    <div>
                        <input type="text" name="post_title" value="" class="mcms-edit-title" placeholder="Manhwa Title..." required />
                    </div>

                    <!-- Synopsis -->
                    <div class="mcms-card">
                        <div class="mcms-card-header">
                            <span class="material-symbols-outlined">description</span> Synopsis
                        </div>
                        <div style="padding:0">
                            <textarea name="post_content" class="mcms-textarea" rows="5" placeholder="Write a synopsis..."></textarea>
                        </div>
                    </div>

                    <!-- Manhwa Information -->
                    <div class="mcms-card">
                        <div class="mcms-card-header">
                            <span class="material-symbols-outlined">info</span> Manhwa Information
                        </div>
                        <div class="mcms-edit-info-grid">
                            <div class="mcms-edit-field">
                                <label>Status</label>
                                <select name="manhwa_status_tax">
                                    <option value="ongoing" selected>Ongoing</option>
                                    <option value="completed">Completed</option>
                                    <option value="hiatus">Hiatus</option>
                                </select>
                            </div>
                            <div class="mcms-edit-field">
                                <label>Type</label>
                                <select name="manhwa_type">
                                    <?php
                                    $types = array('Manhwa', 'Manga', 'Manhua', 'Comic', 'Novel');
                                    foreach ($types as $t):
                                    ?>
                                    <option value="<?php echo $t; ?>"><?php echo $t; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mcms-edit-field">
                                <label>Author</label>
                                <input type="text" name="manhwa_author" value="" placeholder="Author name" />
                            </div>
                            <div class="mcms-edit-field">
                                <label>Artist</label>
                                <input type="text" name="manhwa_artist" value="" placeholder="Artist name" />
                            </div>
                            <div class="mcms-edit-field">
                                <div style="display:flex;justify-content:space-between;align-items:center">
                                    <label>Rating</label>
                                    <span class="mcms-rating-display" id="mcms-rating-val">0.0 / 10</span>
                                </div>
                                <input type="range" name="manhwa_rating" min="0" max="10" step="0.1" value="0" class="mcms-range-slider" id="mcms-rating-range" />
                            </div>
                            <div class="mcms-edit-field">
                                <label>Release Year</label>
                                <input type="number" name="manhwa_release_year" value="<?php echo date('Y'); ?>" placeholder="e.g. 2024" />
                            </div>
                            <div class="mcms-edit-field">
                                <label>Alternative Title</label>
                                <input type="text" name="manhwa_alt_title" value="" placeholder="Alternative name" />
                            </div>
                            <div class="mcms-edit-field">
                                <label>Source URL</label>
                                <input type="url" name="manhwa_source_url" value="" placeholder="https://..." />
                            </div>
                        </div>
                    </div>

                    <!-- Chapter Manager -->
                    <div class="mcms-card">
                        <div class="mcms-card-header" style="justify-content:space-between">
                            <div style="display:flex;align-items:center;gap:8px">
                                <span class="material-symbols-outlined">format_list_bulleted</span> Chapter Manager
                            </div>
                            <span class="mcms-ch-count-badge">0 Chapters</span>
                        </div>
                        <div style="padding:16px 20px">
                            <div id="mcms-chapter-list">
                                <!-- Empty — chapters will be added via JS -->
                            </div>
                            <button type="button" id="mcms-add-chapter" class="mcms-ch-add-btn">
                                <span class="material-symbols-outlined">add_circle</span> Add New Chapter
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <aside class="mcms-edit-sidebar">
                    <!-- Publish -->
                    <div class="mcms-card">
                        <div class="mcms-card-header">
                            <span class="material-symbols-outlined">publish</span> Publish
                        </div>
                        <div style="padding:16px 20px">
                            <div class="mcms-pub-row">
                                <span class="mcms-pub-label">Status:</span>
                                <select name="post_status" class="mcms-pub-select">
                                    <option value="publish">Published</option>
                                    <option value="draft" selected>Draft</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </div>
                            <div class="mcms-pub-actions">
                                <button type="submit" class="mcms-pub-btn-primary" style="width:100%">
                                    <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;margin-right:4px">publish</span> Create Manhwa
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Featured Image -->
                    <div class="mcms-card">
                        <div class="mcms-card-header">
                            <span class="material-symbols-outlined">image</span> Featured Image
                        </div>
                        <div style="padding:16px 20px">
                            <div class="mcms-cover-box" id="mcms-cover-box">
                                <div class="mcms-cover-empty" id="mcms-cover-empty-block">
                                    <span class="material-symbols-outlined">add_photo_alternate</span>
                                    <p>Set Featured Image</p>
                                </div>
                            </div>
                            <p class="mcms-cover-hint">Recommended size: 600×800px</p>
                        </div>
                    </div>

                    <!-- Genres -->
                    <div class="mcms-card">
                        <div class="mcms-card-header">
                            <span class="material-symbols-outlined">label</span> Genres
                        </div>
                        <div class="mcms-genre-pills">
                            <?php if (!is_wp_error($all_genres)):
                                foreach ($all_genres as $g):
                            ?>
                            <label class="mcms-genre-pill">
                                <input type="checkbox" name="manhwa_genres[]" value="<?php echo $g->term_id; ?>" />
                                <?php echo esc_html($g->name); ?>
                            </label>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </aside>
            </div>
        </form>
    </div>

    <script>
    jQuery(function($) {
        // Rating slider
        var rangeEl = document.getElementById('mcms-rating-range');
        var valEl = document.getElementById('mcms-rating-val');
        if (rangeEl) {
            rangeEl.addEventListener('input', function() {
                valEl.textContent = parseFloat(this.value).toFixed(1) + ' / 10';
            });
        }

        // Cover image
        var coverFrame;
        function openCoverPicker() {
            if (coverFrame) { coverFrame.open(); return; }
            coverFrame = wp.media({ title: 'Select Cover Image', button: { text: 'Use as Cover' }, multiple: false });
            coverFrame.on('select', function() {
                var att = coverFrame.state().get('selection').first().toJSON();
                $('#cover_image_id').val(att.id);
                var box = document.getElementById('mcms-cover-box');
                box.innerHTML = '<img src="' + att.url + '" alt="" id="mcms-cover-img" />' +
                    '<div class="mcms-cover-overlay">' +
                    '<button type="button" id="mcms-cover-change" class="mcms-cover-overlay-btn">Change Image</button>' +
                    '<button type="button" id="mcms-cover-remove" class="mcms-cover-overlay-link">Remove</button>' +
                    '</div>';
                bindCoverEvents();
            });
            coverFrame.open();
        }
        function bindCoverEvents() {
            $(document).off('click', '#mcms-cover-change').on('click', '#mcms-cover-change', function(e) { e.preventDefault(); openCoverPicker(); });
            $(document).off('click', '#mcms-cover-remove').on('click', '#mcms-cover-remove', function(e) {
                e.preventDefault();
                $('#cover_image_id').val('');
                var box = document.getElementById('mcms-cover-box');
                box.innerHTML = '<div class="mcms-cover-empty" id="mcms-cover-empty-block"><span class="material-symbols-outlined">add_photo_alternate</span><p>Set Featured Image</p></div>';
            });
        }
        bindCoverEvents();
        $('#mcms-cover-box').on('click', '#mcms-cover-empty-block', function() { openCoverPicker(); });

        // Add chapter
        var chIdx = 0;
        $('#mcms-add-chapter').on('click', function() {
            var num = chIdx + 1;
            var html = '<div class="mcms-ch-row-item mcms-ch-new">' +
                '<span class="material-symbols-outlined mcms-ch-drag">drag_indicator</span>' +
                '<div class="mcms-ch-row-body">' +
                    '<div class="mcms-ch-row-num">CH ' + num + '</div>' +
                    '<div class="mcms-ch-row-title">' +
                        '<input type="hidden" name="chapters[' + chIdx + '][number]" value="' + num + '" />' +
                        '<input type="text" name="chapters[' + chIdx + '][title]" value="Chapter ' + num + '" class="mcms-ch-row-input" />' +
                        '<input type="hidden" name="chapters[' + chIdx + '][date]" value="<?php echo date('Y-m-d'); ?>" />' +
                        '<input type="hidden" name="chapters[' + chIdx + '][images]" value="[]" />' +
                    '</div>' +
                '</div>' +
                '<div class="mcms-ch-row-actions-wrap">' +
                    '<button type="button" class="mcms-ch-row-act mcms-ch-row-del" onclick="this.closest(\'.mcms-ch-row-item\').remove();mcmsChCount()">' +
                        '<span class="material-symbols-outlined">delete</span>' +
                    '</button>' +
                '</div>' +
            '</div>';
            $('#mcms-chapter-list').prepend(html);
            chIdx++;
            mcmsChCount();
        });

        // Genre pill toggle
        $('.mcms-genre-pill input').on('change', function() {
            $(this).parent().toggleClass('active', this.checked);
        });
    });

    function mcmsChCount() {
        var c = document.querySelectorAll('.mcms-ch-row-item').length;
        var badge = document.querySelector('.mcms-ch-count-badge');
        if (badge) badge.textContent = c + ' Chapters';
    }
    </script>
    <?php
}

/* ================================================================
 * EDIT PAGE
 * ============================================================= */

function flavor_render_manhwa_edit_page($post_id) {
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'manhwa') {
        echo '<div class="mcms-wrap"><div class="mcms-header"><h1 class="mcms-page-title">Manhwa not found</h1></div></div>';
        return;
    }

    $saved = isset($_GET['saved']);
    $meta = flavor_get_manhwa_meta($post_id);
    $thumb_id  = get_post_thumbnail_id($post_id);
    $thumb_url = get_the_post_thumbnail_url($post_id, 'medium');
    $chapters  = get_post_meta($post_id, '_manhwa_chapters', true);
    if (!is_array($chapters)) $chapters = array();
    $all_genres = get_terms(array('taxonomy' => 'manhwa_genre', 'hide_empty' => false));
    $post_genres = wp_get_post_terms($post_id, 'manhwa_genre', array('fields' => 'ids'));
    $source_url = get_post_meta($post_id, '_manhwa_source_url', true);
    $list_url = admin_url('admin.php?page=manhwa-cms');
    $view_url = get_permalink($post_id);
    $chapters_url = admin_url('admin.php?page=manhwa-chapters&manhwa_id=' . $post_id);
    $rating = floatval($meta['rating'] ?: 0);
    $last_modified = get_the_modified_date('M j, Y', $post_id);
    $last_author = get_the_modified_author();

    ?>
    <div class="mcms-wrap">
        <form method="post" id="mcms-edit-form">
            <?php wp_nonce_field('mcms_edit_manhwa', 'mcms_edit_nonce'); ?>
            <input type="hidden" name="mcms_save_edit" value="1" />
            <input type="hidden" name="post_id" value="<?php echo $post_id; ?>" />
            <input type="hidden" name="cover_image_id" id="cover_image_id" value="<?php echo $thumb_id; ?>" />

            <!-- Header -->
            <div class="mcms-header">
                <div class="mcms-header-left">
                    <a href="<?php echo esc_url($list_url); ?>" class="mcms-back-link">
                        <span class="material-symbols-outlined">arrow_back</span>
                    </a>
                    <h1 class="mcms-page-title">Edit Manhwa</h1>
                </div>
                <div class="mcms-header-right">
                    <a href="<?php echo esc_url($view_url); ?>" target="_blank" class="mcms-visit-link">
                        <span class="material-symbols-outlined">open_in_new</span> View
                    </a>
                    <button type="submit" class="mcms-btn-save">
                        <span class="material-symbols-outlined">save</span> Update
                    </button>
                </div>
            </div>

            <?php if ($saved): ?>
            <div class="mcms-notice mcms-notice-success">
                <span class="material-symbols-outlined">check_circle</span> Manhwa updated successfully.
            </div>
            <?php endif; ?>

            <!-- Breadcrumbs -->
            <div class="mcms-breadcrumb">
                <a href="<?php echo admin_url('admin.php?page=manhwa-cms'); ?>">All Manhwa</a>
                <span class="material-symbols-outlined" style="font-size:14px;color:#94a3b8">chevron_right</span>
                <span class="mcms-text-bold"><?php echo esc_html($post->post_title); ?></span>
            </div>

            <!-- Main Layout -->
            <div class="mcms-edit-body">
                <!-- Left Column -->
                <div class="mcms-edit-main">
                    <!-- Title + Meta -->
                    <div>
                        <input type="text" name="post_title" value="<?php echo esc_attr($post->post_title); ?>" class="mcms-edit-title" placeholder="Manhwa Title..." />
                        <p class="mcms-edit-meta">
                            <span class="material-symbols-outlined" style="font-size:14px">history</span>
                            Last modified <?php echo $last_author ? 'by ' . esc_html($last_author) : ''; ?> on <?php echo esc_html($last_modified); ?>
                        </p>
                    </div>

                    <!-- Synopsis -->
                    <div class="mcms-card">
                        <div class="mcms-card-header">
                            <span class="material-symbols-outlined">description</span> Synopsis
                        </div>
                        <div style="padding:0">
                            <textarea name="post_content" class="mcms-textarea" rows="5" placeholder="Write a synopsis..."><?php echo esc_textarea($post->post_content); ?></textarea>
                        </div>
                    </div>

                    <!-- Manhwa Information -->
                    <div class="mcms-card">
                        <div class="mcms-card-header">
                            <span class="material-symbols-outlined">info</span> Manhwa Information
                        </div>
                        <div class="mcms-edit-info-grid">
                            <div class="mcms-edit-field">
                                <label>Status</label>
                                <select name="manhwa_status_tax">
                                    <?php
                                    $statuses = array('ongoing' => 'Ongoing', 'completed' => 'Completed', 'hiatus' => 'Hiatus');
                                    foreach ($statuses as $val => $label):
                                        $sel = ($meta['status'] === $val) ? 'selected' : '';
                                    ?>
                                    <option value="<?php echo $val; ?>" <?php echo $sel; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mcms-edit-field">
                                <label>Type</label>
                                <select name="manhwa_type">
                                    <?php
                                    $types = array('Manhwa', 'Manga', 'Manhua', 'Comic', 'Novel');
                                    foreach ($types as $t):
                                        $sel = ($meta['type'] === $t) ? 'selected' : '';
                                    ?>
                                    <option value="<?php echo $t; ?>" <?php echo $sel; ?>><?php echo $t; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mcms-edit-field">
                                <label>Author</label>
                                <input type="text" name="manhwa_author" value="<?php echo esc_attr($meta['author']); ?>" placeholder="Author name" />
                            </div>
                            <div class="mcms-edit-field">
                                <label>Artist</label>
                                <input type="text" name="manhwa_artist" value="<?php echo esc_attr($meta['artist']); ?>" placeholder="Artist name" />
                            </div>
                            <div class="mcms-edit-field">
                                <div style="display:flex;justify-content:space-between;align-items:center">
                                    <label>Rating</label>
                                    <span class="mcms-rating-display" id="mcms-rating-val"><?php echo number_format($rating, 1); ?> / 10</span>
                                </div>
                                <input type="range" name="manhwa_rating" min="0" max="10" step="0.1" value="<?php echo esc_attr($rating); ?>" class="mcms-range-slider" id="mcms-rating-range" />
                            </div>
                            <div class="mcms-edit-field">
                                <label>Release Year</label>
                                <input type="number" name="manhwa_release_year" value="<?php echo esc_attr($meta['release_year'] ?? ''); ?>" placeholder="e.g. 2024" />
                            </div>
                            <div class="mcms-edit-field">
                                <label>Alternative Title</label>
                                <input type="text" name="manhwa_alt_title" value="<?php echo esc_attr($meta['alt_title']); ?>" placeholder="Alternative name" />
                            </div>
                            <div class="mcms-edit-field">
                                <label>Source URL</label>
                                <input type="url" name="manhwa_source_url" value="<?php echo esc_attr($source_url); ?>" placeholder="https://..." />
                            </div>
                        </div>
                    </div>

                    <!-- Chapter Manager -->
                    <div class="mcms-card">
                        <div class="mcms-card-header" style="justify-content:space-between">
                            <div style="display:flex;align-items:center;gap:8px">
                                <span class="material-symbols-outlined">format_list_bulleted</span> Chapter Manager
                            </div>
                            <span class="mcms-ch-count-badge"><?php echo count($chapters); ?> Chapters</span>
                        </div>
                        <div style="padding:16px 20px">
                            <div id="mcms-chapter-list">
                                <?php foreach ($chapters as $i => $ch):
                                    $ch_num = $ch['number'] ?? $ch['title'] ?? '';
                                    $ch_title = $ch['title'] ?? '';
                                    $ch_date = $ch['date'] ?? '';
                                    $ch_images = isset($ch['images']) && is_array($ch['images']) ? $ch['images'] : array();
                                    $img_count = count($ch_images);
                                    $ch_url = flavor_get_chapter_url($post_id, $ch);
                                    $images_url = admin_url('admin.php?page=manhwa-chapters&manhwa_id=' . $post_id . '&chapter_idx=' . $i);
                                    if (preg_match('/(?:chapter|ch)[\s._-]*(\d+(?:\.\d+)?)/i', $ch_title, $cm)) {
                                        $display_num = $cm[1];
                                    } elseif (preg_match('/(\d+)/', $ch_num ?: $ch_title, $cm)) {
                                        $display_num = $cm[1];
                                    } else {
                                        $display_num = $i + 1;
                                    }
                                ?>
                                <div class="mcms-ch-row-item">
                                    <span class="material-symbols-outlined mcms-ch-drag">drag_indicator</span>
                                    <div class="mcms-ch-row-body">
                                        <div class="mcms-ch-row-num">CH <?php echo esc_html($display_num); ?></div>
                                        <div class="mcms-ch-row-title">
                                            <input type="hidden" name="chapters[<?php echo $i; ?>][number]" value="<?php echo esc_attr($ch_num); ?>" />
                                            <input type="text" name="chapters[<?php echo $i; ?>][title]" value="<?php echo esc_attr($ch_title); ?>" class="mcms-ch-row-input" />
                                            <input type="hidden" name="chapters[<?php echo $i; ?>][date]" value="<?php echo esc_attr($ch_date); ?>" />
                                            <input type="hidden" name="chapters[<?php echo $i; ?>][images]" class="mcms-ch-images-json" value="<?php echo esc_attr(json_encode($ch_images)); ?>" />
                                        </div>
                                    </div>
                                    <div class="mcms-ch-row-actions-wrap">
                                        <button type="button" class="mcms-ch-row-act mcms-ch-toggle-urls" title="<?php echo $img_count; ?> image URLs" onclick="mcmsToggleUrls(this)">
                                            <span class="material-symbols-outlined">link</span>
                                            <?php if ($img_count > 0): ?><span class="mcms-ch-img-badge"><?php echo $img_count; ?></span><?php endif; ?>
                                        </button>
                                        <a href="<?php echo esc_url($images_url); ?>" class="mcms-ch-row-act" title="Edit images">
                                            <span class="material-symbols-outlined">photo_library</span>
                                        </a>
                                        <a href="<?php echo esc_url($ch_url); ?>" target="_blank" class="mcms-ch-row-act" title="View">
                                            <span class="material-symbols-outlined">visibility</span>
                                        </a>
                                        <button type="button" class="mcms-ch-row-act mcms-ch-row-del" title="Remove" onclick="this.closest('.mcms-ch-row-item').remove();mcmsChCount()">
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    </div>
                                </div>
                                <?php if ($img_count > 0): ?>
                                <div class="mcms-ch-urls-panel" id="mcms-urls-panel-<?php echo $i; ?>" style="display:none">
                                    <div class="mcms-ch-urls-header">
                                        <span class="material-symbols-outlined" style="font-size:14px;color:#2170b0">link</span>
                                        <span>Image URLs (<?php echo $img_count; ?>)</span>
                                        <button type="button" class="mcms-ch-urls-edit-btn" onclick="mcmsEditUrls(this)">Edit URLs</button>
                                    </div>
                                    <textarea class="mcms-ch-urls-textarea" rows="<?php echo min($img_count + 1, 8); ?>" readonly><?php
                                        foreach ($ch_images as $img) {
                                            $url = is_array($img) ? ($img['url'] ?? '') : $img;
                                            if ($url) echo esc_url($url) . "\n";
                                        }
                                    ?></textarea>
                                </div>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" id="mcms-add-chapter" class="mcms-ch-add-btn">
                                <span class="material-symbols-outlined">add_circle</span> Add New Chapter
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <aside class="mcms-edit-sidebar">
                    <!-- Publish -->
                    <div class="mcms-card">
                        <div class="mcms-card-header">
                            <span class="material-symbols-outlined">publish</span> Publish
                        </div>
                        <div style="padding:16px 20px">
                            <div class="mcms-pub-row">
                                <span class="mcms-pub-label">Status:</span>
                                <select name="post_status" class="mcms-pub-select">
                                    <option value="publish" <?php selected($post->post_status, 'publish'); ?>>Published</option>
                                    <option value="draft" <?php selected($post->post_status, 'draft'); ?>>Draft</option>
                                    <option value="pending" <?php selected($post->post_status, 'pending'); ?>>Pending</option>
                                </select>
                            </div>
                            <div class="mcms-pub-row">
                                <span class="mcms-pub-label">Published:</span>
                                <span class="mcms-pub-val"><?php echo get_the_date('M j, Y', $post_id); ?></span>
                            </div>
                            <div class="mcms-pub-row">
                                <span class="mcms-pub-label">Views:</span>
                                <span class="mcms-pub-val"><?php echo flavor_format_number(get_post_meta($post_id, '_manhwa_views', true) ?: 0); ?></span>
                            </div>
                            <div class="mcms-pub-actions">
                                <a href="<?php echo esc_url($view_url); ?>" target="_blank" class="mcms-pub-btn-outline">Preview</a>
                                <button type="submit" class="mcms-pub-btn-primary">Update</button>
                            </div>
                            <a href="<?php echo get_delete_post_link($post_id); ?>" class="mcms-pub-trash" onclick="return confirm('Move to trash?')">Move to Trash</a>
                        </div>
                    </div>

                    <!-- Featured Image -->
                    <div class="mcms-card">
                        <div class="mcms-card-header">
                            <span class="material-symbols-outlined">image</span> Featured Image
                        </div>
                        <div style="padding:16px 20px">
                            <div class="mcms-cover-box" id="mcms-cover-box">
                                <?php if ($thumb_url): ?>
                                    <img src="<?php echo esc_url($thumb_url); ?>" alt="" id="mcms-cover-img" />
                                    <div class="mcms-cover-overlay">
                                        <button type="button" id="mcms-cover-change" class="mcms-cover-overlay-btn">Change Image</button>
                                        <button type="button" id="mcms-cover-remove" class="mcms-cover-overlay-link">Remove</button>
                                    </div>
                                <?php else: ?>
                                    <div class="mcms-cover-empty" id="mcms-cover-empty-block">
                                        <span class="material-symbols-outlined">add_photo_alternate</span>
                                        <p>Set Featured Image</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <p class="mcms-cover-hint">Recommended size: 600×800px</p>
                        </div>
                    </div>

                    <!-- Genres -->
                    <div class="mcms-card">
                        <div class="mcms-card-header">
                            <span class="material-symbols-outlined">label</span> Genres
                        </div>
                        <div class="mcms-genre-pills">
                            <?php if (!is_wp_error($all_genres)):
                                foreach ($all_genres as $g):
                                    $checked = in_array($g->term_id, $post_genres);
                            ?>
                            <label class="mcms-genre-pill <?php echo $checked ? 'active' : ''; ?>">
                                <input type="checkbox" name="manhwa_genres[]" value="<?php echo $g->term_id; ?>" <?php checked($checked); ?> />
                                <?php echo esc_html($g->name); ?>
                            </label>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </aside>
            </div>
        </form>
    </div>

    <script>
    jQuery(function($) {
        // Rating slider
        var rangeEl = document.getElementById('mcms-rating-range');
        var valEl = document.getElementById('mcms-rating-val');
        if (rangeEl) {
            rangeEl.addEventListener('input', function() {
                valEl.textContent = parseFloat(this.value).toFixed(1) + ' / 10';
            });
        }

        // Cover image
        var coverFrame;
        function openCoverPicker() {
            if (coverFrame) { coverFrame.open(); return; }
            coverFrame = wp.media({ title: 'Select Cover Image', button: { text: 'Use as Cover' }, multiple: false });
            coverFrame.on('select', function() {
                var att = coverFrame.state().get('selection').first().toJSON();
                $('#cover_image_id').val(att.id);
                var box = document.getElementById('mcms-cover-box');
                box.innerHTML = '<img src="' + att.url + '" alt="" id="mcms-cover-img" />' +
                    '<div class="mcms-cover-overlay">' +
                    '<button type="button" id="mcms-cover-change" class="mcms-cover-overlay-btn">Change Image</button>' +
                    '<button type="button" id="mcms-cover-remove" class="mcms-cover-overlay-link">Remove</button>' +
                    '</div>';
                bindCoverEvents();
            });
            coverFrame.open();
        }
        function bindCoverEvents() {
            $(document).off('click', '#mcms-cover-change').on('click', '#mcms-cover-change', function(e) { e.preventDefault(); openCoverPicker(); });
            $(document).off('click', '#mcms-cover-remove').on('click', '#mcms-cover-remove', function(e) {
                e.preventDefault();
                $('#cover_image_id').val('');
                var box = document.getElementById('mcms-cover-box');
                box.innerHTML = '<div class="mcms-cover-empty" id="mcms-cover-empty-block"><span class="material-symbols-outlined">add_photo_alternate</span><p>Set Featured Image</p></div>';
            });
        }
        bindCoverEvents();
        $('#mcms-cover-box').on('click', '#mcms-cover-empty-block', function() { openCoverPicker(); });

        // Add chapter
        var chIdx = <?php echo count($chapters); ?>;
        $('#mcms-add-chapter').on('click', function() {
            var num = chIdx + 1;
            var html = '<div class="mcms-ch-row-item mcms-ch-new">' +
                '<span class="material-symbols-outlined mcms-ch-drag">drag_indicator</span>' +
                '<div class="mcms-ch-row-body">' +
                    '<div class="mcms-ch-row-num">CH ' + num + '</div>' +
                    '<div class="mcms-ch-row-title">' +
                        '<input type="hidden" name="chapters[' + chIdx + '][number]" value="' + num + '" />' +
                        '<input type="text" name="chapters[' + chIdx + '][title]" value="Chapter ' + num + '" class="mcms-ch-row-input" />' +
                        '<input type="hidden" name="chapters[' + chIdx + '][date]" value="<?php echo date('Y-m-d'); ?>" />' +
                        '<input type="hidden" name="chapters[' + chIdx + '][images]" value="[]" />' +
                    '</div>' +
                '</div>' +
                '<div class="mcms-ch-row-actions-wrap">' +
                    '<button type="button" class="mcms-ch-row-act mcms-ch-row-del" onclick="this.closest(\'.mcms-ch-row-item\').remove();mcmsChCount()">' +
                        '<span class="material-symbols-outlined">delete</span>' +
                    '</button>' +
                '</div>' +
            '</div>';
            $('#mcms-chapter-list').prepend(html);
            chIdx++;
            mcmsChCount();
        });

        // Genre pill toggle
        $('.mcms-genre-pill input').on('change', function() {
            $(this).parent().toggleClass('active', this.checked);
        });
    });

    function mcmsChCount() {
        var c = document.querySelectorAll('.mcms-ch-row-item').length;
        var badge = document.querySelector('.mcms-ch-count-badge');
        if (badge) badge.textContent = c + ' Chapters';
    }

    function mcmsToggleUrls(btn) {
        // Find the next urls-panel sibling after the row-item
        var row = btn.closest('.mcms-ch-row-item');
        var panel = row.nextElementSibling;
        if (!panel || !panel.classList.contains('mcms-ch-urls-panel')) return;
        var isOpen = panel.style.display !== 'none';
        panel.style.display = isOpen ? 'none' : 'block';
        btn.classList.toggle('active', !isOpen);
    }

    function mcmsEditUrls(editBtn) {
        var panel = editBtn.closest('.mcms-ch-urls-panel');
        var ta = panel.querySelector('.mcms-ch-urls-textarea');
        if (ta.readOnly) {
            ta.readOnly = false;
            ta.style.background = '#fff';
            ta.style.borderColor = '#2170b0';
            ta.focus();
            editBtn.textContent = 'Save URLs';
        } else {
            ta.readOnly = true;
            ta.style.background = '';
            ta.style.borderColor = '';
            editBtn.textContent = 'Edit URLs';
            // Save back to hidden JSON input
            var row = panel.previousElementSibling;
            if (!row) return;
            var jsonInput = row.querySelector('.mcms-ch-images-json');
            if (!jsonInput) return;
            var lines = ta.value.split('\n').filter(function(l) { return l.trim() !== ''; });
            var images = lines.map(function(u) { return { url: u.trim() }; });
            jsonInput.value = JSON.stringify(images);
            // Update badge
            var badge = row.querySelector('.mcms-ch-img-badge');
            var headerSpan = panel.querySelector('.mcms-ch-urls-header span:nth-child(2)');
            if (badge) badge.textContent = images.length;
            if (headerSpan) headerSpan.textContent = 'Image URLs (' + images.length + ')';
        }
    }
    </script>
    <?php
}

/* ================================================================
 * CHAPTERS PAGE
 * ============================================================= */

function flavor_render_manhwa_chapters_page() {
    $manhwa_id = isset($_GET['manhwa_id']) ? intval($_GET['manhwa_id']) : 0;
    $chapter_idx = isset($_GET['chapter_idx']) ? intval($_GET['chapter_idx']) : -1;
    $base_url = admin_url('admin.php?page=manhwa-chapters');

    // Route to chapter images editor
    if ($manhwa_id && $chapter_idx >= 0) {
        flavor_render_chapter_images_page($manhwa_id, $chapter_idx);
        return;
    }

    ?>
    <div class="mcms-wrap">
        <div class="mcms-header">
            <div class="mcms-header-left">
                <h1 class="mcms-page-title">Chapters</h1>
            </div>
        </div>

        <?php if (!$manhwa_id): ?>
            <div class="mcms-chapter-select">
                <p class="mcms-text-secondary" style="margin-bottom:16px">Select a Manhwa to manage its chapters:</p>
                <?php $manhwa_list = get_posts(array('post_type' => 'manhwa', 'posts_per_page' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC')); ?>
                <div class="mcms-manhwa-grid">
                    <?php foreach ($manhwa_list as $m):
                        $chs = get_post_meta($m->ID, '_manhwa_chapters', true);
                        $ch_count = is_array($chs) ? count($chs) : 0;
                    ?>
                    <a href="<?php echo esc_url(add_query_arg('manhwa_id', $m->ID, $base_url)); ?>" class="mcms-manhwa-card">
                        <div class="mcms-manhwa-card-thumb">
                            <?php if (has_post_thumbnail($m->ID)): ?>
                                <img src="<?php echo get_the_post_thumbnail_url($m->ID, 'thumbnail'); ?>" alt="" />
                            <?php else: ?>
                                <span class="material-symbols-outlined">image</span>
                            <?php endif; ?>
                        </div>
                        <div class="mcms-manhwa-card-info">
                            <div class="mcms-manhwa-card-title"><?php echo esc_html($m->post_title); ?></div>
                            <div class="mcms-manhwa-card-count"><?php echo $ch_count; ?> chapters</div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else:
            $manhwa = get_post($manhwa_id);
            if (!$manhwa) { echo '<p>Manhwa not found.</p>'; return; }
            $chapters = get_post_meta($manhwa_id, '_manhwa_chapters', true);
            if (!is_array($chapters)) $chapters = array();
        ?>
            <div style="padding:16px 24px;display:flex;align-items:center;gap:12px;flex-wrap:wrap">
                <a href="<?php echo esc_url($base_url); ?>" class="mcms-btn-filter">← Back</a>
                <strong><?php echo esc_html($manhwa->post_title); ?></strong>
                <span class="mcms-badge mcms-badge-blue"><?php echo count($chapters); ?> total</span>
                <a href="<?php echo esc_url(flavor_mcms_edit_url($manhwa_id)); ?>" class="mcms-btn-add" style="margin-left:auto">Edit Manhwa</a>
            </div>

            <div class="mcms-table-wrap">
                <table class="mcms-table">
                    <thead>
                        <tr>
                            <th class="mcms-th-check">#</th>
                            <th>Number</th>
                            <th>Title</th>
                            <th>Images</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($chapters)): ?>
                            <tr><td colspan="6" class="mcms-empty">No chapters found.</td></tr>
                        <?php else: foreach ($chapters as $i => $ch):
                            $ch_num = $ch['number'] ?? $ch['title'] ?? '';
                            $imgs = isset($ch['images']) ? $ch['images'] : array();
                            $img_count = is_array($imgs) ? count($imgs) : 0;
                            $ch_url = flavor_get_chapter_url($manhwa_id, $ch);
                            $images_url = add_query_arg(array('manhwa_id' => $manhwa_id, 'chapter_idx' => $i), $base_url);
                        ?>
                        <tr class="mcms-row">
                            <td class="mcms-td-check"><?php echo $i + 1; ?></td>
                            <td><span class="mcms-text-bold"><?php echo esc_html($ch_num); ?></span></td>
                            <td><?php echo esc_html($ch['title'] ?? ''); ?></td>
                            <td>
                                <a href="<?php echo esc_url($images_url); ?>" class="mcms-badge <?php echo $img_count > 0 ? 'mcms-badge-green' : 'mcms-badge-gray'; ?>" style="text-decoration:none">
                                    <?php echo $img_count; ?> images
                                </a>
                            </td>
                            <td class="mcms-date-info"><?php echo esc_html($ch['date'] ?? '—'); ?></td>
                            <td>
                                <div style="display:flex;gap:4px">
                                    <a href="<?php echo esc_url($images_url); ?>" class="mcms-btn-sm" style="background:#6366f1">
                                        <span class="material-symbols-outlined" style="font-size:16px">photo_library</span> Images
                                    </a>
                                    <a href="<?php echo esc_url($ch_url); ?>" target="_blank" class="mcms-btn-sm">
                                        <span class="material-symbols-outlined" style="font-size:16px">visibility</span> View
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/* ================================================================
 * CHAPTER IMAGES MANAGER
 * ============================================================= */

function flavor_ajax_save_chapter_images() {
    check_ajax_referer('mcms_chapter_images', 'nonce');
    $manhwa_id   = intval($_POST['manhwa_id'] ?? 0);
    $chapter_idx = intval($_POST['chapter_idx'] ?? -1);
    if (!$manhwa_id || $chapter_idx < 0 || !current_user_can('edit_post', $manhwa_id)) {
        wp_send_json_error('Invalid request');
    }

    $chapters = get_post_meta($manhwa_id, '_manhwa_chapters', true);
    if (!is_array($chapters) || !isset($chapters[$chapter_idx])) {
        wp_send_json_error('Chapter not found');
    }

    $images = json_decode(stripslashes($_POST['images'] ?? '[]'), true);
    if (!is_array($images)) $images = array();

    $clean = array();
    foreach ($images as $img) {
        $url = is_array($img) ? ($img['url'] ?? '') : $img;
        if ($url) $clean[] = array('url' => esc_url_raw($url));
    }

    // Update chapter title/number if provided
    if (isset($_POST['ch_number'])) $chapters[$chapter_idx]['number'] = sanitize_text_field($_POST['ch_number']);
    if (isset($_POST['ch_title']))  $chapters[$chapter_idx]['title']  = sanitize_text_field($_POST['ch_title']);

    $chapters[$chapter_idx]['images'] = $clean;
    update_post_meta($manhwa_id, '_manhwa_chapters', $chapters);

    wp_send_json_success(array('count' => count($clean)));
}

function flavor_render_chapter_images_page($manhwa_id, $chapter_idx) {
    $manhwa = get_post($manhwa_id);
    if (!$manhwa) { echo '<p>Manhwa not found.</p>'; return; }
    $chapters = get_post_meta($manhwa_id, '_manhwa_chapters', true);
    if (!is_array($chapters) || !isset($chapters[$chapter_idx])) { echo '<p>Chapter not found.</p>'; return; }

    $ch = $chapters[$chapter_idx];
    $ch_num = $ch['number'] ?? $ch['title'] ?? '';
    $ch_title = $ch['title'] ?? '';
    $images = isset($ch['images']) && is_array($ch['images']) ? $ch['images'] : array();
    $ch_url = flavor_get_chapter_url($manhwa_id, $ch);
    $back_url = admin_url('admin.php?page=manhwa-chapters&manhwa_id=' . $manhwa_id);
    $nonce = wp_create_nonce('mcms_chapter_images');

    ?>
    <div class="mcms-wrap">
        <!-- Header -->
        <div class="mcms-header">
            <div class="mcms-header-left">
                <a href="<?php echo esc_url($back_url); ?>" class="mcms-back-link">
                    <span class="material-symbols-outlined">arrow_back</span>
                </a>
                <h1 class="mcms-page-title">Chapter Images Manager</h1>
            </div>
            <div class="mcms-header-right">
                <a href="<?php echo esc_url($ch_url); ?>" target="_blank" class="mcms-visit-link">
                    <span class="material-symbols-outlined">visibility</span> Preview
                </a>
                <button type="button" id="mcms-save-images" class="mcms-btn-save">
                    <span class="material-symbols-outlined">save</span> Save Changes
                </button>
            </div>
        </div>

        <!-- Breadcrumbs -->
        <div class="mcms-breadcrumb">
            <a href="<?php echo admin_url('admin.php?page=manhwa-chapters'); ?>">Library</a>
            <span class="material-symbols-outlined" style="font-size:16px;color:#94a3b8">chevron_right</span>
            <a href="<?php echo esc_url($back_url); ?>"><?php echo esc_html($manhwa->post_title); ?></a>
            <span class="material-symbols-outlined" style="font-size:16px;color:#94a3b8">chevron_right</span>
            <span class="mcms-text-bold"><?php echo esc_html($ch_num ?: $ch_title); ?> Editor</span>
        </div>

        <!-- Sub Header -->
        <div class="mcms-img-subheader">
            <div>
                <h2 class="mcms-img-subtitle">Chapter Images Manager</h2>
                <p class="mcms-text-secondary">Configure and organize pages for <span style="color:#2170b0;font-weight:600"><?php echo esc_html($manhwa->post_title); ?> #<?php echo esc_html($ch_num); ?></span></p>
            </div>
        </div>

        <div class="mcms-notice mcms-notice-success" id="mcms-img-notice" style="display:none">
            <span class="material-symbols-outlined">check_circle</span> <span id="mcms-img-notice-text">Images saved!</span>
        </div>

        <!-- Main Grid -->
        <div class="mcms-img-layout">
            <!-- Left Column -->
            <div class="mcms-img-main">
                <!-- Hidden file input -->
                <input type="file" id="mcms-file-input" multiple accept="image/*" style="display:none" />

                <!-- Dropzone -->
                <div class="mcms-dropzone" id="mcms-dropzone">
                    <div class="mcms-dropzone-icon">
                        <span class="material-symbols-outlined">cloud_upload</span>
                    </div>
                    <h3>Drop images here or Select Files</h3>
                    <p>Supports JPG, PNG, WEBP. For best reading experience, maintain a width of 720px.</p>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center">
                        <button type="button" id="mcms-browse-files" class="mcms-btn-save" style="padding:8px 28px">
                            <span class="material-symbols-outlined" style="font-size:18px">folder_open</span> Browse Files
                        </button>
                        <button type="button" id="mcms-browse-media" class="mcms-btn-save" style="padding:8px 28px;background:#6366f1">
                            <span class="material-symbols-outlined" style="font-size:18px">perm_media</span> Media Library
                        </button>
                    </div>
                </div>

                <!-- Controls Bar -->
                <div class="mcms-img-controls">
                    <div class="mcms-img-controls-left">
                        <span class="mcms-text-bold" id="mcms-page-count"><?php echo count($images); ?> Pages Uploaded</span>
                        <div style="height:16px;width:1px;background:#e2e8f0"></div>
                        <button type="button" class="mcms-link-btn" id="mcms-auto-sort">
                            <span class="material-symbols-outlined" style="font-size:18px">sort</span> Auto-sort by name
                        </button>
                    </div>
                    <div style="display:flex;gap:4px">
                        <button type="button" class="mcms-icon-btn mcms-icon-btn-danger" id="mcms-delete-all" title="Delete All">
                            <span class="material-symbols-outlined">delete_sweep</span>
                        </button>
                    </div>
                </div>

                <!-- Images Grid -->
                <div class="mcms-img-grid" id="mcms-img-grid">
                    <?php foreach ($images as $idx => $img):
                        $url = is_array($img) ? ($img['url'] ?? '') : $img;
                        if (!$url) continue;
                    ?>
                    <div class="mcms-img-card" data-url="<?php echo esc_attr($url); ?>">
                        <div class="mcms-img-card-thumb">
                            <img src="<?php echo esc_url($url); ?>" alt="Page <?php echo $idx + 1; ?>" loading="lazy" />
                            <div class="mcms-img-card-overlay">
                                <button type="button" class="mcms-img-overlay-btn" onclick="window.open(this.closest('.mcms-img-card').dataset.url,'_blank')">
                                    <span class="material-symbols-outlined">open_in_full</span>
                                </button>
                                <button type="button" class="mcms-img-overlay-btn mcms-img-overlay-btn-del" onclick="this.closest('.mcms-img-card').remove();mcmsUpdatePageCount()">
                                    <span class="material-symbols-outlined">delete</span>
                                </button>
                            </div>
                        </div>
                        <div class="mcms-img-card-footer">
                            <span class="mcms-img-card-label">Page <?php echo $idx + 1; ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (empty($images)): ?>
                <div class="mcms-empty" id="mcms-empty-state">No images yet. Upload or paste URLs to add pages.</div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="mcms-img-sidebar">
                <!-- Bulk Paste URLs -->
                <div class="mcms-card">
                    <div class="mcms-card-header">
                        <span class="material-symbols-outlined">link</span> Bulk Paste URLs
                        <button type="button" id="mcms-export-urls" class="mcms-link-btn" style="margin-left:auto;font-size:11px" title="Copy current image URLs to textarea">
                            <span class="material-symbols-outlined" style="font-size:16px">content_copy</span> Export
                        </button>
                    </div>
                    <div style="padding:16px 20px">
                        <p class="mcms-text-secondary" style="font-size:12px;margin-bottom:12px;line-height:1.6">Paste image URLs in any format: one per line, JSON array, HTML img tags, or mixed text. URLs will be auto-detected.</p>
                        <textarea id="mcms-bulk-urls" class="mcms-bulk-textarea" rows="8" placeholder="Supports multiple formats:&#10;&#10;https://cdn.example.com/page1.jpg&#10;https://cdn.example.com/page2.jpg&#10;&#10;or JSON: [{&quot;url&quot;:&quot;...&quot;}]&#10;or HTML: <img src=&quot;...&quot;>"></textarea>
                        <div id="mcms-detect-info" style="display:none;padding:6px 0;font-size:12px;color:#2170b0;font-weight:600"></div>
                        <button type="button" id="mcms-import-urls" class="mcms-btn-import">
                            <span class="material-symbols-outlined" style="font-size:18px">download_for_offline</span> Import Detected Images
                        </button>
                    </div>
                </div>

                <!-- Chapter Info -->
                <div class="mcms-card">
                    <div class="mcms-card-header">
                        <span class="material-symbols-outlined">info</span> Chapter Info
                    </div>
                    <div style="padding:16px 20px">
                        <div class="mcms-field" style="padding:0;margin-bottom:16px">
                            <label>SERIES NAME</label>
                            <input type="text" value="<?php echo esc_attr($manhwa->post_title); ?>" disabled style="background:#f8fafc;color:#94a3b8" />
                        </div>
                        <div class="mcms-field" style="padding:0;margin-bottom:16px">
                            <label>CHAPTER NUMBER</label>
                            <input type="text" id="mcms-ch-number" value="<?php echo esc_attr($ch['number'] ?? ''); ?>" placeholder="e.g. 142" />
                        </div>
                        <div class="mcms-field" style="padding:0">
                            <label>CHAPTER TITLE (OPTIONAL)</label>
                            <input type="text" id="mcms-ch-title" value="<?php echo esc_attr($ch_title); ?>" placeholder="e.g. The Awakening" />
                        </div>
                    </div>
                </div>

                <!-- Tip -->
                <div class="mcms-tip-box">
                    <span class="material-symbols-outlined" style="color:#2170b0;flex-shrink:0">info</span>
                    <p><strong>Tip:</strong> You can drag and drop images to reorder them manually. Click the image count badge in the chapters list to access this editor.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
    jQuery(function($) {
        var grid = document.getElementById('mcms-img-grid');
        var nonce = '<?php echo $nonce; ?>';
        var manhwaId = <?php echo $manhwa_id; ?>;
        var chapterIdx = <?php echo $chapter_idx; ?>;

        // Sortable
        if (typeof Sortable !== 'undefined' && grid) {
            Sortable.create(grid, {
                animation: 200,
                ghostClass: 'mcms-img-card-ghost',
                onEnd: function() { mcmsUpdatePageCount(); }
            });
        }

        var fileInput = document.getElementById('mcms-file-input');
        var uploadNonce = '<?php echo wp_create_nonce('media-form'); ?>';
        var uploadingCount = 0;

        // Browse Files button → open device file picker
        $('#mcms-browse-files').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            fileInput.click();
        });

        // Clicking dropzone area also opens file picker
        $('#mcms-dropzone').on('click', function(e) {
            if ($(e.target).closest('button').length) return; // don't trigger on buttons
            fileInput.click();
        });

        // Handle file input change
        fileInput.addEventListener('change', function() {
            var files = this.files;
            if (!files.length) return;
            for (var i = 0; i < files.length; i++) {
                if (!files[i].type.startsWith('image/')) continue;
                uploadFile(files[i]);
            }
            this.value = ''; // reset so same files can be selected again
        });

        // Browse Media Library button → WP media modal
        var mu;
        $('#mcms-browse-media').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (mu) { mu.open(); return; }
            mu = wp.media({ title: 'Select Chapter Images', button: { text: 'Add Images' }, multiple: true });
            mu.on('select', function() {
                var items = mu.state().get('selection').toJSON();
                items.forEach(function(a) { addImageCard(a.url); });
                mcmsUpdatePageCount();
            });
            mu.open();
        });

        // Smart URL detection from pasted text
        function extractImageUrls(text) {
            var urls = [];
            var seen = {};

            // 1) Try JSON parse first
            try {
                var json = JSON.parse(text.trim());
                if (Array.isArray(json)) {
                    json.forEach(function(item) {
                        var u = (typeof item === 'string') ? item : (item.url || item.src || item.image || '');
                        if (u && !seen[u]) { urls.push(u); seen[u] = true; }
                    });
                    if (urls.length) return urls;
                }
            } catch(e) {}

            // 2) Extract from HTML img/source tags
            var imgRegex = /<img[^>]+(?:src|data-src)=["']([^"']+)["']/gi;
            var m;
            while ((m = imgRegex.exec(text)) !== null) {
                if (!seen[m[1]]) { urls.push(m[1]); seen[m[1]] = true; }
            }
            if (urls.length) return urls;

            // 3) Extract all URLs from text (http/https)
            var urlRegex = /https?:\/\/[^\s"'<>,;\)\]]+/gi;
            while ((m = urlRegex.exec(text)) !== null) {
                var u = m[0].replace(/[\.,;:\)]+$/, ''); // trim trailing punctuation
                if (!seen[u]) { urls.push(u); seen[u] = true; }
            }

            return urls;
        }

        // Live detection count on textarea input
        $('#mcms-bulk-urls').on('input', function() {
            var text = $(this).val().trim();
            var info = $('#mcms-detect-info');
            if (!text) { info.hide(); return; }
            var detected = extractImageUrls(text);
            if (detected.length > 0) {
                info.html('<span class="material-symbols-outlined" style="font-size:14px;vertical-align:middle">check_circle</span> ' + detected.length + ' image URL(s) detected').show();
            } else {
                info.html('<span class="material-symbols-outlined" style="font-size:14px;vertical-align:middle">warning</span> No valid URLs detected').css('color','#f59e0b').show();
            }
        });

        // Import URLs (smart detection)
        $('#mcms-import-urls').on('click', function() {
            var text = $('#mcms-bulk-urls').val().trim();
            if (!text) return;
            var detected = extractImageUrls(text);
            if (!detected.length) {
                alert('No image URLs detected in the pasted text.');
                return;
            }
            detected.forEach(function(u) { addImageCard(u); });
            $('#mcms-bulk-urls').val('');
            $('#mcms-detect-info').hide();
            mcmsUpdatePageCount();
            // Show success
            $('#mcms-img-notice-text').text('Imported ' + detected.length + ' images from URLs.');
            $('#mcms-img-notice').slideDown(200);
            setTimeout(function() { $('#mcms-img-notice').slideUp(200); }, 3000);
        });

        // Export current URLs
        $('#mcms-export-urls').on('click', function() {
            var cards = grid.querySelectorAll('.mcms-img-card');
            var urls = [];
            cards.forEach(function(c) { urls.push(c.dataset.url); });
            if (!urls.length) { alert('No images to export.'); return; }
            $('#mcms-bulk-urls').val(urls.join('\n')).trigger('input');
        });

        // Delete all
        $('#mcms-delete-all').on('click', function() {
            if (!confirm('Delete all images?')) return;
            $('#mcms-img-grid').html('');
            mcmsUpdatePageCount();
        });

        // Auto-sort (alphabetical by URL filename)
        $('#mcms-auto-sort').on('click', function() {
            var cards = Array.from(grid.children);
            cards.sort(function(a, b) {
                var ua = (a.dataset.url || '').split('/').pop();
                var ub = (b.dataset.url || '').split('/').pop();
                return ua.localeCompare(ub, undefined, {numeric: true});
            });
            cards.forEach(function(c) { grid.appendChild(c); });
            mcmsUpdatePageCount();
        });

        // Drag & drop on dropzone
        var dz = document.getElementById('mcms-dropzone');
        ['dragenter','dragover'].forEach(function(e) {
            dz.addEventListener(e, function(ev) { ev.preventDefault(); dz.classList.add('mcms-dropzone-active'); });
        });
        ['dragleave','drop'].forEach(function(e) {
            dz.addEventListener(e, function(ev) { ev.preventDefault(); dz.classList.remove('mcms-dropzone-active'); });
        });
        dz.addEventListener('drop', function(ev) {
            var files = ev.dataTransfer.files;
            for (var i = 0; i < files.length; i++) {
                if (!files[i].type.startsWith('image/')) continue;
                uploadFile(files[i]);
            }
        });

        // Upload file to WordPress via async-upload.php
        function uploadFile(file) {
            uploadingCount++;
            updateUploadStatus();

            var fd = new FormData();
            fd.append('async-upload', file);
            fd.append('name', file.name);
            fd.append('action', 'upload-attachment');
            fd.append('_ajax_nonce', uploadNonce);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                success: function(res) {
                    uploadingCount--;
                    if (res.success && res.data && res.data.url) {
                        addImageCard(res.data.url);
                        mcmsUpdatePageCount();
                    } else {
                        var msg = (res.data && res.data.message) ? res.data.message : 'Upload failed: ' + file.name;
                        console.error(msg);
                        $('#mcms-img-notice-text').text(msg);
                        $('#mcms-img-notice').removeClass('mcms-notice-success').addClass('mcms-notice-error').slideDown(200);
                        setTimeout(function() { $('#mcms-img-notice').slideUp(200).removeClass('mcms-notice-error').addClass('mcms-notice-success'); }, 4000);
                    }
                    updateUploadStatus();
                },
                error: function() {
                    uploadingCount--;
                    console.error('Upload failed: ' + file.name);
                    updateUploadStatus();
                }
            });
        }

        function updateUploadStatus() {
            if (uploadingCount > 0) {
                $('#mcms-page-count').html('<span class="material-symbols-outlined" style="font-size:16px;animation:spin 1s linear infinite">progress_activity</span> Uploading ' + uploadingCount + ' file(s)...');
            } else {
                mcmsUpdatePageCount();
            }
        }

        // Save
        $('#mcms-save-images').on('click', function() {
            var btn = $(this);
            btn.prop('disabled', true).html('<span class="material-symbols-outlined">hourglass_empty</span> Saving...');
            var cards = grid.querySelectorAll('.mcms-img-card');
            var imgs = [];
            cards.forEach(function(c) { imgs.push({url: c.dataset.url}); });
            $.post(ajaxurl, {
                action: 'mcms_save_chapter_images',
                nonce: nonce,
                manhwa_id: manhwaId,
                chapter_idx: chapterIdx,
                images: JSON.stringify(imgs),
                ch_number: $('#mcms-ch-number').val(),
                ch_title: $('#mcms-ch-title').val()
            }, function(res) {
                btn.prop('disabled', false).html('<span class="material-symbols-outlined">save</span> Save Changes');
                if (res.success) {
                    $('#mcms-img-notice-text').text('Saved! ' + res.data.count + ' images.');
                    $('#mcms-img-notice').slideDown(200);
                    setTimeout(function() { $('#mcms-img-notice').slideUp(200); }, 3000);
                } else {
                    alert('Error: ' + (res.data || 'Save failed'));
                }
            });
        });

        function addImageCard(url) {
            var empty = document.getElementById('mcms-empty-state');
            if (empty) empty.remove();
            var count = grid.querySelectorAll('.mcms-img-card').length + 1;
            var html = '<div class="mcms-img-card" data-url="' + url + '">' +
                '<div class="mcms-img-card-thumb">' +
                    '<img src="' + url + '" alt="Page ' + count + '" loading="lazy" />' +
                    '<div class="mcms-img-card-overlay">' +
                        '<button type="button" class="mcms-img-overlay-btn" onclick="window.open(this.closest(\'.mcms-img-card\').dataset.url,\'_blank\')">' +
                            '<span class="material-symbols-outlined">open_in_full</span>' +
                        '</button>' +
                        '<button type="button" class="mcms-img-overlay-btn mcms-img-overlay-btn-del" onclick="this.closest(\'.mcms-img-card\').remove();mcmsUpdatePageCount()">' +
                            '<span class="material-symbols-outlined">delete</span>' +
                        '</button>' +
                    '</div>' +
                '</div>' +
                '<div class="mcms-img-card-footer">' +
                    '<span class="mcms-img-card-label">Page ' + count + '</span>' +
                '</div>' +
            '</div>';
            grid.insertAdjacentHTML('beforeend', html);
        }
    });

    function mcmsUpdatePageCount() {
        var c = document.querySelectorAll('#mcms-img-grid .mcms-img-card').length;
        var el = document.getElementById('mcms-page-count');
        if (el) el.textContent = c + ' Pages Uploaded';
        // Renumber labels
        var cards = document.querySelectorAll('#mcms-img-grid .mcms-img-card');
        cards.forEach(function(card, i) {
            var lbl = card.querySelector('.mcms-img-card-label');
            if (lbl) lbl.textContent = 'Page ' + (i + 1);
        });
    }
    </script>
    <?php
}

/* ================================================================
 * STATISTICS PAGE
 * ============================================================= */

function flavor_render_manhwa_stats_page() {
    // ── Gather all manhwa data ──
    $all_manhwa = get_posts([
        'post_type'      => 'manhwa',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ]);

    $total_manhwa   = count($all_manhwa);
    $total_views    = 0;
    $total_chapters = 0;
    $total_images   = 0;
    $total_rating   = 0;
    $rated_count    = 0;

    $status_counts = ['ongoing' => 0, 'completed' => 0, 'hiatus' => 0, 'other' => 0];
    $type_counts   = [];
    $genre_counts  = [];
    $views_data    = []; // for top views
    $chapters_data = []; // for top chapters

    foreach ($all_manhwa as $m) {
        $pid    = $m->ID;
        $views  = intval(get_post_meta($pid, '_manhwa_views', true));
        $rating = floatval(get_post_meta($pid, '_manhwa_rating', true));
        $status = strtolower(get_post_meta($pid, '_manhwa_status', true) ?: '');
        $type   = get_post_meta($pid, '_manhwa_type', true) ?: 'Unknown';
        $chs    = get_post_meta($pid, '_manhwa_chapters', true);
        $ch_count  = is_array($chs) ? count($chs) : 0;
        $img_count = 0;
        if (is_array($chs)) {
            foreach ($chs as $ch) {
                if (!empty($ch['images']) && is_array($ch['images'])) {
                    $img_count += count($ch['images']);
                }
            }
        }

        $total_views    += $views;
        $total_chapters += $ch_count;
        $total_images   += $img_count;
        if ($rating > 0) { $total_rating += $rating; $rated_count++; }

        // Status
        if (isset($status_counts[$status])) {
            $status_counts[$status]++;
        } else {
            $status_counts['other']++;
        }

        // Type
        if (!isset($type_counts[$type])) $type_counts[$type] = 0;
        $type_counts[$type]++;

        // Genres
        $genres = wp_get_post_terms($pid, 'manhwa_genre', ['fields' => 'names']);
        if (!is_wp_error($genres)) {
            foreach ($genres as $g) {
                if (!isset($genre_counts[$g])) $genre_counts[$g] = 0;
                $genre_counts[$g]++;
            }
        }

        // Collect for rankings
        $thumb = get_the_post_thumbnail_url($pid, 'thumbnail');
        $views_data[] = [
            'id'       => $pid,
            'title'    => $m->post_title,
            'views'    => $views,
            'rating'   => $rating,
            'chapters' => $ch_count,
            'thumb'    => $thumb,
            'status'   => $status,
        ];
        $chapters_data[] = [
            'id'       => $pid,
            'title'    => $m->post_title,
            'views'    => $views,
            'chapters' => $ch_count,
            'images'   => $img_count,
            'thumb'    => $thumb,
        ];
    }

    // Sort
    usort($views_data, fn($a, $b) => $b['views'] - $a['views']);
    usort($chapters_data, fn($a, $b) => $b['chapters'] - $a['chapters']);
    arsort($type_counts);
    arsort($genre_counts);

    $avg_views  = $total_manhwa > 0 ? round($total_views / $total_manhwa) : 0;
    $avg_rating = $rated_count > 0 ? round($total_rating / $rated_count, 1) : 0;

    $top_views    = array_slice($views_data, 0, 10);
    $top_chapters = array_slice($chapters_data, 0, 10);
    $top_genres   = array_slice($genre_counts, 0, 10, true);

    // ── Top Reacted Manhwa ──
    global $wpdb;
    $reactions_table = $wpdb->prefix . 'flavor_reactions';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$reactions_table'") === $reactions_table;
    $top_reactions = [];
    $total_reactions = 0;
    if ($table_exists) {
        $total_reactions = intval($wpdb->get_var("SELECT COUNT(*) FROM $reactions_table"));
        $reaction_rows = $wpdb->get_results("
            SELECT r.post_id,
                   COUNT(*) as total,
                   SUM(CASE WHEN r.reaction_type='like' THEN 1 ELSE 0 END) as cnt_like,
                   SUM(CASE WHEN r.reaction_type='funny' THEN 1 ELSE 0 END) as cnt_funny,
                   SUM(CASE WHEN r.reaction_type='nice' THEN 1 ELSE 0 END) as cnt_nice,
                   SUM(CASE WHEN r.reaction_type='sad' THEN 1 ELSE 0 END) as cnt_sad,
                   SUM(CASE WHEN r.reaction_type='angry' THEN 1 ELSE 0 END) as cnt_angry,
                   p.post_title
            FROM $reactions_table r
            LEFT JOIN {$wpdb->posts} p ON p.ID = r.post_id
            WHERE p.post_status = 'publish'
            GROUP BY r.post_id
            ORDER BY total DESC
            LIMIT 10
        ");
        if ($reaction_rows) {
            foreach ($reaction_rows as $rr) {
                $top_reactions[] = [
                    'id'     => $rr->post_id,
                    'title'  => $rr->post_title,
                    'total'  => intval($rr->total),
                    'like'   => intval($rr->cnt_like),
                    'funny'  => intval($rr->cnt_funny),
                    'nice'   => intval($rr->cnt_nice),
                    'sad'    => intval($rr->cnt_sad),
                    'angry'  => intval($rr->cnt_angry),
                    'thumb'  => get_the_post_thumbnail_url($rr->post_id, 'thumbnail'),
                ];
            }
        }
    }

    ?>
    <div class="mcms-wrap">
        <div class="mcms-header">
            <div class="mcms-header-left">
                <h1 class="mcms-page-title">
                    <span class="material-symbols-outlined" style="font-size:28px;vertical-align:middle;margin-right:4px">bar_chart</span>
                    Statistics
                </h1>
            </div>
            <div class="mcms-header-right">
                <a href="<?php echo admin_url('admin.php?page=manhwa-cms'); ?>" class="mcms-visit-link">
                    <span class="material-symbols-outlined">arrow_back</span> All Manhwa
                </a>
            </div>
        </div>

        <!-- ═══════════ QUICK SUMMARY CARDS ═══════════ -->
        <div class="mcms-stats-summary">
            <?php
            $cards = [
                ['icon' => 'visibility',       'label' => 'Total Views',    'value' => flavor_format_number($total_views),    'color' => '#6366f1', 'bg' => '#eef2ff'],
                ['icon' => 'menu_book',        'label' => 'Total Manhwa',   'value' => number_format($total_manhwa),          'color' => '#2170b0', 'bg' => '#eff6ff'],
                ['icon' => 'auto_stories',     'label' => 'Total Chapters', 'value' => number_format($total_chapters),        'color' => '#059669', 'bg' => '#ecfdf5'],
                ['icon' => 'photo_library',    'label' => 'Total Images',   'value' => flavor_format_number($total_images),   'color' => '#d97706', 'bg' => '#fffbeb'],
                ['icon' => 'trending_up',      'label' => 'Avg Views',      'value' => flavor_format_number($avg_views),      'color' => '#8b5cf6', 'bg' => '#f5f3ff'],
                ['icon' => 'star',             'label' => 'Avg Rating',     'value' => $avg_rating . '/10',                   'color' => '#f59e0b', 'bg' => '#fffbeb'],
            ];
            foreach ($cards as $c): ?>
            <div style="background:<?php echo $c['bg']; ?>;border-radius:14px;padding:20px 22px;position:relative;overflow:hidden">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
                    <div style="width:38px;height:38px;border-radius:10px;background:<?php echo $c['color']; ?>;display:flex;align-items:center;justify-content:center">
                        <span class="material-symbols-outlined" style="color:#fff;font-size:20px"><?php echo $c['icon']; ?></span>
                    </div>
                    <span style="font-size:12px;font-weight:600;color:<?php echo $c['color']; ?>;text-transform:uppercase;letter-spacing:.5px"><?php echo $c['label']; ?></span>
                </div>
                <div style="font-size:28px;font-weight:800;color:#0f172a;line-height:1"><?php echo $c['value']; ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ═══════════ MAIN GRID ═══════════ -->
        <div class="mcms-stats-grid">

            <!-- TOP 10 MOST VIEWED -->
            <div class="mcms-card" style="grid-column:span 1">
                <div class="mcms-card-header" style="justify-content:space-between">
                    <div style="display:flex;align-items:center;gap:8px">
                        <span class="material-symbols-outlined" style="color:#6366f1">trending_up</span>
                        Top 10 Most Viewed
                    </div>
                    <span class="mcms-badge mcms-badge-blue" style="font-size:10px">By Views</span>
                </div>
                <div style="padding:0">
                    <table class="mcms-table" style="margin:0;border:none;box-shadow:none">
                        <thead>
                            <tr>
                                <th style="width:30px;padding-left:20px">#</th>
                                <th style="width:36px"></th>
                                <th>Title</th>
                                <th style="width:80px;text-align:right">Views</th>
                                <th style="width:60px;text-align:right;padding-right:20px">Rating</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($top_views as $i => $m):
                            $edit_url = flavor_mcms_edit_url($m['id']);
                            $bar_pct = $top_views[0]['views'] > 0 ? round(($m['views'] / $top_views[0]['views']) * 100) : 0;
                        ?>
                            <tr class="mcms-row" style="position:relative">
                                <td style="padding-left:20px;font-weight:800;color:<?php echo $i < 3 ? '#6366f1' : '#94a3b8'; ?>;font-size:14px"><?php echo $i + 1; ?></td>
                                <td>
                                    <div style="width:32px;height:42px;border-radius:6px;overflow:hidden;background:#f1f5f9;flex-shrink:0">
                                        <?php if ($m['thumb']): ?>
                                            <img src="<?php echo esc_url($m['thumb']); ?>" style="width:100%;height:100%;object-fit:cover" />
                                        <?php else: ?>
                                            <span class="material-symbols-outlined" style="font-size:20px;color:#94a3b8;display:flex;align-items:center;justify-content:center;height:100%">image</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url($edit_url); ?>" style="color:#0f172a;font-weight:600;text-decoration:none;font-size:13px"><?php echo esc_html($m['title']); ?></a>
                                    <div style="margin-top:4px;height:4px;border-radius:2px;background:#f1f5f9;width:100%">
                                        <div style="height:100%;border-radius:2px;background:linear-gradient(90deg,#6366f1,#a78bfa);width:<?php echo $bar_pct; ?>%"></div>
                                    </div>
                                </td>
                                <td style="text-align:right;font-weight:700;color:#334155"><?php echo flavor_format_number($m['views']); ?></td>
                                <td style="text-align:right;padding-right:20px">
                                    <span style="color:#f59e0b;font-weight:600;font-size:12px">★ <?php echo number_format($m['rating'], 1); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($top_views)): ?>
                            <tr><td colspan="5" class="mcms-empty">No data available.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TOP 10 BY CHAPTERS -->
            <div class="mcms-card" style="grid-column:span 1">
                <div class="mcms-card-header" style="justify-content:space-between">
                    <div style="display:flex;align-items:center;gap:8px">
                        <span class="material-symbols-outlined" style="color:#059669">auto_stories</span>
                        Top 10 By Chapter Count
                    </div>
                    <span class="mcms-badge mcms-badge-green" style="font-size:10px">Most Updated</span>
                </div>
                <div style="padding:0">
                    <table class="mcms-table" style="margin:0;border:none;box-shadow:none">
                        <thead>
                            <tr>
                                <th style="width:30px;padding-left:20px">#</th>
                                <th style="width:36px"></th>
                                <th>Title</th>
                                <th style="width:60px;text-align:right">Ch.</th>
                                <th style="width:70px;text-align:right;padding-right:20px">Images</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($top_chapters as $i => $m):
                            $edit_url = flavor_mcms_edit_url($m['id']);
                            $bar_pct = $top_chapters[0]['chapters'] > 0 ? round(($m['chapters'] / $top_chapters[0]['chapters']) * 100) : 0;
                        ?>
                            <tr class="mcms-row">
                                <td style="padding-left:20px;font-weight:800;color:<?php echo $i < 3 ? '#059669' : '#94a3b8'; ?>;font-size:14px"><?php echo $i + 1; ?></td>
                                <td>
                                    <div style="width:32px;height:42px;border-radius:6px;overflow:hidden;background:#f1f5f9;flex-shrink:0">
                                        <?php if ($m['thumb']): ?>
                                            <img src="<?php echo esc_url($m['thumb']); ?>" style="width:100%;height:100%;object-fit:cover" />
                                        <?php else: ?>
                                            <span class="material-symbols-outlined" style="font-size:20px;color:#94a3b8;display:flex;align-items:center;justify-content:center;height:100%">image</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url($edit_url); ?>" style="color:#0f172a;font-weight:600;text-decoration:none;font-size:13px"><?php echo esc_html($m['title']); ?></a>
                                    <div style="margin-top:4px;height:4px;border-radius:2px;background:#f1f5f9;width:100%">
                                        <div style="height:100%;border-radius:2px;background:linear-gradient(90deg,#059669,#34d399);width:<?php echo $bar_pct; ?>%"></div>
                                    </div>
                                </td>
                                <td style="text-align:right;font-weight:700;color:#334155"><?php echo number_format($m['chapters']); ?></td>
                                <td style="text-align:right;padding-right:20px;font-size:12px;color:#64748b"><?php echo flavor_format_number($m['images']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($top_chapters)): ?>
                            <tr><td colspan="5" class="mcms-empty">No data available.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- STATUS DISTRIBUTION -->
            <div class="mcms-card">
                <div class="mcms-card-header">
                    <span class="material-symbols-outlined" style="color:#2170b0">donut_large</span>
                    Status Distribution
                </div>
                <div style="padding:20px">
                    <?php
                    $status_colors = [
                        'ongoing'   => ['#059669', '#ecfdf5', 'Ongoing'],
                        'completed' => ['#2170b0', '#eff6ff', 'Completed'],
                        'hiatus'    => ['#d97706', '#fffbeb', 'Hiatus'],
                        'other'     => ['#64748b', '#f1f5f9', 'Other'],
                    ];
                    foreach ($status_colors as $key => $info):
                        $count = $status_counts[$key] ?? 0;
                        $pct = $total_manhwa > 0 ? round(($count / $total_manhwa) * 100, 1) : 0;
                        if ($count === 0) continue;
                    ?>
                    <div style="margin-bottom:16px">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                            <div style="display:flex;align-items:center;gap:8px">
                                <div style="width:10px;height:10px;border-radius:3px;background:<?php echo $info[0]; ?>"></div>
                                <span style="font-weight:600;font-size:13px;color:#334155"><?php echo $info[2]; ?></span>
                            </div>
                            <span style="font-size:13px;color:#64748b"><strong style="color:#0f172a"><?php echo $count; ?></strong> (<?php echo $pct; ?>%)</span>
                        </div>
                        <div style="height:10px;border-radius:5px;background:<?php echo $info[1]; ?>;overflow:hidden">
                            <div style="height:100%;border-radius:5px;background:<?php echo $info[0]; ?>;width:<?php echo $pct; ?>%;transition:width .5s"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- TYPE DISTRIBUTION -->
            <div class="mcms-card">
                <div class="mcms-card-header">
                    <span class="material-symbols-outlined" style="color:#8b5cf6">category</span>
                    Type Distribution
                </div>
                <div style="padding:20px">
                    <?php
                    $type_colors = ['#6366f1','#2170b0','#059669','#d97706','#ef4444','#8b5cf6','#ec4899'];
                    $ti = 0;
                    foreach ($type_counts as $type => $count):
                        $pct = $total_manhwa > 0 ? round(($count / $total_manhwa) * 100, 1) : 0;
                        $color = $type_colors[$ti % count($type_colors)];
                        $ti++;
                    ?>
                    <div style="margin-bottom:16px">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                            <div style="display:flex;align-items:center;gap:8px">
                                <div style="width:10px;height:10px;border-radius:3px;background:<?php echo $color; ?>"></div>
                                <span style="font-weight:600;font-size:13px;color:#334155"><?php echo esc_html($type); ?></span>
                            </div>
                            <span style="font-size:13px;color:#64748b"><strong style="color:#0f172a"><?php echo $count; ?></strong> (<?php echo $pct; ?>%)</span>
                        </div>
                        <div style="height:10px;border-radius:5px;background:#f8fafc;overflow:hidden">
                            <div style="height:100%;border-radius:5px;background:<?php echo $color; ?>;width:<?php echo $pct; ?>%;transition:width .5s"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($type_counts)): ?>
                        <p style="color:#94a3b8;text-align:center">No data available.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- TOP 10 MOST REACTED -->
            <div class="mcms-card mcms-stats-full-width">
                <div class="mcms-card-header" style="justify-content:space-between">
                    <div style="display:flex;align-items:center;gap:8px">
                        <span class="material-symbols-outlined" style="color:#f43f5e">favorite</span>
                        Top 10 Most Reacted
                    </div>
                    <span class="mcms-badge mcms-badge-gray" style="font-size:10px"><?php echo number_format($total_reactions); ?> reactions total</span>
                </div>
                <div style="padding:0">
                    <table class="mcms-table" style="margin:0;border:none;box-shadow:none">
                        <thead>
                            <tr>
                                <th style="width:30px;padding-left:20px">#</th>
                                <th style="width:36px"></th>
                                <th>Title</th>
                                <th style="width:200px">Breakdown</th>
                                <th style="width:70px;text-align:right;padding-right:20px">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($top_reactions)):
                            $max_react = $top_reactions[0]['total'];
                            foreach ($top_reactions as $i => $rx):
                                $edit_url = flavor_mcms_edit_url($rx['id']);
                                $bar_pct = $max_react > 0 ? round(($rx['total'] / $max_react) * 100) : 0;
                        ?>
                            <tr class="mcms-row">
                                <td style="padding-left:20px;font-weight:800;color:<?php echo $i < 3 ? '#f43f5e' : '#94a3b8'; ?>;font-size:14px"><?php echo $i + 1; ?></td>
                                <td>
                                    <div style="width:32px;height:42px;border-radius:6px;overflow:hidden;background:#f1f5f9;flex-shrink:0">
                                        <?php if ($rx['thumb']): ?>
                                            <img src="<?php echo esc_url($rx['thumb']); ?>" style="width:100%;height:100%;object-fit:cover" />
                                        <?php else: ?>
                                            <span class="material-symbols-outlined" style="font-size:20px;color:#94a3b8;display:flex;align-items:center;justify-content:center;height:100%">image</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url($edit_url); ?>" style="color:#0f172a;font-weight:600;text-decoration:none;font-size:13px"><?php echo esc_html($rx['title']); ?></a>
                                    <div style="margin-top:4px;height:4px;border-radius:2px;background:#f1f5f9;width:100%">
                                        <div style="height:100%;border-radius:2px;background:linear-gradient(90deg,#f43f5e,#fb7185);width:<?php echo $bar_pct; ?>%"></div>
                                    </div>
                                </td>
                                <td>
                                    <div style="display:flex;gap:6px;flex-wrap:wrap">
                                        <?php if ($rx['like'] > 0): ?><span style="font-size:11px;background:#eff6ff;color:#2170b0;padding:2px 6px;border-radius:4px;font-weight:600">👍 <?php echo $rx['like']; ?></span><?php endif; ?>
                                        <?php if ($rx['funny'] > 0): ?><span style="font-size:11px;background:#fffbeb;color:#d97706;padding:2px 6px;border-radius:4px;font-weight:600">😂 <?php echo $rx['funny']; ?></span><?php endif; ?>
                                        <?php if ($rx['nice'] > 0): ?><span style="font-size:11px;background:#ecfdf5;color:#059669;padding:2px 6px;border-radius:4px;font-weight:600">😍 <?php echo $rx['nice']; ?></span><?php endif; ?>
                                        <?php if ($rx['sad'] > 0): ?><span style="font-size:11px;background:#f5f3ff;color:#8b5cf6;padding:2px 6px;border-radius:4px;font-weight:600">😢 <?php echo $rx['sad']; ?></span><?php endif; ?>
                                        <?php if ($rx['angry'] > 0): ?><span style="font-size:11px;background:#fef2f2;color:#ef4444;padding:2px 6px;border-radius:4px;font-weight:600">😡 <?php echo $rx['angry']; ?></span><?php endif; ?>
                                    </div>
                                </td>
                                <td style="text-align:right;padding-right:20px;font-weight:700;color:#f43f5e;font-size:14px"><?php echo number_format($rx['total']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="mcms-empty">No reaction data available.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TOP GENRES -->
            <div class="mcms-card mcms-stats-full-width">
                <div class="mcms-card-header" style="justify-content:space-between">
                    <div style="display:flex;align-items:center;gap:8px">
                        <span class="material-symbols-outlined" style="color:#ec4899">label</span>
                        Top Genres
                    </div>
                    <span class="mcms-badge mcms-badge-gray" style="font-size:10px"><?php echo count($genre_counts); ?> genres total</span>
                </div>
                <div style="padding:20px">
                    <?php if (!empty($top_genres)):
                        $max_genre = max($top_genres);
                        $genre_cols = ['#6366f1','#2170b0','#059669','#d97706','#ef4444','#8b5cf6','#ec4899','#14b8a6','#f97316','#64748b'];
                    ?>
                    <div class="mcms-stats-genres-grid">
                        <?php $gi = 0; foreach ($top_genres as $name => $count):
                            $pct = $max_genre > 0 ? round(($count / $max_genre) * 100) : 0;
                            $color = $genre_cols[$gi % count($genre_cols)];
                            $gi++;
                        ?>
                        <div>
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
                                <span style="font-weight:600;font-size:13px;color:#334155"><?php echo esc_html($name); ?></span>
                                <span style="font-size:12px;font-weight:700;color:<?php echo $color; ?>"><?php echo $count; ?></span>
                            </div>
                            <div style="height:8px;border-radius:4px;background:#f1f5f9;overflow:hidden">
                                <div style="height:100%;border-radius:4px;background:<?php echo $color; ?>;width:<?php echo $pct; ?>%;transition:width .5s"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                        <p style="color:#94a3b8;text-align:center">No genre data available.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- end main grid -->
    </div><!-- end mcms-wrap -->

    <style>
    .mcms-stats-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-bottom: 28px;
    }
    .mcms-stats-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
    }
    .mcms-stats-full-width {
        grid-column: span 2;
    }
    .mcms-stats-genres-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px 28px;
    }
    .mcms-stats-grid .mcms-table {
        display: table;
        width: 100%;
    }
    @media (max-width: 1200px) {
        .mcms-stats-grid {
            grid-template-columns: 1fr;
        }
        .mcms-stats-full-width {
            grid-column: span 1;
        }
    }
    @media (max-width: 782px) {
        .mcms-stats-summary {
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        .mcms-stats-summary > div {
            padding: 14px 16px !important;
        }
        .mcms-stats-summary > div > div:last-child {
            font-size: 22px !important;
        }
        .mcms-stats-genres-grid {
            grid-template-columns: 1fr;
        }
        .mcms-stats-grid {
            gap: 16px;
        }
        .mcms-stats-grid .mcms-card {
            overflow-x: auto;
        }
    }
    @media (max-width: 480px) {
        .mcms-stats-summary {
            grid-template-columns: 1fr;
        }
    }
    </style>
    <?php
}
