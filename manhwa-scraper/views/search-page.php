<?php
/**
 * Search Page View
 * Search manhwa directly from sources
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap mws-wrap">
    <h1 class="mws-title">
        <span class="dashicons dashicons-search"></span>
        <?php esc_html_e('Search from Sources', 'manhwa-scraper'); ?>
    </h1>
    
    <div class="mws-search-page">
        <!-- Quick Browse Tabs -->
        <div class="mws-browse-tabs">
            <button type="button" class="mws-tab-btn active" data-tab="search">
                <span class="dashicons dashicons-search"></span>
                <?php esc_html_e('Search', 'manhwa-scraper'); ?>
            </button>
            <button type="button" class="mws-tab-btn" data-tab="popular">
                <span class="dashicons dashicons-star-filled"></span>
                <?php esc_html_e('Popular', 'manhwa-scraper'); ?>
            </button>
            <button type="button" class="mws-tab-btn" data-tab="latest">
                <span class="dashicons dashicons-clock"></span>
                <?php esc_html_e('Latest Update', 'manhwa-scraper'); ?>
            </button>
        </div>
        
        <!-- Search Tab Content -->
        <div class="mws-tab-content active" id="mws-tab-search">
            <!-- Search Form -->
            <div class="mws-card mws-search-card">
                <form id="mws-search-form" class="mws-search-form">
                    <div class="mws-search-row">
                        <div class="mws-search-input-wrap">
                            <input type="text" 
                                   id="mws-search-keyword" 
                                   name="keyword" 
                                   placeholder="<?php esc_attr_e('Enter title or leave empty to browse with filters...', 'manhwa-scraper'); ?>" 
                                   class="mws-search-input"
                                   autocomplete="off">
                        </div>
                        <select id="mws-search-source" name="source" class="mws-search-source">
                            <option value="all"><?php esc_html_e('All Sources', 'manhwa-scraper'); ?></option>
                            <?php foreach ($sources as $source): ?>
                            <option value="<?php echo esc_attr($source['id']); ?>">
                                <?php echo esc_html($source['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="button button-primary mws-search-btn" id="mws-search-btn">
                            <span class="dashicons dashicons-search" style="margin-top: 4px;"></span>
                            <?php esc_html_e('Search', 'manhwa-scraper'); ?>
                        </button>
                    </div>
                    
                    <!-- Advanced Filters Toggle -->
                    <div class="mws-advanced-toggle">
                        <button type="button" id="mws-toggle-filters" class="mws-filter-toggle-btn">
                            <span class="dashicons dashicons-filter"></span>
                            <?php esc_html_e('Advanced Filters', 'manhwa-scraper'); ?>
                            <span class="dashicons dashicons-arrow-down-alt2 mws-toggle-arrow"></span>
                        </button>
                    </div>
                    
                    <!-- Advanced Filters Panel -->
                    <div class="mws-advanced-filters" id="mws-advanced-filters" style="display: none;">
                        <div class="mws-filters-grid">
                            <!-- Type Filter -->
                            <div class="mws-filter-group">
                                <label class="mws-filter-label">
                                    <span class="dashicons dashicons-book-alt"></span>
                                    <?php esc_html_e('Type', 'manhwa-scraper'); ?>
                                </label>
                                <select id="mws-filter-type" name="type" class="mws-filter-select">
                                    <option value=""><?php esc_html_e('All Types', 'manhwa-scraper'); ?></option>
                                    <option value="manhwa"><?php esc_html_e('Manhwa (Korean)', 'manhwa-scraper'); ?></option>
                                    <option value="manga"><?php esc_html_e('Manga (Japanese)', 'manhwa-scraper'); ?></option>
                                    <option value="manhua"><?php esc_html_e('Manhua (Chinese)', 'manhwa-scraper'); ?></option>
                                </select>
                            </div>
                            
                            <!-- Status Filter -->
                            <div class="mws-filter-group">
                                <label class="mws-filter-label">
                                    <span class="dashicons dashicons-flag"></span>
                                    <?php esc_html_e('Status', 'manhwa-scraper'); ?>
                                </label>
                                <select id="mws-filter-status" name="status" class="mws-filter-select">
                                    <option value=""><?php esc_html_e('All Status', 'manhwa-scraper'); ?></option>
                                    <option value="ongoing"><?php esc_html_e('Ongoing', 'manhwa-scraper'); ?></option>
                                    <option value="completed"><?php esc_html_e('Completed', 'manhwa-scraper'); ?></option>
                                    <option value="hiatus"><?php esc_html_e('Hiatus', 'manhwa-scraper'); ?></option>
                                </select>
                            </div>
                            
                            <!-- Genre Filter -->
                            <div class="mws-filter-group mws-filter-genre-group">
                                <label class="mws-filter-label">
                                    <span class="dashicons dashicons-tag"></span>
                                    <?php esc_html_e('Genre', 'manhwa-scraper'); ?>
                                </label>
                                <select id="mws-filter-genre" name="genre" class="mws-filter-select">
                                    <option value=""><?php esc_html_e('All Genres', 'manhwa-scraper'); ?></option>
                                    <option value="action"><?php esc_html_e('Action', 'manhwa-scraper'); ?></option>
                                    <option value="adventure"><?php esc_html_e('Adventure', 'manhwa-scraper'); ?></option>
                                    <option value="comedy"><?php esc_html_e('Comedy', 'manhwa-scraper'); ?></option>
                                    <option value="drama"><?php esc_html_e('Drama', 'manhwa-scraper'); ?></option>
                                    <option value="fantasy"><?php esc_html_e('Fantasy', 'manhwa-scraper'); ?></option>
                                    <option value="harem"><?php esc_html_e('Harem', 'manhwa-scraper'); ?></option>
                                    <option value="horror"><?php esc_html_e('Horror', 'manhwa-scraper'); ?></option>
                                    <option value="isekai"><?php esc_html_e('Isekai', 'manhwa-scraper'); ?></option>
                                    <option value="martial-arts"><?php esc_html_e('Martial Arts', 'manhwa-scraper'); ?></option>
                                    <option value="mystery"><?php esc_html_e('Mystery', 'manhwa-scraper'); ?></option>
                                    <option value="psychological"><?php esc_html_e('Psychological', 'manhwa-scraper'); ?></option>
                                    <option value="romance"><?php esc_html_e('Romance', 'manhwa-scraper'); ?></option>
                                    <option value="school-life"><?php esc_html_e('School Life', 'manhwa-scraper'); ?></option>
                                    <option value="sci-fi"><?php esc_html_e('Sci-Fi', 'manhwa-scraper'); ?></option>
                                    <option value="seinen"><?php esc_html_e('Seinen', 'manhwa-scraper'); ?></option>
                                    <option value="shoujo"><?php esc_html_e('Shoujo', 'manhwa-scraper'); ?></option>
                                    <option value="shounen"><?php esc_html_e('Shounen', 'manhwa-scraper'); ?></option>
                                    <option value="slice-of-life"><?php esc_html_e('Slice of Life', 'manhwa-scraper'); ?></option>
                                    <option value="supernatural"><?php esc_html_e('Supernatural', 'manhwa-scraper'); ?></option>
                                    <option value="system"><?php esc_html_e('System', 'manhwa-scraper'); ?></option>
                                    <option value="thriller"><?php esc_html_e('Thriller', 'manhwa-scraper'); ?></option>
                                </select>
                            </div>
                            
                            <!-- Order By -->
                            <div class="mws-filter-group">
                                <label class="mws-filter-label">
                                    <span class="dashicons dashicons-sort"></span>
                                    <?php esc_html_e('Order By', 'manhwa-scraper'); ?>
                                </label>
                                <select id="mws-filter-order" name="order" class="mws-filter-select">
                                    <option value="relevance"><?php esc_html_e('Relevance', 'manhwa-scraper'); ?></option>
                                    <option value="latest"><?php esc_html_e('Latest Update', 'manhwa-scraper'); ?></option>
                                    <option value="popular"><?php esc_html_e('Most Popular', 'manhwa-scraper'); ?></option>
                                    <option value="rating"><?php esc_html_e('Highest Rating', 'manhwa-scraper'); ?></option>
                                    <option value="title"><?php esc_html_e('Title A-Z', 'manhwa-scraper'); ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mws-filter-actions">
                            <button type="button" id="mws-clear-filters" class="button">
                                <span class="dashicons dashicons-dismiss" style="margin-top: 4px;"></span>
                                <?php esc_html_e('Clear Filters', 'manhwa-scraper'); ?>
                            </button>
                        </div>
                    </div>
                </form>
                
                <div class="mws-search-tips">
                    <span class="dashicons dashicons-lightbulb"></span>
                    <?php esc_html_e('Tip: Enter at least 3 characters for better results. Try searching by Korean/Japanese title for more matches.', 'manhwa-scraper'); ?>
                </div>
            </div>
        </div>
        
        <!-- Popular Tab Content -->
        <div class="mws-tab-content" id="mws-tab-popular" style="display: none;">
            <div class="mws-card mws-browse-card">
                <div class="mws-browse-header">
                    <h3>
                        <span class="dashicons dashicons-star-filled" style="color: #f59e0b;"></span>
                        <?php esc_html_e('Popular Manhwa', 'manhwa-scraper'); ?>
                    </h3>
                    <div class="mws-browse-source-select">
                        <label><?php esc_html_e('Source:', 'manhwa-scraper'); ?></label>
                        <select id="mws-popular-source" class="mws-browse-source">
                            <?php foreach ($sources as $source): ?>
                            <option value="<?php echo esc_attr($source['id']); ?>">
                                <?php echo esc_html($source['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" id="mws-load-popular" class="button button-primary">
                            <span class="dashicons dashicons-update" style="margin-top: 4px;"></span>
                            <?php esc_html_e('Load', 'manhwa-scraper'); ?>
                        </button>
                    </div>
                </div>
                <p class="mws-browse-desc"><?php esc_html_e('Browse popular manhwa from sources. Select a source and click Load to fetch popular titles.', 'manhwa-scraper'); ?></p>
            </div>
            <div class="mws-browse-loading" id="mws-popular-loading" style="display: none;">
                <div class="spinner is-active"></div>
                <p><?php esc_html_e('Loading popular manhwa...', 'manhwa-scraper'); ?></p>
            </div>
            <div class="mws-browse-grid" id="mws-popular-grid"></div>
            <div class="mws-browse-pagination" id="mws-popular-pagination" style="display: none;">
                <button type="button" class="button" id="mws-popular-prev" disabled>
                    <span class="dashicons dashicons-arrow-left-alt2"></span> <?php esc_html_e('Previous', 'manhwa-scraper'); ?>
                </button>
                <span class="mws-page-info"><?php esc_html_e('Page', 'manhwa-scraper'); ?> <span id="mws-popular-page">1</span></span>
                <button type="button" class="button button-primary" id="mws-popular-next">
                    <?php esc_html_e('Next', 'manhwa-scraper'); ?> <span class="dashicons dashicons-arrow-right-alt2"></span>
                </button>
                <button type="button" class="button" id="mws-popular-loadmore" style="margin-left: 10px;">
                    <span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e('Load More', 'manhwa-scraper'); ?>
                </button>
            </div>
        </div>
        
        <!-- Latest Tab Content -->
        <div class="mws-tab-content" id="mws-tab-latest" style="display: none;">
            <div class="mws-card mws-browse-card">
                <div class="mws-browse-header">
                    <h3>
                        <span class="dashicons dashicons-clock" style="color: #3b82f6;"></span>
                        <?php esc_html_e('Latest Updates', 'manhwa-scraper'); ?>
                    </h3>
                    <div class="mws-browse-source-select">
                        <label><?php esc_html_e('Source:', 'manhwa-scraper'); ?></label>
                        <select id="mws-latest-source" class="mws-browse-source">
                            <?php foreach ($sources as $source): ?>
                            <option value="<?php echo esc_attr($source['id']); ?>">
                                <?php echo esc_html($source['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" id="mws-load-latest" class="button button-primary">
                            <span class="dashicons dashicons-update" style="margin-top: 4px;"></span>
                            <?php esc_html_e('Load', 'manhwa-scraper'); ?>
                        </button>
                    </div>
                </div>
                <p class="mws-browse-desc"><?php esc_html_e('Browse the latest updated manhwa from sources. These are the most recently updated titles.', 'manhwa-scraper'); ?></p>
            </div>
            <div class="mws-browse-loading" id="mws-latest-loading" style="display: none;">
                <div class="spinner is-active"></div>
                <p><?php esc_html_e('Loading latest updates...', 'manhwa-scraper'); ?></p>
            </div>
            <div class="mws-browse-grid" id="mws-latest-grid"></div>
            <div class="mws-browse-pagination" id="mws-latest-pagination" style="display: none;">
                <button type="button" class="button" id="mws-latest-prev" disabled>
                    <span class="dashicons dashicons-arrow-left-alt2"></span> <?php esc_html_e('Previous', 'manhwa-scraper'); ?>
                </button>
                <span class="mws-page-info"><?php esc_html_e('Page', 'manhwa-scraper'); ?> <span id="mws-latest-page">1</span></span>
                <button type="button" class="button button-primary" id="mws-latest-next">
                    <?php esc_html_e('Next', 'manhwa-scraper'); ?> <span class="dashicons dashicons-arrow-right-alt2"></span>
                </button>
                <button type="button" class="button" id="mws-latest-loadmore" style="margin-left: 10px;">
                    <span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e('Load More', 'manhwa-scraper'); ?>
                </button>
            </div>
        </div>
        
        
        <!-- Loading State -->
        <div class="mws-search-loading" id="mws-search-loading" style="display: none;">
            <div class="mws-loading-spinner">
                <div class="spinner is-active"></div>
            </div>
            <p><?php esc_html_e('Searching sources...', 'manhwa-scraper'); ?></p>
        </div>
        
        <!-- Results Summary -->
        <div class="mws-results-summary" id="mws-results-summary" style="display: none;">
            <div class="mws-summary-text">
                <span class="dashicons dashicons-yes-alt"></span>
                <span id="mws-results-count">0</span> <?php esc_html_e('results', 'manhwa-scraper'); ?> <span id="mws-results-keyword-wrap">- <span id="mws-results-keyword"></span></span>
            </div>
            <div class="mws-results-actions">
                <button type="button" class="button" id="mws-import-selected-results" style="display: none;">
                    <span class="dashicons dashicons-download" style="margin-top: 4px;"></span>
                    <?php esc_html_e('Import Selected', 'manhwa-scraper'); ?> (<span id="mws-selected-count">0</span>)
                </button>
            </div>
        </div>
        
        <!-- Results Grid -->
        <div class="mws-results-grid" id="mws-results-grid">
            <!-- Results will be inserted here -->
        </div>
        
        <!-- Pagination -->
        <div class="mws-pagination" id="mws-pagination" style="display: none;">
            <button type="button" class="button" id="mws-prev-page" disabled>
                <span class="dashicons dashicons-arrow-left-alt2"></span>
                <?php esc_html_e('Previous', 'manhwa-scraper'); ?>
            </button>
            <span class="mws-page-info">
                <?php esc_html_e('Page', 'manhwa-scraper'); ?> <span id="mws-current-page">1</span> / <span id="mws-total-pages">1</span>
            </span>
            <button type="button" class="button button-primary" id="mws-next-page">
                <?php esc_html_e('Next', 'manhwa-scraper'); ?>
                <span class="dashicons dashicons-arrow-right-alt2"></span>
            </button>
            <button type="button" class="button" id="mws-load-more" style="margin-left: 10px;">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php esc_html_e('Load More', 'manhwa-scraper'); ?>
            </button>
        </div>
        
        <!-- Empty State -->
        <div class="mws-empty-state" id="mws-empty-state">
            <span class="dashicons dashicons-book-alt"></span>
            <h3><?php esc_html_e('Search for Manhwa', 'manhwa-scraper'); ?></h3>
            <p><?php esc_html_e('Enter a manhwa title above to search from enabled sources.', 'manhwa-scraper'); ?></p>
        </div>
        
        <!-- No Results State -->
        <div class="mws-no-results" id="mws-no-results" style="display: none;">
            <span class="dashicons dashicons-warning"></span>
            <h3><?php esc_html_e('No Results Found', 'manhwa-scraper'); ?></h3>
            <p><?php esc_html_e('Try different keywords or check if the source is available.', 'manhwa-scraper'); ?></p>
        </div>
        
        <!-- Duplicate Detection Modal -->
        <div class="mws-modal" id="mws-duplicate-modal" style="display: none;">
            <div class="mws-modal-content mws-duplicate-modal-content">
                <div class="mws-modal-header">
                    <h2><span class="dashicons dashicons-warning" style="color: #f59e0b;"></span> <?php esc_html_e('Duplicate Detected', 'manhwa-scraper'); ?></h2>
                    <button type="button" class="mws-modal-close">&times;</button>
                </div>
                <div class="mws-modal-body">
                    <div class="mws-duplicate-info">
                        <p class="mws-duplicate-message"></p>
                        
                        <div class="mws-duplicate-comparison">
                            <div class="mws-duplicate-new">
                                <h4><?php esc_html_e('New Manhwa', 'manhwa-scraper'); ?></h4>
                                <div class="mws-duplicate-item">
                                    <img src="" alt="" class="mws-dup-thumb-new">
                                    <div class="mws-dup-details">
                                        <div class="mws-dup-title-new"></div>
                                        <div class="mws-dup-source-new"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="mws-duplicate-arrow">
                                <span class="dashicons dashicons-arrow-right-alt"></span>
                            </div>
                            <div class="mws-duplicate-existing">
                                <h4><?php esc_html_e('Existing Manhwa', 'manhwa-scraper'); ?></h4>
                                <div class="mws-duplicate-item">
                                    <img src="" alt="" class="mws-dup-thumb-existing">
                                    <div class="mws-dup-details">
                                        <div class="mws-dup-title-existing"></div>
                                        <div class="mws-dup-chapters-existing"></div>
                                        <a href="" target="_blank" class="mws-dup-edit-link"><?php esc_html_e('Edit Post', 'manhwa-scraper'); ?> →</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mws-duplicate-match-info">
                            <span class="mws-match-badge">
                                <span class="mws-match-type"></span>
                                <span class="mws-match-confidence"></span>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="mws-modal-footer">
                    <button type="button" class="button mws-dup-skip">
                        <span class="dashicons dashicons-no" style="margin-top:4px;"></span>
                        <?php esc_html_e('Skip', 'manhwa-scraper'); ?>
                    </button>
                    <button type="button" class="button mws-dup-update">
                        <span class="dashicons dashicons-update" style="margin-top:4px;"></span>
                        <?php esc_html_e('Update Existing', 'manhwa-scraper'); ?>
                    </button>
                    <button type="button" class="button button-primary mws-dup-create">
                        <span class="dashicons dashicons-plus" style="margin-top:4px;"></span>
                        <?php esc_html_e('Create New Anyway', 'manhwa-scraper'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Detail Preview Modal -->
        <div class="mws-modal" id="mws-preview-modal" style="display: none;">
            <div class="mws-modal-content mws-preview-modal-content">
                <div class="mws-modal-header">
                    <h2><span class="dashicons dashicons-book-alt" style="color: var(--primary);"></span> <span class="mws-preview-title-text">Detail</span></h2>
                    <button type="button" class="mws-modal-close">&times;</button>
                </div>
                <div class="mws-modal-body">
                    <div class="mws-preview-loading" style="text-align:center;padding:40px;"><div class="spinner is-active" style="float:none;"></div><p>Loading details...</p></div>
                    <div class="mws-preview-content" style="display:none;">
                        <div class="mws-preview-top">
                            <img class="mws-preview-cover" src="" alt="">
                            <div class="mws-preview-info">
                                <h3 class="mws-preview-title"></h3>
                                <div class="mws-preview-meta">
                                    <span class="mws-preview-status"></span>
                                    <span class="mws-preview-type"></span>
                                    <span class="mws-preview-rating"></span>
                                </div>
                                <div class="mws-preview-genres"></div>
                                <div class="mws-preview-stats">
                                    <div><strong>Chapters:</strong> <span class="mws-preview-ch-count">-</span></div>
                                    <div><strong>Author:</strong> <span class="mws-preview-author">-</span></div>
                                    <div><strong>Source:</strong> <span class="mws-preview-source-name">-</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="mws-preview-synopsis">
                            <h4>Synopsis</h4>
                            <p class="mws-preview-desc"></p>
                        </div>
                        <div class="mws-preview-chapters-section">
                            <h4>Latest Chapters</h4>
                            <div class="mws-preview-chapters-list"></div>
                        </div>
                    </div>
                </div>
                <div class="mws-modal-footer mws-preview-footer">
                    <label class="mws-chapter-scrape-toggle">
                        <input type="checkbox" id="mws-scrape-ch-check">
                        <span>Also scrape chapter images</span>
                    </label>
                    <div class="mws-preview-footer-actions">
                        <a href="#" target="_blank" class="button mws-preview-source-link">
                            <span class="dashicons dashicons-external" style="margin-top:4px;"></span>
                            Open Source
                        </a>
                        <button type="button" class="button button-primary mws-preview-import-btn" data-url="">
                            <span class="dashicons dashicons-download" style="margin-top:4px;"></span>
                            Import
                        </button>
                    </div>
                </div>
                <!-- Chapter Scrape Progress (inside modal) -->
                <div class="mws-ch-scrape-progress" id="mws-ch-scrape-progress" style="display:none;">
                    <div class="mws-ch-scrape-header">
                        <span class="dashicons dashicons-images-alt2" style="color:#8B5CF6;"></span>
                        <strong>Scraping Chapters</strong>
                        <span class="mws-ch-scrape-counter" id="mws-ch-counter">0 / 0</span>
                    </div>
                    <div class="mws-ch-scrape-bar">
                        <div class="mws-ch-scrape-fill" id="mws-ch-fill" style="width:0%"></div>
                    </div>
                    <div class="mws-ch-scrape-current" id="mws-ch-current">Preparing...</div>
                    <div class="mws-ch-scrape-log" id="mws-ch-log"></div>
                </div>
            </div>
        </div>

        <!-- Search History Dropdown -->
        <div class="mws-search-history" id="mws-search-history" style="display:none;">
            <div class="mws-history-header">
                <span>Recent Searches</span>
                <button type="button" id="mws-clear-history" class="mws-history-clear">Clear</button>
            </div>
            <ul class="mws-history-list" id="mws-history-list"></ul>
        </div>
    </div>
</div>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

/* ── Design Tokens ── */
.mws-search-page {
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
    max-width: 1400px;}

