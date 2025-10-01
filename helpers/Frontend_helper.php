<?php

use Google\Service\SQLAdmin\Flag;
use App\Libraries\Fastlang as Flang;

if (!defined('PATH_ROOT')) {
    exit('No direct access allowed.');
}

// Download URL
if (!function_exists('download_url')) {

    function download_url()
    {
        return base_url('download' . '/');
    }
}

//docs url 
if (!function_exists('docs_url')) {
    function docs_url($path = '')
    {
        // Get original app_url
        $app_config = config('app');
        $base_url = $app_config['app_url'] ?? '/';

        // Parse URL to insert 'docs' into subdomain
        $parsed = parse_url($base_url);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';
        $new_host = 'docs.' . $host;

        // Recreate URL
        $url = $scheme . '://' . $new_host . '/';

        // Attach path if exists
        $path = trim($path, '/');
        if (!empty($path)) {
            $url .= $path . '/';
        }

        return $url;
    }
}

//demo url 
if (!function_exists('demo_url')) {
    function demo_url($path = '')
    {
        // Get original app_url
        $app_config = config('app');
        $base_url = $app_config['app_url'] ?? '/';

        // Parse URL to insert 'demo' into subdomain
        $parsed = parse_url($base_url);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';
        $new_host = 'demo.' . $host;

        // Recreate URL
        $url = $scheme . '://' . $new_host . '/';

        // Attach path if exists
        $path = trim($path, '/');
        if (!empty($path)) {
            $url .= $path . '/';
        }

        return $url;
    }
}
// render img tag (lazy load).... title, alt, src, class, style
if (!function_exists('_img')) {
    function _img($src, $title = '', $lazy = true, $class = '', $style = '', $width = '', $height = '', $id = '')
    {
        // If no image source then return empty string
        if (empty($src)) {
            return '';
        }

        // Create attributes for alt and title, escape special characters
        $attr_alt   = !empty($title) ? ' alt="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '"' : '';
        $attr_title = !empty($title) ? ' title="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '"' : '';
        $attr_id    = !empty($id)  ? ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' : '';

        // Process class, style, width, height attributes (escape if needed)
        $attr_class = !empty($class) ? ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"' : '';
        $attr_style = !empty($style) ? ' style="' . htmlspecialchars($style, ENT_QUOTES, 'UTF-8') . '"' : '';
        $attr_width = !empty($width) ? ' width="' . htmlspecialchars($width, ENT_QUOTES, 'UTF-8') . '"' : '';
        $attr_height = !empty($height) ? ' height="' . htmlspecialchars($height, ENT_QUOTES, 'UTF-8') . '"' : '';

        if ($lazy) {
            // If lazy load is enabled, add "lazyload" class
            if (!empty($class)) {
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


/*************** SIMPLE STRUCT ***************/
// hàm get_header include file header.php trong theme / frontend
if (!function_exists('get_header')) {
    function get_header($data = [])
    {
        if (file_exists(APP_THEME_PATH)) {
            // truyền array data sang header.php
            extract($data);
            include_once APP_THEME_PATH . 'Frontend/header.php';
        } else {
            echo '<!-- Header file not found: ' . $headerFile . ' -->';
        }
    }
}

// hàm get_footer include file footer.php trong theme / frontend
if (!function_exists('get_footer')) {
    function get_footer()
    {
        if (file_exists(APP_THEME_PATH)) {
            include_once APP_THEME_PATH . 'Frontend/footer.php';
        } else {
            echo '<!-- Footer file not found: ' . $footerFile . ' -->';
        }
    }
}

// get_template
if (!function_exists('get_template')) {
    function get_template($templateName, $data = [])
    {
        // Kiểm tra xem file template có tồn tại không
        $templateFile = APP_THEME_PATH . 'Frontend/' . $templateName . '.php';
        if (file_exists($templateFile)) {
            // Tạo một scope riêng cho template
            (function ($data) use ($templateFile) {
                extract($data); // Chuyển mảng data thành các biến
                include $templateFile;
            })($data);
        } else {
            echo '<!-- Template file not found: ' . $templateName . ' -->';
        }
    }
}



// viết hàm quy đổi views thành đơn vị 1500 = 1.5K, 1500000 = 1.5M, 1500000000 = 1.5B
if (!function_exists('format_views')) {
    function format_views($views)
    {
        if ($views >= 1000000000) {
            return number_format($views / 1000000000, 1) . 'B';
        } elseif ($views >= 1000000) {
            return number_format($views / 1000000, 1) . 'M';
        } elseif ($views >= 1000) {
            return number_format($views / 1000, 1) . 'K';
        } else {
            return $views;
        }
    }
}


// time ago phút trước, giờ trước, ngày trước, > 10 ngày thì hiển thị ngày/tháng/năm
if (!function_exists('time_ago')) {
    function time_ago($time)
    {
        // kỉem tra xem kiểu time là kiểu nào heịen đang là 2025-05-19 23:13:33 hoặc timéslap
        if (is_string($time)) {
            $time = strtotime($time);
        }
        $time = time() - $time;
        // chuyển time về đơn vị phút hiện tại đang là giây
        $time = $time / 60;
        // làm tròn lên số phút không lẻ được
        $time = ceil($time);
        // chuyển về time text nếu dưới 60p thì show phút, trên 60p thì show giờ, trên 24h thì show ngày, trên 7 ngày thì show tuần, trên 30 ngày thì show tháng, trên 365 ngày thì show năm
        if ($time < 60) {
            return $time . ' phút trước';
        } elseif ($time < 3600) {
            return floor($time / 60) . ' giờ trước';
        } elseif ($time < 86400) {
            return floor($time / 3600) . ' giờ trước';
        } elseif ($time < 86400) {
            return floor($time / 86400) . ' ngày trước';
        } elseif ($time < 604800) {
            return floor($time / 604800) . ' tuần trước';
        } elseif ($time < 2592000) {
            return floor($time / 2592000) . ' tháng trước';
        } else {
            return date('d/m/Y', $time);
        }
    }
}


if (!function_exists('content_url')) {
    function content_url($type = 'blog', $slug = '', $lang = APP_LANG)
    {
        // Bảo vệ đầu vào
        $type = htmlspecialchars(strtolower($type), ENT_QUOTES, 'UTF-8');
        $slug = htmlspecialchars($slug, ENT_QUOTES, 'UTF-8');
        $lang = htmlspecialchars($lang, ENT_QUOTES, 'UTF-8');

        // Hợp lệ các loại
        $allowed = ['blog', 'blogs', 'theme', 'plugin', 'extention', 'project'];
        if (!in_array($type, $allowed)) {
            return '/'; // fallback nếu type không hợp lệ
        }

        // Xây dựng path
        if (empty($slug)) {
            $path = $type;
        } else {
            $path = $type . '/' . $slug;
        }

        // Sử dụng base_url để đảm bảo tính nhất quán
        return base_url($path, $lang);
    }
}

// create function download_url
if (!function_exists('download_url')) {
    function download_url($file = '', $lang = APP_LANG)
    {
        // Bảo vệ đầu vào
        $file = htmlspecialchars($file, ENT_QUOTES, 'UTF-8');
        $lang = htmlspecialchars($lang, ENT_QUOTES, 'UTF-8');

        // Xây dựng đường dẫn tải xuống
        $path = 'downloads/' . $file;

        // Sử dụng base_url để đảm bảo tính nhất quán
        return base_url($path, $lang);
    }
}