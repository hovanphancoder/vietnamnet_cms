// Lazy loading for images
(function() {
    'use strict';

    const LazyLoader = {
        // Configuration
        config: {
            rootMargin: '50px 0px',
            threshold: 0.01,
            placeholder: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PC9zdmc+'
        },

        // Initialize lazy loading
        init() {
            if ('IntersectionObserver' in window) {
                this.observer = new IntersectionObserver(
                    this.handleIntersection.bind(this),
                    this.config
                );
                this.observeImages();
            } else {
                // Fallback for older browsers
                this.loadAllImages();
            }
        },

        // Observe all images with data-src
        observeImages() {
            const images = document.querySelectorAll('img[data-src]');
            images.forEach(img => {
                this.observer.observe(img);
            });
        },

        // Handle intersection
        handleIntersection(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.loadImage(entry.target);
                    this.observer.unobserve(entry.target);
                }
            });
        },

        // Load image
        loadImage(img) {
            const src = img.getAttribute('data-src');
            if (!src) return;

            // Add loading class
            img.classList.add('lazy-loading');

            // Create new image to preload
            const imageLoader = new Image();

            imageLoader.onload = () => {
                img.src = src;
                img.classList.remove('lazy-loading');
                img.classList.add('lazy-loaded');

                // Remove data-src attribute
                img.removeAttribute('data-src');
            };

            imageLoader.onerror = () => {
                img.classList.remove('lazy-loading');
                img.classList.add('lazy-error');
                console.warn('Failed to load image:', src);
            };

            imageLoader.src = src;
        },

        // Fallback: load all images immediately
        loadAllImages() {
            const images = document.querySelectorAll('img[data-src]');
            images.forEach(img => this.loadImage(img));
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => LazyLoader.init());
    } else {
        LazyLoader.init();
    }

})();