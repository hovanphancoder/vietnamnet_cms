<?php
App\Libraries\Fastlang::load('Homepage');


// ===== LẤY DỮ LIỆU BLOG POSTS =====
// Lấy danh sách bài viết blog từ database (CHỈ 1 QUERY)
$posts_data = get_posts([
    'posttype' => 'news',            // Sử dụng posttype 'news'
    'perPage' => 12,                 // 12 bài mỗi trang
    'withCategories' => true,        // Lấy categories
    'sort' => ['created_at', 'DESC'], // Sắp xếp theo ngày tạo mới nhất
    'paged' => S_GET('page', 1),     // Trang hiện tại từ URL
    'lang' => APP_LANG               // Thêm check ngôn ngữ
]);

// Tách dữ liệu posts và pagination (từ 1 query duy nhất)
$posts = $posts_data['data'] ?? [];
$pagination = $posts_data['pagination'] ?? [];

// Debug: Hiển thị dữ liệu
// echo '<pre style="background: #f0f0f0; padding: 10px; margin: 10px; border: 1px solid #ccc;">'; 
// echo "Posts count: " . count($posts) . "\n";
// echo "Posts type: " . gettype($posts) . "\n";

//Get Object Data for this Pages
$locale = APP_LANG.'_'.strtoupper(lang_country(APP_LANG));

// Tạo mảng dữ liệu để truyền vào template
$meta_data = [
    'locale' => $locale,
    'page_title' => 'News - ' . option('site_title', APP_LANG),
    'page_description' => 'Latest news and updates - ' . option('site_description', APP_LANG),
    'page_type' => 'news',
    'posts_count' => count($posts),
    'current_lang' => APP_LANG,
    'site_name' => option('site_title', APP_LANG),
    'custom_data' => [
        'total_posts' => $posts_data['pagination']['total'] ?? 0,
        'current_page' => $posts_data['pagination']['current_page'] ?? 1,
        'total_pages' => $posts_data['pagination']['total_pages'] ?? 1
    ]
];

get_template('_metas/meta_page', $meta_data);

