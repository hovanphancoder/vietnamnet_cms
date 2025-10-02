<?php
App\Libraries\Fastlang::load('Homepage');
System\Libraries\Render::asset('css', 'css/author.css', ['area' => 'frontend', 'location' => 'head']);
//Render::asset('js', 'js/home-index.js', ['area' => 'frontend', 'location' => 'footer']);

// Include UsersModel
use App\Models\UsersModel;
// ekhjfsek
//Get Object Data for this Pages
$locale = APP_LANG.'_'.strtoupper(lang_country(APP_LANG));

get_template('_metas/meta_all', ['locale' => $locale]);

$current_page = (int)(S_GET('page', 1));
// Lấy author_id từ URL
$uri = $_SERVER['REQUEST_URI'];
$uri_parts = explode('/', trim($uri, '/'));
$author_id = isset($uri_parts[1]) ? $uri_parts[1] : null;

$author_info = null;
if (!empty($author_id)) {
    // Thử lấy theo ID số
    if (is_numeric($author_id)) {
        $author_info = getAuthor((int)$author_id);
    } else {
        // Thử lấy theo username - cần query khác
        try {
            $usersModel = new UsersModel();
            $author_info = $usersModel->where('username', $author_id)->first();
        } catch (Exception $e) {
            error_log("Error getting author: " . $e->getMessage());
        }
    }
}

// Nếu không tìm thấy tác giả, redirect về trang chủ
if (!$author_info) {
    header('Location: /');
    exit;
}
?>

