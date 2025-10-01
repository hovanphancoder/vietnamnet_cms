<?php
App\Libraries\Fastlang::load('Homepage');
//Render::asset('js', 'js/home-index.js', ['area' => 'frontend', 'location' => 'footer']);

$slug = get_current_slug();

// ===== LẤY THÔNG TIN PAGE =====
// Lấy thông tin page theo slug sử dụng get_post function
$page = get_post([
    'slug' => $slug,
    'posttype' => 'pages',
    'active' => true,
    'columns' => ['*']
]);

//Get Object Data for this Pages
$locale = APP_LANG.'_'.strtoupper(lang_country(APP_LANG));

// Tạo mảng dữ liệu để truyền vào template từ field admin
$meta_data = [
    'locale' => $locale,
    'page_title' => $page['seo_title'] ?? $page['title'] ?? 'Page - ' . option('site_title', APP_LANG),
    'page_description' => $page['seo_desc'] ?? $page['description'] ?? option('site_description', APP_LANG),
    'page_type' => 'page',
    'current_lang' => APP_LANG,
    'site_name' => option('site_title', APP_LANG),
    'page_data' => $page, // Truyền toàn bộ dữ liệu page
    'custom_data' => [
        'page_id' => $page['id'] ?? 0,
        'page_slug' => $page['slug'] ?? '',
        'page_status' => $page['status'] ?? 'inactive',
        'page_created' => $page['created_at'] ?? '',
        'page_updated' => $page['updated_at'] ?? '',
        'has_content' => !empty($page['content']),
        'content_length' => strlen($page['content'] ?? '')
    ]
];

get_template('_metas/meta_page', $meta_data);





