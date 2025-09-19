// Script loaded test
console.log('Script.js loaded successfully!');

// DOM Elements
const menuToggle = document.getElementById('menuToggle');
const nav = document.querySelector('.nav');
const searchInput = document.getElementById('searchInput');
const searchBtn = document.querySelector('.search-btn');
const navLinks = document.querySelectorAll('.nav-link');
const downloadBtns = document.querySelectorAll('.download-btn');
const updateBtns = document.querySelectorAll('.update-btn');
const infoBtns = document.querySelectorAll('.info-btn');
const tabBtns = document.querySelectorAll('.tab-btn');
const filterBtns = document.querySelectorAll('.filter-btn');

// APKMody Header Elements
const header = document.getElementById('header');
const searchOpen = document.getElementById('search-open');
const menuItems = document.querySelectorAll('.menu-item');
const clickableLinks = document.querySelectorAll('.clickable');

// Mobile Menu Toggle
if (menuToggle && nav) {
    menuToggle.addEventListener('click', () => {
        nav.classList.toggle('active');
        menuToggle.classList.toggle('active');

        // Animate hamburger menu
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
    });
}

// Search Functionality
if (searchBtn && searchInput) {
    searchBtn.addEventListener('click', () => {
        const searchTerm = searchInput.value.trim();
        if (searchTerm) {
            performSearch(searchTerm);
        }
    });
}

if (searchInput) {
    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            const searchTerm = searchInput.value.trim();
            if (searchTerm) {
                performSearch(searchTerm);
            }
        }
    });
}

function performSearch(term) {
    console.log(`Searching for: ${term}`);

    // Show loading state
    if (searchBtn) {
        searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    }

    // Simulate API call
    setTimeout(() => {
        if (searchBtn) {
            searchBtn.innerHTML = '<i class="fas fa-search"></i>';
        }
        showSearchResults(term);
    }, 1000);
}

function showSearchResults(term) {
    alert(`Search results for "${term}" will be displayed here.`);
}

// Navigation Active State
navLinks.forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();

        // Remove active class from all links
        navLinks.forEach(l => l.classList.remove('active'));

        // Add active class to clicked link
        link.classList.add('active');

        // Close mobile menu if open
        if (nav && nav.classList.contains('active')) {
            nav.classList.remove('active');
            if (menuToggle) {
                menuToggle.classList.remove('active');
            }

            // Reset hamburger animation
            if (menuToggle) {
                const spans = menuToggle.querySelectorAll('span');
                spans.forEach(span => {
                    span.style.transform = 'none';
                    span.style.opacity = '1';
                });
            }
        }

        // Smooth scroll to section (if exists)
        const targetId = link.getAttribute('href');
        if (targetId && targetId !== '#') {
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }
    });
});

// Tab Functionality for Games and Apps
tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        const tab = btn.getAttribute('data-tab');
        const section = btn.closest('section');

        // Remove active class from all tabs in this section
        section.querySelectorAll('.tab-btn').forEach(t => t.classList.remove('active'));

        // Add active class to clicked tab
        btn.classList.add('active');

        // Filter content based on tab
        if (section.classList.contains('featured-games')) {
            filterGames(tab);
        } else if (section.classList.contains('featured-apps')) {
            filterApps(tab);
        }
    });
});

function filterGames(tab) {
    const gameCards = document.querySelectorAll('.game-card');
    gameCards.forEach(card => {
        const category = card.getAttribute('data-category');
        if (tab === 'featured' || category === tab) {
            card.style.display = 'block';
            card.style.animation = 'fadeInUp 0.6s ease forwards';
        } else {
            card.style.display = 'none';
        }
    });
}

function filterApps(tab) {
    const appCards = document.querySelectorAll('.app-card');
    appCards.forEach(card => {
        const category = card.getAttribute('data-category');
        if (tab === 'featured' || category === tab) {
            card.style.display = 'block';
            card.style.animation = 'fadeInUp 0.6s ease forwards';
        } else {
            card.style.display = 'none';
        }
    });
}

// Filter Functionality for Top Downloads
filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        const filter = btn.getAttribute('data-filter');

        // Remove active class from all filter buttons
        filterBtns.forEach(b => b.classList.remove('active'));

        // Add active class to clicked button
        btn.classList.add('active');

        // Filter downloads based on time period
        filterDownloads(filter);
    });
});

