// Optimized script for better performance
(function() {
    'use strict';

    // Core functionality - loaded immediately
    const Core = {
        // DOM Elements cache
        elements: {},

        // Initialize core functionality
        init() {
            this.cacheElements();
            this.initMenuToggle();
            this.initSearch();
            this.initScrollEffects();
        },

        // Cache frequently used DOM elements
        cacheElements() {
            this.elements = {
                menuToggle: document.getElementById('menuToggle'),
                nav: document.querySelector('.nav'),
                searchInput: document.getElementById('searchInput'),
                searchBtn: document.querySelector('.search-btn'),
                header: document.querySelector('.header')
            };
        },

        // Mobile menu toggle
        initMenuToggle() {
            const {
                menuToggle,
                nav
            } = this.elements;
            if (!menuToggle || !nav) return;

            menuToggle.addEventListener('click', () => {
                nav.classList.toggle('active');
                menuToggle.classList.toggle('active');
                this.animateHamburger(menuToggle);
            });
        },

        // Animate hamburger menu
        animateHamburger(menuToggle) {
            const spans = menuToggle.querySelectorAll('span');
            spans.forEach((span, index) => {
                if (menuToggle.classList.contains('active')) {
                    if (index === 0) span.style.transform = 'rotate(45deg) translate(5px, 5px)';
                    if (index === 1) span.style.opacity = '0';
                    if (index === 2) span.style.transform = 'rotate(-45deg) translate(7px, -6px)';
                } else {
                    span.style.transform = 'none';
                    span.style.opacity = '1';
                }
            });
        },

        // Search functionality
        initSearch() {
            const {
                searchBtn,
                searchInput
            } = this.elements;

            if (searchBtn && searchInput) {
                const performSearch = (term) => {
                    if (!term.trim()) return;
                    console.log(`Searching for: ${term}`);
                    // Redirect to search page
                    window.location.href = `/search?q=${encodeURIComponent(term)}`;
                };

                searchBtn.addEventListener('click', () => performSearch(searchInput.value));
                searchInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') performSearch(searchInput.value);
                });
            }
        },

        // Header scroll effects
        initScrollEffects() {
            const {
                header
            } = this.elements;
            if (!header) return;

            let ticking = false;
            const updateHeader = () => {
                if (window.scrollY > 100) {
                    header.style.background = 'rgba(37, 99, 235, 0.95)';
                    header.style.backdropFilter = 'blur(10px)';
                } else {
                    header.style.background = 'var(--bg-primary)';
                    header.style.backdropFilter = 'none';
                }
                ticking = false;
            };

            window.addEventListener('scroll', () => {
                if (!ticking) {
                    requestAnimationFrame(updateHeader);
                    ticking = true;
                }
            });
        }
    };

    // Lazy loaded functionality
    const LazyFeatures = {
        // Initialize features when needed
        init() {
            // Use requestIdleCallback for non-critical features
            if ('requestIdleCallback' in window) {
                requestIdleCallback(() => this.loadFeatures());
            } else {
                setTimeout(() => this.loadFeatures(), 100);
            }
        },

        loadFeatures() {
            this.initSidenav();
            this.initModals();
            this.initTabs();
            this.initHeroSlider();
        },

        // Sidenav functionality
        initSidenav() {
            const triggers = document.querySelectorAll('.sidenav-trigger');
            const overlays = document.querySelectorAll('.sidenav-overlay');

            triggers.forEach(trigger => {
                trigger.addEventListener('click', (e) => {
                    e.preventDefault();
                    const target = trigger.getAttribute('data-target');
                    const sidenav = document.getElementById(target);
                    const overlay = document.querySelector(`.sidenav-overlay[data-target="${target}"]`);

                    if (sidenav) this.showSidenav(sidenav, overlay);
                });
            });

            overlays.forEach(overlay => {
                overlay.addEventListener('click', () => {
                    const target = overlay.getAttribute('data-target');
                    const sidenav = document.getElementById(target);
                    this.hideSidenav(sidenav, overlay);
                });
            });
        },

        showSidenav(sidenav, overlay) {
            if (sidenav) {
                sidenav.classList.add('sidenav-open');
                if (overlay) overlay.classList.add('sidenav-overlay-open');
                document.body.style.overflow = 'hidden';
            }
        },

        hideSidenav(sidenav, overlay) {
            if (sidenav) {
                sidenav.classList.remove('sidenav-open');
                if (overlay) overlay.classList.remove('sidenav-overlay-open');
                document.body.style.overflow = '';
            }
        },

        // Modal functionality
        initModals() {
            const downloadBtns = document.querySelectorAll('.download-btn');
            downloadBtns.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const card = btn.closest('.app-card, .game-card');
                    const name = card ? .querySelector('.app-name, .game-name') ? .textContent;
                    if (name) this.showDownloadModal(name);
                });
            });
        },

        showDownloadModal(appName) {
            const modal = document.createElement('div');
            modal.className = 'modal-overlay';
            modal.innerHTML = `
                <div class="modal">
                    <div class="modal-header">
                        <h3>Confirm Download</h3>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to download <strong>${appName}</strong>?</p>
                        <div class="modal-actions">
                            <button class="btn-cancel">Cancel</button>
                            <button class="btn-confirm">Download</button>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);

            // Event handlers
            const closeModal = () => modal.remove();
            modal.querySelector('.modal-close').addEventListener('click', closeModal);
            modal.querySelector('.btn-cancel').addEventListener('click', closeModal);
            modal.querySelector('.btn-confirm').addEventListener('click', () => {
                alert(`Download started for ${appName}!`);
                closeModal();
            });
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });
        },

        // Tab functionality
        initTabs() {
            const tabBtns = document.querySelectorAll('.tab-btn');
            tabBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    const tab = btn.getAttribute('data-tab');
                    const section = btn.closest('section');

                    section.querySelectorAll('.tab-btn').forEach(t => t.classList.remove('active'));
                    btn.classList.add('active');

                    if (section.classList.contains('featured-games')) {
                        this.filterGames(tab);
                    } else if (section.classList.contains('featured-apps')) {
                        this.filterApps(tab);
                    }
                });
            });
        },

        filterGames(tab) {
            const gameCards = document.querySelectorAll('.game-card');
            gameCards.forEach(card => {
                const category = card.getAttribute('data-category');
                if (tab === 'featured' || category === tab) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        },

        filterApps(tab) {
            const appCards = document.querySelectorAll('.app-card');
            appCards.forEach(card => {
                const category = card.getAttribute('data-category');
                if (tab === 'featured' || category === tab) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        },

        // Hero slider
        initHeroSlider() {
            const slides = document.querySelectorAll('.hero-slide');
            if (slides.length === 0) return;

            let currentSlide = 0;
            const showSlide = (index) => {
                slides.forEach((slide, i) => {
                    slide.classList.toggle('active', i === index);
                });
            };

            const nextSlide = () => {
                currentSlide = (currentSlide + 1) % slides.length;
                showSlide(currentSlide);
            };

            // Auto slide
            const slideInterval = setInterval(nextSlide, 5000);

            // Pause on hover
            const heroSlider = document.querySelector('.hero-slider');
            if (heroSlider) {
                heroSlider.addEventListener('mouseenter', () => clearInterval(slideInterval));
                heroSlider.addEventListener('mouseleave', () => setInterval(nextSlide, 5000));
            }
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            Core.init();
            LazyFeatures.init();
            initSearch();
        });
    } else {
        Core.init();
        LazyFeatures.init();
        initSearch();
    }

})();

// Search functionality
function initSearch() {
    const searchOpen = document.getElementById('search-open');
    const searchForm = document.getElementById('search-form');
    const searchClose = document.getElementById('search-close');

    if (searchOpen && searchForm) {
        searchOpen.addEventListener('click', () => {
            showSearchForm();
        });
    }

    if (searchClose && searchForm) {
        searchClose.addEventListener('click', (e) => {
            hideSearchForm();
        });
    }

    // Close search form when pressing Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (searchForm && searchForm.classList.contains('active')) {
                hideSearchForm();
            }
        }
    });
}

function showSearchForm() {
    const searchForm = document.getElementById('search-form');
    if (searchForm) {
        searchForm.classList.add('active');
        // Focus on search input if it exists
        const searchInput = searchForm.querySelector('input[type="search"]');
        if (searchInput) {
            searchInput.focus();
        }
    }
}

function hideSearchForm() {
    const searchForm = document.getElementById('search-form');
    if (searchForm) {
        searchForm.classList.remove('active');
    }
}