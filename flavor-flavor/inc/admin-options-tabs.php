<?php
/**
 * Flavor Theme - Admin Options Tabs Render
 * Contains the HTML for all tab panels
 */
if (!defined('ABSPATH')) exit;

function flavor_render_tab_general() { ?>
    <div class="fvo-page-title">
        <h1>General Settings</h1>
        <p>Configure theme colors, hero slider, and sidebar display options.</p>
    </div>

    <div class="fvo-card">
        <div class="fvo-card-header">
            <div class="fvo-card-header-left">
                <span class="material-symbols-outlined">palette</span>
                <h3>Theme Colors</h3>
            </div>
        </div>
        <div class="fvo-card-body">
            <div class="fvo-grid-2">
                <?php fvo_color('primary_color', 'Primary Color', '#ff5722'); ?>
                <?php fvo_color('secondary_color', 'Secondary Color', '#ff5722'); ?>
            </div>
        </div>
    </div>

    <div class="fvo-card">
        <div class="fvo-card-header">
            <div class="fvo-card-header-left">
                <span class="material-symbols-outlined">view_carousel</span>
                <h3>Hero Slider</h3>
            </div>
            <div><?php fvo_toggle_inline('flavor_hero_slider_enabled', true); ?></div>
        </div>
        <div class="fvo-card-body">
            <div class="fvo-grid-2">
                <?php fvo_select('flavor_hero_slider_mode', 'Slider Mode', array(
                    'manual' => 'Manual (Featured)',
                    'views'  => 'Most Viewed',
                    'rating' => 'Highest Rated',
                    'latest' => 'Latest Updated',
                ), '', 'views'); ?>
                <?php fvo_number('flavor_hero_slider_count', 'Number of Slides', 8, 3, 15); ?>
                <?php fvo_number('flavor_hero_slider_speed', 'Auto-play Speed (ms)', 5000, 2000, 10000, 'Duration in milliseconds between slides'); ?>
            </div>
        </div>
    </div>

    <div class="fvo-card">
        <div class="fvo-card-header">
            <div class="fvo-card-header-left">
                <span class="material-symbols-outlined">visibility</span>
                <h3>Most Viewed Section</h3>
            </div>
            <div><?php fvo_toggle_inline('flavor_most_viewed_enabled', true); ?></div>
        </div>
        <div class="fvo-card-body">
            <div class="fvo-grid-2">
                <?php fvo_number('flavor_most_viewed_count', 'Number of Manhwa', 10, 3, 20, 'How many manhwa to show in Most Viewed section'); ?>
            </div>
        </div>
    </div>

    <div class="fvo-card">
        <div class="fvo-card-header">
            <div class="fvo-card-header-left">
                <span class="material-symbols-outlined">view_sidebar</span>
                <h3>Sidebar Settings</h3>
            </div>
        </div>
        <div class="fvo-card-body">
            <?php
            fvo_toggle('sidebar_global_enable', 'Enable Sidebar (Global)', 'Master toggle. When unchecked, sidebar is hidden on ALL pages.', true);
            fvo_toggle('sidebar_homepage', 'Show on Homepage', '', true);
            fvo_toggle('sidebar_single', 'Show on Single Post / Manhwa Detail', '', true);
            fvo_toggle('sidebar_archive', 'Show on Archive / Manhwa List', '', true);
            fvo_toggle('sidebar_search', 'Show on Search Results', '', true);
            fvo_toggle('sidebar_taxonomy', 'Show on Genre / Taxonomy Pages', '', true);
            ?>
        </div>
    </div>
<?php }

