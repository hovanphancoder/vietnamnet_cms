<div class="relative hidden h-full flex-col p-10 pb-24 text-slate-800 lg:flex overflow-hidden">
            <!-- Modern gradient background -->
            <div class="absolute inset-0 bg-gradient-to-br from-blue-500 via-indigo-500 to-purple-600"></div>

            <!-- Decorative elements -->
            <div class="absolute top-20 left-10 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
            <div class="absolute bottom-20 right-10 w-24 h-24 bg-white/10 rounded-full blur-xl"></div>
            <div class="absolute top-1/2 right-20 w-16 h-16 bg-white/5 rounded-full blur-lg"></div>

            <!-- Header -->
            <div class="relative z-20 flex items-center text-lg font-semibold text-white">
                <a class="flex items-center gap-3 hover:opacity-80 transition-opacity" href="/">
                    <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-white">
                            <rect width="7" height="9" x="3" y="3" rx="1"></rect>
                            <rect width="7" height="5" x="14" y="3" rx="1"></rect>
                            <rect width="7" height="9" x="14" y="12" rx="1"></rect>
                            <rect width="7" height="5" x="3" y="16" rx="1"></rect>
                        </svg>
                    </div>
                    <span class="text-xl font-bold"><?php __e('CMS Full Form') ?></span>
                </a>
            </div>

            <!-- Main content -->
            <div class="relative z-20 flex-1 flex flex-col justify-center">
                <div class="space-y-8">
                    <!-- Logo -->
                    <div class="text-center">
                        <?= _img(
                            theme_assets('images/logo/logo.png'),
                            'Logo CMS',
                            false,
                            'mx-auto mb-6 h-32 w-32 object-contain drop-shadow-lg'
                        ) ?>
                    </div>

                    <!-- Description -->
                    <div class="text-center">
                        <h1 class="text-3xl font-bold text-white mb-4 leading-tight">
                            <?php __e('Welcome to') ?> <?= option('site_brand') ?>
                        </h1>
                        <p class="text-xl text-blue-100 leading-relaxed max-w-md mx-auto">
                            <?= option('site_desc') ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Feature cards -->
            <div class="relative z-20 grid grid-cols-2 gap-4">
                <div class="group flex flex-col items-center p-4 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/20 hover:bg-white/15 transition-all duration-300">
                    <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-white">
                            <path d="M6 3h12l4 6-10 13L2 9Z"></path>
                            <path d="M11 3 8 9l4 13 4-13-3-6"></path>
                            <path d="M2 9h20"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-white text-sm mb-1"><?php __e('Outstanding Features Title') ?></h3>
                    <p class="text-xs text-center text-blue-100 leading-relaxed"><?php __e('Outstanding Features Description') ?></p>
                </div>

                <div class="group flex flex-col items-center p-4 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/20 hover:bg-white/15 transition-all duration-300">
                    <div class="w-12 h-12 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-white">
                            <path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-white text-sm mb-1"><?php __e('Optimized Speed Title') ?></h3>
                    <p class="text-xs text-center text-blue-100 leading-relaxed"><?php __e('Optimized Speed Description') ?></p>
                </div>

                <div class="group flex flex-col items-center p-4 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/20 hover:bg-white/15 transition-all duration-300">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-white">
                            <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path>
                            <path d="m9 12 2 2 4-4"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-white text-sm mb-1"><?php __e('Premium Security Title') ?></h3>
                    <p class="text-xs text-center text-blue-100 leading-relaxed"><?php __e('Premium Security Description') ?></p>
                </div>

                <div class="group flex flex-col items-center p-4 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/20 hover:bg-white/15 transition-all duration-300">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-400 to-pink-500 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-white">
                            <rect width="7" height="9" x="3" y="3" rx="1"></rect>
                            <rect width="7" height="5" x="14" y="3" rx="1"></rect>
                            <rect width="7" height="9" x="14" y="12" rx="1"></rect>
                            <rect width="7" height="5" x="3" y="16" rx="1"></rect>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-white text-sm mb-1"><?php __e('Intuitive Interface Title') ?></h3>
                    <p class="text-xs text-center text-blue-100 leading-relaxed"><?php __e('Intuitive Interface Description') ?></p>
                </div>
            </div>
        </div>