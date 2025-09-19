<?php
// Check if PATH_ROOT is not defined, prevent direct access
if (!defined('PATH_ROOT')) {
    exit('No direct access allowed.');
}

/**
 * xss_clean function
 * Filter inputs to prevent XSS (Cross-Site Scripting)
 * 
 * @param string $data Data to filter
 * @return string Cleaned data
 */
function xss_clean($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * clean_input function
 * Clean input data to prevent security vulnerabilities like XSS
 * 
 * @param mixed $data Data to clean (string or array)
 * @return mixed Cleaned data
 */
function clean_input($data) {
    if (is_array($data)) {
        // If $data is an array, apply clean_input to each element
        foreach ($data as $key => $value) {
            $data[$key] = clean_input($value);
        }
        return $data;
    } else {
        // If $data is a string, clean normally
        // Remove whitespace at beginning and end
        $data = trim($data);
        // Remove backslash characters \
        $data = stripslashes($data);
        // Remove unwanted characters like ', "
        $data = str_replace(["'", '"'], '', $data);
        // Convert special characters to HTML entities
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        // Remove characters that are not letters, numbers, spaces and basic punctuation
        $data = preg_replace('/[^\w\s\p{P}]/u', '', $data);
        //$data = preg_replace('/[^\p{L}\s_-]/u', '', $data);
        return $data;
    }
}

/**
 * Function to safely get data from $_GET
 * 
 * @param string $key Name of parameter to get
 * @param mixed $default Default value if parameter doesn't exist
 * @return mixed Cleaned data or default value
 */
function S_GET($key, $default = null) {
    if (isset($_GET[$key])) {
        return clean_input($_GET[$key]);
    }
    return $default;
}

/**
 * Function to check if data exists in $_GET
 * 
 * @param string $key Name of parameter to get
 * @return boolean True or False
 */
function HAS_GET($key) {
    return isset($_GET[$key]);
}

/**
 * Function to safely get data from $_POST
 * 
 * @param string $key Name of parameter to get
 * @param mixed $default Default value if parameter doesn't exist
 * @return mixed Cleaned data or default value
 */
function S_POST($key, $default = null) {
    if (isset($_POST[$key])) {
        return clean_input($_POST[$key]);
    }
    return $default;
}

/**
 * Function to check if data exists in $_POST
 * 
 * @param string $key Name of parameter to get
 * @return boolean True or False
 */
function HAS_POST($key) {
    return isset($_POST[$key]);
}

/**
 * Function to safely get data from $_REQUEST
 * 
 * @param string $key Name of parameter to get
 * @param mixed $default Default value if parameter doesn't exist
 * @return mixed Cleaned data or default value
 */
function S_REQUEST($key, $default = null) {
    if (isset($_REQUEST[$key])) {
        return clean_input($_REQUEST[$key]);
    }
    return $default;
}

/**
 * Function to check if data exists in $_REQUEST
 * 
 * @param string $key Name of parameter to get
 * @return boolean True or False
 */
function HAS_REQUEST($key) {
    return isset($_REQUEST[$key]);
}

/**
 * Function to safely get data from $_DELETE
 * 
 * @param string $key Name of parameter to get
 * @param mixed $default Default value if parameter doesn't exist
 * @return mixed Cleaned data or default value
 */
function S_DELETE($key, $default = null) {
    if (isset($_DELETE[$key])) {
        return clean_input($_DELETE[$key]);
    }
    return $default;
}

/**
 * Function to check if data exists in $_DELETE
 * 
 * @param string $key Name of parameter to get
 * @return boolean True or False
 */
function HAS_DELETE($key) {
    return isset($_DELETE[$key]);
}

/**
 * uri_security function
 * Clean and protect URI against XSS, SQL Injection attacks
 * 
 * @param string $uri URI data to clean
 * @return string Cleaned URI
 */
// function uri_security($uri) {
//     // Remove invalid characters from URI
//     $uri = filter_var($uri, FILTER_SANITIZE_URL);
//     $uri = preg_replace('#/+#', '/', $uri); // Remove consecutive // characters
//     $uri = preg_replace('#\.\.+#', '', $uri); // Replace .. or ... with index
//     // Apply additional XSS cleaning steps
//     return xss_clean($uri);
// }

/**
 * Clean URI (path) – remove unwanted characters, keep a-z, A-Z, 0-9, -, _
 * While still **preserving** slash `/` characters to divide folders/route levels.
 */
function uri_security($uri) {
    // Step 1: Decode %xx (if any)
    if (!empty($uri)){
        $uri = rawurldecode($uri);
        // Step 2: Remove consecutive // characters -> only 1 remains
        $uri = preg_replace('#/+#', '/', $uri);
        // Step 3: Avoid '..' or '...' => directory traversal security
        $uri = str_replace(['..', '...'], '', $uri);
        // Step 4: Split by slash, sanitize each "segment"
        $parts = explode('/', $uri);
        $cleanParts = [];
        foreach ($parts as $p) {
            // Only allow [A-Za-z0-9_-.], you can expand as needed (e.g., add . or ~)
            $p = preg_replace('/[^A-Za-z0-9_\-.]/', '', $p);
            $p = trim($p, '.');
            // If segment is not empty after filtering then keep it
            if ($p !== '') {
                $cleanParts[] = $p;
            }
        }
        // Step 5: Combine into new URI
        $cleanUri = implode('/', $cleanParts);
        // Step 6: XSS clean (if xss_clean function exists)
        $cleanUri = xss_clean($cleanUri);
        return $cleanUri;
    }
    return '';
}

function sget_security() {
    $cacheParams = [];
    $option_cache = option('cache');
    if (!is_array($option_cache)){
        $option_cache = json_decode($option_cache, true) ?? [];
    }
    $option_cache = array_column($option_cache, 'cache_value', 'cache_key');
    if (isset($option_cache['cache_params']) && !empty($option_cache['cache_params'])) {
        $option_cache['cache_params'] = explode(',', $option_cache['cache_params']);
        $cacheParams = $option_cache['cache_params'];
    }
    unset($option_cache);
    foreach ($_GET as $key => $value) {
        // Convert key to lowercase:
        if (in_array($key, $cacheParams)) {
           //$safeValue = preg_replace('/[^A-Za-z0-9\p{L}\s\/_-.]/u', '', rawurldecode($value) );
            $safeValue = preg_replace('/[^A-Za-z0-9\p{L}\s\/_\.\-]/u', '', rawurldecode($value));
            //$safeValue = rawurlencode($safeValue);
            if ($safeValue === null) {
                $safeValue = '';
            }
            $_GET[$key] = $safeValue;
        }
    }
    return $_GET;
}