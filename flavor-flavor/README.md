# Flavor Flavor — WordPress Manhwa Theme

A modern, all-in-one manga/manhwa reader WordPress theme. No extra plugin required for core manhwa management — everything is built into the theme.

---

## ✨ Features

### 🎨 Core & Design

- 🌙 **Dark / Light Mode** — Toggle antar tema gelap dan terang
- 📱 **Fully Responsive** — Mobile-first design, optimal di semua perangkat
- 🎨 **Customizable Colors** — Primary & secondary color via admin panel
- ✍️ **Google Fonts (Poppins)** — Tipografi modern
- 🧩 **Sidebar Toggle** — Bisa diaktifkan/nonaktifkan per halaman (Home, Single, Archive, Search, Taxonomy)

### 📚 Manhwa Management (Built-in, No Plugin Required)

- 📖 **Manhwa CMS** — Kelola manhwa langsung dari menu WordPress admin (Manhwa CMS)
- ➕ **Add / Edit / Delete Manhwa** — Lengkap dengan cover, synopsis, status, type, rating, author, artist, genre
- 📑 **Chapter Management** — Tambah chapter dengan image URLs, drag & drop, sortir otomatis
- 📤 **Export / Import** — Backup dan restore data manhwa (JSON format)
- 📊 **Statistics Dashboard** — Lihat total views, top viewed, top chapters, top reacted, distribusi status/type, top genres

### 📖 Reader & Frontend

- 📜 **Vertical Scroll Reader** — Baca chapter secara vertikal dengan navigasi prev/next
- 🎠 **Hero Slider** — Slider di homepage dengan mode Manual, Most Viewed, Highest Rated, Latest
- 👁️ **Most Viewed Section** — Horizontal scroll card di homepage, bisa di-toggle on/off dari admin
- 🔥 **Hot Updates** — Section manhwa yang baru diupdate
- ⏱️ **Latest Updates** — Daftar update terbaru dengan paginasi, toggle view List/Grid
- 📊 **Trending Widget** — Sidebar widget dengan tab Mingguan/Bulanan/Semua
- 🔤 **A-Z List** — Halaman daftar manhwa dari A sampai Z

### 🔍 Search & Discovery

- 🔍 **Live Search** — Cari manhwa dengan autocomplete real-time
- 🏷️ **Genre Filtering** — Filter manhwa berdasarkan genre
- 📊 **Status & Type Badges** — Badge Ongoing, Completed, Hiatus, Manhwa, Manga, Manhua
- ⭐ **Rating Display** — Tampilkan rating bintang

### 💬 Social & Interaction

- 💬 **Custom Comments** — Sistem komentar sendiri tanpa WordPress comments (dengan spoiler, edit, delete, like, reply, upload gambar)
- 👍 **Post Reactions** — Reaksi per manhwa (Like, Funny, Nice, Sad, Angry) — bisa digunakan tanpa login
- 📚 **Bookmark System** — Simpan manhwa favorit menggunakan localStorage
- 📜 **Reading History** — Tracking progress baca per chapter

### 🔐 Auth System

- 🔑 **Login / Register Modal** — Popup login & daftar tanpa plugin tambahan
- 👤 **User Profile Page** — Halaman profil pengguna
- 🔒 **Reset Password** — Halaman reset password

### 📢 Marketing & Monetization

- 📢 **Ad Management** — Slot iklan: Header, After Content, Sidebar, Before Footer, In-Article, Float Bottom, Before/After Chapter
- 🔗 **Direct Link Ads** — Iklan direct link saat klik area kosong
- 📣 **Announcement Bar** — Notifikasi di atas halaman, bisa di-dismiss
- 📰 **News Ticker** — Running text di header
- 💼 **Ad Slots (Contact Page)** — 4 slot iklan yang bisa dijual

### ⚡ Performance & SEO

- 🚀 **HTML Minify** — Kompresi output HTML otomatis
- 🔒 **Hide WP Version** — Sembunyikan versi WordPress
- 🧹 **Remove Query Strings** — Hapus version string dari CSS/JS
- 🔍 **SEO Schema Markup** — JSON-LD structured data
- 📝 **Custom Meta Description** — Meta description homepage custom
- 🖼️ **Open Graph Image** — Default social share image

### 🛠️ Advanced

