<?php
// Ví dụ về cách sử dụng dữ liệu từ $post trong single.php

global $post;

// Sử dụng dữ liệu từ $post (đã được set trong FrontendController)
$page = $post;

// Kiểm tra xem $post có dữ liệu không
if (empty($page)) {
    echo '<p>Không có dữ liệu bài viết</p>';
    return;
}

// Debug: Xem cấu trúc dữ liệu $post
echo '<pre style="background: #f4f4f4; padding: 10px; margin: 10px 0;">';
echo '<strong>Dữ liệu từ $post:</strong><br>';
print_r($page);
echo '</pre>';

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page['title'] ?? 'No Title') ?></title>
</head>
<body>

<div class="container">
    <!-- Tiêu đề bài viết -->
    <h1 class="article-title">
        <?= htmlspecialchars($page['title'] ?? 'No Title Available') ?>
    </h1>

    <!-- Meta thông tin -->
    <div class="article-meta">
        <span class="date">
            Ngày đăng: <?= !empty($page['created_at']) ? date('d/m/Y H:i', strtotime($page['created_at'])) : 'N/A' ?>
        </span>
        <span class="author">
            Tác giả: <?= htmlspecialchars($page['author'] ?? get_user_name($page['user_id'] ?? 0) ?? 'Admin') ?>
        </span>
        <span class="views">
            Lượt xem: <?= format_views($page['views'] ?? 0) ?>
        </span>
    </div>

    <!-- Mô tả/Excerpt -->
    <?php if (!empty($page['description']) || !empty($page['excerpt'])): ?>
    <div class="article-excerpt">
        <p><?= htmlspecialchars($page['description'] ?? $page['excerpt']) ?></p>
    </div>
    <?php endif; ?>

    <!-- Hình ảnh đại diện -->
    <?php if (!empty($page['feature'])): ?>
    <div class="article-featured-image">
        <?= _img($page['feature'], $page['title'], true, 'w-full h-auto') ?>
    </div>
    <?php endif; ?>

    <!-- Nội dung chính -->
    <div class="article-content">
        <?= $page['content'] ?? '<p>Nội dung không có sẵn</p>' ?>
    </div>

    <!-- Thông tin bổ sung -->
    <div class="article-info">
        <h3>Thông tin bài viết:</h3>
        <ul>
            <li><strong>ID:</strong> <?= $page['id'] ?? 'N/A' ?></li>
            <li><strong>Slug:</strong> <?= htmlspecialchars($page['slug'] ?? 'N/A') ?></li>
            <li><strong>Trạng thái:</strong> <?= htmlspecialchars($page['status'] ?? 'N/A') ?></li>
            <li><strong>Ngày tạo:</strong> <?= $page['created_at'] ?? 'N/A' ?></li>
            <li><strong>Ngày cập nhật:</strong> <?= $page['updated_at'] ?? 'N/A' ?></li>
            <li><strong>Posttype:</strong> <?= htmlspecialchars($page['posttype'] ?? 'N/A') ?></li>
        </ul>
    </div>

    <!-- Categories/Tags nếu có -->
    <?php if (!empty($page['categories']) || !empty($page['tags'])): ?>
    <div class="article-taxonomy">
        <?php if (!empty($page['categories'])): ?>
        <div class="categories">
            <strong>Danh mục:</strong>
            <?php foreach ($page['categories'] as $category): ?>
                <a href="<?= link_cat($category['slug']) ?>" class="category-link">
                    <?= htmlspecialchars($category['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($page['tags'])): ?>
        <div class="tags">
            <strong>Tags:</strong>
            <?php foreach ($page['tags'] as $tag): ?>
                <span class="tag"><?= htmlspecialchars($tag['name']) ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>

<style>
.container { max-width: 800px; margin: 0 auto; padding: 20px; }
.article-title { font-size: 2rem; margin-bottom: 20px; color: #333; }
.article-meta { margin-bottom: 20px; color: #666; }
.article-meta span { margin-right: 20px; }
.article-excerpt { margin-bottom: 20px; font-style: italic; color: #555; }
.article-featured-image { margin-bottom: 20px; }
.article-content { line-height: 1.6; margin-bottom: 30px; }
.article-info { background: #f9f9f9; padding: 15px; margin-bottom: 20px; }
.article-info ul { list-style: none; padding: 0; }
.article-info li { margin-bottom: 5px; }
.category-link { 
    display: inline-block; 
    background: #007cba; 
    color: white; 
    padding: 2px 8px; 
    margin: 2px; 
    text-decoration: none; 
    border-radius: 3px; 
}
.tag { 
    display: inline-block; 
    background: #666; 
    color: white; 
    padding: 2px 8px; 
    margin: 2px; 
    border-radius: 3px; 
}
</style>

</body>
</html>
