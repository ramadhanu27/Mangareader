<?php
/**
 * HTML Parser Class
 * Parses HTML content using DOMDocument
 */

if (!defined('ABSPATH')) {
    exit;
}

class MWS_Html_Parser {
    
    private $dom;
    private $xpath;
    
    /**
     * Constructor
     *
     * @param string $html HTML content
     */
    public function __construct($html = '') {
        $this->dom = new DOMDocument();
        
        if (!empty($html)) {
            $this->load($html);
        }
    }
    
    /**
     * Load HTML content
     *
     * @param string $html
     * @return bool
     */
    public function load($html) {
        // Suppress warnings from malformed HTML
        libxml_use_internal_errors(true);
        
        // Add UTF-8 encoding
        $html = '<?xml encoding="UTF-8">' . $html;
        
        $success = $this->dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        // Clear errors
        libxml_clear_errors();
        
        // Initialize XPath
        $this->xpath = new DOMXPath($this->dom);
        
        return $success;
    }
    
    /**
     * Query using XPath
     *
     * @param string $query XPath query
     * @return DOMNodeList
     */
    public function xpath($query) {
        return $this->xpath->query($query);
    }
    
    /**
     * Get text content by CSS selector (converted to XPath)
     *
     * @param string $selector CSS-like selector
     * @return string|null
     */
    public function getText($selector) {
        $xpath_query = $this->cssToXPath($selector);
        $nodes = $this->xpath($xpath_query);
        
        if ($nodes->length > 0) {
            return trim($nodes->item(0)->textContent);
        }
        
        return null;
    }
    
    /**
     * Get all text content matching selector
     *
     * @param string $selector
     * @return array
     */
    public function getAllText($selector) {
        $xpath_query = $this->cssToXPath($selector);
        $nodes = $this->xpath($xpath_query);
        $results = [];
        
        foreach ($nodes as $node) {
            $results[] = trim($node->textContent);
        }
        
        return $results;
    }
    
    /**
     * Get attribute value
     *
     * @param string $selector
     * @param string $attribute
     * @return string|null
     */
    public function getAttribute($selector, $attribute) {
        $xpath_query = $this->cssToXPath($selector);
        $nodes = $this->xpath($xpath_query);
        
        if ($nodes->length > 0) {
            return $nodes->item(0)->getAttribute($attribute);
        }
        
        return null;
    }
    
    /**
     * Get all attribute values matching selector
     *
     * @param string $selector
     * @param string $attribute
     * @return array
     */
    public function getAllAttributes($selector, $attribute) {
        $xpath_query = $this->cssToXPath($selector);
        $nodes = $this->xpath($xpath_query);
        $results = [];
        
        foreach ($nodes as $node) {
            $attr_value = $node->getAttribute($attribute);
            if (!empty($attr_value)) {
                $results[] = $attr_value;
            }
        }
        
        return $results;
    }
    
    /**
     * Get DOM elements matching selector
     *
     * @param string $selector CSS selector
     * @return DOMNodeList
     */
    public function getElements($selector) {
        $xpath_query = $this->cssToXPath($selector);
        return $this->xpath($xpath_query);
    }
    
    /**
     * Get HTML content
     *
     * @param string $selector
     * @return string|null
     */
    public function getHtml($selector) {
        $xpath_query = $this->cssToXPath($selector);
        $nodes = $this->xpath($xpath_query);
        
        if ($nodes->length > 0) {
            return $this->dom->saveHTML($nodes->item(0));
        }
        
        return null;
    }
    
    /**
     * Check if element exists
     *
     * @param string $selector
     * @return bool
     */
    public function exists($selector) {
        $xpath_query = $this->cssToXPath($selector);
        $nodes = $this->xpath($xpath_query);
        return $nodes->length > 0;
    }
    
    /**
     * Get count of matching elements
     *
     * @param string $selector
     * @return int
     */
    public function count($selector) {
        $xpath_query = $this->cssToXPath($selector);
        $nodes = $this->xpath($xpath_query);
        return $nodes->length;
    }
    
    /**
     * Get meta tag content
     *
     * @param string $name Meta name or property
     * @return string|null
     */
    public function getMeta($name) {
        // Try name attribute
        $content = $this->getAttribute("meta[name='$name']", 'content');
        
        // Try property attribute (for og: tags)
        if ($content === null) {
            $content = $this->getAttribute("meta[property='$name']", 'content');
        }
        
        return $content;
    }
    
    /**
     * Get all links
     *
     * @param string $selector Optional selector to filter links
     * @return array Array of [href, text]
     */
    public function getLinks($selector = 'a') {
        $xpath_query = $this->cssToXPath($selector);
        $nodes = $this->xpath($xpath_query);
        $links = [];
        
        foreach ($nodes as $node) {
            $href = $node->getAttribute('href');
            $text = trim($node->textContent);
            
            if (!empty($href)) {
                $links[] = [
                    'href' => $href,
                    'text' => $text,
                ];
            }
        }
        
        return $links;
    }
    
