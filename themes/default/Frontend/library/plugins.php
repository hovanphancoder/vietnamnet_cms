<?php

/**
 * Template Name: Plugin Library
 * Description: Trang hiển thị toàn bộ plugins
 */

use System\Libraries\Render;
use App\Libraries\Fastlang as Flang;
use App\Blocks\Schema\Templates\CollectionPage;
use App\Blocks\Meta\MetaBlock;
use App\Models\FastModel;

// Load language files
Flang::load('CMS', APP_LANG);
Flang::load('Library', APP_LANG);
Flang::load('Plugins', APP_LANG);
// Load plugin-specific assets
Render::asset('css', theme_assets('css/plugins_styles.css'), ['area' => 'frontend', 'location' => 'head']);
Render::asset('js', theme_assets('js/plugins.js'), ['area' => 'frontend', 'location' => 'footer']);

// Prepare plugins data for sections

// Parse filter parameters from $params array
$search = str_replace('-', ' ', S_GET('search', ''));
$sortBy = S_GET('sort', 'created_at_desc');
$category = '';
$page = 1;
$perPage = 12;
$canonical = base_url('library/plugins', APP_LANG);
// Parse $params array to extract filter values
if (isset($params) && is_array($params)) {
    for ($i = 0; $i < count($params); $i += 2) {
        if (isset($params[$i]) && isset($params[$i + 1])) {
            $key = $params[$i];
            $value = $params[$i + 1];

            switch ($key) {
                case 'category':
                    $category = $value;
                    $category_id = (new FastModel('fast_terms'))->where('slug', $category)->where('lang', APP_LANG)->value('id_main');
                    $canonical .= '/category/' . $category;
                    break;
                case 'paged':
                    $page = max(1, (int)$value);
                    $canonical .= '/paged/' . $page;
                    break;
            }
        }
    }
}
// Use get_posts helper instead of getAllPlugins
// Map sort options to database columns
$sortColumn = 'created_at';
$sortDirection = 'DESC';

switch ($sortBy) {
    case 'created_at_desc':
        $sortColumn = 'created_at';
        $sortDirection = 'DESC';
        break;
    case 'created_at_asc':
        $sortColumn = 'created_at';
        $sortDirection = 'ASC';
        break;
    case 'title_asc':
        $sortColumn = 'title';
        $sortDirection = 'ASC';
        break;
    case 'title_desc':
        $sortColumn = 'title';
        $sortDirection = 'DESC';
        break;
    case 'rating_desc':
        $sortColumn = 'rating_avg';
        $sortDirection = 'DESC';
        break;
    case 'download_desc':
        $sortColumn = 'download';
        $sortDirection = 'DESC';
        break;
    case 'download_asc':
        $sortColumn = 'download';
        $sortDirection = 'ASC';
        break;
    case 'price_asc':
        $sortColumn = 'price';
        $sortDirection = 'ASC';
        break;
    case 'price_desc':
        $sortColumn = 'price';
        $sortDirection = 'DESC';
        break;
}

$pluginsData = get_posts([
    'posttype' => 'plugins',
    'perPage' => $perPage,
    'paged' => $page,
    'search' => $search,
    'searchcolumns' => ['title', 'seo_desc', 'content'],
    'cat' => !empty($category_id) ? $category_id : null,
    'sort' => [$sortColumn, $sortDirection],
    'filters' => [
        'status' => 'active'
    ],
    'pagination' => true,
    'totalpage' => true,
], APP_LANG);
// Get categories with counts from database
// $categoriesData = $controller->getPluginCategories(APP_LANG);
$categoriesData = get_terms('plugins', 'categories', APP_LANG);
// Extract plugins data - get_posts returns paginated data
$plugins = $pluginsData['data'] ?? $pluginsData['items'] ?? [];
$totalPlugins = $pluginsData['total'] ?? count($plugins);
$currentPage = $pluginsData['current_page'] ?? $page;
$totalPages = $pluginsData['last_page'] ?? 1;
$perPageCount = $pluginsData['per_page'] ?? $perPage;