function flavor_render_tab_announcements() { ?>
    <div class="fvo-page-title">
        <h1>Announcements</h1>
        <p>Configure site-wide notification bars and ticker alerts.</p>
    </div>

    <div class="fvo-card">
        <div class="fvo-card-header">
            <div class="fvo-card-header-left">
                <span class="material-symbols-outlined">notification_important</span>
                <h3>Announcement Bar</h3>
            </div>
            <div><?php fvo_toggle_inline('announcement_bar_enable', false); ?></div>
        </div>
        <div class="fvo-card-body">
            <div class="fvo-grid-2">
                <?php
                fvo_text('announcement_bar_title', 'Announcement Title', 'Title shown at top of announcement box');
                fvo_color('announcement_bar_bg', 'Background Color', '#ff5722');
                ?>
            </div>
            <?php fvo_textarea('announcement_bar_text', 'Announcement Content', 'HTML allowed: <p>, <a>, <br>'); ?>
            <?php fvo_toggle('announcement_bar_dismissible', 'Allow users to close announcement', '', true); ?>
        </div>
    </div>

    <div class="fvo-card">
        <div class="fvo-card-header">
            <div class="fvo-card-header-left">
                <span class="material-symbols-outlined">trending_up</span>
                <h3>News Ticker</h3>
            </div>
            <div><?php fvo_toggle_inline('ticker_enable', false); ?></div>
        </div>
        <div class="fvo-card-body">
            <div class="fvo-grid-2">
                <?php
                fvo_text('ticker_label', 'Ticker Label', 'E.g., INFO, NEWS, UPDATE', 'INFO');
                fvo_number('ticker_speed', 'Scroll Speed (seconds)', 30, 10, 120, 'Duration for one complete scroll');
                fvo_text('ticker_text', 'Ticker Text', 'Text that scrolls. Keep it short.');
                fvo_text('ticker_link', 'Ticker Link URL (optional)', '', '', 'url');
                ?>
            </div>
        </div>
    </div>

    <div class="fvo-alert fvo-alert-warning">
        <span class="material-symbols-outlined">info</span>
        <div><strong>Note:</strong> Enabling multiple announcement bars simultaneously may impact the user experience on mobile devices.</div>
    </div>
<?php }

function flavor_render_tab_contact() { ?>
    <div class="fvo-page-title">
        <h1>Contact & Social</h1>
        <p>Manage how your visitors interact with you across the web.</p>
    </div>

    <div class="fvo-card">
        <div class="fvo-card-header">
            <div class="fvo-card-header-left">
                <span class="material-symbols-outlined">contact_mail</span>
                <h3>Contact Information</h3>
            </div>
        </div>
        <div class="fvo-card-body">
            <div class="fvo-grid-2">
                <?php
                fvo_text('contact_email', 'Support Email', 'Used for footer links and contact page.', 'admin@example.com', 'email');
                fvo_text('contact_whatsapp', 'WhatsApp Number', 'Include country code, e.g., +628123456789', '+628123456789');
                fvo_text('contact_telegram', 'Telegram Username', 'Without @ symbol', 'username');
                ?>
            </div>
        </div>
    </div>

    <div class="fvo-card">
        <div class="fvo-card-header">
            <div class="fvo-card-header-left">
                <span class="material-symbols-outlined">public</span>
                <h3>Social Media URLs</h3>
            </div>
        </div>
        <div class="fvo-card-body">
            <?php
            fvo_social('social_facebook', 'Facebook', 'group', 'https://facebook.com/yourpage');
            fvo_social('social_instagram', 'Instagram', 'photo_camera', 'https://instagram.com/yourprofile');
            fvo_social('social_twitter', 'Twitter / X', 'close', 'https://twitter.com/yourhandle');
            fvo_social('social_discord', 'Discord', 'forum', 'https://discord.gg/invite');
            fvo_social('social_tiktok', 'TikTok', 'music_note', 'https://tiktok.com/@youraccount');
            fvo_social('social_youtube', 'YouTube', 'smart_display', 'https://youtube.com/c/yourchannel');
            fvo_social('social_telegram', 'Telegram', 'send', 'https://t.me/yourchannel');
            ?>
        </div>
    </div>

    <div class="fvo-alert fvo-alert-info">
        <span class="material-symbols-outlined">info</span>
        <div><strong>Pro Tip:</strong> Leaving a field empty will automatically hide the corresponding icon from your website's header and footer.</div>
    </div>
<?php }

