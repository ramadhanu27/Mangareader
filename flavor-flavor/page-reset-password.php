<?php
/**
 * Template Name: Reset Password
 * Custom reset password page
 */

if (!defined('ABSPATH')) exit;

// Already logged in? redirect to home
if (is_user_logged_in()) {
    wp_redirect(home_url('/'));
    exit;
}

$step = 'form'; // form | success | error
$error_msg = '';
$rp_key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';
$rp_login = isset($_GET['login']) ? sanitize_text_field($_GET['login']) : '';

// Validate key & login
if (empty($rp_key) || empty($rp_login)) {
    $step = 'error';
    $error_msg = 'Link reset password tidak valid atau sudah kadaluarsa.';
} else {
    // Check if key is valid
    $user = check_password_reset_key($rp_key, $rp_login);
    if (is_wp_error($user)) {
        $step = 'error';
        $error_msg = 'Link reset password tidak valid atau sudah kadaluarsa. Silakan request ulang.';
    }
}

// Handle form submission
if ($step === 'form' && isset($_POST['flavor_reset_password']) && wp_verify_nonce($_POST['reset_nonce'], 'flavor_reset_password')) {
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';
    
    if (empty($new_pass)) {
        $error_msg = 'Password baru wajib diisi.';
    } elseif (strlen($new_pass) < 6) {
        $error_msg = 'Password minimal 6 karakter.';
    } elseif ($new_pass !== $confirm_pass) {
        $error_msg = 'Konfirmasi password tidak cocok.';
    } else {
        // Re-validate key
        $user = check_password_reset_key($rp_key, $rp_login);
        if (is_wp_error($user)) {
            $step = 'error';
            $error_msg = 'Link reset password sudah kadaluarsa.';
        } else {
            // Reset password
            reset_password($user, $new_pass);
            $step = 'success';
        }
    }
}

get_header();
?>

<div class="reset-page">
    <div class="container">
        <div class="reset-card">
            
            <!-- Logo / Icon -->
            <div class="reset-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </div>

            <?php if ($step === 'success'): ?>
                <!-- Success -->
                <div class="reset-success">
                    <div class="reset-success-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#2ecc71" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                    </div>
                    <h1 class="reset-title">Password Berhasil Direset!</h1>
                    <p class="reset-desc">Password kamu sudah diperbarui. Silakan login dengan password baru.</p>
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="reset-btn" onclick="
                        if (typeof document.querySelector('[data-auth-open]') !== 'undefined') {
                            event.preventDefault();
                            window.location.href='<?php echo esc_url(home_url('/')); ?>';
                            setTimeout(function(){ 
                                var btn = document.querySelector('[data-auth-open]');
                                if(btn) btn.click();
                            }, 500);
                        }
                    ">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" x2="3" y1="12" y2="12"/></svg>
                        Login Sekarang
                    </a>
                </div>

            <?php elseif ($step === 'error'): ?>
                <!-- Error -->
                <div class="reset-error-state">
                    <div class="reset-error-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#e74c3c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="15" x2="9" y1="9" y2="15"/>
                            <line x1="9" x2="15" y1="9" y2="15"/>
                        </svg>
                    </div>
                    <h1 class="reset-title">Link Tidak Valid</h1>
                    <p class="reset-desc"><?php echo esc_html($error_msg); ?></p>
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="reset-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        Kembali ke Beranda
                    </a>
                </div>

            <?php else: ?>
                <!-- Reset Form -->
                <h1 class="reset-title">Reset Password</h1>
                <p class="reset-desc">Buat password baru untuk akun <strong><?php echo esc_html($rp_login); ?></strong></p>

                <?php if (!empty($error_msg)): ?>
                <div class="reset-alert reset-alert-error"><?php echo esc_html($error_msg); ?></div>
                <?php endif; ?>

                <form method="POST" class="reset-form">
                    <?php wp_nonce_field('flavor_reset_password', 'reset_nonce'); ?>
                    <input type="hidden" name="flavor_reset_password" value="1">
                    
                    <div class="reset-field">
                        <label for="resetNewPass">Password Baru</label>
                        <div class="reset-input-wrap">
                            <input type="password" id="resetNewPass" name="new_password" placeholder="Min. 6 karakter" required minlength="6" autocomplete="new-password">
                            <button type="button" class="reset-toggle-pass" data-target="resetNewPass" title="Tampilkan password">
                                <svg class="eye-show" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-hide" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" x2="23" y1="1" y2="23"/></svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="reset-field">
                        <label for="resetConfirmPass">Konfirmasi Password</label>
                        <div class="reset-input-wrap">
                            <input type="password" id="resetConfirmPass" name="confirm_password" placeholder="Ulangi password baru" required minlength="6" autocomplete="new-password">
                            <button type="button" class="reset-toggle-pass" data-target="resetConfirmPass" title="Tampilkan password">
                                <svg class="eye-show" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-hide" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" x2="23" y1="1" y2="23"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Password strength indicator -->
                    <div class="reset-strength" id="resetStrength">
                        <div class="reset-strength-bar"><div class="reset-strength-fill" id="resetStrengthFill"></div></div>
                        <span class="reset-strength-text" id="resetStrengthText"></span>
                    </div>
                    
                    <button type="submit" class="reset-btn reset-submit-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        Simpan Password Baru
                    </button>
                </form>
            <?php endif; ?>

        </div>
    </div>
