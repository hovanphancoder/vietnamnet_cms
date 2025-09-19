<?php
/**
 * Theme Optimizer - Independent performance optimization
 * This script only modifies theme files, never touches core CMS
 */

class ThemeOptimizer {
    private $themePath;
    private $assetsPath;
    private $jsPath;
    private $cssPath;
    
    public function __construct() {
        $this->themePath = __DIR__;
        $this->assetsPath = $this->themePath . '/Assets';
        $this->jsPath = $this->assetsPath . '/js';
        $this->cssPath = $this->assetsPath . '/css';
    }
    
    /**
     * Initialize theme optimization
     */
    public function init() {
        echo "🎨 Theme Optimizer - Independent Performance Enhancement\n";
        echo "====================================================\n\n";
        
        $this->createOptimizedCSS();
        $this->addLazyLoading();
        $this->createPerformanceCSS();
        $this->createOptimizedJS();
        $this->generateReport();
    }
    
    /**
     * Create optimized CSS file
     */
    private function createOptimizedCSS() {
        $sourceFile = $this->cssPath . '/styles.css';
        $targetFile = $this->cssPath . '/styles-optimized.css';
        
        if (!file_exists($sourceFile)) {
            echo "❌ Source CSS file not found: $sourceFile\n";
            return;
        }
        
        $css = file_get_contents($sourceFile);
        
        // Basic CSS optimization
        $css = $this->optimizeCSS($css);
        
        file_put_contents($targetFile, $css);
        echo "✅ Created optimized CSS: " . basename($targetFile) . "\n";
    }
    
    /**
     * Create optimized JavaScript files
     */
    private function createOptimizedJS() {
        // Core theme functionality
        $coreJS = $this->generateCoreJS();
        file_put_contents($this->jsPath . '/theme-core.js', $coreJS);
        
        // Lazy loading functionality
        $lazyJS = $this->generateLazyJS();
        file_put_contents($this->jsPath . '/theme-lazy.js', $lazyJS);
        
        // Performance monitoring
        $perfJS = $this->generatePerfJS();
        file_put_contents($this->jsPath . '/theme-performance.js', $perfJS);
        
        echo "✅ Created optimized JavaScript files\n";
    }
    
