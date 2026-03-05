<?php
/**
 * ManhwaLand Scraper
 * Scrapes manhwa data from manhwaland.land
 */

if (!defined('ABSPATH')) {
    exit;
}

class MWS_Manhwaland_Scraper extends MWS_Scraper_Base {
    
    const SOURCE_ID = 'manhwaland';
    const SOURCE_NAME = 'ManhwaLand';
    const SOURCE_URL = 'https://02.manhwaland.land';
    
    /**
     * Get source identifier
     */
    public function get_source_id() {
        return self::SOURCE_ID;
    }
    
    /**
     * Get source display name
     */
    public function get_source_name() {
        return self::SOURCE_NAME;
    }
    
    /**
     * Get source base URL
     */
    public function get_source_url() {
        return self::SOURCE_URL;
    }
    
    /**
     * Check if this scraper can handle the given URL
     */
    public function can_handle_url($url) {
        return strpos($url, 'manhwaland.land') !== false || strpos($url, 'manhwaland.in') !== false;
    }
    
    /**
     * Scrape single manhwa from URL
     *
     * @param string $url
     * @return array|WP_Error
     */
    public function scrape_single($url) {
        $parser = $this->fetch_and_parse($url);
        
        if (is_wp_error($parser)) {
            $this->log($url, 'error', $parser->get_error_message());
            return $parser;
        }
        
        try {
            $data = $this->extract_manhwa_data($parser, $url);
            $this->log($url, 'success', 'Scraped successfully', ['title' => $data['title']]);
            return $data;
        } catch (Exception $e) {
            $this->log($url, 'error', $e->getMessage());
            return new WP_Error('scrape_error', $e->getMessage());
        }
    }
    
    /**
     * Scrape single manhwa from pre-fetched HTML (for parallel scraping)
     *
     * @param string $url
     * @param string $html Pre-fetched HTML content
     * @return array|WP_Error
     */
    public function scrape_single_from_html($url, $html) {
        if (is_wp_error($html)) {
            return $html;
        }
        
        $parser = $this->parse_html($html);
        
        try {
            $data = $this->extract_manhwa_data($parser, $url);
            return $data;
        } catch (Exception $e) {
            return new WP_Error('scrape_error', $e->getMessage());
        }
    }
    
    /**
     * Extract manhwa data from parser
     *
     * @param MWS_Html_Parser $parser
     * @param string $url
     * @return array
     */
    private function extract_manhwa_data($parser, $url) {
        // Title - try multiple selectors
        $title = $parser->getText('h1.entry-title');
        if (empty($title)) {
            $title = $parser->getText('.infox h1');
        }
        if (empty($title)) {
            $title = $parser->getText('h1');
        }
        if (empty($title)) {
            $title = $parser->getMeta('og:title');
        }
        // Clean title prefixes
        $title = preg_replace('/^(Baca\s+)?Komik\s+/i', '', $title);
        $title = preg_replace('/^Baca\s+/i', '', $title);
        $title = preg_replace('/\s*[-–|]\s*ManhwaLand\s*$/i', '', $title);
        $title = $this->clean_text($title);
        
        // Alternative title
        $alternative_title = $this->extract_alternative_title($parser);
        
        // Synopsis/Description
        $description = $this->extract_description($parser);
        
        // Cover image
        $cover = $parser->getMeta('og:image');
        if (empty($cover)) {
            $cover = $parser->getAttribute('.thumb img', 'src');
        }
        if (empty($cover)) {
            $cover = $parser->getAttribute('.thumbook img', 'src');
        }
        if (empty($cover)) {
            $cover = $parser->getAttribute('.infomanga img', 'src');
        }
        $cover = $this->normalize_url($cover);
        
        // Extract info from structured data
        $info = $this->extract_info_table($parser);
        
        // Genres
        $genres = $parser->getAllText('.seriestugenre a');
        $genres = array_filter(array_map('trim', $genres));
        $genres = array_values(array_unique($genres));
        
        // Fallback: try .mgen
        if (empty($genres)) {
            $genres = $parser->getAllText('.mgen a');
            $genres = array_filter(array_map('trim', $genres));
            $genres = array_values(array_unique($genres));
        }
        
        // If still no genres found, try href pattern
        if (empty($genres)) {
            $genre_links = $parser->getAllAttributes('a[href*="/genres/"]', 'href');
            foreach ($genre_links as $link) {
                if (preg_match('/\/genres\/([^\/]+)/', $link, $matches)) {
                    $genre = ucwords(str_replace('-', ' ', $matches[1]));
                    if (!in_array($genre, $genres)) {
                        $genres[] = $genre;
                    }
                }
            }
        }
        
        // Rating
        $rating = $this->extract_rating($parser);
        
        // Views
        $views = $info['views'] ?? 0;
        
        // Followers
        $followers = $this->extract_followers($parser);
        
        // Chapters with dates
        $chapters = $this->extract_chapters($parser);
        
        // Latest chapter
        $latest_chapter = null;
        if (!empty($chapters)) {
            $latest_chapter = $chapters[0]['title'] ?? null;
        }
        
        // Slug
        $slug = $this->extract_slug($url);
        
        return [
            'title' => $title,
            'alternative_title' => $alternative_title,
            'slug' => $slug,
            'description' => $this->clean_text($description),
            'thumbnail_url' => $cover,
            'genres' => $genres,
            'status' => $info['status'] ?? 'ongoing',
            'type' => $info['type'] ?? 'Manhwa',
            'author' => $info['author'] ?? '',
            'artist' => $info['artist'] ?? '',
            'release_year' => $info['released'] ?? null,
            'posted_on' => $info['posted_on'] ?? '',
            'updated_on' => $info['updated_on'] ?? '',
            'views' => $views,
            'rating' => $rating,
            'followers' => $followers,
            'chapters' => $chapters,
            'latest_chapter' => $latest_chapter,
            'total_chapters' => count($chapters),
            'source' => self::SOURCE_ID,
            'source_url' => $url,
            'scraped_at' => current_time('c'),
        ];
    }
    
