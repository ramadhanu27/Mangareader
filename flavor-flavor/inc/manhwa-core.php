<?php
/**
 * Manhwa Core - Native CPT, Taxonomy, Meta Boxes & Chapter Router
 * Replaces the external Manhwa Manager plugin
 *
 * @package Flavor_Flavor
 */

if (!defined('ABSPATH')) exit;

// Only register if not already registered by the plugin
add_action('init', 'flavor_register_manhwa_cpt', 5);
add_action('init', 'flavor_register_manhwa_taxonomies', 5);
add_action('init', 'flavor_chapter_rewrite_rules', 5);
add_filter('query_vars', 'flavor_chapter_query_vars');
add_action('template_redirect', 'flavor_handle_chapter_request', 5);
add_action('add_meta_boxes', 'flavor_manhwa_meta_boxes', 99);
add_action('save_post_manhwa', 'flavor_save_manhwa_meta', 10, 2);
add_filter('manage_manhwa_posts_columns', 'flavor_manhwa_admin_columns');
add_action('manage_manhwa_posts_custom_column', 'flavor_manhwa_admin_column_content', 10, 2);
add_action('admin_head', 'flavor_manhwa_admin_column_styles');
add_action('wp_ajax_manhwa_upload_cover_from_url', 'flavor_ajax_upload_cover_from_url');

/* ================================================================
 * 1. CUSTOM POST TYPE
 * ============================================================= */

function flavor_register_manhwa_cpt() {
    if (post_type_exists('manhwa')) return;

    register_post_type('manhwa', array(
        'labels' => array(
            'name'               => 'Manhwa',
            'singular_name'      => 'Manhwa',
            'menu_name'          => 'Manhwa',
            'add_new'            => 'Add New',
            'add_new_item'       => 'Add New Manhwa',
            'edit_item'          => 'Edit Manhwa',
            'new_item'           => 'New Manhwa',
            'view_item'          => 'View Manhwa',
            'search_items'       => 'Search Manhwa',
            'not_found'          => 'No manhwa found',
            'not_found_in_trash' => 'No manhwa found in trash',
            'all_items'          => 'All Manhwa',
        ),
        'public'              => true,
        'has_archive'         => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_rest'        => true,
        'rest_base'           => 'manhwa',
        'menu_icon'           => 'dashicons-book-alt',
        'menu_position'       => 5,
        'supports'            => array('title', 'editor', 'thumbnail', 'excerpt', 'comments', 'custom-fields', 'author', 'revisions'),
        'rewrite'             => array('slug' => 'manhwa', 'with_front' => false, 'feeds' => true, 'pages' => true),
        'capability_type'     => 'post',
        'map_meta_cap'        => true,
        'hierarchical'        => false,
        'query_var'           => true,
        'can_export'          => true,
        'delete_with_user'    => false,
        'taxonomies'          => array('manhwa_genre', 'manhwa_status'),
    ));
}

/* ================================================================
 * 2. TAXONOMIES
 * ============================================================= */

function flavor_register_manhwa_taxonomies() {
    if (taxonomy_exists('manhwa_genre')) return;

    register_taxonomy('manhwa_genre', 'manhwa', array(
        'labels' => array(
            'name'          => 'Genres',
            'singular_name' => 'Genre',
            'menu_name'     => 'Genres',
            'all_items'     => 'All Genres',
            'edit_item'     => 'Edit Genre',
            'add_new_item'  => 'Add New Genre',
            'search_items'  => 'Search Genres',
            'not_found'     => 'No genres found',
        ),
        'public'             => true,
        'hierarchical'       => true,
        'show_ui'            => true,
        'show_in_rest'       => true,
        'rest_base'          => 'manhwa-genres',
        'show_admin_column'  => true,
        'show_in_quick_edit' => true,
        'rewrite'            => array('slug' => 'genre', 'with_front' => false, 'hierarchical' => true),
    ));

    if (taxonomy_exists('manhwa_status')) return;

    register_taxonomy('manhwa_status', 'manhwa', array(
        'labels' => array(
            'name'          => 'Status',
            'singular_name' => 'Status',
            'menu_name'     => 'Status',
            'all_items'     => 'All Status',
            'edit_item'     => 'Edit Status',
            'add_new_item'  => 'Add New Status',
            'search_items'  => 'Search Status',
            'not_found'     => 'No status found',
        ),
        'public'             => true,
        'hierarchical'       => true,
        'show_ui'            => true,
        'show_in_rest'       => true,
        'rest_base'          => 'manhwa-status',
        'show_admin_column'  => true,
        'show_in_quick_edit' => true,
        'rewrite'            => array('slug' => 'status', 'with_front' => false),
    ));
}

/* ================================================================
 * 3. CHAPTER REWRITE RULES
 * ============================================================= */

