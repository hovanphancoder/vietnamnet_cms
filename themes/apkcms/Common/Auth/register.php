<?php
namespace System\Libraries;
use App\Libraries\Fastlang;

echo Render::html('Common/Auth/header', ['layout' => 'default', 'title' => Fastlang::_e('Register Account')]);
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
                    <h2 class="text-3xl font-bold text-gray-900"><?php __e('Create Admin Account') ?></h2>
                    <p class="text-slate-600">
                        <?php __e('or') ?>
                        <a class="font-medium text-blue-600 hover:text-blue-500 transition-colors" href="<?= auth_url('login') ?>">
                            <?php __e('Login If Account Exists') ?>
                        </a>
                    </p>
                </div>

                <!-- Google Registration -->
                <button class="w-full flex items-center justify-center gap-3 px-4 py-3 border-2 border-gray-200 rounded-2xl bg-white hover:bg-gray-50 hover:border-blue-300 hover:shadow-lg transition-all duration-300 group" type="button">
                    <i data-lucide="chrome" class="w-5 h-5"></i>
                    <span class="font-semibold text-gray-700 group-hover:text-gray-900 transition-colors">
                        <?php __e('Register With Google') ?>
                    </span>
                </button>

                <!-- Divider -->
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-200"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-white text-gray-500 font-medium"><?php __e('Or Continue With') ?></span>
                    </div>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i data-lucide="x-circle" class="h-5 w-5 text-red-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800">
                                    <?= isset($errors['csrf_failed']) ? $errors['csrf_failed'][0] : __('Please Correct Errors'); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Registration Form -->
                <form class="space-y-4" action="<?php echo auth_url('register'); ?>" method="post" id="registerForm">
                    <input type="hidden" name="csrf_token" value="<?php echo Session::csrf_token(600); ?>">
                    

                    <!-- Username -->
                    <div class="space-y-1">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                <i data-lucide="user" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <input
                                type="text"
                                id="username"
                                name="username"
                                value="<?= HAS_POST('username') ? S_POST('username') : ''; ?>"
                                class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-2xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 placeholder:text-gray-400 text-sm font-medium <?php echo (isset($errors['username']) ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : ''); ?>"
                                placeholder="<?php __e('Username Placeholder') ?>"
                                required>
                        </div>
                        <?php if (isset($errors['username'])): ?>
                            <div class="text-red-500 text-xs mt-1">
                                <?php foreach ($errors['username'] as $error): ?>
                                    <div><?php echo htmlspecialchars($error); ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Email -->
                    <div class="space-y-1">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                <i data-lucide="mail" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?= HAS_POST('email') ? S_POST('email') : ''; ?>"
                                class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-2xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 placeholder:text-gray-400 text-sm font-medium <?php echo (isset($errors['email']) ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : ''); ?>"
                                placeholder="<?php __e('Email Address Placeholder') ?>"
                                required>
                        </div>
                        <?php if (isset($errors['email'])): ?>
                            <div class="text-red-500 text-xs mt-1">
                                <?php foreach ($errors['email'] as $error): ?>
                                    <div><?php echo htmlspecialchars($error); ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Password -->
                    <div class="space-y-1">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                <i data-lucide="lock" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                value="<?= HAS_POST('password') ? S_POST('password') : ''; ?>"
                                class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-2xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 placeholder:text-gray-400 text-sm font-medium <?php echo (isset($errors['password']) ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : ''); ?>"
                                placeholder="<?php __e('Password Placeholder') ?>"
                                required>
                        </div>
                        <?php if (isset($errors['password'])): ?>
                            <div class="text-red-500 text-xs mt-1">
                                <?php foreach ($errors['password'] as $error): ?>
                                    <div><?php echo htmlspecialchars($error); ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Confirm Password -->
                    <div class="space-y-1">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                <i data-lucide="lock" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <input
                                type="password"
                                id="password_repeat"
                                name="password_repeat"
                                value="<?= HAS_POST('password_repeat') ? S_POST('password_repeat') : ''; ?>"
                                class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-2xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 placeholder:text-gray-400 text-sm font-medium <?php echo (isset($errors['password_repeat']) ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : ''); ?>"
                                placeholder="<?php __e('Confirm Password Placeholder') ?>"
                                required>
                        </div>
                        <?php if (isset($errors['password_repeat'])): ?>
                            <div class="text-red-500 text-xs mt-1">
                                <?php foreach ($errors['password_repeat'] as $error): ?>
                                    <div><?php echo htmlspecialchars($error); ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Full Name -->
                    <div class="space-y-1">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                <i data-lucide="user" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <input
                                type="text"
                                id="fullname"
                                name="fullname"
                                value="<?= HAS_POST('fullname') ? S_POST('fullname') : ''; ?>"
                                class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-2xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 placeholder:text-gray-400 text-sm font-medium <?php echo (isset($errors['fullname']) ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : ''); ?>"
                                placeholder="<?php __e('Full Name Placeholder') ?>"
                                required>
                        </div>
                        <?php if (isset($errors['fullname'])): ?>
                            <div class="text-red-500 text-xs mt-1">
                                <?php foreach ($errors['fullname'] as $error): ?>
                                    <div><?php echo htmlspecialchars($error); ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Phone -->
                    <div class="space-y-1">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                <i data-lucide="phone" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                value="<?= HAS_POST('phone') ? S_POST('phone') : ''; ?>"
                                class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-2xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 placeholder:text-gray-400 text-sm font-medium <?php echo (isset($errors['phone']) ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : ''); ?>"
                                placeholder="<?php __e('Phone Number Placeholder') ?>"
                                required>
                        </div>
                        <?php if (isset($errors['phone'])): ?>
                            <div class="text-red-500 text-xs mt-1">
                                <?php foreach ($errors['phone'] as $error): ?>
                                    <div><?php echo htmlspecialchars($error); ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Terms Checkbox -->
                    <div class="space-y-1">
                        <div class="flex items-start space-x-3">
                            <div class="relative flex items-center h-5 mt-0.5">
                                <input
                                    type="checkbox"
                                    id="terms"
                                    name="terms"
                                    class="peer sr-only"
                                    <?php echo (HAS_POST('terms') ? 'checked' : ''); ?>
                                    required />
                                <label for="terms" class="relative flex items-center justify-center w-5 h-5 border-2 border-gray-300 rounded-lg bg-white cursor-pointer transition-all duration-300 hover:border-blue-400 peer-checked:bg-blue-600 peer-checked:border-blue-600 peer-focus:ring-4 peer-focus:ring-blue-500/20 <?php echo (isset($errors['terms']) ? 'border-red-500 peer-checked:border-red-500' : ''); ?>">
                                    <i data-lucide="check" class="w-3 h-3 text-white opacity-0 peer-checked:opacity-100 transition-all duration-300 transform scale-0 peer-checked:scale-100"></i>
                                </label>
                            </div>
                            <label for="terms" class="text-sm text-gray-700 leading-5 cursor-pointer font-medium">
                                <?php __e('I agree to the') ?> <a href="<?= base_url('terms-of-service') ?>" class="text-blue-600 hover:text-blue-500"><?php __e('terms of service') ?></a> <?php __e('and') ?> <a href="<?= base_url('privacy-policy') ?>" class="text-blue-600 hover:text-blue-500"><?php __e('privacy policy') ?></a>
                            </label>
                        </div>
                        <?php if (isset($errors['terms'])): ?>
                            <div class="text-red-500 text-xs mt-1">
                                <?php foreach ($errors['terms'] as $error): ?>
                                    <div><?php echo htmlspecialchars($error); ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Register -->
                    <button
                         type="submit"
                        id="register"
                        class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold rounded-2xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-300">
                        <i data-lucide="user-plus" class="w-4 h-4"></i>
                        <?php __e('Register') ?>
                    </button>
                </form>

                
                <!-- Language Switcher -->
                <div class="mt-6 " style="display: flex; justify-content: center;">
                    <?php echo Render::html('Common/Auth/language-switcher'); ?>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>

<?php
Render::block('Backend\Footer', ['layout' => 'default']);
?>
