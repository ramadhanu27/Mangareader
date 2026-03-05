# 🔍 Panduan SEO - Flavor Theme

Dokumentasi lengkap untuk konfigurasi SEO pada website manhwa/manga.

---

## 📋 Fitur SEO Bawaan Theme

Theme ini sudah dilengkapi dengan fitur SEO built-in yang otomatis aktif:

### ✅ Meta Tags Otomatis

- Meta title
- Meta description
- Canonical URLs
- Robots meta

### ✅ Open Graph (Social Sharing)

- `og:title`, `og:description`, `og:image`
- `og:type` (article/website)
- Twitter Card tags

### ✅ Schema Markup (JSON-LD)

- WebSite schema dengan SearchAction
- CreativeWork untuk detail manhwa
- Article untuk chapter reader
- BreadcrumbList

### ✅ Performance Optimization

- HTML Minification
- Remove WordPress version
- Remove query strings dari CSS/JS
- Cleanup unnecessary head tags

---

## 🎯 Konfigurasi Meta Templates

### SureRank / Rank Math Settings

#### 🏠 **Homepage**

| Field           | Template                                                                                                            | Contoh                              |
| --------------- | ------------------------------------------------------------------------------------------------------------------- | ----------------------------------- |
| **Title**       | `%%sitename%% %%sep%% %%tagline%%`                                                                                  | SinManga - Baca Komik Manhwa Gratis |
| **Description** | Baca manhwa, manhua, dan manga terbaru bahasa Indonesia gratis. Update chapter terbaru setiap hari di %%sitename%%. |                                     |

#### 📖 **Single Manhwa (Detail Page)**

| Field           | Template                                               | Contoh                                                                             |
| --------------- | ------------------------------------------------------ | ---------------------------------------------------------------------------------- |
| **Title**       | `%%title%% %%sep%% %%sitename%%`                       | Solo Leveling - SinManga                                                           |
| **Description** | `Baca %%title%% bahasa Indonesia lengkap. %%excerpt%%` | Baca Solo Leveling bahasa Indonesia lengkap. Sung Jinwoo adalah hunter terlemah... |

#### 📚 **Chapter Reader**

| Field           | Template                                                                | Contoh                              |
| --------------- | ----------------------------------------------------------------------- | ----------------------------------- |
| **Title**       | `%%title%% %%sep%% %%sitename%%`                                        | Solo Leveling Chapter 15 - SinManga |
| **Description** | `Baca %%title%% bahasa Indonesia. Klik untuk baca chapter selanjutnya.` |                                     |

#### 🏷️ **Genre/Taxonomy Archive**

| Field           | Template                                                                                    | Contoh                           |
| --------------- | ------------------------------------------------------------------------------------------- | -------------------------------- |
| **Title**       | `Manhwa %%term_title%% Terbaru %%sep%% %%sitename%%`                                        | Manhwa Action Terbaru - SinManga |
| **Description** | `Daftar manhwa genre %%term_title%% lengkap bahasa Indonesia. Baca gratis di %%sitename%%.` |                                  |

#### 📅 **Manhwa Archive**

| Field           | Template                                                                            | Contoh                   |
| --------------- | ----------------------------------------------------------------------------------- | ------------------------ |
| **Title**       | `Daftar Manhwa %%page%% %%sep%% %%sitename%%`                                       | Daftar Manhwa - SinManga |
| **Description** | `Daftar lengkap semua manhwa bahasa Indonesia di %%sitename%%. Update setiap hari.` |                          |

#### 🔍 **Search Results**

| Field           | Template                                                    | Contoh                           |
| --------------- | ----------------------------------------------------------- | -------------------------------- |
| **Title**       | `Hasil Pencarian: %%search_query%% %%sep%% %%sitename%%`    | Hasil Pencarian: solo - SinManga |
| **Description** | `Hasil pencarian untuk "%%search_query%%" di %%sitename%%.` |                                  |

---

## 📝 Variabel yang Tersedia

