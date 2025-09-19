<?php

use System\Libraries\Session;
use App\Libraries\Fastlang as Flang;
use System\Libraries\Render;

if (Session::has_flash('success')) {
    $success = Session::flash('success');
}
Render::block('Backend\Header', ['layout' => 'default', 'title' => $title ?? 'Terms']);
?>
<?php
function buildOptions($tree, $level = 0, $current_id = null, $parent = null)
{
    $output = '';

    foreach ($tree as $node) {
        // Tạo dấu gạch dựa theo cấp độ
        $prefix = str_repeat('-', $level);
        // Không hiển thị chính node hiện tại trong danh sách cha
        if ($node['id'] == $current_id) {
            continue;
        }

        // Thiết lập `selected` nếu node hiện tại là `parent_id`
        $selected = ($node['id'] == $parent) ? ' selected' : '';
        // Xây dựng option
        $output .= '<option value="' . $node['id'] . '"' . $selected . '>' . $prefix . ' ' . $node['name'] . '</option>';

        // Nếu có children, đệ quy để xây dựng tiếp các options
        if (!empty($node['children'])) {
            $output .= buildOptions($node['children'], $level + 1, $current_id, $parent);
        }
    }

    return $output;
}
// allTerm to options for languages tức là chuyển thành option select cho từng ngôn ngữ
$termsLanguages = [];
foreach ($allTerm as $term) {
    // nếu là current term thì không thêm vào
    if($term['id'] == $data['id']) {
        continue;
    }
    $termsLanguages[$term['lang']][] = $term;
}
?>
<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        <div class="card bg-card text-card-foreground border card-content rounded-lg shadow-md transition-shadow">
            <!-- Card Header: Page Title & Breadcrumb -->
            <div class="card-header">
                <h1 class="card-title text-2xl font-bold mb-4"><?= $title ?? Flang::_e('terms') ?></h1>
            </div>
            <!-- End Card Header -->
            <!-- Card Body: Content -->
            <div class="card-body">
                <!-- Notification Success -->
                <?php if (!empty($success)): ?>
                    <div class="bg-green-100 text-green-800 p-4 mb-4 rounded">
                        <?= htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                <!-- Notification Error -->
                <?php if (!empty($error)): ?>
                    <div class="bg-red-200 text-red-800 p-4 mb-4 rounded">
                        <?= htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                <div class="page-main flex flex-wrap py-5 px-4 md:px-8">
                    <div class="flex flex-wrap flex-col w-full">
                        <div class="w-full">
                            <form action="" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token; ?>">
                                <input type="hidden" name="type" value="<?= $data['type'] ?>">
                                <input type="hidden" name="posttype" value="<?= $data['posttype'] ?>">
                                <!-- Form Fields Container -->
                                <div class="flex flex-wrap -mx-2">
                                    <!-- name -->
                                    <div class="w-full md:w-1/2 px-2 mb-4 ">
                                        <label for="name" class="block font-bold mb-2"><?= Flang::_e('lable_name') ?><span class="text-red-500">*</span></label>
                                        <input type="text" value="<?= $data['name']; ?>" id="name" name="name" class="appearance-none border border-input rounded w-full form-control leading-tight focus:outline-none px-3 py-2 bg-background text-foreground" required>
                                        <?php if (!empty($errors['name'])): ?>
                                            <div class="text-red-800 mt-2 text-sm">
                                                <?php foreach ($errors['name'] as $error): ?>
                                                    <p><?= $error; ?></p>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <!-- slug -->
                                    <div class="w-full md:w-1/2 px-2 mb-4">
                                        <label for="slug" class="block font-bold mb-2"><?= Flang::_e('lable_slug') ?></label>
                                        <input type="text" value="<?= $data['slug']; ?>" id="slug" name="slug" class="appearance-none border border-input rounded w-full form-control leading-tight focus:outline-none px-3 py-2 bg-background text-foreground" required>
                                        <?php if (!empty($errors['slug'])): ?>
                                            <div class="text-red-800 mt-2 text-sm">
                                                <?php foreach ($errors['slug'] as $error): ?>
                                                    <p><?= $error; ?></p>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <!-- lang -->
                                    <div class="w-full md:w-1/2 px-2 mb-4">
                                        <label for="lang" class="block font-bold mb-2"><?= Flang::_e('lable_lang') ?></label>
                                        <select id="lang" name="lang" class="appearance-none border border-input rounded w-full form-control leading-tight focus:outline-none px-3 py-2 bg-background text-foreground" required>
                                            <?php foreach ($langActive as $item) { ?>
                                                <option value="<?= $item['code'] ?>" <?= $data['lang'] == $item['code'] ? 'selected' : '' ?>><?= $item['name'] ?></option>
                                            <?php } ?>
                                        </select>
                                        <?php if (!empty($errors['lang'])): ?>
                                            <div class="text-red-800 mt-2 text-sm">
                                                <?php foreach ($errors['lang'] as $error): ?>
                                                    <p><?= $error; ?></p>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <!-- parent -->
                                    <?php if (isset($currentTermInfo['hierarchical']) && $currentTermInfo['hierarchical']) { ?>
                                        <div class="w-full md:w-1/2 px-2 mb-4" id="parent-container">
                                            <label for="parent" class="block font-bold mb-2"><?= Flang::_e('lable_parent') ?></label>
                                            <select id="parent" name="parent" class="appearance-none border border-input rounded w-full form-control leading-tight focus:outline-none px-3 py-2 bg-background text-foreground">
                                                <?= buildOptions($tree, 0, $data['id'], $data['parent']) ?>
                                            </select>
                                        </div>
                                    <?php } ?>
                                    <!-- description -->
                                    <div class="w-full px-2 mb-4">
                                        <label for="description" class="block font-bold mb-2"><?= Flang::_e('lable_description') ?></label>
                                        <textarea id="description" name="description" class="appearance-none border border-input rounded w-full form-control leading-tight focus:outline-none px-3 py-2 bg-background text-foreground"><?= $data['description'] ?></textarea>
                                    </div>
                                </div>
                                <!-- SEO Title -->
                                <div class="w-full px-2 mb-4">
                                    <label for="seo_title" class="block font-bold mb-2"><?= Flang::_e('lable_seo_title') ?></label>
                                    <input type="text" id="seo_title" name="seo_title" value="<?= $data['seo_title'] ?>" class="appearance-none border border-input rounded w-full form-control leading-tight focus:outline-none px-3 py-2 bg-background text-foreground">
                                </div>
                                <!-- SEO Description -->
                                <div class="w-full px-2 mb-4">
                                    <label for="seo_desc" class="block font-bold mb-2"><?= Flang::_e('lable_seo_desc') ?></label>
                                    <textarea id="seo_desc" name="seo_desc" class="appearance-none border border-input rounded w-full form-control leading-tight focus:outline-none px-3 py-2 bg-background text-foreground"><?= $data['seo_desc'] ?></textarea>
                                </div>
                                <!-- ID Main (chỉ hiển thị khi ngôn ngữ khác ngôn ngữ mặc định) -->
                                <?php if ($data['lang'] !== $default_lang): ?>
                                    <div class="w-full px-2 mb-4" id="id-main-container">
                                        <label for="id_main" class="block font-bold mb-2"><?= Flang::_e('lable_id_main') ?></label>
                                        <select id="id_main" name="id_main" class="appearance-none border border-input rounded w-full form-control leading-tight focus:outline-none px-3 py-2 bg-background text-foreground">
                                            <option value="0"><?= Flang::_e('select_main_term') ?></option>
                                            <?php if (isset($mainterms)) {
                                                echo buildOptions($mainterms, 0, $data['id'], $data['id_main']);
                                            } ?>
                                        </select>
                                    </div>
                                <?php endif; ?>
                                <!-- Submit Button -->
                                <div class="flex items-center gap-2 flex-wrap">
                                    <button type="submit" class="custom-btn inline-flex items-center justify-center whitespace-nowrap font-medium bg-primary text-primary-foreground hover:bg-primary/90 rounded px-4 py-2 w-full md:w-fit flex gap-2 justify-center items-center [&>svg]:mb-0">
                                        <i data-lucide="upload-cloud" class="w-4 h-4"></i> <?= Flang::_e('btn_update') ?>
                                    </button>
                                    <a href="<?= admin_url('terms/delete/' . $data['id'] . '?posttype=' . $data['posttype'] . '&type=' . $data['type']); ?>" class="custom-btn inline-flex items-center justify-center whitespace-nowrap font-medium bg-danger text-danger-foreground hover:bg-danger/90 rounded px-4 py-2 w-full md:w-fit flex gap-2 justify-center items-center [&>svg]:mb-0" onclick="return confirm('<?= Flang::_e('confirm_delete') ?>')">
                                        <i data-lucide="trash" class="w-4 h-4"></i> <?= Flang::_e('btn_del') ?>
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div><!-- end card-body -->
        </div><!-- end card -->
    </div><!-- end pc-content -->