function filterDownloads(filter) {
    const downloadItems = document.querySelectorAll('.download-item');

    // Simulate different data for different time periods
    const mockData = {
        week: [{
                name: 'WhatsApp Plus',
                downloads: '125.3k',
                rating: '4.9'
            },
            {
                name: 'Free Fire',
                downloads: '98.7k',
                rating: '4.3'
            },
            {
                name: 'Spotify Premium',
                downloads: '87.2k',
                rating: '4.9'
            },
            {
                name: 'GTA San Andreas',
                downloads: '76.5k',
                rating: '4.8'
            },
            {
                name: 'Instagram Plus',
                downloads: '65.8k',
                rating: '4.5'
            }
        ],
        month: [{
                name: 'Free Fire',
                downloads: '450.2k',
                rating: '4.3'
            },
            {
                name: 'WhatsApp Plus',
                downloads: '380.7k',
                rating: '4.9'
            },
            {
                name: 'PUBG Mobile',
                downloads: '320.1k',
                rating: '4.5'
            },
            {
                name: 'Spotify Premium',
                downloads: '290.5k',
                rating: '4.9'
            },
            {
                name: 'Call of Duty',
                downloads: '250.8k',
                rating: '4.6'
            }
        ],
        all: [{
                name: 'WhatsApp Plus',
                downloads: '2.1M',
                rating: '4.9'
            },
            {
                name: 'Free Fire',
                downloads: '1.8M',
                rating: '4.3'
            },
            {
                name: 'PUBG Mobile',
                downloads: '1.5M',
                rating: '4.5'
            },
            {
                name: 'Spotify Premium',
                downloads: '1.2M',
                rating: '4.9'
            },
            {
                name: 'GTA San Andreas',
                downloads: '980k',
                rating: '4.8'
            }
        ]
    };

    const data = mockData[filter] || mockData.week;

    downloadItems.forEach((item, index) => {
        if (data[index]) {
            const name = item.querySelector('h3');
            const downloads = item.querySelector('.download-stats span:first-child');
            const rating = item.querySelector('.download-stats span:last-child');

            if (name) name.textContent = data[index].name;
            if (downloads) downloads.innerHTML = `<i class="fas fa-download"></i> ${data[index].downloads}`;
            if (rating) rating.innerHTML = `<i class="fas fa-star"></i> ${data[index].rating}`;
        }
    });
}

// Download Button Functionality
downloadBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
        e.preventDefault();

        const card = btn.closest('.app-card, .game-card');
        const name = card.querySelector('.app-name, .game-name').textContent;

        showDownloadModal(name);
    });
});

