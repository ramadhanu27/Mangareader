    </main><!-- #main -->

    <?php 
    // Before Footer Ad - Display directly
    if (function_exists('flavor_display_ad')) {
        flavor_display_ad('before_footer');
    }
    ?>

    <!-- Site Footer -->
    <footer class="site-footer">
        <!-- Top Bar -->
        <div class="footer-top-bar">
            <div class="container">
                <nav class="footer-nav">
                    <a href="<?php echo esc_url(get_post_type_archive_link('manhwa')); ?>"><?php esc_html_e('A-Z', 'flavor-flavor'); ?></a>
                </nav>
            </div>
        </div>
        
        <!-- Main Footer -->
        <div class="footer-main">
            <div class="container">
                <!-- A-Z List Section -->
                <div class="az-list-section">
                    <div class="az-list-header">
                        <span class="az-title"><?php esc_html_e('A-Z LIST', 'flavor-flavor'); ?></span>
                        <span class="az-desc"><?php esc_html_e('Cari manga dari urutan A sampai Z', 'flavor-flavor'); ?></span>
                    </div>
                    
                    <div class="az-list-letters">
                        <?php
                        global $wpdb;
                        $alphabet = range('A', 'Z');
                        
                        foreach ($alphabet as $letter) {
                            // Count manhwa starting with this letter
                            $count = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$wpdb->posts} 
                                WHERE post_type = 'manhwa' 
                                AND post_status = 'publish' 
                                AND post_title LIKE %s",
                                $letter . '%'
                            ));
                            
                            if ($count > 0) {
                                $url = home_url('/?s=' . $letter . '&post_type=manhwa');
                                echo '<a href="' . esc_url($url) . '" class="az-letter">';
                                echo '<span class="letter-badge">' . esc_html($count) . '</span>';
                                echo '<span class="letter-char">' . esc_html($letter) . '</span>';
                                echo '</a>';
                            }
                        }
                        ?>
                    </div>
                </div>
                
                <!-- Social Icons -->
                <?php 
                $social_facebook = get_theme_mod('social_facebook', '');
                $social_instagram = get_theme_mod('social_instagram', '');
                $social_twitter = get_theme_mod('social_twitter', '');
                $social_discord = get_theme_mod('social_discord', '');
                $social_tiktok = get_theme_mod('social_tiktok', '');
                $social_youtube = get_theme_mod('social_youtube', '');
                $social_telegram = get_theme_mod('social_telegram', '');
                
                // Check if any social media is set
                $has_social = $social_facebook || $social_instagram || $social_twitter || $social_discord || $social_tiktok || $social_youtube || $social_telegram;
                
                if ($has_social): 
                ?>
                <div class="footer-social">
                    <?php if ($social_facebook): ?>
                    <a href="<?php echo esc_url($social_facebook); ?>" class="social-icon" aria-label="Facebook" target="_blank" rel="noopener noreferrer">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 13.5h2.5l1-4H14v-2c0-1.03 0-2 2-2h1.5V2.14c-.326-.043-1.557-.14-2.857-.14C11.928 2 10 3.657 10 6.7v2.8H7v4h3V22h4v-8.5z"/></svg>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($social_instagram): ?>
                    <a href="<?php echo esc_url($social_instagram); ?>" class="social-icon" aria-label="Instagram" target="_blank" rel="noopener noreferrer">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2c2.717 0 3.056.01 4.122.06 1.065.05 1.79.217 2.428.465.66.254 1.216.598 1.772 1.153a4.908 4.908 0 011.153 1.772c.247.637.415 1.363.465 2.428.047 1.066.06 1.405.06 4.122 0 2.717-.01 3.056-.06 4.122-.05 1.065-.218 1.79-.465 2.428a4.883 4.883 0 01-1.153 1.772 4.915 4.915 0 01-1.772 1.153c-.637.247-1.363.415-2.428.465-1.066.047-1.405.06-4.122.06-2.717 0-3.056-.01-4.122-.06-1.065-.05-1.79-.218-2.428-.465a4.89 4.89 0 01-1.772-1.153 4.904 4.904 0 01-1.153-1.772c-.248-.637-.415-1.363-.465-2.428C2.013 15.056 2 14.717 2 12c0-2.717.01-3.056.06-4.122.05-1.066.217-1.79.465-2.428a4.88 4.88 0 011.153-1.772A4.897 4.897 0 015.45 2.525c.638-.248 1.362-.415 2.428-.465C8.944 2.013 9.283 2 12 2zm0 5a5 5 0 100 10 5 5 0 000-10zm0 8.25a3.25 3.25 0 110-6.5 3.25 3.25 0 010 6.5zm5.25-9.5a1.25 1.25 0 110 2.5 1.25 1.25 0 010-2.5z"/></svg>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($social_twitter): ?>
                    <a href="<?php echo esc_url($social_twitter); ?>" class="social-icon" aria-label="Twitter/X" target="_blank" rel="noopener noreferrer">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($social_discord): ?>
                    <a href="<?php echo esc_url($social_discord); ?>" class="social-icon" aria-label="Discord" target="_blank" rel="noopener noreferrer">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.37a19.791 19.791 0 00-4.885-1.515.074.074 0 00-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 00-5.487 0 12.64 12.64 0 00-.617-1.25.077.077 0 00-.079-.037A19.736 19.736 0 003.677 4.37a.07.07 0 00-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 00.031.057 19.9 19.9 0 005.993 3.03.078.078 0 00.084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 00-.041-.106 13.107 13.107 0 01-1.872-.892.077.077 0 01-.008-.128 10.2 10.2 0 00.372-.292.074.074 0 01.077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 01.078.01c.12.098.246.198.373.292a.077.077 0 01-.006.127 12.299 12.299 0 01-1.873.892.077.077 0 00-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 00.084.028 19.839 19.839 0 006.002-3.03.077.077 0 00.032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 00-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/></svg>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($social_tiktok): ?>
                    <a href="<?php echo esc_url($social_tiktok); ?>" class="social-icon" aria-label="TikTok" target="_blank" rel="noopener noreferrer">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-5.2 1.74 2.89 2.89 0 012.31-4.64 2.93 2.93 0 01.88.13V9.4a6.84 6.84 0 00-1-.05A6.33 6.33 0 005 20.1a6.34 6.34 0 0010.86-4.43v-7a8.16 8.16 0 004.77 1.52v-3.4a4.85 4.85 0 01-1-.1z"/></svg>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($social_youtube): ?>
                    <a href="<?php echo esc_url($social_youtube); ?>" class="social-icon" aria-label="YouTube" target="_blank" rel="noopener noreferrer">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($social_telegram): ?>
                    <a href="<?php echo esc_url($social_telegram); ?>" class="social-icon" aria-label="Telegram" target="_blank" rel="noopener noreferrer">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 000 12a12 12 0 0012 12 12 12 0 0012-12A12 12 0 0012 0a12 12 0 00-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 01.171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Disclaimer -->
                <div class="footer-disclaimer">
                    <p><?php esc_html_e('All the comics on this website are only previews of the original comics, there may be many language errors, character names, and story lines. For the original version, please buy the comic if it\'s available in your city.', 'flavor-flavor'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <div class="container">
                <p>&copy; <?php echo date('Y'); ?> <strong><?php bloginfo('name'); ?></strong>. <?php esc_html_e('All Rights Reserved.', 'flavor-flavor'); ?> </p>
            </div>
        </div>
    </footer>

</div><!-- #page -->

<?php 
// Output Tracking Codes (wrapped with NOMIN to prevent minification)
$histats_code = get_option('tracking_histats_code', '');
$ga_code = get_theme_mod('tracking_google_analytics', '');
$footer_scripts = get_theme_mod('tracking_footer_scripts', '');

if (!empty($histats_code)) {
    echo '<!--NOMIN-->';
    echo '<div class="histats-container" style="text-align: center; display: flex; justify-content: center; align-items: center; padding: 15px 0; margin: 0 auto;">';
    echo $histats_code;
    echo '</div>';
    echo '<!--/NOMIN-->';
}

if (!empty($ga_code)) {
    echo '<!--NOMIN-->';
    echo $ga_code;
    echo '<!--/NOMIN-->';
}

if (!empty($footer_scripts)) {
    echo '<!--NOMIN-->';
    echo $footer_scripts;
    echo '<!--/NOMIN-->';
}
?>

<?php wp_footer(); ?>

<script>
// Mobile Menu - Dropdown
document.addEventListener('DOMContentLoaded', function() {
    var mobileNav = document.getElementById('mobileNav');
    var mobileMenuToggle = document.getElementById('mobileMenuToggle');
    
    if (!mobileNav || !mobileMenuToggle) return;
    
    function closeMenu() {
        mobileNav.classList.remove('active');
    }
    
    function openMenu() {
        mobileNav.classList.add('active');
    }
    
    // Toggle button click
    mobileMenuToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        if (mobileNav.classList.contains('active')) {
            closeMenu();
        } else {
            openMenu();
        }
    });
    
    // Close when clicking outside
    document.addEventListener('click', function(e) {
        if (mobileNav.classList.contains('active')) {
            if (!mobileNav.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
                closeMenu();
            }
        }
    });
    
    // Close on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeMenu();
        }
    });
});
</script>

