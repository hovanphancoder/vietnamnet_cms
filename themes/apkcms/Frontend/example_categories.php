<?php
// Ví dụ về cách lấy và hiển thị danh mục của bài viết

global $post;

// Sử dụng dữ liệu từ $post
$page = $post;

if (empty($page)) {
    echo '<p>Không có dữ liệu bài viết</p>';
    return;
}

// Lấy danh mục của bài viết
$post_categories = [];
if (!empty($page['id']) && !empty($page['posttype'])) {
    $post_categories = get_post_terms($page['id'], $page['posttype'], 'category', APP_LANG);
}

// Lấy tags của bài viết
$post_tags = [];
if (!empty($page['id']) && !empty($page['posttype'])) {
    $post_tags = get_post_terms($page['id'], $page['posttype'], 'tag', APP_LANG);
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ví dụ hiển thị Categories</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug { background: #f0f0f0; padding: 15px; margin: 10px 0; border: 1px solid #ccc; }
        .category-list { margin: 10px 0; }
        .category-item { 
            display: inline-block; 
            background: #007cba; 
            color: white; 
            padding: 5px 10px; 
            margin: 2px; 
            text-decoration: none; 
            border-radius: 3px; 
        }
        .tag-item { 
            display: inline-block; 
            background: #666; 
            color: white; 
            padding: 5px 10px; 
            margin: 2px; 
            text-decoration: none; 
            border-radius: 3px; 
        }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
    </style>
</head>
<body>

<h1>Ví dụ hiển thị Categories và Tags</h1>

<!-- Debug thông tin -->
<div class="debug">
    <h3>Debug - Thông tin bài viết:</h3>
    <p><strong>ID:</strong> <?= $page['id'] ?? 'N/A' ?></p>
    <p><strong>Posttype:</strong> <?= $page['posttype'] ?? 'N/A' ?></p>
    <p><strong>Title:</strong> <?= htmlspecialchars($page['title'] ?? 'N/A') ?></p>
</div>

<!-- Hiển thị Categories -->
<div class="section">
    <h3>Danh mục (Categories):</h3>
    <?php if (!empty($post_categories)): ?>
        <div class="category-list">
            <?php foreach ($post_categories as $category): ?>
                <a href="<?= link_cat($category['slug'], $page['posttype'] ?? 'posts') ?>" 
                   class="category-item">
                    <?= htmlspecialchars($category['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
        
        <!-- Chi tiết từng category -->
        <div style="margin-top: 15px;">
            <h4>Chi tiết categories:</h4>
            <ul>
                <?php foreach ($post_categories as $category): ?>
                    <li>
                        <strong><?= htmlspecialchars($category['name']) ?></strong>
                        <ul>
                            <li>ID: <?= $category['id'] ?></li>
                            <li>Slug: <?= htmlspecialchars($category['slug']) ?></li>
                            <li>Description: <?= htmlspecialchars($category['description'] ?? 'N/A') ?></li>
                            <li>Parent: <?= $category['parent'] ?? 'N/A' ?></li>
                            <li>Active: <?= $category['active'] ? 'Yes' : 'No' ?></li>
                        </ul>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php else: ?>
        <p>Không có danh mục nào được gán cho bài viết này.</p>
    <?php endif; ?>
</div>

<!-- Hiển thị Tags -->
<div class="section">
    <h3>Tags:</h3>
    <?php if (!empty($post_tags)): ?>
        <div class="category-list">
            <?php foreach ($post_tags as $tag): ?>
                <a href="<?= link_cat($tag['slug'], $page['posttype'] ?? 'posts') ?>" 
                   class="tag-item">
                    <?= htmlspecialchars($tag['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
        
        <!-- Chi tiết từng tag -->
        <div style="margin-top: 15px;">
            <h4>Chi tiết tags:</h4>
            <ul>
                <?php foreach ($post_tags as $tag): ?>
                    <li>
                        <strong><?= htmlspecialchars($tag['name']) ?></strong>
                        <ul>
                            <li>ID: <?= $tag['id'] ?></li>
                            <li>Slug: <?= htmlspecialchars($tag['slug']) ?></li>
                            <li>Description: <?= htmlspecialchars($tag['description'] ?? 'N/A') ?></li>
                        </ul>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php else: ?>
        <p>Không có tags nào được gán cho bài viết này.</p>
    <?php endif; ?>
</div>

<!-- Breadcrumb Navigation -->
<div class="section">
    <h3>Breadcrumb Navigation:</h3>
    <nav>
        <a href="/">Trang chủ</a> &gt;
        <?php if (!empty($post_categories)): ?>
            <?php foreach ($post_categories as $index => $category): ?>
                <a href="<?= link_cat($category['slug'], $page['posttype'] ?? 'posts') ?>">
                    <?= htmlspecialchars($category['name']) ?>
                </a>
                <?php if ($index < count($post_categories) - 1): ?>
                    &gt;
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <span>Uncategorized</span>
        <?php endif; ?>
        &gt; <strong><?= htmlspecialchars($page['title'] ?? 'No Title') ?></strong>
    </nav>
</div>

<!-- Raw Data Debug -->
<div class="debug">
    <h3>Raw Data - Categories:</h3>
    <pre><?= print_r($post_categories, true) ?></pre>
    
    <h3>Raw Data - Tags:</h3>
    <pre><?= print_r($post_tags, true) ?></pre>
</div>

</body>
</html>
