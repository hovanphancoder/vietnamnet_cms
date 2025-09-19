<?php

// Check if PATH_ROOT is not defined, prevent direct access
if (!defined('PATH_ROOT')) {
    exit('No direct access allowed.');
}

/**
 * base_url function
 * Returns the base URL of the application
 * 
 * @param string $path Relative path to append to base URL
 * @return string Full URL
 */
function base_url($path = '', $lang = APP_LANG) {
	global $base_url;
	if (empty($base_url)){
		$app_url = config('app');
    	$base_url = !empty($app_url['app_url']) ? $app_url['app_url'] : '/';
		unset($app_url);
	}
    //Step 1: Lang Default have Lang Code? True or False
    $rewrite_uri_lang = option('rewrite_uri_lang');
	//Step 2: Split path and query string
    $parts = explode('?', trim($path, '/'), 2);
    $clean_path = trim($parts[0], '/');
    if (!empty($clean_path)) {
        if ($lang != APP_LANG_DF || $rewrite_uri_lang){
            $clean_path = $lang.'/'.$clean_path.'/'; 
        }else{
            $clean_path = $clean_path.'/';
        }
    }else{
        if ($lang != APP_LANG_DF || $rewrite_uri_lang){
            $clean_path = $lang.'/'; 
        }
    }
    $query = isset($parts[1]) && !empty($parts[1]) ? '?' . $parts[1] : '';
    if (empty($query)) {
        return rtrim($base_url, '/') . '/' . $clean_path;
    } else {
        return rtrim($base_url, '/') . '/' . $clean_path . $query;
    }
}

if (!function_exists('lang_url')) {
    /**
     * Change Language of URL
     *
     * @param string $lang Code New Languages (EXP: "en", "vi", ...)
     * @return string Full New Languages URL
     */
   function lang_url($lang = APP_LANG, $uri = null){
        if (empty($uri)){
            $uri = ltrim($_SERVER['REQUEST_URI'], '/');
        }
        $segments = explode('/', $uri);
        if (isset(APP_LANGUAGES[$segments[0]])) {
            array_shift($segments);
        }
        return base_url(implode('/', $segments), $lang);
    }

}


if (!function_exists('public_url')) {
    function public_url($path = '')
    {
        global $public_url;
        if (empty($public_url)) {
            $app_url = config('app');
            $public_url = !empty($app_url['app_url']) ? $app_url['app_url'] : '/';
            unset($app_url);
        }
        return rtrim($public_url, '/') . '/' . trim($path, '/');
    }
}


/**
 * Theme Theme URL
 * @param string $path Relative path to append to theme assets URL
 * @return string URI
 */
if(!function_exists('theme_url')) {
    function theme_url($path = '') {
        return public_url('themes/'.APP_THEME_NAME.'/').trim($path, '/');
    }
}

/**
 * Theme assets URL
 * @param string $path Relative path to append to theme assets URL
 * @return string URI
 */
if(!function_exists('theme_assets')) {
    function theme_assets($path = '', $area = 'Frontend') {
        return public_url('themes/'.APP_THEME_NAME.'/'.ucfirst($area).'/assets/'.trim($path, '/'));
    }
}

/**
 * Plugin assets URL
 * @param string $path Relative path to append to theme assets URL
 * @return string URI
 */
if(!function_exists('plugin_assets')) {
    function plugin_assets($path = '', $area = 'default') {
        return public_url('plugins/'.strtolower($area).'/assets/'.trim($path, '/'));
    }
}



/**
 * redirect function
 * Redirect to another URL
 * 
 * @param string $url URL to redirect to
 */
function redirect($url) {
    header('Location: ' . $url);
    echo '<meta http-equiv="refresh" content="0; url='.$url.'">';
    exit();
}

/**
 * sanitize_url function
 * Process and remove URLs containing unsafe paths like '../../'
 * 
 * @param string $url URL to check
 * @return string Processed URL
 */
function sanitize_url($url) {
    return filter_var($url, FILTER_SANITIZE_URL);
}

/**
 * parse_uri function
 * Convert URI to appropriate format for router processing
 * 
 * @param string $uri URI to parse
 * @return string Cleaned and normalized URI
 */
function parse_uri($uri) {
	if (!empty($uri)){
        return trim($uri, '/');
    }
    return $uri;
}

/**
 * Get URI from request
 * 
 * @return string Processed URI
 */
function request_uri() {
    if (!isset($_SERVER['REQUEST_URI'])){
        $_SERVER['REQUEST_URI'] = '/';
    }
    $app_url = config('app');
    $app_url = !empty($app_url['app_url']) ? $app_url['app_url'] : '/';
    $base_path = parse_url($app_url, PHP_URL_PATH); // Get path part from app_url
    $request_uri = $_SERVER['REQUEST_URI'];
	$request_uri = preg_replace('/(\/+)/', '/', $request_uri);
	if ($request_uri != $_SERVER['REQUEST_URI']){
		redirect($request_uri);
	}
	// If request URI starts with base_path, remove it
	if (strpos($request_uri, $base_path) === 0) {
		$request_uri = substr($request_uri, strlen($base_path));
	}
    // Clean remaining URI
    return parse_url($request_uri, PHP_URL_PATH);
}
