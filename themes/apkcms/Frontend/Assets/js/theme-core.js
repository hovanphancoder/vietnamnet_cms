
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