function flavor_chapter_rewrite_rules() {
    // /manhwa-slug-chapter-XX/
    add_rewrite_rule(
        '([^/]+)-chapter-([0-9]+(?:\.[0-9]+)?)/?$',
        'index.php?chapter_reader=1&manhwa_slug=$matches[1]&chapter_num=$matches[2]',
        'top'
    );
    // /read/manhwa-slug/chapter-XX/
    add_rewrite_rule(
        'read/([^/]+)/chapter-([0-9]+(?:\.[0-9]+)?)/?$',
        'index.php?chapter_reader=1&manhwa_slug=$matches[1]&chapter_num=$matches[2]',
        'top'
    );
    // /chapter/manhwa-slug/chapter-number/
    add_rewrite_rule(
        'chapter/([^/]+)/([^/]+)/?$',
        'index.php?chapter_reader=1&manhwa_slug=$matches[1]&chapter_num=$matches[2]',
        'top'
    );
    add_rewrite_rule('chapter/?$', 'index.php?chapter_reader=1', 'top');
}

function flavor_chapter_query_vars($vars) {
    $vars[] = 'chapter_reader';
    $vars[] = 'manhwa_slug';
    $vars[] = 'chapter_num';
    return $vars;
}

/* ================================================================
 * 4. CHAPTER REQUEST HANDLER
 * ============================================================= */

function flavor_handle_chapter_request() {
    if (!get_query_var('chapter_reader') && !isset($_GET['manhwa'])) return;

    $data = flavor_get_chapter_data();
    if (!$data) {
        status_header(404);
        nocache_headers();
        flavor_chapter_404();
        exit;
    }

    flavor_display_chapter_reader($data);
    exit;
}

function flavor_get_chapter_data() {
    $manhwa_id  = 0;
    $chapter_num = '';

    if (get_query_var('manhwa_slug')) {
        $slug = sanitize_title(get_query_var('manhwa_slug'));
        $posts = get_posts(array('post_type' => 'manhwa', 'name' => $slug, 'posts_per_page' => 1));
        if (!empty($posts)) $manhwa_id = $posts[0]->ID;
        $chapter_num = sanitize_text_field(get_query_var('chapter_num'));
    }
    if (isset($_GET['manhwa'])) $manhwa_id  = intval($_GET['manhwa']);
    if (isset($_GET['chapter'])) $chapter_num = sanitize_text_field($_GET['chapter']);
    if (!$manhwa_id) return null;

    $manhwa = get_post($manhwa_id);
    if (!$manhwa || $manhwa->post_type !== 'manhwa') return null;

    $chapters = get_post_meta($manhwa_id, '_manhwa_chapters', true);
    if (!is_array($chapters) || empty($chapters)) return null;

    $current = null;
    $idx     = -1;
    foreach ($chapters as $i => $ch) {
        if (!empty($chapter_num)) {
            // Match by number field first
            $ch_number = $ch['number'] ?? '';
            $clean_ch = preg_replace('/[^0-9.]/', '', $ch_number);
            if ($clean_ch == $chapter_num) { $current = $ch; $idx = $i; break; }
            // Then match by title
            if (preg_match('/chapter\s*' . preg_quote($chapter_num, '/') . '\b/i', $ch['title'])) { $current = $ch; $idx = $i; break; }
            if (preg_match('/(\d+)/', $ch['title'], $m) && $m[1] == $chapter_num) { $current = $ch; $idx = $i; break; }
        }
    }
    if (!$current && empty($chapter_num)) { $current = $chapters[0]; $idx = 0; }
    if (!$current) return null;

    return array(
        'manhwa_id'      => $manhwa_id,
        'manhwa_title'   => $manhwa->post_title,
        'manhwa_url'     => get_permalink($manhwa_id),
        'chapter'        => $current,
        'chapter_index'  => $idx,
        'images'         => isset($current['images']) ? $current['images'] : array(),
        'prev_chapter'   => ($idx < count($chapters) - 1) ? $chapters[$idx + 1] : null,
        'next_chapter'   => ($idx > 0) ? $chapters[$idx - 1] : null,
        'total_chapters' => count($chapters),
    );
}

