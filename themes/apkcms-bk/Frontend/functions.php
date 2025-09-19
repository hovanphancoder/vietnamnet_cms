<?php
use System\Core\AppException;

// Prevent direct access
if (!defined('APP_THEME_PATH')) {
    exit('Direct access not allowed');
}

/**
 * Get categories with static cache
 * Lấy danh sách categories với cache tĩnh để tránh query SQL nhiều lần
 * 
 * @param string $posttype Loại posttype (posts, news, etc.)
 * @param string $type Loại term (category, tag, etc.)
 * @param string $lang Mã ngôn ngữ
 * @param int $parent_id ID của parent category (0 = root categories)
 * @param bool $active Chỉ lấy categories active
 * @return array Danh sách categories
 */
if (!function_exists('get_categories')) {
    function get_categories($posttype = 'posts', $type = 'category', $lang = APP_LANG, $active = true)
    {
        static $get_categories;
        if (!empty($get_categories)) {
            return $get_categories;
        }
        try {
            $qb = (new \App\Models\FastModel('fast_terms'))
                ->newQuery()
                ->where('posttype', '=', $posttype)
                ->where('type', '=', $type)
                ->where('lang', '=', $lang)
            ;
            if ($active) {
                //$qb->where('active', '=', 1);
            }
            
            $get_categories = $qb->orderBy('name', 'ASC')->get();
            return $get_categories;
        } catch (Exception $e) {
            throw new AppException('Error in get_categories: ' . $e->getMessage());
            return [];
        }
    }
}

/**
 * Get all categories (games + apps) with static cache
 * Lấy tất cả categories với cache tĩnh
 * 
 * @param string $lang Mã ngôn ngữ
 * @return array Array chứa games_categories và apps_categories
 */
if (!function_exists('get_all_categories')) {
    function get_all_categories($lang = APP_LANG)
    {
        static $all_categories_cache = null;
        
        if ($all_categories_cache !== null) {
            return $all_categories_cache;
        }
        
        try {
            // Lấy tất cả categories một lần
            $all_categories = (new \App\Models\FastModel('fast_terms'))
                ->where('posttype', 'posts')
                ->where('type', 'category')
                ->where('lang', $lang)
                ->where('active', 1)
                ->orderBy('name', 'ASC')
                ->get();
            
            // Filter theo parent ID từ options
            $games_categories = array_filter($all_categories, function($category) {
                return $category['parent'] == option('themes_gamesid', 111);
            });
            
            $apps_categories = array_filter($all_categories, function($category) {
                return $category['parent'] == option('themes_appsid', 112);
            });
            
            $all_categories_cache = [
                'games_categories' => array_values($games_categories),
                'apps_categories' => array_values($apps_categories),
                'all_categories' => $all_categories
            ];
            
            return $all_categories_cache;
            
        } catch (Exception $e) {
            error_log('Error in get_all_categories: ' . $e->getMessage());
            $all_categories_cache = [
                'games_categories' => [],
                'apps_categories' => [],
                'all_categories' => []
            ];
            return $all_categories_cache;
        }
    }
}

/**
 * Clear categories cache
 * Xóa cache categories (hữu ích khi có thay đổi dữ liệu)
 */
if (!function_exists('clear_categories_cache')) {
    function clear_categories_cache()
    {
        // Reset static cache bằng cách gọi lại function với tham số khác
        // Hoặc có thể implement cách khác tùy theo nhu cầu
        static $categories_cache = [];
        $categories_cache = [];
    }
}

/**
 * Custom function example
 * You can add your own functions here
 */

/**
 * Generate single post link with trailing slash
 * Tạo link cho single post với "/" ở cuối
 * 
 * @param string $slug Post slug
 * @param string $posttype Loại posttype (posts, news, etc.)
 * @param string $lang Mã ngôn ngữ
 * @return string Post URL với "/" ở cuối
 */
