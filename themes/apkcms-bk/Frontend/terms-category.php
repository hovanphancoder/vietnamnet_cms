<?php
App\Libraries\Fastlang::load('Homepage');

// Load CSS assets (minified for production)
// get_template('_metas/css_assets');

// Load JavaScript assets
// \System\Libraries\Render::asset('js', theme_assets('Assets/js/script.min.js'), [
//     'area' => 'frontend', 
//     'location' => 'footer'
// ]);

$slug = get_current_slug();

// ===== LẤY THÔNG TIN TERM =====
use App\Models\FastModel;

// Lấy thông tin term theo slug
$term = (new FastModel('fast_terms'))
    ->where('slug', $slug)
    ->where('lang', APP_LANG)
    ->first();

// ===== LẤY POSTS THEO TERM =====
// Xử lý sorting
$sort_param = S_GET('sort', 'all');
$sort_array = ['created_at', 'DESC']; // Default sort

switch ($sort_param) {
    case 'updated':
        $sort_array = ['updated_at', 'DESC'];
        break;
    case 'popular':
        $sort_array = ['views', 'DESC'];
        break;
    case 'rating':
        $sort_array = ['rating_total', 'DESC'];
        break;
    case 'all':
    default:
        $sort_array = ['created_at', 'DESC'];
        break;
}

// Lấy danh sách posts theo term ID thông qua relationship table
$posts_data = get_posts([
    'posttype' => 'posts',           // Sử dụng posttype 'posts'
    'perPage' => 20,                 // 20 posts mỗi trang
    'withCategories' => true,        // Lấy categories
    'sort' => $sort_array,           // Sắp xếp theo tham số từ URL
    'paged' => S_GET('page', 1),     // Trang hiện tại từ URL
    'active' => true,                // Chỉ lấy bài active
    'cat' => $term['id'],            // Filter theo term ID (sử dụng cat thay vì filters)
    'lang' => APP_LANG               // Thêm check ngôn ngữ
]);



// Tách dữ liệu posts và pagination
$posts = $posts_data['data'] ?? [];
$pagination = $posts_data['pagination'] ?? [];

//Get Object Data for this Pages
$locale = APP_LANG.'_'.strtoupper(lang_country(APP_LANG));
get_template('_metas/meta_term', ['locale' => $locale]);