- 🚧 **Maintenance Mode** — Mode maintenance dengan countdown timer
- 📱 **Social Media Links** — Facebook, Instagram, Twitter/X, Discord, TikTok, YouTube, Telegram
- 📧 **Contact Info** — Email, WhatsApp, Telegram

---

## 📋 Requirements

| Requirement               | Version                                     |
| ------------------------- | ------------------------------------------- |
| WordPress                 | 5.0+                                        |
| PHP                       | 7.4+                                        |
| MySQL                     | 5.6+                                        |
| **Manhwa Scraper Plugin** | Latest (Optional, untuk scrape dari sumber) |

> **Note:** Theme ini sudah memiliki Manhwa CMS built-in. Tidak perlu plugin Manhwa Manager terpisah.

---

## 🚀 Tutorial Instalasi

### Step 1: Upload Theme

**Opsi A: Via WordPress Admin**

1. Buka **Appearance → Themes → Add New → Upload Theme**
2. Upload file `flavor-flavor.zip`
3. Klik **Install Now**
4. Klik **Activate**

**Opsi B: Via FTP / File Manager**

1. Extract file `flavor-flavor.zip`
2. Upload folder ke `wp-content/themes/`
3. Pastikan nama folder: `flavor-flavor`
4. Aktifkan di **Appearance → Themes**

### Step 2: Flush Permalinks

1. Buka **Settings → Permalinks**
2. Pilih **Post name** (disarankan)
3. Klik **Save Changes**

> ⚠️ Ini WAJIB dilakukan. Jika tidak, halaman chapter dan manhwa bisa error 404.

### Step 3: Konfigurasi Dasar

1. Buka **Flavor Options** (muncul di sidebar admin)
2. Atur **Primary Color** dan **Secondary Color** sesuai selera
3. Aktifkan/nonaktifkan fitur yang diinginkan:
   - Hero Slider → On/Off + pilih mode
   - Most Viewed Section → On/Off + jumlah manhwa
   - Sidebar → On/Off per jenis halaman
   - Announcements, Ticker, Ads, dll.
4. Klik **Save Changes**

### Step 4: Setup Halaman

Halaman **Reader** dan **Bookmarks** otomatis dibuat ketika tema diaktifkan. Jika belum ada:

| Halaman   | Slug        | Template       |
| --------- | ----------- | -------------- |
| Reader    | `reader`    | Chapter Reader |
| Bookmarks | `bookmarks` | Bookmarks      |
| Contact   | `contact`   | Contact        |
| A-Z List  | `az-list`   | A-Z List       |
| Profile   | `profile`   | Profile        |

**Cara buat manual:**

1. **Pages → Add New**
2. Isi judul sesuai tabel
3. Set slug sesuai tabel
4. Pilih **Page Template** sesuai tabel
5. **Publish**

### Step 5: Setup Menu

**Primary Menu (Navigasi Utama):**

1. Buka **Appearance → Menus**
2. Buat menu baru: "Primary Menu"
3. Tambahkan:
   - Home (Link ke homepage)
   - Manhwa List (Custom Link: `/manhwa/`)
   - A-Z List (Link ke halaman A-Z)
   - Bookmarks (Link ke halaman Bookmarks)
   - Contact (Link ke halaman Contact)
4. Di **Menu Settings**, centang ✅ **Primary Menu**
5. Klik **Save Menu**

**Footer Menu:**

1. Buat menu baru: "Footer Menu"
2. Tambahkan: About, Privacy Policy, Terms, dll.
3. Centang ✅ **Footer Menu**
4. Klik **Save Menu**

### Step 6: Tambah Manhwa Pertama

1. Buka **Manhwa CMS** di sidebar admin
2. Klik **Add New**
3. Isi:
   - **Title** — judul manhwa
   - **Cover Image** — upload/set Featured Image
   - **Synopsis** — deskripsi
   - **Type** — Manhwa / Manga / Manhua
   - **Status** — Ongoing / Completed / Hiatus
   - **Rating** — 1 sampai 10
   - **Author / Artist**
   - **Genre** — pilih atau buat genre baru
4. Klik **Publish**

### Step 7: Tambah Chapter

1. Edit manhwa yang sudah dibuat
2. Scroll ke bagian **Chapters**
3. Klik **Add Chapter**
4. Isi:
   - **Chapter Number** (misal: 1, 2, 3)
   - **Image URLs** — satu URL per baris
