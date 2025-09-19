<?php
namespace System\Drivers\Cache;
use System\Core\AppException;

class UriCache {

    protected $cacheDir;
    protected $whitelist = ['page','paged','limit','sortby','sort','sc','order','orderby','id'];
    protected $compression; // 0 = no gzip, 1-9 = gzip level
    protected $headerGzip = false; // Variable to determine sending gzip header when getting cache
    protected $headerType;
    protected $cacheLogin = false;
    protected $cacheMobile = false;

    public function __construct($compression = 0, $type = 'html') {
        // Set cache path (assume PATH_ROOT is defined)
        $this->cacheDir = PATH_WRITE . 'cache/';
        $this->compression = $compression;
        $this->headerType = $type;

        $option_cache = option('cache');
        $option_cache = array_column($option_cache, 'cache_value', 'cache_key');
        if (isset($option_cache['cache_params']) && !empty($option_cache['cache_params'])) {
            $option_cache['cache_params'] = explode(',', $option_cache['cache_params']);
            $this->whitelist = $option_cache['cache_params'];
        }
        if (isset($option_cache['cache_uri']) && !empty($option_cache['cache_uri'])) {
            $this->cacheDir = PATH_WRITE . $option_cache['cache_uri'] .'/';
        }
    }

    /**
     * Function to get gzip compression ratio
     */

    public function gzip_level(){
        return $this->compression;
    }

    /**
     * Send appropriate header based on $this->headerGzip and document type and html or json etc.
     */
    public function headers() {
        $contentTypeMap = [
            'html' => 'text/html; charset=UTF-8',
            'json' => 'application/json; charset=UTF-8',
            'text' => 'text/plain; charset=UTF-8',
            'xml' => 'application/xml; charset=UTF-8',
            'css' => 'text/css; charset=UTF-8',
            'js'  => 'application/javascript; charset=UTF-8',
        ];
        if ($this->headerGzip) {
            header('Content-Encoding: gzip');
        }
        $ctype = isset($contentTypeMap[$this->headerType]) ? $contentTypeMap[$this->headerType] : 'text/html; charset=UTF-8';
        header('Content-Type: ' . $ctype);
    }

    /**
     * Send appropriate header based on $this->headerGzip and document type and html or json etc. Then echo $content to browser
     */
    public function render($content) {
        $this->headers();
        echo $content;
        exit();
    }

    /**
     * Build path to cache folder
     */
    protected function getCacheFolderPath() {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri  = rtrim($uri, '/');
        if ($uri === '') {
            $uri = '/';
        }
        $query_str = $_SERVER['QUERY_STRING'] ?? '';
        $args_path = '';
        if (!empty($query_str)) {
            parse_str($query_str, $params);
            $filtered = [];
            foreach ($params as $k => $v) {
                $k_lower = strtolower($k);
                if (in_array($k_lower, $this->whitelist)) {
                    $filtered[$k_lower] = $v;
                }
            }
            ksort($filtered);
            if (!empty($filtered)) {
                $pairs = [];
                foreach ($filtered as $fk => $fv) {
                    $fv = rawurlencode(rawurldecode($fv));
                    $fv = str_replace('%20', '+', $fv);
                    $pairs[] = $fk . '/' . $fv;
                }
                $args_path = '/' . implode('/', $pairs);
            }
        }
        $fullPath = $this->cacheDir 
            . $host
            . '/'
            . trim($uri, '/')
            . $args_path
            . '/';
        return $fullPath;
    }


