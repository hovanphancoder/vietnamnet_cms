
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
