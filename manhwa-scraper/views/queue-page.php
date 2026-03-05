<?php
/**
 * Download Queue Page View
 */

if (!defined('ABSPATH')) {
    exit;
}

// Auto-create table if not exists
MWS_Queue_Manager::create_table();

$queue_manager = MWS_Queue_Manager::get_instance();
$stats = $queue_manager->get_stats();
$queue_items = $queue_manager->get_queue(null, 100);
?>

<div class="wrap mws-wrap">
    <h1 class="mws-title">
        <span class="dashicons dashicons-list-view"></span>
        <?php esc_html_e('Download Queue', 'manhwa-scraper'); ?>
    </h1>
    
    <div class="mws-queue-page">
        <!-- Stats Cards -->
        <div class="mws-row" style="margin-bottom: 20px;">
            <div class="mws-col-3">
                <div class="mws-stat-card pending">
                    <div class="stat-icon"><span class="dashicons dashicons-clock"></span></div>
                    <div class="stat-content">
                        <div class="stat-number" id="stat-pending"><?php echo $stats['pending']; ?></div>
                        <div class="stat-label"><?php esc_html_e('Pending', 'manhwa-scraper'); ?></div>
                    </div>
                </div>
            </div>
            <div class="mws-col-3">
                <div class="mws-stat-card processing">
                    <div class="stat-icon"><span class="dashicons dashicons-update"></span></div>
                    <div class="stat-content">
                        <div class="stat-number" id="stat-processing"><?php echo $stats['processing']; ?></div>
                        <div class="stat-label"><?php esc_html_e('Processing', 'manhwa-scraper'); ?></div>
                    </div>
                </div>
            </div>
            <div class="mws-col-3">
                <div class="mws-stat-card completed">
                    <div class="stat-icon"><span class="dashicons dashicons-yes-alt"></span></div>
                    <div class="stat-content">
                        <div class="stat-number" id="stat-completed"><?php echo $stats['completed']; ?></div>
                        <div class="stat-label"><?php esc_html_e('Completed', 'manhwa-scraper'); ?></div>
                    </div>
                </div>
            </div>
            <div class="mws-col-3">
                <div class="mws-stat-card failed">
                    <div class="stat-icon"><span class="dashicons dashicons-warning"></span></div>
                    <div class="stat-content">
                        <div class="stat-number" id="stat-failed"><?php echo $stats['failed']; ?></div>
                        <div class="stat-label"><?php esc_html_e('Failed', 'manhwa-scraper'); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="mws-card">
            <h2><?php esc_html_e('Queue Actions', 'manhwa-scraper'); ?></h2>
            <div class="mws-queue-actions">
                <button type="button" class="button button-primary" id="mws-process-queue">
                    <span class="dashicons dashicons-controls-play" style="margin-top: 4px;"></span>
                    <?php esc_html_e('Process Queue', 'manhwa-scraper'); ?>
                </button>
                <button type="button" class="button button-secondary" id="mws-stop-queue" style="display: none;">
                    <span class="dashicons dashicons-controls-pause" style="margin-top: 4px;"></span>
                    <?php esc_html_e('Stop Processing', 'manhwa-scraper'); ?>
                </button>
                <button type="button" class="button" id="mws-retry-failed">
                    <span class="dashicons dashicons-image-rotate" style="margin-top: 4px;"></span>
                    <?php esc_html_e('Retry Failed', 'manhwa-scraper'); ?>
                </button>
                <button type="button" class="button" id="mws-clear-completed">
                    <span class="dashicons dashicons-trash" style="margin-top: 4px;"></span>
                    <?php esc_html_e('Clear Completed', 'manhwa-scraper'); ?>
                </button>
                <button type="button" class="button" id="mws-clear-all" style="color: #d63638;">
                    <span class="dashicons dashicons-dismiss" style="margin-top: 4px;"></span>
                    <?php esc_html_e('Clear All', 'manhwa-scraper'); ?>
                </button>
                <span class="spinner" id="mws-queue-spinner" style="float: none;"></span>
            </div>
            
            <!-- Processing Progress -->
            <div id="mws-queue-progress" style="display: none; margin-top: 15px;">
                <h3><?php esc_html_e('Processing:', 'manhwa-scraper'); ?> <span id="mws-current-item"></span></h3>
                <div style="background: #f0f0f0; border-radius: 4px; height: 25px; overflow: hidden;">
                    <div id="mws-queue-progress-bar" style="background: linear-gradient(90deg, #2271b1, #135e96); height: 100%; width: 0%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: bold;">
                        0%
                    </div>
                </div>
                <p id="mws-queue-progress-text" style="margin-top: 5px; color: #666;"></p>
            </div>
        </div>
        
        <!-- Add to Queue -->
        <div class="mws-card" style="margin-top: 20px;">
            <h2><?php esc_html_e('Add to Queue', 'manhwa-scraper'); ?></h2>
            <div class="mws-row">
                <div class="mws-col-6">
                    <label style="font-weight: 600; display: block; margin-bottom: 5px;">
                        <?php esc_html_e('Select Manhwa:', 'manhwa-scraper'); ?>
                    </label>
                    <select id="mws-queue-manhwa" style="width: 100%; max-width: 400px;">
                        <option value=""><?php esc_html_e('-- Select Manhwa --', 'manhwa-scraper'); ?></option>
                        <?php
                        $manhwa_posts = get_posts([
                            'post_type' => 'manhwa',
                            'posts_per_page' => -1,
                            'orderby' => 'title',
                            'order' => 'ASC'
                        ]);
                        foreach ($manhwa_posts as $manhwa) {
                            $chapters = get_post_meta($manhwa->ID, '_manhwa_chapters', true);
                            $ch_count = is_array($chapters) ? count($chapters) : 0;
                            echo '<option value="' . $manhwa->ID . '">' . esc_html($manhwa->post_title) . ' (' . $ch_count . ' ch)</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="mws-col-6">
                    <label style="font-weight: 600; display: block; margin-bottom: 5px;">
                        <?php esc_html_e('Options:', 'manhwa-scraper'); ?>
                    </label>
                    <label style="display: block; margin-bottom: 5px;">
                        <input type="checkbox" id="mws-queue-skip-existing" checked>
                        <?php esc_html_e('Skip chapters that already have images', 'manhwa-scraper'); ?>
                    </label>
                    <label style="display: block;">
                        <input type="checkbox" id="mws-queue-skip-downloaded" checked>
                        <?php esc_html_e('Skip chapters already downloaded to local', 'manhwa-scraper'); ?>
                    </label>
                </div>
            </div>
            <p style="margin-top: 15px;">
                <button type="button" class="button button-primary" id="mws-add-to-queue">
                    <span class="dashicons dashicons-plus-alt" style="margin-top: 4px;"></span>
                    <?php esc_html_e('Add All Chapters to Queue', 'manhwa-scraper'); ?>
                </button>
            </p>
        </div>
        
        <!-- Queue Table -->
        <div class="mws-card" style="margin-top: 20px;">
            <h2>
                <?php esc_html_e('Queue Items', 'manhwa-scraper'); ?>
                <span style="font-weight: normal; font-size: 14px; color: #666;">
                    (<?php echo $stats['total']; ?> <?php esc_html_e('total', 'manhwa-scraper'); ?>)
                </span>
            </h2>
            
            <div style="margin-bottom: 15px;">
                <label><?php esc_html_e('Filter:', 'manhwa-scraper'); ?></label>
                <select id="mws-queue-filter" style="margin-left: 10px;">
                    <option value=""><?php esc_html_e('All', 'manhwa-scraper'); ?></option>
                    <option value="pending"><?php esc_html_e('Pending', 'manhwa-scraper'); ?></option>
                    <option value="processing"><?php esc_html_e('Processing', 'manhwa-scraper'); ?></option>
                    <option value="completed"><?php esc_html_e('Completed', 'manhwa-scraper'); ?></option>
                    <option value="failed"><?php esc_html_e('Failed', 'manhwa-scraper'); ?></option>
                </select>
                <button type="button" class="button" id="mws-refresh-queue" style="margin-left: 10px;">
                    <span class="dashicons dashicons-update" style="margin-top: 4px;"></span>
                    <?php esc_html_e('Refresh', 'manhwa-scraper'); ?>
                </button>
            </div>
            
            <table class="wp-list-table widefat fixed striped" id="mws-queue-table">
                <thead>
                    <tr>
                        <th style="width: 50px;"><?php esc_html_e('ID', 'manhwa-scraper'); ?></th>
                        <th><?php esc_html_e('Manhwa', 'manhwa-scraper'); ?></th>
                        <th><?php esc_html_e('Chapter', 'manhwa-scraper'); ?></th>
                        <th style="width: 100px;"><?php esc_html_e('Status', 'manhwa-scraper'); ?></th>
                        <th style="width: 100px;"><?php esc_html_e('Images', 'manhwa-scraper'); ?></th>
                        <th style="width: 150px;"><?php esc_html_e('Created', 'manhwa-scraper'); ?></th>
                        <th style="width: 80px;"><?php esc_html_e('Actions', 'manhwa-scraper'); ?></th>
                    </tr>
                </thead>
                <tbody id="mws-queue-body">
                    <?php if (empty($queue_items)): ?>
                    <tr class="no-items">
                        <td colspan="7" style="text-align: center; padding: 20px;">
                            <?php esc_html_e('Queue is empty', 'manhwa-scraper'); ?>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($queue_items as $item): ?>
                    <tr data-id="<?php echo $item->id; ?>">
                        <td><?php echo $item->id; ?></td>
                        <td>
                            <a href="<?php echo get_edit_post_link($item->manhwa_id); ?>" target="_blank">
                                <?php echo esc_html($item->manhwa_title); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html($item->chapter_title ?: $item->chapter_number); ?></td>
                        <td>
                            <span class="mws-status-badge status-<?php echo $item->status; ?>">
                                <?php echo ucfirst($item->status); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($item->images_total > 0): ?>
                                <?php echo $item->images_downloaded; ?>/<?php echo $item->images_total; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?php echo human_time_diff(strtotime($item->created_at), current_time('timestamp')); ?> ago</td>
                        <td>
                            <button type="button" class="button button-small mws-delete-item" data-id="<?php echo $item->id; ?>" title="<?php esc_attr_e('Delete', 'manhwa-scraper'); ?>">
                                <span class="dashicons dashicons-trash" style="margin-top: 3px;"></span>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.mws-stat-card {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.mws-stat-card .stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.mws-stat-card .stat-icon .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
    color: #fff;
}

.mws-stat-card.pending .stat-icon { background: #f0ad4e; }
.mws-stat-card.processing .stat-icon { background: #2271b1; }
.mws-stat-card.completed .stat-icon { background: #46b450; }
.mws-stat-card.failed .stat-icon { background: #dc3232; }

.mws-stat-card .stat-number {
    font-size: 28px;
    font-weight: 700;
    line-height: 1;
}

.mws-stat-card .stat-label {
    color: #666;
    font-size: 13px;
}

.mws-queue-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: center;
}

.mws-status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.mws-status-badge.status-pending { background: #fff3cd; color: #856404; }
.mws-status-badge.status-processing { background: #cce5ff; color: #004085; }
.mws-status-badge.status-completed { background: #d4edda; color: #155724; }
.mws-status-badge.status-failed { background: #f8d7da; color: #721c24; }

.mws-col-3 {
    width: 25%;
    float: left;
    padding: 0 10px;
    box-sizing: border-box;
}

@media (max-width: 782px) {
    .mws-col-3 {
        width: 50%;
        margin-bottom: 15px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    var isProcessing = false;
    var shouldStop = false;
    
    // Refresh queue table
    function refreshQueue() {
        var filter = $('#mws-queue-filter').val();
        
        $.ajax({
            url: mwsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mws_get_queue',
                nonce: mwsData.nonce,
                status: filter
            },
            success: function(response) {
                if (response.success) {
                    updateQueueTable(response.data.items);
                    updateStats(response.data.stats);
                }
            }
        });
    }
    
    function updateQueueTable(items) {
        var $tbody = $('#mws-queue-body');
        $tbody.empty();
        
        if (items.length === 0) {
            $tbody.append('<tr class="no-items"><td colspan="7" style="text-align: center; padding: 20px;"><?php esc_html_e('Queue is empty', 'manhwa-scraper'); ?></td></tr>');
            return;
        }
        
        items.forEach(function(item) {
            var imagesText = item.images_total > 0 ? item.images_downloaded + '/' + item.images_total : '-';
            var editLink = '<?php echo admin_url('post.php?post='); ?>' + item.manhwa_id + '&action=edit';
            
            $tbody.append(
                '<tr data-id="' + item.id + '">' +
                    '<td>' + item.id + '</td>' +
                    '<td><a href="' + editLink + '" target="_blank">' + item.manhwa_title + '</a></td>' +
                    '<td>' + (item.chapter_title || item.chapter_number) + '</td>' +
                    '<td><span class="mws-status-badge status-' + item.status + '">' + item.status.charAt(0).toUpperCase() + item.status.slice(1) + '</span></td>' +
                    '<td>' + imagesText + '</td>' +
                    '<td>' + item.time_ago + '</td>' +
                    '<td><button type="button" class="button button-small mws-delete-item" data-id="' + item.id + '"><span class="dashicons dashicons-trash" style="margin-top: 3px;"></span></button></td>' +
                '</tr>'
            );
        });
    }
    
    function updateStats(stats) {
        $('#stat-pending').text(stats.pending);
        $('#stat-processing').text(stats.processing);
        $('#stat-completed').text(stats.completed);
        $('#stat-failed').text(stats.failed);
    }
    
    // Add to queue
    $('#mws-add-to-queue').on('click', function() {
        var manhwaId = $('#mws-queue-manhwa').val();
        if (!manhwaId) {
            alert('<?php esc_html_e('Please select a manhwa', 'manhwa-scraper'); ?>');
            return;
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true);
        
        $.ajax({
            url: mwsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mws_add_to_queue',
                nonce: mwsData.nonce,
                manhwa_id: manhwaId,
                skip_existing: $('#mws-queue-skip-existing').is(':checked') ? 1 : 0,
                skip_downloaded: $('#mws-queue-skip-downloaded').is(':checked') ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php esc_html_e('Added', 'manhwa-scraper'); ?> ' + response.data.added + ' <?php esc_html_e('chapters to queue', 'manhwa-scraper'); ?>\n<?php esc_html_e('Skipped:', 'manhwa-scraper'); ?> ' + response.data.skipped);
                    refreshQueue();
                } else {
                    alert(response.data.message || 'Error');
                }
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });
    
    // Process queue
    $('#mws-process-queue').on('click', function() {
        if (isProcessing) return;
        
        isProcessing = true;
        shouldStop = false;
        
        $(this).hide();
        $('#mws-stop-queue').show();
        $('#mws-queue-progress').show();
        $('#mws-queue-spinner').addClass('is-active');
        
        processNextItem();
    });
    
    // Stop processing
    $('#mws-stop-queue').on('click', function() {
        shouldStop = true;
        $(this).prop('disabled', true).text('<?php esc_html_e('Stopping...', 'manhwa-scraper'); ?>');
    });
    
    function processNextItem() {
        if (shouldStop) {
            finishProcessing();
            return;
        }
        
        $.ajax({
            url: mwsData.ajaxUrl,
            type: 'POST',
            timeout: 120000, // 2 minutes timeout
            data: {
                action: 'mws_process_queue_item',
                nonce: mwsData.nonce
            },
            success: function(response) {
                if (response.success && response.data.processed) {
                    var item = response.data.item;
                    $('#mws-current-item').text(item.manhwa_title + ' - ' + item.chapter_title);
                    $('#mws-queue-progress-text').html(
                        '<span style="color: green;"><?php esc_html_e('Completed:', 'manhwa-scraper'); ?> ' + 
                        item.images_downloaded + ' <?php esc_html_e('images', 'manhwa-scraper'); ?></span>'
                    );
                    
                    if (response.data.stats) {
                        updateStats(response.data.stats);
                    }
                    
                    // Continue with next item
                    setTimeout(processNextItem, 2000);
                } else if (response.success && !response.data.processed) {
                    // No more items in queue
                    $('#mws-queue-progress-text').html('<span style="color: blue;"><?php esc_html_e('Queue completed!', 'manhwa-scraper'); ?></span>');
                    finishProcessing();
                } else {
                    // Error but continue with next
                    var errMsg = (response.data && response.data.message) ? response.data.message : 'Unknown error';
                    $('#mws-queue-progress-text').html('<span style="color: red;"><?php esc_html_e('Error:', 'manhwa-scraper'); ?> ' + errMsg + '</span>');
                    
                    if (response.data && response.data.stats) {
                        updateStats(response.data.stats);
                    }
                    
                    // Continue to next item after delay
                    setTimeout(processNextItem, 3000);
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', status, error);
                
                if (status === 'timeout') {
                    $('#mws-queue-progress-text').html('<span style="color: orange;"><?php esc_html_e('Timeout - skipping to next...', 'manhwa-scraper'); ?></span>');
                } else {
                    $('#mws-queue-progress-text').html('<span style="color: red;"><?php esc_html_e('Request failed:', 'manhwa-scraper'); ?> ' + (error || status) + '</span>');
                }
                
                // Reset stuck processing item via AJAX
                $.post(mwsData.ajaxUrl, {
                    action: 'mws_reset_stuck_processing',
                    nonce: mwsData.nonce
                });
                
                // Continue to next item
                setTimeout(processNextItem, 5000);
            }
        });
    }
    
    function finishProcessing() {
        isProcessing = false;
        shouldStop = false;
        
        $('#mws-process-queue').show();
        $('#mws-stop-queue').hide().prop('disabled', false).html('<span class="dashicons dashicons-controls-pause" style="margin-top: 4px;"></span> <?php esc_html_e('Stop Processing', 'manhwa-scraper'); ?>');
        $('#mws-queue-spinner').removeClass('is-active');
        
        refreshQueue();
    }
    
    // Retry failed
    $('#mws-retry-failed').on('click', function() {
        if (!confirm('<?php esc_html_e('Retry all failed items?', 'manhwa-scraper'); ?>')) return;
        
        $.ajax({
            url: mwsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mws_retry_failed',
                nonce: mwsData.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php esc_html_e('Retrying', 'manhwa-scraper'); ?> ' + response.data.count + ' <?php esc_html_e('items', 'manhwa-scraper'); ?>');
                    refreshQueue();
                }
            }
        });
    });
    
    // Clear completed
    $('#mws-clear-completed').on('click', function() {
        if (!confirm('<?php esc_html_e('Clear all completed items?', 'manhwa-scraper'); ?>')) return;
        
        $.ajax({
            url: mwsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mws_clear_queue',
                nonce: mwsData.nonce,
                status: 'completed'
            },
            success: function() {
                refreshQueue();
            }
        });
    });
    
    // Clear all
    $('#mws-clear-all').on('click', function() {
        if (!confirm('<?php esc_html_e('Clear ALL queue items? This cannot be undone.', 'manhwa-scraper'); ?>')) return;
        
        $.ajax({
            url: mwsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mws_clear_queue',
                nonce: mwsData.nonce,
                status: ''
            },
            success: function() {
                refreshQueue();
            }
        });
    });
    
    // Delete single item
    $(document).on('click', '.mws-delete-item', function() {
        var id = $(this).data('id');
        
        $.ajax({
            url: mwsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mws_delete_queue_item',
                nonce: mwsData.nonce,
                id: id
            },
            success: function() {
                refreshQueue();
            }
        });
    });
    
    // Filter change
    $('#mws-queue-filter').on('change', refreshQueue);
    
    // Refresh button
    $('#mws-refresh-queue').on('click', refreshQueue);
    
    // Auto-refresh every 10 seconds if processing
    setInterval(function() {
        if (isProcessing) {
            refreshQueue();
        }
    }, 10000);
});
</script>
