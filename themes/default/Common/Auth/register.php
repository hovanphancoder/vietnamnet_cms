<?php

namespace System\Libraries;

use App\Libraries\Fastlang;

Render::block('Backend\Head', ['layout' => 'default', 'title' => Fastlang::_e('register_to_cms')]);
?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50">
    <div class="container relative h-screen flex-col items-center justify-center md:grid lg:max-w-none lg:grid-cols-2 lg:px-0">

        <!-- Left Panel - Branding -->
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
                    <span class="text-xl font-bold"><?php __e('cms_name') ?></span>
                </a>
            </div>

            <!-- Main content -->
            <div class="relative z-20 flex-1 flex flex-col justify-center">
                <div class="space-y-8">
                    <!-- Logo -->
                    <div class="text-center">
                        <?= _img(
                            theme_assets('images/logo/logo-icon.png'),
                            'Logo CMS',
                            false,
                            'mx-auto mb-6 h-32 w-32 object-contain drop-shadow-lg'
                        ) ?>
                    </div>

                    <!-- Description -->
                    <div class="text-center">
                        <h1 class="text-3xl font-bold text-white mb-4 leading-tight">
                            Welcome to Modern CMS
                        </h1>
                        <p class="text-xl text-blue-100 leading-relaxed max-w-md mx-auto">
                            <?php __e('cms_description') ?>
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
                    <h3 class="font-semibold text-white text-sm mb-1"><?php __e('outstanding_features_title') ?></h3>
                    <p class="text-xs text-center text-blue-100 leading-relaxed"><?php __e('outstanding_features_description') ?></p>
                </div>

                <div class="group flex flex-col items-center p-4 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/20 hover:bg-white/15 transition-all duration-300">
                    <div class="w-12 h-12 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-white">
                            <path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-white text-sm mb-1"><?php __e('optimized_speed_title') ?></h3>
                    <p class="text-xs text-center text-blue-100 leading-relaxed"><?php __e('optimized_speed_description') ?></p>
                </div>

                <div class="group flex flex-col items-center p-4 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/20 hover:bg-white/15 transition-all duration-300">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-white">
                            <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path>
                            <path d="m9 12 2 2 4-4"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-white text-sm mb-1"><?php __e('premium_security_title') ?></h3>
                    <p class="text-xs text-center text-blue-100 leading-relaxed"><?php __e('premium_security_description') ?></p>
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
                    <h3 class="font-semibold text-white text-sm mb-1"><?php __e('intuitive_interface_title') ?></h3>
                    <p class="text-xs text-center text-blue-100 leading-relaxed"><?php __e('intuitive_interface_description') ?></p>
                </div>
            </div>
        </div>

        <!-- Right Panel - Login Form -->
        <div class="flex items-center h-full justify-center p-8 bg-white/80 backdrop-blur-sm">
            <div class="w-full max-w-sm space-y-8">
                <!-- Header -->
                <div class="text-center space-y-2">
                    <h2 class="text-3xl font-bold text-gray-900"><?php __e('create_admin_account') ?></h2>
                    <p class="text-gray-600 text-sm">
                        <?php __e('or') ?>
                        <a class="font-semibold text-blue-600 hover:text-blue-500 transition-colors" href="<?= auth_url('login') ?>">
                            <?php __e('login_if_account_exists') ?>
                        </a>
                    </p>
                </div>

                <!-- Google Registration -->
                <button class="w-full flex items-center justify-center gap-3 px-4 py-3 border-2 border-gray-200 rounded-2xl bg-white hover:bg-gray-50 hover:border-blue-300 hover:shadow-lg transition-all duration-300 group" type="button">
                    <svg class="w-5 h-5" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"></path>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"></path>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"></path>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"></path>
                    </svg>
                    <span class="font-semibold text-gray-700 group-hover:text-gray-900 transition-colors">
                        <?php __e('register_with_google') ?>
                    </span>
                </button>

                <!-- Divider -->
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-200"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-white text-gray-500 font-medium"><?php __e('or_continue_with') ?></span>
                    </div>
                </div>

                <!-- Registration Form -->
                <form class="space-y-4">
                    <!-- Full Name -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400">
                                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                        <input
                            type="text"
                            id="fullname"
                            name="fullname"
                            class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-2xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 placeholder:text-gray-400 text-sm font-medium"
                            placeholder="<?php __e('full_name_placeholder') ?>"
                            required>
                    </div>

                    <!-- Username -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400">
                                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-2xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 placeholder:text-gray-400 text-sm font-medium"
                            placeholder="<?php __e('username_placeholder') ?>"
                            required>
                    </div>

                    <!-- Email -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400">
                                <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                            </svg>
                        </div>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-2xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 placeholder:text-gray-400 text-sm font-medium"
                            placeholder="<?php __e('email_address_placeholder') ?>"
                            required>
                    </div>

                    <!-- Password -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400">
                                <rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                        </div>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-2xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 placeholder:text-gray-400 text-sm font-medium"
                            placeholder="<?php __e('password_placeholder') ?>"
                            required>
                    </div>

                    <!-- Confirm Password -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400">
                                <rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                        </div>
                        <input
                            type="password"
                            id="confirmPassword"
                            name="confirmPassword"
                            class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-2xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 placeholder:text-gray-400 text-sm font-medium"
                            placeholder="<?php __e('confirm_password_placeholder') ?>"
                            required>
                    </div>

                    <!-- Terms Checkbox -->
                    <div class="flex items-start space-x-3">
                        <div class="relative flex items-center h-5 mt-0.5">
                            <input
                                type="checkbox"
                                id="terms"
                                name="terms"
                                class="peer sr-only"
                                required />
                            <label for="terms" class="relative flex items-center justify-center w-5 h-5 border-2 border-gray-300 rounded-lg bg-white cursor-pointer transition-all duration-300 hover:border-blue-400 peer-checked:bg-blue-600 peer-checked:border-blue-600 peer-focus:ring-4 peer-focus:ring-blue-500/20">
                                <svg class="w-3 h-3 text-white opacity-0 peer-checked:opacity-100 transition-all duration-300 transform scale-0 peer-checked:scale-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            </label>
                        </div>
                        <label for="terms" class="text-sm text-gray-700 leading-5 cursor-pointer font-medium">
                            <?php __e('terms_and_privacy_agreement') ?>
                        </label>
                    </div>

                    <!-- Register Button -->
                    <button
                        type="button"
                        id="register"
                        class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold rounded-2xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <line x1="19" x2="19" y1="8" y2="14"></line>
                            <line x1="22" x2="16" y1="11" y2="11"></line>
                        </svg>
                        <?php __e('register_button') ?>
                    </button>
                </form>

                <!-- Language Selector -->
                <!-- <div class="relative" id="languageDropdown">
                    <button
                        type="button"
                        id="dropdownButton"
                        class="w-full flex items-center justify-between px-4 py-3 border border-gray-200 rounded-2xl bg-white hover:bg-gray-50 hover:border-blue-300 transition-all duration-300 cursor-pointer">
                        <span id="selectedLanguageDisplay" class="flex items-center space-x-2">
                            <span class="text-lg">🇻🇳</span>
                            <span class="text-gray-700 font-medium"><?php __e('vietnamese_language') ?></span>
                        </span>
                        <svg id="chevronIcon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400 transition-transform duration-200">
                            <path d="m6 9 6 6 6-6"></path>
                        </svg>
                    </button>
                    
                    <div id="language-menu" class="absolute top-full left-0 right-0 mt-2 bg-white border border-gray-200 rounded-2xl shadow-2xl z-50 max-h-60 overflow-auto opacity-0 invisible transform scale-95 transition-all duration-200">
                        <div class="py-2">
                            <?php
                            $languages = [
                                ['code' => 'vi', 'name' => 'Vietnamese', 'flag' => '🇻🇳', 'selected' => true],
                                ['code' => 'en', 'name' => 'English', 'flag' => '🇺🇸', 'selected' => false],
                                ['code' => 'zh', 'name' => '中文', 'flag' => '🇨🇳', 'selected' => false],
                                ['code' => 'ja', 'name' => '日本語', 'flag' => '🇯🇵', 'selected' => false],
                                ['code' => 'ko', 'name' => '한국어', 'flag' => '🇰🇷', 'selected' => false],
                                ['code' => 'fr', 'name' => 'Français', 'flag' => '🇫🇷', 'selected' => false],
                                ['code' => 'de', 'name' => 'Deutsch', 'flag' => '🇩🇪', 'selected' => false],
                                ['code' => 'es', 'name' => 'Español', 'flag' => '🇪🇸', 'selected' => false]
                            ];

                            foreach ($languages as $language):
                            ?>
                                <div class="language-option flex items-center justify-between px-4 py-3 cursor-pointer hover:bg-blue-50 transition-colors font-medium <?= $language['selected'] ? 'bg-blue-50 text-blue-600' : 'text-gray-700' ?>"
                                     data-code="<?= $language['code'] ?>"
                                     data-name="<?= htmlspecialchars($language['name']) ?>"
                                     data-flag="<?= $language['flag'] ?>"
                                     data-selected="<?= $language['selected'] ? 'true' : 'false' ?>">
                                    <div class="flex items-center space-x-3">
                                        <span class="text-lg"><?= $language['flag'] ?></span>
                                        <span><?= htmlspecialchars($language['name']) ?></span>
                                    </div>
                                    <?php if ($language['selected']): ?>
                                        <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div> -->
            </div>
        </div>
    </div>
</div>


<?php
Render::block('Backend\Footer', ['layout' => 'default']);
?>
