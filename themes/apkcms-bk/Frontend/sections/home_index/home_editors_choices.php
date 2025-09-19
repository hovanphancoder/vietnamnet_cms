<?php
// Lấy dữ liệu Editors' Choices từ database
$games_data = get_posts([
    'posttype' => 'posts',
    'sort' => ['rating_total', 'DESC'],
    'limit' => 10,
    'cat' => option('themes_gamesid'), // Games category ID từ options
    'lang' => APP_LANG // Thêm check ngôn ngữ
]);

$apps_data = get_posts([
    'posttype' => 'posts',
    'sort' => ['rating_total', 'DESC'],
    'limit' => 10,
    'cat' => option('themes_appsid'), // Apps category ID từ options
    'lang' => APP_LANG // Thêm check ngôn ngữ
]);

// Xử lý cấu trúc dữ liệu
$games = isset($games_data['data']) ? $games_data['data'] : $games_data;
$apps = isset($apps_data['data']) ? $apps_data['data'] : $apps_data;
?>

<!-- Editors' Choices Section -->
<section class="section-editors-choices" style="min-height: 400px;">
            <div class="container">
                <h2 class="font-size__medium">Editors' Choices</h2>
                <p class="text-align__justify">The best items that are selected by our editors.</p>

                <!-- Games Row -->
                <div class="flex-container-2 invisible-horizontal-scroll">
                    <?php if (!empty($games) && is_array($games)): ?>
                        <?php foreach ($games as $game): ?>
                            <?php
                            $game_title = $game['title'] ?? 'Untitled';
                            $game_slug = $game['slug'] ?? 'game';
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
                            
                         
                            $game_url = (APP_LANG === APP_LANG_DF) ? '/post/' . $game_slug : page_url($game_slug, 'games');
                            ?>
                            <article class="flex-item">
                                <a href="<?php echo htmlspecialchars($game_url, ENT_QUOTES, 'UTF-8'); ?>" class="app clickable" aria-label="<?php echo htmlspecialchars($game_title, ENT_QUOTES, 'UTF-8'); ?> icon">
                                    <div class="app-icon">
                                        <img decoding="async" loading="lazy" src="<?php echo htmlspecialchars($game_image, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($game_title, ENT_QUOTES, 'UTF-8'); ?> icon" width="90" height="90" class="loaded">
                                    </div>
                                    <div class="app-name truncate">
                                        <h3 class="font-size__small no-margin no-padding truncate"><?php echo htmlspecialchars($game_title, ENT_QUOTES, 'UTF-8'); ?></h3>
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

                <!-- Apps Row -->
                <div class="flex-container-2 invisible-horizontal-scroll">
                    <?php if (!empty($apps) && is_array($apps)): ?>
                        <?php foreach ($apps as $app): ?>
                            <?php
                            $app_title = $app['title'] ?? 'Untitled';
                            $app_slug = $app['slug'] ?? 'app';
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
                            
                          
                            
                            $app_url = (APP_LANG === APP_LANG_DF) ? '/post/' . $app_slug : page_url($app_slug, 'apps');
                            ?>
                            <article class="flex-item">
                                <a href="<?php echo htmlspecialchars($app_url, ENT_QUOTES, 'UTF-8'); ?>" class="app clickable" aria-label="<?php echo htmlspecialchars($app_title, ENT_QUOTES, 'UTF-8'); ?> icon">
                                    <div class="app-icon">
                                        <img decoding="async" loading="lazy" src="<?php echo htmlspecialchars($app_image, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($app_title, ENT_QUOTES, 'UTF-8'); ?> icon" width="90" height="90" class="loaded">
                                    </div>
                                    <div class="app-name truncate">
                                        <h3 class="font-size__small no-margin no-padding truncate"><?php echo htmlspecialchars($app_title, ENT_QUOTES, 'UTF-8'); ?></h3>
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