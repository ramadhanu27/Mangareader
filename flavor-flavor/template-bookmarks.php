<?php
/**
 * Bookmarks Page Template
 *
 * Template Name: Bookmarks
 * 
 * Displays user's bookmarked manga from localStorage
 *
 * @package Flavor_Flavor
 */

get_header();
?>

<div class="container">
    <div class="bookmarks-page">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"></path>
                </svg>
                <?php esc_html_e('My Bookmarks', 'flavor-flavor'); ?>
            </h1>
            <p class="page-desc"><?php esc_html_e('Manga yang kamu simpan untuk dibaca nanti', 'flavor-flavor'); ?></p>
        </div>

        <!-- Bookmark Actions -->
        <div class="bookmark-actions">
            <button type="button" id="clearAllBookmarks" class="btn btn-danger">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 6h18"></path>
                    <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                </svg>
                <?php esc_html_e('Hapus Semua', 'flavor-flavor'); ?>
            </button>
        </div>

        <!-- Bookmarks Container -->
        <div id="bookmarksContainer" class="manga-grid">
            <!-- Will be populated by JavaScript -->
        </div>

        <!-- Empty State -->
        <div id="emptyBookmarks" class="empty-state" style="display: none;">
            <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"></path>
            </svg>
            <h3><?php esc_html_e('Belum ada bookmark', 'flavor-flavor'); ?></h3>
            <p><?php esc_html_e('Kamu belum menyimpan manga apapun. Klik tombol bookmark di halaman manga untuk menyimpannya.', 'flavor-flavor'); ?></p>
            <a href="<?php echo esc_url(get_post_type_archive_link('manhwa')); ?>" class="btn btn-primary">
                <?php esc_html_e('Jelajahi Manga', 'flavor-flavor'); ?>
            </a>
        </div>
    </div>
</div>

<style>
.bookmarks-page {
    padding: 30px 0;
}

.page-header {
    text-align: center;
    margin-bottom: 30px;
}

.page-title {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-size: 28px;
    font-weight: 700;
    color: var(--text-color);
    margin: 0 0 10px;
}

.page-title svg {
    color: var(--primary-color);
}

.page-desc {
    color: var(--text-muted);
    margin: 0;
}

.bookmark-actions {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 20px;
    gap: 10px;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 20px;
    border: none;
    border-radius: var(--border-radius);
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background: #e64a19;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:hover {
    background: #c82333;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: var(--card-bg);
    border-radius: var(--border-radius);
    border: 1px solid var(--border-color);
}

.empty-state svg {
    color: var(--text-muted);
    opacity: 0.5;
    margin-bottom: 20px;
}

.empty-state h3 {
    font-size: 20px;
    margin: 0 0 10px;
    color: var(--text-color);
}

.empty-state p {
    color: var(--text-muted);
    margin: 0 0 20px;
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
}

/* Bookmark Item */
.bookmark-item {
    position: relative;
}

.bookmark-item .remove-bookmark {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 30px;
    height: 30px;
    background: rgba(220, 53, 69, 0.9);
    border: none;
    border-radius: 50%;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.2s;
    z-index: 5;
}

.bookmark-item:hover .remove-bookmark {
    opacity: 1;
}

.bookmark-item .remove-bookmark:hover {
    background: #c82333;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('bookmarksContainer');
    const emptyState = document.getElementById('emptyBookmarks');
    const clearAllBtn = document.getElementById('clearAllBookmarks');
    
    // Load bookmarks from localStorage
    function loadBookmarks() {
        const bookmarks = JSON.parse(localStorage.getItem('flavor_bookmarks') || '[]');
        
        if (bookmarks.length === 0) {
            container.style.display = 'none';
            emptyState.style.display = 'block';
            clearAllBtn.style.display = 'none';
            return;
        }
        
        container.style.display = 'grid';
        emptyState.style.display = 'none';
        clearAllBtn.style.display = 'inline-flex';
        
        container.innerHTML = bookmarks.map(function(manga, index) {
            return `
                <article class="manga-item bookmark-item" data-index="${index}">
                    <div class="manga-thumb">
                        <a href="${manga.url}">
                            <img src="${manga.image || '<?php echo get_template_directory_uri(); ?>/assets/images/no-image.svg'}" alt="${manga.title}">
                        </a>
                        <button type="button" class="remove-bookmark" data-index="${index}" title="<?php esc_attr_e('Hapus Bookmark', 'flavor-flavor'); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 6 6 18"></path>
                                <path d="m6 6 12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="manga-info">
                        <h3 class="manga-title">
                            <a href="${manga.url}">${manga.title}</a>
                        </h3>
                    </div>
                </article>
            `;
        }).join('');
        
        // Add remove bookmark event listeners
        document.querySelectorAll('.remove-bookmark').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const index = parseInt(this.getAttribute('data-index'));
                removeBookmark(index);
            });
        });
    }
    
    // Remove single bookmark
    function removeBookmark(index) {
        const bookmarks = JSON.parse(localStorage.getItem('flavor_bookmarks') || '[]');
        bookmarks.splice(index, 1);
        localStorage.setItem('flavor_bookmarks', JSON.stringify(bookmarks));
        updateBookmarkCount();
        loadBookmarks();
    }
    
    // Clear all bookmarks
    clearAllBtn.addEventListener('click', function() {
        if (confirm('<?php esc_attr_e('Apakah kamu yakin ingin menghapus semua bookmark?', 'flavor-flavor'); ?>')) {
            localStorage.removeItem('flavor_bookmarks');
            updateBookmarkCount();
            loadBookmarks();
        }
    });
    
    // Update bookmark count in header
    function updateBookmarkCount() {
        const bookmarks = JSON.parse(localStorage.getItem('flavor_bookmarks') || '[]');
        const countEl = document.getElementById('bookmarkCount');
        if (countEl) {
            countEl.textContent = bookmarks.length;
        }
    }
    
    // Initial load
    loadBookmarks();
});
</script>

<?php get_footer(); ?>
