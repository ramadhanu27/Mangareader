<?php
/**
 * Template Name: Profile Page
 * User profile with bookmarks
 */

if (!defined('ABSPATH')) exit;

// Redirect to home if not logged in
if (!is_user_logged_in()) {
    wp_redirect(home_url('/'));
    exit;
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$bookmarks = flavor_get_bookmark_data($user_id);
$bookmark_count = count(flavor_get_user_bookmark_ids($user_id));
$member_since = date_i18n('d F Y', strtotime($current_user->user_registered));
$avatar_url = get_avatar_url($user_id, array('size' => 120));

// Reading history
$reading_history = get_user_meta($user_id, 'reading_history', true);
if (!is_array($reading_history)) $reading_history = array();
$history_count = count($reading_history);

// Helper: time ago
function flavor_profile_time_ago($timestamp) {
    $diff = time() - intval($timestamp);
    if ($diff < 60) return 'baru saja';
    if ($diff < 3600) return floor($diff / 60) . ' menit lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
    if ($diff < 604800) return floor($diff / 86400) . ' hari lalu';
    if ($diff < 2592000) return floor($diff / 604800) . ' minggu lalu';
    return floor($diff / 2592000) . ' bulan lalu';
}

// Handle profile update
$profile_message = '';
if (isset($_POST['flavor_update_profile']) && wp_verify_nonce($_POST['profile_nonce'], 'flavor_profile_update')) {
    $display_name = sanitize_text_field($_POST['display_name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';
    
    $errors = array();
    
    if (!empty($display_name)) {
        wp_update_user(array('ID' => $user_id, 'display_name' => $display_name));
        $current_user->display_name = $display_name;
    }
    
    if (!empty($email) && is_email($email)) {
        if ($email !== $current_user->user_email) {
            if (email_exists($email)) {
                $errors[] = 'Email sudah digunakan user lain.';
            } else {
                wp_update_user(array('ID' => $user_id, 'user_email' => $email));
                $current_user->user_email = $email;
            }
        }
    }
    
    if (!empty($new_pass)) {
        if (strlen($new_pass) < 6) {
            $errors[] = 'Password minimal 6 karakter.';
        } elseif ($new_pass !== $confirm_pass) {
            $errors[] = 'Konfirmasi password tidak cocok.';
        } else {
            wp_set_password($new_pass, $user_id);
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id, true);
        }
    }
    
    if (empty($errors)) {
        $profile_message = '<div class="profile-alert profile-alert-success">Profil berhasil diperbarui!</div>';
    } else {
        $profile_message = '<div class="profile-alert profile-alert-error">' . implode('<br>', $errors) . '</div>';
    }
}

get_header();
?>

<div class="profile-page">
    <div class="container">
        
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar-wrap">
                <img src="<?php echo esc_url($avatar_url); ?>" alt="Avatar" class="profile-avatar">
            </div>
            <div class="profile-info">
                <h1 class="profile-name"><?php echo esc_html($current_user->display_name); ?></h1>
                <p class="profile-email"><?php echo esc_html($current_user->user_email); ?></p>
                <div class="profile-meta">
                    <span>📅 Member sejak <?php echo $member_since; ?></span>
                    <span>📚 <?php echo $bookmark_count; ?> Bookmark</span>
                    <span>📖 <?php echo $history_count; ?> Riwayat</span>
                </div>
            </div>
        </div>

        <?php echo $profile_message; ?>

        <!-- Profile Tabs -->
        <div class="profile-tabs">
            <button type="button" class="profile-tab active" data-target="profileBookmarks">📚 Bookmark</button>
            <button type="button" class="profile-tab" data-target="profileHistory">📖 History</button>
            <button type="button" class="profile-tab" data-target="profileSettings">⚙ Pengaturan</button>
        </div>

        <!-- Bookmarks Tab -->
        <div class="profile-tab-content active" id="profileBookmarks">
            <?php if (empty($bookmarks)): ?>
                <div class="profile-empty">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" opacity="0.4">
                        <path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"></path>
                    </svg>
                    <h3>Belum ada bookmark</h3>
                    <p>Klik tombol bookmark di halaman manga untuk menyimpannya.</p>
                    <a href="<?php echo esc_url(get_post_type_archive_link('manhwa')); ?>" class="profile-browse-btn">Jelajahi Manga →</a>
                </div>
            <?php else: ?>
                <div class="profile-bookmarks-grid">
                    <?php foreach ($bookmarks as $manga): ?>
                    <div class="profile-bookmark-card" data-id="<?php echo esc_attr($manga['id']); ?>">
                        <a href="<?php echo esc_url($manga['url']); ?>" class="profile-bookmark-cover">
                            <img src="<?php echo esc_url($manga['image']); ?>" alt="<?php echo esc_attr($manga['title']); ?>" loading="lazy"
                                 onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 140%22><rect fill=%22%23333%22 width=%22100%22 height=%22140%22/><text x=%2250%22 y=%2270%22 text-anchor=%22middle%22 fill=%22%23666%22 font-size=%2212%22>No Image</text></svg>'">
                        </a>
                        <div class="profile-bookmark-info">
                            <a href="<?php echo esc_url($manga['url']); ?>" class="profile-bookmark-title"><?php echo esc_html($manga['title']); ?></a>
                            <?php if (!empty($manga['latest_ch'])): ?>
                            <span class="profile-bookmark-chapter"><?php echo esc_html($manga['latest_ch']); ?></span>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="profile-bookmark-remove" data-id="<?php echo esc_attr($manga['id']); ?>" title="Hapus bookmark">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
                            </svg>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- History Tab -->
        <div class="profile-tab-content" id="profileHistory">
            <?php if (empty($reading_history)): ?>
                <div class="profile-empty">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" opacity="0.4">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <h3>Belum ada riwayat bacaan</h3>
                    <p>Mulai baca manga untuk menyimpan riwayat.</p>
                    <a href="<?php echo esc_url(get_post_type_archive_link('manhwa')); ?>" class="profile-browse-btn">Jelajahi Manga →</a>
                </div>
            <?php else: ?>
                <div class="profile-history-list">
                    <?php foreach ($reading_history as $item): ?>
                    <a href="<?php echo esc_url($item['url'] ?? '#'); ?>" class="profile-history-item">
                        <div class="profile-history-thumb">
                            <?php if (!empty($item['thumbnail'])): ?>
                                <img src="<?php echo esc_url($item['thumbnail']); ?>" alt="<?php echo esc_attr($item['manhwa_title'] ?? ''); ?>" loading="lazy">
                            <?php else: ?>
                                <div class="profile-history-no-img">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="profile-history-info">
                            <span class="profile-history-title"><?php echo esc_html($item['manhwa_title'] ?? 'Unknown'); ?></span>
                            <span class="profile-history-chapter"><?php echo esc_html($item['chapter'] ?? ''); ?></span>
                        </div>
                        <span class="profile-history-time"><?php echo esc_html(flavor_profile_time_ago($item['timestamp'] ?? 0)); ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Settings Tab -->
        <div class="profile-tab-content" id="profileSettings">
            <form method="POST" class="profile-settings-form">
                <?php wp_nonce_field('flavor_profile_update', 'profile_nonce'); ?>
                <input type="hidden" name="flavor_update_profile" value="1">
                
                <div class="profile-field">
                    <label>Username</label>
                    <input type="text" value="<?php echo esc_attr($current_user->user_login); ?>" disabled>
                    <small>Username tidak dapat diubah.</small>
                </div>
                
                <div class="profile-field">
                    <label for="profileDisplayName">Nama Tampilan</label>
                    <input type="text" id="profileDisplayName" name="display_name" value="<?php echo esc_attr($current_user->display_name); ?>" maxlength="50">
                </div>
                
                <div class="profile-field">
                    <label for="profileEmail">Email</label>
                    <input type="email" id="profileEmail" name="email" value="<?php echo esc_attr($current_user->user_email); ?>">
                </div>
                
                <hr class="profile-divider">
                <h3 class="profile-section-title">Ubah Password</h3>
                <p class="profile-section-desc">Kosongkan jika tidak ingin mengubah password.</p>
                
                <div class="profile-field">
                    <label for="profileNewPass">Password Baru</label>
                    <input type="password" id="profileNewPass" name="new_password" placeholder="Min. 6 karakter" autocomplete="new-password">
                </div>
                
                <div class="profile-field">
                    <label for="profileConfirmPass">Konfirmasi Password</label>
                    <input type="password" id="profileConfirmPass" name="confirm_password" placeholder="Ulangi password baru" autocomplete="new-password">
                </div>
                
                <button type="submit" class="profile-save-btn">Simpan Perubahan</button>
            </form>
        </div>

    </div>
</div>

<style>
/* =========================================
   PROFILE PAGE
   ========================================= */
.profile-page {
    padding: 30px 0 60px;
    min-height: 60vh;
}

.profile-page .container {
    max-width: 900px;
}

/* Header */
.profile-header {
    display: flex;
    align-items: center;
    gap: 24px;
    padding: 28px;
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 14px;
    margin-bottom: 24px;
}

.profile-avatar {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--primary-color);
}

.profile-name {
    font-size: 22px;
    font-weight: 700;
    margin: 0 0 4px;
    color: var(--text-color);
}

.profile-email {
    font-size: 14px;
    color: var(--text-muted);
    margin: 0 0 8px;
}

.profile-meta {
    display: flex;
    gap: 16px;
    font-size: 13px;
    color: var(--text-muted);
}

/* Alerts */
.profile-alert {
    padding: 12px 16px;
    border-radius: 8px;
    font-size: 14px;
    margin-bottom: 20px;
    font-weight: 500;
}

.profile-alert-success {
    background: rgba(46, 204, 113, 0.12);
    color: #2ecc71;
    border: 1px solid rgba(46, 204, 113, 0.25);
}

.profile-alert-error {
    background: rgba(231, 76, 60, 0.12);
    color: #e74c3c;
    border: 1px solid rgba(231, 76, 60, 0.25);
}

/* Tabs */
.profile-tabs {
    display: flex;
    gap: 0;
    border-bottom: 2px solid var(--border-color);
    margin-bottom: 24px;
}

.profile-tab {
    padding: 12px 20px;
    background: none;
    border: none;
    font-size: 14px;
    font-weight: 600;
    color: var(--text-muted);
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s;
}

.profile-tab:hover {
    color: var(--text-color);
}

.profile-tab.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
}