function showDownloadModal(appName) {
    const modalOverlay = document.createElement('div');
    modalOverlay.className = 'modal-overlay';
    modalOverlay.innerHTML = `
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

    // Add modal styles
    const modalStyles = `
        <style>
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            animation: fadeIn 0.3s ease;
        }
        
        .modal {
            background: white;
            border-radius: 15px;
            padding: 0;
            max-width: 400px;
            width: 90%;
            animation: slideIn 0.3s ease;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .modal-header h3 {
            margin: 0;
            color: #333;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .btn-cancel, .btn-confirm {
            flex: 1;
            padding: 0.75rem 1rem;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-cancel {
            background: #f8f9fa;
            color: #666;
        }
        
        .btn-cancel:hover {
            background: #e9ecef;
        }
        
        .btn-confirm {
            background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
            color: white;
        }
        
        .btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.4);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        </style>
    `;

    document.head.insertAdjacentHTML('beforeend', modalStyles);
    document.body.appendChild(modalOverlay);

    // Handle modal interactions
    const closeBtn = modalOverlay.querySelector('.modal-close');
    const cancelBtn = modalOverlay.querySelector('.btn-cancel');
    const confirmBtn = modalOverlay.querySelector('.btn-confirm');

    const closeModal = () => {
        modalOverlay.remove();
    };

    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);

    confirmBtn.addEventListener('click', () => {
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Downloading...';
        confirmBtn.disabled = true;

        setTimeout(() => {
            alert(`Download started for ${appName}!`);
            closeModal();
        }, 2000);
    });

    // Close on overlay click
    modalOverlay.addEventListener('click', (e) => {
        if (e.target === modalOverlay) {
            closeModal();
        }
    });
}

// Update Button Functionality
updateBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
        e.preventDefault();

        const updateItem = btn.closest('.update-item');
        const appName = updateItem.querySelector('h3').textContent;

        showUpdateModal(appName);
    });
});

function showUpdateModal(appName) {
    const btn = event.target;
    const originalText = btn.textContent;

    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
    btn.disabled = true;

    setTimeout(() => {
        btn.innerHTML = '<i class="fas fa-check"></i> Updated';
        btn.style.background = '#10b981';

        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            btn.style.background = '';
        }, 2000);
    }, 3000);
}

// Info Button Functionality
infoBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
        e.preventDefault();

        const card = btn.closest('.app-card, .game-card');
        const name = card.querySelector('.app-name, .game-name').textContent;
        const category = card.querySelector('.app-category, .game-category').textContent;
        const rating = card.querySelector('.rating-text').textContent;
        const size = card.querySelector('.app-size, .game-size').textContent;
        const version = card.querySelector('.app-version, .game-version').textContent;

        showAppInfo(name, category, rating, size, version);
    });
});

function showAppInfo(name, category, rating, size, version) {
    const modalOverlay = document.createElement('div');
    modalOverlay.className = 'modal-overlay';
    modalOverlay.innerHTML = `
        <div class="modal">
            <div class="modal-header">
                <h3>App Information</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="app-info-detail">
                    <h4>${name}</h4>
                    <p><strong>Category:</strong> ${category}</p>
                    <p><strong>Rating:</strong> ${rating}</p>
                    <p><strong>Version:</strong> ${version}</p>
                    <p><strong>Size:</strong> ${size}</p>
                    <p><strong>Requirements:</strong> Android 5.0+</p>
                    <div class="app-features">
                        <h5>MOD Features:</h5>
                        <ul>
                            <li>Unlocked all premium features</li>
                            <li>No advertisements</li>
                            <li>Unlimited usage</li>
                            <li>Custom themes</li>
                            <li>Enhanced performance</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-actions">
                    <button class="btn-close">Close</button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modalOverlay);

    // Handle modal interactions
    const closeBtn = modalOverlay.querySelector('.modal-close');
    const closeModalBtn = modalOverlay.querySelector('.btn-close');

    const closeModal = () => {
        modalOverlay.remove();
    };

    closeBtn.addEventListener('click', closeModal);
    closeModalBtn.addEventListener('click', closeModal);

    modalOverlay.addEventListener('click', (e) => {
        if (e.target === modalOverlay) {
            closeModal();
        }
    });
}

// Quick Download Functionality
document.querySelectorAll('.quick-download').forEach(btn => {
    btn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();

        const card = btn.closest('.app-card, .game-card');
        const name = card.querySelector('.app-name, .game-name').textContent;

        // Quick download without modal
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;

        setTimeout(() => {
            btn.innerHTML = '<i class="fas fa-check"></i>';
            btn.style.background = '#10b981';

            setTimeout(() => {
                btn.innerHTML = '<i class="fas fa-download"></i>';
                btn.disabled = false;
                btn.style.background = '';
            }, 2000);
        }, 1500);
    });
});

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const href = this.getAttribute('href');
        if (href && href !== '#') {
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }
    });
});

// Header scroll effect
window.addEventListener('scroll', () => {
    const header = document.querySelector('.header');
    if (header) {
        if (window.scrollY > 100) {
            header.style.background = 'rgba(37, 99, 235, 0.95)';
            header.style.backdropFilter = 'blur(10px)';
        } else {
            header.style.background = 'var(--bg-primary)';
            header.style.backdropFilter = 'none';
        }
    }
});

// Intersection Observer for animations
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

// Search suggestions
const searchSuggestions = [
    'WhatsApp Plus',
    'PUBG Mobile',
    'Spotify Premium',
    'Free Fire',
    'Instagram Plus',
    'TikTok Mod',
    'YouTube Premium',
    'Netflix Mod',
    'GTA San Andreas',
    'Minecraft',
    'Call of Duty',
    'Among Us'
];

if (searchInput) {
    searchInput.addEventListener('input', (e) => {
        const value = e.target.value.toLowerCase();
        if (value.length > 2) {
            const suggestions = searchSuggestions.filter(item =>
                item.toLowerCase().includes(value)
            );

            if (suggestions.length > 0) {
                showSearchSuggestions(suggestions);
            } else {
                hideSearchSuggestions();
            }
        } else {
            hideSearchSuggestions();
        }
    });
}

