<?php

/**
 * Template Name: Plugin Detail
 * Description: Trang chi tiết plugin
 */

use System\Libraries\Render;
use App\Libraries\Fastlang as Flang;
use App\Blocks\Schema\Templates\Product;
use App\Blocks\Schema\Templates\WebSite;
use App\Blocks\Meta\MetaBlock;
use App\Libraries\Fastlang;

// Load language files
Flang::load('CMS', APP_LANG);
Flang::load('Library', APP_LANG);
Flang::load('PluginDetail', APP_LANG);

$plugin = get_post([
    'posttype' => 'plugins',
    'slug' => $params[1] ?? '',
    'withCategories' => true
], APP_LANG);
// Validate plugin data
if (empty($plugin) || !is_array($plugin)) {
    header('HTTP/1.0 404 Not Found');
    get_template('404');
    exit;
}

// Load plugin-specific assets
// Render::asset('css', theme_assets('css/plugin_styles.css'), ['area' => 'frontend', 'location' => 'head']);
Render::asset('css', theme_assets('css/favorite-plugins.css'), ['area' => 'frontend', 'location' => 'head']);
Render::asset('css', theme_assets('css/plugin_detail_styles.css'), ['area' => 'frontend', 'location' => 'head']);
Render::asset('js', theme_assets('js/plugin_detail.js'), ['area' => 'frontend', 'location' => 'footer']);

// Create meta tags
$meta = new MetaBlock();
$meta
    ->title($plugin['title'] . ' - ' . Flang::_e('meta_title', APP_LANG))
    ->description($plugin['seo_description'] ?? Flang::_e('meta_description', APP_LANG))
    ->keywords(Flang::_e('meta_keywords', APP_LANG))
    ->og('image', $plugin['icon_url']) // Sử dụng plugin icon
    ->og('url', base_url('library/plugins/' . $plugin['slug'], APP_LANG))
    ->canonical(base_url('library/plugins/' . $plugin['slug'], APP_LANG));
if (option('favicon')) {
    $logoUrl = theme_assets(option('favicon')['path'] ?? '/images/logo/logo-icon.webp');
    $meta
        ->custom('<link rel="icon" href="' . $logoUrl . '" sizes="32x32" />')
        ->custom('<link rel="apple-touch-icon" href="' . $logoUrl . '" />');
}
// Schema for plugin product
$pluginSchema = new Product();
$pluginSchema->setSchemaData([
    'name' => $plugin['title'],
    'description' => $plugin['seo_description'] ?? '',
    'image' => $plugin['icon_url'], // Sử dụng field icon_url từ database
    'url' => base_url('library/plugins/' . $plugin['slug'], APP_LANG),
    'downloadUrl' => $plugin['install_url'] ?? '',
    'offers' => [
        '@type' => 'Offer',
        'price' => 0, // Plugins are typically free
        'priceCurrency' => 'USD',
        'availability' => 'https://schema.org/InStock'
    ],
    'aggregateRating' => [
        '@type' => 'AggregateRating',
        'ratingValue' => $plugin['rating_avg'] ?? 5,
        'reviewCount' => $plugin['review_count'] ?? 1
    ]
]);
updateViews('plugins', $plugin['id']);
$relatedPlugins = getRelated('plugins', $plugin['id'], 4);

// Render meta tags and schema
get_header([
    'meta' => $meta->render(),
    'schema' => $pluginSchema->render(),
    'layout' => 'plugins'
]);
?>

<!-- Plugin Detail Page - Modern Design -->
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-purple-50">

    <?php get_template('sections/plugin_detail/plugin_detail_hero', [
        'plugin' => $plugin
    ]); ?>

    <!-- Main Content Section -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid lg:grid-cols-3 gap-12">

            <?php get_template('sections/plugin_detail/plugin_detail_content', [
                'plugin' => $plugin
            ]); ?>

            <?php get_template('sections/plugin_detail/plugin_detail_sidebar', [
                'plugin' => $plugin
            ]); ?>

        </div>
    </section>

    <?php get_template('sections/plugin_detail/plugin_detail_related', [
        'related_plugins' => $relatedPlugins ?? []
    ]); ?>
</div>

<?php get_template('sections/plugin_detail/plugin_detail_modal'); ?>

<?php get_template('sections/plugin_detail/plugin_detail_scripts'); ?>

<?php get_footer(); ?>
