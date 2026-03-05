<?php
/**
 * ManhwaIndo Scraper
 * Scrapes manga/manhwa data from manhwaindo.my
 */

if (!defined('ABSPATH')) {
    exit;
}

class MWS_Manhwaindo_Scraper extends MWS_Scraper_Base {
    
    const SOURCE_ID = 'manhwaindo';
    const SOURCE_NAME = 'ManhwaIndo';
    const SOURCE_URL = 'https://www.manhwaindo.my';
    
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
        return strpos($url, 'manhwaindo.my') !== false;
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
        // Title - ManhwaIndo uses .entry-title or h1.entry-title
        $title = $parser->getText('.entry-title');
        if (empty($title)) {
            $title = $parser->getText('h1.entry-title');
        }
        if (empty($title)) {
            $title = $parser->getText('h1');
        }
        
        // Clean title (remove "Bahasa Indonesia" suffix)
        $title = preg_replace('/\s*(Bahasa Indonesia|Indonesia|Indo)$/i', '', trim($title));
        
        if (empty($title)) {
            throw new Exception('Could not find title');
        }
        
        // Cover image - try various selectors
        $cover = $parser->getAttribute('.thumb img', 'src');
        if (empty($cover)) {
            $cover = $parser->getAttribute('.thumbook img', 'src');
        }
        if (empty($cover)) {
            $cover = $parser->getAttribute('.ts-post-image', 'src');
        }
        if (empty($cover)) {
            // Try fifu-featured image
            $cover = $parser->getAttribute('img[fifu-featured]', 'src');
        }
        $cover = $this->normalize_url($cover, self::SOURCE_URL);
        
        // Synopsis
        $synopsis = $parser->getText('.entry-content-single');
        if (empty($synopsis)) {
            $synopsis = $parser->getText('.synops');
        }
        if (empty($synopsis)) {
            $synopsis = $parser->getText('.entry-content p');
        }
        $synopsis = $this->clean_text($synopsis);
        
        // Genres - from multiple possible selectors
        $genres = [];
        $genre_selectors = ['.mgen a', '.genxed a', '.seriestugenre a', '.wd-full .mgen a', '.infox .mgen a'];
        foreach ($genre_selectors as $selector) {
            $genre_elements = $parser->getElements($selector);
            if (!empty($genre_elements) && $genre_elements->length > 0) {
                foreach ($genre_elements as $el) {
                    $genre = $this->clean_text($el->textContent);
                    if (!empty($genre) && !in_array($genre, $genres)) {
                        // Skip if it looks like a type instead of genre
                        $genre_lower = strtolower($genre);
                        if (!in_array($genre_lower, ['manga', 'manhwa', 'manhua', 'webtoon', 'korean', 'japanese', 'chinese'])) {
                            $genres[] = $genre;
                        }
                    }
                }
                if (!empty($genres)) break; // Stop once we found genres
            }
        }
        
        // Alternative title
        $alt_title = $parser->getText('.alternative');
        if (empty($alt_title)) {
            $alt_title = $parser->getText('.wd-full span');
        }
        
        // Extract info from info items
        $info = $this->extract_info_items($parser);
        
        // Chapters
        $chapters = $this->extract_chapters($parser, $url);
        
