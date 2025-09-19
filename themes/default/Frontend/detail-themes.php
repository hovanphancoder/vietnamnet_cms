<?php

/**
 * Template Name: Theme Detail
 * Description: Trang chi tiết theme
 */
use System\Libraries\Render;
use App\Libraries\Fastlang as Flang;
use App\Blocks\Schema\Templates\Product;
use App\Blocks\Meta\MetaBlock;

// Load language files
Flang::load('CMS', APP_LANG);
Flang::load('Library', APP_LANG);
Flang::load('ThemeDetail', APP_LANG);
// Prepare theme detail language data for sections
// Language data is now handled directly with Fastlang::_e() in individual sections
// Validate theme data
$theme = get_post([
    'posttype' => 'themes',
    'slug' => $params[0] ?? '',
    'withCategories' => true
], APP_LANG);
if (empty($theme)) {
    get_template('404');
    get_header([
        'layout' => 'themes'
    ]);
    exit;
}
$relatedThemes = getRelated('themes', $theme['id'], 4);
// Load theme-specific assets
Render::asset('css', theme_assets('css/themes-detail_styles.css'), ['area' => 'frontend', 'location' => 'head']);
Render::asset('js', theme_assets('js/theme_detail.js'), ['area' => 'frontend', 'location' => 'footer']);

// Create meta tags
$meta = new MetaBlock();
$meta
    ->title($theme['title'] . ' - ' . option('site_library_title', APP_LANG))
    ->description($theme['seo_desc'] ?? $theme['description'] ?? '') // Sử dụng seo_desc từ bảng mới
    ->og('image', theme_assets(get_image_full($theme['thumbnail_url']))) // Sử dụng thumbnail_url
    ->og('url', base_url('library/themes/' . $theme['slug'], APP_LANG))
    ->canonical(base_url('library/themes/' . $theme['slug'], APP_LANG));
if (option('favicon')) {
    $logoUrl = theme_assets(option('favicon')['path'] ?? '/images/logo/logo-icon.webp');
    $meta
        ->custom('<link rel="icon" href="' . $logoUrl . '" sizes="32x32" />')
        ->custom('<link rel="apple-touch-icon" href="' . $logoUrl . '" />');
}
// Schema for theme product
$themeSchema = new Product();
$themeSchema->setSchemaData([
    'name' => $theme['title'],
    'description' => $theme['seo_desc'] ?? $theme['description'] ?? '', // Sử dụng seo_desc từ bảng mới
    'image' => theme_assets(get_image_full($theme['thumbnail_url'])), // Sử dụng thumbnail_url
    'url' => base_url('library/themes/' . $theme['slug'], APP_LANG),
    'offers' => [
        '@type' => 'Offer',
        'price' => $theme['price'] ?? 0,
        'priceCurrency' => 'USD',
        'availability' => 'https://schema.org/InStock'
    ],
    'aggregateRating' => [
        '@type' => 'AggregateRating',
        'ratingValue' => $theme['rating_avg'] ?? 5,
        'reviewCount' => $theme['rating_count'] ?? 1 // Sử dụng rating_count từ bảng mới
    ]
]);
updateViews('themes', $theme['id']);
// Render meta tags and schema
get_header([
    'meta' => $meta->render(),
    'schema' => $themeSchema->render(),
    'layout' => 'themes'
]);
?>

<!-- Theme Detail Page - Modern Design -->
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50">

    <?php get_template('sections/theme_detail/theme_detail_hero', [
        'theme' => $theme
    ]); ?>

    <!-- Main Content Section -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid lg:grid-cols-3 gap-12">

            <div class="col-span-2">
                <?php get_template('sections/theme_detail/theme_detail_content', [
                    'theme' => $theme
                ]); ?>
            </div>

            <?php get_template('sections/theme_detail/theme_detail_sidebar', [
                'theme' => $theme,
                'authorThemesCount' => $authorThemesCount ?? 0
            ]); ?>

        </div>
    </section>

    <?php get_template('sections/theme_detail/theme_detail_related', [
        'relatedThemes' => $relatedThemes ?? []
    ]); ?>
</div>

<?php get_template('sections/theme_detail/theme_detail_modal'); ?>

<?php get_template('sections/theme_detail/theme_detail_scripts'); ?>

<?php get_footer(); ?>
