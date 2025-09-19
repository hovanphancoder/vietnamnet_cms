<?php
App\Libraries\Fastlang::load('Homepage');
//Render::asset('js', 'js/home-index.js', ['area' => 'frontend', 'location' => 'footer']);

//Get Object Data for this Pages
$locale = APP_LANG.'_'.strtoupper(lang_country(APP_LANG));


get_template('_metas/meta_index', ['locale' => $locale]);
//....
//End Get Object Data

?>


<?php get_template('sections/home_index/home_banner'); ?>
<?php get_template('sections/home_index/home_editors_choices'); ?>
<?php get_template('sections/home_index/home_trending'); ?>
<?php get_template('sections/home_index/home_recently_game_updated'); ?>
<?php get_template('sections/home_index/home_recently_app_updated'); ?>

<?php get_footer(); ?>