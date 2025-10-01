<?php
App\Libraries\Fastlang::load('Homepage');
System\Libraries\Render::asset('css', 'css/home.css', ['area' => 'frontend', 'location' => 'head']);
//Get Object Data for this Pages
$locale = APP_LANG.'_'.strtoupper(lang_country(APP_LANG));

get_template('_metas/meta_index', ['locale' => $locale]);
//End Get Object Data
?>


<?php get_template('sections/home_index/home_main'); ?>

<?php get_footer(); ?>