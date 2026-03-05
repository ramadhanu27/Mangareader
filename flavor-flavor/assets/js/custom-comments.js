/**
 * Custom Comments System - Frontend
 */
(function () {
  "use strict";

  const CFG = window.flavorComments || {};
  let currentSort = "popular";
  let replyTo = 0;
  let editingId = 0;
  let pendingImageUrl = "";

  // ============================
  // INIT
  // ============================
  document.addEventListener("DOMContentLoaded", function () {
    initEditor();
    initToolbar();
    initImageUpload();
    initSortButtons();
    loadComments();
    initSendButton();
  });

  // ============================
  // EDITOR
  // ============================
  function initEditor() {
    const textarea = document.getElementById("fcTextarea");
    if (!textarea) return;

    textarea.addEventListener("input", function () {
      updateCharCount();
    });

    textarea.addEventListener("paste", function (e) {
      e.preventDefault();
      var text = (e.clipboardData || window.clipboardData).getData("text/plain");
      document.execCommand("insertText", false, text);
    });

    textarea.addEventListener("keydown", function (e) {
      if (e.key === "Enter" && !e.shiftKey) {
        // Allow shift+enter for new line
      }
    });
  }

  function updateCharCount() {
    var textarea = document.getElementById("fcTextarea");
    var counter = document.getElementById("fcCharCount");
    if (!textarea || !counter) return;
    var text = textarea.innerText || "";
    var len = text.length;
    counter.textContent = len;
    counter.parentElement.classList.toggle("fc-over-limit", len > 1000);
  }

  function getEditorContent() {
    var textarea = document.getElementById("fcTextarea");
    if (!textarea) return "";
    return textarea.innerHTML
      .replace(/<div>/gi, "<br>")
      .replace(/<\/div>/gi, "")
      .trim();
  }

  function clearEditor() {
    var textarea = document.getElementById("fcTextarea");
    if (textarea) {
      textarea.innerHTML = "";
      updateCharCount();
    }
    replyTo = 0;
    editingId = 0;
    pendingImageUrl = "";
    var replyBar = document.getElementById("fcReplyBar");
    if (replyBar) replyBar.remove();
    // Reset image preview
    var preview = document.getElementById("fcImagePreview");
    if (preview) preview.style.display = "none";
    var previewImg = document.getElementById("fcImagePreviewImg");
    if (previewImg) previewImg.src = "";
    var fileInput = document.getElementById("fcImageInput");
    if (fileInput) fileInput.value = "";
  }

  // ============================
  // TOOLBAR
  // ============================
  function initToolbar() {
    document.querySelectorAll(".fc-tool-btn").forEach(function (btn) {
      btn.addEventListener("mousedown", function (e) {
        e.preventDefault();
      });
      btn.addEventListener("click", function (e) {
        e.preventDefault();
        var cmd = this.getAttribute("data-command");
        var textarea = document.getElementById("fcTextarea");
        textarea.focus();

        if (cmd === "spoiler") {
          var sel = window.getSelection();
          if (sel.rangeCount > 0 && !sel.isCollapsed) {
            // Wrap selected text with spoiler tags
            var txt = sel.toString();
            document.execCommand("insertText", false, "[spoiler]" + txt + "[/spoiler]");
          } else {
            // No selection: insert empty tags
            document.execCommand("insertText", false, "[spoiler][/spoiler]");
          }
        } else {
          document.execCommand(cmd, false, null);
        }
        updateCharCount();
      });
    });
  }

  // ============================
  // SORT BUTTONS
  // ============================
  function initSortButtons() {
    document.querySelectorAll(".fc-sort-btn").forEach(function (btn) {
      btn.addEventListener("click", function () {
        document.querySelectorAll(".fc-sort-btn").forEach(function (b) {
          b.classList.remove("active");
        });
        this.classList.add("active");
        currentSort = this.getAttribute("data-sort");
        loadComments();
      });
    });
  }

  // ============================
  // SEND BUTTON
  // ============================
  function initSendButton() {
    var sendBtn = document.getElementById("fcSendBtn");
    if (!sendBtn) return;

    sendBtn.addEventListener("click", function () {
      if (editingId > 0) {
        submitEdit();
      } else {
        submitComment();
      }
    });
  }

  // ============================
  // SUBMIT COMMENT
  // ============================
  function submitComment() {
    var content = getEditorContent();
    if (!content || content === "<br>") return;

    doSubmitComment(content);
  }

  function setUser(name, callback) {
    ajax("flavor_comment_set_user", { name: name }, function (data) {
      CFG.user_token = data.token;
      CFG.user_name = data.name;
      CFG.user_color = data.color;

      // Update UI
      var nameInput = document.getElementById("fcNameInput");
      if (nameInput) nameInput.style.display = "none";

      var formHeader = document.querySelector(".fc-form-header");
      if (!formHeader) {
        var wrapper = document.querySelector(".fc-form-wrapper");
        var header = document.createElement("div");
        header.className = "fc-form-header";
        header.innerHTML = '<div class="fc-avatar" style="background-color:' + data.color + '">' + data.name.charAt(0).toUpperCase() + '</div><span class="fc-current-user">' + escapeHtml(data.name) + "</span>";
        wrapper.insertBefore(header, wrapper.firstChild);
      }

      if (callback) callback();
    });
  }

  function doSubmitComment(content) {
    // Append image if pending
    if (pendingImageUrl) {
      content += '<br><img src="' + pendingImageUrl + '" alt="comment image" class="fc-comment-img" loading="lazy">';
    }

    var sendBtn = document.getElementById("fcSendBtn");
    sendBtn.disabled = true;
    sendBtn.textContent = "...";

    ajax(
      "flavor_comment_add",
      {
        post_id: CFG.post_id,
        parent_id: replyTo,
        content: content,
        token: CFG.user_token,
        name: CFG.user_name,
        color: CFG.user_color,
      },
      function (data) {
        clearEditor();
        loadComments();
        sendBtn.disabled = false;
        sendBtn.textContent = "Send";
      },
      function () {
        sendBtn.disabled = false;
        sendBtn.textContent = "Send";
      },
    );
  }

  // ============================
  // IMAGE UPLOAD
  // ============================
  function initImageUpload() {
    var imageBtn = document.getElementById("fcImageBtn");
    var fileInput = document.getElementById("fcImageInput");
    var preview = document.getElementById("fcImagePreview");
    var previewImg = document.getElementById("fcImagePreviewImg");
    var removeBtn = document.getElementById("fcImageRemove");
    var uploading = document.getElementById("fcImageUploading");

    if (!imageBtn || !fileInput) return;

    // Click image button → trigger file input
    imageBtn.addEventListener("click", function (e) {
      e.preventDefault();
      fileInput.click();
    });

    // File selected
    fileInput.addEventListener("change", function () {
      var file = this.files[0];
      if (!file) return;

      // Validate size
      var maxSize = parseInt(CFG.max_upload_size || "2") * 1024 * 1024;
      if (file.size > maxSize) {
        alert("Ukuran file maksimal " + CFG.max_upload_size + "MB.");
        this.value = "";
        return;
      }

      // Validate type
      if (!/^image\/(jpeg|png|gif|webp)$/.test(file.type)) {
        alert("Format file harus JPG, PNG, GIF, atau WebP.");
        this.value = "";
        return;
      }

      // Show local preview
      var reader = new FileReader();
      reader.onload = function (e) {
        if (previewImg) previewImg.src = e.target.result;
        if (preview) preview.style.display = "block";
      };
      reader.readAsDataURL(file);

      // Upload to server
      uploadImage(file);
    });

    // Remove image
    if (removeBtn) {
      removeBtn.addEventListener("click", function () {
        pendingImageUrl = "";
        if (preview) preview.style.display = "none";
        if (previewImg) previewImg.src = "";
        if (fileInput) fileInput.value = "";
      });
    }
  }

  function uploadImage(file) {
    var uploading = document.getElementById("fcImageUploading");
    var removeBtn = document.getElementById("fcImageRemove");
    var imageBtn = document.getElementById("fcImageBtn");

    if (uploading) uploading.style.display = "inline";
    if (removeBtn) removeBtn.style.display = "none";
    if (imageBtn) imageBtn.disabled = true;

    var formData = new FormData();
    formData.append("action", "flavor_comment_upload_image");
    formData.append("nonce", CFG.nonce);
    formData.append("image", file);

    fetch(CFG.ajaxurl, {
      method: "POST",
      body: formData,
      credentials: "same-origin",
    })
      .then(function (res) {
        return res.json();
      })
      .then(function (res) {
        if (uploading) uploading.style.display = "none";
        if (removeBtn) removeBtn.style.display = "flex";
        if (imageBtn) imageBtn.disabled = false;

        if (res.success) {
          pendingImageUrl = res.data.url;
          var previewImg = document.getElementById("fcImagePreviewImg");
          if (previewImg) previewImg.src = res.data.url;
        } else {
          alert(res.data || "Gagal upload gambar.");
          // Reset preview
          var preview = document.getElementById("fcImagePreview");
          if (preview) preview.style.display = "none";
          pendingImageUrl = "";
          var fileInput = document.getElementById("fcImageInput");
          if (fileInput) fileInput.value = "";
        }
      })
      .catch(function (err) {
        if (uploading) uploading.style.display = "none";
        if (removeBtn) removeBtn.style.display = "flex";
        if (imageBtn) imageBtn.disabled = false;
        alert("Gagal upload gambar.");
        pendingImageUrl = "";
        var preview = document.getElementById("fcImagePreview");
        if (preview) preview.style.display = "none";
        var fileInput = document.getElementById("fcImageInput");
        if (fileInput) fileInput.value = "";
      });
  }

  // ============================
  // EDIT COMMENT
  // ============================
  function startEdit(id, content) {
    editingId = id;
    var textarea = document.getElementById("fcTextarea");
    textarea.innerHTML = content;
    textarea.focus();

    // Show edit bar
    var existing = document.getElementById("fcReplyBar");
    if (existing) existing.remove();

    var bar = document.createElement("div");
    bar.id = "fcReplyBar";
    bar.className = "fc-reply-bar";
    bar.innerHTML = "<span>✏️ Mengedit komentar</span><button type=\"button\" onclick=\"this.parentElement.remove(); document.getElementById('fcTextarea').innerHTML=''; window._fcEditingId=0;\">✕</button>";

    var formWrapper = document.querySelector(".fc-form-wrapper");
    formWrapper.insertBefore(bar, formWrapper.firstChild);

    var sendBtn = document.getElementById("fcSendBtn");
    sendBtn.textContent = "Update";

    // Scroll to form
    formWrapper.scrollIntoView({ behavior: "smooth", block: "center" });
    updateCharCount();
  }

  function submitEdit() {
    var content = getEditorContent();
    if (!content || content === "<br>") return;

    var sendBtn = document.getElementById("fcSendBtn");
    sendBtn.disabled = true;

    ajax(
      "flavor_comment_edit",
      {
        comment_id: editingId,
        content: content,
        token: CFG.user_token,
      },
      function () {
        clearEditor();
        loadComments();
        sendBtn.disabled = false;
        sendBtn.textContent = "Send";
      },
      function () {
        sendBtn.disabled = false;
        sendBtn.textContent = "Send";
      },
    );
  }

  // ============================
  // LOAD COMMENTS
  // ============================
  function loadComments() {
    var list = document.getElementById("fcCommentsList");
    var loading = document.getElementById("fcLoading");
    if (loading) loading.style.display = "flex";

    ajax(
      "flavor_comment_load",
      {
        post_id: CFG.post_id,
        sort: currentSort,
        token: CFG.user_token || "",
      },
      function (data) {
        if (loading) loading.style.display = "none";
        renderComments(data.comments);
        document.getElementById("fcTotalCount").textContent = data.total;
      },
      function () {
        if (loading) loading.style.display = "none";
        list.innerHTML = '<div class="fc-empty">Gagal memuat komentar</div>';
      },
    );
  }

  // ============================
  // RENDER COMMENTS
  // ============================
  function renderComments(comments) {
    var list = document.getElementById("fcCommentsList");
    if (!comments || comments.length === 0) {
      list.innerHTML = '<div class="fc-empty">Belum ada komentar. Jadilah yang pertama!</div>';
      return;
    }

    var html = "";
    comments.forEach(function (c) {
      html += renderSingleComment(c);

      // Replies
      if (c.replies && c.replies.length > 0) {
        html += '<div class="fc-replies-toggle" data-parent="' + c.id + '">';
        html += '<span class="fc-toggle-icon">▴</span> ' + c.reply_count + " balasan";
        html += "</div>";
        html += '<div class="fc-replies" id="fcReplies' + c.id + '">';
        c.replies.forEach(function (r) {
          html += renderSingleComment(r, true);
        });
        html += "</div>";
      }
    });

    list.innerHTML = html;
    bindCommentActions();
  }

  function renderSingleComment(c, isReply) {
    var cls = "fc-comment" + (isReply ? " fc-reply" : "");
    var youBadge = c.is_owner ? ' <span class="fc-you-badge">YOU</span>' : "";
    var editedBadge = c.is_edited ? ' <span class="fc-edited">(diedit)</span>' : "";

    var actions =
      '<button class="fc-action-btn fc-like-btn' +
      (c.is_liked ? " liked" : "") +
      '" data-id="' +
      c.id +
      '">' +
      '<svg width="14" height="14" viewBox="0 0 24 24" fill="' +
      (c.is_liked ? "currentColor" : "none") +
      '" stroke="currentColor" stroke-width="2"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg> ' +
      "<span>" +
      c.likes +
      "</span></button>";

    actions += '<button class="fc-action-btn fc-reply-btn" data-id="' + c.id + '" data-name="' + escapeHtml(c.author_name) + '">↩ Balas</button>';

    if (c.can_edit) {
      actions += '<button class="fc-action-btn fc-edit-btn" data-id="' + c.id + '" data-content="">✏ Edit</button>';
    }
    if (c.can_delete) {
      actions += '<button class="fc-action-btn fc-delete-btn" data-id="' + c.id + '">🗑 Hapus</button>';
    }
    if (!c.is_owner) {
      actions += '<button class="fc-action-btn fc-report-btn" data-id="' + c.id + '">⚑ Laporkan</button>';
    }

    return (
      '<div class="' +
      cls +
      '" id="fc-comment-' +
      c.id +
      '">' +
      '<div class="fc-comment-left">' +
      '<div class="fc-avatar" style="background-color:' +
      c.author_color +
      '">' +
      c.initial +
      "</div>" +
      "</div>" +
      '<div class="fc-comment-right">' +
      '<div class="fc-comment-meta">' +
      '<span class="fc-author">' +
      escapeHtml(c.author_name) +
      "</span>" +
      youBadge +
      '<span class="fc-time">• ' +
      c.time_ago +
      "</span>" +
      editedBadge +
      "</div>" +
      '<div class="fc-comment-content">' +
      c.content +
      "</div>" +
      '<div class="fc-comment-actions">' +
      actions +
      "</div>" +
      "</div>" +
      "</div>"
    );
  }

  // ============================
  // BIND ACTIONS
  // ============================
  function bindCommentActions() {
    // Like
    document.querySelectorAll(".fc-like-btn").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var id = this.getAttribute("data-id");
        var btnEl = this;
        if (!CFG.user_token) return;
        ajax("flavor_comment_like", { comment_id: id, token: CFG.user_token }, function (data) {
          var span = btnEl.querySelector("span");
          span.textContent = data.likes;
          btnEl.classList.toggle("liked", data.action === "liked");
          var svg = btnEl.querySelector("svg");
          svg.setAttribute("fill", data.action === "liked" ? "currentColor" : "none");
        });
      });
    });

    // Reply
    document.querySelectorAll(".fc-reply-btn").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var id = this.getAttribute("data-id");
        var name = this.getAttribute("data-name");
        replyTo = parseInt(id);
        editingId = 0;

        var existing = document.getElementById("fcReplyBar");
        if (existing) existing.remove();

        var bar = document.createElement("div");
        bar.id = "fcReplyBar";
        bar.className = "fc-reply-bar";
        bar.innerHTML = "<span>↩ Membalas <b>" + escapeHtml(name) + '</b></span><button type="button" class="fc-cancel-reply">✕</button>';

        var formWrapper = document.querySelector(".fc-form-wrapper");
        formWrapper.insertBefore(bar, formWrapper.firstChild);

        bar.querySelector(".fc-cancel-reply").addEventListener("click", function () {
          bar.remove();
          replyTo = 0;
        });

        var textarea = document.getElementById("fcTextarea");
        textarea.innerHTML = "<b>@" + escapeHtml(name) + "</b> - ";
        textarea.focus();

        // Move cursor to end
        var range = document.createRange();
        range.selectNodeContents(textarea);
        range.collapse(false);
        var sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(range);

        formWrapper.scrollIntoView({ behavior: "smooth", block: "center" });
        updateCharCount();

        var sendBtn = document.getElementById("fcSendBtn");
        sendBtn.textContent = "Send";
      });
    });

    // Edit
    document.querySelectorAll(".fc-edit-btn").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var id = this.getAttribute("data-id");
        var commentEl = document.getElementById("fc-comment-" + id);
        var content = commentEl.querySelector(".fc-comment-content").innerHTML;
        startEdit(parseInt(id), content);
      });
    });

    // Delete
    document.querySelectorAll(".fc-delete-btn").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var id = this.getAttribute("data-id");
        if (!confirm("Hapus komentar ini?")) return;
        ajax("flavor_comment_delete", { comment_id: id, token: CFG.user_token }, function () {
          loadComments();
        });
      });
    });

    // Report
    document.querySelectorAll(".fc-report-btn").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var id = this.getAttribute("data-id");
        var btnEl = this;
        if (!confirm("Laporkan komentar ini?")) return;
        ajax("flavor_comment_report", { comment_id: id }, function () {
          btnEl.textContent = "✓ Dilaporkan";
          btnEl.disabled = true;
        });
      });
    });

    // Toggle replies
    document.querySelectorAll(".fc-replies-toggle").forEach(function (toggle) {
      toggle.addEventListener("click", function () {
        var parentId = this.getAttribute("data-parent");
        var replies = document.getElementById("fcReplies" + parentId);
        if (replies) {
          replies.classList.toggle("fc-replies-hidden");
          var icon = this.querySelector(".fc-toggle-icon");
          icon.textContent = replies.classList.contains("fc-replies-hidden") ? "▾" : "▴";
        }
      });
    });

    // Spoiler reveal/hide
    document.querySelectorAll(".fc-spoiler").forEach(function (spoiler) {
      if (spoiler.dataset.initialized) return;
      spoiler.dataset.initialized = "1";
      // Save original content and replace with label
      spoiler.dataset.content = spoiler.innerHTML;
      spoiler.innerHTML = "⚠ Spoiler <small>(klik untuk buka)</small>";
      spoiler.addEventListener("click", function () {
        if (this.classList.contains("revealed")) {
          this.classList.remove("revealed");
          this.innerHTML = "⚠ Spoiler <small>(klik untuk buka)</small>";
        } else {
          this.classList.add("revealed");
          this.innerHTML = this.dataset.content;
        }
      });
    });

    // Image lightbox
    document.querySelectorAll(".fc-comment-content img").forEach(function (img) {
      img.style.cursor = "pointer";
      img.addEventListener("click", function (e) {
        e.preventDefault();
        var lightbox = document.createElement("div");
        lightbox.className = "fc-lightbox";
        lightbox.innerHTML = '<img src="' + this.src + '" alt="Image">' + '<button class="fc-lightbox-close" type="button">✕</button>';
        document.body.appendChild(lightbox);

        lightbox.addEventListener("click", function (ev) {
          if (ev.target === lightbox || ev.target.classList.contains("fc-lightbox-close")) {
            lightbox.remove();
          }
        });

        document.addEventListener("keydown", function handler(ev) {
          if (ev.key === "Escape") {
            lightbox.remove();
            document.removeEventListener("keydown", handler);
          }
        });
      });
    });
  }

  // ============================
  // AJAX HELPER
  // ============================
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
          console.error("Comment error:", res.data);
          if (onError) onError(res.data);
        }
      })
      .catch(function (err) {
        console.error("Comment fetch error:", err);
        if (onError) onError(err);
      });
  }

  // ============================
  // UTILS
  // ============================
  function escapeHtml(str) {
    var div = document.createElement("div");
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

  // Expose for inline handlers
  window._fcEditingId = 0;
})();