    /**
     * Return cache file path based on gzip configuration
     * @param bool $use_gzip If true, return gzip file (.html_gzip), otherwise return uncompressed file (.html)
     */
    protected function getCacheFilePath($use_gzip = false)
    {
        $fullPath = $this->getCacheFolderPath();
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }
        $is_https  = $this->isHttps();
        $is_mobile = $this->isMobile();
        $gzip_suffix = ($use_gzip && $this->compression > 0) ? '_gzip' : '';
        $filename = 'index';
        if ($is_mobile && $this->cacheMobile) {
            $filename .= '-mobile';
        }
        if ($is_https) {
            $filename .= '-https';
        }
        $filename .= '.html' . $gzip_suffix;
        return $fullPath . $filename;
    }

    /**
     * Configure cache for logged in users:
     * If state = 1 => cache even when logged in
     * If state = 0 => when logged in will not cache, when not logged in still cache
     */
    public function cacheLogin($state = 1){
        $this->cacheLogin = $state;
    }

    /**
     * Enable/disable cache specifically for mobile
     *
     * @param int $state 1 = enable (create .mobile-active file), 0 = disable (delete .mobile-active file)
     * @return bool true if operation successful, false if failed
     */
    public function cacheMobile($state = 1) {
        $this->cacheMobile = $state;
        return;
        $fullPath = $this->getCacheFolderPath();
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }
        $mobileActivePath = $fullPath . '/.mobile-active';
        if ($state) {
            return touch($mobileActivePath);
        } else {
            return !file_exists($mobileActivePath) || unlink($mobileActivePath);
        }
    }

    /**
     * Save cache.
     * If $this->compression > 0, will save both uncompressed file and gzip compressed file.
     */
    public function set($content, $return_gzip = false) {
        if ($this->isUserLoggedIn() && !$this->cacheLogin) {
            return false;
        }
        // Save uncompressed file (to ensure when browser doesn't support gzip)
        if (file_put_contents( $this->getCacheFilePath(false) , $content, LOCK_EX) === false){
            throw new AppException('Can not write cache: ' . $this->getCacheFilePath(false));
        }
        // If gzip is enabled, save additional compressed file
        if ($this->compression > 0) {
            $gzipContent = gzencode($content, $this->compression);
            if (file_put_contents( $this->getCacheFilePath(true) , $gzipContent, LOCK_EX) === false){
                throw new AppException('Can not write cache: ' . $this->getCacheFilePath(false));
            }
            if ($return_gzip){
                // Check browser header: support gzip
                $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
                $gzip_supported = (strpos($acceptEncoding, 'gzip') !== false) || (strpos($acceptEncoding, 'br') !== false);
                if ($gzip_supported) {
                    $this->headerGzip = true;
                    return $gzipContent;
                }
            }
        }
        return $content;
    }

    /**
     * Get cache.
     * If browser supports gzip, and .html_gzip file exists then return compressed content (and set headerGzip = true),
     * otherwise return uncompressed file (with headerGzip = false).
     */
    public function get() {
        if ($this->isUserLoggedIn() && !$this->cacheLogin) {
            return null;
        }
        $gzip_supported = false;
        if (!empty($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
            $gzip_supported = true;
        }
        if ($gzip_supported && $this->compression > 0 && file_exists($this->getCacheFilePath(true))) {
            $this->headerGzip = true;
            $file = $this->getCacheFilePath(true);
        } else {
            $this->headerGzip = false;
            $file = $this->getCacheFilePath(false);
        }
        if (!file_exists($file)) {
            return null;
        }
        $data = file_get_contents($file);
        return ($data === false) ? null : $data;
    }

    public function debug() {
        if ($this->isUserLoggedIn() && !$this->cacheLogin) {
            return null;
        }
        $gzip_supported = false;
        if (!empty($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
            $gzip_supported = true;
        }
        if ($gzip_supported && $this->compression > 0 && file_exists($this->getCacheFilePath(true))) {
            $file = $this->getCacheFilePath(true);
        } else {
            $file = $this->getCacheFilePath(false);
        }
        return [
            'gzip' => $gzip_supported,
            'cache_path' => $file
        ];
    }

   /**
     * Delete cache.
     */
    public function delete() {
        $nonGzipPath = $this->getCacheFilePath(false);
        $gzipPath    = $this->getCacheFilePath(true);
        $result1 = file_exists($nonGzipPath) ? @unlink($nonGzipPath) : false;
        $result2 = file_exists($gzipPath) ? @unlink($gzipPath) : false;
        return $result1 || $result2;
    }

    /**
     * Check if cache exists (at least one of the 2 files)
     */
    public function has() {
        return file_exists($this->getCacheFilePath(false)) || file_exists($this->getCacheFilePath(true));
    }

    protected function isHttps() {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }
        if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
            return true;
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
            return true;
        }
        return false;
    }

    protected function isMobile() {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $pattern = '/phone|windows\s+phone|ipod|ipad|blackberry|(?:android|bb\d+|meego|silk|googlebot).+?mobile|palm|windows\s+ce|opera\ mini|avantgo|mobilesafari|docomo|kaios/i';
        return (bool)preg_match($pattern, $ua);
    }

    
    protected function isUserLoggedIn() {
        return isset($_COOKIE['cmsff_logged']);
    }
    
    /**
     * Clear all cache.
     */
    public function clear() {
        $this->rrmdir($this->cacheDir);
        return true;
    }

    protected function rrmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object !== '.' && $object !== '..') {
                    $objPath = $dir.'/'.$object;
                    if (is_dir($objPath)) {
                        $this->rrmdir($objPath);
                    } else {
                        @unlink($objPath);
                    }
                }
            }
            @rmdir($dir);
        }
    }
}
