/**
 * Single Page JavaScript Functions
 * For detail-news.php and detail-post.php
 */

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
    initUnfoldTable();
    initTableOfContents();
    initTitlePostToTopNav();
});