    /**
     * Get all images
     *
     * @param string $selector Optional selector to filter images
     * @return array Array of image attributes including src, data-src, data-lazy-src, alt, etc.
     */
    public function getImages($selector = 'img') {
        $xpath_query = $this->cssToXPath($selector);
        $nodes = $this->xpath($xpath_query);
        $images = [];
        
        foreach ($nodes as $node) {
            $img = [];
            
            // Get all attributes
            if ($node->hasAttributes()) {
                foreach ($node->attributes as $attr) {
                    $img[$attr->nodeName] = $attr->nodeValue;
                }
            }
            
            // Determine best src - priority: data-lazy-src > data-src > data-original > src
            $best_src = null;
            $src_priority = ['data-lazy-src', 'data-src', 'data-original', 'data-cfsrc', 'src'];
            
            foreach ($src_priority as $attr) {
                if (!empty($img[$attr])) {
                    $best_src = $img[$attr];
                    break;
                }
            }
            
            // Add src if we found one
            if (!empty($best_src)) {
                $img['src'] = $best_src;
                $images[] = $img;
            }
        }
        
        return $images;
    }
    
    /**
     * Convert simple CSS selector to XPath
     *
     * @param string $selector
     * @return string
     */
    private function cssToXPath($selector) {
        $selector = trim($selector);
        
        // Handle direct XPath
        if (strpos($selector, '//') === 0 || strpos($selector, '/') === 0) {
            return $selector;
        }
        
        // Split selector into parts (handle space-separated selectors)
        $parts = preg_split('/\s+/', $selector);
        $xpath_parts = [];
        
        foreach ($parts as $part) {
            $xpath_parts[] = $this->selectorPartToXPath($part);
        }
        
        return '//' . implode('//', $xpath_parts);
    }
    
    /**
     * Convert a single CSS selector part to XPath
     *
     * @param string $part
     * @return string
     */
    private function selectorPartToXPath($part) {
        $element = '*';
        $conditions = [];
        
        // Handle > for direct child (shouldn't appear here if split correctly)
        $part = ltrim($part, '>');
        
        // Extract element name (if any)
        if (preg_match('/^([a-zA-Z][a-zA-Z0-9]*)/', $part, $match)) {
            $element = $match[1];
            $part = substr($part, strlen($match[1]));
        }
        
        // Extract #id
        if (preg_match('/#([a-zA-Z0-9_-]+)/', $part, $match)) {
            $conditions[] = '@id="' . $match[1] . '"';
            $part = str_replace($match[0], '', $part);
        }
        
        // Extract .class (can be multiple)
        while (preg_match('/\.([a-zA-Z0-9_-]+)/', $part, $match)) {
            $conditions[] = 'contains(@class, "' . $match[1] . '")';
            $part = preg_replace('/\.' . preg_quote($match[1], '/') . '/', '', $part, 1);
        }
        
        // Extract [attr*="value"] (contains)
        while (preg_match('/\[([a-zA-Z0-9_-]+)\*=["\']([^"\']+)["\']\]/', $part, $match)) {
            $conditions[] = 'contains(@' . $match[1] . ', "' . $match[2] . '")';
            $part = str_replace($match[0], '', $part);
        }
        
        // Extract [attr^="value"] (starts with)
        while (preg_match('/\[([a-zA-Z0-9_-]+)\^=["\']([^"\']+)["\']\]/', $part, $match)) {
            $conditions[] = 'starts-with(@' . $match[1] . ', "' . $match[2] . '")';
            $part = str_replace($match[0], '', $part);
        }
        
        // Extract [attr="value"]
        while (preg_match('/\[([a-zA-Z0-9_-]+)=["\']([^"\']+)["\']\]/', $part, $match)) {
            $conditions[] = '@' . $match[1] . '="' . $match[2] . '"';
            $part = str_replace($match[0], '', $part);
        }
        
        // Extract [attr] (has attribute)
        while (preg_match('/\[([a-zA-Z0-9_-]+)\]/', $part, $match)) {
            $conditions[] = '@' . $match[1];
            $part = str_replace($match[0], '', $part);
        }
        
        // Build XPath
        $xpath = $element;
        if (!empty($conditions)) {
            $xpath .= '[' . implode(' and ', $conditions) . ']';
        }
        
        return $xpath;
    }
    
    /**
     * Get document title
     *
     * @return string|null
     */
    public function getTitle() {
        return $this->getText('title');
    }
    
    /**
     * Get OG image
     *
     * @return string|null
     */
    public function getOgImage() {
        return $this->getMeta('og:image');
    }
    
    /**
     * Get OG description
     *
     * @return string|null
     */
    public function getOgDescription() {
        return $this->getMeta('og:description');
    }
    
    /**
     * Get full HTML content of the document
     *
     * @return string
     */
    public function getFullHtml() {
        return $this->dom->saveHTML();
    }
}
