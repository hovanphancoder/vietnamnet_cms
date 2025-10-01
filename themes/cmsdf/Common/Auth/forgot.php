<?php
namespace System\Libraries;
use App\Libraries\Fastlang;

echo Render::html('Common/Auth/header', ['layout' => 'default', 'title' => Fastlang::_e('Forgot Password')]);
?>
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50">
    <div class="container relative h-screen flex-col items-center justify-center md:grid lg:max-w-none lg:grid-cols-2 lg:px-0">

        <!-- Left Panel - Branding -->
        <?php echo Render::html('Common/Auth/auth-left'); ?>

        <!-- Right Panel - Forgot Password Form -->
        <div class="flex items-center h-full justify-center p-8 bg-white/80 backdrop-blur-sm">
            <div class="w-full max-w-sm space-y-8">
                <!-- Header -->
                <div class="text-center space-y-2">
                    <h2 class="text-3xl font-bold text-slate-900"><?php __e('Forgot Password') ?></h2>
                    <p class="text-slate-600">
                        <?php __e('Enter email receive password reset link') ?>
                    </p>
                    <p class="text-slate-600">
                        <?php __e('or') ?>
                        <a class="font-medium text-blue-600 hover:text-blue-500 transition-colors" href="<?php echo auth_url('login'); ?>">
                            <?php __e('Login If Account Exists') ?>
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
                <?php if (Session::has_flash('success')): ?>
                    <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">
                                    <?php echo Session::flash('success'); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if (Session::has_flash('error')): ?>
                    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800">
                                    <?php echo Session::flash('error'); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Forgot Password Form -->
                <form class="space-y-6" method="POST" action="">
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <!-- Email Input -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-700" for="email">
                            <?php __e('Email Address') ?>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <i data-lucide="mail" class="w-5 h-5 text-slate-400"></i>
                            </div>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="w-full pl-10 pr-4 py-3 border border-slate-200 rounded-xl bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all duration-300 placeholder:text-slate-400"
                                placeholder="<?php __e('Enter your email') ?>"
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                required>
                        </div>
                        <?php if (isset($errors['email'])): ?>
                            <div class="text-red-500 text-sm mt-1">
                                <?php echo implode(', ', $errors['email']); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3.714 3.048a.498.498 0 0 0-.683.627l2.843 7.627a2 2 0 0 1 0 1.396l-2.842 7.627a.498.498 0 0 0 .682.627l18-8.5a.5.5 0 0 0 0-.904z"></path>
                            <path d="M6 12h16"></path>
                        </svg>
                        <?php __e('Send Instructions') ?>
                    </button>
                </form>
                
                
                <!-- Language Switcher -->
                <div class="mt-6" style="display: flex; justify-content: center;">
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