</div><!-- end pc-container -->
<style>
    .table td,
    .table th {
        padding: 1rem !important;
    }
</style>
<?php
Render::block('Backend\Footer', ['layout' => 'default']);
?>
<script>
    var admin_url = '<?= admin_url('terms'); ?>';
    var defaultLang = '<?php if (isset($default_lang)) {
                            echo $default_lang;
                        }; ?>';
    
    const dataTermsLanguages = <?= json_encode($termsLanguages) ?>;
    
    // Hàm cập nhật parent options
    function updateParentOptions(selectedLang) {
        const parentSelect = document.getElementById('parent');
        
        if (!parentSelect) return;
        
        // Xóa tất cả options hiện tại (trừ option đầu tiên)
        parentSelect.innerHTML = '<option value=""><?= Flang::_e('select_parent') ?></option>';
        
        // Thêm options từ ngôn ngữ được chọn
        if (dataTermsLanguages[selectedLang]) {
            dataTermsLanguages[selectedLang].forEach(function(term) {
                const option = document.createElement('option');
                option.value = term.id;
                option.textContent = term.name;
                parentSelect.appendChild(option);
            });
        }
    }
    
    // Load parent options khi trang được tải
    document.addEventListener('DOMContentLoaded', function() {
        const langSelect = document.getElementById('lang');
        if (langSelect) {
            updateParentOptions(langSelect.value);
        }
    });
    
    // Cập nhật parent options khi lang thay đổi
    document.getElementById('lang').addEventListener('change', function() {
        updateParentOptions(this.value);
    });
</script>