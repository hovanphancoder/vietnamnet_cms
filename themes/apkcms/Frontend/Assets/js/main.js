// Vietnamnet Clone - Main JavaScript File
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuBtn = document.querySelector('.btn-hamburger');
    const mobileMenu = document.querySelector('.mainHamburger');
    const closeMenuBtn = document.querySelector('.btn-hamburger-close');
    const iconBar = document.querySelector('.icon-bar');
    const iconClose = document.querySelector('.icon-close');

    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
            iconBar.classList.toggle('hidden');
            iconClose.classList.toggle('hidden');
        });
    }

    if (closeMenuBtn && mobileMenu) {
        closeMenuBtn.addEventListener('click', function() {
            mobileMenu.classList.add('hidden');
            iconBar.classList.remove('hidden');
            iconClose.classList.add('hidden');
        });
    }

    // Search functionality
    const searchInput = document.querySelector('input[name="q"]');
    const searchBtn = document.querySelector('.btn-search');

    if (searchBtn && searchInput) {
        searchBtn.addEventListener('click', function() {
            const searchTerm = searchInput.value.trim();
            if (searchTerm) {
                performSearch(searchTerm);
            }
        });

        // Search on Enter key
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const searchTerm = this.value.trim();
                if (searchTerm) {
                    performSearch(searchTerm);
                }
            }
        });
    }

    // Load more button functionality
    const loadMoreBtn = document.querySelector('.load-more-btn');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            loadMoreContent();
        });
    }

    // News card hover effects - removed zoom and shadow effects
    const newsCards = document.querySelectorAll('.news-card');
    // No hover effects for clean design

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Lazy loading for images
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                observer.unobserve(img);
            }
        });
    });

    images.forEach(img => imageObserver.observe(img));

    // Top stories counter animation
    animateCounters();

    // Initialize tooltips
    initializeTooltips();

    // Search dropdown toggle (common functionality)
    initializeSearchDropdown();

    // Close mobile menu when clicking outside
    document.addEventListener('click', function(event) {
        const popup = document.getElementById('mobileMenuPopup');
        const sidebar = document.getElementById('mobileMenuSidebar');

        if (popup && !popup.classList.contains('hidden') &&
            !sidebar.contains(event.target) &&
            !event.target.closest('button[onclick="toggleMobileMenu()"]')) {
            closeMobileMenu();
        }
    });
});

// Search function
function performSearch(searchTerm) {
    console.log('Searching for:', searchTerm);

    // Show loading state
    const searchBtn = document.querySelector('.search-btn');
    const originalContent = searchBtn.innerHTML;
    searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    searchBtn.disabled = true;

    // Simulate search delay
    setTimeout(() => {
        // Reset button
        searchBtn.innerHTML = originalContent;
        searchBtn.disabled = false;

        // Show search results (in real app, this would make API call)
        showSearchResults(searchTerm);
    }, 1000);
}