?>

     <!-- Category Section -->
     <section>
            <div class="container">
                <div id="breadcrumb" class="font-size__small color__gray truncate">
                    <span>
                        <span><a class="color__gray" href="<?php echo (APP_LANG === APP_LANG_DF) ? '/' : page_url('', 'home'); ?>" aria-label="Home">Home</a></span> / 
                        <span class="color__gray" aria-current="page"><?php echo htmlspecialchars($term['name'] ?? 'Category', ENT_QUOTES, 'UTF-8'); ?></span>
                    </span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h1 class="font-size__larger" id="title-post"><?php echo htmlspecialchars($term['name'] ?? 'Category', ENT_QUOTES, 'UTF-8'); ?></h1>
                </div>
                <div class="text-align__justify" style="font-size: 0.9em;">
                    <p><?php echo htmlspecialchars($term['description'] ?? 'Browse posts in this category.', ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <div id="orderby" class="flex-cat-container">
                <?php 
                    $current_url = $_SERVER['REQUEST_URI'];
                    $base_url = strtok($current_url, '?'); // Lấy URL không có query parameters
                    ?>
                    <div class="flex-cat-item <?php echo !isset($_GET['sort']) || $_GET['sort'] == 'all' ? 'active' : ''; ?>"><a href="<?php echo $base_url; ?>" aria-label="View all content">All</a></div>
                    <div class="flex-cat-item <?php echo isset($_GET['sort']) && $_GET['sort'] == 'updated' ? 'active' : ''; ?>"><a href="<?php echo $base_url . '?sort=updated'; ?>" aria-label="Updated content">Updated</a></div>
                    <div class="flex-cat-item <?php echo isset($_GET['sort']) && $_GET['sort'] == 'popular' ? 'active' : ''; ?>"><a href="<?php echo $base_url . '?sort=popular'; ?>" aria-label="Popular content">Popular</a></div>
                    <div class="flex-cat-item <?php echo isset($_GET['sort']) && $_GET['sort'] == 'rating' ? 'active' : ''; ?>"><a href="<?php echo $base_url . '?sort=rating'; ?>" aria-label="Top rated content">Rating</a></div>
                </div>
            </div>
        </section>

        <!-- Posts Section -->
        <section>
            <div class="container">
                <div class="flex-container">
                    <?php if (!empty($posts)): ?>
                        <?php foreach ($posts as $index => $post): ?>
                            <article class="flex-item">
                                <a href="<?php echo (APP_LANG === APP_LANG_DF) ? '/post/' . ($post['slug'] ?? '') : page_url($post['slug'] ?? '', 'posts'); ?>" class="app clickable" aria-label="<?php echo htmlspecialchars($post['title'] ?? 'Untitled', ENT_QUOTES, 'UTF-8'); ?>">
                                    <div class="app-icon">
                                        <?php
                                        $featured_image = '';
                                        if (!empty($post['feature'])) {
                                            $image_data = is_string($post['feature']) ? json_decode($post['feature'], true) : $post['feature'];
                                            if (isset($image_data['path'])) {
                                                $featured_image = rtrim(base_url(), '/') . '/uploads/' . $image_data['path'];
                                            }
                                        }
                                      
                                        ?>
                                        <img fetchpriority="<?php echo $index < 3 ? 'high' : 'low'; ?>"
                                             src="<?php echo htmlspecialchars($featured_image, ENT_QUOTES, 'UTF-8'); ?>"
                                             alt="<?php echo htmlspecialchars($post['title'] ?? 'Untitled', ENT_QUOTES, 'UTF-8'); ?> icon"
                                             width="90" height="90"
                                             loading="<?php echo $index < 3 ? 'eager' : 'lazy'; ?>"
                                             class="<?php echo $index >= 3 ? 'loaded' : ''; ?>">
                                    </div>
                                    <div class="app-name truncate">
                                        <h2 class="font-size__normal no-margin no-padding truncate"><?php echo htmlspecialchars($post['title'] ?? 'Untitled', ENT_QUOTES, 'UTF-8'); ?></h2>
                                        <div class="app-sub-text font-size__small color__gray truncate">
                                            <?php
                                            $version = $post['version'] ?? 'v1.0';
                                            $status = $post['status'] ?? 'Free';
                                            $genre = !empty($post['categories']) ? $post['categories'][0]['name'] ?? 'App' : 'App';
                                            echo htmlspecialchars($version . ' • ' . $status . ' • ' . $genre, ENT_QUOTES, 'UTF-8');
                                            ?>
                                        </div>
                                        <div class="app-tags font-size__small">
                                            <div class="app-rating">
                                                <?php
                                                $rating = $post['rating_avg'] ?? 0;
                                                for ($i = 1; $i <= 5; $i++):
                                                    $class = $i <= $rating ? 'star filled' : 'star';
                                                ?>
                                                    <span class="<?php echo $class; ?>"></span>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <span class="app-sub-action font-size__small">
                                            <span class="app-sub-action-button">
                                                Get
                                            </span>
                                        </span>
                                    </div>
                                </a>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-apps">
                            <p><?php echo __('No apps found', 'Không tìm thấy app nào'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

    
        <!-- Pagination -->
        <section>
            <div class="container">
                <div class="wp-container archive-pagination">
                    <?php if (!empty($pagination) && $pagination['last_page'] > 1): ?>
                        <?php if ($pagination['current_page'] > 1): ?>
                            <div class="paginate-button">
                                <a class="button clickable" href="?page=<?php echo $pagination['current_page'] - 1; ?>" aria-label="Previous page">
                                    <span class="svg-icon" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 -960 960 960">
                                            <path d="M442.15-480 605.08-317.08q8.3 8.31 8.5 20.89.19 12.57-8.5 21.27-8.7 8.69-21.08 8.69-12.38 0-21.08-8.69L373.65-505.23q-5.61-5.62-7.92-11.85-2.31-6.23-2.31-13.46t2.31-13.46q2.31-6.23 7.92-11.85L562.92-725.08q8.31-8.3 20.89-8.5 12.57-.19 21.27 8.5 8.69 8.7 8.69 21.08 0 12.38-8.69 21.08L442.15-480Z"></path>
                                        </svg>
                                    </span>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $pagination['last_page']; $i++): ?>
                            <?php if ($i == $pagination['current_page']): ?>
                                <div class="paginate-button active">
                                    <span aria-current="page" class="button clickable"><?php echo $i; ?></span>
                                </div>
                            <?php else: ?>
                                <div class="paginate-button">
                                    <a class="button clickable" href="?page=<?php echo $i; ?>" aria-label="Go to page <?php echo $i; ?>"><?php echo $i; ?></a>
                                </div>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
                            <div class="paginate-button">
                                <a class="next button clickable" href="?page=<?php echo $pagination['current_page'] + 1; ?>" aria-label="Next page">
                                    <span class="svg-icon" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 -960 960 960">
                                            <path d="M517.85-480 354.92-642.92q-8.3-8.31-8.5-20.89-.19-12.57 8.5-21.27 8.7-8.69 21.08-8.69 12.38 0 21.08 8.69l179.77 179.77q5.61 5.62 7.92 11.85 2.31 6.23 2.31 13.46t-2.31 13.46q-2.31 6.23-7.92 11.85L397.08-274.92q-8.31 8.3-20.89 8.5-12.57.19-21.27-8.5-8.69-8.7-8.69-21.08 0-12.38 8.69-21.08L517.85-480Z"></path>
                                        </svg>
                                    </span>
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <script>
document.addEventListener('DOMContentLoaded', function() {
    // Kiểm tra xem có element #title-post không
    const titleElement = document.getElementById('title-post');
    
    // Chỉ chạy JavaScript nếu có #title-post
    if (titleElement) {
        const topNavTitle = document.querySelector('.top-nav__title');
        
        if (topNavTitle) {
            // Lấy text content và loại bỏ HTML tags
            let titleText = titleElement.textContent || titleElement.innerText;
            
            // Loại bỏ phần "MOD APK (Menu, Unlimited Money) v1.0.0" để chỉ lấy tên app
            titleText = titleText.replace(/\s+MOD APK.*$/i, '').trim();
            
            // Đưa text vào top-nav
            topNavTitle.textContent = titleText;
        }
    }
});
</script>

<?php get_footer(); ?>