5. Simpan

### Step 8: Install Manhwa Scraper (Opsional)

Jika ingin menggunakan fitur scrape otomatis dari sumber lain:

1. Buka **Plugins → Add New → Upload Plugin**
2. Upload `manhwa-scraper.zip`
3. **Install & Activate**
4. Menu **Manhwa Scraper** akan muncul di sidebar admin

---

## ⚙️ Flavor Options — Panduan Lengkap

Semua pengaturan tema ada di **Flavor Options** (sidebar WordPress admin).

### Tab: General

| Setting            | Default     | Keterangan                                     |
| ------------------ | ----------- | ---------------------------------------------- |
| Primary Color      | `#ff5722`   | Warna utama tema                               |
| Secondary Color    | `#ff5722`   | Warna sekunder                                 |
| Hero Slider Enable | ✅ On       | Aktifkan slider di homepage                    |
| Slider Mode        | Most Viewed | Manual / Most Viewed / Highest Rated / Latest  |
| Number of Slides   | 8           | 3–15                                           |
| Auto-play Speed    | 5000ms      | 2000–10000ms                                   |
| Most Viewed Enable | ✅ On       | Tampilkan section Most Viewed di homepage      |
| Most Viewed Count  | 10          | 3–20                                           |
| Sidebar Settings   | Per-page    | Aktifkan/nonaktifkan sidebar per jenis halaman |

### Tab: Announcements

| Setting          | Keterangan                     |
| ---------------- | ------------------------------ |
| Announcement Bar | Notifikasi bar di atas halaman |
| News Ticker      | Running text di header         |

### Tab: Contact & Social

| Setting           | Keterangan                                                         |
| ----------------- | ------------------------------------------------------------------ |
| Contact Info      | Email, WhatsApp, Telegram                                          |
| Social Media URLs | Facebook, Instagram, Twitter/X, Discord, TikTok, YouTube, Telegram |

### Tab: Ads Management

| Slot               | Ukuran     | Posisi                       |
| ------------------ | ---------- | ---------------------------- |
| Header Ad          | 728x90     | Di bawah header              |
| After Content      | 728x90     | Setelah konten utama         |
| Sidebar Ad         | 300x250    | Di area sidebar              |
| Before Footer      | 728x90     | Sebelum footer               |
| In-Article         | Responsive | Dalam chapter reader         |
| Float Bottom       | 728x90     | Sticky di bawah (closeable)  |
| Before Chapter     | Responsive | Sebelum gambar chapter       |
| After Chapter      | Responsive | Setelah gambar chapter       |
| Direct Link        | —          | Iklan saat klik area kosong  |
| Ad Slots (Contact) | Custom     | 4 slot untuk halaman Contact |

### Tab: Widgets

| Setting           | Keterangan                                 |
| ----------------- | ------------------------------------------ |
| Trending Widget   | Sort, jumlah, tabs, genre, rating          |
| Comments Settings | Enable/disable, pilih system (WP / Disqus) |

### Tab: SEO & Tracking

| Setting          | Keterangan                                |
| ---------------- | ----------------------------------------- |
| Meta Description | Custom homepage meta description          |
| OG Image         | Default social share image                |
| Schema Markup    | JSON-LD structured data                   |
| Tracking Codes   | Google Analytics, Histats, custom scripts |

### Tab: Advanced

| Setting              | Keterangan                         |
| -------------------- | ---------------------------------- |
| Minify HTML          | Kompresi output HTML               |
| Hide WP Version      | Sembunyikan generator tag          |
| Remove Query Strings | Hapus version string dari assets   |
| Maintenance Mode     | Mode maintenance + countdown timer |

---

## 📁 Struktur File Theme

