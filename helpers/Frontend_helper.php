<?php

use Google\Service\SQLAdmin\Flag;
use App\Libraries\Fastlang as Flang;

if (!defined('PATH_ROOT')) {
    exit('No direct access allowed.');
}

if (!function_exists('api_rating')) {
    function api_rating($posttype, $id, $lang = APP_LANG)
    {
        // Check if missing slug or posttype then return empty
        if (empty($id) || empty($posttype)) {
            return '';
        }

        // Normalize output URL
        $lang       = htmlspecialchars($lang, ENT_QUOTES, 'UTF-8');
        $posttype   = htmlspecialchars($posttype, ENT_QUOTES, 'UTF-8');
        $id         = htmlspecialchars($id, ENT_QUOTES, 'UTF-8');

        // Create URL with structure ./lang/posttype/123
        return base_url(sprintf('vi/api/v1/posts/action/rating/%s/%d', $posttype, $id));
    }
}

if (!function_exists('api_count_view')) {
    function api_count_view($posttype, $id, $lang = APP_LANG)
    {
        // Check if missing slug or posttype then return empty
        if (empty($id) || empty($posttype)) {
            return '';
        }

        // Normalize output URL
        $lang       = htmlspecialchars($lang, ENT_QUOTES, 'UTF-8');
        $posttype   = htmlspecialchars($posttype, ENT_QUOTES, 'UTF-8');
        $id         = htmlspecialchars($id, ENT_QUOTES, 'UTF-8');

        // Create URL with structure ./lang/posttype/123
        return base_url(sprintf('vi/api/v1/posts/action/views/%s/%d', $posttype, $id));
    }
}

