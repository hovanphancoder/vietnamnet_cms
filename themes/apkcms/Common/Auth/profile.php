<?php
namespace System\Libraries;
use App\Libraries\Fastlang;

echo Render::html('Common/Auth/header', ['layout' => 'default', 'title' => Fastlang::_e('Profile Settings')]);
?>

<!-- Flatpickr CDN -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <a href="<?php echo base_url(); ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
                    <span class="hidden md:block"><?php __e('Back to Home') ?></span>
                </a>
                <div class="flex items-center justify-between">
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900 mb-4"><?php __e('Profile Settings') ?></h1>
                </div>
                <div>
                    <!-- Language Switcher -->
                    <div class="">
                        <?php echo Render::html('Common/Auth/language-switcher'); ?>
                    </div>
                </div>
            </div>

        </div>

        <!-- Success/Error Messages -->
        <?php if ($error = Session::flash('error')): ?>
            <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i data-lucide="x-circle" class="h-5 w-5 text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800">
                            <?php echo htmlspecialchars($error); ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($success = Session::flash('success')): ?>
            <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i data-lucide="check-circle" class="h-5 w-5 text-green-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">
                            <?php echo htmlspecialchars($success); ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>


        <!-- Validation Errors Summary -->
        <?php if (!empty($errors) && is_array($errors)): ?>
            <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i data-lucide="alert-triangle" class="h-5 w-5 text-red-400"></i>
                    </div>
                    <div class="ml-3 flex-1">
                        <h3 class="text-sm font-medium text-red-800 mb-2">
                            <?php __e('Please fix the following errors:') ?>
                        </h3>
                        <ul class="space-y-1">
                            <?php foreach ($errors as $field => $fieldErrors): ?>
                                <?php if (is_array($fieldErrors) && !empty($fieldErrors)): ?>
                                    <li class="text-sm text-red-700">
                                        <span class="font-medium"><?php echo ucfirst(str_replace('_', ' ', $field)); ?>:</span>
                                        <span class="ml-1"><?php echo implode(', ', array_map('htmlspecialchars', $fieldErrors)); ?></span>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        
        <div class="flex flex-col lg:flex-row gap-6" x-data="profileTabs()" x-init="init()" x-ref="profileContainer">
            <!-- Sidebar Navigation -->
            <div class="lg:w-64 flex-shrink-0">
                <nav class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <ul class="space-y-2">
                        <li>
                            <a href="#personal_info" 
                               @click.prevent="switchTab('personal_info')"
                               class="profile-nav-item flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors text-gray-600 hover:text-gray-900 hover:bg-gray-50"
                               data-tab="personal_info">
                                <i data-lucide="user" class="w-4 h-4 mr-3"></i>
                                <?php __e('Personal Information') ?>
                            </a>
                        </li>
                        <li>
                            <a href="#detailed_info" 
                               @click.prevent="switchTab('detailed_info')"
                               class="profile-nav-item flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors text-gray-600 hover:text-gray-900 hover:bg-gray-50"
                               data-tab="detailed_info">
                                <i data-lucide="briefcase" class="w-4 h-4 mr-3"></i>
                                <?php __e('Detailed Information') ?>
                            </a>
                        </li>
                        <li>
                            <a href="#social_media" 
                               @click.prevent="switchTab('social_media')"
                               class="profile-nav-item flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors text-gray-600 hover:text-gray-900 hover:bg-gray-50"
                               data-tab="social_media">
                                <i data-lucide="share-2" class="w-4 h-4 mr-3"></i>
                                <?php __e('Social Media') ?>
                            </a>
                        </li>
                        <li>
                            <a href="#security" 
                               @click.prevent="switchTab('security')"
                               class="profile-nav-item flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors text-gray-600 hover:text-gray-900 hover:bg-gray-50"
                               data-tab="security">
                                <i data-lucide="shield" class="w-4 h-4 mr-3"></i>
                                <?php __e('Password & Security') ?>
                            </a>
                        </li>
                    </ul>
                    

                    
                </nav>
            </div>

            <!-- Main Content Area -->
            <div class="flex-1">
                <!-- Personal Information Section -->
                <div id="personal_info" class="profile-section">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                                <i data-lucide="user" class="w-5 h-5 mr-2 text-blue-600"></i>
                                <?php __e('Personal Information') ?>
                            </h2>
                            <p class="mt-1 text-sm text-gray-600"><?php __e('Update your basic personal details') ?></p>
                        </div>
                        <div class="p-6">
                            <!-- Profile Form -->
                            <form class="space-y-4" action="<?php echo auth_url('set-profile'); ?>" method="post" id="profileForm">
                                <input type="hidden" name="page_type" value="personal_info">
                                <input type="hidden" name="csrf_token" value="<?php echo Session::csrf_token(600); ?>">
                                
                                <!-- Profile Visibility Section -->
                                <div class="space-y-4">
                                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                        <i data-lucide="eye" class="w-5 h-5 mr-2 text-blue-600"></i>
                                        <?php __e('Profile Visibility') ?>
                                    </h3>
                                    
                                    <!-- Display Profile Toggle -->
                                    <div class="space-y-1">
                                        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-2xl">
                                            <div class="flex items-center">
                                                <i data-lucide="eye" class="w-5 h-5 text-gray-400 mr-3"></i>
                                                <div>
                                                    <h4 class="text-sm font-medium text-gray-900"><?php __e('Profile Visibility') ?></h4>
                                                    <p class="text-xs text-gray-500"><?php __e('Allow others to find and view your profile') ?></p>
                                                </div>
                                            </div>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    id="display"
                                                    name="display"
                                                    value="1"
                                                    <?php echo ($me_info['display'] ?? 0) ? 'checked' : ''; ?>
                                                    class="sr-only peer">
                                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Divider -->
                                <div class="border-t border-gray-200 my-6"></div>

                                <!-- Personal Information Section -->
                                <div class="space-y-4">
                                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                        <i data-lucide="user" class="w-5 h-5 mr-2 text-blue-600"></i>
                                        <?php __e('Personal Information') ?>
                                    </h3>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <!-- Full Name -->
                                        <div class="space-y-1">
                                            <label class="block text-sm font-medium text-gray-700"><?php __e('Full Name') ?></label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                                    <i data-lucide="user" class="w-4 h-4 text-gray-400"></i>
                                                </div>
                                                <input
                                                    type="text"
                                                    id="fullname"
                                                    name="fullname"
                                                    value="<?php echo htmlspecialchars($me_info['fullname'] ?? ''); ?>"
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

                                        <!-- Username (Editable) -->
                                        <div class="space-y-1">
                                            <label class="block text-sm font-medium text-gray-700"><?php __e('Username') ?></label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                                    <i data-lucide="at-sign" class="w-4 h-4 text-gray-400"></i>
                                                </div>
                                                <input
                                                    type="text"
                                                    id="username"
                                                    name="username"
                                                    value="<?php echo htmlspecialchars($me_info['username'] ?? ''); ?>"
                                                    placeholder="<?php __e('Username Placeholder') ?>"
                                                    class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-2xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 placeholder:text-gray-400 text-sm font-medium <?php echo (isset($errors['username']) ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : ''); ?>">
                                            </div>
                                            <?php if (isset($errors['username'])): ?>
                                                <div class="text-red-500 text-xs mt-1">
                                                    <?php foreach ($errors['username'] as $error): ?>
                                                        <div><?php echo htmlspecialchars($error); ?></div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Email (Read-only) -->
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-700"><?php __e('Email Address') ?></label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                                <i data-lucide="mail" class="w-4 h-4 text-gray-400"></i>
                                            </div>
                                            <input
                                                type="email"
                                                id="email"
                                                value="<?php echo htmlspecialchars($me_info['email'] ?? ''); ?>"
                                                class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-2xl bg-gray-50 text-gray-500 cursor-not-allowed"
                                                readonly>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1"><?php __e('Email cannot be changed') ?></p>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <!-- Birthday -->
                                        <div class="space-y-1">
                                            <label class="block text-sm font-medium text-gray-700"><?php __e('Birthday') ?></label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                                    <i data-lucide="calendar" class="w-4 h-4 text-gray-400"></i>
                                                </div>
                                                <input
                                                    type="date"
                                                    id="birthday"
                                                    name="birthday"
                                                    value="<?php echo htmlspecialchars($me_info['birthday'] ?? ''); ?>"
                                                    class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-2xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 placeholder:text-gray-400 text-sm font-medium <?php echo (isset($errors['birthday']) ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : ''); ?>"
                                                    placeholder="YYYY-MM-DD">
                                                <!-- Fallback for old browsers -->
                                                <noscript>
                                                    <div class="mt-2 text-sm text-gray-500">
                                                        <?php __e('Please enter date in YYYY-MM-DD format') ?>
                                                    </div>
                                                </noscript>
                                            </div>
                                            <?php if (isset($errors['birthday'])): ?>
                                                <div class="text-red-500 text-xs mt-1">
                                                    <?php foreach ($errors['birthday'] as $error): ?>
                                                        <div><?php echo htmlspecialchars($error); ?></div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Gender -->
                                        <div class="space-y-1">
                                            <label class="block text-sm font-medium text-gray-700"><?php __e('Gender') ?></label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                                    <i data-lucide="users" class="w-4 h-4 text-gray-400"></i>
                                                </div>
                                                <select
                                                    id="gender"
                                                    name="gender"
                                                    class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-2xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 text-sm font-medium <?php echo (isset($errors['gender']) ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : ''); ?>">
                                                    <option value=""><?php __e('Select Gender') ?></option>
                                                    <option value="male" <?php echo ($me_info['gender'] ?? '') === 'male' ? 'selected' : ''; ?>><?php __e('Male') ?></option>
                                                    <option value="female" <?php echo ($me_info['gender'] ?? '') === 'female' ? 'selected' : ''; ?>><?php __e('Female') ?></option>
                                                    <option value="other" <?php echo ($me_info['gender'] ?? '') === 'other' ? 'selected' : ''; ?>><?php __e('Other') ?></option>
                                                </select>
                                            </div>
                                            <?php if (isset($errors['gender'])): ?>
                                                <div class="text-red-500 text-xs mt-1">
                                                    <?php foreach ($errors['gender'] as $error): ?>
                                                        <div><?php echo htmlspecialchars($error); ?></div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Personal Description -->
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-700"><?php __e('Personal Description') ?></label>
                                        <div class="relative">
                                            <div class="absolute top-3 left-0 flex items-start pl-4">
                                                <i data-lucide="file-text" class="w-4 h-4 text-gray-400"></i>
                                            </div>
                                            <textarea
                                                id="about_me"
                                                name="about_me"
                                                rows="4"
                                                class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-2xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 placeholder:text-gray-400 text-sm font-medium resize-none <?php echo (isset($errors['about_me']) ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : ''); ?>"
                                                placeholder="<?php __e('Tell us about yourself...') ?>"><?php echo htmlspecialchars($me_info['about_me'] ?? ''); ?></textarea>
                                        </div>
                                        <?php if (isset($errors['about_me'])): ?>
                                            <div class="text-red-500 text-xs mt-1">
                                                <?php foreach ($errors['about_me'] as $error): ?>
                                                    <div><?php echo htmlspecialchars($error); ?></div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>


                                <!-- Divider -->
                                <div class="border-t border-gray-200 my-6"></div>

                                <!-- Contact & Location Section -->
                                <div class="space-y-4">
                                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                        <i data-lucide="map-pin" class="w-5 h-5 mr-2 text-blue-600"></i>
                                        <?php __e('Contact & Location') ?>
                                    </h3>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <!-- Phone -->
                                        <div class="space-y-1">
                                            <label class="block text-sm font-medium text-gray-700"><?php __e('Phone Number') ?></label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                                    <i data-lucide="phone" class="w-4 h-4 text-gray-400"></i>
                                                </div>
                                                <input
                                                    type="tel"
                                                    id="phone"
                                                    name="phone"
                                                    value="<?php echo htmlspecialchars($me_info['phone'] ?? ''); ?>"
                                                    class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-2xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 placeholder:text-gray-400 text-sm font-medium <?php echo (isset($errors['phone']) ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : ''); ?>"
                                                    placeholder="<?php __e('Phone Number Placeholder') ?>">
                                            </div>
                                            <?php if (isset($errors['phone'])): ?>
                                                <div class="text-red-500 text-xs mt-1">
                                                    <?php foreach ($errors['phone'] as $error): ?>
                                                        <div><?php echo htmlspecialchars($error); ?></div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Country -->
                                        <div class="space-y-1">
                                            <label class="block text-sm font-medium text-gray-700"><?php __e('Country') ?></label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                                    <i data-lucide="flag" class="w-4 h-4 text-gray-400"></i>
                                                </div>
                                                <select
                                                    id="country"
                                                    name="country"
                                                    class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-2xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 text-sm font-medium <?php echo (isset($errors['country']) ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : ''); ?>">
                                                    <option value=""><?php __e('Select Country') ?></option>
                                                    <option value="VN" <?php echo ($me_info['country'] ?? '') === 'VN' ? 'selected' : ''; ?>>🇻🇳 Vietnam</option>
                                                    <option value="US" <?php echo ($me_info['country'] ?? '') === 'US' ? 'selected' : ''; ?>>🇺🇸 United States</option>
                                                    <option value="GB" <?php echo ($me_info['country'] ?? '') === 'GB' ? 'selected' : ''; ?>>🇬🇧 United Kingdom</option>
                                                    <option value="JP" <?php echo ($me_info['country'] ?? '') === 'JP' ? 'selected' : ''; ?>>🇯🇵 Japan</option>
                                                    <option value="KR" <?php echo ($me_info['country'] ?? '') === 'KR' ? 'selected' : ''; ?>>🇰🇷 South Korea</option>
                                                    <option value="CN" <?php echo ($me_info['country'] ?? '') === 'CN' ? 'selected' : ''; ?>>🇨🇳 China</option>
                                                    <option value="TH" <?php echo ($me_info['country'] ?? '') === 'TH' ? 'selected' : ''; ?>>🇹🇭 Thailand</option>
                                                    <option value="SG" <?php echo ($me_info['country'] ?? '') === 'SG' ? 'selected' : ''; ?>>🇸🇬 Singapore</option>
                                                </select>
                                            </div>
                                            <?php if (isset($errors['country'])): ?>
                                                <div class="text-red-500 text-xs mt-1">
                                                    <?php foreach ($errors['country'] as $error): ?>
                                                        <div><?php echo htmlspecialchars($error); ?></div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Address Fields -->
                                    <?php 
                                    // Use pre-processed address data from _prepare_profile_data
                                    $address = $me_info['address'] ?? [];
                                    ?>

                                    <!-- Address 1 -->
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-700"><?php __e('Address Line 1') ?></label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                                <i data-lucide="home" class="w-4 h-4 text-gray-400"></i>
                                            </div>
                                            <input
                                                type="text"
                                                id="address1"
                                                name="address1"
                                                value="<?php echo htmlspecialchars($address['address1'] ?? ''); ?>"
                                                class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-2xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 placeholder:text-gray-400 text-sm font-medium <?php echo (isset($errors['address1']) ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : ''); ?>"
                                                placeholder="<?php __e('Address Line 1') ?>">
                                        </div>
                                        <?php if (isset($errors['address1'])): ?>
                                            <div class="text-red-500 text-xs mt-1">
                                                <?php foreach ($errors['address1'] as $error): ?>
                                                    <div><?php echo htmlspecialchars($error); ?></div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Address 2 -->
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-700"><?php __e('Address Line 2') ?></label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                                <i data-lucide="building" class="w-4 h-4 text-gray-400"></i>
                                            </div>
                                            <input
                                                type="text"
                                                id="address2"
                                                name="address2"
                                                value="<?php echo htmlspecialchars($address['address2'] ?? ''); ?>"
                                                class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-2xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 placeholder:text-gray-400 text-sm font-medium <?php echo (isset($errors['address2']) ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : ''); ?>"
                                                placeholder="<?php __e('Address Line 2') ?>">
                                        </div>
                                        <?php if (isset($errors['address2'])): ?>
                                            <div class="text-red-500 text-xs mt-1">
                                                <?php foreach ($errors['address2'] as $error): ?>
                                                    <div><?php echo htmlspecialchars($error); ?></div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <!-- City -->
                                        <div class="space-y-1">
                                            <label class="block text-sm font-medium text-gray-700"><?php __e('City') ?></label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                                    <i data-lucide="map-pin" class="w-4 h-4 text-gray-400"></i>
                                                </div>
                                                <input
                                                    type="text"
                                                    id="city"
                                                    name="city"
                                                    value="<?php echo htmlspecialchars($address['city'] ?? ''); ?>"
                                                    class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-2xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 placeholder:text-gray-400 text-sm font-medium <?php echo (isset($errors['city']) ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : ''); ?>"
                                                    placeholder="<?php __e('City') ?>">
                                            </div>
                                            <?php if (isset($errors['city'])): ?>
                                                <div class="text-red-500 text-xs mt-1">
                                                    <?php foreach ($errors['city'] as $error): ?>
                                                        <div><?php echo htmlspecialchars($error); ?></div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- State -->
                                        <div class="space-y-1">
                                            <label class="block text-sm font-medium text-gray-700"><?php __e('State/Province') ?></label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                                    <i data-lucide="map" class="w-4 h-4 text-gray-400"></i>
                                                </div>
                                                <input
                                                    type="text"
                                                    id="state"
                                                    name="state"
                                                    value="<?php echo htmlspecialchars($address['state'] ?? ''); ?>"
                                                    class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-2xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 placeholder:text-gray-400 text-sm font-medium <?php echo (isset($errors['state']) ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : ''); ?>"
                                                    placeholder="<?php __e('State/Province') ?>">
                                            </div>
                                            <?php if (isset($errors['state'])): ?>
                                                <div class="text-red-500 text-xs mt-1">
                                                    <?php foreach ($errors['state'] as $error): ?>
                                                        <div><?php echo htmlspecialchars($error); ?></div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Zip Code -->
                                        <div class="space-y-1">
                                            <label class="block text-sm font-medium text-gray-700"><?php __e('ZIP/Postal Code') ?></label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                                    <i data-lucide="hash" class="w-4 h-4 text-gray-400"></i>
                                                </div>
                                                <input
                                                    type="text"
                                                    id="zipcode"
                                                    name="zipcode"
                                                    value="<?php echo htmlspecialchars($address['zipcode'] ?? ''); ?>"
                                                    class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-2xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 placeholder:text-gray-400 text-sm font-medium <?php echo (isset($errors['zipcode']) ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : ''); ?>"
                                                    placeholder="<?php __e('ZIP/Postal Code') ?>">
                                            </div>
                                            <?php if (isset($errors['zipcode'])): ?>
                                                <div class="text-red-500 text-xs mt-1">
                                                    <?php foreach ($errors['zipcode'] as $error): ?>
                                                        <div><?php echo htmlspecialchars($error); ?></div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Update Profile Button -->
                                <button
                                    type="submit"
                                    id="updateProfile"
                                    class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold rounded-2xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-300">
                                    <i data-lucide="save" class="w-4 h-4"></i>
                                    <?php __e('Update Profile') ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>


                <!-- Detailed Information Section -->
                <div id="detailed_info" x-data="detailedInfo()" class="profile-section hidden">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                                <i data-lucide="briefcase" class="w-5 h-5 mr-2 text-blue-600"></i>
                                <?php __e('Detailed Information') ?>
                            </h2>
                            <p class="mt-1 text-sm text-gray-600"><?php __e('Manage your professional and personal details') ?></p>
                        </div>
                        <div class="p-6">
                            <form class="space-y-6" action="<?php echo auth_url('set-profile'); ?>" method="post" id="detailedInfoForm">
                                <input type="hidden" name="page_type" value="detailed_info">
                                <input type="hidden" name="csrf_token" value="<?php echo Session::csrf_token(600); ?>">
                                
                                <!-- Work Experience -->
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                            <i data-lucide="briefcase" class="w-5 h-5 mr-2 text-blue-600"></i>
                                            <?php __e('Work Experience') ?>
                                        </h3>
                                        <button type="button" @click="addWorkExperience()" class="inline-flex items-center px-3 py-2 border border-blue-300 rounded-lg text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 transition-colors">
                                            <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                                            <?php __e('Add Experience') ?>
                                        </button>
                                    </div>
                                    
                                    <div class="space-y-4" x-show="workExperiences.length > 0">
                                        <template x-for="(work, index) in workExperiences" :key="index">
                                            <div class="border border-gray-200 rounded-lg p-4">
                                                <div class="flex items-center justify-between mb-4">
                                                    <h4 class="text-sm font-medium text-gray-900"><?php __e('Experience') ?> <span x-text="index + 1"></span></h4>
                                                    <button type="button" @click="removeWorkExperience(index)" class="text-red-500 hover:text-red-700">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </button>
                                                </div>
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <div class="space-y-1">
                                                        <label class="block text-sm font-medium text-gray-700"><?php __e('Company') ?></label>
                                                        <input type="text" x-model="work.company" :name="`work_experiences[${index}][company]`" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all duration-300">
                                                    </div>
                                                    <div class="space-y-1">
                                                        <label class="block text-sm font-medium text-gray-700"><?php __e('Position') ?></label>
                                                        <input type="text" x-model="work.position" :name="`work_experiences[${index}][position]`" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all duration-300">
                                                    </div>
                                                    <div class="space-y-1">
                                                        <label class="block text-sm font-medium text-gray-700"><?php __e('Start Date') ?></label>
                                                        <input type="date" x-model="work.start_date" :name="`work_experiences[${index}][start_date]`" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all duration-300">
                                                    </div>
                                                    <div class="space-y-1">
                                                        <label class="block text-sm font-medium text-gray-700"><?php __e('End Date') ?></label>
                                                        <input type="date" x-model="work.end_date" :name="`work_experiences[${index}][end_date]`" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all duration-300">
                                                    </div>
                                                    <div class="md:col-span-2 space-y-1">
                                                        <label class="block text-sm font-medium text-gray-700"><?php __e('Description') ?></label>
                                                        <textarea x-model="work.description" :name="`work_experiences[${index}][description]`" rows="3" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all duration-300 resize-none" placeholder="<?php __e('Describe your role and achievements...') ?>"></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- Education -->
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                            <i data-lucide="graduation-cap" class="w-5 h-5 mr-2 text-blue-600"></i>
                                            <?php __e('Education') ?>
                                        </h3>
                                        <button type="button" @click="addEducation()" class="inline-flex items-center px-3 py-2 border border-blue-300 rounded-lg text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 transition-colors">
                                            <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                                            <?php __e('Add Education') ?>
                                        </button>
                                    </div>
                                    
                                    <div class="space-y-4" x-show="educations.length > 0">
                                        <template x-for="(edu, index) in educations" :key="index">
                                            <div class="border border-gray-200 rounded-lg p-4">
                                                <div class="flex items-center justify-between mb-4">
                                                    <h4 class="text-sm font-medium text-gray-900"><?php __e('Education') ?> <span x-text="index + 1"></span></h4>
                                                    <button type="button" @click="removeEducation(index)" class="text-red-500 hover:text-red-700">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </button>
                                                </div>
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <div class="space-y-1">
                                                        <label class="block text-sm font-medium text-gray-700"><?php __e('Institution') ?></label>
                                                        <input type="text" x-model="edu.institution" :name="`educations[${index}][institution]`" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all duration-300">
                                                    </div>
                                                    <div class="space-y-1">
                                                        <label class="block text-sm font-medium text-gray-700"><?php __e('Degree') ?></label>
                                                        <input type="text" x-model="edu.degree" :name="`educations[${index}][degree]`" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all duration-300">
                                                    </div>
                                                    <div class="space-y-1">
                                                        <label class="block text-sm font-medium text-gray-700"><?php __e('Start Date') ?></label>
                                                        <input type="date" x-model="edu.start_date" :name="`educations[${index}][start_date]`" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all duration-300">
                                                    </div>
                                                    <div class="space-y-1">
                                                        <label class="block text-sm font-medium text-gray-700"><?php __e('End Date') ?></label>
                                                        <input type="date" x-model="edu.end_date" :name="`educations[${index}][end_date]`" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all duration-300">
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- Skills -->
                                <div class="space-y-4">
                                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                        <i data-lucide="award" class="w-5 h-5 mr-2 text-blue-600"></i>
                                        <?php __e('Skills') ?>
                                    </h3>
                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700"><?php __e('Add Skills') ?></label>
                                        <div class="flex flex-wrap gap-2 mb-4" x-show="skills.length > 0">
                                            <template x-for="(skill, index) in skills" :key="index">
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800">
                                                    <span x-text="skill"></span>
                                                    <button type="button" @click="removeSkill(index)" class="ml-2 text-blue-600 hover:text-blue-800">
                                                        <i data-lucide="x" class="w-3 h-3"></i>
                                                    </button>
                                                </span>
                                            </template>
                                        </div>
                                    <div class="flex gap-2">
                                        <input type="text" x-model="newSkill" @keydown.enter.prevent="addSkill()" class="flex-1 px-3 py-2 border border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all duration-300" placeholder="<?php __e('Enter a skill and press Enter') ?>">
                                        <button type="button" @click="addSkill()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                            <i data-lucide="plus" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                    <div x-show="skills.some(skill => skill.toLowerCase() === newSkill.toLowerCase()) && newSkill.trim() !== ''" class="text-amber-500 text-xs mt-1">
                                        <i data-lucide="alert-triangle" class="w-3 h-3 inline mr-1"></i>
                                        <?php __e('This skill already exists') ?>
                                    </div>
                                        <template x-for="(skill, index) in skills" :key="index">
                                            <input type="hidden" :name="`skills[${index}]`" :value="skill">
                                        </template>
                                    </div>
                                </div>

                                <!-- Languages -->
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                            <i data-lucide="globe" class="w-5 h-5 mr-2 text-blue-600"></i>
                                            <?php __e('Languages') ?>
                                        </h3>
                                        <button type="button" @click="addLanguage()" class="inline-flex items-center px-3 py-2 border border-blue-300 rounded-lg text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 transition-colors">
                                            <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                                            <?php __e('Add Language') ?>
                                        </button>
                                    </div>
                                    
                                    <div class="space-y-4" x-show="languages.length > 0">
                                        <template x-for="(lang, index) in languages" :key="index">
                                            <div class="border border-gray-200 rounded-lg p-4">
                                                <div class="flex items-center justify-between mb-4">
                                                    <h4 class="text-sm font-medium text-gray-900"><?php __e('Language') ?> <span x-text="index + 1"></span></h4>
                                                    <button type="button" @click="removeLanguage(index)" class="text-red-500 hover:text-red-700">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </button>
                                                </div>
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <div class="space-y-1">
                                                        <label class="block text-sm font-medium text-gray-700"><?php __e('Language') ?></label>
                                                        <input type="text" x-model="lang.language" :name="`languages[${index}][language]`" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all duration-300">
                                                    </div>
                                                    <div class="space-y-1">
                                                        <label class="block text-sm font-medium text-gray-700"><?php __e('Proficiency') ?></label>
                                                        <select x-model="lang.proficiency" :name="`languages[${index}][proficiency]`" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all duration-300">
                                                            <option value=""><?php __e('Select Proficiency') ?></option>
                                                            <option value="beginner"><?php __e('Beginner') ?></option>
                                                            <option value="intermediate"><?php __e('Intermediate') ?></option>
                                                            <option value="advanced"><?php __e('Advanced') ?></option>
                                                            <option value="native"><?php __e('Native') ?></option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- Hobbies -->
                                <div class="space-y-4">
                                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                        <i data-lucide="heart" class="w-5 h-5 mr-2 text-blue-600"></i>
                                        <?php __e('Hobbies & Interests') ?>
                                    </h3>
                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700"><?php __e('Add Hobbies') ?></label>
                                        <div class="flex flex-wrap gap-2 mb-4" x-show="hobbies.length > 0">
                                            <template x-for="(hobby, index) in hobbies" :key="index">
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-green-100 text-green-800">
                                                    <span x-text="hobby"></span>
                                                    <button type="button" @click="removeHobby(index)" class="ml-2 text-green-600 hover:text-green-800">
                                                        <i data-lucide="x" class="w-3 h-3"></i>
                                                    </button>
                                                </span>
                                            </template>
                                        </div>
                                        <div class="flex gap-2">
                                            <input type="text" x-model="newHobby" @keydown.enter.prevent="addHobby()" class="flex-1 px-3 py-2 border border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all duration-300" placeholder="<?php __e('Enter a hobby and press Enter') ?>">
                                            <button type="button" @click="addHobby()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                                <i data-lucide="plus" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                        <div x-show="hobbies.some(hobby => hobby.toLowerCase() === newHobby.toLowerCase()) && newHobby.trim() !== ''" class="text-amber-500 text-xs mt-1">
                                            <i data-lucide="alert-triangle" class="w-3 h-3 inline mr-1"></i>
                                            <?php __e('This hobby already exists') ?>
                                        </div>
                                        <template x-for="(hobby, index) in hobbies" :key="index">
                                            <input type="hidden" :name="`hobbies[${index}]`" :value="hobby">
                                        </template>
                                    </div>
                                </div>

                                <!-- Certifications -->
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                            <i data-lucide="award" class="w-5 h-5 mr-2 text-blue-600"></i>
                                            <?php __e('Certifications & Achievements') ?>
                                        </h3>
                                        <button type="button" @click="addCertification()" class="inline-flex items-center px-3 py-2 border border-blue-300 rounded-lg text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 transition-colors">
                                            <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                                            <?php __e('Add Certification') ?>
                                        </button>
                                    </div>
                                    
                                    <div class="space-y-4" x-show="certifications.length > 0">
                                        <template x-for="(cert, index) in certifications" :key="index">
                                            <div class="border border-gray-200 rounded-lg p-4">
                                                <div class="flex items-center justify-between mb-4">
                                                    <h4 class="text-sm font-medium text-gray-900"><?php __e('Certification') ?> <span x-text="index + 1"></span></h4>
                                                    <button type="button" @click="removeCertification(index)" class="text-red-500 hover:text-red-700">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </button>
                                                </div>
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <div class="space-y-1">
                                                        <label class="block text-sm font-medium text-gray-700"><?php __e('Name') ?></label>
                                                        <input type="text" x-model="cert.name" :name="`certifications[${index}][name]`" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all duration-300">
                                                    </div>
                                                    <div class="space-y-1">
                                                        <label class="block text-sm font-medium text-gray-700"><?php __e('Issuing Organization') ?></label>
                                                        <input type="text" x-model="cert.issuer" :name="`certifications[${index}][issuer]`" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all duration-300">
                                                    </div>
                                                    <div class="space-y-1">
                                                        <label class="block text-sm font-medium text-gray-700"><?php __e('Issue Date') ?></label>
                                                        <input type="date" x-model="cert.issue_date" :name="`certifications[${index}][issue_date]`" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all duration-300">
                                                    </div>
                                                    <div class="space-y-1">
                                                        <label class="block text-sm font-medium text-gray-700"><?php __e('Expiry Date') ?></label>
                                                        <input type="date" x-model="cert.expiry_date" :name="`certifications[${index}][expiry_date]`" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all duration-300">
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- Update Button -->
                                <button
                                    type="submit"
                                    class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold rounded-2xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-300">
                                    <i data-lucide="save" class="w-4 h-4"></i>
                                    <?php __e('Update Detailed Information') ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Social Media Section -->
                <div id="social_media" x-data="socialMedia()" class="profile-section hidden">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                                <i data-lucide="share-2" class="w-5 h-5 mr-2 text-blue-600"></i>
                                <?php __e('Social Media') ?>
                            </h2>
                            <p class="mt-1 text-sm text-gray-600"><?php __e('Connect your social media accounts') ?></p>
                        </div>
                        <div class="p-6">
                            <form class="space-y-4" action="<?php echo auth_url('set-profile'); ?>" method="post" id="socialForm">
                                <input type="hidden" name="page_type" value="social_media">
                                <input type="hidden" name="csrf_token" value="<?php echo Session::csrf_token(600); ?>">
                                
                                <?php 
                                // Get social media data from me_info
                                $allSocials = $me_info['socials'] ?? [];
                                $socials = [
                                    'facebook' => $allSocials['facebook'] ?? '',
                                    'linkedin' => $allSocials['linkedin'] ?? '',
                                    'telegram' => $allSocials['telegram'] ?? '',
                                    'whatsapp' => $allSocials['whatsapp'] ?? ''
                                ];
                                ?>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Facebook -->
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-700"><?php __e('Facebook') ?></label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                                <i data-lucide="facebook" class="w-4 h-4 text-gray-400"></i>
                                            </div>
                                            <input
                                                type="text"
                                                id="facebook"
                                                name="facebook"
                                                value="<?php echo htmlspecialchars($socials['facebook'] ?? ''); ?>"
                                                class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-2xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 placeholder:text-gray-400 text-sm font-medium <?php echo (isset($errors['facebook']) ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : ''); ?>"
                                                placeholder="<?php __e('Facebook Username/URL') ?>">
                                        </div>
                                        <?php if (isset($errors['facebook'])): ?>
                                            <div class="text-red-500 text-xs mt-1">
                                                <?php foreach ($errors['facebook'] as $error): ?>
                                                    <div><?php echo htmlspecialchars($error); ?></div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Telegram -->
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-700"><?php __e('Telegram') ?></label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                                <i data-lucide="message-circle" class="w-4 h-4 text-gray-400"></i>
                                            </div>
                                            <input
                                                type="text"
                                                id="telegram"
                                                name="telegram"
                                                value="<?php echo htmlspecialchars($socials['telegram'] ?? ''); ?>"
                                                class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-2xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 placeholder:text-gray-400 text-sm font-medium <?php echo (isset($errors['telegram']) ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : ''); ?>"
                                                placeholder="<?php __e('Telegram Username') ?>">
                                        </div>
                                        <?php if (isset($errors['telegram'])): ?>
                                            <div class="text-red-500 text-xs mt-1">
                                                <?php foreach ($errors['telegram'] as $error): ?>
                                                    <div><?php echo htmlspecialchars($error); ?></div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- WhatsApp -->
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-700"><?php __e('WhatsApp') ?></label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                                <i data-lucide="smartphone" class="w-4 h-4 text-gray-400"></i>
                                            </div>
                                            <input
                                                type="text"
                                                id="whatsapp"
                                                name="whatsapp"
                                                value="<?php echo htmlspecialchars($socials['whatsapp'] ?? ''); ?>"
                                                class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-2xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 placeholder:text-gray-400 text-sm font-medium <?php echo (isset($errors['whatsapp']) ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : ''); ?>"
                                                placeholder="<?php __e('WhatsApp Number') ?>">
                                        </div>
                                        <?php if (isset($errors['whatsapp'])): ?>
                                            <div class="text-red-500 text-xs mt-1">
                                                <?php foreach ($errors['whatsapp'] as $error): ?>
                                                    <div><?php echo htmlspecialchars($error); ?></div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- LinkedIn -->
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-700"><?php __e('LinkedIn') ?></label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                                <i data-lucide="linkedin" class="w-4 h-4 text-gray-400"></i>
                                            </div>
                                            <input
                                                type="text"
                                                id="linkedin"
                                                name="linkedin"
                                                value="<?php echo htmlspecialchars($socials['linkedin'] ?? ''); ?>"
                                                class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-2xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 placeholder:text-gray-400 text-sm font-medium <?php echo (isset($errors['linkedin']) ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : ''); ?>"
                                                placeholder="<?php __e('LinkedIn Profile URL') ?>">
                                        </div>
                                        <?php if (isset($errors['linkedin'])): ?>
                                            <div class="text-red-500 text-xs mt-1">
                                                <?php foreach ($errors['linkedin'] as $error): ?>
                                                    <div><?php echo htmlspecialchars($error); ?></div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Custom Social Media Fields -->
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                            <i data-lucide="link" class="w-5 h-5 mr-2 text-blue-600"></i>
                                            <?php __e('Custom Social Media') ?>
                                        </h3>
                                        <button type="button" @click="addCustomSocial()" class="inline-flex items-center px-3 py-2 border border-blue-300 rounded-lg text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 transition-colors">
                                            <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                                            <?php __e('Add Custom Social Media') ?>
                                        </button>
                                    </div>
                                    
                                    <div class="space-y-4" x-show="customSocials.length > 0">
                                        <template x-for="(social, index) in customSocials" :key="index">
                                            <div class="border border-gray-200 rounded-lg p-4">
                                                <div class="flex items-center justify-between mb-4">
                                                    <h4 class="text-sm font-medium text-gray-900"><?php __e('Custom Social') ?> <span x-text="index + 1"></span></h4>
                                                    <button type="button" @click="removeCustomSocial(index)" class="text-red-500 hover:text-red-700">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </button>
                                                </div>
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <div class="space-y-1">
                                                        <label class="block text-sm font-medium text-gray-700"><?php __e('Social Platform Name') ?></label>
                                                        <div class="relative">
                                                            <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                                                <i data-lucide="link" class="w-4 h-4 text-gray-400"></i>
                                                            </div>
                                                            <input 
                                                                type="text" 
                                                                x-model="social.name" 
                                                                :name="`custom_social_name[${index}]`"
                                                                :class="`w-full pl-12 pr-4 py-3 border rounded-2xl bg-white focus:ring-4 transition-all duration-300 placeholder:text-gray-400 text-sm font-medium ${isDuplicateName(social.name, index) ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-200 focus:border-blue-500 focus:ring-blue-500/20'}`"
                                                                placeholder="<?php __e('Social Platform Name') ?>">
                                                        </div>
                                                        <div x-show="isDuplicateName(social.name, index)" class="text-red-500 text-xs">
                                                            <span x-text="getDuplicateWarning(social.name, index)"></span>
                                                        </div>
                                                    </div>
                                                    <div class="space-y-1">
                                                        <label class="block text-sm font-medium text-gray-700"><?php __e('Username/URL') ?></label>
                                                        <div class="relative">
                                                            <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                                                <i data-lucide="user" class="w-4 h-4 text-gray-400"></i>
                                                            </div>
                                                            <input 
                                                                type="text" 
                                                                x-model="social.value" 
                                                                :name="`custom_social_value[${index}]`"
                                                                class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-2xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 placeholder:text-gray-400 text-sm font-medium"
                                                                placeholder="<?php __e('Username/URL') ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                    
                                    <div x-show="customSocials.length === 0" class="text-center py-8 text-gray-500">
                                        <i data-lucide="link" class="w-12 h-12 mx-auto mb-2 text-gray-300"></i>
                                        <p><?php __e('No custom social media added yet') ?></p>
                                    </div>
                                </div>

                                <!-- Update Social Media Button -->
                                <button
                                    type="submit"
                                    class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold rounded-2xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-300">
                                    <i data-lucide="save" class="w-4 h-4"></i>
                                    <?php __e('Update Social Media') ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>


                <!-- Security Section -->
                <div id="security" class="profile-section hidden">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                                <i data-lucide="shield" class="w-5 h-5 mr-2 text-blue-600"></i>
                                <?php __e('Security') ?>
                            </h2>
                            <p class="mt-1 text-sm text-gray-600"><?php __e('Change your password and manage security settings') ?></p>
                        </div>
                        <div class="p-6">
                            <form class="space-y-4" action="<?php echo auth_url('change-password'); ?>" method="post" id="changePasswordForm">
                                <input type="hidden" name="csrf_token" value="<?php echo Session::csrf_token(600); ?>">
                                <input type="hidden" name="page_type" value="security">
                                
                                <!-- Current Password -->
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-700"><?php __e('Current Password') ?></label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                            <i data-lucide="lock" class="w-4 h-4 text-gray-400"></i>
                                        </div>
                                        <input
                                            type="password"
                                            id="current_password"
                                            name="current_password"
                                            class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-2xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 placeholder:text-gray-400 text-sm font-medium"
                                            placeholder="<?php __e('Current Password Placeholder') ?>"
                                            required>
                                    </div>
                                </div>

                                <!-- New Password -->
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-700"><?php __e('New Password') ?></label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                            <i data-lucide="key" class="w-4 h-4 text-gray-400"></i>
                                        </div>
                                        <input
                                            type="password"
                                            id="new_password"
                                            name="new_password"
                                            class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-2xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 placeholder:text-gray-400 text-sm font-medium"
                                            placeholder="<?php __e('New Password Placeholder') ?>"
                                            required>
                                    </div>
                                </div>

                                <!-- Confirm New Password -->
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-700"><?php __e('Confirm New Password') ?></label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                            <i data-lucide="shield-check" class="w-4 h-4 text-gray-400"></i>
                                        </div>
                                        <input
                                            type="password"
                                            id="confirm_password"
                                            name="confirm_password"
                                            class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-2xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 placeholder:text-gray-400 text-sm font-medium"
                                            placeholder="<?php __e('Confirm New Password Placeholder') ?>"
                                            required>
                                    </div>
                                </div>

                                <!-- Change Password Button -->
                                <button
                                    type="submit"
                                    id="changePassword"
                                    class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold rounded-2xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-300">
                                    <i data-lucide="key" class="w-4 h-4"></i>
                                    <?php __e('Change Password') ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Logout Button -->
        <div class="mt-6">
            <a href="<?php echo auth_url('logout'); ?>" class="w-[160px] mx-auto flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-orange-600 to-red-600 text-white font-bold rounded-2xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-300">
                <i data-lucide="log-out" class="w-4 h-4"></i>
                <?php __e('Logout') ?>
            </a>
        </div>
    </div>
