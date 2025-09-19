<?php
namespace System\Libraries;
use App\Libraries\Fastlang as Flang;
Render::block('Backend\Header', ['layout' => 'default', 'title' => $title ?? 'Posts']);

// Định nghĩa biến trước khi sử dụng trong Alpine.js
$posttype_slug = $_GET['type'] ?? $postType['slug'] ?? 'post';
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
      formData.append('type', '<?= htmlspecialchars($posttype_slug) ?>');
      
      const response = await fetch('<?= admin_url('posts/delete') ?>', {
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
  }
}">
<?php
// xoi thêm check box ở trước để có thể hiển thị nhiều dòng
// phân tab theo trạng thái sẽ ổn định hơn
// Ví dụ $posts:
// $posts = [
//   'data'   => [...],
//   'is_next'=> 1,
//   'page'   => 1
// ];
$data    = $posts['data']   ?? [];
$is_next = $posts['is_next'] ?? 0;
$page    = $posts['page']    ?? 1;
// Các tham số GET hiện có
$currentLang = $_GET['post_lang'] ?? '';
$search      = $_GET['q']         ?? '';
$limit       = $_GET['limit']     ?? 10;
$sort        = $_GET['sort']      ?? '';
$order       = $_GET['order']     ?? '';

// Nếu không có post_lang trong URL, chuyển hướng về trang với APP_LANG_DF
if (!isset($_GET['post_lang'])) {
    $redirectParams = $_GET;
    $redirectParams['post_lang'] = APP_LANG_DF;
    header('Location: ' . admin_url('posts') . '?' . http_build_query($redirectParams));
    exit;
}

// Mảng ngôn ngữ có sẵn
// Giả định $languages = ['en','vi','cn']... tuỳ bạn
?>

<?php
// Tạo mảng sao chép $_GET, rồi xóa post_lang để trở về ALL
$allParams = $_GET;
unset($allParams['post_lang']);