// Process plugins data - get_posts returns raw data, need to process
foreach ($plugins as $index => $plugin) {
    // Parse feature JSON if exists
    if (!empty($plugin['feature']) && !isset($plugin['features_data'])) {
        $plugins[$index]['features_data'] = json_decode($plugin['feature'], true);
    }

    // Generate plugin URL
    $plugins[$index]['url'] = base_url('library/plugins/' . $plugin['slug'], APP_LANG);

    // Generate install URL
    $plugins[$index]['install_url'] = $plugin['install_url'] ?? base_url('download/plugin/' . $plugin['slug'], APP_LANG);

    // Set default icon if empty
    if (empty($plugin['icon_url'])) {
        $plugins[$index]['icon_url'] = theme_assets('images/default-plugin-icon.svg');
    }

    // Process price and download data
    $price = $plugin['price'] ?? 0;
    $downloads = $plugin['download'] ?? 0;
    $views = $plugin['views'] ?? 0;

    $is_free = ($price == 0);
    $formatted_price = $is_free ? 'Miễn phí' : number_format($price) . 'đ';
    $formatted_downloads = $downloads > 1000 ? round($downloads / 1000, 1) . 'k' : $downloads;
    $formatted_views = $views > 1000 ? round($views / 1000, 1) . 'k' : $views;

    // Add processed fields
    $plugins[$index] = array_merge($plugin, [
        'formatted_price' => $formatted_price,
        'is_free' => $is_free,
        'formatted_downloads' => $formatted_downloads,
        'formatted_views' => $formatted_views,
        'seo_description' => $plugin['seo_desc'] ?? $plugin['description'] ?? '',
        'download_url' => $plugins[$index]['install_url'],
        'detail_url' => $plugin['detail_url'] ?? '',
        'preview_image' => $plugin['detail_url'] ?? '/assets/images/placeholder-plugin.jpg',
        'icon_bg_class' => $plugin['icon_bg_class'] ?? 'bg-gradient-to-br from-blue-500 to-purple-600',
        'background_class' => $plugin['background_class'] ?? '',
        'category_display' => ucfirst(str_replace(['_', '-'], ' ', $plugin['category'] ?? 'general'))
    ]);
}
// Create meta tags for SEO
$meta = new MetaBlock();
$pageTitle = !empty($search) ?
    sprintf(Flang::_e('plugins.search.results_for', APP_LANG), $search) . ' - ' . Flang::_e('plugins.meta.title', APP_LANG) :
    Flang::_e('plugins.meta.title', APP_LANG);

$meta
    ->title($pageTitle)
    ->description(Flang::_e('plugins.meta.description', APP_LANG))
    ->keywords(Flang::_e('plugins.meta.keywords', APP_LANG))
    ->robots('index, follow')
    ->canonical(base_url('library/plugins', APP_LANG))
    ->og('image', public_url('avatar.webp'))
    ->og('url', base_url('library/plugins', APP_LANG))
    ->og('type', 'website');

if (option('favicon')) {
    $logoUrl = theme_assets(option('favicon')['path'] ?? '/images/logo/logo-icon.webp');
    $meta
        ->custom('<link rel="icon" href="' . $logoUrl . '" sizes="32x32" />')
        ->custom('<link rel="apple-touch-icon" href="' . $logoUrl . '" />');
}
// Schema for plugins collection page
$collectionSchema = new CollectionPage();
$collectionSchema->setSchemaData([
    'name' => Flang::_e('plugins.meta.title', APP_LANG),
    'description' => Flang::_e('plugins.meta.description', APP_LANG),
    'url' => base_url('library/plugins', APP_LANG),
    'mainEntity' => array_map(function ($plugin) {
        return [
            '@type' => 'SoftwareApplication',
            'name' => $plugin['title'],
            'description' => $plugin['seo_description'] ?? '',
            'url' => base_url('library/plugins/', APP_LANG),
            'image' => $plugin['icon_url'],
            'downloadUrl' => $plugin['install_url'],
            'offers' => [
                '@type' => 'Offer',
                'price' => $plugin['price'] ?? 0,
                'priceCurrency' => 'USD'
            ]
        ];
    }, array_slice($plugins, 0, 6)) // Limit schema to first 6 items for performance
]);

// Render header with meta tags and schema
get_header([
    'meta' => $meta->render(),
    'schema' => $collectionSchema->render(),
    'layout' => 'plugins'
]);
?>

<!-- Plugins Library Page -->
<main class="flex-grow">
    <?php get_template('sections/plugins/plugins_hero', ['search' => $search, 'totalPlugins' => $totalPlugins]); ?>

    <?php get_template('sections/plugins/plugins_filter', ['categoriesData' => $categoriesData, 'category' => $category, 'search' => $search, 'sortBy' => $sortBy, 'totalPlugins' => $totalPlugins]); ?>

    <?php get_template('sections/plugins/plugins_grid', ['plugins' => $plugins, 'category' => $category, 'currentPage' => $currentPage, 'totalPages' => $totalPages, 'perPageCount' => $perPageCount, 'sortBy' => $sortBy]); ?>

    <?php get_template('sections/plugins/plugins_stats'); ?>

    <?php get_template('sections/plugins/plugins_cta'); ?>
</main>

<?php get_footer(); ?>
