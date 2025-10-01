<?php

namespace System\Libraries;

use App\Libraries\Fastlang;

Render::block('Backend\Head', ['layout' => 'default', 'title' => Fastlang::_e('Forgot Password')]);
?>
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50">
    <div class="container relative h-screen flex-col items-center justify-center md:grid lg:max-w-none lg:grid-cols-2 lg:px-0">

        <!-- Left Panel - Branding -->
        <?php echo Render::html('Common/Auth/auth-left'); ?>
            <div class="auth-right">
                <div class="auth-form">
                    <h2><?php echo $title; ?></h2>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="form-group">
                            <label for="reset_code">Enter Reset Code</label>
                            <input type="text" 
                                id="reset_code" 
                                name="reset_code" 
                                class="form-control" 
                                placeholder="Enter 6-digit code" 
                                maxlength="6" 
                                pattern="[0-9]{6}"
                                required 
                                style="text-align: center; font-size: 18px; letter-spacing: 2px; font-weight: bold;">
                            <small class="form-text text-muted">
                                Please enter the 6-digit code sent to your email.
                            </small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">
                            Verify Code
                        </button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <a href="<?php echo auth_url('forgot'); ?>" class="text-muted">
                            Back to Forgot Password
                        </a>
                    </div>
                </div>
            </div>

<script>
// Auto-format input to numbers only and limit to 6 digits
document.getElementById('reset_code').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '').substring(0, 6);
});
</script>


<!-- <script>
// Auto-format input to numbers only and limit to 6 digits
document.addEventListener('DOMContentLoaded', function() {
    const resetCodeInput = document.querySelector('input[name="reset_code"]');
    if (resetCodeInput) {
        resetCodeInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').substring(0, 6);
        });
    }
});
</script> -->

<?php
Render::block('Backend\Footer', ['layout' => 'default']);
?>