    /**
     * Extract info table data
     *
     * @param MWS_Html_Parser $parser
     * @return array
     */
    private function extract_info_table($parser) {
        $info = [
            'status' => 'ongoing',
            'type' => 'Manhwa',
            'author' => '',
            'artist' => '',
            'released' => null,
            'posted_on' => '',
            'updated_on' => '',
            'views' => 0,
        ];
        
        // Method 1: Parse .fmed spans (common in ManhwaLand)
        $fmed = $parser->getAllText('.fmed');
        foreach ($fmed as $item) {
            $item = $this->clean_text($item);
            
            if (stripos($item, 'Status') !== false) {
                $info['status'] = $this->normalize_status(preg_replace('/Status\s*/i', '', $item));
            }
            if (stripos($item, 'Type') !== false) {
                $info['type'] = trim(preg_replace('/Type\s*/i', '', $item));
            }
            if (stripos($item, 'Released') !== false && preg_match('/(\d{4})/', $item, $matches)) {
                $info['released'] = (int) $matches[1];
            }
            if (stripos($item, 'Author') !== false) {
                $info['author'] = trim(preg_replace('/Author\s*/i', '', $item));
            }
            if (stripos($item, 'Artist') !== false) {
                $info['artist'] = trim(preg_replace('/Artist\s*/i', '', $item));
            }
        }
        
        // Method 2: Try .imptdt spans
        if (empty($info['author']) && empty($info['artist'])) {
            $spans = $parser->getAllText('.imptdt');
            foreach ($spans as $span) {
                $span = $this->clean_text($span);
                
                if (stripos($span, 'Status') !== false) {
                    $info['status'] = $this->normalize_status(preg_replace('/Status\s*/i', '', $span));
                }
                if (stripos($span, 'Type') !== false) {
                    $info['type'] = trim(preg_replace('/Type\s*/i', '', $span));
                }
                if (stripos($span, 'Released') !== false) {
                    if (preg_match('/(\d{4})/', $span, $matches)) {
                        $info['released'] = (int) $matches[1];
                    }
                }
            }
        }
        
        // Method 3: Try table.infotable
        $table_html = $parser->getHtml('.infotable');
        if (!empty($table_html)) {
            preg_match_all('/<tr[^>]*>\s*<td[^>]*>([^<]+)<\/td>\s*<td[^>]*>(.*?)<\/td>\s*<\/tr>/is', $table_html, $rows, PREG_SET_ORDER);
            
            foreach ($rows as $row) {
                $label = strtolower(trim($row[1]));
                $value = trim(strip_tags($row[2]));
                
                switch ($label) {
                    case 'status':
                        $info['status'] = $this->normalize_status($value);
                        break;
                    case 'type':
                        $info['type'] = $value;
                        break;
                    case 'released':
                        if (preg_match('/(\d{4})/', $value, $m)) {
                            $info['released'] = (int) $m[1];
                        }
                        break;
                    case 'author':
                        $info['author'] = $value;
                        break;
                    case 'artist':
                        $info['artist'] = $value;
                        break;
                }
            }
        }
        
        return $info;
    }
    