</div>

<style>
/* Sidebar Navigation Styles */
.profile-nav-item {
    color: #6b7280;
    background-color: transparent;
}

.profile-nav-item:hover {
    color: #374151;
    background-color: #f3f4f6;
}

.profile-nav-item.active {
    color: #1f2937;
    background-color: #dbeafe;
    border-left: 3px solid #3b82f6;
}

.profile-section {
    display: block;
}

.profile-section.hidden {
    display: none;
}

/* Smooth transitions */
.profile-nav-item,
.profile-section {
    transition: all 0.3s ease;
}

/* Form styling improvements */
.form-section {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

</style>

<!-- Alpine.js CDN -->
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

<script>
// Alpine.js Data
function detailedInfo() {
    return {
        // Work Experience
        workExperiences: <?php echo json_encode($me_info['work_experiences'] ?? []); ?>,
        addWorkExperience() {
            this.workExperiences.push({
                company: '',
                position: '',
                start_date: '',
                end_date: '',
                description: ''
            });
        },
        removeWorkExperience(index) {
            this.workExperiences.splice(index, 1);
        },

        // Education
        educations: <?php echo json_encode($me_info['educations'] ?? []); ?>,
        addEducation() {
            this.educations.push({
                institution: '',
                degree: '',
                start_date: '',
                end_date: ''
            });
        },
        removeEducation(index) {
            this.educations.splice(index, 1);
        },

        // Skills
        skills: <?php echo json_encode($me_info['skills'] ?? []); ?>,
        newSkill: '',
                    addSkill() {
                        if (this.newSkill.trim()) {
                            const skill = this.newSkill.trim();
                            // Check for duplicates (case insensitive)
                            if (!this.skills.some(existingSkill => 
                                existingSkill.toLowerCase() === skill.toLowerCase()
                            )) {
                                this.skills.push(skill);
                            }
                            this.newSkill = '';
                        }
                    },
        removeSkill(index) {
            this.skills.splice(index, 1);
        },

        // Languages
        languages: <?php echo json_encode($me_info['languages'] ?? []); ?>,
        addLanguage() {
            this.languages.push({
                language: '',
                proficiency: ''
            });
        },
        removeLanguage(index) {
            this.languages.splice(index, 1);
        },

        // Hobbies
        hobbies: <?php echo json_encode($me_info['hobbies'] ?? []); ?>,
        newHobby: '',
                    addHobby() {
                        if (this.newHobby.trim()) {
                            const hobby = this.newHobby.trim();
                            // Check for duplicates (case insensitive)
                            if (!this.hobbies.some(existingHobby => 
                                existingHobby.toLowerCase() === hobby.toLowerCase()
                            )) {
                                this.hobbies.push(hobby);
                            }
                            this.newHobby = '';
                        }
                    },
        removeHobby(index) {
            this.hobbies.splice(index, 1);
        },

        // Certifications
        certifications: <?php echo json_encode($me_info['certifications'] ?? []); ?>,
        addCertification() {
            this.certifications.push({
                name: '',
                issuer: '',
                issue_date: '',
                expiry_date: ''
            });
        },
        removeCertification(index) {
            this.certifications.splice(index, 1);
        }
    }
}

// Tab Navigation
function profileTabs() {
    return {
        activeTab: 'personal_info',
        switchTab(tab) {
            // Hide all tabs first
            const allTabs = document.querySelectorAll('.profile-section');
            allTabs.forEach(tabElement => {
                tabElement.classList.add('hidden');
            });
            
            // Show selected tab
            const selectedTab = document.getElementById(tab);
            if (selectedTab) {
                selectedTab.classList.remove('hidden');
            }
            
            // Update navigation active state
            const allNavItems = document.querySelectorAll('.profile-nav-item');
            allNavItems.forEach(navItem => {
                navItem.classList.remove('bg-blue-50', 'text-blue-700');
                navItem.classList.add('text-gray-600');
            });
            
            const activeNavItem = document.querySelector(`[data-tab="${tab}"]`);
            if (activeNavItem) {
                activeNavItem.classList.remove('text-gray-600');
                activeNavItem.classList.add('bg-blue-50', 'text-blue-700');
            }
            
            this.activeTab = tab;
            
            // Force Alpine to re-render
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            });
        },
        init() {
            // Check for activetab from flash message
            const activetab = '<?php echo Session::flash("activetab") ?? ""; ?>';
            
            if (activetab) {
                // Switch to the specified tab
                this.switchTab(activetab);
            } else {
                // Initialize on mount - hide all tabs except first one
                const allTabs = document.querySelectorAll('.profile-section');
                allTabs.forEach((tabElement, index) => {
                    if (index === 0) {
                        tabElement.classList.remove('hidden');
                    } else {
                        tabElement.classList.add('hidden');
                    }
                });
                
                // Set first navigation item as active
                const allNavItems = document.querySelectorAll('.profile-nav-item');
                allNavItems.forEach((navItem, index) => {
                    if (index === 0) {
                        navItem.classList.remove('text-gray-600');
                        navItem.classList.add('bg-blue-50', 'text-blue-700');
                    } else {
                        navItem.classList.remove('bg-blue-50', 'text-blue-700');
                        navItem.classList.add('text-gray-600');
                    }
                });
            }
            
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            });
        }
    }
}

