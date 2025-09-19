/**
 * Single Page JavaScript Functions
 * For detail-news.php and detail-post.php
 */


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

// Title Post to Top Nav functionality
function initTitlePostToTopNav() {
    // Kiểm tra xem có element #title-post không
    const titleElement = document.getElementById('title-post');

    // Chỉ chạy JavaScript nếu có #title-post
    if (titleElement) {
        const topNavTitle = document.querySelector('.top-nav__title');

        if (topNavTitle) {
            // Lấy text content và loại bỏ HTML tags
            let titleText = titleElement.textContent || titleElement.innerText;

            // Loại bỏ phần "MOD APK (Menu, Unlimited Money) v1.0.0" để chỉ lấy tên app
            titleText = titleText.replace(/\s+MOD APK.*$/i, '').trim();

            // Đưa text vào top-nav
            topNavTitle.textContent = titleText;
        }
    }
}

// Initialize all single page functions
document.addEventListener('DOMContentLoaded', function() {
    initTableOfContents();
    initTitlePostToTopNav();
});