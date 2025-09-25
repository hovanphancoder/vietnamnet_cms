<?php

namespace System\Libraries;

use App\Libraries\Fastlang;

Render::block('Backend\Head', ['layout' => 'default', 'title' => Fastlang::_e('Forgot Password')]);
?>
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50">
    <div class="container relative h-screen flex-col items-center justify-center md:grid lg:max-w-none lg:grid-cols-2 lg:px-0">

        <!-- Left Panel - Branding -->
        <?php echo Render::html('Common/Auth/auth-left'); ?>

        <!-- Right Panel - Login Form -->
        <div class="flex items-center h-full justify-center p-8 bg-white/80 backdrop-blur-sm">
            <div class="w-full max-w-sm space-y-6">
                <!-- Header -->
                <div class="text-center space-y-2">
                    <h2 class="text-3xl font-bold text-gray-900"><?php __e('Forgot Password') ?></h2>
                    <p class="text-gray-600 text-sm">
                        <?php __e('Enter your email address and we will send you a password reset link') ?>
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

                <!-- Forgot Password Form -->
                <form method="post" action="" class="space-y-5">
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>" />
                    <!-- Email Input -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400">
                                <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                            </svg>
                        </div>
                        <input
                            type="email" name="email"
                            class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-2xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 placeholder:text-gray-400 text-sm font-medium"
                            placeholder="<?php __e('Enter your email') ?>"
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
                        <?php __e('Send Instructions') ?>
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