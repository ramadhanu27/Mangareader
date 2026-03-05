<?php
/**
 * Indonesian (Bahasa Indonesia) Language Loader
 * 
 * This file loads Indonesian translations for Manhwa Scraper plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Load Indonesian translations
 */
function mws_load_indonesian_language() {
    $language = get_option('mws_plugin_language', 'en_US');
    
    if ($language === 'id_ID') {
        // Load Indonesian translations
        load_plugin_textdomain(
            'manhwa-scraper',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
        
        // Override WordPress locale
        add_filter('locale', function($locale) {
            if (is_admin() && strpos($_SERVER['REQUEST_URI'], 'manhwa-scraper') !== false) {
                return 'id_ID';
            }
            return $locale;
        });
        
        // Add Indonesian translations via filter
        add_filter('gettext', 'mws_translate_to_indonesian', 10, 3);
        add_filter('gettext_with_context', 'mws_translate_to_indonesian_with_context', 10, 4);
    }
}
add_action('plugins_loaded', 'mws_load_indonesian_language');

/**
 * Translate strings to Indonesian
 */
function mws_translate_to_indonesian($translated, $original, $domain) {
    if ($domain !== 'manhwa-scraper') {
        return $translated;
    }
    
    $translations = mws_get_indonesian_translations();
    
    return isset($translations[$original]) ? $translations[$original] : $translated;
}

/**
 * Translate strings with context to Indonesian
 */
function mws_translate_to_indonesian_with_context($translated, $original, $context, $domain) {
    if ($domain !== 'manhwa-scraper') {
        return $translated;
    }
    
    $translations = mws_get_indonesian_translations();
    $key = $context . "\x04" . $original;
    
    return isset($translations[$key]) ? $translations[$key] : $translated;
}

/**
 * Get Indonesian translations array
 */
function mws_get_indonesian_translations() {
    return [
        // Menu & Pages
        'Manhwa Scraper' => 'Manhwa Scraper',
        'Dashboard' => 'Dasbor',
        'Import Single' => 'Import Tunggal',
        'Bulk Scrape' => 'Scrape Massal',
        'Chapter Scraper' => 'Scrape Chapter',
        'Search' => 'Pencarian',
        'Settings' => 'Pengaturan',
        'History' => 'Riwayat',
        'Statistics' => 'Statistik',
        'Download Queue' => 'Antrian Download',
        
        // Dashboard
        'Welcome to Manhwa Scraper' => 'Selamat Datang di Manhwa Scraper',
        'Quick Stats' => 'Statistik Cepat',
        'Total Manhwa' => 'Total Manhwa',
        'Total Scrapes' => 'Total Scrape',
        'Success Rate' => 'Tingkat Keberhasilan',
        'Total Errors' => 'Total Error',
        'Recent Activity' => 'Aktivitas Terbaru',
        'Quick Actions' => 'Aksi Cepat',
        'Import from URL' => 'Import dari URL',
        'Search Sources' => 'Cari Sumber',
        'View History' => 'Lihat Riwayat',
        'Configure Settings' => 'Konfigurasi Pengaturan',
        
        // Import Single
        'Import from URL' => 'Import dari URL',
        'Enter manhwa URL' => 'Masukkan URL manhwa',
        'Enter the URL of the manhwa page you want to scrape' => 'Masukkan URL halaman manhwa yang ingin di-scrape',
        'Scrape' => 'Scrape',
        'Scraping...' => 'Sedang scraping...',
        'Import to WordPress' => 'Import ke WordPress',
        'Download Cover' => 'Download Cover',
        'Create Post' => 'Buat Post',
        'Importing...' => 'Sedang import...',
        
        // Search
        'Search from Sources' => 'Cari dari Sumber',
        'Enter manhwa title to search...' => 'Masukkan judul manhwa untuk dicari...',
        'All Sources' => 'Semua Sumber',
        'Searching sources...' => 'Mencari sumber...',
        'results found for' => 'hasil ditemukan untuk',
        'Import Selected' => 'Import Terpilih',
        'Search for Manhwa' => 'Cari Manhwa',
        'Enter a manhwa title above to search from enabled sources.' => 'Masukkan judul manhwa di atas untuk mencari dari sumber yang aktif.',
        'No Results Found' => 'Tidak Ada Hasil',
        'Try different keywords or check if the source is available.' => 'Coba kata kunci lain atau periksa apakah sumber tersedia.',
        
        // Settings
        'General Settings' => 'Pengaturan Umum',
        'Rate Limit (req/min)' => 'Batas Rate (req/menit)',
        'Maximum number of requests per minute per domain. Lower values are safer.' => 'Jumlah maksimum permintaan per menit per domain. Nilai lebih rendah lebih aman.',
        'Delay Between Requests (ms)' => 'Jeda Antar Permintaan (ms)',
        'Minimum delay between requests in milliseconds. Recommended: 1000-3000ms.' => 'Jeda minimum antar permintaan dalam milidetik. Rekomendasi: 1000-3000ms.',
        'Concurrent Downloads' => 'Download Bersamaan',
        'Number of images to download simultaneously. Higher = faster but uses more resources. Recommended: 5-8.' => 'Jumlah gambar yang didownload bersamaan. Lebih tinggi = lebih cepat tapi gunakan lebih banyak resource. Rekomendasi: 5-8.',
        'Language' => 'Bahasa',
        'Select the language for the plugin interface.' => 'Pilih bahasa untuk antarmuka plugin.',
        'Note: Page will reload after saving to apply language changes.' => 'Catatan: Halaman akan dimuat ulang setelah menyimpan untuk menerapkan perubahan bahasa.',
        
        // Auto Update
        'Auto Update' => 'Update Otomatis',
        'Configure automatic chapter update checking via WP Cron.' => 'Konfigurasi pengecekan update chapter otomatis via WP Cron.',
        'Enable Auto Update' => 'Aktifkan Update Otomatis',
        'Activate automatic chapter checking' => 'Aktifkan pengecekan chapter otomatis',
        'Update Schedule' => 'Jadwal Update',
        'How often to check for new chapters' => 'Seberapa sering memeriksa chapter baru',
        'Auto Download Chapters' => 'Download Chapter Otomatis',
        'Automatically download images to local server when new chapters are found' => 'Otomatis download gambar ke server lokal saat chapter baru ditemukan',
        'Auto Scrape Chapter Images' => 'Scrape Gambar Chapter Otomatis',
        'Automatically scrape chapter image URLs (external) when new chapters are found' => 'Otomatis scrape URL gambar chapter (eksternal) saat chapter baru ditemukan',
        
        // Sources
        'Enabled Sources' => 'Sumber Aktif',
        'Select which sources to enable for scraping' => 'Pilih sumber mana yang akan diaktifkan untuk scraping',
        
        // Proxy
        'Proxy Settings' => 'Pengaturan Proxy',
        'Configure proxy for requests (optional)' => 'Konfigurasi proxy untuk permintaan (opsional)',
        'Enable Proxy' => 'Aktifkan Proxy',
        'Use proxy for all requests' => 'Gunakan proxy untuk semua permintaan',
        'Proxy Host' => 'Host Proxy',
        'Proxy Port' => 'Port Proxy',
        'Proxy Username' => 'Username Proxy',
        'Proxy Password' => 'Password Proxy',
        'Test Connection' => 'Test Koneksi',
        
        // Buttons
        'Save Settings' => 'Simpan Pengaturan',
        'Import' => 'Import',
        'View' => 'Lihat',
        'Details' => 'Detail',
        'Cancel' => 'Batal',
        'Close' => 'Tutup',
        'Delete' => 'Hapus',
        'Edit' => 'Edit',
        'Refresh' => 'Refresh',
        
        // Status
        'Active' => 'Aktif',
        'Inactive' => 'Tidak Aktif',
        'Success' => 'Berhasil',
        'Error' => 'Error',
        'Pending' => 'Menunggu',
        'Processing' => 'Memproses',
        'Completed' => 'Selesai',
        'Failed' => 'Gagal',
        
        // Messages
        'Settings saved.' => 'Pengaturan disimpan.',
        'Auto update activated!' => 'Update otomatis diaktifkan!',
        'Auto update deactivated.' => 'Update otomatis dinonaktifkan.',
        'Language changed. Reloading page...' => 'Bahasa diubah. Memuat ulang halaman...',
        'Successfully imported' => 'Berhasil diimport',
        'Successfully scraped' => 'Berhasil di-scrape',
        'Failed to scrape' => 'Gagal scrape',
        'Failed to import' => 'Gagal import',
        'No data found' => 'Tidak ada data ditemukan',
        'Loading...' => 'Memuat...',
        
        // Statistics
        'Statistics & Analytics' => 'Statistik & Analitik',
        'Scrape Activity (Last 30 Days)' => 'Aktivitas Scrape (30 Hari Terakhir)',
        'Source Distribution' => 'Distribusi Sumber',
        'Source Performance' => 'Performa Sumber',
        'Recent Errors' => 'Error Terbaru',
        'Most Scraped Manhwa' => 'Manhwa Paling Banyak Di-scrape',
        'Activity Timeline' => 'Timeline Aktivitas',
        'Avg Response Time' => 'Waktu Respon Rata-rata',
        'Status' => 'Status',
        
        // History
        'Scrape History' => 'Riwayat Scrape',
        'Date/Time' => 'Tanggal/Waktu',
        'Source' => 'Sumber',
        'Type' => 'Tipe',
        'Message' => 'Pesan',
        'Duration' => 'Durasi',
        'Actions' => 'Aksi',
        'No logs found' => 'Tidak ada log ditemukan',
        
        // Chapter Scraper
        'Select Manhwa' => 'Pilih Manhwa',
        'Select a manhwa to scrape chapters' => 'Pilih manhwa untuk scrape chapter',
        'Scrape Chapter Images' => 'Scrape Gambar Chapter',
        'Download images to local server' => 'Download gambar ke server lokal',
        'Bulk Scrape Chapters' => 'Scrape Chapter Massal',
        
        // Common
        'Title' => 'Judul',
        'Synopsis' => 'Sinopsis',
        'Author' => 'Penulis',
        'Artist' => 'Artist',
        'Genres' => 'Genre',
        'Rating' => 'Rating',
        'Chapters' => 'Chapter',
        'Images' => 'Gambar',
        'URL' => 'URL',
        'Date' => 'Tanggal',
    ];
}
