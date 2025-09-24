<?php

namespace System\Libraries;

use App\Libraries\Fastlang;

Render::block('Backend\Head', ['layout' => 'default', 'title' => Fastlang::_e('update_password_welcome')]);
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
                            Reset Your Password
                        </h1>
                        <p class="text-xl text-blue-100 leading-relaxed max-w-md mx-auto">
                            Enter your new password to complete the reset process
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel - Reset Password Form -->
        <div class="flex items-center h-full justify-center p-8 bg-white/80 backdrop-blur-sm">
            <div class="w-full max-w-sm space-y-6">
                <!-- Header -->
                <div class="text-center space-y-2">
                    <h2 class="text-3xl font-bold text-gray-900"><?php __e('Update Password') ?></h2>
                    <p class="text-gray-600 text-sm">
                        Enter your new password below
                    </p>
                </div>
            
                <!-- Show flash messages -->
                <?php if ($error = Session::flash('error')): ?>
                    <div class="text-red-500 mt-2 text-sm">
                        <p><?php echo $error; ?></p>
                    </div>
                <?php endif; ?>
                <?php if ($success = Session::flash('success')): ?>
                    <div class="text-green-500 mt-2 text-sm">
                        <p><?php echo $success; ?></p>
                    </div>
                <?php endif; ?>
                <!-- Show errors -->
                <?php if (!empty($errors)): ?>
                    <div class="text-red-500 mt-2 text-sm">
                        <?php foreach ($errors as $key => $err): ?>
                            <p><?php echo $key; ?>: <?php echo is_array($err) ? implode(', ', $err) : $err; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Reset Password Form -->
                <form method="post" action="" class="space-y-5">
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>" />
                    
                    <!-- New Password Input -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400">
                                <rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                        </div>
                        <input
                            type="password" name="password"
                            class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-2xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 placeholder:text-gray-400 text-sm font-medium"
                            placeholder="Enter new password"
                            required>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-2xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3.714 3.048a.498.498 0 0 0-.683.627l2.843 7.627a2 2 0 0 1 0 1.396l-2.842 7.627a.498.498 0 0 0 .682.627l18-8.5a.5.5 0 0 0 0-.904z"></path>
                            <path d="M6 12h16"></path>
                        </svg>
                        Reset Password
                    </button>
                </form>

                <!-- Back to Login -->
                <div class="text-center">
                    <a
                        href="<?php echo auth_url('login') ?>"
                        class="inline-flex items-center gap-2 px-4 py-3 border border-gray-200 rounded-2xl bg-white hover:bg-gray-50 hover:border-blue-300 transition-all duration-300 text-gray-700 font-medium text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m12 19-7-7 7-7"></path>
                            <path d="M19 12H5"></path>
                        </svg>
                        <?php __e('Back to Login') ?>
                    </a>
                </div>

                <!-- Language Selector -->
                <div class="flex justify-center mt-6">
                    <?php echo Render::html('Common/Auth/language-switcher'); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
Render::block('Backend\Footer', ['layout' => 'default']);
?>