```
flavor-flavor/
├── assets/
│   ├── css/
│   │   ├── admin-manhwa.css       # Styling Manhwa CMS admin
│   │   └── admin-options.css      # Styling Flavor Options
│   ├── js/
│   │   ├── theme.js               # Dark mode, bookmarks, search, slider
│   │   ├── auth-system.js         # Login/register modal
│   │   ├── custom-comments.js     # Custom comment system
│   │   ├── post-reactions.js      # Post reactions (like, funny, etc.)
│   │   └── admin-options.js       # Admin options panel
│   └── images/
│       ├── no-image.svg           # Placeholder image
│       ├── manhwa.png             # Type badge
│       ├── manga.png              # Type badge
│       └── manhua.png             # Type badge
├── inc/
│   ├── manhwa-core.php            # Custom post type, taxonomy, rewrite rules
│   ├── manhwa-admin.php           # Manhwa CMS admin (list, edit, stats)
│   ├── manhwa-export-import.php   # Export/Import manhwa data
│   ├── admin-options.php          # Flavor Options save handler
│   ├── admin-options-tabs.php     # Flavor Options tab UI
│   ├── auth-system.php            # Login, register, forgot password
│   ├── bookmark-system.php        # Bookmark API
│   ├── custom-comments.php        # Custom comment system (DB, AJAX)
│   ├── post-reactions.php         # Post reactions (DB, AJAX)
│   ├── reading-history.php        # Reading history tracking
│   ├── maintenance-mode.php       # Maintenance mode
│   ├── minify.php                 # HTML minifier
│   └── seo.php                    # SEO meta tags, schema
├── template-parts/
│   ├── auth-modal.php             # Login/register popup
│   ├── custom-comments.php        # Comments UI template
│   └── post-reactions.php         # Reactions UI template
├── style.css                      # Main stylesheet (9000+ lines)
├── functions.php                  # Theme setup & helpers
├── header.php                     # Header template
├── footer.php                     # Footer template
├── front-page.php                 # Homepage (slider, hot, most viewed, latest)
├── index.php                      # Fallback template
├── single-manhwa.php              # Manhwa detail page
├── archive-manhwa.php             # Manhwa archive/list
├── taxonomy-manhwa_genre.php      # Genre archive
├── search.php                     # Search results
├── sidebar.php                    # Sidebar (trending, genres, random)
├── page.php                       # Static page
├── page-contact.php               # Contact page template
├── page-az-list.php               # A-Z list template
├── page-profile.php               # User profile template
├── page-reset-password.php        # Reset password template
├── template-chapter-reader.php    # Chapter reader
├── template-bookmarks.php         # Bookmarks page
├── single.php                     # Single post
├── comments.php                   # WP comments fallback
├── 404.php                        # 404 error page
└── screenshot.png                 # Theme preview
```

---

## 🔗 URL Structure

| Halaman        | URL Pattern                            |
| -------------- | -------------------------------------- |
| Homepage       | `/`                                    |
| Manhwa List    | `/manhwa/`                             |
| Single Manhwa  | `/manhwa/{slug}/`                      |
| Chapter Reader | `/reader/{manhwa-slug}/{chapter-num}/` |
| Genre Archive  | `/manhwa_genre/{genre-slug}/`          |
| Search         | `/?s={query}`                          |
| Bookmarks      | `/bookmarks/`                          |
| A-Z List       | `/az-list/`                            |
| Contact        | `/contact/`                            |
| Profile        | `/profile/`                            |

---

## ❓ Troubleshooting

### Chapter page 404?

1. Buka **Settings → Permalinks**
2. Klik **Save Changes** (flush rewrite rules)
3. Clear cache browser

### Gambar tidak muncul?

1. Cek apakah URL gambar valid
2. Untuk gambar external, pastikan sumber mengizinkan hotlinking
3. Gunakan Manhwa Scraper plugin untuk download gambar ke lokal

### Slider tidak muncul?

1. Buka **Flavor Options → General**
2. Pastikan Hero Slider aktif (toggle On)
3. Untuk Manual mode, minimal 1 manhwa harus di-set "Featured"

### Most Viewed tidak muncul?

1. Buka **Flavor Options → General**
2. Pastikan Most Viewed Section aktif (toggle On)
3. Manhwa harus punya data views (minimal 1 kali dikunjungi)

### Comments tidak muncul?

1. Buka **Flavor Options → Widgets**
2. Pastikan Comments enabled (toggle On)

---

## 📝 Credits

- Original Template: ZeistManga v5.5 by EmissionHex
- WordPress Conversion: Flavor
- Icons: Material Symbols, Feather Icons
- Font: Poppins (Google Fonts)

---

## 📄 License

GNU General Public License v2 or later

---

## 🆘 Support

Untuk masalah dan permintaan fitur, silakan hubungi developer tema atau buka issue di repository.
