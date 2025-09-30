<?php
App\Libraries\Fastlang::load('Homepage');
//Render::asset('js', 'js/home-index.js', ['area' => 'frontend', 'location' => 'footer']);

// Include functions helper
require_once __DIR__ . '/functions.php';

// Include database helper
// if (file_exists(FCPATH . 'helpers/Database_helper.php')) {
//     require_once FCPATH . 'helpers/Database_helper.php';
// }

global $post;
$categories_for_menu = function_exists('globals_categories') ? globals_categories() : ($GLOBALS['categories'] ?? []);

// var_dump($categories_for_menu);

$qb = (new \App\Models\FastModel('fast_posts_posts_rel'))
    ->newQuery()
    ->where('post_id', '=', $post['id'])
    ->where('lang', '=', APP_LANG);

// Thực hiện query và lấy kết quả
$results = $qb->get();

// Lưu các rel_id vào mảng
$rel_ids = [];
if (!empty($results) && is_array($results)) {
    foreach ($results as $result) {
        if (isset($result['rel_id'])) {
            $rel_ids[] = $result['rel_id'];
        }
    }
}

// Debug: hiển thị kết quả
// echo "<!-- Debug: Query results count: " . count($results) . " -->";
// echo "<!-- Debug: Rel IDs: " . print_r($rel_ids, true) . " -->";

// Lấy social links từ options
$social_links = [];
$google_news_url = 'https://news.google.com/'; // Default fallback
$social_option = option('social');
if (!empty($social_option)) {
    $social_links = is_string($social_option) ? json_decode($social_option, true) : $social_option;
    if (!is_array($social_links)) {
        $social_links = [];
    }
    
    // Tìm Google News link
    foreach ($social_links as $social) {
        if (is_array($social) && !empty($social['network']) && strtolower($social['network']) === 'google news') {
            $google_news_url = $social['url'];
            break;
        }
    }
}

$locale = APP_LANG.'_'.strtoupper(lang_country(APP_LANG));
get_template('_metas/meta_single', ['locale' => $locale]);


