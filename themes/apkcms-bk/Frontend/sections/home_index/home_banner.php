<?php
// Dữ liệu mẫu cho banner - có thể chỉnh sửa sau
$featured_apps = [
    [
        'title' => 'TikTok',
        'slug' => 'tiktok-mod-apk',
        'version' => '41.6.2',
        'rating' => 4.5,
        'image' => '/themes/apkcms/Frontend/images/apps/tiktok.jpg',
        'description' => 'MOD Premium Unlocked'
    ],
    [
        'title' => 'Spotify',
        'slug' => 'spotify-premium-mod-apk',
        'version' => '9.0.78.228',
        'rating' => 4.8,
        'image' => '/themes/apkcms/Frontend/images/apps/spotify.jpg',
        'description' => 'MOD Premium Unlocked'
    ],
    [
        'title' => 'Netflix',
        'slug' => 'netflix-premium-mod-apk',
        'version' => '9.32.0',
        'rating' => 4.7,
        'image' => '/themes/apkcms/Frontend/images/apps/netflix.jpg',
        'description' => 'MOD Premium Unlocked'
    ],
    [
        'title' => 'Canva',
        'slug' => 'canva-mod-apk',
        'version' => '2.328.0',
        'rating' => 4.6,
        'image' => '/themes/apkcms/Frontend/images/apps/canva.jpg',
        'description' => 'MOD Premium Unlocked'
    ]
];

// Site information - có thể chỉnh sửa sau
$site_title = 'APKMODY';
$site_description = 'The best free APK app store for Android. Where you can download your favorite games and apps, awesome MODs and more...';
?>

<!-- Banner Section -->
<section class="no-padding section-banner">
            <div class="container">
                <a href="<?php echo (APP_LANG === APP_LANG_DF) ? '/' : page_url('', 'home'); ?>" aria-label="<?php echo htmlspecialchars($site_title, ENT_QUOTES, 'UTF-8'); ?> Home">
                    <h1 class="font-size__larger color__black margin-top-15" style="letter-spacing: 5px;"><?php echo htmlspecialchars($site_title, ENT_QUOTES, 'UTF-8'); ?></h1>
                </a>
                <p><?php echo htmlspecialchars($site_description, ENT_QUOTES, 'UTF-8'); ?></p>

                <div class="cover" id="home-cover" style="height: 400px; min-height: 400px;">
                    <div class="hero-slider">
                        <div class="hero-slide active">
                            <a href="#" aria-label="apkmody 2025">


                                <img decoding="async" fetchpriority="high" width="1440" height="810" class="cover-background loaded" alt="apkmody 2025" src="/themes/apkcms/Frontend/images/hero-banner.webp"  style="object-position:50% 0%; width: 100%; height: 400px; object-fit: cover;" data-object-fit="cover" data-object-position="50% 0%">
                            </a>
                        </div>
                        <div class="hero-slide">
                            <a href="#" aria-label="apkmody 2024">


                                <img decoding="async" fetchpriority="high" width="1440" height="810" class="cover-background loaded" alt="apkmody 2024" src="/themes/apkcms/Frontend/images/hero-banner-2.webp"  style="object-position:50% 0%; width: 100%; height: 400px; object-fit: cover;" data-object-fit="cover" data-object-position="50% 0%">
                            </a>
                        </div>
                        <div class="hero-slide">
                            <a href="#" aria-label="apkmody 2024">


                                <img decoding="async" fetchpriority="high" width="1440" height="810" class="cover-background loaded" alt="apkmody 2024" src="/themes/apkcms/Frontend/images/hero-banner-3.webp"  style="object-position:50% 0%; width: 100%; height: 400px; object-fit: cover;" data-object-fit="cover" data-object-position="50% 0%">
                            </a>
                        </div>
                    </div>
                    <div class="hero-dots">
                        <span class="dot active" data-slide="0"></span>
                        <span class="dot" data-slide="1"></span>
                        <span class="dot" data-slide="2"></span>
                    </div>
                </div>

                <div class="flex-home flex-container horizontal-scroll margin-top-15">
                    <?php if (!empty($featured_apps) && is_array($featured_apps)): ?>
                        <?php foreach ($featured_apps as $index => $app): ?>
                            <?php
                            // Lấy thông tin cơ bản
                            $app_title = $app['title'] ?? 'Untitled';
                            $app_slug = $app['slug'] ?? '';
                            $app_version = $app['version'] ?? '';
                            $app_rating = $app['rating'] ?? 0;
                            $app_description = $app['description'] ?? 'MOD Premium Unlocked';
                            
                            // Lấy hình ảnh (đã là string URL)
                            $app_image = $app['image'] ?? '';
                            
                            // Tạo placeholder color dựa trên index
                            $placeholder_colors = ['#FF9800', '#1DB954', '#E50914', '#00C4CC'];
                            $placeholder_color = $placeholder_colors[$index % count($placeholder_colors)];
                            
                            // Tạo URL
                            $app_url = (APP_LANG === APP_LANG_DF) ? "/{$app_slug}" : page_url($app_slug, 'posts');
                            ?>
                            <div class="flex-item">
                                <article class="card has-shadow clickable">
                                    <a href="<?php echo htmlspecialchars($app_url, ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars($app_title, ENT_QUOTES, 'UTF-8'); ?>"></a>
                                    <div class="card-image">
                                        <a href="<?php echo htmlspecialchars($app_url, ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars($app_title, ENT_QUOTES, 'UTF-8'); ?> MOD APK cover">
                                            <?php if (!empty($app_image)): ?>
                                                <img width="360" height="180" src="<?php echo htmlspecialchars($app_image, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($app_title, ENT_QUOTES, 'UTF-8'); ?> MOD APK cover" decoding="async" <?php echo $index === 0 ? 'fetchpriority="high"' : 'loading="lazy"'; ?> class="loaded" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                            <?php endif; ?>
                                            <div class="app-placeholder" style="display: none; width: 100%; height: 100%; background: <?php echo $placeholder_color; ?>; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem; font-weight: bold;">
                                                <?php echo htmlspecialchars($app_title, ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                            <div class="card-body">
                                                <div class="card-title">
                                                    <div class="truncate"><?php echo htmlspecialchars($app_title, ENT_QUOTES, 'UTF-8'); ?></div>
                                                </div>
                                                <p class="card-excerpt font-size__small truncate">
                                                    <?php if (!empty($app_version)): ?>
                                                        v<?php echo htmlspecialchars($app_version, ENT_QUOTES, 'UTF-8'); ?> • 
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($app_description, ENT_QUOTES, 'UTF-8'); ?>
                                                </p>
                                            </div>
                                        </a>
                                    </div>
                                </article>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Fallback nếu không có dữ liệu -->
                        <div class="flex-item">
                            <article class="card has-shadow clickable">
                                <a href="#" aria-label="No featured apps available"></a>
                                <div class="card-image">
                                    <a href="#" aria-label="No featured apps available">
                                        <div class="app-placeholder" style="display: flex; width: 100%; height: 100%; background: #ccc; align-items: center; justify-content: center; color: white; font-size: 1.2rem; font-weight: bold;">
                                            No Featured Apps
                                        </div>
                                        <div class="card-body">
                                            <div class="card-title">
                                                <div class="truncate">No Featured Apps</div>
                                            </div>
                                            <p class="card-excerpt font-size__small truncate">
                                                Coming Soon
                                            </p>
                                        </div>
                                    </a>
                                </div>
                            </article>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
