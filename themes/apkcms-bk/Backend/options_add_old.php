<?php

namespace System\Libraries;

use App\Libraries\Fastlang as Flang;
use System\Libraries\Session;
use System\Libraries\Render;
Render::block('Backend\Header', ['layout' => 'default']);


// Lấy flash message (success, error)
$success = Session::has_flash('success') ? Session::flash('success') : '';
$error   = Session::has_flash('error') ? Session::flash('error') : '';

// Lấy giá trị group từ URL
$defaultGroup = isset($_GET['group']) ? $_GET['group'] : '';

// Display errors if any exist
if (isset($error) && !empty($error)) {
  echo '<div class="mb-4 p-4 bg-red-100 text-red-700 rounded">';
  echo '<ul>';
  foreach ($error as $fieldErrors) {
    foreach ($fieldErrors as $errorMessage) {
      echo '<li>' . htmlspecialchars($errorMessage) . '</li>';
    }
  }
  echo '</ul>';
  echo '</div>';
}
// Determine if we're editing an existing post
$editLink = admin_url('options/edit/');
?>
<!-- [ Main Content ] start -->
<div class="pc-container">
  <div class="pc-content overflow-hidden">
    <!-- [ breadcrumb ] start -->
    <?php Render::block('Backend\PageTitle', [
          'layout'     => 'default', 
          'title'      => Flang::_e('create option'),
          'breadcrumb' => [
              ['title' => Flang::_e('dashboard'), 'url' => admin_url('home')],
              ['title' => Flang::_e('options'), 'url' => admin_url('options')],
          ],
      ]); ?>
    <!-- [ breadcrumb ] end -->
    <div class="card">
      <!-- Card Header: Page Title & Breadcrumb -->
      <div class="card-header">
        <div class="page-block">
            <h2 class="mb-0"><?= Flang::_e('create option') ?></h2>
        </div>
      </div>
      <!-- End Card Header -->

      <!-- Card Body: Content -->
      <div class="card-body">
        <!-- Notification Success -->
        <?php if (!empty($success)): ?>
          <div class="alert alert-success">
            <?= htmlspecialchars($success); ?>
          </div>
        <?php endif; ?>

        <!-- Notification Error -->
        <?php if (!empty($error)): ?>
          <div class="alert alert-danger">
            <?= htmlspecialchars($error); ?>
          </div>
        <?php endif; ?>

        <div id="app" class="container mx-auto p-4">
          <div id="message">
            <?php
            function convertErrorsToHtml($errors)
            {
              $result = [];
              function flattenErrors($arr, &$result)
              {
                foreach ($arr as $key => $value) {
                  if (is_array($value)) {
                    flattenErrors($value, $result);
                  } else {
                    $result[] = $value;
                  }
                }
              }

              flattenErrors($errors, $result);

              // Tạo HTML sử dụng Tailwind CSS
              $html = '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">';
              $html .= '<p class="font-bold">' . Flang::_e('errors') . '</p>';
              $html .= '<ul class="mt-2 pl-5 list-disc">';

              foreach ($result as $key => $error) {
                $html .= '<li class="text-sm">' . $key . ': ' . htmlspecialchars($error) . '</li>';
              }

              $html .= '</ul></div>';
              return $html;
            }
            if (isset($errors)) {
              echo convertErrorsToHtml($errors);
            }
            ?>
          </div>
          <!-- Form thêm Options sẽ được đặt ở đây -->
          <options-form></options-form>
        </div>
      </div><!-- end card-body -->
    </div><!-- end card -->
  </div><!-- end pc-content -->
</div><!-- end pc-container -->
<?php
Render::block('Backend\Footer', ['layout' => 'default']);
?>
<script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js"></script>

<!-- Script cho Vue component -->
<?php
if (!isset($allPostTypes)) {
    $allPostTypes = [];
}
// $allPostTypes = array_map(function($postType) {
//     return [
//         'id' => $postType['id'],
//         'name' => $postType['name'],
//         'slug' => $postType['slug'],
//     ];
// }, $allPostTypes);
if (isset($options) && isset($options[0]['id'])) {
    $isEdit = true;
    $actionLink = admin_url('options/edit/' . $options[0]['id']);
    $deleteLink = admin_url('options/delete/' . $options[0]['id']);
} else {
    $options = [];
    $isEdit = false;
    $actionLink = admin_url('options/add');
}
if (!empty($_POST['list_options'])) {
    $options = $_POST['list_options'];
}
if (is_string($options)) {
    $options = json_decode($options, true) ?: [];
}
?>