</div>

<style>
/* =========================================
   RESET PASSWORD PAGE
   ========================================= */
.reset-page {
    min-height: 70vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 16px;
}

.reset-card {
    width: 100%;
    max-width: 420px;
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 36px 32px;
    text-align: center;
}

.reset-icon {
    margin-bottom: 20px;
    color: var(--primary-color);
}

.reset-title {
    font-size: 22px;
    font-weight: 700;
    color: var(--text-color);
    margin: 0 0 8px;
}

.reset-desc {
    font-size: 14px;
    color: var(--text-muted);
    margin: 0 0 24px;
    line-height: 1.5;
}

.reset-desc strong {
    color: var(--text-color);
}

/* Alert */
.reset-alert {
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 13px;
    margin-bottom: 18px;
    text-align: left;
}

.reset-alert-error {
    background: rgba(231, 76, 60, 0.12);
    color: #e74c3c;
    border: 1px solid rgba(231, 76, 60, 0.25);
}

/* Form */
.reset-form {
    text-align: left;
}

.reset-field {
    margin-bottom: 16px;
}

.reset-field label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-color);
    margin-bottom: 6px;
}

.reset-input-wrap {
    position: relative;
}

.reset-field input {
    width: 100%;
    padding: 11px 44px 11px 14px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: var(--body-bg);
    color: var(--text-color);
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s;
    box-sizing: border-box;
}

.reset-field input:focus {
    border-color: var(--primary-color);
}

.reset-toggle-pass {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    padding: 4px;
    display: flex;
    align-items: center;
}

.reset-toggle-pass:hover {
    color: var(--text-color);
}

/* Password Strength */
.reset-strength {
    display: none;
    align-items: center;
    gap: 10px;
    margin-bottom: 18px;
}

.reset-strength.visible {
    display: flex;
}

.reset-strength-bar {
    flex: 1;
    height: 4px;
    background: var(--border-color);
    border-radius: 4px;
    overflow: hidden;
}

.reset-strength-fill {
    height: 100%;
    width: 0%;
    border-radius: 4px;
    transition: width 0.3s, background 0.3s;
}

.reset-strength-text {
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
}

/* Button */
.reset-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 24px;
    background: var(--primary-color);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
    width: 100%;
}

.reset-btn:hover {
    opacity: 0.9;
    transform: translateY(-1px);
    color: #fff;
}

/* Success / Error states */
.reset-success,
.reset-error-state {
    padding: 10px 0;
}

.reset-success-icon,
.reset-error-icon {
    margin-bottom: 16px;
}

@media (max-width: 480px) {
    .reset-card {
        padding: 28px 20px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    document.querySelectorAll('.reset-toggle-pass').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var input = document.getElementById(this.getAttribute('data-target'));
            if (!input) return;
            var isPass = input.type === 'password';
            input.type = isPass ? 'text' : 'password';
            this.querySelector('.eye-show').style.display = isPass ? 'none' : 'block';
            this.querySelector('.eye-hide').style.display = isPass ? 'block' : 'none';
        });
    });

    // Password strength indicator
    var passInput = document.getElementById('resetNewPass');
    var strengthWrap = document.getElementById('resetStrength');
    var strengthFill = document.getElementById('resetStrengthFill');
    var strengthText = document.getElementById('resetStrengthText');
    
    if (passInput && strengthWrap) {
        passInput.addEventListener('input', function() {
            var val = this.value;
            if (!val) {
                strengthWrap.classList.remove('visible');
                return;
            }
            strengthWrap.classList.add('visible');
            
            var score = 0;
            if (val.length >= 6) score++;
            if (val.length >= 10) score++;
            if (/[a-z]/.test(val) && /[A-Z]/.test(val)) score++;
            if (/\d/.test(val)) score++;
            if (/[^a-zA-Z0-9]/.test(val)) score++;
            
            var levels = [
                { w: 20, color: '#e74c3c', text: 'Sangat Lemah' },
                { w: 40, color: '#e67e22', text: 'Lemah' },
                { w: 60, color: '#f1c40f', text: 'Cukup' },
                { w: 80, color: '#2ecc71', text: 'Kuat' },
                { w: 100, color: '#27ae60', text: 'Sangat Kuat' },
            ];
            var level = levels[Math.min(score, levels.length - 1)];
            
            strengthFill.style.width = level.w + '%';
            strengthFill.style.background = level.color;
            strengthText.textContent = level.text;
            strengthText.style.color = level.color;
        });
    }
});
</script>

<?php get_footer(); ?>
