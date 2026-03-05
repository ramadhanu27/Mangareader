=== Manhwa Metadata Scraper ===
Contributors: manhwareader
Tags: manhwa, manga, scraper, metadata, comics, webtoon, chapter, auto-update
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Multi-source manhwa/manga metadata scraper with chapter image downloader, auto-update, and anti-blocking features for WordPress.

== Description ==

Manhwa Metadata Scraper is a comprehensive WordPress plugin designed for manhwa/manga websites. It allows you to scrape metadata, download chapter images to your local server, and automatically check for new chapters - all with built-in anti-blocking measures.

**🎯 Core Features:**

* **Multi-Source Support** - Scrape from Manhwaku.id, Komikcast, Manhwaland, and more
* **Single URL Import** - Scrape metadata from a single manhwa URL with live preview
* **Bulk Scraping** - Scrape multiple manhwa from list pages with parallel processing
* **Chapter Image Scraper** - Download chapter images directly to your local server
* **Auto Update System** - Automatic chapter checking via WP Cron with configurable schedules
* **Download Queue** - Background processing for large download operations
* **Search Functionality** - Search manhwa across multiple sources simultaneously
* **Duplicate Detection** - Prevent importing the same manhwa twice

**🖼️ Image Management:**

* **Local Image Storage** - Download chapter images to `/wp-content/uploads/manhwa/`
* **Parallel Downloads** - Download multiple images simultaneously for faster processing
* **Auto Download** - Optionally auto-download images when new chapters are found
* **Batch Conversion** - Convert existing external URLs to local URLs
* **Smart Caching** - Skip already downloaded images to save bandwidth

**🔄 Auto Update Features:**

* **Scheduled Checking** - Automatically check for new chapters (hourly, daily, twice daily, etc.)
* **Smart Detection** - Only updates manhwa with new chapters
* **Auto Download** - Optionally download new chapter images automatically
* **Update Logs** - Track all auto-update operations in history
* **Manual Trigger** - Force update check from settings page

**🛡️ Anti-Blocking Measures:**

* **Rate Limiting** - Configurable requests per minute to avoid detection
* **User-Agent Rotation** - Rotate between multiple user agents
* **Request Delays** - Configurable delays between requests
* **Proxy Support** - Route requests through HTTP/HTTPS proxy servers
* **Smart Retry** - Automatic retry with exponential backoff

**📊 Management Tools:**

* **Dashboard Widget** - Quick stats and recent activity overview
* **Scrape History** - Detailed logs of all scraping operations
* **Statistics Page** - Visual charts and metrics
* **Error Tracking** - Comprehensive error logging and debugging
* **Settings Panel** - Easy configuration of all features

**Supported Sources:**

* Manhwaku.id (Indonesian)
* Komikcast (Indonesian)
* Manhwaland (Indonesian)
* Asura Scans (English)
* Easily extensible for more sources

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/manhwa-scraper/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to 'Manhwa Scraper' in the admin menu
4. Go to Settings and configure:
   - Rate limiting (recommended: 10 requests/minute)
   - Delay between requests (recommended: 2000ms)
   - Enable Auto Update if desired
   - Configure concurrent downloads (recommended: 5-8)
5. Start scraping!

== Frequently Asked Questions ==

= What metadata is scraped? =

The plugin scrapes comprehensive metadata including:
* Title (English and alternative titles)
* Synopsis/Description
* Cover image
* Genres/Tags
* Status (Ongoing/Completed/Hiatus)
* Type (Manga/Manhwa/Manhua)
* Author and Artist
* Release year
* Chapter list with titles, numbers, and dates
* Total chapter count
* Latest chapter information

= Can I download chapter images to my server? =

Yes! The plugin includes a powerful chapter image downloader that:
* Downloads images to `/wp-content/uploads/manhwa/chapters/`
* Supports parallel downloads for faster processing
* Automatically converts external URLs to local URLs
* Skips already downloaded images
* Works with both manual and auto-update modes

= How does Auto Update work? =