function flavor_display_chapter_reader($data) {
    $manhwa_id    = $data['manhwa_id'];
    $manhwa_post  = get_post($manhwa_id);
    $manhwa_slug  = $manhwa_post ? $manhwa_post->post_name : '';
    $get_ch_num   = function($ch) { return preg_match('/([\d.]+)/', $ch['title'], $m) ? $m[1] : '1'; };
    $all_chapters = get_post_meta($manhwa_id, '_manhwa_chapters', true);
    $cur_num      = $get_ch_num($data['chapter']);

    $prev_url = $data['prev_chapter'] ? flavor_get_chapter_url($manhwa_id, $data['prev_chapter']) : null;
    $next_url = $data['next_chapter'] ? flavor_get_chapter_url($manhwa_id, $data['next_chapter']) : null;

    $theme_tpl = locate_template(array('chapter-reader.php', 'template-chapter-reader.php', 'templates/chapter-reader.php'));
    if ($theme_tpl) {
        set_query_var('chapter_data', $data);
        set_query_var('manhwa_id', $manhwa_id);
        set_query_var('manhwa_title', $data['manhwa_title']);
        set_query_var('manhwa_slug', $manhwa_slug);
        set_query_var('chapter', $data['chapter']);
        set_query_var('images', $data['images']);
        set_query_var('prev_chapter', $data['prev_chapter']);
        set_query_var('next_chapter', $data['next_chapter']);
        set_query_var('prev_url', $prev_url);
        set_query_var('next_url', $next_url);
        set_query_var('all_chapters', $all_chapters);
        set_query_var('current_ch_num', $cur_num);
        set_query_var('current_url', flavor_get_chapter_url($manhwa_id, $data['chapter']));
        set_query_var('canonical_url', flavor_get_chapter_url($manhwa_id, $data['chapter']));
        set_query_var('seo_title', $data['manhwa_title'] . ' ' . $data['chapter']['title'] . ' - ' . get_bloginfo('name'));
        set_query_var('seo_description', sprintf('Read %s %s online for free on %s.', $data['manhwa_title'], $data['chapter']['title'], get_bloginfo('name')));
        set_query_var('cover_url', get_the_post_thumbnail_url($manhwa_id, 'large'));
        set_query_var('get_chapter_num', $get_ch_num);
        set_query_var('get_chapter_url_func', 'flavor_get_chapter_url');
        include $theme_tpl;
        return;
    }

    // Minimal fallback if no theme template
    wp_redirect(get_permalink($manhwa_id));
    exit;
}

