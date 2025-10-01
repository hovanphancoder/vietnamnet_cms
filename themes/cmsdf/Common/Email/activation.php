<?php include '_header.php'; ?>
<div style="padding: 20px; font-size: 16px; line-height: 1.5;">
    <h3>Activate Your Account</h3>
    <p>Hi <?php echo $username; ?>,</p>
    <p>Thank you for registering with PhpFast! To complete your registration, please activate your account by clicking the button below:</p>
    <p style="text-align: center;">
        <a href="<?php echo $activation_link; ?>" style="display: inline-block; padding: 10px 20px; background-color: #007bff; color: #fff; text-decoration: none; border-radius: 5px;">Activate Account</a>
    </p>
    <?php if(isset($activation_no) && !empty($activation_no)) : ?>
    <p>Or enter activation code for verify account:</p>
    <p><center><h3><?php echo $activation_no; ?></h3></center></p>
    <p>If you did not sign up for this account, please ignore this email.</p>
    <?php endif; ?>

</div>
<?php include '_footer.php'; ?>