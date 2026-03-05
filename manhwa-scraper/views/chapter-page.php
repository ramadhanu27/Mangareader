<?php
/**
 * Chapter Scraper Page View
 */

if (!defined('ABSPATH')) {
    exit;
}

$manhwa_manager_active = post_type_exists('manhwa');

// Get available sources from scraper manager
$available_sources = [];
$source_stats = ['unknown' => 0];
if (class_exists('MWS_Scraper_Manager')) {
    $available_sources = MWS_Scraper_Manager::get_instance()->get_sources_info();
    // Initialize source stats
    foreach ($available_sources as $src) {
        $source_stats[$src['id']] = 0;
    }
    
    // Count manhwa per source
    if ($manhwa_manager_active) {
        $all_manhwa = get_posts([
            'post_type' => 'manhwa',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ]);
        
        foreach ($all_manhwa as $manhwa_id) {
            $source_url = get_post_meta($manhwa_id, '_mws_source_url', true);
            $detected_source = 'unknown';
            
            if (!empty($source_url)) {
                foreach ($available_sources as $src) {
                    $src_id = $src['id'];
                    // Check base URL or source name in URL
                    if (strpos($source_url, $src_id) !== false) {
                        $detected_source = $src_id;
                        break;
                    }
                    // Also check base_url if available
                    if (!empty($src['base_url']) && strpos($source_url, parse_url($src['base_url'], PHP_URL_HOST)) !== false) {
                        $detected_source = $src_id;
                        break;
                    }
                }
            }
            
            if (isset($source_stats[$detected_source])) {
                $source_stats[$detected_source]++;
            } else {
                $source_stats['unknown']++;
            }
        }
    }
}
?>