/* ── Page Title ── */
.mws-search-page .mws-title {
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
.mws-search-page .mws-title .dashicons {
    font-size: 28px; width: 28px; height: 28px;
    color: var(--primary);
}

/* ── Cards ── */
.mws-search-page .mws-card {
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
.mws-search-page .mws-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--primary), var(--purple));
    opacity: .7;
}

/* ── Browse Tabs ── */
.mws-browse-tabs {
    display: flex;
    gap: 4px;
    margin-bottom: 22px;
    background: #fff;
    padding: 6px;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--gray-200);
}
.mws-tab-btn {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 10px 22px;
    border: none;
    background: transparent;
    color: var(--gray-500);
    font-family: var(--font);
    font-size: 13px;
    font-weight: 600;
    border-radius: var(--radius);
    cursor: pointer;
    transition: var(--transition);
}
.mws-tab-btn:hover {
    background: var(--gray-100);
    color: var(--gray-700);
}
.mws-tab-btn.active {
    background: linear-gradient(135deg, var(--primary), var(--purple));
    color: #fff;
    box-shadow: 0 4px 12px rgba(99,102,241,.3);
}
.mws-tab-btn .dashicons {
    font-size: 16px; width: 16px; height: 16px;
}
.mws-tab-content { display: none; }
.mws-tab-content.active { display: block; }

/* ── Advanced Filters ── */
.mws-advanced-toggle {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid var(--gray-200);
}
.mws-filter-toggle-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 9px 16px;
    background: var(--gray-50);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm);
    color: var(--gray-700);
    font-family: var(--font);
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
}
.mws-filter-toggle-btn:hover { background: var(--gray-100); }
.mws-filter-toggle-btn .dashicons { font-size: 15px; width: 15px; height: 15px; color: var(--primary); }
.mws-toggle-arrow { transition: transform .25s; }
.mws-filter-toggle-btn.active .mws-toggle-arrow { transform: rotate(180deg); }

