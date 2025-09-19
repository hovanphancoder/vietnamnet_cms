<?php
namespace System\Libraries;
use App\Libraries\Fastlang as Flang;
Render::block('Backend\Head', ['layout' => 'default', 'title' => $title ?? 'Files']);
?>
  <script src="https://unpkg.com/vue@3"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<!-- </head>
<body> -->
  <script>
    // Định nghĩa API endpoint
    var urlfiles_tmp = '<?= config('files')['files_url'] ?? '/uploads' ?>';
    const FILES_URL = urlfiles_tmp.replace(/\/$/, '') + '/';
    const FILES_API = "<?= base_url('vi/api/v1/files/'); ?>";
    const BASE_URL = '<?= base_url() ?>';
    
    // Debug log control
    const show_log = true; // Set false để tắt log
    
  </script>

<style>
  /* Global Loading Before Vue Load */
  #global-loader{
    position:fixed;inset:0;display:flex;align-items:center;justify-content:center;
    background:var(--background);z-index:9999;
  }
  @keyframes spin{to{transform:rotate(360deg)}}
</style>

  <div id="global-loader">
    <svg class="w-12 h-12 text-blue-600" style="animation:spin 1s linear infinite"
         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4">
      <circle cx="12" cy="12" r="10" opacity="0.25"></circle>
      <path d="M12 2a10 10 0 0 1 10 10" opacity="0.75"></path>
    </svg>
  </div>

  <div id="app">
    <media-library></media-library>
  </div>

  <!-- Template cho component media library -->
  <template id="media-library-template">
    <div
      ref="rootElement"
      class="min-h-screen bg-background p-4"
      @mousedown="startSelection"
      @mousemove="whileSelecting"
      @mouseup="endSelection"
      @click="handleClickOutside"
      @contextmenu.prevent.stop="handleRightClickOutside($event)"
      @click.outside="handleLeftClickOutside"
    >
      <!-- Loading overlay -->
      <div
        v-if="isLoading"
        class="fixed inset-0 bg-background/60 flex items-center justify-center z-[60]"
      >
        <!-- spinner SVG  -->
        <svg
          class="w-12 h-12 animate-spin text-blue-600"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
          ></path>
        </svg>
      </div>
      <!-- Header -->
      <div class="sticky top-0 z-50 flex flex-wrap justify-between items-center bg-card rounded-xl p-4 mb-4 border shadow-sm" id="files-header">

        <div class="w-full md:w-6/12 mt-2 md:mt-0">
          <div class="flex justify-start space-x-2">
            <!-- Upload Files -->
            <div class="mx-1 mt-2 md:mt-0">
              <button @click="clickMenuUpload" class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 bg-blue-600 text-white hover:bg-blue-700 h-10 px-4 py-2">
                <i class="bi bi-plus-lg h-4 w-4 mr-2"></i> <?= Flang::_e('uploads') ?>
              </button>
            </div>

            <!-- Delete Selected Files -->
            <div class="mx-1 mt-2 md:mt-0" v-if="selectedItems.length > 0">
              <button @click="deleteSelectedItems" class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 bg-red-600 text-white hover:bg-red-700 h-10 px-4 py-2">
                <i class="bi bi-trash h-4 w-4 mr-2"></i> <?= Flang::_e('delete') ?>
              </button>
            </div>
            <!--  Selected Files -->
            <div  class="mx-1 mt-2 md:mt-0"
                  v-if="field_input && ((multi && selectedItems.length > 0) || (!multi && selectedItems.length === 1))">
                <button @click="returnSelectedFile" class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 bg-green-600 text-white hover:bg-green-700 h-10 px-4 py-2">
                <i class="bi bi-check2-square h-4 w-4 mr-2"></i> <?= Flang::_e('select files') ?>
              </button>
            </div>
          </div>
        </div>

        <!-- Sort and View Toggle Buttons -->
        <div class="w-full md:w-6/12 mt-2 md:mt-0">
          <div class="flex justify-end space-x-2">
            <input type="text" v-model="page_search" placeholder="<?= Flang::_e('enter file name') ?>..." class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" />
            <select v-model="page_sort" class="flex h-10 w-full items-center rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 w-48">
              <option value="created_at_asc">Created ↑</option>
              <option value="created_at_desc">Created ↓</option>
              <option value="name">Name A-Z</option>
              <option value="name_za">Name Z-A</option>
              <option value="size_asc">Size ↑</option>
              <option value="size_desc">Size ↓</option>
              <option value="updated_at_asc">Updated ↑</option>
              <option value="updated_at_desc">Updated ↓</option>
            </select>
            <button
              @click="toggleView('list')"
              :class="viewType === 'list' ? 'bg-blue-600 text-white' : 'border border-gray-300 bg-white hover:bg-gray-50 text-gray-700'"
              class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 h-10 px-3 py-2"
            >
              <i class="bi bi-list h-4 w-4"></i>
            </button>
            <button
              @click="toggleView('grid')"
              :class="viewType === 'grid' ? 'bg-blue-600 text-white' : 'border border-gray-300 bg-white hover:bg-gray-50 text-gray-700'"
              class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 h-10 px-3 py-2"
            >
              <i class="bi bi-grid-3x3-gap h-4 w-4"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Content Area -->
      <div class="bg-card rounded-xl p-4 relative border shadow-sm">
        <!-- Selection Rectangle -->
        <div
          v-if="isSelecting"
          :style="selectionRectangleStyles"
          class="absolute bg-blue-200 border border-blue-500 opacity-50 z-40 rounded"
        ></div>

        <!-- Grid View -->
        <div v-if="viewType === 'grid'" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
          <div
            v-for="(item, index) in sortedData"
            :key="item.id"
            :data-index="index"
            @mousedown.stop="handleItemMouseDown(item, index, $event)"
            @click.stop="handleItemClick(item, index, $event)"
            @dblclick.stop="handleItemDoubleClick(item, index, $event)"
            @contextmenu.prevent.stop="showContextMenu(item, $event)"
            :class="[
              'item border p-4 rounded-lg text-center cursor-pointer relative transition-colors hover:bg-blue-50',
              isSelected(item) ? 'bg-blue-100 border-blue-500' : 'bg-white border-gray-200'
            ]"
          >
            <!-- Checkbox và số thứ tự -->
            <div class="absolute top-2 left-2 flex items-center">
                <span v-if="isSelected(item)" class="bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-medium">
                    {{ multi ? selectedOrder[item.id] : '' }}
                </span>
            </div>

            <!-- Item Content -->
            <div v-if="checkImages(item.name)" class="flex justify-center items-center">
                <img :src="link_img(item.path)" class="max-h-32 object-contain" alt="">
            </div>
            <div v-else>
              <i class="bi bi-file-earmark-fill text-gray-400 text-5xl"></i>
            </div>
            <div class="truncate mt-2 text-sm font-medium text-gray-900" style="max-width: 100%;">{{ item.name }}</div>
            <div v-if="item.size" class="text-xs text-gray-500">{{ (item.size / 1024).toFixed(2) }} KB</div>
            <div class="text-xs text-gray-500" :title="'Created at: ' + item.created_at">{{ item.created_at }}</div>
            <div class="text-xs text-gray-500" :title="'Updated at: ' + item.updated_at">{{ item.updated_at }}</div>
          </div>
        </div>

        <!-- List View -->
        <div v-if="viewType === 'list'" class="w-full">
          <div class="overflow-x-auto">
            <div class="relative w-full overflow-auto">
              <table class="w-full caption-bottom text-sm">
                <thead class="[&_tr]:border-b">
                  <tr class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                    <th class="px-4 py-3 text-center align-middle bg-gray-100 text-gray-700 font-medium w-12">Select</th>
                    <th class="px-4 py-3 text-left align-middle bg-gray-100 text-gray-700 font-medium">Name</th>
                    <th class="px-4 py-3 text-left align-middle bg-gray-100 text-gray-700 font-medium">Size</th>
                    <th class="px-4 py-3 text-left align-middle bg-gray-100 text-gray-700 font-medium">Created</th>
                    <th class="px-4 py-3 text-left align-middle bg-gray-100 text-gray-700 font-medium">Updated</th>
                  </tr>
                </thead>
                <tbody class="[&_tr:last-child]:border-0">
                  <tr
                    v-for="(item, index) in sortedData"
                    :key="item.id"
                    :data-index="index"
                    @mousedown.stop="handleItemMouseDown(item, index, $event)"
                    @click.stop="handleItemClick(item, index, $event)"
                    @dblclick.stop="handleItemDoubleClick(item, index, $event)"
                    @contextmenu.prevent.stop="showContextMenu(item, $event)"
                    :class="[
                      'item border-b transition-colors hover:bg-gray-50 cursor-pointer',
                      isSelected(item) ? 'bg-blue-50' : ''
                    ]"
                  >
                    <!-- Checkbox và số thứ tự -->
                    <td class="px-4 py-3 align-middle text-center">
                        <div class="flex items-center justify-center">
                            <span v-if="isSelected(item)" class="bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-medium">
                                {{ multi ? selectedOrder[item.id] : '' }}
                            </span>
                        </div>
                    </td>
                    <!-- Item Content -->
                    <td class="px-4 py-3 align-middle">
                      <div class="flex items-center space-x-3">
                        <div v-if="checkImages(item.name)">
                          <img :src="link_img(item.path)" alt="" class="w-12 h-12 object-contain rounded">
                        </div>
                        <div v-else>
                          <i class="bi bi-file-earmark-fill text-gray-400 text-3xl"></i>
                        </div>
                        <span class="text-gray-900 font-medium">{{ item.name }}</span>
                      </div>
                    </td>
                    <td class="px-4 py-3 align-middle text-gray-500">{{ item.size ? (item.size / 1024).toFixed(2) + ' KB' : '-' }}</td>
                    <td class="px-4 py-3 align-middle text-gray-500">{{ item.created_at }}</td>
                    <td class="px-4 py-3 align-middle text-gray-500">{{ item.updated_at }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination Controls -->
      <div class="flex justify-between items-center bg-card rounded-xl p-4 mt-4 border shadow-sm">
        <!-- Nút Prev -->
        <button
          @click="clickPrevPage"
          :disabled="page_now === 1"
          class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 bg-blue-600 text-white hover:bg-blue-700 h-10 px-4 py-2 disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed"
        >
          <?= Flang::_e('prev') ?>
        </button>

        <!-- Nút Next -->
        <button
          @click="clickNextPage"
          :disabled="!page_isnext"
          class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 bg-blue-600 text-white hover:bg-blue-700 h-10 px-4 py-2 disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed"
        >
          <?= Flang::_e('next') ?>
        </button>
      </div>

      <!-- Context Menu -->
      <div
        v-if="contextMenuVisible"
        @click.stop
        :style="{ top: contextMenuPosition.top + 'px', left: contextMenuPosition.left + 'px', position: 'absolute' }"
        class="context-menu absolute shadow-lg rounded-lg py-2 w-48 z-50 border border-border"
      >
        <ul>
          <li class="px-4 py-2 cursor-pointer hover:bg-accent text-popover-foreground transition-colors" @click="clickMenuDownload">
            <i class="bi bi-download h-4 w-4 mr-2"></i> <?= Flang::_e('download') ?>
          </li>
          <li class="px-4 py-2 cursor-pointer hover:bg-accent text-popover-foreground transition-colors" @click="clickMenuRename">
            <i class="bi bi-pencil h-4 w-4 mr-2"></i> <?= Flang::_e('rename') ?>
          </li>
          <li class="px-4 py-2 cursor-pointer hover:bg-accent text-popover-foreground transition-colors" @click="clickMenuDelete">
            <i class="bi bi-trash h-4 w-4 mr-2"></i> <?= Flang::_e('delete') ?>
          </li>
        </ul>
      </div>
    </div>
  </template>

  <script>
    const { createApp, ref, computed, onMounted, onUnmounted, watch } = Vue;

    const hideGlobalLoader = () => {
      const el = document.getElementById('global-loader');
      if (el) el.remove();                       // hoặc el.style.display='none';
    };

    createApp({
      template: '#media-library-template',
      setup() {
        const config = ref(<?= json_encode($config_files); ?>);
        const field_input = ref(null);
        const multi = ref(false);
        const wisy = ref(false);
        const token = ref(null);
        const value = ref(null);
        const autocrop = ref(false);
        const watermark = ref(false);
        const watermark_img = ref(null);
        const type = ref(null);
        const extensions = ref(null);
        const resizes = ref([]);  
        const files = ref([]);
        const page_now = ref(1);
        const page_limit = ref(config.value.limit || 20);
        const page_isnext = ref(false);
        const page_sort = ref('created_at_desc');
        const page_search = ref('');
        const page_image = ref(false);
        const viewType = ref('grid');
        const contextMenuVisible = ref(false);
        const contextMenuPosition = ref({ top: 0, left: 0 });
        const selectedItem = ref(null);
        const selectedItems = ref([]);
        const selectedOrder = ref({});
        const rootElement = ref(null);

        // Variables for selection handling
        const isSelecting = ref(false);
        const selectionStart = ref({ x: 0, y: 0 });
        const selectionRectangle = ref({ x: 0, y: 0, width: 0, height: 0 });
        const lastClickedIndex = ref(null);
        const selectionMode = ref('select');

        // Debounce search delay
        const searchDebounceTimer = ref(null);
        // Debounce fetch delay
        const fetchDebounceTimer = ref(null);
        // Application Loading
        const isLoading = ref(false); 
        hideGlobalLoader(); //remove Global Loading

        // Xử lý postMessage
        window.addEventListener('message', function(event) {
            const data = event.data;
            if (data.type === 'init') {
                // Cập nhật các biến từ config
                field_input.value = data.config.field;
                multi.value = data.config.multi === 1 || data.config.multi === true;
                value.value = data.config.value || null;
                wisy.value = data.config.wisy === 1;
                token.value = data.config.token || null;
                autocrop.value = data.config.autocrop || false;
                watermark.value = data.config.watermark || false;
                watermark_img.value = data.config.watermark_img || null;
                type.value = data.config.type  || '';
                extensions.value = data.config.extensions  || [];
                if (extensions.value && extensions.value.length > 0){
                  config.value.allowed_types = extensions.value;
                }
                page_image.value = data.config.page_image  || 0;
                watermark_img.value = JSON.parse(watermark_img.value)?.path;
                resizes.value = data.config.resizes || [];
            }
            console.log(data);
        });

        const processInitialValue = () => {
            if (!value.value) return;
            
            try {
                // Parse value nếu là string
                const valueData = typeof value.value === 'string' ? JSON.parse(value.value) : value.value;
                // Chuyển đổi thành mảng nếu là single item
                const items = Array.isArray(valueData) ? valueData : [valueData];
                
                // Reset các mảng
                selectedItems.value = [];
                selectedOrder.value = {};
                
                // Thêm từng item vào selectedItems và cập nhật selectedOrder
                items.forEach((item, index) => {
                    if (item.id) {
                        selectedItems.value.push(item.id);
                        selectedOrder.value[item.id] = index + 1;
                    }
                });
            } catch (error) {
                console.error('Error processing initial value:', error);
            }
        };

        const returnSelectedFile = () => {
            if (field_input.value) {
                // Sắp xếp files theo thứ tự chọn
                const selectedFiles = files.value
                    .filter(file => selectedItems.value.includes(file.id))
                    .sort((a, b) => selectedOrder.value[a.id] - selectedOrder.value[b.id]);

                if(show_log) {
                    console.log('=== DEBUG: Data chuẩn bị trả về cho parent ===');
                    console.log('field_input.value:', field_input.value);
                    console.log('multi.value:', multi.value);
                    console.log('token.value:', token.value);
                    console.log('selectedFiles:', selectedFiles);
                    console.log('selectedItems.value:', selectedItems.value);
                    console.log('selectedOrder.value:', selectedOrder.value);
                }

                if(token.value) {
                    if (!multi.value && selectedFiles.length > 0) {
                        // Single select
                        const selectedFile = selectedFiles[0];
                        const fileData = {
                            id: selectedFile.id,
                            server: BASE_URL,
                            name: selectedFile.name,
                            path: selectedFile.path,
                        };

                        if(show_log) {
                            console.log('=== Single Select with Token ===');
                            console.log('fileData:', fileData);
                        }

                        // Gửi message tới parent window
                        const message = {
                            type: 'fileSelected',
                            field: field_input.value,
                            data: fileData,
                            wisy: wisy.value,
                            multi: false
                        };
                        if(show_log) console.log('Message to parent:', message);
                        window.parent.postMessage(message, '*');

                    } else if (multi.value && selectedFiles.length > 0) {
                        // Multi select
                        const fileDataList = selectedFiles.map(file => ({
                            id: file.id,
                            server: BASE_URL,
                            name: file.name,
                            path: file.path,
                        }));

                        if(show_log) {
                            console.log('=== Multi Select with Token ===');
                            console.log('fileDataList:', fileDataList);
                        }

                        // Gửi message tới parent window
                        const message = {
                            type: 'fileSelected',
                            field: field_input.value,
                            data: fileDataList,
                            wisy: wisy.value,
                            multi: true
                        };
                        if(show_log) console.log('Message to parent:', message);
                        window.parent.postMessage(message, '*');
                    }
                } else {
                    if (!multi.value && selectedFiles.length > 0) {
                        // Single select: Chỉ gửi file đầu tiên
                        const selectedFile = selectedFiles[0];
                        const fileData = {
                            id: selectedFile.id,
                            name: selectedFile.name,
                            path: selectedFile.path,
                        };
                        if(show_log) {
                            console.log('=== Single Select without Token ===');
                            console.log('field_input.value:', field_input.value);
                            console.log('fileData:', fileData);
                        }
                        window.parent.setChosenImageInput(field_input.value, fileData);
                    } else if (multi.value && selectedFiles.length > 0) {
                        // Multi select: Gửi danh sách file
                        const fileDataList = selectedFiles.map(file => ({
                            id: file.id,
                            name: file.name,
                            path: file.path,
                        }));
                        if(show_log) {
                            console.log('=== Multi Select without Token ===');
                            console.log('fileDataList:', fileDataList);
                        }
                        if(show_log) {
                            console.log('field_input.value:', field_input.value);
                            console.log('fileDataList:', fileDataList);
                        }
                        window.parent.setChosenImageInput(field_input.value, fileDataList, true);
                    }
                }

                if(show_log) console.log('=== Đóng File Manager ===');
                // Đóng File Manager
                window.close();
            }
        };

        // md5 function
        const md5 = (str) => {
          return CryptoJS.MD5(str).toString();
        };

        const selectItem = (item) => {
          selectedItem.value = item;
        };

        const deselectItem = () => {
          selectedItem.value = null;
        };

        const handleClickOutside = (event) => {
          if (!rootElement.value) return;
          if (!rootElement.value.contains(event.target)) {
            contextMenuVisible.value = false;
            selectedItem.value = null;
          }
        };

        const handleRightClickOutside = (event) => {
          if (!rootElement.value) return;
          const isClickInsideMenu = event.target.closest('.context-menu');
          const isClickOnItem = event.target.closest('.item');
          const isClickOnHeader = event.target.closest('#files-header');

          if (!isClickInsideMenu && !isClickOnItem && !isClickOnHeader) {
            contextMenuVisible.value = false;
            selectedItem.value = null;
          }
        };

        const handleLeftClickOutside = (event) => {
            if (!contextMenuVisible.value) return;
            const isInsideMenu   = event.target.closest('.context-menu');
            const isOnItem       = event.target.closest('.item');
            const isOnHeader     = event.target.closest('#files-header');

            if (!isInsideMenu && !isOnItem && !isOnHeader) {
                contextMenuVisible.value = false;
                selectedItem.value = null;
            }
        };

        const checkImages = (fileName) => {
          const fileExtension = fileName.split('.').pop().toLowerCase();
          return config.value.images_types.includes(fileExtension);
        };

        const clickPrevPage = async () => {
          if (page_now.value > 1) {
            page_now.value--;
            await fetchFiles();
          }
        };

        const clickNextPage = async () => {
          if (page_isnext.value) {
            page_now.value++;
            await fetchFiles();
          }
        };

        const fetchFiles = async () => {
            if (fetchDebounceTimer.value) {
                fetchDebounceTimer.value.abort();
            }
            const controller = new AbortController();
            fetchDebounceTimer.value = controller;
            isLoading.value = true;
            var isOnlyImages = page_image.value || 0;
            try {
                const query = page_search.value.replace(/[<>{}()'"*%&]/g, '');
                const url   = `${FILES_API}index/?page=${page_now.value}&type=${isOnlyImages}&limit=${page_limit.value}&sort=${page_sort.value}&q=${encodeURIComponent(query)}`;
                const response = await fetch(url, { signal: controller.signal });
                const result   = await response.json();
                if (!controller.signal.aborted && result.status === 'success' && result.data) {
                    files.value       = result.data.items;
                    page_isnext.value = result.data.isnext ?? false;
                    // Xử lý value sau khi fetch files
                    processInitialValue();
                }
            } catch (err) {
                // AbortError
                if (err.name !== 'AbortError') {
                    console.error('Fetch Error:', err);
                }
            } finally {
                isLoading.value = false;
            }
        };

        const sortedData = computed(() => {
          let items = files.value.slice(); // Make a copy of the array

          // Apply search filter (already applied in fetch, but keeping this for safety)
          if (page_search.value) {
            const searchQuery = page_search.value.toLowerCase();
            items = items.filter(item => item.name.toLowerCase().includes(searchQuery));
          }

          // Sorting logic
          items.sort((a, b) => {
            switch (page_sort.value) {
              case 'name':
                return a.name.localeCompare(b.name);
              case 'name_za':
                return b.name.localeCompare(a.name);
              case 'size_asc':
                return (a.size || 0) - (b.size || 0);
              case 'size_desc':
                return (b.size || 0) - (a.size || 0);
              case 'created_at_asc':
                return new Date(a.created_at) - new Date(b.created_at);
              case 'created_at_desc':
                return new Date(b.created_at) - new Date(a.created_at);
              case 'updated_at_asc':
                return new Date(a.updated_at) - new Date(b.updated_at);
              case 'updated_at_desc':
                return new Date(b.updated_at) - new Date(a.updated_at);
              default:
                return a.name.localeCompare(b.name);
            }
          });

          return items;
        });

        watch([page_sort], () => {
          page_now.value = 1;
          fetchFiles();
        });
        watch([page_search], () => {
            page_search.value = page_search.value.replaceAll(' ', '-');
            clearTimeout(searchDebounceTimer.value);
            searchDebounceTimer.value = setTimeout(() => {
                page_now.value = 1;
                fetchFiles();
            }, 1000);
        });

        const toggleView = (view) => {
          viewType.value = view;
        };

        const showContextMenu = (item, event) => {
          selectedItem.value = item;
          contextMenuVisible.value = true;
          contextMenuPosition.value = { top: event.pageY, left: event.pageX };
        };

        const clickMenuDownload = () => {
          if (selectedItem.value) {
            const downloadUrl = `${FILES_URL}${selectedItem.value.path}`;
            window.open(downloadUrl, '_blank');
          }
          contextMenuVisible.value = false;
        };

        const _baseName = (filename = '') => {
          const dot = filename.lastIndexOf('.');
          return dot <= 0 ? filename : filename.slice(0, dot);
        };

        const clickMenuRename = async () => {
            if (!selectedItem.value) return;

            const newName = prompt("<?= Flang::_e('enter new name') ?>", _baseName(selectedItem.value.name));
            if (newName === selectedItem.value.name){
                alert('<?= Flang::_e('name is required and different from current name') ?>');
                return;
            };
            if (!newName || newName.trim() === ''){
                return;
            }
            const type = selectedItem.value.type === 'folder' ? 'folder' : 'file';
            
            isLoading.value = true;
            $.ajax({
                url      : FILES_API + 'rename_file/',
                type     : 'POST',
                dataType : 'json',
                data     : {
                    id      : selectedItem.value.id,
                    type    : type,
                    newname : newName
                },
                beforeSend() {
                    contextMenuVisible.value = false;
                }
            })
            .done((res) => {
                if (res.status === 'success' && res.data && res.data.data) {
                    const idx = files.value.findIndex(f => f.id === selectedItem.value.id);
                    if (idx !== -1) {
                        files.value[idx].name = res.data.data.name;
                        files.value[idx].path = res.data.data.path;
                    }
                    selectedItem.value = null;
                } else {
                    console.error('Error:', res.error);
                }
            })
            .fail((xhr, status) => {
                console.error('Ajax rename error:', xhr.responseText);
            })
            .always(() => {
                isLoading.value = false;
            });

        };

        const clickMenuDelete = async () => {
          if (confirm(`Bạn có chắc chắn muốn xóa "${selectedItem.value.name}"?`)) {
            isLoading.value = true;
            try {
              const formData = new FormData();
              formData.append('id', selectedItem.value.id);
              formData.append('path', selectedItem.value.path);
              const response = await fetch(`${FILES_API}delete/`, {
                method: 'POST',
                body: formData
              });
              const result = await response.json();
              if (result.status === 'success') {
                files.value = files.value.filter(file => file.id !== selectedItem.value.id);
                selectedItems.value = selectedItems.value.filter(id => id !== selectedItem.value.id);
                selectedItem.value = null;
              } else {
                console.error('Error:', result.error);
              }
            } catch (error) {
              console.error('API Error:', error);
            } finally {
              isLoading.value = false;
              contextMenuVisible.value = false;
            }
          }
        };

        const deleteSelectedItems = async () => {
          if (confirm(`Bạn có chắc chắn muốn xóa các mục đã chọn?`)) {
            isLoading.value = true;
            const itemsToDelete = selectedItems.value.map(id => {
              const item = files.value.find(file => file.id === id);
              return item ? { id: item.id, path: item.path } : null;
            }).filter(item => item !== null);
            try {
              const formData = new FormData();
              formData.append('items', JSON.stringify(itemsToDelete));
              const response = await fetch(`${FILES_API}delete_multiple/`, {
                method: 'POST',
                body: formData
              });
              const result = await response.json();
              if (result.status === 'success') {
                files.value = files.value.filter(file => !selectedItems.value.includes(file.id));
                selectedItems.value = [];
              } else {
                console.error('Error:', result.error);
              }
            } catch (error) {
              console.error('API Error:', error);
            } finally {
              isLoading.value = false;
            }
          }
        };

        // Thêm hàm cập nhật số thứ tự
        const updateSelectedOrder = () => {
            const newOrder = {};
            selectedItems.value.forEach((id, index) => {
                newOrder[id] = index + 1;
            });
            selectedOrder.value = newOrder;
        };

        // Handle multiple item selection
        const toggleItemSelection = (item) => {
            const idx = selectedItems.value.indexOf(item.id);

            if (idx !== -1) {
                // ⇢ Đang được chọn  →  BỎ chọn
                selectedItems.value.splice(idx, 1);
                updateSelectedOrder(); // Cập nhật lại số thứ tự
            } else {
                // ⇢ Chưa được chọn  →  Chọn
                if (multi.value) {
                    selectedItems.value.push(item.id);     // cho phép nhiều
                    selectedOrder.value[item.id] = selectedItems.value.length; // Save thứ tự chọn
                } else {
                    selectedItems.value = [item.id];       // chỉ 1 file
                    selectedOrder.value = { [item.id]: 1 }; // Reset thứ tự khi chọn 1 file
                }
            }
        };

        const isSelected = (item) => {
          return selectedItems.value.includes(item.id);
        };

        // Selection handling
        const startSelection = (event) => {
            if (event.button !== 0) return; // Chỉ phản hồi khi click chuột trái
            if (event.shiftKey) return; // Bỏ qua nếu giữ phím Shift (xử lý riêng)

            isSelecting.value = true;
            selectionStart.value = { x: event.pageX, y: event.pageY };
            selectionRectangle.value = { x: event.pageX, y: event.pageY, width: 0, height: 0 };

            // Kiểm tra xem mục được click có được chọn hay không
            const targetItem = event.target.closest('.item');
            if (targetItem) {
            const index = parseInt(targetItem.getAttribute('data-index'));
            const item = sortedData.value[index];
            if (isSelected(item)) {
                selectionMode.value = 'deselect';
            } else {
                selectionMode.value = 'select';
            }
            } else {
            selectionMode.value = 'select';
            }
        };

        const whileSelecting = (event) => {
          if (!isSelecting.value) return;
          const x1 = selectionStart.value.x;
          const y1 = selectionStart.value.y;
          const x2 = event.pageX;
          const y2 = event.pageY;

          selectionRectangle.value = {
            x: Math.min(x1, x2),
            y: Math.min(y1, y2),
            width: Math.abs(x2 - x1),
            height: Math.abs(y2 - y1),
          };

          selectItemsInRectangle();
        };

        const endSelection = () => {
          if (isSelecting.value) {
            isSelecting.value = false;
            selectionRectangle.value = { x: 0, y: 0, width: 0, height: 0 };
          }
        };

        const selectionRectangleStyles = computed(() => {
          return {
            left: selectionRectangle.value.x + 'px',
            top: selectionRectangle.value.y + 'px',
            width: selectionRectangle.value.width + 'px',
            height: selectionRectangle.value.height + 'px',
          };
        });

        const selectItemsInRectangle = () => {
            const rect = selectionRectangle.value;
            if (!rootElement.value) return;

            // Get all item elements
            const itemElements = rootElement.value.querySelectorAll('.item');
            if (!itemElements.length) return;

            itemElements.forEach((element, index) => {
                if (!element) return;
                
                // Kiểm tra xem element có tồn tại trong DOM không
                if (!document.body.contains(element)) return;
                
                const itemRect = element.getBoundingClientRect();
                const item = sortedData.value[index];
                if (!item) return;

                const itemX = itemRect.left + window.scrollX;
                const itemY = itemRect.top + window.scrollY;
                const itemWidth = itemRect.width;
                const itemHeight = itemRect.height;

                // Check if item overlaps with selection rectangle
                if (
                    rect.x < itemX + itemWidth &&
                    rect.x + rect.width > itemX &&
                    rect.y < itemY + itemHeight &&
                    rect.y + rect.height > itemY
                ) {
                    if (selectionMode.value === 'select') {
                        if (!selectedItems.value.includes(item.id)) {
                            selectedItems.value.push(item.id);
                        }
                    } else if (selectionMode.value === 'deselect') {
                        const indexInSelected = selectedItems.value.indexOf(item.id);
                        if (indexInSelected !== -1) {
                            selectedItems.value.splice(indexInSelected, 1);
                        }
                    }
                }
            });
            updateSelectedOrder(); // Cập nhật số thứ tự sau khi chọn bằng kéo chuột
        };

        const handleItemClick = (item, index, event) => {
            // --- 1) Using Shift: Select Area Multi Item ---------------
            if (event.shiftKey && lastClickedIndex.value !== null) {
                const start = Math.min(lastClickedIndex.value, index);
                const end = Math.max(lastClickedIndex.value, index);
                // Kiểm tra trạng thái của mục hiện tại
                const isCurrentlySelected = isSelected(sortedData.value[index]);
                const mode = isCurrentlySelected ? 'deselect' : 'select';

                for (let i = start; i <= end; i++) {
                    const currentItem = sortedData.value[i];
                    if (mode === 'select') {
                        if (!selectedItems.value.includes(currentItem.id)) {
                            selectedItems.value.push(currentItem.id);
                        }
                    } else {
                        const idx = selectedItems.value.indexOf(currentItem.id);
                        if (idx !== -1) {
                            selectedItems.value.splice(idx, 1);
                        }
                    }
                }
                updateSelectedOrder(); // Cập nhật số thứ tự sau khi chọn bằng shift
            }// --- 2) Using Ctrl (or ⌘ on macOS) -------------------
            else if (event.ctrlKey || event.metaKey) {
                const idx = selectedItems.value.indexOf(item.id);
                if (idx === -1) {
                    selectedItems.value.push(item.id);      // ➜ thêm
                } else {
                    selectedItems.value.splice(idx, 1);     // ➜ gỡ
                }
                updateSelectedOrder(); // Cập nhật số thứ tự sau khi chọn bằng ctrl
            }
            // --- 3) Click Normal: single-select + Uncheck when reclick ------
            else {
                toggleItemSelection(item);
            }
            lastClickedIndex.value = index;
        };

        const handleItemMouseDown = (item, index, event) => {
            // Prevent default behavior
            event.preventDefault();
        };

        const handleItemDoubleClick = (item, index, event) => {
            if (!multi.value) {
                selectedItems.value = [item.id];
                returnSelectedFile();
            }
        };

        // Handle file uploads
        const clickMenuUpload = async () => {
            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.multiple = true;
            fileInput.accept = config.value.allowed_types.map(type => `.${type}`).join(',');

            fileInput.onchange = async (event) => {
                const uploadedFiles = event.target.files;
                if (uploadedFiles.length === 0) return;

                isLoading.value = true;

                if (autocrop.value || resizes.value.length <= 0 && !watermark.value){
                  const config = {
                    resizes: resizes.value,
                    watermark: watermark.value,
                    watermark_img: watermark_img.value,
                    output: {
                      jpg: { name: 'jpg', q: 80 },
                      webp: { name: 'jpg.webp', q: 80 }
                    },
                    original: true
                  };

                  const now = new Date();
                  const year = now.getFullYear();
                  const month = String(now.getMonth() + 1).padStart(2, '0');
                  const day = String(now.getDate()).padStart(2, '0');
                  const path = `${year}:${month}:${day}`;

                  const formData = new FormData();
                  formData.append('path', path);
                  formData.append('config', JSON.stringify(config));
                  for (let i = 0; i < uploadedFiles.length; i++) {
                    formData.append('files[]', uploadedFiles[i]);
                  }

                  try {
                    const response = await fetch(`${FILES_API}upload/`, {
                      method: 'POST',
                      body: formData,
                    });

                    const result = await response.json();
                    if (result.status === 'success') {
                      await fetchFiles();
                    } else {
                      console.error('Error:', result.error);
                    }
                  } catch (error) {
                    console.error('API Error:', error);
                  } finally {
                    isLoading.value = false;
                  }
                } else {
                    let imageItems = [];
                    for (let i = 0; i < uploadedFiles.length; i++) {
                        imageItems.push({
                            src: URL.createObjectURL(uploadedFiles[i]),
                            name: uploadedFiles[i].name,
                        });
                    }

                    const config = {
                        server: FILES_API + 'saves/',
                        images: imageItems,
                        sizes: resizes.value.map(size => ({
                            width: size.width,
                            height: size.height,
                            watermark: watermark.value ? {
                                src: link_img(watermark_img.value),
                                position: "bottom-right",
                                padding: 20,
                                opacity: 0.5
                            } : false
                        })),
                        output: {
                            jpg: { name: 'jpg', q: 80 },
                            webp: { name: 'jpg.webp', q: 80 }
                        },
                        original: true
                    };

                    if (watermark.value && watermark_img.value) {
                        config.watermark = {
                            src: link_img(watermark_img.value),
                            position: "bottom-right",
                            padding: 20,
                            opacity: 0.5
                        };
                    }

                    const imageEditor = new iMagify(config);

                    imageEditor.onUpload(function(files) {
                        console.log("Upload xong 1 file, kết quả:", files);
                    });

                    imageEditor.onComplete(function(results) {
                        console.log("Upload hoàn tất, kết quả:", results);
                        fetchFiles();
                        imageItems.forEach(item => URL.revokeObjectURL(item.src));
                        isLoading.value = false;
                    });
                }
            };

            fileInput.click();
        };

        onMounted(() => {
          setTimeout(function(){
            fetchFiles();
          }, 500);
        });

        const link_img = (path) => {
          return `/uploads/${path}`;
        };

        return {
          page_now,
          page_limit,
          page_isnext,
          md5,
          page_search,
          page_image,
          page_sort,
          viewType,
          returnSelectedFile,
          field_input,
          multi,
          wisy,
          type,
          extensions,
          token,
          files,
          sortedData,
          selectItem,
          deselectItem,
          selectionMode,
          handleClickOutside,
          handleRightClickOutside,
          handleLeftClickOutside,
          toggleView,
          contextMenuVisible,
          contextMenuPosition,
          selectedItem,
          selectedItems,
          isSelected,
          toggleItemSelection,
          deleteSelectedItems,
          showContextMenu,
          clickMenuDownload,
          clickMenuRename,
          clickMenuDelete,
          clickMenuUpload,
          clickPrevPage,
          clickNextPage,
          rootElement,
          checkImages,
          link_img,
          // Selection handling
          isSelecting,
          selectionRectangle,
          selectionRectangleStyles,
          startSelection,
          whileSelecting,
          endSelection,
          handleItemClick,
          handleItemMouseDown,
          lastClickedIndex,
          searchDebounceTimer,
          fetchDebounceTimer,
          isLoading,
          selectedOrder,
          updateSelectedOrder,
          value,
          processInitialValue,
          handleItemDoubleClick
        };
      },
    }).component('media-library', {
      template: '#media-library-template',
    }).mount('#app');
  </script>

  <style scoped>
  /* Theme Customizer Integration */
  :root {
    --background: hsl(var(--background));
    --foreground: hsl(var(--foreground));
    --primary: hsl(var(--primary));
    --primary-foreground: hsl(var(--primary-foreground));
    --secondary: hsl(var(--secondary));
    --secondary-foreground: hsl(var(--secondary-foreground));
    --muted: hsl(var(--muted));
    --muted-foreground: hsl(var(--muted-foreground));
    --accent: hsl(var(--accent));
    --accent-foreground: hsl(var(--accent-foreground));
    --destructive: hsl(var(--destructive));
    --destructive-foreground: hsl(var(--destructive-foreground));
    --border: hsl(var(--border));
    --input: hsl(var(--input));
    --ring: hsl(var(--ring));
    --card: hsl(var(--card));
    --card-foreground: hsl(var(--card-foreground));
    --popover: hsl(var(--popover));
    --popover-foreground: hsl(var(--popover-foreground));
    --radius: 6px;
  }
  
  /* Custom styles for media library */
  .context-menu {
    background-color: var(--popover);
    border: 1px solid var(--border);
    color: var(--popover-foreground);
    z-index: 1000;
  }
  
  .context-menu ul li:hover {
    background-color: var(--accent);
    color: var(--accent-foreground);
  }
  </style>
<!-- </body>
</html> -->