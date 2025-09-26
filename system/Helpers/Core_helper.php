<?php

// Check if PATH_ROOT is not defined, prevent direct access
if (!defined('PATH_ROOT')) {
    exit('No direct access allowed.');
}
use System\Libraries\Logger;

/**
 * load_helpers function
 * Load list of specified helpers
 * 
 * @param array $helpers List of helpers to load
 */
function load_helpers(array $helpers = []) {
    // Global variable to track loaded helpers
    global $fast_helpers;
    // If variable not initialized, create array to store loaded helpers
    if (!isset($fast_helpers)) {
        $fast_helpers = [];
    }

    foreach ($helpers as $helper) {
        // Check if helper has been loaded before
        if (!in_array($helper, $fast_helpers)) {
            $helperPath = PATH_SYS . 'Helpers/' . ucfirst($helper) . '_helper.php';
            if (file_exists($helperPath)) {
                $fast_helpers[] = $helper;
                require_once $helperPath;
            } else {
                $helperPath = PATH_ROOT . '/helpers/' . ucfirst($helper) . '_helper.php';
                if (file_exists($helperPath)) {
                    $fast_helpers[] = $helper;
                    require_once $helperPath;
                } else {
                    throw new \System\Core\AppException("Helper not found: " . $helper." - ". $helperPath);
                }
            }
        }
    }
}


if (!function_exists('is_sqltable')) {
    /**
     * Validate SQL table name.
     *
     * @param string $str Table name to check.
     * @return bool Returns true if valid, otherwise returns false.
     */
    function is_sqltable($str)
    {
        // Regex check:
        // ^                    : Start of string.
        // (?!(...))           : Negative lookahead excludes forbidden SQL keywords.
        // (select|order|...)   : List of keywords (case insensitive with /i flag).
        // [A-Za-z0-9_]+        : Allows letters, numbers and underscores.
        // $                    : End of string.
        $pattern = '/^(?!(select|order|table|group|where|index|insert|update|delete|from|join|union|having|into|alter|drop|create)$)[A-Za-z0-9_]+$/i';
        return (bool) preg_match($pattern, $str);
    }
}

if (!function_exists('is_sqlcolumn')) {
    /**
     * Validate SQL column name.
     *
     * @param string $str Column name to check.
     * @return bool Returns true if valid, otherwise returns false.
     */
    function is_sqlcolumn($str)
    {
        // Use same regex as table name
        $pattern = '/^(?!(select|order|table|group|where|index|insert|update|delete|from|join|union|having|into|alter|drop|create)$)[A-Za-z0-9_]+$/i';
        return (bool) preg_match($pattern, $str);
    }
}

if (!function_exists('is_slug')) {
    /**
     * Validate slug.
     *
     * @param string $str Slug to check.
     * @return bool Returns true if slug is valid, otherwise returns false.
     */
    function is_slug($str)
    {
        // Regex for slug: allows letters, numbers, hyphens and underscores.
        // ^[A-Za-z0-9\-_]+$ : Start and end with valid characters.
        $pattern = '/^[A-Za-z0-9\-_]+$/i';
        return (bool) preg_match($pattern, $str);
    }
}

if (!function_exists('_sqlname')) {
    /**
     * @param string $str Table name to convert.
     * @return string Valid table name.
     */
    function _sqlname($str)
    {
        // List of forbidden SQL keywords (case insensitive).
        $reserved = [
            'select', 'order', 'table', 'group', 'where', 'index',
            'insert', 'update', 'delete', 'from', 'join', 'union',
            'having', 'into', 'alter', 'drop', 'create'
        ];
        // Step 1: Clean string: convert to lowercase and remove invalid characters.
        $clean = preg_replace('/[^A-Za-z0-9_]/', '', strtolower($str));
        foreach ($reserved as $keyword) {
            $clean = str_ireplace($keyword, '', $clean);
        }
        $clean = str_replace('-', '_', $clean);
        return $clean;
    }
}

if(!function_exists('posttype_add')) {
    /**
     * Json format
     */
    function posttype_add($dataJson) {
        $posttypeController = new \App\Controllers\Backend\PosttypeController();
        return $posttypeController->addViaCode($dataJson);
    }
}

