<?php
// Lấy thông tin taxonomy từ URL
$uri = $_SERVER['REQUEST_URI'] ?? '';
$path = parse_url($uri, PHP_URL_PATH);
$segments = explode('/', trim($path, '/'));
$segments = array_filter($segments);

$current_page = [
    'uri' => $uri,
    'path' => $path,
    'segments' => array_values($segments),
    'is_home' => empty($segments),
    'page_type' => empty($segments) ? 'home' : $segments[0],
    'page_slug' => end($segments) ?: '',
];

// var_dump($current_page);
$taxonomy_title = __('Vietnamnet cat'); // Default title
$taxonomy_url = '/en'; // Default URL

// Lấy trang hiện tại từ URL
$page_num = (int)(S_GET('page', 1));
$per_page = 10;

if (!empty($current_page['segments']) && count($current_page['segments']) >= 3) {
    $posttype = $current_page['segments'][0];
    $taxonomy = $current_page['segments'][1];
    $term_slug = $current_page['segments'][2];
    
    // Lấy thông tin term bằng hàm get_term()
    $term = get_term($term_slug, $posttype, $taxonomy, APP_LANG);
    
    if ($term && !empty($term['name'])) {
        $taxonomy_title = $term['name'];
        $taxonomy_url = link_cat($term_slug, $posttype);
    }
}

// Lấy bài viết theo danh mục với pagination
$taxonomy_posts = [];
$total_posts = 0;
$total_pages = 1;

