<?php include '_header.php'; ?>
<div style="padding: 20px; font-size: 16px; line-height: 1.5;">
    <h3>Password Reset Request</h3>
    <p>Hi <?php echo $username; ?>,</p>
    <p>We received a request to reset your password. Click the button below to reset it:</p>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="<?php echo $reset_link; ?>" style="display: inline-block; padding: 15px 30px; background-color: #007bff; color: #fff; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: bold;">Reset Password</a>
    </div>
    
    <div style="margin: 20px 0; padding: 15px; background-color: #f8f9fa; border-left: 4px solid #007bff; border-radius: 4px;">
        <p style="margin: 0 0 10px 0; font-weight: bold;">Verification Code:</p>
        <div style="text-align: center; margin: 15px 0;">
            <span style="display: inline-block; padding: 15px 25px; background-color: #fff; border: 2px solid #007bff; border-radius: 5px; font-size: 24px; font-weight: bold; letter-spacing: 3px; color: #007bff; font-family: monospace;"><?php echo $reset_code; ?></span>
        </div>
        <p style="margin: 10px 0 0 0; font-size: 14px; color: #666;">You will need to enter this 6-digit code on the password reset page.</p>
    </div>
    
    <p style="font-size: 14px; color: #666;">This reset link and code will expire in 24 hours.</p>
    <p>If you didn't request a password reset, please ignore this email.</p>
</div>
<?php include '_footer.php'; ?>