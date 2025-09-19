<?php

/**
 * Template Name: Search Results
 * Description: Trang hiển thị kết quả tìm kiếm
 */

use System\Libraries\Render;
use App\Libraries\Fastlang as Flang;
use App\Blocks\Meta\MetaBlock;
// use App\Blocks\Schema\Templates\SearchResultsPage; // Commented out as this class may not exist yet

// Load language files
Flang::load('CMS', APP_LANG);
Flang::load('Search', APP_LANG);

// Load search-specific assets
Render::asset('css', theme_assets('css/search_styles.css'), ['area' => 'frontend', 'location' => 'head']);
Render::asset('js', theme_assets('js/search.js'), ['area' => 'frontend', 'location' => 'footer']);

// Get search query
$searchQuery = trim(S_GET('q', ''));
$searchQuery = str_replace(['-', '_'], ' ', $searchQuery);

// If no search query, redirect to home
if (empty($searchQuery)) {
    redirect(base_url());
}

// Use searching helper to get results
$searchResults = searching($searchQuery);
// Process search results
$blogs = $searchResults['blogs'] ?? [];
$themes = $searchResults['themes'] ?? [];
$plugins = $searchResults['plugins'] ?? [];

// Count total results
$totalResults = count($blogs) + count($themes) + count($plugins);

// Process blogs data
foreach ($blogs as $index => $blog) {
    $blogs[$index]['url'] = base_url('blogs/' . $blog['slug'], APP_LANG);
    $blogs[$index]['thumbnail_url'] = $blog['thumbnail_url'] ?? theme_assets('images/default-blog-thumbnail.jpg');
    $blogs[$index]['type'] = 'blog';
    $blogs[$index]['type_label'] = 'Blog';
    $blogs[$index]['type_color'] = 'purple';
}

// Process themes data
foreach ($themes as $index => $theme) {
    $themes[$index]['url'] = base_url('library/themes/' . $theme['slug'], APP_LANG);
    $themes[$index]['thumbnail'] = $theme['thumbnail'] ?? theme_assets('images/default-theme-thumbnail.jpg');
    $themes[$index]['type'] = 'theme';
    $themes[$index]['type_label'] = 'Theme';
    $themes[$index]['type_color'] = 'green';
}

// Process plugins data
foreach ($plugins as $index => $plugin) {
    $plugins[$index]['url'] = base_url('library/plugins/' . $plugin['slug'], APP_LANG);
    $plugins[$index]['icon_url'] = $plugin['icon_url'] ?? theme_assets('images/default-plugin-icon.svg');
    $plugins[$index]['type'] = 'plugin';
    $plugins[$index]['type_label'] = 'Plugin';
    $plugins[$index]['type_color'] = 'blue';
}

// Combine all results for unified display
$allResults = array_merge($blogs, $themes, $plugins);

// Create meta tags
$meta = new MetaBlock();
$pageTitle = sprintf(Flang::_e('search.results_for'), $searchQuery) . ' - ' . Flang::_e('search.page_title');

$meta
    ->title($pageTitle)
    ->description(sprintf(Flang::_e('search.meta_description'), $searchQuery, $totalResults))
    ->keywords(Flang::_e('search.meta_keywords'))
    ->robots('index, follow')
    ->canonical(base_url('search?q=' . urlencode($searchQuery), APP_LANG))
    ->og('type', 'website')
    ->og('title', $pageTitle)
    ->og('description', sprintf(Flang::_e('search.meta_description'), $searchQuery, $totalResults))
    ->og('url', base_url('search?q=' . urlencode($searchQuery), APP_LANG));

// Schema for search results page - commented out as SearchResultsPage class may not exist yet
// $searchSchema = new SearchResultsPage();
// $searchSchema->setSchemaData([
//     'query' => $searchQuery,
//     'totalResults' => $totalResults,
//     'url' => base_url('search?q=' . urlencode($searchQuery), APP_LANG)
// ]);