// Social Media Management
function socialMedia() {
    return {
        customSocials: <?php 
            // Get custom social media from me_info['socials'] excluding standard ones
            $standardSocials = ['facebook', 'linkedin', 'telegram', 'whatsapp'];
            $allSocials = $me_info['socials'] ?? [];
            $customSocials = [];
            foreach ($allSocials as $key => $value) {
                if (!in_array($key, $standardSocials) && !empty($value)) {
                    $customSocials[] = [
                        'name' => ucfirst($key),
                        'value' => $value
                    ];
                }
            }
            echo json_encode($customSocials);
        ?>,
        addCustomSocial() {
            this.customSocials.push({
                name: '',
                value: ''
            });
            // Re-initialize icons after adding new element
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            });
        },
        removeCustomSocial(index) {
            this.customSocials.splice(index, 1);
            // Re-initialize icons after removing element
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            });
        },
        // Check for duplicate social media names
        isDuplicateName(name, currentIndex) {
            return this.customSocials.some((social, index) => 
                index !== currentIndex && 
                social.name.toLowerCase().trim() === name.toLowerCase().trim() && 
                name.trim() !== ''
            );
        },
        // Get duplicate warning message
        getDuplicateWarning(name, currentIndex) {
            if (this.isDuplicateName(name, currentIndex)) {
                return '<?php __e('This social platform name already exists') ?>';
            }
            return '';
        }
    }
}