function flavor_render_tab_ads() { ?>
    <div class="fvo-page-title">
        <h1>Ads Management</h1>
        <p>Manage advertisement banners and direct link ads.</p>
    </div>

    <div class="fvo-card">
        <div class="fvo-card-header">
            <div class="fvo-card-header-left">
                <span class="material-symbols-outlined">campaign</span>
                <h3>Display Ads</h3>
            </div>
            <div><?php fvo_toggle_inline('flavor_ads_enable', true); ?></div>
        </div>
        <div class="fvo-card-body">
            <?php
            fvo_textarea_raw('flavor_ads_header', 'Header Ad (728x90)', 'Appears below the header. Supports HTML/JS ad codes.');
            fvo_textarea_raw('flavor_ads_after_content', 'After Content Ad (728x90)', 'Appears after main content.');
            fvo_textarea_raw('flavor_ads_sidebar', 'Sidebar Ad (300x250)', 'Appears in sidebar.');
            fvo_textarea_raw('flavor_ads_before_footer', 'Before Footer Ad (728x90)', 'Appears before footer.');
            fvo_textarea_raw('flavor_ads_in_article', 'In-Article Ad', 'Appears within chapter reader content.');
            fvo_textarea_raw('flavor_ads_float_bottom', 'Float Bottom Ad (728x90)', 'Sticky banner at bottom. Closeable by user.');
            fvo_textarea_raw('flavor_ads_before_chapter', 'Before Reading Chapter Ad', 'Appears just before the chapter images in the reader page.');
            fvo_textarea_raw('flavor_ads_after_chapter', 'After Reading Chapter Ad', 'Appears just after the chapter images in the reader page.');
            ?>
        </div>
    </div>

    <div class="fvo-card">
        <div class="fvo-card-header">
            <div class="fvo-card-header-left">
                <span class="material-symbols-outlined">link</span>
                <h3>Direct Link</h3>
            </div>
            <div><?php fvo_toggle_inline('flavor_ads_direct_link_enable', false); ?></div>
        </div>
        <div class="fvo-card-body">
            <div class="fvo-grid-2">
                <?php
                fvo_text('flavor_ads_direct_link_url', 'Direct Link URL', 'URL when user clicks empty area.', '', 'url');
                fvo_number('flavor_ads_direct_link_max', 'Max Clicks Per Session', 2, 0, 20, '0 = unlimited');
                fvo_text('flavor_ads_direct_link_exclude', 'Exclude Pages', 'Comma-separated. E.g.: admin,login,contact', 'admin,login');
                ?>
            </div>
        </div>
    </div>

    <div class="fvo-card">
        <div class="fvo-card-header">
            <div class="fvo-card-header-left">
                <span class="material-symbols-outlined">ad_units</span>
                <h3>Ad Slots (Contact Page)</h3>
            </div>
        </div>
        <div class="fvo-card-body">
            <?php for ($i = 1; $i <= 4; $i++): ?>
            <div style="padding:12px 0;border-bottom:1px solid #f1f5f9;">
                <h4 style="font-size:13px;font-weight:700;margin:0 0 10px;color:#475569;">Ad Slot <?php echo $i; ?></h4>
                <div class="fvo-grid-3">
                    <?php
                    fvo_text("ad_slot_{$i}_name", 'Name');
                    fvo_text("ad_slot_{$i}_size", 'Size', 'e.g., 728x90, 300x250');
                    fvo_select("ad_slot_{$i}_status", 'Status', array('available'=>'Available','sold'=>'Sold','hidden'=>'Hidden'), '', 'available');
                    ?>
                </div>
                <?php fvo_text("ad_slot_{$i}_desc", 'Description'); ?>
            </div>
            <?php endfor; ?>
        </div>
    </div>
<?php }