?>



 <!-- Article Submission Section -->
 <section class="bg-white py-12">
            <div class="max-w-7xl mx-auto">
                <div class="flex flex-col lg:flex-row gap-8">
                    <!-- Left Section: Article Submission Form -->
                    <div class="lg:w-[760px] space-y-8">
                        <!-- Title -->
                        <div class="mb-6">
                            <h1 class="text-2xl font-bold text-sky-600 mb-4">GỬI BÀI VIẾT ĐẾN BÁO VIETNAMNET</h1>
                            <p class="text-gray-700 leading-relaxed text-sm">
                                Báo VietNamNet mong nhận được những bài viết đóng góp của quý độc giả khắp nơi gửi cho chúng tôi. Nội dung gửi về sẽ được xác minh, chọn lọc biên tập và đăng tải nếu phù hợp với định hướng và tiêu chí của báo. VietNamNet giữ quyền biên tập và xuất bản bài viết của quý độc giả gửi tới. Mọi thắc mắc vui lòng liên hệ hotline <span class="font-semibold text-sky-600">0923 457 788</span>.
                            </p>
                        </div>

                        <!-- Form -->
                        <form class="space-y-4">
                            <!-- Full Name -->
                            <div class="flex items-center gap-4">
                                <label class="w-20 sm:w-32 text-sm font-medium text-gray-700">
                                    <span class="hidden sm:inline">Họ và tên</span>
                                    <span class="sm:hidden">Họ tên</span>
                                    <span class="text-red-500">*</span>
                                </label>
                                <input type="text" class="flex-1 px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Email -->
                            <div class="flex items-center gap-4">
                                <label class="w-20 sm:w-32 text-sm font-medium text-gray-700">
                                    <span class="hidden sm:inline">Nhập email</span>
                                    <span class="sm:hidden">Email</span>
                                    <span class="text-red-500">*</span>
                                </label>
                                <input type="email" class="flex-1 px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Title -->
                            <div class="flex items-center gap-4">
                                <label class="w-20 sm:w-32 text-sm font-medium text-gray-700">
                                    <span class="hidden sm:inline">Tiêu đề</span>
                                    <span class="sm:hidden">Tiêu đề</span>
                                    <span class="text-red-500">*</span>
                                </label>
                                <input type="text" class="flex-1 px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- File Upload -->
                            <div class="flex items-center gap-4">
                                <label class="w-20 sm:w-32 text-sm font-medium text-gray-700">
                                    <span class="hidden sm:inline">File đính kèm</span>
                                    <span class="sm:hidden">File</span>
                                </label>
                                <div class="flex-1 relative">
                                    <input type="file" id="file-input" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" multiple>
                                    <div class="px-3 py-2 border-2 border-dashed border-blue-300 bg-blue-50 rounded focus-within:ring-1 focus-within:ring-blue-500 focus-within:border-blue-500 hover:border-blue-400 transition-colors w-fit">
                                        <div class="flex items-center justify-between gap-3">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                            </svg>
                                            <span class="text-gray-500 text-sm" id="file-text">Chọn file</span>

                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">jpg, jpeg, png, gif, doc, docx, pdf, txt</p>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="flex items-start gap-4">
                                <label class="w-20 sm:w-32 text-sm font-medium text-gray-700 mt-2">
                                    <span class="hidden sm:inline">Nội dung</span>
                                    <span class="sm:hidden">Nội dung</span>
                                    <span class="text-red-500">*</span>
                                </label>
                                <textarea rows="8" class="flex-1 px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 resize-vertical"></textarea>
                            </div>

                            <!-- Required Field Note -->
                            <p class="text-xs text-gray-500">
                                Phần có dấu (*) là thông tin bắt buộc
                            </p>

                            <!-- Submit Button -->
                            <button type="submit" class="bg-[#2d67ad] text-white py-2 px-6 rounded font-medium hover:bg-[#1e4a7a] transition-colors">
                                Gửi
                            </button>
                        </form>
                    </div>

                    <!-- Right Section: Editorial Office Information -->
                    <div class="lg:w-[300px]">
                        <!-- Title -->
                        <div class="mb-6">
                            <h2 class="text-2xl font-bold text-sky-600 mb-4">Thông tin tòa soạn</h2>
                        </div>

                        <!-- Editorial Office Illustration -->
                        <div class="mb-6">
                            <div class="">
                                <img src="https://static.vnncdn.net/v1/pictures/lien-he-toa-soan/lien-he-toa-soan.jpg" alt="Thông tin tòa soạn" class="w-full h-auto rounded">
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="space-y-3 text-sm">
                            <!-- Hanoi Office -->
                            <div>
                                <span class="font-bold text-gray-900">Tòa soạn:</span>
                                <p class="text-gray-700 mt-1">
                                    Tầng 18, Toà nhà Cục Viễn thông (VNTA),<br>
                                    68 Dương Đình Nghệ, phường Cầu Giấy, TP. Hà Nội.
                                </p>
                            </div>
                            <div>
                                <span class="font-bold text-gray-900">Đường dây nóng:</span>
                                <span class="text-gray-700 ml-2">0923 457 788</span>
                            </div>
                            <div>
                                <span class="font-bold text-gray-900">Điện thoại:</span>
                                <span class="text-gray-700 ml-2">(024) 39369898</span>
                            </div>
                            <div>
                                <span class="font-bold text-gray-900">Fax:</span>
                                <span class="text-gray-700 ml-2">(024) 39369696</span>
                            </div>
                            <div>
                                <span class="font-bold text-gray-900">Email:</span>
                                <span class="text-gray-700 ml-2">vietnamnet@vietnamnet.vn</span>
                            </div>

                            <!-- Divider -->
                            <div class="border-t border-gray-300 my-4"></div>

                            <!-- Ho Chi Minh City Office -->
                            <div>
                                <span class="font-bold text-gray-900">Văn phòng đại diện tại TP.HCM:</span>
                                <p class="text-gray-700 mt-1">
                                    27 Nguyễn Bỉnh Khiêm, Phường Sài Gòn, TP.HCM
                                </p>
                            </div>
                            <div>
                                <span class="font-bold text-gray-900">Điện thoại:</span>
                                <span class="text-gray-700 ml-2">028.38181436</span>
                            </div>
                            <div>
                                <span class="font-bold text-gray-900">Fax:</span>
                                <span class="text-gray-700 ml-2">028.38181433</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>



<?php get_footer(); ?>