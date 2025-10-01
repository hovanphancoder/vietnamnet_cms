<?php
if (!defined('PATH_ROOT')) {
    exit('No direct access allowed.');
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
// render img tag (lazy load).... title, alt, src, class, style
if(!function_exists('_img')) {
    function _img($src, $title = '', $lazy = true, $class = '', $style = '', $width = '', $height = '', $id = '') {
        // If no image source then return empty string
        if (empty($src)) {
            return '';
        }
        
        // Create attributes for alt and title, escape special characters
        $attr_alt   = ($title !== '') ? ' alt="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '"' : '';
        $attr_title = ($title !== '') ? ' title="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '"' : '';
        
        // Process class, style, width, height attributes (escape if needed)
        $attr_class = ($class !== '') ? ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"' : '';
        $attr_style = ($style !== '') ? ' style="' . htmlspecialchars($style, ENT_QUOTES, 'UTF-8') . '"' : '';
        $attr_width = ($width !== '') ? ' width="' . htmlspecialchars($width, ENT_QUOTES, 'UTF-8') . '"' : '';
        $attr_height= ($height !== '') ? ' height="' . htmlspecialchars($height, ENT_QUOTES, 'UTF-8') . '"' : '';
        $attr_id = ($id !== '') ? ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' : '';
        if ($lazy) {
            // If lazy load is enabled, add "lazyload" class
            if ($class !== '') {
                $class .= ' lazyload';
            } else {
                $class = 'lazyload';
            }
            $attr_class = ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"';
            
            // Create img tag with data-src instead of src for lazy load
            $img_tag = '<img data-src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '"' 
                       . $attr_alt . $attr_title . $attr_class . $attr_style . $attr_width . $attr_height . $attr_id . '>';
            // Create fallback with <noscript> tag so browsers without JS support can still display images
            $noscript_tag = '<noscript><img src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '"' 
                            . $attr_alt . $attr_title . $attr_class . $attr_style . $attr_width . $attr_height . $attr_id . '></noscript>';
            return $img_tag . $noscript_tag;
        } else {
            // If not using lazy load, create normal img tag with src
            return '<img src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '"' 
                   . $attr_alt . $attr_title . $attr_class . $attr_style . $attr_width . $attr_height . $attr_id . '>';
        }
    }
}

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