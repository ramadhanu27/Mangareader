<?php
/**
 * Custom Comments Template
 * Include this in single-manhwa.php & chapter reader
 */
if (!defined('ABSPATH')) exit;

// Check if comments are enabled via Customizer
$comments_enabled = get_theme_mod('comments_enabled', true);
if (!$comments_enabled) {
    $disabled_msg = get_theme_mod('comments_disabled_message', 'Komentar untuk sementara dinonaktifkan oleh admin. Silakan kembali lagi nanti.');
    ?>
    <section class="fc-section fc-disabled-section">
        <div class="fc-disabled-notice">
            <div class="fc-disabled-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    <line x1="9" y1="9" x2="15" y2="15"/>
                    <line x1="15" y1="9" x2="9" y2="15"/>
                </svg>
            </div>
            <h3 class="fc-disabled-title">Komentar Dinonaktifkan</h3>
            <p class="fc-disabled-message"><?php echo esc_html($disabled_msg); ?></p>
        </div>
    </section>
    <style>
        .fc-disabled-section { margin-top: 20px; }
        .fc-disabled-notice {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 40px 20px;
            background: var(--card-bg, #fff);
            border: 1px dashed var(--border-color, #e0e0e0);
            border-radius: 12px;
        }
        .dark-mode .fc-disabled-notice {
            background: var(--secondary-bg, #2a2a2a);
            border-color: rgba(255,255,255,0.1);
        }
        .fc-disabled-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(244, 67, 54, 0.1);
            color: #f44336;
            margin-bottom: 16px;
        }
        .dark-mode .fc-disabled-icon {
            background: rgba(244, 67, 54, 0.15);
            color: #ef5350;
        }
        .fc-disabled-title {
            font-size: 16px;
            font-weight: 600;
            margin: 0 0 8px 0;
            color: var(--text-color, #333);
        }
        .fc-disabled-message {
            font-size: 14px;
            color: var(--text-muted, #888);
            margin: 0;
            max-width: 400px;
            line-height: 1.5;
        }
    </style>
    <?php
    return; // Stop here, don't render the rest of the comments
}

// Get current user data
$current_token = flavor_get_comment_token();
if (is_user_logged_in()) {
    $wp_user = wp_get_current_user();
    $current_name  = $wp_user->display_name;
} else {
    $current_name  = flavor_get_comment_author();
}
$current_color = flavor_get_comment_color();

// Use global if set (chapter reader), otherwise get_the_ID()
global $flavor_comment_post_id;
$comment_post_id = !empty($flavor_comment_post_id) ? $flavor_comment_post_id : get_the_ID();

// Failsafe: Enqueue script directly (works even after wp_head)
wp_enqueue_script(
    'flavor-custom-comments',
    get_template_directory_uri() . '/assets/js/custom-comments.js',
    array(),
    filemtime(get_template_directory() . '/assets/js/custom-comments.js'),
    true
);
?>

<section class="fc-section" id="comments-section" data-post-id="<?php echo $comment_post_id; ?>">
    
    <!-- Comment Rules -->
    <details class="comment-rules">
        <summary class="comment-rules-header">
            <span class="comment-rules-title">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                    <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                </svg>
                <?php printf(esc_html__('Aturan Komentar di %s', 'flavor-flavor'), get_bloginfo('name')); ?>
            </span>
            <svg class="comment-rules-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                <polyline points="18 15 12 9 6 15"></polyline>
            </svg>
        </summary>
        <div class="comment-rules-body">
            <ul class="comment-rules-list">
                <li>
                    <span class="rule-icon">🚫</span>
                    <span>Dilarang <strong>toxic, rasis, provokatif</strong>, atau memancing keributan antar pembaca.</span>
                </li>
                <li>
                    <span class="rule-icon">🍵</span>
                    <span>Jangan membalas komentar bermasalah. <strong>Laporkan ke admin</strong>, bukan ikut ribut. Semua pihak yang terlibat bisa kena ban.</span>
                </li>
                <li>
                    <span class="rule-icon">🚷</span>
                    <span>Dilarang <strong>Spoiler Ending!!</strong> Hanya diperbolehkan membahas 1–2 chapter kedepan.</span>
                </li>
                <li>
                    <span class="rule-icon">👤</span>
                    <span>Dilarang menggunakan foto profil <strong>cabul / tidak pantas</strong>.</span>
                </li>
                <li>
                    <span class="rule-icon">🖼️</span>
                    <span>Dilarang mengirim gambar atau GIF <strong>cabul / mengganggu</strong> kenyamanan.</span>
                </li>
            </ul>
            <div class="comment-rules-warning">
                <span class="warning-title">⚠️ CATATAN PENTING</span>
                <ul>
                    <li>Setiap pelanggaran akan langsung kena <strong>suspend</strong> beberapa hari.</li>
                    <li>Pelanggaran yang parah akan dibanned secara <strong>PERMANEN</strong>.</li>
                </ul>
            </div>
        </div>
    </details>

    <!-- Inline config (failsafe if wp_localize_script didn't run) -->
    <script>
    if (!window.flavorComments) {
        window.flavorComments = {
            ajaxurl:    '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce:      '<?php echo wp_create_nonce('flavor_comments_nonce'); ?>',
            post_id:    '<?php echo $comment_post_id; ?>',
            user_token: '<?php echo esc_js($current_token); ?>',
            user_name:  '<?php echo esc_js($current_name); ?>',
            user_color: '<?php echo esc_js($current_color); ?>',
            is_admin:   '<?php echo current_user_can('manage_options') ? '1' : '0'; ?>',
            is_logged_in: '<?php echo is_user_logged_in() ? '1' : '0'; ?>',
            max_upload_size: '2'
        };
    }
    </script>

    <!-- Comment Form -->
    <div class="fc-form-wrapper">
        <?php if (is_user_logged_in()): ?>
            <div class="fc-form-header">
                <div class="fc-avatar" style="background-color: <?php echo esc_attr($current_color); ?>">
                    <?php echo esc_html(mb_strtoupper(mb_substr($current_name, 0, 1))); ?>
                </div>
                <span class="fc-current-user"><?php echo esc_html($current_name); ?></span>
            </div>

            <div class="fc-editor-wrap">
                <div class="fc-textarea" id="fcTextarea" contenteditable="true" data-placeholder="Write comment..."></div>
            </div>
            
            <div class="fc-toolbar">
                <div class="fc-toolbar-left">
                    <button type="button" class="fc-tool-btn" data-command="bold" title="Bold"><b>B</b></button>
                    <button type="button" class="fc-tool-btn" data-command="italic" title="Italic"><i>I</i></button>
                    <button type="button" class="fc-tool-btn" data-command="strikethrough" title="Strikethrough"><s>S</s></button>
                    <button type="button" class="fc-tool-btn fc-tool-spoiler" data-command="spoiler" title="Blok teks lalu klik tombol ini untuk spoiler">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <span>Spoiler</span>
                    </button>
                    <button type="button" class="fc-tool-btn fc-tool-image" id="fcImageBtn" title="Upload gambar (max 2MB)">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        <span>Gambar</span>
                    </button>
                    <input type="file" id="fcImageInput" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none">
                    <span class="fc-toolbar-hint">Tip: Blok teks → klik Spoiler</span>
                </div>
                <div class="fc-toolbar-right">
                    <span class="fc-char-count"><span id="fcCharCount">0</span>/1000</span>
                    <button type="button" class="fc-send-btn" id="fcSendBtn">Send</button>
                </div>
            </div>
            <!-- Image Preview -->
            <div class="fc-image-preview" id="fcImagePreview" style="display:none">
                <div class="fc-image-preview-inner">
                    <img id="fcImagePreviewImg" src="" alt="Preview">
                    <button type="button" class="fc-image-remove" id="fcImageRemove" title="Hapus gambar">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                    <span class="fc-image-uploading" id="fcImageUploading" style="display:none">Uploading...</span>
                </div>
            </div>
        <?php else: ?>
            <div class="fc-login-prompt">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                <span>Silakan login untuk berkomentar.</span>
                <button type="button" class="fc-login-btn" data-auth-open="login">Masuk</button>
                <button type="button" class="fc-register-btn" data-auth-open="register">Daftar</button>
            </div>
        <?php endif; ?> 
    </div>

    <!-- Comments Header -->
    <div class="fc-header">
        <h3 class="fc-title"><span id="fcTotalCount">0</span> Comments</h3>
        <div class="fc-sort-buttons">
            <button type="button" class="fc-sort-btn active" data-sort="popular">Popular</button>
            <button type="button" class="fc-sort-btn" data-sort="latest">Latest</button>
            <button type="button" class="fc-sort-btn" data-sort="oldest">Oldest</button>
        </div>
    </div>

    <!-- Comments List -->
    <div class="fc-list" id="fcCommentsList">
        <div class="fc-loading" id="fcLoading">
            <div class="fc-spinner"></div>
            <span>Memuat komentar...</span>
        </div>
    </div>

</section>