### SureRank Variables

| Variable           | Keterangan                    |
| ------------------ | ----------------------------- |
| `%%sitename%%`     | Nama website                  |
| `%%tagline%%`      | Tagline/deskripsi site        |
| `%%title%%`        | Judul halaman/post            |
| `%%excerpt%%`      | Kutipan/excerpt               |
| `%%term_title%%`   | Nama taxonomy (genre, status) |
| `%%search_query%%` | Query pencarian               |
| `%%page%%`         | Nomor halaman                 |
| `%%sep%%`          | Separator (biasanya `-`)      |
| `%%currentyear%%`  | Tahun sekarang                |

### Rank Math Variables

| Variable            | Keterangan      |
| ------------------- | --------------- |
| `%sitename%`        | Nama website    |
| `%seo_title%`       | SEO title       |
| `%seo_description%` | SEO description |
| `%title%`           | Judul post      |
| `%excerpt%`         | Excerpt         |
| `%term%`            | Nama term       |
| `%search_query%`    | Query pencarian |

### Yoast SEO Variables

| Variable               | Keterangan                  |
| ---------------------- | --------------------------- |
| `%%sitename%%`         | Nama website                |
| `%%sitedesc%%`         | Tagline website             |
| `%%title%%`            | Judul halaman/post          |
| `%%excerpt%%`          | Kutipan/excerpt             |
| `%%excerpt_only%%`     | Excerpt tanpa auto-generate |
| `%%term_title%%`       | Nama taxonomy term          |
| `%%searchphrase%%`     | Kata kunci pencarian        |
| `%%page%%`             | Nomor halaman (Page X of Y) |
| `%%pagetotal%%`        | Total halaman               |
| `%%pagenumber%%`       | Nomor halaman saat ini      |
| `%%sep%%`              | Separator (default: `-`)    |
| `%%primary_category%%` | Kategori utama              |
| `%%currentyear%%`      | Tahun sekarang              |
| `%%currentmonth%%`     | Bulan sekarang              |
| `%%currentday%%`       | Hari sekarang               |
| `%%date%%`             | Tanggal publikasi           |
| `%%modified%%`         | Tanggal modifikasi          |
| `%%name%%`             | Nama author                 |
| `%%category%%`         | Kategori post               |
| `%%tag%%`              | Tag post                    |
| `%%focuskw%%`          | Focus keyword               |
| `%%pt_single%%`        | Post type singular name     |
| `%%pt_plural%%`        | Post type plural name       |

---

## 🟢 Yoast SEO Configuration

### Instalasi & Aktivasi

1. Install plugin **Yoast SEO** dari WordPress repository
2. Aktifkan plugin
3. Ikuti **Configuration Wizard**

### Search Appearance Settings

Pergi ke **Yoast SEO → Search Appearance**:

#### 🏠 **Homepage** (Tab: General)

| Setting              | Nilai                                                                                               |
| -------------------- | --------------------------------------------------------------------------------------------------- |
| **SEO Title**        | `%%sitename%% %%sep%% %%sitedesc%%`                                                                 |
| **Meta Description** | Baca manhwa, manhua, dan manga terbaru bahasa Indonesia gratis. Update chapter terbaru setiap hari. |

#### 📖 **Content Types** (Tab: Content Types)

**Manhwa (Custom Post Type):**

| Setting                    | Nilai                                                  |
| -------------------------- | ------------------------------------------------------ |
| **Show in search results** | Yes                                                    |
| **SEO Title**              | `%%title%% %%sep%% %%sitename%%`                       |
| **Meta Description**       | `Baca %%title%% bahasa Indonesia lengkap. %%excerpt%%` |
| **Schema Type**            | Article / CreativeWork                                 |

**Posts:**

| Setting              | Nilai                            |
| -------------------- | -------------------------------- |
| **SEO Title**        | `%%title%% %%sep%% %%sitename%%` |
| **Meta Description** | `%%excerpt%%`                    |
| **Schema Type**      | Article                          |