function flavor_render_tab_widgets() { ?>
    <div class="fvo-page-title">
        <h1>Widgets</h1>
        <p>Configure the Trending widget and comment system.</p>
    </div>

    <div class="fvo-card">
        <div class="fvo-card-header">
            <div class="fvo-card-header-left">
                <span class="material-symbols-outlined">trending_up</span>
                <h3>Trending Widget</h3>
            </div>
        </div>
        <div class="fvo-card-body">
            <div class="fvo-grid-2">
                <?php
                // These use get_option() with flavor_ prefix to match sidebar.php
                echo '<div class="fvo-field">';
                echo '<label class="fvo-label">Widget Title</label>';
                echo '<input type="text" name="flavor_trending_title" value="' . esc_attr(get_option('flavor_trending_title', 'Trending')) . '" placeholder="Trending" class="fvo-input">';
                echo '</div>';

                echo '<div class="fvo-field">';
                echo '<label class="fvo-label">Number of Manga</label>';
                echo '<input type="number" name="flavor_trending_count" value="' . esc_attr(get_option('flavor_trending_count', 5)) . '" min="1" max="20" class="fvo-input fvo-input-sm">';
                echo '</div>';
                ?>
            </div>

            <hr class="fvo-separator">
            <h4 style="font-size:13px;font-weight:700;margin:0 0 12px;color:#475569;">Sort per Tab</h4>
            <div class="fvo-grid-3">
                <?php
                $sort_choices = array('views'=>'Most Views','rating'=>'Highest Rating','latest'=>'Latest Added','random'=>'Random','manual'=>'Manual Selection');
                
                $sort_weekly = get_option('flavor_trending_sort_weekly', 'rating');
                echo '<div class="fvo-field"><label class="fvo-label">Sort Mingguan</label><select name="flavor_trending_sort_weekly" class="fvo-select">';
                foreach ($sort_choices as $k => $v) { echo '<option value="' . $k . '"' . selected($sort_weekly, $k, false) . '>' . $v . '</option>'; }
                echo '</select></div>';

                $sort_monthly = get_option('flavor_trending_sort_monthly', 'views');
                echo '<div class="fvo-field"><label class="fvo-label">Sort Bulanan</label><select name="flavor_trending_sort_monthly" class="fvo-select">';
                foreach ($sort_choices as $k => $v) { echo '<option value="' . $k . '"' . selected($sort_monthly, $k, false) . '>' . $v . '</option>'; }
                echo '</select></div>';

                $sort_all = get_option('flavor_trending_sort_all', 'latest');
                echo '<div class="fvo-field"><label class="fvo-label">Sort Semua</label><select name="flavor_trending_sort_all" class="fvo-select">';
                foreach ($sort_choices as $k => $v) { echo '<option value="' . $k . '"' . selected($sort_all, $k, false) . '>' . $v . '</option>'; }
                echo '</select></div>';
                ?>
            </div>

            <?php
            echo '<div class="fvo-field">';
            echo '<label class="fvo-label">Manual Post IDs</label>';
            echo '<input type="text" name="flavor_trending_manual_ids" value="' . esc_attr(get_option('flavor_trending_manual_ids', '')) . '" placeholder="123, 456, 789" class="fvo-input">';
            echo '<p class="fvo-desc">Comma-separated. Only used when Sort = Manual Selection.</p>';
            echo '</div>';
            ?>

            <hr class="fvo-separator">
            <?php
            // Boolean display options - use get_option with flavor_ prefix
            $show_tabs = get_option('flavor_trending_show_tabs', 1);
            $show_genres = get_option('flavor_trending_show_genres', 1);
            $show_rating = get_option('flavor_trending_show_rating', 1);
            ?>
            <div class="fvo-toggle-row">
                <div class="fvo-toggle-info"><h4>Show Tabs (Mingguan/Bulanan/Semua)</h4></div>
                <label class="fvo-toggle"><input type="checkbox" name="flavor_trending_show_tabs" value="1"<?php checked($show_tabs, 1); ?>><span class="fvo-toggle-slider"></span></label>
            </div>
            <div class="fvo-toggle-row">
                <div class="fvo-toggle-info"><h4>Show Genres</h4></div>
                <label class="fvo-toggle"><input type="checkbox" name="flavor_trending_show_genres" value="1"<?php checked($show_genres, 1); ?>><span class="fvo-toggle-slider"></span></label>
            </div>
            <div class="fvo-toggle-row">
                <div class="fvo-toggle-info"><h4>Show Rating Stars</h4></div>
                <label class="fvo-toggle"><input type="checkbox" name="flavor_trending_show_rating" value="1"<?php checked($show_rating, 1); ?>><span class="fvo-toggle-slider"></span></label>
            </div>
        </div>
    </div>

    <div class="fvo-card">
        <div class="fvo-card-header">
            <div class="fvo-card-header-left">
                <span class="material-symbols-outlined">chat</span>
                <h3>Comments Settings</h3>
            </div>
            <div><?php fvo_toggle_inline('comments_enabled', true); ?></div>
        </div>
        <div class="fvo-card-body">
            <div class="fvo-grid-2">
                <?php
                fvo_select('comments_system', 'Comment System', array('wordpress'=>'WordPress Comments','disqus'=>'Disqus'), '', 'wordpress');
                fvo_text('disqus_shortname', 'Disqus Shortname', 'From disqus.com/admin. Leave empty if using WP comments.');
                ?>
            </div>
            <?php fvo_textarea('comments_disabled_message', 'Disabled Message', 'Message shown when comments are off.'); ?>
        </div>
    </div>
<?php }