// Show search results
function showSearchResults(searchTerm) {
    // Create search results modal or update content
    const searchResults = document.createElement('div');
    searchResults.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    searchResults.innerHTML = `
        <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-96 overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Search Results for "${searchTerm}"</h3>
                <button class="close-search text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="space-y-4">
                <div class="border-b pb-2">
                    <h4 class="font-semibold text-gray-900">Modern admission system mustn't tolerate 'virtual filtering'</h4>
                    <p class="text-gray-600 text-sm">Hoang Ngoc Vinh, PhD, believes that while the university admission process...</p>
                </div>
                <div class="border-b pb-2">
                    <h4 class="font-semibold text-gray-900">Households installing solar rooftop systems without registration</h4>
                    <p class="text-gray-600 text-sm">New regulations require registration for solar installations...</p>
                </div>
                <div class="border-b pb-2">
                    <h4 class="font-semibold text-gray-900">Parents welcome changes: no Saturday classes</h4>
                    <p class="text-gray-600 text-sm">Educational reforms bring relief to families...</p>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(searchResults);

    // Close search results
    searchResults.querySelector('.close-search').addEventListener('click', () => {
        document.body.removeChild(searchResults);
    });

    // Close on outside click
    searchResults.addEventListener('click', (e) => {
        if (e.target === searchResults) {
            document.body.removeChild(searchResults);
        }
    });
}

// Load more content
function loadMoreContent() {
    const loadMoreBtn = document.querySelector('.load-more-btn');
    const originalContent = loadMoreBtn.innerHTML;

    // Show loading state
    loadMoreBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Loading...';
    loadMoreBtn.disabled = true;

    // Simulate loading delay
    setTimeout(() => {
        // Add new content
        addNewContent();

        // Reset button
        loadMoreBtn.innerHTML = originalContent;
        loadMoreBtn.disabled = false;
    }, 2000);
}

// Add new content to the page
function addNewContent() {
    const newsGrid = document.querySelector('.news-grid');
    const newArticles = [{
            title: "Vietnam's aviation goes green: A costly journey for a sustainable future",
            category: "Vietnamnet global",
            time: "6 hours ago",
            image: "https://via.placeholder.com/400x250/667eea/ffffff?text=Aviation+Green"
        },
        {
            title: "Vietnam's central bank confirms credit data incident at CIC",
            category: "Vietnamnet global",
            time: "7 hours ago",
            image: "https://via.placeholder.com/400x250/764ba2/ffffff?text=Banking+Security"
        },
        {
            title: "Vietnam's soft power rises with national cultural branding",
            category: "Vietnamnet global",
            time: "8 hours ago",
            image: "https://via.placeholder.com/400x250/667eea/ffffff?text=Cultural+Branding"
        },
        {
            title: "Hanoi's mosque revival tells a story of faith, culture, and quiet determination",
            category: "Vietnamnet global",
            time: "9 hours ago",
            image: "https://via.placeholder.com/400x250/764ba2/ffffff?text=Mosque+Revival"
        }
    ];

    newArticles.forEach(article => {
        const articleElement = document.createElement('article');
        articleElement.className = 'bg-white rounded-lg shadow-md overflow-hidden news-card';
        articleElement.innerHTML = `
            <img src="${article.image}" alt="${article.title}" class="w-full h-48 object-cover">
            <div class="p-4">
                <div class="text-red-600 text-sm font-medium mb-2">${article.category}</div>
                <h3 class="font-bold text-gray-900 mb-2 line-clamp-2">${article.title}</h3>
                <div class="flex items-center text-sm text-gray-500">
                    <span>${article.time}</span>
                </div>
            </div>
        `;

        newsGrid.appendChild(articleElement);
    });

    // Re-attach hover effects to new cards - removed zoom and shadow effects
    const newCards = newsGrid.querySelectorAll('.news-card');
    // No hover effects for clean design
}

// Animate counters
function animateCounters() {
    const counters = document.querySelectorAll('.counter');
    counters.forEach(counter => {
        const target = parseInt(counter.dataset.target);
        const duration = 2000;
        const increment = target / (duration / 16);
        let current = 0;

        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            counter.textContent = Math.floor(current);
        }, 16);
    });
}

// Initialize tooltips
function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

// Show tooltip
function showTooltip(e) {
    const tooltip = document.createElement('div');
    tooltip.className = 'absolute bg-gray-800 text-white text-sm px-2 py-1 rounded shadow-lg z-50';
    tooltip.textContent = e.target.dataset.tooltip;
    tooltip.style.top = e.target.offsetTop - 30 + 'px';
    tooltip.style.left = e.target.offsetLeft + 'px';

    e.target.style.position = 'relative';
    e.target.appendChild(tooltip);
}

// Hide tooltip
function hideTooltip(e) {
    const tooltip = e.target.querySelector('.absolute');
    if (tooltip) {
        tooltip.remove();
    }
}

// Utility function to debounce
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Scroll to top functionality
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Add scroll to top button
function addScrollToTopButton() {
    const scrollBtn = document.createElement('button');
    scrollBtn.className = 'fixed bottom-4 right-4 bg-red-600 text-white p-3 rounded-full hover:bg-red-700 z-40';
    scrollBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
    scrollBtn.addEventListener('click', scrollToTop);

    document.body.appendChild(scrollBtn);
}

// Initialize scroll to top button
addScrollToTopButton();

// Mobile Menu Toggle Function
function toggleMobileMenu() {
    const popup = document.getElementById('mobileMenuPopup');
    const sidebar = document.getElementById('mobileMenuSidebar');

    if (popup && sidebar) {
        popup.classList.remove('hidden');
        // Trigger animation after a small delay
        setTimeout(() => {
            sidebar.classList.remove('-translate-x-full');
        }, 10);
    }
}

// Close Mobile Menu Function
function closeMobileMenu() {
    const popup = document.getElementById('mobileMenuPopup');
    const sidebar = document.getElementById('mobileMenuSidebar');

    if (popup && sidebar) {
        sidebar.classList.add('-translate-x-full');
        // Hide popup after animation completes
        setTimeout(() => {
            popup.classList.add('hidden');
        }, 300);
    }
}

// Search functionality
function initializeSearchDropdown() {
    // Mobile dropdown functionality
    const searchToggle = document.getElementById('searchToggle');
    const searchDropdown = document.getElementById('searchDropdown');

    if (searchToggle && searchDropdown) {
        searchToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            searchDropdown.classList.toggle('hidden');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchToggle.contains(e.target) && !searchDropdown.contains(e.target)) {
                searchDropdown.classList.add('hidden');
            }
        });
    }

    // Desktop expandable input functionality
    const searchToggleDesktop = document.getElementById('searchToggleDesktop');
    const searchInput = document.getElementById('searchInput');

    if (searchToggleDesktop && searchInput) {
        let isExpanded = false;

        searchToggleDesktop.addEventListener('click', function(e) {
            e.preventDefault();

            if (!isExpanded) {
                // Expand input
                searchInput.classList.remove('w-0', 'px-0');
                searchInput.classList.add('w-60', 'px-4');
                searchInput.focus();
                isExpanded = true;
            } else {
                // Collapse input
                searchInput.classList.remove('w-60', 'px-4');
                searchInput.classList.add('w-0', 'px-0');
                searchInput.value = '';
                isExpanded = false;
            }
        });

        // Collapse when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchToggleDesktop.contains(e.target) && !searchInput.contains(e.target)) {
                if (isExpanded) {
                    searchInput.classList.remove('w-60', 'px-4');
                    searchInput.classList.add('w-0', 'px-0');
                    searchInput.value = '';
                    isExpanded = false;
                }
            }
        });

        // Submit form on Enter key
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                if (searchInput.value.trim()) {
                    searchInput.closest('form').submit();
                }
            }
        });
    }
}

// News Register Page Specific JavaScript (only run on news_register.html)
if (window.location.pathname.includes('news_register.html')) {
    document.addEventListener('DOMContentLoaded', function() {
        // File upload handler
        const fileInput = document.getElementById('file-input');
        const fileText = document.getElementById('file-text');

        if (fileInput && fileText) {
            fileInput.addEventListener('change', function(e) {
                const files = e.target.files;
                if (files.length > 0) {
                    if (files.length === 1) {
                        fileText.textContent = files[0].name;
                    } else {
                        fileText.textContent = `${files.length} files selected`;
                    }
                    fileText.classList.remove('text-gray-500');
                    fileText.classList.add('text-gray-900', 'font-medium');
                } else {
                    fileText.textContent = 'Chọn file';
                    fileText.classList.remove('text-gray-900', 'font-medium');
                    fileText.classList.add('text-gray-500');
                }
            });
        }

        // Form validation
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                // Get form data
                const formData = new FormData(form);
                const name = formData.get('name');
                const email = formData.get('email');
                const title = formData.get('title');
                const content = formData.get('content');

                // Basic validation
                if (!name || !email || !title || !content) {
                    alert('Vui lòng điền đầy đủ thông tin bắt buộc');
                    return;
                }

                // Email validation
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    alert('Vui lòng nhập email hợp lệ');
                    return;
                }

                // Show success message
                alert('Bài viết đã được gửi thành công! Chúng tôi sẽ liên hệ lại sớm nhất.');
                form.reset();

                // Reset file text
                if (fileText) {
                    fileText.textContent = 'Chọn file';
                    fileText.classList.remove('text-gray-900', 'font-medium');
                    fileText.classList.add('text-gray-500');
                }
            });
        }
    });
}