<?php
/**
 * Statistics Page View
 * Displays scraper statistics and analytics with charts
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

/* ── Design Tokens ── */
.mws-stats-page {
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
    font-family: 'Inter', -apple-system, sans-serif !important;
    font-size: 22px !important;
    font-weight: 800 !important;
    color: #1F2937 !important;
    margin: 0 0 24px 0 !important;
    padding: 0 !important;
}
.mws-stats-title .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
    color: #6366F1;
}

/* ── Cards ── */
.mws-stats-page .mws-card {
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
.mws-stats-page .mws-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--primary), var(--purple));
    opacity: .7;
}

/* ── Card Header ── */
.mws-stats-page .mws-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 14px;
    border-bottom: 1px solid var(--gray-200);
}
.mws-stats-page .mws-card-header h2 {
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
.mws-stats-page .mws-card-header h2 .dashicons {
    font-size: 20px;
    width: 20px;
    height: 20px;
    color: var(--primary);
}

/* ── Stats Grid ── */
.mws-stats-page .mws-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 22px;
}
.mws-stats-page .mws-stat-box {
    background: #fff;
    border-radius: var(--radius-lg);
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--gray-200);
    border-left: 4px solid;
    transition: var(--transition);
}
.mws-stats-page .mws-stat-box:hover {
    box-shadow: var(--shadow);
    transform: translateY(-2px);
}
.mws-stats-page .mws-stat-box.purple { border-left-color: var(--primary); }
.mws-stats-page .mws-stat-box.blue   { border-left-color: var(--blue); }
.mws-stats-page .mws-stat-box.green  { border-left-color: var(--success); }
.mws-stats-page .mws-stat-box.orange { border-left-color: var(--warning); }

.mws-stats-page .mws-stat-icon {
    width: 50px;
    height: 50px;
    border-radius: var(--radius);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.mws-stats-page .mws-stat-icon .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
}
.mws-stats-page .mws-stat-box.purple .mws-stat-icon { background: var(--primary-bg); color: var(--primary); }
.mws-stats-page .mws-stat-box.blue .mws-stat-icon   { background: var(--blue-bg);    color: var(--blue); }
.mws-stats-page .mws-stat-box.green .mws-stat-icon  { background: var(--success-bg); color: var(--success); }
.mws-stats-page .mws-stat-box.orange .mws-stat-icon { background: var(--warning-bg);  color: var(--warning); }

.mws-stats-page .mws-stat-value {
    font-family: var(--font);
    font-size: 28px;
    font-weight: 800;
    color: var(--gray-800);
    line-height: 1;
}
.mws-stats-page .mws-stat-label {
    font-family: var(--font);
    font-size: 12px;
    font-weight: 500;
    color: var(--gray-400);
    margin-top: 4px;
}

/* ── Charts Row ── */
.mws-stats-page .mws-charts-row {
    display: grid;
    grid-template-columns: 1.8fr 1fr;
    gap: 22px;
    margin-bottom: 22px;
}
.mws-stats-page .mws-charts-row .mws-card {
    margin-bottom: 0;
}
.mws-stats-page .mws-chart-card {
    min-height: 380px;
}
.mws-stats-page .mws-chart-container {
    position: relative;
    padding: 8px 0;
}

/* ── Chart Legend ── */
.mws-stats-page .mws-chart-legend {
    display: flex;
    gap: 14px;
}
.mws-stats-page .legend-item {
    display: flex;
    align-items: center;
    font-family: var(--font);
    font-size: 12px;
    font-weight: 600;
    color: var(--gray-500);
}
.mws-stats-page .legend-item .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 6px;
}
.mws-stats-page .legend-item.success .dot { background: var(--success); }
.mws-stats-page .legend-item.error .dot   { background: var(--danger); }

/* ── Loading State ── */
.mws-stats-page .loading-row {
    text-align: center;
    padding: 40px 20px;
    color: var(--gray-400);
    font-family: var(--font);
    font-size: 13px;
}
.mws-stats-page .loading-row .spinner {
    float: none;
    margin-right: 8px;
}

