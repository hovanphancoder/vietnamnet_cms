<?php
/**
 * Auto CSS Minifier
 * Tự động minify CSS khi có thay đổi
 */

function autoMinifyCSS() {
    $sourceFile = __DIR__ . '/styles.css';
    $targetFile = __DIR__ . '/styles.min.css';
    
    // Kiểm tra file source có tồn tại không
    if (!file_exists($sourceFile)) {
        return false;
    }
    
    // Kiểm tra file source có mới hơn target không
    if (file_exists($targetFile) && filemtime($sourceFile) <= filemtime($targetFile)) {
        return true; // Không cần minify
    }
    
    // Đọc CSS source
    $css = file_get_contents($sourceFile);
    
    // Minify CSS
    $minifiedCSS = minifyCSS($css);
    
    // Ghi file minified
    return file_put_contents($targetFile, $minifiedCSS) !== false;
}

function minifyCSS($css) {
    // Remove comments
    $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
    
    // Remove unnecessary whitespace
    $css = preg_replace('/\s+/', ' ', $css);
    
    // Remove spaces around specific characters
    $css = preg_replace('/\s*{\s*/', '{', $css);
    $css = preg_replace('/;\s*/', ';', $css);
    $css = preg_replace('/\s*}\s*/', '}', $css);
    $css = preg_replace('/\s*,\s*/', ',', $css);
    $css = preg_replace('/\s*:\s*/', ':', $css);
    
    // Remove trailing semicolons
    $css = preg_replace('/;}/', '}', $css);
    
    // Remove spaces before and after operators
    $css = preg_replace('/\s*>\s*/', '>', $css);
    $css = preg_replace('/\s*\+\s*/', '+', $css);
    $css = preg_replace('/\s*~\s*/', '~', $css);
    
    // Remove spaces around parentheses
    $css = preg_replace('/\s*\(\s*/', '(', $css);
    $css = preg_replace('/\s*\)\s*/', ')', $css);
    
    // Remove leading and trailing whitespace
    $css = trim($css);
    
    return $css;
}

// Chạy auto minify
if (autoMinifyCSS()) {
    echo "✅ CSS minified successfully!\n";
} else {
    echo "❌ CSS minification failed!\n";
}
?>
