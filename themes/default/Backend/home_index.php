<?php

namespace System\Libraries;

use App\Libraries\Fastlang;

Render::block('Backend\Header', ['layout' => 'default', 'title' => Fastlang::_e('dashboard')]);
?>
<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12">
                <?php get_template('HomeComponent/website-overview', [], 'Backend'); ?>
            </div>
        </div>
       
    </div>
</div>

<?php
Render::block('Backend\Footer', ['layout' => 'default']);
?>