        return [
            'title' => $title,
            'alternative_title' => $alt_title,
            'cover' => $cover,
            'synopsis' => $synopsis,
            'genres' => $genres,
            'status' => $info['status'],
            'type' => $info['type'],
            'author' => $info['author'],
            'artist' => $info['artist'],
            'released' => $info['released'],
            'rating' => $info['rating'],
            'views' => $info['views'],
            'chapters' => $chapters,
            'source_url' => $url,
            'source' => self::SOURCE_ID,
            'scraped_at' => current_time('c'),
        ];
    }
    
    /**
     * Extract info items (Status, Type, Author, etc.)
     *
     * @param MWS_Html_Parser $parser
     * @return array
     */
    private function extract_info_items($parser) {
        $info = [
            'status' => 'ongoing',
            'type' => 'Manhwa',
            'author' => '',
            'artist' => '',
            'released' => null,
            'rating' => 0,
            'views' => 0,
        ];
        
        // Method 1: Parse .imptdt divs (manhwaindo.my structure)
        // Structure: <div class="imptdt"><i>Label</i><i>Value</i></div>
        // Or: <div class="imptdt"><i>Label</i><a>Value</a></div>
        $imptdt_elements = $parser->getElements('.imptdt');
        if ($imptdt_elements && $imptdt_elements->length > 0) {
            foreach ($imptdt_elements as $el) {
                $html = $el->ownerDocument->saveHTML($el);
                $text_content = $this->clean_text($el->textContent);
                
                // Extract label and value
                // Pattern: <i>Label</i> followed by <i>Value</i> or <a>Value</a>
                if (preg_match('/<i[^>]*>([^<]+)<\/i>\s*(?:<i[^>]*>([^<]+)<\/i>|<a[^>]*>([^<]+)<\/a>)/i', $html, $m)) {
                    $label = strtolower(trim($m[1]));
                    $value = trim($m[2] ?? $m[3] ?? '');

                    
                    if (!empty($value) && $value !== '-') {
                        switch ($label) {
                            case 'status':
                                $info['status'] = $this->normalize_status($value);
                                break;
                            case 'type':
                                $info['type'] = $this->normalize_type($value);
                                break;
                            case 'author':
                            case 'pengarang':
                            case 'posted by':
                                if (empty($info['author'])) {
                                    $info['author'] = $value;
                                }
                                break;
                            case 'artist':
                            case 'ilustrador':
                                if (empty($info['artist'])) {
                                    $info['artist'] = $value;
                                }
                                break;
                            case 'released':
                            case 'tahun':
                            case 'year':
                                if (preg_match('/(\d{4})/', $value, $year_match)) {
                                    $info['released'] = (int) $year_match[1];
                                }
                                break;
                            case 'views':
                            case 'view':
                            case 'dilihat':
                                // Parse views like "4.6K" or "1.2M" or "12345"
                                $info['views'] = $this->parse_views_count($value);

                                break;
                        }
                    }
                }
                // Fallback: try parsing textContent directly (label + value as plain text)
                else {

                    if (!empty($text_content)) {
                        $this->parse_info_from_text($text_content, $info);
                    }
                }
            }
        }
        
        // Method 1.5: Parse .fmed elements (common in Flavor/WP-Starter themes)
        // Structure: <div class="fmed"><b>Label</b><span>Value</span></div>
        $fmed_elements = $parser->getElements('.fmed');
        if ($fmed_elements && $fmed_elements->length > 0) {
            foreach ($fmed_elements as $el) {
                $text = $this->clean_text($el->textContent);

                if (!empty($text)) {
                    $this->parse_info_from_text($text, $info);
                }
            }
        }
        
        // Method 2: Fallback to regex parsing from tsinfo/infox HTML
        $info_html = $parser->getHtml('.tsinfo');
        if (empty($info_html)) {
            $info_html = $parser->getHtml('.infox');
        }
        if (empty($info_html)) {
            $info_html = $parser->getHtml('.spe');
        }
        

        
        if (!empty($info_html)) {
            // Status - if not already set
            if ($info['status'] === 'ongoing') {
                if (preg_match('/Status[:\s]*<\/[^>]+>\s*<[^>]*>([^<]+)/i', $info_html, $m)) {
                    $info['status'] = $this->normalize_status(trim($m[1]));
                } elseif (preg_match('/Status[:\s]*([^<]+)</i', $info_html, $m)) {
                    $val = trim($m[1]);
                    if (!empty($val) && $val !== '-') {
                        $info['status'] = $this->normalize_status($val);
                    }
                }
            }
            
            // Type - if still default
            if ($info['type'] === 'Manhwa') {
                if (preg_match('/Type[:\s]*<\/[^>]+>\s*<a[^>]*>([^<]+)/i', $info_html, $m)) {
                    $info['type'] = $this->normalize_type(trim($m[1]));
                } elseif (preg_match('/Type[:\s]*<\/[^>]+>\s*<[^>]*>([^<]+)/i', $info_html, $m)) {
                    $info['type'] = $this->normalize_type(trim($m[1]));
                } elseif (preg_match('/Type[:\s]*([^<]+)</i', $info_html, $m)) {
                    $val = trim($m[1]);
                    if (!empty($val) && $val !== '-') {
                        $info['type'] = $this->normalize_type($val);
                    }
                }
            }
            
            // Author - if not already set
            if (empty($info['author'])) {
                if (preg_match('/Author[:\s]*<\/[^>]+>\s*<[^>]*>([^<]+)/i', $info_html, $m)) {
                    $info['author'] = trim($m[1]);
                } elseif (preg_match('/Pengarang[:\s]*<\/[^>]+>\s*<[^>]*>([^<]+)/i', $info_html, $m)) {
                    $info['author'] = trim($m[1]);
                } elseif (preg_match('/Posted By[:\s]*<\/[^>]+>\s*<[^>]*>([^<]+)/i', $info_html, $m)) {
                    $info['author'] = trim($m[1]);
                }
            }
            
            // Artist - if not already set
            if (empty($info['artist'])) {
                if (preg_match('/Artist[:\s]*<\/[^>]+>\s*<[^>]*>([^<]+)/i', $info_html, $m)) {
                    $info['artist'] = trim($m[1]);
                }
            }
            
            // Released year - if not already set
            if (empty($info['released'])) {
                if (preg_match('/Released[:\s]*<\/[^>]+>\s*<[^>]*>(\d{4})/i', $info_html, $m)) {
                    $info['released'] = (int) $m[1];
                } elseif (preg_match('/Tahun[:\s]*<\/[^>]+>\s*<[^>]*>(\d{4})/i', $info_html, $m)) {
                    $info['released'] = (int) $m[1];
                } elseif (preg_match('/(\d{4})/', $info_html, $m)) {
                    // Try to find any 4-digit year
                    $year = (int) $m[1];
                    if ($year >= 1990 && $year <= 2030) {
                        $info['released'] = $year;
                    }
                }
            }
            
            // Views - if not already set (fallback from info HTML)
            if (empty($info['views'])) {
                if (preg_match('/(?:Views?|Dilihat)[:\s]*<\/[^>]+>\s*<[^>]*>([^<]+)/i', $info_html, $m)) {
                    $info['views'] = $this->parse_views_count(trim($m[1]));
                } elseif (preg_match('/(?:Views?|Dilihat)[:\s]*([^<]+)</i', $info_html, $m)) {
                    $info['views'] = $this->parse_views_count(trim($m[1]));
                }
            }
        }
        
        // Clean up author/artist - remove dashes
        if ($info['author'] === '-' || $info['author'] === '–') {
            $info['author'] = '';
        }
        if ($info['artist'] === '-' || $info['artist'] === '–') {
            $info['artist'] = '';
        }
        
        
        // Rating - from .rating or .num
        $rating_text = $parser->getText('.rating .num');
        if (empty($rating_text)) {
            $rating_text = $parser->getText('.rating-post');
        }
        if (!empty($rating_text)) {
            if (preg_match('/[\d.]+/', $rating_text, $m)) {
                $info['rating'] = floatval($m[0]);
            }
        }
        
        // Fallback: Try to get type from badge or other elements
        if ($info['type'] === 'Manhwa') {
            // Check for type in badge/label elements
            $type_selectors = ['.type', '.typex', '.mgen a', '.seriestugenre a', '.imptdt i'];
            foreach ($type_selectors as $sel) {
                $type_from_badge = $parser->getText($sel);
                if (!empty($type_from_badge)) {
                    $type_lower = strtolower(trim($type_from_badge));
                    if (strpos($type_lower, 'manga') !== false && strpos($type_lower, 'manhwa') === false) {
                        $info['type'] = 'Manga';
                        break;
                    } elseif (strpos($type_lower, 'manhua') !== false) {
                        $info['type'] = 'Manhua';
                        break;
                    } elseif (strpos($type_lower, 'manhwa') !== false || strpos($type_lower, 'korea') !== false) {
                        $info['type'] = 'Manhwa';
                        break;
                    }
                }
            }
            
            // Check page title or URL for type hints
            $page_html = $parser->getHtml('body');
            if (preg_match('/\b(manga|manhwa|manhua)\b/i', $page_html, $type_match)) {
                $found_type = strtolower($type_match[1]);
                if ($found_type === 'manga') {
                    $info['type'] = 'Manga';
                } elseif ($found_type === 'manhua') {
                    $info['type'] = 'Manhua';
                } elseif ($found_type === 'manhwa') {
                    $info['type'] = 'Manhwa';
                }
            }
        }
        

        
        return $info;
    }
    
    /**
     * Parse views count from string (e.g., "4.6K", "1.2M", "12345")
     *
     * @param string $value
     * @return int
     */
    private function parse_views_count($value) {
        $value = strtoupper(trim($value));
        $value = str_replace([',', ' '], '', $value);
        
        // Match number with optional K/M suffix
        if (preg_match('/^([\d.]+)\s*([KMB])?$/i', $value, $m)) {
            $num = floatval($m[1]);
            $suffix = $m[2] ?? '';
            
            switch ($suffix) {
                case 'K':
                    return (int) ($num * 1000);
                case 'M':
                    return (int) ($num * 1000000);
                case 'B':
                    return (int) ($num * 1000000000);
                default:
                    return (int) $num;
            }
        }
        
        // Try to extract just numbers
        if (preg_match('/(\d+)/', $value, $m)) {
            return (int) $m[1];
        }
        
        return 0;
    }
    
    /**
     * Parse info from plain text (label + value combined)
     * Used as fallback for .imptdt and .fmed elements
     *
     * @param string $text Combined text like "Status Ongoing" or "Views 4.6K"
     * @param array &$info Reference to info array to populate
     */
    private function parse_info_from_text($text, &$info) {
        $text = trim($text);
        if (empty($text)) return;
        
        // Status
        if (stripos($text, 'Status') !== false) {
            $val = trim(preg_replace('/Status\s*/i', '', $text));
            if (!empty($val) && $val !== '-') {
                $info['status'] = $this->normalize_status($val);
            }
        }
        // Type
        elseif (stripos($text, 'Type') !== false) {
            $val = trim(preg_replace('/Type\s*/i', '', $text));
            if (!empty($val) && $val !== '-') {
                $info['type'] = $this->normalize_type($val);
            }
        }
        // Author
        elseif (stripos($text, 'Author') !== false || stripos($text, 'Pengarang') !== false) {
            $val = trim(preg_replace('/(Author|Pengarang)\s*/i', '', $text));
            if (!empty($val) && $val !== '-' && empty($info['author'])) {
                $info['author'] = $val;
            }
        }
        // Artist
        elseif (stripos($text, 'Artist') !== false) {
            $val = trim(preg_replace('/Artist\s*/i', '', $text));
            if (!empty($val) && $val !== '-' && empty($info['artist'])) {
                $info['artist'] = $val;
            }
        }
        // Released / Year
        elseif (stripos($text, 'Released') !== false || stripos($text, 'Tahun') !== false) {
            if (preg_match('/(\d{4})/', $text, $year_match)) {
                $info['released'] = (int) $year_match[1];
            }
        }
        // Views
        elseif (stripos($text, 'View') !== false || stripos($text, 'Dilihat') !== false) {
            $val = trim(preg_replace('/(Views?|Dilihat)\s*/i', '', $text));
            if (!empty($val) && $val !== '-' && empty($info['views'])) {
                $info['views'] = $this->parse_views_count($val);
            }
        }
        // Posted By
        elseif (stripos($text, 'Posted By') !== false || stripos($text, 'Posted On') !== false) {
            if (empty($info['author'])) {
                $val = trim(preg_replace('/Posted\s*(By|On)\s*/i', '', $text));
                if (!empty($val) && $val !== '-') {
                    $info['author'] = $val;
                }
            }
        }
    }
    
    /**
     * Extract chapters from parser
     *
     * @param MWS_Html_Parser $parser
     * @param string $base_url
     * @return array
     */
    private function extract_chapters($parser, $base_url) {
        $chapters = [];
        
        // ManhwaIndo uses #chapterlist li or .eph-num
        $selectors = [
            '#chapterlist li',
            '.chapterlist li',
            '.eplister li',
            '.eplisterfull li',
        ];
        
        foreach ($selectors as $selector) {
            $chapter_elements = $parser->getElements($selector);
            if (!empty($chapter_elements) && count($chapter_elements) > 0) {
                foreach ($chapter_elements as $ch_el) {
                    // Get link
                    $link_el = $ch_el->getElementsByTagName('a')->item(0);
                    if (!$link_el) continue;
                    
                    $url = $link_el->getAttribute('href');
                    
                    // Get chapter title/number
                    $title = '';
                    $chnum_el = $ch_el->getElementsByTagName('span');
                    foreach ($chnum_el as $span) {
                        $class = $span->getAttribute('class');
                        if (strpos($class, 'chapternum') !== false) {
                            $title = $this->clean_text($span->textContent);
                            break;
                        }
                    }
                    
                    if (empty($title)) {
                        $title = $this->clean_text($link_el->textContent);
                    }
                    
                    if (empty($url) || empty($title)) continue;
                    
                    // Extract chapter number from title
                    $number = null;
                    if (preg_match('/chapter[\s:]*(\d+(?:[.\-]\d+)?)/i', $title, $m)) {
                        $number = str_replace('-', '.', $m[1]);
                    } elseif (preg_match('/ch[.\s]*(\d+(?:[.\-]\d+)?)/i', $title, $m)) {
                        $number = str_replace('-', '.', $m[1]);
                    } elseif (preg_match('/^(\d+(?:[.\-]\d+)?)/', trim($title), $m)) {
                        $number = str_replace('-', '.', $m[1]);
                    }
                    
                    // Get date if available
                    $date = null;
                    foreach ($chnum_el as $span) {
                        $class = $span->getAttribute('class');
                        if (strpos($class, 'chapterdate') !== false) {
                            $date = $this->parse_date($span->textContent);
                            break;
                        }
                    }
                    
                    $chapters[] = [
                        'title' => $title,
                        'number' => $number,
                        'url' => $this->normalize_url($url, self::SOURCE_URL),
                        'date' => $date,
                        'images' => [],
                    ];
                }
                break;
            }
        }
        
        return $chapters;
    }
    
    /**
     * Get latest chapter info for a manhwa
     *
     * @param string $url Manhwa URL
     * @return array|WP_Error
     */
    public function get_latest_chapter($url) {
        $parser = $this->fetch_and_parse($url);
        
        if (is_wp_error($parser)) {
            return $parser;
        }
        
        // Get first chapter from list (newest)
        $selectors = [
            '#chapterlist li a',
            '.chapterlist li a',
            '.eplister li a',
        ];
        
        foreach ($selectors as $selector) {
            $chapter_elements = $parser->getElements($selector);
            if (!empty($chapter_elements) && $chapter_elements->length > 0) {
                $chapter_el = $chapter_elements->item(0);
                if ($chapter_el) {
                    $chapter_url = $chapter_el->getAttribute('href');
                    $chapter_title = $this->clean_text($chapter_el->textContent);
                    
                    // Extract chapter number
                    $chapter_number = null;
                    if (preg_match('/chapter[\s:]*(\d+(?:\.\d+)?)/i', $chapter_title, $m)) {
                        $chapter_number = $m[1];
                    } elseif (preg_match('/ch[.\s]*(\d+(?:\.\d+)?)/i', $chapter_title, $m)) {
                        $chapter_number = $m[1];
                    }
                    
                    return [
                        'title' => $chapter_title,
                        'number' => $chapter_number,
                        'url' => $this->normalize_url($chapter_url, self::SOURCE_URL),
                        'source' => self::SOURCE_ID,
                    ];
                }
            }
        }
        
        return new WP_Error('no_chapter', 'No chapters found');
    }
    
    /**
     * Scrape manhwa list from page
     *
     * @param int $page Page number
     * @param string $order Order type: update, popular, az
     * @return array|WP_Error Array of manhwa data
     */
    public function scrape_list($page = 1, $order = 'update') {
        // ManhwaIndo uses /series/?page=X&order=update format
        $params = ['page' => $page];
        
        if ($order === 'popular') {
            $params['order'] = 'popular';
        } elseif ($order === 'az') {
            $params['order'] = 'title';
        } else {
            $params['order'] = 'update';
        }
        
        $url = self::SOURCE_URL . '/series/?' . http_build_query($params);
        
        $parser = $this->fetch_and_parse($url);
        
        if (is_wp_error($parser)) {
            return $parser;
        }
        
        $items = [];
        
        // ManhwaIndo uses .bsx or .bs for manga items
        $item_elements = $parser->getElements('.bsx');
        if (empty($item_elements) || count($item_elements) === 0) {
            $item_elements = $parser->getElements('.bs');
        }
        if (empty($item_elements) || count($item_elements) === 0) {
            $item_elements = $parser->getElements('.utao');
        }
        
        foreach ($item_elements as $item) {
            try {
                // Get link
                $link_el = $item->getElementsByTagName('a')->item(0);
                if (!$link_el) continue;
                
                $manga_url = $link_el->getAttribute('href');
                
                // Get title
                $title = '';
                $title_el = $link_el->getAttribute('title');
                if (!empty($title_el)) {
                    $title = $this->clean_text($title_el);
                } else {
                    // Try getting from child elements
                    $title_xpath = new DOMXPath($item->ownerDocument);
                    $title_nodes = $title_xpath->query('.//*[contains(@class, "tt")]', $item);
                    if ($title_nodes->length > 0) {
                        $title = $this->clean_text($title_nodes->item(0)->textContent);
                    }
                }
                
                // Get cover image
                $cover = '';
                $img_el = $item->getElementsByTagName('img')->item(0);
                if ($img_el) {
                    $cover_attrs = ['src', 'data-src', 'data-lazy-src'];
                    foreach ($cover_attrs as $attr) {
                        $cover = $img_el->getAttribute($attr);
                        if (!empty($cover) && strpos($cover, 'data:image') === false) {
                            break;
                        }
                    }
                }
                
                // Get type (Manga/Manhwa/Manhua)
                $type = 'Manhwa';
                $type_xpath = new DOMXPath($item->ownerDocument);
                $type_nodes = $type_xpath->query('.//*[contains(@class, "type")]', $item);
                if ($type_nodes->length > 0) {
                    $type = $this->clean_text($type_nodes->item(0)->textContent);
                }
                
                // Get latest chapter
                $latest_chapter = '';
                $chapter_xpath = new DOMXPath($item->ownerDocument);
                $chapter_nodes = $chapter_xpath->query('.//*[contains(@class, "epxs")]', $item);
                if ($chapter_nodes->length > 0) {
                    $latest_chapter = $this->clean_text($chapter_nodes->item(0)->textContent);
                }
                
                // Get slug from URL
                $slug = '';
                if (preg_match('/series\/([^\/]+)/i', $manga_url, $m)) {
                    $slug = $m[1];
                }
                
                if (!empty($title) && !empty($manga_url)) {
                    $items[] = [
                        'title' => $title,
                        'slug' => $slug,
                        'url' => $this->normalize_url($manga_url, self::SOURCE_URL),
                        'thumbnail_url' => $this->normalize_url($cover, self::SOURCE_URL),
                        'cover' => $this->normalize_url($cover, self::SOURCE_URL),
                        'type' => $type,
                        'latest_chapter' => $latest_chapter,
                        'source' => self::SOURCE_ID,
                        'source_name' => self::SOURCE_NAME,
                    ];
                }
            } catch (Exception $e) {
                continue;
            }
        }
        
        return [
            'items' => $items,
            'page' => $page,
            'total_items' => count($items),
            'source' => self::SOURCE_ID,
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
     * Scrape chapter images
     *
     * @param string $chapter_url
     * @return array|WP_Error
     */
    public function scrape_chapter_images($chapter_url) {
        $parser = $this->fetch_and_parse($chapter_url);
        
        if (is_wp_error($parser)) {
            return $parser;
        }
        
        $images = [];
        $found_urls = []; // Track unique URLs
        
        // ManhwaIndo uses #readerarea img or .reading-content img
        $selectors = [
            '#readerarea img',
            '.rdminimal img',
            '.reading-content img',
            '.reader-area img',
            '.entry-content img',
        ];
        
        foreach ($selectors as $selector) {
            $img_elements = $parser->getElements($selector);
            
            if (!empty($img_elements) && count($img_elements) > 0) {
                foreach ($img_elements as $img) {
                    // Try multiple attributes for lazy loading
                    $src = '';
                    $attrs_to_try = ['src', 'data-src', 'data-lazy-src', 'data-original', 'data-lazy', 'data-url'];
                    
                    foreach ($attrs_to_try as $attr) {
                        $val = $img->getAttribute($attr);
                        if (!empty($val) && strpos($val, 'data:image') !== 0) {
                            $src = $val;
                            break;
                        }
                    }
                    
                    // Skip if empty or already added
                    if (empty($src)) continue;
                    if (isset($found_urls[$src])) continue;
                    
                    // Skip GIF images (usually ads/banners)
                    if (preg_match('/\.gif(\?|$)/i', $src)) {
                        continue;
                    }
                    
                    // Skip data URIs (placeholder/loading images)
                    if (strpos($src, 'data:image') === 0) {
                        continue;
                    }
                    
                    // Skip icon/banner/logo ads - use /ads/ not ads to avoid matching "uploads"
                    if (preg_match('/(logo|icon|banner|\/ads\/|iklan|sponsor|loading|placeholder|maintanence|maintenance)/i', $src)) {
                        continue;
                    }
                    
                    // Skip specific Manhwaindo assets
                    if (strpos($src, 'logo_manhwaindo') !== false || strpos($src, 'banner_maintanence') !== false) {
                        error_log('[ManhwaIndo] Skipping specific asset: ' . $src);
                        continue;
                    }
                    
                    // Handle gmbr.pro URLs
                    if (strpos($src, 'gmbr.pro') !== false) {
                        // Only accept manga-images path
                        if (strpos($src, 'manga-images') !== false || strpos($src, '/uploads/') !== false) {
                            if (preg_match('/\.(jpg|jpeg|png|webp)(\?|$)/i', $src)) {
                                $normalized = $this->normalize_url($src, self::SOURCE_URL);
                                if (!isset($found_urls[$normalized])) {
                                    $images[] = $normalized;
                                    $found_urls[$normalized] = true;
                                }
                            }
                        }
                        // Skip other gmbr.pro paths (ads)
                        continue;
                    }
                    
                    // Skip very small dimension indicators (likely icons)
                    if (preg_match('/(\d{1,2}x\d{1,2}|1x1|pixel)/i', $src)) {
                        continue;
                    }
                    
                    // Accept valid manga image URLs (jpg, jpeg, png, webp)
                    if (preg_match('/\.(jpg|jpeg|png|webp)(\?|$)/i', $src)) {
                        $normalized = $this->normalize_url($src, self::SOURCE_URL);
                        if (!isset($found_urls[$normalized])) {
                            $images[] = $normalized;
                            $found_urls[$normalized] = true;
                        }
                    }
                }
                
                // Only break if we found images in #readerarea or .rdminimal
                if (!empty($images) && ($selector === '#readerarea img' || $selector === '.rdminimal img')) {
                    break;
                }
            }
        }
        
        // If no images found, try fallback - look for all manga-images
        if (empty($images)) {
            $all_imgs = $parser->getElements('img');
            foreach ($all_imgs as $img) {
                $src = '';
                $attrs_to_try = ['src', 'data-src', 'data-lazy-src', 'data-original'];
                
                foreach ($attrs_to_try as $attr) {
                    $val = $img->getAttribute($attr);
                    if (!empty($val) && strpos($val, 'data:image') !== 0) {
                        $src = $val;
                        break;
                    }
                }
                
                if (empty($src)) continue;
                if (isset($found_urls[$src])) continue;
                
                // Skip GIF
                if (preg_match('/\.gif(\?|$)/i', $src)) continue;
                
                // Skip icon/banner/logo ads (same filter as main loop) - use /ads/ not ads
                if (preg_match('/(logo|icon|banner|\/ads\/|iklan|sponsor|loading|placeholder|maintanence|maintenance)/i', $src)) {
                    continue;
                }
                
                // Skip specific Manhwaindo assets
                if (strpos($src, 'logo_manhwaindo') !== false || strpos($src, 'banner_maintanence') !== false) {
                    continue;
                }
                
                // Look specifically for manga-images path or uploads
                if (strpos($src, 'manga-images') !== false || strpos($src, '/uploads/') !== false) {
                    if (preg_match('/\.(jpg|jpeg|png|webp)(\?|$)/i', $src)) {
                        $normalized = $this->normalize_url($src, self::SOURCE_URL);
                        if (!isset($found_urls[$normalized])) {
                            $images[] = $normalized;
                            $found_urls[$normalized] = true;
                        }
                    }
                }
            }
        }
        
        // Extract chapter number from URL
        $chapter_number = '';
        if (preg_match('/chapter-(\d+(?:-\d+)?)/i', $chapter_url, $m)) {
            $chapter_number = str_replace('-', '.', $m[1]);
        }
        
        error_log('[ManhwaIndo] scrape_chapter_images returning: ' . count($images) . ' images for ' . $chapter_url);
        if (count($images) > 0) {
            error_log('[ManhwaIndo] First image: ' . $images[0]);
        }
        
        return [
            'images' => $images,
            'chapter_url' => $chapter_url,
            'chapter_number' => $chapter_number,
            'source' => self::SOURCE_ID,
        ];
    }
    
    /**
     * Search manga
     *
     * @param string $query Search query
     * @return array|WP_Error
     */
    public function search($query) {
        $url = self::SOURCE_URL . '/?s=' . urlencode($query);
        
        $parser = $this->fetch_and_parse($url);
        
        if (is_wp_error($parser)) {
            return $parser;
        }
        
        $results = [];
        
        // Search results use .bsx or .bs
        $item_elements = $parser->getElements('.bsx');
        if (empty($item_elements) || count($item_elements) === 0) {
            $item_elements = $parser->getElements('.bs');
        }
        
        foreach ($item_elements as $item) {
            try {
                $link_el = $item->getElementsByTagName('a')->item(0);
                if (!$link_el) continue;
                
                $manga_url = $link_el->getAttribute('href');
                
                // Get title
                $title = '';
                $title_el = $link_el->getAttribute('title');
                if (!empty($title_el)) {
                    $title = $this->clean_text($title_el);
                } else {
                    $title_xpath = new DOMXPath($item->ownerDocument);
                    $title_nodes = $title_xpath->query('.//*[contains(@class, "tt")]', $item);
                    if ($title_nodes->length > 0) {
                        $title = $this->clean_text($title_nodes->item(0)->textContent);
                    }
                }
                
                // Get cover image
                $cover = '';
                $img_el = $item->getElementsByTagName('img')->item(0);
                if ($img_el) {
                    $cover_attrs = ['src', 'data-src', 'data-lazy-src'];
                    foreach ($cover_attrs as $attr) {
                        $cover = $img_el->getAttribute($attr);
                        if (!empty($cover) && strpos($cover, 'data:image') === false) {
                            break;
                        }
                    }
                }
                
                // Get type
                $type = 'Manhwa';
                $type_xpath = new DOMXPath($item->ownerDocument);
                $type_nodes = $type_xpath->query('.//*[contains(@class, "type")]', $item);
                if ($type_nodes->length > 0) {
                    $type = $this->clean_text($type_nodes->item(0)->textContent);
                }
                
                // Get slug from URL
                $slug = '';
                if (preg_match('/series\/([^\/]+)/i', $manga_url, $m)) {
                    $slug = $m[1];
                }
                
                if (!empty($title) && !empty($manga_url)) {
                    $results[] = [
                        'title' => $title,
                        'slug' => $slug,
                        'url' => $this->normalize_url($manga_url, self::SOURCE_URL),
                        'thumbnail_url' => $this->normalize_url($cover, self::SOURCE_URL),
                        'cover' => $this->normalize_url($cover, self::SOURCE_URL),
                        'type' => $type,
                        'source' => self::SOURCE_ID,
                        'source_name' => self::SOURCE_NAME,
                    ];
                }
            } catch (Exception $e) {
                continue;
            }
        }
        
        return [
            'query' => $query,
            'results' => $results,
            'total' => count($results),
            'source' => self::SOURCE_ID,
        ];
    }
    
    /**
     * Search manhwa (alias for search method for compatibility)
     *
     * @param string $keyword Search keyword
     * @return array|WP_Error
     */
    public function search_manhwa($keyword) {
        return $this->search($keyword);
    }
}
