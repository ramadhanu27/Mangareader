<?php
/**
 * Bulk Scrape Page View
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

/* ── Design Tokens ── */
.mws-bulk-page {
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
    --purple-dark: #7C3AED;
    --purple-bg: rgba(139,92,246,.06);
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

/* ── Override base card ── */
.mws-bulk-page .mws-card {
    border: 1px solid var(--gray-200) !important;
    border-radius: var(--radius-lg) !important;
    padding: 28px !important;
    box-shadow: var(--shadow-sm) !important;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}
.mws-bulk-page .mws-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--primary), var(--purple));
    opacity: .7;
}
.mws-bulk-page .mws-card:hover {
    box-shadow: var(--shadow) !important;
}
.mws-bulk-page .mws-card h2 {
    font-family: var(--font) !important;
    font-weight: 700 !important;
    font-size: 17px !important;
    color: var(--gray-800) !important;
    margin: 0 0 6px 0 !important;
    padding: 0 !important;
    border: none !important;
    display: flex;
    align-items: center;
    gap: 8px;
}
.mws-bulk-page .mws-card h2 .dashicons {
    color: var(--primary);
    font-size: 20px;
    width: 20px;
    height: 20px;
}
.mws-bulk-page .mws-card > .description {
    color: var(--gray-500) !important;
    font-size: 13px !important;
    margin-bottom: 20px;
}

/* ── Page Title ── */
.mws-bulk-page-title {
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
.mws-bulk-page-title .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
    color: var(--primary);
}

/* ── Form Fields ── */
.mws-field-group {
    margin-bottom: 18px;
}
.mws-field-label {
    display: block;
    font-size: 11.5px !important;
    font-weight: 700 !important;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: var(--gray-400) !important;
    margin-bottom: 6px;
}
.mws-field-group select,
.mws-field-group input[type="number"] {
    font-family: var(--font) !important;
    font-size: 13.5px !important;
    border: 1.5px solid var(--gray-300) !important;
    border-radius: var(--radius-sm) !important;
    padding: 8px 12px !important;
    transition: var(--transition);
    background: #fff !important;
    width: 100% !important;
    max-width: 100% !important;
}
.mws-field-group select:focus,
.mws-field-group input[type="number"]:focus {
    border-color: var(--primary) !important;
    box-shadow: 0 0 0 3px rgba(99,102,241,.12) !important;
    outline: none !important;
}
.mws-field-group input[type="number"].mws-small-input {
    width: 80px !important;
}
.mws-field-hint {
    font-size: 12px;
    color: var(--gray-400);
    margin-top: 4px;
}

/* ── Mode Toggle ── */
.mws-mode-toggle {
    display: flex;
    gap: 0;
    border: 1.5px solid var(--gray-200);
    border-radius: var(--radius-sm);
    overflow: hidden;
    width: fit-content;
}
.mws-mode-toggle label {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    color: var(--gray-500);
    background: var(--gray-50);
    transition: var(--transition);
    border-right: 1px solid var(--gray-200);
}
.mws-mode-toggle label:last-child {
    border-right: none;
}
.mws-mode-toggle input[type="radio"] {
    display: none;
}
.mws-mode-toggle input[type="radio"]:checked + span {
    /* handled via JS classname */
}
.mws-mode-toggle label.mws-mode-active {
    background: var(--primary-bg);
    color: var(--primary-dark);
    font-weight: 600;
}

/* ── Options Checkboxes ── */
.mws-options-group {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 14px 16px;
    background: var(--gray-50);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm);
}
.mws-option-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: var(--gray-600);
    cursor: pointer;
}
.mws-option-item input[type="checkbox"] {
    accent-color: var(--primary);
}
.mws-option-item.mws-option-primary span {
    color: var(--primary-dark);
    font-weight: 600;
}

/* ── Info Tips ── */
.mws-tip-card {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 16px;
    border-radius: var(--radius-sm);
    margin-bottom: 12px;
    font-size: 13px;
    line-height: 1.5;
}
.mws-tip-card:last-child {
    margin-bottom: 0;
}
.mws-tip-icon {
    flex-shrink: 0;
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.mws-tip-icon .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
    color: #fff;
}
.mws-tip-card.tip-info {
    background: rgba(99,102,241,.06);
    border: 1px solid rgba(99,102,241,.12);
}
.mws-tip-card.tip-info .mws-tip-icon { background: var(--primary); }
.mws-tip-card.tip-warning {
    background: var(--warning-bg);
    border: 1px solid rgba(245,158,11,.15);
}
.mws-tip-card.tip-warning .mws-tip-icon { background: var(--warning); }
.mws-tip-card.tip-success {
    background: var(--success-bg);
    border: 1px solid rgba(16,185,129,.15);
}
.mws-tip-card.tip-success .mws-tip-icon { background: var(--success); }
.mws-tip-title {
    font-weight: 700;
    color: var(--gray-800);
    margin-bottom: 2px;
}
.mws-tip-text {
    color: var(--gray-500);
    font-size: 12.5px;
}

