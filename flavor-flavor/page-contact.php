<?php
/**
 * Template Name: Contact & Advertising
 * Description: Contact page for advertising inquiries
 *
 * @package Flavor_Flavor
 */

get_header();

$contact_email = get_theme_mod('contact_email', 'admin@example.com');
$contact_whatsapp = get_theme_mod('contact_whatsapp', '');
$contact_telegram = get_theme_mod('contact_telegram', '');
?>

<div class="container">
    <div class="site-main">
        <div id="primary" class="content-area full-width">
            
            <article class="contact-page">
                <!-- Page Header -->
                <header class="page-header-hero">
                    <div class="page-header-content">
                        <h1 class="page-title"><?php the_title(); ?></h1>
                        <p class="page-subtitle"><?php esc_html_e('Pasang Iklan & Kerjasama', 'flavor-flavor'); ?></p>
                    </div>
                </header>
                
                <div class="contact-grid">
                    <!-- Contact Info -->
                    <div class="contact-info-section">
                        <div class="card">
                            <div class="card-header">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                </svg>
                                <?php esc_html_e('Hubungi Kami', 'flavor-flavor'); ?>
                            </div>
                            <div class="card-body">
                                <div class="contact-methods">
                                    <!-- Email -->
                                    <a href="mailto:<?php echo esc_attr($contact_email); ?>" class="contact-method">
                                        <div class="contact-icon email">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                                <polyline points="22,6 12,13 2,6"/>
                                            </svg>
                                        </div>
                                        <div class="contact-details">
                                            <span class="contact-label">Email</span>
                                            <span class="contact-value"><?php echo esc_html($contact_email); ?></span>
                                        </div>
                                    </a>
                                    
                                    <?php if ($contact_whatsapp): ?>
                                    <!-- WhatsApp -->
                                    <a href="https://wa.me/<?php echo esc_attr(preg_replace('/[^0-9]/', '', $contact_whatsapp)); ?>" target="_blank" class="contact-method">
                                        <div class="contact-icon whatsapp">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                            </svg>
                                        </div>
                                        <div class="contact-details">
                                            <span class="contact-label">WhatsApp</span>
                                            <span class="contact-value"><?php echo esc_html($contact_whatsapp); ?></span>
                                        </div>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($contact_telegram): ?>
                                    <!-- Telegram -->
                                    <a href="https://t.me/<?php echo esc_attr($contact_telegram); ?>" target="_blank" class="contact-method">
                                        <div class="contact-icon telegram">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                                            </svg>
                                        </div>
                                        <div class="contact-details">
                                            <span class="contact-label">Telegram</span>
                                            <span class="contact-value">@<?php echo esc_html($contact_telegram); ?></span>
                                        </div>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Ad Packages -->
                        <div class="card mt-20">
                            <div class="card-header">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="3" y1="9" x2="21" y2="9"/>
                                    <line x1="9" y1="21" x2="9" y2="9"/>
                                </svg>
                                <?php esc_html_e('Slot Iklan', 'flavor-flavor'); ?>
                            </div>
                            <div class="card-body">
                                <div class="promo-slots">
                                    <?php 
                                    $has_slots = false;
                                    for ($i = 1; $i <= 4; $i++):
                                        $slot_name = get_theme_mod("ad_slot_{$i}_name", '');
                                        $slot_size = get_theme_mod("ad_slot_{$i}_size", '');
                                        $slot_desc = get_theme_mod("ad_slot_{$i}_desc", '');
                                        $slot_status = get_theme_mod("ad_slot_{$i}_status", 'available');
                                        
                                        // Skip hidden slots or empty slots
                                        if ($slot_status === 'hidden' || empty($slot_name)) continue;
                                        $has_slots = true;
                                    ?>
                                    <div class="promo-slot <?php echo $slot_status === 'sold' ? 'sold' : ''; ?>">
                                        <div class="promo-slot-header">
                                            <h3><?php echo esc_html($slot_name); ?></h3>
                                            <?php if ($slot_size): ?>
                                                <span class="promo-size"><?php echo esc_html($slot_size); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($slot_desc): ?>
                                            <p class="promo-desc"><?php echo esc_html($slot_desc); ?></p>
                                        <?php endif; ?>
                                        <div class="promo-status">
                                            <?php if ($slot_status === 'available'): ?>
                                                <span class="promo-status-badge available">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                                        <polyline points="22 4 12 14.01 9 11.01"/>
                                                    </svg>
                                                    <?php esc_html_e('Tersedia', 'flavor-flavor'); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="promo-status-badge sold">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <circle cx="12" cy="12" r="10"/>
                                                        <line x1="15" y1="9" x2="9" y2="15"/>
                                                        <line x1="9" y1="9" x2="15" y2="15"/>
                                                    </svg>
                                                    <?php esc_html_e('Sold Out', 'flavor-flavor'); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endfor; ?>
                                    
                                    <?php if (!$has_slots): ?>
                                    <p class="no-slots"><?php esc_html_e('Belum ada slot iklan yang dikonfigurasi.', 'flavor-flavor'); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- .contact-grid -->
            </article>
        </div>
    </div>
</div>

<?php get_footer(); ?>

