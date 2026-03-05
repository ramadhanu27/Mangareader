<?php
/**
 * Komikcast Scraper
 * Scrapes manga/manhwa data from komikcast03.com
 */

if (!defined('ABSPATH')) {
    exit;
}

class MWS_Komikcast_Scraper extends MWS_Scraper_Base {
    
    const SOURCE_ID = 'komikcast';
    const SOURCE_NAME = 'Komikcast';
    const SOURCE_URL = 'https://v1.komikcast05.com';
    
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
        return strpos($url, 'komikcast') !== false;
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
        // Title - Komikcast uses h1.komik_info-content-title
        $title = $parser->getText('h1.komik_info-content-title');
        if (empty($title)) {
            $title = $parser->getText('.komik_info-content-title');
        }
        if (empty($title)) {
            $title = $parser->getText('h1');
        }
        
        // Clean title (remove "Bahasa Indonesia" suffix)
        $title = preg_replace('/\s*(Bahasa Indonesia|Indonesia|Indo)$/i', '', trim($title));
        
        if (empty($title)) {
            throw new Exception('Could not find title');
        }
        
        // Cover image
        $cover = $parser->getAttribute('.komik_info-content-thumbnail img', 'src');
        if (empty($cover)) {
            $cover = $parser->getAttribute('.komik_info img', 'src');
        }
        $cover = $this->normalize_url($cover, self::SOURCE_URL);
        
        // Synopsis
        $synopsis = $parser->getText('.komik_info-description-sinopsis');
        if (empty($synopsis)) {
            $synopsis = $parser->getText('.komik_info-description-son_content');
        }
        $synopsis = $this->clean_text($synopsis);
        
        // Genres
        $genres = [];
        $genre_elements = $parser->getElements('.komik_info-content-genre a');
        foreach ($genre_elements as $el) {
            $genre = $this->clean_text($el->textContent);
            if (!empty($genre)) {
                $genres[] = $genre;
            }
        }
        
