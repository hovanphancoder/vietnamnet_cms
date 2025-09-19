<?php

/**
 * Template Name: Theme Library
 * Description: Trang hiển thị toàn bộ themes
 */

use System\Libraries\Render;
use App\Libraries\Fastlang as Flang;
use App\Blocks\Schema\Templates\CollectionPage;
use App\Blocks\Schema\Templates\WebSite;
use App\Blocks\Meta\MetaBlock;
use App\Controllers\FrontendController;
use App\Models\FastModel;


// Load language files
Flang::load('CMS', APP_LANG);
Flang::load('Themes', APP_LANG);

// Load theme-specific assets
Render::asset('css', theme_assets('css/themes_styles.css'), ['area' => 'frontend', 'location' => 'head']);
Render::asset('js', theme_assets('js/themes.js'), ['area' => 'frontend', 'location' => 'footer']);

$search = str_replace('-', ' ', S_GET('search', ''));
$sortBy = S_GET('sort', 'created_at_desc');
$category = '';
$page = 1;
$perPage = 8;
$canonical = base_url('library/themes', APP_LANG);
// Language data is now handled directly with Fastlang::_e() in individual sections
// Get search query and filters from URL
if (isset($params) && is_array($params)) {
    for ($i = 0; $i < count($params); $i += 2) {
        if (isset($params[$i]) && isset($params[$i + 1])) {
            $key = $params[$i];
            $value = $params[$i + 1];

            switch ($key) {
                case 'category':
                    $category = $value;
                    $category_id = (new FastModel('fast_terms'))->where('slug', $category)->where('lang', APP_LANG)->value('id_main');
                    $canonical .= 'category/' . $category . '/';
                    break;
                case 'paged':
                    $page = max(1, (int)$value);
                    $canonical .= 'paged/' . $page . '/';
                    break;
            }
        }
    }
}
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
// Get data from controller using getAllThemes method
$themesResult = get_posts([
    'posttype' => 'themes',
    'perPage' => $perPage,
    'paged' => $page,
    'search' => $search,
    'searchcolumns' => ['title', 'seo_desc', 'content'],
    'cat' => !empty($category_id) ? $category_id : null,
    'sort' => [$sortColumn, $sortDirection],
    'filters' => [
        'status' => 'active',
    ],
    'pagination' => true,
    'totalpage' => true,
]);

// Get categories with counts from database
$categoriesData = get_terms('themes');
// Extract themes data
$themes = $themesResult['data'] ?? [];
$totalThemes = $themesResult['total'] ?? 0;
$currentPage = $themesResult['current_page'] ?? 1;
$totalPages = $themesResult['last_page'] ?? 1;
$perPageCount = $themesResult['per_page'] ?? $perPage;

// Process themes data - chỉ xử lý những field chưa được xử lý trong Controller  
foreach ($themes as $index => $theme) {
    // Parse feature JSON if exists (thay thế variations) - chỉ nếu chưa có
    if (!empty($theme['feature']) && !isset($theme['feature_data'])) {
        $themes[$index]['feature_data'] = json_decode($theme['feature'], true);
    }

    // Generate theme URL - chỉ nếu chưa có hoặc không đúng format
    if (empty($theme['url']) || !str_contains($theme['url'], 'library/themes')) {
        $themes[$index]['url'] = base_url('library/themes/' . $theme['slug'], APP_LANG);
    }

    // Generate download URL - chỉ nếu chưa có hoặc là placeholder
    if (empty($theme['download_url']) || $theme['download_url'] === '#') {
        $themes[$index]['download_url'] = base_url('download/theme/' . $theme['slug'], APP_LANG);
    }

    // Set default thumbnail if empty
    if (empty($theme['thumbnail'])) {
        $themes[$index]['thumbnail'] = '/placeholder.svg?height=200&width=300&text=' . urlencode($theme['title']);
    }

    // Add category display name if not exists
    if (!isset($theme['category_display'])) {
        $themes[$index]['category_display'] = ucfirst(str_replace(['_', '-'], ' ', $theme['category'] ?? 'general'));
    }
}

// Create meta tags
$meta = new MetaBlock();
$pageTitle = !empty($search) ?
    sprintf(Flang::_e('themes.search.results_for'), $search) . ' - ' . Flang::_e('themes.meta.title') :
    Flang::_e('themes.meta.title');

$meta
    ->title($pageTitle)
    ->description(Flang::_e('themes.meta.description'))
    ->keywords(Flang::_e('themes.meta.keywords'))
    ->robots('index, follow')
    ->canonical(base_url('library/themes', APP_LANG))
    ->og('image', theme_assets(option('site_logo')['path'] ?? '/images/logo.png'))
    ->og('url', base_url('library/themes', APP_LANG))
    ->og('type', 'website');

if (option('favicon')) {
    $logoUrl = theme_assets(option('favicon')['path'] ?? '/images/logo/logo-icon.webp');
    $meta
        ->custom('<link rel="icon" href="' . $logoUrl . '" sizes="32x32" />')
        ->custom('<link rel="apple-touch-icon" href="' . $logoUrl . '" />');
}
// Schema for collection page
$collectionSchema = new CollectionPage();
$collectionSchema->setSchemaData([
    'name' => Flang::_e('themes.meta.title'),
    'description' => Flang::_e('themes.meta.description'),
    'url' => base_url('library/themes', APP_LANG),
    'mainEntity' => array_map(function ($theme) {
        return [
            '@type' => 'SoftwareApplication',
            'name' => $theme['title'],
            'description' => $theme['seo_description'] ?? '',
            'url' => $theme['url'],
            'image' => $theme['thumbnail'],
            'offers' => [
                '@type' => 'Offer',
                'price' => $theme['price'] ?? 0,
                'priceCurrency' => 'USD'
            ]
        ];
    }, array_slice($themes, 0, 6)) // Limit schema to first 6 items for performance
]);
$meta->canonical($canonical);

// Render header with meta tags and schema
get_header([
    'meta' => $meta->render(),
    'schema' => $collectionSchema->render(),
    'layout' => 'themes'
]);
?>

<!-- Themes Library Page -->
<main class="flex-grow">
    <?php get_template('sections/themes/themes_hero', ['search' => $search, 'totalThemes' => $totalThemes]); ?>

    <?php get_template('sections/themes/themes_filter', ['categoriesData' => $categoriesData, 'category' => $category, 'search' => $search, 'sortBy' => $sortBy]); ?>

    <?php get_template('sections/themes/themes_grid', [
        'themes' => $themes,
        'category' => $category,
        'search' => $search,
        'sortBy' => $sortBy,
        'totalPages' => $totalPages,
        'currentPage' => $currentPage
    ]); ?>

    <?php get_template('sections/themes/themes_stats', ['totalThemes' => $totalThemes]); ?>

    <?php get_template('sections/themes/themes_cta'); ?>
</main>

<?php get_footer(); ?>
