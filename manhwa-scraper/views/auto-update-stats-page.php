<?php
/**
 * Auto Update Statistics Page
 * Shows detailed statistics about auto update system
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get statistics
$last_index = get_option('mws_last_update_index', 0);
$last_stats = get_option('mws_last_batch_stats', []);
$last_time = get_option('mws_last_batch_time', '');

// Get total tracked manhwa
global $wpdb;
$total_tracked = $wpdb->get_var("
    SELECT COUNT(DISTINCT p.ID)
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
    WHERE p.post_type = 'manhwa'
    AND pm.meta_key = '_mws_source_url'
    AND pm.meta_value != ''
");

// Get status breakdown
$status_breakdown = $wpdb->get_results("
    SELECT 
        COALESCE(pm.meta_value, 'unknown') as status,
        COUNT(*) as count
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_mws_source_url'
    LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_manhwa_status'
    WHERE p.post_type = 'manhwa'
    GROUP BY status
    ORDER BY count DESC
", ARRAY_A);

// Get recently updated manhwa (last 7 days)
$recent_updates = $wpdb->get_results("
    SELECT 
        p.ID,
        p.post_title,
        pm1.meta_value as last_updated,
        pm2.meta_value as total_chapters,
        pm3.meta_value as latest_chapter,
        pm4.meta_value as status
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_mws_last_updated'
    LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_mws_total_chapters'
    LEFT JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_mws_latest_chapter'
    LEFT JOIN {$wpdb->postmeta} pm4 ON p.ID = pm4.post_id AND pm4.meta_key = '_manhwa_status'
    WHERE p.post_type = 'manhwa'
    AND pm1.meta_value >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY pm1.meta_value DESC
    LIMIT 50
", ARRAY_A);

// Get update frequency stats
$update_frequency = $wpdb->get_results("
    SELECT 
        DATE(pm.meta_value) as update_date,
        COUNT(*) as updates_count
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_mws_last_updated'
    WHERE p.post_type = 'manhwa'
    AND pm.meta_value >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(pm.meta_value)
    ORDER BY update_date DESC
", ARRAY_A);

// Calculate progress
$progress_percent = $total_tracked > 0 ? ($last_index / $total_tracked) * 100 : 0;
$batch_size = $last_stats['batch_size'] ?? 20;
$batches_remaining = $total_tracked > 0 ? ceil(($total_tracked - $last_index) / $batch_size) : 0;

// Source-level breakdown
$source_breakdown = $wpdb->get_results("
    SELECT 
        COALESCE(pm2.meta_value, 'unknown') as source,
        COUNT(*) as total,
        SUM(CASE WHEN pm3.meta_value >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as updated_7d
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_mws_source_url'
    LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_mws_source_name'
    LEFT JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_mws_last_updated'
    WHERE p.post_type = 'manhwa'
    GROUP BY source
    ORDER BY total DESC
", ARRAY_A);

// Failed manhwa (most errors in last 30 days)
$failed_manhwa = [];
$logs_table = $wpdb->prefix . 'mws_logs';
if ($wpdb->get_var("SHOW TABLES LIKE '$logs_table'")) {
    $failed_manhwa = $wpdb->get_results("
        SELECT 
            l.url,
            l.source,
            COUNT(*) as error_count,
            MAX(l.created_at) as last_error,
            MAX(l.message) as last_message,
            MAX(pm.post_id) as manhwa_id,
            MAX(p.post_title) as post_title
        FROM {$logs_table} l
        LEFT JOIN {$wpdb->postmeta} pm ON pm.meta_key = '_mws_source_url' AND pm.meta_value = l.url COLLATE utf8mb4_unicode_520_ci
        LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE l.status = 'error'
        AND l.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY l.url, l.source
        ORDER BY error_count DESC
        LIMIT 15
    ", ARRAY_A);
}

// Storage usage
$storage_info = ['size' => 0, 'files' => 0];
$upload_dir = wp_upload_dir();
$manhwa_dir = $upload_dir['basedir'] . '/manhwa-images';
if (is_dir($manhwa_dir)) {
    $dir_iterator = new RecursiveDirectoryIterator($manhwa_dir, RecursiveDirectoryIterator::SKIP_DOTS);
    $iterator = new RecursiveIteratorIterator($dir_iterator);
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $storage_info['size'] += $file->getSize();
            $storage_info['files']++;
        }
    }
}

// Nonce for AJAX actions
$ajax_nonce = wp_create_nonce('mws_stats_actions');
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

/* ── Design Tokens ── */
.mws-auto-update-stats {
    --font: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --primary: #6366F1;
    --primary-dark: #4F46E5;
    --primary-light: #818CF8;
    --primary-bg: rgba(99,102,241,.06);
    --success: #10B981;
    --success-bg: rgba(16,185,129,.08);
    --warning: #F59E0B;
    --warning-bg: rgba(245,158,11,.08);
    --danger: #EF4444;
    --danger-bg: rgba(239,68,68,.06);
    --purple: #8B5CF6;
    --purple-bg: rgba(139,92,246,.06);
    --blue: #3B82F6;
    --blue-bg: rgba(59,130,246,.06);
    --teal: #14B8A6;
    --teal-bg: rgba(20,184,166,.06);
    --pink: #EC4899;
    --pink-bg: rgba(236,72,153,.06);
    --orange: #F59E0B;
    --orange-bg: rgba(245,158,11,.06);
    --gray-50: #F9FAFB;
    --gray-100: #F3F4F6;
    --gray-200: #E5E7EB;
    --gray-300: #D1D5DB;
    --gray-400: #9CA3AF;
    --gray-500: #6B7280;
    --gray-600: #4B5563;
    --gray-700: #374151;
    --gray-800: #1F2937;
    --radius-sm: 6px;
    --radius: 10px;
    --radius-lg: 14px;
    --shadow-sm: 0 1px 2px rgba(0,0,0,.04), 0 1px 3px rgba(0,0,0,.06);
    --shadow: 0 4px 6px -1px rgba(0,0,0,.06), 0 2px 4px -2px rgba(0,0,0,.06);
    --shadow-md: 0 10px 15px -3px rgba(0,0,0,.07), 0 4px 6px -4px rgba(0,0,0,.05);
    --transition: all .2s cubic-bezier(.4,0,.2,1);
    font-family: var(--font) !important;
    max-width: 1400px;
}

/* ── Page Title ── */
.mws-stats-title {
    display: flex;
    align-items: center;
    gap: 12px;
    font-family: var(--font) !important;
    font-size: 22px !important;
    font-weight: 800 !important;
    color: var(--gray-800) !important;
    margin: 0 0 24px 0 !important;
    padding: 0 !important;
}
.mws-stats-title .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
    color: var(--primary);
}