if (!function_exists('link_single')) {
    function link_single($slug, $posttype = 'posts', $lang = APP_LANG)
    {
        if (empty($slug)) {
            return base_url();
        }
        
        // Normalize slug
        $slug = htmlspecialchars($slug, ENT_QUOTES, 'UTF-8');
        $posttype = htmlspecialchars($posttype, ENT_QUOTES, 'UTF-8');
        $lang = htmlspecialchars($lang, ENT_QUOTES, 'UTF-8');
        
        // Check if lang is default language, don't include it in URL
        $is_default_lang = ($lang === APP_LANG_DF);
        
        // Tạo URL với "/" ở cuối
        if ($is_default_lang) {
            return base_url($posttype . '/' . $slug . '/');
        }
        
        return base_url($lang . '/' . $posttype . '/' . $slug . '/');
    }
}
// Hàm lấy slug cuối cùng từ get_current_page()
if (!function_exists('get_current_slug')) {
    function get_current_slug()
    {
        $current_page = get_current_page();
        
        if (!$current_page || empty($current_page['segments'])) {
            return '';
        }
        
        $segments = $current_page['segments'];
        
        // Lấy phần cuối cùng của segments
        $last_segment = end($segments);
        
        // Kiểm tra xem có phải là slug không (không phải ngôn ngữ hoặc posttype)
        $excluded_parts = ['vi', 'en', 'post', 'posts', 'game', 'games', 'app', 'apps', 'blog', 'blogs', 'page', 'pages'];
        
        if (!in_array($last_segment, $excluded_parts)) {
            return $last_segment;
        }
        
        // Nếu phần cuối là posttype, lấy phần trước đó
        if (count($segments) >= 2) {
            $second_last = $segments[count($segments) - 2];
            if (!in_array($second_last, $excluded_parts)) {
                return $second_last;
            }
        }
        
        return '';
    }
}

// Hàm tạo URL sạch không hiển thị ngôn ngữ mặc định
if (!function_exists('clean_page_url')) {
    function clean_page_url($slug = '', $posttype = '', $lang = APP_LANG)
    {
        // Check if missing slug or posttype then return empty
        if (empty($slug) && empty($posttype)) {
            return base_url($lang);
        }

        // Normalize output URL
        $lang = htmlspecialchars($lang, ENT_QUOTES, 'UTF-8');
        $posttype = htmlspecialchars($posttype, ENT_QUOTES, 'UTF-8');
        $slug = htmlspecialchars($slug, ENT_QUOTES, 'UTF-8');
        
        // Check if lang is default language, don't include it in URL
        $is_default_lang = ($lang === APP_LANG_DF);
        
        // Create URL with structure ./lang/posttype/cat/slug (only if not default lang)
        if (empty($slug)) {
            if ($is_default_lang) {
                return sprintf('/%s/', $posttype);
            }
            return sprintf('/%s/%s/', $lang, $posttype);
        }
        if (empty($posttype)) {
            if ($is_default_lang) {
                return sprintf('/%s/', $slug);
            }
            return sprintf('/%s/%s/', $lang, $slug);
        }
        
        if ($is_default_lang) {
            return sprintf('/%s/%s/', $posttype, $slug);
        }
        return sprintf('/%s/%s/%s/', $lang, $posttype, $slug);
    }
}

if (!function_exists('my_custom_function')) {
    function my_custom_function($param = '') {
        return 'Custom function: ' . $param;
    }
}

/**
 * Custom page title function
 * Override default page title behavior
 */
if (!function_exists('custom_page_title')) {
    function custom_page_title() {
        $current_page = get_current_page();
        
        // Custom logic for different pages
        switch ($current_page['page_type']) {
            case 'home':
                return 'Welcome to ' . option('site_title', APP_LANG);
            case 'blog':
                return 'Our Blog - ' . option('site_title', APP_LANG);
            case 'apps':
                return 'Download Apps - ' . option('site_title', APP_LANG);
            case 'games':
                return 'Download Games - ' . option('site_title', APP_LANG);
            default:
                return get_current_page_title();
        }
    }
}

/**
 * Custom navigation menu
 * Create custom menu structure
 */
if (!function_exists('custom_main_menu')) {
    function custom_main_menu() {
        $menu_items = [
            [
                'title' => __('Home', APP_LANG),
                'url' => base_url(),
                'active' => is_page('home')
            ],
            [
                'title' => __('Apps', APP_LANG),
                'url' => base_url('apps'),
                'active' => is_page('apps')
            ],
            [
                'title' => __('Games', APP_LANG),
                'url' => base_url('games'),
                'active' => is_page('games')
            ],
            [
                'title' => __('Blog', APP_LANG),
                'url' => base_url('blog'),
                'active' => is_page('blog')
            ]
        ];
        
        return $menu_items;
    }
}

/**
 * Custom footer content
 * Add custom content to footer
 */
if (!function_exists('custom_footer_content')) {
    function custom_footer_content() {
        $year = date('Y');
        $site_title = option('site_title', APP_LANG);
        
        return "&copy; {$year} {$site_title}. All rights reserved.";
    }
}

/**
 * Custom CSS/JS enqueue
 * Add custom assets to specific pages
 */
