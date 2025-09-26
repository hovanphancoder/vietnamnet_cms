<!DOCTYPE html>
<html lang="<?= lang_code() ?>">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $title ?? 'Dashboard' ?> - <?= option('site_brand') ?></title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <link rel="apple-touch-icon" sizes="57x57" href="<?= theme_assets('favicon/apple-icon-57x57.png', 'Backend'); ?>">
    <link rel="apple-touch-icon" sizes="60x60" href="<?= theme_assets('favicon/apple-icon-60x60.png', 'Backend'); ?>">
    <link rel="apple-touch-icon" sizes="72x72" href="<?= theme_assets('favicon/apple-icon-72x72.png', 'Backend'); ?>">
    <link rel="apple-touch-icon" sizes="76x76" href="<?= theme_assets('favicon/apple-icon-76x76.png', 'Backend'); ?>">
    <link rel="apple-touch-icon" sizes="114x114" href="<?= theme_assets('favicon/apple-icon-114x114.png', 'Backend'); ?>">
    <link rel="apple-touch-icon" sizes="120x120" href="<?= theme_assets('favicon/apple-icon-120x120.png', 'Backend'); ?>">
    <link rel="apple-touch-icon" sizes="144x144" href="<?= theme_assets('favicon/apple-icon-144x144.png', 'Backend'); ?>">
    <link rel="apple-touch-icon" sizes="152x152" href="<?= theme_assets('favicon/apple-icon-152x152.png', 'Backend'); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= theme_assets('favicon/apple-icon-180x180.png', 'Backend'); ?>">
    <link rel="icon" type="image/png" sizes="192x192"  href="<?= theme_assets('favicon/android-icon-192x192.png', 'Backend'); ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= theme_assets('favicon/favicon-32x32.png', 'Backend'); ?>">
    <link rel="icon" type="image/png" sizes="96x96" href="<?= theme_assets('favicon/favicon-96x96.png', 'Backend'); ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= theme_assets('favicon/favicon-16x16.png', 'Backend'); ?>">
    <link rel="manifest" href="<?= theme_assets('favicon/manifest.json', 'Backend'); ?>">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="<?= theme_assets('favicon/ms-icon-144x144.png', 'Backend'); ?>">
    <meta name="theme-color" content="#ffffff">

    <style type="text/tailwindcss">
        body {
        font-family: 'Inter', sans-serif;
        font-size: var(--font-size);
        font-weight: var(--font-weight);
        line-height: var(--line-height);
      }
      .card-content {
        padding: var(--card-padding);
        border-width: var(--card-border-width);
        border-radius: var(--radius);
      }
      .custom-btn {
        border-radius: var(--button-border-radius);
        font-size: var(--button-font-size);
        padding-top: var(--button-padding-y);
        padding-bottom: var(--button-padding-y);
        padding-left: var(--button-padding-x);
        padding-right: var(--button-padding-x);
      }
      @layer utilities {
        .scrollbar-none {
          scrollbar-width: none;
        }
        .scrollbar-none::-webkit-scrollbar {
          display: none;
        }
      }
      [x-cloak] { display: none !important; }
    </style>

</head>

<body class="bg-background text-foreground">