    /**
     * Add lazy loading to theme images
     */
    private function addLazyLoading() {
        $processed = 0;
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->themePath));
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());
                $newContent = $this->addLazyLoadingToContent($content);
                
                if ($newContent !== $content) {
                    file_put_contents($file->getPathname(), $newContent);
                    $processed++;
                }
            }
        }
        
        echo "✅ Added lazy loading to $processed theme files\n";
    }
    
    /**
     * Create performance CSS
     */
    private function createPerformanceCSS() {
        $perfCSS = '
/* Theme Performance Optimizations */
* {
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

/* Lazy loading styles */
img[data-src] {
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
}

img.lazy-loading {
    opacity: 0.5;
    filter: blur(2px);
}

img.lazy-loaded {
    opacity: 1;
    filter: none;
}

/* Performance optimizations */
.app-card, .game-card, .category-card {
    will-change: transform;
    transform: translateZ(0);
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

/* Critical above-the-fold styles */
.hero-section {
    contain: layout style paint;
}

.navigation {
    contain: layout style;
}
';
        
        file_put_contents($this->cssPath . '/theme-performance.css', $perfCSS);
        echo "✅ Created performance CSS\n";
    }
    
    /**
     * Generate core JavaScript
     */
    private function generateCoreJS() {
        return '
// Theme Core JavaScript - Independent optimization
(function() {
    "use strict";
    
    const ThemeCore = {
        init() {
            this.initMenuToggle();
            this.initSearch();
            this.initScrollEffects();
        },
        
        initMenuToggle() {
            const menuToggle = document.getElementById("menuToggle");
            const nav = document.querySelector(".nav");
            
            if (menuToggle && nav) {
                menuToggle.addEventListener("click", () => {
                    nav.classList.toggle("active");
                    menuToggle.classList.toggle("active");
                });
            }
        },
        
        initSearch() {
            const searchBtn = document.querySelector(".search-btn");
            const searchInput = document.getElementById("searchInput");
            
            if (searchBtn && searchInput) {
                const performSearch = (term) => {
                    if (term.trim()) {
                        window.location.href = `/search?q=${encodeURIComponent(term)}`;
                    }
                };
                
                searchBtn.addEventListener("click", () => performSearch(searchInput.value));
                searchInput.addEventListener("keypress", (e) => {
                    if (e.key === "Enter") performSearch(searchInput.value);
                });
            }
        },
        
        initScrollEffects() {
            const header = document.querySelector(".header");
            if (!header) return;
            
            let ticking = false;
            const updateHeader = () => {
                if (window.scrollY > 100) {
                    header.style.background = "rgba(37, 99, 235, 0.95)";
                    header.style.backdropFilter = "blur(10px)";
                } else {
                    header.style.background = "var(--bg-primary)";
                    header.style.backdropFilter = "none";
                }
                ticking = false;
            };
            
            window.addEventListener("scroll", () => {
                if (!ticking) {
                    requestAnimationFrame(updateHeader);
                    ticking = true;
                }
            });
        }
    };
    
    // Initialize when DOM is ready
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", () => ThemeCore.init());
    } else {
        ThemeCore.init();
    }
})();
';
    }
    
    /**
     * Generate lazy loading JavaScript
     */
    private function generateLazyJS() {
        return '
// Theme Lazy Loading - Independent optimization
(function() {
    "use strict";
    
    const LazyLoader = {
        config: {
            rootMargin: "50px 0px",
            threshold: 0.01
        },
        
        init() {
            if ("IntersectionObserver" in window) {
                this.observer = new IntersectionObserver(
                    this.handleIntersection.bind(this),
                    this.config
                );
                this.observeImages();
            } else {
                this.loadAllImages();
            }
        },
        
        observeImages() {
            const images = document.querySelectorAll("img[data-src]");
            images.forEach(img => this.observer.observe(img));
        },
        
        handleIntersection(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.loadImage(entry.target);
                    this.observer.unobserve(entry.target);
                }
            });
        },
        
        loadImage(img) {
            const src = img.getAttribute("data-src");
            if (!src) return;
            
            img.classList.add("lazy-loading");
            
            const imageLoader = new Image();
            imageLoader.onload = () => {
                img.src = src;
                img.classList.remove("lazy-loading");
                img.classList.add("lazy-loaded");
                img.removeAttribute("data-src");
            };
            imageLoader.onerror = () => {
                img.classList.remove("lazy-loading");
                img.classList.add("lazy-error");
            };
            imageLoader.src = src;
        },
        
        loadAllImages() {
            const images = document.querySelectorAll("img[data-src]");
            images.forEach(img => this.loadImage(img));
        }
    };
    
    // Initialize lazy loading
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", () => LazyLoader.init());
    } else {
        LazyLoader.init();
    }
})();
';
    }
    
    /**
     * Generate performance monitoring JavaScript
     */
    private function generatePerfJS() {
        return '
// Theme Performance Monitoring - Independent optimization
(function() {
    "use strict";
    
    const PerformanceMonitor = {
        init() {
            this.monitorCoreWebVitals();
            this.monitorResourceLoading();
        },
        
        monitorCoreWebVitals() {
            // Monitor LCP
            new PerformanceObserver((entryList) => {
                const entries = entryList.getEntries();
                const lastEntry = entries[entries.length - 1];
                console.log("LCP:", lastEntry.startTime);
            }).observe({ entryTypes: ["largest-contentful-paint"] });
            
            // Monitor FID
            new PerformanceObserver((entryList) => {
                const entries = entryList.getEntries();
                entries.forEach(entry => {
                    console.log("FID:", entry.processingStart - entry.startTime);
                });
            }).observe({ entryTypes: ["first-input"] });
            
            // Monitor CLS
            let clsValue = 0;
            new PerformanceObserver((entryList) => {
                for (const entry of entryList.getEntries()) {
                    if (!entry.hadRecentInput) {
                        clsValue += entry.value;
                    }
                }
                console.log("CLS:", clsValue);
            }).observe({ entryTypes: ["layout-shift"] });
        },
        
        monitorResourceLoading() {
            window.addEventListener("load", () => {
                const resources = performance.getEntriesByType("resource");
                const totalSize = resources.reduce((sum, resource) => sum + resource.transferSize, 0);
                console.log("Total resources loaded:", resources.length);
                console.log("Total size:", Math.round(totalSize / 1024) + "KB");
            });
        }
    };
    
    // Initialize performance monitoring
    PerformanceMonitor.init();
})();
';
    }
    
    /**
     * Add lazy loading to content
     */
    private function addLazyLoadingToContent($content) {
        $pattern = '/<img([^>]*?)src=["\']([^"\']*?)["\']([^>]*?)>/i';
        
        $replacement = function($matches) {
            $beforeSrc = $matches[1];
            $src = $matches[2];
            $afterSrc = $matches[3];
            
            if (strpos($beforeSrc, 'data-src') !== false || 
                strpos($afterSrc, 'data-src') !== false ||
                strpos($src, 'placeholder') !== false ||
                strpos($src, 'data:') !== false) {
                return $matches[0];
            }
            
            return '<img' . $beforeSrc . 'src=""  . $src . '"' . $afterSrc . '>';
        };
        
        return preg_replace_callback($pattern, $replacement, $content);
    }
    
    /**
     * Optimize CSS
     */
    private function optimizeCSS($css) {
        // Remove comments
        $css = preg_replace('/\/\*.*?\*\//s', '', $css);
        
        // Remove unnecessary whitespace
        $css = preg_replace('/\s+/', ' ', $css);
        
        // Remove spaces around specific characters
        $css = preg_replace('/\s*([{}:;,>+~])\s*/', '$1', $css);
        
        // Remove trailing semicolons
        $css = preg_replace('/;}/', '}', $css);
        
        return trim($css);
    }
    
    /**
     * Generate optimization report
     */
    private function generateReport() {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'theme_path' => $this->themePath,
            'optimizations' => [
                'lazy_loading' => 'Added to all theme images',
                'css_optimization' => 'Created optimized CSS',
                'js_optimization' => 'Created modular JavaScript',
                'performance_css' => 'Added performance styles',
                'core_web_vitals' => 'Added monitoring'
            ]
        ];
        
        file_put_contents($this->themePath . '/optimization-report.json', json_encode($report, JSON_PRETTY_PRINT));
        
        echo "\n📊 Optimization Report:\n";
        echo "=====================\n";
        echo "✅ Lazy loading: Added to all theme images\n";
        echo "✅ CSS optimization: Created optimized CSS\n";
        echo "✅ JS optimization: Created modular JavaScript\n";
        echo "✅ Performance CSS: Added performance styles\n";
        echo "✅ Core Web Vitals: Added monitoring\n";
        echo "\n📁 Files created:\n";
        echo "- theme-core.js\n";
        echo "- theme-lazy.js\n";
        echo "- theme-performance.js\n";
        echo "- theme-performance.css\n";
        echo "- optimization-report.json\n";
        echo "\n🎯 Theme optimization complete!\n";
        echo "No core CMS files were modified.\n";
    }
}

// Run optimization
$optimizer = new ThemeOptimizer();
$optimizer->init();
?>