// Initialize Lucide icons when Alpine is ready
document.addEventListener('alpine:init', () => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    initFlatpickrPickers();
});

// Flatpickr initialization for date, datetime, and time inputs
function initFlatpickrPickers(root = document) {
    if (typeof flatpickr === 'undefined') return;

    // Date: Y-m-d with contextual min/max limits
    const dateInputs = root.querySelectorAll('input[type="date"], input[data-picker="date"]');
    dateInputs.forEach(el => {
        if (!el._flatpickr) {
            const now = new Date();
            const year = now.getFullYear();

            const opts = {
                dateFormat: 'Y-m-d',
                allowInput: true
            };

            const name = (el.getAttribute('name') || '').toLowerCase();
            const id = (el.getAttribute('id') || '').toLowerCase();

            // Birthday: between 130 years ago and 12 years ago
            if (id === 'birthday' || name === 'birthday') {
                opts.minDate = new Date(year - 130, now.getMonth(), now.getDate());
                opts.maxDate = new Date(year - 12, now.getMonth(), now.getDate());
            }

            // Work/Education Start/End Dates: last 100 years up to today
            else if (name.includes('[start_date]') || name.includes('[end_date]')) {
                opts.minDate = new Date(year - 100, now.getMonth(), now.getDate());
                opts.maxDate = now;
            }

            // Certifications Issue Date: last 100 years up to today
            else if (name.includes('[issue_date]')) {
                opts.minDate = new Date(year - 100, now.getMonth(), now.getDate());
                opts.maxDate = now;
            }

            flatpickr(el, opts);
        }
    });

    // DateTime: Y-m-d H:i:s
    const dateTimeInputs = root.querySelectorAll('input[data-picker="datetime"]');
    dateTimeInputs.forEach(el => {
        if (!el._flatpickr) {
            flatpickr(el, {
                enableTime: true,
                enableSeconds: true,
                time_24hr: true,
                dateFormat: 'Y-m-d H:i:s',
                allowInput: true
            });
        }
    });

    // Time: H:i:s
    const timeInputs = root.querySelectorAll('input[type="time"], input[data-picker="time"]');
    timeInputs.forEach(el => {
        if (!el._flatpickr) {
            flatpickr(el, {
                enableTime: true,
                noCalendar: true,
                enableSeconds: true,
                time_24hr: true,
                dateFormat: 'H:i:s',
                allowInput: true
            });
        }
    });
}