<div class="wrap mws-wrap">
    <h1 class="mws-title">
        <span class="dashicons dashicons-images-alt2"></span>
        <?php esc_html_e('Chapter Image Scraper', 'manhwa-scraper'); ?>
    </h1>
    
    <div class="mws-chapter-page">
        <div class="mws-row">
            <!-- Scrape Form -->
            <div class="mws-col-6">
                <div class="mws-card">
                    <h2><?php esc_html_e('Scrape Chapter Images', 'manhwa-scraper'); ?></h2>
                    <p class="description">
                        <?php esc_html_e('Enter a chapter URL to scrape all images from that chapter.', 'manhwa-scraper'); ?>
                    </p>
                    
                    <form id="mws-chapter-form">
                        <table class="form-table">
                            <tr>
                                <th><label for="mws-chapter-url"><?php esc_html_e('Chapter URL', 'manhwa-scraper'); ?></label></th>
                                <td>
                                    <input type="url" id="mws-chapter-url" name="url" class="large-text" 
                                           placeholder="https://manhwaku.id/manga-title-chapter-1/" required>
                                    <p class="description">
                                        <?php esc_html_e('Example: https://manhwaku.id/solo-leveling-chapter-1/', 'manhwa-scraper'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary" id="mws-chapter-scrape-btn">
                                <span class="dashicons dashicons-search" style="margin-top: 4px;"></span>
                                <?php esc_html_e('Scrape Chapter Images', 'manhwa-scraper'); ?>
                            </button>
                            <span class="spinner" id="mws-chapter-spinner"></span>
                        </p>
                    </form>
                </div>
            </div>
            
            <!-- Info Card -->
            <div class="mws-col-6">
                <div class="mws-card">
                    <h2><?php esc_html_e('How it works', 'manhwa-scraper'); ?></h2>
                    <ul style="list-style: disc; margin-left: 20px;">
                        <li><?php esc_html_e('Enter a chapter URL from a supported source', 'manhwa-scraper'); ?></li>
                        <li><?php esc_html_e('The scraper will extract all page images', 'manhwa-scraper'); ?></li>
                        <li><?php esc_html_e('Preview the images before saving', 'manhwa-scraper'); ?></li>
                        <?php if ($manhwa_manager_active): ?>
                        <li><strong><?php esc_html_e('Save directly to Manhwa Manager post', 'manhwa-scraper'); ?></strong></li>
                        <?php endif; ?>
                    </ul>
                    
                    <?php if (!$manhwa_manager_active): ?>
                    <p class="notice notice-warning" style="padding: 10px;">
                        <?php esc_html_e('Manhwa Manager plugin is not active. Install and activate it to save chapters to posts.', 'manhwa-scraper'); ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Bulk Chapter Scraping Section -->
        <?php if ($manhwa_manager_active): ?>
        <div class="mws-card" style="margin-top: 20px;">
            <h2>
                ⬇️
                <?php esc_html_e('Bulk Scrape Chapters', 'manhwa-scraper'); ?>
            </h2>
            <p class="description">
                <?php esc_html_e('Select a manhwa, load its chapters, then scrape images in bulk.', 'manhwa-scraper'); ?>
            </p>
            
            <!-- ── Filter Row ── -->
            <div class="mws-bulk-filters-row">
                <div class="mws-filter-group">
                    <label class="mws-filter-label"><?php esc_html_e('Status', 'manhwa-scraper'); ?></label>
                    <select id="mws-bulk-chapter-status-filter">
                        <option value="all"><?php esc_html_e('📋 All Manhwa', 'manhwa-scraper'); ?></option>
                        <option value="need-images"><?php esc_html_e('🔴 Need Images', 'manhwa-scraper'); ?></option>
                        <option value="missing-external"><?php esc_html_e('🟣 Missing External', 'manhwa-scraper'); ?></option>
                        <option value="has-external"><?php esc_html_e('🟡 Has External', 'manhwa-scraper'); ?></option>
                        <option value="partial-download"><?php esc_html_e('🟠 Partial Download', 'manhwa-scraper'); ?></option>
                        <option value="all-downloaded"><?php esc_html_e('🟢 All Downloaded', 'manhwa-scraper'); ?></option>
                        <option value="no-chapters"><?php esc_html_e('⚪ No Chapters', 'manhwa-scraper'); ?></option>
                    </select>
                    <span id="mws-filter-count" class="mws-filter-count-badge"></span>
                </div>
                <div class="mws-filter-group">
                    <label class="mws-filter-label"><?php esc_html_e('Source', 'manhwa-scraper'); ?></label>
                    <select id="mws-bulk-source-filter">
                        <option value="all"><?php esc_html_e('🌐 All Sources', 'manhwa-scraper'); ?></option>
                        <?php foreach ($available_sources as $source): ?>
                        <option value="<?php echo esc_attr($source['id']); ?>">
                            <?php echo esc_html($source['name']); ?>
                        </option>
                        <?php endforeach; ?>
                        <option value="unknown"><?php esc_html_e('❓ Unknown', 'manhwa-scraper'); ?></option>
                    </select>
                    <span id="mws-source-filter-count" class="mws-filter-count-badge"></span>
                </div>
            </div>

            <!-- ── Search + Select ── -->
            <div class="mws-bulk-select-area">
                <input type="text" id="mws-bulk-manhwa-filter" placeholder="<?php esc_attr_e('🔍 Search by title...', 'manhwa-scraper'); ?>" class="mws-search-input">
                <select id="mws-bulk-manhwa" class="mws-manhwa-select">
                    <option value=""><?php esc_html_e('-- Select Manhwa --', 'manhwa-scraper'); ?></option>
                    <?php
                    $upload_dir = wp_upload_dir();
                    
                    $manhwa_posts = get_posts([
                        'post_type' => 'manhwa',
                        'posts_per_page' => -1,
                        'orderby' => 'title',
                        'order' => 'ASC'
                    ]);
                    
                    $stats = ['total' => 0, 'need-images' => 0, 'missing-external' => 0, 'has-external' => 0, 'partial-download' => 0, 'all-downloaded' => 0, 'no-chapters' => 0];
                    $source_stats = [];
                    
                    foreach ($manhwa_posts as $manhwa) {
                        $chapters = get_post_meta($manhwa->ID, '_manhwa_chapters', true);
                        $ch_count = is_array($chapters) ? count($chapters) : 0;
                        
                        $source_url = get_post_meta($manhwa->ID, '_mws_source_url', true);
                        $manhwa_source = 'unknown';
                        if (!empty($source_url)) {
                            if (strpos($source_url, 'manhwaku') !== false) {
                                $manhwa_source = 'manhwaku';
                            } elseif (strpos($source_url, 'asura') !== false || strpos($source_url, 'asuratoon') !== false) {
                                $manhwa_source = 'asura';
                            } elseif (strpos($source_url, 'komikcast') !== false) {
                                $manhwa_source = 'komikcast';
                            } elseif (strpos($source_url, 'manhwaland') !== false) {
                                $manhwa_source = 'manhwaland';
                            } elseif (strpos($source_url, 'manhwaindo') !== false) {
                                $manhwa_source = 'manhwaindo';
                            } elseif (strpos($source_url, 'mangasusuku') !== false || strpos($source_url, 'mangasusu') !== false) {
                                $manhwa_source = 'mangasusuku';
                            }
                        }
                        
                        if (!isset($source_stats[$manhwa_source])) {
                            $source_stats[$manhwa_source] = 0;
                        }
                        $source_stats[$manhwa_source]++;
                        
                        $with_images = 0;
                        $with_local = 0;
                        
                        if (is_array($chapters)) {
                            foreach ($chapters as $ch) {
                                if (!empty($ch['images']) && is_array($ch['images'])) {
                                    $with_images++;
                                    $first_img = $ch['images'][0];
                                    $img_url = is_array($first_img) ? ($first_img['url'] ?? $first_img['src'] ?? '') : $first_img;
                                    if (strpos($img_url, '/wp-content/uploads/manhwa/') !== false) {
                                        $with_local++;
                                    }
                                }
                            }
                        }
                        
                        $status_type = 'no-chapters';
                        $status_icon = '⚪';
                        $status_text = 'No Chapters';
                        
                        if ($ch_count > 0) {
                            if ($with_local == $ch_count && $ch_count > 0) {
                                $status_type = 'all-downloaded';
                                $status_icon = '🟢';
                                $status_text = 'All Downloaded';
                            } elseif ($with_local > 0) {
                                $status_type = 'partial-download';
                                $status_icon = '🟠';
                                $status_text = $with_local . '/' . $ch_count . ' Downloaded';
                            } elseif ($with_images > 0 && $with_images < $ch_count) {
                                $status_type = 'missing-external';
                                $status_icon = '🟣';
                                $missing = $ch_count - $with_images;
                                $status_text = $missing . ' ch missing images';
                            } elseif ($with_images == $ch_count) {
                                $status_type = 'has-external';
                                $status_icon = '🟡';
                                $status_text = $with_images . '/' . $ch_count . ' External';
                            } else {
                                $status_type = 'need-images';
                                $status_icon = '🔴';
                                $status_text = $ch_count . ' ch - Need Images';
                            }
                        }
                        
                        $stats['total']++;
                        $stats[$status_type]++;
                        
                        $source_icons = [
                            'manhwaku' => '📘', 'asura' => '📙', 'komikcast' => '📗',
                            'manhwaland' => '📕', 'manhwaindo' => '📓', 'mangasusuku' => '📒', 'unknown' => '❓'
                        ];
                        $source_icon = $source_icons[$manhwa_source] ?? '❓';
                        
                        echo '<option value="' . $manhwa->ID . '" data-title="' . esc_attr(strtolower($manhwa->post_title)) . '" data-local="' . $with_local . '" data-total="' . $ch_count . '" data-images="' . $with_images . '" data-status="' . $status_type . '" data-source="' . $manhwa_source . '">';
                        echo $status_icon . ' ' . $source_icon . ' ' . esc_html($manhwa->post_title) . ' (' . $ch_count . ' ch) - ' . $status_text;
                        echo '</option>';
                    }
                    ?>
                </select>
            </div>

            <!-- ── Stats Pills ── -->
            <div class="mws-stats-row">
                <div class="mws-stat-pill mws-stat-red">🔴 <strong><?php echo $stats['need-images']; ?></strong> need</div>
                <div class="mws-stat-pill mws-stat-purple">🟣 <strong><?php echo $stats['missing-external']; ?></strong> missing</div>
                <div class="mws-stat-pill mws-stat-yellow">🟡 <strong><?php echo $stats['has-external']; ?></strong> ext</div>
                <div class="mws-stat-pill mws-stat-orange">🟠 <strong><?php echo $stats['partial-download']; ?></strong> partial</div>
                <div class="mws-stat-pill mws-stat-green">🟢 <strong><?php echo $stats['all-downloaded']; ?></strong> done</div>
                <div class="mws-stat-pill mws-stat-gray">⚪ <strong><?php echo $stats['no-chapters']; ?></strong> empty</div>
                <div class="mws-stats-divider"></div>
                <?php foreach ($available_sources as $source): 
                    $src_id = $source['id'];
                    $src_count = $source_stats[$src_id] ?? 0;
                    $src_icon = ['manhwaku' => '📘', 'asura' => '📙', 'komikcast' => '📗', 'manhwaland' => '📕', 'manhwaindo' => '📓', 'mangasusuku' => '📒'][$src_id] ?? '📄';
                ?>
                <div class="mws-stat-pill mws-stat-source"><?php echo $src_icon; ?> <strong><?php echo $src_count; ?></strong> <?php echo esc_html($source['name']); ?></div>
                <?php endforeach; ?>
                <div class="mws-stat-pill mws-stat-source">❓ <strong><?php echo $source_stats['unknown'] ?? 0; ?></strong> Unknown</div>
            </div>

            <!-- ── Settings Bar ── -->
            <div class="mws-bulk-settings-bar">
                <label class="mws-setting-item">
                    <input type="checkbox" id="mws-bulk-skip-existing" checked>
                    <span><?php esc_html_e('Skip existing', 'manhwa-scraper'); ?></span>
                </label>
                <label class="mws-setting-item mws-setting-checkbox">
                    <input type="checkbox" id="mws-bulk-download-local">
                    <span>⬇ <?php esc_html_e('Save to local', 'manhwa-scraper'); ?></span>
                </label>
                <label class="mws-setting-item">
                    <input type="checkbox" id="mws-bulk-skip-downloaded">
                    <span><?php esc_html_e('Skip downloaded', 'manhwa-scraper'); ?></span>
                </label>
                <div class="mws-setting-divider"></div>
                <label class="mws-setting-item">
                    <input type="checkbox" id="mws-bulk-parallel" checked>
                    <span style="color: var(--mws-primary-dark); font-weight: 600;"><?php esc_html_e('Parallel', 'manhwa-scraper'); ?></span>
                </label>
                <label class="mws-setting-item">
                    <input type="number" id="mws-bulk-batch-size" value="10" min="1" max="100">
                    <span><?php esc_html_e('at once', 'manhwa-scraper'); ?></span>
                </label>
                <label class="mws-setting-item">
                    <input type="number" id="mws-bulk-delay" value="2" min="1" max="10">
                    <span><?php esc_html_e('sec delay', 'manhwa-scraper'); ?></span>
                </label>
            </div>
            
            <!-- ── Chapter List Preview ── -->
            <div id="mws-bulk-chapter-list" style="display: none; margin-top: 18px;">
                <div class="mws-chapter-list-header">
                    <h3><?php esc_html_e('Chapters:', 'manhwa-scraper'); ?></h3>
                    <div class="mws-chapter-list-controls">
                        <label class="mws-setting-item">
                            <input type="checkbox" id="mws-bulk-select-all" checked>
                            <span><?php esc_html_e('All', 'manhwa-scraper'); ?></span>
                        </label>
                        <select id="mws-bulk-filter-status">
                            <option value="all"><?php esc_html_e('Show All', 'manhwa-scraper'); ?></option>
                            <option value="no-images"><?php esc_html_e('No Images', 'manhwa-scraper'); ?></option>
                            <option value="external"><?php esc_html_e('External', 'manhwa-scraper'); ?></option>
                            <option value="local"><?php esc_html_e('Downloaded', 'manhwa-scraper'); ?></option>
                        </select>
                    </div>
                </div>
                <div id="mws-bulk-chapters-preview" class="mws-chapters-preview-box">
                    <!-- Chapter list will be loaded here -->
                </div>
                <div class="mws-chapter-counts">
                    <span class="mws-count-item"><strong><?php esc_html_e('Total:', 'manhwa-scraper'); ?></strong> <span id="mws-bulk-total-count">0</span></span>
                    <span class="mws-count-item"><strong><?php esc_html_e('Scrape:', 'manhwa-scraper'); ?></strong> <span id="mws-bulk-scrape-count">0</span></span>
                    <span class="mws-count-item mws-count-ok">🟢 <span id="mws-bulk-downloaded-count">0</span></span>
                    <span class="mws-count-item mws-count-ext">🔵 <span id="mws-bulk-external-count">0</span></span>
                    <span class="mws-count-item mws-count-none">🔴 <span id="mws-bulk-noimages-count">0</span></span>
                </div>
            </div>
            
            <!-- ── Progress ── -->
            <div id="mws-bulk-progress" style="display: none; margin-top: 18px;">
                <!-- Live Stats -->
                <div class="mws-scrape-stats-bar">
                    <div class="mws-stat-chip mws-stat-progress">
                        <span class="mws-stat-icon">⏱</span>
                        <span id="mws-bulk-progress-count">0/0</span>
                    </div>
                    <div class="mws-stat-chip mws-stat-scraped">
                        <span class="mws-stat-icon">✅</span>
                        <span id="mws-bulk-stat-scraped">0</span> <?php esc_html_e('scraped', 'manhwa-scraper'); ?>
                    </div>
                    <div class="mws-stat-chip mws-stat-downloaded">
                        <span class="mws-stat-icon">⬇️</span>
                        <span id="mws-bulk-stat-downloaded">0</span> <?php esc_html_e('downloaded', 'manhwa-scraper'); ?>
                    </div>
                    <div class="mws-stat-chip mws-stat-images">
                        <span class="mws-stat-icon">🖼</span>
                        <span id="mws-bulk-stat-images">0</span> <?php esc_html_e('images', 'manhwa-scraper'); ?>
                    </div>
                    <div class="mws-stat-chip mws-stat-errors">
                        <span class="mws-stat-icon">⚠️</span>
                        <span id="mws-bulk-stat-errors">0</span> <?php esc_html_e('errors', 'manhwa-scraper'); ?>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mws-current-manhwa-card" style="margin-top: 0;">
                    <div class="mws-progress-track">
                        <div class="mws-progress-fill" id="mws-bulk-progress-bar" style="width: 0%;">
                            <span class="mws-progress-glow"></span>
                        </div>
                    </div>
                    <div class="mws-progress-footer">
                        <span class="mws-progress-percent" id="mws-bulk-progress-percent">0%</span>
                        <span class="mws-progress-elapsed" id="mws-bulk-elapsed-time"></span>
                    </div>
                </div>

                <!-- Terminal Log -->
                <div class="mws-scrape-terminal">
                    <div class="mws-terminal-header">
                        <div class="mws-terminal-dots">
                            <span class="mws-dot mws-dot-red"></span>
                            <span class="mws-dot mws-dot-yellow"></span>
                            <span class="mws-dot mws-dot-green"></span>
                        </div>
                        <span class="mws-terminal-title"><?php esc_html_e('Scrape Log', 'manhwa-scraper'); ?></span>
                        <button type="button" class="mws-terminal-clear" onclick="jQuery('#mws-bulk-status').empty();" title="Clear">✕</button>
                    </div>
                    <div id="mws-bulk-status" class="mws-terminal-body"></div>
                </div>
            </div>
            
            <!-- ── Actions ── -->
            <div class="mws-bulk-action-bar">
                <button type="button" class="button button-secondary" id="mws-bulk-load-chapters">
                    👁 <?php esc_html_e('Load Chapters', 'manhwa-scraper'); ?>
                </button>
                <button type="button" class="button button-primary" id="mws-bulk-start-scrape" disabled>
                    ⬇ <?php esc_html_e('Start Scraping', 'manhwa-scraper'); ?>
                </button>
                <button type="button" class="button" id="mws-bulk-download-external" disabled style="background: #ff9800; border-color: #e68900; color: white;" title="<?php esc_attr_e('Download all external images to local server', 'manhwa-scraper'); ?>">
                    ☁ <?php esc_html_e('Ext → Local', 'manhwa-scraper'); ?>
                </button>
                <button type="button" class="button button-secondary" id="mws-bulk-stop-scrape" style="display: none;">
                    ⏹ <?php esc_html_e('Stop', 'manhwa-scraper'); ?>
                </button>
            </div>
        </div>
        
        <!-- Scrape All Manhwa Chapters Section -->
        <div class="mws-card mws-card-bulk-all" style="margin-top: 20px; border: 2px solid #8B5CF6;">
            <h2 style="color: #8B5CF6;">
                🗄 
                <?php esc_html_e('Bulk Scrape All Manhwa', 'manhwa-scraper'); ?>
            </h2>
            <p class="description">
                <?php esc_html_e('Process all manhwa at once — scrape missing images and download to local server.', 'manhwa-scraper'); ?>
            </p>
            
            <!-- ── Filters Row ── -->
            <div class="mws-bulk-all-filters">
                <div class="mws-filter-group">
                    <label class="mws-filter-label"><?php esc_html_e('Status', 'manhwa-scraper'); ?></label>
                    <select id="mws-all-manhwa-filter">
                        <option value="need-images"><?php echo '🔴 ' . esc_html__('Need Images', 'manhwa-scraper') . ' (' . $stats['need-images'] . ')'; ?></option>
                        <option value="missing-external"><?php echo '🟣 ' . esc_html__('Missing External', 'manhwa-scraper') . ' (' . $stats['missing-external'] . ')'; ?></option>
                        <option value="scrape-only"><?php echo '🔵 ' . esc_html__('Scrape Only', 'manhwa-scraper') . ' (' . ($stats['need-images'] + $stats['missing-external']) . ')'; ?></option>
                        <option value="has-external"><?php echo '🟡 ' . esc_html__('Has External', 'manhwa-scraper') . ' (' . $stats['has-external'] . ')'; ?></option>
                        <option value="download-only"><?php echo '⬇️ ' . esc_html__('Download Only', 'manhwa-scraper') . ' (' . ($stats['has-external'] + $stats['partial-download']) . ')'; ?></option>
                        <option value="partial-download"><?php echo '🟠 ' . esc_html__('Partial Download', 'manhwa-scraper') . ' (' . $stats['partial-download'] . ')'; ?></option>
                        <option value="all"><?php echo '📋 ' . esc_html__('All Manhwa', 'manhwa-scraper') . ' (' . $stats['total'] . ')'; ?></option>
                    </select>
                </div>
                <div class="mws-filter-group">
                    <label class="mws-filter-label"><?php esc_html_e('Source', 'manhwa-scraper'); ?></label>
                    <select id="mws-all-source-filter">
                        <option value="all"><?php esc_html_e('🌐 All Sources', 'manhwa-scraper'); ?></option>
                        <?php foreach ($available_sources as $source): 
                            $src_id = $source['id'];
                            $src_count = $source_stats[$src_id] ?? 0;
                            $src_icon = ['manhwaku' => '📘', 'asura' => '📙', 'komikcast' => '📗', 'manhwaland' => '📕', 'manhwaindo' => '📓', 'mangasusuku' => '📒'][$src_id] ?? '📄';
                        ?>
                        <option value="<?php echo esc_attr($src_id); ?>">
                            <?php echo $src_icon . ' ' . esc_html($source['name']) . ' (' . $src_count . ')'; ?>
                        </option>
                        <?php endforeach; ?>
                        <option value="unknown"><?php echo '❓ ' . esc_html__('Unknown', 'manhwa-scraper') . ' (' . ($source_stats['unknown'] ?? 0) . ')'; ?></option>
                    </select>
                </div>
                <div class="mws-filter-group">
                    <label class="mws-filter-label"><?php esc_html_e('Max Chapters', 'manhwa-scraper'); ?></label>
                    <select id="mws-all-chapter-limit">
                        <option value="0"><?php esc_html_e('No Limit', 'manhwa-scraper'); ?></option>
                        <option value="50"><?php esc_html_e('< 50 ch', 'manhwa-scraper'); ?></option>
                        <option value="100" selected><?php esc_html_e('< 100 ch', 'manhwa-scraper'); ?></option>
                        <option value="200"><?php esc_html_e('< 200 ch', 'manhwa-scraper'); ?></option>
                        <option value="500"><?php esc_html_e('< 500 ch', 'manhwa-scraper'); ?></option>
                    </select>
                </div>
            </div>

            <!-- ── Settings Bar ── -->
            <div class="mws-bulk-all-settings">
                <label class="mws-setting-item mws-setting-checkbox">
                    <input type="checkbox" id="mws-all-download-local" checked>
                    <span>⬇ <?php esc_html_e('Save to local', 'manhwa-scraper'); ?></span>
                </label>
                <div class="mws-setting-divider"></div>
                <label class="mws-setting-item">
                    <input type="number" id="mws-all-delay" value="3" min="1" max="30">
                    <span><?php esc_html_e('sec delay', 'manhwa-scraper'); ?></span>
                </label>
                <label class="mws-setting-item">
                    <input type="number" id="mws-all-max-manhwa" value="50" min="1" max="500">
                    <span><?php esc_html_e('max manhwa', 'manhwa-scraper'); ?></span>
                </label>
                <label class="mws-setting-item">
                    <input type="number" id="mws-all-parallel-chapters" value="10" min="1" max="100">
                    <span><?php esc_html_e('parallel', 'manhwa-scraper'); ?></span>
                </label>
            </div>

            <!-- ── Actions + Preview ── -->
            <div class="mws-bulk-all-actions">
                <div class="mws-bulk-all-buttons">
                    <button type="button" class="button button-secondary" id="mws-all-preview-btn">
                        👁 <?php esc_html_e('Preview', 'manhwa-scraper'); ?>
                    </button>
                    <button type="button" class="button" id="mws-all-start-btn" disabled style="background: #8B5CF6; border-color: #7C3AED; color: white;">
                        ▶ <?php esc_html_e('Start Scraping', 'manhwa-scraper'); ?>
                    </button>
                    <button type="button" class="button button-secondary" id="mws-all-stop-btn" style="display: none;">
                        ⏹ <?php esc_html_e('Stop', 'manhwa-scraper'); ?>
                    </button>
                    <span class="spinner" id="mws-all-spinner"></span>
                </div>
                <div id="mws-all-preview" class="mws-bulk-all-preview">
                    <div class="mws-preview-empty">
                        <span>👁</span>
                        <span><?php esc_html_e('Click "Preview" to see matched manhwa', 'manhwa-scraper'); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- ── Progress Panel ── -->
            <div id="mws-all-progress" style="display: none; margin-top: 20px;">
                <!-- Live Stats Dashboard -->
                <div class="mws-scrape-stats-bar">
                    <div class="mws-stat-chip mws-stat-progress">
                        <span class="mws-stat-icon">⏱</span>
                        <span id="mws-all-manhwa-progress">0/0</span>
                    </div>
                    <div class="mws-stat-chip mws-stat-scraped">
                        <span class="mws-stat-icon">✅</span>
                        <span id="mws-stat-scraped-count">0</span> <?php esc_html_e('scraped', 'manhwa-scraper'); ?>
                    </div>
                    <div class="mws-stat-chip mws-stat-downloaded">
                        <span class="mws-stat-icon">⬇️</span>
                        <span id="mws-stat-downloaded-count">0</span> <?php esc_html_e('downloaded', 'manhwa-scraper'); ?>
                    </div>
                    <div class="mws-stat-chip mws-stat-images">
                        <span class="mws-stat-icon">🖼</span>
                        <span id="mws-stat-images-count">0</span> <?php esc_html_e('images', 'manhwa-scraper'); ?>
                    </div>
                    <div class="mws-stat-chip mws-stat-errors">
                        <span class="mws-stat-icon">⚠️</span>
                        <span id="mws-stat-errors-count">0</span> <?php esc_html_e('errors', 'manhwa-scraper'); ?>
                    </div>
                </div>

                <!-- Current Manhwa Card -->
                <div class="mws-current-manhwa-card">
                    <div class="mws-current-header">
                        <div class="mws-current-info">
                            <span class="mws-current-label"><?php esc_html_e('Now processing', 'manhwa-scraper'); ?></span>
                            <span class="mws-current-title" id="mws-all-current-manhwa">-</span>
                        </div>
                        <div class="mws-current-badge" id="mws-current-chapter-badge" style="display:none;">
                            <span>📖</span>
                            <span id="mws-current-chapter-text">-</span>
                        </div>
                    </div>
                    <div class="mws-progress-track">
                        <div class="mws-progress-fill" id="mws-all-progress-bar" style="width: 0%;">
                            <span class="mws-progress-glow"></span>
                        </div>
                    </div>
                    <div class="mws-progress-footer">
                        <span class="mws-progress-percent" id="mws-all-progress-percent">0%</span>
                        <span class="mws-progress-elapsed" id="mws-all-elapsed-time"></span>
                    </div>
                </div>

                <!-- Terminal-Style Log -->
                <div class="mws-scrape-terminal">
                    <div class="mws-terminal-header">
                        <div class="mws-terminal-dots">
                            <span class="mws-dot mws-dot-red"></span>
                            <span class="mws-dot mws-dot-yellow"></span>
                            <span class="mws-dot mws-dot-green"></span>
                        </div>
                        <span class="mws-terminal-title"><?php esc_html_e('Scrape Log', 'manhwa-scraper'); ?></span>
                        <button type="button" class="mws-terminal-clear" onclick="jQuery('#mws-all-status').empty();" title="Clear">✕</button>
                    </div>
                    <div id="mws-all-status" class="mws-terminal-body"></div>
                </div>
            </div>
        </div>
        
        <!-- URL Migration Tool -->
        <div class="mws-card" style="margin-top: 20px; border: 2px solid #dc3232;">
            <h2 style="color: #dc3232;">
                <span class="dashicons dashicons-update" style="color: #dc3232;"></span>
                <?php esc_html_e('URL Migration Tool', 'manhwa-scraper'); ?>
            </h2>
            <p class="description">
                <?php esc_html_e('Replace old chapter URLs with new domain. Useful when source website changes their domain.', 'manhwa-scraper'); ?>
            </p>
            
            <div class="mws-row" style="margin-top: 15px;">
                <div class="mws-col-6">
                    <label style="font-weight: 600; display: block; margin-bottom: 8px;">
                        <?php esc_html_e('Find URL Pattern:', 'manhwa-scraper'); ?>
                    </label>
                    <input type="text" id="mws-migrate-find" class="large-text" 
                           placeholder="https://komikcast05.com" 
                           value="https://komikcast05.com"
                           style="max-width: 400px;">
                    <p class="description"><?php esc_html_e('Enter the old domain/URL pattern to find', 'manhwa-scraper'); ?></p>
                </div>
                <div class="mws-col-6">
                    <label style="font-weight: 600; display: block; margin-bottom: 8px;">
                        <?php esc_html_e('Replace With:', 'manhwa-scraper'); ?>
                    </label>
                    <input type="text" id="mws-migrate-replace" class="large-text" 
                           placeholder="https://v1.komikcast05.com" 
                           value="https://v1.komikcast05.com"
                           style="max-width: 400px;">
                    <p class="description"><?php esc_html_e('Enter the new domain/URL to replace with', 'manhwa-scraper'); ?></p>
                </div>
            </div>
            
            <div style="margin-top: 15px;">
                <label style="display: inline-flex; align-items: center; gap: 5px;">
                    <input type="checkbox" id="mws-migrate-source-url" checked>
                    <?php esc_html_e('Also update manhwa source URL (_mws_source_url)', 'manhwa-scraper'); ?>
                </label>
            </div>
            
            <!-- Preview Results -->
            <div id="mws-migrate-preview" style="margin-top: 15px; display: none;">
                <div style="background: #fef8e6; border: 1px solid #f0c36d; border-radius: 4px; padding: 15px;">
                    <h4 style="margin: 0 0 10px 0; color: #826200;">
                        <span class="dashicons dashicons-warning"></span>
                        <?php esc_html_e('Preview - Chapters to be updated:', 'manhwa-scraper'); ?>
                    </h4>
                    <div id="mws-migrate-preview-content" style="max-height: 200px; overflow-y: auto; font-size: 12px;"></div>
                    <div id="mws-migrate-stats" style="margin-top: 10px; font-weight: bold;"></div>
                </div>
            </div>
            
            <!-- Progress -->
            <div id="mws-migrate-progress" style="margin-top: 15px; display: none;">
                <div style="background: #f0f0f0; border-radius: 4px; height: 25px; overflow: hidden;">
                    <div id="mws-migrate-progress-bar" style="background: linear-gradient(90deg, #dc3232, #a02020); height: 100%; width: 0%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 12px;">
                        0%
                    </div>
                </div>
                <div id="mws-migrate-status" style="margin-top: 5px; font-size: 12px; color: #666;"></div>
            </div>
            
            <p class="submit" style="margin-top: 15px;">
                <button type="button" class="button button-secondary" id="mws-migrate-scan-btn">
                    <span class="dashicons dashicons-search" style="margin-top: 4px;"></span>
                    <?php esc_html_e('Scan for Old URLs', 'manhwa-scraper'); ?>
                </button>
                <button type="button" class="button" id="mws-migrate-execute-btn" disabled style="background: #dc3232; border-color: #a02020; color: white;">
                    <span class="dashicons dashicons-update" style="margin-top: 4px;"></span>
                    <?php esc_html_e('Execute Migration', 'manhwa-scraper'); ?>
                </button>
                <span class="spinner" id="mws-migrate-spinner"></span>
            </p>
        </div>
        
        <!-- Fix Broken Images Section -->
        <div class="mws-card" style="margin-top: 20px; border: 2px solid #ff6b6b;">
            <h2 style="color: #ff6b6b;">
                <span class="dashicons dashicons-warning" style="color: #ff6b6b;"></span>
                <?php esc_html_e('Fix Broken Images', 'manhwa-scraper'); ?>
            </h2>
            <p class="description">
                <?php esc_html_e('Scan and fix broken/inaccessible chapter images. This will detect external images that cannot be loaded and re-scrape them from the source.', 'manhwa-scraper'); ?>
            </p>
            
            <div class="mws-row" style="margin-top: 15px;">
                <div class="mws-col-6">
                    <label style="font-weight: 600; display: block; margin-bottom: 8px;">
                        <?php esc_html_e('Select Manhwa to Scan:', 'manhwa-scraper'); ?>
                    </label>
                    <select id="mws-broken-manhwa" style="width: 100%; max-width: 400px; padding: 8px;">
                        <option value=""><?php esc_html_e('-- Select Manhwa --', 'manhwa-scraper'); ?></option>
                        <option value="all"><?php esc_html_e('🔍 Scan All Manhwa (Quick Check)', 'manhwa-scraper'); ?></option>
                        <?php
                        foreach ($manhwa_posts as $manhwa) {
                            $chapters = get_post_meta($manhwa->ID, '_manhwa_chapters', true);
                            $ch_count = is_array($chapters) ? count($chapters) : 0;
                            if ($ch_count > 0) {
                                echo '<option value="' . $manhwa->ID . '">' . esc_html($manhwa->post_title) . ' (' . $ch_count . ' ch)</option>';
                            }
                        }
                        ?>
                    </select>
                    
                    <div style="margin-top: 15px;">
                        <label style="display: inline-flex; align-items: center; gap: 5px;">
                            <input type="checkbox" id="mws-broken-download-local" checked>
                            <strong style="color: #ff6b6b;"><?php esc_html_e('Download fixed images to local server', 'manhwa-scraper'); ?></strong>
                        </label>
                    </div>
                    
                    <div style="margin-top: 10px;">
                        <label style="display: inline-flex; align-items: center; gap: 5px;">
                            <?php esc_html_e('Check', 'manhwa-scraper'); ?>
                            <select id="mws-broken-sample-size" style="width: 80px;">
                                <option value="1">1</option>
                                <option value="3" selected>3</option>
                                <option value="5">5</option>
                                <option value="0"><?php esc_html_e('All', 'manhwa-scraper'); ?></option>
                            </select>
                            <?php esc_html_e('images per chapter', 'manhwa-scraper'); ?>
                        </label>
                    </div>
                </div>
                
                <div class="mws-col-6">
                    <div id="mws-broken-stats" style="padding: 15px; background: #fff5f5; border-radius: 4px; border: 1px solid #ffcdd2;">
                        <h4 style="margin: 0 0 10px 0; color: #c62828;">
                            <span class="dashicons dashicons-info"></span>
                            <?php esc_html_e('Scan Results', 'manhwa-scraper'); ?>
                        </h4>
                        <p style="margin: 0; color: #666;"><?php esc_html_e('Click "Scan for Broken Images" to start scanning.', 'manhwa-scraper'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Broken Chapters List -->
            <div id="mws-broken-chapters-list" style="display: none; margin-top: 15px;">
                <h3><?php esc_html_e('Broken Chapters Found:', 'manhwa-scraper'); ?></h3>
                <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; background: #fff;">
                    <table class="widefat striped" style="margin: 0;">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="mws-broken-select-all" checked></th>
                                <th><?php esc_html_e('Chapter', 'manhwa-scraper'); ?></th>
                                <th><?php esc_html_e('Images', 'manhwa-scraper'); ?></th>
                                <th><?php esc_html_e('Error', 'manhwa-scraper'); ?></th>
                                <th><?php esc_html_e('Status', 'manhwa-scraper'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="mws-broken-chapters-tbody">
                            <!-- Will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Fix Progress -->
            <div id="mws-broken-progress" style="display: none; margin-top: 15px;">
                <h3><?php esc_html_e('Fixing Progress:', 'manhwa-scraper'); ?></h3>
                <div style="background: #f0f0f0; border-radius: 4px; height: 25px; overflow: hidden; margin-bottom: 10px;">
                    <div id="mws-broken-progress-bar" style="background: linear-gradient(90deg, #ff6b6b, #ee5a5a); height: 100%; width: 0%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 12px;">
                        0%
                    </div>
                </div>
                <div id="mws-broken-status" style="font-size: 12px; color: #666;"></div>
            </div>
            
            <p class="submit" style="margin-top: 15px;">
                <button type="button" class="button button-secondary" id="mws-broken-scan-btn">
                    <span class="dashicons dashicons-search" style="margin-top: 4px;"></span>
                    <?php esc_html_e('Scan for Broken Images', 'manhwa-scraper'); ?>
                </button>
                <button type="button" class="button" id="mws-broken-fix-btn" disabled style="background: #ff6b6b; border-color: #ee5a5a; color: white;">
                    <span class="dashicons dashicons-admin-tools" style="margin-top: 4px;"></span>
                    <?php esc_html_e('Fix Selected Chapters', 'manhwa-scraper'); ?>
                </button>
                <button type="button" class="button button-secondary" id="mws-broken-stop-btn" style="display: none;">
                    <span class="dashicons dashicons-no" style="margin-top: 4px;"></span>
                    <?php esc_html_e('Stop', 'manhwa-scraper'); ?>
                </button>
                <span class="spinner" id="mws-broken-spinner"></span>
            </p>
        </div>
        <?php endif; ?>
        
        <!-- Results Section -->
        <div class="mws-card" id="mws-chapter-results" style="display: none;">
            <div class="mws-chapter-header">
                <div>
                    <h2 id="mws-chapter-title" style="margin-bottom: 5px;"></h2>
                    <div class="mws-chapter-meta">
                        <span class="mws-badge mws-badge-primary" id="mws-chapter-number"></span>
                        <span id="mws-chapter-image-count"></span>
                    </div>
                </div>
                
                <!-- Save to Manhwa Section -->
                <?php if ($manhwa_manager_active): ?>
                <div class="mws-save-section" style="display: flex; gap: 10px; align-items: center;">
                    <label for="mws-select-manhwa" style="white-space: nowrap; font-weight: 600;">
                        <?php esc_html_e('Save to:', 'manhwa-scraper'); ?>
                    </label>
                    <select id="mws-select-manhwa" style="min-width: 250px;">
                        <option value=""><?php esc_html_e('-- Select Manhwa --', 'manhwa-scraper'); ?></option>
                    </select>
                    <button type="button" class="button button-primary" id="mws-save-chapter-btn">
                        <span class="dashicons dashicons-saved" style="margin-top: 4px;"></span>
                        <?php esc_html_e('Save to Manhwa', 'manhwa-scraper'); ?>
                    </button>
                    <span class="spinner" id="mws-save-spinner"></span>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Auto-detected manhwa notice -->
            <div id="mws-detected-manhwa" class="notice notice-info" style="display: none; margin: 15px 0; padding: 10px;">
                <strong><?php esc_html_e('Detected Manhwa:', 'manhwa-scraper'); ?></strong>
                <span id="mws-detected-manhwa-title"></span>
                <a href="#" id="mws-detected-manhwa-link" target="_blank"><?php esc_html_e('Edit', 'manhwa-scraper'); ?></a>
            </div>
            
            <!-- Success message -->
            <div id="mws-save-success" class="notice notice-success" style="display: none; margin: 15px 0; padding: 10px;">
                <span id="mws-save-success-message"></span>
            </div>
            
            <!-- Navigation -->
            <div class="mws-chapter-nav" style="margin-bottom: 20px;">
                <button type="button" class="button" id="mws-prev-chapter" disabled>
                    <span class="dashicons dashicons-arrow-left-alt2" style="margin-top: 4px;"></span>
                    <?php esc_html_e('Previous Chapter', 'manhwa-scraper'); ?>
                </button>
                <button type="button" class="button" id="mws-next-chapter" disabled>
                    <?php esc_html_e('Next Chapter', 'manhwa-scraper'); ?>
                    <span class="dashicons dashicons-arrow-right-alt2" style="margin-top: 4px;"></span>
                </button>
            </div>
            
            <!-- Images Grid -->
            <div class="mws-images-grid" id="mws-images-grid">
                <!-- Images will be loaded here -->
            </div>
            
            <!-- Actions -->
            <div class="mws-chapter-actions" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                <button type="button" class="button button-primary" id="mws-export-images-json">
                    <span class="dashicons dashicons-download" style="margin-top: 4px;"></span>
                    <?php esc_html_e('Export JSON', 'manhwa-scraper'); ?>
                </button>
                <button type="button" class="button" id="mws-copy-images-urls">
                    <span class="dashicons dashicons-clipboard" style="margin-top: 4px;"></span>
                    <?php esc_html_e('Copy All URLs', 'manhwa-scraper'); ?>
                </button>
            </div>
        </div>
        
        <!-- JSON Preview -->
        <div class="mws-card" id="mws-chapter-json-section" style="display: none;">
            <h2><?php esc_html_e('Image URLs JSON', 'manhwa-scraper'); ?></h2>
            <textarea id="mws-chapter-json-output" class="large-text code" rows="10" readonly></textarea>
        </div>
    </div>
</div>

<style>
/* ── Google Fonts ── */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

/* ── Design Tokens ── */
:root {
    --mws-font: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --mws-primary: #6366F1;
    --mws-primary-dark: #4F46E5;
    --mws-primary-light: #818CF8;
    --mws-primary-bg: rgba(99,102,241,.06);
    --mws-success: #10B981;
    --mws-success-bg: rgba(16,185,129,.08);
    --mws-warning: #F59E0B;
    --mws-warning-bg: rgba(245,158,11,.08);
    --mws-danger: #EF4444;
    --mws-danger-bg: rgba(239,68,68,.06);
    --mws-purple: #8B5CF6;
    --mws-purple-dark: #7C3AED;
    --mws-purple-bg: rgba(139,92,246,.06);
    --mws-orange: #F97316;
    --mws-orange-bg: rgba(249,115,22,.06);
    --mws-gray-50: #F9FAFB;
    --mws-gray-100: #F3F4F6;
    --mws-gray-200: #E5E7EB;
    --mws-gray-300: #D1D5DB;
    --mws-gray-400: #9CA3AF;
    --mws-gray-500: #6B7280;
    --mws-gray-600: #4B5563;
    --mws-gray-700: #374151;
    --mws-gray-800: #1F2937;
    --mws-gray-900: #111827;
    --mws-radius-sm: 6px;
    --mws-radius: 10px;
    --mws-radius-lg: 14px;
    --mws-radius-xl: 18px;
    --mws-shadow-sm: 0 1px 2px rgba(0,0,0,.04), 0 1px 3px rgba(0,0,0,.06);
    --mws-shadow: 0 4px 6px -1px rgba(0,0,0,.06), 0 2px 4px -2px rgba(0,0,0,.06);
    --mws-shadow-md: 0 10px 15px -3px rgba(0,0,0,.07), 0 4px 6px -4px rgba(0,0,0,.05);
    --mws-shadow-lg: 0 20px 25px -5px rgba(0,0,0,.08), 0 8px 10px -6px rgba(0,0,0,.05);
    --mws-transition: all .2s cubic-bezier(.4,0,.2,1);
}

/* ── Base ── */
.mws-wrap {
    font-family: var(--mws-font) !important;
    max-width: 1400px;
}
.mws-wrap * {
    font-family: var(--mws-font) !important;
}

/* ── Page Title ── */
.mws-title {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 26px !important;
    font-weight: 800 !important;
    color: var(--mws-gray-900) !important;
    margin-bottom: 28px !important;
    letter-spacing: -0.5px;
}
.mws-title .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
    background: linear-gradient(135deg, var(--mws-primary), var(--mws-purple));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

/* ── Card System ── */
.mws-card {
    background: #fff;
    border: 1px solid var(--mws-gray-200);
    border-radius: var(--mws-radius-lg);
    padding: 28px;
    margin-bottom: 20px;
    box-shadow: var(--mws-shadow-sm);
    transition: var(--mws-transition);
    position: relative;
    overflow: hidden;
}
.mws-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--mws-primary), var(--mws-primary-light));
    opacity: 0;
    transition: opacity .3s;
}
.mws-card:hover {
    box-shadow: var(--mws-shadow);
    border-color: var(--mws-gray-300);
}
.mws-card:hover::before {
    opacity: 1;
}
/* Card with colored border */
.mws-card[style*="border: 2px solid #8B5CF6"],
.mws-card[style*="border:2px solid #8B5CF6"] {
    border: 1px solid rgba(139, 92, 246, .25) !important;
    background: linear-gradient(180deg, var(--mws-purple-bg) 0%, #fff 100px) !important;
}
.mws-card[style*="border: 2px solid #8B5CF6"]::before,
.mws-card[style*="border:2px solid #8B5CF6"]::before {
    background: linear-gradient(90deg, var(--mws-purple), var(--mws-primary-light)) !important;
    opacity: 1;
}
.mws-card[style*="border: 2px solid #dc3232"],
.mws-card[style*="border:2px solid #dc3232"] {
    border: 1px solid rgba(239, 68, 68, .2) !important;
    background: linear-gradient(180deg, var(--mws-danger-bg) 0%, #fff 100px) !important;
}
.mws-card[style*="border: 2px solid #dc3232"]::before,
.mws-card[style*="border:2px solid #dc3232"]::before {
    background: linear-gradient(90deg, var(--mws-danger), var(--mws-orange)) !important;
    opacity: 1;
}
.mws-card[style*="border: 2px solid #ff6b6b"],
.mws-card[style*="border:2px solid #ff6b6b"] {
    border: 1px solid rgba(249, 115, 22, .2) !important;
    background: linear-gradient(180deg, var(--mws-orange-bg) 0%, #fff 100px) !important;
}
.mws-card[style*="border: 2px solid #ff6b6b"]::before,
.mws-card[style*="border:2px solid #ff6b6b"]::before {
    background: linear-gradient(90deg, var(--mws-orange), var(--mws-warning)) !important;
    opacity: 1;
}

/* ── Card Headings ── */
.mws-card h2 {
    font-size: 18px !important;
    font-weight: 700 !important;
    color: var(--mws-gray-800) !important;
    margin: 0 0 8px 0 !important;
    padding: 0 !important;
    display: flex;
    align-items: center;
    gap: 10px;
    letter-spacing: -0.3px;
}
.mws-card h2 .dashicons {
    font-size: 22px;
    width: 22px;
    height: 22px;
}
.mws-card h3 {
    font-size: 15px !important;
    font-weight: 600 !important;
    color: var(--mws-gray-700);
    margin-top: 0;
}
.mws-card p.description {
    color: var(--mws-gray-500) !important;
    font-size: 13.5px !important;
    line-height: 1.6;
    margin-top: 0;
}

/* ── Grid Layout ── */
.mws-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 28px;
}
.mws-col-6 {
    min-width: 0;
}
@media (max-width: 960px) {
    .mws-row {
        grid-template-columns: 1fr;
    }
}

/* ── Form Controls ── */
.mws-card input[type="url"],
.mws-card input[type="text"],
.mws-card input[type="number"],
.mws-card select,
.mws-card textarea {
    font-family: var(--mws-font) !important;
    font-size: 13.5px !important;
    border: 1.5px solid var(--mws-gray-300) !important;
    border-radius: var(--mws-radius-sm) !important;
    padding: 10px 14px !important;
    transition: var(--mws-transition);
    background: #fff !important;
    color: var(--mws-gray-800) !important;
    box-shadow: var(--mws-shadow-sm);
    outline: none !important;
    height: auto !important;
    line-height: 1.5 !important;
}
.mws-card input[type="url"]:focus,
.mws-card input[type="text"]:focus,
.mws-card input[type="number"]:focus,
.mws-card select:focus,
.mws-card textarea:focus {
    border-color: var(--mws-primary) !important;
    box-shadow: 0 0 0 3px rgba(99,102,241,.12), var(--mws-shadow-sm) !important;
}
.mws-card input[type="number"] {
    width: 70px !important;
    text-align: center !important;
}
.mws-card select {
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236B7280' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E") !important;
    background-repeat: no-repeat !important;
    background-position: right 12px center !important;
    padding-right: 36px !important;
}

/* ── Form Labels ── */
.mws-card label {
    font-size: 13.5px;
    color: var(--mws-gray-700);
}
.mws-card label[style*="font-weight: 600"],
.mws-card label[style*="font-weight:600"] {
    font-size: 13px !important;
    font-weight: 600 !important;
    color: var(--mws-gray-700) !important;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* ── Checkboxes ── */
.mws-card input[type="checkbox"] {
    width: 18px !important;
    height: 18px !important;
    border-radius: 4px !important;
    border: 1.5px solid var(--mws-gray-300) !important;
    cursor: pointer;
    accent-color: var(--mws-primary);
    transition: var(--mws-transition);
}
.mws-card input[type="checkbox"]:checked {
    border-color: var(--mws-primary) !important;
}

/* ── Buttons ── */
.mws-card .button,
.mws-card .button-primary,
.mws-card .button-secondary,
.mws-chapter-page .submit .button {
    font-family: var(--mws-font) !important;
    font-size: 13px !important;
    font-weight: 600 !important;
    padding: 8px 20px !important;
    border-radius: var(--mws-radius-sm) !important;
    transition: var(--mws-transition);
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none !important;
    cursor: pointer;
    letter-spacing: 0.2px;
    line-height: 1.5 !important;
    height: auto !important;
    box-shadow: var(--mws-shadow-sm);
}
.mws-card .button .dashicons,
.mws-chapter-page .submit .button .dashicons {
    font-size: 16px !important;
    width: 16px !important;
    height: 16px !important;
    margin-top: 0 !important;
}
.mws-card .button-primary,
.mws-chapter-page .submit .button-primary {
    background: linear-gradient(135deg, var(--mws-primary), var(--mws-primary-dark)) !important;
    border: none !important;
    color: #fff !important;
    box-shadow: 0 2px 4px rgba(99,102,241,.3);
}
.mws-card .button-primary:hover,
.mws-chapter-page .submit .button-primary:hover {
    background: linear-gradient(135deg, var(--mws-primary-dark), #4338CA) !important;
    box-shadow: 0 4px 8px rgba(99,102,241,.35);
    transform: translateY(-1px);
}
.mws-card .button-secondary,
.mws-chapter-page .submit .button-secondary {
    background: #fff !important;
    border: 1.5px solid var(--mws-gray-300) !important;
    color: var(--mws-gray-700) !important;
}
.mws-card .button-secondary:hover,
.mws-chapter-page .submit .button-secondary:hover {
    background: var(--mws-gray-50) !important;
    border-color: var(--mws-gray-400) !important;
    transform: translateY(-1px);
}
.mws-card .button:disabled,
.mws-chapter-page .submit .button:disabled {
    opacity: .5 !important;
    cursor: not-allowed !important;
    transform: none !important;
}
/* Special colored buttons */
.mws-card .button[style*="background: #8B5CF6"],
.mws-card .button[style*="background:#8B5CF6"] {
    background: linear-gradient(135deg, var(--mws-purple), var(--mws-purple-dark)) !important;
    border: none !important;
    color: #fff !important;
    box-shadow: 0 2px 4px rgba(139,92,246,.3);
}
.mws-card .button[style*="background: #8B5CF6"]:hover,
.mws-card .button[style*="background:#8B5CF6"]:hover {
    box-shadow: 0 4px 8px rgba(139,92,246,.4);
    transform: translateY(-1px);
}
.mws-card .button[style*="background: #ff9800"],
.mws-card .button[style*="background:#ff9800"] {
    background: linear-gradient(135deg, var(--mws-warning), #D97706) !important;
    border: none !important;
    color: #fff !important;
    box-shadow: 0 2px 4px rgba(245,158,11,.3);
}
.mws-card .button[style*="background: #dc3232"],
.mws-card .button[style*="background:#dc3232"] {
    background: linear-gradient(135deg, var(--mws-danger), #DC2626) !important;
    border: none !important;
    color: #fff !important;
    box-shadow: 0 2px 4px rgba(239,68,68,.3);
}
.mws-card .button[style*="background: #ff6b6b"],
.mws-card .button[style*="background:#ff6b6b"] {
    background: linear-gradient(135deg, var(--mws-orange), #EA580C) !important;
    border: none !important;
    color: #fff !important;
    box-shadow: 0 2px 4px rgba(249,115,22,.3);
}

/* ── Progress Bars ── */
.mws-card div[style*="background: #f0f0f0"] {
    background: var(--mws-gray-100) !important;
    border-radius: var(--mws-radius) !important;
    overflow: hidden !important;
    box-shadow: inset 0 1px 3px rgba(0,0,0,.08);
}
#mws-bulk-progress-bar {
    background: linear-gradient(90deg, var(--mws-primary), var(--mws-primary-light)) !important;
    border-radius: var(--mws-radius) !important;
    transition: width .4s cubic-bezier(.4,0,.2,1) !important;
    font-size: 12px !important;
    font-weight: 700 !important;
    text-shadow: 0 1px 2px rgba(0,0,0,.2);
    position: relative;
    overflow: hidden;
}
#mws-bulk-progress-bar::after {
    content: '';
    position: absolute;
    top: 0; left: -100px; bottom: 0;
    width: 100px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.25), transparent);
    animation: mws-shimmer 2s infinite;
}
#mws-all-progress-bar {
    background: linear-gradient(90deg, var(--mws-purple), var(--mws-primary-light)) !important;
    border-radius: var(--mws-radius) !important;
    transition: width .4s cubic-bezier(.4,0,.2,1) !important;
    font-size: 12px !important;
    font-weight: 700 !important;
    text-shadow: 0 1px 2px rgba(0,0,0,.2);
    position: relative;
    overflow: hidden;
}
#mws-all-progress-bar::after {
    content: '';
    position: absolute;
    top: 0; left: -100px; bottom: 0;
    width: 100px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.25), transparent);
    animation: mws-shimmer 2s infinite;
}
#mws-migrate-progress-bar {
    background: linear-gradient(90deg, var(--mws-danger), var(--mws-orange)) !important;
    border-radius: var(--mws-radius) !important;
}
#mws-broken-progress-bar {
    background: linear-gradient(90deg, var(--mws-orange), var(--mws-warning)) !important;
    border-radius: var(--mws-radius) !important;
}
@keyframes mws-shimmer {
    0% { left: -100px; }
    100% { left: 100%; }
}

