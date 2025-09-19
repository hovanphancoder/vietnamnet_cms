<?php

// Lấy dữ liệu Recently Updated Games từ database
$recent_games_data = get_posts([
    'posttype' => 'posts',
    'sort' => ['updated_at', 'DESC'],
    'limit' => 8,
    'cat' => option('themes_gamesid'), 
    'lang' => APP_LANG               // Thêm check ngôn ngữ
]);

// Xử lý cấu trúc dữ liệu
$recent_games = isset($recent_games_data['data']) ? $recent_games_data['data'] : $recent_games_data;

// Sử dụng biến global từ header.php
$categories = $GLOBALS['games_categories'] ?? [];
?>

<!-- Recently updated games Section -->
<section class="section-recently-updated">
            <div class="container">
                <a href="<?php echo (APP_LANG === APP_LANG_DF) ? '/posts/category' : page_url('', 'games'); ?>" aria-label="View all games">
                    <h2 class="font-size__medium color__black">Recently updated games</h2>
                </a>
                <p class="text-align__justify">What games have been updated? Browse through the following items to find out.</p>
                <div class="flex-cat-container">
                    <div class="flex-cat-item active">
                        <a href="<?php echo (APP_LANG === APP_LANG_DF) ? '/posts/category/' : page_url('', 'games'); ?>" aria-label="View all games">All</a>
                    </div>
                    <?php if (!empty($categories) && is_array($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                            <div class="flex-cat-item">
                                <a href="<?php echo (APP_LANG === APP_LANG_DF) ? '/posts/category/' . ($category['slug'] ?? '') : page_url($category['slug'] ?? '', 'games'); ?>" aria-label="<?php echo htmlspecialchars($category['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?> games">
                                    <?php echo htmlspecialchars($category['name'] ?? 'Category', ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="flex-container horizontal-scroll">
                    <?php if (!empty($recent_games) && is_array($recent_games)): ?>
                        <?php foreach ($recent_games as $game): ?>
                            <?php
                            $game_title = $game['title'] ?? 'Untitled';
                            $game_slug = $game['slug'] ?? 'game';
                            $game_version = $game['version'] ?? 'v1.0.0';
                            $game_rating = $game['rating'] ?? 0;
                            $game_image = '';
                            
                            // Lấy hình ảnh featured
                            if (!empty($game['feature'])) {
                                $image_data = is_string($game['feature']) ? json_decode($game['feature'], true) : $game['feature'];
                                if (isset($image_data['path'])) {
                                    $game_image = rtrim(base_url(), '/') . '/uploads/' . $image_data['path'];
                                } elseif (is_string($game['feature'])) {
                                    $game_image = $game['feature'];
                                }
                            }
                            
                            // Lấy categories
                            $categories = $game['categories'] ?? [];
                            $category_name = !empty($categories) ? $categories[0]['name'] ?? 'General' : 'General';
                            
                            // Tạo URL
                            $game_url = (APP_LANG === APP_LANG_DF) ? '/post/' . $game_slug : page_url($game_slug, 'games');
                            
                            // Tạo rating stars
                            $stars = '';
                            $rating_int = (int)$game_rating;
                            for ($i = 1; $i <= 5; $i++) {
                                $star_class = $i <= $rating_int ? 'star active' : 'star';
                                $stars .= '<span class="' . $star_class . '"></span>';
                            }
                            ?>
                            <article class="flex-item">
                                <a href="<?php echo htmlspecialchars($game_url, ENT_QUOTES, 'UTF-8'); ?>" class="app clickable" aria-label="<?php echo htmlspecialchars($game_slug, ENT_QUOTES, 'UTF-8'); ?> game">
                                    <div class="app-icon">
                                        <img fetchpriority="high" src="<?php echo htmlspecialchars($game_image, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($game_title, ENT_QUOTES, 'UTF-8'); ?> icon" width="90" height="90">
                                    </div>
                                    <div class="app-name truncate">
                                        <h3 class="font-size__normal no-margin no-padding truncate"><?php echo htmlspecialchars($game_title, ENT_QUOTES, 'UTF-8'); ?></h3>
                                        <div class="app-sub-text font-size__small color__gray truncate">
                                            <?php echo htmlspecialchars($game_version, ENT_QUOTES, 'UTF-8'); ?>
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
                            <p>Không có dữ liệu games</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>