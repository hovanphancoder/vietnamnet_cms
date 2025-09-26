<?php
if (!defined('PATH_ROOT')) {
    exit('No direct access allowed.');
}

if (!function_exists('api_url')){
    function api_url($path = '') {
        global $base_url;
        if (empty($base_url)){
            $app_url = config('app');
            $base_url = !empty($app_url['app_url']) ? $app_url['app_url'] : '/';
            unset($app_url);
        }
        return rtrim($base_url, '/') . '/api/' . trim($path, '/').'/';
    }
}
if (!function_exists('admin_url')){
    function admin_url($path = '') {
        $parts = explode('?', trim($path, '/'), 2);
        if (count($parts) > 1 && !empty(trim($parts[1]))) {
            return base_url('/admin/' . trim($parts[0], '/').'/?' . $parts[1]);
        }else{
            return base_url('/admin/' . trim($parts[0], '/').'/');
        }
    }
}


if (!function_exists('api_url')){
    function api_url($path = '') {
        global $base_url;
        if (empty($base_url)){
            $app_url = config('app');
            $base_url = !empty($app_url['app_url']) ? $app_url['app_url'] : '/';
            unset($app_url);
        }
        return rtrim($base_url, '/') . '/api/' . trim($path, '/').'/';
    }
}
if (!function_exists('admin_url_lang')){
    function admin_url_lang($path = '') {
        global $base_url;
        if (empty($base_url)){
            $app_url = config('app');
            $base_url = !empty($app_url['app_url']) ? $app_url['app_url'] : '/';
            unset($app_url);
        }
        return rtrim($base_url, '/') . '/admin/languages/' . trim($path, '/').'/';
    }
}
//admin_url_lang('index') . admin_url_lang('add') => /admin/languages/add
if (!function_exists('admin_url_posttype')){
    function admin_url_posttype($path = '') {
        global $base_url;
        if (empty($base_url)){
            $app_url = config('app');
            $base_url = !empty($app_url['app_url']) ? $app_url['app_url'] : '/';
            unset($app_url);
        }
        return rtrim($base_url, '/') . '/admin/posttype/' . trim($path, '/').'/';
    }
}




/*
$posttype_slug = 'movie';
$field = ['id': 1735633754761, 'post_type_reference':'director', 'table_save_data_reference': 0] 
$table_rel = table_posts_rel($posttype_slug, $field) => ["posttype_slug" => movie, "field_id" => 1735633754761, "reference" => director, "whereby" => "post_rel_id", "selectby"=> "post_id"];
$this->modelPostrel = new Postrel($table_rel);

$posttype_slug = 'director';
$field = ['id': 1735633754761, 'post_type_reference':'movie', 'table_save_data_reference': 1] 
["posttype_slug" => movie, "field_id" => 1735633754761, "reference" => director, "whereby" => "post_id", "selectby"=> "post_rel_id"];
*/

if(!function_exists('convers_array')) {
    function convers_array($data) {
        if (is_string($data)) {
            $data = json_decode($data, true);
        } elseif (is_object($data)) {
            $data = (array)$data;
        } elseif (!is_array($data)) {
            $data = [];
        }
        return $data;
    }
}

if(!function_exists('indexByFieldName')) {
    function indexByFieldName($data) {
        $result = [];
        if(empty($data)) {
            return $result;
        }
        foreach ($data as $item) {
            if(isset($item['field_name'])) {
                $result[$item['field_name']] = $item;
            }
            if(isset($item['Field'])) {
                $result[$item['Field']] = $item;
            }
        }
        return $result;
    }
}

if(!function_exists('indexByID')) {
    function indexByID($data) {
        if(is_string($data)) {
            $data = json_decode($data, true);
        }
        $result = [];
        if(empty($data)) {
            return $result;
        }
        foreach ($data as $item) {
            if(isset($item['id'])) {
                $result[$item['id']] = $item;
            }
        }
        return $result;
    }
}


// fomat json oject js to json object php
// if(!function_exists('json_decode_array')) {
//     function json_decode_array($data) {
//         $data = preg_replace('/([{,])(\s*)([a-zA-Z_][a-zA-Z0-9_]*)(\s*):/', '$1"$3":', $data);
//         $data = preg_replace('/:(\s*)([a-zA-Z_][a-zA-Z0-9_]*)/', ': "$2"', $data);
//         $data = preg_replace('/"null"/', 'null', $data);
//         $data = preg_replace('/"true"/', 'true', $data);
//         $data = preg_replace('/"false"/', 'false', $data);
//         $data = preg_replace('/:\s*,/', ': null,', $data);
//         return json_decode($data, true);
//     }
// }





// if(!function_exists('del_cache')) {
//     function del_cache($path, $prefix_path = '') {
//         // Normalize path
//         $path = trim($path, '/');
//         $prefix_path = trim($prefix_path, '/');
        
//         // Loop through supported languages
//         foreach (APP_LANGUAGES as $lang) {
//             // Create base path for each language
//             $cache_dir = $config_files['path'] ?? 'writeable/cache';
            
//             $base_cache = realpath(rtrim(PATH_ROOT, '/') . DIRECTORY_SEPARATOR . trim($cache_dir, '/') . DIRECTORY_SEPARATOR);
            
//             // Add prefix_path if exists
//             if (!empty($prefix_path)) {
//                 $base_cache .= $prefix_path . '/';
//             }
            
//             // Call recursive delete function
//             hamXoaFileFolderDeQuy($base_cache . $path . '/');
//         }
//     }
// }


// if(!function_exists('hamXoaFileFolderDeQuy')) {
//     function hamXoaFileFolderDeQuy($dir) {
//         if (!file_exists($dir)) {
//             return;
//         }
        
//         // Scan all files and folders
//         $files = scandir($dir);
//         foreach ($files as $file) {
//             if ($file != "." && $file != "..") {
//                 $full_path = $dir . $file;
                
//                 if (is_dir($full_path)) {
//                     // If is directory, call recursively to delete inside
//                     hamXoaFileFolderDeQuy($full_path . '/');
//                     rmdir($full_path); // Remove empty directory
//                 } else {
//                     // Delete file
//                     unlink($full_path);
//                 }
//             }
//         }
        
//         // Remove root directory if empty
//         if (is_dir($dir)) {
//             rmdir($dir);
//         }
//     }
// }


// render img tag (lazy load).... title, alt, src, class, style

if (!function_exists('get_template')) {
    function get_template($templateName, $data = [], $area = 'Frontend')
    {
        // Kiểm tra xem file template có tồn tại không
        $templateFile = APP_THEME_PATH . ucfirst($area) . '/' . $templateName . '.php';
        if (file_exists($templateFile)) {
            // Tạo một scope riêng cho template
            (function($data) use ($templateFile) {
                extract($data); // Chuyển mảng data thành các biến
                include $templateFile;
            })($data);
        } else {
            echo '<!-- Template file not found: ' . $templateName . ' -->';
        }
    }
}