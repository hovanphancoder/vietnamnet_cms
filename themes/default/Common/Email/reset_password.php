<?php include '_header.php'; ?>
<div style="padding: 20px; font-size: 16px; line-height: 1.5;">
    <h3>Password Reset Request</h3>
    <p>Hi <?php echo $username; ?>,</p>
    <p>We received a request to reset your password. Click the button below to reset it:</p>
    <p style="text-align: center;">
        <a href="<?php echo $reset_link; ?>" style="display: inline-block; padding: 10px 20px; background-color: #007bff; color: #fff; text-decoration: none; border-radius: 5px;">Reset Password</a>
    </p>
    <p>If you didn't request a password reset, please ignore this email.</p>
</div>
<?php include '_footer.php'; ?>