/* ── Status Log Boxes ── */
#mws-bulk-status,
#mws-all-status,
#mws-broken-status {
    background: var(--mws-gray-50) !important;
    border: 1px solid var(--mws-gray-200) !important;
    border-radius: var(--mws-radius-sm) !important;
    padding: 14px !important;
    font-family: 'Cascadia Code', 'Fira Code', 'JetBrains Mono', monospace !important;
    font-size: 12px !important;
    line-height: 1.8 !important;
}
#mws-bulk-status::-webkit-scrollbar,
#mws-all-status::-webkit-scrollbar,
#mws-broken-status::-webkit-scrollbar,
#mws-bulk-chapters-preview::-webkit-scrollbar {
    width: 6px;
}
#mws-bulk-status::-webkit-scrollbar-track,
#mws-all-status::-webkit-scrollbar-track,
#mws-broken-status::-webkit-scrollbar-track,
#mws-bulk-chapters-preview::-webkit-scrollbar-track {
    background: transparent;
}
#mws-bulk-status::-webkit-scrollbar-thumb,
#mws-all-status::-webkit-scrollbar-thumb,
#mws-broken-status::-webkit-scrollbar-thumb,
#mws-bulk-chapters-preview::-webkit-scrollbar-thumb {
    background: var(--mws-gray-300);
    border-radius: 3px;
}

/* ── Stats Bars ── */
.mws-card div[style*="background: #f5f5f5"][style*="font-size: 12px"],
.mws-card div[style*="background: #e8f4fc"] {
    background: var(--mws-gray-50) !important;
    border: 1px solid var(--mws-gray-200) !important;
    border-radius: var(--mws-radius-sm) !important;
    padding: 12px 16px !important;
    display: flex;
    flex-wrap: wrap;
    gap: 4px 16px;
    align-items: center;
    font-size: 12.5px !important;
}

/* ── Parallel Download Box ── */
.mws-card div[style*="background: #e7f3ff"] {
    background: var(--mws-primary-bg) !important;
    border: 1.5px solid rgba(99,102,241,.2) !important;
    border-radius: var(--mws-radius-sm) !important;
    padding: 12px 16px !important;
}

/* ── Preview Boxes ── */
#mws-all-preview,
#mws-bulk-chapters-preview {
    background: var(--mws-gray-50) !important;
    border: 1px solid var(--mws-gray-200) !important;
    border-radius: var(--mws-radius-sm) !important;
}
#mws-broken-stats {
    background: var(--mws-orange-bg) !important;
    border: 1px solid rgba(249,115,22,.15) !important;
    border-radius: var(--mws-radius-sm) !important;
}

/* ── Images Grid ── */
.mws-images-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 14px;
}
.mws-image-item {
    border: 1px solid var(--mws-gray-200);
    border-radius: var(--mws-radius);
    overflow: hidden;
    background: var(--mws-gray-50);
    transition: var(--mws-transition);
    box-shadow: var(--mws-shadow-sm);
}
.mws-image-item:hover {
    transform: translateY(-2px);
    box-shadow: var(--mws-shadow-md);
    border-color: var(--mws-primary-light);
}
.mws-image-item img {
    width: 100%;
    height: 160px;
    object-fit: cover;
    display: block;
    transition: transform .3s;
}
.mws-image-item:hover img {
    transform: scale(1.05);
}
.mws-image-item .mws-image-info {
    padding: 10px 12px;
    font-size: 12px;
    font-weight: 500;
    color: var(--mws-gray-600);
    background: #fff;
    border-top: 1px solid var(--mws-gray-100);
}

/* ── Chapter Header ── */
.mws-chapter-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}
.mws-chapter-meta {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 6px;
}
.mws-chapter-nav {
    display: flex;
    gap: 10px;
}

/* ── Badges ── */
.mws-badge-primary {
    background: linear-gradient(135deg, var(--mws-primary), var(--mws-primary-dark));
    color: #fff;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.3px;
}

/* ── Notices ── */
.mws-card .notice {
    border-radius: var(--mws-radius-sm) !important;
    border-left: 4px solid !important;
    margin: 16px 0 !important;
    padding: 12px 16px !important;
}
.mws-card .notice-info {
    background: var(--mws-primary-bg) !important;
    border-left-color: var(--mws-primary) !important;
}
.mws-card .notice-success {
    background: var(--mws-success-bg) !important;
    border-left-color: var(--mws-success) !important;
}
.mws-card .notice-warning {
    background: var(--mws-warning-bg) !important;
    border-left-color: var(--mws-warning) !important;
}

/* ── Tables ── */
.mws-card .widefat {
    border: none !important;
    border-radius: var(--mws-radius-sm) !important;
    overflow: hidden;
}
.mws-card .widefat thead th {
    background: var(--mws-gray-50) !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--mws-gray-500) !important;
    padding: 10px 14px !important;
    border-bottom: 1.5px solid var(--mws-gray-200) !important;
}
.mws-card .widefat td {
    padding: 10px 14px !important;
    font-size: 13px !important;
    color: var(--mws-gray-700);
    border-bottom: 1px solid var(--mws-gray-100) !important;
}

/* ── Spinner ── */
.mws-card .spinner,
.mws-chapter-page .spinner {
    float: none !important;
    margin: 0 !important;
    vertical-align: middle;
}

/* ── Migrate Preview ── */
#mws-migrate-preview > div {
    background: var(--mws-warning-bg) !important;
    border: 1px solid rgba(245,158,11,.2) !important;
    border-radius: var(--mws-radius-sm) !important;
}

/* ── Form Table Override ── */
.mws-card .form-table th {
    font-size: 13px !important;
    font-weight: 600 !important;
    color: var(--mws-gray-700);
    padding: 12px 14px 12px 0 !important;
    vertical-align: top;
}
.mws-card .form-table td {
    padding: 12px 0 !important;
}
.mws-card .form-table .description {
    color: var(--mws-gray-400) !important;
    font-size: 12px !important;
    margin-top: 6px;
}

/* ── Bulk Chapter Row ── */
.mws-bulk-chapter-row {
    transition: background .15s;
    border-radius: 4px;
    padding: 8px 10px !important;
}
.mws-bulk-chapter-row:hover {
    background: var(--mws-primary-bg) !important;
}

/* ── How it works list ── */
.mws-card ul {
    margin: 0;
    padding: 0;
    list-style: none !important;
}
.mws-card ul li {
    position: relative;
    padding: 6px 0 6px 24px;
    font-size: 13.5px;
    color: var(--mws-gray-600);
    line-height: 1.5;
}
.mws-card ul li::before {
    content: '';
    position: absolute;
    left: 0;
    top: 13px;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--mws-primary-light), var(--mws-primary));
}
.mws-card ul li strong {
    color: var(--mws-primary-dark);
}

/* ── Submit Row ── */
.mws-card p.submit,
.mws-chapter-page > .mws-card p.submit {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 20px !important;
    padding-top: 20px;
    border-top: 1px solid var(--mws-gray-100);
}

/* ── Chapter actions ── */
.mws-chapter-actions {
    display: flex;
    gap: 10px;
    margin-top: 24px !important;
    padding-top: 24px !important;
    border-top: 1px solid var(--mws-gray-200) !important;
}

