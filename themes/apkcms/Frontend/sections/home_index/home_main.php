<?php
// Lấy trang hiện tại từ URL
$current_page = (int)(S_GET('page', 1));
$per_page = 10; // Tạm thời để test pagination

// Lấy tất cả bài viết sắp xếp theo mới nhất với pagination
$home_posts_data = get_posts([
    'posttype' => 'posts',
    'filters' => [
        'status' => 'active'
    ],
    'perPage' => $per_page,
    'paged' => $current_page,
    'sort' => ['created_at', 'DESC'],
    'withCategories' => true,
    'totalpage' => true,
]);

// Debug raw data
echo "<!-- Debug raw data: " . print_r($home_posts_data, true) . " -->";

// Lấy dữ liệu từ key 'data' và thông tin phân trang
if (isset($home_posts_data['data']) && is_array($home_posts_data['data'])) {
    $home_posts = $home_posts_data['data'];
    $total_posts = $home_posts_data['total'] ?? count($home_posts);
    $total_pages = $home_posts_data['last_page'] ?? 1;
    
    // Debug: Kiểm tra thông tin phân trang
    echo "<!-- Debug: Total posts = $total_posts, Total pages = $total_pages, Current page = $current_page -->";
} else {
    $home_posts = [];
    $total_posts = 0;
    $total_pages = 1;
    echo "<!-- Debug: No posts data found -->";
}

?>