// trans table name posttype $tableName = 'fast_posts_'.$data['slug'].'_'.$lang;
if(!function_exists('posttype_name')) {
    /**
     * Get table name for posttype based on slug and language
     * 
     * @param string $slug Posttype slug
     * @param string $lang Language code
     * @return string|null Table name or null if posttype doesn't exist
     */
    function posttype_name($slug, $lang = APP_LANG) {
        // Sanitize slug
        $slug = _sqlname($slug);
        // Get posttype configuration using posttype() function
        $posttype = posttype($slug);
        if (empty($posttype)) {
            return null;
        }
        // If first language is 'all' - table name is just slug
        if (empty($posttype['languages']) || $posttype['languages'][0] === 'all') {
            return 'fast_posts_' . $slug;
        }
        // If not 'all', table name includes language suffix
        return 'fast_posts_' . $slug . '_' . $lang;
    }
}

if(!function_exists('posttype_exists')) {
    /**
     * Check if posttype exists and language is supported
     * 
     * @param string $slug Posttype slug
     * @param string $lang Language code (optional)
     * @return bool True if posttype exists and language is supported, false otherwise
     */
    function posttype_exists($slug, $lang = APP_LANG) {
        // Sanitize slug
        $slug = _sqlname($slug);
        // Get posttype configuration using posttype() function
        $posttype = posttype($slug);
        if (empty($posttype)) {
            return false;
        }
        // Check if first language is 'all' - means supports all languages
        if (empty($posttype['languages']) || $posttype['languages'][0] === 'all') {
            return true;
        }
        // Check if the specified language is in the supported languages array
        if (in_array($lang, $posttype['languages'])) {
            return true;
        }
        return false;
    }
}

// trans table name posttype $tableName = 'fast_posts_'.$data['slug'].'_'.$lang;
if(!function_exists('table_posttype')) {
    function table_posttype($slug, $lang = '') {
        $slug = _sqlname($slug);
        if(empty($lang) || $lang === 'all') {
            $tableName = 'fast_posts_'.$slug;
        } else {
            $tableName = 'fast_posts_'.$slug.'_'.$lang;
        }
        return  $tableName;
    }
}
// trans table name relationshop postype
if(!function_exists('table_posttype_relationship')) {
    function table_posttype_relationship($slug) {
        $slug = _sqlname($slug);
        $tableName = 'fast_posts_'.$slug.'_rel';
        return  $tableName;
    }
}

// trans table name posts rel
if(!function_exists('table_posts_rel')) {
    function table_posts_rel($posttype_slug, $field) {
        if (is_object($field)){
            $field = (array)$field;
        }
        if (empty($posttype_slug) || empty($field['type']) || empty($field['id']) || ucfirst($field['type']) != 'Reference' || empty($field['post_type_reference']) || !isset($field['table_save_data_reference']) ) return null;
        if ((int)$field['table_save_data_reference'] === 1){
            return ["posttype_slug" => $field['post_type_reference'], "field_id" => $field['id'], "reference" => $posttype_slug, "whereby" => "post_id", "selectby"=> "post_rel_id"];
        }else{
            return ["posttype_slug" => $posttype_slug, "field_id" => $field['id'], "reference" => $field['post_type_reference'], "whereby"=> "post_rel_id", "selectby"=> "post_id"];
        }
    }
}

function DateTime() {
    return date('Y-m-d H:i:s');
}

/**
 * version_php function
 * Get current PHP version
 * 
 * @return string Current PHP version
 */
function version_php() {
    return PHP_VERSION;
}

/**
 * dir_writable function
 * Check if path is a directory and has write permission
 * 
 * @param string $path Path to check
 * @return bool True if it's a directory and has write permission, False otherwise
 */
function dir_writable($path) {
    return is_dir($path) && is_writable($path);
}

/**
 * server_info function
 * Return information about current server (including PHP version, server software, etc.)
 * 
 * @return array Array containing server information
 */
function server_info() {
    return [
        'php_version' => version_php(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
        'server_name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
        'server_protocol' => $_SERVER['SERVER_PROTOCOL'] ?? 'Unknown',
    ];
}  

/**
 * random_string function
 * Generate a random string with desired length
 * 
 * @param int $length Length of random string to generate
 * @return string Generated random string
 */
function random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}


