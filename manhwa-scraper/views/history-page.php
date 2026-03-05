<?php
/**
 * History Page View
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

/* ── Design Tokens ── */
.mws-history-page {
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
}

/* ── Page Title ── */
.mws-history-title {
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
.mws-history-title .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
    color: var(--primary);
}

/* ── Cards ── */
.mws-history-page .mws-card {
    border: 1px solid var(--gray-200) !important;
    border-radius: var(--radius-lg) !important;
    padding: 24px !important;
    box-shadow: var(--shadow-sm) !important;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}
.mws-history-page .mws-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--primary), var(--purple));
    opacity: .7;
}

/* ── Filter Bar ── */
.mws-history-filters {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
    padding: 18px 22px !important;
}
.mws-history-filters .mws-filter-item {
    display: flex;
    align-items: center;
    gap: 6px;
}
.mws-history-filters .mws-filter-item label {
    font-family: var(--font) !important;
    font-size: 11px !important;
    font-weight: 700 !important;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: var(--gray-400) !important;
    white-space: nowrap;
}
.mws-history-filters select {
    font-family: var(--font) !important;
    font-size: 13px !important;
    border: 1.5px solid var(--gray-300) !important;
    border-radius: var(--radius-sm) !important;
    padding: 7px 10px !important;
    background: #fff !important;
    transition: var(--transition);
    min-width: 130px;
}
.mws-history-filters select:focus {
    border-color: var(--primary) !important;
    box-shadow: 0 0 0 3px rgba(99,102,241,.12) !important;
    outline: none !important;
}

/* ── Buttons ── */
.mws-history-page .button {
    font-family: var(--font) !important;
    font-weight: 600 !important;
    font-size: 13px !important;
    border-radius: var(--radius-sm) !important;
    display: inline-flex !important;
    align-items: center;
    gap: 6px;
    transition: var(--transition);
}
.mws-history-page .button .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}
.mws-history-page .button-primary {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark)) !important;
    border-color: var(--primary-dark) !important;
}

/* ── Table ── */
.mws-history-page .wp-list-table {
    border-radius: var(--radius-sm) !important;
    overflow: hidden;
    border-collapse: separate;
    border-spacing: 0;
}
.mws-history-page .wp-list-table thead th {
    font-family: var(--font) !important;
    font-size: 11px !important;
    font-weight: 700 !important;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--gray-400) !important;
    background: var(--gray-50) !important;
    border-bottom: 2px solid var(--gray-200) !important;
    padding: 12px 14px !important;
}
.mws-history-page .wp-list-table td {
    font-family: var(--font) !important;
    font-size: 13px !important;
    color: var(--gray-600);
    padding: 12px 14px !important;
    vertical-align: middle;
}
.mws-history-page .wp-list-table tbody tr {
    transition: var(--transition);
}
.mws-history-page .wp-list-table tbody tr:hover {
    background: var(--primary-bg) !important;
}

/* ── Date Cell ── */
.mws-log-date {
    font-weight: 600;
    color: var(--gray-700);
    font-size: 12.5px !important;
    line-height: 1.4;
}
.mws-log-date-tz {
    font-size: 10px;
    color: var(--gray-400);
    font-weight: 400;
}
.mws-log-ago {
    font-size: 11px;
    color: var(--gray-400);
    margin-top: 2px;
}

