<?php
/**
 * CSS Minifier
 * Minifies CSS file and updates the minified version
 */

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
    $css = preg_replace('/\s*;\s*/', ';', $css);
    
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

// File paths
$sourceFile = 'styles.css';
$targetFile = 'styles.min.css';

// Check if source file exists
if (!file_exists($sourceFile)) {
    die("Error: Source file '$sourceFile' not found.\n");
}

// Read source CSS
$css = file_get_contents($sourceFile);

if ($css === false) {
    die("Error: Could not read source file '$sourceFile'.\n");
}

// Minify CSS
$minifiedCSS = minifyCSS($css);

// Write minified CSS to target file
$result = file_put_contents($targetFile, $minifiedCSS);

if ($result === false) {
    die("Error: Could not write to target file '$targetFile'.\n");
}

// Get file sizes
$originalSize = filesize($sourceFile);
$minifiedSize = filesize($targetFile);
$compressionRatio = round((1 - $minifiedSize / $originalSize) * 100, 2);

echo "✅ CSS minification completed successfully!\n";
echo "📁 Source file: $sourceFile (" . number_format($originalSize) . " bytes)\n";
echo "📁 Minified file: $targetFile (" . number_format($minifiedSize) . " bytes)\n";
echo "📊 Compression ratio: $compressionRatio%\n";
echo "💾 Space saved: " . number_format($originalSize - $minifiedSize) . " bytes\n";
?>