/**
 * Function to get configuration from config.php file
 * 
 * @param string $key Name of configuration to get
 * @param mixed $default Default value if configuration not found
 * @return mixed Configuration value or default value
 */
function config($key = '', $file = 'Config') {
    static $global_configs;
    $file = ucfirst($file);
    if (!is_array($global_configs)) {
        $global_configs = array();
    }
    if (!isset($global_configs[$file])) {
        $global_configs[$file] = require PATH_APP . 'Config/'.$file.'.php';
        if (empty($global_configs[$file]) || !is_array($global_configs[$file])) {
            $global_configs[$file] = [];
        }
    }
    return $global_configs[$file][$key] ?? null;
}


function posttype($key, $lang = APP_LANG) {
    static $global_posttypes;
    if (!isset($global_posttypes) || !is_array($global_posttypes)) {
        $global_posttypes = require PATH_APP . 'Config/Posttype.php';
        if (empty($global_posttypes) || !is_array($global_posttypes)) {
            $global_posttypes = [];
        }
    }
    return $global_posttypes[$key] ?? null;
}


function option($key, $lang = APP_LANG) {
    static $global_options;
    if (!isset($global_options) || !is_array($global_options)) {
        $global_options = require PATH_APP . 'Config/Options.php';
        if (empty($global_options) || !is_array($global_options)) {
            $global_options = [];
        }
    }
    if (isset($global_options[$key]) && is_array($global_options[$key])){ // Return value from Application Memory
        if ($lang != APP_LANG_DF && isset($global_options[$key]['valuelang']) && !empty($global_options[$key]['valuelang']) ){
            if (!is_array($global_options[$key]['valuelang'])){   
                $global_options[$key]['valuelang'] = json_decode($global_options[$key]['valuelang'], true) ?? [];
            }
            if (isset($global_options[$key]['valuelang'][$lang])){
                //$value = json_decode($global_options[$key]['valuelang'][$lang], true) ?? $global_options[$key]['valuelang'][$lang];
                return $global_options[$key]['valuelang'][$lang];
            }
        }
        return $global_options[$key]['value'];
    }else{
        static $optionsModel;
        if (empty($optionsModel)){
            $optionsModel = new \App\Models\OptionsModel();
        }
        $option =  $optionsModel->getByName($key);
        $global_options[$key] = array('value' => null);
        if (empty($option)){
            return null;
        }
        
        if ($lang != APP_LANG_DF && isset($option['valuelang']) && !empty($option['valuelang']) ){
            if (!is_array($option['valuelang'])){   
                $option['valuelang'] = json_decode($option['valuelang'], true) ?? [];
                $global_options[$key]['valuelang'] = $option['valuelang'];
            }
            if (isset($option['valuelang'][$lang])){
                $global_options[$key]['value'] = json_decode($option['valuelang'][$lang], true) ?? $option['valuelang'][$lang];
            }
        }
        $global_options[$key]['value'] = $option['value'];
        return $global_options[$key]['value'];
    }
}


if (!function_exists('option_set')) {
    function option_set($key, $value, $lang = '') {
        static $global_options;
        if (!isset($global_options) || !is_array($global_options)) {
            $global_options = require PATH_APP . 'Config/Options.php';
            if (empty($global_options) || !is_array($global_options)) {
                $global_options = [];
            }
        }
        // Clean value: không cho phép inject code PHP
        if (is_string($value)) {
            $value = str_replace(['<?', '?>', '<?php'], '', $value);
        }
        // Assign new value in memory based on $lang
        if (isset(APP_LANGUAGES[$lang])) {
            // If $lang exists, save to [$key]['valuelang'][$lang]
            if (!isset($global_options[$key])) {
                $global_options[$key] = [];
            }
            if (!isset($global_options[$key]['valuelang'])) {
                $global_options[$key]['valuelang'] = [];
            }
            $global_options[$key]['valuelang'][$lang] = $value;
        } else {
            // If no $lang, save to [$key]['value']
            if (!isset($global_options[$key])) {
                $global_options[$key] = [];
            }
            $global_options[$key]['value'] = $value;
        }
        
        // Cleanup: Remove valuelang keys that are not in APP_LANGUAGES
        $codeLanguages = array_keys(APP_LANGUAGES);
        foreach ($global_options as $optionKey => &$optionData) {
            if (isset($optionData['valuelang']) && is_array($optionData['valuelang'])) {
                foreach ($optionData['valuelang'] as $langKey => $langValue) {
                    if (!in_array($langKey, $codeLanguages) || $langKey == APP_LANG_DF) {
                        unset($optionData['valuelang'][$langKey]);
                    }
                }
                // If valuelang becomes empty, remove it
                if (empty($optionData['valuelang'])) {
                    unset($optionData['valuelang']);
                }
            }
        }
        
        // Export array an toàn
        $arrayCode = var_export($global_options, true);
        // Đưa array con lên cùng dòng với key
        $arrayCode = preg_replace('/=>\s*array\s*\(/', '=> array (', $arrayCode);
        $content = "<?php\n\nreturn " . $arrayCode . ";\n";
        @file_put_contents(PATH_APP . 'Config/Options.php', $content);
        // Return just set value
        if (isset(APP_LANGUAGES[$lang])) {
            return $global_options[$key]['valuelang'][$lang];
        } else {
            return $global_options[$key]['value'];
        }
    }
}


