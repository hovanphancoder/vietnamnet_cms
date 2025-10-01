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
    'url' => admin_url('terms'),
    'active' => true
  ]
);
Render::block('Backend\Header', ['layout' => 'default', 'title' => $title, 'breadcrumb' => $breadcrumbs]);

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
  $termsLanguages[$term['lang']][] = $term;
}
// Lấy các tham số GET
$search      = $_GET['q']        ?? '';
$limit       = $_GET['limit']    ?? 10;
$sort        = $_GET['sort']     ?? 'id';
$order       = $_GET['order']    ?? 'desc';
$type        = $_GET['type']     ?? 'default';
$posttype    = $_GET['posttype'] ?? 'default';
$post_lang   = $_GET['post_lang'] ?? 'default';
?>

<div class="" x-data="{ 
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
      alert('<?= __('Please select items to delete') ?>');
      return;
    }
    
    if (!confirm('<?= __('Are you sure you want to delete selected items?') ?>')) {
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
        alert(data.message || '<?= __('Error deleting items') ?>');
      }
    } catch (error) {
      console.error('Error:', error);
      alert('<?= __('Network error occurred') ?>');
    } finally {
      this.isDeleting = false;
    }
  }
}">

  <!-- Header & Filter -->
  <div class="flex flex-col gap-4">
    <div>
      <h1 class="text-2xl font-bold text-foreground"><?= __('Terms Management') ?></h1>
      <p class="text-muted-foreground"><?= __('Manage terms and categories for your content') ?></p>
    </div>

    <!-- Thông báo -->
    <?php if (Session::has_flash('success')): ?>
      <?php Render::block('Backend\Notification', ['layout' => 'default', 'type' => 'success', 'message' => Session::flash('success')]) ?>
    <?php endif; ?>
    <?php if (Session::has_flash('error')): ?>
      <?php Render::block('Backend\Notification', ['layout' => 'default', 'type' => 'error', 'message' => Session::flash('error')]) ?>
    <?php endif; ?>

    <!-- box switch language -->
    <div class="flex items-center gap-2">
      <?php if (!empty($posttypeData['languages'])): ?>
        <?php $currentLang = is_string($posttypeData['languages']) ? json_decode($posttypeData['languages'], true) : $posttypeData['languages']; ?>
        <?php foreach ($currentLang as $langcode): ?>
          <?php if ($langcode !== $post_lang): ?>
            <a href="<?= admin_url('terms?posttype=' . $posttype . '&type=' . $type . '&post_lang=' . $langcode) ?>" class="inline-flex items-center px-3 py-2 rounded-md border border-input bg-background hover:bg-accent hover:text-accent-foreground transition-colors">
              <span class="text-sm font-medium uppercase"><?= $langcode ?></span>
            </a>
          <?php else: ?>
            <div class="inline-flex items-center px-3 py-2 rounded-md bg-primary text-primary-foreground shadow-sm">
              <span class="text-sm font-medium uppercase"><?= $langcode ?></span>
              <span class="ml-2">
                <i data-lucide="check-circle" class="h-4 w-4"></i>
              </span>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Search and Filter Section -->
    <div class="bg-card rounded-xl mb-4">
      <form method="GET" class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">
        <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center flex-1 w-full lg:w-auto">
          <div class="relative flex-1 min-w-[200px] w-full sm:w-auto">
            <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4"></i>
            <input
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 pl-10"
              placeholder="<?= __('Search') ?>..."
              name="q"
              value="<?= htmlspecialchars($search) ?>"
              @keydown.enter="$event.target.closest('form').submit()" />
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
            :disabled="isDeleting || selectedItems.length === 0">
            <i x-show="!isDeleting" data-lucide="trash2" class="h-4 w-4 mr-2"></i>
            <i x-show="isDeleting" data-lucide="loader-2" class="h-4 w-4 mr-2 animate-spin"></i>
            <span x-text="isDeleting ? '<?= __('Deleting...') ?>' : '<?= __('Delete Selected') ?>'"></span>
          </button>

          <!-- Add New Button -->
          <a
            href="<?= admin_url('terms/add?posttype=' . $posttype . '&type=' . $type . '&post_lang=' . ($post_lang)) ?>"
            class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2 whitespace-nowrap w-full lg:w-auto">
            <i data-lucide="plus" class="h-4 w-4 mr-2"></i>
            <span><?= __('Add Term')?></span>
          </a>
        </div>
      </form>
    </div>
  </div>


  <!-- Bảng danh sách -->
  <div class="bg-card card-content !p-0 border overflow-hidden">
    <div class="overflow-x-auto">
      <div class="relative w-full overflow-auto">
        <table class="w-full caption-bottom text-sm table-fixed">
          <thead class="[&_tr]:border-b">
            <tr class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
              <!-- Checkbox Select All -->
              <th class="px-4 py-3 text-center align-middle bg-menu-background-hover text-menu-text-hover font-medium w-12">
                <input type="checkbox" id="selectAll" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded" @change="toggleSelectAll()">
              </th>
              <th class="px-4 py-3 text-left align-middle bg-menu-background-hover text-menu-text-hover font-medium w-16 whitespace-nowrap"><?= __('ID') ?></th>
              <th class="px-4 py-3 text-left align-middle bg-menu-background-hover text-menu-text-hover font-medium m-w-40 whitespace-nowrap"><?= __('Name') ?></th>
              <th class="px-4 py-3 text-left align-middle bg-menu-background-hover text-menu-text-hover font-medium m-w-24 whitespace-nowrap"><?= __('Slug') ?></th>
              <th class="px-4 py-3 text-left align-middle bg-menu-background-hover text-menu-text-hover font-medium m-w-24 whitespace-nowrap"><?= __('Post Type') ?></th>
              <th class="px-4 py-3 text-left align-middle bg-menu-background-hover text-menu-text-hover font-medium w-24 whitespace-nowrap"><?= __('Type') ?></th>
              <th class="px-4 py-3 text-left align-middle bg-menu-background-hover text-menu-text-hover font-medium m-w-80 whitespace-nowrap"><?= __('Languages') ?></th>
              <th class="px-4 py-3 text-left align-middle bg-menu-background-hover text-menu-text-hover font-medium m-w-32 whitespace-nowrap"><?= __('Parent') ?></th>
              <th class="px-4 py-3 text-center align-middle bg-menu-background-hover text-menu-text-hover font-medium w-24 whitespace-nowrap"><?= __('Status') ?></th>
              <th class="px-4 py-3 text-center align-middle bg-menu-background-hover text-menu-text-hover font-medium w-24 whitespace-nowrap"><?= __('Actions') ?></th>
            </tr>
          </thead>
          <tbody class="[&_tr:last-child]:border-0">
            <?php
            function renderTermRows($nodes, $level = 0, $currentLang = [], $post_lang = '')
            {
              foreach ($nodes as $node) {
                if (!$node) continue;
            ?>
                <tr class="border-b transition-colors data-[state=selected]:bg-muted hover:bg-muted/50">
                  <!-- Checkbox -->
                  <td class="px-4 py-1 align-middle text-center">
                    <input type="checkbox"
                      class="row-checkbox h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                      value="<?= $node['id'] ?>"
                      @change="updateSelectedItems()">
                  </td>

                  <!-- ID -->
                  <td class="px-4 py-1 align-middle font-medium text-foreground whitespace-nowrap">
                    <?= htmlspecialchars($node['id'] ?? 'N/A') ?>
                  </td>

                  <!-- Name -->
                  <td class="px-4 py-1 align-middle text-foreground whitespace-nowrap truncate max-w-[300px]"
                    title="<?= htmlspecialchars($node['name']) ?>">
                    <a href="<?= admin_url('terms/edit/' . $node['id'] . '?posttype=' . $node['posttype'] . '&type=' . $node['type']); ?>"
                      class="text-primary hover:underline hover:text-primary/80 transition-colors">
                      <?= str_repeat('&mdash; ', $level) . htmlspecialchars($node['name']); ?>
                    </a>
                  </td>

                  <!-- Slug -->
                  <td class="px-4 py-1 align-middle text-foreground whitespace-nowrap truncate max-w-[120px]"
                    title="<?= htmlspecialchars($node['slug']) ?>">
                    <?= htmlspecialchars($node['slug']); ?>
                  </td>

                  <!-- Post Type -->
                  <td class="px-4 py-1 align-middle text-foreground whitespace-nowrap truncate max-w-[80px]"
                    title="<?= htmlspecialchars($node['posttype']) ?>">
                    <?= htmlspecialchars($node['posttype']); ?>
                  </td>

                  <!-- Type -->
                  <td class="px-4 py-1 align-middle text-foreground whitespace-nowrap truncate max-w-[80px]"
                    title="<?= htmlspecialchars($node['type']) ?>">
                    <?= htmlspecialchars($node['type']); ?>
                  </td>

                  <!-- Language -->
                  <td class="px-4 py-1 align-middle text-foreground whitespace-nowrap truncate max-w-[60px]">
                    <!-- show lang_terms -->
                    <?php if(!empty($currentLang)): ?>
                      <div class="flex flex-wrap gap-1 max-w-full">
                        <?php foreach($currentLang as $lang_term): ?>
                          <?php if(!empty($node['lang_terms'][$lang_term])): ?>
                            <a href="<?= admin_url('terms/edit/' . $node['lang_terms'][$lang_term]['id'] . '?posttype=' . $node['lang_terms'][$lang_term]['posttype'] . '&type=' . $node['lang_terms'][$lang_term]['type']); ?>" 
                              class="flex items-center gap-1 bg-primary text-primary-foreground rounded-md px-2 py-1"
                              data-tooltip="<?= __('Edit Term') . ' ' . $node['lang_terms'][$lang_term]['name'] ?>">
                              <!-- icon + với ngôn ngữ là tag có nền đệp đẹp  -->
                              <i data-lucide="square-pen" class="h-4 w-4"></i>
                              <?= strtoupper($lang_term) ?>
                            </a>
                          <?php elseif($lang_term == $post_lang): 
                            continue;
                          else: ?>
                            <a href="<?= admin_url('terms/add/' . $node['id'] . '?posttype=' . $node['posttype'] . '&type=' . $node['type'] . '&post_lang=' . $lang_term . '&mainterm=' . $node['id_main']); ?>" 
                              class="flex items-center gap-1 bg-primary text-primary-foreground rounded-md px-2 py-1"
                              data-tooltip="<?= __('Add Term') . ' ' . strtoupper($lang_term) ?>">
                              <!-- icon + với ngôn ngữ là tag có nền đệp đẹp  -->
                              <i data-lucide="plus" class="h-4 w-4"></i>
                              <?= strtoupper($lang_term) ?>
                            </a>
                          <?php endif; ?>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </td>

                  <!-- Parent -->
                  <td class="px-4 py-1 align-middle text-foreground whitespace-nowrap truncate max-w-[120px]"
                    title="<?= htmlspecialchars($node['parent_name'] ?? 'No') ?>">
                    <?= htmlspecialchars($node['parent_name'] ?? 'No'); ?>
                  </td>

                  <!-- Status -->
                  <td class="px-4 py-1 align-middle text-center">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?= ($node['status'] ?? 'active') === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                      <?= ucfirst($node['status'] ?? 'active') ?>
                    </span>
                  </td>

                  <!-- Actions -->
                  <td class="px-4 py-1 align-middle text-center">
                    <div class="flex items-center gap-1 justify-center">
                      <a href="<?= admin_url('terms/edit/' . $node['id'] . '?posttype=' . $node['posttype'] . '&type=' . $node['type']); ?>"
                        class="inline-flex items-center justify-center whitespace-nowrap text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground rounded-md h-8 w-8 p-0 flex-shrink-0"
                        title="<?= __('Edit Term') ?>">
                        <i data-lucide="square-pen" class="h-4 w-4"></i>
                      </a>
                      <a href="<?= admin_url('terms/delete/' . $node['id'] . '?posttype=' . $node['posttype'] . '&type=' . $node['type']); ?>"
                        class="inline-flex items-center justify-center whitespace-nowrap text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground rounded-md h-8 w-8 p-0 flex-shrink-0"
                        onclick="return confirm('<?= __('Are you sure you want to delete this item?') ?>')"
                        title="<?= __('Delete Term') ?>">
                        <i data-lucide="trash2" class="h-4 w-4"></i>
                      </a>
                    </div>
                  </td>
                </tr>
              <?php
                // Nếu có children, tiếp tục gọi đệ quy để render các children
                if (!empty($node['children'])) {
                  renderTermRows($node['children'], $level + 1, $currentLang, $post_lang);
                }
              }
            }

            if (!empty($tree)) {
              renderTermRows($tree, 0, $currentLang, $post_lang);
            } else {
              ?>
              <tr>
                <td colspan="10" class="text-center py-4 text-muted-foreground">
                  <?= __('No terms found.') ?>
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<style>
.tooltip-box {
  position: absolute;
  background: #333;
  color: #fff;
  padding: 4px 8px;
  font-size: 12px;
  border-radius: 4px;
  white-space: nowrap;
  z-index: 9999;
  pointer-events: none;
}
</style>

<script>
document.querySelectorAll("[data-tooltip]").forEach(el => {
  el.addEventListener("mouseenter", e => {
    let tip = document.createElement("div");
    tip.className = "tooltip-box";
    tip.textContent = el.dataset.tooltip;
    document.body.appendChild(tip);
    let rect = el.getBoundingClientRect();
    tip.style.top = (rect.top - tip.offsetHeight - 5 + window.scrollY) + "px";
    tip.style.left = (rect.left + rect.width/2 - tip.offsetWidth/2 + window.scrollX) + "px";
    el._tooltip = tip;
  });
  el.addEventListener("mouseleave", e => {
    el._tooltip?.remove();
  });
});
</script>

<?php Render::block('Backend\Footer', ['layout' => 'default']); ?>