<!-- Ad Blocker Warning Banner -->
<div id="adBlockerWarning" class="adblocker-warning" style="display: none;">
    <div class="adblocker-content">
        <div class="adblocker-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M12 8v4"></path>
                <path d="M12 16h.01"></path>
            </svg>
        </div>
        <h3><?php esc_html_e('Ad Blocker Terdeteksi', 'flavor-flavor'); ?></h3>
        <p><?php esc_html_e('Sepertinya kamu menggunakan Ad Blocker. Website ini mengandalkan iklan untuk tetap gratis. Mohon pertimbangkan untuk menonaktifkan Ad Blocker atau whitelist website ini.', 'flavor-flavor'); ?></p>
        <div class="adblocker-buttons">
            <button type="button" class="adblocker-close-btn" onclick="closeAdBlockerWarning()">
                <?php esc_html_e('Mengerti, Lanjutkan', 'flavor-flavor'); ?>
            </button>
        </div>
        <p class="adblocker-note"><?php esc_html_e('Terima kasih atas pengertiannya! ❤️', 'flavor-flavor'); ?></p>
    </div>
</div>

<style>
/* Ad Blocker Warning Styles */
.adblocker-warning {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.85);
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    backdrop-filter: blur(5px);
}

.adblocker-content {
    background: var(--card-bg, #fff);
    border-radius: 16px;
    padding: 40px;
    max-width: 450px;
    text-align: center;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    animation: adBlockerSlideIn 0.3s ease-out;
}

@keyframes adBlockerSlideIn {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(-20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.adblocker-icon {
    color: #f59e0b;
    margin-bottom: 20px;
}

.adblocker-content h3 {
    font-size: 24px;
    font-weight: 700;
    margin: 0 0 15px;
    color: var(--text-color, #333);
}

.adblocker-content p {
    font-size: 15px;
    line-height: 1.7;
    color: var(--text-muted, #666);
    margin: 0 0 25px;
}

.adblocker-buttons {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
}

.adblocker-close-btn {
    background: linear-gradient(135deg, var(--primary-color, #ff5722), #ff7043);
    color: white;
    border: none;
    padding: 14px 32px;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 15px rgba(255, 87, 34, 0.4);
}

.adblocker-close-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 87, 34, 0.5);
}

.adblocker-note {
    font-size: 13px !important;
    margin-top: 20px !important;
    margin-bottom: 0 !important;
    color: var(--text-muted, #999) !important;
}

@media (max-width: 480px) {
    .adblocker-content {
        padding: 30px 20px;
    }
    
    .adblocker-content h3 {
        font-size: 20px;
    }
    
    .adblocker-content p {
        font-size: 14px;
    }
}
</style>

<script>
// Ad Blocker Detection - Improved
(function() {
    'use strict';
    
    // Check if user already dismissed the warning
    var dismissed = localStorage.getItem('adBlockerDismissed');
    var dismissedTime = localStorage.getItem('adBlockerDismissedTime');
    
    // Show warning again after 24 hours
    if (dismissed && dismissedTime) {
        var hoursSinceDismissed = (Date.now() - parseInt(dismissedTime)) / (1000 * 60 * 60);
        if (hoursSinceDismissed > 24) {
            dismissed = false;
            localStorage.removeItem('adBlockerDismissed');
            localStorage.removeItem('adBlockerDismissedTime');
        }
    }
    
    if (dismissed) return;
    
    function showAdBlockerWarning() {
        var warning = document.getElementById('adBlockerWarning');
        if (warning) {
            warning.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    }
    
    function detectAdBlocker() {
        var detected = false;
        var checksCompleted = 0;
        var totalChecks = 3;
        
        function checkComplete(isBlocked) {
            checksCompleted++;
            if (isBlocked) detected = true;
            
            if (checksCompleted >= totalChecks && detected) {
                showAdBlockerWarning();
            }
        }
        
        // Method 1: Create bait element with ad-like classes
        var bait = document.createElement('div');
        bait.innerHTML = '&nbsp;';
        bait.className = 'adsbox ad ads ad-placement carbon-ads';
        bait.setAttribute('id', 'ad-container');
        bait.style.cssText = 'position: absolute !important; left: -9999px !important; top: -9999px !important; height: 1px !important; width: 1px !important;';
        document.body.appendChild(bait);
        
        setTimeout(function() {
            var isBlocked = false;
            if (!bait || 
                bait.offsetParent === null || 
                bait.offsetHeight === 0 || 
                bait.offsetWidth === 0 ||
                bait.clientHeight === 0 ||
                getComputedStyle(bait).display === 'none' ||
                getComputedStyle(bait).visibility === 'hidden') {
                isBlocked = true;
            }
            if (bait && bait.parentNode) bait.parentNode.removeChild(bait);
            checkComplete(isBlocked);
        }, 200);
        
        // Method 2: Create an iframe bait
        var iframe = document.createElement('iframe');
        iframe.src = 'about:blank';
        iframe.className = 'ad-iframe adsense';
        iframe.style.cssText = 'position: absolute !important; left: -9999px !important; top: -9999px !important; height: 1px !important; width: 1px !important;';
        document.body.appendChild(iframe);
        
        setTimeout(function() {
            var isBlocked = !iframe || 
                iframe.offsetParent === null || 
                iframe.offsetHeight === 0 ||
                getComputedStyle(iframe).display === 'none';
            if (iframe && iframe.parentNode) iframe.parentNode.removeChild(iframe);
            checkComplete(isBlocked);
        }, 200);
        
        // Method 3: Try to fetch a file that ad blockers typically block
        var testUrl = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js';
        
        // Use image to test (avoids CORS issues)
        var img = new Image();
        img.onload = function() {
            checkComplete(false);
        };
        img.onerror = function() {
            checkComplete(true); // Blocked
        };
        // Set timeout in case neither fires
        setTimeout(function() {
            if (checksCompleted < 3) {
                checkComplete(true); // Assume blocked if no response
            }
        }, 1500);
        img.src = testUrl + '?_=' + Date.now();
    }
    
    // Run detection when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(detectAdBlocker, 500);
        });
    } else {
        setTimeout(detectAdBlocker, 500);
    }
})();

function closeAdBlockerWarning() {
    var warning = document.getElementById('adBlockerWarning');
    if (warning) {
        warning.style.display = 'none';
        document.body.style.overflow = '';
        
        // Remember dismissal for 24 hours
        localStorage.setItem('adBlockerDismissed', 'true');
        localStorage.setItem('adBlockerDismissedTime', Date.now().toString());
    }
}
</script>
<?php 
// Float Bottom Sticky Ad
$float_bottom_ad = get_theme_mod('flavor_ads_float_bottom', '');
if (!empty($float_bottom_ad) && get_theme_mod('flavor_ads_enable', true)): 
?>
<div id="floatBottomAd" class="fv-sticky-banner">
    <button type="button" class="float-bottom-close" id="floatBottomClose" aria-label="Close">Close ✕</button>
    <div class="float-bottom-wrapper">
        <?php echo $float_bottom_ad; ?>
    </div>
</div>

<style>
/* Float Bottom Sticky Ad */
.fv-sticky-banner {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 9998;
    background: transparent;
    padding: 0;
    text-align: center;
    animation: floatBottomSlideUp 0.4s ease-out;
}

@keyframes floatBottomSlideUp {
    from {
        transform: translateY(100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.float-bottom-close {
    position: absolute;
    top: -28px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(40, 40, 40, 0.95);
    color: #ccc;
    border: 1px solid rgba(255, 255, 255, 0.15);
    padding: 4px 16px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    border-radius: 6px 6px 0 0;
    transition: all 0.2s;
    z-index: 1;
    line-height: 1.4;
}

.float-bottom-close:hover {
    background: rgba(60, 60, 60, 0.95);
    color: #fff;
}

.float-bottom-wrapper {
    max-width: var(--blog-width, 1200px);
    margin: 0 auto;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    overflow: hidden;
}

.float-bottom-wrapper iframe {
    max-width: 100%;
}

/* Mobile - 1 kolom */
@media (max-width: 768px) {
    .fv-sticky-banner {
        padding: 0;
    }
    .float-bottom-wrapper {
        flex-direction: column;
        gap: 4px;
    }
    .float-bottom-wrapper iframe {
        max-width: 100%;
    }
}
</style>

<script>
// Float Bottom Ad - Close
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        var floatAd = document.getElementById('floatBottomAd');
        var closeBtn = document.getElementById('floatBottomClose');
        
        if (!floatAd || !closeBtn) return;
        
        closeBtn.addEventListener('click', function() {
            floatAd.style.animation = 'none';
            floatAd.style.transition = 'transform 0.3s ease, opacity 0.3s ease';
            floatAd.style.transform = 'translateY(100%)';
            floatAd.style.opacity = '0';
            setTimeout(function() {
                floatAd.style.display = 'none';
            }, 300);
        });
    });
})();
</script>
<?php endif; ?>

<?php 
// Direct Link Ad Script
$dl_enabled = get_theme_mod('flavor_ads_direct_link_enable', false);
$dl_url = get_theme_mod('flavor_ads_direct_link_url', '');
$dl_max = get_theme_mod('flavor_ads_direct_link_max', 2);
$ads_enabled = get_theme_mod('flavor_ads_enable', true);

// Don't show in customizer preview
if ($dl_enabled && !empty($dl_url) && $ads_enabled && !is_customize_preview()):
    $dl_exclude = get_theme_mod('flavor_ads_direct_link_exclude', 'admin,login');
?>
<script>
// Direct Link Ad
(function() {
    'use strict';
    
    var dlUrl = <?php echo json_encode(esc_url($dl_url)); ?>;
    var dlMax = <?php echo intval($dl_max); ?>;
    var dlExclude = <?php echo json_encode($dl_exclude); ?>.split(',').map(function(s) { return s.trim().toLowerCase(); });
    
    // Check if current page is excluded
    var currentPath = window.location.pathname.toLowerCase();
    for (var i = 0; i < dlExclude.length; i++) {
        if (dlExclude[i] && currentPath.indexOf(dlExclude[i]) !== -1) {
            return;
        }
    }
    
    // Track clicks in sessionStorage
    var clickCount = parseInt(sessionStorage.getItem('dl_clicks') || '0');
    
    // Check max clicks (0 = unlimited)
    if (dlMax > 0 && clickCount >= dlMax) return;
    
    // Elements to exclude from direct link
    var excludeTags = ['A', 'BUTTON', 'INPUT', 'SELECT', 'TEXTAREA', 'LABEL', 'VIDEO', 'AUDIO', 'IFRAME'];
    var excludeSelectors = '.header-actions, .mobile-menu-toggle, .main-nav, .mobile-nav, .search-overlay, .auth-modal, .mode-switch, .bookmark-btn, .chapter-select, .reader-toolbar, .floating-nav, .scroll-to-top, .adblocker-warning, .fv-sticky-banner, .fv-promo, .comments-area, [data-auth-open], [onclick]';
    
    document.addEventListener('click', function(e) {
        // Re-check max clicks
        var currentClicks = parseInt(sessionStorage.getItem('dl_clicks') || '0');
        if (dlMax > 0 && currentClicks >= dlMax) return;
        
        var target = e.target;
        
        // Don't trigger if user is selecting text
        var selection = window.getSelection();
        if (selection && selection.toString().length > 0) return;
        
        // Check if clicked element or any parent is an interactive element
        var el = target;
        while (el && el !== document.body) {
            // Check tag name
            if (excludeTags.indexOf(el.tagName) !== -1) return;
            
            // Check if element is contenteditable
            if (el.contentEditable === 'true') return;
            
            // Check if element has role="button" or is clickable
            if (el.getAttribute('role') === 'button') return;
            if (el.getAttribute('tabindex')) return;
            
            // Check if element matches exclude selectors
            try {
                if (el.matches && el.matches(excludeSelectors)) return;
            } catch(err) {}
            
            // Check for onclick handler
            if (el.onclick) return;
            
            el = el.parentElement;
        }
        
        // Check if click is on image (cover, thumbnail, etc.)
        if (target.tagName === 'IMG') return;
        
        // Open direct link in new tab
        window.open(dlUrl, '_blank', 'noopener,noreferrer');
        
        // Update click count
        currentClicks++;
        sessionStorage.setItem('dl_clicks', currentClicks.toString());
    });
})();
</script>
<?php endif; ?>

<?php if (!is_user_logged_in()): ?>
    <?php get_template_part('template-parts/auth-modal'); ?>
<?php endif; ?>

</body>
</html>
