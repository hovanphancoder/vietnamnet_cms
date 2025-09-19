<section class="top-nav liquid-glass">
    <div class="container">
        <div class="top-nav__content">
            <!-- Back Button -->
            <div style="text-align: start;">
                <a id="go-back" href="javascript:history.back()" class="clickable" aria-label="Go Back">
                    <span class="svg-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 -960 960 960">
                            <path d="m127.38-480 301.31 301.31q11.92 11.92 11.62 28.07-.31 16.16-12.23 28.08-11.93 11.92-28.08 11.92t-28.08-11.92L65.08-428.77Q54.23-439.62 49-453.08q-5.23-13.46-5.23-26.92T49-506.92q5.23-13.46 16.08-24.31l306.84-306.85q11.93-11.92 28.39-11.61 16.46.31 28.38 12.23 11.92 11.92 11.92 28.08 0 16.15-11.92 28.07L127.38-480Z"></path>
                        </svg>
                    </span>
                </a>
            </div>
            
            <!-- Page Title -->
            <div>
                <span class="top-nav__title"><?= get_page_heading() ?></span>
            </div>
            
            <!-- Menu Button -->
            <div style="text-align: end;">
                <button class="clickable sidenav-trigger" type="button" aria-label="Categories" data-target="menu-app">
                    <span class="svg-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="24" height="24">
                            <circle cx="4" cy="6" r="2" fill="#000000"></circle>
                            <rect x="8" y="5" width="12" height="2" rx="1" fill="#000000"></rect>
                            <circle cx="4" cy="12" r="2" fill="#000000"></circle>
                            <rect x="8" y="11" width="12" height="2" rx="1" fill="#000000"></rect>
                            <circle cx="4" cy="18" r="2" fill="#000000"></circle>
                            <rect x="8" y="17" width="12" height="2" rx="1" fill="#000000"></rect>
                        </svg>
                    </span>
                </button>
            </div>
        </div>
    </div>
</section>