function flavor_render_tab_seo() { ?>
    <div class="fvo-page-title">
        <h1>SEO & Tracking</h1>
        <p>Search engine optimization and analytics tracking codes.</p>
    </div>

    <div class="fvo-card">
        <div class="fvo-card-header">
            <div class="fvo-card-header-left">
                <span class="material-symbols-outlined">search</span>
                <h3>SEO Settings</h3>
            </div>
        </div>
        <div class="fvo-card-body">
            <?php
            $seo_desc = get_option('flavor_seo_home_description', '');
            echo '<div class="fvo-field"><label class="fvo-label">Homepage Meta Description</label>';
            echo '<textarea name="flavor_seo_home_description" rows="3" class="fvo-textarea" style="font-family:inherit">' . esc_textarea($seo_desc) . '</textarea>';
            echo '<p class="fvo-desc">Custom meta description for homepage. Leave empty for auto-generate.</p></div>';
            
            $og_img = get_theme_mod('flavor_seo_og_image', '');
            echo '<div class="fvo-field"><label class="fvo-label">Default Social Share Image</label>';
            echo '<div style="display:flex;gap:8px;align-items:flex-start;">';
            echo '<input type="text" name="flavor_seo_og_image" value="' . esc_attr($og_img) . '" class="fvo-input fvo-media-input" style="flex:1" placeholder="Image URL">';
            echo '<button type="button" class="fvo-media-upload" style="padding:9px 14px;border:1px solid #cbd5e1;border-radius:8px;background:#f8fafc;cursor:pointer;font-size:13px;">Upload</button>';
            echo '<button type="button" class="fvo-media-remove" style="padding:9px 14px;border:1px solid #fca5a5;border-radius:8px;background:#fef2f2;color:#dc2626;cursor:pointer;font-size:13px;">Remove</button>';
            echo '</div>';
            if ($og_img) echo '<div class="fvo-media-preview" style="margin-top:8px"><img src="' . esc_url($og_img) . '" style="max-width:200px;border-radius:8px;"></div>';
            else echo '<div class="fvo-media-preview" style="margin-top:8px"></div>';
            echo '<p class="fvo-desc">Recommended: 1200x630px</p></div>';
            
            fvo_toggle('flavor_seo_enable_schema', 'Enable Schema Markup', 'Output JSON-LD structured data for rich snippets.', true);
            ?>
        </div>
    </div>

    <div class="fvo-card">
        <div class="fvo-card-header">
            <div class="fvo-card-header-left">
                <span class="material-symbols-outlined">analytics</span>
                <h3>Tracking & Analytics</h3>
            </div>
        </div>
        <div class="fvo-card-body">
            <?php
            $histats = get_option('tracking_histats_code', '');
            echo '<div class="fvo-field"><label class="fvo-label">Histats Counter Code</label>';
            echo '<textarea name="tracking_histats_code" rows="3" class="fvo-textarea">' . esc_textarea($histats) . '</textarea>';
            echo '<p class="fvo-desc">Paste Histats counter script code here.</p></div>';
            
            fvo_textarea_raw('tracking_google_analytics', 'Google Analytics / Tag Manager', 'Paste GA or GTM code here.');
            fvo_textarea_raw('tracking_header_scripts', 'Custom Header Scripts', 'Scripts loaded in &lt;head&gt;.');
            fvo_textarea_raw('tracking_footer_scripts', 'Custom Footer Scripts', 'Scripts loaded before &lt;/body&gt;.');
            ?>
        </div>
    </div>
<?php }