/* ── Submit / Action Bar ── */
.mws-submit-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 22px;
    padding-top: 18px;
    border-top: 1px solid var(--gray-100);
}

/* ── Progress ── */
.mws-bulk-page .mws-progress-bar {
    height: 28px !important;
    background: var(--gray-100) !important;
    border-radius: var(--radius-sm) !important;
}
.mws-bulk-page .mws-progress-fill {
    border-radius: var(--radius-sm) !important;
    background: linear-gradient(90deg, var(--primary), var(--primary-light)) !important;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: 12px;
}
.mws-bulk-page .mws-progress-text {
    font-family: var(--font) !important;
    font-size: 13px !important;
    color: var(--gray-500) !important;
}

/* ── Results ── */
.mws-results-action-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 18px;
    padding: 12px 16px;
    background: var(--gray-50);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm);
}
.mws-bulk-page .wp-list-table {
    border-radius: var(--radius-sm) !important;
    overflow: hidden;
}
.mws-bulk-page .wp-list-table thead th {
    font-family: var(--font) !important;
    font-size: 11.5px !important;
    font-weight: 700 !important;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--gray-500) !important;
    background: var(--gray-50) !important;
}
.mws-bulk-page .wp-list-table td {
    font-family: var(--font) !important;
    font-size: 13px !important;
}

/* ── JSON Section ── */
.mws-bulk-page #mws-bulk-json-output {
    font-family: 'Cascadia Code', 'Fira Code', 'JetBrains Mono', monospace !important;
    font-size: 12px !important;
    background: var(--gray-50) !important;
    border: 1px solid var(--gray-200) !important;
    border-radius: var(--radius-sm) !important;
    padding: 14px !important;
    line-height: 1.6 !important;
}

/* ── Chapter Log ── */
.mws-chapter-log-box {
    max-height: 220px;
    overflow-y: auto;
    background: #0f172a;
    padding: 14px;
    border-radius: var(--radius-sm);
    margin-top: 14px;
    font-family: 'Cascadia Code', 'Fira Code', 'JetBrains Mono', monospace;
    font-size: 12px;
    color: #94A3B8;
    line-height: 1.8;
}
.mws-chapter-log-box::-webkit-scrollbar {
    width: 6px;
}
.mws-chapter-log-box::-webkit-scrollbar-track {
    background: transparent;
}
.mws-chapter-log-box::-webkit-scrollbar-thumb {
    background: #334155;
    border-radius: 3px;
}
.mws-chapter-info-row {
    display: flex;
    gap: 24px;
    margin-bottom: 14px;
    padding: 10px 14px;
    background: var(--purple-bg);
    border: 1px solid rgba(139,92,246,.12);
    border-radius: var(--radius-sm);
    font-size: 13px;
}
.mws-chapter-info-row strong {
    color: var(--gray-700);
}

/* ── Buttons ── */
.mws-bulk-page .button {
    font-family: var(--font) !important;
    font-weight: 600 !important;
    font-size: 13px !important;
    border-radius: var(--radius-sm) !important;
    display: inline-flex !important;
    align-items: center;
    gap: 6px;
    transition: var(--transition);
}
.mws-bulk-page .button .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}
.mws-bulk-page .button-primary {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark)) !important;
    border-color: var(--primary-dark) !important;
    box-shadow: 0 2px 4px rgba(99,102,241,.25) !important;
}
.mws-bulk-page .button-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(99,102,241,.3) !important;
}

/* ── Grid Layout ── */
.mws-bulk-page .mws-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