/* Tab Content */
.profile-tab-content {
    display: none;
}

.profile-tab-content.active {
    display: block;
}

/* Empty State */
.profile-empty {
    text-align: center;
    padding: 50px 20px;
    color: var(--text-muted);
}

.profile-empty h3 {
    font-size: 18px;
    margin: 16px 0 6px;
    color: var(--text-color);
}

.profile-empty p {
    font-size: 14px;
    margin: 0 0 20px;
}

.profile-browse-btn {
    display: inline-block;
    padding: 10px 24px;
    background: var(--primary-color);
    color: #fff;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: opacity 0.2s;
}

.profile-browse-btn:hover {
    opacity: 0.9;
    color: #fff;
}

/* Bookmarks Grid */
.profile-bookmarks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 16px;
}

.profile-bookmark-card {
    position: relative;
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    overflow: hidden;
    transition: transform 0.2s;
}

.profile-bookmark-card:hover {
    transform: translateY(-3px);
}

.profile-bookmark-cover {
    display: block;
    aspect-ratio: 3/4;
    overflow: hidden;
}

.profile-bookmark-cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.profile-bookmark-card:hover .profile-bookmark-cover img {
    transform: scale(1.05);
}

.profile-bookmark-info {
    padding: 8px 10px;
}

.profile-bookmark-title {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-color);
    text-decoration: none;
    line-height: 1.3;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.profile-bookmark-title:hover {
    color: var(--primary-color);
}

