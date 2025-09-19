<?php
App\Libraries\Fastlang::load('Homepage');

// Load CSS assets (minified for production)
// get_template('_metas/css_assets');

// Load JavaScript assets
// \System\Libraries\Render::asset('js', theme_assets('Assets/js/script.min.js'), [
//     'area' => 'frontend', 
//     'location' => 'footer'
// ]);

// ===== LẤY DỮ LIỆU GAMES =====
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

// Lấy danh sách games từ database với posttype 'posts' (CHỈ 1 QUERY)
$games_data = get_posts([
    'posttype' => 'posts',           // Sử dụng posttype 'posts'
    'perPage' => 20,                 // 20 games mỗi trang
    'withCategories' => true,        // Lấy categories
    'sort' => $sort_array,           // Sắp xếp theo tham số từ URL
    'paged' => S_GET('page', 1),     // Trang hiện tại từ URL
    'active' => true,                // Chỉ lấy bài active

    'cat' => option('themes_gamesid'), // Filter theo games category ID từ options
    'lang' => APP_LANG               // Thêm check ngôn ngữ
]);

// Tách dữ liệu games và pagination (từ 1 query duy nhất)
$games = $games_data['data'] ?? [];
$pagination = $games_data['pagination'] ?? [];


// if (!empty($games) && is_array($games)) {
//     echo "First game type: " . gettype($games[0]) . "\n";
//     if (isset($games[0]) && is_array($games[0])) {
//         echo "First game keys: " . implode(', ', array_keys($games[0])) . "\n";
//         print_r($games[0]);
//     } else {
//         echo "First game is not array: ";
//         var_dump($games[0]);
//     }
// } else {
//     echo "Games is empty or not array\n";
//     var_dump($games);
// }
// echo '</pre>';

//Get Object Data for this Pages
$locale = APP_LANG.'_'.strtoupper(lang_country(APP_LANG));

// Tạo mảng dữ liệu để truyền vào template
$meta_data = [
    'locale' => $locale,
    'page_title' => 'Games - ' . option('site_title', APP_LANG),
    'page_description' => 'Download the latest games and mods - ' . option('site_description', APP_LANG),
    'page_type' => 'games',
    'posts_count' => count($games),
    'current_lang' => APP_LANG,
    'site_name' => option('site_title', APP_LANG),
    'custom_data' => [
        'total_games' => $games_data['pagination']['total'] ?? 0,
        'current_page' => $games_data['pagination']['current_page'] ?? 1,
        'total_pages' => $games_data['pagination']['total_pages'] ?? 1,
        'categories_count' => count($games_categories ?? [])
    ]
];

get_template('_metas/meta_page', $meta_data);

?>

<!-- Games -->
<section>
            <div class="container">
                <div id="breadcrumb" class="font-size__small color__gray truncate"><span><span><a class="color__gray" href="/" aria-label="Home">Home</a></span> / <span class="color__gray" aria-current="page">Games</span></span></div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h1 class="font-size__larger">Games</h1>
                </div>
                <div class="text-align__justify" style="font-size: 0.9em;">
            <p>Honestly, Game has become an indispensable part of players' youth. Most of all, it is the love and burning passion for Games that made us – the developers of APKMODY create this category. We hope that we can preserve the good memories and open up a new playing field for the next generation of gamers. Here you can find the best MOD APK, Paid APK and Original APK games. Countless exciting and new games are being updated and shared with you every day. In particular, we do these things completely for free without collecting a single penny from you. Feel free to consult, choose a game that suits you and create the best memories together.</p>
                </div>
                <div id="orderby" class="flex-cat-container">
                    <?php 
                    $current_url = $_SERVER['REQUEST_URI'];
                    $base_url = strtok($current_url, '?'); // Lấy URL không có query parameters
                    ?>
                    <div class="flex-cat-item <?php echo !isset($_GET['sort']) || $_GET['sort'] == 'all' ? 'active' : ''; ?>"><a href="<?php echo $base_url; ?>" aria-label="View all games">All</a></div>
                    <div class="flex-cat-item <?php echo isset($_GET['sort']) && $_GET['sort'] == 'updated' ? 'active' : ''; ?>"><a href="<?php echo $base_url . '?sort=updated'; ?>" aria-label="Updated games">Updated</a></div>
                    <div class="flex-cat-item <?php echo isset($_GET['sort']) && $_GET['sort'] == 'popular' ? 'active' : ''; ?>"><a href="<?php echo $base_url . '?sort=popular'; ?>" aria-label="Popular games">Popular</a></div>
                    <div class="flex-cat-item <?php echo isset($_GET['sort']) && $_GET['sort'] == 'rating' ? 'active' : ''; ?>"><a href="<?php echo $base_url . '?sort=rating'; ?>" aria-label="Top rated games">Rating</a></div>
                </div>
            </div>
        </section>

        <!-- Games Section -->
        <section>
            <div class="container">
                <div class="flex-container">
            <?php if (!empty($games)): ?>
                <?php foreach ($games as $index => $game): ?>
                    <article class="flex-item" aria-label="Link">
                        <a href="<?php echo (APP_LANG === APP_LANG_DF) ? '/post/' . ($game['slug'] ?? '') : page_url($game['slug'] ?? '', 'posts'); ?>" class="app clickable" aria-label="<?php echo htmlspecialchars($game['title'] ?? 'Untitled', ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="app-icon">
                                <?php 
                                // Lấy hình ảnh featured
                                $featured_image = '';
                                if (!empty($game['feature'])) {
                                    $image_data = is_string($game['feature']) ? json_decode($game['feature'], true) : $game['feature'];
                                    if (isset($image_data['path'])) {
                                        $featured_image = rtrim(base_url(), '/') . '/uploads/' . $image_data['path'];
                                    }
                                }
                                
                                // Hình ảnh mặc định nếu không có
                                if (empty($featured_image)) {
                                    $featured_image = 'https://via.placeholder.com/90x90/4CAF50/FFFFFF?text=Game';
                                }
                                ?>
                                <img fetchpriority="<?php echo $index < 3 ? 'high' : 'low'; ?>" 
                                     src="<?php echo htmlspecialchars($featured_image, ENT_QUOTES, 'UTF-8'); ?>" 
                                     alt="<?php echo htmlspecialchars($game['title'] ?? 'Untitled', ENT_QUOTES, 'UTF-8'); ?> icon" 
                                     width="90" height="90"
                                     loading="<?php echo $index < 3 ? 'eager' : 'lazy'; ?>"
                                     class="<?php echo $index >= 3 ? 'loaded' : ''; ?>">
                            </div>
                            <div class="app-name truncate">
                                <h2 class="font-size__normal no-margin no-padding truncate"><?php echo htmlspecialchars($game['title'] ?? 'Untitled', ENT_QUOTES, 'UTF-8'); ?></h2>
                                <div class="app-sub-text font-size__small color__gray truncate">
                                    <?php 
                                    $version = $game['version'] ?? 'v1.0';
                                    $genre = !empty($game['categories']) ? $game['categories'][0]['name'] ?? 'Game' : 'Game';
                                    echo htmlspecialchars($version . ' • ' . $genre, ENT_QUOTES, 'UTF-8'); 
                                    ?>
                                </div>
                                <div class="app-tags font-size__small">
                                    <div class="app-rating">
                                        <?php 
                                        $rating = $game['rating_avg'] ?? 0;
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
                <div class="no-games">
                    <p><?php echo __('No games found', 'Không tìm thấy game nào'); ?></p>
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
            $base_url = base_url('games');
            
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