/**
 * env function
 * Get environment variable value from cache or read from .env file (if not exists in cache)
 * 
 * @param string $key Environment variable name to get
 * @param mixed $default Default value if variable doesn't exist
 * @return mixed Environment variable value or default value
 */
if(!function_exists('env')) {
    function env($key, $default = null) {
        // Use static array to store loaded values
        static $env_cache = [];

        // If value already exists in cache, return value from cache
        if (isset($env_cache[$key])) {
            return $env_cache[$key];
        }

        // Get value from environment variable
        $value = getenv($key);

        // If environment variable not found, use default value
        if ($value === false) {
            $env_cache[$key] = $default;
            return $default;
        }

        // Remove unsafe characters and save to cache
        $value = trim($value);
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

        // Process special values: true, false, null
        switch (strtolower($value)) {
            case 'true':
                $env_cache[$key] = true;
                break;
            case 'false':
                $env_cache[$key] = false;
                break;
            case 'null':
                $env_cache[$key] = null;
                break;
            default:
                $env_cache[$key] = $value;
        }

        return $env_cache[$key];
    }
}



// debug function
if(!function_exists('prt')) {
    function prt($variable, $name = '') {
        $type = gettype($variable);
        echo '<div style="background: #f4f4f4; border: 1px solid #ccc; border-radius: 6px; padding: 12px; margin: 10px 0; font-size: 15px; line-height: 1.5; font-family: Consolas, monospace;">';
        if ($name) {
            echo '<div style="font-weight: bold; color: #333; margin-bottom: 4px;">' . htmlspecialchars($name) . '</div>';
        }
        echo '<div style="color: #888; font-size: 13px; margin-bottom: 6px;">Type Object: <b>' . $type . '</b></div>';
        echo '<pre style="margin:0; background:transparent; border:none; padding:0; color:#222;">';
        if (is_array($variable) || is_object($variable)) {
            echo htmlspecialchars(print_r($variable, true));
        } else {
            echo htmlspecialchars(var_export($variable, true));
        }
        echo '</pre>';
        echo '</div>';
    }
}
function _bytes($size) {
    $unit = strtolower(substr($size, -1));
    $bytes = (int) $size;
    switch ($unit) {
        case 'g':
            $bytes *= 1024 * 1024 * 1024;
            break;
        case 'm':
            $bytes *= 1024 * 1024;
            break;
        case 'k':
            $bytes *= 1024;
            break;
    }
    return $bytes;
}