<div class="max-w-7xl mx-auto">
            <div class="main bg-white mx-auto">
                <div class=" flex flex-col lg:flex-row gap-0 lg:gap-10">
                    <!-- Left Column -->
                    <div class="container__left flex-1 w-full lg:w-[760px]">
                        <!-- Breadcrumb -->
                        <div class="bread-crumb-detail flex items-center justify-between mb-5 pt-5 lg:pt-0">
                            <ul class="flex items-center space-x-2 text-sm">
                                <li class="hidden lg:hidden">
                                    <a href="<?= base_url() ?>" class="flex items-center">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill="#2d67ad" d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                                        </svg>
                                    </a>
                                </li>
                                <li class="text-gray-500 hidden lg:hidden">></li>
                                <li>
                                    <a href="<?= link_authors() ?>" title="Author" class="text-sky-600 font-bold text-[14px] lg:text-[16px]  uppercase">
                                        Author
                                    </a>
                                </li>
                                <li class="text-gray-400">></li>
                                <li>
                                    <a href="<?= link_author($author_info['username']) ?>" title="<?= htmlspecialchars($author_info['fullname'] ?? $author_info['username']) ?>" class="text-[#868686] text-[14px] uppercase">
                                        <?= htmlspecialchars($author_info['fullname'] ?? $author_info['username']) ?>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <!-- Author Info -->
                        <div class="info-author mb-2.5 px-0 sm:px-4">
                            <div class="flex flex-row items-center ">
                                <div class="info-author__image float-left mb-5 sm:mb-0 mr-5 relative">
                                    <?php if (!empty($author_info['avatar'])): ?>
                                        <img src="<?= htmlspecialchars($author_info['avatar']) ?>" 
                                             alt="<?= htmlspecialchars($author_info['fullname'] ?? $author_info['username']) ?>" 
                                             class="w-[100px] h-[100px] lg:w-[170px] lg:h-[170px] rounded-full object-cover block"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="w-[100px] h-[100px] lg:w-[170px] lg:h-[170px] rounded-full bg-[#2d67ad] flex items-center justify-center text-white text-4xl lg:text-6xl font-bold hidden">
                                            <?= strtoupper(substr($author_info['fullname'] ?? $author_info['username'], 0, 1)) ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="w-[100px] h-[100px] lg:w-[170px] lg:h-[170px] rounded-full bg-[#2d67ad] flex items-center justify-center text-white text-4xl lg:text-6xl font-bold">
                                        <img src="<?= theme_assets('images/default-avatar.png') ?>" >
                                        </div>
                                    <?php endif; ?>

                                    <!-- <button class="absolute -bottom-3 left-1/2 transform -translate-x-1/2 bg-gray-100 border border-[#ccc] rounded-[10px] text-gray-800 cursor-pointer h-6 px-2 z-100 flex items-center text-[12px]  justify-center transition-colors">
                                        <span class="icon w-3 h-3 mr-2 flex items-center justify-center">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
                                            </svg>
                                        </span>
                                        <span class="text text-xs font-medium">Follow</span>
                                    </button> -->
                                </div>

                                <div class="info-author__text flex-1">
                                    <h1 class="info-author__name text-[18px] lg:text-[20px]  font-bold text-sky-600 mb-2" title="<?= htmlspecialchars($author_info['fullname'] ?? $author_info['username']) ?>">
                                        <?= htmlspecialchars($author_info['fullname'] ?? $author_info['username']) ?>
                                    </h1>

                                  

                                    <div class="info-author__desc">
                                        <?php if (!empty($author_info['about_me'])): ?>
                                            <p class="text-gray-700 text-sm leading-relaxed">
                                                <?= htmlspecialchars($author_info['about_me']) ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Article Author Heading -->
                        <div class="article-author flex justify-between items-center border-b border-vnn-light-gray mb-5 pb-1.5 sm:px-4">
                            <div class="text-[20px] font-bold mt-4">
                                <?= countAuthorThemesPlugins('posts', $author_info['id']) ?> bài viết của tác giả <?= htmlspecialchars($author_info['fullname'] ?? $author_info['username']) ?>
                            </div>
                        </div>

                        <!-- Articles List -->
                        <?php
                        // Lấy danh sách bài viết của tác giả
                        $per_page = 10; 
                        $author_posts = get_posts([
                            'posttype' => 'posts',
                            'filters' => [
                                'author' => $author_info['id'],
                                'status' => 'active'
                            ],
                            'perPage' => $per_page,
                            'paged' => $current_page,
                            'withCategories' => true,
                            'totalpage' => true,
                            'sort' => ['created_at', 'DESC']
                        ]);
                      
                        // Nếu không có kết quả, thử lấy tất cả bài viết để test
                        if (empty($author_posts) || (isset($author_posts['data']) && empty($author_posts['data']))) {
                            echo "<!-- Debug: No posts found with author filter, trying all posts -->";
                            $test_posts = get_posts([
                                'posttype' => 'posts',
                                'perPage' => 5,
                                'sort' => ['created_at', 'DESC']
                            ]);
                            echo "<!-- Debug: All posts count = " . (isset($test_posts['data']) ? count($test_posts['data']) : 'No data key') . " -->";
                        }
                        
                        // Lấy dữ liệu từ key 'data' và thông tin phân trang
                        if (isset($author_posts['data']) && is_array($author_posts['data'])) {
                            $posts_data = $author_posts['data'];
                            $total_pages = $author_posts['last_page'] ?? 1;
                            $total_posts = $author_posts['total'] ?? count($posts_data);
                        } else {
                            $posts_data = [];
                            $total_pages = 1;
                            $total_posts = 0;
                        }
                        
                        // Gán lại để sử dụng trong vòng lặp
                        $author_posts = $posts_data;
                        
                     
                      
                        ?>
                        
                        <div class="space-y-4 sm:space-y-6">
                            <?php if (!empty($author_posts)): ?>
                                <?php foreach ($author_posts as $post): ?>
                                <div class="flex gap-4">
                                <div class="w-[130px] sm:w-[240px] h-[86px] sm:h-40 flex-shrink-0">
                                        <a href="<?= link_single($post['slug'], $post['posttype'] ?? 'posts') ?>" title="<?= htmlspecialchars($post['title']) ?>">
                                            <?php 
                                            // Xử lý hình ảnh - kiểm tra nhiều trường
                                            $image_url = '';
                                            
                                            // Kiểm tra trường 'feature' chứa JSON với path
                                            if (!empty($post['feature']) && is_string($post['feature'])) {
                                                $feature_data = json_decode($post['feature'], true);
                                                if (is_array($feature_data)) {
                                                    if (!empty($feature_data['path'])) {
                                                        $image_url = '/uploads/' . $feature_data['path'];
                                                    } elseif (!empty($feature_data['name'])) {
                                                        $image_url = '/uploads/' . $feature_data['name'];
                                                    }
                                                }
                                            }
                                            
                                            // Kiểm tra các trường ảnh đại diện khác nếu chưa có
                                            if (empty($image_url)) {
                                                $featured_image_fields = ['featured_image', 'thumbnail', 'image', 'cover_image', 'post_image'];
                                                foreach ($featured_image_fields as $field) {
                                                    if (!empty($post[$field]) && is_string($post[$field])) {
                                                        $image_url = $post[$field];
                                                        break;
                                                    }
                                                }
                                            }
                                            
                                            // Nếu vẫn không có, kiểm tra trong metadata cho ảnh đại diện
                                            if (empty($image_url) && !empty($post['metadata'])) {
                                                $metadata = is_string($post['metadata']) ? json_decode($post['metadata'], true) : $post['metadata'];
                                                if (is_array($metadata)) {
                                                    foreach ($featured_image_fields as $field) {
                                                        if (!empty($metadata[$field])) {
                                                            $image_url = $metadata[$field];
                                                            break;
                                                        }
                                                    }
                                                }
                                            }
                                            
                                            // Kiểm tra trong custom fields hoặc options
                                            if (empty($image_url)) {
                                                $custom_fields = ['_thumbnail_id', '_featured_image', 'post_thumbnail', 'main_image'];
                                                foreach ($custom_fields as $field) {
                                                    if (!empty($post[$field]) && is_string($post[$field])) {
                                                        $image_url = $post[$field];
                                                        break;
                                                    }
                                                }
                                            }
                                            
                                            // Xử lý trường hợp ảnh đại diện là ID thay vì URL
                                            if (!empty($image_url) && is_numeric($image_url)) {
                                                try {
                                                    $fileModel = new \App\Models\FastModel('fast_files');
                                                    $file = $fileModel->where('id', $image_url)->first();
                                                    if ($file && !empty($file['url'])) {
                                                        $image_url = $file['url'];
                                                    } else {
                                                        $image_url = '';
                                                    }
                                                } catch (Exception $e) {
                                                    $image_url = '';
                                                }
                                            }
                                            
                                            if (!empty($image_url)): ?>
                                                <img src="<?= htmlspecialchars($image_url) ?>" 
                                                     alt="<?= htmlspecialchars($post['title']) ?>" 
                                                     class="w-[130px] sm:w-[240px] h-[86px] sm:h-40 object-cover"
                                                     onerror="console.log('Image failed to load:', this.src); this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                <div class="w-[130px] sm:w-[240px] h-[86px] sm:h-40 bg-gradient-to-br from-blue-100 to-indigo-200 flex items-center justify-center hidden">
                                                    <i class="fas fa-newspaper text-gray-400 text-2xl"></i>
                                                </div>
                                            <?php else: ?>
                                                <div class="w-[130px] sm:w-[240px] h-[86px] sm:h-40 bg-gradient-to-br from-blue-100 to-indigo-200 flex flex-col items-center justify-center text-center">
                                                    <i class="fas fa-newspaper text-gray-400 text-2xl mb-1"></i>
                                                    <span class="text-xs text-gray-500 font-medium">No Image</span>
                                                </div>
                                            <?php endif; ?>
                                    </a>
                                </div>
                                <div class="flex-1 flex flex-col">
                                        <div class="text-xs sm:text-sm font-bold mb-2 text-sky-600 order-2 sm:order-1">
                                            <?php 
                                            // Hiển thị danh mục - cải thiện logic
                                            $category_display = '';
                                            
                                            // Kiểm tra categories từ withCategories - chỉ lấy 1 danh mục đầu tiên
                                            if (!empty($post['categories']) && is_array($post['categories'])) {
                                                $first_category = '';
                                                foreach ($post['categories'] as $cat) {
                                                    if (is_array($cat) && !empty($cat['name'])) {
                                                        $first_category = $cat['name'];
                                                        break; // Chỉ lấy danh mục đầu tiên
                                                    } elseif (is_string($cat)) {
                                                        $first_category = $cat;
                                                        break; // Chỉ lấy danh mục đầu tiên
                                                    }
                                                }
                                                if (!empty($first_category)) {
                                                    $category_display = strtoupper($first_category);
                                                }
                                            }
                                            
                                            // Kiểm tra category_id hoặc category
                                            if (empty($category_display)) {
                                                if (!empty($post['category_id'])) {
                                                    // Có thể cần query để lấy tên category
                                                    $category_display = 'CATEGORY';
                                                } elseif (!empty($post['category'])) {
                                                    $category_display = strtoupper($post['category']);
                                                }
                                            }
                                            
                                            // Fallback về posttype
                                            if (empty($category_display)) {
                                                $category_display = strtoupper($post['posttype'] ?? 'POSTS');
                                            }
                                            
                                            echo $category_display;
                                            ?>
                                        </div>
                                    <h3 class="text-base font-bold text-gray-900 mb-3 leading-tight order-1 sm:order-2">
                                            <a href="<?= link_single($post['slug'], $post['posttype'] ?? 'posts') ?>" title="<?= htmlspecialchars($post['title']) ?>" class="hover:text-sky-600">
                                                <?= htmlspecialchars($post['title']) ?>
                                        </a>
                                    </h3>
                                        <?php if (!empty($post['description'])): ?>
                                    <p class="hidden sm:block text-gray-600 leading-relaxed order-3">
                                            <?= htmlspecialchars($post['description']) ?>
                                    </p>
                                        <?php endif; ?>
                                        
                                       
                            </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-12">
                                    <i class="fas fa-newspaper text-6xl text-gray-300 mb-4"></i>
                                    <h3 class="text-xl font-semibold text-gray-600 mb-2">Chưa có bài viết nào</h3>
                                    <p class="text-gray-500">Tác giả này chưa đăng bài viết nào.</p>
                                </div>
                            <?php endif; ?>
                            </div>

                        <!-- Pagination -->
                        <?php 
                        echo "<!-- Debug Pagination: Posts count = " . count($author_posts) . ", Total pages = $total_pages, Current page = $current_page, Total posts = $total_posts -->";
                        
                        // Hiển thị phân trang nếu có nhiều hơn 1 trang
                        $should_show_pagination = $total_pages > 1;
                        
                        if ($should_show_pagination): ?>
                        <div class="mt-8 flex justify-center">
                            <nav class="flex items-center space-x-2">
                                <?php if ($current_page > 1): ?>
                                    <a href="?page=<?= $current_page - 1 ?>" 
                                       class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php
                                // Tính toán số trang để hiển thị
                                $start_page = max(1, $current_page - 2);
                                $end_page = min($total_pages, $current_page + 2);
                                
                                // Đảm bảo hiển thị ít nhất 5 trang nếu có thể
                                if ($end_page - $start_page < 4) {
                                    if ($start_page == 1) {
                                        $end_page = min($total_pages, $start_page + 4);
                                    } else {
                                        $start_page = max(1, $end_page - 4);
                                    }
                                }
                                
                                for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <a href="?page=<?= $i ?>" 
                                       class="px-3 py-2 text-sm font-medium rounded-md <?= $i == $current_page ? 'bg-[#2d67ad] text-white' : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-50' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($current_page < $total_pages): ?>
                                    <a href="?page=<?= $current_page + 1 ?>" 
                                       class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </nav>
                        </div>
                        <?php endif; ?>




                    </div>

                    <!-- Right Column -->
                    <div class="container__right w-[300px] flex-shrink-0">
                        <!-- Featured Authors -->
                        <div class="featured-author border border-vnn-light-gray rounded-lg p-5 sticky top-16 hidden lg:block">
                            <div class="featured-author__heading mb-4 text-center uppercase">
                                <a href="<?= link_authors() ?>" class="text-vnn-dark-blue font-bold text-xl">
                                    Other Authors
                                </a>
                            </div>

                            <!-- <form action="/tac-gia" class="featured-author__form h-9.5 leading-7 mb-5 relative w-full h-[40px] max-w-66">
                                <input type="text" name="q" placeholder="Search by author name" class="bg-[#efefef] border-none rounded-lg text-vnn-gray font-normal text-sm h-full outline-none pl-2.5 pr-9 w-full">
                                <button type="submit" class="absolute right-2.5 top-1/2 transform -translate-y-1/2">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill="#2d67ad" d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                                    </svg>
                                </button>
                            </form> -->

                            <?php
                            // Lấy danh sách tác giả khác (loại trừ tác giả hiện tại)
                            $other_authors = [];
                            try {
                                $usersModel = new UsersModel();
                                $other_authors = $usersModel->newQuery()
                                    ->where('status', 'active')
                                    ->where('role', 'author')
                                    ->where('id', '!=', $author_info['id'])
                                    ->orderBy('created_at', 'DESC')
                                    ->limit(5)
                                    ->get();
                            } catch (Exception $e) {
                                error_log("Error getting other authors: " . $e->getMessage());
                            }
                            ?>
                            
                            <div class="space-y-4">
                                <?php if (!empty($other_authors)): ?>
                                    <?php foreach ($other_authors as $other_author): ?>
                                        <div class="featured-author__article flex items-center gap-3">
                                            <div class="featured-author__image relative overflow-hidden w-[88px] h-[88px] flex-shrink-0">
                                                <a href="<?= link_author($other_author['username'] ?? $other_author['id']) ?>" title="<?= htmlspecialchars($other_author['fullname'] ?? $other_author['username']) ?>">
                                                    <?php if (!empty($other_author['avatar'])): ?>
                                                        <img src="<?= htmlspecialchars($other_author['avatar']) ?>" 
                                                             alt="<?= htmlspecialchars($other_author['fullname'] ?? $other_author['username']) ?>" 
                                                             class="w-full h-full object-cover rounded-full"
                                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                        <div class="w-full h-full bg-[#2d67ad] rounded-full flex items-center justify-center text-white text-2xl font-bold hidden">
                                                            <?= strtoupper(substr($other_author['fullname'] ?? $other_author['username'], 0, 1)) ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="w-full h-full bg-[#2d67ad] rounded-full flex items-center justify-center text-white text-2xl font-bold">
                                                            <img src="<?= theme_assets('images/default-avatar.png') ?>" >
                                                        </div>
                                                    <?php endif; ?>
                                                </a>
                                            </div>
                                            <div class="flex-1">
                                                <div class="featured-author__name mb-1">
                                                    <a href="<?= link_author($other_author['username'] ?? $other_author['id']) ?>" class="text-gray-700 font-bold text-base ">
                                                        <?= htmlspecialchars($other_author['fullname'] ?? $other_author['username']) ?>
                                                    </a>
                                                </div>
                                                <?php if (!empty($other_author['about_me'])): ?>
                                                    <p class="text-xs text-gray-500 line-clamp-2">
                                                        <?= htmlspecialchars(substr($other_author['about_me'], 0, 60)) ?><?= strlen($other_author['about_me']) > 60 ? '...' : '' ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-4 text-gray-500 text-sm">
                                        <p>No other authors available</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        </div>


<?php get_footer(); ?>