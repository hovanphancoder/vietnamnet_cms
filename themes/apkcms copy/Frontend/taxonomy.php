<?php
App\Libraries\Fastlang::load('Homepage');
//Render::asset('js', 'js/home-index.js', ['area' => 'frontend', 'location' => 'footer']);
System\Libraries\Render::asset('css', 'css/categories.css', ['area' => 'frontend', 'location' => 'head']);

//Get Object Data for this Pages
$locale = APP_LANG.'_'.strtoupper(lang_country(APP_LANG));


get_template('_metas/meta_term', ['locale' => $locale]);
//....
//End Get Object Data

?>


<?php get_template('sections/taxonomy/taxonomy_main'); ?>

<?php get_footer(); ?>