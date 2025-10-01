
                        <!-- Section You might be interested in-->
                        <div class="pt-4">
                            <h2 class="text-lg notosans-bold text-sky-600 uppercase font-bold border-b border-[#add2ff] mb-4 pb-2">RECENT POSTS</h2>
                            
                            <?php
                            // Lấy related posts cho sidebar
                            $sidebar_related_posts = [];
                            if (!empty($post['id']) && !empty($post['posttype'])) {
                                $sidebar_related_posts = getRelated($post['posttype'], $post['id'], 5);
                                // Debug: uncomment để xem dữ liệu
                                // var_dump($sidebar_related_posts);
                            }
                            ?>
                            
                            <div class="space-y-4">
                                <?php if (!empty($sidebar_related_posts) && is_array($sidebar_related_posts)): ?>
                                    <?php foreach ($sidebar_related_posts as $related_post): ?>
                                        <?php if (is_array($related_post) && !empty($related_post['title'])): ?>
                                            <article class="flex space-x-3">
                                                <a href="<?= link_single($related_post['slug'] ?? '', $related_post['posttype'] ?? $post['posttype']) ?>" title="<?= htmlspecialchars($related_post['title'] ?? '') ?>">
                                                    <?php 
                                                    // Lấy ảnh đại diện - xử lý đúng cấu trúc JSON
                                                    $feature_image = '';
                                                    
                                                    // Xử lý feature image
                                                    if (!empty($related_post['feature'])) {
                                                        $feature = is_string($related_post['feature']) ? json_decode($related_post['feature'], true) : $related_post['feature'];
                                                        if (is_array($feature) && !empty($feature['path'])) {
                                                            $feature_image = '/uploads/' . $feature['path'];
                                                        }
                                                    }
                                                    
                                                    // Nếu không có feature, thử banner
                                                    if (empty($feature_image) && !empty($related_post['banner'])) {
                                                        $banner = is_string($related_post['banner']) ? json_decode($related_post['banner'], true) : $related_post['banner'];
                                                        if (is_array($banner) && !empty($banner['path'])) {
                                                            $feature_image = '/uploads/' . $banner['path'];
                                                        }
                                                    }
                                                    
                                                    // Fallback image nếu không có ảnh
                                                    if (empty($feature_image)) {
                                                        $feature_image = theme_assets('images/lng-expansion.webp');
                                                    }
                                                    ?>
                                                    <?= _img($feature_image, htmlspecialchars($related_post['title'] ?? ''), false, 'w-[135px] h-[90px] object-cover') ?>
                                                </a>
                                                <div class="flex-1">
                                                    <h3 class="text-sm font-medium text-gray-900 leading-tight hover:text-sky-600 transition-colors">
                                                        <a href="<?= link_single($related_post['slug'] ?? '', $related_post['posttype'] ?? $post['posttype']) ?>">
                                                            <?= htmlspecialchars($related_post['title'] ?? '') ?>
                                                        </a>
                                                    </h3>
                                                </div>
                                            </article>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <!-- Fallback nếu không có related posts -->
                                    <p class="text-gray-500 text-sm">No related articles found.</p>
                                <?php endif; ?>
                            </div>

                        </div>