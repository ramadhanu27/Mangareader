<?php
/**
 * Auth Modal Template - Login / Register / Forgot Password
 */
if (!defined('ABSPATH')) exit;
?>

<!-- Auth Modal Overlay -->
<div class="auth-modal-overlay" id="authModal">
    <div class="auth-modal">
        <!-- Close button -->
        <button type="button" class="auth-close" id="authClose">&times;</button>
        
        <!-- Tabs -->
        <div class="auth-tabs" id="authTabs">
            <button type="button" class="auth-tab active" data-tab="login">Masuk</button>
            <button type="button" class="auth-tab" data-tab="register">Daftar</button>
        </div>
        
        <!-- Alert message -->
        <div class="auth-alert" id="authAlert" style="display:none;"></div>
        
        <!-- LOGIN FORM -->
        <form class="auth-form" id="authLoginForm">
            <div class="auth-field">
                <label for="authLoginUser">Username atau Email</label>
                <input type="text" id="authLoginUser" name="username" placeholder="Masukkan username atau email" autocomplete="username" required>
            </div>
            <div class="auth-field">
                <label for="authLoginPass">Password</label>
                <div class="auth-password-wrap">
                    <input type="password" id="authLoginPass" name="password" placeholder="Masukkan password" autocomplete="current-password" required>
                    <button type="button" class="auth-toggle-pass" data-target="authLoginPass">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>
            <div class="auth-options">
                <label class="auth-checkbox">
                    <input type="checkbox" name="remember" value="1" checked> Ingat saya
                </label>
                <a href="#" class="auth-forgot-link" id="authForgotLink">Lupa password?</a>
            </div>
            <button type="submit" class="auth-submit-btn" id="authLoginBtn">
                <span class="auth-btn-text">Masuk</span>
                <span class="auth-btn-loading" style="display:none;">Memproses...</span>
            </button>
        </form>
        
        <!-- REGISTER FORM -->
        <form class="auth-form" id="authRegisterForm" style="display:none;">
            <div class="auth-field">
                <label for="authRegUser">Username</label>
                <input type="text" id="authRegUser" name="username" placeholder="Username (min. 3 karakter)" autocomplete="username" minlength="3" maxlength="30" required>
                <small class="auth-field-hint">Huruf, angka, dan underscore saja</small>
            </div>
            <div class="auth-field">
                <label for="authRegEmail">Email</label>
                <input type="email" id="authRegEmail" name="email" placeholder="email@contoh.com" autocomplete="email" required>
            </div>
            <div class="auth-field">
                <label for="authRegPass">Password</label>
                <div class="auth-password-wrap">
                    <input type="password" id="authRegPass" name="password" placeholder="Min. 6 karakter" autocomplete="new-password" minlength="6" required>
                    <button type="button" class="auth-toggle-pass" data-target="authRegPass">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>
            <div class="auth-field">
                <label for="authRegConfirm">Konfirmasi Password</label>
                <div class="auth-password-wrap">
                    <input type="password" id="authRegConfirm" name="confirm_password" placeholder="Ulangi password" autocomplete="new-password" minlength="6" required>
                    <button type="button" class="auth-toggle-pass" data-target="authRegConfirm">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>
            <button type="submit" class="auth-submit-btn" id="authRegBtn">
                <span class="auth-btn-text">Daftar</span>
                <span class="auth-btn-loading" style="display:none;">Memproses...</span>
            </button>
        </form>
        
        <!-- FORGOT PASSWORD FORM -->
        <form class="auth-form" id="authForgotForm" style="display:none;">
            <div class="auth-forgot-header">
                <button type="button" class="auth-back-btn" id="authBackToLogin">← Kembali ke login</button>
            </div>
            <h3 class="auth-form-title">Reset Password</h3>
            <p class="auth-form-desc">Masukkan email yang terdaftar. Kami akan mengirim link untuk reset password.</p>
            <div class="auth-field">
                <label for="authForgotEmail">Email</label>
                <input type="email" id="authForgotEmail" name="email" placeholder="email@contoh.com" autocomplete="email" required>
            </div>
            <button type="submit" class="auth-submit-btn" id="authForgotBtn">
                <span class="auth-btn-text">Kirim Link Reset</span>
                <span class="auth-btn-loading" style="display:none;">Mengirim...</span>
            </button>
        </form>
    </div>
</div>
