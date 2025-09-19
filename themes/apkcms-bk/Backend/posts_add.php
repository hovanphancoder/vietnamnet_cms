<?php
namespace System\Libraries;

use System\Libraries\Render;
use System\Libraries\Session;

global $me_info;

$current_user = $me_info['id'];
$languages = isset($posttype['languages']) && is_string($posttype['languages']) ? json_decode($posttype['languages'], true) : [];
$posttype_encode = json_encode($posttype, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
if(!empty($post)) {
    $post_encode = json_encode($post, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
} else {
    $post_encode = '[]';
}
// Lấy danh sách ngôn ngữ từ config
$type = S_GET('type') ?? '';
$isEdit = !empty($post);
$langHasPost = []; // Có thể lấy từ database nếu cần
$currentLang = S_GET('post_lang') ?? APP_LANG_DF;
if($isEdit) {
  $created_at = $post['created_at'];
} else {
  $created_at = '';
}
// Breadcrumbs
$breadcrumbs = array(
  [
      'name' => __('Dashboard'),
      'url' => admin_url('home')
  ],
  [
      'name' => __('Posts'),
      'url' => admin_url('posts')
  ],
  [
      'name' => $isEdit ? __('Edit') : __('Add').' '. $posttype['name'],
      'url' => admin_url('posts/add'),
      'active' => true
  ]
);

// [1] LẤY CÁC THÔNG TIN CHUNG
Render::block('Backend\Header', ['layout'=>'default', 'title' => $isEdit ? __('Edit') : __('Add'), 'breadcrumb' => $breadcrumbs]);
?>

<div class="pc-container">
    <div class="pc-content relative">

    <!-- Header & Description -->
    <div class="flex flex-col gap-4 mb-6">
      <div>
        <h1 class="text-2xl font-bold text-foreground"><?= $isEdit ? __('Edit') : __('Add').' '. $posttype['name'] ?></h1>
        <p class="text-muted-foreground"><?= $isEdit ? __('Edit') : __('Add').' '. $posttype['name'] ?></p>
      </div>

      <!-- Thông báo -->
      <?php if (Session::has_flash('success')): ?>
        <?php Render::block('Backend\\Notification', ['layout' => 'default', 'type' => 'success', 'message' => Session::flash('success')]) ?>
      <?php endif; ?>
      <?php if (Session::has_flash('error')): ?>
        <?php Render::block('Backend\\Notification', ['layout' => 'default', 'type' => 'error', 'message' => Session::flash('error')]) ?>
      <?php endif; ?>
      
      <!-- Validation Errors -->
      <?php if (isset($errors) && !empty($errors)): ?>
        <div class="bg-destructive/10 border border-destructive/20 rounded-lg p-4 mb-4">
          <div class="flex items-start gap-3">
            <i data-lucide="alert-circle" class="h-5 w-5 text-destructive flex-shrink-0 mt-0.5"></i>
            <div class="flex-1">
              <h3 class="text-sm font-semibold text-destructive mb-2"><?= __('Please fix the following errors') ?>:</h3>
              <ul class="space-y-1">
                <?php foreach ($errors as $field => $fieldErrors): ?>
                  <?php if (is_array($fieldErrors)): ?>
                    <?php foreach ($fieldErrors as $error): ?>
                      <li class="text-sm text-destructive/80 flex items-start gap-2">
                        <span class="text-destructive">•</span>
                        <span><strong><?= ucfirst(str_replace('_', ' ', $field)) ?>:</strong> <?= __($error) ?></span>
                      </li>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <li class="text-sm text-destructive/80 flex items-start gap-2">
                      <span class="text-destructive">•</span>
                      <span><?= __($fieldErrors) ?></span>
                    </li>
                  <?php endif; ?>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- PHP sẽ tạo form wrapper này -->
    <form id="acf-form" method="post" action="" enctype="multipart/form-data">
        <!-- PHP sẽ thêm các hidden fields cần thiết -->
        <input type="hidden" name="post_id" value="" />
        <input type="hidden" name="type" value="<?= S_GET('type') ?? '' ?>" />
        <!-- <input type="hidden" name="lang" value="<?= $currentLang ?>" /> -->

        <!-- POST CONTROLS BAR -->
        <div class="bg-card rounded-xl mb-4 border">
            <!-- LANGUAGE SWITCHER -->
            <div class="flex items-center gap-2 m-4">
                <!-- Ngôn ngữ hiện tại -->
                <div class="inline-flex items-center px-3 py-2 rounded-md bg-primary text-primary-foreground shadow-sm">
                    <span class="text-sm font-medium uppercase"><?= $currentLang ?></span>
                    <span class="ml-2">
                        <?php if($isEdit) { ?>
                        <i data-lucide="check-circle" class="h-4 w-4"></i>
                        <?php } else { ?>
                        <i data-lucide="plus" class="h-4 w-4"></i>
                        <?php } ?>
                    </span>
                </div>

                <!-- Các ngôn ngữ khác -->
                <?php foreach($languages as $lang): 
                    if($lang !== $currentLang):
                        if($isEdit) {
                            $langUrlAction = admin_url('posts/clone/' . $post['id']) 
                                            . '?type=' . $type
                                            . '&post_lang=' . $lang
                                            . '&oldpost_lang=' . $currentLang;
                        } else {
                            $langUrlAction = admin_url('posts/add', '/'.APP_LANG)
                                            . '?type=' . $type
                                            . '&post_lang=' . $lang;
                        }
                    ?>
                <a href="<?= $langUrlAction; ?>" 
                    class="inline-flex items-center px-3 py-2 rounded-md border border-input bg-background hover:bg-accent hover:text-accent-foreground transition-colors">
                    <span class="text-sm font-medium uppercase"><?= $lang ?></span>
                    <span class="ml-2">
                        <?php if( $isEdit && in_array($lang, $langHasPost)) { ?>
                        <i data-lucide="edit" class="h-4 w-4"></i>
                        <?php } else { ?>
                        <i data-lucide="plus" class="h-4 w-4"></i>
                        <?php }?>
                    </span>
                </a>
                <?php 
                    endif;
                    endforeach; ?>
            </div> 

            <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between p-4 pt-0">
                <!-- STATUS & TIME SECTION -->
                <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center flex-1 w-full lg:w-auto">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 w-full sm:w-auto">
                        <label for="last-updated-input" class="text-sm font-medium text-muted-foreground whitespace-nowrap"><?= __('Created at') ?>:</label>
                        <input id="last-updated-input" name="created_at" type="datetime-local" 
                               step="1"
                               class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 min-w-[180px]" 
                               value="<?= $created_at ?? '' ?>"
                               />
                        <!-- lần cuối cập nhật text note nhỏ-->
                        <p class="text-sm text-muted-foreground whitespace-nowrap"><?= __('Last updated') ?>: <?= $created_at ?? '' ?></p>
                    </div>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 w-full sm:w-auto">
                        <label for="post-status" class="text-sm font-medium text-muted-foreground whitespace-nowrap"><?= __('Post status') ?>:</label>
                        <select id="post-status" name="status" 
                                class="flex h-10 w-full items-center rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 min-w-[120px]">
                            <option value="active" <?= isset($post['status']) && $post['status'] == 'active' ? 'selected' : '' ?>><?= __('Published') ?></option>
                            <option value="inactive" <?= isset($post['status']) && $post['status'] == 'inactive' ? 'selected' : '' ?>><?= __('Draft') ?></option>
                        </select>
                    </div>
                </div>
                <!-- PUBLISH BUTTON -->
                <div class="flex items-center">
                    <button type="submit" id="publish-btn" 
                            class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2">
                        <i data-lucide="send" class="h-4 w-4 mr-2"></i>
                        <?= __('Publish') ?>
                    </button>
                </div>
            </div>
        </div>

    <style>
        /* Custom styles for better spacing */
        .field-wrapper {
            margin-bottom: 1rem;
        }
        
        .field-wrapper:last-child {
            margin-bottom: 0;
        }
        
        /* Custom styles for better spacing */
        .field-wrapper {
            margin-bottom: 1rem;
        }
        
        .field-wrapper:last-child {
            margin-bottom: 0;
        }
        
        /* Editor.js styles */
        .codex-editor {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }
        
        .codex-editor__redactor {
            padding: 12px;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        /* Loading spinner */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* DND Kit specific styles */
        .repeater-item {
            transition: all 0.2s ease;
        }
        
        .repeater-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .repeater-handle {
            cursor: grab;
        }
        
        .repeater-handle:active {
            cursor: grabbing;
        }
        
        .flexible-layout-selector {
            max-height: 300px;
            overflow-y: auto;
        }

        /* DND Kit drag overlay styles */
        .dnd-overlay {
            opacity: 0.8;
            transform: rotate(5deg);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
    </style>
  <script type="module" crossorigin src="<?= theme_assets('js/posts_add.js', 'Backend') ?>"></script>
  <link rel="stylesheet" crossorigin href="<?= theme_assets('css/posts_add.css', 'Backend') ?>">

  <style>
       .ce-block__content {
        max-width: inherit;
       }
      /* Form actions đã được chuyển lên POST CONTROLS BAR */
    </style>
        <!-- React app container - Chỉ render fields -->
        <div id="initial-loading" style="display: flex; justify-content: center; align-items: center; height: 200px; flex-direction: column;">
            <div class="loading-spinner"></div>
            <p style="margin-top: 10px; color: #666;"><?= __('Loading ACF Form Builder...') ?></p>
            <p style="margin-top: 5px; color: #999; font-size: 12px;" id="initial-loading-content"><?= __('Initializing drag & drop components...') ?></p>
        </div>
        <div id="root">
            <!-- Initial loading state -->
            
        </div>
        
        <!-- Form actions đã được chuyển lên POST CONTROLS BAR -->
    </form>
    <!-- PHP sẽ inject data vào đây -->
    <script>
        // PHP sẽ truyền data vào window object
        window.ACF_DATA = {
        "lang": "<?= $currentLang ?>",
        "current_user": <?= $current_user; ?>,
        "inputConfig": {
          "border": {
            "width": 1,
            "style": "solid",
            "color": "#e2e8f0",
            "radius": 6
          },
          "background": {
            "color": "#ffffff",
            "hover": "#f8fafc",
            "focus": "#ffffff"
          },
          "text": {
            "color": "#1e293b",
            "fontSize": 14,
            "fontWeight": "normal"
          },
          "spacing": {
            "padding": { "x": 12, "y": 8 },
            "margin": { "x": 0, "y": 2 },
            "fieldGap": 16
          },
          "size": {
            "height": 38,
            "minHeight": 38
          },
          "wrapper": {
            "enabled": true,
            "border": {
              "width": 1,
              "style": "solid",
              "color": "#f1f5f9",
              "radius": 6
            },
            "background": "#ffffff",
            "padding": { "x": 12, "y": 12 },
            "margin": { "x": 0, "y": 8 }
          },
          "label": {
            "color": "#374151",
            "fontSize": 14,
            "fontWeight": "medium",
            "marginBottom": 4
          },
          "effects": {
            "transition": "all 0.2s ease",
            "focusRing": true,
            "hoverEnabled": true
          },
          "outerWrapper": {
            "enabled": true,
            "border": {
              "width": 0,
              "style": "solid",
              "color": "transparent",
              "radius": 8
            },
            "background": "#fafafa",
            "padding": { "x": 0, "y": 0 },
            "margin": { "x": 0, "y": 0 },
            "shadow": true
          }
        },
        "postType": <?= $posttype_encode; ?>,
        "postEdit": <?= $post_encode; ?>
      };

        // Update last updated time
        //document.getElementById('last-updated').textContent = new Date().toLocaleString();
        // Handle form submission
        //document.getElementById('save-draft-btn').addEventListener('click', function() {
        //});
    </script>


    </div>
</div>

<?php Render::block('Backend\Footer'); ?>

