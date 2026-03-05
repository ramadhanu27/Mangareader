<?php
/**
 * Comments Template
 *
 * @package Flavor_Flavor
 */

if (post_password_required()) {
    return;
}
?>

<div id="comments" class="comments-area card mt-20">
    <div class="card-header">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline; vertical-align: middle; width: 20px; height: 20px;">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
        <?php
        $comments_number = get_comments_number();
        if ($comments_number === '0') {
            esc_html_e('No Comments', 'flavor-flavor');
        } else {
            printf(
                esc_html(_n('%s Comment', '%s Comments', $comments_number, 'flavor-flavor')),
                number_format_i18n($comments_number)
            );
        }
        ?>
    </div>
    
    <div class="card-body">
        <!-- Comment Rules -->
        <details class="comment-rules" open>
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
                        <span>Dilarang mengirim gambar <strong>cabul / mengganggu</strong> kenyamanan.</span>
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
        <?php if (have_comments()): ?>
        
        <ol class="comment-list" style="list-style: none; padding: 0;">
            <?php
            wp_list_comments(array(
                'style'       => 'ol',
                'short_ping'  => true,
                'avatar_size' => 50,
                'callback'    => 'flavor_comment_callback',
            ));
            ?>
        </ol>
        
        <?php
        the_comments_navigation(array(
            'prev_text' => __('&laquo; Older Comments', 'flavor-flavor'),
            'next_text' => __('Newer Comments &raquo;', 'flavor-flavor'),
        ));
        ?>
        
        <?php endif; ?>
        
        <?php if (!comments_open() && get_comments_number() && post_type_supports(get_post_type(), 'comments')): ?>
            <p class="no-comments text-muted"><?php esc_html_e('Comments are closed.', 'flavor-flavor'); ?></p>
        <?php endif; ?>
        
        <?php
        comment_form(array(
            'class_form'         => 'comment-form',
            'title_reply'        => __('Leave a Comment', 'flavor-flavor'),
            'title_reply_before' => '<h4 id="reply-title" class="comment-reply-title">',
            'title_reply_after'  => '</h4>',
            'comment_field'      => '<p class="comment-form-comment"><label for="comment">' . __('Comment', 'flavor-flavor') . '</label><textarea id="comment" name="comment" class="search-input" rows="5" required></textarea></p>',
            'submit_button'      => '<button type="submit" name="%1$s" id="%2$s" class="search-btn">%4$s</button>',
            'class_submit'       => 'submit',
        ));
        ?>
    </div>
</div>

<?php
/**
 * Custom comment callback
 */
function flavor_comment_callback($comment, $args, $depth) {
    ?>
    <li id="comment-<?php comment_ID(); ?>" <?php comment_class('comment-item'); ?> style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid var(--border-color);">
        <article class="comment-body" style="display: flex; gap: 15px;">
            <div class="comment-author-avatar">
                <?php echo get_avatar($comment, 50, '', '', array('class' => 'avatar', 'style' => 'border-radius: 50%;')); ?>
            </div>
            
            <div class="comment-content" style="flex: 1;">
                <header class="comment-meta" style="margin-bottom: 10px;">
                    <cite class="fn" style="font-weight: 600; font-style: normal;">
                        <?php comment_author_link(); ?>
                    </cite>
                    <span class="comment-date text-muted" style="font-size: 12px; margin-left: 10px;">
                        <?php
                        printf(
                            esc_html__('%1$s at %2$s', 'flavor-flavor'),
                            get_comment_date(),
                            get_comment_time()
                        );
                        ?>
                    </span>
                </header>
                
                <?php if ($comment->comment_approved == '0'): ?>
                    <p class="comment-awaiting-moderation text-muted" style="font-size: 13px; font-style: italic;">
                        <?php esc_html_e('Your comment is awaiting moderation.', 'flavor-flavor'); ?>
                    </p>
                <?php endif; ?>
                
                <div class="comment-text">
                    <?php comment_text(); ?>
                </div>
                
                <div class="comment-actions" style="margin-top: 10px; font-size: 13px;">
                    <?php
                    comment_reply_link(array_merge($args, array(
                        'add_below' => 'comment',
                        'depth'     => $depth,
                        'max_depth' => $args['max_depth'],
                        'before'    => '<span class="reply-link">',
                        'after'     => '</span>',
                    )));
                    ?>
                    
                    <?php edit_comment_link(__('Edit', 'flavor-flavor'), '<span class="edit-link" style="margin-left: 10px;">', '</span>'); ?>
                </div>
            </div>
        </article>
    </li>
    <?php
}
?>
