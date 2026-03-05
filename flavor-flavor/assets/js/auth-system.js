/**
 * Auth System - Login / Register / Forgot Password
 */
(function () {
  "use strict";

  var CFG = window.flavorAuth || {};
  var modal, loginForm, registerForm, forgotForm, alertBox;

  document.addEventListener("DOMContentLoaded", function () {
    modal = document.getElementById("authModal");
    loginForm = document.getElementById("authLoginForm");
    registerForm = document.getElementById("authRegisterForm");
    forgotForm = document.getElementById("authForgotForm");
    alertBox = document.getElementById("authAlert");

    // User dropdown (for logged-in users)
    initUserMenu();
    initLogout();

    if (!modal) return;

    initTabs();
    initForms();
    initTogglePassword();
    initModalControls();
  });

  // ============================
  // MODAL CONTROLS
  // ============================
  function initModalControls() {
    // Close button
    var closeBtn = document.getElementById("authClose");
    if (closeBtn) {
      closeBtn.addEventListener("click", closeModal);
    }

    // Click overlay to close
    modal.addEventListener("click", function (e) {
      if (e.target === modal) closeModal();
    });

    // Escape key
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && modal.classList.contains("active")) {
        closeModal();
      }
    });

    // Open modal triggers - any element with data-auth-open
    document.querySelectorAll("[data-auth-open]").forEach(function (btn) {
      btn.addEventListener("click", function (e) {
        e.preventDefault();
        var tab = this.getAttribute("data-auth-open") || "login";
        openModal(tab);
      });
    });

    // Forgot password link
    var forgotLink = document.getElementById("authForgotLink");
    if (forgotLink) {
      forgotLink.addEventListener("click", function (e) {
        e.preventDefault();
        showForm("forgot");
      });
    }

    // Back to login from forgot
    var backBtn = document.getElementById("authBackToLogin");
    if (backBtn) {
      backBtn.addEventListener("click", function () {
        showForm("login");
      });
    }
  }

  function openModal(tab) {
    modal.classList.add("active");
    document.body.style.overflow = "hidden";
    showForm(tab || "login");
    hideAlert();
  }

  function closeModal() {
    modal.classList.remove("active");
    document.body.style.overflow = "";
  }

  // Expose globally
  window.openAuthModal = openModal;

  // ============================
  // TABS
  // ============================
  function initTabs() {
    document.querySelectorAll(".auth-tab").forEach(function (tab) {
      tab.addEventListener("click", function () {
        var target = this.getAttribute("data-tab");
        showForm(target);
      });
    });
  }

  function showForm(tab) {
    hideAlert();
    var tabs = document.getElementById("authTabs");

    if (tab === "forgot") {
      loginForm.style.display = "none";
      registerForm.style.display = "none";
      forgotForm.style.display = "block";
      tabs.style.display = "none";
    } else {
      forgotForm.style.display = "none";
      tabs.style.display = "flex";

      document.querySelectorAll(".auth-tab").forEach(function (t) {
        t.classList.toggle("active", t.getAttribute("data-tab") === tab);
      });

      if (tab === "login") {
        loginForm.style.display = "block";
        registerForm.style.display = "none";
      } else {
        loginForm.style.display = "none";
        registerForm.style.display = "block";
      }
    }
  }

  // ============================
  // FORMS
  // ============================
  function initForms() {
    // Login
    if (loginForm) {
      loginForm.addEventListener("submit", function (e) {
        e.preventDefault();
        var btn = document.getElementById("authLoginBtn");
        setLoading(btn, true);
        hideAlert();

        ajax(
          "flavor_auth_login",
          {
            username: document.getElementById("authLoginUser").value,
            password: document.getElementById("authLoginPass").value,
            remember: loginForm.querySelector('[name="remember"]').checked ? "1" : "0",
          },
          function (data) {
            setLoading(btn, false);
            showAlert(data.message, "success");
            setTimeout(function () {
              location.reload();
            }, 1000);
          },
          function (err) {
            setLoading(btn, false);
            showAlert(err, "error");
          },
        );
      });
    }

    // Register
    if (registerForm) {
      registerForm.addEventListener("submit", function (e) {
        e.preventDefault();
        var btn = document.getElementById("authRegBtn");

        var pass = document.getElementById("authRegPass").value;
        var confirm = document.getElementById("authRegConfirm").value;

        if (pass !== confirm) {
          showAlert("Konfirmasi password tidak cocok.", "error");
          return;
        }

        setLoading(btn, true);
        hideAlert();

        ajax(
          "flavor_auth_register",
          {
            username: document.getElementById("authRegUser").value,
            email: document.getElementById("authRegEmail").value,
            password: pass,
            confirm_password: confirm,
          },
          function (data) {
            setLoading(btn, false);
            showAlert(data.message, "success");
            setTimeout(function () {
              location.reload();
            }, 1000);
          },
          function (err) {
            setLoading(btn, false);
            showAlert(err, "error");
          },
        );
      });
    }

    // Forgot Password
    if (forgotForm) {
      forgotForm.addEventListener("submit", function (e) {
        e.preventDefault();
        var btn = document.getElementById("authForgotBtn");
        setLoading(btn, true);
        hideAlert();

        ajax(
          "flavor_auth_forgot_password",
          {
            email: document.getElementById("authForgotEmail").value,
          },
          function (data) {
            setLoading(btn, false);
            showAlert(data.message, "success");
          },
          function (err) {
            setLoading(btn, false);
            showAlert(err, "error");
          },
        );
      });
    }
  }

  // ============================
  // TOGGLE PASSWORD
  // ============================
  function initTogglePassword() {
    document.querySelectorAll(".auth-toggle-pass").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var targetId = this.getAttribute("data-target");
        var input = document.getElementById(targetId);
        if (input) {
          var isPass = input.type === "password";
          input.type = isPass ? "text" : "password";
          this.classList.toggle("active", isPass);
        }
      });
    });
  }

  // ============================
  // HELPERS
  // ============================
  function showAlert(msg, type) {
    if (!alertBox) return;
    alertBox.textContent = msg;
    alertBox.className = "auth-alert auth-alert-" + type;
    alertBox.style.display = "block";
  }

  function hideAlert() {
    if (!alertBox) return;
    alertBox.style.display = "none";
  }

  function setLoading(btn, loading) {
    if (!btn) return;
    var text = btn.querySelector(".auth-btn-text");
    var spin = btn.querySelector(".auth-btn-loading");
    if (loading) {
      btn.disabled = true;
      if (text) text.style.display = "none";
      if (spin) spin.style.display = "inline";
    } else {
      btn.disabled = false;
      if (text) text.style.display = "inline";
      if (spin) spin.style.display = "none";
    }
  }

  function ajax(action, data, onSuccess, onError) {
    var formData = new FormData();
    formData.append("action", action);
    formData.append("nonce", CFG.nonce);
    for (var key in data) {
      if (data.hasOwnProperty(key)) {
        formData.append(key, data[key]);
      }
    }

    fetch(CFG.ajaxurl, {
      method: "POST",
      body: formData,
      credentials: "same-origin",
    })
      .then(function (res) {
        return res.json();
      })
      .then(function (res) {
        if (res.success) {
          if (onSuccess) onSuccess(res.data);
        } else {
          if (onError) onError(res.data || "Terjadi kesalahan.");
        }
      })
      .catch(function () {
        if (onError) onError("Koneksi gagal. Coba lagi.");
      });
  }
  // ============================
  // USER MENU DROPDOWN
  // ============================
  function initUserMenu() {
    var userBtn = document.getElementById("headerUserBtn");
    var userMenu = document.getElementById("headerUserMenu");
    if (!userBtn || !userMenu) return;

    userBtn.addEventListener("click", function (e) {
      e.stopPropagation();
      userMenu.classList.toggle("active");
    });

    document.addEventListener("click", function (e) {
      if (!userMenu.contains(e.target)) {
        userMenu.classList.remove("active");
      }
    });
  }

  // ============================
  // LOGOUT
  // ============================
  function initLogout() {
    var logoutBtn = document.getElementById("headerLogoutBtn");
    if (!logoutBtn) return;

    logoutBtn.addEventListener("click", function (e) {
      e.preventDefault();

      ajax(
        "flavor_auth_logout",
        {},
        function () {
          location.reload();
        },
        function () {
          // Fallback: redirect to home
          location.href = CFG.logout_url || "/";
        },
      );
    });
  }
})();