// Date input compatibility check and fallback
function checkDateInputSupport() {
    const dateInput = document.createElement('input');
    dateInput.type = 'date';
    // Check if browser supports date input
    if (dateInput.type !== 'date') {
        // Browser doesn't support date input
        console.warn('Browser does not support HTML5 date input');
        return false;
    }
    
    // Additional check for older browsers that claim support but don't work properly
    const testValue = '2024-01-01';
    dateInput.value = testValue;
    return dateInput.value === testValue;
}

// Enhanced date input fallback
function enhanceDateInputs() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    
    dateInputs.forEach(input => {
        // Add pattern attribute for better validation
        input.setAttribute('pattern', '[0-9]{4}-[0-9]{2}-[0-9]{2}');
        
        // Add title for better UX
        input.setAttribute('title', '<?php __e('Please enter date in YYYY-MM-DD format') ?>');
        
        // Add placeholder text for browsers that show it
        if (!input.placeholder) {
            input.placeholder = 'YYYY-MM-DD';
        }
        
        // Add validation message
        input.addEventListener('invalid', function(e) {
            if (e.target.validity.patternMismatch) {
                e.target.setCustomValidity('<?php __e('Please enter date in YYYY-MM-DD format') ?>');
            } else if (e.target.validity.valueMissing) {
                e.target.setCustomValidity('<?php __e('Please enter a valid date') ?>');
            } else {
                e.target.setCustomValidity('');
            }
        });
        
        // Clear custom validity on input
        input.addEventListener('input', function(e) {
            e.target.setCustomValidity('');
        });
    });
}

