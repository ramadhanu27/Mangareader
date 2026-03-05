/**
 * Post Reactions System - Frontend
 */
(function () {
  "use strict";

  var CFG = window.flavorReactions || {};

  document.addEventListener("DOMContentLoaded", function () {
    loadReactions();
    bindReactionButtons();
  });

  function loadReactions() {
    ajax("flavor_reaction_load", { post_id: CFG.post_id, token: CFG.user_token || "" }, function (data) {
      updateUI(data.counts, data.user_reaction, data.total);
    });
  }

  function bindReactionButtons() {
    document.querySelectorAll(".pr-reaction-btn").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var type = this.getAttribute("data-type");
        var token = CFG.user_token;

        if (!token) {
          // Auto-generate anonymous token for guest users
          token = getCookie("flavor_reaction_token");
          if (!token) {
            token = "anon_" + generateToken(32);
            setCookie("flavor_reaction_token", token, 365);
          }
          CFG.user_token = token;
        }

        // Disable buttons briefly
        var btns = document.querySelectorAll(".pr-reaction-btn");
        btns.forEach(function (b) {
          b.disabled = true;
        });

        ajax(
          "flavor_reaction_react",
          {
            post_id: CFG.post_id,
            type: type,
            token: token,
          },
          function (data) {
            updateUI(data.counts, data.user_reaction, data.total);
            btns.forEach(function (b) {
              b.disabled = false;
            });
          },
          function () {
            btns.forEach(function (b) {
              b.disabled = false;
            });
          },
        );
      });
    });
  }

  function updateUI(counts, userReaction, total) {
    // Update total
    var totalEl = document.getElementById("prTotalCount");
    if (totalEl) totalEl.textContent = total;

    // Update each count
    var types = ["like", "funny", "nice", "sad", "angry"];
    types.forEach(function (type) {
      var countEl = document.getElementById("prCount" + capitalize(type));
      if (countEl) countEl.textContent = counts[type] || 0;
    });

    // Highlight active reaction
    document.querySelectorAll(".pr-reaction-btn").forEach(function (btn) {
      var type = btn.getAttribute("data-type");
      if (type === userReaction) {
        btn.classList.add("pr-active");
      } else {
        btn.classList.remove("pr-active");
      }
    });
  }

  function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
  }

  function getCookie(name) {
    var match = document.cookie.match(new RegExp("(^| )" + name + "=([^;]+)"));
    return match ? decodeURIComponent(match[2]) : "";
  }

  function setCookie(name, value, days) {
    var expires = "";
    if (days) {
      var date = new Date();
      date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
      expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + encodeURIComponent(value) + expires + "; path=/";
  }

  function generateToken(length) {
    var chars = "abcdefghijklmnopqrstuvwxyz0123456789";
    var result = "";
    for (var i = 0; i < length; i++) {
      result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return result;
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
          console.error("Reaction error:", res.data);
          if (onError) onError(res.data);
        }
      })
      .catch(function (err) {
        console.error("Reaction fetch error:", err);
        if (onError) onError(err);
      });
  }
})();
