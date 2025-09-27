<?php
App\Libraries\Fastlang::load('Homepage');
//Render::asset('js', 'js/home-index.js', ['area' => 'frontend', 'location' => 'footer']);

//Get Object Data for this Pages
$locale = APP_LANG.'_'.strtoupper(lang_country(APP_LANG));

// Lấy dữ liệu search
$search_keyword = trim(S_GET('key', ''));
$search_order = S_GET('od', '2'); // 1 = oldest, 2 = newest
$search_sort = S_GET('sort', 'all');
$search_category = S_GET('cate', 'all');
$search_page = (int)(S_GET('page', 1));

// Khởi tạo dữ liệu search results
$search_results = [];
$total_results = 0;
$categories = [];


// Nếu có từ khóa tìm kiếm
if (!empty($search_keyword)) {
    try {
        // Xử lý từ khóa tìm kiếm
        $search_keyword_processed = str_replace(['-', '_'], ' ', $search_keyword);
        
        // Sử dụng get_posts function với search
        $search_params = [
            'posttype' => 'posts',
            'filters' => [
                'status' => 'active'
            ],
            'search' => $search_keyword_processed,
            'searchcolumns' => ['title', 'description', 'content', 'search_string'],
            'perPage' => 10,
            'paged' => $search_page,
            'withCategories' => true,
            'totalpage' => true
        ];
        
        // Filter theo category nếu có
        if ($search_category !== 'all' && !empty($search_category)) {
            $search_params['cat'] = $search_category;
        }
        
        // Sắp xếp
        if ($search_order === '1') {
            $search_params['sort'] = ['created_at', 'ASC']; // Oldest
        } else {
            $search_params['sort'] = ['created_at', 'DESC']; // Newest
        }
        
        // Filter theo thời gian
        if ($search_sort !== 'all') {
            $date_conditions = [
                '1' => '1 DAY',      // Past 24 hours
                '2' => '7 DAY',      // Past week
                '3' => '1 MONTH',    // Past month
                '4' => '1 YEAR'      // Past year
            ];
            
            if (isset($date_conditions[$search_sort])) {
                $search_params['filters']['created_at'] = ['>=', date('Y-m-d H:i:s', strtotime('-' . $date_conditions[$search_sort]))];
            }
        }
        
        // Lấy kết quả search
        $search_data = get_posts($search_params);
        
        if (is_array($search_data) && isset($search_data['data'])) {
            // Kết quả có pagination
            $search_results = $search_data['data'];
            $total_results = $search_data['total'] ?? 0;
        } elseif (is_array($search_data)) {
            // Kết quả không có pagination
            $search_results = $search_data;
            $total_results = count($search_results);
        } else {
            $search_results = [];
            $total_results = 0;
        }
        
    } catch (Exception $e) {
        error_log('Error searching posts: ' . $e->getMessage());
        $search_results = [];
        $total_results = 0;
    }
}

get_template('_metas/meta_index', ['locale' => $locale]);
//....
//End Get Object Data