.mws-advanced-filters {
    margin-top: 14px;
    padding: 20px;
    background: var(--gray-50);
    border-radius: var(--radius);
    border: 1px solid var(--gray-200);
}
.mws-filters-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 14px;
}
.mws-filter-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.mws-filter-label {
    display: flex;
    align-items: center;
    gap: 5px;
    font-family: var(--font);
    font-size: 10.5px;
    font-weight: 700;
    color: var(--gray-400);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.mws-filter-label .dashicons { font-size: 13px; width: 13px; height: 13px; color: var(--primary); }
.mws-filter-select {
    padding: 9px 12px;
    border: 1.5px solid var(--gray-300);
    border-radius: var(--radius-sm);
    font-family: var(--font);
    font-size: 13px;
    background: #fff;
    cursor: pointer;
    transition: var(--transition);
}
.mws-filter-select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(99,102,241,.1);
    outline: none;
}
.mws-filter-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding-top: 14px;
    border-top: 1px solid var(--gray-200);
}

/* ── Browse Cards ── */
.mws-browse-card { margin-bottom: 20px; }
.mws-browse-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 14px;
    margin-bottom: 10px;
}
.mws-browse-header h3 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    font-family: var(--font);
    font-size: 16px;
    font-weight: 700;
    color: var(--gray-800);
}
.mws-browse-source-select {
    display: flex;
    align-items: center;
    gap: 10px;
}
.mws-browse-source-select label {
    font-family: var(--font);
    font-size: 12px;
    font-weight: 600;
    color: var(--gray-400);
    text-transform: uppercase;
}
.mws-browse-source {
    padding: 8px 12px;
    border: 1.5px solid var(--gray-300);
    border-radius: var(--radius-sm);
    font-family: var(--font);
    font-size: 13px;
    min-width: 160px;
    transition: var(--transition);
}
.mws-browse-source:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99,102,241,.1); outline: none; }

.mws-browse-desc {
    color: var(--gray-500);
    font-family: var(--font);
    font-size: 13px;
    margin: 0;
    line-height: 1.5;
}
.mws-browse-loading {
    text-align: center;
    padding: 60px 20px;
    background: #fff;
    border-radius: var(--radius-lg);
    border: 1px solid var(--gray-200);
    box-shadow: var(--shadow-sm);
}
.mws-browse-loading .spinner { float: none; margin: 0 auto 10px; }
.mws-browse-loading p { font-family: var(--font); color: var(--gray-500); font-size: 13px; }
.mws-browse-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(175px, 1fr));
    gap: 16px;
}

/* ── Pagination ── */
.mws-pagination,
.mws-browse-pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 16px 20px;
    background: #fff;
    border-radius: var(--radius);
    border: 1px solid var(--gray-200);
    box-shadow: var(--shadow-sm);
    margin-top: 20px;
}
.mws-pagination .button,
.mws-browse-pagination .button {
    display: flex !important;
    align-items: center;
    gap: 5px;
    font-family: var(--font) !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    border-radius: var(--radius-sm) !important;
    padding: 8px 14px !important;
    height: auto !important;
    min-height: auto !important;
    transition: var(--transition);
}
.mws-pagination .button-primary,
.mws-browse-pagination .button-primary {
    background: linear-gradient(135deg, var(--primary), var(--purple)) !important;
    border: none !important;
    color: #fff !important;
}
.mws-pagination .dashicons,
.mws-browse-pagination .dashicons {
    font-size: 14px; width: 14px; height: 14px;
}
.mws-page-info {
    font-family: var(--font);
    font-size: 12px;
    color: var(--gray-600);
    padding: 7px 14px;
    background: var(--gray-100);
    border-radius: var(--radius-sm);
    font-weight: 600;
}
.mws-page-info span { font-weight: 700; color: var(--primary); }

/* ── Search Card ── */
.mws-search-card { margin-bottom: 24px; }
.mws-search-form { margin-bottom: 14px; }
.mws-search-row {
    display: flex;
    gap: 12px;
    align-items: stretch;
}
.mws-search-input-wrap { flex: 1; position: relative; }
.mws-search-input-wrap .dashicons {
    position: absolute; left: 14px; top: 50%;
    transform: translateY(-50%); color: var(--gray-400); font-size: 18px;
}
.mws-search-input {
    width: 100%;
    padding: 11px 14px;
    font-family: var(--font);
    font-size: 14px;
    border: 1.5px solid var(--gray-300);
    border-radius: var(--radius);
    transition: var(--transition);
    background: #fff;
}
.mws-search-input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(99,102,241,.1);
    outline: none;
}
.mws-search-source {
    min-width: 170px;
    padding: 11px 14px;
    font-family: var(--font);
    font-size: 13px;
    border: 1.5px solid var(--gray-300);
    border-radius: var(--radius);
    background: #fff;
    cursor: pointer;
    transition: var(--transition);
}
.mws-search-source:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(99,102,241,.1);
    outline: none;
}
.mws-search-btn {
    padding: 11px 24px !important;
    font-family: var(--font) !important;
    font-size: 13px !important;
    font-weight: 600 !important;
    height: auto !important;
    display: flex !important;
    align-items: center;
    gap: 7px;
    background: linear-gradient(135deg, var(--primary), var(--purple)) !important;
    border: none !important;
    border-radius: var(--radius) !important;
    color: #fff !important;
    box-shadow: 0 4px 12px rgba(99,102,241,.25);
    transition: var(--transition);
}
.mws-search-btn:hover { opacity: .9; box-shadow: 0 6px 16px rgba(99,102,241,.35); }
.mws-search-btn .dashicons { margin-top: 0 !important; font-size: 16px; width: 16px; height: 16px; }
.mws-search-tips {
    display: flex;
    align-items: center;
    gap: 8px;
    font-family: var(--font);
    color: var(--gray-500);
    font-size: 12px;
    background: var(--blue-bg);
    padding: 10px 14px;
    border-radius: var(--radius-sm);
    border: 1px solid rgba(59,130,246,.1);
}
.mws-search-tips .dashicons { color: var(--blue); font-size: 16px; width: 16px; height: 16px; }

/* ── Loading State ── */
.mws-search-loading {
    text-align: center;
    padding: 60px 20px;
    background: #fff;
    border-radius: var(--radius-lg);
    border: 1px solid var(--gray-200);
    box-shadow: var(--shadow-sm);
}
.mws-loading-spinner .spinner { float: none; margin: 0 auto 10px; }
.mws-search-loading p { font-family: var(--font); color: var(--gray-500); font-size: 13px; }

/* ── Results Summary ── */
.mws-results-summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 20px;
    background: var(--success-bg);
    border: 1px solid rgba(16,185,129,.15);
    border-radius: var(--radius);
    margin-bottom: 20px;
}
.mws-summary-text {
    display: flex;
    align-items: center;
    gap: 10px;
    font-family: var(--font);
    font-size: 13px;
    font-weight: 600;
    color: #065f46;
}
.mws-summary-text .dashicons { color: var(--success); }

/* ── Results Grid ── */
.mws-results-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(175px, 1fr));
    gap: 16px;
}

/* ── Result Card ── */
.mws-result-card {
    background: #fff;
    border-radius: var(--radius);
    overflow: hidden;
    border: 1px solid var(--gray-200);
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
    position: relative;
}
.mws-result-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-md);
    border-color: var(--gray-300);
}
.mws-result-card.selected {
    border: 2px solid var(--primary);
    box-shadow: 0 0 0 3px rgba(99,102,241,.12);
}
.mws-result-checkbox {
    position: absolute;
    top: 10px; left: 10px;
    z-index: 10;
    width: 20px; height: 20px;
    cursor: pointer;
    accent-color: var(--primary);
}
.mws-result-thumbnail {
    position: relative;
    padding-top: 133%;
    background: linear-gradient(135deg, var(--gray-100), var(--gray-200));
    overflow: hidden;
}
.mws-result-thumbnail img {
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
    object-fit: cover;
    transition: transform .3s;
}
.mws-result-card:hover .mws-result-thumbnail img { transform: scale(1.04); }
.mws-result-source {
    position: absolute;
    top: 8px; right: 8px;
    padding: 3px 9px;
    background: rgba(0,0,0,.65);
    backdrop-filter: blur(4px);
    color: #fff;
    font-family: var(--font);
    font-size: 10px;
    font-weight: 600;
    border-radius: 12px;
    letter-spacing: 0.3px;
}
.mws-result-content { padding: 10px; }
.mws-result-title {
    font-family: var(--font);
    font-size: 12px;
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: 8px;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 34px;
}
.mws-result-actions {
    display: flex;
    gap: 5px;
}
.mws-result-actions .button {
    flex: 1;
    text-align: center;
    justify-content: center;
    display: flex !important;
    align-items: center;
    gap: 3px;
    padding: 5px 6px !important;
    font-family: var(--font) !important;
    font-size: 10.5px !important;
    font-weight: 600 !important;
    min-height: auto !important;
    height: auto !important;
    border-radius: var(--radius-sm) !important;
    transition: var(--transition);
}
.mws-result-actions .button-primary {
    background: linear-gradient(135deg, var(--primary), var(--purple)) !important;
    border: none !important;
    color: #fff !important;
}
.mws-result-actions .button .dashicons { font-size: 14px; width: 14px; height: 14px; }
.mws-result-actions a.button { text-decoration: none; }

