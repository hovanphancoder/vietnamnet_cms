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

                <?php if ($error = Session::flash('error')): ?>
                    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
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
                    <div class="bg-green-50 border border-green-200 rounded-xl p-4">
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



                <!-- Registration Form -->
                <form class="space-y-4" action="<?php echo auth_url('register'); ?>" method="post" id="registerForm">
                    <input type="hidden" name="csrf_token" value="<?php echo Session::csrf_token(600); ?>">
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

                    <!-- Register Button -->
                    <button
                         type="submit"
                        id="register"
                        class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold rounded-2xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-300">
                        <i data-lucide="user-plus" class="w-4 h-4"></i>
                        <?php __e('Register Button') ?>
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
// document.addEventListener('DOMContentLoaded', function() {
//     const registerButton = document.getElementById('register');
//     const registerForm = document.getElementById('registerForm');
//     const fullnameInput = document.getElementById('fullname');
//     const usernameInput = document.getElementById('username');
//     const emailInput = document.getElementById('email');
//     const passwordInput = document.getElementById('password');
//     const passwordRepeatInput = document.getElementById('password_repeat');
//     const termsCheckbox = document.getElementById('terms');

//     // Lấy CSRF token
//     let csrfToken = '';
    
//     // Lấy CSRF token khi trang load
//     fetch('/api/v2/auth/register')
//         .then(response => response.json())
//         .then(data => {
//             if (data.success && data.data.csrf_token) {
//                 csrfToken = data.data.csrf_token;
//             }
//         })
//         .catch(error => {
//             console.error('Error getting CSRF token:', error);
//         });

//     registerButton.addEventListener('click', function(e) {
//         e.preventDefault();
        
//         // Kiểm tra validation cơ bản
//         if (!validateForm()) {
//             return;
//         }

//         // Disable button và hiển thị loading
//         registerButton.disabled = true;
//         registerButton.innerHTML = `
//             <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
//                 <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
//                 <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
//             </svg>
//             Đang đăng ký...
//         `;

//         // Chuẩn bị dữ liệu
//         const formData = new FormData();
//         formData.append('username', usernameInput.value.trim());
//         formData.append('fullname', fullnameInput.value.trim());
//         formData.append('email', emailInput.value.trim());
//         formData.append('password', passwordInput.value);
//         formData.append('password_repeat', passwordRepeatInput.value);
//         formData.append('csrf_token', csrfToken);

//         // Gửi request đăng ký
//         fetch('/api/v2/auth/register', {
//             method: 'POST',
//             body: formData
//         })
//         .then(response => response.json())
//         .then(data => {
//             if (data.success) {
//                 // Đăng ký thành công
//                 showMessage('Đăng ký thành công! Vui lòng kiểm tra email để kích hoạt tài khoản.', 'success');
                
//                 // Reset form
//                 registerForm.reset();
                
//                 // Redirect sau 2 giây
//                 setTimeout(() => {
//                     window.location.href = '/account/login';
//                 }, 2000);
//             } else {
//                 // Hiển thị lỗi
//                 showMessage(data.message || 'Có lỗi xảy ra khi đăng ký', 'error');
                
//                 // Hiển thị chi tiết lỗi nếu có
//                 if (data.data && typeof data.data === 'object') {
//                     Object.keys(data.data).forEach(field => {
//                         const input = document.getElementById(field);
//                         if (input) {
//                             input.classList.add('border-red-500');
//                             showFieldError(input, data.data[field]);
//                         }
//                     });
//                 }
//             }
//         })
//         .catch(error => {
//             console.error('Error:', error);
//             showMessage('Có lỗi xảy ra khi kết nối server', 'error');
//         })
//         .finally(() => {
//             // Reset button
//             registerButton.disabled = false;
//             registerButton.innerHTML = `
//                 <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
//                     <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
//                     <circle cx="9" cy="7" r="4"></circle>
//                     <line x1="19" x2="19" y1="8" y2="14"></line>
//                     <line x1="22" x2="16" y1="11" y2="11"></line>
//                 </svg>
//                 Đăng ký
//             `;
//         });
//     });