        // Alternative title
        $alt_title = $parser->getText('.komik_info-content-native');
        
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
            'type' => 'Manga',
            'author' => '',
            'artist' => '',
            'released' => null,
            'rating' => 0,
        ];
        
        // Get meta container HTML
        $meta_html = $parser->getHtml('.komik_info-content-meta');
        
        if (!empty($meta_html)) {
            // Released - from span.komik_info-content-info-release
            if (preg_match('/Released[:\s]*<\/b>\s*([^<]+)/i', $meta_html, $m)) {
                $released = trim($m[1]);
                // Extract year
                if (preg_match('/\d{4}/', $released, $year_match)) {
                    $info['released'] = (int) $year_match[0];
                }
            }
            
            // Author - look for Author: text
            if (preg_match('/Author[:\s]*<\/b>\s*([^<]+)/i', $meta_html, $m)) {
                $info['author'] = trim($m[1]);
            }
            
            // Status - look for Status: text
            if (preg_match('/Status[:\s]*<\/b>\s*([^<]+)/i', $meta_html, $m)) {
                $info['status'] = $this->normalize_status(trim($m[1]));
            }
        }
        
        // Type - from span.komik_info-content-info-type (contains a link)
        $type_html = $parser->getHtml('.komik_info-content-info-type');
        if (!empty($type_html)) {
            // Type is inside an <a> tag
            if (preg_match('/<a[^>]*>([^<]+)<\/a>/i', $type_html, $m)) {
                $info['type'] = trim($m[1]);
            } elseif (preg_match('/Type[:\s]*<\/b>\s*([^<]+)/i', $type_html, $m)) {
                $info['type'] = trim($m[1]);
            }
        }
        
        // Rating - from data-ratingkomik attribute or text
        $rating_attr = $parser->getAttribute('.data-rating', 'data-ratingkomik');
        if (!empty($rating_attr)) {
            $info['rating'] = floatval($rating_attr);
        } else {
            // Try getting from text
            $rating_text = $parser->getText('.data-rating strong');
            if (!empty($rating_text)) {
                if (preg_match('/[\d.]+/', $rating_text, $m)) {
                    $info['rating'] = floatval($m[0]);
                }
            }
        }
        
        // Fallback: try other rating selectors
        if ($info['rating'] == 0) {
            $rating = $parser->getText('.komik_info-content-rating-inner');
            if (!empty($rating)) {
                $info['rating'] = floatval($rating);
            }
        }
        
        return $info;
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
        
        // Try different selectors for chapter list
        $selectors = [
            'ul#chapter-wrapper li',
            '.komik_info-chapters-list li',
            '.komik_info-chapters li',
            '.chapter-list li',
        ];
        
        foreach ($selectors as $selector) {
            $chapter_elements = $parser->getElements($selector);
            if (!empty($chapter_elements)) {
                foreach ($chapter_elements as $ch_el) {
                    // Get link
                    $link_el = $ch_el->getElementsByTagName('a')->item(0);
                    if (!$link_el) continue;
                    
                    $url = $link_el->getAttribute('href');
                    $title = $this->clean_text($link_el->textContent);
                    
                    if (empty($url) || empty($title)) continue;
                    
                    // Extract chapter number from title
                    $number = null;
                    if (preg_match('/chapter[:\s]*(\d+(?:\.\d+)?)/i', $title, $m)) {
                        $number = $m[1];
                    } elseif (preg_match('/ch[.\s]*(\d+(?:\.\d+)?)/i', $title, $m)) {
                        $number = $m[1];
                    }
                    
                    // Get date if available
                    $date = null;
                    $date_text = '';
                    $time_el = $ch_el->getElementsByTagName('span');
                    if ($time_el->length > 0) {
                        $date_text = $time_el->item($time_el->length - 1)->textContent;
                        $date = $this->parse_date($date_text);
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
        
        // Get chapters and return the first one (newest)
        $selectors = [
            'ul#chapter-wrapper li a',
            '.komik_info-chapters-list li a',
            '.komik_info-chapters li a',
            '.chapter-list li a',
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
                    if (preg_match('/chapter[:\s]*(\d+(?:\.\d+)?)/i', $chapter_title, $m)) {
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
        // Komikcast uses /daftar-komik/page/{n}/?orderby=popular format
        $page_path = $page > 1 ? 'page/' . $page . '/' : '';
        
        if ($order === 'popular') {
            $url = self::SOURCE_URL . '/daftar-komik/' . $page_path . '?status&type&orderby=popular';
        } elseif ($order === 'az') {
            $url = self::SOURCE_URL . '/daftar-komik/' . $page_path . '?status&type&orderby=title';
        } else {
            // Latest updates
            $url = self::SOURCE_URL . '/daftar-komik/' . $page_path . '?status&type&orderby=update';
        }
        
        $parser = $this->fetch_and_parse($url);
        
        if (is_wp_error($parser)) {
            return $parser;
        }
        
        $items = [];
        
        // Get all manga items - Komikcast uses .list-update_item
        $item_elements = $parser->getElements('.list-update_item');
        
        foreach ($item_elements as $item) {
            try {
                // Get link
                $link_el = $item->getElementsByTagName('a')->item(0);
                if (!$link_el) continue;
                
                $manga_url = $link_el->getAttribute('href');
                
                // Get title
                $title = '';
                $title_el = $item->getElementsByTagName('h3')->item(0);
                if ($title_el) {
                    $title = $this->clean_text($title_el->textContent);
                } else {
                    // Try getting from title class
                    $title_xpath = new DOMXPath($item->ownerDocument);
                    $title_nodes = $title_xpath->query('.//*[contains(@class, "title")]', $item);
                    if ($title_nodes->length > 0) {
                        $title = $this->clean_text($title_nodes->item(0)->textContent);
                    }
                }
                
                // Get cover image - handle lazy loading
                $cover = '';
                $img_el = $item->getElementsByTagName('img')->item(0);
                if ($img_el) {
                    // Try multiple attributes for lazy loading
                    $cover_attrs = ['src', 'data-src', 'data-lazy-src', 'data-ll-src', 'data-original'];
                    foreach ($cover_attrs as $attr) {
                        $cover = $img_el->getAttribute($attr);
                        if (!empty($cover) && strpos($cover, 'data:image') === false && strpos($cover, 'placeholder') === false) {
                            break;
                        }
                    }
                }
                
                // Get type (Manga/Manhwa/Manhua)
                $type = 'Manga';
                $type_xpath = new DOMXPath($item->ownerDocument);
                $type_nodes = $type_xpath->query('.//*[contains(@class, "type")]', $item);
                if ($type_nodes->length > 0) {
                    $type = $this->clean_text($type_nodes->item(0)->textContent);
                }
                
                // Get latest chapter
                $latest_chapter = '';
                $chapter_xpath = new DOMXPath($item->ownerDocument);
                $chapter_nodes = $chapter_xpath->query('.//*[contains(@class, "epss") or contains(@class, "chapter")]', $item);
                if ($chapter_nodes->length > 0) {
                    $latest_chapter = $this->clean_text($chapter_nodes->item(0)->textContent);
                }
                
                // Get rating
                $rating = 0;
                $rating_nodes = $chapter_xpath->query('.//*[contains(@class, "rating")]', $item);
                if ($rating_nodes->length > 0) {
                    $rating = floatval($this->clean_text($rating_nodes->item(0)->textContent));
                }
                
                // Get slug from URL
                $slug = '';
                if (preg_match('/komik\/([^\/]+)/i', $manga_url, $m)) {
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
                        'rating' => $rating,
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
        
        // Komikcast uses .main-reading-area img or #readerarea img
        $selectors = [
            '.main-reading-area img',
            '#readerarea img',
            '#content-images img',
            '.chapter-images img',
            '.reading-content img',
        ];
        
        foreach ($selectors as $selector) {
            $img_elements = $parser->getElements($selector);
            
            if (!empty($img_elements) && count($img_elements) > 0) {
                foreach ($img_elements as $img) {
                    $src = $img->getAttribute('src');
                    if (empty($src)) {
                        $src = $img->getAttribute('data-src');
                    }
                    if (empty($src)) {
                        $src = $img->getAttribute('data-lazy-src');
                    }
                    
                    // Filter out small images, icons, ads
                    if (!empty($src) && !preg_match('/(logo|icon|banner|ads|\.gif)/i', $src)) {
                        // Check if it's a valid manga image (usually hosted on specific domains)
                        if (strpos($src, 'imgkc') !== false || 
                            strpos($src, 'wp-content/uploads') !== false ||
                            strpos($src, 'cdn') !== false) {
                            $images[] = $src;
                        } elseif (preg_match('/\.(jpg|jpeg|png|webp)(\?|$)/i', $src)) {
                            // Accept any jpg/png/webp that looks like a chapter image
                            $images[] = $src;
                        }
                    }
                }
                
                if (!empty($images)) {
                    break;
                }
            }
        }
        
        // If no images found with selectors, try getting all large images
        if (empty($images)) {
            $all_imgs = $parser->getElements('img');
            foreach ($all_imgs as $img) {
                $src = $img->getAttribute('src');
                if (empty($src)) continue;
                
                // Look for content images
                if (strpos($src, 'imgkc') !== false || 
                    (strpos($src, 'wp-content/uploads') !== false && !preg_match('/(thumb|cover|poster)/i', $src))) {
                    $images[] = $src;
                }
            }
        }
        
        // Extract chapter number from URL
        $chapter_number = '';
        if (preg_match('/chapter-(\d+(?:-\d+)?)/i', $chapter_url, $m)) {
            $chapter_number = str_replace('-', '.', $m[1]);
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
        
        // Search results use same structure as list
        $item_elements = $parser->getElements('.list-update_item');
        
        foreach ($item_elements as $item) {
            try {
                $link_el = $item->getElementsByTagName('a')->item(0);
                if (!$link_el) continue;
                
                $manga_url = $link_el->getAttribute('href');
                
                $title = '';
                $title_el = $item->getElementsByTagName('h3')->item(0);
                if ($title_el) {
                    $title = $this->clean_text($title_el->textContent);
                }
                
                // Get cover image - handle lazy loading
                $cover = '';
                $img_el = $item->getElementsByTagName('img')->item(0);
                if ($img_el) {
                    // Try multiple attributes for lazy loading
                    $cover_attrs = ['src', 'data-src', 'data-lazy-src', 'data-ll-src', 'data-original'];
                    foreach ($cover_attrs as $attr) {
                        $cover = $img_el->getAttribute($attr);
                        if (!empty($cover) && strpos($cover, 'data:image') === false && strpos($cover, 'placeholder') === false) {
                            break;
                        }
                    }
                }
                
                // Get type
                $type = 'Manga';
                $type_xpath = new DOMXPath($item->ownerDocument);
                $type_nodes = $type_xpath->query('.//*[contains(@class, "type")]', $item);
                if ($type_nodes->length > 0) {
                    $type = $this->clean_text($type_nodes->item(0)->textContent);
                }
                
                // Get slug from URL
                $slug = '';
                if (preg_match('/komik\/([^\/]+)/i', $manga_url, $m)) {
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