/* ── Rank Badge ── */
.mws-rank-badge {
    position: absolute;
    top: 8px; left: 8px;
    padding: 3px 9px;
    background: linear-gradient(135deg, var(--warning), #D97706);
    color: #fff;
    font-family: var(--font);
    font-size: 11px;
    font-weight: 800;
    border-radius: var(--radius-sm);
    box-shadow: 0 2px 8px rgba(245,158,11,.4);
    z-index: 5;
}
.mws-result-card[data-index="0"] .mws-rank-badge {
    background: linear-gradient(135deg, #FBBF24, #F59E0B);
    box-shadow: 0 2px 10px rgba(251,191,36,.5);
}
.mws-result-card[data-index="1"] .mws-rank-badge {
    background: linear-gradient(135deg, #9CA3AF, #6B7280);
    box-shadow: 0 2px 10px rgba(107,114,128,.4);
}
.mws-result-card[data-index="2"] .mws-rank-badge {
    background: linear-gradient(135deg, #CD7F32, #A0522D);
    box-shadow: 0 2px 10px rgba(205,127,50,.4);
}

/* ── Empty & No Results ── */
.mws-empty-state,
.mws-no-results {
    text-align: center;
    padding: 70px 24px;
    background: #fff;
    border-radius: var(--radius-lg);
    border: 1px solid var(--gray-200);
    box-shadow: var(--shadow-sm);
}
.mws-empty-state .dashicons,
.mws-no-results .dashicons {
    font-size: 56px; width: 56px; height: 56px;
    color: var(--gray-300);
    margin-bottom: 14px;
}
.mws-empty-state h3,
.mws-no-results h3 {
    margin: 0 0 8px;
    font-family: var(--font);
    font-size: 17px;
    font-weight: 700;
    color: var(--gray-700);
}
.mws-empty-state p,
.mws-no-results p {
    font-family: var(--font);
    color: var(--gray-400);
    font-size: 13px;
    margin: 0;
}

/* ── Import States ── */
.mws-result-card.importing { opacity: .55; pointer-events: none; }
.mws-result-card.imported .mws-result-actions .button-primary {
    background: linear-gradient(135deg, var(--success), #059669) !important;
    pointer-events: none;
}
.mws-result-card.mws-exists {
    border: 2px solid var(--success);
}
.mws-result-card.mws-exists .mws-result-thumbnail::after {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(16,185,129,.1);
    pointer-events: none;
}
.mws-exists-badge {
    position: absolute;
    bottom: 8px; left: 50%;
    transform: translateX(-50%);
    padding: 4px 11px;
    background: linear-gradient(135deg, var(--success), #059669);
    color: #fff;
    font-family: var(--font);
    font-size: 9px;
    font-weight: 700;
    border-radius: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 8px rgba(16,185,129,.4);
    white-space: nowrap;
}
.mws-result-card.mws-exists .mws-result-actions .button-primary {
    background: linear-gradient(135deg, var(--success), #059669) !important;
}

/* ── Duplicate Modal ── */
.mws-modal {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,.5);
    backdrop-filter: blur(4px);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.mws-modal-content {
    background: #fff;
    border-radius: var(--radius-lg);
    max-width: 680px;
    width: 100%;
    max-height: 90vh;
    overflow: auto;
    box-shadow: 0 25px 60px rgba(0,0,0,.25);
}
.mws-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid var(--gray-200);
}
.mws-modal-header h2 {
    margin: 0;
    font-family: var(--font);
    font-size: 17px;
    font-weight: 700;
    color: var(--gray-800);
    display: flex;
    align-items: center;
    gap: 10px;
}
.mws-modal-close {
    width: 30px; height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--gray-100);
    border: none;
    border-radius: var(--radius-sm);
    font-size: 20px;
    cursor: pointer;
    color: var(--gray-400);
    transition: var(--transition);
}
.mws-modal-close:hover { background: var(--gray-200); color: var(--gray-700); }
.mws-modal-body { padding: 24px; }
.mws-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    padding: 18px 24px;
    border-top: 1px solid var(--gray-200);
    background: var(--gray-50);
    border-radius: 0 0 var(--radius-lg) var(--radius-lg);
}
.mws-modal-footer .button {
    display: flex;
    align-items: center;
    gap: 5px;
    font-family: var(--font) !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    border-radius: var(--radius-sm) !important;
    padding: 8px 16px !important;
    height: auto !important;
    min-height: auto !important;
}
.mws-modal-footer .button-primary {
    background: linear-gradient(135deg, var(--primary), var(--purple)) !important;
    border: none !important;
}
.mws-duplicate-message {
    font-family: var(--font);
    font-size: 13px;
    color: var(--gray-500);
    margin-bottom: 20px;
    line-height: 1.5;
}
.mws-duplicate-comparison {
    display: flex;
    gap: 16px;
    align-items: stretch;
}
.mws-duplicate-new,
.mws-duplicate-existing {
    flex: 1;
    background: var(--gray-50);
    border-radius: var(--radius);
    padding: 16px;
    border: 1px solid var(--gray-200);
}
.mws-duplicate-new h4,
.mws-duplicate-existing h4 {
    margin: 0 0 12px;
    font-family: var(--font);
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--gray-400);
}
.mws-duplicate-arrow {
    display: flex;
    align-items: center;
    color: var(--gray-300);
}
.mws-duplicate-arrow .dashicons { font-size: 28px; width: 28px; height: 28px; }
.mws-duplicate-item { display: flex; gap: 12px; }
.mws-duplicate-item img {
    width: 56px; height: 75px;
    object-fit: cover;
    border-radius: var(--radius-sm);
    background: var(--gray-200);
}
.mws-dup-details { flex: 1; min-width: 0; }
.mws-dup-title-new,
.mws-dup-title-existing {
    font-family: var(--font);
    font-weight: 700;
    font-size: 13px;
    color: var(--gray-800);
    margin-bottom: 4px;
    line-height: 1.3;
}
.mws-dup-source-new,
.mws-dup-chapters-existing {
    font-family: var(--font);
    font-size: 11px;
    color: var(--gray-400);
    margin-bottom: 5px;
}
.mws-dup-edit-link {
    font-family: var(--font);
    font-size: 11px;
    font-weight: 600;
    color: var(--primary);
    text-decoration: none;
}
.mws-dup-edit-link:hover { text-decoration: underline; }
.mws-duplicate-match-info { margin-top: 18px; text-align: center; }
.mws-match-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 14px;
    background: var(--warning-bg);
    border: 1px solid rgba(245,158,11,.15);
    border-radius: 20px;
    font-family: var(--font);
    font-size: 12px;
}
.mws-match-type { color: #92400e; font-weight: 600; }
.mws-match-confidence {
    background: var(--warning);
    color: #fff;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 700;
}

/* ── WordPress Button Overrides ── */
.mws-search-page .button {
    font-family: var(--font) !important;
}
.mws-search-page .button .dashicons {
    margin-top: 0 !important;
}
.mws-search-page .notice {
    font-family: var(--font) !important;
    border-radius: var(--radius-sm);
    margin: 0 0 16px 0 !important;
}

/* ── Responsive Design ── */
@media (max-width: 1200px) {
    .mws-filters-grid { grid-template-columns: repeat(2, 1fr); }
    .mws-results-grid,
    .mws-browse-grid {
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    }
}
@media (max-width: 782px) {
    .mws-search-row { flex-direction: column; }
    .mws-search-source { min-width: 100%; }
    .mws-results-summary {
        flex-direction: column;
        gap: 12px;
        text-align: center;
    }
    .mws-filters-grid { grid-template-columns: 1fr; }
    .mws-browse-header {
        flex-direction: column;
        align-items: flex-start;
    }
    .mws-browse-source-select { width: 100%; }
    .mws-browse-source { flex: 1; }
    .mws-results-grid,
    .mws-browse-grid {
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 12px;
    }
    .mws-pagination,
    .mws-browse-pagination {
        flex-wrap: wrap;
        gap: 8px;
        padding: 14px;
    }
    .mws-search-page .mws-card { padding: 18px !important; }
    .mws-modal-content { max-width: 95%; }
    .mws-modal-footer { flex-wrap: wrap; }
}
@media (max-width: 600px) {
    .mws-browse-tabs { flex-wrap: wrap; }
    .mws-tab-btn {
        flex: 1;
        justify-content: center;
        min-width: 90px;
        padding: 9px 12px;
        font-size: 12px;
    }
    .mws-duplicate-comparison { flex-direction: column; }
    .mws-duplicate-arrow {
        transform: rotate(90deg);
        justify-content: center;
    }
    .mws-empty-state,
    .mws-no-results { padding: 50px 18px; }
    .mws-search-page .mws-title {
        font-size: 18px !important;
    }
}

/* ── Detail Preview Modal ── */
.mws-preview-modal-content { max-width: 780px; }
.mws-preview-top {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}
.mws-preview-cover {
    width: 160px;
    height: 220px;
    object-fit: cover;
    border-radius: var(--radius);
    background: var(--gray-200);
    flex-shrink: 0;
}
.mws-preview-info { flex: 1; min-width: 0; }
.mws-preview-title {
    font-family: var(--font);
    font-size: 18px;
    font-weight: 800;
    color: var(--gray-800);
    margin: 0 0 10px;
    line-height: 1.3;
}
.mws-preview-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 12px;
}
.mws-preview-meta span {
    padding: 3px 10px;
    border-radius: 12px;
    font-family: var(--font);
    font-size: 11px;
    font-weight: 600;
}
.mws-preview-status {
    background: var(--success-bg);
    color: #065f46;
    border: 1px solid rgba(16,185,129,.15);
}
.mws-preview-type {
    background: var(--primary-bg);
    color: var(--primary-dark);
    border: 1px solid rgba(99,102,241,.12);
}
.mws-preview-rating {
    background: var(--warning-bg);
    color: #92400e;
    border: 1px solid rgba(245,158,11,.12);
}
.mws-preview-genres {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-bottom: 14px;
}
.mws-preview-genres .mws-genre-tag {
    padding: 3px 9px;
    background: var(--gray-100);
    border: 1px solid var(--gray-200);
    border-radius: 10px;
    font-family: var(--font);
    font-size: 10px;
    font-weight: 600;
    color: var(--gray-600);
}
.mws-preview-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    font-family: var(--font);
    font-size: 12px;
    color: var(--gray-500);
}
.mws-preview-stats strong { color: var(--gray-700); }
.mws-preview-synopsis h4,
.mws-preview-chapters-section h4 {
    font-family: var(--font);
    font-size: 13px;
    font-weight: 700;
    color: var(--gray-700);
    margin: 0 0 8px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}
.mws-preview-desc {
    font-family: var(--font);
    font-size: 13px;
    color: var(--gray-600);
    line-height: 1.65;
    margin: 0 0 18px;
    max-height: 120px;
    overflow-y: auto;
}
.mws-preview-chapters-list {
    max-height: 160px;
    overflow-y: auto;
}
.mws-preview-ch-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 10px;
    border-bottom: 1px solid var(--gray-100);
    font-family: var(--font);
    font-size: 12px;
    color: var(--gray-600);
}
.mws-preview-ch-item:last-child { border-bottom: none; }

/* ── Chapter Scrape in Modal ── */
.mws-preview-footer {
    flex-wrap: wrap;
    gap: 12px;
}
.mws-preview-footer-actions {
    display: flex;
    gap: 8px;
    margin-left: auto;
}
.mws-chapter-scrape-toggle {
    display: flex;
    align-items: center;
    gap: 8px;
    font-family: var(--font);
    font-size: 12px;
    font-weight: 600;
    color: var(--purple);
    cursor: pointer;
    padding: 4px 12px;
    background: var(--purple-bg);
    border: 1px solid rgba(139,92,246,.12);
    border-radius: var(--radius-sm);
    transition: var(--transition);
}
.mws-chapter-scrape-toggle:hover { background: rgba(139,92,246,.1); }
.mws-chapter-scrape-toggle input { accent-color: var(--purple); }

