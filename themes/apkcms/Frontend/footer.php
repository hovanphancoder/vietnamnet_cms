<?php
use App\Models\FastModel;


?>

</main>

    <!-- Mobile Menu Popup -->
    <div id="mobileMenuPopup" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="fixed left-0 top-0 h-full w-full  bg-[#f4f4f4] transform -translate-x-full transition-transform duration-300 ease-in-out flex flex-col" id="mobileMenuSidebar">
            <!-- Header - Fixed -->
            <div class="bg-white flex items-center justify-between h-12 px-4 border-b border-gray-200 flex-shrink-0">
                <button onclick="closeMobileMenu()" class="p-1 hover:bg-gray-100 rounded-full">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                <div class="flex-1 flex justify-center">
                    <img src="https://static.vnncdn.net/v1/icon/VietnamNet-bridge-vien-trang.svg" alt="VietnamNet" class="h-10">
                </div>
                <div class="w-6"></div>
            </div>

            <!-- Scrollable Content -->
            <div class="flex-1 overflow-y-auto">
                <!-- Search Bar -->
                <div class="p-4 border-b border-gray-200">
                    <form action="/search/" method="get">
                        <div class="relative">
                            <input name="key" type="text" placeholder="Type keywords...." class="w-full px-4 py-2 pl-4 pr-10 bg-white border border-gray-300 rounded-[999px] text-sm focus:outline-none">
                            <button class="btn-search absolute right-2 top-1/2 transform -translate-y-1/2" type="submit">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Navigation Menu -->
                <div class="p-4">
                    <div class="grid grid-cols-2 gap-4">
                        <?php if (!empty($GLOBALS['categories'])): ?>
                            <?php 
                            $categories = $GLOBALS['categories'];
                            $half = ceil(count($categories) / 2);
                            $left_categories = array_slice($categories, 0, $half);
                            $right_categories = array_slice($categories, $half);
                            ?>
                            
                            <!-- Left Column -->
                            <div class="space-y-2">
                                <?php foreach ($left_categories as $category): ?>
                                    <a href="<?= link_cat($category['slug']) ?>" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">
                                        <?= htmlspecialchars($category['name']) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>

                            <!-- Right Column -->
                            <div class="space-y-2">
                                <?php foreach ($right_categories as $category): ?>
                                    <a href="<?= link_cat($category['slug']) ?>" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">
                                        <?= htmlspecialchars($category['name']) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Copyright Section -->
                <div class="p-4 border-t border-gray-200">
                    <div class="text-center text-sm text-gray-600 space-y-1">
                        <div class="font-medium">© Copyright of Vietnamnet Global.</div>
                        <div class="text-xs">Tel: 024 3772 7988 Fax: (024) 37722734</div>
                        <div class="text-xs">Email: evnn@vietnamnet.vn</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer-en bg-gray-100 py-6 sm:py-8 mt-6 sm:mt-8" style="font-family: 'Noto Sans', sans-serif;">
        <div class="footer-en__bottom max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row items-start justify-between gap-6">
                <!-- Left Side - Logo and Copyright -->
                <div class="footer-en__bottom-left flex flex-col sm:flex-row items-start space-y-4 sm:space-y-0 sm:space-x-8 w-full lg:w-auto">
                    <a class="footer-en__bottom-logo flex-shrink-0" href="/en">
                            <?php
                                    $site_logo = option('site_logo');
                                    // Decode JSON string to array
                                    $site_logoData = json_decode($site_logo, true);
                                    
                                    if ($site_logoData && isset($site_logoData['path'])) {
                                        $logoUrl ='/uploads/' . $site_logoData['path'];
                                    } else {
                                        $logoUrl = theme_assets('/images/logo-icon.webp');
                                    }

                                ?>
                        <img src="<?= $logoUrl ?>" alt="VietnamNet Global" class="h-12 sm:h-16">
                    </a>
                    <div class="footer-en__bottom-list text-sm text-gray-600 space-y-2">
                        <div class="footer-en__bottom-item font-medium">© Copyright of VietNamNet Global</div>
                        <div class="footer-en__bottom-item text-xs leading-relaxed">
                            <span>Tel: 024 3772 7988 Fax: (024) 37722734</span>
                            <span>, </span>
                            <span>Email: evnn@vietnamnet.vn</span>
                        </div>
                    </div>
                </div>

                <!-- Right Side - Follow Us and Social Media -->
                <div class="footer-en__bottom-follow flex flex-col items-end space-y-4">
                    <div class="text-right">
                        <div class="text-sm font-medium text-gray-700 mb-3">Follow us on</div>
                        <div class="footer-en__bottom-social flex items-center space-x-2">
                            <?php
                            $social_links = [];
                            $social_option = option('social');
                            if (!empty($social_option)) {
                                $social_links = is_string($social_option) ? json_decode($social_option, true) : $social_option;
                                if (!is_array($social_links)) {
                                    $social_links = [];
                                }
                            }
                            // Chỉ hiển thị 5 social networks cụ thể
                            $allowed_networks = ['facebook', 'youtube', 'tiktok', 'x', 'zalo'];
                            
                            // Mapping network names to icons (sử dụng file local)
                            $icon_mapping = [
                                'facebook' => '/themes/apkcms/Frontend/Assets/icons/facebook-black.svg',
                                'youtube' => '/themes/apkcms/Frontend/Assets/icons/youtube-black.svg',
                                'tiktok' => '/themes/apkcms/Frontend/Assets/icons/tiktok-black.svg',
                                'x' => '/themes/apkcms/Frontend/Assets/icons/twitter-black.svg',
                                'zalo' => '/themes/apkcms/Frontend/Assets/icons/zalo-black.svg'
                            ];
                            
                            
                            // Debug: Kiểm tra dữ liệu
                            echo "<!-- Debug social_links: " . print_r($social_links, true) . " -->";
                            
                            // Duyệt qua từng social link trong mảng
                            if (!empty($social_links) && is_array($social_links)):
                                foreach ($social_links as $social):
                                    $network = $social['network'] ?? '';
                                    $url = $social['url'] ?? '';
                                    
                                    // Chỉ hiển thị nếu network nằm trong danh sách cho phép
                                    if (in_array($network, $allowed_networks) && !empty($url)):
                                        $icon_url = $icon_mapping[$network] ?? '';
                                        echo "<!-- Debug: network=$network, url=$url, icon_url=$icon_url -->";
                                        if (!empty($icon_url)):
                            ?>
                                <a title="<?= htmlspecialchars($network) ?> vietnamnet" 
                                   target="_blank" 
                                   href="<?= htmlspecialchars($url) ?>" 
                                   class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-gray-700 transition-colors">
                                    <img src="<?= htmlspecialchars($icon_url) ?>" 
                                         alt="<?= htmlspecialchars($network) ?>" 
                                         class="w-4 h-4 filter invert">
                                </a>
                            <?php 
                                        endif;
                                    endif;
                                endforeach;
                            endif;
                            ?>
                           
                        </div>
                    </div>
                    <div class="flex space-x-4">
                        <a class=" text-gray-800  text-sm font-medium hover:bg-gray-50  border-gray-300 " href="/news_register.html">Độc giả gửi bài</a>
                        <a class=" text-gray-800  text-sm font-medium hover:bg-gray-50  border-gray-300 " href="/">Tuyển dụng</a>
                    </div>
                </div>
            </div>
        </div>


    </footer>

    <!-- JavaScript Files -->
    <script src="/themes/apkcms/Frontend/Assets/js/script.js"></script>
    <script src="/themes/apkcms/Frontend/Assets/js/index.js"></script>
    <script src="/themes/apkcms/Frontend/Assets/js/main.js"></script>

</body>

</html>