    /**
     * Extract alternative title
     *
     * @param MWS_Html_Parser $parser
     * @return string
     */
    private function extract_alternative_title($parser) {
        $alt_title = $parser->getText('.alternative');
        
        if (empty($alt_title)) {
            $alt_title = $parser->getText('.wd-full span');
        }
        
        return $this->clean_text($alt_title);
    }
    
    /**
     * Extract description
     *
     * @param MWS_Html_Parser $parser
     * @return string
     */
    private function extract_description($parser) {
        // First try .entry-content p
        $description = $parser->getText('.entry-content p');
        
        // Try .synp p
        if (empty($description)) {
            $description = $parser->getText('.synp p');
        }
        
        // Try og:description
        if (empty($description)) {
            $description = $parser->getMeta('og:description');
        }
        
        // Clean up
        $description = preg_replace('/^Baca\s+Komik\s+[^.]+\.\s*/i', '', $description);
        
        return $description;
    }
    
    /**
     * Extract rating
     *
     * @param MWS_Html_Parser $parser
     * @return float|null
     */
    private function extract_rating($parser) {
        // Try itemprop="ratingValue" attribute
        $rating_attr = $parser->getAttribute('[itemprop="ratingValue"]', 'content');
        if (!empty($rating_attr) && is_numeric($rating_attr)) {
            return (float) $rating_attr;
        }
        
        // Try .num inside .rating
        $rating_text = $parser->getText('.rating .num');
        if (empty($rating_text)) {
            $rating_text = $parser->getText('.rt .num');
        }
        
        if (!empty($rating_text) && is_numeric($rating_text)) {
            return (float) $rating_text;
        }
        
        return null;
    }
    
    /**
     * Extract followers count
     *
     * @param MWS_Html_Parser $parser
     * @return int
     */
    private function extract_followers($parser) {
        $text = $parser->getText('.rt');
        
        if (preg_match('/Followed by\s*([\d,]+)\s*people/i', $text, $matches)) {
            return (int) str_replace(',', '', $matches[1]);
        }
        
        return 0;
    }
    
    /**
     * Extract chapters list with dates
     *
     * @param MWS_Html_Parser $parser
     * @return array
     */
    private function extract_chapters($parser) {
        $chapters = [];
        
        // Try #chapterlist first
        $chapter_list_html = $parser->getHtml('#chapterlist');
        
        if (!empty($chapter_list_html)) {
            // Parse structured chapter list - manhwaland uses different structure
            preg_match_all('/<li[^>]*data-num=["\']([^"\']+)["\'][^>]*>.*?<a[^>]*href=["\']([^"\']+)["\'][^>]*>.*?<span class=["\']chapternum["\'][^>]*>([^<]+).*?<span class=["\']chapterdate["\'][^>]*>([^<]+)/is', $chapter_list_html, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $match) {
                $chapter_num = (float) $match[1];
                $href = $match[2];
                $chapter_text = $this->clean_text($match[3]);
                $date_text = $this->clean_text($match[4]);
                
                $chapters[] = [
                    'number' => $chapter_num,
                    'title' => $chapter_text,
                    'url' => $this->normalize_url($href),
                    'date' => $this->parse_date($date_text),
                ];
            }
        }
        
        // Fallback: Get all chapter links
        if (empty($chapters)) {
            $links = $parser->getLinks('a');
            
            foreach ($links as $link) {
                $href = $link['href'];
                $text = $link['text'];
                
                // Filter chapter links
                if (preg_match('/chapter-?(\d+(?:\.\d+)?)/i', $href, $matches)) {
                    $chapter_num = (float) $matches[1];
                    
                    $chapters[] = [
                        'number' => $chapter_num,
                        'title' => $this->clean_text($text),
                        'url' => $this->normalize_url($href),
                        'date' => null,
                    ];
                }
            }
        }
        
        // Remove duplicates and sort by chapter number (descending)
        $unique = [];
        foreach ($chapters as $chapter) {
            $key = $chapter['number'];
            if (!isset($unique[$key])) {
                $unique[$key] = $chapter;
            }
        }
        
        $chapters = array_values($unique);
        usort($chapters, function($a, $b) {
            return $b['number'] <=> $a['number'];
        });
        
        return $chapters;
    }
    