/* ── Cards ── */
.mws-auto-update-stats .mws-card {
    border: 1px solid var(--gray-200) !important;
    border-radius: var(--radius-lg) !important;
    padding: 24px !important;
    box-shadow: var(--shadow-sm) !important;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
    margin-bottom: 22px;
    background: #fff !important;
}
.mws-auto-update-stats .mws-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--primary), var(--purple));
    opacity: .7;
}

/* ── Card Header ── */
.mws-auto-update-stats .mws-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 14px;
    border-bottom: 1px solid var(--gray-200);
}
.mws-auto-update-stats .mws-card-header h2 {
    font-family: var(--font) !important;
    font-size: 15px !important;
    font-weight: 700 !important;
    color: var(--gray-800) !important;
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 !important;
    padding: 0 !important;
    border: none !important;
}
.mws-auto-update-stats .mws-card-header h2 .dashicons {
    font-size: 20px;
    width: 20px;
    height: 20px;
    color: var(--primary);
}
.mws-header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

/* ── Buttons ── */
.mws-auto-update-stats .button {
    font-family: var(--font) !important;
    font-weight: 600 !important;
    font-size: 13px !important;
    border-radius: var(--radius-sm) !important;
    display: inline-flex !important;
    align-items: center;
    gap: 6px;
    transition: var(--transition);
    padding: 6px 14px !important;
    line-height: 1.5 !important;
}
.mws-auto-update-stats .button .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}
.mws-auto-update-stats .button-primary {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark)) !important;
    border-color: var(--primary-dark) !important;
    color: #fff !important;
}
.mws-auto-update-stats .button-primary:hover {
    box-shadow: 0 4px 12px rgba(99,102,241,.35) !important;
    transform: translateY(-1px);
}

/* ── Progress Bar ── */
.mws-progress-bar-container { margin-bottom: 20px; }
.mws-auto-update-stats .mws-progress-bar {
    height: 28px;
    background: var(--gray-100);
    border-radius: 14px;
    overflow: hidden;
    position: relative;
    border: 1px solid var(--gray-200);
}
.mws-auto-update-stats .mws-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--primary), var(--purple));
    transition: width .4s cubic-bezier(.4,0,.2,1);
    border-radius: 14px;
    position: relative;
}
.mws-auto-update-stats .mws-progress-fill::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.3), transparent);
    animation: mwsShimmer 2s infinite;
}
@keyframes mwsShimmer {
    0%   { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}
.mws-auto-update-stats .mws-progress-text {
    text-align: center;
    margin-top: 10px;
    font-family: var(--font);
    font-size: 13px;
    font-weight: 700;
    color: var(--gray-700);
}

/* ── Batch Info Grid ── */
.mws-batch-info-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    margin-top: 22px;
}
.mws-batch-card {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 16px;
    background: var(--gray-50);
    border-radius: var(--radius);
    border: 1px solid var(--gray-200);
    transition: var(--transition);
}
.mws-batch-card:hover {
    border-color: rgba(99,102,241,.25);
    box-shadow: var(--shadow);
}
.mws-batch-icon {
    width: 44px;
    height: 44px;
    border-radius: var(--radius);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.mws-batch-icon .dashicons {
    font-size: 20px;
    width: 20px;
    height: 20px;
}
.mws-batch-icon.blue   { background: var(--blue-bg);    color: var(--blue); }
.mws-batch-icon.purple { background: var(--purple-bg);  color: var(--purple); }
.mws-batch-icon.orange { background: var(--warning-bg);  color: var(--warning); }
.mws-batch-icon.green  { background: var(--success-bg); color: var(--success); }
.mws-batch-icon.teal   { background: var(--teal-bg);    color: var(--teal); }
.mws-batch-icon.pink   { background: var(--pink-bg);    color: var(--pink); }

.mws-batch-content { flex: 1; min-width: 0; }
.mws-batch-label {
    font-family: var(--font);
    font-size: 10.5px;
    font-weight: 700;
    color: var(--gray-400);
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin-bottom: 4px;
}
.mws-batch-value {
    font-family: var(--font);
    font-size: 14px;
    font-weight: 700;
    color: var(--gray-800);
    margin-bottom: 3px;
    word-break: break-word;
    line-height: 1.3;
}
.mws-batch-sub {
    font-family: var(--font);
    font-size: 11.5px;
    color: var(--gray-400);
}

/* ── Stats Grid (Last Batch) ── */
.mws-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 22px;
}
.mws-stat-box {
    background: #fff;
    border-radius: var(--radius-lg);
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--gray-200);
    border-left: 4px solid;
    transition: var(--transition);
}
.mws-stat-box:hover {
    box-shadow: var(--shadow);
    transform: translateY(-2px);
}
.mws-stat-box.blue   { border-left-color: var(--blue); }
.mws-stat-box.green  { border-left-color: var(--success); }
.mws-stat-box.orange { border-left-color: var(--warning); }
.mws-stat-box.red    { border-left-color: var(--danger); }
.mws-stat-box.purple { border-left-color: var(--purple); }
.mws-stat-box.teal   { border-left-color: var(--teal); }

.mws-stat-icon {
    width: 46px;
    height: 46px;
    border-radius: var(--radius);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.mws-stat-icon .dashicons {
    font-size: 22px;
    width: 22px;
    height: 22px;
}
.mws-stat-box.blue .mws-stat-icon   { background: var(--blue-bg);    color: var(--blue); }
.mws-stat-box.green .mws-stat-icon  { background: var(--success-bg); color: var(--success); }
.mws-stat-box.orange .mws-stat-icon { background: var(--warning-bg);  color: var(--warning); }
.mws-stat-box.red .mws-stat-icon    { background: var(--danger-bg);  color: var(--danger); }
.mws-stat-box.purple .mws-stat-icon { background: var(--purple-bg);  color: var(--purple); }
.mws-stat-box.teal .mws-stat-icon   { background: var(--teal-bg);    color: var(--teal); }

.mws-stat-value {
    font-family: var(--font);
    font-size: 26px;
    font-weight: 800;
    color: var(--gray-800);
    line-height: 1;
}
.mws-stat-label {
    font-family: var(--font);
    font-size: 12px;
    font-weight: 500;
    color: var(--gray-400);
    margin-top: 4px;
}

/* ── 2-column Row ── */
.mws-auto-update-stats .mws-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 22px;
    margin-bottom: 22px;
}
.mws-auto-update-stats .mws-row .mws-card {
    margin-bottom: 0;
}
.mws-col-6 { min-width: 0; }

/* ── Charts ── */
.mws-chart-container {
    padding: 16px 0;
}