/* ── Tables ── */
.mws-stats-page .wp-list-table {
    border-radius: var(--radius-sm) !important;
    overflow: hidden;
    border-collapse: separate;
    border-spacing: 0;
}
.mws-stats-page .wp-list-table thead th {
    font-family: var(--font) !important;
    font-size: 10.5px !important;
    font-weight: 700 !important;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--gray-400) !important;
    background: var(--gray-50) !important;
    border-bottom: 2px solid var(--gray-200) !important;
    padding: 10px 14px !important;
}
.mws-stats-page .wp-list-table td {
    font-family: var(--font) !important;
    font-size: 13px !important;
    color: var(--gray-600);
    padding: 11px 14px !important;
    vertical-align: middle;
}
.mws-stats-page .wp-list-table tbody tr {
    transition: var(--transition);
}
.mws-stats-page .wp-list-table tbody tr:hover {
    background: var(--primary-bg) !important;
}

/* ── Success Rate Bar ── */
.mws-stats-page .success-rate-bar {
    background: var(--gray-100);
    border-radius: 10px;
    height: 6px;
    overflow: hidden;
    min-width: 80px;
    flex: 1;
}
.mws-stats-page .success-rate-fill {
    height: 100%;
    border-radius: 10px;
    transition: width .4s cubic-bezier(.4,0,.2,1);
}
.mws-stats-page .success-rate-fill.high   { background: linear-gradient(90deg, var(--success), #34D399); }
.mws-stats-page .success-rate-fill.medium { background: linear-gradient(90deg, var(--warning), #FBBF24); }
.mws-stats-page .success-rate-fill.low    { background: linear-gradient(90deg, var(--danger), #F87171); }

/* ── Status Badge ── */
.mws-stats-page .status-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 12px;
    border-radius: 16px;
    font-family: var(--font);
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.mws-stats-page .status-badge.active {
    background: var(--success-bg);
    color: var(--success);
    border: 1px solid rgba(16,185,129,.15);
}
.mws-stats-page .status-badge.inactive {
    background: var(--gray-100);
    color: var(--gray-400);
    border: 1px solid var(--gray-200);
}

/* ── Error Items ── */
.mws-stats-page .error-item {
    display: flex;
    align-items: flex-start;
    padding: 14px 16px;
    gap: 14px;
    border-radius: var(--radius-sm);
    transition: var(--transition);
    margin-bottom: 2px;
}
.mws-stats-page .error-item:hover {
    background: var(--gray-50);
}
.mws-stats-page .error-item + .error-item {
    border-top: 1px solid var(--gray-100);
}
.mws-stats-page .error-icon {
    width: 38px;
    height: 38px;
    background: var(--danger-bg);
    border: 1px solid rgba(239,68,68,.1);
    border-radius: var(--radius);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--danger);
    flex-shrink: 0;
}
.mws-stats-page .error-icon .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}
.mws-stats-page .error-content {
    flex: 1;
    min-width: 0;
}
.mws-stats-page .error-title {
    font-family: var(--font);
    font-weight: 600;
    font-size: 13px;
    color: var(--gray-800);
    margin-bottom: 3px;
    word-break: break-word;
}
.mws-stats-page .error-message {
    font-family: var(--font);
    font-size: 12px;
    color: var(--danger);
    margin-bottom: 4px;
    line-height: 1.5;
    word-break: break-word;
}
.mws-stats-page .error-meta {
    font-family: var(--font);
    font-size: 11px;
    color: var(--gray-400);
    font-weight: 500;
}

/* ── Top Manhwa List ── */
.mws-stats-page .top-manhwa-item {
    display: flex;
    align-items: center;
    padding: 11px 14px;
    gap: 14px;
    border-radius: var(--radius-sm);
    transition: var(--transition);
}
.mws-stats-page .top-manhwa-item:hover {
    background: var(--gray-50);
}
.mws-stats-page .top-manhwa-item + .top-manhwa-item {
    border-top: 1px solid var(--gray-100);
}
.mws-stats-page .top-manhwa-rank {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: var(--font);
    font-weight: 800;
    font-size: 12px;
    color: #fff;
    flex-shrink: 0;
    background: var(--gray-300);
}
.mws-stats-page .top-manhwa-rank.gold {
    background: linear-gradient(135deg, #F59E0B, #D97706);
    box-shadow: 0 2px 8px rgba(245,158,11,.35);
}
.mws-stats-page .top-manhwa-rank.silver {
    background: linear-gradient(135deg, #9CA3AF, #6B7280);
    box-shadow: 0 2px 8px rgba(107,114,128,.25);
}
.mws-stats-page .top-manhwa-rank.bronze {
    background: linear-gradient(135deg, #D97706, #B45309);
    box-shadow: 0 2px 8px rgba(180,83,9,.25);
}
.mws-stats-page .top-manhwa-info {
    flex: 1;
    min-width: 0;
}
.mws-stats-page .top-manhwa-title {
    font-family: var(--font);
    font-weight: 600;
    font-size: 13px;
    color: var(--gray-800);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.mws-stats-page .top-manhwa-count {
    font-family: var(--font);
    font-size: 11.5px;
    color: var(--gray-400);
    font-weight: 500;
    margin-top: 1px;
}
.mws-stats-page .top-manhwa-scrapes {
    font-family: var(--font);
    font-size: 11px;
    font-weight: 700;
    background: var(--primary-bg);
    color: var(--primary-dark);
    padding: 3px 10px;
    border-radius: 12px;
    border: 1px solid rgba(99,102,241,.12);
    flex-shrink: 0;
}

/* ── Timeline ── */
.mws-stats-page .timeline-item {
    display: flex;
    padding: 10px 14px;
    gap: 14px;
    align-items: flex-start;
    position: relative;
    transition: var(--transition);
}
.mws-stats-page .timeline-item:hover {
    background: var(--gray-50);
    border-radius: var(--radius-sm);
}
/* vertical connector line */
.mws-stats-page .timeline-item:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 19px;
    top: 28px;
    bottom: -4px;
    width: 2px;
    background: var(--gray-200);
}
.mws-stats-page .timeline-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-top: 5px;
    flex-shrink: 0;
    position: relative;
    z-index: 1;
    box-shadow: 0 0 0 3px #fff, 0 0 0 4px var(--gray-200);
}
.mws-stats-page .timeline-dot.success { background: var(--success); box-shadow: 0 0 0 3px #fff, 0 0 0 4px rgba(16,185,129,.3); }
.mws-stats-page .timeline-dot.error   { background: var(--danger);  box-shadow: 0 0 0 3px #fff, 0 0 0 4px rgba(239,68,68,.3); }
.mws-stats-page .timeline-content {
    flex: 1;
    min-width: 0;
}
.mws-stats-page .timeline-title {
    font-family: var(--font);
    font-size: 13px;
    font-weight: 500;
    color: var(--gray-700);
    margin-bottom: 2px;
    word-break: break-word;
}
.mws-stats-page .timeline-time {
    font-family: var(--font);
    font-size: 11px;
    color: var(--gray-400);
    font-weight: 500;
}

/* ── 2-column Row ── */
.mws-stats-page .mws-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 22px;
    margin-bottom: 22px;
}
.mws-stats-page .mws-row .mws-card {
    margin-bottom: 0;
}
.mws-stats-page .mws-col-6 { min-width: 0; }

/* ── Empty State ── */
.mws-stats-page .empty-state {
    text-align: center;
    padding: 46px 20px;
    color: var(--gray-400);
}
.mws-stats-page .empty-state .dashicons {
    font-size: 42px;
    width: 42px;
    height: 42px;
    opacity: .3;
    margin-bottom: 8px;
    display: block;
    margin-left: auto;
    margin-right: auto;
}
.mws-stats-page .empty-state p {
    font-family: var(--font);
    font-size: 13px;
    color: var(--gray-400);
    margin: 0;
}

/* ── Responsive ── */
@media (max-width: 1200px) {
    .mws-stats-page .mws-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .mws-stats-page .mws-charts-row {
        grid-template-columns: 1fr;
    }
    .mws-stats-page .mws-row {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 782px) {
    .mws-stats-page .mws-stats-grid {
        grid-template-columns: 1fr;
    }
    .mws-stats-page .mws-card {
        padding: 16px !important;
    }
    .mws-stats-page .mws-stat-box {
        padding: 16px;
    }
    .mws-stats-page .mws-stat-value {
        font-size: 22px;
    }
    .mws-stats-page .mws-stat-icon {
        width: 42px;
        height: 42px;
    }
    .mws-stats-page .mws-card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    .mws-stats-page .mws-chart-card {
        min-height: 280px;
    }
    .mws-stats-page .error-item {
        flex-direction: column;
        gap: 10px;
    }
}
</style>

<div class="wrap mws-wrap">
    <h1 class="mws-stats-title">
        <span class="dashicons dashicons-chart-area"></span>
        <?php esc_html_e('Statistics & Analytics', 'manhwa-scraper'); ?>
    </h1>
    
    <div class="mws-stats-page">
        <!-- ── Stats Summary Cards ── -->
        <div class="mws-stats-grid">
            <div class="mws-stat-box purple">
                <div class="mws-stat-icon"><span class="dashicons dashicons-book"></span></div>
                <div class="mws-stat-info">
                    <div class="mws-stat-value" id="stat-total-manhwa">--</div>
                    <div class="mws-stat-label"><?php esc_html_e('Total Manhwa', 'manhwa-scraper'); ?></div>
                </div>
            </div>
            <div class="mws-stat-box blue">
                <div class="mws-stat-icon"><span class="dashicons dashicons-download"></span></div>
                <div class="mws-stat-info">
                    <div class="mws-stat-value" id="stat-total-scrapes">--</div>
                    <div class="mws-stat-label"><?php esc_html_e('Total Scrapes', 'manhwa-scraper'); ?></div>
                </div>
            </div>
            <div class="mws-stat-box green">
                <div class="mws-stat-icon"><span class="dashicons dashicons-yes-alt"></span></div>
                <div class="mws-stat-info">
                    <div class="mws-stat-value" id="stat-success-rate">--%</div>
                    <div class="mws-stat-label"><?php esc_html_e('Success Rate', 'manhwa-scraper'); ?></div>
                </div>
            </div>
            <div class="mws-stat-box orange">
                <div class="mws-stat-icon"><span class="dashicons dashicons-warning"></span></div>
                <div class="mws-stat-info">
                    <div class="mws-stat-value" id="stat-total-errors">--</div>
                    <div class="mws-stat-label"><?php esc_html_e('Total Errors', 'manhwa-scraper'); ?></div>
                </div>
            </div>
        </div>
        
        <!-- ── Charts Row ── -->
        <div class="mws-charts-row">
            <!-- Activity Chart -->
            <div class="mws-card mws-chart-card">
                <div class="mws-card-header">
                    <h2>
                        <span class="dashicons dashicons-chart-area"></span>
                        <?php esc_html_e('Scrape Activity (Last 30 Days)', 'manhwa-scraper'); ?>
                    </h2>
                    <div class="mws-chart-legend">
                        <span class="legend-item success"><span class="dot"></span> Success</span>
                        <span class="legend-item error"><span class="dot"></span> Error</span>
                    </div>
                </div>
                <div class="mws-chart-container">
                    <canvas id="activityChart" height="300"></canvas>
                </div>
            </div>
            
            <!-- Source Distribution -->
            <div class="mws-card mws-chart-card mws-chart-small">
                <div class="mws-card-header">
                    <h2>
                        <span class="dashicons dashicons-chart-pie"></span>
                        <?php esc_html_e('Source Distribution', 'manhwa-scraper'); ?>
                    </h2>
                </div>
                <div class="mws-chart-container">
                    <canvas id="sourceChart" height="250"></canvas>
                </div>
            </div>
        </div>
        
        <!-- ── Source Performance Table ── -->
        <div class="mws-card">
            <div class="mws-card-header">
                <h2>
                    <span class="dashicons dashicons-performance"></span>
                    <?php esc_html_e('Source Performance', 'manhwa-scraper'); ?>
                </h2>
            </div>
            <table class="wp-list-table widefat fixed striped" id="source-stats-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Source', 'manhwa-scraper'); ?></th>
                        <th><?php esc_html_e('Total Scrapes', 'manhwa-scraper'); ?></th>
                        <th><?php esc_html_e('Success', 'manhwa-scraper'); ?></th>
                        <th><?php esc_html_e('Errors', 'manhwa-scraper'); ?></th>
                        <th><?php esc_html_e('Success Rate', 'manhwa-scraper'); ?></th>
                        <th><?php esc_html_e('Avg Response Time', 'manhwa-scraper'); ?></th>
                        <th><?php esc_html_e('Status', 'manhwa-scraper'); ?></th>
                    </tr>
                </thead>
                <tbody id="source-stats-body">
                    <tr>
                        <td colspan="7" class="loading-row">
                            <span class="spinner is-active"></span>
                            <?php esc_html_e('Loading statistics...', 'manhwa-scraper'); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- ── Recent Errors ── -->
        <div class="mws-card">
            <div class="mws-card-header">
                <h2>
                    <span class="dashicons dashicons-warning"></span>
                    <?php esc_html_e('Recent Errors', 'manhwa-scraper'); ?>
                </h2>
            </div>
            <div id="recent-errors-container">
                <div class="loading-row">
                    <span class="spinner is-active"></span>
                    <?php esc_html_e('Loading errors...', 'manhwa-scraper'); ?>
                </div>
            </div>
        </div>
        
        <!-- ── Bottom 2-col Row ── -->
        <div class="mws-row">
            <div class="mws-col-6">
                <div class="mws-card">
                    <div class="mws-card-header">
                        <h2>
                            <span class="dashicons dashicons-star-filled"></span>
                            <?php esc_html_e('Most Scraped Manhwa', 'manhwa-scraper'); ?>
                        </h2>
                    </div>
                    <div id="top-manhwa-container">
                        <div class="loading-row">
                            <span class="spinner is-active"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mws-col-6">
                <div class="mws-card">
                    <div class="mws-card-header">
                        <h2>
                            <span class="dashicons dashicons-backup"></span>
                            <?php esc_html_e('Activity Timeline', 'manhwa-scraper'); ?>
                        </h2>
                    </div>
                    <div id="timeline-container">
                        <div class="loading-row">
                            <span class="spinner is-active"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
jQuery(document).ready(function($) {
    loadStatistics();
    
    function loadStatistics() {
        $.ajax({
            url: mwsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mws_get_statistics',
                nonce: mwsData.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderStatistics(response.data);
                } else {
                    showError('Failed to load statistics');
                }
            },
            error: function() {
                showError('Error loading statistics');
            }
        });
    }
    
    function renderStatistics(data) {
        // Update summary cards with animated counter
        animateValue($('#stat-total-manhwa'), data.summary.total_manhwa);
        animateValue($('#stat-total-scrapes'), data.summary.total_scrapes);
        $('#stat-success-rate').text(data.summary.success_rate + '%');
        animateValue($('#stat-total-errors'), data.summary.total_errors);
        
        renderActivityChart(data.activity);
        renderSourceChart(data.sources);
        renderSourceTable(data.sources);
        renderRecentErrors(data.recent_errors);
        renderTopManhwa(data.top_manhwa);
        renderTimeline(data.timeline);
    }
    
    function animateValue($el, target) {
        var start = 0;
        var duration = 600;
        var startTime = null;
        function step(ts) {
            if (!startTime) startTime = ts;
            var progress = Math.min((ts - startTime) / duration, 1);
            var eased = 1 - Math.pow(1 - progress, 3);
            $el.text(Math.floor(eased * target).toLocaleString());
            if (progress < 1) requestAnimationFrame(step);
            else $el.text(target.toLocaleString());
        }
        if (target > 0) requestAnimationFrame(step);
        else $el.text('0');
    }
    
    function renderActivityChart(activity) {
        var ctx = document.getElementById('activityChart').getContext('2d');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: activity.labels,
                datasets: [{
                    label: 'Success',
                    data: activity.success,
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16,185,129,0.06)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2.5,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#10B981',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }, {
                    label: 'Errors',
                    data: activity.errors,
                    borderColor: '#EF4444',
                    backgroundColor: 'rgba(239,68,68,0.04)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2.5,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#EF4444',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: '#1F2937',
                        titleFont: { family: "'Inter', sans-serif", size: 12, weight: 600 },
                        bodyFont: { family: "'Inter', sans-serif", size: 12 },
                        padding: 12,
                        cornerRadius: 8,
                        boxPadding: 4
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: "'Inter', sans-serif", size: 11 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,.04)' },
                        ticks: {
                            font: { family: "'Inter', sans-serif", size: 11 },
                            stepSize: 1
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    }
    
    function renderSourceChart(sources) {
        var ctx = document.getElementById('sourceChart').getContext('2d');
        
        var labels = sources.map(function(s) { return s.name; });
        var data = sources.map(function(s) { return s.total; });
        var colors = ['#6366F1', '#8B5CF6', '#EC4899', '#EF4444', '#14B8A6', '#F59E0B'];
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors.slice(0, sources.length),
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverOffset: 8
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
    }
    
    function renderSourceTable(sources) {
        var $tbody = $('#source-stats-body').empty();
        
        if (sources.length === 0) {
            $tbody.html('<tr><td colspan="7" class="empty-state"><span class="dashicons dashicons-database"></span><p>No source data available</p></td></tr>');
            return;
        }
        
        sources.forEach(function(source) {
            var rateClass = source.success_rate >= 80 ? 'high' : (source.success_rate >= 50 ? 'medium' : 'low');
            var statusClass = source.active ? 'active' : 'inactive';
            var statusText = source.active ? 'Active' : 'Inactive';
            
            $tbody.append(
                '<tr>' +
                    '<td><strong>' + source.name + '</strong></td>' +
                    '<td>' + source.total.toLocaleString() + '</td>' +
                    '<td style="color: #10B981; font-weight: 600;">' + source.success.toLocaleString() + '</td>' +
                    '<td style="color: #EF4444; font-weight: 600;">' + source.errors.toLocaleString() + '</td>' +
                    '<td>' +
                        '<div style="display: flex; align-items: center; gap: 10px;">' +
                            '<div class="success-rate-bar">' +
                                '<div class="success-rate-fill ' + rateClass + '" style="width: ' + source.success_rate + '%;"></div>' +
                            '</div>' +
                            '<span style="font-weight: 700; font-size: 12px; color: #374151; min-width: 36px;">' + source.success_rate + '%</span>' +
                        '</div>' +
                    '</td>' +
                    '<td><span style="font-family: \'Cascadia Code\', monospace; font-size: 12px;">' + (source.avg_response_time || 'N/A') + '</span></td>' +
                    '<td><span class="status-badge ' + statusClass + '">' + statusText + '</span></td>' +
                '</tr>'
            );
        });
    }
    
    function renderRecentErrors(errors) {
        var $container = $('#recent-errors-container').empty();
        
        if (errors.length === 0) {
            $container.html(
                '<div class="empty-state">' +
                    '<span class="dashicons dashicons-yes-alt"></span>' +
                    '<p>No recent errors! Everything is working smoothly.</p>' +
                '</div>'
            );
            return;
        }
        
        errors.forEach(function(error) {
            $container.append(
                '<div class="error-item">' +
                    '<div class="error-icon"><span class="dashicons dashicons-warning"></span></div>' +
                    '<div class="error-content">' +
                        '<div class="error-title">' + error.title + '</div>' +
                        '<div class="error-message">' + error.message + '</div>' +
                        '<div class="error-meta">' + error.source + ' • ' + error.time_ago + '</div>' +
                    '</div>' +
                '</div>'
            );
        });
    }
    
    function renderTopManhwa(manhwa) {
        var $container = $('#top-manhwa-container').empty();
        
        if (manhwa.length === 0) {
            $container.html(
                '<div class="empty-state">' +
                    '<span class="dashicons dashicons-book"></span>' +
                    '<p>No scrape data yet</p>' +
                '</div>'
            );
            return;
        }
        
        manhwa.forEach(function(item, index) {
            var rankClass = index === 0 ? 'gold' : (index === 1 ? 'silver' : (index === 2 ? 'bronze' : ''));
            
            $container.append(
                '<div class="top-manhwa-item">' +
                    '<div class="top-manhwa-rank ' + rankClass + '">' + (index + 1) + '</div>' +
                    '<div class="top-manhwa-info">' +
                        '<div class="top-manhwa-title">' + item.title + '</div>' +
                        '<div class="top-manhwa-count">' + item.count + ' scrapes</div>' +
                    '</div>' +
                    '<span class="top-manhwa-scrapes">' + item.count + 'x</span>' +
                '</div>'
            );
        });
    }
    
    function renderTimeline(timeline) {
        var $container = $('#timeline-container').empty();
        
        if (timeline.length === 0) {
            $container.html(
                '<div class="empty-state">' +
                    '<span class="dashicons dashicons-clock"></span>' +
                    '<p>No activity yet</p>' +
                '</div>'
            );
            return;
        }
        
        timeline.forEach(function(item) {
            $container.append(
                '<div class="timeline-item">' +
                    '<div class="timeline-dot ' + item.status + '"></div>' +
                    '<div class="timeline-content">' +
                        '<div class="timeline-title">' + item.title + '</div>' +
                        '<div class="timeline-time">' + item.time_ago + '</div>' +
                    '</div>' +
                '</div>'
            );
        });
    }
    
    function showError(message) {
        $('#source-stats-body').html(
            '<tr><td colspan="7" style="color: #EF4444; text-align: center; font-family: var(--font); padding: 30px;">' + message + '</td></tr>'
        );
    }
});
</script>