function showSearchSuggestions(suggestions) {
    let suggestionsContainer = document.getElementById('searchSuggestions');

    if (suggestionsContainer) {
        suggestionsContainer.innerHTML = suggestions.map(suggestion =>
            `<div class="suggestion-item">${suggestion}</div>`
        ).join('');

        suggestionsContainer.style.display = 'block';

        // Add click handlers to suggestions
        suggestionsContainer.querySelectorAll('.suggestion-item').forEach(item => {
            item.addEventListener('click', () => {
                if (searchInput) {
                    searchInput.value = item.textContent;
                }
                hideSearchSuggestions();
                performSearch(item.textContent);
            });
        });
    }
}

function hideSearchSuggestions() {
    const suggestionsContainer = document.getElementById('searchSuggestions');
    if (suggestionsContainer) {
        suggestionsContainer.style.display = 'none';
    }
}

// Category card hover effects
document.querySelectorAll('.category-card').forEach(card => {
    card.addEventListener('mouseenter', () => {
        card.style.transform = 'translateY(-10px) scale(1.02)';
    });

    card.addEventListener('mouseleave', () => {
        card.style.transform = 'translateY(0) scale(1)';
    });
});

// App/Game card hover effects
document.querySelectorAll('.app-card, .game-card').forEach(card => {
    card.addEventListener('mouseenter', () => {
        card.style.transform = 'translateY(-5px) scale(1.02)';
    });

    card.addEventListener('mouseleave', () => {
        card.style.transform = 'translateY(0) scale(1)';
    });
});

// Hero buttons functionality
document.querySelectorAll('.btn-primary, .btn-secondary').forEach(btn => {
    btn.addEventListener('click', (e) => {
        e.preventDefault();

        if (btn.classList.contains('btn-primary')) {
            // Scroll to games section
            const gamesSection = document.querySelector('.featured-games');
            if (gamesSection) {
                gamesSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        } else {
            // Scroll to apps section
            const appsSection = document.querySelector('.featured-apps');
            if (appsSection) {
                appsSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }
    });
});

// APKMody Header Functionality
const searchForm = document.getElementById('search-form');
const searchClose = document.getElementById('search-close');

if (searchOpen) {
    searchOpen.addEventListener('click', () => {
        showSearchForm();
    });
}

if (searchClose) {
    searchClose.addEventListener('click', (e) => {
        // Close when clicked anywhere on the close button (which covers the entire background)
        hideSearchForm();
    });
}

// Close search form when pressing Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        // Close search form if open
        if (searchForm && searchForm.classList.contains('active')) {
            hideSearchForm();
        }

        // Close any open sidenav
        const openSidenavs = document.querySelectorAll('.sidenav.sidenav-open');
        openSidenavs.forEach(sidenav => {
            const target = sidenav.id;
            const overlay = document.querySelector(`.sidenav-overlay[data-target="${target}"]`);
            hideSidenav(sidenav, overlay);
        });
    }
});

// Sidenav Functionality
const sidenavTriggers = document.querySelectorAll('.sidenav-trigger');
const sidenavOverlays = document.querySelectorAll('.sidenav-overlay');


sidenavTriggers.forEach(trigger => {
    trigger.addEventListener('click', (e) => {
        e.preventDefault();
        const target = trigger.getAttribute('data-target');
        const sidenav = document.getElementById(target);
        const overlay = document.querySelector(`.sidenav-overlay[data-target="${target}"]`);


        if (sidenav) {
            showSidenav(sidenav, overlay);
        }
    });
});

sidenavOverlays.forEach(overlay => {
    overlay.addEventListener('click', (e) => {
        const target = overlay.getAttribute('data-target');
        const sidenav = document.getElementById(target);
        hideSidenav(sidenav, overlay);
    });
});

function showSidenav(sidenav, overlay) {
    if (sidenav) {
        sidenav.classList.add('sidenav-open');

        if (overlay) {
            overlay.classList.add('sidenav-overlay-open');
        }

        // Prevent body scroll
        document.body.style.overflow = 'hidden';
    }
}

function hideSidenav(sidenav, overlay) {
    if (sidenav) {
        sidenav.classList.remove('sidenav-open');

        if (overlay) {
            overlay.classList.remove('sidenav-overlay-open');
        }

        // Restore body scroll
        document.body.style.overflow = '';
    }
}

function showSearchForm() {
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
    if (searchForm) {
        searchForm.classList.remove('active');
    }
}

