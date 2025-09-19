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
                    <form action="/search.html" method="get">
                        <div class="relative">
                            <input name="q" type="text" placeholder="Type keywords...." class="w-full px-4 py-2 pl-4 pr-10 bg-white border border-gray-300 rounded-[999px] text-sm focus:outline-none">
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
                        <!-- Left Column -->
                        <div class="space-y-2">
                            <a href="/category.html" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">Chính trị</a>
                            <a href="/category.html" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">Kinh doanh</a>
                            <a href="/category.html" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">Thể thao</a>
                            <a href="/category.html" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">Thế giới</a>
                            <a href="/category.html" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">Văn hóa – Giải trí</a>
                            <a href="/category.html" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">Công nghệ</a>
                            <a href="/category.html" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">Ô tô xe máy</a>
                            <a href="/category.html" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">Bất động sản</a>
                            <a href="/category.html" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">Tuần Việt Nam</a>
                            <a href="/category.html" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">Công nghiệp hỗ trợ</a>
                            <a href="/category.html" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">Thị trường tiêu dùng</a>
                            <a href="/category.html" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">Nông thôn mới</a>
                            <a href="/category.html" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">Nội dung chuyên đề</a>
                            <a href="/category.html" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">Talks</a>
                            <a href="/category.html" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">Hồ sơ</a>
                            <a href="/category.html" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">Video</a>
                            <a href="/category.html" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">Podcast</a>
                            <a href="/category.html" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">Lịch vạn niên</a>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-2">
                            <a href="/category.html" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">Thời sự</a>
                            <a href="/category.html" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">Dân tộc và Tôn giáo</a>
                            <a href="/category.html" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">Giáo dục</a>
                            <a href="/category.html" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">Đời sống</a>
                            <a href="/category.html" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">Sức khỏe</a>
                            <a href="/category.html" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">Pháp luật</a>
                            <a href="/category.html" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">Du lịch</a>
                            <a href="/category.html" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">Bạn đọc</a>
                            <a href="/category.html" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">Toàn văn</a>
                            <a href="/category.html" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">Bảo vệ người tiêu dùng</a>
                            <a href="/category.html" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">Giảm nghèo bền vững</a>
                            <a href="/category.html" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">Dân tộc thiểu số và miền núi</a>
                            <a href="/category.html" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">English</a>
                            <a href="/category.html" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">Đính chính</a>
                            <a href="/category.html" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">Ảnh</a>
                            <a href="/category.html" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">Multimedia</a>
                            <a href="/category.html" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">24h qua</a>
                        </div>
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
                        <img src="https://static.vnncdn.net/v1/icon/VietnamNet-bridge-vien-trang.svg" alt="VietnamNet Global" class="h-12 sm:h-16">
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
                            <a title="facebook vietnamnet" target="_blank" href="https://www.facebook.com/vietnamnet.vn" class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-gray-700 transition-colors">
                                <img src="https://static.vnncdn.net/v1/icon/facebook-black.svg" alt="Facebook" class="w-4 h-4 filter invert">
                            </a>
                            <a title="youtube vietnamnet" target="_blank" href="https://www.youtube.com/c/B%C3%A1oVietNamNetTV" class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-gray-700 transition-colors">
                                <img src="https://static.vnncdn.net/v1/icon/youtube-black.svg" alt="YouTube" class="w-4 h-4 filter invert">
                            </a>
                            <a title="tiktok vietnamnet" target="_blank" href="https://www.tiktok.com/@vietnamnet.vn" class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-gray-700 transition-colors">
                                <img src="https://static.vnncdn.net/v1/icon/tiktok-black.svg" alt="TikTok" class="w-4 h-4 filter invert">
                            </a>
                            <a title="twitter vietnamnet" target="_blank" href="https://twitter.com/vietnamnetvn" class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-gray-700 transition-colors">
                                <img src="https://static.vnncdn.net/v1/icon/twitter-black.svg" alt="Twitter" class="w-4 h-4 filter invert">
                            </a>
                            <a title="zalo vietnamnet" target="_blank" href="http://zalo.me/660139855964186242?src=qr" class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-gray-700 transition-colors">
                                <img src="https://static.vnncdn.net/v1/icon/zalo-black.svg" alt="Zalo" class="w-4 h-4 filter invert">
                            </a>
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
    <script src="js/main.js"></script>
    <script src="js/index.js"></script>

</body>

</html>