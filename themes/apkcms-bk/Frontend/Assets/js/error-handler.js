// Error Handler - Fix browser console errors
(function() {
    'use strict';

    // Handle runtime errors
    window.addEventListener('error', function(e) {
        // Ignore extension-related errors
        if (e.message && (
                e.message.includes('Could not establish connection') ||
                e.message.includes('Receiving end does not exist') ||
                e.message.includes('Extension context invalidated')
            )) {
            e.preventDefault();
            return false;
        }
    });

    // Handle unhandled promise rejections
    window.addEventListener('unhandledrejection', function(e) {
        // Ignore extension-related errors
        if (e.reason && (
                e.reason.message && (
                    e.reason.message.includes('Could not establish connection') ||
                    e.reason.message.includes('Receiving end does not exist') ||
                    e.reason.message.includes('Extension context invalidated')
                )
            )) {
            e.preventDefault();
            return false;
        }
    });

    // Override console.error to filter extension errors
    const originalConsoleError = console.error;
    console.error = function(...args) {
        const message = args.join(' ');
        if (message.includes('Could not establish connection') ||
            message.includes('Receiving end does not exist') ||
            message.includes('Extension context invalidated')) {
            return; // Don't log extension errors
        }
        originalConsoleError.apply(console, args);
    };

    // Handle Chrome extension errors
    if (typeof chrome !== 'undefined' && chrome.runtime) {
        chrome.runtime.onMessage.addListener(function(request, sender, sendResponse) {
            // Handle extension messages gracefully
            try {
                if (sendResponse) {
                    sendResponse({
                        status: 'ok'
                    });
                }
            } catch (e) {
                // Ignore extension errors
            }
        });
    }

    console.log('Error handler loaded - Extension errors will be suppressed');
})();