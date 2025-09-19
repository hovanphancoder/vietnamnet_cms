<?php
/**
 * Theme Assets Loader - Independent optimization
 * This file handles loading of optimized theme assets
 * Does not modify core CMS functionality
 */

// Prevent direct access
if (!defined('APP_THEME_NAME')) {
    die('Direct access not allowed');
}

/**
 * Load optimized theme assets
 */
function load_theme_assets() {
    $themePath = '/themes/' . APP_THEME_NAME . '/Frontend/Assets';
    
    // CSS Assets
    $cssAssets = [
        'theme-performance.css' => 'Performance optimizations',
        'styles.min.css' => 'Main theme styles'
    ];
    
    // JavaScript Assets
    $jsAssets = [
        'theme-core.js' => 'Core theme functionality',
        'theme-lazy.js' => 'Lazy loading functionality',
        'theme-performance.js' => 'Performance monitoring'
    ];
    
    // Generate CSS links
    $cssOutput = '';
    foreach ($cssAssets as $file => $description) {
        $filePath = $themePath . '/css/' . $file;
        if (file_exists(__DIR__ . '/Assets/css/' . $file)) {
            $cssOutput .= "    <link rel=\"stylesheet\" href=\"{$filePath}\" as=\"style\" type=\"text/css\" media=\"all\" />\n";
        }
    }
    
    // Generate JavaScript loading
    $jsOutput = '
    <script>
    // Theme Asset Loader - Independent optimization
    (function() {
        function loadThemeAsset(src, type = "script") {
            if (type === "script") {
                const script = document.createElement("script");
                script.src = src;
                script.defer = true;
                document.head.appendChild(script);
            } else if (type === "style") {
                const link = document.createElement("link");
                link.rel = "stylesheet";
                link.href = src;
                document.head.appendChild(link);
            }
        }
        
        // Load core functionality immediately
        loadThemeAsset("' . $themePath . '/js/theme-core.js");
        
        // Load lazy loading
        loadThemeAsset("' . $themePath . '/js/theme-lazy.js");
        
        // Load performance monitoring
        loadThemeAsset("' . $themePath . '/js/theme-performance.js");
        
        // Load page-specific scripts
        document.addEventListener("DOMContentLoaded", function() {
            // Load single page scripts if needed
            if (document.querySelector("#unfold-table, #toc-trigger")) {
                loadThemeAsset("' . $themePath . '/js/single.min.js");
            }
            
            if (document.querySelector("#title-post") && !document.querySelector("#unfold-table")) {
                loadThemeAsset("' . $themePath . '/js/single-news.min.js");
            }
        });
    })();
    </script>';
    
    return $cssOutput . $jsOutput;
}

/**
 * Get optimized image URL with lazy loading
 */
function get_optimized_image_url($url, $lazy = true) {
    if (!$url) return '';
    
    if ($lazy) {
        return 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PC9zdmc+';
    }
    
    return $url;
}

/**
 * Get lazy loading attributes
 */
function get_lazy_loading_attrs($src, $lazy = true) {
    if (!$lazy) {
        return 'src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '"';
    }
    
    $placeholder = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PC9zdmc+';
    
    return 'src="' . $placeholder . '" data-src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" loading="lazy"';
}

/**
 * Check if optimization is enabled
 */
function is_theme_optimization_enabled() {
    return file_exists(__DIR__ . '/theme-optimizer.php');
}

/**
 * Get optimization status
 */
function get_optimization_status() {
    $status = [
        'optimizer_exists' => file_exists(__DIR__ . '/theme-optimizer.php'),
        'core_js_exists' => file_exists(__DIR__ . '/Assets/js/theme-core.js'),
        'lazy_js_exists' => file_exists(__DIR__ . '/Assets/js/theme-lazy.js'),
        'perf_js_exists' => file_exists(__DIR__ . '/Assets/js/theme-performance.js'),
        'perf_css_exists' => file_exists(__DIR__ . '/Assets/css/theme-performance.css'),
        'report_exists' => file_exists(__DIR__ . '/optimization-report.json')
    ];
    
    return $status;
}

/**
 * Generate performance report
 */
function generate_performance_report() {
    $status = get_optimization_status();
    $report = [
        'timestamp' => date('Y-m-d H:i:s'),
        'theme_path' => __DIR__,
        'status' => $status,
        'optimizations' => [
            'lazy_loading' => $status['lazy_js_exists'] ? 'Enabled' : 'Disabled',
            'css_optimization' => $status['perf_css_exists'] ? 'Enabled' : 'Disabled',
            'js_optimization' => $status['core_js_exists'] ? 'Enabled' : 'Disabled',
            'performance_monitoring' => $status['perf_js_exists'] ? 'Enabled' : 'Disabled'
        ]
    ];
    
    return $report;
}
?>