/* ── Bulk Scrape: Filters Row ── */
.mws-bulk-filters-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-top: 20px;
    padding: 18px;
    background: var(--mws-gray-50);
    border: 1px solid var(--mws-gray-200);
    border-radius: var(--mws-radius);
}
.mws-filter-count-badge {
    font-size: 11px;
    color: var(--mws-gray-400);
    font-weight: 500;
}

/* ── Bulk Scrape: Search + Select ── */
.mws-bulk-select-area {
    margin-top: 14px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.mws-search-input {
    width: 100% !important;
    max-width: 100% !important;
}
.mws-manhwa-select {
    width: 100% !important;
    max-width: 100% !important;
}

/* ── Stats Pills ── */
.mws-stats-row {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 12px;
    align-items: center;
}
.mws-stat-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11.5px;
    padding: 4px 10px;
    border-radius: 16px;
    background: var(--mws-gray-100);
    color: var(--mws-gray-600);
    border: 1px solid var(--mws-gray-200);
    white-space: nowrap;
}
.mws-stat-pill strong {
    font-weight: 700;
    color: var(--mws-gray-800);
}
.mws-stat-red { background: var(--mws-danger-bg); border-color: rgba(239,68,68,.15); }
.mws-stat-purple { background: var(--mws-purple-bg); border-color: rgba(139,92,246,.15); }
.mws-stat-yellow { background: var(--mws-warning-bg); border-color: rgba(245,158,11,.15); }
.mws-stat-orange { background: var(--mws-orange-bg); border-color: rgba(249,115,22,.15); }
.mws-stat-green { background: var(--mws-success-bg); border-color: rgba(16,185,129,.15); }
.mws-stat-gray { background: var(--mws-gray-100); }
.mws-stat-source { background: var(--mws-primary-bg); border-color: rgba(99,102,241,.1); }
.mws-stats-divider {
    width: 1px;
    height: 20px;
    background: var(--mws-gray-300);
    margin: 0 4px;
}

/* ── Bulk Scrape: Settings Bar ── */
.mws-bulk-settings-bar {
    display: flex;
    align-items: center;
    gap: 18px;
    margin-top: 14px;
    padding: 12px 18px;
    background: var(--mws-primary-bg);
    border: 1px solid rgba(99,102,241,.15);
    border-radius: var(--mws-radius);
    flex-wrap: wrap;
}

/* ── Bulk Scrape: Chapter List ── */
.mws-chapter-list-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}
.mws-chapter-list-header h3 {
    margin: 0 !important;
}
.mws-chapter-list-controls {
    display: flex;
    align-items: center;
    gap: 12px;
}
.mws-chapters-preview-box {
    max-height: 280px;
    overflow-y: auto;
    padding: 12px;
}
.mws-chapter-counts {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 10px;
    padding: 10px 14px;
    background: var(--mws-gray-50);
    border: 1px solid var(--mws-gray-200);
    border-radius: var(--mws-radius-sm);
    font-size: 12.5px;
}
.mws-count-item {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    color: var(--mws-gray-600);
}
.mws-count-ok { color: var(--mws-success); }
.mws-count-ext { color: var(--mws-primary); }
.mws-count-none { color: var(--mws-danger); }

/* ── Bulk Scrape: Action Bar ── */
.mws-bulk-action-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 18px;
    padding-top: 18px;
    border-top: 1px solid var(--mws-gray-100);
}

/* ── Bulk All: Filters Row ── */
.mws-bulk-all-filters {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 16px;
    margin-top: 20px;
    padding: 18px;
    background: var(--mws-gray-50);
    border: 1px solid var(--mws-gray-200);
    border-radius: var(--mws-radius);
}
.mws-filter-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.mws-filter-label {
    font-size: 11px !important;
    font-weight: 700 !important;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: var(--mws-gray-400) !important;
}
.mws-filter-group select {
    width: 100% !important;
}

/* ── Bulk All: Settings Bar ── */
.mws-bulk-all-settings {
    display: flex;
    align-items: center;
    gap: 18px;
    margin-top: 14px;
    padding: 12px 18px;
    background: var(--mws-purple-bg);
    border: 1px solid rgba(139,92,246,.15);
    border-radius: var(--mws-radius);
    flex-wrap: wrap;
}
.mws-setting-item {
    display: inline-flex !important;
    align-items: center;
    gap: 6px;
    font-size: 13px !important;
    color: var(--mws-gray-600);
    cursor: pointer;
    white-space: nowrap;
}
.mws-setting-item input[type="number"] {
    width: 58px !important;
    padding: 6px 8px !important;
    font-size: 13px !important;
    text-align: center !important;
}
.mws-setting-item span {
    color: var(--mws-gray-600);
    font-size: 12.5px;
}
.mws-setting-checkbox {
    font-weight: 600 !important;
}
.mws-setting-checkbox span {
    color: var(--mws-purple-dark) !important;
    font-weight: 600;
    font-size: 13px !important;
}
.mws-setting-divider {
    width: 1px;
    height: 24px;
    background: rgba(139,92,246,.2);
}

/* ── Bulk All: Actions + Preview ── */
.mws-bulk-all-actions {
    margin-top: 16px;
}
.mws-bulk-all-buttons {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
}
.mws-bulk-all-preview {
    min-height: 60px;
    padding: 16px !important;
}
.mws-preview-empty {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--mws-gray-400);
    font-size: 13px;
}
.mws-preview-empty .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
    color: var(--mws-gray-300);
}

/* ── Bulk All: Progress Header ── */
.mws-progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}
.mws-progress-header h3 {
    margin: 0 !important;
}
.mws-progress-meta {
    display: flex;
    align-items: center;
    gap: 14px;
    font-size: 13px;
}
.mws-progress-current {
    color: var(--mws-gray-600);
    font-weight: 500;
}
.mws-progress-count {
    background: var(--mws-purple-bg);
    color: var(--mws-purple-dark);
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 700;
}

/* ── Responsive tweaks ── */
@media (max-width: 960px) {
    .mws-bulk-all-filters {
        grid-template-columns: 1fr;
    }
    .mws-bulk-filters-row {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 782px) {
    .mws-card {
        padding: 18px;
    }
    .mws-chapter-header {
        flex-direction: column;
    }
    .mws-save-section {
        flex-direction: column;
        align-items: flex-start !important;
    }
    .mws-bulk-all-filters {
        grid-template-columns: 1fr;
    }
    .mws-bulk-all-settings {
        flex-direction: column;
        align-items: flex-start;
    }
    .mws-bulk-filters-row {
        grid-template-columns: 1fr;
    }
    .mws-bulk-settings-bar {
        flex-direction: column;
        align-items: flex-start;
    }
    .mws-chapter-list-header {
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
    }
    .mws-setting-divider {
        width: 100%;
        height: 1px;
    }
    .mws-stats-divider {
        display: none;
    }
}

/* ═══════════════════════════════════════════
   SCRAPE PROGRESS PANEL
   ═══════════════════════════════════════════ */

/* ── Stats Dashboard ── */
.mws-scrape-stats-bar {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 14px;
}
.mws-stat-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    font-family: 'Inter', -apple-system, sans-serif;
}
.mws-stat-icon {
    font-size: 14px;
    line-height: 1;
    flex-shrink: 0;
}
.mws-stat-progress {
    background: rgba(99,102,241,.08);
    color: #6366F1;
}
.mws-stat-scraped {
    background: rgba(16,185,129,.08);
    color: #059669;
}
.mws-stat-downloaded {
    background: rgba(59,130,246,.08);
    color: #2563EB;
}
.mws-stat-images {
    background: rgba(139,92,246,.08);
    color: #7C3AED;
}
.mws-stat-errors {
    background: rgba(239,68,68,.06);
    color: #DC2626;
}

/* ── Current Manhwa Card ── */
.mws-current-manhwa-card {
    background: #fff;
    border: 1px solid #E5E7EB;
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 14px;
    box-shadow: 0 1px 3px rgba(0,0,0,.04);
}
.mws-current-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}
.mws-current-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
    flex: 1;
}
.mws-current-label {
    font-size: 11px;
    font-weight: 500;
    color: #9CA3AF;
    text-transform: uppercase;
    letter-spacing: .5px;
    font-family: 'Inter', sans-serif;
}
.mws-current-title {
    font-size: 14px;
    font-weight: 700;
    color: #1F2937;
    font-family: 'Inter', sans-serif;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.mws-current-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    background: rgba(139,92,246,.08);
    color: #7C3AED;
    border-radius: 16px;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
    font-family: 'Inter', sans-serif;
}

/* ── Progress Bar ── */
.mws-progress-track {
    height: 8px;
    background: #F3F4F6;
    border-radius: 10px;
    overflow: hidden;
    position: relative;
}
.mws-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #6366F1, #8B5CF6, #A78BFA);
    border-radius: 10px;
    transition: width .4s cubic-bezier(.4,0,.2,1);
    position: relative;
    overflow: hidden;
    min-width: 0;
}
.mws-progress-glow {
    position: absolute;
    top: 0; right: 0; bottom: 0;
    width: 60px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.3), transparent);
    animation: mws-progress-shine 1.5s ease-in-out infinite;
}
@keyframes mws-progress-shine {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(200%); }
}
.mws-progress-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 8px;
}
.mws-progress-percent {
    font-size: 13px;
    font-weight: 700;
    color: #6366F1;
    font-family: 'Inter', sans-serif;
}
.mws-progress-elapsed {
    font-size: 11px;
    color: #9CA3AF;
    font-family: 'Inter', sans-serif;
}

