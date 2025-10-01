<?php

use System\Libraries\Session;
use App\Libraries\Fastlang as Flang;
use System\Libraries\Render;

// Load language files
Flang::load('Terms', APP_LANG);

$breadcrumbs = array(
  [
      'name' => __('Dashboard'),
      'url' => admin_url('home')
  ],
  [
      'name' => __('Terms'),
      'url' => admin_url('terms')
  ],
  [
      'name' => __('Edit Term'),
      'url' => admin_url('terms/edit'),
      'active' => true
  ]
);

Render::block('Backend\Header', ['layout' => 'default', 'title' => $title ?? 'Edit Term', 'breadcrumb' => $breadcrumbs]);

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

// Get posttype data for language switching (passed from controller)
$posttypeData['languages'] = is_string($posttypeData['languages']) ? json_decode($posttypeData['languages'], true) : $posttypeData['languages'];
?>
<div class="" x-data="{ 
  generateSlug() {
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');
    
    if (nameInput && slugInput) {
      const name = nameInput.value;
      if (name) {
        // Check if url_slug function is available
        if (typeof url_slug === 'function') {
          slugInput.value = url_slug(name, {
            delimiter: '-',
            lowercase: true,
            limit: 50
          });
        } else {
          // Fallback: simple slug generation
          slugInput.value = name
            .toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim('-')
            .substring(0, 50);
        }
      }
    }
  }
}">

  <!-- Header -->
  <div class="flex flex-col gap-4">
    <div>
      <h1 class="text-2xl font-bold text-foreground"><?= __('Edit Term') ?></h1>
      <p class="text-muted-foreground"><?= __('Edit term information and settings') ?></p>
    </div>
    
    <!-- Language Switch -->
    <div class="flex items-center gap-2 mb-2">
      <?php 
      // Tạo array để check term đã tồn tại chưa
      $existingTerms = [];
      foreach($allTerm as $term) {
        if($term['id_main'] == $data['id_main']) {
          $existingTerms[$term['lang']] = $term;
        }
      }
      ?>
      <?php foreach($posttypeData['languages'] as $langcode): ?>
        <?php if($langcode !== $data['lang']): ?>
          <?php if(isset($existingTerms[$langcode])): ?>
            <!-- Đã có term cho ngôn ngữ này - Edit -->
            <?php $langUrlAction = admin_url('terms/edit/' . $existingTerms[$langcode]['id']) . '?posttype=' . $data['posttype'] . '&type=' . $data['type']; ?>
            <a href="<?= $langUrlAction; ?>" class="inline-flex items-center px-3 py-2 rounded-md border border-input bg-background hover:bg-accent hover:text-accent-foreground transition-colors">
              <span class="text-sm font-medium uppercase"><?= $langcode ?></span>
              <span class="ml-2">
                <i data-lucide="edit" class="h-4 w-4"></i>
              </span>
            </a>
          <?php else: ?>
            <!-- Chưa có term cho ngôn ngữ này - Add -->
            <?php $langUrlAction = admin_url('terms/add') . '?posttype=' . $data['posttype'] . '&type=' . $data['type'] . '&post_lang=' . $langcode . '&mainterm=' . $data['id_main']; ?>
            <a href="<?= $langUrlAction; ?>" class="inline-flex items-center px-3 py-2 rounded-md border border-input bg-background hover:bg-accent hover:text-accent-foreground transition-colors">
              <span class="text-sm font-medium uppercase"><?= $langcode ?></span>
              <span class="ml-2">
                <i data-lucide="plus" class="h-4 w-4"></i>
              </span>
            </a>
          <?php endif; ?>
        <?php else: ?>
          <!-- Ngôn ngữ hiện tại -->
          <div class="inline-flex items-center px-3 py-2 rounded-md bg-primary text-primary-foreground shadow-sm">
            <span class="text-sm font-medium uppercase"><?= $langcode ?></span>
            <span class="ml-2">
              <i data-lucide="check-circle" class="h-4 w-4"></i>
            </span>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
    
    <!-- Thông báo -->
    <?php if (Session::has_flash('success')): ?>
      <?php Render::block('Backend\Notification', ['layout' => 'default', 'type' => 'success', 'message' => Session::flash('success')]) ?>
    <?php endif; ?>
    <?php if (Session::has_flash('error')): ?>
      <?php Render::block('Backend\Notification', ['layout' => 'default', 'type' => 'error', 'message' => Session::flash('error')]) ?>
    <?php endif; ?>
    <?php if (!empty($errors)):
      foreach($errors as $key => $error):
        // nối chuỗi key và các lỗi bên trong key: lỗi 1, lỗi 2
        $mess = $key . ': ' . implode(', ', $error);
        Render::block('Backend\Notification', ['layout' => 'default', 'type' => 'error', 'message' => $mess]);
      endforeach;
    endif;
      ?>



  </div>

  <!-- Form Container -->
  <div class="bg-card rounded-xl mb-4 p-6 border">
    <form action="<?= admin_url('terms/edit/' . $data['id']) ?>" method="POST" id="editTermForm">
      <input type="hidden" name="csrf_token" value="<?= $csrf_token; ?>">
      <input type="hidden" name="type" value="<?= $data['type'] ?>">
      <input type="hidden" name="posttype" value="<?= $data['posttype'] ?>">
      <input type="hidden" name="lang" value="<?= $data['lang'] ?>">
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <!-- Name -->
        <div>
          <label for="name" class="block text-sm font-medium mb-2"><?= Flang::_e('lable_name') ?><span class="text-red-500">*</span></label>
          <input 
            type="text" 
            id="name" 
            name="name" 
            value="<?= $data['name'] ?>"
            @input="generateSlug()" 
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" 
            required
            placeholder="<?= __('Enter term name') ?>"
          >
          <?php if (!empty($errors['name'])): ?>
            <div class="text-red-600 mt-1 text-sm">
              <?php foreach ($errors['name'] as $error): ?>
                <p><?= $error; ?></p>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
        
        <!-- Slug -->
        <div>
          <label for="slug" class="block text-sm font-medium mb-2"><?= Flang::_e('lable_slug') ?><span class="text-red-500">*</span></label>
          <input 
            type="text" 
            id="slug" 
            name="slug" 
            value="<?= $data['slug'] ?>"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" 
            required
            placeholder="<?= __('Enter term slug') ?>"
          >
          <?php if (!empty($errors['slug'])): ?>
            <div class="text-red-600 mt-1 text-sm">
              <?php foreach ($errors['slug'] as $error): ?>
                <p><?= $error; ?></p>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
        
        <!-- Status -->
        <div>
          <label for="status" class="block text-sm font-medium mb-2"><?= __('Status') ?></label>
          <select 
            id="status" 
            name="status" 
            class="flex h-10 w-full items-center rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
          >
            <option value="active" <?= ($data['status'] ?? 'active') === 'active' ? 'selected' : '' ?>><?= __('Active') ?></option>
            <option value="inactive" <?= ($data['status'] ?? 'active') === 'inactive' ? 'selected' : '' ?>><?= __('Inactive') ?></option>
          </select>
        </div>
        
        <!-- Parent -->
        <?php if (isset($currentTermInfo['hierarchical']) && $currentTermInfo['hierarchical']): ?>
          <div id="parent-container">
            <label for="parent" class="block text-sm font-medium mb-2"><?= Flang::_e('lable_parent') ?></label>
            <select 
              id="parent" 
              name="parent" 
              class="flex h-10 w-full items-center rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
            >
              <option value=""><?= Flang::_e('select_parent') ?></option>
              <?= buildOptions($tree, 0, $data['id'], $data['parent']) ?>
            </select>
          </div>
        <?php endif; ?>
      </div>
      
      <!-- Description -->
      <div class="mb-4">
        <label for="description" class="block text-sm font-medium mb-2"><?= Flang::_e('lable_description') ?></label>
        <textarea 
          id="description" 
          name="description" 
          rows="3" 
          class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
          placeholder="<?= __('Enter term description') ?>"
        ><?= $data['description'] ?></textarea>
      </div>
      
      <!-- SEO Fields -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
          <label for="seo_title" class="block text-sm font-medium mb-2"><?= Flang::_e('lable_seo_title') ?></label>
          <input 
            type="text" 
            id="seo_title" 
            name="seo_title" 
            value="<?= $data['seo_title'] ?>"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
            placeholder="<?= __('Enter SEO title') ?>"
          >
        </div>
        
        <div>
          <label for="seo_desc" class="block text-sm font-medium mb-2"><?= Flang::_e('lable_seo_desc') ?></label>
          <textarea 
            id="seo_desc" 
            name="seo_desc" 
            rows="2" 
            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
            placeholder="<?= __('Enter SEO description') ?>"
          ><?= $data['seo_desc'] ?></textarea>
        </div>
      </div>
      
      <!-- ID Main (hidden field - không cho sửa) -->
      <input type="hidden" name="id_main" value="<?= $data['id_main'] ?>">
      
      <!-- Submit Buttons -->
      <div class="flex justify-end gap-2">
        <a 
          href="<?= admin_url('terms/?posttype=' . $data['posttype'] . '&type=' . $data['type'] . '&post_lang=' . $data['lang']) ?>" 
          class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"
        >
          <i data-lucide="arrow-left" class="h-4 w-4 mr-2"></i>
          <?= __('Cancel') ?>
        </a>
        <a 
          href="<?= admin_url('terms/delete/' . $data['id'] . '?posttype=' . $data['posttype'] . '&type=' . $data['type']); ?>" 
          class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-red-600 hover:text-white h-10 px-4 py-2"
          onclick="return confirm('<?= Flang::_e('confirm_delete') ?>')"
        >
          <i data-lucide="trash2" class="h-4 w-4 mr-2"></i>
          <?= __('Delete') ?>
        </a>
        <button 
          type="submit" 
          class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
        >
          <i data-lucide="save" class="h-4 w-4 mr-2"></i>
          <?= Flang::_e('Update') ?>
        </button>
      </div>
    </form>
  </div>

</div>
<script>
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
        
        // Test url_slug function availability
        if (!(typeof url_slug === 'function')) {
            console.warn('url_slug function is not available');
        }
    });
    
    // Cập nhật parent options khi lang thay đổi
    document.getElementById('lang').addEventListener('change', function() {
        updateParentOptions(this.value);
    });
</script>

<?php Render::block('Backend\Footer', ['layout' => 'default']); ?>