/* ── Tables ── */
.mws-auto-update-stats .wp-list-table {
    border-radius: var(--radius-sm) !important;
    overflow: hidden;
    border-collapse: separate;
    border-spacing: 0;
}
.mws-auto-update-stats .wp-list-table thead th {
    font-family: var(--font) !important;
    font-size: 10.5px !important;
    font-weight: 700 !important;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--gray-400) !important;
    background: var(--gray-50) !important;
    border-bottom: 2px solid var(--gray-200) !important;
    padding: 10px 12px !important;
}
.mws-auto-update-stats .wp-list-table td {
    font-family: var(--font) !important;
    font-size: 13px !important;
    color: var(--gray-600);
    padding: 10px 12px !important;
    vertical-align: middle;
}
.mws-auto-update-stats .wp-list-table tbody tr {
    transition: var(--transition);
}
.mws-auto-update-stats .wp-list-table tbody tr:hover {
    background: var(--primary-bg) !important;
}
.mws-auto-update-stats .wp-list-table td a {
    color: var(--primary);
    font-weight: 500;
    text-decoration: none;
}
.mws-auto-update-stats .wp-list-table td a:hover {
    text-decoration: underline;
}

/* ── Badges ── */
.mws-count-badge {
    font-family: var(--font);
    background: linear-gradient(135deg, var(--primary), var(--purple));
    color: #fff;
    padding: 4px 12px;
    border-radius: 16px;
    font-size: 11.5px;
    font-weight: 700;
}
.mws-auto-update-stats .mws-badge {
    display: inline-block;
    font-family: var(--font);
    padding: 3px 10px;
    border-radius: 14px;
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.mws-badge-success {
    background: var(--success-bg);
    color: var(--success);
    border: 1px solid rgba(16,185,129,.15);
}
.mws-badge-info {
    background: var(--blue-bg);
    color: var(--blue);
    border: 1px solid rgba(59,130,246,.15);
}
.mws-badge-default {
    background: var(--gray-100);
    color: var(--gray-500);
    border: 1px solid var(--gray-200);
}
.mws-badge-error {
    background: var(--danger-bg);
    color: var(--danger);
    border: 1px solid rgba(239,68,68,.15);
}
.mws-mini-badge {
    display: inline-block;
    font-family: var(--font);
    background: var(--primary-bg);
    color: var(--primary-dark);
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 700;
    border: 1px solid rgba(99,102,241,.12);
}
.mws-size-badge {
    font-family: 'Cascadia Code', 'Fira Code', monospace;
    color: var(--warning);
    font-weight: 700;
}

/* ── Status Badges (Active / Inactive) ── */
.mws-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 12px;
    border-radius: 16px;
    font-family: var(--font);
    font-size: 11.5px;
    font-weight: 700;
}
.mws-status-active {
    background: var(--success-bg);
    color: var(--success);
    border: 1px solid rgba(16,185,129,.15);
}
.mws-status-inactive {
    background: var(--danger-bg);
    color: var(--danger);
    border: 1px solid rgba(239,68,68,.15);
}
.mws-status-badge .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

/* ── Empty State ── */
.mws-empty-state {
    text-align: center;
    padding: 50px 20px;
    color: var(--gray-400);
}
.mws-empty-state .dashicons {
    font-size: 42px;
    width: 42px;
    height: 42px;
    opacity: .35;
    margin-bottom: 8px;
    display: block;
    margin-left: auto;
    margin-right: auto;
}
.mws-empty-state p {
    font-family: var(--font);
    font-size: 13px;
    margin: 0;
}
.mws-empty-small { padding: 30px 20px; }
.mws-empty-small .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
}

/* ── Image Scraper Section ── */
.mws-image-scraper-section::before {
    background: linear-gradient(90deg, var(--purple), var(--primary)) !important;
}
.mws-image-scraper-section .mws-card-header h2 .dashicons {
    color: var(--purple) !important;
}

/* 5-col grid */
.mws-stats-grid-5 {
    grid-template-columns: repeat(5, 1fr) !important;
}

/* ── History Table Wrapper ── */
.mws-history-table-wrapper {
    max-height: 500px;
    overflow-y: auto;
    border-radius: var(--radius-sm);
    border: 1px solid var(--gray-200);
}
.mws-history-table-wrapper::-webkit-scrollbar { width: 6px; }
.mws-history-table-wrapper::-webkit-scrollbar-track { background: var(--gray-100); }
.mws-history-table-wrapper::-webkit-scrollbar-thumb { background: var(--gray-300); border-radius: 3px; }

.mws-text-muted { color: var(--gray-400); }

/* ── Spin ── */
.spin { animation: mwsSpin 1s linear infinite; }
@keyframes mwsSpin { 100% { transform: rotate(360deg); } }

/* ── Responsive ── */
@media (max-width: 1200px) {
    .mws-stats-grid { grid-template-columns: repeat(2, 1fr); }
    .mws-stats-grid-5 { grid-template-columns: repeat(3, 1fr) !important; }
    .mws-auto-update-stats .mws-row { grid-template-columns: 1fr; }
    .mws-batch-info-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 782px) {
    .mws-auto-update-stats .mws-card {
        padding: 16px !important;
    }
    .mws-stats-grid { grid-template-columns: 1fr; }
    .mws-stats-grid-5 { grid-template-columns: repeat(2, 1fr) !important; }
    .mws-batch-info-grid { grid-template-columns: 1fr; }
    .mws-batch-card { padding: 14px; }
    .mws-batch-icon { width: 38px; height: 38px; }
    .mws-batch-value { font-size: 13px; }
    .mws-stat-value { font-size: 22px; }
    .mws-card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    .mws-header-actions {
        width: 100%;
        justify-content: flex-start;
    }
}
</style>

