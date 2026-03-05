# Manhwa Scraper

Plugin WordPress untuk scraping dan import konten manhwa/manga dari berbagai website sumber. Otomatisasi pengumpulan konten dengan fitur scraping yang cerdas.

## ⚠️ Disclaimer

Plugin ini hanya untuk **penggunaan pribadi**. Harap hormati hak cipta dan ketentuan layanan website sumber. Hanya scrape konten yang Anda miliki hak untuk menggunakannya.

---

## � Plugin Info

| Property              | Value                                                                      |
| --------------------- | -------------------------------------------------------------------------- |
| **Author**            | RD                                                                         |
| **Version**           | 1.0.0                                                                      |
| **Website**           | [GitHub Repository](https://github.com/ramadhanu27/Theme-Plugin-Wordpress) |
| **WordPress Version** | 5.0 or higher                                                              |
| **Tested up To**      | 6.4                                                                        |
| **PHP Version**       | 7.4 or higher                                                              |

---

## �📋 Requirements

- WordPress 5.0 atau lebih tinggi
- PHP 7.4 atau lebih tinggi
- PHP Extensions: `curl`, `dom`, `libxml`, `json`
- MySQL 5.6 atau lebih tinggi
- **Manhwa Manager** plugin (wajib)
- Theme yang direkomendasikan: **MangaZen**

---

## 🚀 Panduan Instalasi

### Step 1: Download Plugin

Pastikan Anda sudah memiliki file:

- ✅ Folder `manhwa-scraper` atau `manhwa-scraper.zip`

---

### Step 2: Install Plugin

#### Cara A: Upload via FTP/File Manager (Recommended)

1. **Extract** file ZIP jika masih dalam format ZIP
2. **Upload** folder `manhwa-scraper` ke:
   ```
   wp-content/plugins/manhwa-scraper
   ```
3. Pastikan struktur folder seperti ini:
   ```
   wp-content/
   └── plugins/
       └── manhwa-scraper/
           ├── manhwa-scraper.php
           ├── includes/
           ├── views/
           └── assets/
   ```

#### Cara B: Upload via WordPress Admin

1. Buka **Plugins → Add New → Upload Plugin**
2. Pilih file `manhwa-scraper.zip`
3. Klik **Install Now**
4. Tunggu sampai selesai

#### Cara C: Local (XAMPP/Laragon)

```bash
# Copy ke folder plugins
C:\xampp\htdocs\wordpress\wp-content\plugins\manhwa-scraper\
```

---

### Step 3: Activate Plugin

1. Buka **Plugins** di WordPress Admin
2. Cari **Manhwa Scraper**
3. Klik **Activate**

---

### Step 4: Install Required Plugin

⚠️ **PENTING**: Plugin ini membutuhkan **Manhwa Manager**!

1. Install dan aktifkan plugin **Manhwa Manager** terlebih dahulu
2. Tanpa plugin ini, Manhwa Scraper tidak akan berfungsi

---

### Step 5: Configure Settings

Buka **Manhwa Scraper → Settings** dan konfigurasi:

#### General Settings

| Setting         | Recommended | Deskripsi               |
| --------------- | ----------- | ----------------------- |
| Request Delay   | 1000-2000ms | Delay antar request     |
| Request Timeout | 30s         | Timeout per request     |
| Max Retries     | 3           | Jumlah retry jika gagal |

#### Image Settings

| Setting         | Recommended | Deskripsi                          |
| --------------- | ----------- | ---------------------------------- |
| Download Images | ✅ ON       | Simpan gambar ke server lokal      |
| Image Quality   | 85          | Kualitas JPEG                      |
| Max Width       | 1200px      | Resize gambar besar                |
| Use WebP        | ✅ ON       | Konversi ke WebP (hemat bandwidth) |

#### Auto-Update Settings

| Setting            | Recommended | Deskripsi                 |
| ------------------ | ----------- | ------------------------- |
| Enable Auto-Update | ✅ ON       | Cek chapter baru otomatis |
| Update Interval    | 6 hours     | Seberapa sering cek       |
| Batch Size         | 10-20       | Jumlah manhwa per batch   |

---

## 📖 Cara Scrape Manhwa (Step by Step)

### Method 1: Single Import (Satu Manhwa)

#### Langkah 1: Buka Halaman Import

1. Buka **Manhwa Scraper → Import** di menu admin

#### Langkah 2: Pilih Source

1. Pilih sumber website dari dropdown

#### Langkah 3: Paste URL

1. Buka website sumber
2. Pilih manhwa yang ingin di-scrape
3. **Copy URL** halaman detail manhwa tersebut
4. **Paste** ke kolom URL di halaman Import

**Contoh URL yang valid:**

```
https://asurascans.com/manga/solo-leveling/
https://komikcast.io/komik/solo-leveling/
https://manhwaku.com/manga/martial-peak/
https://manhwaland.com/manga/solo-leveling/
```

#### Langkah 4: Fetch Info

1. Klik tombol **Fetch Info**
2. Tunggu proses scraping (5-15 detik)
3. Data manhwa akan muncul:
   - ✅ Judul
   - ✅ Synopsis/Sinopsis
   - ✅ Cover Image
   - ✅ Genre, Author, Status
   - ✅ Daftar Chapter

#### Langkah 5: Review Data

1. Periksa data yang di-scrape
2. Edit jika diperlukan (judul, synopsis)
3. Pilih **Genre** yang sesuai

#### Langkah 6: Select Chapters

1. Lihat daftar chapter yang tersedia
2. Gunakan tombol:
   - **Select All** - Pilih semua chapter
   - **Select Range** - Pilih range tertentu (misal: 1-50)
   - **Deselect All** - Batalkan semua

#### Langkah 7: Import

1. Klik tombol **Import**
2. Tunggu proses import selesai
3. Progress bar akan menunjukkan status
4. Setelah selesai, manhwa akan muncul di **Manhwa → All Manhwa**

---

### Method 2: Bulk Import (Banyak Manhwa Sekaligus)

#### Langkah 1: Aktifkan Bulk Mode

1. Buka **Manhwa Scraper → Import**
2. Toggle **Bulk Mode** menjadi ON

#### Langkah 2: Paste Multiple URLs

1. Paste banyak URL, **satu per baris**:

```
https://asurascans.com/manga/solo-leveling/
https://asurascans.com/manga/omniscient-reader/
https://asurascans.com/manga/the-beginning-after-the-end/
```

#### Langkah 3: Set Options

1. **Delay Between Imports**: 5-10 detik (recommended)
2. **Auto Select Chapters**: Pilih berapa chapter terakhir yang di-import

#### Langkah 4: Start Bulk Import

1. Klik **Start Bulk Import**
2. Proses berjalan di background
3. Jangan tutup browser sampai selesai

---

### Method 3: Auto-Update (Otomatis Cek Chapter Baru)

#### Setup Auto-Update:

1. Buka **Manhwa Scraper → Settings**
2. Di bagian **Auto-Update Settings**:
   - ✅ Enable Auto-Update: **ON**
   - Update Interval: **6 hours**
   - Batch Size: **10**
3. Klik **Save Settings**

#### Cara Kerja:

```
┌────────────────────────────────────────────────────────────┐
│ 1. Cron job berjalan setiap X jam                          │
│ 2. Plugin ambil 10 manhwa yang sudah lama tidak di-update  │
│ 3. Cek source website untuk chapter baru                   │
│ 4. Jika ada chapter baru → auto import                     │
│ 5. Ulangi untuk batch berikutnya                           │
└────────────────────────────────────────────────────────────┘
```

#### Lihat Progress:

1. Buka **Manhwa Scraper → Auto-Update Stats**
2. Lihat:
   - Last update time
   - Jumlah manhwa yang di-update
   - Success/Error count

---

## 🌐 Supported Sources (Website yang Didukung)

| Source      | URL            | Status     | Language   |
| ----------- | -------------- | ---------- | ---------- |
| Asura Scans | asurascans.com | Not Active | English    |
| Komikcast   | komikcast.io   | ✅ Active  | Indonesian |
| Manhwaku    | manhwaku.com   | ✅ Active  | Indonesian |
| Manhwaland  | manhwaland.com | ✅ Active  | Indonesian |

**Note**: Source websites dapat berubah sewaktu-waktu. Jika scraper tidak berfungsi, mungkin website sudah berubah struktur.

---

## 📊 Monitoring & Logs

### Scrape History

Buka **Manhwa Scraper → History** untuk melihat:

- ✅ Log semua aktivitas scraping
- ✅ Filter by status (success/error)
- ✅ Cari berdasarkan judul
- ✅ Hapus log lama

### Auto-Update Stats

Buka **Manhwa Scraper → Auto-Update Stats** untuk melihat:

- ✅ Progress batch saat ini
- ✅ Jumlah manhwa yang berhasil/gagal
- ✅ Waktu update terakhir
- ✅ Detail log per manhwa

---

## ❓ Troubleshooting

### "Connection timed out"

**Solusi:**

- Tingkatkan timeout di Settings (30-60 detik)
- Cek apakah website sumber bisa diakses
- Coba aktifkan proxy

### "Images not downloading"

**Solusi:**

- Cek disk space server
- Pastikan folder `wp-content/uploads` writable
- Tingkatkan PHP memory_limit

### "Chapters not found"

**Solusi:**

- Website sumber mungkin sudah berubah struktur
- Coba source lain
- Report bug ke developer

### "Rate limited / Blocked"

**Solusi:**

- Tingkatkan request delay (2000-5000ms)
- Kurangi batch size
- Gunakan proxy
- Tunggu beberapa jam sebelum coba lagi

### "cURL error"

**Solusi:**

- Pastikan PHP cURL extension aktif
- Cek SSL certificates
- Cek firewall settings

### Import stuck / tidak jalan

**Solusi:**

- Refresh halaman
- Cek Console browser untuk error
- Coba import satu chapter dulu
- Bersihkan cache browser

---

## 📊 Performance Tips

1. **Download images locally** - Lebih cepat, tidak bergantung server luar
2. **Enable WebP conversion** - File size lebih kecil
3. **Set reasonable delays** - 1000-2000ms untuk menghindari block
4. **Jangan import terlalu banyak sekaligus** - Max 5-10 manhwa per batch
5. **Clean old logs regularly** - Database tetap ringan
6. **Use cron properly** - Jangan overlap auto-updates

---

## ✅ Checklist Setelah Install

- [ ] Plugin `manhwa-scraper` sudah di folder `wp-content/plugins/`
- [ ] Plugin sudah diaktifkan
- [ ] Plugin `manhwa-manager` sudah aktif
- [ ] Settings sudah dikonfigurasi
- [ ] Test import 1 manhwa berhasil
- [ ] Auto-update sudah diaktifkan (opsional)

---

## 📁 Plugin Structure

```
manhwa-scraper/
├── includes/
│   ├── class-scraper-manager.php     # Main scraper logic
│   ├── class-rest-api.php            # REST API endpoints
│   ├── admin/
│   │   ├── class-admin-pages.php     # Admin pages
│   │   └── class-dashboard-widget.php
│   └── scrapers/
│       ├── class-scraper-base.php    # Base scraper class
│       ├── class-asura-scraper.php   # Asura Scans
│       ├── class-komikcast-scraper.php
│       ├── class-manhwaku-scraper.php
│       └── class-manhwaland-scraper.php
├── views/
│   ├── main-page.php                 # Dashboard
│   ├── import-page.php               # Import UI
│   ├── history-page.php              # Scrape logs
│   ├── settings-page.php             # Settings
│   ├── sources-page.php              # Source management
│   └── auto-update-stats-page.php    # Auto-update stats
├── assets/
│   ├── css/
│   │   └── admin.css
│   └── js/
│       └── admin.js
├── manhwa-scraper.php                # Main plugin file
└── uninstall.php                     # Cleanup
```

---

## 🔌 REST API (Advanced)

Plugin menyediakan REST API endpoints:

```
GET /wp-json/manhwa/v1/stats
GET /wp-json/manhwa/v1/manhwa
GET /wp-json/manhwa/v1/manhwa/{id}
```

Aktifkan API key di Settings → REST API untuk authentication.

---

## 📄 License

GNU General Public License v2 or later

---

**Manhwa Scraper** - Automate Your Content Collection 🤖
