<?php
App\Libraries\Fastlang::load('Homepage');

// Load CSS assets (minified for production)
// get_template('_metas/css_assets');

// Load JavaScript assets
// \System\Libraries\Render::asset('js', theme_assets('Assets/js/script.min.js'), [
//     'area' => 'frontend', 
//     'location' => 'footer'
// ]);

// ===== LẤY DỮ LIỆU APPS =====
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

// Lấy danh sách apps từ database với posttype 'posts' (CHỈ 1 QUERY)
$apps_data = get_posts([
    'posttype' => 'posts',           // Sử dụng posttype 'posts'
    'perPage' => 20,                 // 20 apps mỗi trang
    'withCategories' => true,        // Lấy categories
    'sort' => $sort_array,           // Sắp xếp theo tham số từ URL
    'paged' => S_GET('page', 1),     // Trang hiện tại từ URL
    'active' => true,                // Chỉ lấy bài active
    'cat' => option('themes_appsid'), // Filter theo apps category ID từ options
    'lang' => APP_LANG               // Thêm check ngôn ngữ
]);

// Tách dữ liệu apps và pagination (từ 1 query duy nhất)
$apps = $apps_data['data'] ?? [];
$pagination = $apps_data['pagination'] ?? [];

//Get Object Data for this Pages
$locale = APP_LANG.'_'.strtoupper(lang_country(APP_LANG));

// Tạo mảng dữ liệu để truyền vào template
$meta_data = [
    'locale' => $locale,
    'page_title' => 'Apps - ' . option('site_title', APP_LANG),
    'page_description' => 'Download the latest apps and mods - ' . option('site_description', APP_LANG),
    'page_type' => 'apps',
    'posts_count' => count($apps),
    'current_lang' => APP_LANG,
    'site_name' => option('site_title', APP_LANG),
    'custom_data' => [
        'total_apps' => $apps_data['pagination']['total'] ?? 0,
        'current_page' => $apps_data['pagination']['current_page'] ?? 1,
        'total_pages' => $apps_data['pagination']['total_pages'] ?? 1,
        'categories_count' => count($apps_categories ?? [])
    ]
];

get_template('_metas/meta_page', $meta_data);

?>

     <!-- Apps Section -->
     <section>
            <div class="container">
                <div id="breadcrumb" class="font-size__small color__gray truncate"><span><span><a class="color__gray" href="/" aria-label="Home">Home</a></span> / <span class="color__gray" aria-current="page">Apps</span></span></div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h1 class="font-size__larger">Apps</h1>
                </div>
                <div class="text-align__justify" style="font-size: 0.9em;">
                    <p>Add convenience to your phone with new apps updated on APKMODY. In this section, you can easily find different apps in every aspect such as watching movies, calculating, editing photos, finance, health… All of which have been researched, selected and trusted by us. Immediately download these applications to turn your phone into a “miniature library” with full of desirable utilities and entertainment.</p>
                </div>
                <div id="orderby" class="flex-cat-container">
                    <?php 
                    $current_url = $_SERVER['REQUEST_URI'];
                    $base_url = strtok($current_url, '?'); // Lấy URL không có query parameters
                    ?>
                    <div class="flex-cat-item <?php echo !isset($_GET['sort']) || $_GET['sort'] == 'all' ? 'active' : ''; ?>" aria-label="Link"><a href="<?php echo $base_url; ?>" aria-label="All apps">All</a></div>
                    <div class="flex-cat-item <?php echo isset($_GET['sort']) && $_GET['sort'] == 'updated' ? 'active' : ''; ?>" aria-label="Link"><a href="<?php echo $base_url . '?sort=updated'; ?>" aria-label="Updated content">Updated</a></div>
                    <div class="flex-cat-item <?php echo isset($_GET['sort']) && $_GET['sort'] == 'popular' ? 'active' : ''; ?>" aria-label="Link"><a href="<?php echo $base_url . '?sort=popular'; ?>" aria-label="Popular content">Popular</a></div>
                    <div class="flex-cat-item <?php echo isset($_GET['sort']) && $_GET['sort'] == 'rating' ? 'active' : ''; ?>" aria-label="Link"><a href="<?php echo $base_url . '?sort=rating'; ?>" aria-label="Top rated content">Rating</a></div>
                </div>
            </div>
        </section>

        <!-- Apps Section -->
        <section>
            <div class="container">
                <div class="flex-container">
                    <?php if (!empty($apps)): ?>
                        <?php foreach ($apps as $index => $app): ?>
                            <article class="flex-item">
                                <a href="<?php echo (APP_LANG === APP_LANG_DF) ? '/post/' . ($app['slug'] ?? '') : page_url($app['slug'] ?? '', 'posts'); ?>" class="app clickable" aria-label="<?php echo htmlspecialchars($app['title'] ?? 'Untitled', ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="app-icon">
                                        <?php
                                        $featured_image = '';
                                        if (!empty($app['feature'])) {
                                            $image_data = is_string($app['feature']) ? json_decode($app['feature'], true) : $app['feature'];
                                            if (isset($image_data['path'])) {
                                                $featured_image = rtrim(base_url(), '/') . '/uploads/' . $image_data['path'];
                                            }
                                        }
                                        if (empty($featured_image)) {
                                            $featured_image = 'https://via.placeholder.com/90x90/2196F3/FFFFFF?text=App';
                                        }
                                        ?>
                                        <img fetchpriority="<?php echo $index < 3 ? 'high' : 'low'; ?>"
                                             src="<?php echo htmlspecialchars($featured_image, ENT_QUOTES, 'UTF-8'); ?>"
                                             alt="<?php echo htmlspecialchars($app['title'] ?? 'Untitled', ENT_QUOTES, 'UTF-8'); ?> icon"
                                             width="90" height="90"
                                             loading="<?php echo $index < 3 ? 'eager' : 'lazy'; ?>"
                                             class="<?php echo $index >= 3 ? 'loaded' : ''; ?>">
                            </div>
                            <div class="app-name truncate">
                                        <h2 class="font-size__normal no-margin no-padding truncate"><?php echo htmlspecialchars($app['title'] ?? 'Untitled', ENT_QUOTES, 'UTF-8'); ?></h2>
                                        <div class="app-sub-text font-size__small color__gray truncate">
                                            <?php
                                            $version = $app['version'] ?? 'v1.0';
                                            $status = $app['status'] ?? 'Free';
                                            $genre = !empty($app['categories']) ? $app['categories'][0]['name'] ?? 'App' : 'App';
                                            echo htmlspecialchars($version . ' • ' . $status . ' • ' . $genre, ENT_QUOTES, 'UTF-8');
                                            ?>
                            </div>
                                <div class="app-tags font-size__small">
                                    <div class="app-rating">
                                                <?php
                                                $rating = $app['rating_avg'] ?? 0;
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

<?php get_footer(); ?>