<div class="wrap mws-wrap">
    <h1 class="mws-stats-title">
        <span class="dashicons dashicons-update"></span>
        <?php esc_html_e('Auto Update Statistics', 'manhwa-scraper'); ?>
    </h1>
    
    <div class="mws-auto-update-stats">
        <!-- ── Current Batch Progress ── -->
        <div class="mws-card">
            <div class="mws-card-header">
                <h2>
                    <span class="dashicons dashicons-performance"></span>
                    <?php esc_html_e('Current Batch Progress', 'manhwa-scraper'); ?>
                </h2>
                <div class="mws-header-actions">
                    <button type="button" class="button" onclick="location.reload()">
                        <span class="dashicons dashicons-update"></span>
                        <?php esc_html_e('Refresh', 'manhwa-scraper'); ?>
                    </button>
                </div>
            </div>
            
            <div class="mws-progress-bar-container">
                <div class="mws-progress-bar">
                    <div class="mws-progress-fill" style="width: <?php echo esc_attr($progress_percent); ?>%"></div>
                </div>
                <div class="mws-progress-text">
                    <?php echo esc_html($last_index); ?> / <?php echo esc_html($total_tracked); ?> 
                    (<?php echo number_format($progress_percent, 1); ?>%)
                </div>
            </div>
            
            <?php
            $cron_interval_hours = 2;
            $cron_schedule = get_option('mws_cron_schedule', 'twicedaily');
            $interval_map = [
                'hourly' => 1,
                'twicedaily' => 12,
                'daily' => 24,
                'mws_every_4_hours' => 4,
                'mws_every_6_hours' => 6,
            ];
            $cron_interval_hours = isset($interval_map[$cron_schedule]) ? $interval_map[$cron_schedule] : 2;
            $estimated_hours = $batches_remaining * $cron_interval_hours;
            
            $last_batch_wib = '';
            $next_batch_wib = '';
            if ($last_time) {
                $dt = new DateTime($last_time, new DateTimeZone('UTC'));
                $dt->setTimezone(new DateTimeZone('Asia/Jakarta'));
                $last_batch_wib = $dt->format('d M Y H:i:s') . ' WIB';
                
                $next_dt = clone $dt;
                $next_dt->modify("+{$cron_interval_hours} hours");
                $next_batch_wib = $next_dt->format('d M Y H:i:s') . ' WIB';
            }
            ?>
            
            <div class="mws-batch-info-grid">
                <div class="mws-batch-card">
                    <div class="mws-batch-icon blue">
                        <span class="dashicons dashicons-location"></span>
                    </div>
                    <div class="mws-batch-content">
                        <div class="mws-batch-label"><?php esc_html_e('Current Position', 'manhwa-scraper'); ?></div>
                        <div class="mws-batch-value"><?php echo esc_html($last_index); ?> / <?php echo esc_html($total_tracked); ?></div>
                        <div class="mws-batch-sub"><?php printf(__('Processing manhwa #%d', 'manhwa-scraper'), $last_index + 1); ?></div>
                    </div>
                </div>
                
                <div class="mws-batch-card">
                    <div class="mws-batch-icon purple">
                        <span class="dashicons dashicons-database"></span>
                    </div>
                    <div class="mws-batch-content">
                        <div class="mws-batch-label"><?php esc_html_e('Batch Size', 'manhwa-scraper'); ?></div>
                        <div class="mws-batch-value"><?php echo esc_html($batch_size); ?> manhwa</div>
                        <div class="mws-batch-sub"><?php esc_html_e('per batch process', 'manhwa-scraper'); ?></div>
                    </div>
                </div>
                
                <div class="mws-batch-card">
                    <div class="mws-batch-icon orange">
                        <span class="dashicons dashicons-backup"></span>
                    </div>
                    <div class="mws-batch-content">
                        <div class="mws-batch-label"><?php esc_html_e('Batches Remaining', 'manhwa-scraper'); ?></div>
                        <div class="mws-batch-value"><?php echo esc_html($batches_remaining); ?> batch</div>
                        <div class="mws-batch-sub">
                            <?php 
                            if ($estimated_hours > 24) {
                                printf(__('~%d days to complete', 'manhwa-scraper'), ceil($estimated_hours / 24));
                            } elseif ($estimated_hours > 0) {
                                printf(__('~%d hours to complete', 'manhwa-scraper'), $estimated_hours);
                            } else {
                                esc_html_e('Almost done!', 'manhwa-scraper');
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <div class="mws-batch-card">
                    <div class="mws-batch-icon green">
                        <span class="dashicons dashicons-clock"></span>
                    </div>
                    <div class="mws-batch-content">
                        <div class="mws-batch-label"><?php esc_html_e('Last Batch', 'manhwa-scraper'); ?></div>
                        <div class="mws-batch-value"><?php echo $last_batch_wib ?: '-'; ?></div>
                        <div class="mws-batch-sub">
                            <?php 
                            if ($last_time) {
                                echo human_time_diff(strtotime($last_time)) . ' ' . __('ago', 'manhwa-scraper');
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <div class="mws-batch-card">
                    <div class="mws-batch-icon teal">
                        <span class="dashicons dashicons-calendar-alt"></span>
                    </div>
                    <div class="mws-batch-content">
                        <div class="mws-batch-label"><?php esc_html_e('Next Batch (Est.)', 'manhwa-scraper'); ?></div>
                        <div class="mws-batch-value"><?php echo $next_batch_wib ?: '-'; ?></div>
                        <div class="mws-batch-sub">
                            <?php printf(__('Every %d hours', 'manhwa-scraper'), $cron_interval_hours); ?>
                        </div>
                    </div>
                </div>
                
                <div class="mws-batch-card">
                    <div class="mws-batch-icon pink">
                        <span class="dashicons dashicons-chart-bar"></span>
                    </div>
                    <div class="mws-batch-content">
                        <div class="mws-batch-label"><?php esc_html_e('Full Cycle', 'manhwa-scraper'); ?></div>
                        <div class="mws-batch-value"><?php echo ceil($total_tracked / $batch_size); ?> batches</div>
                        <div class="mws-batch-sub">
                            <?php printf(__('%d manhwa total', 'manhwa-scraper'), $total_tracked); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ── Last Batch Stats ── -->
        <?php if (!empty($last_stats)): ?>
        <div class="mws-stats-grid">
            <div class="mws-stat-box blue">
                <div class="mws-stat-icon"><span class="dashicons dashicons-search"></span></div>
                <div class="mws-stat-info">
                    <div class="mws-stat-value"><?php echo esc_html($last_stats['checked'] ?? 0); ?></div>
                    <div class="mws-stat-label"><?php esc_html_e('Checked', 'manhwa-scraper'); ?></div>
                </div>
            </div>
            <div class="mws-stat-box green">
                <div class="mws-stat-icon"><span class="dashicons dashicons-yes-alt"></span></div>
                <div class="mws-stat-info">
                    <div class="mws-stat-value"><?php echo esc_html($last_stats['updated'] ?? 0); ?></div>
                    <div class="mws-stat-label"><?php esc_html_e('Updated', 'manhwa-scraper'); ?></div>
                </div>
            </div>
            <div class="mws-stat-box orange">
                <div class="mws-stat-icon"><span class="dashicons dashicons-dismiss"></span></div>
                <div class="mws-stat-info">
                    <div class="mws-stat-value"><?php echo esc_html($last_stats['skipped'] ?? 0); ?></div>
                    <div class="mws-stat-label"><?php esc_html_e('Skipped', 'manhwa-scraper'); ?></div>
                </div>
            </div>
            <div class="mws-stat-box red">
                <div class="mws-stat-icon"><span class="dashicons dashicons-warning"></span></div>
                <div class="mws-stat-info">
                    <div class="mws-stat-value"><?php echo esc_html($last_stats['errors'] ?? 0); ?></div>
                    <div class="mws-stat-label"><?php esc_html_e('Errors', 'manhwa-scraper'); ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- ── Charts Row ── -->
        <div class="mws-row">
            <div class="mws-col-6">
                <div class="mws-card">
                    <div class="mws-card-header">
                        <h2>
                            <span class="dashicons dashicons-chart-pie"></span>
                            <?php esc_html_e('Manhwa by Status', 'manhwa-scraper'); ?>
                        </h2>
                    </div>
                    <div class="mws-chart-container">
                        <canvas id="statusChart" height="250"></canvas>
                    </div>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Status', 'manhwa-scraper'); ?></th>
                                <th><?php esc_html_e('Count', 'manhwa-scraper'); ?></th>
                                <th><?php esc_html_e('Percentage', 'manhwa-scraper'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($status_breakdown as $status): ?>
                            <tr>
                                <td><strong><?php echo esc_html(ucfirst($status['status'])); ?></strong></td>
                                <td><?php echo esc_html($status['count']); ?></td>
                                <td><?php echo number_format(($status['count'] / $total_tracked) * 100, 1); ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="mws-col-6">
                <div class="mws-card">
                    <div class="mws-card-header">
                        <h2>
                            <span class="dashicons dashicons-chart-area"></span>
                            <?php esc_html_e('Update Activity (Last 30 Days)', 'manhwa-scraper'); ?>
                        </h2>
                    </div>
                    <div class="mws-chart-container">
                        <canvas id="activityChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ── Recently Updated Manhwa ── -->
        <div class="mws-card">
            <div class="mws-card-header">
                <h2>
                    <span class="dashicons dashicons-update"></span>
                    <?php esc_html_e('Recently Updated Manhwa (Last 7 Days)', 'manhwa-scraper'); ?>
                </h2>
                <div class="mws-header-actions">
                    <span class="mws-count-badge"><?php echo count($recent_updates); ?> updates</span>
                </div>
            </div>
            
            <?php if (!empty($recent_updates)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 35%;"><?php esc_html_e('Title', 'manhwa-scraper'); ?></th>
                        <th><?php esc_html_e('Status', 'manhwa-scraper'); ?></th>
                        <th><?php esc_html_e('Total Chapters', 'manhwa-scraper'); ?></th>
                        <th><?php esc_html_e('Latest Chapter', 'manhwa-scraper'); ?></th>
                        <th><?php esc_html_e('Last Updated', 'manhwa-scraper'); ?></th>
                        <th><?php esc_html_e('Actions', 'manhwa-scraper'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_updates as $manhwa): ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($manhwa['post_title']); ?></strong>
                        </td>
                        <td>
                            <?php 
                            $status = $manhwa['status'] ?: 'unknown';
                            $status_class = $status === 'ongoing' ? 'success' : ($status === 'completed' ? 'info' : 'default');
                            ?>
                            <span class="mws-badge mws-badge-<?php echo esc_attr($status_class); ?>">
                                <?php echo esc_html(ucfirst($status)); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($manhwa['total_chapters'] ?: '-'); ?></td>
                        <td><?php echo esc_html($manhwa['latest_chapter'] ?: '-'); ?></td>
                        <td>
                            <?php 
                            $time_ago = human_time_diff(strtotime($manhwa['last_updated']), current_time('timestamp'));
                            echo esc_html($time_ago . ' ago');
                            ?>
                        </td>
                        <td>
                            <a href="<?php echo esc_url(get_edit_post_link($manhwa['ID'])); ?>" class="button button-small">
                                <span class="dashicons dashicons-visibility"></span>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="mws-empty-state">
                <span class="dashicons dashicons-info"></span>
                <p><?php esc_html_e('No updates in the last 7 days', 'manhwa-scraper'); ?></p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- ══════════════ IMAGE SCRAPER HISTORY ══════════════ -->
        <?php
        $image_scraper_stats = [];
        $image_scraper_logs = [];
        $top_scraped = [];
        $daily_scrape_stats = [];
        
        if (class_exists('MWS_Logger')) {
            $image_scraper_stats = MWS_Logger::get_summary_stats('auto_image_scraper', 7);
            $image_scraper_logs = MWS_Logger::get_logs([
                'type' => 'auto_image_scraper',
                'limit' => 50,
            ]);
            $top_scraped = MWS_Logger::get_top_scraped(10, 30);
            $daily_scrape_stats = MWS_Logger::get_daily_stats('auto_image_scraper', 14);
        }
        ?>
        
        <!-- ── Image Scraper Statistics ── -->
        <div class="mws-card mws-image-scraper-section">
            <div class="mws-card-header">
                <h2>
                    <span class="dashicons dashicons-images-alt2"></span>
                    <?php esc_html_e('Image Scraper Statistics (Last 7 Days)', 'manhwa-scraper'); ?>
                </h2>
                <div class="mws-header-actions">
                    <button type="button" id="run-image-scraper-btn" class="button button-primary">
                        <span class="dashicons dashicons-update"></span>
                        <?php esc_html_e('Run Now', 'manhwa-scraper'); ?>
                    </button>
                    <?php 
                    $image_scraper_enabled = get_option('mws_auto_image_scraper_enabled', false);
                    if ($image_scraper_enabled): 
                    ?>
                        <span class="mws-status-badge mws-status-active">
                            <span class="dashicons dashicons-yes"></span> <?php esc_html_e('Active', 'manhwa-scraper'); ?>
                        </span>
                    <?php else: ?>
                        <span class="mws-status-badge mws-status-inactive">
                            <span class="dashicons dashicons-no"></span> <?php esc_html_e('Disabled', 'manhwa-scraper'); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($image_scraper_stats) && $image_scraper_stats['total_scrapes'] > 0): ?>
            <div class="mws-stats-grid mws-stats-grid-5">
                <div class="mws-stat-box blue">
                    <div class="mws-stat-icon"><span class="dashicons dashicons-database"></span></div>
                    <div class="mws-stat-info">
                        <div class="mws-stat-value"><?php echo esc_html($image_scraper_stats['total_scrapes']); ?></div>
                        <div class="mws-stat-label"><?php esc_html_e('Total Scrapes', 'manhwa-scraper'); ?></div>
                    </div>
                </div>
                <div class="mws-stat-box purple">
                    <div class="mws-stat-icon"><span class="dashicons dashicons-book"></span></div>
                    <div class="mws-stat-info">
                        <div class="mws-stat-value"><?php echo esc_html($image_scraper_stats['total_chapters']); ?></div>
                        <div class="mws-stat-label"><?php esc_html_e('Chapters Scraped', 'manhwa-scraper'); ?></div>
                    </div>
                </div>
                <div class="mws-stat-box teal">
                    <div class="mws-stat-icon"><span class="dashicons dashicons-format-gallery"></span></div>
                    <div class="mws-stat-info">
                        <div class="mws-stat-value"><?php echo esc_html($image_scraper_stats['total_images']); ?></div>
                        <div class="mws-stat-label"><?php esc_html_e('Images Found', 'manhwa-scraper'); ?></div>
                    </div>
                </div>
                <div class="mws-stat-box orange">
                    <div class="mws-stat-icon"><span class="dashicons dashicons-download"></span></div>
                    <div class="mws-stat-info">
                        <div class="mws-stat-value"><?php echo esc_html(MWS_Logger::format_bytes($image_scraper_stats['total_size'])); ?></div>
                        <div class="mws-stat-label"><?php esc_html_e('Downloaded Size', 'manhwa-scraper'); ?></div>
                    </div>
                </div>
                <div class="mws-stat-box green">
                    <div class="mws-stat-icon"><span class="dashicons dashicons-yes-alt"></span></div>
                    <div class="mws-stat-info">
                        <div class="mws-stat-value"><?php echo esc_html($image_scraper_stats['success_count']); ?></div>
                        <div class="mws-stat-label"><?php esc_html_e('Successful', 'manhwa-scraper'); ?></div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="mws-empty-state mws-empty-small">
                <span class="dashicons dashicons-info"></span>
                <p><?php esc_html_e('No image scraping activity in the last 7 days', 'manhwa-scraper'); ?></p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- ── Top Scraped Manhwa ── -->
        <?php if (!empty($top_scraped)): ?>
        <div class="mws-card">
            <div class="mws-card-header">
                <h2>
                    <span class="dashicons dashicons-chart-bar"></span>
                    <?php esc_html_e('Top Scraped Manhwa by Size (Last 30 Days)', 'manhwa-scraper'); ?>
                </h2>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 30%;"><?php esc_html_e('Title', 'manhwa-scraper'); ?></th>
                        <th><?php esc_html_e('Scrape Count', 'manhwa-scraper'); ?></th>
                        <th><?php esc_html_e('Chapters', 'manhwa-scraper'); ?></th>
                        <th><?php esc_html_e('Images', 'manhwa-scraper'); ?></th>
                        <th><?php esc_html_e('Total Size', 'manhwa-scraper'); ?></th>
                        <th><?php esc_html_e('Last Scraped', 'manhwa-scraper'); ?></th>
                        <th><?php esc_html_e('Actions', 'manhwa-scraper'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_scraped as $item): ?>
                    <tr>
                        <td><strong><?php echo esc_html($item['manhwa_title']); ?></strong></td>
                        <td><span class="mws-mini-badge"><?php echo esc_html($item['scrape_count']); ?>x</span></td>
                        <td><?php echo esc_html($item['total_chapters']); ?></td>
                        <td><?php echo esc_html($item['total_images']); ?></td>
                        <td>
                            <span class="mws-size-badge"><?php echo esc_html(MWS_Logger::format_bytes($item['total_size'])); ?></span>
                        </td>
                        <td>
                            <?php 
                            $time_ago = human_time_diff(strtotime($item['last_scraped']), current_time('timestamp'));
                            echo esc_html($time_ago . ' ago');
                            ?>
                        </td>
                        <td>
                            <?php if ($item['manhwa_id'] > 0): ?>
                            <a href="<?php echo esc_url(get_edit_post_link($item['manhwa_id'])); ?>" class="button button-small">
                                <span class="dashicons dashicons-edit"></span>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <!-- ── Image Scraper History Log ── -->
        <?php if (!empty($image_scraper_logs)): ?>
        <div class="mws-card">
            <div class="mws-card-header">
                <h2>
                    <span class="dashicons dashicons-list-view"></span>
                    <?php esc_html_e('Image Scraper History Log', 'manhwa-scraper'); ?>
                </h2>
                <div class="mws-header-actions">
                    <span class="mws-count-badge"><?php echo count($image_scraper_logs); ?> entries</span>
                </div>
            </div>
            
            <div class="mws-history-table-wrapper">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 130px;"><?php esc_html_e('Date', 'manhwa-scraper'); ?></th>
                            <th style="width: 35%;"><?php esc_html_e('Title', 'manhwa-scraper'); ?></th>
                            <th><?php esc_html_e('Status', 'manhwa-scraper'); ?></th>
                            <th><?php esc_html_e('Chapters', 'manhwa-scraper'); ?></th>
                            <th><?php esc_html_e('Images', 'manhwa-scraper'); ?></th>
                            <th><?php esc_html_e('Size', 'manhwa-scraper'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($image_scraper_logs as $log): ?>
                        <tr>
                            <td>
                                <?php 
                                $log_time = new DateTime($log['created_at'], new DateTimeZone('UTC'));
                                $log_time->setTimezone(new DateTimeZone('Asia/Jakarta'));
                                echo esc_html($log_time->format('d M H:i'));
                                ?>
                            </td>
                            <td>
                                <?php if ($log['manhwa_id'] > 0): ?>
                                    <a href="<?php echo esc_url(get_edit_post_link($log['manhwa_id'])); ?>">
                                        <?php echo esc_html($log['manhwa_title']); ?>
                                    </a>
                                <?php else: ?>
                                    <?php echo esc_html($log['manhwa_title'] ?: '-'); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $status_class = $log['status'] === 'success' ? 'success' : ($log['status'] === 'error' ? 'error' : 'info');
                                ?>
                                <span class="mws-badge mws-badge-<?php echo esc_attr($status_class); ?>">
                                    <?php echo esc_html(ucfirst($log['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($log['chapters_scraped']); ?></td>
                            <td><?php echo esc_html($log['images_found']); ?></td>
                            <td>
                                <?php if ($log['total_size'] > 0): ?>
                                    <span class="mws-size-badge"><?php echo esc_html(MWS_Logger::format_bytes($log['total_size'])); ?></span>
                                <?php else: ?>
                                    <span class="mws-text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- ══════════════ SOURCE BREAKDOWN ══════════════ -->
        <?php if (!empty($source_breakdown)): ?>
        <div class="mws-row">
            <div class="mws-col-6">
                <div class="mws-card">
                    <div class="mws-card-header">
                        <h2>
                            <span class="dashicons dashicons-networking"></span>
                            <?php esc_html_e('Manhwa by Source', 'manhwa-scraper'); ?>
                        </h2>
                    </div>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Source', 'manhwa-scraper'); ?></th>
                                <th><?php esc_html_e('Total', 'manhwa-scraper'); ?></th>
                                <th><?php esc_html_e('Updated (7d)', 'manhwa-scraper'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($source_breakdown as $src): ?>
                            <tr>
                                <td><strong><?php echo esc_html(ucfirst($src['source'])); ?></strong></td>
                                <td><span class="mws-mini-badge"><?php echo esc_html($src['total']); ?></span></td>
                                <td>
                                    <?php if ($src['updated_7d'] > 0): ?>
                                        <span class="mws-badge mws-badge-success"><?php echo esc_html($src['updated_7d']); ?></span>
                                    <?php else: ?>
                                        <span class="mws-text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="mws-chart-container">
                        <canvas id="sourceChart" height="200"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- ── Daily Image Scrape Chart ── -->
            <div class="mws-col-6">
                <div class="mws-card">
                    <div class="mws-card-header">
                        <h2>
                            <span class="dashicons dashicons-images-alt2"></span>
                            <?php esc_html_e('Daily Image Scrapes (14 Days)', 'manhwa-scraper'); ?>
                        </h2>
                    </div>
                    <div class="mws-chart-container">
                        <canvas id="dailyScrapeChart" height="280"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- ══════════════ FAILED MANHWA ══════════════ -->
        <?php if (!empty($failed_manhwa)): ?>
        <div class="mws-card" style="--card-accent: var(--danger);">
            <div class="mws-card-header">
                <h2>
                    <span class="dashicons dashicons-warning" style="color: var(--danger) !important;"></span>
                    <?php esc_html_e('Failed Scrapes (Last 30 Days)', 'manhwa-scraper'); ?>
                </h2>
                <div class="mws-header-actions">
                    <span class="mws-count-badge" style="background: linear-gradient(135deg, var(--danger), #DC2626);"><?php echo count($failed_manhwa); ?> errors</span>
                </div>
            </div>
            
            <div class="mws-history-table-wrapper">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 30%;"><?php esc_html_e('Title / URL', 'manhwa-scraper'); ?></th>
                            <th><?php esc_html_e('Source', 'manhwa-scraper'); ?></th>
                            <th><?php esc_html_e('Errors', 'manhwa-scraper'); ?></th>
                            <th><?php esc_html_e('Last Error', 'manhwa-scraper'); ?></th>
                            <th style="width: 30%;"><?php esc_html_e('Message', 'manhwa-scraper'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($failed_manhwa as $fail): ?>
                        <tr>
                            <td>
                                <?php if ($fail['post_title']): ?>
                                    <strong><?php echo esc_html($fail['post_title']); ?></strong>
                                <?php else: ?>
                                    <span class="mws-text-muted" title="<?php echo esc_attr($fail['url']); ?>"><?php echo esc_html(wp_basename(parse_url($fail['url'] ?? '', PHP_URL_PATH))); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><span class="mws-badge mws-badge-info"><?php echo esc_html(ucfirst($fail['source'] ?? 'unknown')); ?></span></td>
                            <td><span class="mws-badge mws-badge-error"><?php echo esc_html($fail['error_count']); ?>x</span></td>
                            <td>
                                <?php 
                                if ($fail['last_error']) {
                                    echo esc_html(human_time_diff(strtotime($fail['last_error']), current_time('timestamp')) . ' ago');
                                }
                                ?>
                            </td>
                            <td><small class="mws-text-muted"><?php echo esc_html(wp_trim_words($fail['last_message'] ?? '', 12)); ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- ══════════════ STORAGE & ADMIN TOOLS ══════════════ -->
        <div class="mws-row">
            <!-- Storage Info -->
            <div class="mws-col-6">
                <div class="mws-card">
                    <div class="mws-card-header">
                        <h2>
                            <span class="dashicons dashicons-database"></span>
                            <?php esc_html_e('Storage Usage', 'manhwa-scraper'); ?>
                        </h2>
                    </div>
                    <div class="mws-batch-info-grid" style="grid-template-columns: repeat(2, 1fr);">
                        <div class="mws-batch-card">
                            <div class="mws-batch-icon purple">
                                <span class="dashicons dashicons-media-default"></span>
                            </div>
                            <div class="mws-batch-content">
                                <div class="mws-batch-label"><?php esc_html_e('Total Files', 'manhwa-scraper'); ?></div>
                                <div class="mws-batch-value"><?php echo number_format($storage_info['files']); ?></div>
                                <div class="mws-batch-sub"><?php esc_html_e('images stored', 'manhwa-scraper'); ?></div>
                            </div>
                        </div>
                        <div class="mws-batch-card">
                            <div class="mws-batch-icon orange">
                                <span class="dashicons dashicons-download"></span>
                            </div>
                            <div class="mws-batch-content">
                                <div class="mws-batch-label"><?php esc_html_e('Disk Usage', 'manhwa-scraper'); ?></div>
                                <div class="mws-batch-value"><?php echo esc_html(size_format($storage_info['size'])); ?></div>
                                <div class="mws-batch-sub"><?php echo esc_html(str_replace(ABSPATH, '/', $manhwa_dir)); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Admin Tools -->
            <div class="mws-col-6">
                <div class="mws-card">
                    <div class="mws-card-header">
                        <h2>
                            <span class="dashicons dashicons-admin-tools"></span>
                            <?php esc_html_e('Admin Tools', 'manhwa-scraper'); ?>
                        </h2>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <button type="button" class="button" id="mws-reset-index-btn" data-nonce="<?php echo esc_attr($ajax_nonce); ?>">
                            <span class="dashicons dashicons-controls-skipback"></span>
                            <?php esc_html_e('Reset Batch Index (Start from #1)', 'manhwa-scraper'); ?>
                        </button>
                        <button type="button" class="button" id="mws-clear-logs-btn" data-nonce="<?php echo esc_attr($ajax_nonce); ?>">
                            <span class="dashicons dashicons-trash"></span>
                            <?php esc_html_e('Clear Logs Older Than 30 Days', 'manhwa-scraper'); ?>
                        </button>
                        <button type="button" class="button" id="mws-export-csv-btn">
                            <span class="dashicons dashicons-download"></span>
                            <?php esc_html_e('Export Recent Updates (CSV)', 'manhwa-scraper'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
jQuery(document).ready(function($) {

    // ── Auto-Refresh Progress (every 30s) ──
    var autoRefreshInterval = setInterval(function() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: { action: 'mws_get_manhwa_stats', nonce: (typeof mwsData !== 'undefined' ? mwsData.nonce : '') },
            success: function(r) {
                if (r.success && r.data) {
                    var i = r.data.last_index || 0;
                    var t = r.data.total_tracked || <?php echo (int)$total_tracked; ?>;
                    var pct = t > 0 ? (i / t * 100).toFixed(1) : 0;
                    $('.mws-progress-fill').css('width', pct + '%');
                    $('.mws-progress-text').text(i + ' / ' + t + ' (' + pct + '%)');
                }
            }
        });
    }, 30000);
    // ── Status Chart ──
    var statusData = <?php echo json_encode($status_breakdown); ?>;
    var statusLabels = statusData.map(function(item) { return item.status.charAt(0).toUpperCase() + item.status.slice(1); });
    var statusCounts = statusData.map(function(item) { return parseInt(item.count); });
    
    var statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{
                data: statusCounts,
                backgroundColor: ['#10B981', '#F59E0B', '#3B82F6', '#EF4444', '#8B5CF6'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: { family: "'Inter', sans-serif", size: 12, weight: 500 },
                        padding: 16,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                }
            },
            cutout: '65%'
        }
    });
    
    // ── Activity Chart ──
    var activityData = <?php echo json_encode(array_reverse($update_frequency)); ?>;
    var activityLabels = activityData.map(function(item) { 
        return new Date(item.update_date).toLocaleDateString('en-US', {month: 'short', day: 'numeric'});
    });
    var activityCounts = activityData.map(function(item) { return parseInt(item.updates_count); });
    
    var activityCtx = document.getElementById('activityChart').getContext('2d');
    new Chart(activityCtx, {
        type: 'line',
        data: {
            labels: activityLabels,
            datasets: [{
                label: 'Updates',
                data: activityCounts,
                borderColor: '#6366F1',
                backgroundColor: 'rgba(99,102,241,0.08)',
                fill: true,
                tension: 0.4,
                borderWidth: 2.5,
                pointBackgroundColor: '#6366F1',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        font: { family: "'Inter', sans-serif", size: 11 }
                    },
                    grid: { color: 'rgba(0,0,0,.04)' }
                },
                x: {
                    ticks: { font: { family: "'Inter', sans-serif", size: 11 } },
                    grid: { display: false }
                }
            }
        }
    });
    
    // ── Manual Image Scraper Trigger ──
    $('#run-image-scraper-btn').on('click', function() {
        var $btn = $(this);
        var originalText = $btn.html();
        
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Running...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mws_run_image_scraper',
                nonce: '<?php echo wp_create_nonce('mws_ajax_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    var stats = response.data.stats;
                    var message = 'Scraping completed!\n';
                    message += 'Checked: ' + stats.total_checked + '\n';
                    message += 'Chapters: ' + stats.chapters_scraped + '\n';
                    message += 'Images: ' + stats.images_found + '\n';
                    message += 'Errors: ' + stats.errors;
                    alert(message);
                    location.reload();
                } else {
                    alert('Error: ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('Request failed. Please try again.');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // ── Source Chart ──
    var sourceEl = document.getElementById('sourceChart');
    if (sourceEl) {
        var sourceData = <?php echo json_encode($source_breakdown); ?>;
        var sourceColors = ['#6366F1','#8B5CF6','#3B82F6','#14B8A6','#10B981','#F59E0B','#EC4899','#EF4444'];
        new Chart(sourceEl.getContext('2d'), {
            type: 'bar',
            data: {
                labels: sourceData.map(function(s) { return s.source.charAt(0).toUpperCase() + s.source.slice(1); }),
                datasets: [{
                    label: 'Total',
                    data: sourceData.map(function(s) { return parseInt(s.total); }),
                    backgroundColor: sourceColors.slice(0, sourceData.length),
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: 'rgba(0,0,0,.04)' } },
                    x: { ticks: { font: { size: 11 } }, grid: { display: false } }
                }
            }
        });
    }
    
    // ── Daily Image Scrape Chart (uses $daily_scrape_stats) ──
    var dailyScrapeEl = document.getElementById('dailyScrapeChart');
    if (dailyScrapeEl) {
        var dailyData = <?php echo json_encode(array_reverse($daily_scrape_stats ?: [])); ?>;
        new Chart(dailyScrapeEl.getContext('2d'), {
            type: 'bar',
            data: {
                labels: dailyData.map(function(d) { return new Date(d.log_date).toLocaleDateString('en-US', {month: 'short', day: 'numeric'}); }),
                datasets: [
                    {
                        label: 'Scrapes',
                        data: dailyData.map(function(d) { return parseInt(d.total_scrapes || 0); }),
                        backgroundColor: 'rgba(139,92,246,0.7)',
                        borderRadius: 4
                    },
                    {
                        label: 'Errors',
                        data: dailyData.map(function(d) { return parseInt(d.total_errors || 0); }),
                        backgroundColor: 'rgba(239,68,68,0.6)',
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { font: { family: "'Inter', sans-serif", size: 11 }, usePointStyle: true, pointStyle: 'rectRounded' }
                    }
                },
                scales: {
                    y: { beginAtZero: true, stacked: false, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: 'rgba(0,0,0,.04)' } },
                    x: { ticks: { font: { size: 10 } }, grid: { display: false } }
                }
            }
        });
    }
    
    // ── Admin Tools ──
    $('#mws-reset-index-btn').on('click', function() {
        if (!confirm('Reset batch index to 0? The next batch will start from the beginning.')) return;
        var $btn = $(this);
        $btn.prop('disabled', true).find('.dashicons').addClass('spin');
        $.post(ajaxurl, {
            action: 'mws_stats_admin_tools',
            nonce: $btn.data('nonce'),
            tool: 'reset_index'
        }, function(r) {
            $btn.prop('disabled', false).find('.dashicons').removeClass('spin');
            if (r.success) { alert(r.data.message || 'Index reset!'); location.reload(); }
            else { alert('Failed: ' + (r.data && r.data.message || 'Unknown error')); }
        }).fail(function() {
            $btn.prop('disabled', false).find('.dashicons').removeClass('spin');
            alert('Request failed.');
        });
    });
    
    $('#mws-clear-logs-btn').on('click', function() {
        if (!confirm('Delete all logs older than 30 days? This cannot be undone.')) return;
        var $btn = $(this);
        $btn.prop('disabled', true).find('.dashicons').addClass('spin');
        $.post(ajaxurl, {
            action: 'mws_stats_admin_tools',
            nonce: $btn.data('nonce'),
            tool: 'clear_old_logs'
        }, function(r) {
            $btn.prop('disabled', false).find('.dashicons').removeClass('spin');
            alert(r.data && r.data.message || 'Old logs cleared!');
            location.reload();
        }).fail(function() {
            $btn.prop('disabled', false).find('.dashicons').removeClass('spin');
            alert('Request failed.');
        });
    });
    
    // ── Export CSV ──
    $('#mws-export-csv-btn').on('click', function() {
        var rows = [['Title', 'Status', 'Total Chapters', 'Latest Chapter', 'Last Updated']];
        <?php foreach ($recent_updates as $m): ?>
        rows.push([<?php echo json_encode($m['post_title']); ?>, <?php echo json_encode($m['status'] ?: 'unknown'); ?>, <?php echo json_encode($m['total_chapters'] ?: '0'); ?>, <?php echo json_encode($m['latest_chapter'] ?: '-'); ?>, <?php echo json_encode($m['last_updated'] ?? ''); ?>]);
        <?php endforeach; ?>
        
        var csv = rows.map(function(r) {
            return r.map(function(c) { return '"' + String(c).replace(/"/g, '""') + '"'; }).join(',');
        }).join('\n');
        
        var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'manhwa-updates-' + new Date().toISOString().slice(0,10) + '.csv';
        link.click();
    });
});
</script>
