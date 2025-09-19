<?php

/**
 * Template Name: Trang Chủ
 * Description: Trang chủ của website truyện tranh.
 *
 * @package FastComic
 */

namespace System\Libraries;
use System\Libraries\Render;
use App\Blocks\Schema\Templates\WebSite;
use App\Blocks\Schema\Templates\Organization;
use App\Blocks\Schema\Templates\FAQPage;
use App\Blocks\Schema\Templates\Product;
use App\Libraries\Fastlang;
use App\Blocks\Meta\MetaBlock;

$subpage = $params[0] ?? '';

if($subpage == 'themes') {
   get_template('library/themes');
} else if($subpage == 'plugins') {
    get_template('library/plugins');
} else {
    Fastlang::load('CMS', APP_LANG);
    Fastlang::load('Library', APP_LANG);
    Render::asset('css', 'css/library_styles.css', ['area' => 'frontend', 'location' => 'head']);
    Render::asset('css', 'css/favorite-themes.css', ['area' => 'frontend', 'location' => 'head']);
    Render::asset('js', 'js/library.js', ['area' => 'frontend', 'location' => 'footer']);


    // Create meta tags for homepage directly from MetaBlock
    $meta = new MetaBlock();

    $meta
        ->title(option('site_library_title', APP_LANG))
        ->description(option('site_library_description', APP_LANG))
        ->robots('index, follow')
        ->canonical(base_url('library'));

    // Add basic meta tags
    $meta
        ->custom('<meta name="generator" content="CMSFullForm">')
        ->custom('<meta name="language" content="' . APP_LANG . '">')
        ->custom('<meta name="author" content="' . option('site_library_title', APP_LANG) . '">')
        ->custom('<meta name="theme-color" content="#354BD9">');

    // Add Open Graph tags

    $locale = APP_LANG === 'en' ? 'en_US' : 'vi_VN';
    $meta
        ->og('locale', $locale)
        ->og('type', 'website')
        ->og('title', option('site_library_title', APP_LANG))
        ->og('description', option('site_library_description', APP_LANG))
        ->og('url', base_url('library'))
        ->og('site_name', option('site_library_title', APP_LANG))
        ->og('updated_time', date('c'));

    // Add Twitter Card tags
    $meta
        ->twitter('card', 'summary_large_image')
        ->twitter('title', option('site_library_title', APP_LANG))
        ->twitter('description', option('site_library_description', APP_LANG))
        ->twitter('site', '@' . option('site_library_title', APP_LANG));

    // Add favicon if available
    if (option('favicon')) {
        $logoUrl = theme_assets(option('favicon')['path'] ?? '/images/logo/logo-icon.webp');
        $meta
            ->og('image', $logoUrl)
            ->twitter('image', $logoUrl)
            ->custom('<link rel="icon" href="' . $logoUrl . '" sizes="32x32" />')
            ->custom('<link rel="apple-touch-icon" href="' . $logoUrl . '" />');
    }
    $pluginsFeatured = get_posts([
        'posttype' => 'plugins',
        'filter' => [
            'is_featured' => 1,
        ],
        'perPage' => 8,
    ], APP_LANG);
    $themesFeatured = get_posts([
        'posttype' => 'themes',
        'filter' => [
            'is_featured' => 1,
        ],
        'perPage' => 8,
    ], APP_LANG);
    $threeinone = [
        'tabs' => [
            [
                'themes' => $themesFeatured['data'],
            ],
            [
                'plugins' => $pluginsFeatured['data'],
            ],
        ],
    ];

    //Schema
    $schemaData = [
        'site' => [
            'title' => option('site_library_title', APP_LANG),
            'desc' => option('site_library_description', APP_LANG),
            'logo' => option('site_logo'),
            'social' => option('social'),
        ],
    ];

    // Create schema for homepage directly from blocks
    $librarySchema  = new WebSite([
        'name' => $schemaData['site']['title'],
        'description' => $schemaData['site']['desc'],
        'url' => base_url(),
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => base_url('search/?s={search_term_string}')
            ],
            'query-input' => 'required name=search_term_string'
        ]
    ]);

    // Add Organization schema
    $orgSchema = new Organization([
        'name' => $schemaData['site']['title'],
        'url' => base_url(),
        'logo' => [
            '@type' => 'ImageObject',
            'url' => is_string($schemaData['site']['logo']) ? $schemaData['site']['logo'] : base_url('assets/images/logo.png'),
            'width' => 600,
            'height' => 60
        ],
        'sameAs' => [
            $schemaData['site']['social']['facebook'] ?? '',
            $schemaData['site']['social']['twitter'] ?? '',
            $schemaData['site']['social']['youtube'] ?? '',
            $schemaData['site']['social']['instagram'] ?? ''
        ]
    ]);

    // Add FAQ schema if available
    if (!empty($schemaData['faq'])) {
        $faqSchema = new FAQPage();
        foreach ($schemaData['faq'] as $faq) {
            $faqSchema->addFAQ($faq['question'], $faq['answer']);
        }
        $librarySchema->addSchemaChild('mainEntity', $faqSchema);
    }

    $publisherData = $orgSchema->getSchemaData();

    // 1) Get publisher data
    $publisherData = $orgSchema->getSchemaData();

    // 2) Build hasPart array (projects + themes/plugins/extensions)
    $hasParts = [];

    // -- Themes, Plugins, Extensions --
    $threeInOne = new \App\Blocks\Schema\SchemaBlock();
    $threeInOne->setSchemaType('ItemList');

    $threeItems = [];
    foreach ($threeinone['tabs'] as $tab) {
        foreach ($tab as $items) {
            foreach ($items as $entry) {
                // Image
                $imageUrl = '';
                if (!empty($entry['thumbnail_url'])) {
                    $imageUrl = theme_assets(get_image_full($entry['thumbnail_url']));
                } elseif (!empty($entry['thumbnail'])) {
                    $imageUrl = theme_assets(get_image_full($entry['thumbnail']));
                } elseif (!empty($entry['icon_url'])) {
                    $imageUrl = theme_assets(get_image_full($entry['icon_url']));
                }

                $prod = new Product([
                    'name'        => $entry['title'] ?? '',
                    'image'       => $imageUrl,
                    'description' => $entry['seo_desc'] ?? $entry['content'] ?? $entry['description'] ?? '' // Sử dụng seo_desc từ bảng mới
                ]);
                if (!empty($entry['price'])) {
                    $prod->setPrice($entry['price'], $entry['currency'] ?? 'USD'); // Sử dụng USD thay vì VND
                }
                if (!empty($entry['rating_avg'])) {
                    $prod->setAggregateRating(
                        $entry['rating_avg'],
                        $entry['rating_count'] ?? 0 // Sử dụng rating_count từ bảng mới
                    );
                }

                $threeItems[] = [
                    '@type'    => 'ListItem',
                    'position' => count($threeItems) + 1,
                    'item'     => $prod->getSchemaData()
                ];
            }
        }
    }

    $threeInOne->setSchemaData([
        'name'            => 'Danh sách Themes, Plugins và Extensions',
        'itemListElement' => $threeItems
    ]);
    $hasParts[] = $threeInOne->getSchemaData();

    // 4) Finally: assign everything at once, without overwriting
    $librarySchema->setSchemaData([
        'publisher'  => $publisherData,
        'hasPart'    => $hasParts,
    ]);
    // Render meta tags and schema in header
    get_header([
        'meta' => $meta->render(),
        'schema' => $librarySchema->render(),
        'layout' => 'library'
    ]);
    ?>

    <?php get_template('sections/library/library_title'); ?>

    <?php get_template('sections/library/library_all'); ?>

    <?php get_template('sections/library/library_themes', ['themes' => $themesFeatured['data'] ?? []]); ?>

    <?php get_template('sections/library/library_plugs', ['plug' => $pluginsFeatured['data'] ?? []]); ?>

    <?php get_template('sections/library/library_statis'); ?>

    <?php get_template('sections/library/library_src'); ?>

    <?php get_template('sections/library/library_rate'); ?>

    <?php get_template('sections/library/library_experience'); ?>

    <?php get_footer(); ?>
<?php } ?>