?>


        <!-- Search Results Section -->
        <div class="bg-white py-8">
            <div class="max-w-7xl mx-auto">
                <!-- Title -->
                <div class="text-center mb-8">
                    <h1 class="text-2xl font-bold text-[#2d67ad] uppercase">SEARCH RESULTS</h1>
                </div>

                <!-- Search Form -->
                <form action="/search" method="get" class="">
                    <!-- Search Bar -->
                    <div class="relative mb-6">
                        <input type="text" name="key" value="<?= htmlspecialchars($search_keyword) ?>" placeholder="Enter keywords..." class="w-full px-4 py-3 pr-12 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="submit" class="absolute right-3 top-1/2 transform -translate-y-1/2 p-2 hover:bg-gray-100 rounded">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Filter Options -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Sort By -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Sort by</label>
                            <select name="od" class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="1" <?= $search_order === '1' ? 'selected' : '' ?>>Oldest</option>
                                <option value="2" <?= $search_order === '2' ? 'selected' : '' ?>>Newest</option>
                            </select>
                        </div>

                        <!-- Time Range -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Time range</label>
                            <select name="sort" class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="all" <?= $search_sort === 'all' ? 'selected' : '' ?>>All time</option>
                                <option value="1" <?= $search_sort === '1' ? 'selected' : '' ?>>Past 24 hours</option>
                                <option value="2" <?= $search_sort === '2' ? 'selected' : '' ?>>Past week</option>
                                <option value="3" <?= $search_sort === '3' ? 'selected' : '' ?>>Past month</option>
                                <option value="4" <?= $search_sort === '4' ? 'selected' : '' ?>>Past year</option>
                            </select>
                        </div>

                        <!-- Category -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Category</label>
                            <select name="cate" class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="all" <?= $search_category === 'all' ? 'selected' : '' ?>>All categories</option>
                                <?php if (!empty($categories)): ?>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>" <?= $search_category == $category['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="space-y-2">
                        <label class="block text-sm font-medium text-transparent">search </label>
                            <button type="submit" class="w-full px-3 text-white bg-[#2d67ad] py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">Search</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>


        <!-- Main News Section -->
        <section class="bg-white">
            <div class="max-w-7xl mx-auto border-t border-gray-200 mt-4 sm:mt-6 pt-4 sm:pt-5">
                <?php if (!empty($search_keyword)): ?>
                    <!-- Search Results Info -->
                    <div class="mb-6">
                        <h2 class="text-lg font-semibold text-gray-800">
                            <?= $total_results ?> results for "<?= htmlspecialchars($search_keyword) ?>"
                        </h2>
                    </div>
                <?php endif; ?>

                <div class="space-y-4 sm:space-y-6">
                    <?php if (!empty($search_results)): ?>
                        <?php foreach ($search_results as $index => $post): ?>
                            <?php
                            // Xử lý hình ảnh
                            $image_url = '/themes/apkcms/Frontend/Assets/images/lng-expansion.webp'; // Default image
                            if (!empty($post['feature'])) {
                                $feature_data = json_decode($post['feature'], true);
                                if (is_array($feature_data) && !empty($feature_data['url'])) {
                                    $image_url = '/uploads/' . $feature_data['url'];
                                }
                            } elseif (!empty($post['banner'])) {
                                $banner_data = json_decode($post['banner'], true);
                                if (is_array($banner_data) && !empty($banner_data['url'])) {
                                    $image_url = '/uploads/' . $banner_data['url'];
                                }
                            }

                            // Xử lý category name
                            $category_name = 'VIETNAMNET GLOBAL';
                            if (!empty($post['categories']) && is_array($post['categories'])) {
                                $category_name = strtoupper($post['categories'][0]['name'] ?? 'POSTS');
                            } elseif (!empty($post['category_name'])) {
                                $category_name = strtoupper($post['category_name']);
                            } elseif (!empty($post['category'])) {
                                $category_name = strtoupper($post['category']);
                            }

                            // Xử lý URL
                            $post_url = link_single($post['slug'], $post['posttype'] ?? 'posts');
                            ?>
                    <div class="flex gap-4 pb-6 border-b border-gray-200 last:border-b-0 last:pb-0">
                        <div class="w-[130px] sm:w-[240px] h-[86px] sm:h-40 flex-shrink-0">
                                    <a href="<?= $post_url ?>" title="<?= htmlspecialchars($post['title']) ?>">
                                        <img src="<?= $image_url ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="w-[130px] sm:w-[240px] h-[86px] sm:h-40 object-cover">
                            </a>
                        </div>
                        <div class="flex-1 flex flex-col">
                                    <div class="text-xs sm:text-sm font-bold mb-2 text-[#2d67ad] order-2 sm:order-1"><?= $category_name ?></div>
                            <h3 class="text-base font-bold text-gray-900 mb-3 leading-tight order-1 sm:order-2">
                                        <a href="<?= $post_url ?>" title="<?= htmlspecialchars($post['title']) ?>" class="hover:text-[#2d67ad]">
                                            <?= htmlspecialchars($post['title']) ?>
                                </a>
                            </h3>
                            <p class="hidden sm:block text-gray-600 leading-relaxed order-3">
                                        <?= htmlspecialchars($post['description'] ?? substr(strip_tags($post['content'] ?? ''), 0, 150) . '...') ?>
                            </p>
                        </div>
                    </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php if (!empty($search_keyword)): ?>
                            <!-- No results found -->
                            <div class="text-center py-12">
                                <div class="text-gray-500 text-lg mb-4">No articles found for "<?= htmlspecialchars($search_keyword) ?>"</div>
                                <p class="text-gray-400">Try different keywords or check your spelling.</p>
                        </div>
                        <?php else: ?>
                            <!-- No search performed -->
                            <div class="text-center py-12">
                                <div class="text-gray-500 text-lg mb-4">Enter keywords to search</div>
                                <p class="text-gray-400">Use the search form above to find articles.</p>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php if (!empty($search_results) && $total_results > 10): ?>
                <!-- Pagination - Desktop -->
                <div class="hidden sm:flex justify-center items-center w-full mt-5 mb-10">
                    <div class="flex items-center space-x-2">
                            <?php
                            $per_page = 10;
                            $total_pages = ceil($total_results / $per_page);
                            $current_page = $search_page;
                            
                            // Previous button
                            if ($current_page > 1):
                                $prev_params = $_GET;
                                $prev_params['page'] = $current_page - 1;
                                $prev_url = '/search?' . http_build_query($prev_params);
                            ?>
                                <a href="<?= $prev_url ?>" class="w-10 h-10 bg-white text-gray-700 border border-gray-300 rounded-md flex items-center justify-center text-sm font-medium hover:bg-gray-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                </a>
                            <?php endif; ?>

                            <?php
                            // Page numbers
                            $start_page = max(1, $current_page - 2);
                            $end_page = min($total_pages, $current_page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                                $page_params = $_GET;
                                $page_params['page'] = $i;
                                $page_url = '/search?' . http_build_query($page_params);
                                $is_active = $i == $current_page;
                            ?>
                                <a href="<?= $page_url ?>" class="w-10 h-10 <?= $is_active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300' ?> rounded-md flex items-center justify-center text-sm font-medium <?= $is_active ? 'hover:bg-blue-700' : 'hover:bg-gray-50' ?> transition-colors">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>

                            <?php
                            // Next button
                            if ($current_page < $total_pages):
                                $next_params = $_GET;
                                $next_params['page'] = $current_page + 1;
                                $next_url = '/search?' . http_build_query($next_params);
                            ?>
                                <a href="<?= $next_url ?>" class="w-10 h-10 bg-white text-gray-700 border border-gray-300 rounded-md flex items-center justify-center text-sm font-medium hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </section>




<?php get_footer(); ?>