// Menu item active state
clickableLinks.forEach(link => {
    link.addEventListener('click', (e) => {
        // Remove active class from all menu items
        menuItems.forEach(item => item.classList.remove('menu-item__active'));

        // Add active class to clicked menu item
        const menuItem = link.querySelector('.menu-item');
        if (menuItem) {
            menuItem.classList.add('menu-item__active');
        }
    });
});

// Hero slider functionality
let currentSlide = 0;
const slides = document.querySelectorAll('.hero-slide');
const dots = document.querySelectorAll('.dot');
let slideInterval;

function showSlide(index) {
    slides.forEach((slide, i) => {
        slide.classList.toggle('active', i === index);
    });

    dots.forEach((dot, i) => {
        dot.classList.toggle('active', i === index);
    });
}

function nextSlide() {
    currentSlide = (currentSlide + 1) % slides.length;
    showSlide(currentSlide);
}

function prevSlide() {
    currentSlide = (currentSlide - 1 + slides.length) % slides.length;
    showSlide(currentSlide);
}

function startAutoSlide() {
    slideInterval = setInterval(nextSlide, 5000);
}

function stopAutoSlide() {
    clearInterval(slideInterval);
}

function initializeHeroSlider() {
    if (slides.length > 0) {
        showSlide(0);
        startAutoSlide();

        // Add click handlers to dots
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                currentSlide = index;
                showSlide(currentSlide);
                stopAutoSlide();
                startAutoSlide();
            });
        });

        // Pause on hover
        const heroSlider = document.querySelector('.hero-slider');
        if (heroSlider) {
            heroSlider.addEventListener('mouseenter', stopAutoSlide);
            heroSlider.addEventListener('mouseleave', startAutoSlide);
        }
    }
}


// Go Back Functionality
function initGoBack() {
    const goBackBtn = document.getElementById('go-back');
    if (goBackBtn) {
        goBackBtn.addEventListener('click', function(e) {
            e.preventDefault();

            // Check if there's a previous page in history
            if (window.history.length > 1) {
                window.history.back();
            } else {
                // If no history, redirect to home page or a default page
                window.location.href = '/';
            }
        });
    }
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded, script is running!');
    // Initialize hero slider
    initializeHeroSlider();

    // Initialize default tab states
    if (typeof filterGames === 'function') filterGames('featured');
    if (typeof filterApps === 'function') filterApps('featured');
    if (typeof filterDownloads === 'function') filterDownloads('week');

    // Top nav toggle removed - no scroll effects needed

    // Share button removed - no longer needed

    // Initialize unfold table
    initUnfoldTable();

    // Initialize table of contents with delay
    setTimeout(() => {

        initTableOfContents();
    }, 100);

    // Initialize go back functionality
    initGoBack();
});


// Unfold Table Functionality
function initUnfoldTable() {
    const unfoldButton = document.getElementById('unfold-table');
    const collapseRows = document.querySelectorAll('.collapse-row');

    if (!unfoldButton || collapseRows.length === 0) return;

    // Remove any existing inline styles and active classes
    collapseRows.forEach(row => {
        row.removeAttribute('style');
        row.classList.remove('active');
    });

    let isUnfolded = false;

    unfoldButton.addEventListener('click', function() {
        isUnfolded = !isUnfolded;

        collapseRows.forEach(row => {
            if (isUnfolded) {
                row.classList.add('active');
            } else {
                row.classList.remove('active');
            }
        });

        // Update button text/icon if needed
        const svgIcon = unfoldButton.querySelector('.svg-icon svg path');
        if (svgIcon) {
            if (isUnfolded) {
                // Change to "show less" icon (up arrow)
                svgIcon.setAttribute('d', 'M480-544.23 280-344.23q-8.92 8.92-21.19 8.8-12.27-.11-21.58-9.42-8.69-9.31-8.38-21.38.3-12.08 9-20.77l221.77-221.77q9.31-9.31 21.38-9.31 12.08 0 21.38 9.31L703.77-395.42q8.69 8.69 9 20.77.3 12.08-8.38 21.38-9.31 9.31-21.38 9.31-12.08 0-21.38-9.31L480-544.23Z');
            } else {
                // Change to "show more" icon (down arrow)
                svgIcon.setAttribute('d', 'M249.23-420q-24.75 0-42.37-17.63-17.63-17.62-17.63-42.37 0-24.75 17.63-42.37Q224.48-540 249.23-540q24.75 0 42.38 17.63 17.62 17.62 17.62 42.37 0 24.75-17.62 42.37Q273.98-420 249.23-420ZM480-420q-24.75 0-42.37-17.63Q420-455.25 420-480q0-24.75 17.63-42.37Q455.25-540 480-540q24.75 0 42.37 17.63Q540-504.75 540-480q0 24.75-17.63 42.37Q504.75-420 480-420Zm230.77 0q-24.75 0-42.38-17.63-17.62-17.62-17.62-42.37 0-24.75 17.62-42.37Q686.02-540 710.77-540q24.75 0 42.37 17.63 17.63 17.62 17.63 42.37 0 24.75-17.63 42.37Q735.52-420 710.77-420Z');
            }
        }
    });
}


