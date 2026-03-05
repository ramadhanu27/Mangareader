/**
 * Flavor Flavor Theme JavaScript
 * Dark mode, Bookmarks, Search, Reading Progress
 *
 * @package Flavor_Flavor
 */

(function ($) {
  "use strict";

  // =========================================
  // Dark Mode Toggle
  // =========================================
  const DarkMode = {
    init: function () {
      this.toggle = document.getElementById("darkModeToggle");
      this.body = document.body;

      // Check saved preference
      const savedMode = localStorage.getItem("flavor_dark_mode");
      if (savedMode === "true") {
        this.enable();
      }

      // Toggle event
      if (this.toggle) {
        this.toggle.addEventListener("change", () => {
          if (this.toggle.checked) {
            this.enable();
          } else {
            this.disable();
          }
        });
      }
    },

    enable: function () {
      this.body.classList.add("dark-mode");
      if (this.toggle) this.toggle.checked = true;
      localStorage.setItem("flavor_dark_mode", "true");
    },

    disable: function () {
      this.body.classList.remove("dark-mode");
      if (this.toggle) this.toggle.checked = false;
      localStorage.setItem("flavor_dark_mode", "false");
    },
  };

  // =========================================
  // Announcement Box
  // =========================================
  const Announcement = {
    init: function () {
      this.box = document.getElementById("announcementBar");
      this.closeBtn = document.getElementById("announcementClose");

      if (!this.box) return;

      // Check if announcement was dismissed
      const announcementId = this.box.dataset.id;
      const dismissedId = localStorage.getItem("flavor_announcement_dismissed");

      if (dismissedId === announcementId) {
        this.box.classList.add("hidden");
        return;
      }

      // Bind close event
      if (this.closeBtn) {
        this.closeBtn.addEventListener("click", () => this.dismiss());
      }
    },

    dismiss: function () {
      if (!this.box) return;

      const announcementId = this.box.dataset.id;
      localStorage.setItem("flavor_announcement_dismissed", announcementId);

      // Animate out (fade and collapse)
      this.box.style.transition = "all 0.4s ease";
      this.box.style.opacity = "0";
      this.box.style.maxHeight = "0";
      this.box.style.marginBottom = "0";
      this.box.style.padding = "0";
      this.box.style.overflow = "hidden";

      setTimeout(() => {
        this.box.classList.add("hidden");
      }, 400);
    },
  };

  // =========================================
  // Bookmark System
  // =========================================
  const Bookmarks = {
    storageKey: "flavor_bookmarks",

    init: function () {
      this.bookmarkBtn = document.getElementById("bookmarkBtn");
      this.bookmarkToggle = document.getElementById("bookmarkToggle");
      this.bookmarkMenuBtn = document.getElementById("bookmarkMenuBtn");
      this.mobileBookmarkBtn = document.getElementById("mobileBookmarkBtn");
      this.bookmarkPopup = document.getElementById("bookmarkPopup");
      this.bookmarkClose = document.getElementById("bookmarkClose");
      this.bookmarkList = document.getElementById("bookmarkList");
      this.bookmarkCount = document.getElementById("bookmarkCount");

      this.updateCount();
      this.checkCurrentPage();
      this.bindEvents();

      // Sync localStorage to server on login
      this.syncToServer();
    },

    bindEvents: function () {
      // Add/Remove bookmark
      if (this.bookmarkBtn) {
        this.bookmarkBtn.addEventListener("click", () => this.toggleBookmark());
      }

      // Open popup
      [this.bookmarkToggle, this.bookmarkMenuBtn, this.mobileBookmarkBtn].forEach((btn) => {
        if (btn) {
          btn.addEventListener("click", (e) => {
            e.preventDefault();
            this.openPopup();
          });
        }
      });

      // Close popup
      if (this.bookmarkClose) {
        this.bookmarkClose.addEventListener("click", () => this.closePopup());
      }

      // Close on backdrop click
      if (this.bookmarkPopup) {
        this.bookmarkPopup.addEventListener("click", (e) => {
          if (e.target === this.bookmarkPopup) {
            this.closePopup();
          }
        });
      }
    },

    getBookmarks: function () {
      const data = localStorage.getItem(this.storageKey);
      return data ? JSON.parse(data) : [];
    },

    saveBookmarks: function (bookmarks) {
      localStorage.setItem(this.storageKey, JSON.stringify(bookmarks));
      this.updateCount();
    },

    addBookmark: function (manga) {
      const bookmarks = this.getBookmarks();
      const exists = bookmarks.find((b) => b.id === manga.id);

      if (!exists) {
        bookmarks.unshift(manga);
        this.saveBookmarks(bookmarks);
        this.showNotification(flavorData.strings.bookmarkAdded);
        return true;
      }
      return false;
    },

    removeBookmark: function (id) {
      let bookmarks = this.getBookmarks();
      bookmarks = bookmarks.filter((b) => b.id !== id);
      this.saveBookmarks(bookmarks);
      this.showNotification(flavorData.strings.bookmarkRemoved);
    },

    isBookmarked: function (id) {
      const bookmarks = this.getBookmarks();
      return bookmarks.some((b) => b.id === id);
    },

    toggleBookmark: function () {
      if (!this.bookmarkBtn) return;

      const id = this.bookmarkBtn.dataset.id;
      const title = this.bookmarkBtn.dataset.title;
      const url = this.bookmarkBtn.dataset.url;
      const image = this.bookmarkBtn.dataset.image;

      if (this.isBookmarked(id)) {
        this.removeBookmark(id);
        this.bookmarkBtn.classList.remove("bookmarked");
        this.bookmarkBtn.querySelector("span").textContent = "Add to Bookmark";
      } else {
        this.addBookmark({ id, title, url, image, date: new Date().toISOString() });
        this.bookmarkBtn.classList.add("bookmarked");
        this.bookmarkBtn.querySelector("span").textContent = "Bookmarked";
      }

      // Sync to server if logged in
      if (typeof flavorAuth !== "undefined" && flavorAuth.logged_in === "1") {
        const formData = new FormData();
        formData.append("action", "flavor_bookmark_toggle");
        formData.append("nonce", flavorAuth.nonce);
        formData.append("post_id", id);
        fetch(flavorAuth.ajaxurl, { method: "POST", body: formData, credentials: "same-origin" });
      }
    },

    checkCurrentPage: function () {
      if (!this.bookmarkBtn) return;

      const id = this.bookmarkBtn.dataset.id;
      if (this.isBookmarked(id)) {
        this.bookmarkBtn.classList.add("bookmarked");
        this.bookmarkBtn.querySelector("span").textContent = "Bookmarked";
      }
    },

    syncToServer: function () {
      if (typeof flavorAuth === "undefined" || flavorAuth.logged_in !== "1") return;

      const localBookmarks = this.getBookmarks();
      const localIds = localBookmarks.map((b) => String(b.id));

      if (localIds.length === 0) return;

      // Check if already synced this session
      if (sessionStorage.getItem("flavor_bookmarks_synced")) return;
      sessionStorage.setItem("flavor_bookmarks_synced", "1");

      const formData = new FormData();
      formData.append("action", "flavor_bookmark_sync");
      formData.append("nonce", flavorAuth.nonce);
      formData.append("local_ids", JSON.stringify(localIds.map(Number)));

      fetch(flavorAuth.ajaxurl, { method: "POST", body: formData, credentials: "same-origin" })
        .then((r) => r.json())
        .then((res) => {
          if (res.success && res.data.ids) {
            // Update localStorage with server data (source of truth)
            const serverIds = res.data.ids;
            const updatedLocal = [];
            serverIds.forEach((sid) => {
              const existing = localBookmarks.find((b) => String(b.id) === String(sid));
              if (existing) {
                updatedLocal.push(existing);
              }
            });
            localStorage.setItem(this.storageKey, JSON.stringify(updatedLocal));
            this.updateCount();
          }
        })
        .catch(() => {});
    },

    updateCount: function () {
      if (this.bookmarkCount) {
        const count = this.getBookmarks().length;
        this.bookmarkCount.textContent = count;
        this.bookmarkCount.style.display = count > 0 ? "inline" : "none";
      }
    },

    openPopup: function () {
      if (!this.bookmarkPopup) return;

      this.renderList();
      this.bookmarkPopup.classList.add("active");
      document.body.style.overflow = "hidden";
    },

    closePopup: function () {
      if (!this.bookmarkPopup) return;

      this.bookmarkPopup.classList.remove("active");
      document.body.style.overflow = "";
    },

    renderList: function () {
      if (!this.bookmarkList) return;

      const bookmarks = this.getBookmarks();

      if (bookmarks.length === 0) {
        this.bookmarkList.innerHTML = `
                    <div class="text-center text-muted">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="margin-bottom: 10px;">
                            <path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"></path>
                        </svg>
                        <p>${flavorData.strings.noBookmarks}</p>
                    </div>
                `;
        return;
      }

      let html = "";
      bookmarks.forEach((bookmark) => {
        html += `
                    <div class="bookmark-list-item" data-id="${bookmark.id}">
                        <img src="${bookmark.image || ""}" alt="${bookmark.title}" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 140%22><rect fill=%22%23ddd%22 width=%22100%22 height=%22140%22/></svg>'">
                        <div class="bookmark-list-info">
                            <a href="${bookmark.url}" class="bookmark-list-title">${bookmark.title}</a>
                            <div class="bookmark-list-chapter text-muted">
                                Added: ${new Date(bookmark.date).toLocaleDateString()}
                            </div>
                        </div>
                        <button type="button" class="bookmark-remove" data-id="${bookmark.id}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 6h18"></path>
                                <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                            </svg>
                        </button>
                    </div>
                `;
      });

      this.bookmarkList.innerHTML = html;

      // Bind remove events
      this.bookmarkList.querySelectorAll(".bookmark-remove").forEach((btn) => {
        btn.addEventListener("click", (e) => {
          const id = btn.dataset.id;
          this.removeBookmark(id);
          this.renderList();
          this.checkCurrentPage();
        });
      });
    },

    showNotification: function (message) {
      // Create notification element
      const notification = document.createElement("div");
      notification.className = "flavor-notification";
      notification.textContent = message;
      notification.style.cssText = `
                position: fixed;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%);
                background: var(--primary-color, #ff5722);
                color: white;
                padding: 12px 24px;
                border-radius: 8px;
                z-index: 10000;
                animation: slideUp 0.3s ease;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            `;

      document.body.appendChild(notification);

      setTimeout(() => {
        notification.style.animation = "slideDown 0.3s ease";
        setTimeout(() => notification.remove(), 300);
      }, 2000);
    },
  };

  // =========================================
  // Search Modal with Live Search
  // =========================================
  const Search = {
    searchTimeout: null,

    init: function () {
      this.toggle = document.getElementById("searchToggle");
      this.modal = document.getElementById("searchModal");
      this.close = document.getElementById("searchClose");
      this.input = document.getElementById("liveSearchInput");
      this.results = document.getElementById("liveSearchResults");

      this.bindEvents();
    },

    bindEvents: function () {
      if (this.toggle) {
        this.toggle.addEventListener("click", () => this.open());
      }

      if (this.close) {
        this.close.addEventListener("click", () => this.closeModal());
      }

      if (this.modal) {
        this.modal.addEventListener("click", (e) => {
          if (e.target === this.modal) {
            this.closeModal();
          }
        });
      }

      // Live search on input
      if (this.input) {
        this.input.addEventListener("input", (e) => this.handleInput(e));
        this.input.addEventListener("keydown", (e) => {
          if (e.key === "ArrowDown" || e.key === "ArrowUp") {
            e.preventDefault();
            this.navigateResults(e.key);
          }
        });
      }

      // ESC key to close
      document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && this.modal && this.modal.classList.contains("active")) {
          this.closeModal();
        }
      });
    },

    handleInput: function (e) {
      const query = e.target.value.trim();

      // Clear previous timeout
      if (this.searchTimeout) {
        clearTimeout(this.searchTimeout);
      }

      // Hide results if query is too short
      if (query.length < 2) {
        this.hideResults();
        return;
      }

      // Debounce search
      this.searchTimeout = setTimeout(() => {
        this.performSearch(query);
      }, 300);
    },

    performSearch: function (query) {
      if (!this.results) return;

      // Show loading
      this.results.innerHTML = '<div class="live-search-loading"><div class="spinner"></div> Searching...</div>';
      this.results.classList.add("active");

      // AJAX request
      fetch(`${flavorData.ajaxUrl}?action=flavor_live_search&s=${encodeURIComponent(query)}`)
        .then((response) => response.json())
        .then((data) => {
          this.renderResults(data, query);
        })
        .catch((error) => {
          console.error("Search error:", error);
          this.results.innerHTML = '<div class="live-search-empty">Error searching. Please try again.</div>';
        });
    },

    renderResults: function (results, query) {
      if (!this.results) return;

      if (results.length === 0) {
        this.results.innerHTML = `<div class="live-search-empty">No results found for "${query}"</div>`;
        return;
      }

      let html = '<div class="live-search-list">';

      results.forEach((item, index) => {
        html += `
          <a href="${item.url}" class="live-search-item" data-index="${index}">
            <div class="live-search-thumb">
              <img src="${item.thumbnail}" alt="${item.title}" loading="lazy">
            </div>
            <div class="live-search-info">
              <div class="live-search-title">${this.highlightMatch(item.title, query)}</div>
              <div class="live-search-meta">
                ${item.type ? `<span class="live-search-type type-${item.type.toLowerCase()}">${item.type}</span>` : ""}
                ${item.status ? `<span class="live-search-status">${item.status}</span>` : ""}
                ${item.chapter ? `<span class="live-search-chapter">${item.chapter}</span>` : ""}
              </div>
            </div>
          </a>
        `;
      });

      html += "</div>";
      html += `<a href="${flavorData.homeUrl}?s=${encodeURIComponent(query)}&post_type=manhwa" class="live-search-viewall">View All Results →</a>`;

      this.results.innerHTML = html;
    },

    highlightMatch: function (text, query) {
      const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")})`, "gi");
      return text.replace(regex, "<mark>$1</mark>");
    },

    navigateResults: function (key) {
      const items = this.results?.querySelectorAll(".live-search-item");
      if (!items || items.length === 0) return;

      const current = this.results.querySelector(".live-search-item.focused");
      let index = current ? parseInt(current.dataset.index) : -1;

      if (key === "ArrowDown") {
        index = (index + 1) % items.length;
      } else {
        index = index <= 0 ? items.length - 1 : index - 1;
      }

      items.forEach((item) => item.classList.remove("focused"));
      items[index].classList.add("focused");
      items[index].scrollIntoView({ block: "nearest" });
    },

    hideResults: function () {
      if (this.results) {
        this.results.classList.remove("active");
        this.results.innerHTML = "";
      }
    },

    open: function () {
      if (this.modal) {
        this.modal.classList.add("active");
        if (this.input) {
          setTimeout(() => this.input.focus(), 100);
        }
      }
    },

    closeModal: function () {
      if (this.modal) {
        this.modal.classList.remove("active");
        this.hideResults();
        if (this.input) {
          this.input.value = "";
        }
      }
    },
  };

  // =========================================
  // Mobile Menu - REMOVED
  // Handled by inline script in footer.php
  // =========================================

  // =========================================
  // Reading Progress Bar
  // =========================================
  const ReadingProgress = {
    init: function () {
      this.progress = document.getElementById("readingProgress");

      if (this.progress) {
        window.addEventListener("scroll", () => this.update());
        this.update();
      }
    },

    update: function () {
      const scrollTop = window.scrollY;
      const docHeight = document.documentElement.scrollHeight - window.innerHeight;
      const percentage = Math.min((scrollTop / docHeight) * 100, 100);

      if (this.progress) {
        this.progress.style.width = percentage + "%";
      }
    },
  };

  // =========================================
  // Image Protection (optional)
  // =========================================
  const ImageProtection = {
    init: function () {
      // Disable right-click on images in reader
      const readerImages = document.querySelectorAll(".reader-images img");
      readerImages.forEach((img) => {
        img.addEventListener("contextmenu", (e) => e.preventDefault());
      });
    },
  };

  // =========================================
  // Initialize All Modules
  // =========================================
  document.addEventListener("DOMContentLoaded", function () {
    DarkMode.init();
    Announcement.init();
    Bookmarks.init();
    Search.init();
    // MobileMenu.init(); // Disabled - handled by inline script in footer.php
    ReadingProgress.init();
    ImageProtection.init();
  });

  // =========================================
  // Add CSS Animations
  // =========================================
  const style = document.createElement("style");
  style.textContent = `
        @keyframes slideUp {
            from { opacity: 0; transform: translateX(-50%) translateY(20px); }
            to { opacity: 1; transform: translateX(-50%) translateY(0); }
        }
        @keyframes slideDown {
            from { opacity: 1; transform: translateX(-50%) translateY(0); }
            to { opacity: 0; transform: translateX(-50%) translateY(20px); }
        }
        
        .search-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
            z-index: 9999;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding-top: 100px;
            opacity: 0;
            visibility: hidden;
            transition: 0.3s;
        }
        
        .search-modal.active {
            opacity: 1;
            visibility: visible;
        }
        
        .search-modal-content {
            background: var(--card-bg, white);
            padding: 20px;
            border-radius: 10px;
            width: calc(100% - 40px);
            max-width: 600px;
            display: flex;
            gap: 10px;
            position: relative;
        }
        
        .search-modal .search-input {
            flex: 1;
        }
        
        .search-close {
            position: absolute;
            top: -40px;
            right: 0;
            background: none;
            border: none;
            color: white;
            font-size: 30px;
            cursor: pointer;
        }
        
        .bookmark-count {
            background: #dc3545;
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 3px;
        }
    `;
  document.head.appendChild(style);

  // =========================================
  // Filter Dropdown Toggle
  // =========================================
  const filterToggles = document.querySelectorAll(".filter-dropdown-toggle");

  filterToggles.forEach((toggle) => {
    toggle.addEventListener("click", function (e) {
      e.preventDefault();
      const dropdown = this.closest(".filter-dropdown");
      const isActive = dropdown.classList.contains("active");

      // Close all other dropdowns
      document.querySelectorAll(".filter-dropdown.active").forEach((d) => {
        if (d !== dropdown) d.classList.remove("active");
      });

      // Toggle current dropdown
      dropdown.classList.toggle("active");
    });
  });

  // Close dropdown when clicking outside
  document.addEventListener("click", function (e) {
    if (!e.target.closest(".filter-dropdown")) {
      document.querySelectorAll(".filter-dropdown.active").forEach((d) => {
        d.classList.remove("active");
      });
    }
  });

  // =========================================
  // Hero Slider
  // =========================================
  const heroSlider = document.getElementById("heroSlider");
  if (heroSlider) {
    const heroSection = heroSlider.closest(".hero-slider");
    const sliderSpeed = heroSection ? parseInt(heroSection.dataset.speed) || 5000 : 5000;
    const slides = heroSlider.querySelectorAll(".hero-slide");
    const dots = document.querySelectorAll(".hero-dot");
    const prevBtn = document.getElementById("heroPrev");
    const nextBtn = document.getElementById("heroNext");
    let currentIndex = 0;
    let autoPlayInterval;

    function showSlide(index) {
      // Wrap around
      if (index >= slides.length) index = 0;
      if (index < 0) index = slides.length - 1;

      // Update slides
      slides.forEach((slide, i) => {
        slide.classList.toggle("active", i === index);
      });

      // Update dots
      dots.forEach((dot, i) => {
        dot.classList.toggle("active", i === index);
      });

      currentIndex = index;
    }

    function nextSlide() {
      showSlide(currentIndex + 1);
    }

    function prevSlide() {
      showSlide(currentIndex - 1);
    }

    // Auto-play
    function startAutoPlay() {
      autoPlayInterval = setInterval(nextSlide, sliderSpeed);
    }

    function stopAutoPlay() {
      clearInterval(autoPlayInterval);
    }

    // Event listeners
    if (nextBtn)
      nextBtn.addEventListener("click", () => {
        stopAutoPlay();
        nextSlide();
        startAutoPlay();
      });
    if (prevBtn)
      prevBtn.addEventListener("click", () => {
        stopAutoPlay();
        prevSlide();
        startAutoPlay();
      });

    dots.forEach((dot, i) => {
      dot.addEventListener("click", () => {
        stopAutoPlay();
        showSlide(i);
        startAutoPlay();
      });
    });

    // Touch/swipe support
    let touchStartX = 0;
    let touchEndX = 0;

    heroSlider.addEventListener(
      "touchstart",
      (e) => {
        touchStartX = e.changedTouches[0].screenX;
        stopAutoPlay();
      },
      { passive: true },
    );

    heroSlider.addEventListener(
      "touchend",
      (e) => {
        touchEndX = e.changedTouches[0].screenX;
        if (touchStartX - touchEndX > 50) nextSlide();
        if (touchEndX - touchStartX > 50) prevSlide();
        startAutoPlay();
      },
      { passive: true },
    );

    // Start auto-play
    startAutoPlay();

    // Pause on hover
    heroSlider.addEventListener("mouseenter", stopAutoPlay);
    heroSlider.addEventListener("mouseleave", startAutoPlay);
  }
})(jQuery);
