
<?php

// Lấy dữ liệu Recently Updated Apps từ database
$recent_apps_data = get_posts([
    'posttype' => 'posts',
    'sort' => ['updated_at', 'DESC'],
    'limit' => 8,
    'cat' => option('themes_appsid'), 
    'lang' => APP_LANG               // Thêm check ngôn ngữ
]);

// Xử lý cấu trúc dữ liệu
$recent_apps = isset($recent_apps_data['data']) ? $recent_apps_data['data'] : $recent_apps_data;

// Sử dụng biến global từ header.php
$categories = $GLOBALS['apps_categories'] ?? [];
?>

<!-- Recently updated apps Section -->
<section class="section-recently-updated">
            <div class="container">
                <a href="<?php echo (APP_LANG === APP_LANG_DF) ? '/apps' : page_url('', 'apps'); ?>" aria-label="View all apps">
                    <h2 class="font-size__medium color__black">Recently updated apps</h2>
                </a>
                <p class="text-align__justify">Newly updated applications will be placed here. Your life will certainly be a lot easier thanks to the huge application store that APKMODY is building.</p>
                <div class="flex-cat-container">
                    <div class="flex-cat-item active">
                        <a href="<?php echo (APP_LANG === APP_LANG_DF) ? '/posts/category' : page_url('', 'apps'); ?>" aria-label="View all apps">All</a>
                    </div>
                    <?php if (!empty($categories) && is_array($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                            <div class="flex-cat-item">
                                <a href="<?php echo (APP_LANG === APP_LANG_DF) ? '/posts/category/' . ($category['slug'] ?? '') : page_url($category['slug'] ?? '', 'apps'); ?>" aria-label="<?php echo htmlspecialchars($category['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?> apps">
                                    <?php echo htmlspecialchars($category['name'] ?? 'Category', ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="flex-container horizontal-scroll">
                    <?php if (!empty($recent_apps) && is_array($recent_apps)): ?>
                        <?php foreach ($recent_apps as $app): ?>
                            <?php
                            $app_title = $app['title'] ?? 'Untitled';
                            $app_slug = $app['slug'] ?? 'app';
                            $app_version = $app['version'] ?? 'v1.0.0';
                            $app_rating = $app['rating'] ?? 0;
                            $app_image = '';
                            
                            // Lấy hình ảnh featured
                            if (!empty($app['feature'])) {
                                $image_data = is_string($app['feature']) ? json_decode($app['feature'], true) : $app['feature'];
                                if (isset($image_data['path'])) {
                                    $app_image = rtrim(base_url(), '/') . '/uploads/' . $image_data['path'];
                                } elseif (is_string($app['feature'])) {
                                    $app_image = $app['feature'];
                                }
                            }
                        
                            
                            // Lấy categories
                            $categories = $app['categories'] ?? [];
                            $category_name = !empty($categories) ? $categories[0]['name'] ?? 'General' : 'General';
                            
                            // Tạo URL
                            $app_url = (APP_LANG === APP_LANG_DF) ? '/post/' . $app_slug : page_url($app_slug, 'apps');
                            
                            // Tạo rating stars
                            $stars = '';
                            $rating_int = (int)$app_rating;
                            for ($i = 1; $i <= 5; $i++) {
                                $star_class = $i <= $rating_int ? 'star active' : 'star';
                                $stars .= '<span class="' . $star_class . '"></span>';
                            }
                            ?>
                            <article class="flex-item">
                                <a href="<?php echo htmlspecialchars($app_url, ENT_QUOTES, 'UTF-8'); ?>" class="app clickable" aria-label="<?php echo htmlspecialchars($app_slug, ENT_QUOTES, 'UTF-8'); ?> app">
                                    <div class="app-icon">
                                        <img fetchpriority="high" src="<?php echo htmlspecialchars($app_image, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($app_title, ENT_QUOTES, 'UTF-8'); ?> icon" width="90" height="90">
                                    </div>
                                    <div class="app-name truncate">
                                        <h3 class="font-size__normal no-margin no-padding truncate"><?php echo htmlspecialchars($app_title, ENT_QUOTES, 'UTF-8'); ?></h3>
                                        <div class="app-sub-text font-size__small color__gray truncate">
                                            <?php echo htmlspecialchars($app_version, ENT_QUOTES, 'UTF-8'); ?>
                                            <span class="app-genre">• <?php echo htmlspecialchars($category_name, ENT_QUOTES, 'UTF-8'); ?></span>
                                        </div>
                                        <div class="app-tags font-size__small">
                                            <div class="app-rating">
                                                <?php echo $stars; ?>
                                            </div>
                                        </div>
                                        <span class="app-sub-action font-size__small">
                                            <span class="app-sub-action-button">Get</span>
                                        </span>
                                    </div>
                                </a>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-data">
                            <p>Không có dữ liệu apps</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>