<?php
/**
 * Theme Integration - Independent optimization
 * This file integrates optimizations into theme without modifying core CMS
 */

// Prevent direct access
if (!defined('APP_THEME_NAME')) {
    die('Direct access not allowed');
}

// Include theme assets loader
require_once __DIR__ . '/theme-assets.php';

/**
 * Enhanced get_header function for theme
 * Adds optimized assets without modifying core
 */
function theme_get_header($args = []) {
    // Call original get_header if it exists
    if (function_exists('get_header')) {
        // Add optimized assets to append
        $args['append'] = ($args['append'] ?? '') . load_theme_assets();
        
        // Call original function
        return get_header($args);
    }
    
    // Fallback if get_header doesn't exist
    echo '<!DOCTYPE html><html><head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo load_theme_assets();
    echo '</head><body>';
}

/**
 * Enhanced get_footer function for theme
 * Adds optimized scripts without modifying core
 */
function theme_get_footer() {
    // Call original get_footer if it exists
    if (function_exists('get_footer')) {
        return get_footer();
    }
    
    // Fallback if get_footer doesn't exist
    echo '</body></html>';
}

/**
 * Optimized image function for theme
 */
function theme_get_image($url, $alt = '', $class = '', $lazy = true) {
    $attrs = get_lazy_loading_attrs($url, $lazy);
    $classAttr = $class ? ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"' : '';
    $altAttr = $alt ? ' alt="' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . '"' : '';
    
    return '<img' . $attrs . $classAttr . $altAttr . '>';
}

/**
 * Performance monitoring for theme
 */
function theme_performance_monitor() {
    if (!is_theme_optimization_enabled()) {
        return;
    }
    
    $report = generate_performance_report();
    
    echo '<!-- Theme Performance Report -->';
    echo '<script>';
    echo 'console.log("Theme Optimization Status:", ' . json_encode($report) . ');';
    echo '</script>';
}

/**
 * Initialize theme optimizations
 */
function init_theme_optimizations() {
    // Add performance monitoring
    add_action('wp_footer', 'theme_performance_monitor');
    
    // Add optimized assets to head
    add_action('wp_head', function() {
        echo load_theme_assets();
    });
}

// Auto-initialize if WordPress actions are available
if (function_exists('add_action')) {
    init_theme_optimizations();
}

/**
 * Theme optimization status check
 */
function check_theme_optimization_status() {
    $status = get_optimization_status();
    $allGood = array_reduce($status, function($carry, $item) {
        return $carry && $item;
    }, true);
    
    if ($allGood) {
        echo '<div style="background: #d4edda; color: #155724; padding: 10px; margin: 10px 0; border-radius: 4px;">';
        echo '✅ Theme optimization is active and working properly.';
        echo '</div>';
    } else {
        echo '<div style="background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 4px;">';
        echo '⚠️ Theme optimization is not fully active. Run theme-optimizer.php to enable.';
        echo '</div>';
    }
}

/**
 * Theme optimization admin notice
 */
function theme_optimization_admin_notice() {
    if (is_admin() && current_user_can('manage_options')) {
        $status = get_optimization_status();
        $allGood = array_reduce($status, function($carry, $item) {
            return $carry && $item;
        }, true);
        
        if (!$allGood) {
            echo '<div class="notice notice-warning">';
            echo '<p><strong>Theme Optimization:</strong> Run theme-optimizer.php to enable performance optimizations.</p>';
            echo '</div>';
        }
    }
}

// Add admin notice if in admin area
if (is_admin() && function_exists('add_action')) {
    add_action('admin_notices', 'theme_optimization_admin_notice');
}
?>
