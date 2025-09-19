  <!-- Trending Section -->
  <section class="section-trending">
            <div class="container">
                <h2 class="font-size__medium color__black">Trending ⚡</h2>
                <p class="text-align__justify">Explore what people is searching for right now</p>
                
                <?php
                // Lấy dữ liệu trending từ database
                $trending_posts = get_posts([
                    'posttype' => 'posts',
                    'sort' => ['views', 'DESC'],
                    'limit' => 8,
                    // 'withCategories' => true,
                    'lang' => APP_LANG // Thêm check ngôn ngữ
                ]);
                

              
                ?>
                
                <div class="flex-container horizontal-scroll">
                    <?php 
                    // Kiểm tra cấu trúc dữ liệu
                    if (isset($trending_posts['data'])) {
                        $posts_data = $trending_posts['data'];
                    } else {
                        $posts_data = $trending_posts;
                    }
                    
                    if (!empty($posts_data) && is_array($posts_data)): ?>
                        <?php foreach ($posts_data as $post): ?>
                            <?php
                            // Lấy thông tin cơ bản
                            $post_data = $post;
                            
                            $post_title = $post_data['title'] ?? 'Untitled';
                            $post_slug = $post_data['slug'] ?? '';
                            $post_version = $post_data['version'] ?? 'v1.0.0';
                            $post_views = $post_data['views'] ?? 0;
                            $post_rating = $post_data['rating'] ?? 0;
                            
                            // Lấy hình ảnh featured
                            $post_image = '';
                            if (!empty($post_data['feature'])) {
                                $image_data = is_string($post_data['feature']) ? json_decode($post_data['feature'], true) : $post_data['feature'];
                                if (isset($image_data['path'])) {
                                    $post_image = rtrim(base_url(), '/') . '/uploads/' . $image_data['path'];
                                }
                            }
                            
                            // Lấy categories
                            $categories = $post_data['categories'] ?? [];
                            $category_name = !empty($categories) ? $categories[0]['name'] ?? 'General' : 'General';
                            
                            // Tạo URL
                                $post_url = (APP_LANG === APP_LANG_DF) ? '/post/' . $post_slug : page_url($post_slug, 'posts');
                            // Tạo rating stars
                            $stars = '';
                            $rating_int = (int)$post_rating;
                            for ($i = 1; $i <= 5; $i++) {
                                $star_class = $i <= $rating_int ? 'star active' : 'star';
                                $stars .= '<span class="' . $star_class . '"></span>';
                            }
                            ?>
                            <article class="flex-item">
                                <a href="<?php echo htmlspecialchars($post_url, ENT_QUOTES, 'UTF-8'); ?>" class="app clickable" aria-label="<?php echo htmlspecialchars($post_title, ENT_QUOTES, 'UTF-8'); ?> icon">
                                    <div class="app-icon">
                                        <?php if (!empty($post_image)): ?>
                                            <img fetchpriority="high" src="<?php echo htmlspecialchars($post_image, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($post_title, ENT_QUOTES, 'UTF-8'); ?> icon" width="90" height="90">
                                        <?php else: ?>
                                            <img fetchpriority="high" src="/themes/apkcms/Frontend/images/editors/unnamed.webp"  alt="<?php echo htmlspecialchars($post_title, ENT_QUOTES, 'UTF-8'); ?> icon" width="90" height="90">
                                        <?php endif; ?>
                                    </div>
                                    <div class="app-name truncate">
                                        <h3 class="font-size__normal no-margin no-padding truncate"><?php echo htmlspecialchars($post_title, ENT_QUOTES, 'UTF-8'); ?></h3>
                                        <div class="app-sub-text font-size__small color__gray truncate">
                                            <?php echo htmlspecialchars($post_version, ENT_QUOTES, 'UTF-8'); ?>
                                            • <?php echo formatViews($post_views); ?> views
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
                            <p>Không có dữ liệu trending</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>