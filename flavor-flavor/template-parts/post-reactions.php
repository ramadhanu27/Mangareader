<?php
/**
 * Post Reactions Template
 * "Your Reaction?" section with chibi anime characters
 */
if (!defined('ABSPATH')) exit;

$images_url = get_template_directory_uri() . '/assets/images/';
$reaction_token = flavor_get_comment_token();

// Use global if set (chapter reader), otherwise get_the_ID()
global $flavor_comment_post_id;
$reaction_post_id = !empty($flavor_comment_post_id) ? $flavor_comment_post_id : get_the_ID();

// Failsafe: Enqueue script directly
wp_enqueue_script(
    'flavor-post-reactions',
    get_template_directory_uri() . '/assets/js/post-reactions.js',
    array(),
    filemtime(get_template_directory() . '/assets/js/post-reactions.js'),
    true
);
?>

<div class="pr-section" id="postReactions" data-post-id="<?php echo $reaction_post_id; ?>">
    
    <!-- Inline config (failsafe) -->
    <script>
    if (!window.flavorReactions) {
        window.flavorReactions = {
            ajaxurl:    '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce:      '<?php echo wp_create_nonce('flavor_reactions_nonce'); ?>',
            post_id:    '<?php echo $reaction_post_id; ?>',
            user_token: '<?php echo esc_js($reaction_token); ?>',
            images_url: '<?php echo $images_url; ?>'
        };
    }
    </script>

    <h3 class="pr-title">Your Reaction?</h3>
    <p class="pr-subtitle"><span id="prTotalCount">0</span> Responses</p>
    
    <div class="pr-reactions">
        <button type="button" class="pr-reaction-btn" data-type="like">
            <div class="pr-img-wrap">
                <img src="<?php echo $images_url; ?>reaction_like_1770879514704.png" alt="Like" loading="lazy">
            </div>
            <span class="pr-count" id="prCountLike">0</span>
            <span class="pr-label">Like</span>
        </button>
        
        <button type="button" class="pr-reaction-btn" data-type="funny">
            <div class="pr-img-wrap">
                <img src="<?php echo $images_url; ?>reaction_funny_1770879534614.png" alt="Funny" loading="lazy">
            </div>
            <span class="pr-count" id="prCountFunny">0</span>
            <span class="pr-label">Funny</span>
        </button>
        
        <button type="button" class="pr-reaction-btn" data-type="nice">
            <div class="pr-img-wrap">
                <img src="<?php echo $images_url; ?>reaction_nice_1770879556947.png" alt="Nice" loading="lazy">
            </div>
            <span class="pr-count" id="prCountNice">0</span>
            <span class="pr-label">Nice</span>
        </button>
        
        <button type="button" class="pr-reaction-btn" data-type="sad">
            <div class="pr-img-wrap">
                <img src="<?php echo $images_url; ?>reaction_sad_1770879575425.png" alt="Sad" loading="lazy">
            </div>
            <span class="pr-count" id="prCountSad">0</span>
            <span class="pr-label">Sad</span>
        </button>
        
        <button type="button" class="pr-reaction-btn" data-type="angry">
            <div class="pr-img-wrap">
                <img src="<?php echo $images_url; ?>reaction_angry_1770879591391.png" alt="Angry" loading="lazy">
            </div>
            <span class="pr-count" id="prCountAngry">0</span>
            <span class="pr-label">Angry</span>
        </button>
    </div>
</div>