if (!empty($term) && !empty($term['id'])) {
    try {
        // Lấy post_ids từ bảng fast_posts_posts_rel với rel_type = 'term'
        $qb = (new \App\Models\FastModel('fast_posts_posts_rel'))
            ->newQuery()
            ->where('rel_id', '=', $term['id'])
            ->where('rel_type', '=', 'term')
            ->where('lang', '=', APP_LANG)
            ->orderBy('created_at', 'DESC');
        
        $relations = $qb->get();
        
        if ($relations && !empty($relations)) {
            // Lấy post_ids từ relations
            $post_ids = [];
            foreach ($relations as $relation) {
                if (!empty($relation['post_id'])) {
                    $post_ids[] = $relation['post_id'];
                }
            }
            
            if (!empty($post_ids)) {
                $total_posts = count($post_ids);
                $total_pages = ceil($total_posts / $per_page);
                
                // Tính offset cho pagination
                $offset = ($page_num - 1) * $per_page;
                
                // Lấy bài viết theo post_ids với pagination
                $table_name = posttype_name($posttype ?? 'posts');
                if (!empty($table_name)) {
                    $qb_posts = (new \App\Models\FastModel($table_name))
                        ->newQuery()
                        ->where('status', '=', 'active')
                        ->whereIn('id', $post_ids)
                        ->orderBy('created_at', 'DESC')
                        ->limit($per_page)
                        ->offset($offset);
                    
                    $taxonomy_posts = $qb_posts->get();
                }
            }
        }
    } catch (Exception $e) {
        error_log('Error getting posts by category: ' . $e->getMessage());
    }
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
                            <h1 class="text-2xl font-bold text-sky-600" style=" font-family: notosans-bold; font-size: 32px;">
                               <?= htmlspecialchars($taxonomy_title) ?>
                            </h1>
                        </div>
                    </div>

                    <!-- Right Side - Search Form -->
                    <div class="search-small">
                        <form class="search-small__form relative" action="/search/">
                            <input class="search-small__form-input w-60 h-[28px] px-4 pr-10 border border-gray-300 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" name="q" type="text" placeholder="Type keywords....">
                            <button class="search-small__form-btn absolute right-2 top-1/2 transform -translate-y-1/2 w-6 h-6 flex items-center justify-center hover:bg-gray-100 rounded-full transition-colors" type="submit">
                                <?= _img(theme_assets('images/search.png'), 'icon search', false, 'w-4 h-4') ?>
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
                                <?php if (!empty($taxonomy_posts) && isset($taxonomy_posts[0])): ?>
                                    <?php 
                                    $featured_post = $taxonomy_posts[0];
                                    
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
                                        $featured_image = 'https://static-images.vnncdn.net/vps_images_publish/000001/00000Q/2025/9/17/free-checkups-hospital-care-by-2030-vietnams-bold-new-healthcare-vision-551ef314a29b4800b5625e3e87070fd7-44.jpg?width=550&s=H7rye5Abl2PKSon-r0oqtQ';
                                    }
                                    ?>
                                    <a href="<?= link_single($featured_post['slug'], $featured_post['posttype'] ?? 'posts') ?>" title="<?= htmlspecialchars($featured_post['title'] ?? '') ?>">
                                        <?= _img($featured_image, htmlspecialchars($featured_post['title'] ?? ''), false, 'w-full h-48 sm:h-64 md:h-80 object-cover mb-4') ?>
                                </a>
                                <h3 class="text-lg sm:text-xl md:text-2xl font-bold text-gray-900 mb-3 leading-tight">
                                        <a href="<?= link_single($featured_post['slug'], $featured_post['posttype'] ?? 'posts') ?>" title="<?= htmlspecialchars($featured_post['title'] ?? '') ?>" class="hover:text-sky-600">
                                            <?= htmlspecialchars($featured_post['title'] ?? 'No Title') ?>
                                    </a>
                                </h3>
                                <p class="text-sm sm:text-base text-gray-700 leading-relaxed">
                                        <?= htmlspecialchars($featured_post['description'] ?? $featured_post['excerpt'] ?? 'No description available') ?>
                                    </p>
                                <?php else: ?>
                                    <!-- Fallback nếu không có bài viết -->
                                    <p class="text-gray-500">No articles available in this category</p>

                                <?php endif; ?>
                            </div>

                            <!-- Two Stacked Articles -->
                            <div class="w-full md:w-[240px] flex-shrink-0 space-y-4 sm:space-y-6">
                                <?php for ($i = 1; $i <= 2; $i++): ?>
                                    <?php if (!empty($taxonomy_posts) && isset($taxonomy_posts[$i])): ?>
                                        <?php 
                                        $side_post = $taxonomy_posts[$i];
                                        
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
                                        $category_name = strtoupper($taxonomy_title);
                                        if (!empty($side_post['categories']) && is_array($side_post['categories'])) {
                                            $category_name = strtoupper($side_post['categories'][0]['name'] ?? 'POSTS');
                                        } elseif (!empty($side_post['category_name'])) {
                                            $category_name = strtoupper($side_post['category_name']);
                                        } elseif (!empty($side_post['category'])) {
                                            $category_name = strtoupper($side_post['category']);
                                        }
                                        ?>
                                <div class="flex gap-4 sm:block pb-4 sm:pb-6 border-b sm:border-b-0 border-gray-200 last:border-b-0 last:pb-0">
                                            <a href="<?= link_single($side_post['slug'], $side_post['posttype'] ?? 'posts') ?>" title="<?= htmlspecialchars($side_post['title'] ?? '') ?>" class="w-[130px] sm:w-full flex-shrink-0">
                                                <?= _img($side_image, htmlspecialchars($side_post['title'] ?? ''), false, 'w-[130px] sm:w-full h-[86px] sm:h-40 object-cover mb-3') ?>
                                            </a>
                                            <div class="flex-1 flex flex-col">
                                                <div class="text-xs font-bold mb-2 text-sky-600 order-2 sm:order-1 sm:hidden">
                                                    <?= $category_name ?>
                                </div>
                                        <h3 class="text-base font-bold text-gray-900 leading-tight mb-3 order-1 sm:order-2">
                                                    <a href="<?= link_single($side_post['slug'], $side_post['posttype'] ?? 'posts') ?>" title="<?= htmlspecialchars($side_post['title'] ?? '') ?>" class="hover:text-sky-600">
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
                                <?php if (!empty($taxonomy_posts) && isset($taxonomy_posts[$i])): ?>
                                    <?php 
                                    $grid_post = $taxonomy_posts[$i];
                                    
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
                                    $category_name = strtoupper($taxonomy_title);
                                    if (!empty($grid_post['categories']) && is_array($grid_post['categories'])) {
                                        $category_name = strtoupper($grid_post['categories'][0]['name'] ?? 'POSTS');
                                    } elseif (!empty($grid_post['category_name'])) {
                                        $category_name = strtoupper($grid_post['category_name']);
                                    } elseif (!empty($grid_post['category'])) {
                                        $category_name = strtoupper($grid_post['category']);
                                    }
                                    ?>
                            <div class="flex gap-4 sm:block pb-4 sm:pb-6 border-b sm:border-b-0 border-gray-200 last:border-b-0 last:pb-0">
                                        <a href="<?= link_single($grid_post['slug'], $grid_post['posttype'] ?? 'posts') ?>" title="<?= htmlspecialchars($grid_post['title'] ?? '') ?>" class="w-[130px] sm:w-full flex-shrink-0">
                                            <?= _img($grid_image, htmlspecialchars($grid_post['title'] ?? ''), false, 'w-[130px] sm:w-full h-[86px] sm:h-40 object-cover mb-3') ?>
                                </a>
                                <div class="flex-1 flex flex-col">
                                            <div class="text-xs font-bold mb-2 text-sky-600 order-2 sm:order-1 sm:hidden">
                                                <?= $category_name ?>
                            </div>
                                    <h3 class="text-base font-bold text-gray-900 leading-tight mb-3 order-1 sm:order-2">
                                                <a href="<?= link_single($grid_post['slug'], $grid_post['posttype'] ?? 'posts') ?>" title="<?= htmlspecialchars($grid_post['title'] ?? '') ?>" class="hover:text-sky-600">
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
                                <?php for ($i = 6; $i < count($taxonomy_posts); $i++): ?>
                                    <?php if (isset($taxonomy_posts[$i])): ?>
                                        <?php 
                                        $list_post = $taxonomy_posts[$i];
                                        
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
                                        $category_name = strtoupper($taxonomy_title);
                                        if (!empty($list_post['categories']) && is_array($list_post['categories'])) {
                                            $category_name = strtoupper($list_post['categories'][0]['name'] ?? 'POSTS');
                                        } elseif (!empty($list_post['category_name'])) {
                                            $category_name = strtoupper($list_post['category_name']);
                                        } elseif (!empty($list_post['category'])) {
                                            $category_name = strtoupper($list_post['category']);
                                        }
                                        ?>
                                <div class="flex gap-4 pb-6 border-b border-gray-200 last:border-b-0 last:pb-0">
                                    <div class="w-[130px] sm:w-[240px] h-[86px] sm:h-40 flex-shrink-0">
                                                <a href="<?= link_single($list_post['slug'], $list_post['posttype'] ?? 'posts') ?>" title="<?= htmlspecialchars($list_post['title'] ?? '') ?>">
                                                    <?= _img($list_image, htmlspecialchars($list_post['title'] ?? ''), false, 'w-[130px] sm:w-[240px] h-[86px] sm:h-40 object-cover') ?>
                                        </a>
                                    </div>
                                    <div class="flex-1 flex flex-col">
                                                <div class="text-xs sm:text-sm font-bold mb-2 text-sky-600 order-2 sm:order-1">
                                                    <?= $category_name ?>
                                    </div>
                                        <h3 class="text-base font-bold text-gray-900 mb-3 leading-tight order-1 sm:order-2">
                                                    <a href="<?= link_single($list_post['slug'], $list_post['posttype'] ?? 'posts') ?>" title="<?= htmlspecialchars($list_post['title'] ?? '') ?>" class="hover:text-sky-600">
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
                            <?php if ($page_num > 1): ?>
                            <a href="<?= $page_num == 2 ? $taxonomy_url : $taxonomy_url . '?page=' . ($page_num - 1) ?>" class="w-10 h-10 bg-white text-gray-700 border border-gray-300 rounded-md flex items-center justify-center text-sm font-medium hover:bg-gray-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </a>
                            <?php endif; ?>
                            
                            <!-- Page numbers for mobile -->
                            <?php
                            $start_page = max(1, $page_num - 1);
                            $end_page = min($total_pages, $page_num + 1);
                            
                            // Show first page if not in range
                            if ($start_page > 1):
                            ?>
                            <a href="<?= $taxonomy_url ?>" class="w-10 h-10 bg-white text-gray-700 border border-gray-300 rounded-md flex items-center justify-center text-sm font-medium hover:bg-gray-50 transition-colors">
                                1
                            </a>
                            <?php if ($start_page > 2): ?>
                            <span class="px-2 text-gray-500">...</span>
                            <?php endif; ?>
                            <?php endif; ?>

                            <?php
                            // Show page numbers in range
                            for ($i = $start_page; $i <= $end_page; $i++):
                                $page_url = $i == 1 ? $taxonomy_url : $taxonomy_url . '?page=' . $i;
                                $is_active = $i == $page_num;
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
                            <a href="<?= $taxonomy_url ?>?page=<?= $total_pages ?>" class="w-10 h-10 bg-white text-gray-700 border border-gray-300 rounded-md flex items-center justify-center text-sm font-medium hover:bg-gray-50 transition-colors">
                                <?= $total_pages ?>
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($page_num < $total_pages): ?>
                            <a href="<?= $taxonomy_url ?>?page=<?= $page_num + 1 ?>" class="w-10 h-10 bg-white text-gray-700 border border-gray-300 rounded-md flex items-center justify-center text-sm font-medium hover:bg-gray-50 transition-colors">
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
                            <h3 class="text-lg font-bold  mb-4 text-center text-sky-600">TOP STORIES</h3>
                            <?php
                            // Lấy top stories - bài viết có lượt xem cao nhất
                            $top_stories = get_posts([
                                'posttype' => 'posts',
                                'filters' => [
                                    'status' => 'active'
                                ],
                                'perPage' => 5,
                                'sort' => ['views', 'DESC']
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
                                                   class="text-sm text-gray-700 hover:text-sky-600 leading-relaxed"
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
                if ($page_num > 1):
                    $prev_page = $page_num - 1;
                    $prev_url = $prev_page == 1 ? $taxonomy_url : $taxonomy_url . '?page=' . $prev_page;
                ?>
                <a href="<?= $prev_url ?>" class="w-10 h-10 bg-white text-gray-700 border border-gray-300 rounded-md flex items-center justify-center text-sm font-medium hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <?php endif; ?>

                <?php
                // Page numbers
                $start_page = max(1, $page_num - 2);
                $end_page = min($total_pages, $page_num + 2);
                
                // Show first page if not in range
                if ($start_page > 1):
                ?>
                <a href="<?= $taxonomy_url ?>" class="w-10 h-10 bg-white text-gray-700 border border-gray-300 rounded-md flex items-center justify-center text-sm font-medium hover:bg-gray-50 transition-colors">
                    1
                </a>
                <?php if ($start_page > 2): ?>
                <span class="w-10 h-10 flex items-center justify-center text-gray-500">...</span>
                <?php endif; ?>
                <?php endif; ?>

                <?php
                // Show page numbers in range
                for ($i = $start_page; $i <= $end_page; $i++):
                    $page_url = $i == 1 ? $taxonomy_url : $taxonomy_url . '?page=' . $i;
                    $is_active = $i == $page_num;
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
                <span class="w-10 h-10 flex items-center justify-center text-gray-500">...</span>
                <?php endif; ?>
                <a href="<?= $taxonomy_url ?>?page=<?= $total_pages ?>" class="w-10 h-10 bg-white text-gray-700 border border-gray-300 rounded-md flex items-center justify-center text-sm font-medium hover:bg-gray-50 transition-colors">
                    <?= $total_pages ?>
                </a>
                <?php endif; ?>

                <?php
                // Next button
                if ($page_num < $total_pages):
                    $next_page = $page_num + 1;
                    $next_url = $taxonomy_url . '?page=' . $next_page;
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