// if (!function_exists('custom_enqueue_assets')) {
//     function custom_enqueue_assets() {
//         $current_page = get_current_page();
        
//         // Add custom CSS for specific pages
//         if (is_page('blog')) {
//             \System\Libraries\Render::asset('css', theme_assets('Assets/css/blog-custom.css'), [
//                 'area' => 'frontend',
//                 'location' => 'head'
//             ]);
//         }
        
//         // Add custom JS for specific pages
//         if (is_page('apps') || is_page('games')) {
//             \System\Libraries\Render::asset('js', theme_assets('Assets/js/download-counter.js'), [
//                 'area' => 'frontend',
//                 'location' => 'footer'
//             ]);
//         }
//     }
// }

/**
 * Custom post query
 * Modify post queries for specific pages
 */
if (!function_exists('custom_get_posts')) {
    function custom_get_posts($post_type = 'post', $limit = 10) {
        // Add custom logic here
        return get_posts($post_type, $limit);
    }
}

/**
 * Custom breadcrumb
 * Generate custom breadcrumb navigation
 */
if (!function_exists('custom_breadcrumb')) {
    function custom_breadcrumb() {
        $current_page = get_current_page();
        $breadcrumb = [];
        
        // Always start with home
        $breadcrumb[] = [
            'title' => __('Home', APP_LANG),
            'url' => base_url(),
            'active' => false
        ];
        
        // Add current page
        if (!is_page('home')) {
            $breadcrumb[] = [
                'title' => get_page_heading(),
                'url' => '',
                'active' => true
            ];
        }
        
        return $breadcrumb;
    }
}

/**
 * Custom social media links
 * Get social media links from options
 */
if (!function_exists('custom_social_links')) {
    function custom_social_links() {
        $social_links = option('social', APP_LANG);
        
        if (empty($social_links)) {
            return [];
        }
        
        $links = [];
        foreach ($social_links as $platform => $url) {
            if (!empty($url)) {
                $links[] = [
                    'platform' => $platform,
                    'url' => $url,
                    'icon' => 'icon-' . strtolower($platform)
                ];
            }
        }
        
        return $links;
    }
}

/**
 * Custom pagination
 * Generate custom pagination HTML
 */
if (!function_exists('custom_pagination')) {
    function custom_pagination($current_page = 1, $total_pages = 1, $base_url = '') {
        if ($total_pages <= 1) {
            return '';
        }
        
        $pagination = '<div class="pagination">';
        
        // Previous button
        if ($current_page > 1) {
            $prev_url = $base_url . '?page=' . ($current_page - 1);
            $pagination .= '<a href="' . $prev_url . '" class="pagination-link prev">← Previous</a>';
        }
        
        // Page numbers
        for ($i = 1; $i <= $total_pages; $i++) {
            $active_class = ($i == $current_page) ? ' active' : '';
            $page_url = $base_url . '?page=' . $i;
            $pagination .= '<a href="' . $page_url . '" class="pagination-link' . $active_class . '">' . $i . '</a>';
        }
        
        // Next button
        if ($current_page < $total_pages) {
            $next_url = $base_url . '?page=' . ($current_page + 1);
            $pagination .= '<a href="' . $next_url . '" class="pagination-link next">Next →</a>';
        }
        
        $pagination .= '</div>';
        
        return $pagination;
    }
}

/**
 * Initialize theme functions
 * Call this function to initialize all custom functions
 */
if (!function_exists('init_theme_functions')) {
    function init_theme_functions() {
        // Enqueue custom assets
        // custom_enqueue_assets();
        
        // Add any other initialization code here
    }
}


// Auto-initialize theme functions
init_theme_functions();





/**
 * Get page title for specific page type
 * @param string $type
 * @param string $custom_title
 * @return string
 */
if (!function_exists('get_page_title')) {
    function get_page_title($type = '', $custom_title = '')
    {
        if (!empty($custom_title)) {
            return $custom_title . ' - ' . option('site_title', APP_LANG);
        }
        
        $current_page = get_current_page();
        $page_type = !empty($type) ? $type : $current_page['page_type'];
        
        switch ($page_type) {
            case 'home':
                return option('site_title', APP_LANG) ?: 'Home';
            case 'blog':
                return __('Blog', APP_LANG) . ' - ' . option('site_title', APP_LANG);
            case 'apps':
                return __('Apps', APP_LANG) . ' - ' . option('site_title', APP_LANG);
            case 'games':
                return __('Games', APP_LANG) . ' - ' . option('site_title', APP_LANG);
            case 'single':
                return ucwords(str_replace('-', ' ', $current_page['page_slug'])) . ' - ' . option('site_title', APP_LANG);
            default:
                return option('site_title', APP_LANG) ?: 'Page';
        }
    }
}

