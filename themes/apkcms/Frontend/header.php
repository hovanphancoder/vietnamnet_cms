<?php
use App\Models\FastModel;
require_once __DIR__ . '/functions.php';

$current_page = get_current_page();
// var_dump($current_page);

$all_categories = get_categories('posts','category');
$GLOBALS['categories'] = $all_categories;
// var_dump($GLOBALS['categories']);

?>
<!DOCTYPE html>
<html lang="<?= lang_code() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    echo $meta ?? '';
    echo $schema ?? '';
    echo $append ?? '';
    ?>
    <?= \System\Libraries\Render::renderAsset('head', 'frontend') ?>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- <link href="css/style.css" rel="stylesheet"> -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="">

  

    <!-- Main Navigation Bar -->
    <header class="bg-white border-b border-gray-200 fixed left-0 top-0 w-full z-40">
        <!-- Top Header Bar -->
        <div class="bg-white border-b border-gray-200  ">
            <div class="container max-w-[1140px] mx-auto px-4">
                <div class="flex items-center justify-between h-12">
                    <!-- Left Side - Logo and Date -->
                    <div class="flex items-center space-x-4">


                        <!-- Date -->
                        <div class="text-sm text-gray-600 font-medium">
                            <!-- Desktop: Goback link -->
                            <a class="goback hidden lg:inline-flex items-center gap-2 px-2 py-0 border border-gray-300 rounded-full" href="/">
                                <img src="https://static.vnncdn.net/v1/icon/return.png" class="w-4 h-4" alt="return icon">
                                <span class="hidden sm:inline">Vietnamnet.vn</span>
                            </a>

                            <!-- Mobile: Hamburger menu icon -->
                            <button class="lg:hidden inline-flex items-center justify-center w-8 h-8 text-gray-600 hover:text-gray-900" onclick="toggleMobileMenu()">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center space-x-4">
                        <!-- Vietnamnet Logo -->
                        <div class="flex items-center space-x-2">
                            <a href="index.html">
                                <img class="w-[140px]" src="https://static.vnncdn.net/v1/logo/logoVietnamNet.svg" alt="VietNamNet">
                            </a>
                        </div>
                    </div>

                    <!-- Right Side - Links and Login -->
                    <div class="flex items-center space-x-4 md:w-[200px] justify-end">

                        <!-- Search -->
                        <div class="flex items-center space-x-3 relative">
                            <!-- Desktop: Expandable search form -->
                            <div class="hidden md:block relative">
                                <form class="search-small__form  flex items-center" action="/search.html">
                                    <input id="searchInput" class="search-small__form-input w-0 h-[28px] px-0 pr-10 border border-gray-300 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-300 ease-in-out" name="key" type="text" placeholder="Type keywords....">
                                    <button id="searchToggleDesktop" class="search-small__form-btn absolute right-2 top-1/2 transform -translate-y-1/2 w-6 h-6 flex items-center justify-center hover:bg-gray-100 rounded-full transition-colors" type="button">
                                        <img src="https://static.vnncdn.net/v1/icon/search.png" alt="icon search" class="w-4 h-4">
                                    </button>
                                </form>
                            </div>

                            <!-- Mobile: Search icon with dropdown -->
                            <div class="md:hidden">
                                <button id="searchToggle" class="flex items-center space-x-1 text-gray-700 hover:text-[#2d67ad]">
                                    <img src="https://static.vnncdn.net/v1/icon/search.svg" class="w-6 h-6" alt="search icon">
                                </button>

                                <!-- Search Dropdown -->
                                <div id="searchDropdown" class="absolute right-0 top-full mt-2 w-80 bg-white border border-gray-200 rounded-lg shadow-lg hidden z-50">
                                    <form class="p-4" action="/search.html">
                                        <div class="relative">
                                            <input class="w-full h-10 px-4 pr-10 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" name="key" type="text" placeholder="Type keywords....">
                                            <button class="absolute right-2 top-1/2 transform -translate-y-1/2 w-6 h-6 flex items-center justify-center hover:bg-gray-100 rounded-full transition-colors" type="submit">
                                                <img src="https://static.vnncdn.net/v1/icon/search.png" alt="icon search" class="w-4 h-4">
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="hidden lg:block" id="mainBar">
            <div class="flex items-center justify-center h-14">
                <!-- Left Side - Home Icon and Navigation -->
                <div class="flex items-center space-x-6">
                    <!-- Home Icon -->
                    <a href="/" class="text-blue-600 hover:text-blue-700">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                        </svg>
                    </a>

                    <!-- Navigation Menu -->
                    <nav class="hidden lg:flex items-center space-x-2 text-sm font-medium whitespace-nowrap overflow-x-auto">
                        <?php if (!empty($GLOBALS['categories'])): ?>
                            <?php foreach ($GLOBALS['categories'] as $index => $category): ?>
                                <a href="<?= link_cat($category['slug']) ?>" 
                                   class="text-gray-800 hover:text-[#447ec5] <?= $index === 0 ? 'color-[#2d67ad]' : 'color-[#2a2a2a]' ?> whitespace-nowrap text-sm font-bold">
                                    <?= htmlspecialchars($category['name']) ?>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                           
                        <?php endif; ?>
                    </nav>
                   
                </div>

            </div>
        </div>

    </header>

    <!-- Main Content -->
    <main class="container max-w-[1140px] mx-auto py-6 px-5 mt-12 lg:mt-24">