    /**
     * Scrape manhwa list from page
     *
     * @param int $page
     * @param string $order Order type: update, popular, latest
     * @return array|WP_Error
     */
    public function scrape_list($page = 1, $order = 'update') {
        // Manhwaland uses /manga/?order=X&page=Y format
        $url = self::SOURCE_URL . '/manga/?order=' . $order . '&page=' . $page;
        
        $parser = $this->fetch_and_parse($url);
        
        if (is_wp_error($parser)) {
            return $parser;
        }
        
        $manhwa_list = [];
        $processed = [];
        
        // Find manga items - get full HTML first
        $items_html = $parser->getHtml('.listupd');
        
        if (!empty($items_html)) {
            // Method 1: Try .bsx items with more flexible regex
            preg_match_all(
                '/<div[^>]*class="[^"]*bsx[^"]*"[^>]*>.*?<a[^>]*href="([^"]+)"[^>]*>.*?<img[^>]*(?:src|data-src)="([^"]+)"[^>]*>.*?<div[^>]*class="[^"]*tt[^"]*"[^>]*>([^<]+)</is',
                $items_html,
                $matches,
                PREG_SET_ORDER
            );
            
            foreach ($matches as $match) {
                $item_url = $match[1];
                $thumbnail = $match[2];
                $title = trim($match[3]);
                
                if (strpos($item_url, '/manga/') !== false && !in_array($item_url, $processed)) {
                    $slug = $this->extract_slug($item_url);
                    
                    $manhwa_list[] = [
                        'slug' => $slug,
                        'title' => $this->clean_text($title),
                        'url' => $this->normalize_url($item_url),
                        'thumbnail_url' => $this->normalize_url($thumbnail),
                        'source' => self::SOURCE_ID,
                        'source_name' => self::SOURCE_NAME,
                    ];
                    $processed[] = $item_url;
                }
            }
            
            // Method 2: Try .bs items with flexible regex
            if (count($manhwa_list) < 10) {
                preg_match_all(
                    '/<div[^>]*class="[^"]*bs[^"]*"[^>]*>.*?<a[^>]*href="([^"]+)"[^>]*>.*?<img[^>]*(?:src|data-src)="([^"]+)"[^>]*>.*?<div[^>]*class="[^"]*tt[^"]*"[^>]*>([^<]+)</is',
                    $items_html,
                    $matches2,
                    PREG_SET_ORDER
                );
                
                foreach ($matches2 as $match) {
                    $item_url = $match[1];
                    $thumbnail = $match[2];
                    $title = trim($match[3]);
                    
                    if (strpos($item_url, '/manga/') !== false && !in_array($item_url, $processed)) {
                        $slug = $this->extract_slug($item_url);
                        
                        $manhwa_list[] = [
                            'slug' => $slug,
                            'title' => $this->clean_text($title),
                            'url' => $this->normalize_url($item_url),
                            'thumbnail_url' => $this->normalize_url($thumbnail),
                            'source' => self::SOURCE_ID,
                            'source_name' => self::SOURCE_NAME,
                        ];
                        $processed[] = $item_url;
                    }
                }
            }
            
            // Method 3: Simple anchor + title extraction as fallback
            if (count($manhwa_list) < 10) {
                preg_match_all(
                    '/<a[^>]*href="([^"]*\/manga\/[^"]+)"[^>]*title="([^"]+)"[^>]*>.*?<img[^>]*(?:src|data-src)="([^"]+)"/is',
                    $items_html,
                    $matches3,
                    PREG_SET_ORDER
                );
                
                foreach ($matches3 as $match) {
                    $item_url = $match[1];
                    $title = trim($match[2]);
                    $thumbnail = $match[3];
                    
                    if (!in_array($item_url, $processed) && strlen($title) > 2) {
                        $slug = $this->extract_slug($item_url);
                        
                        $manhwa_list[] = [
                            'slug' => $slug,
                            'title' => $this->clean_text($title),
                            'url' => $this->normalize_url($item_url),
                            'thumbnail_url' => $this->normalize_url($thumbnail),
                            'source' => self::SOURCE_ID,
                            'source_name' => self::SOURCE_NAME,
                        ];
                        $processed[] = $item_url;
                    }
                }
            }
            
            // Method 3: Generic link+image extraction
            if (count($manhwa_list) < 10) {
                preg_match_all(
                    '/<a[^>]*href="([^"]*\/manga\/[^"]+)"[^>]*title="([^"]*)"[^>]*>.*?<img[^>]*(?:src|data-src)="([^"]+)"/is',
                    $items_html,
                    $matches3,
                    PREG_SET_ORDER
                );
                
                foreach ($matches3 as $match) {
                    $item_url = $match[1];
                    $title = trim($match[2]);
                    $thumbnail = $match[3];
                    
                    if (!in_array($item_url, $processed) && strpos($item_url, 'chapter') === false && !empty($title)) {
                        $slug = $this->extract_slug($item_url);
                        
                        $manhwa_list[] = [
                            'slug' => $slug,
                            'title' => $this->clean_text($title),
                            'url' => $this->normalize_url($item_url),
                            'thumbnail_url' => $this->normalize_url($thumbnail),
                            'source' => self::SOURCE_ID,
                            'source_name' => self::SOURCE_NAME,
                        ];
                        $processed[] = $item_url;
                    }
                }
            }
        }
        
        // Method 4: Fallback - use link parser
        if (count($manhwa_list) < 5) {
            $links = $parser->getLinks('a[href*="/manga/"]');
            
            foreach ($links as $link) {
                $href = $link['href'];
                
                if (in_array($href, $processed) || strpos($href, 'chapter') !== false) {
                    continue;
                }
                
                if (preg_match('/\/manga\/([^\/]+)\/?$/', $href, $matches)) {
                    $slug = $matches[1];
                    $title = $link['text'];
                    
                    if (empty($title) || strlen($title) < 3) {
                        continue;
                    }
                    
                    $manhwa_list[] = [
                        'slug' => $slug,
                        'title' => $this->clean_text($title),
                        'url' => $this->normalize_url($href),
                        'thumbnail_url' => '',
                        'source' => self::SOURCE_ID,
                        'source_name' => self::SOURCE_NAME,
                    ];
                    
                    $processed[] = $href;
                }
            }
        }
        
        return [
            'page' => $page,
            'items' => $manhwa_list,
            'count' => count($manhwa_list),
        ];
    }
    
