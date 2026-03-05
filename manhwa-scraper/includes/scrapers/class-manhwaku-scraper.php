<?php
/**
 * Manhwaku.id Scraper
 * Scrapes manhwa data from manhwaku.id
 */

if (!defined('ABSPATH')) {
    exit;
}

class MWS_Manhwaku_Scraper extends MWS_Scraper_Base {
    
    const SOURCE_ID = 'manhwaku';
    const SOURCE_NAME = 'Manhwaku.id';
    const SOURCE_URL = 'https://manhwaku.id';
    
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
        return strpos($url, 'manhwaku.id') !== false;
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
        $title = preg_replace('/\s*[-–|]\s*Manhwaku\s*$/i', '', $title);
        $title = $this->clean_text($title);
        
        // Alternative title - text after h1
        $alternative_title = $this->extract_alternative_title($parser);
        
        // Synopsis/Description - from entry-content paragraphs
        $description = $this->extract_description($parser);
        
        // Cover image
        $cover = $parser->getMeta('og:image');
        if (empty($cover)) {
            $cover = $parser->getAttribute('.thumb img', 'src');
        }
        if (empty($cover)) {
            $cover = $parser->getAttribute('.thumbook img', 'src');
        }
        $cover = $this->normalize_url($cover);
        
        // Extract info from structured data (.imptdt spans or .fmed)
        $info = $this->extract_info_table($parser);
        
        // Genres from .seriestugenre (primary for Manhwaku)
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
     * Extract info table data (Status, Type, Author, Artist, Released, etc.)
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
        
        // Method 1: Parse table.infotable structure (most reliable for Manhwaku)
        $table_html = $parser->getHtml('.infotable');
        if (!empty($table_html)) {
            // Extract all rows: <tr><td>Label</td><td>Value</td></tr>
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
                    case 'posted by':
                        // Skip
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
        
        // Method 2: Try .imptdt spans if table not found
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
        
        // Method 3: Try .fmed (alternative layout)
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
        
        // Views extraction from span.ts-views-count
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
        // Alternative title is often right after h1 or in a specific element
        $alt_title = $parser->getText('.alternative');
        
        if (empty($alt_title)) {
            // Try to find text between h1 and next element
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
        // First try .entry-content-single p
        $description = $parser->getText('.entry-content-single p');
        
        // Fall back to .entry-content p
        if (empty($description)) {
            $description = $parser->getText('.entry-content p');
        }
        
        // Try og:description
        if (empty($description)) {
            $description = $parser->getMeta('og:description');
        }
        
        // Clean up - remove "Baca Komik..." prefix if present
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
        // Try itemprop="ratingValue" attribute (most reliable for Manhwaku)
        $rating_attr = $parser->getAttribute('[itemprop="ratingValue"]', 'content');
        if (!empty($rating_attr) && is_numeric($rating_attr)) {
            return (float) $rating_attr;
        }
        
        // Try .num inside .rtp div
        $rating_text = $parser->getText('.rtp .num');
        if (!empty($rating_text) && is_numeric($rating_text)) {
            return (float) $rating_text;
        }
        
        // Try .rating .num or .rating-prc .num
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
        
        // Try #chapterlist first (structured chapter list)
        $chapter_list_html = $parser->getHtml('#chapterlist');
        
        if (!empty($chapter_list_html)) {
            // Parse structured chapter list
            preg_match_all('/<li[^>]*>.*?<a[^>]*href=["\']([^"\']+)["\'][^>]*>.*?class=["\']chapternum["\'][^>]*>([^<]+).*?class=["\']chapterdate["\'][^>]*>([^<]+)/is', $chapter_list_html, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $match) {
                $href = $match[1];
                $chapter_text = $this->clean_text($match[2]);
                $date_text = $this->clean_text($match[3]);
                
                // Extract chapter number
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
                
                // Filter chapter links
                if (preg_match('/chapter-?(\d+(?:\.\d+)?)/i', $href, $matches)) {
                    $chapter_num = (float) $matches[1];
                    
                    // Try to extract date from text
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
     * @param string $order Order type: update, popular, latest, az
     * @return array|WP_Error
     */
    public function scrape_list($page = 1, $order = 'update') {
        // Manhwaku uses /manga/?page=X&order=Y format
        $url = self::SOURCE_URL . '/manga/?page=' . $page . '&order=' . $order;
        
        $parser = $this->fetch_and_parse($url);
        
        if (is_wp_error($parser)) {
            return $parser;
        }
        
        $manhwa_list = [];
        $processed = [];
        
        // Get full HTML for parsing
        $items_html = $parser->getHtml('.listupd');
        
        if (!empty($items_html)) {
            // Method 1: Try .bsx items (common structure)
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
            
            // Method 2: Try .bs items
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
        }
        
        // Method 3: Fallback to link parsing
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
        // Manhwaku uses query parameter for search
        $search_url = self::SOURCE_URL . '/?s=' . urlencode($keyword);
        
        $parser = $this->fetch_and_parse($search_url);
        
        if (is_wp_error($parser)) {
            return $parser;
        }
        
        $results = [];
        
        // Parse search results - Manhwaku uses .bs class for manga items
        $items_html = $parser->getHtml('.listupd');
        
        if (!empty($items_html)) {
            // Match each manga item
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
                
                // Only include manga URLs
                if (strpos($url, '/manga/') !== false) {
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
        
        // Alternative parsing method if above doesn't work
        if (empty($results)) {
            $links = $parser->getLinks('a[href*="/manga/"]');
            $processed = [];
            
            foreach ($links as $link) {
                $href = $link['href'];
                
                if (in_array($href, $processed) || strpos($href, 'chapter') !== false) {
                    continue;
                }
                
                if (preg_match('/\/manga\/([^\/]+)\/?$/', $href, $match)) {
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
            'chapters' => array_slice($data['chapters'], 0, 5), // Return last 5 chapters
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
        
        // Method 1: Get images from img.ts-main-image (primary for Manhwaku)
        $img_elements = $parser->getImages('.ts-main-image');
        
        if (!empty($img_elements)) {
            foreach ($img_elements as $index => $img) {
                $src = $img['src'] ?? '';
                if (!empty($src)) {
                    $images[] = [
                        'index' => $index,
                        'url' => $this->normalize_url($src),
                        'alt' => $img['alt'] ?? '',
                    ];
                }
            }
        }
        
        // Method 2: Try #readerarea img (alternative layout)
        if (empty($images)) {
            $img_elements = $parser->getImages('#readerarea img');
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
        
        // Method 3: Try .chapter-content img
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
        
        // Extract chapter number from URL first
        $chapter_number = null;
        if (preg_match('/chapter-?(\d+(?:\.\d+)?)/i', $url, $matches)) {
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
            if (preg_match('/chapter\s*(\d+(?:\.\d+)?)/i', $page_title, $matches)) {
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
            'ads', 'banner', 'promo', 'sponsor',
            'advertisement', 'adsense', 'advert',
            'pixel', 'tracking', 'analytics',
            'facebook.com', 'google.com/ads',
        ];
        
        $url_lower = strtolower($url);
        foreach ($ad_patterns as $pattern) {
            if (strpos($url_lower, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
}
