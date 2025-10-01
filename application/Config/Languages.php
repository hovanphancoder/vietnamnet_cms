<?php
define('APP_LANG_DF', 'en');
define('APP_LANGUAGES', array (
  'en' => 
  array (
    'name' => 'English (US)',
    'flag' => 'us',
  ),
  'vi' => 
  array (
    'name' => 'Tiếng Việt',
    'flag' => 'vn',
  ),
  'zh' => 
  array (
    'name' => 'Chinese',
    'flag' => 'cn',
  ),
  'th' => 
  array (
    'name' => 'Thailand',
    'flag' => 'th',
  ),
  'ko' => 
  array (
    'name' => 'Korea',
    'flag' => 'kr',
  ),
  'id' => 
  array (
    'name' => 'Indonesian',
    'flag' => 'id',
  ),
));

$uri_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri_path = preg_replace('#/+#', '/', $uri_path); // Replace multiple consecutive / with a single /
$uri_segments = explode('/', trim($uri_path, '/'));

// Check if the first segment is in the language list
if (!empty($uri_segments[0]) && isset(APP_LANGUAGES[$uri_segments[0]])) {
    define('APP_LANG', $uri_segments[0]);
} else {
    if (!empty($_REQUEST['lang']) && isset(APP_LANGUAGES[$_REQUEST['lang']])) {
        define('APP_LANG', $_REQUEST['lang']);
    }else{
        define('APP_LANG', APP_LANG_DF);
    }
}
unset($uri_path);
unset($uri_segments);