    /**
     * Get popular manhwa list
     * Override parent to use ManhwaLand specific URL
     *
     * @param int $page Page number
     * @return array|WP_Error
     */
    public function get_popular($page = 1) {
        $result = $this->scrape_list($page, 'popular');
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return $result['items'] ?? [];
    }
    
    /**
     * Get latest updated manhwa list
     * Override parent to use ManhwaLand specific URL
     *
     * @param int $page Page number
     * @return array|WP_Error
     */
    public function get_latest($page = 1) {
        $result = $this->scrape_list($page, 'update');
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return $result['items'] ?? [];
    }
    
    /**
     * Search manhwa from source
     *
     * @param string $keyword Search keyword
     * @return array|WP_Error
     */
    public function search_manhwa($keyword) {
        $search_url = self::SOURCE_URL . '/?s=' . urlencode($keyword);
        
        $parser = $this->fetch_and_parse($search_url);
        
        if (is_wp_error($parser)) {
            return $parser;
        }
        
        $results = [];
        $processed = [];
        
        // Parse search results - get full listupd HTML
        $items_html = $parser->getHtml('.listupd');
        
        if (!empty($items_html)) {
            // Method 1: Try parsing each .bsx item (more common in ManhwaLand)
            preg_match_all(
                '/<div class="bsx"[^>]*>.*?<a[^>]*href="([^"]+)"[^>]*>.*?<img[^>]*(?:src|data-src)="([^"]+)"[^>]*>.*?<div class="tt"[^>]*>([^<]+)/is',
                $items_html,
                $matches,
                PREG_SET_ORDER
            );
            
            foreach ($matches as $match) {
                $url = $match[1];
                $thumbnail = $match[2];
                $title = trim($match[3]);
                
                if (strpos($url, '/manga/') !== false && !in_array($url, $processed)) {
                    $slug = $this->extract_slug($url);
                    
                    $results[] = [
                        'slug' => $slug,
                        'title' => $this->clean_text($title),
                        'url' => $this->normalize_url($url),
                        'thumbnail_url' => $this->normalize_url($thumbnail),
                        'source' => self::SOURCE_ID,
                        'source_name' => self::SOURCE_NAME,
                    ];
                    $processed[] = $url;
                }
            }
            
            // Method 2: Try parsing .bs items (alternative structure)
            if (count($results) < 10) {
                preg_match_all(
                    '/<div class="bs"[^>]*>.*?<a[^>]*href="([^"]+)"[^>]*>.*?<img[^>]*(?:src|data-src)="([^"]+)"[^>]*>.*?<div class="tt"[^>]*>([^<]+)/is',
                    $items_html,
                    $matches2,
                    PREG_SET_ORDER
                );
                
                foreach ($matches2 as $match) {
                    $url = $match[1];
                    $thumbnail = $match[2];
                    $title = trim($match[3]);
                    
                    if (strpos($url, '/manga/') !== false && !in_array($url, $processed)) {
                        $slug = $this->extract_slug($url);
                        
                        $results[] = [
                            'slug' => $slug,
                            'title' => $this->clean_text($title),
                            'url' => $this->normalize_url($url),
                            'thumbnail_url' => $this->normalize_url($thumbnail),
                            'source' => self::SOURCE_ID,
                            'source_name' => self::SOURCE_NAME,
                        ];
                        $processed[] = $url;
                    }
                }
            }
            
            // Method 3: Extract with simpler pattern - bigseries 
            if (count($results) < 10) {
                preg_match_all(
                    '/<article[^>]*class="[^"]*bs[^"]*"[^>]*>.*?<a[^>]*href="([^"]+)"[^>]*title="([^"]*)"[^>]*>.*?<img[^>]*(?:src|data-src)="([^"]+)"/is',
                    $items_html,
                    $matches3,
                    PREG_SET_ORDER
                );
                
                foreach ($matches3 as $match) {
                    $url = $match[1];
                    $title = trim($match[2]);
                    $thumbnail = $match[3];
                    
                    if (strpos($url, '/manga/') !== false && !in_array($url, $processed)) {
                        $slug = $this->extract_slug($url);
                        
                        $results[] = [
                            'slug' => $slug,
                            'title' => $this->clean_text($title),
                            'url' => $this->normalize_url($url),
                            'thumbnail_url' => $this->normalize_url($thumbnail),
                            'source' => self::SOURCE_ID,
                            'source_name' => self::SOURCE_NAME,
                        ];
                        $processed[] = $url;
                    }
                }
            }
        }
        
        // Method 4: Fallback - find all manga links with images
        if (count($results) < 5) {
            // Get all divs with images and links
            preg_match_all(
                '/<a[^>]*href="([^"]*\/manga\/[^"]+)"[^>]*>.*?<img[^>]*(?:src|data-src)="([^"]+)"[^>]*.*?alt="([^"]*)"/is',
                $items_html ?: $parser->getFullHtml(),
                $fallback_matches,
                PREG_SET_ORDER
            );
            
            foreach ($fallback_matches as $match) {
                $url = $match[1];
                $thumbnail = $match[2];
                $title = trim($match[3]);
                
                if (!in_array($url, $processed) && strpos($url, 'chapter') === false && !empty($title)) {
                    $slug = $this->extract_slug($url);
                    
                    $results[] = [
                        'slug' => $slug,
                        'title' => $this->clean_text($title),
                        'url' => $this->normalize_url($url),
                        'thumbnail_url' => $this->normalize_url($thumbnail),
                        'source' => self::SOURCE_ID,
                        'source_name' => self::SOURCE_NAME,
                    ];
                    $processed[] = $url;
                }
            }
        }
        
        return [
            'keyword' => $keyword,
            'results' => $results,
            'count' => count($results),
            'source' => self::SOURCE_ID,
        ];
    }
    
