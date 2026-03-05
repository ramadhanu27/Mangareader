<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php 
    // Custom Header Scripts from Customizer (wrapped with NOMIN to prevent minification)
    $header_scripts = get_theme_mod('tracking_header_scripts', '');
    if (!empty($header_scripts)) {
        echo '<!--NOMIN-->';
        echo $header_scripts;
        echo '<!--/NOMIN-->';
    }
    ?>
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">  
    <!-- Site Header -->
    <header class="site-header">
        <div class="container">
            <div class="header-inner">
                <!-- Mobile Menu Toggle (LEFT on mobile) -->
                <button type="button" class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="<?php esc_attr_e('Menu', 'flavor-flavor'); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="4" x2="20" y1="12" y2="12"></line>
                        <line x1="4" x2="20" y1="6" y2="6"></line>
                        <line x1="4" x2="20" y1="18" y2="18"></line>
                    </svg>
                </button>
                
                <!-- Logo -->
                <div class="site-logo">
                    <?php if (has_custom_logo()): ?>
                        <?php the_custom_logo(); ?>
                    <?php else: ?>
                        <a href="<?php echo esc_url(home_url('/')); ?>">
                            <?php bloginfo('name'); ?>
                        </a>
                    <?php endif; ?>
                </div>
                
                <!-- Main Navigation (Desktop only) -->
                <nav class="main-nav nav-a">
                    <?php
                    wp_nav_menu(array(
                        'theme_location' => 'primary',
                        'container'      => false,
                        'menu_class'     => '',
                        'fallback_cb'    => function() {
                            echo '<ul>';
                            echo '<li><a href="' . esc_url(home_url('/')) . '" data="Home">' . __('Home', 'flavor-flavor') . '</a></li>';
                            if (post_type_exists('manhwa')) {
                                echo '<li><a href="' . esc_url(get_post_type_archive_link('manhwa')) . '" data="Manga List">' . __('Manga List', 'flavor-flavor') . '</a></li>';
                            }
                            echo '</ul>';
                        },
                    ));
                    ?>
                </nav>
                
                <?php 
                // Ticker Running Text
                $ticker_enable = get_theme_mod('ticker_enable', false);
                $ticker_text = get_theme_mod('ticker_text', '');
                if ($ticker_enable && !empty($ticker_text)):
                    $ticker_label = get_theme_mod('ticker_label', 'INFO');
                    $ticker_speed = get_theme_mod('ticker_speed', 30);
                    $ticker_link = get_theme_mod('ticker_link', '');
                ?>
                <div class="header-ticker">
                    <span class="ticker-label"><?php echo esc_html($ticker_label); ?></span>
                    <div class="ticker-wrapper">
                        <div class="ticker-content" style="animation-duration: <?php echo esc_attr($ticker_speed); ?>s;">
                            <?php if (!empty($ticker_link)): ?>
                                <a href="<?php echo esc_url($ticker_link); ?>"><?php echo esc_html($ticker_text); ?></a>
                            <?php else: ?>
                                <span><?php echo esc_html($ticker_text); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Header Actions (RIGHT) -->
                <div class="header-actions">
                    <!-- Dark Mode Toggle -->
                    <label class="mode-switch" title="<?php esc_attr_e('Toggle Dark Mode', 'flavor-flavor'); ?>">
                        <input type="checkbox" id="darkModeToggle">
                        <span class="toggle-switch"></span>
                    </label>
                    
                    <!-- User Auth Button -->
                    <?php if (is_user_logged_in()): 
                        $current_user = wp_get_current_user();
                    ?>
                    <div class="header-user-menu" id="headerUserMenu">
                        <button type="button" class="header-user-btn" id="headerUserBtn" title="<?php echo esc_attr($current_user->display_name); ?>">
                            <img src="<?php echo esc_url(get_avatar_url($current_user->ID, array('size' => 32))); ?>" alt="Avatar" class="header-user-avatar">
                        </button>
                        <div class="header-user-dropdown" id="headerUserDropdown">
                            <div class="header-user-info">
                                <img src="<?php echo esc_url(get_avatar_url($current_user->ID, array('size' => 48))); ?>" alt="Avatar">
                                <div>
                                    <strong><?php echo esc_html($current_user->display_name); ?></strong>
                                    <small><?php echo esc_html($current_user->user_email); ?></small>
                                </div>
                            </div>
                            <div class="header-user-links">
                                <?php if (current_user_can('manage_options')): ?>
                                <a href="<?php echo esc_url(admin_url()); ?>" class="header-user-link">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <span>Dashboard</span>
                                </a>
                                <?php endif; ?>
                                <a href="<?php echo esc_url(home_url('/profile/')); ?>" class="header-user-link">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    <span>Profile</span>
                                </a>
                                <a href="<?php echo esc_url(home_url('/bookmarks/')); ?>" class="header-user-link">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/></svg>
                                    <span>Bookmark</span>
                                </a>
                            </div>
                            <div class="header-user-footer">
                                <a href="#" class="header-user-link header-user-logout" id="headerLogoutBtn">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                    <span>Logout</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <button type="button" class="header-login-btn" data-auth-open="login" title="Masuk / Daftar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </button>
                    <?php endif; ?>
                    
                    <!-- Search Toggle -->
                    <button type="button" id="searchToggle" aria-label="<?php esc_attr_e('Search', 'flavor-flavor'); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.3-4.3"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile Navigation (Dropdown) -->
        <nav class="mobile-nav" id="mobileNav">
            <?php
            $bookmark_page = get_page_by_path('bookmarks');
            $bookmark_url = $bookmark_page ? get_permalink($bookmark_page) : home_url('/bookmarks/');
            
            wp_nav_menu(array(
                'theme_location' => 'primary',
                'container'      => false,
                'menu_class'     => '',
                'fallback_cb'    => function() use ($bookmark_url) {
                    echo '<ul>';
                    echo '<li><a href="' . esc_url(home_url('/')) . '">' . __('Home', 'flavor-flavor') . '</a></li>';
                    if (post_type_exists('manhwa')) {
                        echo '<li><a href="' . esc_url(get_post_type_archive_link('manhwa')) . '">' . __('Manga List', 'flavor-flavor') . '</a></li>';
                    }
                    echo '<li><a href="' . esc_url($bookmark_url) . '">' . __('Bookmark', 'flavor-flavor') . '</a></li>';
                    echo '</ul>';
                },
            ));
            ?>
        </nav>
    </header>
    
    <?php 
    // Mobile Ticker Bar - Display only on mobile (CSS controlled)
    $ticker_enable = get_theme_mod('ticker_enable', false);
    $ticker_text = get_theme_mod('ticker_text', '');
    if ($ticker_enable && !empty($ticker_text)):
        $ticker_label = get_theme_mod('ticker_label', 'INFO');
        $ticker_speed = get_theme_mod('ticker_speed', 30);
        $ticker_link = get_theme_mod('ticker_link', '');
    ?>
    <div class="mobile-ticker-bar">
        <span class="ticker-label"><?php echo esc_html($ticker_label); ?></span>
        <div class="ticker-wrapper">
            <div class="ticker-content" style="animation-duration: <?php echo esc_attr($ticker_speed); ?>s;">
                <?php if (!empty($ticker_link)): ?>
                    <a href="<?php echo esc_url($ticker_link); ?>"><?php echo esc_html($ticker_text); ?></a>
                <?php else: ?>
                    <span><?php echo esc_html($ticker_text); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Search Modal -->
    <div class="search-modal" id="searchModal">
        <div class="search-modal-content">
            <form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>" autocomplete="off">
                <input type="search" class="search-input" id="liveSearchInput" placeholder="<?php esc_attr_e('Search manga...', 'flavor-flavor'); ?>" value="<?php echo get_search_query(); ?>" name="s">
                <input type="hidden" name="post_type" value="manhwa">
                <button type="submit" class="search-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.3-4.3"></path>
                    </svg>
                </button>
            </form>
            <!-- Live Search Results -->
            <div class="live-search-results" id="liveSearchResults"></div>
            <button type="button" class="search-close" id="searchClose">&times;</button>
        </div>
    </div>

    <?php 
    // Header Ad - Display directly
    if (function_exists('flavor_display_ad')) {
        flavor_display_ad('header');
    }
    ?>

    <main id="main" class="site-content">