/**
 * Get page heading (H1) for current page
 * @return string
 */
if (!function_exists('get_page_heading')) {
    function get_page_heading()
    {
        $current_page = get_current_page();
        
        switch ($current_page['page_type']) {
            case 'home':
                return option('site_title', APP_LANG) ?: 'Welcome';
            case 'blog':
                return __('Blog', APP_LANG);
            case 'apps':
                return __('Apps', APP_LANG);
            case 'games':
                return __('Games', APP_LANG);
            case 'single':
                return ucwords(str_replace('-', ' ', $current_page['page_slug']));
            default:
                return 'Page';
        }
    }
}


if (!function_exists('get_page_description')) {
    function get_page_description()
    {
        $current_page = get_current_page();
        
        switch ($current_page['page_type']) {
            case 'home':
                return option('site_desc', APP_LANG) ?: 'Welcome to our website';
            case 'blog':
                return __('Discover the latest news, tips, and insights about mobile apps and games.', APP_LANG);
            case 'apps':
                return __('Download the latest mod APK apps for Android. Get premium features for free.', APP_LANG);
            case 'games':
                return __('Download the latest mod APK games for Android. Get unlimited features for free.', APP_LANG);
            case 'single':
                return 'Read more about ' . str_replace('-', ' ', $current_page['page_slug']);
            default:
                return option('site_desc', APP_LANG) ?: 'Page description';
        }
    }
}

if (!function_exists('get_current_posttype')) {
    function get_current_posttype()
    {
        $current_page = get_current_page();
        if (!$current_page) {
            return 'posts';
        }
        
        // Check page type first
        switch ($current_page['page_type']) {
            case 'blog':
                return 'news';
            case 'apps':
                return 'posts'; // Assuming apps are stored in posts table
            case 'games':
                return 'posts'; // Assuming games are stored in posts table
            case 'single':
                // For single pages, try to determine posttype from URL
                $segments = $current_page['segments'];
                if (!empty($segments)) {
                    $first_segment = $segments[0];
                    switch ($first_segment) {
                        case 'blog':
                            return 'news';
                        case 'apps':
                        case 'app':
                            return 'posts';
                        case 'games':
                        case 'game':
                            return 'posts';
                        default:
                            return 'posts';
                    }
                }
                return 'posts';
            default:
                return 'posts';
        }
    }
}

if (!function_exists('is_posttype')) {
    function is_posttype($posttype)
    {
        return get_current_posttype() === $posttype;
    }
}

