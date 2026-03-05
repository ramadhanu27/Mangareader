<?php
/**
 * Dashboard Page View — Enhanced
 */

if (!defined('ABSPATH')) {
    exit;
}

// Helper to format bytes
function mws_format_bytes($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 0) . ' KB';
    return $bytes . ' B';
}
?>

<div class="wrap mws-wrap">
    <h1 class="mws-title">
        <span class="dashicons dashicons-download"></span>
        <?php esc_html_e('Manhwa Metadata Scraper', 'manhwa-scraper'); ?>
    </h1>
    
    <div class="mws-dashboard">

        <!-- ═══════════════════ STAT CARDS (Row 1) ═══════════════════ -->
        <div class="mws-stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(200px,1fr))">
            <!-- Total Scraped -->
            <div class="mws-stat-card">
                <div class="mws-stat-icon success">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="mws-stat-content">
                    <h3><?php echo esc_html($stats['total_scraped']); ?></h3>
                    <p><?php esc_html_e('Total Scraped', 'manhwa-scraper'); ?></p>
                </div>
            </div>

            <!-- Scraped Today -->
            <div class="mws-stat-card">
                <div class="mws-stat-icon primary">
                    <span class="dashicons dashicons-calendar-alt"></span>
                </div>
                <div class="mws-stat-content">
                    <h3><?php echo esc_html($stats['today_scraped']); ?></h3>
                    <p><?php esc_html_e('Scraped Today', 'manhwa-scraper'); ?></p>
                </div>
            </div>

            <!-- Errors -->
            <div class="mws-stat-card">
                <div class="mws-stat-icon warning">
                    <span class="dashicons dashicons-warning"></span>
                </div>
                <div class="mws-stat-content">
                    <h3><?php echo esc_html($stats['total_errors']); ?></h3>
                    <p><?php esc_html_e('Errors', 'manhwa-scraper'); ?></p>
                </div>
            </div>

            <!-- Next Update -->
            <div class="mws-stat-card">
                <div class="mws-stat-icon info">
                    <span class="dashicons dashicons-clock"></span>
                </div>
                <div class="mws-stat-content">
                    <h3><?php echo $next_cron ? esc_html(human_time_diff($next_cron)) : '-'; ?></h3>
                    <p><?php esc_html_e('Next Update', 'manhwa-scraper'); ?></p>
                </div>
            </div>

            <!-- Queue -->
            <div class="mws-stat-card">
                <div class="mws-stat-icon" style="background:#fef3c7;color:#d97706">
                    <span class="dashicons dashicons-list-view"></span>
                </div>
                <div class="mws-stat-content">
                    <h3><?php echo esc_html($queue_count); ?></h3>
                    <p><?php esc_html_e('In Queue', 'manhwa-scraper'); ?></p>
                </div>
            </div>

            <!-- Storage -->
            <div class="mws-stat-card">
                <div class="mws-stat-icon" style="background:#ede9fe;color:#7c3aed">
                    <span class="dashicons dashicons-cloud"></span>
                </div>
                <div class="mws-stat-content">
                    <h3><?php echo esc_html(mws_format_bytes($storage_size)); ?></h3>
                    <p><?php esc_html_e('Storage Used', 'manhwa-scraper'); ?></p>
                </div>
            </div>
        </div>

        <!-- ═══════════════════ AUTO-PILOT STATUS BAR ═══════════════════ -->
        <div class="mws-card" style="margin-bottom:20px">
            <h2 style="margin-bottom:12px">
                <span class="dashicons dashicons-controls-play" style="color:#059669;margin-right:4px"></span>
                <?php esc_html_e('Auto-Pilot Status', 'manhwa-scraper'); ?>
            </h2>
            <div style="display:flex;flex-wrap:wrap;gap:16px;align-items:center">
                <!-- Auto Update -->
                <div class="mws-pilot-badge <?php echo $auto_update_on ? 'on' : 'off'; ?>">
                    <span class="dashicons dashicons-update"></span>
                    <span><?php esc_html_e('Auto Update', 'manhwa-scraper'); ?></span>
                    <span class="mws-pilot-dot"></span>
                </div>
                <!-- Auto Discovery -->
                <div class="mws-pilot-badge <?php echo $auto_discovery_on ? 'on' : 'off'; ?>">
                    <span class="dashicons dashicons-search"></span>
                    <span><?php esc_html_e('Auto Discovery', 'manhwa-scraper'); ?></span>
                    <span class="mws-pilot-dot"></span>
                </div>
                <!-- Auto Image Scraper -->
                <div class="mws-pilot-badge <?php echo $auto_image_on ? 'on' : 'off'; ?>">
                    <span class="dashicons dashicons-format-image"></span>
                    <span><?php esc_html_e('Auto Image Scraper', 'manhwa-scraper'); ?></span>
                    <span class="mws-pilot-dot"></span>
                </div>
                <!-- Tracked -->
                <div style="margin-left:auto;font-size:13px;color:#64748b">
                    <strong><?php echo esc_html($tracked_manhwa); ?></strong> <?php esc_html_e('tracked manhwa', 'manhwa-scraper'); ?>
                    &nbsp;·&nbsp;
                    <?php esc_html_e('Weekly success rate:', 'manhwa-scraper'); ?>
                    <strong style="color:<?php echo $success_rate >= 90 ? '#059669' : ($success_rate >= 70 ? '#d97706' : '#dc2626'); ?>">
                        <?php echo esc_html($success_rate); ?>%
                    </strong>
                </div>
            </div>
        </div>

        <!-- ═══════════════════ ROW: Chart + Quick Actions ═══════════════════ -->
        <div class="mws-row">
            <!-- Activity Chart — 7 Days -->
            <div class="mws-col-8">
                <div class="mws-card">
                    <h2><?php esc_html_e('Scraping Activity — Last 7 Days', 'manhwa-scraper'); ?></h2>
                    <div style="position:relative;height:220px;margin-top:8px" id="mws-chart-container">
                        <canvas id="mws-activity-chart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="mws-col-4">
                <div class="mws-card">
                    <h2><?php esc_html_e('Quick Actions', 'manhwa-scraper'); ?></h2>
                    <div class="mws-quick-actions" style="flex-direction:column">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=manhwa-scraper-search')); ?>" class="button button-primary button-hero" style="width:100%;text-align:center">
                            <span class="dashicons dashicons-search"></span>
                            <?php esc_html_e('Search & Import', 'manhwa-scraper'); ?>
                        </a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=manhwa-scraper-import')); ?>" class="button button-secondary button-hero" style="width:100%;text-align:center">
                            <span class="dashicons dashicons-upload"></span>
                            <?php esc_html_e('Import Single', 'manhwa-scraper'); ?>
                        </a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=manhwa-scraper-bulk')); ?>" class="button button-secondary button-hero" style="width:100%;text-align:center">
                            <span class="dashicons dashicons-download"></span>
                            <?php esc_html_e('Bulk Scrape', 'manhwa-scraper'); ?>
                        </a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=manhwa-scraper-settings')); ?>" class="button button-secondary button-hero" style="width:100%;text-align:center">
                            <span class="dashicons dashicons-admin-generic"></span>
                            <?php esc_html_e('Settings', 'manhwa-scraper'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════ ROW: Source Status + Top Errors ═══════════════════ -->
        <div class="mws-row">
            <!-- Source Status -->
            <div class="mws-col-6">
                <div class="mws-card">
                    <h2><?php esc_html_e('Source Status', 'manhwa-scraper'); ?></h2>
                    <div class="mws-source-list">
                        <?php foreach ($sources as $source): ?>
                        <div class="mws-source-item" data-source="<?php echo esc_attr($source['id']); ?>">
                            <div class="mws-source-info">
                                <strong><?php echo esc_html($source['name']); ?></strong>
                                <span class="mws-source-url"><?php echo esc_html($source['url']); ?></span>
                            </div>
                            <button type="button" class="button mws-test-connection" data-source="<?php echo esc_attr($source['id']); ?>">
                                <?php esc_html_e('Test', 'manhwa-scraper'); ?>
                            </button>
                            <span class="mws-status-badge pending"><?php esc_html_e('Unknown', 'manhwa-scraper'); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="button mws-test-all-connections" style="margin-top: 10px;">
                        <?php esc_html_e('Test All Connections', 'manhwa-scraper'); ?>
                    </button>
                </div>
            </div>

            <!-- Top Errors -->
            <div class="mws-col-6">
                <div class="mws-card">
                    <h2>
                        <span class="dashicons dashicons-flag" style="color:#dc2626;margin-right:4px"></span>
                        <?php esc_html_e('Top Failing URLs (7 Days)', 'manhwa-scraper'); ?>
                    </h2>
                    <?php if (!empty($top_errors)): ?>
                    <table class="wp-list-table widefat fixed striped" style="margin-top:8px">
                        <thead>
                            <tr>
                                <th style="width:50%"><?php esc_html_e('URL', 'manhwa-scraper'); ?></th>
                                <th style="width:15%;text-align:center"><?php esc_html_e('Fails', 'manhwa-scraper'); ?></th>
                                <th><?php esc_html_e('Last Error', 'manhwa-scraper'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_errors as $err): ?>
                            <tr>
                                <td class="mws-truncate" title="<?php echo esc_attr($err->url); ?>">
                                    <?php echo esc_html(strlen($err->url) > 45 ? substr($err->url, 0, 45) . '...' : $err->url); ?>
                                </td>
                                <td style="text-align:center">
                                    <span class="mws-status-badge error"><?php echo esc_html($err->error_count); ?>×</span>
                                </td>
                                <td style="font-size:12px;color:#64748b">
                                    <?php echo esc_html(strlen($err->last_message) > 50 ? substr($err->last_message, 0, 50) . '...' : $err->last_message); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p style="color:#94a3b8;text-align:center;padding:24px 0">
                        <span class="dashicons dashicons-smiley" style="font-size:28px;width:28px;height:28px;display:block;margin:0 auto 8px"></span>
                        <?php esc_html_e('No errors in the last 7 days!', 'manhwa-scraper'); ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ═══════════════════ RECENT ACTIVITY ═══════════════════ -->
        <div class="mws-card">
            <h2><?php esc_html_e('Recent Activity', 'manhwa-scraper'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Time', 'manhwa-scraper'); ?></th>
                        <th><?php esc_html_e('Source', 'manhwa-scraper'); ?></th>
                        <th><?php esc_html_e('URL', 'manhwa-scraper'); ?></th>
                        <th><?php esc_html_e('Status', 'manhwa-scraper'); ?></th>
                        <th><?php esc_html_e('Message', 'manhwa-scraper'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_logs)): ?>
                    <tr>
                        <td colspan="5"><?php esc_html_e('No activity yet', 'manhwa-scraper'); ?></td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($recent_logs as $log): ?>
                    <tr>
                        <td><?php echo esc_html(human_time_diff(strtotime($log->created_at)) . ' ago'); ?></td>
                        <td><span class="mws-badge"><?php echo esc_html($log->source); ?></span></td>
                        <td class="mws-truncate" title="<?php echo esc_attr($log->url); ?>">
                            <?php echo esc_html(strlen($log->url) > 50 ? substr($log->url, 0, 50) . '...' : $log->url); ?>
                        </td>
                        <td>
                            <span class="mws-status-badge <?php echo esc_attr($log->status); ?>">
                                <?php echo esc_html(ucfirst($log->status)); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($log->message); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <p style="margin-top: 10px;">
                <a href="<?php echo esc_url(admin_url('admin.php?page=manhwa-scraper-history')); ?>">
                    <?php esc_html_e('View All History →', 'manhwa-scraper'); ?>
                </a>
            </p>
        </div>
    </div>
</div>

<!-- ═══════════════════ CHART.JS (tiny CDN) + inline chart init ═══════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
(function() {
    var data = <?php echo json_encode($chart_data); ?>;
    var labels = data.map(function(d) { return d.label; });
    var success = data.map(function(d) { return d.success; });
    var errors = data.map(function(d) { return d.errors; });

    var ctx = document.getElementById('mws-activity-chart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: '<?php esc_html_e('Success', 'manhwa-scraper'); ?>',
                    data: success,
                    backgroundColor: 'rgba(5,150,105,0.7)',
                    borderRadius: 4,
                    barPercentage: 0.6,
                    categoryPercentage: 0.7
                },
                {
                    label: '<?php esc_html_e('Errors', 'manhwa-scraper'); ?>',
                    data: errors,
                    backgroundColor: 'rgba(220,38,38,0.6)',
                    borderRadius: 4,
                    barPercentage: 0.6,
                    categoryPercentage: 0.7
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, padding: 16 } }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 },
                    grid: { color: 'rgba(0,0,0,0.04)' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
})();
</script>

<!-- ═══════════════════ AUTO-PILOT BADGE STYLES ═══════════════════ -->
<style>
.mws-pilot-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    transition: all .2s;
}
.mws-pilot-badge .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}
.mws-pilot-badge.on {
    background: #ecfdf5;
    color: #059669;
    border: 1px solid #a7f3d0;
}
.mws-pilot-badge.off {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
}
.mws-pilot-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
}
.mws-pilot-badge.on .mws-pilot-dot {
    background: #10b981;
    box-shadow: 0 0 4px rgba(16,185,129,0.5);
    animation: mwsPulse 2s infinite;
}
.mws-pilot-badge.off .mws-pilot-dot {
    background: #ef4444;
}
@keyframes mwsPulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}

/* Layout helpers for 8-4 and 6-6 grids inside mws-row */
.mws-col-4 {
    flex: 0 0 33.333%;
    max-width: 33.333%;
    padding: 0 10px;
    box-sizing: border-box;
}
.mws-col-8 {
    flex: 0 0 66.666%;
    max-width: 66.666%;
    padding: 0 10px;
    box-sizing: border-box;
}
@media (max-width: 960px) {
    .mws-col-4, .mws-col-8 {
        flex: 0 0 100%;
        max-width: 100%;
    }
}
</style>