<!-- Breadcrumb Section -->
<section class=" bg-white border-b border-gray-200 py-4 hidden sm:block">
            <div class="max-w-7xl mx-auto">
                <div class="flex items-center justify-between">
                    <!-- Left Side - Home Icon and Title -->
                    <div class="flex items-center space-x-4">


                        <!-- Title -->
                        <div class="breadcrumb__heading">
                            <h1 class="text-2xl font-bold text-[#2d67ad]" style=" font-family: notosans-bold; font-size: 32px;">
                                <a href="/en" title="Vietnamnet Global">Vietnamnet Global</a>
                            </h1>
                        </div>
                    </div>

                    <!-- Right Side - Search Form -->
                    <div class="search-small">
                        <form class="search-small__form relative" action="/search/">
                            <input class="search-small__form-input w-60 h-[28px] px-4 pr-10 border border-gray-300 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" name="key" type="text" placeholder="Type keywords....">
                            <button class="search-small__form-btn absolute right-2 top-1/2 transform -translate-y-1/2 w-6 h-6 flex items-center justify-center hover:bg-gray-100 rounded-full transition-colors" type="submit">
                                <img src="https://static.vnncdn.net/v1/icon/search.png" alt="icon search" class="w-4 h-4">
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
        <!-- Main News Section -->
        <section class="bg-white">
            <div class="max-w-7xl mx-auto pt-4 ">
                <div class="flex flex-col lg:flex-row gap-4 sm:gap-6 lg:gap-8">
                    <!-- Left Column - Main Content -->
                    <div class="flex-1">
                        <!-- Top Section: Large Article + Two Stacked Articles -->
                        <div class="flex flex-col md:flex-row gap-4 sm:gap-5 mb-6 sm:mb-8">
                            <!-- Large Featured Article -->
                            <div class="flex-1 w-full md:w-[500px]">
                                <?php if (!empty($home_posts) && isset($home_posts[0])): ?>
                                    <?php 
                                    $featured_post = $home_posts[0];
                                    
                                    // Lấy ảnh đại diện
                                    $featured_image = '';
                                    if (!empty($featured_post['feature'])) {
                                        $feature = is_string($featured_post['feature']) ? json_decode($featured_post['feature'], true) : $featured_post['feature'];
                                        if (is_array($feature) && !empty($feature['path'])) {
                                            $featured_image = '/uploads/' . $feature['path'];
                                        }
                                    }
                                    
                                    // Fallback image nếu không có ảnh
                                    if (empty($featured_image)) {
                                        $featured_image = '';
                                    }
                                    ?>
                                    <a href="<?= link_single($featured_post['slug'], $featured_post['posttype'] ?? 'posts') ?>" title="<?= htmlspecialchars($featured_post['title'] ?? '') ?>">
                                        <img src="<?= $featured_image ?>" alt="<?= htmlspecialchars($featured_post['title'] ?? '') ?>" class="w-full h-48 sm:h-64 md:h-80 object-cover mb-4">
                                </a>
                                <h3 class="text-lg sm:text-xl md:text-2xl font-bold text-gray-900 mb-3 leading-tight">
                                        <a href="<?= link_single($featured_post['slug'], $featured_post['posttype'] ?? 'posts') ?>" title="<?= htmlspecialchars($featured_post['title'] ?? '') ?>" class="hover:text-[#2d67ad]">
                                            <?= htmlspecialchars($featured_post['title'] ?? 'No Title') ?>
                                    </a>
                                </h3>
                                <p class="text-sm sm:text-base text-gray-700 leading-relaxed">
                                        <?= htmlspecialchars($featured_post['description'] ?? $featured_post['excerpt'] ?? 'No description available') ?>
                                    </p>
                                <?php else: ?>
                                    <!-- Fallback nếu không có bài viết -->
                                    <div class="w-full h-48 sm:h-64 md:h-80 bg-gray-200 mb-4 flex items-center justify-center">
                                        <p class="text-gray-500">No articles available</p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Two Stacked Articles -->
                            <div class="w-full md:w-[240px] flex-shrink-0 space-y-4 sm:space-y-6">
                                <?php for ($i = 1; $i <= 2; $i++): ?>
                                    <?php if (!empty($home_posts) && isset($home_posts[$i])): ?>
                                        <?php 
                                        $side_post = $home_posts[$i];
                                        
                                        // Lấy ảnh đại diện
                                        $side_image = '';
                                        if (!empty($side_post['feature'])) {
                                            $feature = is_string($side_post['feature']) ? json_decode($side_post['feature'], true) : $side_post['feature'];
                                            if (is_array($feature) && !empty($feature['path'])) {
                                                $side_image = '/uploads/' . $feature['path'];
                                            }
                                        }
                                        
                                        // Fallback image nếu không có ảnh
                                        if (empty($side_image)) {
                                            $side_image = 'https://static-images.vnncdn.net/vps_images_publish/000001/00000Q/2025/9/17/classical-stars-unite-for-vivaldi-beethoven-concert-in-hanoi-bb0763974f9c4f0daf96e7eb1665aba6-52.png?width=360&s=g-RuxjAOe5ay9qlQYhkzNA';
                                        }
                                        
                                        // Lấy danh mục từ dữ liệu đã có
                                        $category_name = 'VIETNAMNET GLOBAL';
                                        
                                        // Thử nhiều cách lấy danh mục
                                        if (!empty($side_post['categories']) && is_array($side_post['categories'])) {
                                            $category_name = strtoupper($side_post['categories'][0]['name'] ?? 'POSTS');
                                        } elseif (!empty($side_post['category_name'])) {
                                            $category_name = strtoupper($side_post['category_name']);
                                        } elseif (!empty($side_post['category'])) {
                                            $category_name = strtoupper($side_post['category']);
                                        } elseif (!empty($side_post['term_name'])) {
                                            $category_name = strtoupper($side_post['term_name']);
                                        } elseif (!empty($side_post['name'])) {
                                            $category_name = strtoupper($side_post['name']);
                                        }
                                        
                                        ?>
                                <div class="flex gap-4 sm:block pb-4 sm:pb-6 border-b sm:border-b-0 border-gray-200 last:border-b-0 last:pb-0">
                                            <a href="<?= link_single($side_post['slug'], $side_post['posttype'] ?? 'posts') ?>" title="<?= htmlspecialchars($side_post['title'] ?? '') ?>" class="w-[130px] sm:w-full flex-shrink-0">
                                                <img src="<?= $side_image ?>" alt="<?= htmlspecialchars($side_post['title'] ?? '') ?>" class="w-[130px] sm:w-full h-[86px] sm:h-40 object-cover mb-3">
                                            </a>
                                            <div class="flex-1 flex flex-col">
                                                <div class="text-xs font-bold mb-2 text-[#2d67ad] order-2 sm:order-1 sm:hidden">
                                                    <?= $category_name ?>
                                </div>
                                        <h3 class="text-base font-bold text-gray-900 leading-tight mb-3 order-1 sm:order-2">
                                                    <a href="<?= link_single($side_post['slug'], $side_post['posttype'] ?? 'posts') ?>" title="<?= htmlspecialchars($side_post['title'] ?? '') ?>" class="hover:text-[#2d67ad]">
                                                        <?= htmlspecialchars($side_post['title'] ?? 'No Title') ?>
                                            </a>
                                        </h3>
                                    </div>
                                </div>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <!-- Bottom Section: Three Horizontal Articles -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
                            <?php for ($i = 3; $i <= 5; $i++): ?>
                                <?php if (!empty($home_posts) && isset($home_posts[$i])): ?>
                                    <?php 
                                    $grid_post = $home_posts[$i];
                                    
                                    // Lấy ảnh đại diện
                                    $grid_image = '';
                                    if (!empty($grid_post['feature'])) {
                                        $feature = is_string($grid_post['feature']) ? json_decode($grid_post['feature'], true) : $grid_post['feature'];
                                        if (is_array($feature) && !empty($feature['path'])) {
                                            $grid_image = '/uploads/' . $feature['path'];
                                        }
                                    }
                                    
                                    // Fallback image nếu không có ảnh
                                    if (empty($grid_image)) {
                                        $grid_image = 'https://static-images.vnncdn.net/vps_images_publish/000001/00000Q/2025/9/17/vietnamese-dance-duo-wins-world-bronze-in-latin-showdance-debut-f90b4100f4734b28a7bd6a64bd89b804-98.gif?width=360&s=R3gNXmh-f4G_2fNK9Yri_A';
                                    }
                                    
                                        // Lấy danh mục từ dữ liệu đã có
                                        $category_name = 'VIETNAMNET GLOBAL';
                                        
                                        // Thử nhiều cách lấy danh mục
                                        if (!empty($grid_post['categories']) && is_array($grid_post['categories'])) {
                                            $category_name = strtoupper($grid_post['categories'][0]['name'] ?? 'POSTS');
                                        } elseif (!empty($grid_post['category_name'])) {
                                            $category_name = strtoupper($grid_post['category_name']);
                                        } elseif (!empty($grid_post['category'])) {
                                            $category_name = strtoupper($grid_post['category']);
                                        } elseif (!empty($grid_post['term_name'])) {
                                            $category_name = strtoupper($grid_post['term_name']);
                                        } elseif (!empty($grid_post['name'])) {
                                            $category_name = strtoupper($grid_post['name']);
                                        }
                                    ?>
                            <div class="flex gap-4 sm:block pb-4 sm:pb-6 border-b sm:border-b-0 border-gray-200 last:border-b-0 last:pb-0">
                                        <a href="<?= link_single($grid_post['slug'], $grid_post['posttype'] ?? 'posts') ?>" title="<?= htmlspecialchars($grid_post['title'] ?? '') ?>" class="w-[130px] sm:w-full flex-shrink-0">
                                            <img src="<?= $grid_image ?>" alt="<?= htmlspecialchars($grid_post['title'] ?? '') ?>" class="w-[130px] sm:w-full h-[86px] sm:h-40 object-cover mb-3">
                                </a>
                                <div class="flex-1 flex flex-col">
                                            <div class="text-xs font-bold mb-2 text-[#2d67ad] order-2 sm:order-1 sm:hidden">
                                                <?= $category_name ?>
                            </div>
                                    <h3 class="text-base font-bold text-gray-900 leading-tight mb-3 order-1 sm:order-2">
                                                <a href="<?= link_single($grid_post['slug'], $grid_post['posttype'] ?? 'posts') ?>" title="<?= htmlspecialchars($grid_post['title'] ?? '') ?>" class="hover:text-[#2d67ad]">
                                                    <?= htmlspecialchars($grid_post['title'] ?? 'No Title') ?>
                                        </a>
                                    </h3>
                                </div>
                            </div>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        <div class="max-w-7xl mx-auto border-t border-gray-200 mt-4 sm:mt-6 pt-4 sm:pt-5">
                            <div class="space-y-4 sm:space-y-6">
                                <?php for ($i = 6; $i < count($home_posts); $i++): ?>
                                    <?php if (isset($home_posts[$i])): ?>
                                        <?php 
                                        $list_post = $home_posts[$i];
                                        
                                        // Lấy ảnh đại diện
                                        $list_image = '';
                                        if (!empty($list_post['feature'])) {
                                            $feature = is_string($list_post['feature']) ? json_decode($list_post['feature'], true) : $list_post['feature'];
                                            if (is_array($feature) && !empty($feature['path'])) {
                                                $list_image = '/uploads/' . $feature['path'];
                                            }
                                        }
                                        
                                        // Fallback image nếu không có ảnh
                                        if (empty($list_image)) {
                                            $list_image = 'https://static-images.vnncdn.net/vps_images_publish/000001/00000Q/2025/9/17/two-tiktokers-arrested-in-hcm-city-for-staging-offensive-livestreams-3b53f60a389a464aa7b50d307a31d81a-94.png?width=360&s=HzyBbElIc-cePtrOPTt0VQ';
                                        }
                                        
                                        // Lấy danh mục từ dữ liệu đã có
                                        $category_name = 'VIETNAMNET GLOBAL';
                                        
                                        // Thử nhiều cách lấy danh mục
                                        if (!empty($list_post['categories']) && is_array($list_post['categories'])) {
                                            $category_name = strtoupper($list_post['categories'][0]['name'] ?? 'POSTS');
                                        } elseif (!empty($list_post['category_name'])) {
                                            $category_name = strtoupper($list_post['category_name']);
                                        } elseif (!empty($list_post['category'])) {
                                            $category_name = strtoupper($list_post['category']);
                                        } elseif (!empty($list_post['term_name'])) {
                                            $category_name = strtoupper($list_post['term_name']);
                                        } elseif (!empty($list_post['name'])) {
                                            $category_name = strtoupper($list_post['name']);
                                        }
                                        ?>
                                <div class="flex gap-4 pb-6 border-b border-gray-200 last:border-b-0 last:pb-0">
                                    <div class="w-[130px] sm:w-[240px] h-[86px] sm:h-40 flex-shrink-0">
                                                <a href="<?= link_single($list_post['slug'], $list_post['posttype'] ?? 'posts') ?>" title="<?= htmlspecialchars($list_post['title'] ?? '') ?>">
                                                    <img src="<?= $list_image ?>" alt="<?= htmlspecialchars($list_post['title'] ?? '') ?>" class="w-[130px] sm:w-[240px] h-[86px] sm:h-40 object-cover">
                                        </a>
                                    </div>
                                    <div class="flex-1 flex flex-col">
                                                <div class="text-xs sm:text-sm font-bold mb-2 text-[#2d67ad] order-2 sm:order-1">
                                                    <?= $category_name ?>
                                    </div>
                                        <h3 class="text-base font-bold text-gray-900 mb-3 leading-tight order-1 sm:order-2">
                                                    <a href="<?= link_single($list_post['slug'], $list_post['posttype'] ?? 'posts') ?>" title="<?= htmlspecialchars($list_post['title'] ?? '') ?>" class="hover:text-[#2d67ad]">
                                                        <?= htmlspecialchars($list_post['title'] ?? 'No Title') ?>
                                            </a>
                                        </h3>
                                        <p class="hidden sm:block text-gray-600 leading-relaxed order-3">
                                                    <?= htmlspecialchars($list_post['description'] ?? $list_post['excerpt'] ?? 'No description available') ?>
                                        </p>
                                    </div>
                                </div>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                    <!-- Pagination - Mobile -->
                    <?php if ($total_pages > 1): ?>
                    <div class="sm:hidden flex justify-center items-center w-full mt-5 mb-10">
                        <div class="flex items-center space-x-1">
                            <?php if ($current_page > 1): ?>
                            <a href="<?= $current_page == 2 ? '/' : '/?page=' . ($current_page - 1) ?>" class="w-10 h-10 bg-white text-gray-700 border border-gray-300 rounded-md flex items-center justify-center text-sm font-medium hover:bg-gray-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </a>
                            <?php endif; ?>
                            
                            <!-- Page numbers for mobile -->
                            <?php
                            $start_page = max(1, $current_page - 1);
                            $end_page = min($total_pages, $current_page + 1);
                            
                            // Show first page if not in range
                            if ($start_page > 1):
                            ?>
                            <a href="/" class="w-10 h-10 bg-white text-gray-700 border border-gray-300 rounded-md flex items-center justify-center text-sm font-medium hover:bg-gray-50 transition-colors">
                                1
                            </a>
                            <?php if ($start_page > 2): ?>
                            <span class="px-2 text-gray-500">...</span>
                            <?php endif; ?>
                            <?php endif; ?>

                            <?php
                            // Show page numbers in range
                            for ($i = $start_page; $i <= $end_page; $i++):
                                $page_url = $i == 1 ? '/' : '/?page=' . $i;
                                $is_active = $i == $current_page;
                            ?>
                            <a href="<?= $page_url ?>" class="w-10 h-10 <?= $is_active ? 'bg-[#2d67ad] text-white' : 'bg-white text-gray-700 border border-gray-300' ?> rounded-md flex items-center justify-center text-sm font-medium hover:bg-gray-50 transition-colors">
                                <?= $i ?>
                            </a>
                            <?php endfor; ?>

                            <?php
                            // Show last page if not in range
                            if ($end_page < $total_pages):
                            ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                            <span class="px-2 text-gray-500">...</span>
                            <?php endif; ?>
                            <a href="/?page=<?= $total_pages ?>" class="w-10 h-10 bg-white text-gray-700 border border-gray-300 rounded-md flex items-center justify-center text-sm font-medium hover:bg-gray-50 transition-colors">
                                <?= $total_pages ?>
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($current_page < $total_pages): ?>
                            <a href="/?page=<?= $current_page + 1 ?>" class="w-10 h-10 bg-white text-gray-700 border border-gray-300 rounded-md flex items-center justify-center text-sm font-medium hover:bg-gray-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <!-- Right Sidebar - TOP STORIES -->
                    <div class="w-full lg:w-[300px] flex-shrink-0">
                        <!-- TOP STORIES -->
                        <div class="bg-white border border-gray-200 p-6 mb-6">
                            <h3 class="text-lg font-bold  mb-4 text-center text-[#2d67ad]">TOP STORIES</h3>
                            <?php
                            // Lấy top stories - bài viết có lượt xem cao nhất
                            $top_stories = get_posts([
                                'posttype' => 'posts',
                                'filters' => [
                                    'status' => 'active'
                                ],
                                'perPage' => 5,
                                'sort' => ['views', 'DESC'],
                                'withCategories' => true,
                                'withTags' => true


                            ]);
                            
                            // Lấy dữ liệu từ key 'data' nếu có
                            if (isset($top_stories['data']) && is_array($top_stories['data'])) {
                                $top_stories = $top_stories['data'];
                            } else {
                                $top_stories = [];
                            }
                            ?>
                            
                            <?php if (!empty($top_stories) && is_array($top_stories)): ?>
                                <ul class="space-y-3">
                                    <?php foreach ($top_stories as $story): ?>
                                        <?php if (isset($story['title']) && isset($story['slug'])): ?>
                                            <li class="border-b border-gray-100 pb-3 last:border-b-0 last:pb-0">
                                                <a href="<?= link_single($story['slug'], $story['posttype'] ?? 'posts') ?>" 
                                                   class="text-sm text-gray-700 hover:text-[#2d67ad] leading-relaxed"
                                                   title="<?= htmlspecialchars($story['title']) ?>">
                                                    <?= htmlspecialchars($story['title']) ?>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="text-center py-4 text-gray-500 text-sm">
                                    <p>No top stories available</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>


        <!-- Pagination - Desktop -->
        <?php if ($total_pages > 1): ?>
        <div class="hidden sm:flex justify-center items-center w-full mt-5 mb-10">
            <div class="flex items-center space-x-2">
                <?php
                // Previous button
                if ($current_page > 1):
                    $prev_page = $current_page - 1;
                    $prev_url = $prev_page == 1 ? '/' : '/?page=' . $prev_page;
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
                
                // Show first page if not in range
                if ($start_page > 1):
                ?>
                <a href="/" class="w-10 h-10 bg-white text-gray-700 border border-gray-300 rounded-md flex items-center justify-center text-sm font-medium hover:bg-gray-50 transition-colors">
                    1
                </a>
                <?php if ($start_page > 2): ?>
                <span class="w-10 h-10 flex items-center justify-center text-gray-500">...</span>
                <?php endif; ?>
                <?php endif; ?>

                <?php
                // Show page numbers in range
                for ($i = $start_page; $i <= $end_page; $i++):
                    $page_url = $i == 1 ? '/' : '/?page=' . $i;
                    $is_active = $i == $current_page;
                ?>
                <a href="<?= $page_url ?>" class="w-10 h-10 <?= $is_active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300' ?> rounded-md flex items-center justify-center text-sm font-medium hover:bg-gray-50 transition-colors">
                    <?= $i ?>
                </a>
                <?php endfor; ?>

                <?php
                // Show last page if not in range
                if ($end_page < $total_pages):
                ?>
                <?php if ($end_page < $total_pages - 1): ?>
                <span class="w-10 h-10 flex items-center justify-center text-gray-500">...</span>
                <?php endif; ?>
                <a href="/?page=<?= $total_pages ?>" class="w-10 h-10 bg-white text-gray-700 border border-gray-300 rounded-md flex items-center justify-center text-sm font-medium hover:bg-gray-50 transition-colors">
                    <?= $total_pages ?>
                </a>
                <?php endif; ?>

                <?php
                // Next button
                if ($current_page < $total_pages):
                    $next_page = $current_page + 1;
                    $next_url = '/?page=' . $next_page;
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