**Pages:**

| Setting              | Nilai                            |
| -------------------- | -------------------------------- |
| **SEO Title**        | `%%title%% %%sep%% %%sitename%%` |
| **Meta Description** | `%%excerpt%%`                    |
| **Schema Type**      | WebPage                          |

#### 🏷️ **Taxonomies** (Tab: Taxonomies)

**Genre:**

| Setting                    | Nilai                                                                                                |
| -------------------------- | ---------------------------------------------------------------------------------------------------- |
| **Show in search results** | Yes                                                                                                  |
| **SEO Title**              | `Manhwa %%term_title%% %%sep%% %%sitename%%`                                                         |
| **Meta Description**       | `Daftar manhwa genre %%term_title%% lengkap bahasa Indonesia. Update terbaru hanya di %%sitename%%.` |

**Status / Type:**

| Setting              | Nilai                                                         |
| -------------------- | ------------------------------------------------------------- |
| **SEO Title**        | `Manhwa %%term_title%% %%sep%% %%sitename%%`                  |
| **Meta Description** | `Daftar manhwa dengan status %%term_title%% di %%sitename%%.` |

#### 📅 **Archives** (Tab: Archives)

**Author Archives:**

| Setting                    | Nilai                           |
| -------------------------- | ------------------------------- |
| **Show in search results** | No (Disabled untuk manhwa site) |

**Date Archives:**

| Setting                    | Nilai |
| -------------------------- | ----- |
| **Show in search results** | No    |

**Search Pages:**

| Setting       | Nilai                                                    |
| ------------- | -------------------------------------------------------- |
| **SEO Title** | `Hasil Pencarian: %%searchphrase%% %%sep%% %%sitename%%` |

**404 Pages:**

| Setting       | Nilai                                          |
| ------------- | ---------------------------------------------- |
| **SEO Title** | `Halaman Tidak Ditemukan %%sep%% %%sitename%%` |

### Social Settings

Pergi ke **Yoast SEO → Social**:

#### Facebook Tab

- **Add Open Graph meta data**: ✅ Enabled
- **Default Image**: Upload gambar 1200x630px

#### Twitter Tab

- **Add Twitter card meta data**: ✅ Enabled
- **Default card type**: Summary with large image

### XML Sitemap

Pergi ke **Yoast SEO → General → Features**:

- **XML Sitemaps**: ✅ Enabled

Sitemap URL: `https://domain.com/sitemap_index.xml`

### Recommended Yoast Settings

#### General → Features

| Feature                        | Status |
| ------------------------------ | ------ |
| SEO analysis                   | ✅ On  |
| Readability analysis           | ✅ On  |
| Cornerstone content            | ✅ On  |
| XML sitemaps                   | ✅ On  |
| Admin bar menu                 | ✅ On  |
| Security: no advanced settings | ✅ On  |

#### Search Appearance → General

| Setting         | Nilai                   |
| --------------- | ----------------------- | --- |
| Title Separator | `-` atau `              | `   |
| Site Image      | Upload logo/brand image |

### Yoast Breadcrumbs

Untuk mengaktifkan Yoast breadcrumbs:

1. **Yoast SEO → Search Appearance → Breadcrumbs**
2. Enable breadcrumbs
3. Configure settings:

| Setting                       | Nilai        |
| ----------------------------- | ------------ |
| Separator                     | `»` atau `>` |
| Anchor text for homepage      | Home         |
| Show blog page in breadcrumbs | No           |

Tambahkan kode berikut ke theme (sudah include di theme ini):

```php
if ( function_exists('yoast_breadcrumb') ) {
  yoast_breadcrumb( '<nav class="breadcrumb">','</nav>' );
}
```

---

## 🖼️ Social Media Images

### Ukuran yang Direkomendasikan

| Platform                  | Ukuran        | Rasio  |
| ------------------------- | ------------- | ------ |
| **Open Graph (Facebook)** | 1200 x 630 px | 1.91:1 |
| **Twitter Card**          | 1200 x 628 px | 1.91:1 |
| **Pinterest**             | 735 x 1102 px | 2:3    |

