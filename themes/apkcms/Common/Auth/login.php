<?php

namespace System\Libraries;

use App\Libraries\Fastlang;

Render::block('Backend\Head', ['layout' => 'default', 'title' => Fastlang::_e('login_to_cms')]);
?>
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50">
    <div class="container relative h-screen flex-col items-center justify-center md:grid lg:max-w-none lg:grid-cols-2 lg:px-0">

        <!-- Left Panel - Branding -->
        <?php echo Render::html('Common/Auth/auth-left'); ?>

        <!-- Right Panel - Login Form -->
        <div class="flex items-center h-full justify-center p-8 bg-white/80 backdrop-blur-sm">
            <div class="w-full max-w-sm space-y-8">
                <!-- Header -->
                <div class="text-center space-y-2">
                    <h2 class="text-3xl font-bold text-slate-900"><?php __e('Login To CMS') ?></h2>
                    <p class="text-slate-600">
                        <?php __e('or') ?>
                        <a class="font-medium text-blue-600 hover:text-blue-500 transition-colors" href="<?php echo auth_url('register'); ?>">
                            <?php __e('Create New Account') ?>
                        </a>
                    </p>
                </div>

                <!-- Error Messages -->
                <?php if (isset($errors) && !empty($errors)): ?>
                    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">
                                    <?php __e('Please Correct Errors'); ?>
                                </h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <ul class="list-disc pl-5 space-y-1">
                                        <?php foreach ($errors as $field => $fieldErrors): ?>
                                            <?php foreach ($fieldErrors as $error): ?>
                                                <li><?php echo htmlspecialchars($error); ?></li>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Success Messages -->
                <?php if (Session::has('success')): ?>
                    <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">
                                    <?php echo Session::get('success'); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Google Login -->
                <a href="<?php echo auth_url('google'); ?>" class="block">
                    <button class="w-full flex items-center justify-center gap-3 px-4 py-3 border-2 border-slate-200 rounded-xl bg-white hover:bg-slate-50 hover:border-slate-300 transition-all duration-300 shadow-sm hover:shadow-md group" type="button">
                        <svg class="w-5 h-5" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"></path>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"></path>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"></path>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"></path>
                        </svg>
                        <span class="font-medium text-slate-700 group-hover:text-slate-900 transition-colors">
                            <?php __e('Login With Google') ?>
                        </span>
                    </button>
                </a>

                <!-- Divider -->
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-slate-200"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-white text-slate-500"><?php __e('Or Login With') ?></span>
                    </div>
                </div>

                <!-- Login Form -->
                <form class="space-y-6" method="POST" action="<?php echo auth_url('login'); ?>">
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <!-- Email Input -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-700" for="username">
                            <?php __e('Email Or Username Placeholder') ?>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400">
                                    <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                                    <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                                </svg>
                            </div>
                            <input
                                type="text"
                                id="username"
                                name="username"
                                class="w-full pl-10 pr-4 py-3 border border-slate-200 rounded-xl bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all duration-300 placeholder:text-slate-400"
                                placeholder="<?php __e('Email Or Username Placeholder') ?>"
                                value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                required>
                        </div>
                        <?php if (isset($errors['username'])): ?>
                            <div class="text-red-500 text-sm mt-1">
                                <?php echo implode(', ', $errors['username']); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Password Input -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-700" for="password">
                            <?php __e('Password Label') ?>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400">
                                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                            </div>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="w-full pl-10 pr-4 py-3 border border-slate-200 rounded-xl bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all duration-300 placeholder:text-slate-400"
                                placeholder="<?php __e('Password Label') ?>"
                                required>
                        </div>
                        <?php if (isset($errors['password'])): ?>
                            <div class="text-red-500 text-sm mt-1">
                                <?php echo implode(', ', $errors['password']); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Remember & Forgot -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <input
                                type="checkbox"
                                id="remember"
                                name="remember"
                                value="on"
                                <?php echo (isset($_POST['remember']) && $_POST['remember'] == 'on') ? 'checked' : ''; ?>
                                class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500 focus:ring-2">
                            <label for="remember" class="text-sm text-slate-700 cursor-pointer">
                                <?php __e('Remember Me') ?>
                            </label>
                        </div>
                        <a class="text-sm font-medium text-blue-600 hover:text-blue-500 transition-colors" href="<?php echo auth_url('forgot'); ?>">
                            <?php __e('Forgot Password') ?>
                        </a>
                    </div>

                    <!-- Login Button -->
                    <button
                        type="submit"
                        class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                            <polyline points="10 17 15 12 10 7"></polyline>
                            <line x1="15" x2="3" y1="12" y2="12"></line>
                        </svg>
                        <?php __e('login') ?>
                    </button>
                </form>

                <!-- Language Selector -->
                <!-- <div class="relative" id="languageDropdown">
                    <button
                        type="button"
                        id="dropdownButton"
                        class="w-full flex items-center justify-between px-4 py-3 border border-slate-200 rounded-xl bg-white hover:bg-slate-50 transition-colors cursor-pointer">
                        <span id="selectedLanguageDisplay" class="flex items-center space-x-2">
                            <span class="text-lg">🇻🇳</span>
                            <span class="text-slate-700"><?= APP_LANG ?></span>
                        </span>
                        <svg id="chevronIcon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400 transition-transform duration-200">
                            <path d="m6 9 6 6 6-6"></path>
                        </svg>
                    </button>

                    <div id="language-menu" class="absolute top-full left-0 right-0 mt-2 bg-white border border-slate-200 rounded-xl shadow-xl z-50 max-h-60 overflow-auto opacity-0 invisible transform scale-95 transition-all duration-200">
                        <div class="py-2">
                            <?php
                            $languages = [
                                ['code' => 'vi', 'name' => 'Vietnamese', 'flag' => '🇻🇳', 'selected' => true],
                                ['code' => 'en', 'name' => 'English', 'flag' => '🇺🇸', 'selected' => false],
                                ['code' => 'zh', 'name' => '中文', 'flag' => '🇨🇳', 'selected' => false],
                                ['code' => 'ja', 'name' => '日本語', 'flag' => '🇯🇵', 'selected' => false],
                                ['code' => 'es', 'name' => 'Español', 'flag' => '🇪🇸', 'selected' => false]
                            ];

                            foreach ($languages as $language):
                            ?>
                                <div class="language-option flex items-center justify-between px-4 py-3 cursor-pointer hover:bg-slate-50 transition-colors <?= $language['selected'] ? 'bg-blue-50 text-blue-600' : 'text-slate-700' ?>"
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
                
                <!-- Language Switcher -->
                <div class="mt-6 " style="display: flex; justify-content: center;">
                    <?php echo Render::html('Common/Auth/language-switcher'); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
Render::block('Backend\Footer', ['layout' => 'default']);
?>