?>
        <!-- breadcrumb -->
        <section>
            <div class="container">
                <div id="breadcrumb" class="font-size__small color__gray truncate"><span><span><a class="color__gray" href="/">Home</a></span> / <span class="color__gray" aria-current="page">News</span></span></div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h1 class="font-size__larger">News</h1>
                </div>
                <div class="text-align__justify" style="font-size: 0.9em;">
                    <p>Coming to the Blog, you will have access to important articles and information about Games &amp; Apps. We provide the most detailed instructions, ensuring players understand the gameplay and special symbols. Besides, we also share tips and tricks from the experience of veteran gamers, to help you play more smoothly. In addition, the news about games is also regularly updated here. It can be said that if you find this Blog, you have found a secret book about games!</p>
                </div>
            </div>
        </section>

        <!-- list post -->
        <section>
            <div class="container">
                <div class="flex-container">
                    <?php if (!empty($posts)): ?>
                        <?php foreach ($posts as $index => $post): ?>
                            <div class="flex-item">
                                <article class="card has-shadow clickable">
                                    <div class="card-image">
                                        <a href="<?php echo (APP_LANG === APP_LANG_DF) ? '/news/' . ($post['slug'] ?? '') : page_url($post['slug'] ?? '', 'news'); ?>" aria-label="<?php echo htmlspecialchars($post['title'] ?? 'Untitled', ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php 
                                            // Lấy hình ảnh featured
                                            $featured_image = '';
                                            if (!empty($post['feature'])) {
                                                $image_data = is_string($post['feature']) ? json_decode($post['feature'], true) : $post['feature'];
                                                if (isset($image_data['path'])) {
                                                    $featured_image = rtrim(base_url(), '/') . '/uploads/' . $image_data['path'];
                                                }
                                            }
                                            
                                            // Hình ảnh mặc định nếu không có
                                            if (empty($featured_image)) {
                                                // Mảng ảnh mẫu
                                                $default_images = [
                                                    'images/editors/blog-sample-1.jpg',
                                                    'images/editors/blog-sample-2.jpg', 
                                                    'images/editors/blog-sample-3.jpg',
                                                    'images/editors/blog-esports-titles-540x360.webp'
                                                ];
                                                // Chọn ảnh ngẫu nhiên dựa trên ID bài viết
                                                $image_index = ($post['id'] ?? $index) % count($default_images);
                                                $featured_image = theme_assets($default_images[$image_index]);
                                            }
                                            ?>
                                            <img width="360" height="180" 
                                                 src="<?php echo htmlspecialchars($featured_image, ENT_QUOTES, 'UTF-8'); ?>" 
                                                 alt="<?php echo htmlspecialchars($post['title'] ?? 'Untitled', ENT_QUOTES, 'UTF-8'); ?>" 
                                                 decoding="async" 
                                                 loading="<?php echo $index < 3 ? 'eager' : 'lazy'; ?>" 
                                                 class="loaded">
                                        <div class="card-body">
                                            <div class="card-title">
                                                    <h2 class="truncate"><?php echo htmlspecialchars($post['title'] ?? 'Untitled', ENT_QUOTES, 'UTF-8'); ?></h2>
                                            </div>
                                            <p class="card-excerpt font-size__small truncate">
                                                    <?php 
                                                    $excerpt = !empty($post['excerpt']) ? $post['excerpt'] : strip_tags($post['content'] ?? '');
                                                    $excerpt = $excerpt ? mb_substr($excerpt, 0, 100) . '...' : 'No content available...';
                                                    echo htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8'); 
                                                    ?>
                                                </p>
                                                <?php if (!empty($post['categories'])): ?>
                                                    <div class="card-categories">
                                                        <?php foreach ($post['categories'] as $category): ?>
                                                            <span class="category-tag"><?php echo htmlspecialchars($category['name'] ?? 'Uncategorized', ENT_QUOTES, 'UTF-8'); ?></span>
                                                        <?php endforeach; ?>
                                        </div>
                                                <?php endif; ?>
                                              
                                            </div>
                                        </a>
                                        </div>
                        </article>
                    </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-posts">
                            <p><?php echo __('No blog posts found', 'Không tìm thấy bài viết nào'); ?></p>
                                            </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- pagination -->
        <?php if (!empty($pagination) && isset($pagination['total_pages']) && $pagination['total_pages'] > 1): ?>
        <section>
            <div class="container">
                <div class="wp-container archive-pagination">
                    <?php 
                    $current_page = $pagination['current_page'] ?? 1;
                    $total_pages = $pagination['total_pages'] ?? 1;
                    $base_url = base_url('blog');
                    
                    // Previous button
                    if ($current_page > 1): 
                        $prev_page = $current_page - 1;
                        $prev_url = $prev_page == 1 ? $base_url : $base_url . '?page=' . $prev_page;
                    ?>
                        <div class="paginate-button">
                            <a class="button clickable" href="<?php echo $prev_url; ?>" aria-label="Go to previous page">
                                <span class="svg-icon" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 -960 960 960">
                                        <path d="M442.15-480 605.08-317.08q8.3 8.31 8.5 20.89.19 12.57-8.5 21.27-8.7 8.69-21.08 8.69-12.38 0-21.08-8.69L374.15-445.23q-5.61-5.62-7.92-11.85-2.31-6.23-2.31-13.46t2.31-13.46q2.31-6.23 7.92-11.85L562.92-685.08q8.31-8.3 20.89-8.5 12.57-.19 21.27 8.5 8.69 8.7 8.69 21.08 0 12.38-8.69 21.08L442.15-480Z"></path>
                                    </svg>
                                </span>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <?php 
                    // Page numbers
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++): 
                        $page_url = $i == 1 ? $base_url : $base_url . '?page=' . $i;
                        $is_active = $i == $current_page;
                    ?>
                        <div class="paginate-button <?php echo $is_active ? 'active' : ''; ?>">
                            <?php if ($is_active): ?>
                                <span aria-current="page" class="button clickable"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a class="button clickable" href="<?php echo $page_url; ?>" aria-label="Go to page <?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                    
                    <?php 
                    // Next button
                    if ($current_page < $total_pages): 
                        $next_page = $current_page + 1;
                        $next_url = $base_url . '?page=' . $next_page;
                    ?>
                        <div class="paginate-button">
                            <a class="next button clickable" href="<?php echo $next_url; ?>" aria-label="Go to next page">
                                <span class="svg-icon" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 -960 960 960">
                                    <path d="M517.85-480 354.92-642.92q-8.3-8.31-8.5-20.89-.19-12.57 8.5-21.27 8.7-8.69 21.08-8.69 12.38 0 21.08 8.69l179.77 179.77q5.61 5.62 7.92 11.85 2.31 6.23 2.31 13.46t-2.31 13.46q-2.31 6.23-7.92 11.85L397.08-274.92q-8.31 8.3-20.89 8.5-12.57.19-21.27-8.5-8.69-8.7-8.69-21.08 0-12.38 8.69-21.08L517.85-480Z"></path>
                                    </svg>
                                </span>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>


<?php get_footer(); ?>