### Konfigurasi Default Social Image

1. Pergi ke **Appearance → Customize → SEO Settings**
2. Upload **Default Social Share Image**
3. Gambar ini akan digunakan jika post tidak punya featured image

---

## ⚙️ Customizer Settings

Pergi ke **Appearance → Customize**:

### SEO Settings

- **Homepage Meta Description** - Deskripsi custom untuk homepage
- **Default Social Share Image** - Gambar default untuk OG image
- **Enable Schema Markup** - Aktifkan/nonaktifkan JSON-LD

### Performance Settings

- **Enable HTML Minification** - Compress HTML output
- **Remove WordPress Version** - Hapus versi WP (security)
- **Remove Query Strings** - Hapus ?ver= dari CSS/JS

---

## 🏗️ Struktur H1 per Halaman

Setiap halaman harus memiliki **tepat 1 tag H1**:

| Halaman            | H1 Content                         |
| ------------------ | ---------------------------------- |
| **Homepage**       | `[Site Name] - [Tagline]` (hidden) |
| **Single Manhwa**  | Judul Manhwa                       |
| **Chapter Reader** | `[Manhwa] Chapter [Number]`        |
| **Genre Archive**  | `Manhwa [Genre]`                   |
| **Search Results** | `Hasil Pencarian: [Query]`         |

---

## 📊 Schema Markup Details

### WebSite Schema (Homepage)

```json
{
  "@type": "WebSite",
  "name": "SinManga",
  "url": "https://sinmanga.web.id/",
  "potentialAction": {
    "@type": "SearchAction",
    "target": "https://sinmanga.web.id/?s={search_term_string}"
  }
}
```

### CreativeWork Schema (Manhwa Detail)

```json
{
  "@type": "CreativeWork",
  "name": "Solo Leveling",
  "author": "Chugong",
  "genre": ["Action", "Adventure", "Fantasy"],
  "aggregateRating": {
    "ratingValue": 9.5,
    "bestRating": 10
  }
}
```

### BreadcrumbList Schema

```json
{
  "@type": "BreadcrumbList",
  "itemListElement": [{ "name": "Home", "item": "https://sinmanga.web.id/" }, { "name": "Manhwa", "item": "https://sinmanga.web.id/manhwa/" }, { "name": "Solo Leveling" }]
}
```

---

## 🚀 Checklist SEO

### Wajib

- [x] Meta title unik per halaman
- [x] Meta description 150-160 karakter
- [x] H1 heading per halaman
- [x] Alt text pada gambar
- [x] Canonical URLs
- [x] Mobile responsive
- [x] HTTPS enabled

### Disarankan

- [x] Schema markup
- [x] Open Graph tags
- [x] Twitter Cards
- [x] XML Sitemap
- [x] robots.txt
- [x] Page speed optimization
- [x] Internal linking

---

## 🔧 Troubleshooting

### SEO Plugin Terdeteksi

Jika menggunakan plugin SEO (Yoast, Rank Math, AIOSEO), fitur SEO bawaan theme akan **otomatis dinonaktifkan** untuk menghindari duplikasi.

### Meta Description Terlalu Panjang

- Ideal: 150-160 karakter
- Maksimum: 160 karakter
- Gunakan excerpt yang ringkas

### Schema Tidak Muncul

1. Cek di **Appearance → Customize → SEO Settings**
2. Pastikan "Enable Schema Markup" aktif
3. Cek apakah plugin SEO sudah menambahkan schema

---

## 📞 Support

Jika ada pertanyaan tentang SEO:

- Cek dokumentasi plugin SEO yang digunakan
- Test dengan [Google Rich Results Test](https://search.google.com/test/rich-results)
- Validasi schema di [Schema.org Validator](https://validator.schema.org/)

---

_Dokumentasi ini dibuat untuk Flavor Theme v1.0_