    /**
     * Get latest chapter info
     *
     * @param string $url
     * @return array|WP_Error
     */
    public function get_latest_chapter($url) {
        $data = $this->scrape_single($url);
        
        if (is_wp_error($data)) {
            return $data;
        }
        
        return [
            'title' => $data['title'],
            'latest_chapter' => $data['latest_chapter'],
            'total_chapters' => $data['total_chapters'],
            'chapters' => array_slice($data['chapters'], 0, 5),
        ];
    }
    
    /**
     * Scrape chapter images from a chapter URL
     *
     * @param string $url Chapter URL
     * @return array|WP_Error
     */
    public function scrape_chapter_images($url) {
        $start_time = microtime(true);
        
        $parser = $this->fetch_and_parse($url);
        
        if (is_wp_error($parser)) {
            $duration_ms = round((microtime(true) - $start_time) * 1000);
            $this->log($url, 'error', 'Failed to fetch chapter: ' . $parser->get_error_message(), [], 'scrape', $duration_ms);
            return $parser;
        }
        
        try {
            $images = $this->extract_chapter_images($parser, $url);
            $duration_ms = round((microtime(true) - $start_time) * 1000);
            $this->log($url, 'success', 'Scraped chapter images', ['count' => count($images['images'])], 'scrape', $duration_ms);
            return $images;
        } catch (Exception $e) {
            $duration_ms = round((microtime(true) - $start_time) * 1000);
            $this->log($url, 'error', $e->getMessage(), [], 'scrape', $duration_ms);
            return new WP_Error('scrape_error', $e->getMessage());
        }
    }
    