// Render header with meta tags and schema
get_header([
    'meta' => $meta->render(),
    'schema' => '', // Removed schema as SearchResultsPage class may not exist yet
    'layout' => 'search'
]);
?>

<!-- Search Results Page -->
<main class="flex-grow">
    <!-- Search Hero Section -->
    <section class="bg-gradient-to-br from-blue-50 to-indigo-100 py-16">
        <div class="container mx-auto px-4">
            <div class="text-center max-w-3xl mx-auto">
                <h1 class="text-4xl md:text-5xl font-bold text-slate-900 mb-6">
                    <?php printf(Flang::_e('search.results_for'), htmlspecialchars($searchQuery)); ?>
                </h1>
                <p class="text-xl text-slate-600 mb-8">
                    <?php printf(Flang::_e('Found %d results'), $totalResults); ?>
                </p>

                <!-- Search Form -->
                <form action="<?= base_url('search') ?>" method="GET" class="max-w-2xl mx-auto">
                    <div class="relative flex">
                        <div class="relative flex-1">
                            <input
                                type="text"
                                name="q"
                                value="<?= htmlspecialchars($searchQuery) ?>"
                                placeholder="<?php __e('search.search_placeholder'); ?>"
                                class="w-full pl-12 pr-4 py-3 text-lg border border-slate-300 rounded-l-xl focus:outline-none focus:ring-0 focus:border-blue-500 transition-all duration-200">
                            <svg class="absolute left-4 top-1/2 transform -translate-y-1/2 w-6 h-6 text-slate-400" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                        </div>
                        <button
                            type="submit"
                            class="px-4 py-[15px] absolute right-0 top-0 text-white font-medium rounded-l-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 flex items-center justify-center min-w-[120px]">
                            <svg class="w-5 h-5 mr-2" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                            <?php __e('search.search_button'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Search Results Section -->
    <section class="py-16">
        <div class="container mx-auto px-4">
            <?php if ($totalResults > 0): ?>
                <!-- Results Summary -->
                <div class="mb-8">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex max-sm:flex-col max-sm:items-start items-center sm:space-x-4">
                            <span class="text-lg font-medium text-slate-700">
                                <?php printf(Flang::_e('search.showing_results'), $totalResults); ?>
                            </span>

                            <!-- View Toggle -->
                            <div class="flex items-center justify-between">
                                <!-- Filter Tabs -->
                                <div class="flex space-x-1 bg-slate-100 rounded-lg p-1">
                                    <button class="filter-tab active px-4 py-2 rounded-md text-sm font-medium transition-all duration-200" data-filter="all">
                                        <?php __e('All'); ?> (<?= $totalResults ?>)
                                    </button>
                                    <?php if (count($blogs) > 0): ?>
                                        <button class="filter-tab px-4 py-2 rounded-md text-sm font-medium transition-all duration-200" data-filter="blog">
                                            <?php __e('search.blogs'); ?> (<?= count($blogs) ?>)
                                        </button>
                                    <?php endif; ?>
                                    <?php if (count($themes) > 0): ?>
                                        <button class="filter-tab px-4 py-2 rounded-md text-sm font-medium transition-all duration-200" data-filter="theme">
                                            <?php __e('search.themes'); ?> (<?= count($blogs) ?>)
                                        </button>
                                    <?php endif; ?>
                                    <?php if (count($plugins) > 0): ?>
                                        <button class="filter-tab px-4 py-2 rounded-md text-sm font-medium transition-all duration-200" data-filter="plugin">
                                            <?php __e('search.plugins'); ?> (<?= count($plugins) ?>)
                                        </button>
                                    <?php endif; ?>
                                </div>

                            </div>
                        </div>
                        <!-- Layout Toggle -->
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-slate-600"><?php __e('Layout'); ?>:</span>
                            <div class="flex bg-slate-100 rounded-lg p-1">
                                <button id="gridViewBtn" class="view-toggle-btn active px-3 py-1.5 rounded text-sm font-medium transition-all duration-200" data-view="grid" title="<?php __e('Grid View'); ?>">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                                    </svg>
                                </button>
                                <button id="sectionViewBtn" class="view-toggle-btn px-3 py-1.5 rounded text-sm font-medium transition-all duration-200" data-view="section" title="<?php __e('search.section_view'); ?>">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Results Grid (Default View) -->
                <div id="gridView" class="search-view">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6" id="searchResults">
                        <?php foreach ($allResults as $result): ?>
                            <div class="search-result bg-white rounded-xl shadow-sm border border-slate-200 hover:shadow-lg transition-all duration-300 overflow-hidden" data-type="<?= $result['type'] ?>">
                                <a href="<?= $result['url'] ?>" class="block group">
                                    <!-- Image/Thumbnail -->
                                    <div class="aspect-video bg-slate-100 overflow-hidden">
                                        <?php if ($result['type'] === 'blog'): ?>
                                            <?= _img(theme_assets(get_image_full($result['thumbnail_url'] ?? $result['thumbnail'] ?? '/assets/images/placeholder-theme.jpg')), $result['title'], true, 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-300') ?>
                                        <?php elseif ($result['type'] === 'theme'): ?>
                                            <?= _img(theme_assets(get_image_full($result['thumbnail_url'] ?? $result['thumbnail'] ?? '/assets/images/placeholder-theme.jpg')), $result['title'], true, 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-300') ?>
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-500 to-purple-600">
                                                <?= _img(theme_assets(get_image_full($result['icon_url'])), $result['title'], true, 'w-16 h-16 object-contain') ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Content -->
                                    <div class="p-6">
                                        <!-- Type Badge -->
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-<?= $result['type_color'] ?>-100 text-<?= $result['type_color'] ?>-800">
                                                <?= $result['type_label'] ?>
                                            </span>

                                            <?php if (isset($result['views'])): ?>
                                                <span class="text-sm text-slate-500 flex items-center">
                                                    <svg class="w-4 h-4 mr-1" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                        <circle cx="12" cy="12" r="3"></circle>
                                                    </svg>
                                                    <?= formatViews($result['views']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Title -->
                                        <h3 class="text-lg font-semibold text-slate-900 group-hover:text-blue-600 transition-colors duration-200 mb-2 line-clamp-2">
                                            <?= htmlspecialchars($result['title']) ?>
                                        </h3>

                                        <!-- Description -->
                                        <?php if (!empty($result['seo_desc']) || !empty($result['description'])): ?>
                                            <p class="text-slate-600 text-sm line-clamp-3 mb-4">
                                                <?= htmlspecialchars($result['seo_desc'] ?? $result['description'] ?? '') ?>
                                            </p>
                                        <?php endif; ?>

                                        <!-- Meta Info -->
                                        <div class="flex items-center justify-between text-sm text-slate-500">
                                            <?php if (isset($result['created_at'])): ?>
                                                <span>
                                                    <?= date('M j, Y', strtotime($result['created_at'])) ?>
                                                </span>
                                            <?php endif; ?>

                                            <?php if (isset($result['download'])): ?>
                                                <span class="flex items-center">
                                                    <svg class="w-4 h-4 mr-1" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                        <polyline points="7,10 12,15 17,10"></polyline>
                                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                                    </svg>
                                                    <?= formatViews($result['download']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Section View (New Layout) -->
                <div id="sectionView" class="search-view hidden">
                    <!-- Themes Section -->
                    <?php if (count($themes) > 0): ?>
                        <section class="mb-12">
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="text-2xl font-bold text-slate-900"><?php __e('search.themes'); ?></h2>
                                        <p class="text-slate-600"><?php printf(Flang::_e('search.found_in_themes'), count($themes)); ?></p>
                                    </div>
                                </div>
                                <a href="<?= base_url('library/themes') ?>" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                    <?php __e('search.view_all_themes'); ?> →
                                </a>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                <?php foreach ($themes as $theme): ?>
                                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 hover:shadow-lg transition-all duration-300 overflow-hidden">
                                        <a href="<?= $theme['url'] ?>" class="block group">
                                            <div class="aspect-video bg-slate-100 overflow-hidden">
                                                <?= _img(theme_assets(get_image_full($theme['thumbnail'] ?? '/assets/images/placeholder-theme.jpg')), $theme['title'], true, 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-300') ?>
                                            </div>
                                            <div class="p-6">
                                                <div class="flex items-center justify-between mb-3">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        Theme
                                                    </span>
                                                    <?php if (isset($theme['download'])): ?>
                                                        <span class="text-sm text-slate-500 flex items-center">
                                                            <svg class="w-4 h-4 mr-1" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                                <polyline points="7,10 12,15 17,10"></polyline>
                                                                <line x1="12" y1="15" x2="12" y2="3"></line>
                                                            </svg>
                                                            <?= formatViews($theme['download']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <h3 class="text-lg font-semibold text-slate-900 group-hover:text-blue-600 transition-colors duration-200 mb-2 line-clamp-2">
                                                    <?= htmlspecialchars($theme['title']) ?>
                                                </h3>
                                                <?php if (!empty($theme['seo_desc']) || !empty($theme['description'])): ?>
                                                    <p class="text-slate-600 text-sm line-clamp-3 mb-4">
                                                        <?= htmlspecialchars($theme['seo_desc'] ?? $theme['description'] ?? '') ?>
                                                    </p>
                                                <?php endif; ?>
                                                <div class="flex items-center justify-between text-sm text-slate-500">
                                                    <?php if (isset($theme['created_at'])): ?>
                                                        <span><?= date('M j, Y', strtotime($theme['created_at'])) ?></span>
                                                    <?php endif; ?>
                                                    <span class="text-blue-600 font-medium"><?php __e('search.view_theme'); ?></span>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Plugins Section -->
                    <?php if (count($plugins) > 0): ?>
                        <section class="mb-12">
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="text-2xl font-bold text-slate-900"><?php __e('search.plugins'); ?></h2>
                                        <p class="text-slate-600"><?php printf(Flang::_e('search.found_in_plugins'), count($plugins)); ?></p>
                                    </div>
                                </div>
                                <a href="<?= base_url('library/plugins') ?>" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                    <?php __e('search.view_all_plugins'); ?> →
                                </a>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                <?php foreach ($plugins as $plugin): ?>
                                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 hover:shadow-lg transition-all duration-300 overflow-hidden">
                                        <a href="<?= $plugin['url'] ?>" class="block group">
                                            <div class="aspect-video bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                                                <?= _img(theme_assets(get_image_full($plugin['icon_url'] ?? '/assets/images/default-plugin-icon.svg')), $plugin['title'], true, 'w-16 h-16 object-contain') ?>
                                            </div>
                                            <div class="p-6">
                                                <div class="flex items-center justify-between mb-3">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        Plugin
                                                    </span>
                                                    <?php if (isset($plugin['download'])): ?>
                                                        <span class="text-sm text-slate-500 flex items-center">
                                                            <svg class="w-4 h-4 mr-1" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                                <polyline points="7,10 12,15 17,10"></polyline>
                                                                <line x1="12" y1="15" x2="12" y2="3"></line>
                                                            </svg>
                                                            <?= formatViews($plugin['download']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <h3 class="text-lg font-semibold text-slate-900 group-hover:text-blue-600 transition-colors duration-200 mb-2 line-clamp-2">
                                                    <?= htmlspecialchars($plugin['title']) ?>
                                                </h3>
                                                <?php if (!empty($plugin['seo_desc']) || !empty($plugin['description'])): ?>
                                                    <p class="text-slate-600 text-sm line-clamp-3 mb-4">
                                                        <?= htmlspecialchars($plugin['seo_desc'] ?? $plugin['description'] ?? '') ?>
                                                    </p>
                                                <?php endif; ?>
                                                <div class="flex items-center justify-between text-sm text-slate-500">
                                                    <?php if (isset($plugin['created_at'])): ?>
                                                        <span><?= date('M j, Y', strtotime($plugin['created_at'])) ?></span>
                                                    <?php endif; ?>
                                                    <span class="text-blue-600 font-medium"><?php __e('search.view_plugin'); ?></span>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Blogs Section -->
                    <?php if (count($blogs) > 0): ?>
                        <section class="mb-12">
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="text-2xl font-bold text-slate-900"><?php __e('search.blogs'); ?></h2>
                                        <p class="text-slate-600"><?php printf(Flang::_e('search.found_in_blogs'), count($blogs)); ?></p>
                                    </div>
                                </div>
                                <a href="<?= base_url('blogs') ?>" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                    <?php __e('search.view_all_blogs'); ?> →
                                </a>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                <?php foreach ($blogs as $blog): ?>
                                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 hover:shadow-lg transition-all duration-300 overflow-hidden">
                                        <a href="<?= $blog['url'] ?>" class="block group">
                                            <div class="aspect-video bg-slate-100 overflow-hidden">
                                                <?= _img(theme_assets(get_image_full($blog['thumbnail_url'] ?? '/assets/images/default-blog-thumbnail.jpg')), $blog['title'], true, 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-300') ?>
                                            </div>
                                            <div class="p-6">
                                                <div class="flex items-center justify-between mb-3">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                        Blog
                                                    </span>
                                                    <?php if (isset($blog['views'])): ?>
                                                        <span class="text-sm text-slate-500 flex items-center">
                                                            <svg class="w-4 h-4 mr-1" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                                <circle cx="12" cy="12" r="3"></circle>
                                                            </svg>
                                                            <?= formatViews($blog['views']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <h3 class="text-lg font-semibold text-slate-900 group-hover:text-blue-600 transition-colors duration-200 mb-2 line-clamp-2">
                                                    <?= htmlspecialchars($blog['title']) ?>
                                                </h3>
                                                <?php if (!empty($blog['seo_desc']) || !empty($blog['description'])): ?>
                                                    <p class="text-slate-600 text-sm line-clamp-3 mb-4">
                                                        <?= htmlspecialchars($blog['seo_desc'] ?? $blog['description'] ?? '') ?>
                                                    </p>
                                                <?php endif; ?>
                                                <div class="flex items-center justify-between text-sm text-slate-500">
                                                    <?php if (isset($blog['created_at'])): ?>
                                                        <span><?= date('M j, Y', strtotime($blog['created_at'])) ?></span>
                                                    <?php endif; ?>
                                                    <span class="text-blue-600 font-medium"><?php __e('search.read_more'); ?></span>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <!-- No Results -->
                <div class="text-center py-16">
                    <div class="max-w-md mx-auto">
                        <div class="w-24 h-24 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-6">
                            <svg class="w-12 h-12 text-slate-400" width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-semibold text-slate-900 mb-4">
                            <?php __e('No results found'); ?>
                        </h3>
                        <p class="text-slate-600 mb-8">
                            <?php printf(Flang::_e('search.no_results_message'), htmlspecialchars($searchQuery)); ?>
                        </p>
                        <div class="space-y-4">
                            <a href="<?= base_url() ?>" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors duration-200">
                                <svg class="w-5 h-5 mr-2" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M19 12H5M12 19l-7-7 7-7"></path>
                                </svg>
                                <?php __e('Back to Home'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Search Suggestions -->
    <?php if ($totalResults > 0): ?>
        <section class="bg-slate-50 py-16">
            <div class="container mx-auto px-4">
                <div class="max-w-4xl mx-auto">
                    <h2 class="text-2xl font-bold text-slate-900 text-center mb-8">
                        <?php __e('search.suggestions_title'); ?>
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mx-auto">
                        <!-- Browse Categories -->
                        <div class="bg-white rounded-xl p-6 shadow-sm">
                            <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-green-600" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"></path>
                                </svg>
                                <?php __e('Browse Categories'); ?>
                            </h3>
                            <div class="space-y-2">
                                <a href="<?= base_url('library/themes') ?>" class="block text-sm text-slate-600 hover:text-green-600 transition-colors duration-200">
                                    <?php __e('Themes'); ?>
                                </a>
                                <a href="<?= base_url('library/plugins') ?>" class="block text-sm text-slate-600 hover:text-green-600 transition-colors duration-200">
                                    <?php __e('Plugins'); ?>
                                </a>
                                <a href="<?= base_url('blogs') ?>" class="block text-sm text-slate-600 hover:text-green-600 transition-colors duration-200">
                                    <?php __e('Blogs'); ?>
                                </a>
                            </div>
                        </div>

                        <!-- Get Help -->
                        <div class="bg-white rounded-xl p-6 shadow-sm">
                            <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-purple-600" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                </svg>
                                <?php __e('search.need_help'); ?>
                            </h3>
                            <div class="space-y-2">
                                <a href="<?= docs_url() ?>" class="block text-sm text-slate-600 hover:text-purple-600 transition-colors duration-200">
                                    <?php __e('Documentation'); ?>
                                </a>
                                <a href="<?= base_url('contact') ?>" class="block text-sm text-slate-600 hover:text-purple-600 transition-colors duration-200">
                                    <?php __e('search.contact_support'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize view toggle functionality
        initViewToggle();

        // Initialize filter functionality
        initFilterTabs();

        // Initialize search highlighting
        initSearchHighlighting();
    });

    /**
     * Initialize view toggle functionality
     */
    function initViewToggle() {
        const gridViewBtn = document.getElementById('gridViewBtn');
        const sectionViewBtn = document.getElementById('sectionViewBtn');
        const gridView = document.getElementById('gridView');
        const sectionView = document.getElementById('sectionView');

        if (gridViewBtn && sectionViewBtn && gridView && sectionView) {
            // Grid view button click
            gridViewBtn.addEventListener('click', function() {
                setActiveView('grid');
            });

            // Section view button click
            sectionViewBtn.addEventListener('click', function() {
                setActiveView('section');
            });
        }
    }

    /**
     * Set active view
     */
    function setActiveView(view) {
        const gridViewBtn = document.getElementById('gridViewBtn');
        const sectionViewBtn = document.getElementById('sectionViewBtn');
        const gridView = document.getElementById('gridView');
        const sectionView = document.getElementById('sectionView');

        if (view === 'grid') {
            // Show grid view
            gridView.classList.remove('hidden');
            sectionView.classList.add('hidden');

            // Update button states
            gridViewBtn.classList.add('active', 'bg-white', 'text-slate-900');
            gridViewBtn.classList.remove('bg-transparent', 'text-slate-600');
            sectionViewBtn.classList.remove('active', 'bg-white', 'text-slate-900');
            sectionViewBtn.classList.add('bg-transparent', 'text-slate-600');
        } else {
            // Show section view
            sectionView.classList.remove('hidden');
            gridView.classList.add('hidden');

            // Update button states
            sectionViewBtn.classList.add('active', 'bg-white', 'text-slate-900');
            sectionViewBtn.classList.remove('bg-transparent', 'text-slate-600');
            gridViewBtn.classList.remove('active', 'bg-white', 'text-slate-900');
            gridViewBtn.classList.add('bg-transparent', 'text-slate-600');
        }
    }

    /**
     * Initialize filter tabs functionality
     */
    function initFilterTabs() {
        const filterTabs = document.querySelectorAll('.filter-tab');
        const searchResults = document.querySelectorAll('.search-result');

        filterTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const filter = this.dataset.filter;

                // Update active tab
                filterTabs.forEach(t => t.classList.remove('active', 'bg-white', 'text-slate-900'));
                filterTabs.forEach(t => t.classList.add('bg-transparent', 'text-slate-600'));
                this.classList.add('active', 'bg-white', 'text-slate-900');
                this.classList.remove('bg-transparent', 'text-slate-600');

                // Check current view
                const isGridView = !document.getElementById('gridView').classList.contains('hidden');
                const isSectionView = !document.getElementById('sectionView').classList.contains('hidden');

                if (isGridView) {
                    // Filter results for grid view (existing functionality)
                    searchResults.forEach(result => {
                        if (filter === 'all' || result.dataset.type === filter) {
                            result.style.display = 'block';
                            result.style.animation = 'fadeIn 0.3s ease-in-out';
                        } else {
                            result.style.display = 'none';
                        }
                    });
                } else if (isSectionView && filter !== 'all') {
                    // Scroll to corresponding section for section view
                    scrollToSection(filter);
                } else if (isSectionView && filter === 'all') {
                    // Scroll to top when "All" is selected in section view
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                }
            });
        });
    }

    /**
     * Scroll to specific section based on filter type
     */
    function scrollToSection(filterType) {
        let targetSection = null;
        
        // Find the target section based on filter type
        const sections = document.querySelectorAll('#sectionView section');
        sections.forEach(section => {
            const heading = section.querySelector('h2');
            if (heading) {
                const headingText = heading.textContent.toLowerCase();
                if (filterType === 'blog' && headingText.includes('blogs')) {
                    targetSection = section;
                } else if (filterType === 'theme' && headingText.includes('themes')) {
                    targetSection = section;
                } else if (filterType === 'plugin' && headingText.includes('plugins')) {
                    targetSection = section;
                }
            }
        });

        if (targetSection) {
            // Smooth scroll to section with offset for better UX
            const offset = 120; // Offset from top for better visibility
            const targetPosition = targetSection.offsetTop - offset;
            
            // Add highlight effect to the section first
            targetSection.style.animation = 'sectionHighlight 0.6s ease-in-out';
            
            // Scroll to section
            window.scrollTo({
                top: targetPosition,
                behavior: 'smooth'
            });

            // Remove animation after completion
            setTimeout(() => {
                targetSection.style.animation = '';
            }, 600);
        }
    }

    /**
     * Initialize search highlighting
     */
    function initSearchHighlighting() {
        const searchQuery = '<?= addslashes($searchQuery) ?>';
        if (searchQuery) {
            // Highlight in grid view
            const gridTitles = document.querySelectorAll('#gridView .search-result h3');
            gridTitles.forEach(title => {
                highlightSearchTerms(title, searchQuery);
            });

            // Highlight in section view
            const sectionTitles = document.querySelectorAll('#sectionView h3');
            sectionTitles.forEach(title => {
                highlightSearchTerms(title, searchQuery);
            });
        }
    }

    /**
     * Highlight search terms in text
     */
    function highlightSearchTerms(element, searchQuery) {
        const text = element.textContent;
        const highlightedText = text.replace(
            new RegExp(searchQuery, 'gi'),
            match => `<mark class="bg-yellow-200 px-1 rounded">${match}</mark>`
        );
        element.innerHTML = highlightedText;
    }
</script>

<style>
    .filter-tab.active,
    .view-toggle-btn.active {
        background-color: white;
        color: #1e293b;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    }

    .view-toggle-btn {
        transition: all 0.2s ease;
    }

    .view-toggle-btn:hover {
        background-color: rgba(255, 255, 255, 0.8);
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .line-clamp-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .search-view {
        transition: opacity 0.3s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes sectionHighlight {
        0% {
            background-color: rgba(59, 130, 246, 0.1);
            transform: scale(1);
        }
        50% {
            background-color: rgba(59, 130, 246, 0.2);
            transform: scale(1.02);
        }
        100% {
            background-color: transparent;
            transform: scale(1);
        }
    }
</style>

<?php get_footer(); ?>
