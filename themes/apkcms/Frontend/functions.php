<?php
use System\Core\AppException;

// Prevent direct access
if (!defined('APP_THEME_PATH')) {
    exit('Direct access not allowed');
}
/**
 * Get categories of a post using FastModel (theme-level helper)
 * Bắt categories của bài viết dựa trên bảng fast_post_terms (đảm bảo đúng type/lang/status)
 */
if (!function_exists('get_post_categories')) {
    function get_post_categories($post_id, $posttype, $lang = APP_LANG)
    {
        if (empty($post_id) || empty($posttype)) {
            return [];
        }
        try {
            // Join fast_post_terms (pivot) với fast_terms để lấy slug/name
            $pivot = 'fast_post_terms';
            $termsTable = 'fast_terms';
            $qb = (new \App\Models\FastModel($pivot))
                ->newQuery()
                ->select([
                    "$termsTable.id",
                    "$termsTable.id_main",
                    "$termsTable.slug",
                    "$termsTable.name",
                    "$termsTable.type",
                    "$termsTable.lang",
                ])
                ->join($termsTable, "$termsTable.id_main", '=', "$pivot.rel_id")
                ->where("$pivot.post_id", '=', $post_id)
                ->where("$pivot.posttype", '=', $posttype)
                ->where("$pivot.type", '=', 'category')
                ->where("$pivot.lang", '=', $lang)
                ->where("$pivot.status", '=', 'active')
                ->where("$termsTable.posttype", '=', $posttype)
                ->where("$termsTable.type", '=', 'category')
                ->where("$termsTable.lang", '=', $lang)
                ->orderBy("$termsTable.name", 'ASC');

            $terms = $qb->get();
            return is_array($terms) ? $terms : [];
        } catch (Exception $e) {
            error_log('Error in get_post_categories: ' . $e->getMessage());
            return [];
        }
    }
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
 * Initialize global categories for entire theme scope
 * Thiết lập biến global để dùng ở mọi template trong theme
 * Lưu ý: Không chạm vào core, chạy ngay khi file functions.php của theme được nạp
 */
if (!isset($GLOBALS['all_categories'])) {
    $GLOBALS['all_categories'] = get_all_categories(APP_LANG);
}
if (!isset($GLOBALS['categories'])) {
    // Lấy tất cả category (posttype=posts, type=category) theo ngôn ngữ hiện tại
    $GLOBALS['categories'] = get_categories('posts', 'category', APP_LANG, true);
}

// Helper: truy cập nhanh biến global categories
if (!function_exists('globals_categories')) {
    function globals_categories() {
        return $GLOBALS['categories'] ?? [];
    }
}
if (!function_exists('globals_all_categories')) {
    function globals_all_categories() {
        // Trả về cấu trúc mặc định nếu chưa có dữ liệu
        return $GLOBALS['all_categories'] ?? [

            'all_categories' => []
        ];
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


if (!function_exists('get_post_terms')) {
    /**
     * Lấy danh sách terms của một post
     * 
     * @param int $post_id ID của post
     * @param string $posttype Loại posttype
     * @param string $type Loại term (category, tag, etc.)
     * @param string $lang Mã ngôn ngữ
     * @return array Danh sách terms
     */
    function get_post_terms($post_id, $posttype, $type = 'category', $lang = APP_LANG)
    {
        if (empty($post_id) || empty($posttype)) {
            return [];
        }
        
        try {
            $qb = (new \App\Models\FastModel('fast_post_terms'))
                ->newQuery()
                ->where('post_id', '=', $post_id)
                ->where('posttype', '=', $posttype)
                ->where('type', '=', $type)
                ->where('lang', '=', $lang)
                ->where('status', '=', 'active');
            
            $post_terms = $qb->get();
            
            if ($post_terms && is_array($post_terms)) {
                return $post_terms;
            }
            
            return [];
            
        } catch (Exception $e) {
            error_log('Error in get_post_terms: ' . $e->getMessage());
            return [];
        }
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
 * Generate category link with proper URL format
 * Tạo link category với format URL đúng
 * 
 * @param string $slug Slug của category
 * @param string $posttype Loại posttype (posts, news, etc.)
 * @return string URL đầy đủ của category
 */
if (!function_exists('link_cat')) {
    function link_cat($slug, $posttype = 'posts')
    {
        // Đảm bảo slug không rỗng
        if (empty($slug)) {
            return '#';
        }
        
        // Loại bỏ ký tự đặc biệt và chuẩn hóa slug
        $slug = trim($slug, '/');
        $slug = preg_replace('/[^a-zA-Z0-9\-_]/', '', $slug);
        
        // Chuẩn hóa posttype
        $posttype = trim($posttype, '/');
        $posttype = preg_replace('/[^a-zA-Z0-9\-_]/', '', $posttype);
        
        // Tạo URL với format: /$posttype/category/$slug
        $url = "/{$posttype}/category/{$slug}/";
        
        return $url;
    }
}

if (!function_exists('link_tag')) {
    function link_tag($slug, $posttype = 'posts')
    {
        // Đảm bảo slug không rỗng
        if (empty($slug)) {
            return '#';
        }
        
        // Loại bỏ ký tự đặc biệt và chuẩn hóa slug
        $slug = trim($slug, '/');
        $slug = preg_replace('/[^a-zA-Z0-9\-_]/', '', $slug);
        
        // Chuẩn hóa posttype
        $posttype = trim($posttype, '/');
        $posttype = preg_replace('/[^a-zA-Z0-9\-_]/', '', $posttype);
        
        // Tạo URL với format: /$posttype/category/$slug
        $url = "/{$posttype}/tag/{$slug}/";
        
        return $url;
    }
}
/**
 * Generate post link with proper URL format
 * Tạo link post với format URL đúng
 * 
 * @param string $slug Slug của post
 * @param string $posttype Loại posttype (posts, news, etc.)
 * @return string URL đầy đủ của post
 */
if (!function_exists('link_post')) {
    function link_post($slug, $posttype = 'posts')
    {
        // Đảm bảo slug không rỗng
        if (empty($slug)) {
            return '#';
        }
        
        // Loại bỏ ký tự đặc biệt và chuẩn hóa slug
        $slug = trim($slug, '/');
        $slug = preg_replace('/[^a-zA-Z0-9\-_]/', '', $slug);
        
        // Tạo URL với format chuẩn
        $url = "/{$posttype}/{$slug}/";
        
        return $url;
    }
}

/**
 * Generate rewrite link with proper URL format
 * Tạo link rewrite với format URL đúng
 * 
 * @param string $slug Slug của rewrite
 * @param string $type Loại rewrite (page, custom, etc.)
 * @return string URL đầy đủ của rewrite
 */
if (!function_exists('link_rewrite')) {
    function link_rewrite($slug, $type = 'page')
    {
        // Đảm bảo slug không rỗng
        if (empty($slug)) {
            return '#';
        }
        
        // Loại bỏ ký tự đặc biệt và chuẩn hóa slug
        $slug = trim($slug, '/');
        $slug = preg_replace('/[^a-zA-Z0-9\-_]/', '', $slug);
        
        // Tạo URL với format chuẩn
        $url = "/{$slug}/";
        
        return $url;
    }
}


/**
 * Lấy danh sách terms (categories, tags) của một post
 * 
 * @param int $post_id ID của post
 * @param string $posttype Loại posttype
 * @param string $type Loại term (category, tag, etc.)
 * @param string $lang Mã ngôn ngữ
 * @return array Danh sách terms
 */
if (!function_exists('get_post_terms')) {
    function get_post_terms($post_id, $posttype, $type = 'category', $lang = APP_LANG)
    {
        if (empty($post_id) || empty($posttype)) {
            return [];
        }
        
        try {
            // Sử dụng PostsModel có sẵn
            $postsModel = new \App\Models\PostsModel($posttype, $lang);
            return $postsModel->getPostTermsByPostId($posttype, $post_id, $lang);
            
        } catch (Exception $e) {
            error_log('Error in get_post_terms: ' . $e->getMessage());
            return [];
        }
    }
}

/**
 * Lấy danh mục của bài viết theo slug
 * Lấy categories của bài viết dựa trên slug thay vì ID
 * 
 * @param string $slug_post Slug của bài viết
 * @param string $posttype Loại posttype (posts, news, etc.)
 * @param string $type Loại term (category, tag, etc.)
 * @param string $lang Mã ngôn ngữ
 * @return array Danh sách categories/terms
 */

if (!function_exists('get_post_categories_by_slug')) {
    function get_post_categories_by_slug($slug_post, $posttype = 'posts', $type = 'category', $lang = APP_LANG)
    {
        if (empty($slug_post) || empty($posttype)) {
            return [];
        }
        
        try {
            // Lấy thông tin bài viết theo slug để có ID
            $post = get_post([
                'slug' => $slug_post,
                'posttype' => $posttype,
                'active' => true
            ]);
            
            if (empty($post) || !isset($post['id'])) {
                return [];
            }
            
            // Sử dụng hàm get_post_categories có sẵn với ID
            return get_post_categories($post['id'], $posttype, $lang);
            
        } catch (Exception $e) {
            error_log('Error in get_post_categories_by_slug: ' . $e->getMessage());
            return [];
        }
    }
}

/**
 * Lấy terms (tags, categories) của bài viết theo slug
 * Lấy terms của bài viết dựa trên slug thay vì ID
 * 
 * @param string $slug_post Slug của bài viết
 * @param string $posttype Loại posttype (posts, news, etc.)
 * @param string $type Loại term (category, tag, etc.)
 * @param string $lang Mã ngôn ngữ
 * @return array Danh sách terms
 */
if (!function_exists('get_post_terms_by_slug')) {
    function get_post_terms_by_slug($slug_post, $posttype = 'posts', $type = 'category', $lang = APP_LANG)
    {
        if (empty($slug_post) || empty($posttype)) {
            return [];
        }
        
        try {
            // Lấy thông tin bài viết theo slug để có ID
            $post = get_post([
                'slug' => $slug_post,
                'posttype' => $posttype,
                'active' => true
            ]);
            
            if (empty($post) || !isset($post['id'])) {
                return [];
            }
            
            // Sử dụng hàm get_post_terms có sẵn với ID
            return get_post_terms($post['id'], $posttype, $type, $lang);
            
        } catch (Exception $e) {
            error_log('Error in get_post_terms_by_slug: ' . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('get_tags')) {
    /**
     * Lấy tags của một bài viết
     * 
     * @param string $posttype Loại posttype
     * @param int $post_id ID của bài viết
     * @param string $lang Mã ngôn ngữ
     * @return array Danh sách tags
     */
    function get_tags($posttype, $post_id, $lang = APP_LANG)
    {
        try {
            $pivotTable = table_posttype_relationship($posttype);
            $termTable = 'fast_terms';
            
            $qb = (new \App\Models\FastModel($pivotTable))
                ->newQuery()
                ->join($termTable, "{$termTable}.id_main", '=', "{$pivotTable}.rel_id")
                ->where("{$pivotTable}.post_id", $post_id)
                ->where("{$termTable}.type", 'tag')
                ->where("{$termTable}.posttype", $posttype)
                ->where("{$termTable}.lang", $lang)
                ->select(["{$termTable}.id_main", "{$termTable}.name", "{$termTable}.slug"]);
            
            return $qb->get();
        } catch (Exception $e) {
            error_log("Error getting tags: " . $e->getMessage());
            return [];
        }
    }
}