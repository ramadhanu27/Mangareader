<?php
/**
 * Import Page View
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap mws-wrap">
    <h1 class="mws-title">
        <span class="dashicons dashicons-upload"></span>
        <?php esc_html_e('Import Single Manhwa', 'manhwa-scraper'); ?>
    </h1>
    
    <div class="mws-import-page">
        <div class="mws-row">
            <!-- Import Form -->
            <div class="mws-col-6">
                <div class="mws-card">
                    <h2><?php esc_html_e('Scrape from URL', 'manhwa-scraper'); ?></h2>
                    <p class="description">
                        <?php esc_html_e('Enter a manhwa URL to scrape its metadata.', 'manhwa-scraper'); ?>
                    </p>
                    
                    <!-- Mode Toggle -->
                    <div style="margin-bottom: 20px;">
                        <button type="button" class="button" id="mws-toggle-mode" style="margin-bottom: 10px;">
                            <span class="dashicons dashicons-list-view" style="margin-top: 4px;"></span>
                            <?php esc_html_e('Switch to Bulk Mode', 'manhwa-scraper'); ?>
                        </button>
                        <p class="description" id="mws-mode-description">
                            <?php esc_html_e('Currently in Single Mode. Switch to Bulk Mode to scrape multiple manhwa at once.', 'manhwa-scraper'); ?>
                        </p>
                    </div>
                    
                    <form id="mws-import-form">
                        <!-- Single Mode -->
                        <div id="mws-single-mode">
                            <table class="form-table">
                                <tr>
                                    <th><label for="mws-url"><?php esc_html_e('Manhwa URL', 'manhwa-scraper'); ?></label></th>
                                    <td>
                                        <input type="url" id="mws-url" name="url" class="large-text" 
                                               placeholder="https://manhwaku.id/manga/solo-leveling/">
                                        <p class="description">
                                            <?php esc_html_e('Supported sources:', 'manhwa-scraper'); ?>
                                            <?php 
                                            $source_names = array_map(function($s) { return $s['name']; }, $sources);
                                            echo esc_html(implode(', ', $source_names)); 
                                            ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <!-- Bulk Mode -->
                        <div id="mws-bulk-mode" style="display: none;">
                            <table class="form-table">
                                <tr>
                                    <th><label for="mws-urls-bulk"><?php esc_html_e('Manhwa URLs', 'manhwa-scraper'); ?></label></th>
                                    <td>
                                        <textarea id="mws-urls-bulk" name="urls_bulk" class="large-text code" rows="10" 
                                                  placeholder="https://manhwaku.id/manga/solo-leveling/&#10;https://manhwaku.id/manga/the-beginning-after-the-end/&#10;https://manhwaku.id/manga/omniscient-readers-viewpoint/"></textarea>
                                        <p class="description">
                                            <?php esc_html_e('Enter one URL per line. You can paste multiple URLs at once.', 'manhwa-scraper'); ?>
                                            <br>
                                            <strong><?php esc_html_e('Supported sources:', 'manhwa-scraper'); ?></strong>
                                            <?php 
                                            $source_names = array_map(function($s) { return $s['name']; }, $sources);
                                            echo esc_html(implode(', ', $source_names)); 
                                            ?>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="mws-delay"><?php esc_html_e('Delay Between Requests', 'manhwa-scraper'); ?></label></th>
                                    <td>
                                        <input type="number" id="mws-delay" name="delay" value="2000" min="500" max="10000" step="500" style="width: 100px;">
                                        <span class="description"><?php esc_html_e('milliseconds (recommended: 2000-3000ms to avoid blocking)', 'manhwa-scraper'); ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary" id="mws-scrape-btn">
                                <span class="dashicons dashicons-search" style="margin-top: 4px;"></span>
                                <span id="mws-btn-text"><?php esc_html_e('Scrape Metadata', 'manhwa-scraper'); ?></span>
                            </button>
                            <span class="spinner" id="mws-spinner"></span>
                            <span id="mws-progress" style="margin-left: 10px; display: none;"></span>
                        </p>
                    </form>
                </div>
            </div>
            
            <!-- Bulk Results Section -->
            <div class="mws-col-12" id="mws-bulk-results-section" style="display: none;">
                <div class="mws-card">
                    <h2><?php esc_html_e('Bulk Scraping Results', 'manhwa-scraper'); ?></h2>
                    <div id="mws-bulk-summary" style="margin-bottom: 15px; padding: 10px; background: #f0f0f1; border-left: 4px solid #2271b1; display: none;">
                        <strong><?php esc_html_e('Summary:', 'manhwa-scraper'); ?></strong>
                        <span id="mws-bulk-summary-text"></span>
                    </div>
                    <div id="mws-bulk-results-list" class="mws-bulk-list">
                        <!-- Results will be populated here -->
                    </div>
                    <div class="mws-import-options" id="mws-bulk-import-options" style="display: none; margin-top: 20px;">
                        <h4><?php esc_html_e('Bulk Import Options', 'manhwa-scraper'); ?></h4>
                        <label>
                            <input type="checkbox" id="mws-bulk-download-cover" checked>
                            <?php esc_html_e('Download all cover images to Media Library', 'manhwa-scraper'); ?>
                        </label>
                        <br>
                        <label>
                            <input type="checkbox" id="mws-bulk-create-post" <?php echo post_type_exists('manhwa') ? 'checked' : 'disabled'; ?>>
                            <?php esc_html_e('Create Manhwa posts for all (requires Manhwa Manager)', 'manhwa-scraper'); ?>
                            <?php if (!post_type_exists('manhwa')): ?>
                                <span style="color: #dc3232; font-size: 12px;"><?php esc_html_e('(Manhwa Manager not active)', 'manhwa-scraper'); ?></span>
                            <?php endif; ?>
                        </label>
                    </div>
                    <div class="mws-import-actions" id="mws-bulk-import-actions" style="display: none; margin-top: 15px;">
                        <button type="button" class="button button-primary" id="mws-bulk-import-btn">
                            <span class="dashicons dashicons-download" style="margin-top: 4px;"></span>
                            <?php esc_html_e('Import All Manhwa', 'manhwa-scraper'); ?>
                            <span id="mws-bulk-import-count"></span>
                        </button>
                        <button type="button" class="button" id="mws-bulk-export-json-btn">
                            <span class="dashicons dashicons-download" style="margin-top: 4px;"></span>
                            <?php esc_html_e('Export All as JSON', 'manhwa-scraper'); ?>
                        </button>
                        <span class="spinner" id="mws-bulk-import-spinner"></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mws-row">
            <!-- Supported Sources -->
            <div class="mws-col-6">
                <div class="mws-card">
                    <h2><?php esc_html_e('Supported Sources', 'manhwa-scraper'); ?></h2>
                    <ul class="mws-source-list-simple">
                        <?php foreach ($sources as $source): ?>
                        <li>
                            <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                            <strong><?php echo esc_html($source['name']); ?></strong>
                            <br>
                            <code><?php echo esc_html($source['url']); ?></code>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

            </div>
        </div>
        
        <!-- Preview Section -->
        <div class="mws-card" id="mws-preview-section" style="display: none;">
            <h2><?php esc_html_e('Preview', 'manhwa-scraper'); ?></h2>
            
            <div class="mws-preview-content">
                <div class="mws-preview-header">
                    <div class="mws-preview-cover">
                        <img id="mws-preview-image" src="" alt="">
                    </div>
                    <div class="mws-preview-info">
                        <h3 id="mws-preview-title"></h3>
                        <p id="mws-preview-alt-title" class="mws-alt-title" style="color: #646970; font-size: 13px; margin-top: -10px;"></p>
                        <div class="mws-preview-meta">
                            <span class="mws-badge" id="mws-preview-status"></span>
                            <span class="mws-badge secondary" id="mws-preview-type"></span>
                            <span id="mws-preview-chapters"></span>
                            <span id="mws-preview-rating" style="color: #ffb900;"></span>
                        </div>
                        <div class="mws-preview-genres" id="mws-preview-genres"></div>
                        
                        <!-- Detailed Info Table -->
                        <table class="mws-info-table" style="margin-top: 15px; font-size: 13px;">
                            <tr id="mws-row-author" style="display: none;">
                                <td style="padding: 3px 10px 3px 0; font-weight: 600;"><?php esc_html_e('Author', 'manhwa-scraper'); ?></td>
                                <td id="mws-preview-author"></td>
                            </tr>
                            <tr id="mws-row-artist" style="display: none;">
                                <td style="padding: 3px 10px 3px 0; font-weight: 600;"><?php esc_html_e('Artist', 'manhwa-scraper'); ?></td>
                                <td id="mws-preview-artist"></td>
                            </tr>
                            <tr id="mws-row-year" style="display: none;">
                                <td style="padding: 3px 10px 3px 0; font-weight: 600;"><?php esc_html_e('Released', 'manhwa-scraper'); ?></td>
                                <td id="mws-preview-year"></td>
                            </tr>
                            <tr id="mws-row-views" style="display: none;">
                                <td style="padding: 3px 10px 3px 0; font-weight: 600;"><?php esc_html_e('Views', 'manhwa-scraper'); ?></td>
                                <td id="mws-preview-views"></td>
                            </tr>
                            <tr id="mws-row-updated" style="display: none;">
                                <td style="padding: 3px 10px 3px 0; font-weight: 600;"><?php esc_html_e('Updated', 'manhwa-scraper'); ?></td>
                                <td id="mws-preview-updated"></td>
                            </tr>
                        </table>
                        
                        <p class="mws-preview-description" id="mws-preview-description" style="margin-top: 15px;"></p>
                    </div>
                </div>
                
                <!-- Chapters List -->
                <div class="mws-chapters-preview">
                    <h4><?php esc_html_e('Chapters', 'manhwa-scraper'); ?> (<span id="mws-chapters-count">0</span>)</h4>
                    <div class="mws-chapters-list" id="mws-chapters-list"></div>
                </div>
            </div>
            
            <!-- Import Options -->
            <div class="mws-import-options">
                <h4><?php esc_html_e('Import Options', 'manhwa-scraper'); ?></h4>
                <label>
                    <input type="checkbox" id="mws-download-cover" checked>
                    <?php esc_html_e('Download cover image to Media Library', 'manhwa-scraper'); ?>
                </label>
                <br>
                <label>
                    <input type="checkbox" id="mws-create-post" <?php echo post_type_exists('manhwa') ? 'checked' : 'disabled'; ?>>
                    <?php esc_html_e('Create Manhwa post (requires Manhwa Manager)', 'manhwa-scraper'); ?>
                    <?php if (!post_type_exists('manhwa')): ?>
                        <span style="color: #dc3232; font-size: 12px;"><?php esc_html_e('(Manhwa Manager not active)', 'manhwa-scraper'); ?></span>
                    <?php endif; ?>
                </label>
                <br>
                <label>
                    <input type="checkbox" id="mws-track-updates">
                    <?php esc_html_e('Track for automatic updates', 'manhwa-scraper'); ?>
                </label>
            </div>
            
            <div class="mws-import-actions">
                <button type="button" class="button button-primary" id="mws-import-btn">
                    <span class="dashicons dashicons-download" style="margin-top: 4px;"></span>
                    <?php esc_html_e('Import Manhwa', 'manhwa-scraper'); ?>
                </button>
                <button type="button" class="button" id="mws-export-json-btn">
                    <span class="dashicons dashicons-download" style="margin-top: 4px;"></span>
                    <?php esc_html_e('Export JSON', 'manhwa-scraper'); ?>
                </button>
                <button type="button" class="button" id="mws-copy-json-btn">
                    <span class="dashicons dashicons-clipboard" style="margin-top: 4px;"></span>
                    <?php esc_html_e('Copy JSON', 'manhwa-scraper'); ?>
                </button>
                <span class="spinner" id="mws-import-spinner"></span>
            </div>
        </div>
        
        <!-- JSON Preview -->
        <div class="mws-card" id="mws-json-section" style="display: none;">
            <h2><?php esc_html_e('JSON Data', 'manhwa-scraper'); ?></h2>
            <textarea id="mws-json-output" class="large-text code" rows="15" readonly></textarea>
        </div>
        
        <!-- Import Result -->
        <div class="mws-card" id="mws-result-section" style="display: none;">
            <h2><?php esc_html_e('Import Result', 'manhwa-scraper'); ?></h2>
            <div id="mws-result-content"></div>
        </div>
    </div>
</div>

<script>
// Store scraped data globally
var mwsScrapedData = null;
</script>