/* ── Terminal-Style Log ── */
.mws-scrape-terminal {
    border: 1px solid #E5E7EB;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,.04);
}
.mws-terminal-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 16px;
    background: #1F2937;
    color: #9CA3AF;
}
.mws-terminal-dots {
    display: flex;
    gap: 5px;
}
.mws-dot {
    width: 10px; height: 10px;
    border-radius: 50%;
}
.mws-dot-red { background: #EF4444; }
.mws-dot-yellow { background: #F59E0B; }
.mws-dot-green { background: #10B981; }
.mws-terminal-title {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .5px;
    flex: 1;
    font-family: 'Inter', sans-serif;
}
.mws-terminal-clear {
    background: none;
    border: none;
    color: #6B7280;
    cursor: pointer;
    padding: 2px;
    line-height: 1;
    transition: color .2s;
}
.mws-terminal-clear:hover { color: #EF4444; }
.mws-terminal-body {
    background: #111827;
    padding: 14px 16px;
    max-height: 350px;
    overflow-y: auto;
    font-family: 'SF Mono', 'Consolas', 'Monaco', 'Menlo', monospace;
    font-size: 12px;
    line-height: 1.7;
    scrollbar-width: thin;
    scrollbar-color: #374151 #111827;
}
.mws-terminal-body::-webkit-scrollbar {
    width: 6px;
}
.mws-terminal-body::-webkit-scrollbar-track {
    background: #111827;
}
.mws-terminal-body::-webkit-scrollbar-thumb {
    background: #374151;
    border-radius: 3px;
}

/* ── Log Entry Styles ── */
.mws-log-entry {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 3px 0;
    animation: mws-log-fadein .3s ease-out;
}
@keyframes mws-log-fadein {
    from { opacity: 0; transform: translateY(4px); }
    to   { opacity: 1; transform: translateY(0); }
}
.mws-log-time {
    color: #4B5563;
    font-size: 10px;
    white-space: nowrap;
    min-width: 52px;
    padding-top: 1px;
}
.mws-log-icon {
    flex-shrink: 0;
    font-size: 12px;
    line-height: 1.7;
}
.mws-log-text {
    flex: 1;
    word-break: break-word;
}

/* Log type colors */
.mws-log-entry.log-info .mws-log-text    { color: #93C5FD; }
.mws-log-entry.log-success .mws-log-text { color: #6EE7B7; }
.mws-log-entry.log-error .mws-log-text   { color: #FCA5A5; }
.mws-log-entry.log-warning .mws-log-text { color: #FCD34D; }

/* Manhwa header log */
.mws-log-entry.log-manhwa {
    margin-top: 6px;
    padding: 6px 10px;
    background: rgba(99,102,241,.08);
    border-radius: 6px;
    border-left: 3px solid #6366F1;
}
.mws-log-entry.log-manhwa .mws-log-text {
    color: #A5B4FC;
    font-weight: 600;
}

/* Chapter sub-entry */
.mws-log-entry.log-chapter {
    padding-left: 20px;
}
.mws-log-entry.log-chapter .mws-log-text {
    color: #D1D5DB;
}
.mws-log-entry.log-chapter.log-success .mws-log-text {
    color: #6EE7B7;
}
.mws-log-entry.log-chapter.log-error .mws-log-text {
    color: #FCA5A5;
}

/* Loading dots animation */
.mws-loading-dots::after {
    content: '';
    animation: mws-dots 1.5s steps(4, end) infinite;
}
@keyframes mws-dots {
    0%  { content: ''; }
    25% { content: '.'; }
    50% { content: '..'; }
    75% { content: '...'; }
}

/* Chapter count badge in log */
.mws-log-badge {
    display: inline-block;
    padding: 1px 6px;
    border-radius: 8px;
    font-size: 10px;
    font-weight: 700;
    margin-left: 4px;
}
.mws-log-badge-purple { background: rgba(139,92,246,.15); color: #A78BFA; }
.mws-log-badge-green  { background: rgba(16,185,129,.15); color: #6EE7B7; }
.mws-log-badge-blue   { background: rgba(59,130,246,.15); color: #93C5FD; }
.mws-log-badge-red    { background: rgba(239,68,68,.12); color: #FCA5A5; }
.mws-log-badge-yellow { background: rgba(245,158,11,.15); color: #FCD34D; }

/* ── Responsive ── */
@media (max-width: 782px) {
    .mws-scrape-stats-bar { gap: 6px; }
    .mws-stat-chip { font-size: 11px; padding: 5px 8px; }
    .mws-current-manhwa-card { padding: 12px 14px; }
    .mws-current-header { flex-direction: column; align-items: flex-start; gap: 8px; }
    .mws-terminal-body { max-height: 250px; font-size: 11px; }
}

/* ══════════════════════════════════════════
   Custom Confirm Modal
   ══════════════════════════════════════════ */
.mws-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, .55);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    z-index: 999999;
    align-items: center;
    justify-content: center;
    animation: mws-modal-bg-in .25s ease;
}
.mws-modal-overlay.active {
    display: flex;
}
@keyframes mws-modal-bg-in {
    from { opacity: 0; }
    to { opacity: 1; }
}
.mws-modal-dialog {
    background: #1E1E2E;
    border-radius: 16px;
    width: 420px;
    max-width: 92vw;
    box-shadow: 0 25px 60px rgba(0,0,0,.5), 0 0 0 1px rgba(255,255,255,.06);
    animation: mws-modal-slide-in .3s cubic-bezier(.34,1.56,.64,1);
    overflow: hidden;
    font-family: 'Inter', -apple-system, sans-serif;
}
@keyframes mws-modal-slide-in {
    from { opacity: 0; transform: scale(.9) translateY(20px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}

/* Header */
.mws-modal-header {
    padding: 20px 24px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    border-bottom: 1px solid rgba(255,255,255,.06);
}
.mws-modal-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.mws-modal-icon.mws-modal-blue    { background: rgba(59,130,246,.15); }
.mws-modal-icon.mws-modal-purple  { background: rgba(139,92,246,.15); }
.mws-modal-icon.mws-modal-orange  { background: rgba(245,158,11,.15); }
.mws-modal-title {
    font-size: 16px;
    font-weight: 700;
    color: #E5E7EB;
    line-height: 1.3;
}
.mws-modal-subtitle {
    font-size: 12px;
    color: #9CA3AF;
    margin-top: 2px;
}

/* Body */
.mws-modal-body {
    padding: 16px 24px;
}
.mws-modal-info-grid {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.mws-modal-info-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    background: rgba(255,255,255,.04);
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,.04);
}
.mws-modal-info-icon {
    font-size: 16px;
    width: 24px;
    text-align: center;
    flex-shrink: 0;
}
.mws-modal-info-label {
    font-size: 12px;
    color: #9CA3AF;
    min-width: 90px;
    flex-shrink: 0;
}
.mws-modal-info-value {
    font-size: 13px;
    font-weight: 600;
    color: #E5E7EB;
}
.mws-modal-info-value.mws-val-green  { color: #6EE7B7; }
.mws-modal-info-value.mws-val-red    { color: #FCA5A5; }
.mws-modal-info-value.mws-val-yellow { color: #FCD34D; }
.mws-modal-info-value.mws-val-blue   { color: #93C5FD; }
.mws-modal-info-value.mws-val-purple { color: #C4B5FD; }

/* Warning banner */
.mws-modal-warning {
    margin-top: 14px;
    padding: 10px 14px;
    background: rgba(245,158,11,.08);
    border: 1px solid rgba(245,158,11,.2);
    border-radius: 10px;
    font-size: 12px;
    color: #FCD34D;
    display: flex;
    align-items: center;
    gap: 8px;
}
.mws-modal-warning-icon { font-size: 16px; }

/* Footer */
.mws-modal-footer {
    padding: 14px 24px 20px;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}
.mws-modal-btn {
    padding: 9px 22px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all .2s ease;
    font-family: 'Inter', sans-serif;
}
.mws-modal-btn-cancel {
    background: rgba(255,255,255,.06);
    color: #9CA3AF;
    border: 1px solid rgba(255,255,255,.08);
}
.mws-modal-btn-cancel:hover {
    background: rgba(255,255,255,.1);
    color: #E5E7EB;
}
.mws-modal-btn-confirm {
    color: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,.3);
}
.mws-modal-btn-confirm:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,.4);
}
.mws-modal-btn-confirm.mws-btn-blue {
    background: linear-gradient(135deg, #3B82F6, #2563EB);
}
.mws-modal-btn-confirm.mws-btn-purple {
    background: linear-gradient(135deg, #8B5CF6, #7C3AED);
}
.mws-modal-btn-confirm.mws-btn-orange {
    background: linear-gradient(135deg, #F59E0B, #D97706);
}
</style>

<script>
var mwsChapterData = null;

jQuery(document).ready(function($) {
    // ══════ Custom Confirm Modal ══════
    function mwsConfirm(options) {
        return new Promise(function(resolve) {
            // Build modal HTML
            var colorClass = options.color || 'blue';
            var html = '<div class="mws-modal-overlay active" id="mws-confirm-modal">';
            html += '<div class="mws-modal-dialog">';
            
            // Header
            html += '<div class="mws-modal-header">';
            html += '<div class="mws-modal-icon mws-modal-' + colorClass + '">' + (options.icon || '⚡') + '</div>';
            html += '<div><div class="mws-modal-title">' + (options.title || 'Confirm') + '</div>';
            if (options.subtitle) {
                html += '<div class="mws-modal-subtitle">' + options.subtitle + '</div>';
            }
            html += '</div></div>';
            
            // Body
            html += '<div class="mws-modal-body"><div class="mws-modal-info-grid">';
            if (options.rows) {
                options.rows.forEach(function(row) {
                    html += '<div class="mws-modal-info-row">';
                    html += '<span class="mws-modal-info-icon">' + (row.icon || '') + '</span>';
                    html += '<span class="mws-modal-info-label">' + (row.label || '') + '</span>';
                    html += '<span class="mws-modal-info-value ' + (row.valueClass || '') + '">' + (row.value || '') + '</span>';
                    html += '</div>';
                });
            }
            html += '</div>';
            
            // Warning
            if (options.warning) {
                html += '<div class="mws-modal-warning">';
                html += '<span class="mws-modal-warning-icon">⚠️</span>';
                html += '<span>' + options.warning + '</span>';
                html += '</div>';
            }
            html += '</div>';
            
            // Footer
            html += '<div class="mws-modal-footer">';
            html += '<button class="mws-modal-btn mws-modal-btn-cancel" id="mws-modal-cancel">' + (options.cancelText || 'Cancel') + '</button>';
            html += '<button class="mws-modal-btn mws-modal-btn-confirm mws-btn-' + colorClass + '" id="mws-modal-ok">' + (options.confirmText || 'Start') + '</button>';
            html += '</div>';
            
            html += '</div></div>';
            
            // Remove old modal if exists
            $('#mws-confirm-modal').remove();
            $('body').append(html);
            
            // Bind events
            $('#mws-modal-ok').on('click', function() {
                $('#mws-confirm-modal').remove();
                resolve(true);
            });
            $('#mws-modal-cancel').on('click', function() {
                $('#mws-confirm-modal').remove();
                resolve(false);
            });
            $('#mws-confirm-modal').on('click', function(e) {
                if ($(e.target).is('#mws-confirm-modal')) {
                    $('#mws-confirm-modal').remove();
                    resolve(false);
                }
            });
            // ESC key
            $(document).one('keydown.mwsmodal', function(e) {
                if (e.key === 'Escape') {
                    $('#mws-confirm-modal').remove();
                    resolve(false);
                }
            });
        });
    }
    
    // Chapter scrape form
    $('#mws-chapter-form').on('submit', function(e) {
        e.preventDefault();
        
        var url = $('#mws-chapter-url').val();
        var $spinner = $('#mws-chapter-spinner');
        var $btn = $('#mws-chapter-scrape-btn');
        
        if (!url) return;
        
        $btn.prop('disabled', true);
        $spinner.addClass('is-active');
        $('#mws-chapter-results').hide();
        $('#mws-chapter-json-section').hide();
        $('#mws-save-success').hide();
        
        $.ajax({
            url: mwsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mws_scrape_chapter_images',
                nonce: mwsData.nonce,
                url: url
            },
            success: function(response) {
                if (response.success) {
                    mwsChapterData = response.data.data;
                    displayChapterImages(mwsChapterData);
                    populateManhwaSelect(mwsChapterData);
                    $('#mws-chapter-results').show();
                } else {
                    alert(response.data.message || 'Error scraping chapter');
                }
            },
            error: function(xhr, status, error) {
                alert('Error: ' + error);
            },
            complete: function() {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });
    
    function displayChapterImages(data) {
        $('#mws-chapter-title').text(data.chapter_title);
        $('#mws-chapter-number').text('Chapter ' + (data.chapter_number || 'N/A'));
        $('#mws-chapter-image-count').text(data.total_images + ' images');
        
        var $grid = $('#mws-images-grid').empty();
        
        data.images.forEach(function(img, index) {
            $grid.append(
                '<div class="mws-image-item">' +
                    '<img src="' + img.url + '" alt="Page ' + (index + 1) + '" loading="lazy" onerror="this.style.display=\'none\'">' +
                    '<div class="mws-image-info">Page ' + (index + 1) + '</div>' +
                '</div>'
            );
        });
        
        // Navigation buttons
        if (data.prev_chapter) {
            $('#mws-prev-chapter').prop('disabled', false).data('url', data.prev_chapter);
        } else {
            $('#mws-prev-chapter').prop('disabled', true);
        }
        
        if (data.next_chapter) {
            $('#mws-next-chapter').prop('disabled', false).data('url', data.next_chapter);
        } else {
            $('#mws-next-chapter').prop('disabled', true);
        }
    }
    
    function populateManhwaSelect(data) {
        var $select = $('#mws-select-manhwa');
        $select.find('option:not(:first)').remove();
        
        // Add available manhwa options
        if (data.available_manhwa) {
            data.available_manhwa.forEach(function(manhwa) {
                $select.append('<option value="' + manhwa.id + '">' + manhwa.title + '</option>');
            });
        }
        
        // Auto-select detected manhwa
        if (data.manhwa_post) {
            $select.val(data.manhwa_post.id);
            $('#mws-detected-manhwa-title').text(data.manhwa_post.title);
            $('#mws-detected-manhwa-link').attr('href', data.manhwa_post.edit_url);
            $('#mws-detected-manhwa').show();
        } else {
            $('#mws-detected-manhwa').hide();
        }
    }
    
    // Save chapter to manhwa post
    $('#mws-save-chapter-btn').on('click', function() {
        if (!mwsChapterData) return;
        
        var postId = $('#mws-select-manhwa').val();
        if (!postId) {
            alert('<?php esc_html_e('Please select a manhwa post', 'manhwa-scraper'); ?>');
            return;
        }
        
        var $btn = $(this);
        var $spinner = $('#mws-save-spinner');
        
        $btn.prop('disabled', true);
        $spinner.addClass('is-active');
        $('#mws-save-success').hide();
        
        $.ajax({
            url: mwsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mws_save_chapter_to_post',
                nonce: mwsData.nonce,
                post_id: postId,
                chapter_data: JSON.stringify(mwsChapterData)
            },
            success: function(response) {
                if (response.success) {
                    $('#mws-save-success-message').html(
                        response.data.message + 
                        ' <a href="' + response.data.edit_url + '" target="_blank"><?php esc_html_e('Edit Manhwa', 'manhwa-scraper'); ?></a>'
                    );
                    $('#mws-save-success').show();
                } else {
                    alert(response.data.message || 'Error saving chapter');
                }
            },
            error: function(xhr, status, error) {
                alert('Error: ' + error);
            },
            complete: function() {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });
    
    // Navigation buttons
    $('#mws-prev-chapter, #mws-next-chapter').on('click', function() {
        var url = $(this).data('url');
        if (url) {
            $('#mws-chapter-url').val(url);
            $('#mws-chapter-form').submit();
        }
    });
    
    // Export JSON
    $('#mws-export-images-json').on('click', function() {
        if (!mwsChapterData) return;
        
        var json = JSON.stringify(mwsChapterData, null, 2);
        var blob = new Blob([json], {type: 'application/json'});
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'chapter-images-' + (mwsChapterData.chapter_number || 'unknown') + '.json';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    });
    
    // Copy URLs
    $('#mws-copy-images-urls').on('click', function() {
        if (!mwsChapterData) return;
        
        var urls = mwsChapterData.images.map(function(img) { return img.url; }).join('\n');
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(urls).then(function() {
                alert('<?php esc_html_e('URLs copied to clipboard!', 'manhwa-scraper'); ?>');
            });
        } else {
            $('#mws-chapter-json-output').val(urls);
            $('#mws-chapter-json-section').show();
        }
    });
    
    // ========== BULK SCRAPING FUNCTIONALITY ==========
    var bulkChaptersData = [];
    var bulkScrapeRunning = false;
    var bulkScrapeAborted = false;
    
    // Load chapters for selected manhwa
    $('#mws-bulk-load-chapters').on('click', function() {
        var postId = $('#mws-bulk-manhwa').val();
        if (!postId) {
            alert('<?php esc_html_e('Please select a manhwa first', 'manhwa-scraper'); ?>');
            return;
        }
        
        var $spinner = $('#mws-bulk-spinner');
        $spinner.addClass('is-active');
        
        $.ajax({
            url: mwsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mws_get_manhwa_chapters',
                nonce: mwsData.nonce,
                post_id: postId
            },
            success: function(response) {
                if (response.success) {
                    bulkChaptersData = response.data.chapters;
                    displayBulkChapters(bulkChaptersData);
                    $('#mws-bulk-chapter-list').show();
                    $('#mws-bulk-start-scrape').prop('disabled', false);
                } else {
                    alert(response.data.message || 'Error loading chapters');
                }
            },
            error: function(xhr, status, error) {
                alert('Error: ' + error);
            },
            complete: function() {
                $spinner.removeClass('is-active');
            }
        });
    });
    
    function displayBulkChapters(chapters, filterStatus) {
        var $preview = $('#mws-bulk-chapters-preview').empty();
        var skipExisting = $('#mws-bulk-skip-existing').is(':checked');
        var skipDownloaded = $('#mws-bulk-skip-downloaded').is(':checked');
        var toScrape = 0;
        var downloadedCount = 0;
        var externalCount = 0;
        var noImagesCount = 0;
        
        filterStatus = filterStatus || $('#mws-bulk-filter-status').val() || 'all';
        
        chapters.forEach(function(ch, index) {
            var hasImages = ch.images && ch.images.length > 0;
            var hasUrl = ch.url && ch.url.trim() !== '';
            var isLocal = false;
            
            // Check if images are downloaded to local server
            if (hasImages) {
                var firstImg = ch.images[0];
                var imgUrl = typeof firstImg === 'object' ? (firstImg.url || firstImg.src || '') : firstImg;
                isLocal = imgUrl.indexOf('/wp-content/uploads/manhwa/') !== -1;
            }
            
            // Count stats
            if (isLocal) {
                downloadedCount++;
            } else if (hasImages) {
                externalCount++;
            } else {
                noImagesCount++;
            }
            
            // Determine chapter status type
            var statusType = isLocal ? 'local' : (hasImages ? 'external' : 'no-images');
            
            // Filter visibility
            var showItem = filterStatus === 'all' || filterStatus === statusType;
            if (!showItem) return;
            
            // Determine if should be checked for scraping
            var willScrape = hasUrl && !isLocal; // Don't scrape if already downloaded
            if (skipExisting && hasImages) willScrape = false;
            if (skipDownloaded && isLocal) willScrape = false;
            
            if (willScrape) toScrape++;
            
            // Status display
            var statusClass, statusText, statusIcon;
            if (isLocal) {
                statusClass = 'color: green;';
                statusIcon = '[OK]';
                statusText = 'Downloaded (' + ch.images.length + ' images)';
            } else if (hasImages) {
                statusClass = 'color: #2271b1;';
                statusIcon = '[EXT]';
                statusText = 'External (' + ch.images.length + ' images)';
            } else if (hasUrl) {
                statusClass = 'color: orange;';
                statusIcon = '[~]';
                statusText = 'Ready to scrape';
            } else {
                statusClass = 'color: #999;';
                statusIcon = '[!]';
                statusText = 'No URL';
            }
            
            $preview.append(
                '<div class="mws-bulk-chapter-row" style="display: flex; justify-content: space-between; padding: 8px 5px; border-bottom: 1px solid #eee;" data-index="' + index + '" data-status="' + statusType + '">' +
                    '<span><input type="checkbox" class="mws-bulk-chapter-check" ' + (willScrape ? 'checked' : '') + ' data-index="' + index + '"> ' + 
                    (ch.title || ch.number || 'Chapter ' + (index + 1)) + '</span>' +
                    '<span style="' + statusClass + ' font-size: 12px;">' + statusIcon + ' ' + statusText + '</span>' +
                '</div>'
            );
        });
        
        $('#mws-bulk-total-count').text(chapters.length);
        $('#mws-bulk-scrape-count').text(toScrape);
        $('#mws-bulk-downloaded-count').text(downloadedCount);
        $('#mws-bulk-external-count').text(externalCount);
        $('#mws-bulk-noimages-count').text(noImagesCount);
        
        // Enable/disable Download External button based on external count
        if (externalCount > 0) {
            $('#mws-bulk-download-external').prop('disabled', false);
        } else {
            $('#mws-bulk-download-external').prop('disabled', true);
        }
    }
    
    // Combined filter function for manhwa list
    function filterManhwaList() {
        var textFilter = $('#mws-bulk-manhwa-filter').val().toLowerCase();
        var statusFilter = $('#mws-bulk-chapter-status-filter').val();
        var sourceFilter = $('#mws-bulk-source-filter').val();
        var visibleCount = 0;
        
        $('#mws-bulk-manhwa option').each(function() {
            var $opt = $(this);
            if ($opt.val() === '') {
                return; // Keep placeholder always visible
            }
            
            var title = $opt.data('title') || $opt.text().toLowerCase();
            var status = $opt.data('status') || '';
            var source = $opt.data('source') || 'unknown';
            
            // Text filter
            var matchesText = textFilter === '' || title.indexOf(textFilter) !== -1;
            
            // Status filter - direct match
            var matchesStatus = (statusFilter === 'all') || (status === statusFilter);
            
            // Source filter - direct match
            var matchesSource = (sourceFilter === 'all') || (source === sourceFilter);
            
            if (matchesText && matchesStatus && matchesSource) {
                $opt.show();
                visibleCount++;
            } else {
                $opt.hide();
            }
        });
        
        // Update count display
        $('#mws-filter-count').text('(' + visibleCount + ' manhwa)');
        $('#mws-source-filter-count').text('');
    }
    
    // Manhwa title filter
    $('#mws-bulk-manhwa-filter').on('input', filterManhwaList);
    
    // Status filter change
    $('#mws-bulk-chapter-status-filter').on('change', function() {
        filterManhwaList();
        // Reset manhwa selection
        $('#mws-bulk-manhwa').val('');
    });
    
    // Source filter change
    $('#mws-bulk-source-filter').on('change', function() {
        filterManhwaList();
        // Reset manhwa selection
        $('#mws-bulk-manhwa').val('');
    });
    
    // Filter status change
    $('#mws-bulk-filter-status').on('change', function() {
        if (bulkChaptersData.length > 0) {
            displayBulkChapters(bulkChaptersData, $(this).val());
        }
    });

    
    // Select all checkbox
    $('#mws-bulk-select-all').on('change', function() {
        var isChecked = $(this).is(':checked');
        $('.mws-bulk-chapter-check:visible').prop('checked', isChecked);
        var checked = $('.mws-bulk-chapter-check:checked').length;
        $('#mws-bulk-scrape-count').text(checked);
    });
    
    // Update count when checkboxes change
    $(document).on('change', '.mws-bulk-chapter-check', function() {
        var checked = $('.mws-bulk-chapter-check:checked').length;
        $('#mws-bulk-scrape-count').text(checked);
    });
    
    // Skip existing checkbox change
    $('#mws-bulk-skip-existing, #mws-bulk-skip-downloaded').on('change', function() {
        if (bulkChaptersData.length > 0) {
            displayBulkChapters(bulkChaptersData);
        }
    });
    
    // Start bulk scraping
    $('#mws-bulk-start-scrape').on('click', function() {
        var postId = $('#mws-bulk-manhwa').val();
        if (!postId) return;
        
        var selectedIndices = [];
        $('.mws-bulk-chapter-check:checked').each(function() {
            selectedIndices.push(parseInt($(this).data('index')));
        });
        
        if (selectedIndices.length === 0) {
            alert('<?php esc_html_e('No chapters selected for scraping', 'manhwa-scraper'); ?>');
            return;
        }
        
        var isParallel = $('#mws-bulk-parallel').is(':checked');
        var batchSizeVal = parseInt($('#mws-bulk-batch-size').val()) || 5;
        var delayVal = parseInt($('#mws-bulk-delay').val()) || 2;
        var downloadLocal = $('#mws-bulk-download-local').is(':checked');
        
        // Estimate time
        var estSeconds = isParallel 
            ? Math.ceil(selectedIndices.length / batchSizeVal) * (delayVal + 5)
            : selectedIndices.length * (delayVal + 5);
        var estMin = Math.floor(estSeconds / 60);
        var estTime = estMin >= 60 ? Math.floor(estMin / 60) + 'h ' + (estMin % 60) + 'm' : (estMin > 0 ? '~' + estMin + ' min' : '~' + estSeconds + ' sec');
        
        mwsConfirm({
            icon: '📥',
            title: '<?php esc_html_e('Bulk Scrape Chapters', 'manhwa-scraper'); ?>',
            subtitle: selectedIndices.length + ' <?php esc_html_e('chapters selected', 'manhwa-scraper'); ?>',
            color: 'blue',
            confirmText: '🚀 <?php esc_html_e('Start Scraping', 'manhwa-scraper'); ?>',
            rows: [
                { icon: '📊', label: '<?php esc_html_e('Chapters', 'manhwa-scraper'); ?>', value: selectedIndices.length, valueClass: 'mws-val-blue' },
                { icon: '⚡', label: '<?php esc_html_e('Mode', 'manhwa-scraper'); ?>', value: isParallel ? 'Parallel (' + batchSizeVal + ' at a time)' : 'Sequential' },
                { icon: '⏱', label: '<?php esc_html_e('Delay', 'manhwa-scraper'); ?>', value: delayVal + ' sec' },
                { icon: '⬇', label: '<?php esc_html_e('Download Local', 'manhwa-scraper'); ?>', value: downloadLocal ? '<?php esc_html_e('Yes', 'manhwa-scraper'); ?> ✅' : '<?php esc_html_e('No', 'manhwa-scraper'); ?> ❌', valueClass: downloadLocal ? 'mws-val-green' : 'mws-val-red' },
                { icon: '⏳', label: '<?php esc_html_e('Estimated', 'manhwa-scraper'); ?>', value: estTime, valueClass: 'mws-val-yellow' }
            ],
            warning: estMin >= 10 ? '<?php esc_html_e('This may take a long time. Keep this tab open.', 'manhwa-scraper'); ?>' : null
        }).then(function(confirmed) {
            if (!confirmed) return;
        
        bulkScrapeRunning = true;
        bulkScrapeAborted = false;
        
        // Reset live stats
        window.mwsBulkStats = { scraped: 0, downloaded: 0, images: 0, errors: 0 };
        $('#mws-bulk-stat-scraped').text('0');
        $('#mws-bulk-stat-downloaded').text('0');
        $('#mws-bulk-stat-images').text('0');
        $('#mws-bulk-stat-errors').text('0');
        $('#mws-bulk-progress-count').text('0/' + selectedIndices.length);
        $('#mws-bulk-progress-percent').text('0%');
        $('#mws-bulk-progress-bar').css('width', '0%');
        
        // Start elapsed timer
        window.mwsBulkStartTime = Date.now();
        window.mwsBulkElapsedTimer = setInterval(function() {
            var elapsed = Math.floor((Date.now() - window.mwsBulkStartTime) / 1000);
            var m = Math.floor(elapsed / 60);
            var s = elapsed % 60;
            $('#mws-bulk-elapsed-time').text((m > 0 ? m + 'm ' : '') + s + 's');
        }, 1000);
        
        $('#mws-bulk-start-scrape').hide();
        $('#mws-bulk-stop-scrape').show();
        $('#mws-bulk-progress').show();
        $('#mws-bulk-status').empty();
        
        var delay = parseInt($('#mws-bulk-delay').val()) * 1000 || 2000;
        var batchSize = parseInt($('#mws-bulk-batch-size').val()) || 5;
        batchSize = Math.max(1, Math.min(100, batchSize));
        
        if (isParallel) {
            addBulkStatus('<?php esc_html_e('Mode: Parallel', 'manhwa-scraper'); ?> (' + batchSize + ' <?php esc_html_e('chapters at a time', 'manhwa-scraper'); ?>)', 'info', '⚡');
            processBulkChaptersParallel(postId, selectedIndices, 0, delay, batchSize);
        } else {
            addBulkStatus('<?php esc_html_e('Mode: Sequential (1 at a time)', 'manhwa-scraper'); ?>', 'info', '📝');
            processBulkChapters(postId, selectedIndices, 0, delay);
        }
        }); // end .then()
    });
    
    // Stop bulk scraping
    $('#mws-bulk-stop-scrape').on('click', function() {
        bulkScrapeAborted = true;
        addBulkStatus('<?php esc_html_e('Stopping after current chapter...', 'manhwa-scraper'); ?>', 'warning', '⏹');
    });
    
    // Download External Images to Local
    $('#mws-bulk-download-external').on('click', function() {
        var postId = $('#mws-bulk-manhwa').val();
        if (!postId) return;
        
        // Get chapters with external images
        var externalChapters = [];
        bulkChaptersData.forEach(function(ch, index) {
            if (ch.images && ch.images.length > 0) {
                var firstImg = ch.images[0];
                var imgUrl = typeof firstImg === 'object' ? (firstImg.url || firstImg.src || '') : firstImg;
                var isLocal = imgUrl.indexOf('/wp-content/uploads/manhwa/') !== -1;
                
                if (!isLocal) {
                    externalChapters.push({
                        index: index,
                        chapter: ch
                    });
                }
            }
        });
        
        if (externalChapters.length === 0) {
            alert('<?php esc_html_e('No external images found to download', 'manhwa-scraper'); ?>');
            return;
        }
        
        var estDlMin = Math.ceil(externalChapters.length * 0.5);
        var estDlTime = estDlMin >= 60 ? Math.floor(estDlMin / 60) + 'h ' + (estDlMin % 60) + 'm' : '~' + estDlMin + ' min';
        
        mwsConfirm({
            icon: '☁️',
            title: '<?php esc_html_e('Download External → Local', 'manhwa-scraper'); ?>',
            subtitle: '<?php esc_html_e('Convert external images to local server', 'manhwa-scraper'); ?>',
            color: 'orange',
            confirmText: '⬇ <?php esc_html_e('Start Download', 'manhwa-scraper'); ?>',
            rows: [
                { icon: '📁', label: '<?php esc_html_e('Chapters', 'manhwa-scraper'); ?>', value: externalChapters.length, valueClass: 'mws-val-blue' },
                { icon: '⬇', label: '<?php esc_html_e('Action', 'manhwa-scraper'); ?>', value: '<?php esc_html_e('Download to local server', 'manhwa-scraper'); ?>' },
                { icon: '⏳', label: '<?php esc_html_e('Estimated', 'manhwa-scraper'); ?>', value: estDlTime, valueClass: 'mws-val-yellow' }
            ],
            warning: estDlMin >= 10 ? '<?php esc_html_e('This may take a while. Keep this tab open.', 'manhwa-scraper'); ?>' : null
        }).then(function(confirmed) {
            if (!confirmed) return;
        
        bulkScrapeRunning = true;
        bulkScrapeAborted = false;
        
        $('#mws-bulk-start-scrape').hide();
        $('#mws-bulk-download-external').hide();
        $('#mws-bulk-stop-scrape').show();
        $('#mws-bulk-progress').show();
        $('#mws-bulk-status').empty();
        
        // Reset stats for download mode
        window.mwsBulkStats = { scraped: 0, downloaded: 0, images: 0, errors: 0 };
        $('#mws-bulk-stat-scraped').text('0');
        $('#mws-bulk-stat-downloaded').text('0');
        $('#mws-bulk-stat-images').text('0');
        $('#mws-bulk-stat-errors').text('0');
        $('#mws-bulk-progress-count').text('0/' + externalChapters.length);
        $('#mws-bulk-progress-percent').text('0%');
        $('#mws-bulk-progress-bar').css('width', '0%');
        
        window.mwsBulkStartTime = Date.now();
        window.mwsBulkElapsedTimer = setInterval(function() {
            var elapsed = Math.floor((Date.now() - window.mwsBulkStartTime) / 1000);
            var m = Math.floor(elapsed / 60);
            var s = elapsed % 60;
            $('#mws-bulk-elapsed-time').text((m > 0 ? m + 'm ' : '') + s + 's');
        }, 1000);
        
        addBulkStatus('<?php esc_html_e('Downloading external images to local server...', 'manhwa-scraper'); ?>', 'info', '🔄');
        addBulkStatus('<?php esc_html_e('Total chapters to download:', 'manhwa-scraper'); ?> ' + externalChapters.length, 'info', '📊');
        
        processExternalDownloads(postId, externalChapters, 0);
        }); // end .then()
    });
    
    // Process external downloads sequentially
    function processExternalDownloads(postId, chapters, currentIndex) {
        if (bulkScrapeAborted || currentIndex >= chapters.length) {
            bulkScrapeRunning = false;
            $('#mws-bulk-stop-scrape').hide();
            $('#mws-bulk-start-scrape').show();
            $('#mws-bulk-download-external').show();
            
            if (window.mwsBulkElapsedTimer) clearInterval(window.mwsBulkElapsedTimer);
            
            if (bulkScrapeAborted) {
                addBulkStatus('<?php esc_html_e('Download stopped by user', 'manhwa-scraper'); ?>', 'warning', '⏹');
            } else {
                addBulkStatus('<?php esc_html_e('All external images downloaded successfully!', 'manhwa-scraper'); ?>', 'success', '✅');
                addBulkStatus('<?php esc_html_e('Refreshing chapter list...', 'manhwa-scraper'); ?>', 'info', '🔄');
                
                // Reload chapters from database to show updated local URLs
                $.ajax({
                    url: mwsData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'mws_get_manhwa_chapters',
                        nonce: mwsData.nonce,
                        post_id: postId
                    },
                    success: function(response) {
                        if (response.success) {
                            bulkChaptersData = response.data.chapters;
                            displayBulkChapters(bulkChaptersData);
                            addBulkStatus('<?php esc_html_e('Chapter list refreshed!', 'manhwa-scraper'); ?>', 'success', '✅');
                        }
                    }
                });
            }
            return;
        }
        
        var item = chapters[currentIndex];
        var chapter = item.chapter;
        var chapterTitle = chapter.title || chapter.number || 'Chapter ' + (item.index + 1);
        
        // Update progress
        var progress = Math.round(((currentIndex + 1) / chapters.length) * 100);
        $('#mws-bulk-progress-bar').css('width', progress + '%');
        $('#mws-bulk-progress-percent').text(progress + '%');
        $('#mws-bulk-progress-count').text((currentIndex + 1) + '/' + chapters.length);
        
        // Build chapter data for download
        var chapterData = {
            chapter_number: chapter.number || (item.index + 1),
            chapter_title: chapterTitle,
            images: chapter.images.map(function(img, idx) {
                return {
                    index: idx,
                    url: typeof img === 'object' ? (img.url || img.src || img) : img,
                    alt: 'Page ' + (idx + 1)
                };
            })
        };
        
        $.ajax({
            url: mwsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mws_download_chapter_images',
                nonce: mwsData.nonce,
                post_id: postId,
                chapter_data: JSON.stringify(chapterData)
            },
            success: function(response) {
                if (response.success) {
                    var r = response.data.result;
                    addBulkStatus(chapterTitle + ' <span class="mws-log-badge mws-log-badge-blue">⬇ ' + r.success + '/' + r.total + ' img</span>', 'chapter log-success', '✓');
                        window.mwsBulkStats.downloaded++;
                        window.mwsBulkStats.images += (r.success || 0);
                        $('#mws-bulk-stat-downloaded').text(window.mwsBulkStats.downloaded);
                        $('#mws-bulk-stat-images').text(window.mwsBulkStats.images);
                    
                    // Update local chapter data
                    if (response.data.local_images) {
                        bulkChaptersData[item.index].images = response.data.local_images;
                    }
                } else {
                    addBulkStatus(chapterTitle + ' — ' + (response.data.message || 'Error'), 'chapter log-error', '✗');
                    window.mwsBulkStats.errors++;
                    $('#mws-bulk-stat-errors').text(window.mwsBulkStats.errors);
                }
                
                // Continue with next
                setTimeout(function() {
                    processExternalDownloads(postId, chapters, currentIndex + 1);
                }, 500);
            },
            error: function() {
                addBulkStatus(chapterTitle + ' — <?php esc_html_e('Request failed', 'manhwa-scraper'); ?>', 'chapter log-error', '✗');
                window.mwsBulkStats.errors++;
                $('#mws-bulk-stat-errors').text(window.mwsBulkStats.errors);
                setTimeout(function() {
                    processExternalDownloads(postId, chapters, currentIndex + 1);
                }, 500);
            }
        });
    }
    
    // Parallel processing function
    function processBulkChaptersParallel(postId, indices, startIndex, delay, batchSize) {
        if (bulkScrapeAborted || startIndex >= indices.length) {
            // Done
            bulkScrapeRunning = false;
            $('#mws-bulk-stop-scrape').hide();
            $('#mws-bulk-start-scrape').show();
            
            if (window.mwsBulkElapsedTimer) clearInterval(window.mwsBulkElapsedTimer);
            
            if (bulkScrapeAborted) {
                addBulkStatus('<?php esc_html_e('Scraping stopped by user', 'manhwa-scraper'); ?>', 'warning', '⏹');
            } else {
                addBulkStatus('<?php esc_html_e('All chapters scraped successfully!', 'manhwa-scraper'); ?>', 'success', '✅');
            }
            return;
        }
        
        // Get batch of chapters to process
        var batch = indices.slice(startIndex, startIndex + batchSize);
        var batchNum = Math.floor(startIndex / batchSize) + 1;
        var totalBatches = Math.ceil(indices.length / batchSize);
        
        addBulkStatus('<?php esc_html_e('Processing batch', 'manhwa-scraper'); ?> ' + batchNum + '/' + totalBatches + ' <span class="mws-log-badge mws-log-badge-purple">' + batch.length + ' ch</span>', 'info', '📦');
        
        var progress = Math.round(((startIndex + batch.length) / indices.length) * 100);
        $('#mws-bulk-progress-bar').css('width', progress + '%');
        $('#mws-bulk-progress-percent').text(progress + '%');
        $('#mws-bulk-progress-count').text((startIndex + batch.length) + '/' + indices.length);
        
        // Process all chapters in batch simultaneously
        var promises = batch.map(function(chapterIndex) {
            return scrapeChapterAsync(postId, chapterIndex);
        });
        
        Promise.all(promises).then(function() {
            // All batch items complete, continue with next batch
            setTimeout(function() {
                processBulkChaptersParallel(postId, indices, startIndex + batchSize, delay, batchSize);
            }, delay);
        });
    }
    
    // Async chapter scrape function for parallel processing
    function scrapeChapterAsync(postId, chapterIndex) {
        return new Promise(function(resolve) {
            var chapter = bulkChaptersData[chapterIndex];
            var chapterTitle = chapter.title || chapter.number || 'Chapter ' + (chapterIndex + 1);
            
            if (!chapter.url) {
                addBulkStatus(chapterTitle + ' — <?php esc_html_e('Skipped (no URL)', 'manhwa-scraper'); ?>', 'warning', '⏭');
                resolve();
                return;
            }
            
            $.ajax({
                url: mwsData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mws_scrape_chapter_images',
                    nonce: mwsData.nonce,
                    url: chapter.url
                },
                success: function(response) {
                    if (response.success && response.data.data) {
                        var imgCount = response.data.data.images ? response.data.data.images.length : 0;
                        addBulkStatus(chapterTitle + ' <span class="mws-log-badge mws-log-badge-green">' + imgCount + ' img</span>', 'chapter log-success', '✓');
                        window.mwsBulkStats.scraped++;
                        window.mwsBulkStats.images += imgCount;
                        $('#mws-bulk-stat-scraped').text(window.mwsBulkStats.scraped);
                        $('#mws-bulk-stat-images').text(window.mwsBulkStats.images);
                        
                        // Check if should download to local FIRST
                        var downloadLocal = $('#mws-bulk-download-local').is(':checked');
                        if (downloadLocal && response.data.data.images && response.data.data.images.length > 0) {
                            // Download first
                            $.ajax({
                                url: mwsData.ajaxUrl,
                                type: 'POST',
                                data: {
                                    action: 'mws_download_chapter_images',
                                    nonce: mwsData.nonce,
                                    post_id: postId,
                                    chapter_data: JSON.stringify(response.data.data)
                                },
                                success: function(dlResponse) {
                                    if (dlResponse.success) {
                                        var r = dlResponse.data.result;
                                        addBulkStatus(chapterTitle + ' <span class="mws-log-badge mws-log-badge-blue">⬇ ' + r.success + ' saved</span>', 'chapter log-success', '✓');
                                        window.mwsBulkStats.downloaded++;
                                        $('#mws-bulk-stat-downloaded').text(window.mwsBulkStats.downloaded);
                                        
                                        // Now save with LOCAL URLs from download result
                                        var chapterDataWithLocal = Object.assign({}, response.data.data);
                                        if (dlResponse.data.local_images && dlResponse.data.local_images.length > 0) {
                                            chapterDataWithLocal.images = dlResponse.data.local_images;
                                        }
                                        
                                        $.ajax({
                                            url: mwsData.ajaxUrl,
                                            type: 'POST',
                                            data: {
                                                action: 'mws_save_chapter_to_post',
                                                nonce: mwsData.nonce,
                                                post_id: postId,
                                                chapter_data: JSON.stringify(chapterDataWithLocal)
                                            },
                                            success: function(saveResponse) {
                                                if (saveResponse.success) {
                                                    bulkChaptersData[chapterIndex].images = chapterDataWithLocal.images;
                                                    bulkChaptersData[chapterIndex].images_local = true;
                                                }
                                                resolve();
                                            },
                                            error: function() {
                                                addBulkStatus(chapterTitle + ' — <?php esc_html_e('Save error', 'manhwa-scraper'); ?>', 'chapter log-error', '✗');
                                window.mwsBulkStats.errors++;
                                $('#mws-bulk-stat-errors').text(window.mwsBulkStats.errors);
                                                resolve();
                                            }
                                        });
                                    } else {
                                        // Download failed, save with external URLs
                                        saveChapterData(response.data.data, postId, chapterIndex, chapterTitle, resolve);
                                    }
                                },
                                error: function() {
                                    // Download failed, save with external URLs
                                    saveChapterData(response.data.data, postId, chapterIndex, chapterTitle, resolve);
                                }
                            });
                        } else {
                            // No download, save with external URLs
                            saveChapterData(response.data.data, postId, chapterIndex, chapterTitle, resolve);
                        }
                    } else {
                        var errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error';
                        addBulkStatus(chapterTitle + ' — ' + errorMsg, 'chapter log-error', '✗');
                        window.mwsBulkStats.errors++;
                        $('#mws-bulk-stat-errors').text(window.mwsBulkStats.errors);
                        console.error('Scrape error for ' + chapterTitle + ':', response);
                        resolve();
                    }
                },
                error: function(xhr, status, error) {
                    var errorMsg = error || status || 'Request failed';
                    if (xhr.responseText && xhr.responseText.indexOf('Fatal error') !== -1) {
                        errorMsg = 'PHP Fatal Error - check server logs';
                    }
                    addBulkStatus(chapterTitle + ' — ' + errorMsg, 'chapter log-error', '✗');
                    window.mwsBulkStats.errors++;
                    $('#mws-bulk-stat-errors').text(window.mwsBulkStats.errors);
                    console.error('AJAX error for ' + chapterTitle + ':', {status: status, error: error, xhr: xhr});
                    resolve();
                }
            });
        });
    }
    
    // Sequential processing function (original)
    function processBulkChapters(postId, indices, currentIndex, delay) {
        if (bulkScrapeAborted || currentIndex >= indices.length) {
            // Done
            bulkScrapeRunning = false;
            $('#mws-bulk-stop-scrape').hide();
            $('#mws-bulk-start-scrape').show();
            
            if (window.mwsBulkElapsedTimer) clearInterval(window.mwsBulkElapsedTimer);
            
            if (bulkScrapeAborted) {
                addBulkStatus('<?php esc_html_e('Scraping stopped by user', 'manhwa-scraper'); ?>', 'warning', '⏹');
            } else {
                addBulkStatus('<?php esc_html_e('All chapters scraped successfully!', 'manhwa-scraper'); ?>', 'success', '✅');
            }
            return;
        }
        
        var chapterIndex = indices[currentIndex];
        var chapter = bulkChaptersData[chapterIndex];
        var chapterTitle = chapter.title || chapter.number || 'Chapter ' + (chapterIndex + 1);
        
        var progress = Math.round(((currentIndex + 1) / indices.length) * 100);
        $('#mws-bulk-progress-bar').css('width', progress + '%');
        $('#mws-bulk-progress-percent').text(progress + '%');
        $('#mws-bulk-progress-count').text((currentIndex + 1) + '/' + indices.length);
        
        addBulkStatus('<?php esc_html_e('Scraping:', 'manhwa-scraper'); ?> ' + chapterTitle + '...', 'info', '🔍');
        console.log('Scraping URL:', chapter.url);
        
        if (!chapter.url) {
            addBulkStatus(chapterTitle + ' — <?php esc_html_e('Skipped (no URL)', 'manhwa-scraper'); ?>', 'warning', '⏭');
            setTimeout(function() {
                processBulkChapters(postId, indices, currentIndex + 1, delay);
            }, 100);
            return;
        }
        
        $.ajax({
            url: mwsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mws_scrape_chapter_images',
                nonce: mwsData.nonce,
                url: chapter.url
            },
            success: function(response) {
                if (response.success && response.data.data) {
                    var imgCount = response.data.data.images ? response.data.data.images.length : 0;
                    addBulkStatus(chapterTitle + ' <span class="mws-log-badge mws-log-badge-green">' + imgCount + ' img</span>', 'chapter log-success', '✓');
                    window.mwsBulkStats.scraped++;
                    window.mwsBulkStats.images += imgCount;
                    $('#mws-bulk-stat-scraped').text(window.mwsBulkStats.scraped);
                    $('#mws-bulk-stat-images').text(window.mwsBulkStats.images);
                    
                    // Save to post
                    $.ajax({
                        url: mwsData.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'mws_save_chapter_to_post',
                            nonce: mwsData.nonce,
                            post_id: postId,
                            chapter_data: JSON.stringify(response.data.data)
                        },
                        success: function(saveResponse) {
                            if (saveResponse.success) {
                                addBulkStatus(chapterTitle + ' — <?php esc_html_e('Saved to post', 'manhwa-scraper'); ?>', 'chapter log-success', '  ✓');
                                // Update local data
                                bulkChaptersData[chapterIndex].images = response.data.data.images;
                                
                                // Check if should download to local
                                var downloadLocal = $('#mws-bulk-download-local').is(':checked');
                                if (downloadLocal && response.data.data.images && response.data.data.images.length > 0) {
                                    addBulkStatus(chapterTitle + ' — <?php esc_html_e('Downloading to local...', 'manhwa-scraper'); ?>', 'chapter', '  ⬇');
                                    
                                    $.ajax({
                                        url: mwsData.ajaxUrl,
                                        type: 'POST',
                                        data: {
                                            action: 'mws_download_chapter_images',
                                            nonce: mwsData.nonce,
                                            post_id: postId,
                                            chapter_data: JSON.stringify(response.data.data)
                                        },
                                        success: function(dlResponse) {
                                            if (dlResponse.success) {
                                                var r = dlResponse.data.result;
                                                addBulkStatus(chapterTitle + ' <span class="mws-log-badge mws-log-badge-blue">⬇ ' + r.success + ' saved</span>' + (r.skipped > 0 ? ' <span class="mws-log-badge mws-log-badge-yellow">' + r.skipped + ' skipped</span>' : ''), 'chapter log-success', '  ✓');
                                                window.mwsBulkStats.downloaded++;
                                                $('#mws-bulk-stat-downloaded').text(window.mwsBulkStats.downloaded);
                                            } else {
                                                addBulkStatus(chapterTitle + ' — <?php esc_html_e('Download failed', 'manhwa-scraper'); ?>', 'chapter log-error', '  ✗');
                                                window.mwsBulkStats.errors++;
                                                $('#mws-bulk-stat-errors').text(window.mwsBulkStats.errors);
                                            }
                                        },
                                        error: function() {
                                            addBulkStatus(chapterTitle + ' — <?php esc_html_e('Download error', 'manhwa-scraper'); ?>', 'chapter log-error', '  ✗');
                                            window.mwsBulkStats.errors++;
                                            $('#mws-bulk-stat-errors').text(window.mwsBulkStats.errors);
                                        },
                                        complete: function() {
                                            // Continue with next chapter after delay
                                            setTimeout(function() {
                                                processBulkChapters(postId, indices, currentIndex + 1, delay);
                                            }, delay);
                                        }
                                    });
                                } else {
                                    // Continue with next chapter after delay
                                    setTimeout(function() {
                                        processBulkChapters(postId, indices, currentIndex + 1, delay);
                                    }, delay);
                                }
                            } else {
                                addBulkStatus(chapterTitle + ' — <?php esc_html_e('Save error', 'manhwa-scraper'); ?>', 'chapter log-error', '  ✗');
                                window.mwsBulkStats.errors++;
                                $('#mws-bulk-stat-errors').text(window.mwsBulkStats.errors);
                                setTimeout(function() {
                                    processBulkChapters(postId, indices, currentIndex + 1, delay);
                                }, delay);
                            }
                        },
                        error: function() {
                            addBulkStatus(chapterTitle + ' — <?php esc_html_e('Save error', 'manhwa-scraper'); ?>', 'chapter log-error', '  ✗');
                            window.mwsBulkStats.errors++;
                            $('#mws-bulk-stat-errors').text(window.mwsBulkStats.errors);
                            // Continue with next chapter after delay
                            setTimeout(function() {
                                processBulkChapters(postId, indices, currentIndex + 1, delay);
                            }, delay);
                        }
                    });
                } else {
                    var errorUrl = response.data.url ? ' [' + response.data.url.substring(0, 50) + '...]' : '';
                    addBulkStatus(chapterTitle + ' — ' + (response.data.message || 'Unknown error'), 'chapter log-error', '✗');
                    window.mwsBulkStats.errors++;
                    $('#mws-bulk-stat-errors').text(window.mwsBulkStats.errors);
                    setTimeout(function() {
                        processBulkChapters(postId, indices, currentIndex + 1, delay);
                    }, delay);
                }
            },
            error: function(xhr, status, error) {
                var errorMsg = error;
                
                // Check if response is HTML (server returned error page/captcha instead of JSON)
                if (xhr.responseText && xhr.responseText.trim().charAt(0) === '<') {
                    if (xhr.responseText.indexOf('403') !== -1 || xhr.responseText.indexOf('Forbidden') !== -1) {
                        errorMsg = '<?php esc_html_e('Website blocked request (403). Try increasing delay.', 'manhwa-scraper'); ?>';
                    } else if (xhr.responseText.indexOf('captcha') !== -1 || xhr.responseText.indexOf('Cloudflare') !== -1) {
                        errorMsg = '<?php esc_html_e('Website showing captcha. Cannot scrape.', 'manhwa-scraper'); ?>';
                    } else if (xhr.responseText.indexOf('fatal error') !== -1 || xhr.responseText.indexOf('Fatal error') !== -1) {
                        errorMsg = '<?php esc_html_e('PHP Fatal Error. Check server logs.', 'manhwa-scraper'); ?>';
                    } else {
                        errorMsg = '<?php esc_html_e('Server returned HTML. Rate limited or blocked.', 'manhwa-scraper'); ?>';
                        // Log first 500 chars of response for debugging
                        console.log('Server Response (first 500 chars):', xhr.responseText.substring(0, 500));
                    }
                } else if (status === 'timeout') {
                    errorMsg = '<?php esc_html_e('Request timeout.', 'manhwa-scraper'); ?>';
                }
                
                addBulkStatus(chapterTitle + ' — ' + errorMsg, 'chapter log-error', '✗');
                window.mwsBulkStats.errors++;
                $('#mws-bulk-stat-errors').text(window.mwsBulkStats.errors);
                console.log('Full error details:', {status: status, error: error, responseText: xhr.responseText ? xhr.responseText.substring(0, 1000) : 'empty'});
                setTimeout(function() {
                    processBulkChapters(postId, indices, currentIndex + 1, delay);
                }, delay);
            }
        });
    }
    
    // Helper function to save chapter data
    function saveChapterData(chapterData, postId, chapterIndex, chapterTitle, callback) {
        $.ajax({
            url: mwsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mws_save_chapter_to_post',
                nonce: mwsData.nonce,
                post_id: postId,
                chapter_data: JSON.stringify(chapterData)
            },
            success: function(saveResponse) {
                if (saveResponse.success) {
                    bulkChaptersData[chapterIndex].images = chapterData.images;
                }
                callback();
            },
            error: function() {
                addBulkStatus(chapterTitle + ' — <?php esc_html_e('Save error', 'manhwa-scraper'); ?>', 'chapter log-error', '✗');
                window.mwsBulkStats.errors++;
                $('#mws-bulk-stat-errors').text(window.mwsBulkStats.errors);
                callback();
            }
        });
    }
    
    // Helper function to save chapter (for Scrape All)
    function saveChapterToPost(chapterData, postId, callback) {
        $.ajax({
            url: mwsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mws_save_chapter_to_post',
                nonce: mwsData.nonce,
                post_id: postId,
                chapter_data: JSON.stringify(chapterData)
            },
            success: function() {
                callback();
            },
            error: function() {
                callback();
            }
        });
    }
    
    function addBulkStatus(message, type, icon) {
        var now = new Date();
        var time = String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0') + ':' + String(now.getSeconds()).padStart(2,'0');
        
        var icons = {
            'info': '›', 'success': '✓', 'error': '✗', 'warning': '⚠',
            'chapter': '  ›', 'chapter log-success': '  ›', 'chapter log-error': '  ›'
        };
        var displayIcon = icon || icons[type] || '›';
        
        var entry = '<div class="mws-log-entry log-' + type + '">';
        entry += '<span class="mws-log-time">' + time + '</span>';
        entry += '<span class="mws-log-icon">' + displayIcon + '</span>';
        entry += '<span class="mws-log-text">' + message + '</span>';
        entry += '</div>';
        
        $('#mws-bulk-status').append(entry);
        var el = $('#mws-bulk-status')[0];
        if (el) el.scrollTop = el.scrollHeight;
    }
    
    // ========== SCRAPE ALL MANHWA FUNCTIONALITY ==========
    var allManhwaData = [];
    var allManhwaRunning = false;
    var allManhwaAborted = false;
    
    // Preview button
    $('#mws-all-preview-btn').on('click', function() {
        var filterType = $('#mws-all-manhwa-filter').val();
        var sourceFilter = $('#mws-all-source-filter').val();
        var maxManhwa = parseInt($('#mws-all-max-manhwa').val()) || 10;
        var chapterLimit = parseInt($('#mws-all-chapter-limit').val()) || 0;
        
        allManhwaData = [];
        
        // Get manhwa from the existing dropdown
        $('#mws-bulk-manhwa option').each(function() {
            var $opt = $(this);
            if ($opt.val() === '') return;
            
            var status = $opt.data('status') || '';
            var source = $opt.data('source') || 'unknown';
            var postId = $opt.val();
            var title = $opt.text();
            var totalChapters = parseInt($opt.data('total')) || 0;
            
            // Filter by chapter limit (0 = no limit)
            if (chapterLimit > 0 && totalChapters >= chapterLimit) {
                return; // Skip this manhwa
            }
            
            // Filter by source
            if (sourceFilter !== 'all' && source !== sourceFilter) {
                return; // Skip this manhwa
            }
            
            // Filter by status
            if (filterType === 'all' || status === filterType) {
                allManhwaData.push({
                    id: postId,
                    title: title,
                    status: status,
                    source: source,
                    chapters: totalChapters
                });
            }
        });
        
        // Limit to max
        if (allManhwaData.length > maxManhwa) {
            allManhwaData = allManhwaData.slice(0, maxManhwa);
        }
        
        // Update preview
        var $preview = $('#mws-all-preview').empty();
        if (allManhwaData.length === 0) {
            var msg = '<?php esc_html_e('No manhwa found matching this filter.', 'manhwa-scraper'); ?>';
            if (chapterLimit > 0) {
                msg += ' (with less than ' + chapterLimit + ' chapters)';
            }
            if (sourceFilter !== 'all') {
                msg += ' [Source: ' + sourceFilter + ']';
            }
            $preview.html('<p style="color: #dc3232;">' + msg + '</p>');
            $('#mws-all-start-btn').prop('disabled', true);
        } else {
            var html = '<p style="margin-bottom: 10px;"><strong>' + allManhwaData.length + ' <?php esc_html_e('manhwa will be processed:', 'manhwa-scraper'); ?></strong>';
            if (chapterLimit > 0) {
                html += ' <span style="color: #8B5CF6;">(under ' + chapterLimit + ' chapters)</span>';
            }
            if (sourceFilter !== 'all') {
                html += ' <span style="color: #2271b1;">[' + sourceFilter + ']</span>';
            }
            html += '</p>';
            html += '<div style="max-height: 150px; overflow-y: auto; font-size: 12px;">';
            allManhwaData.forEach(function(m, i) {
                html += '<div>' + (i+1) + '. ' + m.title + ' <span style="color: #666;">(' + m.chapters + ' ch)</span></div>';
            });
            html += '</div>';
            $preview.html(html);
            $('#mws-all-start-btn').prop('disabled', false);
        }
    });

    
    // Start Scraping All
    $('#mws-all-start-btn').on('click', function() {
        if (allManhwaData.length === 0) {
            alert('<?php esc_html_e('No manhwa selected. Click Preview first.', 'manhwa-scraper'); ?>');
            return;
        }
        
        var filterType = $('#mws-all-manhwa-filter').val();
        var downloadLocal = $('#mws-all-download-local').is(':checked');
        var delayVal = parseInt($('#mws-all-delay').val()) || 3;
        var parallelCh = parseInt($('#mws-all-parallel-chapters').val()) || 10;
        var maxManhwa = parseInt($('#mws-all-max-manhwa').val()) || 50;
        
        // Count total chapters
        var totalChapters = 0;
        allManhwaData.forEach(function(m) { totalChapters += (m.chapters || 0); });
        
        // Estimate time
        var estSeconds = allManhwaData.length * (Math.ceil((totalChapters / allManhwaData.length) / parallelCh) * (delayVal + 3) + 5);
        var estMin = Math.floor(estSeconds / 60);
        var estTime = estMin >= 60 ? Math.floor(estMin / 60) + 'h ' + (estMin % 60) + 'm' : (estMin > 0 ? estMin + ' min' : '~' + estSeconds + ' sec');
        
        var modeLabel = filterType === 'has-external' ? '<?php esc_html_e('Download External → Local', 'manhwa-scraper'); ?>' : '<?php esc_html_e('Scrape New Chapters', 'manhwa-scraper'); ?>';
        
        mwsConfirm({
            icon: '📚',
            title: '<?php esc_html_e('Bulk Scrape All Manhwa', 'manhwa-scraper'); ?>',
            subtitle: allManhwaData.length + ' <?php esc_html_e('manhwa selected', 'manhwa-scraper'); ?>',
            color: 'purple',
            confirmText: '🚀 <?php esc_html_e('Start Scraping', 'manhwa-scraper'); ?>',
            rows: [
                { icon: '📚', label: '<?php esc_html_e('Manhwa', 'manhwa-scraper'); ?>', value: allManhwaData.length, valueClass: 'mws-val-purple' },
                { icon: '📄', label: '<?php esc_html_e('Chapters', 'manhwa-scraper'); ?>', value: '~' + totalChapters, valueClass: 'mws-val-blue' },
                { icon: '🎯', label: '<?php esc_html_e('Mode', 'manhwa-scraper'); ?>', value: modeLabel },
                { icon: '⬇', label: '<?php esc_html_e('Download Local', 'manhwa-scraper'); ?>', value: downloadLocal ? '<?php esc_html_e('Yes', 'manhwa-scraper'); ?> ✅' : '<?php esc_html_e('No', 'manhwa-scraper'); ?> ❌', valueClass: downloadLocal ? 'mws-val-green' : 'mws-val-red' },
                { icon: '⚡', label: '<?php esc_html_e('Parallel', 'manhwa-scraper'); ?>', value: parallelCh + ' <?php esc_html_e('chapters at a time', 'manhwa-scraper'); ?>' },
                { icon: '⏱', label: '<?php esc_html_e('Delay', 'manhwa-scraper'); ?>', value: delayVal + ' sec' },
                { icon: '⏳', label: '<?php esc_html_e('Estimated', 'manhwa-scraper'); ?>', value: estTime, valueClass: 'mws-val-yellow' }
            ],
            warning: '<?php esc_html_e('This may take a very long time. Keep this tab open and do not close the browser.', 'manhwa-scraper'); ?>'
        }).then(function(confirmed) {
            if (!confirmed) return;
        
        allManhwaRunning = true;
        allManhwaAborted = false;
        
        // Reset live stats
        window.mwsScrapeStats = { scraped: 0, downloaded: 0, images: 0, errors: 0 };
        $('#mws-stat-scraped-count').text('0');
        $('#mws-stat-downloaded-count').text('0');
        $('#mws-stat-images-count').text('0');
        $('#mws-stat-errors-count').text('0');
        
        // Start elapsed timer
        window.mwsScrapeStartTime = Date.now();
        window.mwsElapsedTimer = setInterval(function() {
            var elapsed = Math.floor((Date.now() - window.mwsScrapeStartTime) / 1000);
            var m = Math.floor(elapsed / 60);
            var s = elapsed % 60;
            $('#mws-all-elapsed-time').text((m > 0 ? m + 'm ' : '') + s + 's');
        }, 1000);
        
        $('#mws-all-start-btn').hide();
        $('#mws-all-preview-btn').hide();
        $('#mws-all-stop-btn').show();
        $('#mws-all-progress').show();
        $('#mws-all-status').empty();
        
        addAllStatus('<?php esc_html_e('Starting scrape for', 'manhwa-scraper'); ?> ' + allManhwaData.length + ' <?php esc_html_e('manhwa...', 'manhwa-scraper'); ?>', 'info', '🚀');
        
        var delay = parseInt($('#mws-all-delay').val()) * 1000 || 3000;
        
        processAllManhwa(0, delay, downloadLocal, filterType);
        }); // end .then()
    });
    
    // Stop button
    $('#mws-all-stop-btn').on('click', function() {
        allManhwaAborted = true;
            addAllStatus('<?php esc_html_e('Stopping after current chapter finishes...', 'manhwa-scraper'); ?>', 'warning', '⏹️');
    });
    
    // Process all manhwa one by one
    function processAllManhwa(index, delay, downloadLocal, filterType) {
        if (allManhwaAborted || index >= allManhwaData.length) {
            allManhwaRunning = false;
            $('#mws-all-stop-btn').hide();
            $('#mws-all-start-btn').show();
            $('#mws-all-preview-btn').show();
            
            if (allManhwaAborted) {
                addAllStatus('<?php esc_html_e('Stopped by user', 'manhwa-scraper'); ?>', 'warning', '⏹️');
            } else {
                addAllStatus('<?php esc_html_e('All manhwa processed successfully!', 'manhwa-scraper'); ?>', 'success', '✅');
            }
            
            // Auto-refresh stats after completion
            // Stop elapsed timer
            if (window.mwsElapsedTimer) clearInterval(window.mwsElapsedTimer);
            
            setTimeout(function() {
                refreshStats();
            }, 1000);
            
            return;
        }
        
        var manhwa = allManhwaData[index];
        var progress = Math.round(((index + 1) / allManhwaData.length) * 100);
        
        $('#mws-all-current-manhwa').text(manhwa.title);
        $('#mws-all-manhwa-progress').text((index + 1) + '/' + allManhwaData.length);
        $('#mws-all-progress-bar').css('width', progress + '%');
        $('#mws-all-progress-percent').text(progress + '%');
        $('#mws-current-chapter-badge').hide();
        
        addAllStatus(manhwa.title + ' <span class="mws-log-badge mws-log-badge-purple">' + (manhwa.chapters || '?') + ' ch</span>', 'manhwa', '📚');
        
        // First, get chapters for this manhwa
        $.ajax({
            url: mwsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mws_get_manhwa_chapters',
                nonce: mwsData.nonce,
                post_id: manhwa.id
            },
            success: function(response) {
                if (response.success && response.data.chapters) {
                    var chapters = response.data.chapters;
                    
                    // Filter chapters based on mode
                    var chaptersToProcess = [];
                    
                    chapters.forEach(function(ch, i) {
                        var hasImages = ch.images && ch.images.length > 0;
                        var hasUrl = ch.url && ch.url.trim() !== '';
                        var isLocal = false;
                        
                        if (hasImages) {
                            var firstImg = ch.images[0];
                            var imgUrl = typeof firstImg === 'object' ? (firstImg.url || firstImg.src || '') : firstImg;
                            isLocal = imgUrl.indexOf('/wp-content/uploads/manhwa/') !== -1;
                        }
                        
                        // Determine which chapters to process based on filter
                        if (filterType === 'has-external' && hasImages && !isLocal) {
                            // Download external to local
                            chaptersToProcess.push({
                                index: i,
                                chapter: ch,
                                mode: 'download'
                            });
                        } else if (filterType === 'download-only' && hasImages && !isLocal) {
                            // Download only - only download existing external images to local
                            chaptersToProcess.push({
                                index: i,
                                chapter: ch,
                                mode: 'download'
                            });
                        } else if (filterType === 'need-images' && !hasImages && hasUrl) {
                            // Scrape new
                            chaptersToProcess.push({
                                index: i,
                                chapter: ch,
                                mode: 'scrape'
                            });
                        } else if (filterType === 'missing-external' && !hasImages && hasUrl) {
                            // Scrape chapters that don't have images yet (partial scrape)
                            chaptersToProcess.push({
                                index: i,
                                chapter: ch,
                                mode: 'scrape'
                            });
                        } else if (filterType === 'scrape-only' && !hasImages && hasUrl) {
                            // Scrape only - scrape then download to local automatically
                            chaptersToProcess.push({
                                index: i,
                                chapter: ch,
                                mode: 'scrape'
                            });
                        } else if (filterType === 'partial-download' || filterType === 'all') {
                            if (!isLocal && hasUrl) {
                                chaptersToProcess.push({
                                    index: i,
                                    chapter: ch,
                                    mode: hasImages ? 'download' : 'scrape'
                                });
                            }
                        }
                    });
                    
                    if (chaptersToProcess.length === 0) {
                        addAllStatus('<?php esc_html_e('No chapters to process, skipping', 'manhwa-scraper'); ?>', 'chapter', '⏭️');
                        setTimeout(function() {
                            processAllManhwa(index + 1, delay, downloadLocal, filterType);
                        }, delay);
                    } else {
                        addAllStatus(chaptersToProcess.length + ' <?php esc_html_e('chapters to process', 'manhwa-scraper'); ?>', 'chapter', '📑');
                        $('#mws-current-chapter-badge').show();
                        $('#mws-current-chapter-text').text('0/' + chaptersToProcess.length);
                        processAllChapters(manhwa.id, chaptersToProcess, 0, downloadLocal, function() {
                            setTimeout(function() {
                                processAllManhwa(index + 1, delay, downloadLocal, filterType);
                            }, delay);
                        });
                    }
                } else {
                    addAllStatus('<?php esc_html_e('Failed to get chapters', 'manhwa-scraper'); ?>', 'error', '✗');
                    window.mwsScrapeStats.errors++;
                    $('#mws-stat-errors-count').text(window.mwsScrapeStats.errors);
                    setTimeout(function() {
                        processAllManhwa(index + 1, delay, downloadLocal, filterType);
                    }, delay);
                }
            },
            error: function() {
                addAllStatus('<?php esc_html_e('Error loading chapters', 'manhwa-scraper'); ?>', 'error', '✗');
                    window.mwsScrapeStats.errors++;
                    $('#mws-stat-errors-count').text(window.mwsScrapeStats.errors);
                setTimeout(function() {
                    processAllManhwa(index + 1, delay, downloadLocal, filterType);
                }, delay);
            }
        });
    }
    
    // Process chapters for a single manhwa (PARALLEL version)
    function processAllChapters(postId, chapters, startIndex, downloadLocal, callback) {
        if (allManhwaAborted || startIndex >= chapters.length) {
            callback();
            return;
        }
        
        var parallelCount = parseInt($('#mws-all-parallel-chapters').val()) || 3;
        var endIndex = Math.min(startIndex + parallelCount, chapters.length);
        var batch = chapters.slice(startIndex, endIndex);
        var completed = 0;
        
        batch.forEach(function(item) {
            processSingleChapter(postId, item, downloadLocal, function() {
                completed++;
                // Update chapter badge
                var done = startIndex + completed;
                $('#mws-current-chapter-text').text(done + '/' + chapters.length);
                if (completed >= batch.length) {
                    // All in batch completed, process next batch
                    if (!allManhwaAborted) {
                        setTimeout(function() {
                            processAllChapters(postId, chapters, endIndex, downloadLocal, callback);
                        }, 200);
                    } else {
                        callback();
                    }
                }
            });
        });
    }
    
    // Process a single chapter
    function processSingleChapter(postId, item, downloadLocal, callback) {
        var chapter = item.chapter;
        var chapterTitle = chapter.title || chapter.number || 'Chapter ' + (item.index + 1);
        
        if (item.mode === 'download') {
            // Download external to local
            var chapterData = {
                chapter_number: chapter.number || (item.index + 1),
                chapter_title: chapterTitle,
                title: chapter.title,
                url: chapter.url,
                images: chapter.images.map(function(img, idx) {
                    return {
                        index: idx,
                        url: typeof img === 'object' ? (img.url || img.src || img) : img,
                        alt: 'Page ' + (idx + 1)
                    };
                })
            };
            
            $.ajax({
                url: mwsData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mws_download_chapter_images',
                    nonce: mwsData.nonce,
                    post_id: postId,
                    chapter_data: JSON.stringify(chapterData)
                },
                success: function(response) {
                    if (response.success) {
                        var r = response.data.result;
                        addAllStatus(chapterTitle + ' <span class="mws-log-badge mws-log-badge-blue">⬇ ' + r.success + ' img</span>', 'chapter log-success', '✓');
                        window.mwsScrapeStats.downloaded++;
                        window.mwsScrapeStats.images += (r.success || 0);
                        $('#mws-stat-downloaded-count').text(window.mwsScrapeStats.downloaded);
                        $('#mws-stat-images-count').text(window.mwsScrapeStats.images);
                    } else {
                        addAllStatus(chapterTitle + ' — <?php esc_html_e('Download failed', 'manhwa-scraper'); ?>', 'chapter log-error', '✗');
                        window.mwsScrapeStats.errors++;
                        $('#mws-stat-errors-count').text(window.mwsScrapeStats.errors);
                    }
                    callback();
                },
                error: function() {
                    addAllStatus(chapterTitle + ' — <?php esc_html_e('Error', 'manhwa-scraper'); ?>', 'chapter log-error', '✗');
                    window.mwsScrapeStats.errors++;
                    $('#mws-stat-errors-count').text(window.mwsScrapeStats.errors);
                    callback();
                }
            });
        } else {
            // Scrape new chapter
            $.ajax({
                url: mwsData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mws_scrape_chapter_images',
                    nonce: mwsData.nonce,
                    url: chapter.url
                },
                success: function(response) {
                    if (response.success && response.data.data) {
                        var imgCount = response.data.data.images ? response.data.data.images.length : 0;
                        
                        // Download first if enabled
                        if (downloadLocal && imgCount > 0) {
                            $.ajax({
                                url: mwsData.ajaxUrl,
                                type: 'POST',
                                data: {
                                    action: 'mws_download_chapter_images',
                                    nonce: mwsData.nonce,
                                    post_id: postId,
                                    chapter_data: JSON.stringify(response.data.data)
                                },
                                success: function(dlResponse) {
                                    if (dlResponse.success) {
                                        var r = dlResponse.data.result;
                                        addAllStatus(chapterTitle + ' <span class="mws-log-badge mws-log-badge-green">' + imgCount + ' img</span> <span class="mws-log-badge mws-log-badge-blue">⬇ ' + r.success + ' saved</span>', 'chapter log-success', '✓');
                                        window.mwsScrapeStats.scraped++;
                                        window.mwsScrapeStats.downloaded++;
                                        window.mwsScrapeStats.images += imgCount;
                                        $('#mws-stat-scraped-count').text(window.mwsScrapeStats.scraped);
                                        $('#mws-stat-downloaded-count').text(window.mwsScrapeStats.downloaded);
                                        $('#mws-stat-images-count').text(window.mwsScrapeStats.images);
                                        
                                        // Save with local URLs
                                        var chapterDataWithLocal = Object.assign({}, response.data.data);
                                        if (dlResponse.data.local_images && dlResponse.data.local_images.length > 0) {
                                            chapterDataWithLocal.images = dlResponse.data.local_images;
                                        }
                                        
                                        $.ajax({
                                            url: mwsData.ajaxUrl,
                                            type: 'POST',
                                            data: {
                                                action: 'mws_save_chapter_to_post',
                                                nonce: mwsData.nonce,
                                                post_id: postId,
                                                chapter_data: JSON.stringify(chapterDataWithLocal)
                                            },
                                            success: function() {
                                                callback();
                                            },
                                            error: function() {
                                                callback();
                                            }
                                        });
                                    } else {
                                        addAllStatus(chapterTitle + ' <span class="mws-log-badge mws-log-badge-green">' + imgCount + ' img</span> <span class="mws-log-badge mws-log-badge-purple">ext</span>', 'chapter log-success', '✓');
                                        window.mwsScrapeStats.scraped++;
                                        window.mwsScrapeStats.images += imgCount;
                                        $('#mws-stat-scraped-count').text(window.mwsScrapeStats.scraped);
                                        $('#mws-stat-images-count').text(window.mwsScrapeStats.images);
                                        saveChapterToPost(response.data.data, postId, callback);
                                    }
                                },
                                error: function() {
                                    addAllStatus(chapterTitle + ' <span class="mws-log-badge mws-log-badge-green">' + imgCount + ' img</span> <span class="mws-log-badge mws-log-badge-purple">ext</span>', 'chapter log-success', '✓');
                                    window.mwsScrapeStats.scraped++;
                                    window.mwsScrapeStats.images += imgCount;
                                    $('#mws-stat-scraped-count').text(window.mwsScrapeStats.scraped);
                                    $('#mws-stat-images-count').text(window.mwsScrapeStats.images);
                                    saveChapterToPost(response.data.data, postId, callback);
                                }
                            });
                        } else {
                            addAllStatus(chapterTitle + ' <span class="mws-log-badge mws-log-badge-green">' + imgCount + ' img</span>', 'chapter log-success', '✓');
                            window.mwsScrapeStats.scraped++;
                            window.mwsScrapeStats.images += imgCount;
                            $('#mws-stat-scraped-count').text(window.mwsScrapeStats.scraped);
                            $('#mws-stat-images-count').text(window.mwsScrapeStats.images);
                            saveChapterToPost(response.data.data, postId, callback);
                        }
                    } else {
                        addAllStatus(chapterTitle + ' — <?php esc_html_e('failed', 'manhwa-scraper'); ?>', 'chapter log-error', '✗');
                        window.mwsScrapeStats.errors++;
                        $('#mws-stat-errors-count').text(window.mwsScrapeStats.errors);
                        callback();
                    }
                },
                error: function() {
                    addAllStatus(chapterTitle + ' — <?php esc_html_e('error', 'manhwa-scraper'); ?>', 'chapter log-error', '✗');
                    window.mwsScrapeStats.errors++;
                    $('#mws-stat-errors-count').text(window.mwsScrapeStats.errors);
                    callback();
                }
            });
        }
    }
    
    function addAllStatus(message, type, icon) {
        var now = new Date();
        var time = String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0') + ':' + String(now.getSeconds()).padStart(2,'0');
        
        var icons = {
            'info': '›',
            'success': '✓',
            'error': '✗',
            'warning': '⚠',
            'manhwa': '📚',
            'chapter': '  ›',
            'chapter log-success': '  ›',
            'chapter log-error': '  ›'
        };
        var displayIcon = icon || icons[type] || '›';
        
        var entry = '<div class="mws-log-entry log-' + type + '">';
        entry += '<span class="mws-log-time">' + time + '</span>';
        entry += '<span class="mws-log-icon">' + displayIcon + '</span>';
        entry += '<span class="mws-log-text">' + message + '</span>';
        entry += '</div>';
        
        $('#mws-all-status').append(entry);
        var el = $('#mws-all-status')[0];
        if (el) el.scrollTop = el.scrollHeight;
    }
    
    // Auto-refresh stats function
    function refreshStats() {
        addAllStatus('<?php esc_html_e('Refreshing stats...', 'manhwa-scraper'); ?>', 'info', '🔄');
        
        $.ajax({
            url: mwsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mws_get_manhwa_stats',
                nonce: mwsData.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    var stats = response.data;
                    
                    // Update Bulk Scrape dropdown options
                    var $bulkSelect = $('#mws-bulk-manhwa');
                    if ($bulkSelect.length && stats.manhwa_list) {
                        var currentVal = $bulkSelect.val();
                        $bulkSelect.find('option:not(:first)').remove();
                        stats.manhwa_list.forEach(function(m) {
                            $bulkSelect.append('<option value="' + m.id + '" data-status="' + m.status_type + '">' + m.display + '</option>');
                        });
                        if (currentVal) $bulkSelect.val(currentVal);
                    }
                    
                    // Update Summary in Bulk Scrape
                    var $summary = $('.mws-card:has(#mws-bulk-manhwa) div:contains("Summary:")');
                    if ($summary.length) {
                        $summary.html('<strong><?php esc_html_e('Summary:', 'manhwa-scraper'); ?></strong>' +
                            '<span style="margin-left: 10px;">🔴 ' + stats['need-images'] + ' need images</span>' +
                            '<span style="margin-left: 10px;">🟣 ' + stats['missing-external'] + ' missing ext</span>' +
                            '<span style="margin-left: 10px;">🟡 ' + stats['has-external'] + ' external</span>' +
                            '<span style="margin-left: 10px;">🟠 ' + stats['partial-download'] + ' partial</span>' +
                            '<span style="margin-left: 10px;">🟢 ' + stats['all-downloaded'] + ' complete</span>' +
                            '<span style="margin-left: 10px;">⚪ ' + stats['no-chapters'] + ' no ch</span>'
                        );
                    }
                    
                    // Update Bulk All dropdown
                    var $allFilter = $('#mws-all-manhwa-filter');
                    if ($allFilter.length) {
                        $allFilter.find('option[value="need-images"]').text('🔴 <?php esc_html_e('Need Images', 'manhwa-scraper'); ?> (' + stats['need-images'] + ')');
                        $allFilter.find('option[value="missing-external"]').text('🟣 <?php esc_html_e('Missing External', 'manhwa-scraper'); ?> (' + stats['missing-external'] + ')');
                        $allFilter.find('option[value="scrape-only"]').text('🔵 <?php esc_html_e('Scrape Only', 'manhwa-scraper'); ?> (' + (stats['need-images'] + stats['missing-external']) + ')');
                        $allFilter.find('option[value="has-external"]').text('🟡 <?php esc_html_e('Has External', 'manhwa-scraper'); ?> (' + stats['has-external'] + ')');
                        $allFilter.find('option[value="download-only"]').text('⬇️ <?php esc_html_e('Download Only', 'manhwa-scraper'); ?> (' + (stats['has-external'] + stats['partial-download']) + ')');
                        $allFilter.find('option[value="partial-download"]').text('🟠 <?php esc_html_e('Partial Download', 'manhwa-scraper'); ?> (' + stats['partial-download'] + ')');
                        $allFilter.find('option[value="all"]').text('📋 <?php esc_html_e('All Manhwa', 'manhwa-scraper'); ?> (' + stats['total'] + ')');
                    }
                    
                    addAllStatus('<?php esc_html_e('Stats refreshed!', 'manhwa-scraper'); ?>', 'success', '✅');
                }
            },
            error: function() {
                addAllStatus('<?php esc_html_e('Failed to refresh stats', 'manhwa-scraper'); ?>', 'error', '❌');
            }
        });
    }
    
    // ========== URL MIGRATION TOOL ==========
    var migrateData = [];
    
    // Scan for old URLs
    $('#mws-migrate-scan-btn').on('click', function() {
        var findPattern = $('#mws-migrate-find').val().trim();
        var replaceWith = $('#mws-migrate-replace').val().trim();
        
        if (!findPattern) {
            alert('<?php esc_html_e('Please enter the URL pattern to find', 'manhwa-scraper'); ?>');
            return;
        }
        
        var $spinner = $('#mws-migrate-spinner');
        $spinner.addClass('is-active');
        $('#mws-migrate-scan-btn').prop('disabled', true);
        
        $.ajax({
            url: mwsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mws_migrate_scan_urls',
                nonce: mwsData.nonce,
                find_pattern: findPattern,
                replace_with: replaceWith,
                include_source_url: $('#mws-migrate-source-url').is(':checked') ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    migrateData = response.data.manhwa_list || [];
                    var totalChapters = response.data.total_chapters || 0;
                    var totalManhwa = response.data.total_manhwa || 0;
                    var totalSourceUrls = response.data.total_source_urls || 0;
                    
                    if (totalChapters === 0 && totalSourceUrls === 0) {
                        $('#mws-migrate-preview-content').html('<p style="color: #666;"><?php esc_html_e('No URLs found matching the pattern.', 'manhwa-scraper'); ?></p>');
                        $('#mws-migrate-stats').html('');
                        $('#mws-migrate-execute-btn').prop('disabled', true);
                    } else {
                        var html = '';
                        migrateData.forEach(function(m) {
                            html += '<div style="padding: 5px 0; border-bottom: 1px solid #eee;">';
                            html += '<strong>' + m.title + '</strong>';
                            html += ' <span style="color: #666;">(' + m.chapter_count + ' chapters';
                            if (m.source_url_match) {
                                html += ', source URL match';
                            }
                            html += ')</span>';
                            html += '</div>';
                        });
                        $('#mws-migrate-preview-content').html(html);
                        
                        var statsHtml = '📊 ' + totalManhwa + ' manhwa, ' + totalChapters + ' chapters';
                        if (totalSourceUrls > 0) {
                            statsHtml += ', ' + totalSourceUrls + ' source URLs';
                        }
                        statsHtml += ' will be updated.';
                        statsHtml += '<br><code>' + findPattern + '</code> → <code>' + replaceWith + '</code>';
                        $('#mws-migrate-stats').html(statsHtml);
                        
                        $('#mws-migrate-execute-btn').prop('disabled', false);
                    }
                    
                    $('#mws-migrate-preview').show();
                } else {
                    alert(response.data.message || 'Error scanning URLs');
                }
            },
            error: function(xhr, status, error) {
                alert('Error: ' + error);
            },
            complete: function() {
                $spinner.removeClass('is-active');
                $('#mws-migrate-scan-btn').prop('disabled', false);
            }
        });
    });
    
    // Execute Migration
    $('#mws-migrate-execute-btn').on('click', function() {
        if (migrateData.length === 0) {
            alert('<?php esc_html_e('No URLs to migrate. Please scan first.', 'manhwa-scraper'); ?>');
            return;
        }
        
        if (!confirm('<?php esc_html_e('Are you sure you want to update all matching URLs? This cannot be undone.', 'manhwa-scraper'); ?>')) {
            return;
        }
        
        var findPattern = $('#mws-migrate-find').val().trim();
        var replaceWith = $('#mws-migrate-replace').val().trim();
        var includeSourceUrl = $('#mws-migrate-source-url').is(':checked') ? 1 : 0;
        
        var $spinner = $('#mws-migrate-spinner');
        $spinner.addClass('is-active');
        $('#mws-migrate-execute-btn').prop('disabled', true);
        $('#mws-migrate-scan-btn').prop('disabled', true);
        $('#mws-migrate-progress').show();
        
        var totalManhwa = migrateData.length;
        var processedManhwa = 0;
        var successCount = 0;
        var errorCount = 0;
        
        function processMigrateManhwa(index) {
            if (index >= migrateData.length) {
                // Done
                $spinner.removeClass('is-active');
                $('#mws-migrate-execute-btn').prop('disabled', true);
                $('#mws-migrate-scan-btn').prop('disabled', false);
                $('#mws-migrate-status').html('✅ <?php esc_html_e('Migration complete!', 'manhwa-scraper'); ?> ' + successCount + ' success, ' + errorCount + ' errors');
                $('#mws-migrate-progress-bar').css('width', '100%').text('100%');
                return;
            }
            
            var manhwa = migrateData[index];
            var progress = Math.round((index / totalManhwa) * 100);
            $('#mws-migrate-progress-bar').css('width', progress + '%').text(progress + '%');
            $('#mws-migrate-status').text('Processing: ' + manhwa.title + ' (' + (index + 1) + '/' + totalManhwa + ')');
            
            $.ajax({
                url: mwsData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mws_migrate_execute',
                    nonce: mwsData.nonce,
                    post_id: manhwa.id,
                    find_pattern: findPattern,
                    replace_with: replaceWith,
                    include_source_url: includeSourceUrl
                },
                success: function(response) {
                    if (response.success) {
                        successCount++;
                    } else {
                        errorCount++;
                    }
                },
                error: function() {
                    errorCount++;
                },
                complete: function() {
                    processedManhwa++;
                    processMigrateManhwa(index + 1);
                }
            });
        }
        
        processMigrateManhwa(0);
    });
    
    // ==========================================
    // Fix Broken Images Section
    // ==========================================
    var brokenChaptersData = [];
    var currentBrokenManhwaId = null;
    var brokenFixStopped = false;
    
    // Scan for broken images
    $('#mws-broken-scan-btn').on('click', function() {
        var manhwaId = $('#mws-broken-manhwa').val();
        var sampleSize = parseInt($('#mws-broken-sample-size').val());
        
        if (!manhwaId) {
            alert('<?php esc_html_e('Please select a manhwa to scan.', 'manhwa-scraper'); ?>');
            return;
        }
        
        var $spinner = $('#mws-broken-spinner');
        $spinner.addClass('is-active');
        $(this).prop('disabled', true);
        $('#mws-broken-fix-btn').prop('disabled', true);
        $('#mws-broken-chapters-list').hide();
        brokenChaptersData = [];
        currentBrokenManhwaId = manhwaId;
        
        // Update stats display
        $('#mws-broken-stats').html('<p style="margin: 0;"><span class="spinner is-active" style="float: none; margin: 0 10px 0 0;"></span><?php esc_html_e('Scanning for broken images...', 'manhwa-scraper'); ?></p>');
        
        $.ajax({
            url: mwsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mws_scan_broken_images',
                nonce: mwsData.nonce,
                manhwa_id: manhwaId,
                sample_size: sampleSize
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    brokenChaptersData = data.broken_chapters || [];
                    
                    // Update stats
                    var statsHtml = '<h4 style="margin: 0 0 10px 0; color: #c62828;"><span class="dashicons dashicons-info"></span> <?php esc_html_e('Scan Results', 'manhwa-scraper'); ?></h4>';
                    statsHtml += '<p style="margin: 5px 0;"><strong><?php esc_html_e('Manhwa:', 'manhwa-scraper'); ?></strong> ' + data.manhwa_title + '</p>';
                    statsHtml += '<p style="margin: 5px 0;"><strong><?php esc_html_e('Total Chapters:', 'manhwa-scraper'); ?></strong> ' + data.total_chapters + '</p>';
                    statsHtml += '<p style="margin: 5px 0; color: green;"><strong><?php esc_html_e('Healthy:', 'manhwa-scraper'); ?></strong> ' + data.healthy_chapters + '</p>';
                    statsHtml += '<p style="margin: 5px 0; color: red;"><strong><?php esc_html_e('Broken:', 'manhwa-scraper'); ?></strong> ' + brokenChaptersData.length + '</p>';
                    statsHtml += '<p style="margin: 5px 0; color: #999;"><strong><?php esc_html_e('No Images:', 'manhwa-scraper'); ?></strong> ' + data.no_images_chapters + '</p>';
                    $('#mws-broken-stats').html(statsHtml);
                    
                    // Populate broken chapters list
                    if (brokenChaptersData.length > 0) {
                        var tbody = $('#mws-broken-chapters-tbody');
                        tbody.empty();
                        
                        brokenChaptersData.forEach(function(chapter, index) {
                            var errorText = chapter.broken_urls && chapter.broken_urls.length > 0 
                                ? chapter.broken_urls[0].error 
                                : 'Unknown';
                            
                            var sourceUrl = chapter.source_url || '';
                            var hasSource = sourceUrl ? '✓' : '✗';
                            var sourceClass = sourceUrl ? 'color: green;' : 'color: red;';
                            
                            tbody.append(
                                '<tr data-index="' + index + '" data-chapter-index="' + chapter.chapter_index + '">' +
                                '<td><input type="checkbox" class="broken-chapter-checkbox" checked></td>' +
                                '<td>Chapter ' + chapter.chapter_number + '</td>' +
                                '<td>' + chapter.total_images + ' images</td>' +
                                '<td style="color: red; font-size: 11px;">' + errorText + '</td>' +
                                '<td class="broken-status"><span style="' + sourceClass + '">' + hasSource + ' Source</span></td>' +
                                '</tr>'
                            );
                        });
                        
                        $('#mws-broken-chapters-list').show();
                        $('#mws-broken-fix-btn').prop('disabled', false);
                    } else {
                        $('#mws-broken-stats').append('<p style="margin-top: 10px; color: green;"><strong>✓ <?php esc_html_e('All images are accessible!', 'manhwa-scraper'); ?></strong></p>');
                    }
                } else {
                    alert(response.data.message || 'Error scanning');
                }
            },
            error: function(xhr, status, error) {
                alert('Error: ' + error);
            },
            complete: function() {
                $spinner.removeClass('is-active');
                $('#mws-broken-scan-btn').prop('disabled', false);
            }
        });
    });
    
    // Select all broken chapters
    $('#mws-broken-select-all').on('change', function() {
        $('.broken-chapter-checkbox').prop('checked', $(this).is(':checked'));
    });
    
    // Fix selected broken chapters
    $('#mws-broken-fix-btn').on('click', function() {
        var selectedChapters = [];
        
        $('.broken-chapter-checkbox:checked').each(function() {
            var $row = $(this).closest('tr');
            var chapterIndex = $row.data('chapter-index');
            var listIndex = $row.data('index');
            selectedChapters.push({
                chapter_index: chapterIndex,
                list_index: listIndex
            });
        });
        
        if (selectedChapters.length === 0) {
            alert('<?php esc_html_e('Please select at least one chapter to fix.', 'manhwa-scraper'); ?>');
            return;
        }
        
        if (!confirm('<?php esc_html_e('This will re-scrape images for', 'manhwa-scraper'); ?> ' + selectedChapters.length + ' <?php esc_html_e('chapters. Continue?', 'manhwa-scraper'); ?>')) {
            return;
        }
        
        var downloadLocal = $('#mws-broken-download-local').is(':checked');
        var $spinner = $('#mws-broken-spinner');
        
        brokenFixStopped = false;
        $spinner.addClass('is-active');
        $('#mws-broken-fix-btn').prop('disabled', true);
        $('#mws-broken-scan-btn').prop('disabled', true);
        $('#mws-broken-stop-btn').show();
        $('#mws-broken-progress').show();
        
        var totalToFix = selectedChapters.length;
        var currentIndex = 0;
        var successCount = 0;
        var errorCount = 0;
        
        function fixNextChapter() {
            if (brokenFixStopped || currentIndex >= selectedChapters.length) {
                // Done
                $spinner.removeClass('is-active');
                $('#mws-broken-fix-btn').prop('disabled', false);
                $('#mws-broken-scan-btn').prop('disabled', false);
                $('#mws-broken-stop-btn').hide();
                
                var doneMsg = brokenFixStopped ? '⚠️ <?php esc_html_e('Stopped by user.', 'manhwa-scraper'); ?>' : '✅ <?php esc_html_e('Done!', 'manhwa-scraper'); ?>';
                $('#mws-broken-status').html(doneMsg + ' <?php esc_html_e('Success:', 'manhwa-scraper'); ?> ' + successCount + ', <?php esc_html_e('Errors:', 'manhwa-scraper'); ?> ' + errorCount);
                $('#mws-broken-progress-bar').css('width', '100%').text('100%');
                return;
            }
            
            var chapter = selectedChapters[currentIndex];
            var brokenChapter = brokenChaptersData[chapter.list_index];
            
            var progress = Math.round((currentIndex / totalToFix) * 100);
            $('#mws-broken-progress-bar').css('width', progress + '%').text(progress + '%');
            $('#mws-broken-status').text('<?php esc_html_e('Fixing Chapter', 'manhwa-scraper'); ?> ' + brokenChapter.chapter_number + ' (' + (currentIndex + 1) + '/' + totalToFix + ')');
            
            // Update row status
            var $row = $('tr[data-index="' + chapter.list_index + '"]');
            $row.find('.broken-status').html('<span style="color: orange;">⏳ <?php esc_html_e('Fixing...', 'manhwa-scraper'); ?></span>');
            
            $.ajax({
                url: mwsData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mws_fix_broken_chapter',
                    nonce: mwsData.nonce,
                    manhwa_id: currentBrokenManhwaId,
                    chapter_index: chapter.chapter_index,
                    download_local: downloadLocal ? 1 : 0
                },
                timeout: 120000, // 2 minutes timeout
                success: function(response) {
                    if (response.success) {
                        successCount++;
                        $row.find('.broken-status').html('<span style="color: green;">✅ <?php esc_html_e('Fixed!', 'manhwa-scraper'); ?> (' + response.data.images_count + ' img)</span>');
                        $row.css('background', '#e8f5e9');
                    } else {
                        errorCount++;
                        $row.find('.broken-status').html('<span style="color: red;">❌ ' + (response.data.message || 'Error') + '</span>');
                        $row.css('background', '#ffebee');
                    }
                },
                error: function(xhr, status, error) {
                    errorCount++;
                    $row.find('.broken-status').html('<span style="color: red;">❌ ' + error + '</span>');
                    $row.css('background', '#ffebee');
                },
                complete: function() {
                    currentIndex++;
                    // Add delay between requests
                    setTimeout(fixNextChapter, 2000);
                }
            });
        }
        
        fixNextChapter();
    });
    
    // Stop fixing
    $('#mws-broken-stop-btn').on('click', function() {
        brokenFixStopped = true;
        $(this).prop('disabled', true).text('<?php esc_html_e('Stopping...', 'manhwa-scraper'); ?>');
    });
});
</script>

