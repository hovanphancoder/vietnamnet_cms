<?php
namespace System\Libraries;
if(!empty($allPostTypes)) {
    foreach($allPostTypes as &$item) {
        $item = [
            'id' => $item['id'],
            'name' => $item['name'],
            'slug' => $item['slug'],
            'menu' => $item['menu'],
            'status' => $item['status'],
        ];
    }
}
Render::block('Backend\Header', ['layout' => 'default', 'title' => $title ?? 'CMS Full Form']);

$errors = (!empty($errors)) ? json_encode($errors) : '{}';
$actionLink = admin_url('options/add');
if(isset($options[0]['id']) && !empty($options[0]['id'])) {
    $actionLink = admin_url('options/edit/' . $options[0]['id']);
    $deleteLink = admin_url('options/delete/' . $options[0]['id']);
    $data['delete_link'] = $deleteLink;
}
?>
<script defer="defer" src="<?php echo theme_assets('js/posttype.js', 'Backend') ?>"></script>
<link href="<?php echo theme_assets('css/posttype.css', 'Backend') ?>" rel="stylesheet">
<?php
// include file lang (langcode) in file Ptf-react.php
$langFile = PATH_APP . 'Languages/' . APP_LANG . '/Ptf-react.php';
if(!file_exists($langFile)) {
    $langFile = PATH_APP . 'Languages/en/Ptf-react.php';
}
if(file_exists($langFile)) {
    $translate = include $langFile;
}
?>
<script>
    const errors = <?php echo json_encode($errors); ?>;
    const currentLanguage = '<?php echo APP_LANG; ?>';
    const allLanguages = <?php echo json_encode(array_keys(APP_LANGUAGES)); ?>;
    const isEditing = <?php echo isset($posttype['id']) && !empty($posttype['id']) ? 'true' : 'false'; ?>;
    const actionLink = <?php echo json_encode($actionLink); ?>;
    const csrf_token = '<?php echo $csrf_token; ?>';
    const optionsData = <?php echo json_encode($data); ?>;
    const page = 'options';
    window.languageData = {
      name: "<?php echo lang_name(); ?>",
      code: "<?php echo lang_code(); ?>",
      flag: "<?php echo lang_flag(); ?>",
      t: <?php echo json_encode($translate); ?>
    };
</script>

<div class="pc-container">

    <div class="pc-content">
    <div id="app"></div>
    <div id="root"></div>
    </div>
</div>


<?php
Render::block('Backend\Footer', ['layout' => 'default']);
?>