<?php include '_header.php'; ?>
<div style="padding: 20px; font-size: 16px; line-height: 1.5;">
    <h3>New Notification</h3>
    <p>Dear <?php echo $username; ?>,</p>
    <p>You have a new notification on your account:</p>
    <p><?php echo $message; ?></p>
    <p>Log in to your account to view more details.</p>
</div>
<?php include '_footer.php'; ?>