.profile-bookmark-chapter {
    display: block;
    font-size: 11px;
    color: var(--text-muted);
    margin-top: 2px;
}

.profile-bookmark-remove {
    position: absolute;
    top: 6px;
    right: 6px;
    background: rgba(0,0,0,0.7);
    border: none;
    color: #fff;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.2s;
}

.profile-bookmark-card:hover .profile-bookmark-remove {
    opacity: 1;
}

.profile-bookmark-remove:hover {
    background: #e74c3c;
}

/* Settings Form */
.profile-settings-form {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: 28px;
}

.profile-field {
    margin-bottom: 18px;
}

.profile-field label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-color);
    margin-bottom: 6px;
}

.profile-field input {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: var(--body-bg);
    color: var(--text-color);
    font-size: 14px;
    transition: border-color 0.2s;
    outline: none;
    box-sizing: border-box;
}

.profile-field input:focus {
    border-color: var(--primary-color);
}

.profile-field input:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.profile-field small {
    display: block;
    font-size: 11px;
    color: var(--text-muted);
    margin-top: 4px;
}

.profile-divider {
    border: none;
    border-top: 1px solid var(--border-color);
    margin: 24px 0 16px;
}

.profile-section-title {
    font-size: 16px;
    font-weight: 700;
    color: var(--text-color);
    margin: 0 0 4px;
}