<script>
    Vue.component('field-item', {
        props: ['field', 'availableFieldTypes', 'postTypesList', 'index', 'fieldsArray', 'optionGroups'],

        data() {
            return {
                allowed_types: <?= json_encode( config('files')['allowed_types'] ?? ['jpg', 'jpeg', 'png', 'gif', 'webp'] ) ?>,
                images_types: <?= json_encode( config('files')['images_types'] ?? ['jpg', 'jpeg', 'png', 'gif', 'webp'] ) ?>,
                isCollapsed: this.field.collapsed || false,
                touchData: {
                startY: 0,
                index: null,
                dragging: false,
                },
            };
        },
        created() {
            // Set giá trị mặc định cho option_group nếu có từ URL
            if (this.field && !this.field.option_group && '<?= $defaultGroup ?>') {
                this.field.option_group = '<?= $defaultGroup ?>';
            }
        },
        methods: {
            confirmRemove() {
                if (confirm('Bạn có chắc chắn muốn xóa không?')) {
                    this.$emit('remove');
                }
            },
            dragStart(index, event) {
                event.dataTransfer.setData('optionIndex', index);
            },
            drop(dropIndex, event, optionsArray) {
                const dragIndex = parseInt(event.dataTransfer.getData('optionIndex'));
                if (dragIndex === dropIndex) return;
                const draggedOption = optionsArray.splice(dragIndex, 1)[0];
                optionsArray.splice(dropIndex, 0, draggedOption);
            },

            /* --- Xử lý cảm ứng cho mobile --- */
            touchStart(index, event) {
                this.touchData.startY = event.touches[0].clientY;
                this.touchData.index = index;
                this.touchData.dragging = true;
            },
            touchMove(index, event, optionsArray) {
                // Ngăn chặn scroll khi kéo
                event.preventDefault();
                const currentY = event.touches[0].clientY;
                const deltaY = currentY - this.touchData.startY;
                // Điều chỉnh ngưỡng di chuyển, có thể tinh chỉnh theo nhu cầu
                const threshold = 30;
                let newIndex = this.touchData.index;
                if (deltaY > threshold && this.touchData.index < optionsArray.length - 1) {
                newIndex = this.touchData.index + 1;
                } else if (deltaY < -threshold && this.touchData.index > 0) {
                newIndex = this.touchData.index - 1;
                }
                if (newIndex !== this.touchData.index) {
                const draggedOption = optionsArray.splice(this.touchData.index, 1)[0];
                optionsArray.splice(newIndex, 0, draggedOption);
                // Cập nhật lại chỉ số và điểm bắt đầu
                this.touchData.index = newIndex;
                this.touchData.startY = currentY;
                }
            },
            touchEnd(index, event, optionsArray) {
                this.touchData.dragging = false;
                this.touchData.index = null;
                this.touchData.startY = 0;
            },
            addInputOption() {
                this.field.options.push({
                    value: '',
                    label: '',
                    is_group: false
                });
            },
            removeInputOption(optionIndex) {
                this.field.options.splice(optionIndex, 1);
            },
            addRepeaterField() {
                const uniqueId = Date.now(); // Assign unique ID based on current time
                this.field.fields.push({
                    id: uniqueId, // Add the unique ID
                    type: 'Text',
                    label: '',
                    name: '',
                    description: '',
                    status: true,
                    save_file: false,
                    visibility: true,
                    css_class: '',
                    placeholder: '',
                    default_value: '',
                    order: this.field.fields.length + 1,
                    min: null,
                    max: null,
                    options: [],
                    rows: null,
                    allow_types: [],
                    max_file_size: null,
                    multiple: false,
                    post_type_reference: null,
                    bidirectional_sync: 1,
                    table_save_data_reference: 0,
                    post_status_filter: 'all',
                    post_query_filter: '',
                    post_query_sort: '',
                    synchronous: true,
                    fields: [],
                    collapsed: false,
                    width_value: 100,
                    width_unit: '%',
                    position: 'left',
                });
            },
            removeRepeaterField(index) {
                this.field.fields.splice(index, 1);
            },
            toggleCollapse() {
                this.isCollapsed = !this.isCollapsed;
                this.field.collapsed = this.isCollapsed;
            },
            moveUp() {
                if (this.index > 0) {
                    const temp = this.fieldsArray[this.index - 1];
                    Vue.set(this.fieldsArray, this.index - 1, this.field);
                    Vue.set(this.fieldsArray, this.index, temp);
                }
            },
            moveDown() {
                if (this.index < this.fieldsArray.length - 1) {
                    const temp = this.fieldsArray[this.index + 1];
                    Vue.set(this.fieldsArray, this.index + 1, this.field);
                    Vue.set(this.fieldsArray, this.index, temp);
                }
            },
            addServer() {
                if (!this.field.servers) {
                    this.$set(this.field, 'servers', []);
                }
                this.field.servers.push({
                    url: '',
                    token: ''
                });
            },
            removeServer(index) {
                if (confirm('<?php echo Flang::_e('confirm_remove_server'); ?>')) {
                    this.field.servers.splice(index, 1);
                }
            },
            addResize() {
                if (!this.field.resizes) {
                    this.$set(this.field, 'resizes', []);
                }
                this.field.resizes.push({
                    width: '',
                    height: ''
                });
            },
            removeResize(index) {
                this.field.resizes.splice(index, 1);
            },
        },
        watch: {
            'field.watermark'(newVal) {
                if (newVal) {
                    setTimeout(() => {
                        initImages('watermark', false);
                    }, 500);
                }
            }
        },
        mounted() {
            // Xét trường hợp ban đầu
            if (this.field.watermark) {
                setTimeout(() => {
                    initImages('watermark', false);
                }, 500);
            }
        },
        template: `
        <div class="mb-2 p-2 border rounded">
            <div class="flex justify-between items-center mb-3">
                <div class="flex items-center">
                    <button @click="toggleCollapse" type="button" class="mr-2 text-sm focus:outline-none">
                        <span v-if="isCollapsed" style="font-size:20px;">[+]</span>
                        <span v-else style="font-size:20px;">[-]</span>
                    </button>
                    <h3 class="font-semibold text-sm">Field: {{ field.label || '<?php echo Flang::_e('no_name'); ?>' }}</h3>
                </div>
                <div class="flex items-center space-x-2">
                    <button @click="moveUp" type="button" class="text-gray-500 hover: text-sm focus:outline-none" :disabled="index === 0">
                        ↑
                    </button>
                    <button @click="moveDown" type="button" class="text-gray-500 hover: text-sm focus:outline-none" :disabled="index === fieldsArray.length - 1">
                        ↓
                    </button>
                    <button @click="confirmRemove" type="button" class="text-red-500 text-sm focus:outline-none">
                        🗑️
                    </button>
                </div>
            </div>
            
            <div v-if="!isCollapsed">
                <!-- <?php echo Flang::_e('field_details'); ?> -->
                <!-- <?php echo Flang::_e('field_type'); ?> -->

                <div class="flex flex-col xl:flex-row xl:space-x-0 -mx-0 xl:-mx-2 mb-4">
                    <div class="w-full xl:w-1/4 px-0 xl:px-2 mb-4 xl:mb-0 floating-label relative wrap-label ">
                        <label for="name" class="block mb-2 font-medium text-sm leading-5 light:text-gray-900 ">
                            <?php echo Flang::_e('field_type'); ?><span class="text-red-500">*</span>
                        </label>
                        <select v-model="field.type" class="border border-gray-300 light:text-gray-900 bg-transparent text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                            <option disabled value=""><?php echo Flang::_e('choose_field_type'); ?></option>
                            <option v-for="type in availableFieldTypes" :value="type"><?php echo Flang::_e('{{ type }}'); ?></option>
                        </select>
                    </div>
                    <!-- Option Group Selection - Thêm phần này -->
                    <div class="w-full xl:w-1/4 px-0 xl:px-2 mb-4 xl:mb-0 floating-label relative wrap-label">
                        <label for="option_group" class="block mb-2 font-medium text-sm leading-5 light:text-gray-900">
                            <?php echo Flang::_e('option_group'); ?><span class="text-red-500">*</span>
                        </label>
                        <select v-model="field.option_group" class="border border-gray-300 light:text-gray-900 bg-transparent text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                            <option disabled value=""><?php echo Flang::_e('select_option_group'); ?></option>
                            <option v-for="group in optionGroups" :value="group.value">{{ group.label }}</option>
                        </select>
                    </div>
                    <div class="w-full xl:w-1/4 px-0 xl:px-2 mb-4 xl:mb-0 floating-label relative wrap-label ">
                        <label for="field_label" class="block mb-2 font-medium text-sm leading-5 light:text-gray-900">
                            <?php echo Flang::_e('label'); ?><span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="field.label"
                            type="text"
                            id="field_label"
                            name="label"
                            placeholder="<?php echo Flang::_e('enter_label'); ?>"
                            required
                            class="form-control w-full px-3 py-2 border rounded-md text-sm focus:ring-blue-500 focus:border-blue-500 bg-transparent"
                        >
                    </div>
                    <div class="w-full xl:w-1/4 px-0 xl:px-2 floating-label relative wrap-label ">
                        <label for="name" class="block mb-2 font-medium text-sm leading-5 light:text-gray-900">
                            <?php echo Flang::_e('name_slug'); ?><span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="field.name"
                            type="text"
                            id="name"
                            name="name"
                            placeholder="<?php echo Flang::_e('enter_name'); ?>"
                            required
                            class="form-control w-full px-3 py-2 border rounded-md text-sm focus:ring-blue-500 focus:border-blue-500 bg-transparent"
                        >
                    </div>
                </div>

                <div class="field floating-label mb-4 relative wrap-description">
                    <label for="field_description" class="block mb-2 font-medium text-sm leading-5 light:text-gray-900 dark:bg-none bg-white">
                        <?php echo Flang::_e('description'); ?>
                    </label>
                    <input
                        v-model="field.description"
                        type="text"
                        id="field_description"
                        name="description"
                        placeholder="<?php echo Flang::_e('enter_description'); ?>"
                        class="form-control w-full px-3 py-2 border rounded-md text-sm focus:ring-blue-500 focus:border-blue-500 !bg-transparent"
                    />
                </div>

                <div class="flex flex-col xl:flex-row xl:space-x-0 -mx-0 xl:-mx-2 mb-4">
                    <div class="flex items-center mb-1 space-x-4 w-full xl:w-1/2 px-0 xl:px-2 mb-4 xl:mb-0">
                        <div class="field mb-4">
                            <label class="inline-flex items-center cursor-pointer">
                                <input v-model="field.status" type="checkbox" class="sr-only peer">
                                <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600">
                                </div>
                                <span class="ms-3 text-sm font-medium leading-5"><?php echo Flang::_e('status'); ?></span>
                            </label>
                        </div>
                    </div>
                    <div class="flex items-center mb-1 space-x-4 w-full xl:w-1/2 px-0 xl:px-2 mb-4 xl:mb-0">
                        <div class="field mb-4">
                            <label class="inline-flex items-center cursor-pointer">
                                <input v-model="field.save_file" type="checkbox" class="sr-only peer">
                                <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600">
                                </div>
                                <span class="ms-3 text-sm font-medium leading-5"><?php echo Flang::_e('save_file'); ?></span>
                            </label>
                        </div>
                    </div>
                    <div class="flex items-center mb-1 space-x-4 w-full xl:w-1/2 px-0 xl:px-2 mb-4 xl:mb-0">
                        <div class="field mb-4">
                            <label class="inline-flex items-center cursor-pointer">
                                <input v-model="field.synchronous" type="checkbox" class="sr-only peer">
                                <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600">
                                </div>
                                <span class="ms-3 text-sm font-medium leading-5"><?php echo Flang::_e('synchronous'); ?></span>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center mb-1 space-x-4 w-full xl:w-1/2 px-0 xl:px-2 mb-4 xl:mb-0">
                        <div class="field mb-4">
                            <label class="inline-flex items-center cursor-pointer">
                                <input v-model="field.visibility" type="checkbox" class="sr-only peer">
                                <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600">
                                </div>
                                <span class="ms-3 text-sm font-medium leading-5"><?php echo Flang::_e('visibility'); ?></span>
                            </label>
                        </div>
                    </div>
                
                    <div v-if="['Text', 'Email', 'Number', 'Password', 'Date', 'DateTime', 'URL'].includes(field.type)" class="flex space-x-2 mb-1 w-full xl:w-1/2 px-0 xl:px-2 mb-4 xl:mb-0">
                        <div class="field floating-label mb-4 relative wrap-min w-1/2">
                            <label for="field_min" class="block mb-2 font-medium text-sm leading-5 light:text-gray-900 dark:bg-none bg-white">
                                <?php echo Flang::_e('min'); ?>
                            </label>
                            <input
                                v-model.number="field.min"
                                type="number"
                                id="field_min"
                                name="min"
                                placeholder="<?php echo Flang::_e('enter_min'); ?>"
                                class="form-control w-full px-3 py-2 border rounded-md text-sm focus:ring-blue-500 focus:border-blue-500 !bg-transparent"
                            >
                        </div>
                        <div class="field floating-label mb-4 relative wrap-max w-1/2">
                            <label for="field_max" class="block mb-2 font-medium text-sm leading-5 light:text-gray-900 dark:bg-none bg-white">
                                <?php echo Flang::_e('max'); ?>
                            </label>
                            <input
                                v-model.number="field.max"
                                type="number"
                                id="field_max"
                                name="max"
                                placeholder="<?php echo Flang::_e('enter_max'); ?>"
                                class="form-control w-full px-3 py-2 border rounded-md text-sm focus:ring-blue-500 focus:border-blue-500 !bg-transparent"
                            >
                        </div>
                    </div>

                </div>
            
                <div class="flex flex-col xl:flex-row xl:space-x-0 -mx-0 xl:-mx-2 mb-4">
                    <div class="w-full xl:w-1/2 px-0 xl:px-2 mb-4 xl:mb-0 floating-label relative wrap-label wrap-default_value">
                        <label for="field_default_value" class="block mb-2 font-medium text-sm leading-5 light:text-gray-900">
                            <?php echo Flang::_e('default_value'); ?>
                        </label>
                        <input
                            v-model="field.default_value"
                            type="text"
                            id="field_default_value"
                            name="default_value"
                            placeholder="<?php echo Flang::_e('enter_default_value'); ?>"
                            class="form-control w-full px-3 py-2 border rounded-md text-sm focus:ring-blue-500 focus:border-blue-500 bg-transparent"
                        >
                    </div>

                    <div class="w-full xl:w-1/4 px-0 xl:px-2 mb-4 xl:mb-0 floating-label relative wrap-label wrap-placeholder">
                        <label for="field_placeholder" class="block mb-2 font-medium text-sm leading-5 light:text-gray-900">
                            <?php echo Flang::_e('placeholder'); ?>
                        </label>
                        <input
                            v-model="field.placeholder"
                            type="text"
                            id="field_placeholder"
                            name="placeholder"
                            placeholder="<?php echo Flang::_e('enter_placeholder'); ?>"
                            class="form-control w-full px-3 py-2 border rounded-md text-sm focus:ring-blue-500 focus:border-blue-500 bg-transparent"
                        >
                    </div>
                    <div class="w-full xl:w-1/4 px-0 xl:px-2 mb-4 xl:mb-0 floating-label relative wrap-label wrap-css_class">
                        <label for="field_css_class" class="block mb-2 font-medium text-sm leading-5 light:text-gray-900">
                            <?php echo Flang::_e('css_class_name'); ?>
                        </label>
                        <input
                            v-model="field.css_class"
                            type="text"
                            id="field_css_class"
                            name="css_class"
                            placeholder="<?php echo Flang::_e('enter_css_class_name'); ?>"
                            class="form-control w-full px-3 py-2 border rounded-md text-sm focus:ring-blue-500 focus:border-blue-500 bg-transparent"
                        >
                    </div>
                </div>


                <div class="flex flex-col xl:flex-row xl:space-x-0 -mx-0 xl:-mx-2 mb-4">
                    <div class="field floating-label mb-4 relative wrap-width_input w-full xl:w-1/2 px-0 xl:px-2 mb-4 xl:mb-0">
                        <label class="block mb-2 font-medium text-sm leading-5 light:text-gray-900 dark:bg-none bg-white">
                            <?php echo Flang::_e('width_input'); ?>
                        </label>
                        <div class="flex space-x-2">
                            <input
                                v-model.number="field.width_value"
                                type="number"
                                id="field_width_value"
                                name="width_value"
                                placeholder="<?php echo Flang::_e('enter_width'); ?>"
                                class="form-control w-1/2 px-3 py-2 border rounded-md text-sm focus:ring-blue-500 focus:border-blue-500 !bg-transparent"
                            >
                            <select
                                v-model="field.width_unit"
                                id="field_width_unit"
                                name="width_unit"
                                class="form-control w-1/2 px-3 py-2 border rounded-md text-sm focus:ring-blue-500 focus:border-blue-500 bg-transparent"
                            >
                                <option value="px">px</option>
                                <option value="%">%</option>
                                <option value="em">em</option>
                                <option value="rem">rem</option>
                                <option value="vw">vw</option>
                                <option value="vh">vh</option>
                            </select>
                        </div>
                    </div>
                    <div class="w-full xl:w-1/2 px-0 xl:px-2 mb-4 xl:mb-0 field floating-label mb-4 relative">
                        <label class=" block mb-2 font-medium text-sm leading-5 light:text-gray-900 dark:bg-none bg-white">
                            <?php echo Flang::_e('position'); ?>
                        </label>
                        <select
                            v-model="field.position"
                            id="field_position"
                            name="position"
                            class="form-control px-3 py-2 border rounded-md text-sm focus:ring-blue-500 focus:border-blue-500 bg-transparent"
                        >
                            <option value="top">Top</option>
                            <option value="left">Left</option>
                            <option value="right">Right</option>
                            <option value="bottom">Bottom</option>
                        </select>
                    </div>
                </div>
            

                <div v-if="field.type === 'Textarea'" class="field floating-label mb-4 relative wrap-rows">
                    <label for="field_rows" class="block mb-2 font-medium text-sm leading-5 light:text-gray-900">
                        <?php echo Flang::_e('rows'); ?><span v-if="field.required" class="text-red-500">*</span>
                    </label>
                    <input
                        v-model.number="field.rows"
                        type="number"
                        id="field_rows"
                        name="rows"
                        placeholder="<?php echo Flang::_e('enter_number_of_rows'); ?>"
                        :required="field.required"
                        class="form-control w-full px-3 py-2 border rounded-md text-sm focus:ring-blue-500 focus:border-blue-500 !bg-transparent"
                    >
                </div>

                <!-- Khối cho Checkbox và Radio -->
                <div v-if="['Checkbox', 'Radio'].includes(field.type)" class="mb-1">
                    <label class="block text-sm font-bold"><?php echo Flang::_e('options'); ?></label>
                    <div class="flex flex-wrap -mx-1">
                        <div
                        v-for="(option, optionIndex) in field.options"
                        :key="optionIndex"
                        class="w-1/2 px-1 mb-1 flex items-center option-item"
                        draggable="true"
                        @dragstart="dragStart(optionIndex, $event)"
                        @dragover.prevent
                        @drop="drop(optionIndex, $event, field.options)"
                        @touchstart="touchStart(optionIndex, $event)"
                        @touchmove="touchMove(optionIndex, $event, field.options)"
                        @touchend="touchEnd(optionIndex, $event, field.options)"
                        >
                        <!-- Icon kéo thả -->
                        <span class="cursor-move mr-2">⇅</span>
                        <input
                            v-model="option.label"
                            placeholder="<?php echo Flang::_e('label'); ?>"
                            class="w-1/2 mr-2 ml-2 px-3 py-1 border rounded-md text-sm focus:ring-blue-500 focus:border-blue-500"
                        >
                        <input
                            v-model="option.value"
                            placeholder="<?php echo Flang::_e('value'); ?>"
                            class="w-1/2 mr-2 px-3 py-1 border rounded-md text-sm focus:ring-blue-500 focus:border-blue-500"
                        >
                        <button @click="removeInputOption(optionIndex, field.options)" type="button" class="text-red-500 text-sm ml-1 mr-2" title="<?php echo Flang::_e('delete'); ?>">🗑️</button>
                        </div>
                    </div>
                    <button @click="addInputOption(field.options)" type="button" class="mt-1 px-2 py-1 bg-blue-500 text-white text-sm rounded"><i data-feather="plus"></i><?php echo Flang::_e('add_option'); ?></button>
                    </div>

                    <!-- Khối cho Select -->
                    <div v-if="field.type === 'Select'" class="mb-1">
                    <label class="block text-sm"><?php echo Flang::_e('options'); ?></label>
                    <div class="flex flex-wrap -mx-1">
                        <div
                        v-for="(option, optionIndex) in field.options"
                        :key="optionIndex"
                        class="w-full px-1 mb-1 flex items-center option-item"
                        draggable="true"
                        @dragstart="dragStart(optionIndex, $event)"
                        @dragover.prevent
                        @drop="drop(optionIndex, $event, field.options)"
                        @touchstart="touchStart(optionIndex, $event)"
                        @touchmove="touchMove(optionIndex, $event, field.options)"
                        @touchend="touchEnd(optionIndex, $event, field.options)"
                        >
                        <!-- Icon kéo thả -->
                        <span class="cursor-move mr-2">⇅</span>
                        <input v-model="option.is_group" type="checkbox" class="mr-1">
                        <label class="text-sm mr-1"><?php echo Flang::_e('group'); ?></label>
                        <input
                            v-model="option.label"
                            placeholder="<?php echo Flang::_e('label'); ?>"
                            class="w-1/3 pl-1 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500"
                        >
                        <input
                            v-model="option.value"
                            v-if="!option.is_group"
                            placeholder="<?php echo Flang::_e('value'); ?>"
                            class="w-1/3 pl-1 border border-gray-300 rounded-md text-sm mr-1 focus:ring-blue-500 focus:border-blue-500"
                        >
                        <button @click="removeInputOption(optionIndex, field.options)" type="button" class="text-red-500 text-sm ml-1" title="<?php echo Flang::_e('delete'); ?>">🗑️</button>
                        </div>
                    </div>
                    <button @click="addInputOption(field.options)" type="button" class="mt-1 px-2 py-1 bg-blue-500 text-white text-sm rounded"><i data-feather="plus"></i><?php echo Flang::_e('add_option'); ?></button>
                    <div class="flex items-center mt-1">
                        <label class="inline-flex items-center cursor-pointer">
                        <input v-model="field.multiple" type="checkbox" class="sr-only peer">
                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                        </label>
                        <span class="ml-3 text-sm"><?php echo Flang::_e('multiple'); ?></span>
                    </div>
                </div>

                <div v-if="['File'].includes(field.type)" class="mb-1">
                    <div class="flex items-center mb-1">
                        <label class="inline-flex items-center cursor-pointer">
                            <input v-model="field.multiple" type="checkbox" class="sr-only peer">
                            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600">
                            </div>
                        </label>
                        <span class="ml-3 text-sm"><?php echo Flang::_e('multiple'); ?></span>
                    </div>
                    <div class="field floating-label mb-4 relative wrap-max-file-size">
                        <label for="max_file_size" class="block mb-2 font-medium text-sm leading-5 light:text-gray-900">
                            <?php echo Flang::_e('max_file_size_mb'); ?><span v-if="field.required" class="text-red-500">*</span>
                        </label>
                        <input
                            v-model.number="field.max_file_size"
                            type="number"
                            id="max_file_size"
                            name="max_file_size"
                            placeholder="<?php echo Flang::_e('enter_max_file_size'); ?>"
                            :required="field.required"
                            class="form-control w-full px-3 py-2 border rounded-md text-sm focus:ring-blue-500 focus:border-blue-500 bg-transparent"
                        >
                    </div>

                    <label class="block  text-sm"><?php echo Flang::_e('allow_types'); ?></label>
                    <div class="flex flex-wrap -mx-1 mb-1">
                        <div v-for="type in allowed_types" 
                            :key="type" 
                            class="w-1/4 px-1 mb-1 flex items-center">
                            <label class="inline-flex items-center cursor-pointer">
                                <input v-model="field.allow_types" :value="type" type="checkbox" class="sr-only peer">
                                <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600">
                                </div>
                            </label>
                            <span class="ml-2 text-sm">{{ type }}</span>
                        </div>
                    </div>

                </div>
                <div v-if="['Image'].includes(field.type)" class="mb-1">
                    <div class="flex items-center mb-1">
                        <label class="inline-flex items-center cursor-pointer">
                            <input v-model="field.multiple" type="checkbox" class="sr-only peer">
                            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600">
                            </div>
                        </label>
                        <span class="ml-3 text-sm"><?php echo Flang::_e('multiple'); ?></span>
                    </div>

                    <label class="block  text-sm"><?php echo Flang::_e('allow_types'); ?></label>
                    <div class="flex flex-wrap -mx-1 mb-1">
                        <div v-for="type in images_types" 
                            :key="type" 
                            class="w-1/4 px-1 mb-1 flex items-center">
                            <label class="inline-flex items-center cursor-pointer">
                                <input v-model="field.allow_types" :value="type" type="checkbox" class="sr-only peer">
                                <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600">
                                </div>
                            </label>
                            <span class="ml-2 text-sm">{{ type }}</span>
                        </div>
                    </div>

                    <div class="flex flex-wrap mb-4">
                        
                    </div>

                    <div class="field floating-label mb-4 relative wrap-max-file-size">
                        <label for="max_file_size" class="block mb-2 font-medium text-sm leading-5 light:text-gray-900">
                            <?php echo Flang::_e('max_file_size_mb'); ?><span v-if="field.required" class="text-red-500">*</span>
                        </label>
                        <input
                            v-model.number="field.max_file_size"
                            type="number"
                            id="max_file_size"
                            name="max_file_size"
                            placeholder="<?php echo Flang::_e('enter_max_file_size'); ?>"
                            :required="field.required"
                            class="form-control w-full px-3 py-2 border rounded-md text-sm focus:ring-blue-500 focus:border-blue-500 bg-transparent"
                        >
                    </div>

                    

                    <!-- Thêm phần cấu hình resize cho Image -->
                    <div v-if="field.type === 'Image'" class="mt-4">
                        <h4 class="text-sm font-semibold mb-2"><?php echo Flang::_e('image_resize'); ?></h4>
                        
                        <div v-for="(resize, resizeIndex) in field.resizes" :key="resizeIndex" 
                            class="mb-4 p-4 border rounded-lg bg-gray-50">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium"><?php echo Flang::_e('resize'); ?> #{{resizeIndex + 1}}</span>
                                <button @click="removeResize(resizeIndex)" 
                                        type="button"
                                        class="text-red-500 hover:text-red-700">
                                    🗑️
                                </button>
                            </div>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium  mb-1">
                                        <?php echo Flang::_e('width'); ?>
                                    </label>
                                    <input
                                        v-model.number="resize.width"
                                        type="number"
                                        class="form-control w-full px-3 py-2 border rounded-md text-sm focus:ring-blue-500 focus:border-blue-500 bg-transparent"
                                        :placeholder="'<?php echo Flang::_e('enter_width'); ?>'"
                                    >
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium  mb-1">
                                        <?php echo Flang::_e('height'); ?>
                                    </label>
                                    <input
                                        v-model.number="resize.height"
                                        type="number"
                                        class="form-control w-full px-3 py-2 border rounded-md text-sm focus:ring-blue-500 focus:border-blue-500 bg-transparent"
                                        :placeholder="'<?php echo Flang::_e('enter_height'); ?>'"
                                    >
                                </div>
                            </div>
                        </div>
                        
                        <button @click="addResize" 
                                type="button"
                                class="mt-2 px-4 py-2 bg-blue-500 text-white rounded-md text-sm hover:bg-blue-600">
                            <?php echo Flang::_e('add_resize'); ?>
                        </button>

                        <!-- Thêm phần cấu hình autocrop và watermark -->
                        <div class="mt-4">
                            <div class="flex items-center mb-1">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input v-model="field.autocrop" type="checkbox" class="sr-only peer">
                                    <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600">
                                    </div>
                                </label>
                                <span class="ml-3 text-sm"><?php echo Flang::_e('autocrop'); ?></span>
                            </div>

                            <div class="flex items-center mb-1">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input v-model="field.watermark" type="checkbox" class="sr-only peer">
                                    <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600">
                                    </div>
                                </label>
                                <span class="ml-3 text-sm"><?php echo Flang::_e('watermark'); ?></span>
                            </div>

                            <div v-if="field.watermark" class="mt-4">
                                <div class="fieldset px-1 w-full mb-8 wrap-banner" style="width:100%;">
                                    <!-- Upload Area -->
                                    <div id="upload-area-watermark" :class="field.watermark_img ? 'hidden' : ''" class="form-control flex flex-col items-center justify-center w-full h-48 p-4 rounded-lg border-2 border-dashed border-gray-300" style="">
                                        <div class="text-center cursor-pointer" id="open-library-button-watermark">
                                            <div class="flex justify-center text-gray-400 mb-4">
                                                <i data-lucide="cloud-upload" class="w-10 h-10"></i>
                                            </div>
                                            <p class="form-control border-none">
                                                Click để chọn ảnh từ thư viện
                                            </p>
                                            <div class="form-control rounded-md border-none cursor-pointer">
                                                <!-- Chọn từ Thư viện -->
                                            </div>
                                        </div>
                                    </div>

                                    <input type="hidden" id="watermark_data" name="watermark" v-model="field.watermark_img">
                                    <!-- Preview Section -->
                                    <div id="preview-area-watermark" class="mt-6">
                                        <div class="flex items-center justify-between w-full p-4 rounded-lg bg-gray-50">
                                            <div class="flex items-center">
                                                <img id="image-preview-watermark" :src="field.watermark_img ? (baseURL + JSON.parse(field.watermark_img).path) : ''" alt="Preview" class="w-20 h-20 mr-4 rounded-lg object-cover">
                                                <div>
                                                    <p id="image-name-watermark" style="max-width: 4rem;" class="font-semibold truncate max-w-xs"></p>
                                                    <p id="image-size-watermark" class="text-gray-500 text-sm"></p>
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-4">
                                                <div id="replace-button-watermark" class="text-primary text-sm font-medium leading-5 cursor-pointer">
                                                    Thay thế
                                                </div>
                                                <div id="remove-button-watermark" class="text-gray-600 cursor-pointer">
                                                    <i data-lucide="x" class="w-6 h-6"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal -->
                                <div id="file-manager-modal-watermark" class="fixed inset-0 z-[99999] hidden overflow-y-auto">
                                    <div class="flex items-center justify-center min-h-screen px-4">
                                        <!-- Backdrop -->
                                        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
                                        
                                        <!-- Modal Content -->
                                        <div class="relative bg-white rounded-lg w-full max-w-7xl !h-[80vh] max-h-[90vh] flex flex-col">
                                            <!-- Modal Header -->
                                            <div class="flex items-center justify-between p-4 border-b">
                                                <h3 class="text-xl font-semibold text-gray-900">Thư viện Media</h3>
                                                <button type="button" class="modal-close text-gray-400 hover:text-gray-500">
                                                    <i data-lucide="x" class="h-6 w-6"></i>
                                                </button>
                                            </div>
                                            
                                            <!-- Modal Body -->
                                            <div class="flex-1 overflow-hidden">
                                                <iframe id="file-manager-iframe-watermark" class="w-full h-full" src="about:blank" frameborder="0"></iframe>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="field.type === 'WYSIWYG'" class="mb-1">
                    <p class="text-gray-600 text-sm"><?php echo Flang::_e('wysiwyg_notice'); ?></p>
                </div>

                <div v-if="field.type === 'Reference'" class="mb-1">
                    <div class="mb-3 relative">
                        <label class="block  text-sm mt-1">
                            <?php echo Flang::_e('choose_post_type_reference'); ?>
                        </label>
                        <select 
                            v-model="field.post_type_reference" required
                            class="mt-0.5 pl-3 pr-10 py-2 block w-full border border-gray-300 rounded-md bg-white 
                                focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        >
                            <option v-for="pt in postTypesList" :value="pt.slug">{{ pt.name }}</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <span class="block  text-sm mt-1">
                            <?php echo Flang::_e('bidirectional_sync'); ?>
                        </span>
                        <div class="radio-type1 flex">
                            <div class="flex items-center">
                                <input 
                                    :id="'bidirectional_sync_true_' + field.id" 
                                    type="radio" 
                                    value="1" 
                                    v-model="field.bidirectional_sync" 
                                    :name="'bidirectional_sync_' + field.id"
                                    class="focus:ring-blue-500 h-4 w-4 text-primary border-gray-300"
                                >
                                <label :for="'bidirectional_sync_true_' + field.id" class="ml-2 block text-sm ">
                                    <?php echo Flang::_e('true'); ?>
                                </label>
                            </div>
                            <div class="flex items-center">
                                <input 
                                    :id="'bidirectional_sync_false_' + field.id" 
                                    type="radio" 
                                    value="0" 
                                    v-model="field.bidirectional_sync" 
                                    :name="'bidirectional_sync_' + field.id"
                                    class="focus:ring-blue-500 h-4 w-4 text-primary border-gray-300"
                                >
                                <label :for="'bidirectional_sync_false_' + field.id" class="ml-2 block text-sm ">
                                    <?php echo Flang::_e('false'); ?>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <span class="block  text-sm mt-1">
                            <?php echo Flang::_e('table_save_data_reference'); ?>
                        </span>
                        <div class="radio-type1 flex">
                            <div class="flex items-center">
                                <input 
                                    :id="'table_save_data_reference_true_' + field.id" 
                                    type="radio" 
                                    value="1" 
                                    v-model="field.table_save_data_reference" 
                                    :name="'table_save_data_reference_' + field.id"
                                    class="focus:ring-blue-500 h-4 w-4 text-primary border-gray-300"
                                >
                                <label :for="'table_save_data_reference_true_' + field.id" class="ml-2 block text-sm ">
                                    <?php echo Flang::_e('true'); ?>
                                </label>
                            </div>
                            <div class="flex items-center">
                                <input 
                                    :id="'table_save_data_reference_false_' + field.id" 
                                    type="radio" 
                                    value="0" 
                                    v-model="field.table_save_data_reference" 
                                    :name="'table_save_data_reference_' + field.id"
                                    class="focus:ring-blue-500 h-4 w-4 text-primary border-gray-300"
                                >
                                <label :for="'table_save_data_reference_false_' + field.id" class="ml-2 block text-sm ">
                                    <?php echo Flang::_e('false'); ?>
                                </label>
                            </div>
                        </div>
                    </div>                    
                    <div class="mb-4">
                        <span class="block  text-sm mt-1">
                            <?php echo Flang::_e('post_status_filter'); ?>
                        </span>
                        <div class="radio-type1 flex">
                            <div class="flex items-center me-4">
                                <input 
                                    :id="'post_status_all_' + field.id" 
                                    type="radio" 
                                    value="all" 
                                    v-model="field.post_status_filter" 
                                    :name="'post_status_filter_' + field.id"
                                    class="focus:ring-blue-500 h-4 w-4 text-primary border-gray-300"
                                >
                                <label :for="'post_status_all_' + field.id" class="ml-2 block text-sm ">
                                    <?php echo Flang::_e('all'); ?>
                                </label>
                            </div>
                            <div class="flex items-center me-4">
                                <input 
                                    :id="'post_status_active_' + field.id" 
                                    type="radio" 
                                    value="active" 
                                    v-model="field.post_status_filter" 
                                    :name="'post_status_filter_' + field.id"
                                    class="focus:ring-blue-500 h-4 w-4 text-primary border-gray-300"
                                >
                                <label :for="'post_status_active_' + field.id" class="ml-2 block text-sm ">
                                    <?php echo Flang::_e('active'); ?>
                                </label>
                            </div>
                            <div class="flex items-center">
                                <input 
                                    :id="'post_status_inactive_' + field.id" 
                                    type="radio" 
                                    value="inactive" 
                                    v-model="field.post_status_filter" 
                                    :name="'post_status_filter_' + field.id"
                                    class="focus:ring-blue-500 h-4 w-4 text-primary border-gray-300"
                                >
                                <label :for="'post_status_inactive_' + field.id" class="ml-2 block text-sm ">
                                    <?php echo Flang::_e('inactive'); ?>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="field floating-label mb-4 relative wrap-post-query-filter">
                        <label :for="'post_query_filter_' + field.id" class="block mb-2 font-medium text-sm leading-5 light:text-gray-900">
                            <?php echo Flang::_e('post_query_filter'); ?>
                        </label>
                        
                        <input
                            v-model="field.post_query_filter"
                            type="text"
                            :id="'post_query_filter_' + field.id"
                            :name="'post_query_filter_' + field.id"
                            placeholder="<?php echo Flang::_e('enter_post_query_filter'); ?>"
                            class="form-control w-full px-3 py-2 border rounded-md text-sm focus:ring-blue-500 focus:border-blue-500 bg-transparent"
                        >
                        <p class="text-sm text-gray-500 mb-2">
                            <?php echo Flang::_e('post_query_filter_notice'); ?>
                        </p>
                    </div>

                    <div class="field floating-label mb-4 relative wrap-post-query-sort">
                        <label :for="'post_query_sort_' + field.id" class="block mb-2 font-medium text-sm leading-5 light:text-gray-900">
                            <?php echo Flang::_e('post_query_sort'); ?>
                        </label>
                        
                        <input
                            v-model="field.post_query_sort"
                            type="text"
                            :id="'post_query_sort_' + field.id"
                            :name="'post_query_sort_' + field.id"
                            placeholder="<?php echo Flang::_e('enter_post_query_sort'); ?>"
                            class="form-control w-full px-3 py-2 border rounded-md text-sm focus:ring-blue-500 focus:border-blue-500 bg-transparent"
                        >
                        <p class="text-sm text-gray-500 mb-2">
                            <?php echo Flang::_e('post_query_sort_notice'); ?>
                        </p>
                    </div>
                </div>

                <div v-if="field.type === 'Repeater'" class="mb-1">
                    <h4 class="font-semibold text-sm mb-1"><?php echo Flang::_e('fields_in_repeater'); ?></h4>
                    <div v-for="(repField, repIndex) in field.fields" :key="repIndex">
                        <field-item
                            :field="repField"
                            :available-field-types="availableFieldTypes"
                            :post-types-list="postTypesList"
                            :option-groups="optionGroups"
                            @remove="removeRepeaterField(repIndex)"
                            :parent-field="field"
                            :index="repIndex"
                            :fields-array="field.fields"
                        ></field-item>
                    </div>
                    <button @click="addRepeaterField" type="button" class="mt-1 px-2 py-1 bg-blue-500 text-white text-sm rounded"><?php echo Flang::_e('add_field_to_repeater'); ?></button>
                </div>
            </div>
        </div>
        `
    });


    // Component chính
    Vue.component('options-form', {
        data() {
            return {
                csrfToken: '<?= $csrf_token ?>',
                listOptions: <?= json_encode($options) ?>,
                availableFieldTypes: [
                    'Text', 'Email', 'Number', 'Password', 'Date', 'DateTime', 'ColorPicker', 'URL', 'OEmbed',
                    'Textarea', 'Boolean', 'Checkbox', 'Radio', 'Select', 'File', 'Image',
                    'WYSIWYG', 'Reference', 'Repeater', 'User', 'Point'
                ],
                postTypesList: <?= json_encode($allPostTypes); ?>,
                optionGroups: <?= json_encode($option_groups); ?> // Thêm dòng này

            };
        },
        mounted() {
            if (!this.listOptions || this.listOptions.length == 0) {
                this.listOptions = [];
                this.addOptionsRow();
            }
        },
        methods: {
            addOptionsRow() {
                if (this.listOptions.length > 0) {
                    this.listOptions[this.listOptions.length - 1].collapsed = true;
                }
                const uniqueId = Date.now(); // Assign unique ID based on current time
                this.listOptions.push({
                    id: uniqueId, // Add the unique ID
                    type: 'Text',
                    label: '',
                    option_group: '',
                    name: '',
                    description: '',
                    status: true,
                    save_file: false,
                    visibility: true,
                    css_class: '',
                    placeholder: '',
                    default_value: '',
                    order: this.listOptions.length + 1,
                    min: null,
                    max: null,
                    options: [],
                    rows: null,
                    allow_types: [],
                    max_file_size: null,
                    multiple: false,
                    multiple_server: false,
                    post_type_reference: null,
                    bidirectional_sync: 1,
                    table_save_data_reference: 0,
                    post_status_filter: 'all',
                    post_query_filter: '',
                    post_query_sort: '',
                    synchronous: true,
                    fields: [],
                    collapsed: false,
                    width_value: 100,
                    width_unit: '%',
                    position: 'left',
                });
            },
            removeOptionsRow(index) {
                this.listOptions.splice(index, 1);
            },

        },
        template: `
    <div>
        <form method="post" action="<?php echo $actionLink; ?>">
            <div id="sticky-action" class="sticky top-header-height bg-theme-cardbg dark:bg-themedark-cardbg" style="z-index: 100">
                <div class="!py-2">
                    <div class="col-span-12 sm:col-span-6 self-center ltr:sm:text-right rtl:sm:text-left my-1">
                        <button type="submit" class="btn btn-primary rounded-md"><?= Flang::_e('submit') ?></button> <button type="reset" class="btn btn-light-secondary rounded-md"><?= Flang::_e('reset') ?></button>
                    </div>
                </div>
            </div>


            <input type="hidden" name="csrf_token" :value="csrfToken">
            <!-- Danh sách Các Options Fields -->
            <div class="mb-3">
                <h2 class="text-lg font-semibold mb-1"><?php echo Flang::_e('option_field'); ?></h2>
                <div v-for="(field, index) in listOptions" :key="index">
                    <field-item
                        :field="field"
                        :available-field-types="availableFieldTypes"
                        :post-types-list="postTypesList"
                        :option-groups="optionGroups"
                        @remove="removeOptionsRow(index)"
                        :parent-field="null"
                        :index="index"
                        :fields-array="listOptions"
                        :field-name="'field[' + index + ']'"
                    ></field-item>
                </div>
            <?php if (!$isEdit): ?>
                <button type="button" @click="addOptionsRow" class="btn btn-sm btn-primary font-bold py-2 px-4 rounded"><?php echo Flang::_e('add_field'); ?></button>
            <?php endif; ?>
                </div>
             <!-- Nút submit -->
            <button type="submit" class="btn btn-primary text-white font-bold py-2 px-4 rounded mb-5"><?php echo Flang::_e('save_options'); ?></button>
            <?php if ($isEdit): ?>
            <a href="<?= $deleteLink; ?>" class="text-red-500 font-bold py-2 px-4  mb-5"><?php echo Flang::_e('Delete'); ?></a>
            <?php endif; ?>
            <!-- Hiển thị JSON của form để kiểm tra -->
            <div class="mb-3">
                <h2 class="text-lg font-semibold mb-1"><?php echo Flang::_e('json_form_data'); ?></h2>
                <pre class="text-xs">{{ JSON.stringify(listOptions, null, 2) }}</pre>
            </div>

            <input type="hidden" name="list_options" :value="JSON.stringify(listOptions)">
        </form>
    </div>
`

    });

    new Vue({
        el: '#app',
    });
</script>