if (!function_exists('clear_cache')) {
    function clear_cache($url = '') {
        try {
            $cache_path = PATH_ROOT . '/writeable/cache/';

            if (empty($url)) {
                // Delete all subdirectories in cache/
                $dirs = glob($cache_path . '*', GLOB_ONLYDIR);
                foreach ($dirs as $dir) {
                    delete_dir_recursive($dir);
                }
                Logger::info('Clear All Cache');
                return true;
            }

            // Remove http:// or https:// from URL
            $url = preg_replace('#^https?://#', '', $url);

            // Split host and path
            $parts = explode('/', $url, 2);
            $host = $parts[0];
            $path = isset($parts[1]) ? '/' . $parts[1] : '';

            // Cache directory path
            $dirPath = rtrim(PATH_ROOT . '/writeable/cache/' . $host . $path, '/');
            $file1 = $dirPath . '/index-https.html';
            $file2 = $dirPath . '/index-https.html_gzip';

            if (file_exists($file1)) unlink($file1);
            if (file_exists($file2)) unlink($file2);

            // If directory is empty after deleting files, delete it too
            if (is_dir($dirPath) && count(glob("$dirPath/*")) === 0) {
                rmdir($dirPath);
            }

            Logger::info('Clear Cache: ' . $url);
            return true;

        } catch (\Throwable $e) {
            Logger::info('Clear Cache Failed: ' . $e->getMessage());
            return false;
        }
    }

    function delete_dir_recursive($dir) {
        if (!is_dir($dir)) return;
        $items = array_diff(scandir($dir), ['.', '..']);
        foreach ($items as $item) {
            $path = $dir . '/' . $item;
            is_dir($path) ? delete_dir_recursive($path) : unlink($path);
        }
        rmdir($dir);
    }
}

/**
 * autoload_plugins function
 * Load configured plugins
 */
function autoload_plugins() {
    $pluginsDir = PATH_ROOT . '/plugins';
    if (!is_dir($pluginsDir)) {
        return;
    }

    $plugins = scandir($pluginsDir);
    foreach ($plugins as $plugin) {
        if ($plugin === '.' || $plugin === '..') {
            continue;
        }

        $pluginPath = $pluginsDir . '/' . $plugin;
        if (is_dir($pluginPath)) {
            // Load plugin config
            $configFile = $pluginPath . '/Config/Config.php';
            if (file_exists($configFile)) {
                $pluginConfig = require $configFile;
                if (empty($pluginConfig) || !is_array($pluginConfig)) {
                    $pluginConfig = [];
                }
                if (isset($pluginConfig['status']) && $pluginConfig['status'] === true) {
                    // Load plugin index file
                    $indexFile = $pluginPath . '/index.php';
                    if (file_exists($indexFile)) {
                        require_once $indexFile;
                    }
                }
            }
        }
    }
}
/**
 * dd function (Dump and Die) - Laravel style debug function
 * Dump the given variables and end execution of the script
 * 
 * @param mixed ...$variables Variables to dump
 */
if(!function_exists('dd')) {
    function dd(...$variables) {
        // Get backtrace to show file and line where dd() was called
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $file = $backtrace[0]['file'] ?? 'Unknown';
        $line = $backtrace[0]['line'] ?? 'Unknown';
        
        echo '<div style="background: #1e1e1e; color: #fff; font-family: Consolas, Monaco, monospace; padding: 20px; margin: 10px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
        echo '<div style="color: #ff6b6b; font-weight: bold; margin-bottom: 15px; border-bottom: 1px solid #333; padding-bottom: 10px;">';
        echo '🔍 DD() called at: ' . htmlspecialchars($file) . ':' . $line;
        echo '</div>';
        
        if (empty($variables)) {
            echo '<div style="color: #888; font-style: italic;">No variables provided to dd()</div>';
        } else {
            foreach ($variables as $index => $variable) {
                $type = gettype($variable);
                $typeColor = match($type) {
                    'string' => '#4ecdc4',
                    'integer' => '#45b7d1',
                    'double' => '#45b7d1',
                    'boolean' => '#f39c12',
                    'array' => '#9b59b6',
                    'object' => '#e74c3c',
                    'NULL' => '#95a5a6',
                    default => '#95a5a6'
                };
                
                echo '<div style="margin-bottom: 20px; border: 1px solid #333; border-radius: 4px; overflow: hidden;">';
                echo '<div style="background: #2c2c2c; padding: 8px 12px; font-weight: bold; color: ' . $typeColor . ';">';
                echo 'Variable #' . ($index + 1) . ' (' . $type . ')';
                echo '</div>';
                echo '<div style="padding: 12px; background: #1e1e1e;">';
                echo '<pre style="margin: 0; color: #fff; font-size: 13px; line-height: 1.4; overflow-x: auto;">';
                
                if (is_array($variable)) {
                    echo htmlspecialchars(print_r($variable, true));
                } elseif (is_object($variable)) {
                    echo htmlspecialchars(print_r($variable, true));
                    if (method_exists($variable, '__toString')) {
                        echo "\n\nString representation:\n";
                        echo htmlspecialchars((string) $variable);
                    }
                } elseif (is_bool($variable)) {
                    echo $variable ? 'true' : 'false';
                } elseif (is_null($variable)) {
                    echo 'null';
                } else {
                    echo htmlspecialchars(var_export($variable, true));
                }
                
                echo '</pre>';
                echo '</div>';
                echo '</div>';
            }
        }
        
        echo '<div style="color: #ff6b6b; font-weight: bold; margin-top: 15px; border-top: 1px solid #333; padding-top: 10px;">';
        echo '🚫 Script execution terminated by dd()';
        echo '</div>';
        echo '</div>';
        
        // End execution
        exit(1);
    }
}