// Form submission loading states
document.addEventListener('DOMContentLoaded', function() {
    // Check date input support
    if (!checkDateInputSupport()) {
        console.warn('Date input not supported, using fallback behavior');
        // You could add a date picker library here if needed
    }
    
    // Enhance date inputs
    enhanceDateInputs();

    // Initialize Flatpickr for existing inputs
    if (typeof initFlatpickrPickers === 'function') {
        initFlatpickrPickers(document);
    }

    // Observe dynamic DOM updates to initialize Flatpickr on new inputs
    const container = document.querySelector('[x-ref="profileContainer"]') || document.body;
    if (window.MutationObserver && container && typeof initFlatpickrPickers === 'function') {
        const observer = new MutationObserver(mutations => {
            mutations.forEach(m => {
                if (m.addedNodes && m.addedNodes.length) {
                    m.addedNodes.forEach(node => {
                        if (node.nodeType === 1) {
                            initFlatpickrPickers(node);
                        }
                    });
                }
            });
        });
        observer.observe(container, { childList: true, subtree: true });
    }
    
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 mr-2 animate-spin"></i>Processing...';
                
                // Re-enable after 3 seconds as fallback
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }, 3000);
            }
        });
    });
});
</script>

<?php
Render::block('Backend\Footer', ['layout' => 'default']);
?>