function flavor_render_tab_advanced() { ?>
    <div class="fvo-page-title">
        <h1>Advanced & Performance</h1>
        <p>Configure speed optimizations, security hardening, and maintenance protocols.</p>
    </div>

    <div class="fvo-card">
        <div class="fvo-card-header">
            <div class="fvo-card-header-left">
                <span class="material-symbols-outlined">speed</span>
                <h3>Speed Optimization</h3>
            </div>
            <span class="fvo-badge fvo-badge-green">Recommended</span>
        </div>
        <div class="fvo-card-body">
            <?php
            fvo_toggle('flavor_enable_minify', 'Minify HTML & Resources', 'Strips whitespace from HTML to reduce page weight.', true);
            fvo_toggle('flavor_remove_wp_version', 'Hide WordPress Version', 'Removes the WP generator meta tag for security.', true);
            fvo_toggle('flavor_remove_query_strings', 'Remove Query Strings', 'Remove version query strings from CSS/JS for better caching.', true);
            ?>
        </div>
    </div>

    <div class="fvo-card">
        <div class="fvo-card-header">
            <div class="fvo-card-header-left">
                <span class="material-symbols-outlined">construction</span>
                <h3>Maintenance Mode</h3>
            </div>
            <div><?php fvo_toggle_inline('flavor_maintenance_mode', false); ?></div>
        </div>
        <div class="fvo-card-body">
            <div class="fvo-grid-2">
                <?php
                fvo_text('flavor_maintenance_title', 'Maintenance Title', '', 'Sedang Maintenance');
                fvo_text('flavor_maintenance_timer_date', 'Target End Time', 'Format: YYYY-MM-DDTHH:MM:SS', '', 'datetime-local');
                fvo_color('flavor_maintenance_bg_color', 'Background Color', '#0f172a');
                fvo_color('flavor_maintenance_accent', 'Accent Color', '#6366f1');
                ?>
            </div>
            <?php
            fvo_textarea('flavor_maintenance_message', 'Maintenance Message', '', 3);
            fvo_toggle('flavor_maintenance_timer', 'Show Countdown Timer', 'Display countdown timer on maintenance page.');
            ?>

            <div class="fvo-alert fvo-alert-warning" style="margin-top:16px;margin-bottom:0;">
                <span class="material-symbols-outlined">warning</span>
                <div><strong>Attention!</strong> Enabling maintenance mode returns a 503 status to search engines. Use only for short periods to avoid SEO penalties.</div>
            </div>
        </div>
    </div>
<?php }

// Inline toggle (for card headers)
function fvo_toggle_inline($name, $default = false) {
    $val = get_theme_mod($name, $default);
    echo '<label class="fvo-toggle"><input type="checkbox" name="' . esc_attr($name) . '" value="1"' . checked($val, true, false) . '><span class="fvo-toggle-slider"></span></label>';
}