// Nút ALL
$allBtnClasses = 'btn btn-secondary';
if (is_null($currentLang)) {
  // Nếu đang ở ALL thì có thể thêm "active" hoặc style khác
  $allBtnClasses = 'btn btn-primary';
}
?>
<!-- [ Main Content ] start -->
<div class="pc-container">
  <div class="pc-content">
    <div class="card bg-card text-card-foreground border card-content rounded-lg shadow-md transition-shadow">
    <div class="card-header">
      <h1 class="card-title text-2xl font-bold mb-4"><?= $postType['name'] ?></h1>
    </div>
    <div class="card-body table-border-style">
        <div class="table-responsive">
          <div class="datatable-wrapper datatable-loading no-footer sortable searchable">
            <!-- Tabs ngôn ngữ -->
            <div class="mb-4">
              <div role="tablist" aria-orientation="horizontal" class="inline-flex p-1 items-center justify-center rounded-md bg-muted text-muted-foreground">
                <?php 
                $langParams = $allParams;
                foreach ($languages as $lang): 
                  $langParams['post_lang'] = $lang;
                  $langtext = Flang::_e($lang);
                  $isActive = $lang == $currentLang;
                ?>
                  <a href="<?= admin_url('posts') . '?' . http_build_query($langParams) ?>">
                    <button type="button" role="tab" 
                      aria-selected="<?= $isActive ? 'true' : 'false' ?>" 
                      data-state="<?= $isActive ? 'active' : 'inactive' ?>"
                      class="justify-center whitespace-nowrap rounded-sm px-2.5 py-1 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm flex items-center gap-2 <?= $isActive ? 'bg-background text-foreground shadow-sm' : 'bg-transparent text-muted-foreground' ?>">
                      <?= strtoupper($langtext) ?>
                    </button>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
            <div class="bg-card rounded-xl mb-4">
              <form method="GET" class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">
                <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center flex-1 w-full lg:w-auto">
                  <div class="relative flex-1 min-w-[200px] w-full sm:w-auto">
                    <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4"></i>
                    <input 
                      class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 pl-10" 
                      placeholder="<?= Flang::_e('place_search') ?>..." 
                      name="q" 
                      value="<?= htmlspecialchars($search) ?>"
                      @keydown.enter="$event.target.closest('form').submit()"
                    />
                  </div>
                  <div class="min-w-[100px] w-full sm:w-auto">
                    <select name="type" class="flex h-10 w-full items-center rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2" onchange="this.form.submit()">
                      <?php foreach ($allPostType as $item): ?>
                        <option value="<?= $item['slug'] ?>" <?= $item['slug'] ==$posttype_slug ? 'selected':''  ?>><?= Flang::_e($item['name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="min-w-[100px] w-full sm:w-auto">
                    <select name="limit" class="flex h-10 w-full items-center rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2" onchange="this.form.submit()">
                      <option value="5" <?= ($limit == 5)  ? 'selected' : '' ?>>5</option>
                      <option value="10" <?= ($limit == 10) ? 'selected' : '' ?>>10</option>
                      <option value="15" <?= ($limit == 15) ? 'selected' : '' ?>>15</option>
                      <option value="20" <?= ($limit == 20) ? 'selected' : '' ?>>20</option>
                      <option value="25" <?= ($limit == 25) ? 'selected' : '' ?>>25</option>
                      <option value="50" <?= ($limit == 50) ? 'selected' : '' ?>>50</option>
                      <option value="100" <?= ($limit == 100) ? 'selected' : '' ?>>100</option>
                      <option value="200" <?= ($limit == 200) ? 'selected' : '' ?>>200</option>
                      <option value="500" <?= ($limit == 500) ? 'selected' : '' ?>>500</option>
                    </select>
                  </div>
                </div>
                
                <!-- Hidden inputs to preserve other params -->
                <?php if (!empty($sort)) echo '<input type="hidden" name="sort" value="' . htmlspecialchars($sort) . '">'; ?>
                <?php if (!empty($order)) echo '<input type="hidden" name="order" value="' . htmlspecialchars($order) . '">'; ?>
                <?php if (!empty($currentLang)) echo '<input type="hidden" name="post_lang" value="' . htmlspecialchars($currentLang) . '">'; ?>
                
                <div class="flex gap-2">
                  <!-- Delete Selected Button -->
                  <button 
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
                  <a href="<?= admin_url('posts/add') . '?' . http_build_query(['type' => $posttype_slug]) ?>" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2 whitespace-nowrap w-full lg:w-auto">
                    <i data-lucide="plus" class="h-4 w-4 mr-2"></i>
                    <?= Flang::_e('add_new'); ?>
                  </a>
                </div>
              </form>
            </div>
  <!-- Bảng danh sách -->
  <div class="bg-card card-content !p-0 border overflow-hidden">
    <div class="overflow-x-auto">
      <div class="relative w-full overflow-auto">
        <table class="w-full caption-bottom text-sm ">
          <thead class="[&_tr]:border-b">
            <tr class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
              <!-- Checkbox Select All -->
              <th class="px-4 py-3 text-center align-middle bg-menu-background-hover text-menu-text-hover font-medium w-12">
                <input type="checkbox" id="selectAll" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded" @change="toggleSelectAll()">
              </th>
              <th class="px-4 py-3 text-left align-middle bg-menu-background-hover text-menu-text-hover font-medium">ID</th>
              <th class="px-4 py-3 text-left align-middle bg-menu-background-hover text-menu-text-hover font-medium"><?= Flang::_e('table.title') ?></th>
              <th class="px-4 py-3 text-left align-middle bg-menu-background-hover text-menu-text-hover font-medium"><?= Flang::_e('table.status') ?></th>
              <th class="px-4 py-3 text-left align-middle bg-menu-background-hover text-menu-text-hover font-medium"><?= Flang::_e('table.created') ?></th>
              <th class="px-4 py-3 text-left align-middle bg-menu-background-hover text-menu-text-hover font-medium"><?= Flang::_e('table.lang') ?></th>
              <th class="px-4 py-3 text-center align-middle bg-menu-background-hover text-menu-text-hover font-medium"><?= Flang::_e('table.actions') ?></th>
            </tr>
          </thead>
          <tbody class="[&_tr:last-child]:border-0">
            <?php if (!empty($data)): ?>
              <?php foreach ($data as $post): ?>
                <tr class="border-b transition-colors data-[state=selected]:bg-muted hover:bg-muted/50">
                  <!-- Checkbox -->
                  <td class="px-4 py-1 align-middle text-center">
                    <input type="checkbox" class="row-checkbox h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded" 
                           value="<?= $post['id'] ?>" @change="updateSelectedItems()">
                  </td>
                  <td class="px-4 py-1 align-middle font-medium text-foreground"><?= htmlspecialchars($post['id'] ?? 'N/A') ?></td>
                  <td class="px-4 py-1 align-middle text-foreground">
                    <a href="<?= admin_url('posts/edit/' . urlencode($post['id'])) . '?' . http_build_query([
                                'type'      => $posttype_slug,
                                'post_lang' => $currentLang,
                              ]) ?>" class="text-primary hover:underline hover:text-primary/80 transition-colors">
                      <?= htmlspecialchars($post['title'] ?? 'N/A') ?>
                    </a>
                  </td>
                  <td class="px-4 py-1 align-middle">
                    <?php
                    $status = strtolower($post['status'] ?? '');
                    if ($status === 'active') {
                      echo '<span class="text-primary text-xs font-medium">Published</span>';
                    } elseif ($status === 'inactive') {
                      echo '<span class="text-primary text-xs font-medium">Draft</span>';
                    } else {
                      echo '<span class="text-primary text-xs font-medium">'.ucfirst($status ?: 'N/A').'</span>';
                    }
                    ?>
                  </td>
                  <td class="px-4 py-1 align-middle text-foreground"><?= htmlspecialchars($post['created_at'] ?? 'N/A') ?></td>
                  <td class="px-4 py-1 align-middle">
                    <?php
                    $langList = $post['langList'] ?? [];
                    if (!empty($langList)) {
                      $links = [];
                      foreach ($langList as $lc) {
                        if (empty($lc)) continue;
                        // Link edit kèm param post_lang
                        $editParams = [
                          'type'      => $posttype_slug,
                          'post_lang' => $lc,
                        ];
                        $editUrl = admin_url('posts/edit/' . urlencode($post['id']))
                          . '?' . http_build_query($editParams);

                        $links[] = '<a href="' . htmlspecialchars($editUrl) . '" class="text-primary hover:underline">'
                          . htmlspecialchars(strtoupper($lc)) . '</a>';
                      }
                      echo implode(', ', $links);
                    } else {
                      echo '<span class="text-muted-foreground text-xs">N/A</span>';
                    }
                    ?>
                  </td>
                  <td class="px-4 py-1 align-middle text-center">
                    <div class="flex items-center gap-1 justify-center">
                      <?php if (!empty($post['id'])): ?>
                        <a href="<?= admin_url('posts/edit/' . urlencode($post['id'])) . '?' . http_build_query([
                                    'type'      => $posttype_slug,
                                    'post_lang' => $currentLang,
                                  ]) ?>" class="inline-flex items-center justify-center whitespace-nowrap text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground rounded-md h-8 w-8 p-0" title="Edit Post">
                          <i data-lucide="square-pen" class="h-4 w-4"></i>
                        </a>
                        <a href="<?= admin_url('posts/delete/' . urlencode($post['id'])) . '?' . http_build_query([
                                    'type'      => $posttype_slug,
                                    'post_lang' => $currentLang,
                                  ]) ?>" class="inline-flex items-center justify-center whitespace-nowrap text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground rounded-md h-8 w-8 p-0" onclick="return confirm('<?= Flang::_e('confirm_delete') ?>');" title="Delete Post">
                          <i data-lucide="trash2" class="h-4 w-4"></i>
                        </a>
                      <?php else: ?>
                        <span class="text-muted-foreground text-xs">N/A</span>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="7" class="text-center py-4 text-muted-foreground"><?= Flang::_e('post_not_found') ?></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <!-- Pagination -->
    <div class="flex flex-col sm:flex-row items-center justify-between px-4 py-1 border-t gap-4">
      <div class="text-sm text-muted-foreground">
        <!-- Hiển thị số lượng -->
        <?php
        $total = $posts['total'] ?? count($data);
        $from = ($page - 1) * $limit + 1;
        $to = $from + count($data) - 1;
        if ($total > 0) {
          echo "Showing $from to $to of $total results";
        } else {
          echo "No results";
        }
        ?>
      </div>
      <div class="flex items-center gap-2">
        <?php
        // Cấu hình param build link pagination
        $query_params = [];
        if (!empty($search)) {
          $query_params['q'] = $search;
        }
        if ($limit != 10) {
          $query_params['limit'] = $limit;
        }
        if (!empty($sort)) {
          $query_params['sort'] = $sort;
        }
        if (!empty($order)) {
          $query_params['order'] = $order;
        }
        if (!empty($posttype_slug) && $posttype_slug !== 'post') {
          $query_params['type'] = $posttype_slug;
        }
        // Giữ post_lang nếu có
        if (!empty($currentLang)) {
          $query_params['post_lang'] = $currentLang;
        }

        // Gọi hàm Render::pagination(...)
        echo Render::pagination(
          admin_url('posts/index'),
          $page,
          $is_next,
          $query_params
        );
        ?>
      </div>
    </div>
  </div>
</div>
<!-- [ Main Content ] end -->
</div>
<?php
Render::block('Backend\Footer', ['layout' => 'default']);
?>