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

get_template('_metas/meta_single', $meta_data);





?>
  <div class="max-w-7xl mx-auto">
            <!-- Main Content Layout -->
            <div class="flex flex-col lg:flex-row gap-0 lg:gap-8 pt-0 lg:pt-6">
                <!-- Left Column - Main Content -->
                <div class="flex-1 ">
                    <!-- Article Header -->
                    <div class="mb-6 w-full lg:w-[760px]">
                        <!-- Breadcrumb Navigation Desktop -->
                        <nav class=" mb-4 lg:flex flex-col sm:flex-row sm:items-center sm:justify-between text-sm  gap-2 sm:gap-0" aria-label="Breadcrumb">
                            <!-- Left side: Breadcrumb path -->
                            <div class="flex items-center">

                                <a href="/" class="flex lg:hidden items-center text-gray-500 hover:text-blue-600">
                                    <img class="w-3 h-3" src="https://static.vnncdn.net/v1/icon/home_mobile.svg" alt="home icon">
                                </a>
                                <a href="/business" class="text-[#2d67ad] text-[16px] font-bold   transition-colors text-sm sm:text-base">
                                    Business
                                </a>

                            </div>
                            <!-- Right side: Date and time -->
                            <div class="text-gray-500 hidden lg:block text-xs sm:text-sm text-[12px]">
                                22/09/2025 10:25 (GMT+07:00)
                            </div>
                        </nav>

                        <h1 class="merriweather-bold text-2xl sm:notosans-bold sm:text-3xl font-bold text-gray-h1 mb-4 leading-tight">
                            Billions on paper: Vietnam's LNG power ambitions stalled by policy bottlenecks
                        </h1>

                        <div class="text-gray-500 lg:hidden text-xs sm:text-sm text-[12px]">
                            22/09/2025 10:25 (GMT+07:00)
                        </div>
                        <div class="flex flex-wrap flex-col sm:flex-row sm:items-center sm:justify-between mb-0 lg:mb-6 ">
                            <div class="flex items-center space-x-2 mb-3 sm:mb-0">
                                <!-- Share Buttons -->

                                <div class="hidden lg:flex gap-2">
                                    <button class="flex items-center justify-center min-w-[30px] w-[30px] h-[30px] border border-gray-300 rounded-full hover:bg-gray-50 transition-colors">
                                        <svg width="16px" height="16px" viewBox="-5 0 20 20" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="#000000">
                                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                            <g id="SVGRepo_iconCarrier">
                                                <title>facebook [#757575]</title>
                                                <desc>Created with Sketch.</desc>
                                                <defs> </defs>
                                                <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                                    <g id="Dribbble-Light-Preview" transform="translate(-385.000000, -7399.000000)" fill="#757575">
                                                        <g id="icons" transform="translate(56.000000, 160.000000)">
                                                            <path d="M335.821282,7259 L335.821282,7250 L338.553693,7250 L339,7246 L335.821282,7246 L335.821282,7244.052 C335.821282,7243.022 335.847593,7242 337.286884,7242 L338.744689,7242 L338.744689,7239.14 C338.744689,7239.097 337.492497,7239 336.225687,7239 C333.580004,7239 331.923407,7240.657 331.923407,7243.7 L331.923407,7246 L329,7246 L329,7250 L331.923407,7250 L331.923407,7259 L335.821282,7259 Z" id="facebook-[#757575]"> </path>
                                                        </g>
                                                    </g>
                                                </g>
                                            </g>
                                        </svg>
                                    </button>
                                    <button class="flex items-center justify-center min-w-[30px] w-[30px] h-[30px] border border-gray-300 rounded-full hover:bg-gray-50 transition-colors">
                                        <img src="https://static.vnncdn.net/v1/icon/zalo-unactive-1.svg" alt="Zalo" class="w-[40px] h-[40px]">
                                    </button>
                                    <button class="flex items-center justify-center min-w-[30px] w-[30px] h-[30px] border border-gray-300 rounded-full hover:bg-gray-50 transition-colors">
                                        <svg class="w-[16px] h-3 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
                                        </svg>
                                    </button>
                                    <button class="flex items-center justify-center min-w-[30px] w-[30px] h-[30px] border border-gray-300 rounded-full hover:bg-gray-50 transition-colors">
                                        <svg class="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                                            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                                        </svg>
                                    </button>
                                    <button class="flex items-center justify-center min-w-[30px] w-[30px] h-[30px] border border-gray-300 rounded-full hover:bg-gray-50 transition-colors">
                                        <img src="https://static.vnncdn.net/v1/icon/icon-share-link-bookmark.svg" alt="Twitter" class="w-[12px] h-[12px]">
                                    </button>
                                </div>


                                <a class="flex items-center px-0 lg:px-3 py-2 ml-0 m-0 transition-colors" style="margin-left:0 !important" href="https://news.google.com/publications/CAAqBwgKMNi4kgswo_-nAw/sections/CAQqEAgAKgcICjDYuJILMKP_pwMw7pCTBw?ceid=VN:vi&oc=3&hl=vi&gl=VN" target="_blank" rel="noopener nofollow">
                                    <img src="https://static.vnncdn.net/v1/icon/google-news-en.svg" alt="Google News" class="h-[30px] ml-0">
                                </a>
                            </div>
                            <!-- Google News Button -->
                            <div class="flex items-center">

                            </div>
                        </div>
                        <!-- Article Summary -->
                        <h2 class="arial  text-gray-h2 mb-6 arial font-bold leading-relaxed">
                            Vietnam's Power Development Plan VIII placed strong emphasis on liquefied natural gas (LNG) as a transitional energy source to gradually replace coal-fired power and ensure energy security amid the shift to renewables. Yet many LNG projects worth billions of dollars remain stuck "on paper" due to persistent policy and administrative hurdles, including unresolved land clearance issues.
                        </h2>
                    </div>
                    <!-- Article Content -->
                    <div class="prose prose-lg max-w-none">
                        <!-- Main Image -->

                        <!-- Article Text -->
                        <div class="text-gray-p space-y-4 font-arial text-base leading-relaxed">
                            <p><strong>Bureaucratic bottlenecks persist</strong></p>
                            <p>According to the revised Power Development Plan VIII, Vietnam's LNG import-based power capacity is expected to rise from 0.8 GW to 22.5 GW by 2030, through the development of 15 projects. This would represent approximately 12.3% of the country's total power generation capacity - a critical share intended to replace coal and balance the rising share of renewables.</p>
                            <p>In practice, however, many of these projects are moving slowly or remain stagnant. For instance, the USD 2.2 billion Quang Ninh LNG project is still facing land clearance obstacles. The provincial government has issued a final warning and threatened forced land acquisition if owners refuse to hand over the land.</p>
                            <p>In Ca Na, a 1,500 MW LNG project received only one bid during its tender in July, with a proposed tariff of 12.83 US cents per kWh - an unusually high rate reflecting investor concerns over policy and financial risks.</p>
                            <p>Projects that were once expected to lead Vietnam's LNG development - such as Son My I and II (in Lam Dong province) and the Son My LNG terminal - are still stuck in paperwork and financing limbo. The Nghi Son LNG project, valued at USD 2.2 billion (equivalent to about 57,000–58,000 billion VND) and with a 1,500 MW capacity, is considered a vital link in the revised national plan. But after two bidding rounds and two extensions, the project was cancelled due to lack of interest from investors.</p>
                            <p>Another prominent example is the 3,200 MW Bac Lieu LNG power plant, which has been dormant for years. Despite receiving its investment certificate in January 2020 and being scheduled to begin phase one by late 2023, the project has not broken ground. Over five years later, unresolved land clearance and compensation issues have left the site idle.</p>
                        </div>
                        <!-- ------------------- -->
                        <!-- Info Box -->
                        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
                            <p class="text-sm text-gray-p italic">
                                One rare bright spot is the Nhon Trach 3 and 4 LNG projects. Earlier this year, Nhon Trach 3 was connected to the grid, and by the end of June, Nhon Trach 4 delivered its first power using imported LNG - marking the first time Vietnam generated electricity from LNG.
                            </p>
                        </div>
                        <!-- Author -->
                        <p class="notosans-bold font-bold mt-6">Tien Phong</p>
                        <!-- Related Articles -->
                        <!-- Related Articles -->
                        <div class="mt-6 pt-4 mb-6 border-t border-[#add2e1] related-articles">
                            <ul class="space-y-3 ml-0 ">
                                <li class="flex items-start">
                                    <span class="text-[#2d67ad] font-bold mr-3 mt-1 text-[8px] leading-[14px]">■</span>
                                    <a href="/en/vietnam-s-lam-dong-set-to-lead-power-production-with-lng-expansion-2422194.html" class="text-[#6c6c6c] font-bold hover:text-[#0a569d] notosans-bold text-sm leading-6 no-underline transition-colors" title="Vietnam's Lam Dong set to lead power production with LNG expansion">
                                        Vietnam's Lam Dong set to lead power production with LNG expansion
                                    </a>
                                </li>
                                <li class="flex items-start">
                                    <span class="text-[#2d67ad] font-bold mr-3 mt-1 text-[8px] leading-[14px]">■</span>
                                    <a href="/en/vietnam-s-lng-power-push-advances-with-nhon-trach-4-debut-2415990.html" class="text-[#6c6c6c] font-bold hover:text-[#0a569d] notosans-bold text-sm leading-6 no-underline transition-colors" title="Vietnam's LNG power push advances with Nhon Trach 4 debut">
                                        Vietnam's LNG power push advances with Nhon Trach 4 debut
                                    </a>
                                </li>
                                <li class="flex items-start">
                                    <span class="text-[#2d67ad] font-bold mr-3 mt-1 text-[8px] leading-[14px]">■</span>
                                    <a href="/en/pm-calls-for-us-firm-s-cooperation-in-developing-lng-hub-in-vietnam-2406107.html" class="text-[#6c6c6c] font-bold hover:text-[#0a569d] notosans-bold text-sm leading-6 no-underline transition-colors" title="PM calls for US firm's cooperation in developing LNG hub in Vietnam">
                                        PM calls for US firm's cooperation in developing LNG hub in Vietnam
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 ">
                            <div class="flex items-center space-x-2 mb-3 sm:mb-0">
                                <!-- Share Buttons -->
                                <button class="bg-[#2d67ad] border border-[#2d67ad] rounded-[50px] text-white cursor-pointer notosans-bold text-xs px-[15px] py-[7px] hover:bg-[#1e4a7a] hover:border-[#1e4a7a] transition-colors">
                                    Comment
                                </button>
                                <button class="flex items-center justify-center w-[30px] h-[30px] border border-gray-300 rounded-full hover:bg-gray-50 transition-colors">
                                    <svg width="16px" height="16px" viewBox="-5 0 20 20" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="#000000">
                                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                        <g id="SVGRepo_iconCarrier">
                                            <title>facebook [#757575]</title>
                                            <desc>Created with Sketch.</desc>
                                            <defs> </defs>
                                            <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                                <g id="Dribbble-Light-Preview" transform="translate(-385.000000, -7399.000000)" fill="#757575">
                                                    <g id="icons" transform="translate(56.000000, 160.000000)">
                                                        <path d="M335.821282,7259 L335.821282,7250 L338.553693,7250 L339,7246 L335.821282,7246 L335.821282,7244.052 C335.821282,7243.022 335.847593,7242 337.286884,7242 L338.744689,7242 L338.744689,7239.14 C338.744689,7239.097 337.492497,7239 336.225687,7239 C333.580004,7239 331.923407,7240.657 331.923407,7243.7 L331.923407,7246 L329,7246 L329,7250 L331.923407,7250 L331.923407,7259 L335.821282,7259 Z" id="facebook-[#757575]"> </path>
                                                    </g>
                                                </g>
                                            </g>
                                        </g>
                                    </svg>
                                </button>
                                <button class="flex items-center justify-center w-[30px] h-[30px] border border-gray-300 rounded-full hover:bg-gray-50 transition-colors">
                                    <img src="https://static.vnncdn.net/v1/icon/zalo-unactive-1.svg" alt="Zalo" class="w-[40px] h-[40px]">
                                </button>
                                <button class="flex items-center justify-center w-[30px] h-[30px] border border-gray-300 rounded-full hover:bg-gray-50 transition-colors">
                                    <svg class="w-[16px] h-3 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
                                    </svg>
                                </button>
                                <button class="flex items-center justify-center w-[30px] h-[30px] border border-gray-300 rounded-full hover:bg-gray-50 transition-colors">
                                    <svg class="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                                    </svg>
                                </button>
                                <button class="flex items-center justify-center w-[30px] h-[30px] border border-gray-300 rounded-full hover:bg-gray-50 transition-colors">
                                    <img src="https://static.vnncdn.net/v1/icon/icon-share-link-bookmark.svg" alt="Twitter" class="w-[12px] h-[12px]">
                                </button>

                            </div>
                            <!-- Google News Button -->
                            <div class="flex items-center">

                            </div>
                        </div>
                    </div>

                    <!-- Tags -->
                    <div class=" ">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm text-gray-500 font-medium">Topic:</span>
                            <a href="#" class="text-[#555] text-sm inline-block px-[10px] py-[2px] no-underline hover:underline transition-colors border border-gray-300 rounded-full">
                                business news
                            </a>
                            <a href="#" class="text-[#555] text-sm inline-block px-[10px] py-[2px] no-underline hover:underline transition-colors border border-gray-300 rounded-full">
                                LNG
                            </a>
                            <a href="#" class="text-[#555] text-sm inline-block px-[10px] py-[2px] no-underline hover:underline transition-colors border border-gray-300 rounded-full">
                                features
                            </a>
                            <a href="#" class="text-[#555] text-sm inline-block px-[10px] py-[2px] no-underline hover:underline transition-colors border border-gray-300 rounded-full">
                                vietnam's power industry
                            </a>
                        </div>
                    </div>
                    <!-- Comment Section -->
                    <div class="mt-8 pt-6 ">
                        <h3 class="text-lg notosans-bold mb-4 text-gray-h3 uppercase text-[#0a569d] font-bold">Comments</h3>
                        <div class="block h-[70px] w-full border border-[#CDE3FF] bg-[#EEF5FF] rounded-[5px] cursor-text "><input class=" bg-[#EEF5FF] h-[40px] w-full rounded-[5px] py-[10px] px-[10px] focus:outline-none " type="text" placeholder="Your comment...."></div>
                    </div>
                </div>
                <!-- Right Column - Sidebar -->
                <div class="w-full lg:w-1/3 space-y-6  pt-10 w-[300px]">
                    <!-- Sticky Sidebar Content -->
                    <div class="lg:sticky lg:top-6 space-y-6">

                        <!-- Section You might be interested in-->
                        <div class="pt-4">
                            <h2 class="text-lg notosans-bold text-[#2d67ad] uppercase font-bold border-b border-[#add2ff] mb-4 pb-2">READ MORE</h2>
                            <div class="space-y-4">
                                <article class="flex space-x-3">
                                    <img class="w-[135px] h-[90px] object-cover " src="https://static-images.vnncdn.net/vps_images_publish/000001/00000Q/2025/5/1/vietnam-cuts-lng-import-tariff-to-boost-clean-energy-transition-66869dc66a9d44709ba2d4c02f093560-91876.webp?width=260&s=vt3pApaCIWf71lP7z3APQQ" alt="Vietnam Lam Dong LNG expansion">
                                    <div class="flex-1">
                                        <h3 class="text-sm font-medium text-gray-900 leading-tight hover:text-blue-600 transition-colors">
                                            <a href="#">Vietnam's Lam Dong set to lead power production with LNG expansion</a>
                                        </h3>
                                    </div>
                                </article>
                                <article class="flex space-x-3">
                                    <img class="w-[135px] h-[90px] object-cover " src="https://static-images.vnncdn.net/vps_images_publish/000001/00000Q/2025/5/1/vietnam-cuts-lng-import-tariff-to-boost-clean-energy-transition-66869dc66a9d44709ba2d4c02f093560-91876.webp?width=260&s=vt3pApaCIWf71lP7z3APQQ" alt="Vietnam LNG power push advances">
                                    <div class="flex-1">
                                        <h3 class="text-sm font-medium text-gray-900 leading-tight hover:text-blue-600 transition-colors">
                                            <a href="#">Vietnam's LNG power push advances with Nhon Trach 4 debut</a>
                                        </h3>
                                    </div>
                                </article>
                                <article class="flex space-x-3">
                                    <img class="w-[135px] h-[90px] object-cover" src="https://static-images.vnncdn.net/vps_images_publish/000001/00000Q/2025/5/1/vietnam-cuts-lng-import-tariff-to-boost-clean-energy-transition-66869dc66a9d44709ba2d4c02f093560-91876.webp?width=260&s=vt3pApaCIWf71lP7z3APQQ" alt="PM calls for US firm cooperation">
                                    <div class="flex-1">
                                        <h3 class="text-sm font-medium text-gray-900 leading-tight hover:text-blue-600 transition-colors">
                                            <a href="#">PM calls for US firm's cooperation in developing LNG hub in Vietnam</a>
                                        </h3>
                                    </div>
                                </article>
                            </div>

                            <div class="text-center my-6">
                                <a href="/" class="inline-flex items-center px-4 py-2 text-[#2d67ad] border border-[#2d67ad] text-xs uppercase font-bold rounded-full hover:bg-[#2d67ad] hover:text-white transition-colors" title="GO BACK TO THE HOME PAGE">
                                    <img class="mr-2" src="https://static.vnncdn.net/v1/icon/icon-pre-bule-sm.png">
                                    GO BACK TO THE HOME PAGE
                                </a>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <!-- Main News Section -->
            <!-- Layout Component - Two Column Layout with Flexbox and Tailwind CSS -->
            <!-- Mobile: 1 column vertical (Main content first, Sidebar second) -->
            <!-- Desktop (≥1024px): 2 columns horizontal (Main content 2/3, Sidebar 1/3) -->
            <div class="flex flex-col lg:flex-row gap-6 lg:gap-8">
                <!-- Main Content Column -->
                <div class="flex-1 lg:w-2/3 ">

                    <!-- Desktop Featured News Section -->
                    <div class="">
                        <h2 class="text-[18px] notosans-bold  text-[#2d67ad] font-bold uppercase pt-6 border-b border-blue-200">Hot news</h2>
                        <!-- Main content ở đây -->
                        <div class="mt-6">
                            <div class="lg:space-y-6 space-y-2">
                                <!-- Article 1 -->
                                <div class="flex gap-4  pb-0">
                                    <div class="w-[130px] sm:w-[240px] h-[86px] sm:h-40 flex-shrink-0">
                                        <a href="/single.html" title="FPT CEO: Vietnamese businesses must unite to succeed">
                                            <img src="https://static-images.vnncdn.net/vps_images_publish/000001/00000Q/2025/9/16/fpt-ceo-vietnamese-businesses-must-unite-to-succeed-af2be71520fb4280adcacdb5fb0639a0-3137.jpg?width=360&s=W4KZRT6rDusoQIi73OwVcg" alt="FPT CEO: Vietnamese businesses must unite to succeed" class="w-[130px] sm:w-[240px] h-[86px] sm:h-40 object-cover">
                                        </a>
                                    </div>
                                    <div class="flex-1 flex flex-col">
                                        <div class="text-xs sm:text-sm font-bold mb-2 text-[#2d67ad] order-2 sm:order-1">VIETNAMNET GLOBAL</div>
                                        <h3 class="text-base font-bold text-gray-900 mb-3 leading-tight order-1 sm:order-2">
                                            <a href="/single.html" title="FPT CEO: Vietnamese businesses must unite to succeed" class="hover:text-[#2d67ad]">
                                                FPT CEO: Vietnamese businesses must unite to succeed
                                            </a>
                                        </h3>
                                        <p class="hidden sm:block text-gray-600 leading-relaxed order-3">
                                            At the Vietnam Private Sector Forum 2025, FPT CEO called for unity, collaboration, and digital skills to drive national transformation.
                                        </p>
                                    </div>
                                </div>
                                <!-- Article 1 -->
                                <div class="flex gap-4  pb-0">
                                    <div class="w-[130px] sm:w-[240px] h-[86px] sm:h-40 flex-shrink-0">
                                        <a href="/single.html" title="FPT CEO: Vietnamese businesses must unite to succeed">
                                            <img src="https://static-images.vnncdn.net/vps_images_publish/000001/00000Q/2025/9/16/fpt-ceo-vietnamese-businesses-must-unite-to-succeed-af2be71520fb4280adcacdb5fb0639a0-3137.jpg?width=360&s=W4KZRT6rDusoQIi73OwVcg" alt="FPT CEO: Vietnamese businesses must unite to succeed" class="w-[130px] sm:w-[240px] h-[86px] sm:h-40 object-cover">
                                        </a>
                                    </div>
                                    <div class="flex-1 flex flex-col">
                                        <div class="text-xs sm:text-sm font-bold mb-2 text-[#2d67ad] order-2 sm:order-1">VIETNAMNET GLOBAL</div>
                                        <h3 class="text-base font-bold text-gray-900 mb-3 leading-tight order-1 sm:order-2">
                                            <a href="/single.html" title="FPT CEO: Vietnamese businesses must unite to succeed" class="hover:text-[#2d67ad]">
                                                FPT CEO: Vietnamese businesses must unite to succeed
                                            </a>
                                        </h3>
                                        <p class="hidden sm:block text-gray-600 leading-relaxed order-3">
                                            At the Vietnam Private Sector Forum 2025, FPT CEO called for unity, collaboration, and digital skills to drive national transformation.
                                        </p>
                                    </div>
                                </div>
                                <!-- Article 1 -->
                                <div class="flex gap-4  pb-0">
                                    <div class="w-[130px] sm:w-[240px] h-[86px] sm:h-40 flex-shrink-0">
                                        <a href="/single.html" title="FPT CEO: Vietnamese businesses must unite to succeed">
                                            <img src="https://static-images.vnncdn.net/vps_images_publish/000001/00000Q/2025/9/16/fpt-ceo-vietnamese-businesses-must-unite-to-succeed-af2be71520fb4280adcacdb5fb0639a0-3137.jpg?width=360&s=W4KZRT6rDusoQIi73OwVcg" alt="FPT CEO: Vietnamese businesses must unite to succeed" class="w-[130px] sm:w-[240px] h-[86px] sm:h-40 object-cover">
                                        </a>
                                    </div>
                                    <div class="flex-1 flex flex-col">
                                        <div class="text-xs sm:text-sm font-bold mb-2 text-[#2d67ad] order-2 sm:order-1">VIETNAMNET GLOBAL</div>
                                        <h3 class="text-base font-bold text-gray-900 mb-3 leading-tight order-1 sm:order-2">
                                            <a href="/single.html" title="FPT CEO: Vietnamese businesses must unite to succeed" class="hover:text-[#2d67ad]">
                                                FPT CEO: Vietnamese businesses must unite to succeed
                                            </a>
                                        </h3>
                                        <p class="hidden sm:block text-gray-600 leading-relaxed order-3">
                                            At the Vietnam Private Sector Forum 2025, FPT CEO called for unity, collaboration, and digital skills to drive national transformation.
                                        </p>
                                    </div>
                                </div>
                                <!-- Article 1 -->
                                <div class="flex gap-4  pb-0">
                                    <div class="w-[130px] sm:w-[240px] h-[86px] sm:h-40 flex-shrink-0">
                                        <a href="/single.html" title="FPT CEO: Vietnamese businesses must unite to succeed">
                                            <img src="https://static-images.vnncdn.net/vps_images_publish/000001/00000Q/2025/9/16/fpt-ceo-vietnamese-businesses-must-unite-to-succeed-af2be71520fb4280adcacdb5fb0639a0-3137.jpg?width=360&s=W4KZRT6rDusoQIi73OwVcg" alt="FPT CEO: Vietnamese businesses must unite to succeed" class="w-[130px] sm:w-[240px] h-[86px] sm:h-40 object-cover">
                                        </a>
                                    </div>
                                    <div class="flex-1 flex flex-col">
                                        <div class="text-xs sm:text-sm font-bold mb-2 text-[#2d67ad] order-2 sm:order-1">VIETNAMNET GLOBAL</div>
                                        <h3 class="text-base font-bold text-gray-900 mb-3 leading-tight order-1 sm:order-2">
                                            <a href="/single.html" title="FPT CEO: Vietnamese businesses must unite to succeed" class="hover:text-[#2d67ad]">
                                                FPT CEO: Vietnamese businesses must unite to succeed
                                            </a>
                                        </h3>
                                        <p class="hidden sm:block text-gray-600 leading-relaxed order-3">
                                            At the Vietnam Private Sector Forum 2025, FPT CEO called for unity, collaboration, and digital skills to drive national transformation.
                                        </p>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="my-12 text-center">
                        <button class="vnn-load-more btn-outline-gray border text-xs notosans-bold uppercase text-blue-primary mb-2 items-center border border-blue-primary px-4 py-1 rounded-full">
                            See more
                            <img class="icon-loading hidden" src="https://static.vnncdn.net/v1/icon/infonet/loading.svg" alt="icon loading">
                        </button>
                    </div>
                </div>
                <!-- Cột phải -->
                <div class="w-full lg:w-1/3">
                </div>
            </div>
            <!-- Video -->
            <!-- <div eventslug="video" class="mt-8 bg-gray-video rounded-lg shadow-sm border border-gray-200" templategroupid="00001O" data-vnn-utm-source="#vnn_source=chitiet&amp;vnn_medium=box_video" categoryid="000001" priority="1" pagesize="5" pageindex="0">
               <div class="px-4 mt-4">
                  <h2 class="text-lg font-bold text-gray-h2 uppercase">
                     <a href="/video/chinh-tri" title="Video chính trị" class="hover:text-blue-600 transition-colors">
                     Video thời sự
                     </a>
                  </h2>
               </div>
               <div class="flex flex-col lg:flex-row">
                  <div class="lg:w-2/3 p-4">
                     <div class="relative mb-4">
                        <a href="/ngan-nguoi-xuc-dong-khi-duoc-tro-ve-thoi-khac-thieng-lieng-nam-1945-2433781.html" data-utm-source="#vnn_source=chitiet&amp;vnn_medium=box_video1" title="Ngàn người xúc động khi được trở về &quot;thời khắc thiêng liêng&quot;  năm 1945" class="block group">
                           <div class="absolute inset-0 flex items-center justify-center z-10">
                              <span class="bg-black bg-opacity-50 rounded-full p-3 group-hover:bg-opacity-70 transition-all">
                              <img src="https://static.vnncdn.net/v1/icon/play-button.svg" alt="play icon" class="w-6 h-6">
                              </span>
                           </div>
                           <picture class="block">
                              <source data-srcset="https://static-images.vnncdn.net/vps_images_publish/000001/000003/2025/8/19/ngan-nguoi-xuc-dong-khi-duoc-tro-ve-thoi-khac-thieng-lieng-nam-1945-4347.gif?width=760&amp;s=5Uc2oyGL-rhv0sweDngMPw" media="(max-width: 767px)" srcset="https://static-images.vnncdn.net/vps_images_publish/000001/000003/2025/8/19/ngan-nguoi-xuc-dong-khi-duoc-tro-ve-thoi-khac-thieng-lieng-nam-1945-4347.gif?width=760&amp;s=5Uc2oyGL-rhv0sweDngMPw">
                              <source data-srcset="https://static-images.vnncdn.net/vps_images_publish/000001/000003/2025/8/19/ngan-nguoi-xuc-dong-khi-duoc-tro-ve-thoi-khac-thieng-lieng-nam-1945-4347.gif?width=760&amp;s=5Uc2oyGL-rhv0sweDngMPw" media="(max-width: 1023px)" srcset="https://static-images.vnncdn.net/vps_images_publish/000001/000003/2025/8/19/ngan-nguoi-xuc-dong-khi-duoc-tro-ve-thoi-khac-thieng-lieng-nam-1945-4347.gif?width=760&amp;s=5Uc2oyGL-rhv0sweDngMPw">
                              <img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" class="w-full h-auto rounded-lg" data-srcset="https://static-images.vnncdn.net/vps_images_publish/000001/000003/2025/8/19/ngan-nguoi-xuc-dong-khi-duoc-tro-ve-thoi-khac-thieng-lieng-nam-1945-4347.gif?width=760&amp;s=5Uc2oyGL-rhv0sweDngMPw" alt="video thumbnail" srcset="https://static-images.vnncdn.net/vps_images_publish/000001/000003/2025/8/19/ngan-nguoi-xuc-dong-khi-duoc-tro-ve-thoi-khac-thieng-lieng-nam-1945-4347.gif?width=760&amp;s=5Uc2oyGL-rhv0sweDngMPw">
                           </picture>
                        </a>
                     </div>
                     <div class="mb-4">
                        <h2 class="text-lg font-bold text-gray-900 leading-tight" data-id="2433781">
                           <a href="/ngan-nguoi-xuc-dong-khi-duoc-tro-ve-thoi-khac-thieng-lieng-nam-1945-2433781.html" data-utm-source="#vnn_source=chitiet&amp;vnn_medium=cungchuyemuc11" title="Ngàn người xúc động khi được trở về &quot;thời khắc thiêng liêng&quot;  năm 1945" class="hover:text-blue-600 transition-colors">
                           Ngàn người xúc động khi được trở về "thời khắc thiêng liêng"  năm 1945
                           </a>
                        </h2>
                     </div>
                  </div>
                  <div class="lg:w-1/3 p-4">
                     <div class="space-y-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class=" flex flex-col">
                           <div class="flex-shrink-0 w-full h-auto relative">
                              <a href="/man-nhan-dan-may-bay-l-39ng-luyen-bay-doi-hinh-bac-thang-chao-mung-2-9-2425830.html" data-utm-source="#vnn_source=chitiet&amp;vnn_medium=cungchuyemuc11" title="Mãn nhãn dàn máy bay L-39NG luyện bay đội hình bậc thang chào mừng 2/9" class="block group">
                                 <picture class="block">
                                    <source data-srcset="https://static-images.vnncdn.net/vps_images_publish/000001/000003/2025/7/25/9-2994.gif?width=260&amp;s=NV2GYgfQ7jWcudJf0DkjaQ" media="(max-width: 1023px)" srcset="https://static-images.vnncdn.net/vps_images_publish/000001/000003/2025/7/25/9-2994.gif?width=260&amp;s=NV2GYgfQ7jWcudJf0DkjaQ">
                                    <img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" class="w-full h-full object-cover rounded" data-srcset="https://static-images.vnncdn.net/vps_images_publish/000001/000003/2025/7/25/9-2994.gif?width=260&amp;s=NV2GYgfQ7jWcudJf0DkjaQ" alt="video thumbnail" srcset="https://static-images.vnncdn.net/vps_images_publish/000001/000003/2025/7/25/9-2994.gif?width=260&amp;s=NV2GYgfQ7jWcudJf0DkjaQ">
                                    <source data-srcset="https://static-images.vnncdn.net/vps_images_publish/000001/000003/2025/7/25/9-2994.gif?width=390&amp;s=2RoUAJxk7VxGKVL8RLXK8Q" media="(max-width: 767px)" srcset="https://static-images.vnncdn.net/vps_images_publish/000001/000003/2025/7/25/9-2994.gif?width=390&amp;s=2RoUAJxk7VxGKVL8RLXK8Q">
                                 </picture>
                                 <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="bg-black bg-opacity-50 rounded-full p-1 group-hover:bg-opacity-70 transition-all">
                                    <img src="https://static.vnncdn.net/v1/icon/video-icon-gray-v1.svg" alt="play icon" class="w-3 h-3">
                                    </span>
                                 </div>
                              </a>
                           </div>
                           <div class="flex-1 min-w-0">
                              <h3 class="text-sm font-medium text-gray-900 leading-tight hover:text-blue-600 transition-colors" data-id="2425830">
                                 <a href="/man-nhan-dan-may-bay-l-39ng-luyen-bay-doi-hinh-bac-thang-chao-mung-2-9-2425830.html" data-utm-source="#vnn_source=chitiet&amp;vnn_medium=cungchuyemuc11" title="Mãn nhãn dàn máy bay L-39NG luyện bay đội hình bậc thang chào mừng 2/9">
                                 Mãn nhãn dàn máy bay L-39NG luyện bay đội hình bậc thang chào mừng 2/9
                                 </a>
                              </h3>
                           </div>
                        </div>
                        <div class="flex flex-col">
                           <div class="flex-shrink-0 w-full h-auto  relative">
                              <a href="/hon-15-nam-ben-bi-tim-danh-phan-cho-dong-doi-2424468.html" data-utm-source="#vnn_source=chitiet&amp;vnn_medium=cungchuyemuc11" title="Hơn 15 năm bền bỉ tìm 'danh phận' cho đồng đội" class="block group">
                                 <picture class="block">
                                    <source data-srcset="https://static-images.vnncdn.net/vps_images_publish/000001/000003/2025/7/24/hon-15-nam-ben-bi-tim-danh-phan-cho-dong-doi-2838.gif?width=260&amp;s=SHoQDlMqIQCY_xpPoCGXLw" media="(max-width: 1023px)" srcset="https://static-images.vnncdn.net/vps_images_publish/000001/000003/2025/7/24/hon-15-nam-ben-bi-tim-danh-phan-cho-dong-doi-2838.gif?width=260&amp;s=SHoQDlMqIQCY_xpPoCGXLw">
                                    <img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" class="w-full h-full object-cover rounded" data-srcset="https://static-images.vnncdn.net/vps_images_publish/000001/000003/2025/7/24/hon-15-nam-ben-bi-tim-danh-phan-cho-dong-doi-2838.gif?width=260&amp;s=SHoQDlMqIQCY_xpPoCGXLw" alt="video thumbnail" srcset="https://static-images.vnncdn.net/vps_images_publish/000001/000003/2025/7/24/hon-15-nam-ben-bi-tim-danh-phan-cho-dong-doi-2838.gif?width=260&amp;s=SHoQDlMqIQCY_xpPoCGXLw">
                                    <source data-srcset="https://static-images.vnncdn.net/vps_images_publish/000001/000003/2025/7/24/hon-15-nam-ben-bi-tim-danh-phan-cho-dong-doi-2838.gif?width=390&amp;s=olzbPyMrWOeL_RoCKVVbvQ" media="(max-width: 767px)" srcset="https://static-images.vnncdn.net/vps_images_publish/000001/000003/2025/7/24/hon-15-nam-ben-bi-tim-danh-phan-cho-dong-doi-2838.gif?width=390&amp;s=olzbPyMrWOeL_RoCKVVbvQ">
                                 </picture>
                                 <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="bg-black bg-opacity-50 rounded-full p-1 group-hover:bg-opacity-70 transition-all">
                                    <img src="https://static.vnncdn.net/v1/icon/video-icon-gray-v1.svg" alt="play icon" class="w-3 h-3">
                                    </span>
                                 </div>
                              </a>
                           </div>
                           <div class="flex-1 min-w-0">
                              <h3 class="text-sm font-medium text-gray-900 leading-tight hover:text-blue-600 transition-colors" data-id="2424468">
                                 <a href="/hon-15-nam-ben-bi-tim-danh-phan-cho-dong-doi-2424468.html" data-utm-source="#vnn_source=chitiet&amp;vnn_medium=cungchuyemuc11" title="Hơn 15 năm bền bỉ tìm 'danh phận' cho đồng đội">
                                 Hơn 15 năm bền bỉ tìm 'danh phận' cho đồng đội
                                 </a>
                                 <a href="https://vietnamnet.vn/hon-15-nam-ben-bi-tim-danh-phan-cho-dong-doi-2424468.html#comment" class="inline-flex items-center ml-2 text-xs text-gray-500 hover:text-blue-600">
                                 <img class="w-3 h-3 mr-1" src="https://static.vnncdn.net/v1/icon/binh-luan.svg" alt="comment icon">
                                 1
                                 </a>
                              </h3>
                           </div>
                        </div>
                        <div class="flex flex-col">
                           <div class="flex-shrink-0 w-full h-auto  relative">
                              <a href="/tphcm-day-manh-chuyen-doi-so-de-van-hanh-mo-hinh-hanh-chinh-2-cap-hieu-qua-2424450.html" data-utm-source="#vnn_source=chitiet&amp;vnn_medium=cungchuyemuc11" title="TPHCM: Đẩy mạnh chuyển đổi số để vận hành mô hình hành chính 2 cấp hiệu quả" class="block group">
                                 <picture class="block">
                                    <source data-srcset="https://static-images.vnncdn.net/vps_images_publish/000001/000003/2025/7/22/tphcm-day-manh-chuyen-doi-so-de-van-hanh-mo-hinh-hanh-chinh-2-cap-hieu-qua-1391.gif?width=260&amp;s=1qYv1HIrEr3Wf4f4vvu9Ng" media="(max-width: 1023px)" srcset="https://static-images.vnncdn.net/vps_images_publish/000001/000003/2025/7/22/tphcm-day-manh-chuyen-doi-so-de-van-hanh-mo-hinh-hanh-chinh-2-cap-hieu-qua-1391.gif?width=260&amp;s=1qYv1HIrEr3Wf4f4vvu9Ng">
                                    <img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" class="w-full h-full object-cover rounded" data-srcset="https://static-images.vnncdn.net/vps_images_publish/000001/000003/2025/7/22/tphcm-day-manh-chuyen-doi-so-de-van-hanh-mo-hinh-hanh-chinh-2-cap-hieu-qua-1391.gif?width=260&amp;s=1qYv1HIrEr3Wf4f4vvu9Ng" alt="video thumbnail" srcset="https://static-images.vnncdn.net/vps_images_publish/000001/000003/2025/7/22/tphcm-day-manh-chuyen-doi-so-de-van-hanh-mo-hinh-hanh-chinh-2-cap-hieu-qua-1391.gif?width=260&amp;s=1qYv1HIrEr3Wf4f4vvu9Ng">
                                    <source data-srcset="https://static-images.vnncdn.net/vps_images_publish/000001/000003/2025/7/22/tphcm-day-manh-chuyen-doi-so-de-van-hanh-mo-hinh-hanh-chinh-2-cap-hieu-qua-1391.gif?width=390&amp;s=0V4PtomqEPLghWsherJIuw" media="(max-width: 767px)" srcset="https://static-images.vnncdn.net/vps_images_publish/000001/000003/2025/7/22/tphcm-day-manh-chuyen-doi-so-de-van-hanh-mo-hinh-hanh-chinh-2-cap-hieu-qua-1391.gif?width=390&amp;s=0V4PtomqEPLghWsherJIuw">
                                 </picture>
                                 <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="bg-black bg-opacity-50 rounded-full p-1 group-hover:bg-opacity-70 transition-all">
                                    <img src="https://static.vnncdn.net/v1/icon/video-icon-gray-v1.svg" alt="play icon" class="w-3 h-3">
                                    </span>
                                 </div>
                              </a>
                           </div>
                           <div class="flex-1 min-w-0">
                              <h3 class="text-sm font-medium text-gray-900 leading-tight hover:text-blue-600 transition-colors" data-id="2424450">
                                 <a href="/tphcm-day-manh-chuyen-doi-so-de-van-hanh-mo-hinh-hanh-chinh-2-cap-hieu-qua-2424450.html" data-utm-source="#vnn_source=chitiet&amp;vnn_medium=cungchuyemuc11" title="TPHCM: Đẩy mạnh chuyển đổi số để vận hành mô hình hành chính 2 cấp hiệu quả">
                                 TPHCM: Đẩy mạnh chuyển đổi số để vận hành mô hình hành chính 2 cấp hiệu quả
                                 </a>
                              </h3>
                           </div>
                        </div>
                        <div class="flex flex-col">
                           <div class="flex-shrink-0 w-full h-auto  relative">
                              <a href="/buoc-tien-moi-ben-vung-cho-vung-dong-bao-dan-toc-thieu-so-va-mien-nui-2420377.html" data-utm-source="#vnn_source=chitiet&amp;vnn_medium=cungchuyemuc11" title="Nâng cao đời sống cho đồng bào dân tộc thiểu số và miền núi" class="block group">
                                 <picture class="block">
                                    <source data-srcset="https://static-images.vnncdn.net/vps_images_publish/000001/000003/2025/7/11/nang-cao-doi-song-cho-dong-bao-dan-toc-thieu-so-va-mien-nui-38820.jpg?width=260&amp;s=WF9zWpelKo2NnS3EzKfWZQ" media="(max-width: 1023px)" srcset="https://static-images.vnncdn.net/vps_images_publish/000001/000003/2025/7/11/nang-cao-doi-song-cho-dong-bao-dan-toc-thieu-so-va-mien-nui-38820.jpg?width=260&amp;s=WF9zWpelKo2NnS3EzKfWZQ">
                                    <img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" class="w-full h-full object-cover rounded" data-srcset="https://static-images.vnncdn.net/vps_images_publish/000001/000003/2025/7/11/nang-cao-doi-song-cho-dong-bao-dan-toc-thieu-so-va-mien-nui-38820.jpg?width=260&amp;s=WF9zWpelKo2NnS3EzKfWZQ" alt="video thumbnail" srcset="https://static-images.vnncdn.net/vps_images_publish/000001/000003/2025/7/11/nang-cao-doi-song-cho-dong-bao-dan-toc-thieu-so-va-mien-nui-38820.jpg?width=260&amp;s=WF9zWpelKo2NnS3EzKfWZQ">
                                    <source data-srcset="https://static-images.vnncdn.net/vps_images_publish/000001/000003/2025/7/11/nang-cao-doi-song-cho-dong-bao-dan-toc-thieu-so-va-mien-nui-38820.jpg?width=390&amp;s=soYHQHK22_fpIfIQsWieHw" media="(max-width: 767px)" srcset="https://static-images.vnncdn.net/vps_images_publish/000001/000003/2025/7/11/nang-cao-doi-song-cho-dong-bao-dan-toc-thieu-so-va-mien-nui-38820.jpg?width=390&amp;s=soYHQHK22_fpIfIQsWieHw">
                                 </picture>
                                 <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="bg-black bg-opacity-50 rounded-full p-1 group-hover:bg-opacity-70 transition-all">
                                    <img src="https://static.vnncdn.net/v1/icon/video-icon-gray-v1.svg" alt="play icon" class="w-3 h-3">
                                    </span>
                                 </div>
                              </a>
                           </div>
                           <div class="flex-1 min-w-0">
                              <h3 class="text-sm font-medium text-gray-900 leading-tight hover:text-blue-600 transition-colors" data-id="2420377">
                                 <a href="/buoc-tien-moi-ben-vung-cho-vung-dong-bao-dan-toc-thieu-so-va-mien-nui-2420377.html" data-utm-source="#vnn_source=chitiet&amp;vnn_medium=cungchuyemuc11" title="Nâng cao đời sống cho đồng bào dân tộc thiểu số và miền núi">
                                 Nâng cao đời sống cho đồng bào dân tộc thiểu số và miền núi
                                 </a>
                              </h3>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
               </div> -->
            <!--  -->
            <!-- Layout Component - Two Column Layout with Flexbox and Tailwind CSS -->
            <!-- Mobile: 1 column vertical (Main content first, Sidebar second) -->
            <!-- Desktop (≥1024px): 2 columns horizontal (Main content 2/3, Sidebar 1/3) -->

        </div>



<?php get_footer(); ?>