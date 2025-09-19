
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