if (!function_exists('get_user_by_id')) {
    /**
     * Lấy thông tin user theo ID
     * 
     * @param int $user_id ID của user
     * @param string $lang Ngôn ngữ (mặc định: APP_LANG)
     * @return array|null Thông tin user hoặc null nếu không tìm thấy
     */
    function get_user_by_id($user_id, $lang = null)
    {
        if (empty($user_id) || !is_numeric($user_id)) {
            return null;
        }
        
        $lang = $lang ?? APP_LANG;
        
        try {
            $user_data = \App\Models\FastModel::table('fast_users')
                ->where('id', $user_id)
                ->where('active', 1)
                ->first();
            
            if ($user_data && is_array($user_data)) {
                return $user_data;
            }
            
            return null;
        } catch (Exception $e) {
            error_log('Error getting user by ID: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('get_user_name')) {
    /**
     * Lấy tên user theo ID
     * 
     * @param mixed $user_id ID của user (có thể là int, string, hoặc null)
     * @param string $lang Ngôn ngữ (mặc định: APP_LANG)
     * @return string Tên user hoặc 'Admin' nếu không tìm thấy
     */
    function get_user_name($user_id, $lang = null)
    {
        // Kiểm tra input cơ bản
        if (empty($user_id)) {
            return 'Admin';
        }
        
        // Chuyển đổi thành số nếu có thể
        $user_id = (int) $user_id;
        if ($user_id <= 0) {
            return 'Admin';
        }
        
        try {
            $user_data = get_user_by_id($user_id, $lang);
            
            if ($user_data && is_array($user_data)) {
                return $user_data['name'] ?? $user_data['username'] ?? $user_data['display_name'] ?? $user_data['title'] ?? 'Admin';
            }
        } catch (Exception $e) {
            error_log('Error in get_user_name: ' . $e->getMessage());
        }
        
        return 'Admin';
    }
}

if (!function_exists('get_user_avatar')) {
    /**
     * Lấy avatar user theo ID
     * 
     * @param mixed $user_id ID của user (có thể là int, string, hoặc null)
     * @param string $lang Ngôn ngữ (mặc định: APP_LANG)
     * @return string Đường dẫn avatar hoặc avatar mặc định
     */
    function get_user_avatar($user_id, $lang = null)
    {
        // Kiểm tra input cơ bản
        if (empty($user_id)) {
            return '/themes/apkcms/Frontend/images/default-user.png';
        }
        
        // Chuyển đổi thành số nếu có thể
        $user_id = (int) $user_id;
        if ($user_id <= 0) {
            return '/themes/apkcms/Frontend/images/default-user.png';
        }
        
        try {
            $user_data = get_user_by_id($user_id, $lang);
            
            if ($user_data && is_array($user_data)) {
                $avatar = $user_data['avatar'] ?? $user_data['profile_image'] ?? $user_data['image'] ?? null;
                if ($avatar) {
                    return $avatar;
                }
            }
        } catch (Exception $e) {
            error_log('Error in get_user_avatar: ' . $e->getMessage());
        }
        
        return '/themes/apkcms/Frontend/images/default-user.png';
    }
}

if (!function_exists('get_term_by_slug')) {
    /**
     * Lấy 1 term theo slug từ bảng fast_terms
     * 
     * @param string $slug Slug của term
     * @param string $posttype Loại posttype
     * @param string $type Loại term (categories, tags, etc.)
     * @param string $lang Mã ngôn ngữ
     * @param bool $active Chỉ lấy term active
     * @return array|null Term data hoặc null
     */
    function get_term_by_slug($slug, $posttype = '', $type = 'category', $lang = APP_LANG, $active = true)
    {
        if (empty($slug)) {
            return null;
        }
        
        try {
            $qb = (new \App\Models\FastModel('fast_terms'))
                ->newQuery()
                ->where('slug', '=', $slug);
                
          
            if ($lang) {
                $qb->where('lang', '=', $lang);
            }
            
            if ($posttype) {
                $qb->where('posttype', '=', $posttype);
            }
            
            if ($type) {
                $qb->where('type', '=', $type);
            }
            
            return $qb->first();
            
        } catch (Exception $e) {
            error_log('Error in get_term_by_slug: ' . $e->getMessage());
            return null;
        }
    }
}





/**
 * Get current page information
 * @return array
 */
if (!function_exists('get_current_page')) {
    function get_current_page()
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url($uri, PHP_URL_PATH);
        $segments = explode('/', trim($path, '/'));
        
        // Remove empty segments
        $segments = array_filter($segments);
        
        $page = [
            'uri' => $uri,
            'path' => $path,
            'segments' => array_values($segments),
            'is_home' => empty($segments) || (count($segments) === 1 && $segments[0] === ''),
            'is_blog' => false,
            'is_apps' => false,
            'is_games' => false,
            'is_single' => false,
            'page_type' => 'home',
            'page_slug' => '',
            'page_id' => null
        ];
        
        // Check specific pages
        if (!empty($segments)) {
            $first_segment = $segments[0];
            
            switch ($first_segment) {
                case 'blog':
                    $page['is_blog'] = true;
                    $page['page_type'] = 'blog';
                    $page['page_slug'] = 'blog';
                    break;
                    
                case 'apps':
                    $page['is_apps'] = true;
                    $page['page_type'] = 'apps';
                    $page['page_slug'] = 'apps';
                    break;
                    
                case 'games':
                    $page['is_games'] = true;
                    $page['page_type'] = 'games';
                    $page['page_slug'] = 'games';
                    break;
                    
                default:
                    // Check if it's a single post/page
                    if (count($segments) >= 1) {
                        $page['is_single'] = true;
                        $page['page_type'] = 'single';
                        $page['page_slug'] = $first_segment;
                    }
                    break;
            }
        }
        
        return $page;
    }
}



/**
 * Check if current page is specific page type
 * @param string $type
 * @return bool
 */
if (!function_exists('is_page')) {
    function is_page($type = '')
    {
        $current_page = get_current_page();
        
        if (empty($type)) {
            return $current_page;
        }
        
        switch ($type) {
            case 'home':
                return $current_page['is_home'];
            case 'blog':
                return $current_page['is_blog'];
            case 'apps':
                return $current_page['is_apps'];
            case 'games':
                return $current_page['is_games'];
            case 'single':
                return $current_page['is_single'];
            default:
                return $current_page['page_type'] === $type;
        }
    }
}
