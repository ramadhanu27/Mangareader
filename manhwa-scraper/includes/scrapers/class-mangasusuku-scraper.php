<?php
/**
 * Mangasusuku.com Scraper
 * Scrapes manga data from mangasusuku.com
 */

if (!defined('ABSPATH')) {
    exit;
}

class MWS_Mangasusuku_Scraper extends MWS_Scraper_Base {
    
    const SOURCE_ID = 'mangasusuku';
    const SOURCE_NAME = 'Mangasusuku.com';
    const SOURCE_URL = 'https://mangasusuku.com';
    
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
        return strpos($url, 'mangasusuku.com') !== false || strpos($url, 'mangasusu.') !== false;
    }
    
    /**
     * Override fetch_and_parse with custom headers for Mangasusuku
     * This helps bypass 403 Forbidden errors
     *
     * @param string $url
     * @return MWS_Html_Parser|WP_Error
     */
    protected function fetch_and_parse($url) {
        // Custom headers that work better for Mangasusuku
        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language' => 'en-US,en;q=0.9,id;q=0.8',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Referer' => self::SOURCE_URL . '/',
            'Connection' => 'keep-alive',
            'Upgrade-Insecure-Requests' => '1',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'same-origin',
            'Sec-Fetch-User' => '?1',
            'sec-ch-ua' => '"Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"',
            'Cache-Control' => 'max-age=0',
        ];
        
        $html = $this->http_client->get($url, $headers);
        
        if (is_wp_error($html)) {
            return $html;
        }
        
        return new MWS_Html_Parser($html);
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
            $title = $parser->getText('h1');
        }
        if (empty($title)) {
            $title = $parser->getMeta('og:title');
        }
        // Clean title prefixes
        $title = preg_replace('/^(Baca\s+)?Komik\s+/i', '', $title);
        $title = preg_replace('/^Baca\s+/i', '', $title);
        $title = preg_replace('/\s*[-–|]\s*Mangasusuku\s*$/i', '', $title);
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
        if (empty($genres)) {
            $genres = $parser->getAllText('.mgen a');
        }
        if (empty($genres)) {
            $genres = $parser->getAllText('.genre-info a');
        }
        $genres = array_filter(array_map('trim', $genres));
        $genres = array_values(array_unique($genres));
        
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
            'type' => $info['type'] ?? 'Manga',
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
            'type' => 'Manga',
            'author' => '',
            'artist' => '',
            'released' => null,
            'posted_on' => '',
            'updated_on' => '',
            'views' => 0,
        ];
        
        // Parse table.infotable structure
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
                    case 'posted on':
                        $info['posted_on'] = $value;
                        break;
                    case 'updated on':
                        $info['updated_on'] = $value;
                        break;
                }
            }
        }
        
        // Try .imptdt spans
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
                if (stripos($span, 'Author') !== false) {
                    $info['author'] = trim(preg_replace('/Author\s*/i', '', $span));
                }
                if (stripos($span, 'Artist') !== false) {
                    $info['artist'] = trim(preg_replace('/Artist\s*/i', '', $span));
                }
            }
        }
        
        // Try .fmed
        if (empty($info['author']) && empty($info['artist'])) {
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
        }
        
        // Views
        $views_text = $parser->getText('.ts-views-count');
        if (!empty($views_text)) {
            if (preg_match('/([\d,\.]+)/', $views_text, $matches)) {
                $info['views'] = (int) str_replace([',', '.'], '', $matches[1]);
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
            $entry_content = $parser->getHtml('.entry-content');
            if (preg_match('/<\/h1>\s*([^<]+)/i', $entry_content, $matches)) {
                $alt_title = trim($matches[1]);
            }
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
        $description = $parser->getText('.entry-content-single p');
        
        if (empty($description)) {
            $description = $parser->getText('.entry-content p');
        }
        
        if (empty($description)) {
            $description = $parser->getText('.synops p');
        }
        
        if (empty($description)) {
            $description = $parser->getMeta('og:description');
        }
        
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
        $rating_attr = $parser->getAttribute('[itemprop="ratingValue"]', 'content');
        if (!empty($rating_attr) && is_numeric($rating_attr)) {
            return (float) $rating_attr;
        }
        
        $rating_text = $parser->getText('.rtp .num');
        if (!empty($rating_text) && is_numeric($rating_text)) {
            return (float) $rating_text;
        }
        
        $rating_text = $parser->getText('.rating .num');
        if (empty($rating_text)) {
            $rating_text = $parser->getText('.rating-prc .num');
        }
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
            preg_match_all('/<li[^>]*>.*?<a[^>]*href=["\']([^"\']+)["\'][^>]*>.*?class=["\']chapternum["\'][^>]*>([^<]+).*?class=["\']chapterdate["\'][^>]*>([^<]+)/is', $chapter_list_html, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $match) {
                $href = $match[1];
                $chapter_text = $this->clean_text($match[2]);
                $date_text = $this->clean_text($match[3]);
                
                $chapter_num = null;
                if (preg_match('/(?:Ch(?:apter)?\.?\s*)?(\d+(?:\.\d+)?)/i', $chapter_text, $num_match)) {
                    $chapter_num = (float) $num_match[1];
                } elseif (preg_match('/chapter-?(\d+(?:\.\d+)?)/i', $href, $num_match)) {
                    $chapter_num = (float) $num_match[1];
                }
                
                if ($chapter_num !== null) {
                    $chapters[] = [
                        'number' => $chapter_num,
                        'title' => $chapter_text,
                        'url' => $this->normalize_url($href),
                        'date' => $this->parse_date($date_text),
                    ];
                }
            }
        }
        
        // Fallback: Get all chapter links
        if (empty($chapters)) {
            $links = $parser->getLinks('a');
            
            foreach ($links as $link) {
                $href = $link['href'];
                $text = $link['text'];
                
                if (preg_match('/chapter-?(\d+(?:\.\d+)?)/i', $href, $matches)) {
                    $chapter_num = (float) $matches[1];
                    
                    $date = null;
                    if (preg_match('/(\d{1,2}\s+\w+\s+\d{4}|\w+\s+\d{1,2},?\s+\d{4})/i', $text, $date_match)) {
                        $date = $this->parse_date($date_match[1]);
                    }
                    
                    $chapters[] = [
                        'number' => $chapter_num,
                        'title' => $this->clean_text($text),
                        'url' => $this->normalize_url($href),
                        'date' => $date,
                    ];
                }
            }
        }
        
        // Remove duplicates and sort
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
     * @param string $order Order type: update, popular, latest, az
     * @return array|WP_Error
     */
    public function scrape_list($page = 1, $order = 'update') {
        $url = self::SOURCE_URL . '/komik/?page=' . $page . '&order=' . $order;
        
        $parser = $this->fetch_and_parse($url);
        
        if (is_wp_error($parser)) {
            return $parser;
        }
        
        $manhwa_list = [];
        $processed = [];
        
        $items_html = $parser->getHtml('.listupd');
        
        if (!empty($items_html)) {
            // Try .bsx items
            preg_match_all(
                '/<div class="bsx"[^>]*>.*?<a[^>]*href="([^"]+)"[^>]*>.*?<img[^>]*(?:src|data-src)="([^"]+)"[^>]*.*?<div class="tt"[^>]*>([^<]+)/is',
                $items_html,
                $matches,
                PREG_SET_ORDER
            );
            
            foreach ($matches as $match) {
                $item_url = $match[1];
                $thumbnail = $match[2];
                $title = trim($match[3]);
                
                if (strpos($item_url, '/komik/') !== false && !in_array($item_url, $processed)) {
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
            
            // Try .bs items
            if (count($manhwa_list) < 10) {
                preg_match_all(
                    '/<div class="bs"[^>]*>.*?<a[^>]*href="([^"]+)"[^>]*>.*?<img[^>]*(?:src|data-src)="([^"]+)"[^>]*.*?<div class="tt"[^>]*>([^<]+)/is',
                    $items_html,
                    $matches2,
                    PREG_SET_ORDER
                );
                
                foreach ($matches2 as $match) {
                    $item_url = $match[1];
                    $thumbnail = $match[2];
                    $title = trim($match[3]);
                    
                    if (strpos($item_url, '/komik/') !== false && !in_array($item_url, $processed)) {
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
        
        // Fallback to link parsing
        if (count($manhwa_list) < 5) {
            $links = $parser->getLinks('a[href*="/komik/"]');
            
            foreach ($links as $link) {
                $href = $link['href'];
                
                if (in_array($href, $processed) || strpos($href, 'chapter') !== false) {
                    continue;
                }
                
                if (preg_match('/\/komik\/([^\/]+)\/?$/', $href, $matches)) {
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
        
        $items_html = $parser->getHtml('.listupd');
        
        if (!empty($items_html)) {
            preg_match_all(
                '/<div class="bs"[^>]*>.*?<a[^>]*href="([^"]+)"[^>]*>.*?<img[^>]*src="([^"]+)"[^>]*>.*?<div class="tt"[^>]*>([^<]+)/is',
                $items_html,
                $matches,
                PREG_SET_ORDER
            );
            
            foreach ($matches as $match) {
                $url = $match[1];
                $thumbnail = $match[2];
                $title = trim($match[3]);
                
                if (strpos($url, '/komik/') !== false) {
                    $slug = $this->extract_slug($url);
                    
                    $results[] = [
                        'slug' => $slug,
                        'title' => $this->clean_text($title),
                        'url' => $this->normalize_url($url),
                        'thumbnail_url' => $this->normalize_url($thumbnail),
                        'source' => self::SOURCE_ID,
                        'source_name' => self::SOURCE_NAME,
                    ];
                }
            }
        }
        
        // Alternative parsing
        if (empty($results)) {
            $links = $parser->getLinks('a[href*="/komik/"]');
            $processed = [];
            
            foreach ($links as $link) {
                $href = $link['href'];
                
                if (in_array($href, $processed) || strpos($href, 'chapter') !== false) {
                    continue;
                }
                
                if (preg_match('/\/komik\/([^\/]+)\/?$/', $href, $match)) {
                    $slug = $match[1];
                    $title = $link['text'];
                    
                    if (empty($title) || strlen($title) < 3) {
                        continue;
                    }
                    
                    $results[] = [
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
            $result = $this->extract_chapter_images($parser, $url);
            $image_count = isset($result['images']) ? count($result['images']) : 0;
            $duration_ms = round((microtime(true) - $start_time) * 1000);
            
            // Debug log return value
            error_log('[Mangasusuku] scrape_chapter_images returning: ' . $image_count . ' images for ' . $url);
            if ($image_count > 0 && isset($result['images'][0])) {
                error_log('[Mangasusuku] First image URL: ' . ($result['images'][0]['url'] ?? 'NO URL KEY'));
            }
            
            $this->log($url, 'success', 'Scraped chapter images', ['count' => $image_count], 'scrape', $duration_ms);
            return $result;
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
        
        // Method 1: Parse ts_reader.run JavaScript (PRIORITY - for lazy loaded images)
        // This is the most reliable source as images are loaded via JavaScript
        $html = $parser->getHtml('body');
        
        // If getHtml('body') returns null, try getFullHtml
        if (empty($html)) {
            $html = $parser->getFullHtml();
        }
        
        // Debug log
        error_log('[Mangasusuku] HTML length: ' . strlen($html ?: ''));
        
        // Try multiple patterns for ts_reader.run()
        $ts_reader_patterns = [
            '/ts_reader\.run\s*\(\s*(\{[\s\S]*?\})\s*\)/i',
            '/ts_reader\.run\s*\(\s*(\{[^}]+\})\s*\)/i',
            '/ts_reader\.run\((\{.*?"images"\s*:\s*\[.*?\].*?\})\)/is',
        ];
        
        $found_ts_reader = false;
        foreach ($ts_reader_patterns as $pattern) {
            if (preg_match($pattern, $html, $match)) {
                $found_ts_reader = true;
                $json_str = $match[1];
                
                error_log('[Mangasusuku] Found ts_reader.run, JSON length: ' . strlen($json_str));
                
                // Convert JavaScript object to JSON - more robust conversion
                // 1. Quote unquoted keys
                $json_str = preg_replace('/([{,])\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*:/i', '$1"$2":', $json_str);
                // 2. Convert single quotes to double quotes
                $json_str = str_replace("'", '"', $json_str);
                // 3. Remove trailing commas before ] or }
                $json_str = preg_replace('/,\s*([\]}])/m', '$1', $json_str);
                
                $data = json_decode($json_str, true);
                
                if ($data && isset($data['sources']) && is_array($data['sources'])) {
                    foreach ($data['sources'] as $source) {
                        if (isset($source['images']) && is_array($source['images'])) {
                            error_log('[Mangasusuku] Found ' . count($source['images']) . ' images in ts_reader');
                            
                            // Debug: log first image URL
                            if (!empty($source['images'][0])) {
                                error_log('[Mangasusuku] Sample image URL: ' . $source['images'][0]);
                                error_log('[Mangasusuku] is_ad_image check: ' . ($this->is_ad_image($source['images'][0]) ? 'FILTERED' : 'PASSED'));
                            }
                            
                            foreach ($source['images'] as $index => $img) {
                                if (!empty($img) && !$this->is_ad_image($img)) {
                                    $images[] = [
                                        'index' => $index,
                                        'url' => $this->normalize_url($img),
                                        'alt' => '',
                                    ];
                                }
                            }
                            
                            error_log('[Mangasusuku] After filter: ' . count($images) . ' images remain');
                            break; // Use first source
                        }
                    }
                } else {
                    error_log('[Mangasusuku] JSON decode failed: ' . json_last_error_msg());
                }
                
                if (!empty($images)) {
                    break; // Found images, stop trying other patterns
                }
            }
        }
        
        if (!$found_ts_reader) {
            error_log('[Mangasusuku] ts_reader.run not found in HTML');
        }
        
        // Method 1b: Try alternative JavaScript patterns for image arrays
        if (empty($images)) {
            // Look for JSON array of images in JavaScript
            if (preg_match('/(?:images|chapter_images|pages)\s*[=:]\s*\[([\s\S]*?)\]/i', $html, $match)) {
                $array_content = $match[1];
                // Extract URLs from the array
                preg_match_all('/["\']([^"\']+\.(?:jpg|jpeg|png|webp|gif)[^"\']*)["\']/', $array_content, $url_matches);
                if (!empty($url_matches[1])) {
                    foreach ($url_matches[1] as $index => $img_url) {
                        if (!empty($img_url) && !$this->is_ad_image($img_url)) {
                            $images[] = [
                                'index' => $index,
                                'url' => $this->normalize_url($img_url),
                                'alt' => '',
                            ];
                        }
                    }
                }
            }
        }
        
        // Method 2: Try #readerarea - get HTML and parse images with regex
        if (empty($images)) {
            $reader_html = $parser->getHtml('#readerarea');
            if (!empty($reader_html)) {
                // Match img tags with src or data-src
                preg_match_all('/<img[^>]+(?:src|data-src)=["\']([^"\']+)["\'][^>]*>/i', $reader_html, $matches);
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $index => $src) {
                        $src = trim($src);
                        if (!empty($src) && !$this->is_ad_image($src) && (strpos($src, 'cdn.') !== false || strpos($src, 'http') === 0)) {
                            $images[] = [
                                'index' => $index,
                                'url' => $this->normalize_url($src),
                                'alt' => '',
                            ];
                        }
                    }
                }
            }
        }
        
        // Method 3: Try div.entry-content.entry-content-single - get HTML and parse images
        if (empty($images)) {
            $content_html = $parser->getHtml('.entry-content-single');
            if (!empty($content_html)) {
                preg_match_all('/<img[^>]+(?:src|data-src)=["\']([^"\']+)["\'][^>]*>/i', $content_html, $matches);
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $index => $src) {
                        $src = trim($src);
                        if (!empty($src) && !$this->is_ad_image($src)) {
                            $images[] = [
                                'index' => $index,
                                'url' => $this->normalize_url($src),
                                'alt' => '',
                            ];
                        }
                    }
                }
            }
        }
        
        // Method 4: Try .chapter-content img
        if (empty($images)) {
            $img_elements = $parser->getImages('.chapter-content img');
            foreach ($img_elements as $index => $img) {
                $src = $img['src'] ?? '';
                if (!empty($src) && !$this->is_ad_image($src)) {
                    $images[] = [
                        'index' => $index,
                        'url' => $this->normalize_url($src),
                        'alt' => $img['alt'] ?? '',
                    ];
                }
            }
        }
        
        // Method 5: Try .entry-content img
        if (empty($images)) {
            $img_elements = $parser->getImages('.entry-content img');
            foreach ($img_elements as $index => $img) {
                $src = $img['src'] ?? '';
                if (!empty($src) && !$this->is_ad_image($src)) {
                    $images[] = [
                        'index' => $index,
                        'url' => $this->normalize_url($src),
                        'alt' => $img['alt'] ?? '',
                    ];
                }
            }
        }
        
        // Method 6: Parse ts_reader.run JavaScript
        if (empty($images)) {
            $html = $parser->getHtml('body');
            if (preg_match('/ts_reader\\.run\\s*\\(\\s*(\\{[\\s\\S]*?\\})\\s*\\)/i', $html, $match)) {
                $json_str = $match[1];
                $json_str = preg_replace('/([{,])\\s*([a-zA-Z_][a-zA-Z0-9_]*)\\s*:/i', '$1"$2":', $json_str);
                $json_str = preg_replace("/'/", '"', $json_str);
                
                $data = json_decode($json_str, true);
                if ($data && isset($data['sources']) && is_array($data['sources'])) {
                    foreach ($data['sources'] as $source) {
                        if (isset($source['images']) && is_array($source['images'])) {
                            foreach ($source['images'] as $index => $img) {
                                if (!empty($img) && !$this->is_ad_image($img)) {
                                    $images[] = [
                                        'index' => $index,
                                        'url' => $this->normalize_url($img),
                                        'alt' => '',
                                    ];
                                }
                            }
                            break;
                        }
                    }
                }
            }
        }
        
        // Method 7: Regex fallback - find all images in page body
        if (empty($images)) {
            $html = $parser->getHtml('body');
            // Look for common manga image patterns
            preg_match_all('/<img[^>]+(?:src|data-src)=["\']([^"\']+(?:\.(?:jpg|jpeg|png|webp|gif))[^"\']*)["\'][^>]*>/i', $html, $matches);
            if (!empty($matches[1])) {
                $seen = [];
                foreach ($matches[1] as $index => $src) {
                    $src = trim($src);
                    // Filter: must look like manga page (usually has chapter/page numbers or is from CDN)
                    if (!empty($src) && !$this->is_ad_image($src) && !isset($seen[$src])) {
                        $seen[$src] = true;
                        // Check if URL looks like a manga page
                        if (preg_match('/(?:chapter|page|p\d|ch\d|\d{2,}|cdn|img|images)/i', $src)) {
                            $images[] = [
                                'index' => count($images),
                                'url' => $this->normalize_url($src),
                                'alt' => '',
                            ];
                        }
                    }
                }
            }
        }
        
        // Extract chapter number from URL
        $chapter_number = null;
        if (preg_match('/chapter-?(\\d+(?:\\.\\d+)?)/i', $url, $matches)) {
            $chapter_number = (float) $matches[1];
        }
        
        // Create simple chapter title from number
        $chapter_title = 'Chapter ' . ($chapter_number ?? 'Unknown');
        
        // If no number found, try to extract from page title
        if (!$chapter_number) {
            $page_title = $parser->getText('h1.entry-title');
            if (empty($page_title)) {
                $page_title = $parser->getText('h1');
            }
            // Try to extract chapter number from title
            if (preg_match('/chapter\\s*(\\d+(?:\\.\\d+)?)/i', $page_title, $matches)) {
                $chapter_number = (float) $matches[1];
                $chapter_title = 'Chapter ' . $chapter_number;
            }
        }
        
        // Get navigation links (prev/next chapter)
        $prev_chapter = $parser->getAttribute('a.ch-prev-btn', 'href');
        $next_chapter = $parser->getAttribute('a.ch-next-btn', 'href');
        
        // Alternative navigation selectors
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
        $ad_patterns = [
            'banner',
            'advert',
            'sponsor',
            'promo',
            'iklan',
            '/ads/',  // Changed from ads/ to /ads/ to avoid matching "uploads/"
            '/ad/',
            'googlead',
            'doubleclick',
            'facebook.com',
            'twitter.com',
            'data:image',
            '.gif',
            '.svg',  // SVG files are usually placeholders
            'logo',
            'icon',
            'avatar',
            'placeholder',
            'loading',
            'readerarea.svg',  // Specific Mangasusuku placeholder
            '/themes/',  // Theme assets
            '/assets/',  // Theme assets
            'wp-content/themes',  // WordPress theme files
            'gravatar',
            'default-',
        ];
        
        $url_lower = strtolower($url);
        
        foreach ($ad_patterns as $pattern) {
            if (strpos($url_lower, $pattern) !== false) {
                error_log('[Mangasusuku] is_ad_image matched pattern: "' . $pattern . '" in URL: ' . $url);
                return true;
            }
        }
        
        return false;
    }
}