.profile-section-desc {
    font-size: 13px;
    color: var(--text-muted);
    margin: 0 0 16px;
}

.profile-save-btn {
    padding: 12px 28px;
    border: none;
    border-radius: 8px;
    background: var(--primary-color);
    color: #fff;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.profile-save-btn:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

/* History List */
.profile-history-list {
    display: flex;
    flex-direction: column;
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 14px;
    overflow: hidden;
}

.profile-history-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 16px;
    text-decoration: none;
    border-bottom: 1px solid var(--border-color);
    transition: background 0.15s;
}

.profile-history-item:last-child {
    border-bottom: none;
}

.profile-history-item:hover {
    background: var(--body-bg);
}

.profile-history-thumb {
    flex-shrink: 0;
    width: 48px;
    height: 64px;
    border-radius: 6px;
    overflow: hidden;
    background: var(--body-bg);
}

.profile-history-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-history-no-img {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
    background: var(--body-bg);
}

.profile-history-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.profile-history-title {
    font-size: 14px;
    font-weight: 600;
    color: var(--text-color);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.profile-history-item:hover .profile-history-title {
    color: var(--primary-color);
}

.profile-history-chapter {
    font-size: 12px;
    color: var(--primary-color);
    font-weight: 500;
}

.profile-history-time {
    flex-shrink: 0;
    font-size: 11px;
    color: var(--text-muted);
    white-space: nowrap;
}

/* Mobile */
@media (max-width: 640px) {
    .profile-header {
        flex-direction: column;
        text-align: center;
    }
    
    .profile-meta {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .profile-bookmarks-grid {
        grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
        gap: 10px;
    }
    
    .profile-settings-form {
        padding: 18px;
    }
    
    .profile-bookmark-remove {
        opacity: 1;
    }

    .profile-history-item {
        gap: 10px;
        padding: 10px 12px;
    }

    .profile-history-thumb {
        width: 40px;
        height: 54px;
    }

    .profile-history-title {
        font-size: 13px;
    }

    .profile-history-time {
        font-size: 10px;
    }

    .profile-tabs {
        overflow-x: auto;
        scrollbar-width: none;
    }

    .profile-tabs::-webkit-scrollbar {
        display: none;
    }

    .profile-tab {
        white-space: nowrap;
        font-size: 13px;
        padding: 10px 14px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Profile tabs
    document.querySelectorAll('.profile-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.profile-tab').forEach(function(t) { t.classList.remove('active'); });
            document.querySelectorAll('.profile-tab-content').forEach(function(c) { c.classList.remove('active'); });
            
            this.classList.add('active');
            var target = document.getElementById(this.getAttribute('data-target'));
            if (target) target.classList.add('active');
        });
    });
    
    // Remove bookmark
    document.querySelectorAll('.profile-bookmark-remove').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var card = this.closest('.profile-bookmark-card');
            var postId = this.getAttribute('data-id');
            
            // AJAX remove
            var formData = new FormData();
            formData.append('action', 'flavor_bookmark_toggle');
            formData.append('nonce', flavorAuth.nonce);
            formData.append('post_id', postId);
            
            fetch(flavorAuth.ajaxurl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) {
                    card.style.transition = 'all 0.3s';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.9)';
                    setTimeout(function() { card.remove(); }, 300);
                    
                    // Also remove from localStorage
                    try {
                        var local = JSON.parse(localStorage.getItem('flavor_bookmarks') || '[]');
                        local = local.filter(function(b) { return b.id !== postId && b.id !== String(postId); });
                        localStorage.setItem('flavor_bookmarks', JSON.stringify(local));
                    } catch(e) {}
                }
            });
        });
    });
});
</script>

<?php get_footer(); ?>