.mws-ch-scrape-progress {
    border-top: 1px solid var(--gray-200);
    padding: 16px 24px;
    background: var(--gray-50);
    border-radius: 0 0 var(--radius-lg) var(--radius-lg);
}
.mws-ch-scrape-header {
    display: flex;
    align-items: center;
    gap: 8px;
    font-family: var(--font);
    font-size: 13px;
    color: var(--gray-700);
    margin-bottom: 10px;
}
.mws-ch-scrape-counter {
    margin-left: auto;
    font-size: 11px;
    font-weight: 700;
    color: var(--purple);
    background: var(--purple-bg);
    padding: 2px 10px;
    border-radius: 10px;
}
.mws-ch-scrape-bar {
    height: 6px;
    background: var(--gray-200);
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 8px;
}
.mws-ch-scrape-fill {
    height: 100%;
    background: linear-gradient(90deg, #8B5CF6, #a78bfa);
    border-radius: 3px;
    transition: width .3s;
}
.mws-ch-scrape-current {
    font-family: var(--font);
    font-size: 11px;
    color: var(--gray-500);
    margin-bottom: 8px;
}
.mws-ch-scrape-log {
    max-height: 180px;
    overflow-y: auto;
    background: #0f172a;
    padding: 10px 14px;
    border-radius: var(--radius-sm);
    font-family: 'Cascadia Code', 'Fira Code', 'JetBrains Mono', monospace;
    font-size: 11px;
    color: #94A3B8;
    line-height: 1.7;
}
.mws-ch-scrape-log::-webkit-scrollbar { width: 5px; }
.mws-ch-scrape-log::-webkit-scrollbar-track { background: transparent; }
.mws-ch-scrape-log::-webkit-scrollbar-thumb { background: #334155; border-radius: 3px; }

/* ── Result Card Metadata ── */
.mws-result-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-bottom: 6px;
}
.mws-result-meta-tag {
    padding: 1px 6px;
    border-radius: 8px;
    font-family: var(--font);
    font-size: 9px;
    font-weight: 600;
    line-height: 1.5;
}
.mws-meta-status {
    background: var(--success-bg);
    color: #065f46;
}
.mws-meta-status.completed { background: var(--blue-bg); color: #1e40af; }
.mws-meta-status.hiatus { background: var(--warning-bg); color: #92400e; }
.mws-meta-chapters {
    background: var(--purple-bg);
    color: #6b21a8;
}
.mws-meta-rating {
    background: var(--warning-bg);
    color: #92400e;
}

/* ── Toast Notifications ── */
.mws-toast-container {
    position: fixed;
    top: 40px;
    right: 20px;
    z-index: 100001;
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-width: 380px;
}
.mws-toast {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 18px;
    border-radius: var(--radius);
    font-family: var(--font);
    font-size: 13px;
    font-weight: 500;
    box-shadow: var(--shadow-md);
    animation: mwsToastIn .3s ease;
    border: 1px solid;
    cursor: pointer;
}
.mws-toast.success { background: #ecfdf5; border-color: rgba(16,185,129,.2); color: #065f46; }
.mws-toast.error { background: #fef2f2; border-color: rgba(239,68,68,.2); color: #991b1b; }
.mws-toast.warning { background: #fffbeb; border-color: rgba(245,158,11,.2); color: #92400e; }
.mws-toast.info { background: #eff6ff; border-color: rgba(59,130,246,.2); color: #1e40af; }
.mws-toast .dashicons { font-size: 18px; width: 18px; height: 18px; flex-shrink: 0; }
.mws-toast.leaving { animation: mwsToastOut .25s ease forwards; }
@keyframes mwsToastIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
@keyframes mwsToastOut { to { transform: translateX(100%); opacity: 0; } }

/* ── Search History ── */
.mws-search-history {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius);
    box-shadow: var(--shadow-md);
    z-index: 50;
    max-height: 220px;
    overflow-y: auto;
}
.mws-search-input-wrap { position: relative; }
.mws-history-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 14px;
    border-bottom: 1px solid var(--gray-100);
    font-family: var(--font);
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--gray-400);
    letter-spacing: 0.4px;
}
.mws-history-clear {
    background: none;
    border: none;
    color: var(--danger);
    cursor: pointer;
    font-family: var(--font);
    font-size: 10px;
    font-weight: 600;
}
.mws-history-list {
    list-style: none;
    margin: 0;
    padding: 0;
}
.mws-history-list li {
    padding: 8px 14px;
    font-family: var(--font);
    font-size: 13px;
    color: var(--gray-600);
    cursor: pointer;
    transition: background .15s;
    display: flex;
    align-items: center;
    gap: 8px;
}
.mws-history-list li:hover { background: var(--primary-bg); color: var(--primary-dark); }
.mws-history-list li .dashicons { font-size: 14px; width: 14px; height: 14px; color: var(--gray-300); }

/* ── Skeleton Loading ── */
.mws-skeleton-card {
    background: #fff;
    border-radius: var(--radius);
    overflow: hidden;
    border: 1px solid var(--gray-200);
}
.mws-skeleton-thumb {
    padding-top: 133%;
    background: linear-gradient(90deg, var(--gray-100) 25%, var(--gray-200) 50%, var(--gray-100) 75%);
    background-size: 200% 100%;
    animation: mwsShimmer 1.5s infinite;
}
.mws-skeleton-line {
    height: 12px;
    margin: 10px;
    border-radius: 6px;
    background: linear-gradient(90deg, var(--gray-100) 25%, var(--gray-200) 50%, var(--gray-100) 75%);
    background-size: 200% 100%;
    animation: mwsShimmer 1.5s infinite;
}
.mws-skeleton-line.short { width: 60%; }
.mws-skeleton-line.btn { height: 28px; margin-top: 8px; }
@keyframes mwsShimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

/* ── Batch Import Progress ── */
.mws-batch-progress {
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: #fff;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
    padding: 14px 24px;
    z-index: 100001;
    min-width: 320px;
    font-family: var(--font);
}
.mws-batch-progress-bar {
    height: 6px;
    background: var(--gray-100);
    border-radius: 3px;
    overflow: hidden;
    margin-top: 8px;
}
.mws-batch-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--primary), var(--purple));
    border-radius: 3px;
    transition: width .3s;
}
.mws-batch-progress-text {
    font-size: 12px;
    font-weight: 600;
    color: var(--gray-700);
}
.mws-batch-progress-sub {
    font-size: 11px;
    color: var(--gray-400);
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

@media (max-width: 600px) {
    .mws-preview-top { flex-direction: column; align-items: center; text-align: center; }
    .mws-preview-cover { width: 120px; height: 165px; }
    .mws-preview-meta, .mws-preview-genres { justify-content: center; }
    .mws-preview-stats { justify-content: center; }
}
</style>


<script>
jQuery(document).ready(function($) {

    // ── State Variables ──
    var searchResults = [];
    var currentPage = 1;
    var totalPages = 1;
    var lastKeyword = '';
    var lastSource = 'all';
    var lastFilters = null;
    var pendingImport = null;
    var pendingScrapedData = null;
    var browseResults = { popular: [], latest: [] };
    var browsePages = { popular: 1, latest: 1 };

    // ── Tab Switching ──
    $('.mws-tab-btn').on('click', function() {
        var tab = $(this).data('tab');

        // Update active button
        $('.mws-tab-btn').removeClass('active');
        $(this).addClass('active');

        // Show/hide tab content
        $('.mws-tab-content').hide();
        $('#mws-tab-' + tab).show();

        // Show/hide shared elements based on tab
        if (tab === 'search') {
            $('#mws-search-loading, #mws-results-summary, #mws-results-grid, #mws-pagination, #mws-empty-state, #mws-no-results').each(function() {
                // Keep their current visibility for search tab
            });
        } else {
            // Hide search-specific elements when on other tabs
        }
    });

    // ── Advanced Filters Toggle ──
    $('#mws-toggle-filters').on('click', function() {
        var $filters = $('#mws-advanced-filters');
        var $btn = $(this);

        if ($filters.is(':visible')) {
            $filters.slideUp(200);
            $btn.removeClass('active');
        } else {
            $filters.slideDown(200);
            $btn.addClass('active');
        }
    });

    // ── Clear Filters ──
    $('#mws-clear-filters').on('click', function() {
        $('#mws-filter-type').val('');
        $('#mws-filter-status').val('');
        $('#mws-filter-genre').val('');
        $('#mws-filter-order').val('popular');
    });

    // ── Search Form Submit ──
    $('#mws-search-form').on('submit', function(e) {
        e.preventDefault();

        var keyword = $('#mws-search-keyword').val().trim();
        var source = $('#mws-search-source').val();
        var filters = {
            type: $('#mws-filter-type').val(),
            status: $('#mws-filter-status').val(),
            genre: $('#mws-filter-genre').val(),
            order: $('#mws-filter-order').val()
        };

        var hasFilters = filters.type || filters.status || filters.genre;

        if (!keyword && !hasFilters) {
            alert('Please enter a search keyword or select at least one filter.');
            return;
        }

        if (keyword && keyword.length < 2) {
            alert('Please enter at least 2 characters.');
            return;
        }

        currentPage = 1;
        searchResults = [];
        performSearch(keyword, source, filters, 1);
    });

    // ── Perform Search (AJAX) ──
    function performSearch(keyword, source, filters, page) {
        lastKeyword = keyword;
        lastSource = source;
        lastFilters = filters;
        currentPage = page;

        // UI state: show loading
        $('#mws-empty-state').hide();
        $('#mws-no-results').hide();
        $('#mws-results-summary').hide();
        $('#mws-pagination').hide();
        $('#mws-search-loading').show();

        if (page === 1) {
            $('#mws-results-grid').empty();
            searchResults = [];
        }

        $.ajax({
            url: mwsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mws_search_manhwa',
                nonce: mwsData.nonce,
                keyword: keyword,
                source: source,
                page: page,
                type: filters ? filters.type : '',
                status: filters ? filters.status : '',
                genre: filters ? filters.genre : '',
                order: filters ? filters.order : 'popular'
            },
            success: function(response) {
                $('#mws-search-loading').hide();

                if (response.success && response.data.results && response.data.results.length > 0) {
                    searchResults = response.data.results;
                    displayResults(searchResults);

                    // Update summary
                    $('#mws-results-count').text(response.data.count || searchResults.length);
                    if (keyword) {
                        $('#mws-results-keyword').text('"' + keyword + '"');
                        $('#mws-results-keyword-wrap').show();
                    } else {
                        $('#mws-results-keyword-wrap').hide();
                    }
                    $('#mws-results-summary').show();
                    updatePagination();
                } else {
                    $('#mws-no-results').show();
                    if (response.data && response.data.message) {
                        $('#mws-no-results').find('p').text(response.data.message);
                    }
                }
            },
            error: function(xhr, status, error) {
                $('#mws-search-loading').hide();
                showError('Search failed: ' + (error || 'Network error'));
            }
        });
    }

    // ── Display Results in Grid ──
    function displayResults(results) {
        var $grid = $('#mws-results-grid');
        $grid.empty();

        results.forEach(function(item, i) {
            var thumbnail = item.thumbnail_url || '';
            var thumbnailHtml = thumbnail
                ? '<img src="' + thumbnail + '" alt="' + (item.title || '').replace(/"/g, '&quot;') + '" loading="lazy">'
                : '<div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:48px;opacity:0.3;">📚</div>';

            var existsBadge = '';
            var actionButtons = '';

            if (item.exists_in_db) {
                existsBadge = '<span class="mws-exists-badge">✓ Already Exists</span>';
                actionButtons =
                    '<a href="' + (item.existing_view_url || '#') + '" target="_blank" class="button mws-view-existing">' +
                        '<span class="dashicons dashicons-visibility"></span> View' +
                    '</a>' +
                    '<a href="' + (item.existing_edit_url || '#') + '" target="_blank" class="button button-primary mws-edit-existing">' +
                        '<span class="dashicons dashicons-edit"></span> Edit' +
                    '</a>';
            } else {
                actionButtons =
                    '<button type="button" class="button mws-view-details" data-index="' + i + '" data-url="' + (item.url || '') + '">' +
                        '<span class="dashicons dashicons-visibility"></span> View' +
                    '</button>' +
                    '<button type="button" class="button button-primary mws-quick-import" data-index="' + i + '">' +
                        '<span class="dashicons dashicons-download"></span> Import' +
                    '</button>';
            }

            var card = $(
                '<div class="mws-result-card' + (item.exists_in_db ? ' mws-exists' : '') + '" data-index="' + i + '">' +
                    '<input type="checkbox" class="mws-result-checkbox" data-index="' + i + '"' + (item.exists_in_db ? ' disabled' : '') + '>' +
                    '<div class="mws-result-thumbnail">' +
                        thumbnailHtml +
                        '<span class="mws-result-source">' + (item.source_name || item.source || '') + '</span>' +
                        existsBadge +
                    '</div>' +
                    '<div class="mws-result-content">' +
                        '<div class="mws-result-title" title="' + (item.title || '').replace(/"/g, '&quot;') + '">' + (item.title || 'Untitled') + '</div>' +
                        '<div class="mws-result-actions">' +
                            actionButtons +
                        '</div>' +
                    '</div>' +
                '</div>'
            );

            $grid.append(card);
        });
    }

    // ── Update Pagination ──
    function updatePagination() {
        var $pagination = $('#mws-pagination');
        if (searchResults.length > 0) {
            $pagination.show();
            $('#mws-current-page').text(currentPage);
            $('#mws-prev-page').prop('disabled', currentPage <= 1);
        } else {
            $pagination.hide();
        }
    }

    // ── Browse Popular ──
    $('#mws-load-popular').on('click', function() {
        browsePages.popular = 1;
        loadBrowse('popular');
    });

    $('#mws-popular-next').on('click', function() {
        browsePages.popular++;
        loadBrowse('popular');
    });

    $('#mws-popular-prev').on('click', function() {
        if (browsePages.popular > 1) {
            browsePages.popular--;
            loadBrowse('popular');
        }
    });

    $('#mws-popular-loadmore').on('click', function() {
        browsePages.popular++;
        loadBrowse('popular', true);
    });

    // ── Browse Latest ──
    $('#mws-load-latest').on('click', function() {
        browsePages.latest = 1;
        loadBrowse('latest');
    });

    $('#mws-latest-next').on('click', function() {
        browsePages.latest++;
        loadBrowse('latest');
    });

    $('#mws-latest-prev').on('click', function() {
        if (browsePages.latest > 1) {
            browsePages.latest--;
            loadBrowse('latest');
        }
    });

    $('#mws-latest-loadmore').on('click', function() {
        browsePages.latest++;
        loadBrowse('latest', true);
    });

    // ── Load Browse (Popular / Latest) ──
    function loadBrowse(type, append) {
        var source = $('#mws-' + type + '-source').val();
        var page = browsePages[type];

        if (!source) {
            alert('Please select a source.');
            return;
        }

        var $loading = $('#mws-' + type + '-loading');
        var $grid = $('#mws-' + type + '-grid');
        var $pagination = $('#mws-' + type + '-pagination');

        $loading.show();
        if (!append) {
            $grid.empty();
            browseResults[type] = [];
        }

        $.ajax({
            url: mwsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mws_browse_manhwa',
                nonce: mwsData.nonce,
                source: source,
                type: type,
                page: page
            },
            success: function(response) {
                $loading.hide();

                if (response.success && response.data.results && response.data.results.length > 0) {
                    var results = response.data.results;

                    results.forEach(function(item, i) {
                        browseResults[type].push(item);
                        var idx = browseResults[type].length - 1;

                        var thumbnail = item.thumbnail_url || '';
                        var thumbnailHtml = thumbnail
                            ? '<img src="' + thumbnail + '" alt="' + (item.title || '').replace(/"/g, '&quot;') + '" loading="lazy">'
                            : '<div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:48px;opacity:0.3;">📚</div>';

                        var existsBadge = '';
                        var actionButtons = '';

                        if (item.exists_in_db) {
                            existsBadge = '<span class="mws-exists-badge">✓ Already Exists</span>';
                            actionButtons =
                                '<a href="' + (item.existing_view_url || '#') + '" target="_blank" class="button mws-view-existing">' +
                                    '<span class="dashicons dashicons-visibility"></span> View' +
                                '</a>' +
                                '<a href="' + (item.existing_edit_url || '#') + '" target="_blank" class="button button-primary mws-edit-existing">' +
                                    '<span class="dashicons dashicons-edit"></span> Edit' +
                                '</a>';
                        } else {
                            actionButtons =
                                '<button type="button" class="button mws-view-details" data-url="' + (item.url || '') + '">' +
                                    '<span class="dashicons dashicons-visibility"></span> View' +
                                '</button>' +
                                '<button type="button" class="button button-primary mws-quick-import" data-browse-type="' + type + '" data-browse-index="' + idx + '">' +
                                    '<span class="dashicons dashicons-download"></span> Import' +
                                '</button>';
                        }

                        var rankBadge = '';
                        if (type === 'popular' && !append && i < 3) {
                            rankBadge = '<span class="mws-rank-badge">#' + (i + 1) + '</span>';
                        }

                        var card = $(
                            '<div class="mws-result-card' + (item.exists_in_db ? ' mws-exists' : '') + '" data-index="' + idx + '">' +
                                rankBadge +
                                '<div class="mws-result-thumbnail">' +
                                    thumbnailHtml +
                                    '<span class="mws-result-source">' + (item.source_name || item.source || source) + '</span>' +
                                    existsBadge +
                                '</div>' +
                                '<div class="mws-result-content">' +
                                    '<div class="mws-result-title" title="' + (item.title || '').replace(/"/g, '&quot;') + '">' + (item.title || 'Untitled') + '</div>' +
                                    '<div class="mws-result-actions">' +
                                        actionButtons +
                                    '</div>' +
                                '</div>' +
                            '</div>'
                        );

                        $grid.append(card);
                    });

                    // Update pagination
                    $pagination.show();
                    $('#mws-' + type + '-page').text(page);
                    $('#mws-' + type + '-prev').prop('disabled', page <= 1);
                } else {
                    if (!append) {
                        $grid.html('<div class="mws-no-results" style="display:block;"><span class="dashicons dashicons-warning"></span><h3>No Results</h3><p>No ' + type + ' manhwa found from this source.</p></div>');
                    }
                }
            },
            error: function() {
                $loading.hide();
                alert('Failed to load ' + type + ' manhwa. Please try again.');
            }
        });
    }

    // ── Browse Import Handler ──
    $(document).on('click', '.mws-quick-import[data-browse-type]', function() {
        var $btn = $(this);
        var $card = $btn.closest('.mws-result-card');
        var browseType = $btn.data('browse-type');
        var browseIndex = $btn.data('browse-index');
        var item = browseResults[browseType][browseIndex];

        if (!item) return;

        // Add to searchResults temporarily so the import flow works
        var tempIndex = searchResults.length;
        searchResults.push(item);

        // Update button attributes to use search flow
        $btn.removeAttr('data-browse-type data-browse-index');
        $btn.attr('data-index', tempIndex);
        $card.attr('data-index', tempIndex);

        // Trigger the standard import
        pendingImport = { btn: $btn, card: $card, item: item };

        $card.addClass('importing');
        $btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none;margin:0;"></span>');

        // Check for duplicates first
        $.ajax({
            url: mwsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mws_check_duplicate',
                nonce: mwsData.nonce,
                title: item.title,
                slug: item.slug || '',
                url: item.url || ''
            },
            success: function(dupResponse) {
                if (dupResponse.success && dupResponse.data.is_duplicate) {
                    showDuplicateModal(item, dupResponse.data);
                } else {
                    proceedWithImport(item, $card, $btn, false, null);
                }
            },
            error: function() {
                proceedWithImport(item, $card, $btn, false, null);
            }
        });
    });

    // ── Search Pagination ──
    $('#mws-prev-page').on('click', function() {
        if (currentPage > 1) {
            currentPage--;
            performSearch(lastKeyword, lastSource, lastFilters, currentPage);
        }
    });
    
    $('#mws-next-page').on('click', function() {
        currentPage++;
        performSearch(lastKeyword, lastSource, lastFilters, currentPage);
    });
    
    $('#mws-load-more').on('click', function() {
        currentPage++;
        // For load more, we append instead of replace
        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none;margin:0;"></span> Loading...');
        
        $.ajax({
            url: mwsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mws_search_manhwa',
                nonce: mwsData.nonce,
                keyword: lastKeyword,
                source: lastSource,
                page: currentPage,
                type: lastFilters ? lastFilters.type : '',
                status: lastFilters ? lastFilters.status : '',
                genre: lastFilters ? lastFilters.genre : '',
                order: lastFilters ? lastFilters.order : 'popular'
            },
            success: function(response) {
                if (response.success && response.data.results.length > 0) {
                    // Append results to searchResults
                    searchResults = searchResults.concat(response.data.results);
                    
                    // Append to grid
                    var $grid = $('#mws-results-grid');
                    response.data.results.forEach(function(item, i) {
                        var thumbnail = item.thumbnail_url || '';
                        var thumbnailHtml = thumbnail 
                            ? '<img src="' + thumbnail + '" alt="' + item.title + '" loading="lazy">'
                            : '<div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:48px;opacity:0.3;">📚</div>';
                        
                        var existsBadge = '';
                        var actionButtons = '';
                        var idx = searchResults.length - response.data.results.length + i;
                        
                        if (item.exists_in_db) {
                            existsBadge = '<span class="mws-exists-badge">✓ Already Exists</span>';
                            actionButtons = 
                                '<a href="' + item.existing_view_url + '" target="_blank" class="button mws-view-existing">' +
                                    '<span class="dashicons dashicons-visibility"></span> View' +
                                '</a>' +
                                '<a href="' + item.existing_edit_url + '" target="_blank" class="button button-primary mws-edit-existing">' +
                                    '<span class="dashicons dashicons-edit"></span> Edit' +
                                '</a>';
                        } else {
                            actionButtons = 
                                '<button type="button" class="button mws-view-details" data-index="' + idx + '" data-url="' + item.url + '">' +
                                    '<span class="dashicons dashicons-visibility"></span> View' +
                                '</button>' +
                                '<button type="button" class="button button-primary mws-quick-import" data-index="' + idx + '">' +
                                    '<span class="dashicons dashicons-download"></span> Import' +
                                '</button>';
                        }
                        
                        var card = $('<div class="mws-result-card' + (item.exists_in_db ? ' mws-exists' : '') + '" data-index="' + idx + '">' +
                            '<input type="checkbox" class="mws-result-checkbox" data-index="' + idx + '"' + (item.exists_in_db ? ' disabled' : '') + '>' +
                            '<div class="mws-result-thumbnail">' +
                                thumbnailHtml +
                                '<span class="mws-result-source">' + (item.source_name || item.source) + '</span>' +
                                existsBadge +
                            '</div>' +
                            '<div class="mws-result-content">' +
                                '<div class="mws-result-title" title="' + item.title + '">' + item.title + '</div>' +
                                '<div class="mws-result-actions">' +
                                    actionButtons +
                                '</div>' +
                            '</div>' +
                        '</div>');
                        
                        $grid.append(card);
                    });
                    
                    // Update count display
                    $('#mws-results-count').text(searchResults.length);
                    updatePagination();
                } else {
                    // No more results
                    $('#mws-load-more').prop('disabled', true).text('No more results');
                    currentPage--; // Revert page number
                }
            },
            error: function() {
                currentPage--;
            },
            complete: function() {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-plus-alt"></span> Load More');
            }
        });
    });
    
    function showError(message) {
        $('#mws-no-results').show().find('p').text(message);
    }
    
    
    // View details (handled by preview modal below)
    
    // Quick import with duplicate check
    $(document).on('click', '.mws-quick-import', function() {
        var $btn = $(this);
        var $card = $btn.closest('.mws-result-card');
        var index = $btn.data('index');
        var item = searchResults[index];
        
        if (!item) return;
        
        pendingImport = { btn: $btn, card: $card, item: item };
        
        $card.addClass('importing');
        $btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none;margin:0;"></span>');
        
        // First check for duplicates
        $.ajax({
            url: mwsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mws_check_duplicate',
                nonce: mwsData.nonce,
                title: item.title,
                slug: item.slug,
                url: item.url
            },
            success: function(dupResponse) {
                if (dupResponse.success && dupResponse.data.is_duplicate) {
                    // Show duplicate modal
                    showDuplicateModal(item, dupResponse.data);
                } else {
                    // No duplicate, proceed with import
                    proceedWithImport(item, $card, $btn, false, null);
                }
            },
            error: function() {
                // If check fails, proceed anyway
                proceedWithImport(item, $card, $btn, false, null);
            }
        });
    });
    
    // Show duplicate modal
    function showDuplicateModal(newItem, duplicateData) {
        var $modal = $('#mws-duplicate-modal');
        var existing = duplicateData.existing;
        
        // Set new item info
        $('.mws-dup-thumb-new').attr('src', newItem.thumbnail_url || '');
        $('.mws-dup-title-new').text(newItem.title);
        $('.mws-dup-source-new').text('Source: ' + (newItem.source_name || newItem.source));
        
        // Set existing item info
        $('.mws-dup-thumb-existing').attr('src', existing.thumbnail_url || '');
        $('.mws-dup-title-existing').text(existing.title);
        $('.mws-dup-chapters-existing').text(existing.total_chapters + ' chapters');
        $('.mws-dup-edit-link').attr('href', existing.edit_url || '#');
        
        // Set match info
        var matchTypeLabels = {
            'source_url': 'Same source URL',
            'slug': 'Same slug',
            'title_exact': 'Exact title match',
            'title_similar': 'Similar title'
        };
        $('.mws-match-type').text(matchTypeLabels[duplicateData.match_type] || duplicateData.match_type);
        $('.mws-match-confidence').text(duplicateData.confidence + '% match');
        
        // Set message
        $('.mws-duplicate-message').text(
            'A similar manhwa already exists in your library. What would you like to do?'
        );
        
        // Store reference  
        $modal.data('existing-id', existing.id);
        
        $modal.show();
    }
    
    // Hide modal
    $(document).on('click', '.mws-modal-close, .mws-modal', function(e) {
        if (e.target === this || $(e.target).hasClass('mws-modal-close')) {
            $('#mws-duplicate-modal').hide();
            resetPendingImport();
        }
    });
    
    // Skip button
    $(document).on('click', '.mws-dup-skip', function() {
        $('#mws-duplicate-modal').hide();
        if (pendingImport) {
            pendingImport.card.removeClass('importing');
            pendingImport.btn.prop('disabled', false).html('<span class="dashicons dashicons-download"></span> Import');
            showNotice('Import skipped: ' + pendingImport.item.title, 'warning');
        }
        resetPendingImport();
    });
    
    // Update existing button
    $(document).on('click', '.mws-dup-update', function() {
        var existingId = $('#mws-duplicate-modal').data('existing-id');
        $('#mws-duplicate-modal').hide();
        
        if (pendingImport) {
            proceedWithImport(pendingImport.item, pendingImport.card, pendingImport.btn, true, existingId);
        }
    });
    
    // Create new anyway button
    $(document).on('click', '.mws-dup-create', function() {
        $('#mws-duplicate-modal').hide();
        
        if (pendingImport) {
            proceedWithImport(pendingImport.item, pendingImport.card, pendingImport.btn, false, null);
        }
    });
    
    function resetPendingImport() {
        pendingImport = null;
        pendingScrapedData = null;
    }
    
    // Proceed with import
    function proceedWithImport(item, $card, $btn, isUpdate, existingId) {
        // First scrape full details
        $.ajax({
            url: mwsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mws_scrape_single',
                nonce: mwsData.nonce,
                url: item.url
            },
            success: function(scrapeResponse) {
                if (scrapeResponse.success) {
                    var importData = scrapeResponse.data.data;
                    
                    // Add update flag if updating
                    if (isUpdate && existingId) {
                        importData.update_existing = true;
                        importData.existing_id = existingId;
                    }
                    
                    // Now import
                    $.ajax({
                        url: mwsData.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'mws_import_manhwa',
                            nonce: mwsData.nonce,
                            data: JSON.stringify([importData]),
                            download_cover: 'true',
                            create_post: 'true'
                        },
                        success: function(importResponse) {
                            if (importResponse.success) {
                                $card.addClass('imported').removeClass('importing');
                                var actionText = isUpdate ? 'Updated' : 'Imported';
                                $btn.html('<span class="dashicons dashicons-yes"></span> ' + actionText);
                                showNotice('Successfully ' + actionText.toLowerCase() + ': ' + item.title, 'success');
                            } else {
                                $card.removeClass('importing');
                                $btn.prop('disabled', false).html('<span class="dashicons dashicons-download"></span> Import');
                                showNotice(importResponse.data.message || 'Import failed', 'error');
                            }
                        },
                        error: function() {
                            $card.removeClass('importing');
                            $btn.prop('disabled', false).html('<span class="dashicons dashicons-download"></span> Import');
                            showNotice('Import failed', 'error');
                        },
                        complete: function() {
                            resetPendingImport();
                        }
                    });
                } else {
                    $card.removeClass('importing');
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-download"></span> Import');
                    showNotice(scrapeResponse.data.message || 'Failed to scrape details', 'error');
                    resetPendingImport();
                }
            },
            error: function() {
                $card.removeClass('importing');
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-download"></span> Import');
                showNotice('Scrape failed', 'error');
                resetPendingImport();
            }
        });
    }
    
    // Checkbox selection
    $(document).on('change', '.mws-result-checkbox', function() {
        var $card = $(this).closest('.mws-result-card');
        $card.toggleClass('selected', $(this).is(':checked'));
        updateSelectedCount();
    });
    
    function updateSelectedCount() {
        var count = $('.mws-result-checkbox:checked').length;
        $('#mws-selected-count').text(count);
        
        if (count > 0) {
            $('#mws-import-selected-results').show();
        } else {
            $('#mws-import-selected-results').hide();
        }
    }
    
    // Import selected
    $('#mws-import-selected-results').on('click', function() {
        var selectedItems = [];
        $('.mws-result-checkbox:checked').each(function() {
            var index = $(this).data('index');
            if (searchResults[index]) {
                selectedItems.push({
                    index: index,
                    item: searchResults[index]
                });
            }
        });
        
        if (selectedItems.length === 0) return;
        
        if (!confirm('Import ' + selectedItems.length + ' manhwa? This will scrape full details for each.')) {
            return;
        }
        
        // Import one by one
        var $btn = $(this);
        $btn.prop('disabled', true).text('Importing...');
        
        importSelectedSequentially(selectedItems, 0, function() {
            $btn.prop('disabled', false).html('<span class="dashicons dashicons-download" style="margin-top: 4px;"></span> Import Selected (<span id="mws-selected-count">0</span>)');
            $('.mws-result-checkbox').prop('checked', false);
            $('.mws-result-card').removeClass('selected');
            updateSelectedCount();
        });
    });
    
    // ══════════════════════════════════════════
    // #9 + #4: FIXED Batch Import (Sequential with Progress)
    // ══════════════════════════════════════════
    function importSelectedSequentially(items, index, callback) {
        if (index >= items.length) {
            // Remove progress bar
            $('.mws-batch-progress').remove();
            callback();
            return;
        }
        
        var total = items.length;
        var pct = Math.round(((index) / total) * 100);
        
        // Create or update progress bar
        if (!$('.mws-batch-progress').length) {
            $('body').append(
                '<div class="mws-batch-progress">' +
                    '<div class="mws-batch-progress-text">Importing ' + (index + 1) + ' / ' + total + '</div>' +
                    '<div class="mws-batch-progress-sub"></div>' +
                    '<div class="mws-batch-progress-bar"><div class="mws-batch-progress-fill" style="width:' + pct + '%"></div></div>' +
                '</div>'
            );
        } else {
            $('.mws-batch-progress-text').text('Importing ' + (index + 1) + ' / ' + total);
            $('.mws-batch-progress-fill').css('width', pct + '%');
        }
        
        var item = items[index];
        var $card = $('.mws-result-card[data-index="' + item.index + '"]');
        var itemData = item.item;
        
        $('.mws-batch-progress-sub').text(itemData.title);
        
        // Import using direct AJAX (not trigger click — avoids race condition)
        $card.addClass('importing');
        
        $.ajax({
            url: mwsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mws_scrape_single',
                nonce: mwsData.nonce,
                url: itemData.url
            },
            success: function(scrapeResp) {
                if (scrapeResp.success) {
                    $.ajax({
                        url: mwsData.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'mws_import_manhwa',
                            nonce: mwsData.nonce,
                            data: JSON.stringify([scrapeResp.data.data]),
                            download_cover: 'true',
                            create_post: 'true'
                        },
                        success: function(impResp) {
                            $card.addClass('imported').removeClass('importing');
                            $card.find('.mws-quick-import').html('<span class="dashicons dashicons-yes"></span> Imported');
                            showToast('Imported: ' + itemData.title, 'success');
                        },
                        error: function() {
                            $card.removeClass('importing');
                            showToast('Failed: ' + itemData.title, 'error');
                        },
                        complete: function() {
                            importSelectedSequentially(items, index + 1, callback);
                        }
                    });
                } else {
                    $card.removeClass('importing');
                    showToast('Scrape failed: ' + itemData.title, 'error');
                    importSelectedSequentially(items, index + 1, callback);
                }
            },
            error: function() {
                $card.removeClass('importing');
                showToast('Network error: ' + itemData.title, 'error');
                importSelectedSequentially(items, index + 1, callback);
            }
        });
    }
    
    // ══════════════════════════════════════════
    // #9: Toast Notification System (replaces showNotice)
    // ══════════════════════════════════════════
    function showNotice(message, type) { showToast(message, type); }
    
    function showToast(message, type) {
        type = type || 'info';
        if (!$('.mws-toast-container').length) {
            $('body').append('<div class="mws-toast-container"></div>');
        }
        var icons = { success: 'yes-alt', error: 'warning', warning: 'flag', info: 'info-outline' };
        var $toast = $('<div class="mws-toast ' + type + '"><span class="dashicons dashicons-' + (icons[type] || 'info-outline') + '"></span>' + message + '</div>');
        $('.mws-toast-container').append($toast);
        
        $toast.on('click', function() {
            $(this).addClass('leaving');
            setTimeout(function() { $toast.remove(); }, 250);
        });
        
        setTimeout(function() {
            $toast.addClass('leaving');
            setTimeout(function() { $toast.remove(); }, 250);
        }, 4000);
    }
    
    // ══════════════════════════════════════════
    // #1: Detail Preview Modal
    // ══════════════════════════════════════════
    $(document).on('click', '.mws-view-details', function(e) {
        e.preventDefault();
        var url = $(this).data('url');
        if (!url) return;
        
        var $modal = $('#mws-preview-modal');
        $modal.show();
        $('.mws-preview-loading').show();
        $('.mws-preview-content').hide();
        $('.mws-preview-title-text').text('Loading...');
        
        // Scrape full details
        $.ajax({
            url: mwsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mws_scrape_single',
                nonce: mwsData.nonce,
                url: url
            },
            success: function(resp) {
                if (resp.success && resp.data.data) {
                    var d = resp.data.data;
                    
                    // Fill modal
                    $('.mws-preview-title-text').text(d.title || 'Detail');
                    $('.mws-preview-title').text(d.title || 'Untitled');
                    $('.mws-preview-cover').attr('src', d.thumbnail_url || '').on('error', function() {
                        $(this).attr('src', '').css('background', 'var(--gray-200)');
                    });
                    
                    // Meta badges
                    $('.mws-preview-status').text(d.status || 'Unknown').toggle(!!d.status);
                    $('.mws-preview-type').text(d.type || '').toggle(!!d.type);
                    if (d.rating) {
                        $('.mws-preview-rating').text('★ ' + d.rating).show();
                    } else {
                        $('.mws-preview-rating').hide();
                    }
                    
                    // Genres
                    var $genres = $('.mws-preview-genres').empty();
                    if (d.genres && d.genres.length) {
                        d.genres.forEach(function(g) {
                            var name = typeof g === 'object' ? (g.name || g.title || '') : g;
                            if (name) $genres.append('<span class="mws-genre-tag">' + name + '</span>');
                        });
                    }
                    
                    // Stats
                    var chCount = d.chapters ? d.chapters.length : 0;
                    $('.mws-preview-ch-count').text(chCount);
                    $('.mws-preview-author').text(d.author || d.artist || '-');
                    $('.mws-preview-source-name').text(d.source_name || d.source || '-');
                    
                    // Synopsis
                    $('.mws-preview-desc').html(d.description || d.synopsis || '<em>No synopsis available.</em>');
                    
                    // Chapters (latest 10)
                    var $chList = $('.mws-preview-chapters-list').empty();
                    if (d.chapters && d.chapters.length) {
                        var showChs = d.chapters.slice(0, 10);
                        showChs.forEach(function(ch) {
                            var title = ch.title || ch.name || 'Chapter';
                            $chList.append('<div class="mws-preview-ch-item"><span>' + title + '</span><span>' + (ch.date || '') + '</span></div>');
                        });
                    } else {
                        $chList.html('<div class="mws-preview-ch-item" style="color:var(--gray-400);"><em>No chapters data</em></div>');
                    }
                    
                    // Source link & import button
                    $('.mws-preview-source-link').attr('href', url);
                    $('.mws-preview-import-btn').data('url', url).data('title', d.title);
                    
                    $('.mws-preview-loading').hide();
                    $('.mws-preview-content').show();
                } else {
                    showToast('Failed to load details', 'error');
                    $modal.hide();
                }
            },
            error: function() {
                showToast('Network error loading details', 'error');
                $modal.hide();
            }
        });
    });
    
    // Close preview modal
    $(document).on('click', '#mws-preview-modal .mws-modal-close, #mws-preview-modal', function(e) {
        if (e.target === this || $(e.target).hasClass('mws-modal-close')) {
            $('#mws-preview-modal').hide();
        }
    });
    
    // Import from preview modal (with optional chapter scraping)
    $(document).on('click', '.mws-preview-import-btn', function() {
        var url = $(this).data('url');
        var title = $(this).data('title');
        var $btn = $(this);
        var scrapeChapters = $('#mws-scrape-ch-check').is(':checked');
        
        $btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none;margin:0;"></span> Importing...');
        
        $.ajax({
            url: mwsData.ajaxUrl,
            type: 'POST',
            data: { action: 'mws_scrape_single', nonce: mwsData.nonce, url: url },
            success: function(resp) {
                if (resp.success) {
                    var scrapedData = resp.data.data;
                    
                    $.ajax({
                        url: mwsData.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'mws_import_manhwa',
                            nonce: mwsData.nonce,
                            data: JSON.stringify([scrapedData]),
                            download_cover: 'true',
                            create_post: 'true'
                        },
                        success: function(impResp) {
                            if (impResp.success) {
                                $btn.html('<span class="dashicons dashicons-yes" style="margin-top:4px;"></span> Imported').addClass('button-success');
                                showToast('Imported: ' + title, 'success');
                                
                                // Start chapter scraping if checkbox is checked
                                if (scrapeChapters && scrapedData.chapters && scrapedData.chapters.length > 0) {
                                    // Get post_id from import result
                                    var postId = (impResp.data.result && impResp.data.result.posts && impResp.data.result.posts[0]) 
                                        ? impResp.data.result.posts[0].id : null;
                                    startChapterScraping(scrapedData.chapters, title, postId);
                                } else {
                                    $('#mws-preview-modal').hide();
                                }
                            } else {
                                $btn.prop('disabled', false).html('<span class="dashicons dashicons-download" style="margin-top:4px;"></span> Import');
                                showToast(impResp.data.message || 'Import failed', 'error');
                            }
                        },
                        error: function() {
                            $btn.prop('disabled', false).html('<span class="dashicons dashicons-download" style="margin-top:4px;"></span> Import');
                            showToast('Import failed', 'error');
                        }
                    });
                } else {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-download" style="margin-top:4px;"></span> Import');
                    showToast(resp.data.message || 'Scrape failed', 'error');
                }
            },
            error: function() {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-download" style="margin-top:4px;"></span> Import');
                showToast('Network error', 'error');
            }
        });
    });
    
    // ══════════════════════════════════════════
    // Chapter Scraping in Preview Modal
    // ══════════════════════════════════════════
    function startChapterScraping(chapters, manhwaTitle, postId) {
        var BATCH_SIZE = 5; // scrape 5 chapters at a time
        var $progress = $('#mws-ch-scrape-progress');
        var $log = $('#mws-ch-log');
        var $fill = $('#mws-ch-fill');
        var $counter = $('#mws-ch-counter');
        var $current = $('#mws-ch-current');
        var total = chapters.length;
        var completed = 0;
        var successCount = 0;
        var failCount = 0;
        
        // Show progress, hide footer actions
        $progress.show();
        $('.mws-preview-footer').hide();
        $log.html('');
        $fill.css('width', '0%');
        $counter.text('0 / ' + total);
        
        addChLog('Starting chapter image scraping for: ' + manhwaTitle, 'info');
        addChLog('Mode: Parallel (' + BATCH_SIZE + ' at a time) — Total: ' + total + ' chapters', 'info');
        if (postId) {
            addChLog('Post ID: ' + postId + ' — images will be saved automatically', 'info');
        } else {
            addChLog('⚠ No post ID — images will only be scraped, not saved', 'warning');
        }
        addChLog('', '');
        
        function processBatch(startIndex) {
            if (startIndex >= total) {
                // All done
                $fill.css('width', '100%');
                $counter.text(total + ' / ' + total);
                $current.text('Completed!');
                addChLog('', '');
                addChLog('✅ Done! ' + successCount + ' success, ' + failCount + ' failed out of ' + total + ' chapters.', 'success');
                showToast('Chapter scraping completed: ' + successCount + '/' + total, successCount > 0 ? 'success' : 'warning');
                
                $progress.append('<div style="text-align:center;margin-top:12px;"><button type="button" class="button" onclick="jQuery(\'#mws-preview-modal\').hide();jQuery(\'#mws-ch-scrape-progress\').hide();jQuery(\'.mws-preview-footer\').show();"><span class="dashicons dashicons-yes" style="margin-top:4px;"></span> Close</button></div>');
                return;
            }
            
            var endIndex = Math.min(startIndex + BATCH_SIZE, total);
            var batchNum = Math.floor(startIndex / BATCH_SIZE) + 1;
            var totalBatches = Math.ceil(total / BATCH_SIZE);
            $current.text('Batch ' + batchNum + '/' + totalBatches + ' — scraping chapters ' + (startIndex + 1) + '-' + endIndex);
            addChLog('── Batch ' + batchNum + '/' + totalBatches + ' (' + (endIndex - startIndex) + ' chapters) ──', 'info');
            
            var batchPromises = [];
            
            for (var i = startIndex; i < endIndex; i++) {
                (function(idx) {
                    var ch = chapters[idx];
                    var chTitle = ch.title || ch.name || ('Chapter ' + (idx + 1));
                    var chUrl = ch.url;
                    
                    if (!chUrl) {
                        addChLog('⚠ ' + chTitle + ' — no URL, skipped', 'warning');
                        failCount++;
                        completed++;
                        updateProgress();
                        batchPromises.push($.Deferred().resolve().promise());
                        return;
                    }
                    
                    var deferred = $.Deferred();
                    batchPromises.push(deferred.promise());
                    
                    $.ajax({
                        url: mwsData.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'mws_scrape_chapter_images',
                            nonce: mwsData.nonce,
                            url: chUrl
                        },
                        success: function(r) {
                            if (r.success && r.data && r.data.data) {
                                var chData = r.data.data;
                                var imgCount = chData.images ? chData.images.length : (chData.total_images || 0);
                                addChLog('✓ ' + chTitle + ' — ' + imgCount + ' images', 'success');
                                successCount++;
                                
                                // Save to post
                                if (postId && imgCount > 0) {
                                    $.ajax({
                                        url: mwsData.ajaxUrl,
                                        type: 'POST',
                                        data: {
                                            action: 'mws_save_chapter_to_post',
                                            nonce: mwsData.nonce,
                                            post_id: postId,
                                            chapter_data: JSON.stringify(chData)
                                        },
                                        success: function(sr) {
                                            if (sr.success) {
                                                addChLog('  💾 ' + chTitle + ' saved', 'info');
                                            }
                                        },
                                        complete: function() { deferred.resolve(); }
                                    });
                                } else {
                                    deferred.resolve();
                                }
                            } else {
                                addChLog('✗ ' + chTitle + ' — ' + (r.data ? r.data.message : 'failed'), 'error');
                                failCount++;
                                deferred.resolve();
                            }
                            completed++;
                            updateProgress();
                        },
                        error: function() {
                            addChLog('✗ ' + chTitle + ' — request failed', 'error');
                            failCount++;
                            completed++;
                            updateProgress();
                            deferred.resolve();
                        }
                    });
                })(i);
            }
            
            // Wait for all in batch to complete, then next batch
            $.when.apply($, batchPromises).then(function() {
                setTimeout(function() { processBatch(endIndex); }, 300);
            });
        }
        
        function updateProgress() {
            var pct = Math.round((completed / total) * 100);
            $fill.css('width', pct + '%');
            $counter.text(completed + ' / ' + total);
        }
        
        function addChLog(msg, type) {
            var color = '#94A3B8';
            if (type === 'success') color = '#10b981';
            else if (type === 'error') color = '#ef4444';
            else if (type === 'warning') color = '#f59e0b';
            else if (type === 'info') color = '#8b5cf6';
            
            var time = new Date().toLocaleTimeString();
            $log.append('<div style="color:' + color + '">[' + time + '] ' + msg + '</div>');
            $log.scrollTop($log[0].scrollHeight);
        }
        
        // Start processing
        processBatch(0);
    }
    
    // ══════════════════════════════════════════
    // #2: Search History (localStorage)
    // ══════════════════════════════════════════
    var HISTORY_KEY = 'mws_search_history';
    
    function getSearchHistory() {
        try { return JSON.parse(localStorage.getItem(HISTORY_KEY)) || []; } catch(e) { return []; }
    }
    
    function saveSearchHistory(keyword) {
        if (!keyword || keyword.length < 2) return;
        var history = getSearchHistory();
        history = history.filter(function(h) { return h !== keyword; });
        history.unshift(keyword);
        history = history.slice(0, 10);
        localStorage.setItem(HISTORY_KEY, JSON.stringify(history));
    }
    
    function showSearchHistory() {
        var history = getSearchHistory();
        if (history.length === 0) { $('#mws-search-history').hide(); return; }
        
        var $list = $('#mws-history-list').empty();
        history.forEach(function(h) {
            $list.append('<li data-keyword="' + h.replace(/"/g, '&quot;') + '"><span class="dashicons dashicons-clock"></span>' + h + '</li>');
        });
        $('#mws-search-history').show();
    }
    
    $('#mws-search-keyword').on('focus', function() {
        if (!$(this).val()) showSearchHistory();
    }).on('input', function() {
        if (!$(this).val()) showSearchHistory();
        else $('#mws-search-history').hide();
    });
    
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.mws-search-input-wrap, #mws-search-history').length) {
            $('#mws-search-history').hide();
        }
    });
    
    $(document).on('click', '#mws-history-list li', function() {
        var keyword = $(this).data('keyword');
        $('#mws-search-keyword').val(keyword);
        $('#mws-search-history').hide();
        $('#mws-search-form').trigger('submit');
    });
    
    $('#mws-clear-history').on('click', function() {
        localStorage.removeItem(HISTORY_KEY);
        $('#mws-search-history').hide();
    });
    
    // Save search keyword when searching
    var origPerformSearch = performSearch;
    performSearch = function(keyword, source, filters, page) {
        if (keyword && page === 1) saveSearchHistory(keyword);
        origPerformSearch(keyword, source, filters, page);
    };
    
    // ══════════════════════════════════════════
    // #5: Cover Image Fallback (onerror)
    // ══════════════════════════════════════════
    $(document).on('error', '.mws-result-thumbnail img', function() {
        $(this).replaceWith('<div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:48px;opacity:0.3;">📚</div>');
    });
    
    // ══════════════════════════════════════════
    // #6: Keyboard Shortcuts
    // ══════════════════════════════════════════
    $(document).on('keydown', function(e) {
        // Esc → close modals
        if (e.key === 'Escape') {
            if ($('#mws-preview-modal').is(':visible')) {
                $('#mws-preview-modal').hide();
                e.preventDefault();
            }
            if ($('#mws-duplicate-modal').is(':visible')) {
                $('#mws-duplicate-modal').hide();
                resetPendingImport();
                e.preventDefault();
            }
        }
        
        // Ctrl+A → select all visible results (only when not focused on input)
        if ((e.ctrlKey || e.metaKey) && e.key === 'a' && !$(e.target).is('input, textarea, select')) {
            var $checkboxes = $('.mws-result-checkbox:not(:disabled)');
            if ($checkboxes.length) {
                e.preventDefault();
                var allChecked = $checkboxes.filter(':checked').length === $checkboxes.length;
                $checkboxes.prop('checked', !allChecked).trigger('change');
            }
        }
    });
    
    // ══════════════════════════════════════════
    // #8: Skeleton Loading
    // ══════════════════════════════════════════
    function showSkeletonCards(count) {
        var $grid = $('#mws-results-grid');
        $grid.empty();
        for (var i = 0; i < (count || 12); i++) {
            $grid.append(
                '<div class="mws-skeleton-card">' +
                    '<div class="mws-skeleton-thumb"></div>' +
                    '<div class="mws-skeleton-line"></div>' +
                    '<div class="mws-skeleton-line short"></div>' +
                    '<div class="mws-skeleton-line btn"></div>' +
                '</div>'
            );
        }
    }
    
    // ══════════════════════════════════════════
    // #7: Enhanced result card with metadata
    // ══════════════════════════════════════════
    var origDisplayResults = displayResults;
    displayResults = function(results) {
        var $grid = $('#mws-results-grid');
        $grid.empty();
        
        results.forEach(function(item, i) {
            var thumbnail = item.thumbnail_url || '';
            var thumbnailHtml = thumbnail
                ? '<img src="' + thumbnail + '" alt="' + (item.title || '').replace(/"/g, '&quot;') + '" loading="lazy" onerror="this.outerHTML=\'<div style=&quot;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:48px;opacity:0.3;&quot;>📚</div>\'">'
                : '<div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:48px;opacity:0.3;">📚</div>';
            
            // Build metadata tags
            var metaHtml = '<div class="mws-result-meta">';
            if (item.status) {
                var statusClass = (item.status || '').toLowerCase();
                metaHtml += '<span class="mws-result-meta-tag mws-meta-status ' + statusClass + '">' + item.status + '</span>';
            }
            if (item.chapters && item.chapters.length) {
                metaHtml += '<span class="mws-result-meta-tag mws-meta-chapters">' + item.chapters.length + ' ch</span>';
            } else if (item.total_chapters) {
                metaHtml += '<span class="mws-result-meta-tag mws-meta-chapters">' + item.total_chapters + ' ch</span>';
            }
            if (item.rating) {
                metaHtml += '<span class="mws-result-meta-tag mws-meta-rating">★ ' + item.rating + '</span>';
            }
            metaHtml += '</div>';
            
            var existsBadge = '';
            var actionButtons = '';
            
            if (item.exists_in_db) {
                existsBadge = '<span class="mws-exists-badge">✓ Already Exists</span>';
                actionButtons =
                    '<a href="' + (item.existing_view_url || '#') + '" target="_blank" class="button mws-view-existing">' +
                        '<span class="dashicons dashicons-visibility"></span> View' +
                    '</a>' +
                    '<a href="' + (item.existing_edit_url || '#') + '" target="_blank" class="button button-primary mws-edit-existing">' +
                        '<span class="dashicons dashicons-edit"></span> Edit' +
                    '</a>';
            } else {
                actionButtons =
                    '<button type="button" class="button mws-view-details" data-index="' + i + '" data-url="' + (item.url || '') + '">' +
                        '<span class="dashicons dashicons-visibility"></span> View' +
                    '</button>' +
                    '<button type="button" class="button button-primary mws-quick-import" data-index="' + i + '">' +
                        '<span class="dashicons dashicons-download"></span> Import' +
                    '</button>';
            }
            
            var card = $(
                '<div class="mws-result-card' + (item.exists_in_db ? ' mws-exists' : '') + '" data-index="' + i + '">' +
                    '<input type="checkbox" class="mws-result-checkbox" data-index="' + i + '"' + (item.exists_in_db ? ' disabled' : '') + '>' +
                    '<div class="mws-result-thumbnail">' +
                        thumbnailHtml +
                        '<span class="mws-result-source">' + (item.source_name || item.source || '') + '</span>' +
                        existsBadge +
                    '</div>' +
                    '<div class="mws-result-content">' +
                        '<div class="mws-result-title" title="' + (item.title || '').replace(/"/g, '&quot;') + '">' + (item.title || 'Untitled') + '</div>' +
                        metaHtml +
                        '<div class="mws-result-actions">' +
                            actionButtons +
                        '</div>' +
                    '</div>' +
                '</div>'
            );
            
            $grid.append(card);
        });
    };
    
    // ══════════════════════════════════════════
    // #3 + #8: Show skeleton when searching, fix total_pages
    // ══════════════════════════════════════════
    var origFormSubmit = $('#mws-search-form').data('events');
    // Override the search loading to show skeleton
    $(document).ajaxSend(function(e, xhr, settings) {
        if (settings.data && typeof settings.data === 'string' && settings.data.indexOf('mws_search_manhwa') !== -1 && settings.data.indexOf('page=1') !== -1) {
            showSkeletonCards(12);
        }
    });
    
});
</script>