/* ── Source Badge ── */
.mws-history-page .mws-badge {
    font-family: var(--font) !important;
    font-size: 10.5px !important;
    font-weight: 700 !important;
    padding: 3px 8px;
    border-radius: 4px;
    background: var(--primary-bg);
    color: var(--primary-dark);
    border: 1px solid rgba(99,102,241,.12);
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

/* ── Type Badges ── */
.mws-type-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 4px;
    font-family: var(--font) !important;
    font-size: 10.5px !important;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #fff;
}
.mws-type-scrape   { background: #3B82F6; }
.mws-type-download { background: #10B981; }
.mws-type-update   { background: #F59E0B; }
.mws-type-import   { background: #8B5CF6; }
.mws-type-auto     { background: #64748B; }
.mws-type-default  { background: #9CA3AF; }

/* ── Status Badges ── */
.mws-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 16px;
    font-family: var(--font) !important;
    font-size: 11.5px !important;
    font-weight: 600;
    white-space: nowrap;
}
.mws-status-auto {
    background: var(--blue-bg);
    color: #2563EB;
    border: 1px solid rgba(59,130,246,.15);
}
.mws-status-success {
    background: var(--success-bg);
    color: var(--success);
    border: 1px solid rgba(16,185,129,.15);
}
.mws-status-error {
    background: var(--danger-bg);
    color: var(--danger);
    border: 1px solid rgba(239,68,68,.15);
}
.mws-status-updated {
    background: var(--warning-bg);
    color: #D97706;
    border: 1px solid rgba(245,158,11,.15);
}
.mws-status-default {
    background: var(--gray-100);
    color: var(--gray-500);
    border: 1px solid var(--gray-200);
}

/* ── Duration ── */
.mws-duration {
    font-family: 'Cascadia Code', 'Fira Code', monospace;
    font-size: 12px;
    font-weight: 600;
}
.mws-dur-fast  { color: var(--success); }
.mws-dur-mid   { color: var(--warning); }
.mws-dur-slow  { color: var(--danger); }

/* ── Message ── */
.mws-log-message {
    font-size: 12.5px !important;
    color: var(--gray-600);
    line-height: 1.5;
    max-width: 350px;
    word-break: break-word;
}

/* ── Pagination ── */
.mws-history-page .mws-pagination {
    margin-top: 18px;
    padding-top: 16px;
    border-top: 1px solid var(--gray-200);
    text-align: center;
}
.mws-history-page .mws-pagination .page-numbers {
    font-family: var(--font) !important;
    font-size: 13px;
    font-weight: 500;
    padding: 6px 12px;
    border-radius: var(--radius-sm);
    transition: var(--transition);
}
.mws-history-page .mws-pagination .page-numbers.current {
    background: var(--primary) !important;
    border-color: var(--primary-dark) !important;
    color: #fff !important;
}

/* ── Clear Logs Section ── */
.mws-clear-section h3 {
    font-family: var(--font) !important;
    font-size: 15px !important;
    font-weight: 700 !important;
    color: var(--gray-800) !important;
    margin: 0 0 4px 0 !important;
    display: flex;
    align-items: center;
    gap: 8px;
}
.mws-clear-section .description {
    font-size: 13px !important;
    color: var(--gray-500) !important;
    margin-bottom: 14px;
}
.mws-clear-form {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
    padding: 14px 16px;
    background: var(--gray-50);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm);
}
.mws-clear-form select {
    font-family: var(--font) !important;
    font-size: 13px !important;
    border: 1.5px solid var(--gray-300) !important;
    border-radius: var(--radius-sm) !important;
    padding: 7px 10px !important;
    background: #fff !important;
}
.mws-clear-divider {
    width: 1px;
    height: 24px;
    background: var(--gray-300);
    margin: 0 4px;
}
.mws-btn-danger {
    background: var(--danger) !important;
    color: #fff !important;
    border-color: #DC2626 !important;
}
.mws-btn-danger:hover {
    background: #DC2626 !important;
    transform: translateY(-1px);
}
.mws-total-logs {
    margin-top: 12px;
    font-size: 12px;
    color: var(--gray-400);
}
.mws-total-logs strong {
    color: var(--gray-700);
}

/* ── Modal ── */
.mws-history-page ~ .mws-modal,
.mws-modal {
    position: fixed;
    inset: 0;
    background: rgba(15,23,42,.6);
    backdrop-filter: blur(4px);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.mws-history-modal {
    background: #fff;
    border-radius: var(--radius-lg);
    padding: 28px;
    max-width: 700px;
    width: 100%;
    max-height: 85vh;
    overflow-y: auto;
    position: relative;
    box-shadow: var(--shadow-md);
    animation: mwsModalIn .2s ease;
}
@keyframes mwsModalIn {
    from { opacity: 0; transform: translateY(12px) scale(.97); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}
.mws-history-modal h2 {
    font-family: var(--font) !important;
    font-size: 17px !important;
    font-weight: 700 !important;
    color: var(--gray-800) !important;
    margin: 0 0 18px 0 !important;
    padding: 0 30px 14px 0 !important;
    border-bottom: 1px solid var(--gray-200) !important;
    display: flex;
    align-items: center;
    gap: 8px;
}
.mws-modal-close {
    position: absolute;
    top: 18px;
    right: 20px;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    cursor: pointer;
    color: var(--gray-400);
    background: var(--gray-100);
    border-radius: 6px;
    transition: var(--transition);
}
.mws-modal-close:hover {
    background: var(--gray-200);
    color: var(--gray-700);
}

/* Modal Content */
.mws-detail-card {
    background: var(--gray-50);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm);
    padding: 16px;
    margin-bottom: 14px;
}
.mws-detail-card h4 {
    font-family: var(--font) !important;
    margin: 0 0 10px;
    font-size: 13px;
    font-weight: 700;
    color: var(--gray-700);
}
.mws-chapter-list {
    max-height: 200px;
    overflow-y: auto;
    background: #fff;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm);
}
.mws-chapter-item {
    padding: 8px 12px;
    border-bottom: 1px solid var(--gray-100);
    font-size: 13px;
    font-family: var(--font);
}
.mws-chapter-item:last-child { border-bottom: none; }
.mws-chapter-item a {
    color: var(--primary);
    text-decoration: none;
    font-weight: 500;
}
.mws-chapter-item a:hover { text-decoration: underline; }

.mws-stat-row {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}
.mws-stat-item {
    background: #fff;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm);
    padding: 12px 18px;
    text-align: center;
    min-width: 90px;
    flex: 1;
}
.mws-stat-item .label {
    font-family: var(--font);
    font-size: 10px;
    font-weight: 700;
    color: var(--gray-400);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.mws-stat-item .value {
    font-family: var(--font);
    font-size: 26px;
    font-weight: 800;
    color: var(--gray-800);
    margin-top: 2px;
}
.mws-stat-item.highlight .value { color: var(--success); }

.mws-json-display {
    font-family: 'Cascadia Code', 'Fira Code', monospace !important;
    font-size: 12px !important;
    background: #0f172a !important;
    color: #94A3B8 !important;
    padding: 14px !important;
    border-radius: var(--radius-sm) !important;
    line-height: 1.7;
    max-height: 300px;
    overflow: auto;
}
.mws-raw-toggle {
    cursor: pointer;
    font-family: var(--font);
    font-size: 12px;
    color: var(--gray-400);
    font-weight: 600;
    margin-top: 16px;
}
.mws-raw-toggle:hover { color: var(--gray-600); }
.mws-modal-meta {
    font-family: var(--font);
    font-size: 12px;
    color: var(--gray-400);
    margin-top: 4px;
}
.mws-modal-meta strong { color: var(--gray-600); }

/* ── Responsive ── */
@media (max-width: 960px) {
    .mws-history-page .wp-list-table th:nth-child(6),
    .mws-history-page .wp-list-table td:nth-child(6) {
        display: none;
    }
}
@media (max-width: 782px) {
    .mws-history-page .mws-card {
        padding: 16px !important;
    }
    .mws-history-filters {
        padding: 14px !important;
        gap: 10px;
    }
    .mws-history-filters .mws-filter-item {
        width: 100%;
    }
    .mws-history-filters select {
        flex: 1;
    }
    .mws-clear-form {
        flex-direction: column;
        align-items: flex-start;
    }
    .mws-clear-divider {
        width: 100%;
        height: 1px;
    }
    .mws-history-page .wp-list-table th:nth-child(3),
    .mws-history-page .wp-list-table td:nth-child(3),
    .mws-history-page .wp-list-table th:nth-child(6),
    .mws-history-page .wp-list-table td:nth-child(6) {
        display: none;
    }
    .mws-history-modal {
        padding: 20px;
        max-height: 90vh;
    }
    .mws-stat-row {
        gap: 8px;
    }
    .mws-stat-item {
        min-width: 70px;
        padding: 10px 12px;
    }
}
</style>

<div class="wrap mws-wrap">
    <h1 class="mws-history-title">
        <span class="dashicons dashicons-backup"></span>
        <?php esc_html_e('Scrape History', 'manhwa-scraper'); ?>
    </h1>
    
    <?php settings_errors('mws_history'); ?>
    
    <div class="mws-history-page">
        <!-- ── Filters ── -->
        <div class="mws-card mws-history-filters">
            <form method="get" action="" style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap; width: 100%;">
                <input type="hidden" name="page" value="manhwa-scraper-history">
                
                <div class="mws-filter-item">
                    <label for="mws-filter-status"><?php esc_html_e('Status', 'manhwa-scraper'); ?></label>
                    <select name="status" id="mws-filter-status">
                        <option value=""><?php esc_html_e('All', 'manhwa-scraper'); ?></option>
                        <option value="auto_update" <?php selected($status_filter, 'auto_update'); ?>><?php esc_html_e('🔄 Auto Update', 'manhwa-scraper'); ?></option>
                        <option value="success" <?php selected($status_filter, 'success'); ?>><?php esc_html_e('✓ Success', 'manhwa-scraper'); ?></option>
                        <option value="error" <?php selected($status_filter, 'error'); ?>><?php esc_html_e('✗ Error', 'manhwa-scraper'); ?></option>
                        <option value="updated" <?php selected($status_filter, 'updated'); ?>><?php esc_html_e('↑ Updated', 'manhwa-scraper'); ?></option>
                    </select>
                </div>
                
                <div class="mws-filter-item">
                    <label for="mws-filter-source"><?php esc_html_e('Source', 'manhwa-scraper'); ?></label>
                    <select name="source" id="mws-filter-source">
                        <option value=""><?php esc_html_e('All', 'manhwa-scraper'); ?></option>
                        <?php foreach ($sources as $source): ?>
                        <option value="<?php echo esc_attr($source); ?>" <?php selected($source_filter, $source); ?>>
                            <?php echo esc_html($source); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-filter"></span>
                    <?php esc_html_e('Filter', 'manhwa-scraper'); ?>
                </button>
                <?php if (!empty($status_filter) || !empty($source_filter)): ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=manhwa-scraper-history')); ?>" class="button">
                    <?php esc_html_e('Clear', 'manhwa-scraper'); ?>
                </a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- ── Logs Table ── -->
        <div class="mws-card">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 150px;"><?php esc_html_e('Date/Time', 'manhwa-scraper'); ?></th>
                        <th style="width: 90px;"><?php esc_html_e('Source', 'manhwa-scraper'); ?></th>
                        <th style="width: 85px;"><?php esc_html_e('Type', 'manhwa-scraper'); ?></th>
                        <th style="width: 110px;"><?php esc_html_e('Status', 'manhwa-scraper'); ?></th>
                        <th><?php esc_html_e('Message', 'manhwa-scraper'); ?></th>
                        <th style="width: 75px;"><?php esc_html_e('Duration', 'manhwa-scraper'); ?></th>
                        <th style="width: 95px;"><?php esc_html_e('Actions', 'manhwa-scraper'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 30px !important; color: var(--gray-400);">
                            <span class="dashicons dashicons-database" style="font-size: 32px; width: 32px; height: 32px; display: block; margin: 0 auto 8px;"></span>
                            <?php esc_html_e('No logs found', 'manhwa-scraper'); ?>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                    <?php 
                    $status_class = '';
                    $status_icon = '';
                    switch ($log->status) {
                        case 'auto_update':
                            $status_class = 'mws-status-auto';
                            $status_icon = '🔄';
                            break;
                        case 'success':
                            $status_class = 'mws-status-success';
                            $status_icon = '✓';
                            break;
                        case 'error':
                            $status_class = 'mws-status-error';
                            $status_icon = '✗';
                            break;
                        case 'updated':
                            $status_class = 'mws-status-updated';
                            $status_icon = '↑';
                            break;
                        default:
                            $status_class = 'mws-status-default';
                            $status_icon = '•';
                    }
                    
                    // Get type with fallback
                    $log_type = isset($log->type) ? $log->type : 'scrape';
                    $type_class_map = [
                        'scrape'   => 'mws-type-scrape',
                        'download' => 'mws-type-download', 
                        'update'   => 'mws-type-update',
                        'import'   => 'mws-type-import',
                        'auto'     => 'mws-type-auto'
                    ];
                    $type_class = $type_class_map[$log_type] ?? 'mws-type-default';
                    
                    // Format duration
                    $duration_ms = isset($log->duration_ms) ? intval($log->duration_ms) : 0;
                    if ($duration_ms > 0) {
                        if ($duration_ms >= 60000) {
                            $duration_str = round($duration_ms / 60000, 1) . 'm';
                        } elseif ($duration_ms >= 1000) {
                            $duration_str = round($duration_ms / 1000, 1) . 's';
                        } else {
                            $duration_str = $duration_ms . 'ms';
                        }
                    } else {
                        $duration_str = '-';
                    }
                    $dur_class = $duration_ms > 5000 ? 'mws-dur-slow' : ($duration_ms > 2000 ? 'mws-dur-mid' : 'mws-dur-fast');
                    ?>
                    <tr>
                        <td>
                            <div class="mws-log-date">
                                <?php 
                                $date = new DateTime($log->created_at, new DateTimeZone('UTC'));
                                $date->setTimezone(new DateTimeZone('Asia/Jakarta'));
                                echo esc_html($date->format('d M Y H:i:s'));
                                ?>
                                <span class="mws-log-date-tz">WIB</span>
                            </div>
                            <div class="mws-log-ago"><?php echo esc_html(human_time_diff(strtotime($log->created_at)) . ' ago'); ?></div>
                        </td>
                        <td><span class="mws-badge"><?php echo esc_html($log->source); ?></span></td>
                        <td>
                            <span class="mws-type-badge <?php echo esc_attr($type_class); ?>">
                                <?php echo esc_html($log_type); ?>
                            </span>
                        </td>
                        <td>
                            <span class="mws-status-badge <?php echo esc_attr($status_class); ?>">
                                <?php echo $status_icon; ?> <?php echo esc_html(ucfirst(str_replace('_', ' ', $log->status))); ?>
                            </span>
                        </td>
                        <td>
                            <span class="mws-log-message"><?php echo esc_html($log->message); ?></span>
                        </td>
                        <td style="text-align: center;">
                            <span class="mws-duration <?php echo esc_attr($dur_class); ?>">
                                <?php echo esc_html($duration_str); ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($log->data)): ?>
                            <button type="button" class="button button-small mws-view-data" data-log-id="<?php echo esc_attr($log->id); ?>" data-data="<?php echo esc_attr($log->data); ?>">
                                <span class="dashicons dashicons-visibility"></span>
                            </button>
                            <?php endif; ?>
                            <?php 
                            $data = json_decode($log->data, true);
                            if (!empty($data['post_id'])): 
                            ?>
                            <a href="<?php echo get_edit_post_link($data['post_id']); ?>" class="button button-small" target="_blank" title="Edit Post">
                                <span class="dashicons dashicons-edit"></span>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="mws-pagination">
                <?php
                $pagination_args = [
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'current' => $current_page,
                    'total' => $total_pages,
                    'prev_text' => '&laquo; ' . __('Previous', 'manhwa-scraper'),
                    'next_text' => __('Next', 'manhwa-scraper') . ' &raquo;',
                ];
                echo paginate_links($pagination_args);
                ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- ── Clear Logs ── -->
        <div class="mws-card mws-clear-section">
            <h3>
                <span class="dashicons dashicons-trash" style="color: var(--danger); font-size: 18px; width: 18px; height: 18px;"></span>
                <?php esc_html_e('Clear Logs', 'manhwa-scraper'); ?>
            </h3>
            <p class="description">
                <?php esc_html_e('Remove old log entries to free up database space.', 'manhwa-scraper'); ?>
            </p>
            <form method="post" action="" id="mws-clear-logs-form" class="mws-clear-form">
                <?php wp_nonce_field('mws_clear_logs', 'mws_clear_logs_nonce'); ?>
                <select name="clear_period">
                    <option value="7"><?php esc_html_e('Older than 7 days', 'manhwa-scraper'); ?></option>
                    <option value="30"><?php esc_html_e('Older than 30 days', 'manhwa-scraper'); ?></option>
                    <option value="90"><?php esc_html_e('Older than 90 days', 'manhwa-scraper'); ?></option>
                </select>
                <button type="submit" class="button button-secondary" onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete these logs?', 'manhwa-scraper'); ?>');">
                    <span class="dashicons dashicons-dismiss"></span>
                    <?php esc_html_e('Clear Old Logs', 'manhwa-scraper'); ?>
                </button>
                
                <span class="mws-clear-divider"></span>
                
                <button type="submit" name="clear_period" value="all" class="button mws-btn-danger" 
                    onclick="return confirm('<?php esc_attr_e('⚠️ WARNING: This will DELETE ALL LOG DATA permanently!\n\nAre you absolutely sure?', 'manhwa-scraper'); ?>');">
                    <span class="dashicons dashicons-trash"></span>
                    <?php esc_html_e('Delete All Logs', 'manhwa-scraper'); ?>
                </button>
            </form>
            
            <?php 
            global $wpdb;
            $table_name = $wpdb->prefix . 'mws_logs';
            $total_logs = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
            ?>
            <p class="mws-total-logs">
                <?php printf(esc_html__('Total logs in database: %s', 'manhwa-scraper'), '<strong>' . number_format($total_logs) . '</strong>'); ?>
            </p>
        </div>
    </div>
</div>

<!-- Data Modal -->
<div id="mws-data-modal" class="mws-modal" style="display: none;">
    <div class="mws-history-modal">
        <span class="mws-modal-close">&times;</span>
        <h2>
            <span class="dashicons dashicons-info-outline" style="color: var(--primary);"></span>
            <?php esc_html_e('Update Details', 'manhwa-scraper'); ?>
        </h2>
        <div id="mws-modal-data-formatted"></div>
        <details class="mws-raw-toggle" style="margin-top: 16px;">
            <summary><?php esc_html_e('Raw JSON Data', 'manhwa-scraper'); ?></summary>
            <pre id="mws-modal-data" class="mws-json-display" style="margin-top: 10px;"></pre>
        </details>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // View data modal
    $('.mws-view-data').on('click', function() {
        var data = $(this).data('data');
        var parsed = typeof data === 'string' ? JSON.parse(data) : data;
        
        // Format JSON for display
        $('#mws-modal-data').text(JSON.stringify(parsed, null, 2));
        
        // Create formatted view
        var html = '';
        
        if (parsed.title) {
            html += '<h3 style="margin: 0 0 16px; font-size: 16px; font-weight: 700; color: #1F2937;">' + parsed.title + '</h3>';
        }
        
        // Stats row
        var hasStats = parsed.old_chapters !== undefined || parsed.new_chapters !== undefined || parsed.chapters_added !== undefined;
        if (hasStats) {
            html += '<div class="mws-stat-row">';
            if (parsed.old_chapters !== undefined) {
                html += '<div class="mws-stat-item"><div class="label">Before</div><div class="value">' + parsed.old_chapters + '</div></div>';
            }
            if (parsed.new_chapters !== undefined) {
                html += '<div class="mws-stat-item"><div class="label">After</div><div class="value">' + parsed.new_chapters + '</div></div>';
            }
            if (parsed.chapters_added !== undefined) {
                html += '<div class="mws-stat-item highlight"><div class="label">Added</div><div class="value">+' + parsed.chapters_added + '</div></div>';
            }
            html += '</div>';
        }
        
        // New chapters list
        if (parsed.new_chapter_list && parsed.new_chapter_list.length > 0) {
            html += '<div class="mws-detail-card">';
            html += '<h4>📖 New Chapters Added</h4>';
            html += '<div class="mws-chapter-list">';
            parsed.new_chapter_list.forEach(function(ch) {
                html += '<div class="mws-chapter-item">';
                if (ch.url) {
                    html += '<a href="' + ch.url + '" target="_blank">' + (ch.title || 'Chapter ' + ch.number) + '</a>';
                } else {
                    html += (ch.title || 'Chapter ' + ch.number);
                }
                html += '</div>';
            });
            html += '</div></div>';
        }
        
        // Additional info
        var metaHtml = '';
        if (parsed.auto_download !== undefined) {
            metaHtml += '<p class="mws-modal-meta"><strong>Auto Download:</strong> ' + (parsed.auto_download ? '✓ Enabled' : '✗ Disabled') + '</p>';
        }
        if (parsed.auto_scrape_images !== undefined) {
            metaHtml += '<p class="mws-modal-meta"><strong>Auto Scrape Images:</strong> ' + (parsed.auto_scrape_images ? '✓ Enabled' : '✗ Disabled') + '</p>';
        }
        if (parsed.updated_at) {
            metaHtml += '<p class="mws-modal-meta"><strong>Updated:</strong> ' + parsed.updated_at + '</p>';
        }
        html += metaHtml;
        
        $('#mws-modal-data-formatted').html(html);
        $('#mws-data-modal').fadeIn(200);
    });
    
    // Close modal
    $('.mws-modal-close, .mws-modal').on('click', function(e) {
        if (e.target === this) {
            $('#mws-data-modal').fadeOut(200);
        }
    });
    
    // Prevent modal content click from closing
    $('.mws-history-modal').on('click', function(e) {
        e.stopPropagation();
    });
});
</script>
