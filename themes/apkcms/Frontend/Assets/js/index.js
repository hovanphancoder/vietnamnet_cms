// Index Page Specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Image slider functionality
    initializeImageSlider();
});

// Image slider for Advertisement and News section
function initializeImageSlider() {
    const slideContainer = document.querySelector('.slide-container');
    if (!slideContainer) return;

    const slides = slideContainer.querySelectorAll('.slide');
    const totalSlides = slides.length;
    let currentSlide = 0;
    let slideInterval;
    let isDragging = false;
    let startX = 0;

    function showSlide(index) {
        slides.forEach((slide, i) => {
            slide.style.display = i === index ? 'block' : 'none';
        });
    }

    function nextSlide() {
        currentSlide = (currentSlide + 1) % totalSlides;
        showSlide(currentSlide);
    }

    function startSlideShow() {
        slideInterval = setInterval(nextSlide, 5000); // Auto-advance every 5 seconds
    }

    function stopSlideShow() {
        clearInterval(slideInterval);
    }

    // Initialize first slide
    showSlide(0);
    startSlideShow();

    // Mouse events for desktop
    slideContainer.addEventListener('mousedown', (e) => {
        startX = e.clientX;
        isDragging = true;
        stopSlideShow();
    });

    slideContainer.addEventListener('mouseup', (e) => {
        if (isDragging) {
            const endX = e.clientX;
            const diffX = startX - endX;

            if (Math.abs(diffX) > 50) {
                if (diffX > 0) {
                    nextSlide();
                } else {
                    currentSlide = currentSlide === 0 ? totalSlides - 1 : currentSlide - 1;
                    showSlide(currentSlide);
                }
            }

            isDragging = false;
            startSlideShow();
        }
    });

    // Touch events for mobile
    slideContainer.addEventListener('touchstart', (e) => {
        startX = e.touches[0].clientX;
        stopSlideShow();
    });

    slideContainer.addEventListener('touchend', (e) => {
        const endX = e.changedTouches[0].clientX;
        const diffX = startX - endX;

        if (Math.abs(diffX) > 50) {
            if (diffX > 0) {
                nextSlide();
            } else {
                currentSlide = currentSlide === 0 ? totalSlides - 1 : currentSlide - 1;
                showSlide(currentSlide);
            }
        }

        startSlideShow();
    });
}