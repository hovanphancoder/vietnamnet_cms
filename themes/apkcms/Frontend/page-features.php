<?php

/**
 * Template Name: Trang Chủ
 * Description: Trang chủ của website truyện tranh.
 *
 * @package FastComic
 */

namespace System\Libraries;

use System\Libraries\Render;
use App\Libraries\Fastlang as Flang;
use App\Blocks\Schema\Templates\Book;
use App\Blocks\Schema\Templates\WebSite;
use App\Blocks\Schema\Templates\WebPage;
use App\Blocks\Schema\Templates\Organization;

Flang::load('CMS', APP_LANG);
Flang::load('Features', APP_LANG);
Render::asset('css', '/themes/cmsfullform/Frontend/assets/css/features_styles.css', ['area' => 'frontend', 'location' => 'head']);
Render::asset('js', '/themes/cmsfullform/Frontend/assets/js/features.js', ['area' => 'frontend', 'location' => 'footer']);

use App\Blocks\Meta\MetaBlock;

// Create meta tags for homepage directly from MetaBlock
$meta = new MetaBlock();

$meta
    ->title(option('site_features_title', APP_LANG))
    ->description(option('site_features_description', APP_LANG))
    ->robots('index, follow')
    ->canonical(base_url('features'));

// Add basic meta tags
$meta
    ->custom('<meta name="generator" content="CMSFullForm">')
    ->custom('<meta name="language" content="' . APP_LANG . '">')
    ->custom('<meta name="author" content="' . option('site_features_title', APP_LANG) . '">')
    ->custom('<meta name="theme-color" content="#354BD9">');

// Add Open Graph tags

$locale = APP_LANG === 'en' ? 'en_US' : 'vi_VN';
$meta
    ->og('locale', $locale)
    ->og('type', 'website')
    ->og('title', option('site_features_title', APP_LANG))
    ->og('description', option('site_features_description', APP_LANG))
    ->og('url', base_url('features'))
    ->og('site_name', option('site_features_title', APP_LANG))
    ->og('updated_time', date('c'));

// Add Twitter Card tags
$meta
    ->twitter('card', 'summary_large_image')
    ->twitter('title', option('site_features_title', APP_LANG))
    ->twitter('description', option('site_features_description', APP_LANG))
    ->twitter('site', '@' . option('site_features_title', APP_LANG));
// Add favicon if available
if (option('favicon')) {
    $logoUrl = theme_assets(option('favicon')['path'] ?? '/images/logo/logo-icon.webp');
    $meta
        ->og('image', $logoUrl)
        ->twitter('image', $logoUrl)
        ->custom('<link rel="icon" href="' . $logoUrl . '" sizes="32x32" />')
        ->custom('<link rel="apple-touch-icon" href="' . $logoUrl . '" />');
}

// Prepare data for schema
$schemaData = [
    'site' => [
        'title' => option('site_features_title', APP_LANG),
        'desc' => option('site_features_description', APP_LANG),
        'logo' => option('site_logo', APP_LANG),
        'social' => option('social', APP_LANG),
    ],

];

$features = [
    [
        'name'        => __('feature.multi_posttype.title'),
        'description' => __('feature.multi_posttype.description'),
    ],
    [
        'name'        => __('feature.multi_languages.title'),
        'description' => __('feature.multi_languages.description'),
    ],
    [
        'name'        => __('feature.seo_meta.title'),
        'description' => __('feature.seo_meta.description'),
    ],
    [
        'name'        => __('feature.rich_schema.title'),
        'description' => __('feature.rich_schema.description'),
    ],
    [
        'name'        => __('feature.editor.title'),
        'description' => __('feature.editor.description'),
    ],
    [
        'name'        => __('feature.files_manager.title'),
        'description' => __('feature.files_manager.description'),
    ],
    [
        'name'        => __('feature.super_cache.title'),
        'description' => __('feature.super_cache.description'),
    ],
    [
        'name'        => __('feature.ai_automation.title'),
        'description' => __('feature.ai_automation.description'),
    ],
    [
        'name'        => __('feature.security.title'),
        'description' => __('feature.security.description'),
    ],
    [
        'name'        => __('feature.marketplace.title'),
        'description' => __('feature.marketplace.description'),
    ],
    [
        'name'        => __('feature.custom_options.title'),
        'description' => __('feature.custom_options.description'),
    ],
    [
        'name'        => __('feature.dynamic_router.title'),
        'description' => __('feature.dynamic_router.description'),
    ],
    [
        'name'        => __('feature.rest_api.title'),
        'description' => __('feature.rest_api.description'),
    ],
    [
        'name'        => __('feature.migration_tool.title'),
        'description' => __('feature.migration_tool.description'),
    ],
];

// 2) Create ItemList from $features
$featureList = new \App\Blocks\Schema\SchemaBlock();
$featureList->setSchemaType('ItemList');

$itemElements = [];
foreach ($features as $i => $f) {
    $itemElements[] = [
        '@type'    => 'ListItem',
        'position' => $i + 1,
        'item'     => [
            '@type'       => 'WebPageElement',
            'name'        => $f['name'],
            'description' => $f['description'],
        ]
    ];
}

$featureList->setSchemaData([
    'name'            => __('feature.overview.title') . ' ' . __('feature.overview.subtitle.1'),
    'itemListElement' => $itemElements
]);

// 3) Initialize WebPage schema for "Featured Features" page
$featuresSchema = new WebPage([
    '@id'             => base_url('features') . '#webpage',
    'name'            => __('feature.overview.title') . ' ' . __('feature.overview.subtitle.1'),
    'description'     => __('feature.overview.subtitle'),
    'url'             => base_url('features'),
    'inLanguage'      => 'vi-VN',
    'isPartOf'        => [
        '@type' => 'WebSite',
        '@id'   => base_url() . '#website',
        'name'  => option('site_title'),
        'url'   => base_url()
    ],
    'breadcrumb'      => [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
            [
                '@type'    => 'ListItem',
                'position' => 1,
                'name'     => __('Home'),
                'item'     => base_url()
            ],
            [
                '@type'    => 'ListItem',
                'position' => 2,
                'name'     => __('feature.overview.title'),
                'item'     => base_url('features')
            ]
        ]
    ],
    'mainEntity'      => $featureList->getSchemaData(),
    'potentialAction' => [
        '@type'       => 'SearchAction',
        'target'      => [
            '@type'      => 'EntryPoint',
            'urlTemplate' => base_url('search/?s={search_term_string}')
        ],
        'query-input' => 'required name=search_term_string'
    ]
]);

// Render meta tags and schema in header
get_header([
    'meta' => $meta->render(),
    'schema' => $featuresSchema->render(),
    'layout' => 'features'
]);
?>



<?php get_template('sections/features/features_title'); ?>

<?php get_template('sections/features/features_all'); ?>

<?php get_template('sections/features/features_manager'); ?>

<?php get_template('sections/features/features_seo'); ?>

<?php get_template('sections/features/features_ai'); ?>

<?php get_template('sections/features/features_sercurity'); ?>

<?php get_template('sections/features/features_dev'); ?>

<?php get_template('sections/features/features_migra'); ?>

<?php get_template('sections/features/features_compare'); ?>

<?php get_template('sections/cta'); ?>

<?php get_footer(); ?>