?>
        <div class="max-w-7xl mx-auto">
            <!-- Main Content Layout -->
            <div class="flex flex-col lg:flex-row gap-0 lg:gap-8 pt-0 lg:pt-6">
                <!-- Left Column - Main Content -->
                <div class="flex-1 ">
                    <!-- Article Header -->
                    <div class="mb-6 w-full lg:w-[760px]">
                        <!-- Breadcrumb Navigation Desktop -->
                        <nav class=" mb-4 lg:flex flex-col sm:flex-row sm:items-center sm:justify-between text-sm  gap-2 sm:gap-0" aria-label="Breadcrumb">
                            <!-- Left side: Breadcrumb path -->
                            <div class="flex flex-wrap items-center">

                                <a href="/" class="flex lg:hidden items-center text-[#2d67ad] mr-2">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill="#2d67ad" d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                                    </svg>
                                </a>
                                <?php 
                              
                                // Nếu vẫn không có, thử nhiều function khác nhau
                           
                               
                                    // Lấy categories của bài viết hiện tại dựa trên rel_ids
                                    $post_categories = [];
                                    if (!empty($rel_ids) && !empty($categories_for_menu)) {
                                        foreach ($categories_for_menu as $category) {
                                            if (in_array($category['id_main'], $rel_ids)) {
                                                $post_categories[] = $category;
                                            }
                                        }
                                    }
                                    ?>
                                    
                                    <?php if (!empty($post_categories) && is_array($post_categories)): ?>
                                        <?php foreach ($post_categories as $index => $category): ?>
                                            <?php if (isset($category['name']) && isset($category['slug'])): ?>
                                                <a href="<?= link_cat($category['slug'], $post['posttype'] ?? 'posts') ?>" 
                                                   class="text-[#2d67ad] text-[16px] font-bold transition-colors  text-sm sm:text-base hover:text-[#0a569d]">
                                                    <?= htmlspecialchars($category['name']) ?>
                                                </a>
                                                <?php if ($index < count($post_categories) - 1): ?>
                                                    <span class="text-gray-500 mr-1">, </span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-gray-500 text-sm">No categories available</span>
                                    <?php endif; ?>
                                
                                
                                
                            </div>
                            <!-- Right side: Date and time -->
                            <div class="text-gray-500 hidden lg:block text-xs sm:text-sm text-[12px]">
                                <?= !empty($post['created_at']) ? date('d/m/Y H:i', strtotime($post['created_at'])) . '' : date('d/m/Y H:i') . '' ?>
                            </div>
                        </nav>

                        <h1 class="merriweather-bold text-2xl sm:notosans-bold sm:text-3xl font-bold text-gray-h1 mb-4 leading-tight">
                            <?= htmlspecialchars($post['title'] ?? 'No Title Available') ?>
                        </h1>

                        <div class="text-gray-500 lg:hidden text-xs sm:text-sm text-[12px]">
                            <?= !empty($post['created_at']) ? date('d/m/Y H:i', strtotime($post['created_at'])) . '' : date('d/m/Y H:i') . '' ?>
                        </div>
                        <div class="flex flex-wrap flex-col sm:flex-row sm:items-center sm:justify-between mb-0 lg:mb-6 ">
                            <div class="flex items-center space-x-2 mb-3 sm:mb-0">
                                <!-- Share Buttons -->

                                <div class="hidden lg:flex gap-2">
                                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>&quote=<?= urlencode($post['title'] ?? '') ?>" 
                                       target="_blank" 
                                       rel="noopener noreferrer"
                                       class="flex items-center justify-center min-w-[30px] w-[30px] h-[30px] border border-gray-300 rounded-full hover:bg-gray-50 transition-colors"
                                       title="Chia sẻ lên Facebook">
                                        <svg width="16px" height="16px" viewBox="-5 0 20 20" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="#000000">
                                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                            <g id="SVGRepo_iconCarrier">
                                                <title>facebook [#757575]</title>
                                                <desc>Created with Sketch.</desc>
                                                <defs> </defs>
                                                <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                                    <g id="Dribbble-Light-Preview" transform="translate(-385.000000, -7399.000000)" fill="#757575">
                                                        <g id="icons" transform="translate(56.000000, 160.000000)">
                                                            <path d="M335.821282,7259 L335.821282,7250 L338.553693,7250 L339,7246 L335.821282,7246 L335.821282,7244.052 C335.821282,7243.022 335.847593,7242 337.286884,7242 L338.744689,7242 L338.744689,7239.14 C338.744689,7239.097 337.492497,7239 336.225687,7239 C333.580004,7239 331.923407,7240.657 331.923407,7243.7 L331.923407,7246 L329,7246 L329,7250 L331.923407,7250 L331.923407,7259 L335.821282,7259 Z" id="facebook-[#757575]"> </path>
                                                        </g>
                                                    </g>
                                                </g>
                                            </g>
                                        </svg>
                                    </a>
                                    <a href="https://zalo.me/share?url=<?= urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>&title=<?= urlencode($post['title'] ?? '') ?>" 
                                       target="_blank" 
                                       rel="noopener noreferrer"
                                       class="flex items-center justify-center min-w-[30px] w-[30px] h-[30px] border border-gray-300 rounded-full hover:bg-gray-50 transition-colors"
                                       title="Chia sẻ lên Zalo">
                                        <img src="/themes/apkcms/Frontend/Assets/icons/zalo-unactive-1.svg" alt="Zalo" class="w-[40px] h-[40px]">
                                    </a>
                                    <a href="mailto:?subject=<?= urlencode($post['title'] ?? '') ?>&body=<?= urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>" 
                                       class="flex items-center justify-center min-w-[30px] w-[30px] h-[30px] border border-gray-300 rounded-full hover:bg-gray-50 transition-colors"
                                       title="Gửi email">
                                        <svg class="w-[16px] h-3 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
                                        </svg>
                                    </a>
                                    <button onclick="copyToClipboard('http://<?= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>')" 
                                            class="flex items-center justify-center min-w-[30px] w-[30px] h-[30px] border border-gray-300 rounded-full hover:bg-gray-50 transition-colors"
                                            title="Copy link">
                                        <svg class="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                                            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                                        </svg>
                                    </button>
                                </div>


                                <a class="flex items-center px-0 lg:px-3 py-2 ml-0 m-0 transition-colors" 
                                   style="margin-left:0 !important" 
                                   href="<?= htmlspecialchars($google_news_url) ?>" 
                                   target="_blank" 
                                   rel="noopener nofollow"
                                   title="Google News">
                                    <img src="/themes/apkcms/Frontend/Assets/icons/google-news-en.svg" 
                                         alt="Google News" 
                                         class="h-[30px] ml-0">
                                </a>
                            </div>
                            <!-- Google News Button -->
                            <div class="flex items-center">

                            </div>
                        </div>
                        <!-- Article Summary -->
                        <h2 class="arial  text-gray-h2 mb-6 arial font-bold leading-relaxed">
                            <?= htmlspecialchars($post['description'] ?? $post['excerpt'] ?? 'No description available') ?>
                        </h2>
                    </div>
                    <!-- Article Content -->
                    <div class="prose prose-lg max-w-none">
                        <!-- Main Image -->

                        <!-- Article Text -->
                        <div class="text-gray-p space-y-4 font-arial text-base leading-relaxed">
                            <?= $post['content'] ?? '<p>No content available</p>' ?>
                        </div>
                        <!-- ------------------- -->
                       
                        <!-- Author -->
                        <?php
                        // Lấy thông tin tác giả từ $post['author']
                        $author_info = null;
                        if (!empty($post['author'])) {
                            $author_info = getAuthor($post['author']);
                        }
                        
                        // Nếu không có thông tin tác giả, sử dụng thông tin mặc định
                        $author_name = 'Admin';
                        $author_avatar = '/themes/apkcms/Frontend/assets/images/default-avatar.png';
                        $author_bio = '';
                        $author_url = '/author/' . ($post['author'] ?? 'admin');
                        
                        if ($author_info && is_array($author_info)) {
                            $author_name = $author_info['fullname'] ?? $author_info['username'] ?? 'Admin';
                            $author_avatar = !empty($author_info['avatar']) ? $author_info['avatar'] : $author_avatar;
                            $author_bio = $author_info['about_me'] ?? '';
                            $author_url = '/author/' . ($author_info['username'] ?? $post['author']);
                        }
                        ?>
                        
                        <div class="author-info mt-4">
                            <div class="flex items-center space-x-4">
                                <!-- Avatar -->
                                <div class="flex-shrink-0">
                                    <img src="<?= $author_avatar ?>" 
                                         alt="<?= htmlspecialchars($author_name) ?>" 
                                         class="w-8 h-8 rounded-full object-cover border-2 border-gray-200">
                                </div>
                                
                                <!-- Author Details -->
                                <div class="flex-grow">
                                    <h4 class="notosans-bold font-bold text-lg hover:text-[#2d67ad] ">
                                        <a href="<?= $author_url ?>" 
                                           title="Xem tất cả bài viết của <?= htmlspecialchars($author_name) ?>"
                                           class="hover:text-blue-600 transition-colors">
                                            <?= htmlspecialchars($author_name) ?>
                                        </a>
                                    </h4>
                                    
                                   
                                    
                                   
                                </div>
                            </div>
                        </div>
                       
                        <!-- Related Articles -->
                        <!-- Related Articles -->
                        <div class="mt-2 pt-4 mb-6 border-t border-[#add2e1] related-articles">
                            <?php
                            // Lấy 3 bài viết mới nhất cùng tác giả
                            $posts_data = [];
                            // var_dump($post['author']);
                            if (!empty($post['author'])) {
                                $posts_data = get_posts([
                                    'posttype' => 'posts',
                                    'filters' => [
                                        'author' => $post['author'] 
                                    ],
                                    'perPage' => 3,
                                    'sort' => ['created_at', 'DESC']
                                   
                                ]);
                                // Lấy dữ liệu từ key 'data'
                                if (isset($posts_data['data']) && is_array($posts_data['data'])) {
                                    $posts_data = $posts_data['data'];
                                } else {
                                    $posts_data = [];
                                }
                                
                                // Loại bỏ bài viết hiện tại
                                if (!empty($posts_data) && is_array($posts_data)) {
                                    $posts_data = array_filter($posts_data, function($author_post) use ($post) {
                                        return is_array($author_post) && isset($author_post['id']) && $author_post['id'] != $post['id'];
                                    });
                                }
                                
                                // Chỉ lấy 3 bài đầu tiên
                                $posts_data = array_slice($posts_data, 0, 3);
                               
                            }
                            // var_dump($posts_data);
                            ?>
                            
                            <?php if (!empty($posts_data) && is_array($posts_data)): ?>
                                <ul class="space-y-3 ml-0">
                                    <?php foreach ($posts_data as $author_post): ?>
                                        <?php if (is_array($author_post) && !empty($author_post['title'])): ?>
                                    <li class="flex items-start">
                                        <span class="text-[#2d67ad] font-bold mr-3 mt-1 text-[8px] leading-[14px]">■</span>
                                        <a href="<?= link_single($author_post['slug'] ?? '', $author_post['posttype'] ?? $post['posttype']) ?>"
                                           class="text-[#6c6c6c] font-bold hover:text-[#0a569d] notosans-bold text-sm leading-6 no-underline transition-colors"
                                           title="<?= htmlspecialchars($author_post['title'] ?? '') ?>">
                                            <?= htmlspecialchars($author_post['title'] ?? 'No Title') ?>
                                        </a>
                                    </li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                            <div class="text-center py-4 text-gray-500 text-sm">
                                <p>Không có bài viết khác của tác giả này</p>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 ">
                            <div class="flex items-center space-x-2 mb-3 sm:mb-0">
                                <!-- Share Buttons -->
                                <button class="bg-[#2d67ad] border border-[#2d67ad] rounded-[50px] text-white cursor-pointer notosans-bold text-xs px-[15px] py-[7px] hover:bg-[#1e4a7a] hover:border-[#1e4a7a] transition-colors">
                                    Comment
                                </button>
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>&quote=<?= urlencode($post['title'] ?? '') ?>" 
                                       target="_blank" 
                                       rel="noopener noreferrer"
                                       class="flex items-center justify-center min-w-[30px] w-[30px] h-[30px] border border-gray-300 rounded-full hover:bg-gray-50 transition-colors"
                                       title="Chia sẻ lên Facebook">
                                        <svg width="16px" height="16px" viewBox="-5 0 20 20" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="#000000">
                                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                            <g id="SVGRepo_iconCarrier">
                                                <title>facebook [#757575]</title>
                                                <desc>Created with Sketch.</desc>
                                                <defs> </defs>
                                                <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                                    <g id="Dribbble-Light-Preview" transform="translate(-385.000000, -7399.000000)" fill="#757575">
                                                        <g id="icons" transform="translate(56.000000, 160.000000)">
                                                            <path d="M335.821282,7259 L335.821282,7250 L338.553693,7250 L339,7246 L335.821282,7246 L335.821282,7244.052 C335.821282,7243.022 335.847593,7242 337.286884,7242 L338.744689,7242 L338.744689,7239.14 C338.744689,7239.097 337.492497,7239 336.225687,7239 C333.580004,7239 331.923407,7240.657 331.923407,7243.7 L331.923407,7246 L329,7246 L329,7250 L331.923407,7250 L331.923407,7259 L335.821282,7259 Z" id="facebook-[#757575]"> </path>
                                                        </g>
                                                    </g>
                                                </g>
                                            </g>
                                        </svg>
                                    </a>
                                    <a href="https://zalo.me/share?url=<?= urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>&title=<?= urlencode($post['title'] ?? '') ?>" 
                                       target="_blank" 
                                       rel="noopener noreferrer"
                                       class="flex items-center justify-center min-w-[30px] w-[30px] h-[30px] border border-gray-300 rounded-full hover:bg-gray-50 transition-colors"
                                       title="Chia sẻ lên Zalo">
                                        <img src="/themes/apkcms/Frontend/Assets/icons/zalo-unactive-1.svg" alt="Zalo" class="w-[40px] h-[40px]">
                                    </a>
                                    <a href="mailto:?subject=<?= urlencode($post['title'] ?? '') ?>&body=<?= urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>" 
                                       class="flex items-center justify-center min-w-[30px] w-[30px] h-[30px] border border-gray-300 rounded-full hover:bg-gray-50 transition-colors"
                                       title="Gửi email">
                                        <svg class="w-[16px] h-3 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
                                        </svg>
                                    </a>
                                    <button onclick="copyToClipboard('http://<?= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>')" 
                                            class="flex items-center justify-center min-w-[30px] w-[30px] h-[30px] border border-gray-300 rounded-full hover:bg-gray-50 transition-colors"
                                            title="Copy link">
                                        <svg class="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                                            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                                        </svg>
                                    </button>

                            </div>
                            <!-- Google News Button -->
                            <div class="flex items-center">

                            </div>
                        </div>
                    </div>

                    <!-- Categories and Tags -->
                    <div class=" ">
                        <div class="flex flex-wrap items-center gap-2">
                          
                            
                            <?php 
                            // Lấy tags của bài viết
                            $post_tags = get_tags($post['posttype'], $post['id']);
                            ?>
                            
                            <?php if (!empty($post_tags)): ?>
                                <span class="text-sm text-gray-500 font-medium ml-4">Topics:</span>
                                <?php foreach ($post_tags as $tag): ?>
                                    <a href="<?= link_cat($tag['slug'], $post['posttype'] ?? 'posts') ?>" 
                                       class="text-[#555] text-sm inline-block px-[10px] py-[2px] no-underline hover:underline transition-colors border border-gray-300 rounded-full">
                                        <?= htmlspecialchars($tag['name']) ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <?php if (empty($post_categories) && empty($post_tags)): ?>
                                <span class="text-sm text-gray-500 font-medium">No categories or tags</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Comment Section -->
                    <div class="mt-8 pt-6 ">
                        <h3 class="text-lg notosans-bold mb-4 text-gray-h3 uppercase text-[#0a569d] font-bold">Comments</h3>
                        <!-- <div class="block h-[70px] w-full border border-[#CDE3FF] bg-[#EEF5FF] rounded-[5px] cursor-text "><input class=" bg-[#EEF5FF] h-[40px] w-full rounded-[5px] py-[10px] px-[10px] focus:outline-none " type="text" placeholder="Your comment...."></div> -->
                        <?php// do_shortcode('rw-rating',$post['posttype'], $post['id']); ?>
                    </div>
                </div>
                <!-- Right Column - Sidebar -->
                <div class="w-full lg:w-1/3 space-y-6  pt-10 w-[300px]">
                    <!-- Sticky Sidebar Content -->
                    <div class="lg:sticky lg:top-6 space-y-6">

                        <!-- Section You might be interested in-->
                        <div class="pt-4">
                            <h2 class="text-lg notosans-bold text-[#2d67ad] uppercase font-bold border-b border-[#add2ff] mb-4 pb-2">READ MORE</h2>
                            
                            <?php
                            // Lấy related posts cho sidebar
                            $sidebar_related_posts = [];
                            if (!empty($post['id']) && !empty($post['posttype'])) {
                                $sidebar_related_posts = getRelated($post['posttype'], $post['id'], 3);
                                // Debug: uncomment để xem dữ liệu
                                // var_dump($sidebar_related_posts);
                            }
                            ?>
                            
                            <div class="space-y-4">
                                <?php if (!empty($sidebar_related_posts) && is_array($sidebar_related_posts)): ?>
                                    <?php foreach ($sidebar_related_posts as $related_post): ?>
                                        <?php if (is_array($related_post) && !empty($related_post['title'])): ?>
                                            <article class="flex space-x-3">
                                                <a href="<?= link_single($related_post['slug'] ?? '', $related_post['posttype'] ?? $post['posttype']) ?>" title="<?= htmlspecialchars($related_post['title'] ?? '') ?>">
                                                    <?php 
                                                    // Lấy ảnh đại diện - xử lý đúng cấu trúc JSON
                                                    $feature_image = '';
                                                    
                                                    // Xử lý feature image
                                                    if (!empty($related_post['feature'])) {
                                                        $feature = is_string($related_post['feature']) ? json_decode($related_post['feature'], true) : $related_post['feature'];
                                                        if (is_array($feature) && !empty($feature['path'])) {
                                                            $feature_image = '/uploads/' . $feature['path'];
                                                        }
                                                    }
                                                    
                                                    // Nếu không có feature, thử banner
                                                    if (empty($feature_image) && !empty($related_post['banner'])) {
                                                        $banner = is_string($related_post['banner']) ? json_decode($related_post['banner'], true) : $related_post['banner'];
                                                        if (is_array($banner) && !empty($banner['path'])) {
                                                            $feature_image = '/uploads/' . $banner['path'];
                                                        }
                                                    }
                                                    
                                                    // Fallback image nếu không có ảnh
                                                    if (empty($feature_image)) {
                                                        $feature_image = '/themes/apkcms/Frontend/Assets/images/lng-expansion.webp';
                                                    }
                                                    ?>
                                                    <img class="w-[135px] h-[90px] object-cover" 
                                                         src="<?= $feature_image ?>" 
                                                         alt="<?= htmlspecialchars($related_post['title'] ?? '') ?>">
                                                </a>
                                                <div class="flex-1">
                                                    <h3 class="text-sm font-medium text-gray-900 leading-tight hover:text-blue-600 transition-colors">
                                                        <a href="<?= link_single($related_post['slug'] ?? '', $related_post['posttype'] ?? $post['posttype']) ?>">
                                                            <?= htmlspecialchars($related_post['title'] ?? '') ?>
                                                        </a>
                                                    </h3>
                                                </div>
                                            </article>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <!-- Fallback nếu không có related posts -->
                                    <p class="text-gray-500 text-sm">No related articles found.</p>
                                <?php endif; ?>
                            </div>

                            <div class="text-center my-6">
                                <a href="/" class="inline-flex items-center px-4 py-2 text-[#2d67ad] border border-[#2d67ad] text-xs uppercase font-bold rounded-full hover:bg-[#2d67ad] hover:text-white transition-colors" title="GO BACK TO THE HOME PAGE">
                                    <svg class="mr-2 w-4 h-4" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M10 12L6 8L10 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    GO BACK TO THE HOME PAGE
                                </a>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <!-- Main News Section -->
            <div class="flex flex-col lg:flex-row gap-6 lg:gap-8">
                <!-- Main Content Column -->
                <div class="flex-1 lg:w-2/3 ">

                    <!-- Desktop Featured News Section -->
                    <div class="">
                        <h2 class="text-[18px] notosans-bold  text-[#2d67ad] font-bold uppercase pt-6 border-b border-blue-200">Hot news</h2>
                        <!-- Main content ở đây -->
                        <div class="mt-6">
                            <?php
                            // Lấy bài viết cùng danh mục và sắp xếp theo lượt xem
                            $hot_news_posts = [];
                            if (!empty($post_categories) && is_array($post_categories) && !empty($post['posttype'])) {
                                // Lấy ID của các danh mục
                                $category_ids = array_column($post_categories, 'id_main');
                                
                                if (!empty($category_ids)) {
                                    $hot_news_posts = get_posts([
                                        'posttype' => $post['posttype'],
                                        'filters' => [
                                            'status' => 'active'
                                        ],
                                        'cat__in' => $category_ids,
                                        'perPage' => 5,
                                        'sort' => ['views', 'DESC']
                                    ]);
                                    // var_dump($hot_news_posts);
                                    
                                    // Lấy dữ liệu từ key 'data' nếu có
                                    if (isset($hot_news_posts['data']) && is_array($hot_news_posts['data'])) {
                                        $hot_news_posts = $hot_news_posts['data'];
                                    } else {
                                        $hot_news_posts = [];
                                    }
                                    
                                    // Loại bỏ bài viết hiện tại
                                    if (!empty($hot_news_posts) && is_array($hot_news_posts)) {
                                        $hot_news_posts = array_filter($hot_news_posts, function($news_post) use ($post) {
                                            return is_array($news_post) && isset($news_post['id']) && $news_post['id'] != $post['id'];
                                        });
                                    }
                                    
                                    // Chỉ lấy 4 bài đầu tiên
                                    $hot_news_posts = array_slice($hot_news_posts, 0, 4);
                                }
                            }
                            
                            
                            // var_dump($hot_news_posts);
                            ?>
                            <?php if (!empty($hot_news_posts)): ?>
                            <div class="lg:space-y-6 space-y-2">
                                <?php foreach ($hot_news_posts as $news_post): ?>
                                <?php
                                // Xử lý ảnh từ get_posts() - decode JSON và lấy path
                                $image_url = '';
                                if (!empty($news_post['feature'])) {
                                    $feature_data = json_decode($news_post['feature']);
                                    if (!empty($feature_data->path)) {
                                        $image_url = '/uploads/' . $feature_data->path;
                                    }
                                }
                                
                                // Nếu không có ảnh, dùng ảnh mặc định
                                if (empty($image_url)) {
                                    $image_url = '/themes/apkcms/Frontend/Assets/images/fpt-ceo.jpg';
                                }
                                ?>
                                <div class="flex gap-4 pb-0">
                                    <div class="w-[130px] sm:w-[240px] h-[86px] sm:h-40 flex-shrink-0">
                                        <a href="<?= link_single($news_post['slug'], $news_post['posttype'] ?? $post['posttype']) ?>"
                                           title="<?= htmlspecialchars($news_post['title'] ?? '') ?>">
                                            <img src="<?= $image_url ?>"
                                                 alt="<?= htmlspecialchars($news_post['title'] ?? '') ?>"
                                                 class="w-[130px] sm:w-[240px] h-[86px] sm:h-40 object-cover">
                                        </a>
                                    </div>
                                    <div class="flex-1 flex flex-col">
                                        <div class="text-xs sm:text-sm font-bold mb-2 text-[#2d67ad] order-2 sm:order-1">
                                            <?php
                                            if (!empty($news_post['categories']) && is_array($news_post['categories'])) {
                                                echo strtoupper($news_post['categories'][0]['name'] ?? 'POSTS');
                                            } else {
                                                echo strtoupper($news_post['posttype'] ?? 'POSTS');
                                            }
                                            ?>
                                        </div>
                                        <h3 class="text-base font-bold text-gray-900 mb-3 leading-tight order-1 sm:order-2">
                                            <a href="<?= link_single($news_post['slug'], $post['posttype']) ?>"
                                               title="<?= htmlspecialchars($news_post['title'] ?? '') ?>"
                                               class="hover:text-[#2d67ad]">
                                                <?= htmlspecialchars($news_post['title'] ?? 'No Title') ?>
                                            </a>
                                        </h3>
                                        <p class="hidden sm:block text-gray-600 leading-relaxed order-3">
                                            <?= htmlspecialchars($news_post['description'] ?? '') ?>
                                        </p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-8 text-gray-500">
                                <p>Không có bài viết liên quan</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="my-12 text-center">
                        <!-- <button class="vnn-load-more btn-outline-gray border text-xs notosans-bold uppercase text-blue-primary mb-2 items-center border border-blue-primary px-4 py-1 rounded-full">
                            See more
                             <img class="icon-loading hidden" src="/themes/apkcms/Frontend/Assets/icons/loading.svg" alt="icon loading">
                        </button> -->
                    </div>
                </div>
                <!-- Cột phải -->
                <div class="w-full lg:w-1/3">
                </div>
            </div>
            
        </div>

        <!-- Toast Notification -->
        <div id="toast" class="fixed top-4 right-[-5px] bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300 z-50">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>Link đã được copy vào clipboard!</span>
            </div>
        </div>

        <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                showToast();
            }).catch(function(err) {
                // Fallback cho trình duyệt cũ
                var textArea = document.createElement("textarea");
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showToast();
            });
        }

        function showToast() {
            const toast = document.getElementById('toast');
            toast.classList.remove('translate-x-full');
            toast.classList.add('translate-x-0');
            
            setTimeout(function() {
                toast.classList.remove('translate-x-0');
                toast.classList.add('translate-x-full');
            }, 3000);
        }
        </script>
        <script src="/plugins/reactix/Asstets/js/wp-rating.js"></script>
        <script src="/plugins/reactix/Asstets/js/swiper-bundle.min.js"></script>

<?php get_footer(); ?>