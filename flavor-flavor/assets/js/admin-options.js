/**
 * Flavor Theme - Admin Options JS
 */
(function ($) {
  "use strict";

  $(document).ready(function () {
    // Tab switching
    var $navItems = $(".fvo-nav-item");
    var $panels = $(".fvo-tab-panel");
    var $activeTabInput = $("#fvo_active_tab");

    $navItems.on("click", function (e) {
      e.preventDefault();
      var tab = $(this).data("tab");

      $navItems.removeClass("active");
      $(this).addClass("active");

      $panels.removeClass("active");
      $("#fvo-tab-" + tab).addClass("active");

      // Track active tab
      $activeTabInput.val(tab);

      // Update URL hash
      if (history.replaceState) {
        history.replaceState(null, null, "#" + tab);
      }
    });

    // Load tab from hidden input (after save) or URL hash
    var savedTab = $activeTabInput.val();
    var hash = window.location.hash.replace("#", "");
    var targetTab = hash || savedTab || "general";

    if (targetTab && $('[data-tab="' + targetTab + '"]').length) {
      $('[data-tab="' + targetTab + '"]').trigger("click");
    }

    // Ensure save button works - force form submit on click
    $(document).on("click", ".fvo-btn-save", function (e) {
      var $form = $(this).closest("form");
      if ($form.length) {
        $form.submit();
      }
    });

    // Initialize WP Color Pickers (wrapped in try-catch to prevent JS errors)
    try {
      if ($.fn.wpColorPicker) {
        $(".fvo-color-picker").wpColorPicker();
      }
    } catch (err) {
      console.log("Color picker init error:", err);
    }

    // Initialize WP Media Uploader
    $(document).on("click", ".fvo-media-upload", function (e) {
      e.preventDefault();
      var $btn = $(this);
      var $input = $btn.siblings(".fvo-media-input");
      var $preview = $btn.siblings(".fvo-media-preview");

      var frame = wp.media({
        title: "Select Image",
        button: { text: "Use this image" },
        multiple: false,
      });

      frame.on("select", function () {
        var attachment = frame.state().get("selection").first().toJSON();
        $input.val(attachment.url);
        if ($preview.length) {
          $preview.html('<img src="' + attachment.url + '" style="max-width:200px;border-radius:8px;">');
        }
      });

      frame.open();
    });

    // Media remove
    $(document).on("click", ".fvo-media-remove", function (e) {
      e.preventDefault();
      var $btn = $(this);
      $btn.siblings(".fvo-media-input").val("");
      $btn.siblings(".fvo-media-preview").html("");
    });

    // Auto-hide saved notice
    var $notice = $(".fvo-saved-notice");
    if ($notice.length) {
      setTimeout(function () {
        $notice.fadeOut(300, function () {
          $(this).remove();
        });
      }, 3000);
    }

    // Range input value display
    $(".fvo-range").on("input", function () {
      var $display = $(this).siblings(".fvo-range-value");
      if ($display.length) {
        $display.text($(this).val());
      }
    });
  });
})(jQuery);