/**
 * dump function - Laravel style debug function without terminating execution
 * Dump the given variables but continue execution
 * 
 * @param mixed ...$variables Variables to dump
 */
if(!function_exists('dump')) {
    function dump(...$variables) {
        // Get backtrace to show file and line where dump() was called
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $file = $backtrace[0]['file'] ?? 'Unknown';
        $line = $backtrace[0]['line'] ?? 'Unknown';
        
        echo '<div style="background: #1e1e1e; color: #fff; font-family: Consolas, Monaco, monospace; padding: 20px; margin: 10px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
        echo '<div style="color: #4ecdc4; font-weight: bold; margin-bottom: 15px; border-bottom: 1px solid #333; padding-bottom: 10px;">';
        echo '🔍 DUMP() called at: ' . htmlspecialchars($file) . ':' . $line;
        echo '</div>';
        
        if (empty($variables)) {
            echo '<div style="color: #888; font-style: italic;">No variables provided to dump()</div>';
        } else {
            foreach ($variables as $index => $variable) {
                $type = gettype($variable);
                $typeColor = match($type) {
                    'string' => '#4ecdc4',
                    'integer' => '#45b7d1',
                    'double' => '#45b7d1',
                    'boolean' => '#f39c12',
                    'array' => '#9b59b6',
                    'object' => '#e74c3c',
                    'NULL' => '#95a5a6',
                    default => '#95a5a6'
                };
                
                echo '<div style="margin-bottom: 20px; border: 1px solid #333; border-radius: 4px; overflow: hidden;">';
                echo '<div style="background: #2c2c2c; padding: 8px 12px; font-weight: bold; color: ' . $typeColor . ';">';
                echo 'Variable #' . ($index + 1) . ' (' . $type . ')';
                echo '</div>';
                echo '<div style="padding: 12px; background: #1e1e1e;">';
                echo '<pre style="margin: 0; color: #fff; font-size: 13px; line-height: 1.4; overflow-x: auto;">';
                
                if (is_array($variable)) {
                    echo htmlspecialchars(print_r($variable, true));
                } elseif (is_object($variable)) {
                    echo htmlspecialchars(print_r($variable, true));
                    if (method_exists($variable, '__toString')) {
                        echo "\n\nString representation:\n";
                        echo htmlspecialchars((string) $variable);
                    }
                } elseif (is_bool($variable)) {
                    echo $variable ? 'true' : 'false';
                } elseif (is_null($variable)) {
                    echo 'null';
                } else {
                    echo htmlspecialchars(var_export($variable, true));
                }
                
                echo '</pre>';
                echo '</div>';
                echo '</div>';
            }
        }
        
        echo '<div style="color: #4ecdc4; font-weight: bold; margin-top: 15px; border-top: 1px solid #333; padding-top: 10px;">';
        echo '✅ Script execution continues after dump()';
        echo '</div>';
        echo '</div>';
    }
}



if (!function_exists('_json_decode')) {
    /**
     * Safely decode JSON data
     * @param mixed $data
     * @param mixed $default
     * @return array
     */
    function _json_decode($data, $default = []) {
        if (is_array($data)) {
            return $data;
        }
        $decoded = json_decode((string)$data, true);
        return is_array($decoded) ? $decoded : $default;
    }
}