// Table of Contents Functionality
function initTableOfContents() {

    const tocTrigger = document.getElementById('toc-trigger');
    const tableOfContent = document.getElementById('table-of-content');



    if (!tocTrigger || !tableOfContent) {

        return;
    }

    // Generate TOC from headings

    generateTableOfContents();

    // Toggle TOC visibility
    tocTrigger.addEventListener('click', function() {

        const isOpen = tableOfContent.hasAttribute('open');

        if (isOpen) {
            tableOfContent.removeAttribute('open');
            tocTrigger.textContent = 'Show Contents';
        } else {
            tableOfContent.setAttribute('open', '');
            tocTrigger.textContent = 'Hide Contents';
        }
    });
}

// Generate table of contents from headings
function generateTableOfContents() {
    console.log('Starting TOC generation...');

    const tableOfContent = document.getElementById('table-of-content');
    if (!tableOfContent) {
        console.log('TOC container not found');
        return;
    }

    // Try multiple selectors to find content - prioritize main content area
    let content = document.querySelector('.entry-block.entry-content.main-entry-content');
    if (!content) {
        content = document.querySelector('.entry-content');
    }
    if (!content) {
        content = document.querySelector('.main-entry-content');
    }
    if (!content) {
        content = document.querySelector('.entry-block');
    }

    if (!content) {
        console.log('Content container not found');
        return;
    }

    console.log('Found content container:', content.className);

    const headings = content.querySelectorAll('h1, h2, h3, h4, h5, h6');
    console.log('Found headings:', headings.length);

    if (headings.length === 0) {
        console.log('No headings found in content');
        return;
    }

    const tocList = tableOfContent.querySelector('ul');
    if (!tocList) {
        console.log('TOC list not found');
        return;
    }

    // Clear existing content
    tocList.innerHTML = '';

    let addedCount = 0;
    let headingNumbers = [0, 0, 0, 0, 0, 0]; // Track numbers for each level

    headings.forEach((heading, index) => {
        // Skip headings in recommended section
        if (heading.closest('.recommended-section, .recommended-for-you, .related-posts')) {
            console.log('Skipping heading in recommended section:', heading.textContent);
            return;
        }

        // Create ID if not exists
        if (!heading.id) {
            heading.id = `heading-${index}`;
        }

        // Create list item
        const li = document.createElement('li');
        const level = parseInt(heading.tagName.charAt(1));

        // Increment number for current level and reset deeper levels
        headingNumbers[level - 1]++;
        for (let i = level; i < 6; i++) {
            headingNumbers[i] = 0;
        }

        // Generate number string (e.g., "1.2.3")
        let numberString = '';
        for (let i = 0; i < level; i++) {
            if (headingNumbers[i] > 0) {
                if (numberString) numberString += '.';
                numberString += headingNumbers[i];
            }
        }

        // Add indentation based on heading level
        li.style.marginLeft = `${(level - 1) * 20}px`;

        // Create link with number
        const a = document.createElement('a');
        a.href = `#${heading.id}`;
        a.innerHTML = `<span class="toc-number">${numberString}.</span> ${heading.textContent.trim()}`;
        a.setAttribute('aria-label', `Jump to ${heading.textContent.trim()}`);

        // Add click handler for smooth scroll with header offset
        a.addEventListener('click', function(e) {
            e.preventDefault();
            const headerHeight = 50;
            const elementPosition = heading.getBoundingClientRect().top;
            const offsetPosition = elementPosition + window.pageYOffset - headerHeight;

            window.scrollTo({
                top: offsetPosition,
                behavior: 'smooth'
            });
        });

        li.appendChild(a);
        tocList.appendChild(li);
        addedCount++;

        console.log('Added heading:', heading.textContent.trim(), 'Level:', level, 'Number:', numberString);
    });

    console.log('TOC generated with', addedCount, 'items');
}