if (!function_exists('api_like_post')) {
    function api_like_post($posttype, $id, $lang = APP_LANG)
    {
        // Check if missing slug or posttype then return empty
        if (empty($id) || empty($posttype)) {
            return '';
        }

        // Normalize output URL
        $lang       = htmlspecialchars($lang, ENT_QUOTES, 'UTF-8');
        $posttype   = htmlspecialchars($posttype, ENT_QUOTES, 'UTF-8');
        $id         = htmlspecialchars($id, ENT_QUOTES, 'UTF-8');

        // Create URL with structure ./lang/posttype/123
        return base_url(sprintf('vi/api/v1/posts/action/like/%s/%d', $posttype, $id));
    }
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




if (!function_exists('auth_url')) {
    function auth_url($path = '')
    {
        $path = trim($path, '/');
        switch ($path) {
            case 'login':
                return base_url('account/login');
                break;
            case 'register':
                return base_url('account/register');
                break;
            case 'forgot':
                return base_url('account/forgot');
                break;
            case 'reset':
                return base_url('account/forgot');
                break;
            case 'logout':
                return base_url('account/logout');
                break;
            case 'google':
                return base_url('account/login_google');
                break;
            case 'profile':
                return base_url('account/me');
                break;
            default:
                return base_url('account/' . $path);
                break;
        }
    }
}



function search_url($keyword = '', $type = '', $category = '', $status = '', $page = 1)
{
    if (empty($keyword)) {
        return base_url('search');
    }
    $url = base_url('search?s=' . urlencode($keyword));
    if (!empty($type)) {
        $url .= '&type=' . $type;
    }

    if (!empty($category)) {
        $url .= '&cat=' . $category;
    }

    if (!empty($status)) {
        $url .= '&status=' . $status;
    }

    if ($page > 1) {
        $url .= '/' . $page;
    }

    return $url;
}

if (!function_exists('type_url')) {
    function type_url($slug, $posttype = '', $lang = APP_LANG)
    {
        // Check if missing slug or posttype then return empty
        if (empty($slug) || empty($posttype)) {
            return '';
        }
        $type = 'type';

        // Normalize output URL
        $lang = htmlspecialchars($lang, ENT_QUOTES, 'UTF-8');
        $posttype = htmlspecialchars($posttype, ENT_QUOTES, 'UTF-8');
        $slug = htmlspecialchars($slug, ENT_QUOTES, 'UTF-8');

        // Create URL with structure ./lang/posttype/type/slug
        return sprintf('/%s/%s/%s/%s/', $lang, $posttype, $type, $slug);
    }
}



if (!function_exists('page_url')) {
    function page_url($slug = '', $posttype = '', $lang = APP_LANG)
    {
        // Check if missing slug or posttype then return empty
        if (empty($slug)  && empty($posttype)) {
            return base_url($lang);
        }

        // Normalize output URL
        $lang = htmlspecialchars($lang, ENT_QUOTES, 'UTF-8');
        $posttype = htmlspecialchars($posttype, ENT_QUOTES, 'UTF-8');
        $slug = htmlspecialchars($slug, ENT_QUOTES, 'UTF-8');
        // Create URL with structure ./lang/posttype/cat/slug
        // if any element is empty, cannot use this way because it will be duplicated
        // check which variable is empty then don't put it in URL
        if (empty($slug)) {
            return sprintf('/%s/%s/', $lang, $posttype);
        }
        if (empty($posttype)) {
            return sprintf('/%s/%s/', $lang, $slug);
        }
        return sprintf('/%s/%s/%s/', $lang, $posttype, $slug);
    }
}

if (!function_exists('single_url')) {
    function single_url($slug, $lang = APP_LANG)
    {

        return base_url($lang . '/' . $slug);
    }
}


// user url generation
if (!function_exists('user_url')) {
    function user_url($sub = '', $slug = '', $posttype = 'manage', $lang = APP_LANG)
    {
        // Normalize output URL
        $lang = htmlspecialchars($lang, ENT_QUOTES, 'UTF-8');
        $posttype = htmlspecialchars($posttype, ENT_QUOTES, 'UTF-8');
        $slug = htmlspecialchars($slug, ENT_QUOTES, 'UTF-8');

        // // Check if missing slug or posttype then return empty
        // if (empty($sub)) {
        //     return sprintf('/%s/%s/user/', $lang, $posttype);
        // }

        if (empty($slug)) {
            return sprintf('/%s/%s/%s', $lang, $posttype, $sub);
        }
        // Create URL with structure ./lang/posts/list-film/slug
        return sprintf('/%s/%s/%s/%s/', $lang, $posttype, $sub, $slug);
    }
}

// convert number to string number format
if (!function_exists("convert_to_string_number")) {
    function convert_to_string_number($number)
    {
        if ($number >= 10 ** 9) {
            return number_format($number / 10 ** 9, 2, '.') . 'B';
        } elseif ($number >= 10 ** 6) {
            return number_format($number / 10 ** 6, 2, '.') . 'M';
        } elseif ($number >= 10 ** 3) {
            return number_format($number / 10 ** 3, 2, '.') . 'K';
        } else {
            return $number;
        }
    }
}
// get url api upload files

if (!function_exists('api_upload_url')) {
    function api_upload_url($path = '')
    {
        $api = config('api')['files'] ?? '';
        return $api . $path;
    }
}

// debug function
if (!function_exists('prt')) {
    function prt($variable, $name = '')
    {
        echo '<pre style="background-color: #f4f4f4; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">';
        echo '<h1>' . $name . '</h1> ';
        var_dump($variable);
        echo '</pre>';
    }
}

if (!function_exists('get_filters')) {
    function get_filters()
    {
        $filter_string = S_GET('filters') ?? '';
        $filter = explode('__', $filter_string);
        if (empty($filter) || count($filter) < 2) return [];
        $filter_array = [];
        for ($i = 0; $i < count($filter); $i += 2) {
            $filter_array[$filter[$i]] = $filter[$i + 1];
        }
        return $filter_array;
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

if (!function_exists('format_title_seo')) {
    function format_title_seo($format, $data = array())
    {
        // Array of default variables taken from system or pre-set.
        $defaults = array(
            // Get website title from configuration (if option function exists)
            'site_title' => function_exists('option') ? option('site_title') : '',
            // Get website description from configuration
            'site_desc'  => function_exists('option') ? option('site_desc') : '',
            // Current date
            'year'       => date('Y'),
            'month'      => date('m'),
            'day'        => date('d'),
            // Other SEO variables (you can change or add as needed)
            'separator'  => '|',    // Separator character between title parts
            'title'      => '',     // Article/page title (will be passed via $data if available)
            'excerpt'    => '',     // Short description or excerpt of the article
            'category'   => '',     // Article category
            'tag'        => '',     // Tags related to the article
            'author'     => '',     // Author name
            'page'       => '',     // Page number if using pagination
            // You can add other variables as needed, e.g.: custom_field, current_date, etc.
        );

        // Merge data from $data (prioritize passed values) with default values.
        $data = array_merge($defaults, $data);

        // Replace placeholders in format %key% with corresponding values in $data. If not found, return empty value.
        return preg_replace_callback('/%([^%]+)%/', function ($matches) use ($data) {
            $key = trim($matches[1]);
            return isset($data[$key]) ? $data[$key] : '';
        }, $format);
    }
}

// _meta_data 
if (!function_exists('_meta_data')) {
    /* 
    * @param $functionname array
    * @param $data string
    * @param $lang string
    */
    function _meta_data($functionname = '', $data = [], $lang = APP_LANG)
    {
        // Get SEO configuration as JSON and convert to array
        $seoConfig = is_string(option('seo_config', $lang)) ? json_decode(option('seo_config', $lang), true) : option('seo_config', $lang);
        // Find configuration matching the passed function name
        $matchedConfig = [];
        if (is_array($seoConfig)) {
            foreach ($seoConfig as $config) {
                if (isset($config['function']) && $config['function'] === $functionname) {
                    $matchedConfig = $config;
                    break;
                }
            }
        }

        if (empty($matchedConfig)) {
            return [];
        }

        $data['banner'] = isset($data['banner']->path) ? base_url() . $data['banner']->path : '';
        $seoData = [
            'title'   => format_title_seo($matchedConfig['seo_title'], $data),
            'desc'    => format_title_seo($matchedConfig['seo_desc'], $data),
            'index'   => $matchedConfig['seo_index'],
            'follow'  => $matchedConfig['follow'],
        ];

        // $index check $matchedConfig['seo_index'] vs option('site_index')
        if (option('site_index') == 'false') {
            $seoData['index'] = false;
            $seoData['follow'] = false;
        }
        if ($seoData['index']) {
            $seoData['index'] = 'index';
        } else {
            $seoData['index'] = 'noindex';
        }

        if ($seoData['follow']) {
            $seoData['follow'] = 'follow';
        } else {
            $seoData['follow'] = 'nofollow';
        }

        // check if canonical exists in data, if not then get from url, if no url then leave empty
        if (isset($data['canonical'])) {
            $seoData['canonical'] = $data['canonical'];
        } else {
            if (isset($data['url'])) {
                $seoData['canonical'] = $data['url'];
            } else {
                $seoData['canonical'] = '';
            }
        }

        // Additional Meta Tags for SEO & Social Sharing
        // Open Graph Meta Tags
        // check if no og_title then get seoData['title']
        if (isset($data['og_title'])) {
            $seoData['og_title'] = $data['og_title'];
        } else {
            $seoData['og_title'] = $seoData['title'];
        }

        // check if no og_desc then get seoData['desc']
        if (isset($data['og_desc'])) {
            $seoData['og_desc'] = $data['og_desc'];
        } else {
            $seoData['og_desc'] = $seoData['desc'];
        }

        // check if no og_url then get url
        if (isset($data['og_url'])) {
            $seoData['og_url'] = $data['og_url'];
        } else {
            if (isset($data['url'])) {
                $seoData['og_url'] = $data['url'];
            } else {
                $seoData['og_url'] = '';
            }
        }

        // check if no og_image then get banner
        if (isset($data['og_image'])) {
            $seoData['og_image'] = $data['og_image'];
        } else {
            if (isset($data['banner'])) {
                $seoData['og_image'] = $data['banner'];
            } else {
                $seoData['og_image'] = '';
            }
        }

        // check if no og_type then get website
        if (isset($data['og_type'])) {
            $seoData['og_type'] = $data['og_type'];
        } else {
            $seoData['og_type'] = 'website';
        }

        // Twitter Card Meta Tags

        // check if no twitter_card then get summary_large_image
        if (isset($data['twitter_card'])) {
            $seoData['twitter_card'] = $data['twitter_card'];
        } else {
            $seoData['twitter_card'] = 'summary_large_image';
        }


        // check if no twitter_title then get seoData['title']

        if (isset($data['twitter_title'])) {
            $seoData['twitter_title'] = $data['twitter_title'];
        } else {
            $seoData['twitter_title'] = $seoData['title'];
        }

        // check if no twitter_desc then get seoData['desc']

        if (isset($data['twitter_desc'])) {
            $seoData['twitter_desc'] = $data['twitter_desc'];
        } else {
            $seoData['twitter_desc'] = $seoData['desc'];
        }

        // check if no twitter_image then get banner

        if (isset($data['twitter_image'])) {
            $seoData['twitter_image'] = $data['twitter_image'];
        } else {
            if (isset($data['banner'])) {
                $seoData['twitter_image'] = $data['banner'];
            } else {
                $seoData['twitter_image'] = '';
            }
        }

        // author tag seo
        if (isset($data['author'])) {
            $seoData['author'] = $data['author'];
        } else {
            $seoData['author'] = '';
        }

        if (isset($data['hreflang'])) {
            $seoData['hreflang'] = $data['hreflang'];
        } else {
            $seoData['hreflang'] = '';
        }

        // theme color
        $seoData['theme_color'] = '#000';

        return $seoData;
    }


    if (!function_exists('me_url')) {
        function me_url($path = '')
        {
            global $me_url;
            if (empty($me_url)) {
                $app_url = config('app');
                $me_url = !empty($app_url['app_url']) ? $app_url['app_url'] : '/';
                unset($app_url);
            }
            return rtrim($me_url, '/') . '/' . APP_LANG . '/' . 'me' . '/' . trim($path, '/') . '/';
        }
    }
}




if (!function_exists('status_format')) {
    function status_format($status)
    {
        $statusMap = [
            0 => 'Ongoing Release',
            1 => 'Completed',
            2 => 'Paused',
            'coming_soon' => 'Coming Soon',
            'ongoing' => 'Ongoing Release',
            'completed' => 'Completed',
        ];

        return $statusMap[$status] ?? 'Coming Soon';
    }
}

// rating format divide by 10 get 1 decimal place
if (!function_exists('rating_format')) {
    function rating_format($rating)
    {
        if (empty($rating)) return 0;
        $rating = $rating / 10;
        return number_format($rating, 1);
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
if (!function_exists('formatViews')) {
    function formatViews($views)
    {
        $views = (int) $views;

        if ($views < 1000) {
            return (string) $views;
        } elseif ($views < 1000000) {
            return round($views / 1000, 1) . 'K';
        } elseif ($views < 1000000000) {
            return round($views / 1000000, 1) . 'M';
        } else {
            return round($views / 1000000000, 1) . 'B';
        }
    }
}