Auto Update uses WordPress Cron to:
1. Check all tracked manhwa for new chapters on a schedule
2. Compare with existing chapters in database
3. Add new chapters automatically
4. Optionally download chapter images
5. Log all operations for review

You can configure the schedule (hourly, daily, etc.) and enable/disable it from Settings.

= Is this plugin legal? =

The plugin is a tool for personal use. Please respect the Terms of Service of source websites and use scraped data responsibly. Always credit original sources.

= How do I prevent getting blocked? =

To avoid being blocked:
1. Use reasonable rate limits (10-20 requests/minute)
2. Add delays between requests (2000-5000ms)
3. Enable user-agent rotation
4. Use proxy if your IP is blocked
5. Don't scrape too aggressively
6. Respect robots.txt

= Can I add more sources? =

Yes! You can extend the plugin by:
1. Creating a new scraper class that extends `MWS_Scraper_Base`
2. Implementing required methods: `can_handle()`, `scrape()`, `scrape_chapters()`
3. Registering it via the `mws_register_scrapers` action hook

See documentation for detailed guide.

= How do I convert existing external URLs to local? =

Use the "Download External to Local" button in Chapter Scraper page, or use the utility script `fix-external-to-local-urls.php` for batch conversion.

= What happens if download fails? =

If image download fails:
* The existing URL is preserved (not replaced with broken link)
* Error is logged for debugging
* You can retry later using "Download External to Local"
* Failed downloads don't break the chapter data

== Screenshots ==

1. Dashboard - Overview of scraping statistics and recent activity
2. Import Single - Scrape metadata from a single URL with preview
3. Chapter Scraper - Download chapter images to local server
4. Bulk Scrape - Scrape multiple manhwa with parallel processing
5. Settings - Configure rate limiting, auto-update, and more
6. History - Track all scraping operations with detailed logs

== Changelog ==

= 1.0.0 - 2024-12-29 =
**Initial Release**

* Multi-source scraping (Manhwaku, Komikcast, Manhwaland, Asura)
* Single URL import with preview
* Bulk scraping with parallel processing
* Chapter image scraper with local storage
* Auto-update system with WP Cron
* Download queue for background processing
* Search across multiple sources
* Duplicate detection
* Rate limiting and user-agent rotation
* Proxy support
* Cover image download to Media Library
* JSON export for Manhwa Manager compatibility
* Comprehensive logging and history
* Statistics and dashboard widgets
* Settings panel for easy configuration

**Features:**
* Download chapter images to local server
* Auto-download new chapters when found
* Parallel image downloads (configurable concurrency)
* Smart URL conversion (external to local)
* Enable/disable auto-update from settings
* Configurable cron schedules (hourly, daily, etc.)
* Error handling with detailed messages
* Auto-refresh UI after downloads complete
* Database schema auto-migration

**Bug Fixes:**
* Fixed external URLs showing after download
* Fixed database column errors (type, duration_ms)
* Fixed image download format compatibility
* Fixed merge logic for partial downloads
* Fixed index preservation in download results

== Upgrade Notice ==

= 1.0.0 =
Initial release of Manhwa Metadata Scraper with comprehensive features including chapter image downloader, auto-update system, and multi-source support.

== Additional Info ==

**Requirements:**
* WordPress 5.0 or higher
* PHP 7.4 or higher
* MySQL 5.6 or higher
* cURL extension enabled
* Sufficient disk space for image storage
* WP Cron enabled (or real cron configured)

**Recommended Server Specs:**
* PHP Memory Limit: 256M or higher
* Max Execution Time: 300 seconds or higher
* Upload Max Filesize: 64M or higher
* Post Max Size: 64M or higher

**Performance Tips:**
* Use real cron instead of WP Cron for better reliability
* Adjust concurrent downloads based on server capacity
* Use CDN for serving downloaded images
* Enable object caching for better performance
* Schedule auto-updates during low-traffic hours

**Support:**
For support, bug reports, or feature requests, please visit the plugin's GitHub repository or contact the developer.

**Credits:**
Developed by Manhwa Reader Team
Special thanks to all contributors and testers