/* ── Responsive ── */
@media (max-width: 960px) {
    .mws-bulk-page .mws-row {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 782px) {
    .mws-bulk-page .mws-card {
        padding: 18px !important;
    }
    .mws-results-action-bar {
        flex-direction: column;
        align-items: flex-start;
    }
    .mws-chapter-info-row {
        flex-direction: column;
        gap: 8px;
    }
}
</style>

<div class="wrap mws-wrap">
    <h1 class="mws-bulk-page-title">
        <span class="dashicons dashicons-download"></span>
        <?php esc_html_e('Bulk Scrape', 'manhwa-scraper'); ?>
    </h1>
    
    <div class="mws-bulk-page">
        <div class="mws-row">
            <!-- ── Scrape Settings ── -->
            <div>
                <div class="mws-card">
                    <h2>
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php esc_html_e('Scrape Settings', 'manhwa-scraper'); ?>
                    </h2>
                    <p class="description">
                        <?php esc_html_e('Scrape multiple manhwa from a source list page.', 'manhwa-scraper'); ?>
                    </p>
                    
                    <form id="mws-bulk-form">
                        <!-- Source -->
                        <div class="mws-field-group">
                            <label class="mws-field-label" for="mws-source"><?php esc_html_e('Source', 'manhwa-scraper'); ?></label>
                            <select id="mws-source" name="source" required>
                                <option value=""><?php esc_html_e('Select a source...', 'manhwa-scraper'); ?></option>
                                <?php foreach ($sources as $source): ?>
                                <option value="<?php echo esc_attr($source['id']); ?>">
                                    <?php echo esc_html($source['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Scrape Mode -->
                        <div class="mws-field-group">
                            <label class="mws-field-label"><?php esc_html_e('Mode', 'manhwa-scraper'); ?></label>
                            <div class="mws-mode-toggle">
                                <label class="mws-mode-active" data-mode="range">
                                    <input type="radio" name="scrape_mode" value="range" checked>
                                    <span><?php esc_html_e('Page Range', 'manhwa-scraper'); ?></span>
                                </label>
                                <label data-mode="single">
                                    <input type="radio" name="scrape_mode" value="single">
                                    <span><?php esc_html_e('Single Page', 'manhwa-scraper'); ?></span>
                                </label>
                            </div>
                        </div>

                        <!-- Page Number -->
                        <div class="mws-field-group" id="mws-page-number-row">
                            <label class="mws-field-label" for="mws-start-page"><?php esc_html_e('Page Number', 'manhwa-scraper'); ?></label>
                            <input type="number" id="mws-start-page" name="start_page" value="1" min="1" max="500" class="mws-small-input">
                            <p class="mws-field-hint" id="mws-page-desc-range">
                                <?php esc_html_e('Start from this page number', 'manhwa-scraper'); ?>
                            </p>
                            <p class="mws-field-hint" id="mws-page-desc-single" style="display: none;">
                                <?php esc_html_e('Only scrape this specific page', 'manhwa-scraper'); ?>
                            </p>
                        </div>

                        <!-- Pages Count -->
                        <div class="mws-field-group" id="mws-pages-count-row">
                            <label class="mws-field-label" for="mws-pages"><?php esc_html_e('Pages to Scrape', 'manhwa-scraper'); ?></label>
                            <input type="number" id="mws-pages" name="pages" value="1" min="1" max="50" class="mws-small-input">
                            <p class="mws-field-hint"><?php esc_html_e('Number of pages from start page (max 50)', 'manhwa-scraper'); ?></p>
                        </div>

                        <!-- Options -->
                        <div class="mws-field-group">
                            <label class="mws-field-label"><?php esc_html_e('Options', 'manhwa-scraper'); ?></label>
                            <div class="mws-options-group">
                                <label class="mws-option-item">
                                    <input type="checkbox" id="mws-scrape-details" name="scrape_details" value="1">
                                    <span><?php esc_html_e('Scrape full details (slower)', 'manhwa-scraper'); ?></span>
                                </label>
                                <label class="mws-option-item mws-option-primary">
                                    <input type="checkbox" id="mws-scrape-chapters" name="scrape_chapters" value="1">
                                    <span><?php esc_html_e('Also scrape all chapters', 'manhwa-scraper'); ?></span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="mws-submit-bar">
                            <button type="submit" class="button button-primary" id="mws-bulk-scrape-btn">
                                <span class="dashicons dashicons-download"></span>
                                <?php esc_html_e('Start Bulk Scrape', 'manhwa-scraper'); ?>
                            </button>
                            <span class="spinner" id="mws-bulk-spinner"></span>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- ── Info Panel ── -->
            <div>
                <div class="mws-card">
                    <h2>
                        <span class="dashicons dashicons-lightbulb"></span>
                        <?php esc_html_e('Tips', 'manhwa-scraper'); ?>
                    </h2>
                    
                    <div class="mws-tip-card tip-info">
                        <div class="mws-tip-icon"><span class="dashicons dashicons-performance"></span></div>
                        <div>
                            <div class="mws-tip-title"><?php esc_html_e('Parallel Scraping', 'manhwa-scraper'); ?></div>
                            <div class="mws-tip-text"><?php esc_html_e('Uses 5 parallel requests for faster scraping when "Scrape full details" is enabled.', 'manhwa-scraper'); ?></div>
                        </div>
                    </div>
                    
                    <div class="mws-tip-card tip-warning">
                        <div class="mws-tip-icon"><span class="dashicons dashicons-info-outline"></span></div>
                        <div>
                            <div class="mws-tip-title"><?php esc_html_e('Full Details Mode', 'manhwa-scraper'); ?></div>
                            <div class="mws-tip-text"><?php esc_html_e('Gets description, genres, chapters, and rating for each manhwa.', 'manhwa-scraper'); ?></div>
                        </div>
                    </div>
                    
                    <div class="mws-tip-card tip-success">
                        <div class="mws-tip-icon"><span class="dashicons dashicons-clock"></span></div>
                        <div>
                            <div class="mws-tip-title"><?php esc_html_e('Speed Comparison', 'manhwa-scraper'); ?></div>
                            <div class="mws-tip-text">
                                <?php esc_html_e('Sequential: ~30s/manhwa', 'manhwa-scraper'); ?><br>
                                <?php esc_html_e('Parallel (5×): ~6s/manhwa', 'manhwa-scraper'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ── Progress ── -->
        <div class="mws-card" id="mws-progress-section" style="display: none;">
            <h2>
                <span class="dashicons dashicons-update"></span>
                <?php esc_html_e('Progress', 'manhwa-scraper'); ?>
            </h2>
            <div class="mws-progress-bar">
                <div class="mws-progress-fill" id="mws-progress-fill" style="width: 0%"></div>
            </div>
            <p class="mws-progress-text" id="mws-progress-text">
                <?php esc_html_e('Starting...', 'manhwa-scraper'); ?>
            </p>
        </div>
        
        <!-- ── Results ── -->
        <div class="mws-card" id="mws-bulk-results" style="display: none;">
            <h2>
                <span class="dashicons dashicons-list-view"></span>
                <?php esc_html_e('Results', 'manhwa-scraper'); ?>
            </h2>
            
            <div class="mws-results-summary" id="mws-results-summary"></div>
            
            <div class="mws-results-action-bar">
                <button type="button" class="button button-primary" id="mws-export-all-json">
                    <span class="dashicons dashicons-download"></span>
                    <?php esc_html_e('Export JSON', 'manhwa-scraper'); ?>
                </button>
                <button type="button" class="button" id="mws-import-selected">
                    <span class="dashicons dashicons-upload"></span>
                    <?php esc_html_e('Import Selected', 'manhwa-scraper'); ?>
                </button>
                <button type="button" class="button" id="mws-scrape-all-chapters-btn" style="background: #8b5cf6; border-color: #7c3aed; color: #fff;">
                    <span class="dashicons dashicons-images-alt2"></span>
                    <?php esc_html_e('Scrape Chapters', 'manhwa-scraper'); ?>
                </button>
                <label class="mws-option-item" style="margin-left: auto;">
                    <input type="checkbox" id="mws-select-all-results">
                    <span><?php esc_html_e('Select All', 'manhwa-scraper'); ?></span>
                </label>
            </div>
            
            <table class="wp-list-table widefat fixed striped" id="mws-results-table">
                <thead>
                    <tr>
                        <th class="check-column"><input type="checkbox" id="mws-check-all"></th>
                        <th><?php esc_html_e('Cover', 'manhwa-scraper'); ?></th>
                        <th><?php esc_html_e('Title', 'manhwa-scraper'); ?></th>
                        <th><?php esc_html_e('Type', 'manhwa-scraper'); ?></th>
                        <th><?php esc_html_e('Chapters', 'manhwa-scraper'); ?></th>
                        <th><?php esc_html_e('Status', 'manhwa-scraper'); ?></th>
                        <th><?php esc_html_e('Actions', 'manhwa-scraper'); ?></th>
                    </tr>
                </thead>
                <tbody id="mws-results-body"></tbody>
            </table>
        </div>
        
        <!-- ── JSON Export ── -->
        <div class="mws-card" id="mws-bulk-json-section" style="display: none;">
            <h2>
                <span class="dashicons dashicons-editor-code"></span>
                <?php esc_html_e('JSON Export', 'manhwa-scraper'); ?>
                <button type="button" class="button button-small" id="mws-copy-bulk-json" style="margin-left: auto;">
                    <span class="dashicons dashicons-clipboard"></span>
                    <?php esc_html_e('Copy', 'manhwa-scraper'); ?>
                </button>
            </h2>
            <textarea id="mws-bulk-json-output" class="large-text code" rows="15" readonly></textarea>
        </div>
        
        <!-- ── Chapter Scraping Progress ── -->
        <div class="mws-card" id="mws-chapter-progress-section" style="display: none;">
            <h2>
                <span class="dashicons dashicons-images-alt2" style="color: var(--purple);"></span>
                <?php esc_html_e('Scraping Chapters', 'manhwa-scraper'); ?>
            </h2>

            <div class="mws-chapter-info-row">
                <div><strong><?php esc_html_e('Manhwa:', 'manhwa-scraper'); ?></strong> <span id="mws-chapter-current-manhwa">-</span></div>
                <div><strong><?php esc_html_e('Chapter:', 'manhwa-scraper'); ?></strong> <span id="mws-chapter-current-chapter">-</span></div>
            </div>

            <div class="mws-progress-bar">
                <div class="mws-progress-fill" id="mws-chapter-progress-fill" style="width: 0%; background: linear-gradient(90deg, var(--purple), #a78bfa) !important;"></div>
            </div>
            <p class="mws-progress-text" id="mws-chapter-progress-text">
                <?php esc_html_e('Preparing...', 'manhwa-scraper'); ?>
            </p>

            <div id="mws-chapter-log" class="mws-chapter-log-box">
                <!-- Log entries will appear here -->
            </div>
        </div>
    </div>
</div>

<script>
// Store bulk scraped data globally
var mwsBulkData = [];

// Scrape Mode Toggle
jQuery(document).ready(function($) {
    // Handle mode toggle
    $('input[name="scrape_mode"]').on('change', function() {
        var mode = $(this).val();
        
        // Update toggle button active state
        $('.mws-mode-toggle label').removeClass('mws-mode-active');
        $(this).closest('label').addClass('mws-mode-active');
        
        if (mode === 'single') {
            // Single page mode
            $('#mws-pages-count-row').hide();
            $('#mws-page-desc-range').hide();
            $('#mws-page-desc-single').show();
            $('#mws-page-number-row .mws-field-label').text('<?php esc_html_e('Page Number', 'manhwa-scraper'); ?>');
            // Set pages to 1 for single mode
            $('#mws-pages').val(1);
        } else {
            // Range mode
            $('#mws-pages-count-row').show();
            $('#mws-page-desc-range').show();
            $('#mws-page-desc-single').hide();
            $('#mws-page-number-row .mws-field-label').text('<?php esc_html_e('Start Page', 'manhwa-scraper'); ?>');
        }
    });
    
    // Initialize on page load
    $('input[name="scrape_mode"]:checked').trigger('change');
    
    // Scrape All Chapters Handler
    $('#mws-scrape-all-chapters-btn').on('click', function() {
        // Get selected manhwa
        var selectedRows = $('#mws-results-body tr').filter(function() {
            return $(this).find('input[type="checkbox"]:checked').length > 0;
        });
        
        if (selectedRows.length === 0) {
            alert('<?php esc_html_e('Please select at least one manhwa to scrape chapters.', 'manhwa-scraper'); ?>');
            return;
        }
        
        // Collect manhwa data with chapters
        var manhwaToScrape = [];
        selectedRows.each(function() {
            var index = $(this).data('index');
            if (typeof mwsBulkData[index] !== 'undefined' && mwsBulkData[index].chapters) {
                manhwaToScrape.push({
                    index: index,
                    title: mwsBulkData[index].title,
                    chapters: mwsBulkData[index].chapters
                });
            }
        });
        
        if (manhwaToScrape.length === 0) {
            alert('<?php esc_html_e('No chapters found for selected manhwa. Make sure to enable "Scrape full details" when scraping.', 'manhwa-scraper'); ?>');
            return;
        }
        
        // Show progress section
        $('#mws-chapter-progress-section').show();
        $('#mws-chapter-log').html('');
        
        // Calculate total chapters
        var totalChapters = 0;
        manhwaToScrape.forEach(function(m) {
            totalChapters += m.chapters.length;
        });
        
        addChapterLog('Starting bulk chapter scrape...', 'info');
        addChapterLog('Total manhwa: ' + manhwaToScrape.length + ', Total chapters: ' + totalChapters, 'info');
        
        var processedChapters = 0;
        var currentManhwaIndex = 0;
        
        function processManhwa(mIndex) {
            if (mIndex >= manhwaToScrape.length) {
                // All done
                $('#mws-chapter-progress-text').text('<?php esc_html_e('Completed!', 'manhwa-scraper'); ?>');
                $('#mws-chapter-progress-fill').css('width', '100%');
                addChapterLog('All chapters scraped successfully!', 'success');
                return;
            }
            
            var manhwa = manhwaToScrape[mIndex];
            $('#mws-chapter-current-manhwa').text(manhwa.title + ' (' + (mIndex + 1) + '/' + manhwaToScrape.length + ')');
            addChapterLog('Processing: ' + manhwa.title + ' (' + manhwa.chapters.length + ' chapters)', 'info');
            
            processChapters(manhwa, 0, function() {
                // Move to next manhwa
                processManhwa(mIndex + 1);
            });
        }
        
        function processChapters(manhwa, cIndex, callback) {
            if (cIndex >= manhwa.chapters.length) {
                callback();
                return;
            }
            
            var chapter = manhwa.chapters[cIndex];
            var chapterTitle = chapter.title || ('Chapter ' + (cIndex + 1));
            var chapterUrl = chapter.url;
            
            $('#mws-chapter-current-chapter').text(chapterTitle + ' (' + (cIndex + 1) + '/' + manhwa.chapters.length + ')');
            
            // Make AJAX call to scrape chapter images
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mws_scrape_chapter_images',
                    nonce: mwsAdmin.nonce,
                    url: chapterUrl
                },
                success: function(response) {
                    processedChapters++;
                    var pct = Math.round(processedChapters / totalChapters * 100);
                    $('#mws-chapter-progress-fill').css('width', pct + '%');
                    $('#mws-chapter-progress-text').text(processedChapters + ' / ' + totalChapters + ' chapters processed (' + pct + '%)');
                    
                    if (response.success && response.data && response.data.data) {
                        var imgData = response.data.data;
                        var imgCount = imgData.images ? imgData.images.length : 0;
                        addChapterLog('✓ ' + chapterTitle + ' - ' + imgCount + ' images', 'success');
                        
                        // Store images in chapters data
                        if (mwsBulkData[manhwa.index] && mwsBulkData[manhwa.index].chapters[cIndex]) {
                            mwsBulkData[manhwa.index].chapters[cIndex].images = imgData.images;
                        }
                    } else {
                        addChapterLog('✗ ' + chapterTitle + ' - ' + (response.data ? response.data.message : 'Failed'), 'error');
                    }
                    
                    // Small delay before next chapter to avoid rate limiting
                    setTimeout(function() {
                        processChapters(manhwa, cIndex + 1, callback);
                    }, 500);
                },
                error: function() {
                    processedChapters++;
                    addChapterLog('✗ ' + chapterTitle + ' - Request failed', 'error');
                    
                    setTimeout(function() {
                        processChapters(manhwa, cIndex + 1, callback);
                    }, 500);
                }
            });
        }
        
        function addChapterLog(message, type) {
            var color = '#888';
            if (type === 'success') color = '#10b981';
            else if (type === 'error') color = '#ef4444';
            else if (type === 'info') color = '#8b5cf6';
            
            var time = new Date().toLocaleTimeString();
            $('#mws-chapter-log').append('<div style="color: ' + color + ';">[' + time + '] ' + message + '</div>');
            $('#mws-chapter-log').scrollTop($('#mws-chapter-log')[0].scrollHeight);
        }
        
        // Start processing
        processManhwa(0);
    });
    
    // Enable "Scrape chapters" checkbox only when "Scrape details" is checked
    $('#mws-scrape-details').on('change', function() {
        if ($(this).is(':checked')) {
            $('#mws-scrape-chapters').prop('disabled', false);
        } else {
            $('#mws-scrape-chapters').prop('disabled', true).prop('checked', false);
        }
    });
    $('#mws-scrape-details').trigger('change');
});
</script>