function flavor_chapter_404() {
    $manhwa_id = isset($_GET['manhwa']) ? intval($_GET['manhwa']) : 0;
    $manhwa    = $manhwa_id ? get_post($manhwa_id) : null;
    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo('charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="robots" content="noindex, follow">
        <title>Chapter Not Found - <?php bloginfo('name'); ?></title>
        <style>*{margin:0;padding:0;box-sizing:border-box}body{background:#16151d;color:#fff;font-family:-apple-system,sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center}.box{background:#1c1b24;padding:40px;border-radius:16px;text-align:center;max-width:500px}.icon{font-size:72px;margin-bottom:20px}h1{font-size:24px;margin-bottom:10px}p{color:#888;margin-bottom:20px}.btn{display:inline-block;padding:12px 24px;background:#366ad3;color:#fff;text-decoration:none;border-radius:8px}</style>
    </head>
    <body>
        <div class="box">
            <div class="icon">📖</div>
            <h1>Chapter Not Found</h1>
            <?php if ($manhwa): ?>
                <p>Chapter not found in "<?php echo esc_html($manhwa->post_title); ?>"</p>
                <a href="<?php echo esc_url(get_permalink($manhwa_id)); ?>" class="btn">Back to <?php echo esc_html($manhwa->post_title); ?></a>
            <?php else: ?>
                <p>The requested chapter could not be found.</p>
                <a href="<?php echo home_url(); ?>" class="btn">Go to Homepage</a>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
}

/* ================================================================
 * 5. META BOXES
 * ============================================================= */

function flavor_manhwa_meta_boxes() {
    // Remove plugin's conflicting meta boxes if they exist
    remove_meta_box('manhwa_cover', 'manhwa', 'side');
    remove_meta_box('manhwa_details', 'manhwa', 'normal');
    remove_meta_box('manhwa_chapters', 'manhwa', 'normal');
    remove_meta_box('manhwa_details_metabox', 'manhwa', 'normal'); // Meta Box plugin version

    // Register our own with unique IDs
    add_meta_box('flavor_manhwa_cover', '📸 Manhwa Cover', 'flavor_render_cover_meta_box', 'manhwa', 'side', 'high');
    add_meta_box('flavor_manhwa_details', '📋 Manhwa Details', 'flavor_render_details_meta_box', 'manhwa', 'normal', 'high');
    add_meta_box('flavor_manhwa_chapters', '📖 Chapters', 'flavor_render_chapters_meta_box', 'manhwa', 'normal', 'high');
}

/* --- Cover Meta Box --- */
function flavor_render_cover_meta_box($post) {
    wp_nonce_field('flavor_manhwa_meta', 'flavor_manhwa_nonce');
    $thumb_id  = get_post_thumbnail_id($post->ID);
    $thumb_url = get_the_post_thumbnail_url($post->ID, 'medium');
    $cover_url = get_post_meta($post->ID, '_manhwa_cover_url', true);
    ?>
    <div style="text-align:center">
        <div id="manhwa-cover-preview" style="margin-bottom:15px">
            <?php if ($thumb_url): ?>
                <img src="<?php echo esc_url($thumb_url); ?>" style="max-width:100%;height:auto;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.1)">
            <?php else: ?>
                <div style="background:#f0f0f0;padding:60px 20px;border-radius:10px;color:#999"><span style="font-size:48px">📖</span><p style="margin:10px 0 0">No cover</p></div>
            <?php endif; ?>
        </div>
        <button type="button" id="manhwa-upload-cover-btn" class="button button-primary button-large" style="width:100%;margin-bottom:10px">📤 Upload Cover</button>
        <?php if ($thumb_id): ?>
            <button type="button" id="manhwa-remove-cover-btn" class="button" style="width:100%;background:#dc3545;color:#fff;border:none">🗑️ Remove</button>
        <?php endif; ?>
        <input type="hidden" id="manhwa-cover-id" name="manhwa_cover_id" value="<?php echo esc_attr($thumb_id); ?>">
        <div style="margin-top:20px;padding-top:20px;border-top:1px solid #ddd">
            <p style="margin:0 0 10px;font-weight:bold;text-align:left">Or upload from URL:</p>
            <input type="text" id="manhwa-cover-url" name="manhwa_cover_url" value="<?php echo esc_attr($cover_url); ?>" placeholder="https://example.com/image.jpg" style="width:100%;padding:8px;margin-bottom:10px">
            <button type="button" id="manhwa-upload-from-url-btn" class="button" style="width:100%">🌐 Upload from URL</button>
        </div>
    </div>
    <script>
    jQuery(function($){
        var mu;
        $('#manhwa-upload-cover-btn').on('click',function(e){
            e.preventDefault();
            if(mu){mu.open();return;}
            mu=wp.media({title:'Choose Cover',button:{text:'Set as Cover'},multiple:false});
            mu.on('select',function(){
                var a=mu.state().get('selection').first().toJSON();
                $('#manhwa-cover-id').val(a.id);
                $('#manhwa-cover-preview').html('<img src="'+a.url+'" style="max-width:100%;height:auto;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.1)">');
                if(!$('#manhwa-remove-cover-btn').length) $('#manhwa-upload-cover-btn').after('<button type="button" id="manhwa-remove-cover-btn" class="button" style="width:100%;background:#dc3545;color:#fff;border:none;margin-top:10px">🗑️ Remove</button>');
            });
            mu.open();
        });
        $(document).on('click','#manhwa-remove-cover-btn',function(e){
            e.preventDefault();$('#manhwa-cover-id').val('');
            $('#manhwa-cover-preview').html('<div style="background:#f0f0f0;padding:60px 20px;border-radius:10px;color:#999"><span style="font-size:48px">📖</span><p style="margin:10px 0 0">No cover</p></div>');
            $(this).remove();
        });
        $('#manhwa-upload-from-url-btn').on('click',function(e){
            e.preventDefault();var url=$('#manhwa-cover-url').val();if(!url){alert('Enter URL');return;}
            $(this).text('⏳ Uploading...').prop('disabled',true);
            $.post(ajaxurl,{action:'manhwa_upload_cover_from_url',url:url,post_id:<?php echo $post->ID; ?>,nonce:'<?php echo wp_create_nonce("manhwa_cover_upload"); ?>'},function(r){
                if(r.success){$('#manhwa-cover-id').val(r.data.attachment_id);$('#manhwa-cover-preview').html('<img src="'+r.data.url+'" style="max-width:100%;height:auto;border-radius:10px">');alert('✅ Done!');}
                else{alert('❌ '+r.data.message);}
            }).always(function(){$('#manhwa-upload-from-url-btn').text('🌐 Upload from URL').prop('disabled',false);});
        });
    });
    </script>
    <?php
}

/* --- Details Meta Box --- */
function flavor_render_details_meta_box($post) {
    $m = array(
        'author'      => get_post_meta($post->ID, '_manhwa_author', true),
        'artist'      => get_post_meta($post->ID, '_manhwa_artist', true),
        'alt'         => get_post_meta($post->ID, '_manhwa_alternative_title', true),
        'rating'      => get_post_meta($post->ID, '_manhwa_rating', true),
        'views'       => get_post_meta($post->ID, '_manhwa_views', true),
        'status'      => get_post_meta($post->ID, '_manhwa_status', true),
        'year'        => get_post_meta($post->ID, '_manhwa_release_year', true),
        'type'        => get_post_meta($post->ID, '_manhwa_type', true),
        'source_url'  => get_post_meta($post->ID, '_manhwa_source_url', true),
    );
    ?>
    <style>
        .fmg{display:grid;grid-template-columns:repeat(2,1fr);gap:20px;padding:20px;background:#f9f9f9;border-radius:10px}
        .fmf{display:flex;flex-direction:column}.fmf label{font-weight:600;color:#2c3e50;margin-bottom:8px;font-size:14px}
        .fmf input,.fmf select{padding:12px 15px;border:2px solid #e0e0e0;border-radius:8px;font-size:14px;background:#fff}
        .fmf input:focus,.fmf select:focus{outline:none;border-color:#3498db;box-shadow:0 0 0 3px rgba(52,152,219,.1)}
        .fmfull{grid-column:1/-1}.fhint{font-size:12px;color:#7f8c8d;margin-top:5px}
    </style>
    <div class="fmg">
        <div class="fmf"><label>✍️ Author</label><input type="text" name="manhwa_author" value="<?php echo esc_attr($m['author']); ?>" placeholder="Author name"><span class="fhint">Creator / writer</span></div>
        <div class="fmf"><label>🎨 Artist</label><input type="text" name="manhwa_artist" value="<?php echo esc_attr($m['artist']); ?>" placeholder="Artist name"><span class="fhint">Illustrator / artist</span></div>
        <div class="fmf fmfull"><label>📝 Alternative Title</label><input type="text" name="manhwa_alternative_title" value="<?php echo esc_attr($m['alt']); ?>" placeholder="Other names"></div>
        <div class="fmf"><label>⭐ Rating</label><input type="number" name="manhwa_rating" value="<?php echo esc_attr($m['rating'] ?: '7.0'); ?>" step="0.1" min="0" max="10"><span class="fhint">0 to 10</span></div>
        <div class="fmf"><label>👁️ Views</label><input type="number" name="manhwa_views" value="<?php echo esc_attr($m['views'] ?: '0'); ?>" min="0"><span class="fhint">Total views</span></div>
        <div class="fmf"><label>📊 Status</label>
            <select name="manhwa_status">
                <option value="">Select…</option>
                <option value="ongoing" <?php selected($m['status'], 'ongoing'); ?>>Ongoing</option>
                <option value="completed" <?php selected($m['status'], 'completed'); ?>>Completed</option>
                <option value="hiatus" <?php selected($m['status'], 'hiatus'); ?>>Hiatus</option>
            </select>
        </div>
        <div class="fmf"><label>📅 Release Year</label><input type="number" name="manhwa_release_year" value="<?php echo esc_attr($m['year']); ?>" placeholder="<?php echo date('Y'); ?>"></div>
        <div class="fmf"><label>📖 Type</label>
            <select name="manhwa_type">
                <option value="">Select…</option>
                <option value="Manhwa" <?php selected($m['type'], 'Manhwa'); ?>>🇰🇷 Manhwa</option>
                <option value="Manga" <?php selected($m['type'], 'Manga'); ?>>🇯🇵 Manga</option>
                <option value="Manhua" <?php selected($m['type'], 'Manhua'); ?>>🇨🇳 Manhua</option>
                <option value="Comic" <?php selected($m['type'], 'Comic'); ?>>🌍 Comic</option>
            </select>
        </div>
        <div class="fmf fmfull"><label>🔗 Source URL</label><input type="url" name="manhwa_source_url" value="<?php echo esc_attr($m['source_url']); ?>" placeholder="https://…"><span class="fhint">Original source for scraping</span></div>
    </div>
    <?php
}

/* --- Chapters Meta Box --- */
function flavor_render_chapters_meta_box($post) {
    $chapters = get_post_meta($post->ID, '_manhwa_chapters', true);
    if (!is_array($chapters)) $chapters = array();
    ?>
    <style>
        .fch{background:#f5f5f5;padding:15px;margin-bottom:10px;border-radius:8px;border:1px solid #ddd;position:relative}
        .fch:hover{border-color:#2271b1}
        .fchg{display:grid;grid-template-columns:120px 1fr 1fr;gap:10px;align-items:end}
        .fch-row2{display:grid;grid-template-columns:120px 1fr auto;gap:10px;align-items:end;margin-top:10px}
        .fchi-section{margin-top:15px;padding-top:15px;border-top:1px dashed #ccc}
        .fchi-toggle{background:#2271b1;color:#fff;border:none;padding:5px 10px;border-radius:4px;cursor:pointer;font-size:12px}
        .fchi-toggle:hover{background:#135e96}
        .fchi-container{margin-top:10px;display:none}.fchi-container.active{display:block}
        .fchi-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(80px,1fr));gap:8px;margin-top:10px;max-height:200px;overflow-y:auto;padding:10px;background:#fff;border-radius:4px}
        .fchi-thumb{width:100%;height:80px;object-fit:cover;border-radius:4px;border:1px solid #ddd}
        .fchi-ta{width:100%;height:100px;font-family:monospace;font-size:11px}
        .fch-actions{display:flex;gap:6px;align-items:center}
        .fch-view{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;background:#2271b1;color:#fff;border-radius:4px;text-decoration:none;font-size:12px}
        .fch-view:hover{background:#135e96;color:#fff}
        .fch-count{background:#2271b1;color:#fff;padding:8px 16px;border-radius:8px;font-size:14px;font-weight:600;display:inline-block;margin-bottom:12px}
    </style>
    <div id="manhwa-chapters-container">
        <div class="fch-count">📖 Total: <?php echo count($chapters); ?> chapter(s)</div>
        <div id="chapters-list">
            <?php foreach ($chapters as $i => $ch):
                $imgs = isset($ch['images']) ? $ch['images'] : array();
                $cnt  = is_array($imgs) ? count($imgs) : 0;
                $ch_num = $ch['number'] ?? $ch['title'] ?? '';
                $ch_view_url = flavor_get_chapter_url($post->ID, $ch);
            ?>
            <div class="fch">
                <div class="fchg">
                    <div><label><strong>Number:</strong></label><input type="text" name="manhwa_chapters[<?php echo $i; ?>][number]" value="<?php echo esc_attr($ch['number'] ?? ''); ?>" placeholder="01" style="width:100%;padding:8px"></div>
                    <div><label><strong>Title:</strong></label><input type="text" name="manhwa_chapters[<?php echo $i; ?>][title]" value="<?php echo esc_attr($ch['title']); ?>" placeholder="Chapter 01" style="width:100%;padding:8px"></div>
                    <div><label><strong>URL (legacy):</strong></label><input type="text" name="manhwa_chapters[<?php echo $i; ?>][url]" value="<?php echo esc_attr($ch['url'] ?? ''); ?>" style="width:100%;padding:8px"></div>
                </div>
                <div class="fch-row2">
                    <div><label><strong>Date:</strong></label><input type="date" name="manhwa_chapters[<?php echo $i; ?>][date]" value="<?php echo esc_attr($ch['date'] ?? ''); ?>" style="width:100%;padding:8px"></div>
                    <div><label><strong>Added:</strong></label><input type="text" name="manhwa_chapters[<?php echo $i; ?>][added_at]" value="<?php echo esc_attr($ch['added_at'] ?? ''); ?>" placeholder="auto" style="width:100%;padding:8px" readonly></div>
                    <div class="fch-actions">
                        <a href="<?php echo esc_url($ch_view_url); ?>" target="_blank" class="fch-view">👁 View</a>
                        <button type="button" class="button remove-chapter" style="background:#dc3545;color:#fff;border:none;padding:4px 10px">✕ Remove</button>
                    </div>
                </div>
                <div class="fchi-section">
                    <button type="button" class="fchi-toggle" onclick="this.nextElementSibling.classList.toggle('active');this.textContent=this.nextElementSibling.classList.contains('active')?'📷 Hide Images':'📷 Images (<?php echo $cnt; ?>)'">📷 Images (<?php echo $cnt; ?>)</button>
                    <div class="fchi-container">
                        <?php if ($cnt): ?>
                        <div class="fchi-grid">
                            <?php foreach ($imgs as $img):
                                $u = is_array($img) ? ($img['url'] ?? '') : $img;
                            ?>
                                <img src="<?php echo esc_url($u); ?>" class="fchi-thumb" loading="lazy" onerror="this.style.display='none'">
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <div style="margin-top:10px">
                            <label><strong>Image URLs (one per line):</strong></label>
                            <textarea name="manhwa_chapters[<?php echo $i; ?>][images_text]" class="fchi-ta" placeholder="https://…"><?php
                                if ($cnt) {
                                    $urls = array_map(function($img) { return is_array($img) ? ($img['url'] ?? '') : $img; }, $imgs);
                                    echo esc_textarea(implode("\n", $urls));
                                }
                            ?></textarea>
                        </div>
                        <input type="hidden" name="manhwa_chapters[<?php echo $i; ?>][images_json]" value="<?php echo esc_attr(json_encode($imgs)); ?>" class="images-json-field">
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" id="add-chapter" class="button button-primary" style="margin-top:10px">+ Add Chapter</button>
    </div>
    <script>
    jQuery(function($){
        var ci=<?php echo count($chapters); ?>;
        $('#add-chapter').on('click',function(){
            var now=new Date().toISOString().split('T')[0];
            var h='<div class="fch">'+
              '<div class="fchg">'+
                '<div><label><strong>Number:</strong></label><input type="text" name="manhwa_chapters['+ci+'][number]" placeholder="01" style="width:100%;padding:8px"></div>'+
                '<div><label><strong>Title:</strong></label><input type="text" name="manhwa_chapters['+ci+'][title]" placeholder="Chapter 01" style="width:100%;padding:8px"></div>'+
                '<div><label><strong>URL (legacy):</strong></label><input type="text" name="manhwa_chapters['+ci+'][url]" style="width:100%;padding:8px"></div>'+
              '</div>'+
              '<div class="fch-row2">'+
                '<div><label><strong>Date:</strong></label><input type="date" name="manhwa_chapters['+ci+'][date]" value="'+now+'" style="width:100%;padding:8px"></div>'+
                '<div><label><strong>Added:</strong></label><input type="text" name="manhwa_chapters['+ci+'][added_at]" value="" placeholder="auto" style="width:100%;padding:8px" readonly></div>'+
                '<div class="fch-actions"><button type="button" class="button remove-chapter" style="background:#dc3545;color:#fff;border:none;padding:4px 10px">✕ Remove</button></div>'+
              '</div>'+
              '<div class="fchi-section">'+
                '<button type="button" class="fchi-toggle" onclick="this.nextElementSibling.classList.toggle(\'active\');this.textContent=this.nextElementSibling.classList.contains(\'active\')?  \'📷 Hide Images\':\'📷 Images (0)\'">📷 Images (0)</button>'+
                '<div class="fchi-container"><div style="margin-top:10px"><label><strong>Image URLs (one per line):</strong></label><textarea name="manhwa_chapters['+ci+'][images_text]" class="fchi-ta" placeholder="https://…"></textarea></div><input type="hidden" name="manhwa_chapters['+ci+'][images_json]" value="[]" class="images-json-field"></div>'+
              '</div>'+
            '</div>';
            $('#chapters-list').append(h);ci++;
            // Update counter
            var total=$('#chapters-list .fch').length;
            $('.fch-count').text('📖 Total: '+total+' chapter(s)');
        });
        $(document).on('click','.remove-chapter',function(){
            $(this).closest('.fch').remove();
            var total=$('#chapters-list .fch').length;
            $('.fch-count').text('📖 Total: '+total+' chapter(s)');
        });
        $('form#post').on('submit',function(){
            $('.fchi-ta').each(function(){
                var urls=$(this).val().split('\n').filter(function(u){return u.trim()!=='';}).map(function(u){return{url:u.trim()};});
                $(this).closest('.fchi-container').find('.images-json-field').val(JSON.stringify(urls));
            });
        });
    });
    </script>
    <?php
}

/* ================================================================
 * 6. SAVE META
 * ============================================================= */

function flavor_save_manhwa_meta($post_id, $post) {
    if (!isset($_POST['flavor_manhwa_nonce']) || !wp_verify_nonce($_POST['flavor_manhwa_nonce'], 'flavor_manhwa_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    // Cover
    if (isset($_POST['manhwa_cover_id'])) {
        $cid = intval($_POST['manhwa_cover_id']);
        if ($cid > 0) set_post_thumbnail($post_id, $cid); else delete_post_thumbnail($post_id);
    }
    if (isset($_POST['manhwa_cover_url']))
        update_post_meta($post_id, '_manhwa_cover_url', esc_url_raw($_POST['manhwa_cover_url']));

    // Text fields
    $fields = array('_manhwa_author' => 'manhwa_author', '_manhwa_artist' => 'manhwa_artist', '_manhwa_alternative_title' => 'manhwa_alternative_title', '_manhwa_type' => 'manhwa_type', '_manhwa_status' => 'manhwa_status', '_manhwa_source_url' => 'manhwa_source_url');
    foreach ($fields as $meta_key => $post_key) {
        if (isset($_POST[$post_key]))
            update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$post_key]));
    }
    // Numeric
    if (isset($_POST['manhwa_rating']))       update_post_meta($post_id, '_manhwa_rating', floatval($_POST['manhwa_rating']));
    if (isset($_POST['manhwa_views']))        update_post_meta($post_id, '_manhwa_views', intval($_POST['manhwa_views']));
    if (isset($_POST['manhwa_release_year'])) update_post_meta($post_id, '_manhwa_release_year', intval($_POST['manhwa_release_year']));

    // Chapters
    if (isset($_POST['manhwa_chapters']) && is_array($_POST['manhwa_chapters'])) {
        $chapters = array();
        foreach ($_POST['manhwa_chapters'] as $ch) {
            $images = array();
            if (!empty($ch['images_json'])) {
                $images = json_decode(stripslashes($ch['images_json']), true);
                if (!is_array($images)) $images = array();
            } elseif (!empty($ch['images_text'])) {
                foreach (explode("\n", $ch['images_text']) as $line) {
                    $url = trim($line);
                    if ($url) $images[] = array('url' => esc_url_raw($url));
                }
            }
            $added_at = !empty($ch['added_at']) ? sanitize_text_field($ch['added_at']) : current_time('mysql');
            $chapters[] = array(
                'number'   => sanitize_text_field($ch['number'] ?? ''),
                'title'    => sanitize_text_field($ch['title'] ?? ''),
                'url'      => esc_url_raw($ch['url'] ?? ''),
                'date'     => sanitize_text_field($ch['date'] ?? ''),
                'added_at' => $added_at,
                'images'   => $images,
            );
        }
        update_post_meta($post_id, '_manhwa_chapters', $chapters);
    }
}

/* ================================================================
 * 7. ADMIN COLUMNS
 * ============================================================= */

function flavor_manhwa_admin_columns($columns) {
    $new = array();
    if (isset($columns['cb'])) $new['cb'] = $columns['cb'];
    $new['cover'] = 'Cover';
    if (isset($columns['title'])) $new['title'] = $columns['title'];
    $new['chapters'] = 'Chapters';
    $new['type'] = 'Type';
    foreach ($columns as $k => $v) { if (!isset($new[$k])) $new[$k] = $v; }
    return $new;
}

function flavor_manhwa_admin_column_content($col, $pid) {
    switch ($col) {
        case 'cover':
            if (has_post_thumbnail($pid))
                echo '<a href="' . get_edit_post_link($pid) . '">' . get_the_post_thumbnail($pid, array(50, 70), array('style' => 'border-radius:4px')) . '</a>';
            else
                echo '<div style="width:50px;height:70px;background:#f0f0f1;border-radius:4px;display:flex;align-items:center;justify-content:center;color:#999">📚</div>';
            break;
        case 'chapters':
            $chs = get_post_meta($pid, '_manhwa_chapters', true);
            echo '<strong>' . (is_array($chs) ? count($chs) : 0) . '</strong>';
            break;
        case 'type':
            $t = get_post_meta($pid, '_manhwa_type', true);
            if ($t) {
                $colors = array('manhwa' => '#667eea', 'manga' => '#e74c3c', 'manhua' => '#27ae60');
                $c = $colors[strtolower($t)] ?? '#888';
                echo '<span style="background:' . $c . ';color:#fff;padding:2px 8px;border-radius:3px;font-size:11px">' . esc_html(ucfirst($t)) . '</span>';
            } else echo '—';
            break;
    }
}

function flavor_manhwa_admin_column_styles() {
    global $pagenow, $typenow;
    if ($pagenow === 'edit.php' && $typenow === 'manhwa')
        echo '<style>.column-cover{width:60px}.column-chapters{width:80px;text-align:center}.column-type{width:80px}</style>';
}

/* ================================================================
 * 8. AJAX: UPLOAD COVER FROM URL
 * ============================================================= */

function flavor_ajax_upload_cover_from_url() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'manhwa_cover_upload')) wp_send_json_error(array('message' => 'Invalid nonce'));
    if (!current_user_can('edit_posts')) wp_send_json_error(array('message' => 'Permission denied'));

    $url     = esc_url_raw($_POST['url']);
    $post_id = intval($_POST['post_id']);
    if (empty($url)) wp_send_json_error(array('message' => 'Invalid URL'));

    $response = wp_remote_get($url, array('timeout' => 30, 'sslverify' => false));
    if (is_wp_error($response)) wp_send_json_error(array('message' => $response->get_error_message()));

    $data = wp_remote_retrieve_body($response);
    if (empty($data)) wp_send_json_error(array('message' => 'Download failed'));

    $filename = sanitize_file_name(basename(parse_url($url, PHP_URL_PATH)));
    if (!preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $filename)) $filename .= '.jpg';

    $upload = wp_upload_bits($filename, null, $data);
    if ($upload['error']) wp_send_json_error(array('message' => $upload['error']));

    $attach_id = wp_insert_attachment(array(
        'post_mime_type' => $upload['type'],
        'post_title'     => pathinfo($filename, PATHINFO_FILENAME),
        'post_content'   => '',
        'post_status'    => 'inherit',
    ), $upload['file'], $post_id);

    if (is_wp_error($attach_id)) wp_send_json_error(array('message' => $attach_id->get_error_message()));

    require_once ABSPATH . 'wp-admin/includes/image.php';
    wp_update_attachment_metadata($attach_id, wp_generate_attachment_metadata($attach_id, $upload['file']));
    set_post_thumbnail($post_id, $attach_id);
    update_post_meta($post_id, '_manhwa_cover_url', $url);

    wp_send_json_success(array('attachment_id' => $attach_id, 'url' => wp_get_attachment_url($attach_id)));
}