    /**
     * Extract chapter images from parser
     *
     * @param MWS_Html_Parser $parser
     * @param string $url
     * @return array
     */
    private function extract_chapter_images($parser, $url) {
        $images = [];
        
        // Get full HTML to parse JavaScript embedded images
        $full_html = $parser->getHtml('body');
        if (empty($full_html)) {
            $full_html = $parser->getFullHtml();
        }
        
        // === Method 1 (PRIORITY): Extract from ts_reader.run() JavaScript ===
        // ManhwaLand embeds images in: ts_reader.run({ sources: [{ images: [...] }] })
        $ts_reader_pos = strpos($full_html, 'ts_reader.run(');
        if ($ts_reader_pos !== false) {
            // Find the opening brace
            $start = strpos($full_html, '{', $ts_reader_pos);
            if ($start !== false) {
                // Find matching closing brace using brace counting
                $brace_count = 0;
                $end = $start;
                $len = strlen($full_html);
                
                for ($i = $start; $i < $len && $i < $start + 100000; $i++) {
                    $char = $full_html[$i];
                    if ($char === '{') {
                        $brace_count++;
                    } elseif ($char === '}') {
                        $brace_count--;
                        if ($brace_count === 0) {
                            $end = $i;
                            break;
                        }
                    }
                }
                
                if ($end > $start) {
                    $json_str = substr($full_html, $start, $end - $start + 1);
                    $json_data = json_decode($json_str, true);
                    
                    if ($json_data) {
                        // Try sources array first (primary for ManhwaLand)
                        if (isset($json_data['sources']) && is_array($json_data['sources'])) {
                            foreach ($json_data['sources'] as $source) {
                                if (isset($source['images']) && is_array($source['images'])) {
                                    foreach ($source['images'] as $index => $img_url) {
                                        if (!empty($img_url) && !$this->is_ad_image($img_url)) {
                                            $images[] = [
                                                'index' => $index,
                                                'url' => $this->normalize_url($img_url),
                                                'alt' => 'Page ' . ($index + 1),
                                            ];
                                        }
                                    }
                                    break; // Use first source
                                }
                            }
                        }
                        
                        // Try direct images array
                        if (empty($images) && isset($json_data['images']) && is_array($json_data['images'])) {
                            foreach ($json_data['images'] as $index => $img_url) {
                                if (!empty($img_url) && !$this->is_ad_image($img_url)) {
                                    $images[] = [
                                        'index' => $index,
                                        'url' => $this->normalize_url($img_url),
                                        'alt' => 'Page ' . ($index + 1),
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // === Method 2: Extract ManhwaLand CDN URLs directly from HTML ===
        // Pattern: https://cdn-go-wd.gmbr.pro/storage/drive/...
        if (empty($images)) {
            // Look for gmbr.pro CDN URLs specifically
            preg_match_all('/["\'](https?:\/\/[^"\'>\s]*gmbr\.pro[^"\'>\s]*\.(?:jpg|jpeg|png|webp))["\']?/i', $full_html, $cdn_matches);
            
            if (!empty($cdn_matches[1])) {
                $seen = [];
                foreach ($cdn_matches[1] as $img_url) {
                    if (!in_array($img_url, $seen) && !$this->is_ad_image($img_url)) {
                        $images[] = [
                            'index' => count($images),
                            'url' => $this->normalize_url($img_url),
                            'alt' => 'Page ' . (count($images) + 1),
                        ];
                        $seen[] = $img_url;
                    }
                }
            }
        }
        
        // === Method 2: Regex extraction from #readerarea HTML ===
        if (empty($images)) {
            $reader_html = $parser->getHtml('#readerarea');
            if (!empty($reader_html)) {
                // Match all img tags - flexible pattern
                preg_match_all('/<img[^>]+src\s*=\s*["\']([^"\']+)["\'][^>]*>/i', $reader_html, $img_matches);
                
                if (!empty($img_matches[1])) {
                    foreach ($img_matches[1] as $index => $src) {
                        if (!empty($src) && !$this->is_ad_image($src)) {
                            $images[] = [
                                'index' => $index,
                                'url' => $this->normalize_url($src),
                                'alt' => 'Page ' . ($index + 1),
                            ];
                        }
                    }
                }
            }
        }
        
        // === Method 3: Find images array in JavaScript (fallback) ===
        if (empty($images)) {
            if (preg_match('/["\']?images["\']?\s*[:\=]\s*\[([^\]]+)\]/s', $full_html, $arr_match)) {
                preg_match_all('/["\']([^"\']+\.(jpg|jpeg|png|webp|gif)[^"\']*)["\']?/i', $arr_match[1], $url_matches);
                if (!empty($url_matches[1])) {
                    foreach ($url_matches[1] as $index => $img_url) {
                        if (!empty($img_url) && !$this->is_ad_image($img_url)) {
                            $images[] = [
                                'index' => $index,
                                'url' => $this->normalize_url($img_url),
                                'alt' => 'Page ' . ($index + 1),
                            ];
                        }
                    }
                }
            }
        }
        
        // === Method 4: Last resort - find all image URLs in HTML ===
        if (empty($images)) {
            preg_match_all('/["\']?(https?:\/\/[^"\'>\s]+\.(jpg|jpeg|png|webp))["\']?/i', $full_html, $all_imgs);
            
            if (!empty($all_imgs[1])) {
                $seen = [];
                foreach ($all_imgs[1] as $img_url) {
                    // Filter likely chapter images (often contain chapter/chap numbers in URL)
                    if (preg_match('/(chapter|chap|ch|page|img|image|\d{2,})/i', $img_url) && !$this->is_ad_image($img_url)) {
                        if (!in_array($img_url, $seen)) {
                            $images[] = [
                                'index' => count($images),
                                'url' => $this->normalize_url($img_url),
                                'alt' => 'Page ' . (count($images) + 1),
                            ];
                            $seen[] = $img_url;
                        }
                    }
                }
            }
        }
        
        // Remove duplicates
        $unique_images = [];
        $seen_urls = [];
        foreach ($images as $img) {
            if (!in_array($img['url'], $seen_urls)) {
                $unique_images[] = $img;
                $seen_urls[] = $img['url'];
            }
        }
        $images = $unique_images;
        
        // Re-index
        foreach ($images as $i => &$img) {
            $img['index'] = $i;
        }
        
        // Extract chapter number from URL
        $chapter_number = null;
        if (preg_match('/chapter-?([\d]+(?:[.-]\d+)?)/i', $url, $matches)) {
            $chapter_number = (float) str_replace('-', '.', $matches[1]);
        }
        
        $chapter_title = 'Chapter ' . ($chapter_number ?? 'Unknown');
        
        // Get navigation links
        $prev_chapter = $parser->getAttribute('a.ch-prev-btn', 'href');
        $next_chapter = $parser->getAttribute('a.ch-next-btn', 'href');
        
        if (empty($prev_chapter)) {
            $prev_chapter = $parser->getAttribute('.prevnext a[rel="prev"]', 'href');
        }
        if (empty($next_chapter)) {
            $next_chapter = $parser->getAttribute('.prevnext a[rel="next"]', 'href');
        }
        
        return [
            'chapter_url' => $url,
            'chapter_title' => $chapter_title,
            'chapter_number' => $chapter_number,
            'images' => $images,
            'total_images' => count($images),
            'prev_chapter' => $prev_chapter ? $this->normalize_url($prev_chapter) : null,
            'next_chapter' => $next_chapter ? $this->normalize_url($next_chapter) : null,
            'source' => self::SOURCE_ID,
            'scraped_at' => current_time('c'),
        ];
    }
    
    /**
     * Check if image URL is likely an advertisement
     *
     * @param string $url
     * @return bool
     */
    private function is_ad_image($url) {
        // Whitelist patterns - if URL matches these, it's NOT an ad
        $whitelist_patterns = [
            '/gmbr\.pro/i',           // ManhwaLand CDN
            '/manga-images/i',        // Manga images folder
            '/chapter-?\d+/i',        // Chapter folders
            '/uploads\/manga/i',      // Manga uploads
        ];
        
        foreach ($whitelist_patterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return false; // Not an ad - it's a manga image
            }
        }
        
        $ad_patterns = [
            '/\/ads?\//i',            // /ad/ or /ads/ folder
            '/\/ads?[_-]/i',          // /ad_ or /ad- or /ads_ or /ads-
            '/[_-]ads?[_-]/i',        // _ad_ or -ad- or _ads_ etc
            '/banner/i',
            '/sponsor/i',
            '/promo/i',
            '/widget/i',
            '/avatar/i',
            '/icon\./i',              // icon. (file) but not "icon" in middle of word
            '/\/logo[.\/]/i',         // /logo. or /logo/
            '/\.(gif)$/i',
            '/loading[_.-]/i',        // loading_ loading- loading.
            '/spinner/i',
        ];
        
        foreach ($ad_patterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }
        
        return false;
    }
}
