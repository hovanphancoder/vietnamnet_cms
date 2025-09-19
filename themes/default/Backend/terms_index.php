<?php

use System\Libraries\Session;
use App\Libraries\Fastlang as Flang;
use System\Libraries\Render;
$breadcrumbs = array(
  [
      'name' => 'Dashboard',
      'url' => admin_url('home')
  ],
  [
      'name' => 'Terms',
      'url' => admin_url('terms'),
      'active' => true
  ]
);
Render::block('Backend\Header', ['layout' => 'default', 'title' => $title ?? 'Terms', 'breadcrumb' => $breadcrumbs]);
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
// Lấy các tham số GET
$search      = $_GET['q']        ?? '';
$limit       = $_GET['limit']    ?? 10;
$sort        = $_GET['sort']     ?? 'id';
$order       = $_GET['order']    ?? 'desc';
$type        = $_GET['type']     ?? 'default';
$posttype    = $_GET['posttype'] ?? 'default';
?>

  <div class="" x-data="{ 
    showForm: false,
    selectedItems: [], 
    isDeleting: false,
  
  toggleSelectAll() {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    const selectAllCheckbox = document.getElementById('selectAll');
    
    checkboxes.forEach(checkbox => {
      checkbox.checked = selectAllCheckbox.checked;
    });
    
    this.updateSelectedItems();
  },
  
  updateSelectedItems() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    this.selectedItems = Array.from(checkboxes).map(checkbox => checkbox.value);
    
    // Update select all checkbox state
    const allCheckboxes = document.querySelectorAll('.row-checkbox');
    const selectAllCheckbox = document.getElementById('selectAll');
    const allChecked = Array.from(allCheckboxes).every(checkbox => checkbox.checked);
    const someChecked = Array.from(allCheckboxes).some(checkbox => checkbox.checked);
    
    selectAllCheckbox.checked = allChecked;
    selectAllCheckbox.indeterminate = someChecked && !allChecked;
  },
  
  async deleteSelected() {
    if (this.selectedItems.length === 0) {
      alert('Please select items to delete');
      return;
    }
    
    if (!confirm('Are you sure you want to delete selected items?')) {
      return;
    }
    
    this.isDeleting = true;
    
    try {
      const formData = new FormData();
      formData.append('csrf_token', '<?= Session::csrf_token(600) ?>');
      formData.append('ids', JSON.stringify(this.selectedItems));
      formData.append('type', '<?= htmlspecialchars($type) ?>');
      formData.append('posttype', '<?= htmlspecialchars($posttype) ?>');
      
      const response = await fetch('<?= admin_url('terms/delete') ?>', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
      });
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      const data = await response.json();
      
      if (data.status === 'success') {
        window.location.reload();
      } else {
        alert(data.message || 'Error deleting items');
      }
    } catch (error) {
      console.error('Error:', error);
      alert('Network error occurred');
    } finally {
      this.isDeleting = false;
    }
  },
  
  generateSlug() {
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');
    
    if (nameInput && slugInput) {
      const name = nameInput.value;
      if (name && typeof url_slug === 'function') {
        slugInput.value = url_slug(name, {
          delimiter: '-',
          lowercase: true,
          limit: 50
        });
      }
    }
  }
}">

  <!-- Header & Filter -->
  <div class="flex flex-col gap-4">
    <div>
      <h1 class="text-2xl font-bold text-foreground">Terms Management</h1>
      <p class="text-muted-foreground">Manage terms and categories for your content</p>
    </div>

    <!-- Thông báo -->
    <?php if (Session::has_flash('success')): ?>
      <?php Render::block('Backend\Notification', ['layout' => 'default', 'type' => 'success', 'message' => Session::flash('success')]) ?>
    <?php endif; ?>
    <?php if (Session::has_flash('error')): ?>
      <?php Render::block('Backend\Notification', ['layout' => 'default', 'type' => 'error', 'message' => Session::flash('error')]) ?>
    <?php endif; ?>

    <!-- Search and Filter Section -->
    <div class="bg-card rounded-xl mb-4">
      <form method="GET" class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">
        <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center flex-1 w-full lg:w-auto">
          <div class="relative flex-1 min-w-[200px] w-full sm:w-auto">
            <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4"></i>
            <input 
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 pl-10" 
              placeholder="<?= Flang::_e('search_terms') ?? 'Search terms...' ?>" 
              name="q" 
              value="<?= htmlspecialchars($search) ?>"
              @keydown.enter="$event.target.closest('form').submit()"
            />
          </div>
          <div class="min-w-[100px] w-full sm:w-auto">
            <select name="limit" class="flex h-10 w-full items-center rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2" onchange="this.form.submit()">
              <option value="5" <?= ($limit == 5) ? 'selected' : '' ?>>5</option>
              <option value="10" <?= ($limit == 10) ? 'selected' : '' ?>>10</option>
              <option value="15" <?= ($limit == 15) ? 'selected' : '' ?>>15</option>
              <option value="20" <?= ($limit == 20) ? 'selected' : '' ?>>20</option>
              <option value="25" <?= ($limit == 25) ? 'selected' : '' ?>>25</option>
              <option value="50" <?= ($limit == 50) ? 'selected' : '' ?>>50</option>
              <option value="100" <?= ($limit == 100) ? 'selected' : '' ?>>100</option>
              <option value="200" <?= ($limit == 200) ? 'selected' : '' ?>>200</option>
            </select>
          </div>
        </div>
        
        <!-- Hidden inputs to preserve other params -->
        <?php if (!empty($type)) echo '<input type="hidden" name="type" value="' . htmlspecialchars($type) . '">'; ?>
        <?php if (!empty($posttype)) echo '<input type="hidden" name="posttype" value="' . htmlspecialchars($posttype) . '">'; ?>
        
        <div class="flex gap-2">
          <!-- Delete Selected Button -->
          <button 
            type="button"
            @click="deleteSelected()" 
            class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 h-10 px-4 py-2 whitespace-nowrap"
            :class="selectedItems.length > 0 ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-gray-200 text-gray-500 cursor-not-allowed'"
            :disabled="isDeleting || selectedItems.length === 0"
          >
            <i x-show="!isDeleting" data-lucide="trash2" class="h-4 w-4 mr-2"></i>
            <i x-show="isDeleting" data-lucide="loader-2" class="h-4 w-4 mr-2 animate-spin"></i>
            <span x-text="isDeleting ? 'Deleting...' : 'Delete Selected'"></span>
          </button>
          
          <!-- Add New Button -->
          <button 
            type="button"
            @click="showForm = !showForm" 
            class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2 whitespace-nowrap w-full lg:w-auto">
            <i x-show="!showForm" data-lucide="plus" class="h-4 w-4 mr-2"></i>
            <i x-show="showForm" data-lucide="x" class="h-4 w-4 mr-2"></i>
            <span x-text="showForm ? '<?= Flang::_e('hide_form') ?? 'Hide Form' ?>' : '<?= Flang::_e('add_term') ?? 'Add Term' ?>'"></span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Add Term Form -->
  <div x-show="showForm" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 transform scale-100" x-transition:leave-end="opacity-0 transform scale-95" class="bg-card rounded-xl mb-4 p-6 border">
    <form action="<?= admin_url('terms/add') ?>" method="POST" id="addTermForm">
      <input type="hidden" name="csrf_token" value="<?= Session::csrf_token(600) ?>">
      <input type="hidden" name="type" value="<?= $type ?? 'default' ?>">
      <input type="hidden" name="posttype" value="<?= $posttype ?? 'default' ?>">
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <!-- Name -->
        <div>
          <label for="name" class="block text-sm font-medium mb-2"><?= Flang::_e('lable_name') ?><span class="text-red-500">*</span></label>
          <input type="text" id="name" name="name" @input="generateSlug()" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" required>
        </div>
        
        <!-- Slug -->
        <div>
          <label for="slug" class="block text-sm font-medium mb-2"><?= Flang::_e('lable_slug') ?></label>
          <input type="text" id="slug" name="slug" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" required>
        </div>
        
        <!-- Language -->
        <div>
          <label for="lang" class="block text-sm font-medium mb-2"><?= Flang::_e('lable_lang') ?></label>
          <select id="lang" name="lang" class="flex h-10 w-full items-center rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2" required>
            <option value=""><?= Flang::_e('select_lang') ?></option>
            <?php foreach ($langActive as $lang) { ?>
              <option value="<?= $lang['code'] ?>" <?= $lang['code'] == APP_LANG ? 'selected' : '' ?>><?= $lang['name'] ?></option>
            <?php } ?>
          </select>
        </div>
        
        <!-- Parent -->
        <?php if (isset($currentTermInfo['hierarchical']) && $currentTermInfo['hierarchical']) { ?>
          <div id="parent-container" style="display: none;">
            <label for="parent" class="block text-sm font-medium mb-2"><?= Flang::_e('lable_parent') ?></label>
            <select id="parent" name="parent" class="flex h-10 w-full items-center rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
              <option value=""><?= Flang::_e('select_parent') ?></option>
            </select>
          </div>
        <?php } ?>
      </div>
      
      <!-- Description -->
      <div class="mb-4">
        <label for="description" class="block text-sm font-medium mb-2"><?= Flang::_e('lable_description') ?></label>
        <textarea id="description" name="description" rows="3" class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"></textarea>
      </div>
      
      <!-- SEO Fields -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
          <label for="seo_title" class="block text-sm font-medium mb-2"><?= Flang::_e('lable_seo_title') ?></label>
          <input type="text" id="seo_title" name="seo_title" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
        </div>
        
        <div>
          <label for="id_main" class="block text-sm font-medium mb-2"><?= Flang::_e('lable_id_main') ?></label>
          <select id="id_main" name="id_main" class="flex h-10 w-full items-center rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
            <option value="0"><?= Flang::_e('select_main_term') ?></option>
            <?php if (isset($mainterms)) {
              echo buildOptions($mainterms);
            } ?>
          </select>
        </div>
      </div>
      
      <!-- SEO Description -->
      <div class="mb-4">
        <label for="seo_desc" class="block text-sm font-medium mb-2"><?= Flang::_e('lable_seo_desc') ?></label>
        <textarea id="seo_desc" name="seo_desc" rows="2" class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"></textarea>
      </div>
      
      <!-- Submit Button -->
      <div class="flex justify-end gap-2">
        <button type="button" @click="showForm = false" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2">
          <?= Flang::_e('cancel') ?? 'Cancel' ?>
        </button>
        <button type="submit" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2">
          <i data-lucide="plus" class="h-4 w-4 mr-2"></i>
          <?= Flang::_e('add') ?>
        </button>
      </div>
    </form>
  </div>

  <!-- Bảng danh sách -->
  <div class="bg-card card-content !p-0 border overflow-hidden">
    <div class="overflow-x-auto">
      <div class="relative w-full overflow-auto">
        <table class="w-full caption-bottom text-sm">
          <thead class="[&_tr]:border-b">
            <tr class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
              <!-- Checkbox Select All -->
              <th class="px-4 py-3 text-center align-middle bg-menu-background-hover text-menu-text-hover font-medium w-12">
                <input type="checkbox" id="selectAll" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded" @change="toggleSelectAll()">
              </th>
              <th class="px-4 py-3 text-left align-middle bg-menu-background-hover text-menu-text-hover font-medium">ID</th>
              <th class="px-4 py-3 text-left align-middle bg-menu-background-hover text-menu-text-hover font-medium"><?= Flang::_e('table_name') ?></th>
              <th class="px-4 py-3 text-left align-middle bg-menu-background-hover text-menu-text-hover font-medium"><?= Flang::_e('table_slug') ?></th>
              <th class="px-4 py-3 text-left align-middle bg-menu-background-hover text-menu-text-hover font-medium"><?= Flang::_e('table_post_type') ?></th>
              <th class="px-4 py-3 text-left align-middle bg-menu-background-hover text-menu-text-hover font-medium"><?= Flang::_e('table_type') ?></th>
              <th class="px-4 py-3 text-left align-middle bg-menu-background-hover text-menu-text-hover font-medium"><?= Flang::_e('table_lang') ?></th>
              <th class="px-4 py-3 text-left align-middle bg-menu-background-hover text-menu-text-hover font-medium"><?= Flang::_e('table_parent') ?></th>
              <th class="px-4 py-3 text-center align-middle bg-menu-background-hover text-menu-text-hover font-medium"><?= Flang::_e('table_action') ?></th>
            </tr>
          </thead>
          <tbody class="[&_tr:last-child]:border-0">
            <?php 
            function renderTermRows($nodes, $level = 0) {
    foreach ($nodes as $node) {
                if (!$node) continue;
            ?>
                              <tr class="border-b transition-colors data-[state=selected]:bg-muted hover:bg-muted/50">
                  <!-- Checkbox -->
                  <td class="px-4 py-1 align-middle text-center">
                    <input type="checkbox" class="row-checkbox h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded" 
                           value="<?= $node['id'] ?>" @change="updateSelectedItems()">
                  </td>
                  <td class="px-4 py-1 align-middle font-medium text-foreground"><?= htmlspecialchars($node['id'] ?? 'N/A') ?></td>
                  <td class="px-4 py-1 align-middle text-foreground">
                    <a href="<?= admin_url('terms/edit/' . $node['id'] . '?posttype=' . $node['posttype'] . '&type=' . $node['type']); ?>" class="text-primary hover:underline hover:text-primary/80 transition-colors">
                      <?= str_repeat('&mdash; ', $level) . htmlspecialchars($node['name']); ?>
                    </a>
                  </td>
                <td class="px-4 py-1 align-middle text-foreground"><?= htmlspecialchars($node['slug']); ?></td>
                <td class="px-4 py-1 align-middle text-foreground"><?= htmlspecialchars($node['posttype']); ?></td>
                <td class="px-4 py-1 align-middle text-foreground"><?= htmlspecialchars($node['type']); ?></td>
                <td class="px-4 py-1 align-middle text-foreground"><?= htmlspecialchars($node['lang_name']); ?></td>
                <td class="px-4 py-1 align-middle text-foreground"><?= htmlspecialchars($node['parent_name'] ?? 'No'); ?></td>
                <td class="px-4 py-1 align-middle text-center">
                  <div class="flex items-center gap-1 justify-center">
                    <a href="<?= admin_url('terms/edit/' . $node['id'] . '?posttype=' . $node['posttype'] . '&type=' . $node['type']); ?>" class="inline-flex items-center justify-center whitespace-nowrap text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground rounded-md h-8 w-8 p-0" title="Edit Term">
                      <i data-lucide="square-pen" class="h-4 w-4"></i>
                    </a>
                    <a href="<?= admin_url('terms/delete/' . $node['id'] . '?posttype=' . $node['posttype'] . '&type=' . $node['type']); ?>" class="inline-flex items-center justify-center whitespace-nowrap text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground rounded-md h-8 w-8 p-0" onclick="return confirm('<?= Flang::_e('confirm_delete') ?>')" title="Delete Term">
                      <i data-lucide="trash2" class="h-4 w-4"></i>
                    </a>
                </div>
            </td>
        </tr>
<?php
        // Nếu có children, tiếp tục gọi đệ quy để render các children
        if (!empty($node['children'])) {
            renderTermRows($node['children'], $level + 1);
        }
    }
}
            
            if (!empty($tree)) {
              renderTermRows($tree);
        } else {
            ?>
              <tr><td colspan="9" class="text-center py-4 text-muted-foreground">No terms found.</td></tr>
                                            <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

<?php Render::block('Backend\Footer', ['layout' => 'default']); ?>
