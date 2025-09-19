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
$posttype['fields'] = isset($posttype['fields']) && is_string($posttype['fields']) ? json_decode($posttype['fields'], true) : $fields_available;
$posttype['terms'] = isset($posttype['terms']) && is_string($posttype['terms']) ? json_decode($posttype['terms'], true) : [];
$posttype['languages'] = isset($posttype['languages']) && is_string($posttype['languages']) ? json_decode($posttype['languages'], true) : [];

$actionLink = admin_url('posttype/add');
if(isset($posttype['id']) && !empty($posttype['id'])) {
    $actionLink = admin_url('posttype/edit/' . $posttype['id']);
}

?>
<script defer="defer" src="<?php echo theme_assets('js/posttype.js', 'Backend') ?>"></script>
<link href="<?php echo theme_assets('css/posttype.css', 'Backend') ?>" rel="stylesheet">
<script>
    const errors = <?php echo json_encode($errors); ?>;
    const currentLanguage = '<?php echo APP_LANG; ?>';
    const allLanguages = <?php echo json_encode(array_keys(APP_LANGUAGES)); ?>;
    const allPostTypes = <?php echo json_encode($allPostTypes); ?>;
    const posttype = <?php echo json_encode($posttype); ?>;
    const isEditing = <?php echo isset($posttype['id']) && !empty($posttype['id']) ? 'true' : 'false'; ?>;
    const actionLink = <?php echo json_encode($actionLink); ?>;
    const csrf_token = '<?php echo $csrf_token; ?>';
    const data = <?php echo json_encode(['posttype' => $posttype, 'isEditing' => isset($posttype['id']) && !empty($posttype['id'])]); ?>;
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