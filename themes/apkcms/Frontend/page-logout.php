<?php
use System\Libraries\Session;
    setcookie('cmsff_logged', '', time()-1, '/');
    Session::del('user_id');
    Session::del('role');
    Session::del('permissions');
    
    if (isset($_COOKIE['cmsff_token'])){
        setcookie('cmsff_token', '', time()-1, '/');
    }
?>
<script>
    localStorage.removeItem('user');
    window.location.href = '<?= base_url() ?>';
</script>