//     function validateForm() {
//         let isValid = true;
        
//         // Clear previous errors
//         clearErrors();
        
//         // Validate fullname
//         if (!fullnameInput.value.trim()) {
//             showFieldError(fullnameInput, 'Họ tên không được để trống');
//             isValid = false;
//         } else if (fullnameInput.value.trim().length < 6) {
//             showFieldError(fullnameInput, 'Họ tên phải có ít nhất 6 ký tự');
//             isValid = false;
//         }
        
//         // Validate username
//         if (!usernameInput.value.trim()) {
//             showFieldError(usernameInput, 'Tên đăng nhập không được để trống');
//             isValid = false;
//         } else if (usernameInput.value.trim().length < 6) {
//             showFieldError(usernameInput, 'Tên đăng nhập phải có ít nhất 6 ký tự');
//             isValid = false;
//         } else if (!/^[a-zA-Z0-9@._]+$/.test(usernameInput.value.trim())) {
//             showFieldError(usernameInput, 'Tên đăng nhập chỉ được chứa chữ, số và ký tự @, ., _');
//             isValid = false;
//         }
        
//         // Validate email
//         if (!emailInput.value.trim()) {
//             showFieldError(emailInput, 'Email không được để trống');
//             isValid = false;
//         } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value.trim())) {
//             showFieldError(emailInput, 'Email không hợp lệ');
//             isValid = false;
//         }
        
//         // Validate password
//         if (!passwordInput.value) {
//             showFieldError(passwordInput, 'Mật khẩu không được để trống');
//             isValid = false;
//         } else if (passwordInput.value.length < 6) {
//             showFieldError(passwordInput, 'Mật khẩu phải có ít nhất 6 ký tự');
//             isValid = false;
//         }
        
//         // Validate password repeat
//         if (!passwordRepeatInput.value) {
//             showFieldError(passwordRepeatInput, 'Xác nhận mật khẩu không được để trống');
//             isValid = false;
//         } else if (passwordInput.value !== passwordRepeatInput.value) {
//             showFieldError(passwordRepeatInput, 'Mật khẩu xác nhận không khớp');
//             isValid = false;
//         }
        
//         // Validate terms
//         if (!termsCheckbox.checked) {
//             showMessage('Vui lòng đồng ý với điều khoản sử dụng', 'error');
//             isValid = false;
//         }
        
//         return isValid;
//     }
    
//     function showFieldError(input, message) {
//         input.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-500/20');
        
//         // Tạo hoặc cập nhật thông báo lỗi
//         let errorDiv = input.parentNode.querySelector('.error-message');
//         if (!errorDiv) {
//             errorDiv = document.createElement('div');
//             errorDiv.className = 'error-message text-red-500 text-xs mt-1';
//             input.parentNode.appendChild(errorDiv);
//         }
//         errorDiv.textContent = message;
//     }
    
//     function clearErrors() {
//         const inputs = [fullnameInput, usernameInput, emailInput, passwordInput, passwordRepeatInput];
//         inputs.forEach(input => {
//             input.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500/20');
//             const errorDiv = input.parentNode.querySelector('.error-message');
//             if (errorDiv) {
//                 errorDiv.remove();
//             }
//         });
//     }
    
//     function showMessage(message, type) {
//         // Tạo thông báo
//         const messageDiv = document.createElement('div');
//         messageDiv.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm ${
//             type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
//         }`;
//         messageDiv.innerHTML = `
//             <div class="flex items-center">
//                 <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
//                     ${type === 'success' 
//                         ? '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>'
//                         : '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>'
//                     }
//                 </svg>
//                 <span>${message}</span>
//             </div>
//         `;
        
//         document.body.appendChild(messageDiv);
        
//         // Tự động ẩn sau 5 giây
//         setTimeout(() => {
//             messageDiv.remove();
//         }, 5000);
//     }
// });
</script>

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
